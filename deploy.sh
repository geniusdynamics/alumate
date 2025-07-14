#!/bin/bash
set -e

echo "Deploying application ..."

# Enter maintenance mode
(php artisan down --message 'The app is being (quickly!) updated. Please try again in a minute.') || true

    # Update codebase
    git pull origin main

    # Install dependencies based on lock file
    composer install --no-interaction --prefer-dist --optimize-autoloader

    # Migrate database
    php artisan migrate --force

    # Clear cache
    php artisan optimize:clear

    # Create cache
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # Build assets
    npm run build

# Exit maintenance mode
php artisan up

echo "Application deployed!"
