<?php
require __DIR__ . '/app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'GET') { header('Content-Type: application/json'); echo json_encode(['csrf' => csrf_token()]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: GET, POST'); exit; }
verify_csrf();
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');
// Simple, hardcoded demo accounts. No database or account setup required.
if ($email === 'student@gmail.com' && $password === '1234') {
    $user = ['id' => 1, 'email' => $email, 'role' => 'student', 'student_id' => 'B2500004'];
} elseif ($email === 'admin@gmail.com' && $password === '1234') {
    $user = ['id' => 2, 'email' => $email, 'role' => 'admin', 'student_id' => null];
} else {
    header('Location: index.html?error=invalid', true, 303);
    exit;
}
session_regenerate_id(true);
$_SESSION = ['user' => $user, 'csrf' => bin2hex(random_bytes(32))];
header('Location: ' . ($user['role'] === 'admin' ? 'dashboard.php' : 'dashboard-student.php'), true, 303);
