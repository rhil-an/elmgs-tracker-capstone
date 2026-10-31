<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/database-users.php';

$passed = 0;
function postgres_check(bool $ok, string $label): void
{
    global $passed;
    if (!$ok) { throw new RuntimeException('FAIL: ' . $label); }
    $passed++;
    echo 'PASS: ' . $label . PHP_EOL;
}
function postgres_rejects(PDO $db, string $sql, array $parameters, string $state, string $label): void
{
    $db->exec('SAVEPOINT constraint_test');
    try {
        $statement = $db->prepare($sql);
        $statement->execute($parameters);
    } catch (PDOException $error) {
        $db->exec('ROLLBACK TO SAVEPOINT constraint_test');
        postgres_check($error->getCode() === $state, $label);
        return;
    }
    throw new RuntimeException('FAIL: constraint accepted ' . $label);
}

try {
    $config = database_local_config();
    $app = database_connect($config);
    postgres_check($app->query('SELECT current_user')->fetchColumn() === 'icompliance_app', 'application uses its restricted account');
    postgres_check($app->query('SHOW timezone')->fetchColumn() === 'Asia/Kuala_Lumpur', 'Malaysia timezone');
    $students = json_decode(file_get_contents(dirname(__DIR__) . '/data/students.json'), true, 512, JSON_THROW_ON_ERROR);
    $lookup = $app->prepare('SELECT name,nationality,visa_expiry,last_check_in FROM students WHERE student_id=?');
    foreach ($students as $student) {
        $lookup->execute([$student['id']]);
        postgres_check($lookup->fetch(PDO::FETCH_ASSOC) === [
            'name'=>$student['name'], 'nationality'=>$student['nationality'],
            'visa_expiry'=>$student['visaExpiry'], 'last_check_in'=>$student['lastCheckIn'],
        ], 'dummy profile matches ' . $student['id']);
    }
    $users = $app->query('SELECT * FROM users ORDER BY user_id')->fetchAll(PDO::FETCH_ASSOC);
    postgres_check(count($users) === 41, 'forty student accounts and one administrator');
    foreach ($users as $user) {
        postgres_check($user['active'] === true, $user['role'] . ' account active');
        postgres_check(database_password_matches('1234', $user['password_hash']), $user['role'] . ' saved hash verifies');
        postgres_check(!database_password_matches('wrong', $user['password_hash']), $user['role'] . ' wrong password fails');
    }
    postgres_check(!$app->query("SELECT rolsuper OR rolcreatedb OR rolcreaterole OR rolreplication FROM pg_roles WHERE rolname=current_user")->fetchColumn(), 'no administrator powers');
    postgres_check(!$app->query("SELECT has_schema_privilege(current_user,'public','CREATE')")->fetchColumn(), 'application cannot create tables');
    postgres_check(!$app->query("SELECT has_table_privilege(current_user,'users','DELETE')")->fetchColumn(), 'application cannot delete accounts');
    postgres_check(!$app->query("SELECT has_table_privilege(current_user,'schema_migrations','UPDATE')")->fetchColumn(), 'application cannot change migration history');
    $db = database_connect($config, true);
    $db->beginTransaction();
    $sql = 'INSERT INTO users(user_id,email,password_hash,role,student_id) VALUES (?,?,?,?,?)';
    $hash = database_password_hash('test-only');
    postgres_rejects($db, $sql, [900001,'invalid@example.test',$hash,'owner',null], '23514', 'unknown role rejected');
    postgres_rejects($db, $sql, [900001,'invalid@example.test',$hash,'student',null], '23514', 'student requires profile');
    postgres_rejects($db, $sql, [900001,'invalid@example.test',$hash,'admin','B2500001'], '23514', 'admin cannot claim student profile');
    postgres_rejects($db, $sql, [900001,'invalid@example.test',' ','admin',null], '23514', 'blank hash rejected');
    postgres_rejects($db, $sql, [900001,'invalid@example.test',$hash,'student','MISSING'], '23503', 'missing student rejected');
    postgres_rejects($db, $sql, [900001,'invalid@example.test',$hash,'student','B2500004'], '23505', 'duplicate student account rejected');
    postgres_rejects($db, $sql, [900001,'admin@gmail.com',$hash,'admin',null], '23505', 'duplicate email rejected');
    $db->rollBack();
    echo $passed . ' PostgreSQL checks passed. Test writes rolled back.' . PHP_EOL;
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    fwrite(STDERR, $error instanceof PDOException ? 'Database check failed. Check connection/permissions locally.' . PHP_EOL : $error->getMessage() . PHP_EOL);
    exit(1);
}
