param([string]$PostgresBin, [switch]$VerifyRestore)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
if (-not $PostgresBin) {
    $dumpCommand = Get-Command pg_dump -ErrorAction SilentlyContinue
    if ($dumpCommand) { $PostgresBin = Split-Path -Parent $dumpCommand.Source }
}
if (-not $PostgresBin) { throw 'Provide -PostgresBin pointing to the PostgreSQL bin folder.' }
$oldPasswordFile = $env:PGPASSFILE
try {
    $env:PGPASSFILE = Join-Path $projectRoot '.runtime/postgres.pgpass'
    $verify = if ($VerifyRestore) { '--verify' } else { '--backup-only' }
    & php -d extension=pdo_pgsql (Join-Path $PSScriptRoot 'backup-postgres.php') $PostgresBin $verify
    if ($LASTEXITCODE -ne 0) { throw 'Backup or restore verification failed.' }
} finally { $env:PGPASSFILE = $oldPasswordFile }
