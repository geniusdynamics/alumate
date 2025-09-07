#!/bin/bash

# Advanced Multi-Tenant Deployment Script for Laravel Alumni Platform
# Supports zero-downtime deployments with rollback capabilities

set -euo pipefail

# =========================================================================
# CONFIGURATION
# =========================================================================

# Deployment configuration
DEPLOY_ENV=${DEPLOY_ENV:-"production"}
DEPLOY_TAG=${DEPLOY_TAG:-"latest"}
BACKUP_BEFORE_DEPLOY=true
ENABLE_ROLLBACK=true
HEALTH_CHECK_TIMEOUT=300
ROLLBACK_TIMEOUT=300

# Application paths (relative to script location)
APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
RELEASES_DIR="${APP_ROOT}/releases"
CURRENT_LINK="${APP_ROOT}/current"
SHARED_DIR="${APP_ROOT}/shared"
ROLLBACK_FILE="${APP_ROOT}/.rollback_info"
LOG_FILE="${APP_ROOT}/storage/logs/deploy.log"

# Database configuration
BACKUP_DATABASE=true
BACKUP_RETAIN_DAYS=7

# Tenant-specific configurations
TENANT_MIGRATIONS_ENABLED=true
TENANT_SEEDERS_ENABLED=true

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# =========================================================================
# UTILITY FUNCTIONS
# =========================================================================

log() {
    echo "$(date +'%Y-%m-%d %H:%M:%S') [$1] $2" >> "$LOG_FILE"
    echo -e "${2}" >&2
}

log_info() {
    log "INFO" "${BLUE}${1}${NC}"
}

log_warning() {
    log "WARNING" "${YELLOW}${1}${NC}"
}

log_error() {
    log "ERROR" "${RED}${1}${NC}"
}

log_success() {
    log "SUCCESS" "${GREEN}${1}${NC}"
}

cleanup_on_error() {
    log_error "Deployment failed! Cleaning up..."
    if [ "$ENABLE_ROLLBACK" = true ] && [ -f "$ROLLBACK_FILE" ]; then
        perform_rollback
    fi
    exit 1
}

trap cleanup_on_error ERR

# =========================================================================
# DEPLOYMENT FUNCTIONS
# =========================================================================

validate_prerequisites() {
    log_info "Validating deployment prerequisites..."

    # Check if required commands exist
    for cmd in git composer npm python3 docker docker-compose; do
        if ! command -v $cmd >/dev/null 2>&1; then
            log_error "Required command '$cmd' not found"
            exit 1
        fi
    done

    # Validate environment variables
    required_vars=("APP_KEY" "DB_USERNAME" "DB_PASSWORD" "REDIS_PASSWORD")
    for var in "${required_vars[@]}"; do
        if [ -z "${!var:-}" ]; then
            log_error "Required environment variable '$var' is not set"
            exit 1
        fi
    done

    log_success "Prerequisites validation passed"
}

create_release_directory() {
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local release_dir="${RELEASES_DIR}/${timestamp}"

    log_info "Creating release directory: $release_dir"
    mkdir -p "$release_dir"

    echo "$release_dir"

    # Save rollback information
    echo "DEPLOY_TIMESTAMP=$timestamp" > "$ROLLBACK_FILE"
    echo "RELEASE_DIR=$release_dir" >> "$ROLLBACK_FILE"
}

clone_repository() {
    local release_dir=$1

    log_info "Cloning repository to $release_dir"

    # Clone the repository
    git clone --branch main --depth 1 "$GIT_REPOSITORY" "$release_dir" 2>/dev/null || {
        log_error "Failed to clone repository"
        exit 1
    }

    # If deploying a specific tag/commit
    if [ "$DEPLOY_TAG" != "latest" ]; then
        cd "$release_dir"
        git checkout "$DEPLOY_TAG"
        cd - >/dev/null
    fi

    log_success "Repository cloned successfully"
}

install_dependencies() {
    local release_dir=$1

    log_info "Installing dependencies for release: $release_dir"

    cd "$release_dir"

    # Install PHP dependencies
    log_info "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader --no-interaction --quiet

    # Install Node dependencies and build assets
    log_info "Installing Node dependencies..."
    npm ci --silent

    log_info "Building frontend assets..."
    npm run build --silent

    cd - >/dev/null

    log_success "Dependencies installed successfully"
}

create_shared_symlinks() {
    local release_dir=$1

    log_info "Creating shared symlinks for $release_dir"

    # Create shared directory structure if it doesn't exist
    mkdir -p "$SHARED_DIR/storage"
    mkdir -p "$SHARED_DIR/storage/logs"
    mkdir -p "$SHARED_DIR/storage/framework/cache"
    mkdir -p "$SHARED_DIR/storage/framework/sessions"
    mkdir -p "$SHARED_DIR/storage/framework/views"
    mkdir -p "$SHARED_DIR/storage/app/public"

    # Remove existing storage directory from release
    rm -rf "$release_dir/storage"

    # Create symbolic links to shared storage
    ln -sf "$SHARED_DIR/storage" "$release_dir/storage"

    # Create .env symlink if using shared .env
    if [ -f "$SHARED_DIR/.env" ]; then
        ln -sf "$SHARED_DIR/.env" "$release_dir/.env"
    fi

    log_success "Shared symlinks created"
}

backup_database() {
    if [ "$BACKUP_DATABASE" != true ]; then
        log_info "Database backup skipped"
        return
    fi

    log_info "Creating database backup..."

    local backup_file="${SHARED_DIR}/backups/pre_deploy_$(date +%Y%m%d_%H%M%S).sql.gz"

    # Create backups directory
    mkdir -p "$(dirname "$backup_file")"

    # Backup database
    if pg_dump --version >/dev/null 2>&1; then
        # PostgreSQL backup
        PGPASSWORD="$DB_PASSWORD" pg_dump \
            -h "$DB_HOST" \
            -U "$DB_USERNAME" \
            -d "$DB_DATABASE" \
            --compress=9 \
            --format=custom \
            --file="$backup_file" \
            --no-owner \
            --no-privileges \
            --verbose
    else
        log_error "PostgreSQL client not found"
        exit 1
    fi

    # Store backup info for cleanup
    echo "LAST_BACKUP=$backup_file" >> "$ROLLBACK_FILE"

    log_success "Database backup completed: $backup_file"
}

run_pre_deploy_hooks() {
    local release_dir=$1

    log_info "Running pre-deployment hooks..."

    cd "$release_dir"

    # Put application in maintenance mode
    php artisan down --message="System is being updated. Please try again in a few minutes." --retry=60

    # Clear any existing caches
    php artisan config:clear
    php artisan cache:clear
    php artisan route:clear
    php artisan view:clear

    cd - >/dev/null

    log_success "Pre-deployment hooks completed"
}

run_database_migrations() {
    local release_dir=$1

    log_info "Running system database migrations..."

    cd "$release_dir"

    # Run system database migrations
    php artisan migrate --force --no-interaction

    if [ "$TENANT_MIGRATIONS_ENABLED" = true ]; then
        log_info "Running tenant database migrations..."
        php artisan tenants:migrate --force --no-interaction
    fi

    cd - >/dev/null

    log_success "Database migrations completed"
}

run_post_deploy_tasks() {
    local release_dir=$1

    log_info "Running post-deployment tasks..."

    cd "$release_dir"

    # Generate optimized application cache
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan optimize

    # Generate storage link if needed
    if [ ! -L "public/storage" ]; then
        php artisan storage:link
    fi

    # Warm up caches if needed
    php artisan tenant:cache-warmup || log_warning "Tenant cache warmup not available"

    cd - >/dev/null

    log_success "Post-deployment tasks completed"
}

switch_to_new_release() {
    local release_dir=$1

    log_info "Switching to new release: $release_dir"

    # Remove old current link
    if [ -L "$CURRENT_LINK" ]; then
        rm "$CURRENT_LINK"
    fi

    # Create new current link
    ln -sf "$release_dir" "$CURRENT_LINK"

    # Update release info
    echo "CURRENT_RELEASE=$release_dir" >> "$ROLLBACK_FILE"

    log_success "Switched to new release"
}

run_health_checks() {
    log_info "Running health checks..."

    local health_check_url="${APP_URL}/health-check"
    local timeout=$HEALTH_CHECK_TIMEOUT
    local start_time=$(date +%s)

    while [ $(( $(date +%s) - start_time )) -lt $timeout ]; do
        if curl -f -s --max-time 10 "$health_check_url" >/dev/null 2>&1; then
            log_success "Health check passed"
            return 0
        fi
        log_info "Health check failed, retrying..."
        sleep 10
    done

    log_error "Health check failed after ${timeout} seconds"
    return 1
}

complete_deployment() {
    local release_dir=$1

    log_info "Completing deployment..."

    cd "$release_dir"

    # Bring application out of maintenance mode
    php artisan up

    cd - >/dev/null

    # Clear old releases (keep last 5)
    if [ -d "$RELEASES_DIR" ]; then
        log_info "Cleaning up old releases..."
        ls -td "$RELEASES_DIR"/* | tail -n +6 | xargs rm -rf 2>/dev/null || true
    fi

    # Send deployment notifications
    send_deploy_notification "success"

    log_success "Deployment completed successfully!"
    log_info "New release: $release_dir"
}

perform_rollback() {
    if [ ! -f "$ROLLBACK_FILE" ]; then
        log_error "No rollback information available"
        return 1
    fi

    log_warning "Performing rollback..."

    # Source rollback information
    source "$ROLLBACK_FILE"

    # Restore database from backup if it exists
    if [ -n "${LAST_BACKUP:-}" ] && [ -f "$LAST_BACKUP" ]; then
        log_info "Restoring database from backup: $LAST_BACKUP"
        if pg_restore --version >/dev/null 2>&1; then
            PGPASSWORD="$DB_PASSWORD" pg_restore \
                -h "$DB_HOST" \
                -U "$DB_USERNAME" \
                -d "$DB_DATABASE" \
                --clean \
                --if-exists \
                --create \
                "$LAST_BACKUP"
        fi
    fi

    # Switch back to previous release if available
    if [ -n "${PREVIOUS_RELEASE:-}" ] && [ -d "$PREVIOUS_RELEASE" ]; then
        log_info "Switching back to previous release: $PREVIOUS_RELEASE"
        rm "$CURRENT_LINK" 2>/dev/null || true
        ln -sf "$PREVIOUS_RELEASE" "$CURRENT_LINK"
    fi

    # Bring application back online
    if [ -L "$CURRENT_LINK" ]; then
        cd "$CURRENT_LINK"
        php artisan up || log_warning "Failed to bring application online"
        cd - >/dev/null
    fi

    send_deploy_notification "rollback"
    log_success "Rollback completed"
}

send_deploy_notification() {
    local status=$1
    local webhook_url="${DEPLOY_WEBHOOK_URL:-}"

    if [ -n "$webhook_url" ]; then
        curl -X POST "$webhook_url" \
            -H 'Content-Type: application/json' \
            -d "{\"status\":\"$status\",\"environment\":\"$DEPLOY_ENV\",\"timestamp\":\"$(date)\"}" \
            --silent --output /dev/null || true
    fi
}

restart_services() {
    log_info "Restarting application services..."

    # Restart PHP-FPM
    if command -v systemctl >/dev/null 2>&1; then
        systemctl reload php8.3-fpm || log_warning "Failed to reload PHP-FPM"
    fi

    # Restart queue worker
    if command -v supervisorctl >/dev/null 2>&1; then
        supervisorctl restart all || log_warning "Failed to restart supervisor services"
    fi

    log_success "Services restarted"
}

# =========================================================================
# MAIN DEPLOYMENT LOGIC
# =========================================================================

main() {
    log_info "Starting $DEPLOY_ENV deployment process..."

    validate_prerequisites

    # Store current release for rollback
    if [ -L "$CURRENT_LINK" ]; then
        PREVIOUS_RELEASE=$(readlink "$CURRENT_LINK")
        echo "PREVIOUS_RELEASE=$PREVIOUS_RELEASE" >> "$ROLLBACK_FILE"
    fi

    local release_dir
    release_dir=$(create_release_directory)

    clone_repository "$release_dir"
    install_dependencies "$release_dir"
    create_shared_symlinks "$release_dir"

    if [ "$BACKUP_BEFORE_DEPLOY" = true ]; then
        backup_database
    fi

    run_pre_deploy_hooks "$release_dir"
    run_database_migrations "$release_dir"
    run_post_deploy_tasks "$release_dir"
    switch_to_new_release "$release_dir"
    restart_services

    if run_health_checks; then
        complete_deployment "$release_dir"
    else
        log_error "Health checks failed - rolling back deployment"
        perform_rollback
        exit 1
    fi
}

# =========================================================================
# SCRIPT ENTRY POINT
# =========================================================================

# Load environment variables if .env file exists
if [ -f ".env" ]; then
    set -a
    source .env
    set +a
fi

# Run main deployment function
main "$@"