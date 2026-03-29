param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$Args
)

$ErrorActionPreference = "Stop"
$runtime = "E:\Dev\Tools\laravel-runtime"
$npm = Join-Path $runtime "node\npm.cmd"
$node = Join-Path $runtime "node"
$phpDir = Join-Path $runtime "php"

$env:Path = "$phpDir;$node;$env:Path"

if (-not $Args -or $Args.Count -eq 0) {
    Write-Host "Uso: .\scripts\npm-local.ps1 run build"
    exit 1
}

& $npm @Args
