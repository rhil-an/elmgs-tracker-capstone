param(
    [string]$DatabaseHost = '127.0.0.1',
    [ValidateRange(1,65535)][int]$DatabasePort = 5432,
    [string]$Administrator = 'postgres'
)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$passwordFile = Join-Path $projectRoot '.runtime/postgres.pgpass'
if (-not (Test-Path -LiteralPath $passwordFile -PathType Leaf)) {
    throw 'Create .runtime/postgres.pgpass with host:port:*:administrator:password first. Do not commit it.'
}
$previousPasswordFile = $env:PGPASSFILE
try {
    $env:PGPASSFILE = $passwordFile
    & php -d extension=pdo_pgsql (Join-Path $PSScriptRoot 'setup-postgres.php') $DatabaseHost $DatabasePort $Administrator
    if ($LASTEXITCODE -ne 0) { throw 'Database setup did not finish. See the message above.' }
} finally {
    $env:PGPASSFILE = $previousPasswordFile
}
