<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function postString(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}

function queryString(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? $value : $default;
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrfToken(), $submitted)) {
        http_response_code(400);
        throw new RuntimeException('Votre session a expiré. Veuillez réessayer.');
    }
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function statusBadge(string $status): string
{
    $class = match ($status) {
        'Disponible', 'confirmé', 'payé' => 'status-success',
        'Complet', 'annulé' => 'status-danger',
        'en attente' => 'status-warning',
        'non payé' => 'status-muted',
        default => 'status-muted',
    };
    return '<span class="badge status-badge ' . $class . '">' . e($status) . '</span>';
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function takeFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function redirect(string $page): never
{
    header('Location: index.php?page=' . rawurlencode($page));
    exit;
}

function requireStudent(): void
{
    if (!isset($_SESSION['student_id'])) {
        setFlash('warning', 'Veuillez vous connecter pour accéder à cette page.');
        redirect('student-login');
    }
}

function requireAdmin(): void
{
    if (!isset($_SESSION['admin_id'])) {
        setFlash('warning', 'Veuillez vous connecter en tant qu’administrateur.');
        redirect('admin-login');
    }
}
