param([ValidateRange(1024,65535)][int]$Port = 8081)
$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath (Split-Path -Parent $PSScriptRoot)
$oldMode = $env:ICOMPLIANCE_STORAGE
$oldDemo = $env:ICOMPLIANCE_DEMO_FILE
try {
    $env:ICOMPLIANCE_STORAGE = 'postgres'
    $env:ICOMPLIANCE_DEMO_FILE = $null
    & php -d extension=pdo_pgsql -d extension=fileinfo -r 'require "app/database.php"; database_connect(database_local_config()); echo "PostgreSQL connection ready.\n";'
    if ($LASTEXITCODE -ne 0) { throw 'Check the local database setup first.' }
    Write-Host "PostgreSQL demo: http://127.0.0.1:$Port/ — Ctrl+C stops the server."
    & php -d extension=pdo_pgsql -d extension=fileinfo -S "127.0.0.1:$Port" -t . router.php
} finally {
    $env:ICOMPLIANCE_STORAGE = $oldMode
    $env:ICOMPLIANCE_DEMO_FILE = $oldDemo
}
