<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Kuala_Lumpur');
require_once __DIR__ . '/demo-store.php';

if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('icompliance_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => getenv('SESSION_SECURE') === '1' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    if (!session_start()) {
        throw new RuntimeException('Session storage is unavailable.');
    }

    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');

    set_exception_handler(function (Throwable $error): void {
        // PDO errors can contain SQL parameters; never write credentials/evidence to logs.
        error_log($error instanceof PDOException ? 'Database request failed. SQLSTATE: '.$error->getCode() : (string)$error);
        http_response_code(503);
        echo 'The demo could not complete this request. Check local storage and connection settings.';
    });
}

function csrf_token(): string {
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function verify_csrf(): void {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(csrf_token(), $_POST['csrf'])) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

function current_user(): ?array {
    $user = $_SESSION['user'] ?? null;
    if ($user && storage_uses_postgres()) {
        $query=postgres_connection()->prepare('SELECT user_id,email,role,student_id,active FROM users WHERE user_id=?');
        $query->execute([$user['id']]);
        $saved=$query->fetch(PDO::FETCH_ASSOC);
        if (!$saved || !$saved['active'] || $saved['role']!==$user['role'] || $saved['student_id']!==$user['student_id']) {
            unset($_SESSION['user']); return null;
        }
        $user['email']=$saved['email'];
    }
    return $user;
}

function require_user(?string $role = null): array {
    $user = current_user();

    if (!$user) {
        header('Location: index.html', true, 302);
        exit;
    }

    if ($role && $user['role'] !== $role) {
        http_response_code(403);
        exit('Access denied.');
    }

    return $user;
}

function require_owner(string $studentId): void {
    $user = require_user();

    if ($user['role'] !== 'admin' && $user['student_id'] !== $studentId) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function session_student(): array {
    $user = require_user('student');

    foreach ([$_GET, $_POST] as $input) {
        foreach (['student_id', 'id'] as $key) {
            if (isset($input[$key]) && $input[$key] !== $user['student_id']) {
                http_response_code(403);
                exit('Access denied.');
            }
        }
    }

    require_owner($user['student_id']);

    return find_student($user['student_id']) ?? throw new RuntimeException('Student account has no record.');
}

function logout_control(): string {
    return '<form class="logout-form" action="logout.php" method="post">'
        . csrf_field()
        . '<button class="logout-button" type="submit">Log out</button>'
        . '</form>';
}
