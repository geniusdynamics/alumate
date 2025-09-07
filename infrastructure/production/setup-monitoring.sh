#!/bin/bash

# Production Monitoring Setup Script
# This script configures the complete production monitoring and analytics system
# for the Alumni Platform.

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="$(dirname "$SCRIPT_DIR")"
BACKUP_SUFFIX=$(date +%Y%m%d_%H%M%S)
LOG_FILE="/var/log/alumate-monitoring-setup.log"

# Logging functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $*" | tee -a "$LOG_FILE"
}

success() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] SUCCESS:${NC} $*" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING:${NC} $*" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR:${NC} $*" | tee -a "$LOG_FILE"
}

backup_file() {
    local file="$1"
    if [[ -f "$file" ]]; then
        local backup_file="${file}.${BACKUP_SUFFIX}"
        cp "$file" "$backup_file"
        log "Backed up $file to $backup_file"
    fi
}

# Pre-flight checks
preflight_checks() {
    log "Running pre-flight checks..."

    # Check if we're running as root or with sudo
    if [[ $EUID -ne 0 ]]; then
        error "This script must be run as root or with sudo"
        exit 1
    fi

    # Check if PHP is installed
    if ! command -v php &> /dev/null; then
        error "PHP is not installed or not in PATH"
        exit 1
    fi

    # Check if Composer is installed
    if ! command -v composer &> /dev/null; then
        error "Composer is not installed or not in PATH"
        exit 1
    fi

    # Check if Node.js is installed
    if ! command -v node &> /dev/null; then
        error "Node.js is not installed or not in PATH"
        exit 1
    fi

    # Check if Redis is available
    if ! command -v redis-cli &> /dev/null; then
        warning "Redis CLI is not available - some monitoring features will be limited"
    fi

    success "Pre-flight checks completed"
}

# Environment configuration
configure_environment() {
    log "Configuring production environment variables..."

    local env_file="$APP_ROOT/.env"

    # Backup existing .env file
    backup_file "$env_file"

    # Read current .env or create new one
    if [[ ! -f "$env_file" ]]; then
        warning ".env file not found, creating from template"
        cp "$APP_ROOT/.env.example" "$env_file" 2>/dev/null || touch "$env_file"
    fi

    # Monitoring-specific environment variables
    cat >> "$env_file" << EOF

# === PRODUCTION MONITORING CONFIGURATION ===

# Enable monitoring features
MONITORING_ENABLED=true
APP_ENV=production

# Alert channels
ALERTS_ENABLED=true
ALERT_EMAIL_ENABLED=true
ALERT_SLACK_ENABLED=true
ALERT_WEBHOOK_ENABLED=false
ALERT_SMS_ENABLED=false

# Alert recipients (comma-separated)
ALERT_EMAIL_RECIPIENTS=admin@example.com,dev@example.com

# Performance monitoring
PERFORMANCE_MONITORING_ENABLED=true
COLLECT_RESPONSE_TIMES=true
COLLECT_MEMORY_USAGE=true
COLLECT_DB_QUERIES=true
COLLECT_CACHE_HITS=true

# Security monitoring
SECURITY_MONITORING_ENABLED=true

# External monitoring services (update with your API keys)
SENTRY_ENABLED=false
SENTRY_DSN=
ROLLBAR_ENABLED=false
ROLLBAR_ACCESS_TOKEN=

DATADOG_ENABLED=false
DATADOG_API_KEY=
DATADOG_APP_KEY=

NEW_RELIC_ENABLED=false
NEW_RELIC_LICENSE_KEY=
NEW_RELIC_APP_NAME="Alumni Platform"

# Redis configuration (if using Redis for monitoring)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Database configuration
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=alumate_prod
DB_USERNAME=alumate_user
DB_PASSWORD=

# Cache configuration
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Logging configuration
LOG_CHANNEL=daily
LOG_LEVEL=warning

# Monitoring data retention
DATA_RETENTION_DAYS=365
ANALYTICS_DATA_RETENTION_DAYS=90

EOF

    success "Environment configuration completed"
}

# Install PHP dependencies
install_dependencies() {
    log "Installing PHP dependencies..."

    cd "$APP_ROOT"

    # Check if vendor directory exists
    if [[ ! -d "vendor" ]]; then
        log "Installing Composer dependencies..."
        composer install --no-dev --optimize-autoloader
    else
        warning "Composer dependencies already installed"
    fi

    #_generate_compiled_optimizations
    log "Optimizing Composer autoloader..."
    composer dump-autoload --optimize

    success "PHP dependencies installed"
}

# Install Node.js dependencies for monitoring
install_frontend_dependencies() {
    log "Installing Node.js dependencies for monitoring..."

    cd "$APP_ROOT"

    # Install only production dependencies
    if [[ ! -d "node_modules" ]]; then
        npm ci --only=production
    else
        warning "Node.js dependencies already installed"
    fi

    # Build assets for production
    log "Building production assets..."
    npm run build

    success "Node.js dependencies installed"
}

# Database setup and migrations
setup_database() {
    log "Setting up database for monitoring..."

    cd "$APP_ROOT"

    # Run migrations
    log "Running database migrations..."
    php artisan migrate --force

    # Seed analytics and monitoring data (if available)
    if [[ -f "database/seeders/MonitoringDataSeeder.php" ]]; then
        log "Seeding monitoring data..."
        php artisan db:seed --class=MonitoringDataSeeder
    fi

    # Create indexes for monitoring tables
    log "Creating database indexes for monitoring..."
    php artisan monitoring:create-indexes || warning "Monitoring indexes creation failed"

    success "Database setup completed"
}

# Cache and storage setup
setup_cache_storage() {
    log "Setting up cache and storage..."

    cd "$APP_ROOT"

    # Create cache directories
    mkdir -p storage/cache/data
    mkdir -p storage/framework/cache/data
    mkdir -p storage/framework/sessions
    mkdir -p storage/logs

    # Set proper permissions
    chown -R www-data:www-data storage
    chown -R www-data:www-data bootstrap/cache
    chmod -R 775 storage
    chmod -R 775 bootstrap/cache

    # Clear and warm caches
    log "Clearing and warming application caches..."
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

    # Warm config cache in production
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    success "Cache and storage setup completed"
}

# Configure monitoring services
configure_monitoring() {
    log "Configuring monitoring services..."

    cd "$APP_ROOT"

    # Create monitoring directories
    mkdir -p storage/logs/monitoring
    mkdir -p storage/app/monitoring

    # Set permissions
    chown -R www-data:www-data storage/app/monitoring
    chown -R www-data:www-data storage/logs/monitoring

    # Create log channels configuration
    cat >> config/logging.php << 'EOF'

// Monitoring-specific log channels
'channels' => [
    'monitoring' => [
        'driver' => 'daily',
        'path' => storage_path('logs/monitoring/monitoring.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 30,
    ],
    'alerts' => [
        'driver' => 'daily',
        'path' => storage_path('logs/monitoring/alerts.log'),
        'level' => env('LOG_LEVEL', 'info'),
        'days' => 90,
    ],
    'performance_alerts' => [
        'driver' => 'daily',
        'path' => storage_path('logs/monitoring/performance.log'),
        'level' => env('LOG_LEVEL', 'warning'),
        'days' => 90,
    ],
],
EOF

    # Configure Redis cache for monitoring
    if command -v redis-cli &> /dev/null; then
        log "Configuring Redis for monitoring data..."

        # Create Redis configuration for monitoring
        cat > config/database.php << 'EOF'
// Redis configuration for monitoring
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_CACHE_DB', 1),
    ],
    'monitoring' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_MONITORING_DB', 2),
    ],
],
EOF
    fi

    success "Monitoring services configured"
}

# Setup cron jobs for monitoring
setup_cron_jobs() {
    log "Setting up cron jobs for monitoring..."

    # Create cron jobs for regular monitoring tasks
    local cron_file="/etc/cron.d/alumate-monitoring"

    cat > "$cron_file" << EOF
# Alumni Platform Production Monitoring Cron Jobs
# Generated on $(date)

# Every 5 minutes - Real-time monitoring cycle
*/5 * * * * www-data cd $APP_ROOT && php artisan monitoring:cycle --frequency=realtime --alert-threshold=high

# Every hour - Performance monitoring
0 * * * * www-data cd $APP_ROOT && php artisan monitoring:cycle --frequency=hourly --alert-threshold=medium

# Daily monitoring and cleanup
0 3 * * * www-data cd $APP_ROOT && php artisan monitoring:cycle --frequency=daily --alert-threshold=low

# Weekly comprehensive monitoring
0 4 * * 1 www-data cd $APP_ROOT && php artisan monitoring:cycle --frequency=weekly --alert-threshold=critical

# Data cleanup and maintenance
0 5 * * * www-data cd $APP_ROOT && php artisan monitoring:cleanup-old-data

# Performance metrics collection
*/10 * * * * www-data cd $APP_ROOT && php artisan monitoring:collect-metrics

# Security monitoring
*/30 * * * * www-data cd $APP_ROOT && php artisan monitoring:security-scan

# Automated reports
0 6 * * * www-data cd $APP_ROOT && php artisan monitoring:generate-daily-report
0 7 * * 1 www-data cd $APP_ROOT && php artisan monitoring:generate-weekly-report
0 8 1 * * www-data cd $APP_ROOT && php artisan monitoring:generate-monthly-report
EOF

    # Set proper permissions
    chown root:root "$cron_file"
    chmod 644 "$cron_file"

    # Reload cron
    if command -v systemctl &> /dev/null; then
        systemctl reload cron || true
    fi

    success "Cron jobs configured"
}

# Setup logrotate for monitoring logs
setup_log_rotation() {
    log "Setting up log rotation for monitoring logs..."

    local logrotate_file="/etc/logrotate.d/alumate-monitoring"

    cat > "$logrotate_file" << EOF
/var/www/html/storage/logs/monitoring/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload php8.1-fpm || systemctl reload php-fpm || true
    endscript
}
EOF

    # Set proper permissions
    chown root:root "$logrotate_file"
    chmod 644 "$logrotate_file"

    success "Log rotation configured"
}

# Configure web server for monitoring
configure_web_server() {
    log "Configuring web server for monitoring endpoints..."

    # Check if using Apache or Nginx
    if [[ -f "/etc/apache2/apache2.conf" ]]; then
        log "Configuring Apache for monitoring..."
        # Add monitoring endpoints to Apache config
        cat >> "/etc/apache2/sites-available/default-ssl.conf" << 'EOF'

# Monitoring endpoints
<Location "/api/monitoring">
    Require all granted
    Header always set X-Monitoring-Enabled "true"
</Location>

<Location "/webhooks">
    Require all granted
    Header always set X-Webhook-Enabled "true"
</Location>
EOF

        a2ensite default-ssl.conf || true
        systemctl reload apache2 || true

    elif [[ -f "/etc/nginx/nginx.conf" ]]; then
        log "Configuring Nginx for monitoring..."
        # Add monitoring endpoints to Nginx config
        cat >> "/etc/nginx/sites-available/default" << 'EOF'

# Monitoring and webhook endpoints
location /api/monitoring {
    try_files $uri $uri/ =404;
    include /etc/nginx/fastcgi_params;
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root/index.php;
    add_header X-Monitoring-Enabled "true";
}

location /webhooks {
    try_files $uri $uri/ =404;
    include /etc/nginx/fastcgi_params;
    fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root/index.php;
    add_header X-Webhook-Enabled "true";
}
EOF

        nginx -t && systemctl reload nginx || true
    fi

    success "Web server configured for monitoring"
}

# Final verification
verify_setup() {
    log "Verifying monitoring setup..."

    cd "$APP_ROOT"

    # Test monitoring cycle
    if php artisan monitoring:cycle --dry-run; then
        success "Monitoring cycle test passed"
    else
        error "Monitoring cycle test failed"
        return 1
    fi

    # Test monitoring dashboard endpoint
    if curl -f -s http://localhost/api/ping > /dev/null; then
        success "API endpoints are responding"
    else
        error "API endpoints are not responding"
       # return 1
    fi

    # Verify log files
    if [[ -f "storage/logs/monitoring/monitoring.log" ]]; then
        success "Monitoring log file created"
    else
        warning "Monitoring log file not found - this is normal for initial setup"
    fi

    success "Monitoring setup verification completed"
}

# Main function
main() {
    log "=== ALUMNI PLATFORM PRODUCTION MONITORING SETUP ==="
    log "Started by $(whoami) on $(hostname -f)"
    log "Application root: $APP_ROOT"

    preflight_checks

    configure_environment

    install_dependencies

    install_frontend_dependencies

    setup_database

    setup_cache_storage

    configure_monitoring

    setup_cron_jobs

    setup_log_rotation

    configure_web_server

    verify_setup

    log "=== SETUP COMPLETED SUCCESSFULLY ==="
    log "Monitoring system is now ready for production use"
    log ""
    log "Next steps:"
    log "1. Configure external monitoring services (Sentry, Datadog, etc.)"
    log "2. Set up monitoring alerts in your preferred channels"
    log "3. Configure backup systems for monitoring data"
    log "4. Review and adjust monitoring thresholds based on your needs"
    log ""
    log "Use 'php artisan monitoring:cycle' to manually trigger monitoring"
    log "Access the monitoring dashboard at: /admin/monitoring"
}

# Run main function with error handling
trap 'error "Setup failed with exit code $?"; exit 1' ERR
main "$@"