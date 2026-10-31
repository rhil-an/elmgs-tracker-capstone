<?php

require __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/database-users.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode(['csrf' => csrf_token()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit;
}

verify_csrf();

$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');

if (storage_uses_postgres()) {
    $user = database_authenticate($email, $password);
} elseif ($email === 'student@gmail.com' && $password === '1234') {
    $user = ['id' => 1, 'email' => $email, 'role' => 'student', 'student_id' => 'B2500004'];
} elseif ($email === 'admin@gmail.com' && $password === '1234') {
    $user = ['id' => 2, 'email' => $email, 'role' => 'admin', 'student_id' => null];
} else {
    $user = null;
}
if (!$user) {
    header('Location: index.html?error=invalid', true, 303);
    exit;
}

session_regenerate_id(true);

$_SESSION = [
    'user' => $user,
    'csrf' => bin2hex(random_bytes(32)),
    'show_stay_notice' => $user['role'] === 'student'
];

header('Location: ' . ($user['role'] === 'admin' ? 'dashboard.php' : 'dashboard-student.php'), true, 303);
