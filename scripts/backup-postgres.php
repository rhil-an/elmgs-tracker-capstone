<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/database.php';
require dirname(__DIR__) . '/app/database-permissions.php';

function backup_manifest(PDO $db): array {
    $manifest=[];
    foreach ($db->query("SELECT tablename FROM pg_tables WHERE schemaname='public' ORDER BY tablename") as $row) {
        $name=$row['tablename'];
        if (!preg_match('/^[a-z_]+$/D',$name)) { throw new RuntimeException('Unexpected table name.'); }
        $records=$db->query('SELECT row_to_json(t)::text FROM (SELECT * FROM "'.$name.'" ORDER BY 1) t')->fetchAll(PDO::FETCH_COLUMN);
        $manifest[$name]=hash('sha256',implode("\n",$records));
    }
    return $manifest;
}
function backup_command(array $command, string $log): void {
    $process=proc_open($command,[0=>['pipe','r'],1=>['file',$log,'a'],2=>['file',$log,'a']],$pipes);
    if (!is_resource($process)) { throw new RuntimeException('Could not start PostgreSQL backup tool.'); }
    fclose($pipes[0]);
    if (proc_close($process)!==0) { throw new RuntimeException('PostgreSQL backup tool failed; inspect the private backup log locally.'); }
}
$admin=null; $temporaryDatabase=null; $temporaryCreated=false; $source=null;
$oldPassword=getenv('PGPASSWORD');
try {
    $config=database_local_config();
    $bin=$argv[1] ?? '';
    $suffix=PHP_OS_FAMILY==='Windows'?'.exe':'';
    foreach (['pg_dump','pg_restore'] as $tool) {
        if (!is_file($bin.'/'.$tool.$suffix)) { throw new RuntimeException('PostgreSQL bin folder is invalid.'); }
    }
    $dir=dirname(__DIR__).'/.runtime/backups';
    if (!is_dir($dir)) { mkdir($dir,0700,true); }
    $base=$dir.'/demo-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));
    $file=$base.'.dump'; $log=$base.'.log';
    $source=database_connect($config,true);
    $source->beginTransaction();
    $source->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
    $snapshot=$source->query('SELECT pg_export_snapshot()')->fetchColumn();
    $expected=backup_manifest($source);
    putenv('PGPASSWORD='.$config['migration']['password']);
    $common=['--host='.$config['host'],'--port='.$config['port'],'--username='.$config['migration']['user'],'--no-password'];
    backup_command(array_merge([$bin.'/pg_dump'.$suffix],$common,
        ['--format=custom','--schema=public','--no-owner','--no-privileges','--snapshot='.$snapshot,'--file='.$file,'--dbname='.$config['database']]),$log);
    $source->commit();
    echo 'Backup saved: .runtime/backups/'.basename($file).PHP_EOL;
    if (($argv[2] ?? '')==='--verify') {
        if (!in_array($config['host'],['127.0.0.1','localhost','::1'],true)) { throw new RuntimeException('Restore verification is local-only.'); }
        // The standard local pgpass authenticates postgres; source app secrets are never printed.
        putenv('PGPASSWORD');
        $admin=new PDO('pgsql:host='.$config['host'].';port='.$config['port'].';dbname=postgres','postgres',null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $temporaryDatabase='icompliance_restore_'.bin2hex(random_bytes(8));
        $admin->exec('CREATE DATABASE "'.$temporaryDatabase.'" OWNER icompliance_migrator');
        $temporaryCreated=true;
        $admin->exec('REVOKE ALL ON DATABASE "'.$temporaryDatabase.'" FROM PUBLIC');
        $admin->exec('GRANT CONNECT ON DATABASE "'.$temporaryDatabase.'" TO icompliance_app');
        putenv('PGPASSWORD='.$config['migration']['password']);
        backup_command(array_merge([$bin.'/pg_restore'.$suffix],$common,
            ['--exit-on-error','--clean','--if-exists','--no-owner','--no-privileges','--dbname='.$temporaryDatabase,$file]),$log);
        $restoredConfig=$config; $restoredConfig['database']=$temporaryDatabase;
        $restored=database_connect($restoredConfig,true);
        if (backup_manifest($restored)!==$expected) { throw new RuntimeException('Restored records differ from the backup snapshot.'); }
        database_apply_permissions($restored);
        $restoredApp=database_connect($restoredConfig);
        $restoredApp->query('SELECT count(*) FROM submissions')->fetchColumn();
        if ($restoredApp->query("SELECT has_table_privilege(current_user,'users','UPDATE') OR has_table_privilege(current_user,'audit_events','DELETE')")->fetchColumn()) {
            throw new RuntimeException('Restored runtime permissions are incorrect.');
        }
        $restoredApp=null;
        $restored=null;
        $restoredApp=null;
        echo 'Restore verified: every table matches, including account hashes, proof bytes and audit history.'.PHP_EOL;
    }
} catch (Throwable $error) {
    if ($source && $source->inTransaction()) { $source->rollBack(); }
    fwrite(STDERR,$error instanceof PDOException ? 'Database backup/restore check failed. Check local credentials and permissions.'.PHP_EOL : $error->getMessage().PHP_EOL);
    $failed=true;
} finally {
    if ($admin && $temporaryCreated && preg_match('/^icompliance_restore_[a-f0-9]{16}$/D',$temporaryDatabase)) {
        $restored=null;
        // Only the temporary database created above is removed; the demo stays intact.
        $admin->exec('DROP DATABASE "'.$temporaryDatabase.'"');
        echo 'Temporary restore-check database removed.'.PHP_EOL;
    }
    $oldPassword===false ? putenv('PGPASSWORD') : putenv('PGPASSWORD='.$oldPassword);
}
if ($failed ?? false) { exit(1); }
