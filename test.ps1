$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
$runtimeIni = Join-Path (Split-Path $PSScriptRoot -Parent) '.runtime/php.ini'
if (Test-Path -LiteralPath $runtimeIni) { & php -c $runtimeIni vendor/phpunit/phpunit/phpunit --testdox }
else { & php vendor/phpunit/phpunit/phpunit --testdox }
exit $LASTEXITCODE
