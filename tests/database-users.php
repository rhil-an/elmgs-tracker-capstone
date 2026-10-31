<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/database-users.php';

$passed = 0;
function check_database_user(bool $ok, string $label): void
{
    global $passed;
    if (!$ok) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    $passed++;
    echo 'PASS: ' . $label . PHP_EOL;
}

$users = database_demo_users();
foreach ($users as $user) {
    check_database_user($user['password_hash'] !== '1234', $user['role'] . ' stores a hash');
    check_database_user(!array_key_exists('password', $user), 'no plaintext password field');
    check_database_user(database_password_matches('1234', $user['password_hash']), 'correct password accepted');
    check_database_user(!database_password_matches('wrong', $user['password_hash']), 'incorrect password rejected');
    check_database_user(!database_password_matches('', $user['password_hash']), 'empty password rejected');
    check_database_user(!database_password_matches("1234\0extra", $user['password_hash']), 'null-byte password rejected');
}

$first = database_password_hash('same password');
$second = database_password_hash('same password');
check_database_user($first !== $second, 'independent salts for identical passwords');
check_database_user(database_password_matches('same password', $second), 'salted password verifies');
check_database_user(!database_password_matches('1234', 'not-a-hash'), 'invalid hash rejected');

foreach (['', "bad\0password", str_repeat('a', 73)] as $invalid) {
    if (strlen($invalid) > 72 && PASSWORD_DEFAULT !== PASSWORD_BCRYPT) {
        continue;
    }
    try {
        database_password_hash($invalid);
        throw new RuntimeException('Invalid password was accepted.');
    } catch (InvalidArgumentException $error) {
        check_database_user(true, 'invalid password cannot be hashed');
    }
}

$longHash = password_hash(str_repeat('a', 72), PASSWORD_BCRYPT);
check_database_user(!database_password_matches(str_repeat('a', 73), $longHash), 'bcrypt truncation cannot authenticate');
check_database_user($users[0]['user_id'] === 1 && $users[0]['student_id'] === 'B2500004', 'existing student identity retained');
check_database_user($users[1]['user_id'] === 2 && $users[1]['student_id'] === null, 'existing administrator identity retained');
echo $passed . ' database user checks passed.' . PHP_EOL;
