#!/bin/sh
set -e

echo "Starting Laravel production container..."

# Wait for database to be ready
echo "Waiting for database connection..."
while ! pg_isready -h db -p 5432 -U ${DB_USERNAME} >/dev/null 2>&1; do
    echo "Database not ready, waiting..."
    sleep 2
done
echo "Database is ready!"

# Wait for Redis to be ready
echo "Waiting for Redis connection..."
while ! redis-cli -h redis -p 6379 -a ${REDIS_PASSWORD} ping >/dev/null 2>&1; do
    echo "Redis not ready, waiting..."
    sleep 2
done
echo "Redis is ready!"

# Set Laravel environment file
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.production /var/www/html/.env
fi

# Generate Laravel app key if not set
if [ -z "${APP_KEY}" ]; then
    echo "Generating Laravel application key..."
    php artisan key:generate
fi

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Cache Laravel configurations and routes
echo "Optimizing Laravel configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear and cache optimized assets
echo "Preparing optimized assets..."
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Set proper permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Start PHP-FPM
echo "Starting PHP-FPM..."
php-fpm

# Start supervisor for queue worker and scheduler
echo "Starting supervisor..."
supervisord -c /etc/supervisord.conf

# Create health check file
touch /var/www/html/storage/health-check

# Keep container running
echo "Container started successfully!"
exec "$@"