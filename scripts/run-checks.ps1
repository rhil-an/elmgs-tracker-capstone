$ErrorActionPreference = 'Stop'
$oldPhpConfig = $env:PHPRC
try {
Set-Location -LiteralPath (Split-Path -Parent $PSScriptRoot)
if (Test-Path -LiteralPath '.runtime/php.ini') { $env:PHPRC = Join-Path (Get-Location) '.runtime/php.ini' }
$failed = @()
foreach ($suite in @('auth','submissions','dashboard','progress','review-table','evidence','demo-seed','student-profile','student-dashboard')) {
    Write-Host "Checking $suite"
    & php "tests/$suite.php"
    if ($LASTEXITCODE -ne 0) { $failed += $suite }
}
if ($failed.Count) { throw ('Failed suites: ' + ($failed -join ', ')) }
Write-Host 'All nine suites passed. Test fixtures use isolated runtime state.'
} finally { $env:PHPRC = $oldPhpConfig }
