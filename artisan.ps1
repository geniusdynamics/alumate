#!/usr/bin/env pwsh
# Laravel Artisan Helper Script
# Usage: .\artisan.ps1 [artisan-command] [parameters...]
# Example: .\artisan.ps1 migrate --path=database/migrations/some_migration.php
# Example: .\artisan.ps1 tinker --execute="print_r(Schema::getColumnListing('posts'));"

param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$Arguments
)

# PHP executable path
$phpPath = "D:\DevCenter\xampp\php-8.3.23\php.exe"

# Check if PHP exists
if (-not (Test-Path $phpPath)) {
    Write-Error "PHP not found at: $phpPath"
    Write-Error "Please update the script with the correct PHP path."
    exit 1
}

# Check if artisan exists
if (-not (Test-Path "artisan")) {
    Write-Error "Laravel artisan file not found in current directory."
    Write-Error "Please run this script from your Laravel project root."
    exit 1
}

# Build the command
$command = @($phpPath, "artisan") + $Arguments

# Execute the command
Write-Host "Executing: $($command -join ' ')" -ForegroundColor Green
& $command[0] $command[1..($command.Length-1)]

# Capture and return the exit code
exit $LASTEXITCODE