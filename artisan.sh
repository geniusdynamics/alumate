#!/bin/bash
# Laravel Artisan Helper Script (Bash equivalent of artisan.ps1)
# Usage: ./artisan.sh [artisan-command] [parameters...]
# Example: ./artisan.sh migrate --path=database/migrations/some_migration.php
# Example: ./artisan.sh tinker --execute="print_r(Schema::getColumnListing('posts'));"

# PHP executable path (update this if PHP is not in PATH)
PHP_PATH="php"

# Function to check if PHP exists
check_php() {
    if ! command -v "$PHP_PATH" &> /dev/null; then
        echo "Error: PHP not found at: $PHP_PATH"
        echo "Please update the PHP_PATH variable with the correct PHP path or ensure PHP is in your PATH."
        exit 1
    fi
}

# Function to check if artisan exists
check_artisan() {
    if [[ ! -f "artisan" ]]; then
        echo "Error: Laravel artisan file not found in current directory."
        echo "Please run this script from your Laravel project root."
        exit 1
    fi
}

# Get all arguments passed to the script
ARGUMENTS=("$@")

# Check PHP and artisan
check_php
check_artisan

# Build the command
COMMAND=("$PHP_PATH" "artisan" "${ARGUMENTS[@]}")

# Execute the command
echo "Executing: ${COMMAND[*]}"
"${COMMAND[@]}"

# Capture and return the exit code
exit $?