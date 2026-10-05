param([int]$Port = 8000)
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
$runtimeIni = Join-Path (Split-Path $PSScriptRoot -Parent) '.runtime/php.ini'
if (Test-Path -LiteralPath $runtimeIni) { & php -c $runtimeIni -S "127.0.0.1:$Port" -t public server.php }
else { & php artisan serve --host=127.0.0.1 --port=$Port }
