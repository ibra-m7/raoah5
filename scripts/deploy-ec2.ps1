# Run on the AWS EC2 Laragon server (PowerShell) after git pull.
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

Write-Host '>> Pulling latest code...' -ForegroundColor Cyan
git pull origin main

Write-Host '>> Running migrations...' -ForegroundColor Cyan
php artisan migrate --force

Write-Host '>> Clearing caches...' -ForegroundColor Cyan
php artisan optimize:clear

Write-Host '>> Done. Test: GET /api/pickup/slots (auth) and POST /api/orders' -ForegroundColor Green
