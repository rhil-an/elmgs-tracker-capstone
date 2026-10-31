<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/database-users.php';
require dirname(__DIR__) . '/app/database-permissions.php';

function setup_scalar(PDO $db, string $sql, array $values = []): mixed
{
    $statement = $db->prepare($sql);
    $statement->execute($values);
    return $statement->fetchColumn();
}

try {
    if (!in_array('pgsql', PDO::getAvailableDrivers(), true)) {
        throw new RuntimeException('Enable pdo_pgsql or use scripts/setup-postgres.ps1.');
    }
    $host = $argv[1] ?? '127.0.0.1';
    $port = (int)($argv[2] ?? 5432);
    $administrator = $argv[3] ?? 'postgres';
    // This setup intentionally targets only the local computer.
    if (!in_array($host, ['127.0.0.1', 'localhost', '::1'], true) || $port < 1 || $port > 65535) {
        throw new RuntimeException('Use a valid local PostgreSQL host and port.');
    }
    $admin = new PDO("pgsql:host=$host;port=$port;dbname=postgres", $administrator, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    // Never overwrite an existing database or reuse existing roles on first setup.
    $path = dirname(__DIR__) . '/.runtime/database.local.json';
    if (!is_file($path)) {
        if (setup_scalar($admin, "SELECT 1 FROM pg_database WHERE datname='icompliance_demo'") ||
            setup_scalar($admin, "SELECT 1 FROM pg_roles WHERE rolname IN ('icompliance_migrator','icompliance_app')")) {
            throw new RuntimeException('Demo database/role names already exist. Stopped without changing them.');
        }
        $config = [
            'host' => $host, 'port' => $port, 'database' => 'icompliance_demo',
            'setup_marker' => 'icompliance-local-' . bin2hex(random_bytes(16)),
            'migration' => ['user' => 'icompliance_migrator', 'password' => bin2hex(random_bytes(32))],
            'application' => ['user' => 'icompliance_app', 'password' => bin2hex(random_bytes(32))],
        ];
        $handle = fopen($path, 'x');
        if (!$handle) { throw new RuntimeException('Cannot create private local database configuration.'); }
        try {
            $json = json_encode($config, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            if (fwrite($handle, $json) !== strlen($json)) { throw new RuntimeException('Configuration save failed.'); }
        } finally { fclose($handle); }
    }
    $config = database_local_config();
    if ($config['host'] !== $host || $config['port'] !== $port || $config['database'] !== 'icompliance_demo' ||
        $config['migration']['user'] !== 'icompliance_migrator' || $config['application']['user'] !== 'icompliance_app') {
        throw new RuntimeException('Local configuration does not match the setup target.');
    }
    foreach (['migration', 'application'] as $kind) {
        $user = $config[$kind]['user'];
        if (!setup_scalar($admin, 'SELECT 1 FROM pg_roles WHERE rolname = ?', [$user])) {
            $admin->exec('CREATE ROLE "' . $user . '" LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION PASSWORD ' .
                $admin->quote($config[$kind]['password']));
        }
        // A retry must authenticate using the saved password; never reset an existing role.
        new PDO("pgsql:host=$host;port=$port;dbname=postgres", $user, $config[$kind]['password']);
    }
    $owner = setup_scalar($admin, "SELECT pg_get_userbyid(datdba) FROM pg_database WHERE datname='icompliance_demo'");
    if ($owner !== false && $owner !== 'icompliance_migrator') {
        throw new RuntimeException('Existing demo database has a different owner. Stopped.');
    }
    if ($owner === false) {
        $admin->exec('CREATE DATABASE icompliance_demo OWNER icompliance_migrator');
        $admin->exec('COMMENT ON DATABASE icompliance_demo IS ' . $admin->quote($config['setup_marker']));
    } elseif (setup_scalar($admin, "SELECT shobj_description(oid,'pg_database') FROM pg_database WHERE datname='icompliance_demo'") !== $config['setup_marker']) {
        throw new RuntimeException('Existing database was not created by this setup. Stopped.');
    }
    $admin->exec('REVOKE ALL ON DATABASE icompliance_demo FROM PUBLIC');
    $admin->exec('GRANT CONNECT ON DATABASE icompliance_demo TO icompliance_app');
    $db = database_connect($config, true);
    $db->exec("SELECT pg_advisory_lock(716205311)");
    $db->exec('REVOKE CREATE ON SCHEMA public FROM PUBLIC');
    $db->exec('GRANT USAGE ON SCHEMA public TO icompliance_app');
    $db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version TEXT PRIMARY KEY, checksum TEXT NOT NULL, applied_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    foreach (glob(dirname(__DIR__) . '/migrations/*.sql') as $migration) {
    $version = basename($migration, '.sql');
    $checksum = hash_file('sha256', $migration);
    $applied = setup_scalar($db, 'SELECT checksum FROM schema_migrations WHERE version=?', [$version]);
    if ($applied !== false && $applied !== $checksum) { throw new RuntimeException('Applied migration checksum changed. Stopped.'); }
    if ($applied === false) {
        $db->beginTransaction();
        $db->exec(file_get_contents($migration));
        $statement = $db->prepare('INSERT INTO schema_migrations(version,checksum) VALUES (?,?)');
        $statement->execute([$version, $checksum]);
        $db->commit();
    }
    }
    $mapping = [
        'id'=>'student_id', 'name'=>'name', 'nationality'=>'nationality', 'faculty'=>'faculty',
        'programme'=>'programme', 'studyMode'=>'study_mode', 'learnerIntake'=>'learner_intake',
        'candidatureDate'=>'candidature_date', 'candidaturePeriod'=>'candidature_period',
        'preliminaryResearchProposal'=>'preliminary_research_proposal', 'supervisorAppointment'=>'supervisor_appointment',
        'researchSeminar'=>'research_seminar', 'proposalDefence'=>'proposal_defence',
        'ethicsClearance'=>'ethics_clearance', 'finalDraftSubmission'=>'final_draft_submission', 'vivaVoce'=>'viva_voce',
        'visaExpiry'=>'visa_expiry', 'lastCheckIn'=>'last_check_in', 'currentLocation'=>'current_location',
        'status'=>'sample_compliance_status', 'postgraduateStayStartDate'=>'postgraduate_stay_start_date',
        'postgraduateStayRequiredCompletionDate'=>'postgraduate_stay_required_completion_date',
    ];
    $students = json_decode(file_get_contents(dirname(__DIR__) . '/data/students.json'), true, 512, JSON_THROW_ON_ERROR);
    $db->beginTransaction();
    $insert = $db->prepare('INSERT INTO students (' . implode(',', $mapping) . ') VALUES (' .
        implode(',', array_fill(0, count($mapping), '?')) . ') ON CONFLICT (student_id) DO NOTHING');
    foreach ($students as $student) {
        $values = [];
        foreach ($mapping as $key => $column) { $values[] = $student[$key] ?? null; }
        $insert->execute($values);
    }
    $insert = $db->prepare('INSERT INTO users(user_id,email,password_hash,role,student_id,active) VALUES (?,?,?,?,?,TRUE) ON CONFLICT DO NOTHING');
    foreach (database_demo_users() as $user) {
        $insert->execute([$user['user_id'], $user['email'], $user['password_hash'], $user['role'], $user['student_id']]);
    }
    $db->exec("SELECT setval(pg_get_serial_sequence('users','user_id'), GREATEST((SELECT last_value FROM users_user_id_seq),(SELECT max(user_id) FROM users)),true)");
    $db->commit();
    database_apply_permissions($db);
    $app = database_connect($config);
    echo 'Connected as restricted application account.' . PHP_EOL;
    echo 'Students: ' . setup_scalar($app, 'SELECT count(*) FROM students') . PHP_EOL;
    echo 'Users: ' . setup_scalar($app, 'SELECT count(*) FROM users') . PHP_EOL;
    echo 'Setup complete. Use scripts/start-postgres.ps1 for the database website.' . PHP_EOL;
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    // Do not print connection arguments, SQL, passwords, or hashes.
    fwrite(STDERR, 'Setup failed (' . get_class($error) . '). Check local credentials, permissions and database state.' . PHP_EOL);
    if (!$error instanceof PDOException) { fwrite(STDERR, $error->getMessage() . PHP_EOL); }
    exit(1);
}
