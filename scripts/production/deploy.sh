#!/bin/bash

# Production Deployment Script for Unified Platform
# This script handles deployment of all interconnected systems:
# - Alumni Platform
# - Graduate Tracking System
# - Component Library System
# - Vue.js Page Builder System

set -e  # Exit on any error

# Configuration
DEPLOY_ENV=${DEPLOY_ENV:-production}
BACKUP_BEFORE_DEPLOY=${BACKUP_BEFORE_DEPLOY:-true}
SKIP_TESTS=${SKIP_TESTS:-false}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Pre-deployment checks
pre_deployment_checks() {
    log_info "Running pre-deployment checks..."

    # Check if required commands exist
    local required_commands=("php" "composer" "npm" "node")
    for cmd in "${required_commands[@]}"; do
        if ! command_exists "$cmd"; then
            log_error "Required command '$cmd' not found"
            exit 1
        fi
    done

    # Check if .env file exists
    if [ ! -f ".env" ]; then
        log_error ".env file not found"
        exit 1
    fi

    # Check database connectivity
    log_info "Checking database connectivity..."
    if ! php artisan db:monitor; then
        log_error "Database connection failed"
        exit 1
    fi

    log_success "Pre-deployment checks passed"
}

# Backup database and files
create_backup() {
    if [ "$BACKUP_BEFORE_DEPLOY" = true ]; then
        log_info "Creating backup before deployment..."

        local timestamp=$(date +"%Y%m%d_%H%M%S")
        local backup_dir="storage/backups/pre_deploy_$timestamp"

        mkdir -p "$backup_dir"

        # Database backup
        log_info "Backing up database..."
        php artisan backup:run --filename="pre_deploy_$timestamp"

        # File backup (storage and public files)
        log_info "Backing up files..."
        tar -czf "$backup_dir/files_backup.tar.gz" storage/ public/uploads/ 2>/dev/null || true

        log_success "Backup created: $backup_dir"
    else
        log_warning "Skipping backup as requested"
    fi
}

# Install/update PHP dependencies
install_php_dependencies() {
    log_info "Installing PHP dependencies..."

    # Clear composer cache
    composer clear-cache

    # Install dependencies
    if [ "$DEPLOY_ENV" = "production" ]; then
        composer install --no-dev --optimize-autoloader
    else
        composer install
    fi

    log_success "PHP dependencies installed"
}

# Install/update Node.js dependencies
install_node_dependencies() {
    log_info "Installing Node.js dependencies..."

    # Clear npm cache
    npm cache clean --force

    # Install dependencies
    npm ci

    log_success "Node.js dependencies installed"
}

# Build frontend assets
build_assets() {
    log_info "Building frontend assets..."

    # Build assets for production
    if [ "$DEPLOY_ENV" = "production" ]; then
        npm run build
    else
        npm run dev
    fi

    log_success "Frontend assets built"
}

# Run database migrations
run_migrations() {
    log_info "Running database migrations..."

    # Run central database migrations
    php artisan migrate --force

    # Run tenant migrations
    php artisan tenants:migrate --force

    log_success "Database migrations completed"
}

# Seed database
seed_database() {
    log_info "Seeding database..."

    # Seed central database
    php artisan db:seed --force

    # Seed tenant databases
    php artisan tenants:seed --force

    log_success "Database seeding completed"
}

# Clear and optimize caches
optimize_application() {
    log_info "Optimizing application..."

    # Clear all caches
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

    # Optimize for production
    if [ "$DEPLOY_ENV" = "production" ]; then
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan optimize
    fi

    # Warm up caches
    php artisan cache:warm

    log_success "Application optimized"
}

# Run tests
run_tests() {
    if [ "$SKIP_TESTS" = false ]; then
        log_info "Running tests..."

        # Run PHP tests
        if [ "$DEPLOY_ENV" = "production" ]; then
            php artisan test --parallel
        else
            php artisan test
        fi

        # Run JavaScript tests
        npm test

        log_success "Tests passed"
    else
        log_warning "Skipping tests as requested"
    fi
}

# Deploy to Kubernetes (if applicable)
deploy_to_kubernetes() {
    if command_exists kubectl && [ -f "infrastructure/k8s/deployment.yaml" ]; then
        log_info "Deploying to Kubernetes..."

        # Apply Kubernetes manifests
        kubectl apply -f infrastructure/k8s/

        # Wait for rollout to complete
        kubectl rollout status deployment/alumni-platform-app
        kubectl rollout status deployment/graduate-tracking-app
        kubectl rollout status deployment/component-library-app
        kubectl rollout status deployment/page-builder-app

        log_success "Kubernetes deployment completed"
    else
        log_info "Skipping Kubernetes deployment (kubectl not found or manifests not present)"
    fi
}

# Post-deployment health checks
health_checks() {
    log_info "Running post-deployment health checks..."

    # Check application health
    local health_url="${APP_URL:-http://localhost}/health"
    if command_exists curl; then
        if curl -f -s "$health_url" > /dev/null; then
            log_success "Application health check passed"
        else
            log_warning "Application health check failed"
        fi
    fi

    # Check database connectivity
    if php artisan db:monitor; then
        log_success "Database health check passed"
    else
        log_error "Database health check failed"
    fi
}

# Main deployment function
main() {
    log_info "Starting deployment for environment: $DEPLOY_ENV"

    pre_deployment_checks
    create_backup
    install_php_dependencies
    install_node_dependencies
    build_assets
    run_migrations
    seed_database
    optimize_application
    run_tests
    deploy_to_kubernetes
    health_checks

    log_success "Deployment completed successfully!"
    log_info "Don't forget to:"
    log_info "  - Update DNS records if needed"
    log_info "  - Configure monitoring and alerting"
    log_info "  - Update CDN cache if applicable"
    log_info "  - Notify stakeholders of deployment"
}

# Handle command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --env=*)
            DEPLOY_ENV="${1#*=}"
            shift
            ;;
        --no-backup)
            BACKUP_BEFORE_DEPLOY=false
            shift
            ;;
        --skip-tests)
            SKIP_TESTS=true
            shift
            ;;
        --help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --env=ENVIRONMENT    Set deployment environment (default: production)"
            echo "  --no-backup          Skip backup before deployment"
            echo "  --skip-tests         Skip running tests"
            echo "  --help               Show this help message"
            exit 0
            ;;
        *)
            log_error "Unknown option: $1"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# Run main deployment
main