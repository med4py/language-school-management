<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$baseUrl = rtrim(getenv('QA_BASE_URL') ?: 'http://127.0.0.1:8000', '/');
$db = Database::connect();
$token = 'QA-' . bin2hex(random_bytes(5));
$temporaryStudentLogin = strtolower($token);
$temporaryCourseNames = [$token . '-A', $token . '-B', $token . '-C'];

register_shutdown_function(static function () use ($db, $token, $temporaryStudentLogin): void {
    try {
        $deleteCourses = $db->prepare('DELETE FROM cours WHERE langue LIKE :prefix');
        $deleteCourses->execute(['prefix' => $token . '-%']);
        $deleteStudent = $db->prepare('DELETE FROM etudiants WHERE login = :login');
        $deleteStudent->execute(['login' => $temporaryStudentLogin]);
    } catch (Throwable $exception) {
        error_log('QA cleanup failed: ' . $exception->getMessage());
    }
});

final class QaHttpClient
{
    private string $cookie = '';

    public function __construct(private string $baseUrl)
    {
    }

    public function request(string $method, string $path, array $data = []): array
    {
        $headers = ['Accept: text/html'];
        if ($this->cookie !== '') {
            $headers[] = 'Cookie: ' . $this->cookie;
        }
        $options = [
            'method' => $method,
            'header' => $headers,
            'ignore_errors' => true,
            'timeout' => 10,
            'follow_location' => 0,
            'max_redirects' => 0,
        ];
        if ($method === 'POST') {
            $options['header'][] = 'Content-Type: application/x-www-form-urlencoded';
            $options['content'] = http_build_query($data);
        }
        $context = stream_context_create(['http' => $options]);
        $body = file_get_contents($this->baseUrl . '/' . ltrim($path, '/'), false, $context);
        $responseHeaders = $http_response_header ?? [];
        if ($body === false || !$responseHeaders) {
            throw new RuntimeException("HTTP request failed: {$method} {$path}");
        }
        foreach ($responseHeaders as $header) {
            if (preg_match('/^Set-Cookie:\s*([^;]+)/i', $header, $match)) {
                $this->cookie = $match[1];
            }
        }
        preg_match('/HTTP\/\S+\s+(\d+)/', $responseHeaders[0], $statusMatch);
        return [
            'status' => isset($statusMatch[1]) ? (int) $statusMatch[1] : 0,
            'headers' => $responseHeaders,
            'body' => $body,
        ];
    }

    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    public function post(string $action, array $data = [], ?string $csrf = null): array
    {
        if ($csrf === null) {
            $home = $this->get('index.php');
            $csrf = self::csrf($home['body']);
        }
        return $this->request('POST', 'index.php', ['csrf_token' => $csrf, 'action' => $action] + $data);
    }

    public function login(string $page, string $action, string $login, string $password): array
    {
        $form = $this->get('index.php?page=' . $page);
        return $this->post($action, [
            'login' => $login,
            'password' => $password,
        ], self::csrf($form['body']));
    }

    public function follow(array $response): array
    {
        $location = self::headerValue($response['headers'], 'Location');
        if ($response['status'] < 300 || $response['status'] >= 400 || $location === null) {
            return $response;
        }
        $path = parse_url($location, PHP_URL_PATH);
        $query = parse_url($location, PHP_URL_QUERY);
        return $this->get(($path !== null && $path !== '' ? $path : 'index.php') . ($query ? '?' . $query : ''));
    }

    public static function csrf(string $body): string
    {
        if (!preg_match('/name="csrf_token"\s+value="([a-f0-9]+)"/', $body, $match)) {
            throw new RuntimeException('CSRF token was not present in the page.');
        }
        return $match[1];
    }

    public static function headerValue(array $headers, string $name): ?string
    {
        foreach ($headers as $header) {
            if (stripos($header, $name . ':') === 0) {
                return trim(substr($header, strlen($name) + 1));
            }
        }
        return null;
    }
}

$checks = 0;
$escapeHtml = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$check = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
    $checks++;
    echo "PASS: {$message}\n";
};

$cleanPage = static function (array $response, string $label) use ($check): void {
    $check($response['status'] === 200, $label . ' returns HTTP 200');
    $check(!preg_match('/(Fatal error|Warning:|Notice:|PDOException|Stack trace:)/i', $response['body']), $label . ' has no exposed PHP/database errors');
};

try {
    $extensionCheck = extension_loaded('pdo_mysql') && extension_loaded('mbstring');
    $check($extensionCheck, 'PDO MySQL and mbstring extensions are loaded');
    $tables = $db->query(
        "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'"
    )->fetchAll(PDO::FETCH_COLUMN);
    sort($tables);
    $check($tables === ['admins', 'cours', 'etudiants', 'inscriptions'], 'all four application tables exist');

    $constraints = (int) $db->query(
        "SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inscriptions' AND REFERENCED_TABLE_NAME IS NOT NULL"
    )->fetchColumn();
    $check($constraints === 2, 'registration foreign keys exist');
    $duplicateIndex = (int) $db->query(
        "SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inscriptions'
         AND INDEX_NAME = 'uq_etudiant_cours' AND NON_UNIQUE = 0"
    )->fetchColumn();
    $check($duplicateIndex === 2, 'unique student/course registration index exists');
    $courseDateIndex = (int) $db->query(
        "SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cours' AND INDEX_NAME = 'idx_cours_date_debut'"
    )->fetchColumn();
    $check($courseDateIndex === 1, 'course start-date index exists');
    $studentCount = (int) $db->query('SELECT COUNT(*) FROM etudiants')->fetchColumn();
    $adminCount = (int) $db->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    $courseCount = (int) $db->query('SELECT COUNT(*) FROM cours')->fetchColumn();
    $check($studentCount > 0 && $adminCount > 0 && $courseCount > 0, 'student, admin, and sample course records exist');
    $check((bool) $db->query("SELECT 1 FROM etudiants WHERE login = 'student'")->fetchColumn(), 'demo student exists');
    $check((bool) $db->query("SELECT 1 FROM admins WHERE login = 'admin'")->fetchColumn(), 'demo admin exists');

    $homepageClient = new QaHttpClient($baseUrl);
    $home = $homepageClient->get('/');
    $cleanPage($home, 'homepage');
    $check(str_contains($home['body'], 'Login Étudiant') && str_contains($home['body'], 'Login Admin'), 'public navigation exposes both login links');
    $check(str_contains($home['body'], 'css/style.css') && str_contains($home['body'], 'bootstrap@5.3.3'), 'homepage includes Bootstrap and project CSS');
    $css = $homepageClient->get('css/style.css');
    $check($css['status'] === 200 && str_contains($css['body'], '.public-navbar'), 'project stylesheet is served');
    $oldDbTest = $homepageClient->get('test-db.php');
    $check(!str_contains($oldDbTest['body'], 'Connexion MySQL réussie'), 'database connectivity output is not publicly exposed');
    $upcoming = $db->query(
        'SELECT langue FROM cours WHERE dateDebut >= CURRENT_DATE ORDER BY dateDebut DESC'
    )->fetchAll(PDO::FETCH_COLUMN);
    $lastPosition = -1;
    foreach ($upcoming as $language) {
        $position = strpos($home['body'], '<strong>' . $escapeHtml($language) . '</strong>');
        $check($position !== false && $position > $lastPosition, 'upcoming courses are displayed in descending date order');
        $lastPosition = $position;
    }

    foreach (['student-profile', 'student-courses', 'student-registrations'] as $page) {
        $response = $homepageClient->get('index.php?page=' . $page);
        $check($response['status'] === 302 && str_contains((string) QaHttpClient::headerValue($response['headers'], 'Location'), 'student-login'), $page . ' is protected for logged-out visitors');
    }
    foreach (['admin-dashboard', 'admin-courses', 'admin-students', 'admin-registrations', 'admin-pending'] as $page) {
        $response = $homepageClient->get('index.php?page=' . $page);
        $check($response['status'] === 302 && str_contains((string) QaHttpClient::headerValue($response['headers'], 'Location'), 'admin-login'), $page . ' is protected for logged-out visitors');
    }
    $unknownPage = $homepageClient->get('index.php?page=unknown-route');
    $cleanPage($unknownPage, 'unknown page fallback');
    $malformedPage = $homepageClient->get('index.php?page%5B%5D=admin-dashboard');
    $cleanPage($malformedPage, 'malformed page parameter');

    $originalStudent = $db->query(
        "SELECT idEtudiant, cin, nom, email, tel FROM etudiants WHERE login = 'student'"
    )->fetch();
    $check(is_array($originalStudent), 'demo student profile can be loaded');
    $temporaryStudent = $db->prepare(
        'INSERT INTO etudiants (cin, nom, email, tel, login, password)
         VALUES (:cin, :nom, :email, :tel, :login, :password)'
    );
    $temporaryStudent->execute([
        'cin' => $token,
        'nom' => 'QA Temporary Student',
        'email' => strtolower($token) . '@example.test',
        'tel' => '0600000000',
        'login' => $temporaryStudentLogin,
        'password' => password_hash('Temporary123!', PASSWORD_DEFAULT),
    ]);

    $studentClient = new QaHttpClient($baseUrl);
    foreach ([['unknown-user', 'bad-password'], ["' OR '1'='1", 'Student123!'], ['', 'Student123!'], ['student', '']] as [$login, $password]) {
        $failed = $studentClient->follow($studentClient->login('student-login', 'student-login', $login, $password));
        $expectedMessage = $login === '' || $password === '' ? 'Veuillez saisir' : 'incorrect';
        $check($failed['status'] === 200 && str_contains($failed['body'], $expectedMessage), 'invalid student credentials are rejected cleanly');
        $protected = $studentClient->get('index.php?page=student-profile');
        $check($protected['status'] === 302, 'invalid student credentials do not authenticate');
    }
    $studentLogin = $studentClient->login('student-login', 'student-login', 'student', 'Student123!');
    $check($studentLogin['status'] === 302 && str_contains((string) QaHttpClient::headerValue($studentLogin['headers'], 'Location'), 'student-profile'), 'valid student login redirects to profile');
    $profile = $studentClient->get('index.php?page=student-profile');
    $cleanPage($profile, 'student profile');
    $check(str_contains($profile['body'], $escapeHtml($originalStudent['nom'])) && str_contains($profile['body'], $escapeHtml($originalStudent['cin'])) && str_contains($profile['body'], $escapeHtml($originalStudent['email'])) && str_contains($profile['body'], $escapeHtml($originalStudent['tel'])), 'student profile displays name, CIN, email, and phone');
    $check(str_contains($profile['body'], 'Mes inscriptions') && str_contains($profile['body'], 'Déconnexion'), 'student navigation appears after login');
    $adminPageDenied = $studentClient->get('index.php?page=admin-dashboard');
    $check($adminPageDenied['status'] === 302 && str_contains((string) QaHttpClient::headerValue($adminPageDenied['headers'], 'Location'), 'admin-login'), 'student session cannot access admin routes');

    $profileStudentStatement = $db->prepare('SELECT idEtudiant, cin, nom, email, tel FROM etudiants WHERE login = :login');
    $profileStudentStatement->execute(['login' => $temporaryStudentLogin]);
    $profileStudent = $profileStudentStatement->fetch();
    $profileClient = new QaHttpClient($baseUrl);
    $profileClient->login('student-login', 'student-login', $temporaryStudentLogin, 'Temporary123!');
    $profile = $profileClient->get('index.php?page=student-profile');
    $profileCsrf = QaHttpClient::csrf($profile['body']);
    $validProfile = [
        'nom' => $profileStudent['nom'],
        'cin' => $profileStudent['cin'],
        'email' => $profileStudent['email'],
        'tel' => $profileStudent['tel'],
    ];
    $profileSave = $profileClient->post('student-profile-save', $validProfile, $profileCsrf);
    $check($profileSave['status'] === 302, 'valid student profile update is accepted');
    $invalidProfile = $profileClient->post('student-profile-save', array_replace($validProfile, ['nom' => '']));
    $check(str_contains($profileClient->follow($invalidProfile)['body'], 'Vérifiez les champs'), 'empty student name is rejected');
    $invalidEmail = $profileClient->post('student-profile-save', [
        'nom' => $profileStudent['nom'], 'cin' => $profileStudent['cin'], 'email' => 'not-an-email', 'tel' => $profileStudent['tel'],
    ]);
    $check(str_contains($profileClient->follow($invalidEmail)['body'], 'Vérifiez les champs'), 'malformed student email is rejected');
    $duplicateEmail = $profileClient->post('student-profile-save', [
        'nom' => $profileStudent['nom'], 'cin' => $profileStudent['cin'], 'email' => $originalStudent['email'], 'tel' => $profileStudent['tel'],
    ]);
    $check(str_contains($profileClient->follow($duplicateEmail)['body'], 'déjà utilisé'), 'duplicate student email is rejected');
    $xssName = '<script>alert(1)</script>';
    $xssSave = $profileClient->post('student-profile-save', [
        'nom' => $xssName, 'cin' => $profileStudent['cin'], 'email' => $profileStudent['email'], 'tel' => $profileStudent['tel'],
    ]);
    $check($xssSave['status'] === 302, 'profile accepts text requiring HTML escaping');
    $xssProfile = $profileClient->get('index.php?page=student-profile');
    $check(str_contains($xssProfile['body'], '&lt;script&gt;alert(1)&lt;/script&gt;') && !str_contains($xssProfile['body'], $xssName), 'profile output escapes script content');
    $restoreProfile = $profileClient->post('student-profile-save', $validProfile);
    $check($restoreProfile['status'] === 302, 'temporary profile restored after XSS test');
    $malformedProfile = $profileClient->post('student-profile-save', [
        'nom' => ['array-value'], 'cin' => [], 'email' => 'not-an-email', 'tel' => [],
    ]);
    $malformedProfileResult = $profileClient->follow($malformedProfile);
    $check(!preg_match('/(Fatal error|Warning:|Notice:|PDOException)/i', $malformedProfileResult['body']), 'array-valued profile fields fail safely without PHP warnings');

    $malformedLoginForm = $homepageClient->get('index.php?page=student-login');
    $malformedLogin = $homepageClient->post(
        'student-login',
        ['login' => ['student'], 'password' => ['Student123!']],
        QaHttpClient::csrf($malformedLoginForm['body'])
    );
    $malformedLoginResult = $homepageClient->follow($malformedLogin);
    $check(!preg_match('/(Fatal error|Warning:|Notice:|PDOException)/i', $malformedLoginResult['body']), 'array-valued login fields fail safely without PHP warnings');

    $adminClient = new QaHttpClient($baseUrl);
    foreach ([['admin', 'wrong'], ['unknown-admin', 'Admin123!'], ['admin', '']] as [$login, $password]) {
        $failed = $adminClient->follow($adminClient->login('admin-login', 'admin-login', $login, $password));
        $expectedMessage = $login === '' || $password === '' ? 'Veuillez saisir' : 'incorrect';
        $check($failed['status'] === 200 && str_contains($failed['body'], $expectedMessage), 'invalid admin credentials are rejected cleanly');
    }
    $adminLogin = $adminClient->login('admin-login', 'admin-login', 'admin@example.com', 'Admin123!');
    $check($adminLogin['status'] === 302 && str_contains((string) QaHttpClient::headerValue($adminLogin['headers'], 'Location'), 'admin-dashboard'), 'admin can log in using email');
    $adminByLoginClient = new QaHttpClient($baseUrl);
    $adminByLogin = $adminByLoginClient->login('admin-login', 'admin-login', 'admin', 'Admin123!');
    $check($adminByLogin['status'] === 302, 'admin can also log in using login');
    $dashboard = $adminClient->get('index.php?page=admin-dashboard');
    $cleanPage($dashboard, 'admin dashboard');
    $check(str_contains($dashboard['body'], 'Cours') && str_contains($dashboard['body'], 'Inscriptions payées'), 'dashboard displays its statistics cards');
    $studentRouteDenied = $adminClient->get('index.php?page=student-profile');
    $check($studentRouteDenied['status'] === 302 && str_contains((string) QaHttpClient::headerValue($studentRouteDenied['headers'], 'Location'), 'student-login'), 'admin session cannot access student routes');

    foreach (['admin-courses', 'admin-students', 'admin-registrations', 'admin-pending'] as $page) {
        $response = $adminClient->get('index.php?page=' . $page);
        $cleanPage($response, $page);
    }
    $dashboardStats = [
        (int) $db->query('SELECT COUNT(*) FROM etudiants')->fetchColumn(),
        (int) $db->query('SELECT COUNT(*) FROM cours')->fetchColumn(),
        (int) $db->query('SELECT COUNT(*) FROM inscriptions')->fetchColumn(),
        (int) $db->query("SELECT COUNT(*) FROM inscriptions WHERE paiement = 'payé'")->fetchColumn(),
    ];
    preg_match_all('/class="stat-value">(\d+)</', $dashboard['body'], $statMatches);
    $check(array_map('intval', $statMatches[1] ?? []) === $dashboardStats, 'dashboard statistics match database counts');

    $courseDefinitions = [
        [$temporaryCourseNames[0], '2099-10-01', '2099-11-01', 3],
        [$temporaryCourseNames[1], '2099-11-01', '2099-12-01', 1],
        [$temporaryCourseNames[2], '2099-12-01', '2099-12-15', 1],
    ];
    foreach ($courseDefinitions as $index => [$language, $startDate, $endDate, $capacity]) {
        $adminCourses = $adminClient->get('index.php?page=admin-courses');
        $csrf = QaHttpClient::csrf($adminCourses['body']);
        $response = $adminClient->post('admin-course-save', [
            'langue' => $language,
            'niveau' => 'QA',
            'prix' => '100.00',
            'dateDebut' => $startDate,
            'dateFin' => $endDate,
            'places_total' => (string) $capacity,
        ], $csrf);
        $check($response['status'] === 302, 'admin creates test course ' . ($index + 1));
    }
    $courseIdsStatement = $db->prepare('SELECT idCours, langue, places_total, places_restantes FROM cours WHERE langue LIKE :prefix ORDER BY langue');
    $courseIdsStatement->execute(['prefix' => $token . '-%']);
    $testCourses = $courseIdsStatement->fetchAll();
    $check(count($testCourses) === 3, 'created courses are stored in the database');
    $courseByName = [];
    foreach ($testCourses as $testCourse) {
        $courseByName[$testCourse['langue']] = $testCourse;
    }
    $homeWithQaCourses = $homepageClient->get('/');
    preg_match_all('/<td><span class="table-language"><i[^>]*>.*?<\/i><strong>([^<]+)<\/strong>/s', $homeWithQaCourses['body'], $courseTitles);
    $expectedCourseTitles = $db->query(
        'SELECT langue FROM cours WHERE dateDebut >= CURRENT_DATE ORDER BY dateDebut DESC'
    )->fetchAll(PDO::FETCH_COLUMN);
    $check(array_map('html_entity_decode', $courseTitles[1] ?? []) === $expectedCourseTitles, 'homepage renders every upcoming course in exact database order');
    $check(str_contains($homeWithQaCourses['body'], 'Disponible'), 'homepage displays available course badge');

    $invalidCourseResponse = $adminClient->post('admin-course-save', [
        'langue' => [$token . '-INVALID'], 'niveau' => 'QA', 'prix' => '-10',
        'dateDebut' => '2099-14-40', 'dateFin' => '2099-01-01', 'places_total' => '0',
    ]);
    $invalidCourseResult = $adminClient->follow($invalidCourseResponse);
    $check(str_contains($invalidCourseResult['body'], 'Vérifiez les informations du cours'), 'invalid course data is rejected');
    $check(!preg_match('/(Fatal error|Warning:|Notice:|PDOException)/i', $invalidCourseResult['body']), 'array-valued course fields fail safely without PHP warnings');
    $missingCsrfResponse = $adminClient->request('POST', 'index.php', [
        'action' => 'admin-course-save', 'langue' => $token . '-CSRF', 'niveau' => 'QA', 'prix' => '1',
        'dateDebut' => '2099-01-01', 'dateFin' => '2099-01-02', 'places_total' => '1',
    ]);
    $check($missingCsrfResponse['status'] === 302, 'missing CSRF token is rejected');
    $rejectedCourses = $db->prepare('SELECT COUNT(*) FROM cours WHERE langue IN (:invalid, :csrf)');
    $rejectedCourses->execute(['invalid' => $token . '-INVALID', 'csrf' => $token . '-CSRF']);
    $check((int) $rejectedCourses->fetchColumn() === 0, 'rejected course posts do not change database');

    $studentClient->get('index.php?page=student-courses');
    foreach ($temporaryCourseNames as $language) {
        $check(str_contains($studentClient->get('index.php?page=student-courses')['body'], $escapeHtml($language)), 'student course page lists ' . $language);
    }
    $firstCourseId = (int) $courseByName[$temporaryCourseNames[0]]['idCours'];
    $secondCourseId = (int) $courseByName[$temporaryCourseNames[1]]['idCours'];
    $thirdCourseId = (int) $courseByName[$temporaryCourseNames[2]]['idCours'];

    $studentCoursePage = $studentClient->get('index.php?page=student-courses');
    $studentCsrf = QaHttpClient::csrf($studentCoursePage['body']);
    $enroll = $studentClient->post('student-enroll', ['course_id' => (string) $firstCourseId], $studentCsrf);
    $check($enroll['status'] === 302, 'student enrolls into available course');
    $registration = $db->prepare('SELECT idInscription, statut, paiement FROM inscriptions WHERE idEtudiant = :student AND idCours = :course');
    $registration->execute(['student' => (int) $originalStudent['idEtudiant'], 'course' => $firstCourseId]);
    $firstRegistration = $registration->fetch();
    $check($firstRegistration && $firstRegistration['statut'] === 'en attente' && $firstRegistration['paiement'] === 'non payé', 'new enrollment has expected pending and unpaid statuses');
    $check((int) $db->query('SELECT places_restantes FROM cours WHERE idCours = ' . $firstCourseId)->fetchColumn() === 2, 'enrollment decreases available seats exactly once');
    $duplicate = $studentClient->post('student-enroll', ['course_id' => (string) $firstCourseId]);
    $check(str_contains($studentClient->follow($duplicate)['body'], 'déjà une inscription'), 'duplicate enrollment is rejected');
    $check((int) $db->query('SELECT places_restantes FROM cours WHERE idCours = ' . $firstCourseId)->fetchColumn() === 2, 'duplicate enrollment does not consume another seat');
    $nonexistent = $studentClient->post('student-enroll', ['course_id' => '999999999']);
    $check($nonexistent['status'] === 302, 'nonexistent course enrollment is rejected');
    $invalidId = $studentClient->post('student-enroll', ['course_id' => 'abc']);
    $check($invalidId['status'] === 302, 'invalid course ID is rejected');

    $secondClient = new QaHttpClient($baseUrl);
    $secondLogin = $secondClient->login('student-login', 'student-login', $temporaryStudentLogin, 'Temporary123!');
    $check($secondLogin['status'] === 302, 'temporary second student can authenticate');
    $secondEnroll = $secondClient->post('student-enroll', ['course_id' => (string) $firstCourseId]);
    $check($secondEnroll['status'] === 302, 'second student can reserve a remaining seat');
    $check((int) $db->query('SELECT places_restantes FROM cours WHERE idCours = ' . $firstCourseId)->fetchColumn() === 1, 'second enrollment decrements seats exactly once');
    $reduceCapacity = $adminClient->post('admin-course-save', [
        'course_id' => (string) $firstCourseId, 'langue' => $temporaryCourseNames[0], 'niveau' => 'QA',
        'prix' => '100.00', 'dateDebut' => '2099-10-01', 'dateFin' => '2099-11-01', 'places_total' => '1',
    ]);
    $check(str_contains($adminClient->follow($reduceCapacity)['body'], 'places déjà réservées'), 'course capacity cannot be reduced below reserved seats');
    $check((int) $db->query('SELECT places_total FROM cours WHERE idCours = ' . $firstCourseId)->fetchColumn() === 3, 'rejected capacity edit leaves course unchanged');

    $fullEnroll = $studentClient->post('student-enroll', ['course_id' => (string) $secondCourseId]);
    $check($fullEnroll['status'] === 302, 'student can reserve last available seat in a course');
    $fullAgain = $secondClient->post('student-enroll', ['course_id' => (string) $secondCourseId]);
    $check(str_contains($secondClient->follow($fullAgain)['body'], 'complet'), 'full course rejects another student');
    $check((int) $db->query('SELECT places_restantes FROM cours WHERE idCours = ' . $secondCourseId)->fetchColumn() === 0, 'full course never decrements below zero');
    $homeWithFullCourse = $homepageClient->get('/');
    $check(str_contains($homeWithFullCourse['body'], 'Complet'), 'homepage displays full course badge');

    $myRegistrations = $studentClient->get('index.php?page=student-registrations');
    $cleanPage($myRegistrations, 'student registrations');
    $check(str_contains($myRegistrations['body'], 'en attente') && str_contains($myRegistrations['body'], 'non payé'), 'student registrations show status and payment');
    $check(str_contains($myRegistrations['body'], 'Supprimer'), 'pending registrations expose delete action');
    $crossDelete = $secondClient->post('student-registration-delete', ['registration_id' => (string) $firstRegistration['idInscription']]);
    $check(str_contains($secondClient->follow($crossDelete)['body'], 'Seules vos demandes'), 'student cannot delete another student registration');
    $invalidDelete = $studentClient->post('student-registration-delete', ['registration_id' => '999999999']);
    $check(str_contains($studentClient->follow($invalidDelete)['body'], 'Seules vos demandes'), 'invalid registration deletion is rejected');

    $adminRegistrationPage = $adminClient->get('index.php?page=admin-registrations');
    $check(str_contains($adminRegistrationPage['body'], $temporaryCourseNames[0]), 'admin registration list displays student course');
    $pendingPage = $adminClient->get('index.php?page=admin-pending');
    $check(str_contains($pendingPage['body'], $temporaryCourseNames[0]), 'pending page includes pending registration');
    $pendingQueryCount = (int) $db->query("SELECT COUNT(*) FROM inscriptions WHERE statut = 'en attente'")->fetchColumn();
    preg_match('/<tbody>(.*?)<\/tbody>/s', $pendingPage['body'], $pendingTable);
    preg_match_all('/<tr>/', $pendingTable[1] ?? '', $pendingRows);
    $check(count($pendingRows[0]) === $pendingQueryCount, 'pending page contains exactly pending database records');
    $confirm = $adminClient->post('admin-registration-confirm', ['registration_id' => (string) $firstRegistration['idInscription']]);
    $check($confirm['status'] === 302, 'admin confirms pending registration');
    $repeatConfirm = $adminClient->follow($adminClient->post('admin-registration-confirm', ['registration_id' => (string) $firstRegistration['idInscription']]));
    $check(str_contains($repeatConfirm['body'], 'Seules les demandes en attente'), 'repeated confirmation is rejected');
    $paid = $adminClient->post('admin-registration-paid', ['registration_id' => (string) $firstRegistration['idInscription']]);
    $check($paid['status'] === 302, 'admin marks enrollment paid');
    $cancel = $adminClient->post('admin-registration-cancel', ['registration_id' => (string) $firstRegistration['idInscription']]);
    $check($cancel['status'] === 302, 'admin cancels enrollment');
    $repeatCancel = $adminClient->follow($adminClient->post('admin-registration-cancel', ['registration_id' => (string) $firstRegistration['idInscription']]));
    $check(str_contains($repeatCancel['body'], 'déjà annulée'), 'repeated cancellation is rejected');
    $invalidPaid = $adminClient->follow($adminClient->post('admin-registration-paid', ['registration_id' => '999999999']));
    $check(str_contains($invalidPaid['body'], 'introuvable'), 'mark-paid action rejects nonexistent registration');
    $finalRegistration = $db->prepare('SELECT statut, paiement FROM inscriptions WHERE idInscription = :id');
    $finalRegistration->execute(['id' => $firstRegistration['idInscription']]);
    $finalStatus = $finalRegistration->fetch();
    $check($finalStatus['statut'] === 'annulé' && $finalStatus['paiement'] === 'payé', 'admin actions persist status and payment');
    $check((int) $db->query('SELECT places_restantes FROM cours WHERE idCours = ' . $firstCourseId)->fetchColumn() === 2, 'cancellation restores one reserved place');
    $studentRegistrationsAfterCancel = $studentClient->get('index.php?page=student-registrations');
    $check(!preg_match('/<form[^>]*>.*?value="student-registration-delete".*?value="' . (int) $firstRegistration['idInscription'] . '".*?<\/form>/s', $studentRegistrationsAfterCancel['body']), 'cancelled registration does not show student delete action');
    $cancelledDelete = $studentClient->post('student-registration-delete', ['registration_id' => (string) $firstRegistration['idInscription']]);
    $check(str_contains($studentClient->follow($cancelledDelete)['body'], 'Seules vos demandes'), 'cancelled registration cannot be deleted by student');

    $thirdEnroll = $studentClient->post('student-enroll', ['course_id' => (string) $thirdCourseId]);
    $check($thirdEnroll['status'] === 302, 'student creates a separate pending request for deletion test');
    $thirdRegistration = $db->prepare('SELECT idInscription FROM inscriptions WHERE idEtudiant = :student AND idCours = :course');
    $thirdRegistration->execute(['student' => (int) $originalStudent['idEtudiant'], 'course' => $thirdCourseId]);
    $thirdRegistrationId = (int) $thirdRegistration->fetchColumn();
    $thirdDelete = $studentClient->post('student-registration-delete', ['registration_id' => (string) $thirdRegistrationId]);
    $check($thirdDelete['status'] === 302, 'student deletes own pending request');
    $check((int) $db->query('SELECT places_restantes FROM cours WHERE idCours = ' . $thirdCourseId)->fetchColumn() === 1, 'deleting pending request restores place');

    $deleteCourse = $adminClient->post('admin-course-delete', ['course_id' => (string) $secondCourseId]);
    $check($deleteCourse['status'] === 302, 'admin deletes a course that has registrations');
    $cascadeCount = $db->prepare('SELECT COUNT(*) FROM inscriptions WHERE idCours = :course');
    $cascadeCount->execute(['course' => $secondCourseId]);
    $check((int) $cascadeCount->fetchColumn() === 0, 'course deletion safely cascades related registrations');

    $editPage = $adminClient->get('index.php?page=admin-courses&edit=' . $firstCourseId);
    $check(str_contains($editPage['body'], 'Modifier le cours'), 'admin can open course edit form');
    $editCourse = $adminClient->post('admin-course-save', [
        'course_id' => (string) $firstCourseId, 'langue' => $temporaryCourseNames[0], 'niveau' => 'QA',
        'prix' => '125.50', 'dateDebut' => '2099-10-01', 'dateFin' => '2099-11-01', 'places_total' => '4',
    ]);
    $check($editCourse['status'] === 302, 'admin updates course');
    $updatedCourse = $db->prepare('SELECT prix, places_total, places_restantes FROM cours WHERE idCours = :id');
    $updatedCourse->execute(['id' => $firstCourseId]);
    $updated = $updatedCourse->fetch();
    $check((float) $updated['prix'] === 125.5 && (int) $updated['places_total'] === 4 && (int) $updated['places_restantes'] === 3, 'course edit persists fields and maintains seat capacity');

    $studentLogout = $studentClient->post('logout');
    $check($studentLogout['status'] === 302, 'student logout redirects');
    $loggedOutPage = $studentClient->get('index.php?page=student-profile');
    $check($loggedOutPage['status'] === 302 && str_contains((string) QaHttpClient::headerValue($loggedOutPage['headers'], 'Location'), 'student-login'), 'student protected routes deny access after logout');
    $adminLogout = $adminClient->post('logout');
    $check($adminLogout['status'] === 302, 'admin logout redirects');
    $adminLoggedOut = $adminClient->get('index.php?page=admin-dashboard');
    $check($adminLoggedOut['status'] === 302 && str_contains((string) QaHttpClient::headerValue($adminLoggedOut['headers'], 'Location'), 'admin-login'), 'admin protected routes deny access after logout');
    $adminByLoginClient->post('logout');

    $integrityChecks = [
        'orphan registrations' => "SELECT COUNT(*) FROM inscriptions i LEFT JOIN etudiants e ON e.idEtudiant=i.idEtudiant LEFT JOIN cours c ON c.idCours=i.idCours WHERE e.idEtudiant IS NULL OR c.idCours IS NULL",
        'duplicate student/course registrations' => 'SELECT COUNT(*) FROM (SELECT idEtudiant, idCours FROM inscriptions GROUP BY idEtudiant, idCours HAVING COUNT(*) > 1) duplicates',
        'negative remaining places' => 'SELECT COUNT(*) FROM cours WHERE places_restantes < 0',
        'remaining places above total' => 'SELECT COUNT(*) FROM cours WHERE places_restantes > places_total',
        'invalid registration statuses' => "SELECT COUNT(*) FROM inscriptions WHERE statut NOT IN ('en attente', 'confirmé', 'annulé')",
        'invalid payment statuses' => "SELECT COUNT(*) FROM inscriptions WHERE paiement NOT IN ('payé', 'non payé')",
    ];
    foreach ($integrityChecks as $label => $sql) {
        $check((int) $db->query($sql)->fetchColumn() === 0, 'database integrity: ' . $label . ' = 0');
    }
    echo "\nQA smoke suite passed {$checks} checks.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "\nQA smoke suite failed after {$checks} checks: " . $exception->getMessage() . "\n");
    exit(1);
}
