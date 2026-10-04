<?php
declare(strict_types=1);

final class AuthController
{
    public function __construct(private Student $students, private Admin $admins)
    {
    }

    public function handle(string $action): void
    {
        if ($action === 'logout') {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            header('Location: index.php');
            exit;
        }

        $login = trim(postString('login'));
        $password = postString('password');
        if ($login === '' || $password === '' || strlen($login) > 150) {
            setFlash('danger', 'Veuillez saisir votre identifiant et votre mot de passe.');
            redirect($action === 'student-login' ? 'student-login' : 'admin-login');
        }

        if ($action === 'student-login') {
            $student = $this->students->authenticate($login, $password);
            if (!$student) {
                setFlash('danger', 'Identifiant ou mot de passe incorrect.');
                redirect('student-login');
            }
            session_regenerate_id(true);
            $_SESSION['student_id'] = (int) $student['idEtudiant'];
            $_SESSION['student_name'] = $student['nom'];
            unset($_SESSION['admin_id'], $_SESSION['admin_name']);
            redirect('student-profile');
        }

        $admin = $this->admins->authenticate($login, $password);
        if (!$admin) {
            setFlash('danger', 'Identifiant ou mot de passe incorrect.');
            redirect('admin-login');
        }
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['idAdmin'];
        $_SESSION['admin_name'] = $admin['nom'];
        unset($_SESSION['student_id'], $_SESSION['student_name']);
        redirect('admin-dashboard');
    }
}
