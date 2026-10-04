<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Registration.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/StudentController.php';
require_once __DIR__ . '/../controllers/AdminController.php';

try {
    $db = Database::connect();
    $studentModel = new Student($db);
    $adminModel = new Admin($db);
    $courseModel = new Course($db);
    $registrationModel = new Registration($db);
    $auth = new AuthController($studentModel, $adminModel);
    $studentController = new StudentController($db, $studentModel, $courseModel, $registrationModel);
    $adminController = new AdminController($db, $studentModel, $courseModel, $registrationModel);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $action = postString('action');
        if ($action === 'student-login' || $action === 'admin-login' || $action === 'logout') {
            $auth->handle($action);
        } elseif (str_starts_with($action, 'student-')) {
            requireStudent();
            $studentController->handle($action);
        } elseif (str_starts_with($action, 'admin-')) {
            requireAdmin();
            $adminController->handle($action);
        } else {
            throw new RuntimeException('Action invalide.');
        }
        throw new RuntimeException('La requête n’a pas pu être traitée.');
    }

    $page = queryString('page', 'home');
    if (in_array($page, ['student-profile', 'student-courses', 'student-registrations'], true)) {
        requireStudent();
        $content = match ($page) {
            'student-profile' => $studentController->profile(),
            'student-courses' => $studentController->courses(),
            default => $studentController->registrations(),
        };
    } elseif (in_array($page, ['admin-dashboard', 'admin-courses', 'admin-students', 'admin-registrations', 'admin-pending'], true)) {
        requireAdmin();
        $content = match ($page) {
            'admin-dashboard' => $adminController->dashboard(),
            'admin-courses' => $adminController->courses(),
            'admin-students' => $adminController->students(),
            'admin-pending' => $adminController->registrations(true),
            default => $adminController->registrations(),
        };
    } else {
        $content = match ($page) {
            'student-login' => ['view' => 'auth/student-login', 'title' => 'Connexion étudiant'],
            'admin-login' => ['view' => 'auth/admin-login', 'title' => 'Connexion administration'],
            default => ['view' => 'home', 'title' => 'École de langues', 'courses' => $courseModel->upcoming()],
        };
    }

    extract($content, EXTR_SKIP);
    require __DIR__ . '/../views/layout.php';
} catch (DomainException $exception) {
    setFlash('danger', $exception->getMessage());
    $action = postString('action');
    $destination = match (true) {
        str_starts_with($action, 'student-profile') => 'student-profile',
        str_starts_with($action, 'student-enroll'), str_starts_with($action, 'student-registration') => 'student-registrations',
        str_starts_with($action, 'admin-course') => 'admin-courses',
        str_starts_with($action, 'admin-registration') => 'admin-registrations',
        $action === 'student-login' => 'student-login',
        $action === 'admin-login' => 'admin-login',
        isset($_SESSION['admin_id']) => 'admin-dashboard',
        isset($_SESSION['student_id']) => 'student-profile',
        default => 'home',
    };
    redirect($destination);
} catch (Throwable $exception) {
    error_log((string) $exception);
    if (!isset($db)) {
        http_response_code(503);
        echo '<!doctype html><html lang="fr"><meta charset="utf-8"><title>Service indisponible</title>'
            . '<body><h1>Service temporairement indisponible</h1><p>Vérifiez la configuration de la base de données puis réessayez.</p></body></html>';
        exit;
    }
    setFlash('danger', 'Une erreur est survenue. Veuillez réessayer.');
    redirect(isset($_SESSION['admin_id']) ? 'admin-dashboard' : (isset($_SESSION['student_id']) ? 'student-profile' : 'home'));
}