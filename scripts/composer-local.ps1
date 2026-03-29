param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$Args
)

$ErrorActionPreference = "Stop"
$runtime = "E:\Dev\Tools\laravel-runtime"
$php = Join-Path $runtime "php\php.exe"
$composer = Join-Path $runtime "composer\composer.phar"
$node = Join-Path $runtime "node"
$phpDir = Join-Path $runtime "php"

$env:Path = "$phpDir;$node;$env:Path"

if (-not $Args -or $Args.Count -eq 0) {
    Write-Host "Uso: .\scripts\composer-local.ps1 install"
    exit 1
}

& $php $composer @Args
