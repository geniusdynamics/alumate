#!/bin/bash

# Homepage Production Deployment Script
# This script handles the deployment of homepage features to production

set -e

echo "Starting homepage deployment..."

# Configuration
BACKUP_DIR="/var/backups/homepage"
APP_DIR="/var/www/alumni-platform"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup current homepage files
echo "Creating backup..."
tar -czf "$BACKUP_DIR/homepage_backup_$TIMESTAMP.tar.gz" \
  -C $APP_DIR \
  public/build \
  app/Http/Controllers/HomepageController.php \
  app/Services/Homepage \
  resources/js/Pages/Homepage \
  resources/js/Components/Homepage 2>/dev/null || true

# Extract new deployment
echo "Extracting deployment package..."
tar -xzf homepage-deployment.tar.gz -C $APP_DIR

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data $APP_DIR/public/build
chown -R www-data:www-data $APP_DIR/storage
chmod -R 755 $APP_DIR/public/build
chmod -R 775 $APP_DIR/storage

# Install/update dependencies
echo "Installing dependencies..."
cd $APP_DIR
composer install --no-dev --optimize-autoloader --no-interaction

# Run database migrations for homepage features
echo "Running homepage migrations..."
php artisan migrate --path=database/migrations --force --no-interaction

# Clear and optimize caches
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Restart services
echo "Restarting services..."
systemctl reload nginx
systemctl restart php8.2-fpm

# Verify deployment
echo "Verifying deployment..."
if curl -f http://localhost/health-check/homepage > /dev/null 2>&1; then
  echo "✅ Homepage deployment successful!"
  
  # Clean up old backups (keep last 5)
  cd $BACKUP_DIR
  ls -t homepage_backup_*.tar.gz | tail -n +6 | xargs -r rm
  
else
  echo "❌ Deployment verification failed! Rolling back..."
  
  # Rollback to previous version
  LATEST_BACKUP=$(ls -t $BACKUP_DIR/homepage_backup_*.tar.gz | head -n 1)
  if [ -n "$LATEST_BACKUP" ]; then
    echo "Rolling back to: $LATEST_BACKUP"
    tar -xzf "$LATEST_BACKUP" -C $APP_DIR
    php artisan optimize
    systemctl reload nginx
    systemctl restart php8.2-fpm
  fi
  
  exit 1
fi

echo "Homepage deployment completed successfully!"