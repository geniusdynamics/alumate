#!/bin/bash

# Advanced Analytics System Production Deployment Script
# This script handles deployment of the Laravel multi-tenant analytics system

set -e  # Exit on any error

cd /app || error_exit "Failed to change to /app directory"

# Configuration
LOG_FILE="/var/log/alumate-deploy.log"
ENV_FILE=".env.prod"
BACKUP_DIR="/var/backups/alumate"

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

# Error handling
error_exit() {
    log "ERROR: $1"
    exit 1
}

# Check if running as root or with sudo
if [[ $EUID -eq 0 ]]; then
    error_exit "This script should not be run as root"
fi

log "Starting Advanced Analytics System deployment"

# Pull latest changes
log "Pulling latest code changes"
git pull origin main || error_exit "Failed to pull latest changes"

# Install PHP dependencies
log "Installing PHP dependencies"
composer install --no-dev --optimize-autoloader || error_exit "Composer install failed"

# Copy production environment file if it doesn't exist
if [ ! -f "$ENV_FILE" ]; then
    log "Copying production environment file"
    cp .env.example "$ENV_FILE" || error_exit "Failed to copy environment file"
    log "WARNING: Please configure $ENV_FILE with production values before proceeding"
    exit 1
fi

# Cache configuration
log "Caching Laravel configuration"
php artisan config:cache || error_exit "Config cache failed"

log "Caching routes"
php artisan route:cache || error_exit "Route cache failed"

log "Caching views"
php artisan view:cache || error_exit "View cache failed"

# Run database migrations
log "Running database migrations"
php artisan migrate --force || error_exit "Database migration failed"

log "Running tenant migrations"
php artisan tenants:migrate --force || error_exit "Tenant migration failed"

# Clear and restart queues
log "Restarting queue workers"
php artisan queue:restart || error_exit "Queue restart failed"

# Terminate Horizon (it will restart automatically)
log "Terminating Horizon"
php artisan horizon:terminate || log "Horizon terminate failed (may not be running)"

# Build frontend assets (if needed)
if [ -f "package.json" ]; then
    log "Building frontend assets"
    npm run build || error_exit "Frontend build failed"
fi

# Clear application cache
log "Clearing application cache"
php artisan cache:clear || log "Cache clear failed"

# Optimize application
log "Optimizing application"
php artisan optimize || log "Optimize failed"

# Run health check
log "Running health checks"
php artisan health:check || log "Health check failed"

# Restart supervisor services
supervisorctl restart all || log "Supervisor restart failed"

log "Deployment completed successfully"
echo "Deploy complete"

# Optional: Send notification
# curl -X POST -H 'Content-type: application/json' --data '{"text":"Alumate deployment completed"}' $SLACK_WEBHOOK_URL || true

exit 0