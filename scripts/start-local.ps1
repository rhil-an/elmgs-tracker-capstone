param([ValidateRange(1024,65535)][int]$Port = 8080, [ValidateSet('auto','json','postgres')][string]$Storage = 'auto')
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectRoot
if (-not (Get-Command php -ErrorAction SilentlyContinue)) { throw 'PHP is not on PATH. Install PHP 8.2+ and enable required extensions.' }
if ($Storage -eq 'auto') {
    $Storage = & php -r 'require "app/database.php"; echo storage_uses_postgres() ? "postgres" : "json";'
    if ($LASTEXITCODE -ne 0) { throw 'Cannot read local storage configuration.' }
}
if ($Storage -eq 'postgres') {
    & (Join-Path $PSScriptRoot 'start-postgres.ps1') -Port $Port
    return
}
$previousStorage = $env:ICOMPLIANCE_STORAGE
try {
$env:ICOMPLIANCE_STORAGE = 'json'
& php -d extension=fileinfo -r 'if (PHP_VERSION_ID < 80200) { fwrite(STDERR, "PHP 8.2+ required\n"); exit(1); } foreach (["json","session","fileinfo"] as $ext) { if (!extension_loaded($ext)) { fwrite(STDERR, "Missing extension: ".$ext."\n"); exit(1); } }'
if ($LASTEXITCODE -ne 0) { throw 'PHP requirements failed.' }
& php -d extension=fileinfo scripts/seed-demo.php
if ($LASTEXITCODE -ne 0) { throw 'Demo seed failed. Check sample files and runtime permissions.' }
Write-Host "Open http://127.0.0.1:$Port/ — Ctrl+C stops the server."
& php -d extension=fileinfo -S "127.0.0.1:$Port" -t . router.php
} finally { $env:ICOMPLIANCE_STORAGE = $previousStorage }
