<?php

declare(strict_types=1);

/** Password helpers shared by database authentication and demo seeding. */
function database_password_hash(string $password): string
{
    if ($password === '' || str_contains($password, "\0")) {
        throw new InvalidArgumentException('Provide a non-empty password without null bytes.');
    }

    // PASSWORD_DEFAULT currently uses bcrypt, which has a 72-byte input limit.
    // Reject longer input rather than silently accepting truncated passwords.
    if (PASSWORD_DEFAULT === PASSWORD_BCRYPT && strlen($password) > 72) {
        throw new InvalidArgumentException('Password exceeds the supported byte length.');
    }

    return password_hash($password, PASSWORD_DEFAULT);
}

function database_password_matches(string $password, string $hash): bool
{
    if ($password === '' || str_contains($password, "\0")) {
        return false;
    }

    $info = password_get_info($hash);
    if ($info['algoName'] === 'bcrypt' && strlen($password) > 72) {
        return false;
    }

    return password_verify($password, $hash);
}

/** Fictional fixtures only. IDs match existing session/audit demo identities. */
function database_demo_users(): array
{
    $users = [
        [
            'user_id' => 1,
            'email' => 'student@gmail.com',
            'password_hash' => database_password_hash('1234'),
            'role' => 'student',
            'student_id' => 'B2500004',
            'active' => true,
        ],
        [
            'user_id' => 2,
            'email' => 'admin@gmail.com',
            'password_hash' => database_password_hash('1234'),
            'role' => 'admin',
            'student_id' => null,
            'active' => true,
        ],
    ];
    $students = json_decode(file_get_contents(dirname(__DIR__) . '/data/students.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach ($students as $index => $student) {
        if ($student['id'] === 'B2500004') { continue; }
        $users[] = [
            'user_id' => $index + 3,
            'email' => strtolower($student['id']) . '@students.demo.test',
            'password_hash' => database_password_hash('1234'),
            'role' => 'student', 'student_id' => $student['id'], 'active' => true,
        ];
    }
    return $users;
}

function database_authenticate(string $email, string $password): ?array
{
    $db = postgres_connection();
    $query = $db->prepare('SELECT user_id,email,password_hash,role,student_id,active FROM users WHERE email=?');
    $query->execute([strtolower(trim($email))]);
    $user = $query->fetch(PDO::FETCH_ASSOC);
    // No demo password comparison here: each account's saved hash controls access.
    if (!$user || !database_password_matches($password, $user['password_hash']) || !$user['active']) { return null; }
    return ['id'=>(int)$user['user_id'], 'email'=>$user['email'], 'role'=>$user['role'], 'student_id'=>$user['student_id']];
}
