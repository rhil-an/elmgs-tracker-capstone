<?php

declare(strict_types=1);

/** Local configuration is private; tests can select an isolated configuration. */
function database_local_config(): array
{
    $path = getenv('ICOMPLIANCE_DATABASE_CONFIG') ?: dirname(__DIR__) . '/.runtime/database.local.json';
    if (!is_file($path)) {
        throw new RuntimeException('Run scripts/setup-postgres.ps1 first.');
    }
    return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
}

function database_connect(array $config, bool $migration = false): PDO
{
    $role = $migration ? 'migration' : 'application';
    $db = new PDO(
        'pgsql:host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['database'],
        $config[$role]['user'],
        $config[$role]['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );
    $db->exec("SET TIME ZONE 'Asia/Kuala_Lumpur'");
    if (isset($config['schema'])) {
        if (!preg_match('/^[a-z][a-z0-9_]*$/D', $config['schema'])) { throw new RuntimeException('Invalid database schema.'); }
        $db->exec('SET search_path TO "' . $config['schema'] . '"');
    }
    return $db;
}

function storage_uses_postgres(): bool
{
    // Existing regression suites deliberately select their own private JSON file.
    if (getenv('ICOMPLIANCE_DEMO_FILE')) { return false; }
    $mode = getenv('ICOMPLIANCE_STORAGE');
    if (!$mode) {
        $path = dirname(__DIR__) . '/.runtime/storage.json';
        $mode = is_file($path) ? (json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR)['storage'] ?? 'json') : 'json';
    }
    if (!in_array($mode, ['json','postgres'], true)) { throw new RuntimeException('Invalid storage mode.'); }
    return $mode === 'postgres';
}

function postgres_connection(): PDO
{
    static $connections = [];
    $config = database_local_config();
    $key = hash('sha256', json_encode($config, JSON_THROW_ON_ERROR));
    return $connections[$key] ??= database_connect($config);
}
