# Deployment Documentation

This document provides comprehensive deployment procedures for the Alumni Platform, including environment setup, deployment processes, rollback procedures, and troubleshooting guides.

## Table of Contents

1. [Deployment Overview](#deployment-overview)
2. [Prerequisites](#prerequisites)
3. [Environment Setup](#environment-setup)
4. [Deployment Procedures](#deployment-procedures)
5. [Rollback Procedures](#rollback-procedures)
6. [Troubleshooting Guide](#troubleshooting-guide)
7. [Monitoring and Alerting](#monitoring-and-alerting)
8. [Backup and Recovery](#backup-and-recovery)

---

## Deployment Overview

### Deployment Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          Deployment Pipeline                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────┐ │
│  │   GitHub    │───▶│  CI/CD with │───▶│   Staging   │───▶│Production│ │
│  │   Repository│    │   Testing   │    │  Environment│    │Environment│ │
│  └─────────────┘    └─────────────┘    └─────────────┘    └─────────┘ │
│         │                  │                  │                  │       │
│         │                  │                  │                  │       │
│         ▼                  ▼                  ▼                  ▼       │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │                    Monitoring & Alerting                          │  │
│  └─────────────────────────────────────────────────────────────────┘  │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### Deployment Environments

| Environment | Purpose | Access | URL |
|-------------|---------|--------|-----|
| Development | Developer testing | Internal | dev.alumate.com |
| Staging | Pre-production testing | Internal/QA | staging.alumate.com |
| Production | Live environment | Public | www.alumate.com |

### Deployment Strategies

- **Zero-Downtime Deployment**: Symlink-based release switching
- **Blue-Green Deployment**: Two production environments with instant switch
- **Rolling Deployment**: Gradual rollout across multiple instances
- **Canary Deployment**: Percentage-based traffic splitting

### Key Components

| Component | Technology | Purpose |
|-----------|------------|---------|
| Application | Laravel 11 + PHP 8.3 | Core application |
| Frontend | Vue.js 3 + TypeScript | User interface |
| Database | PostgreSQL 17 | Primary data store |
| Cache | Redis 7 | Cache and sessions |
| Queue | Redis + Horizon | Background jobs |
| Web Server | Nginx | Reverse proxy |
| Containerization | Docker | Environment consistency |
| CI/CD | GitHub Actions | Automation |

---

## Prerequisites

### System Requirements

#### Development Machine

```bash
# Minimum Requirements
- PHP 8.3+
- Node.js 18+
- Composer 2.x
- PostgreSQL 17+ (local)
- Redis 7+ (local)
- Docker Desktop
- Git
```

#### Production Server

```bash
# Minimum Requirements
- CPU: 2 cores (4+ recommended)
- RAM: 4 GB (8+ GB recommended)
- Storage: 50 GB SSD (100+ GB recommended)
- OS: Ubuntu 22.04 LTS / RHEL 8+ / Debian 11+
- Docker: 24+
- Docker Compose: 2+
```

### Required Software Installation

#### PHP 8.3 (Ubuntu/Debian)

```bash
# Add PHP repository
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# Install PHP and extensions
sudo apt install -y php8.3 \
    php8.3-cli \
    php8.3-fpm \
    php8.3-mysql \
    php8.3-pgsql \
    php8.3-redis \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-bcmath \
    php8.3-tokenizer \
    php8.3-pdo \
    php8.3-pdo_pgsql \
    php8.3-gd \
    php8.3-intl \
    php8.3-sockets
```

#### Node.js 22 (Ubuntu/Debian)

```bash
# Install NodeSource repository
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -

# Install Node.js
sudo apt-get install -y nodejs
```

#### Docker (Ubuntu/Debian)

```bash
# Install Docker
curl -fsSL https://get.docker.com | sh

# Add user to docker group
sudo usermod -aG docker $USER

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### Required Accounts and Credentials

| Service | Required For | Setup Location |
|---------|--------------|---------------|
| GitHub | CI/CD access | GitHub Settings > Developers > OAuth Apps |
| PostgreSQL | Database | Infrastructure/production/.env.production.example |
| Redis | Cache/Queue | Infrastructure/production/.env.production.example |
| S3/Cloud Storage | Backups | AWS/GCP/Azure Console |
| Monitoring | Alerts | Sentry/Datadog/New Relic Console |
| SSL Certificates | HTTPS | Let's Encrypt / Certificate Authority |

### Git Repository Setup

```bash
# Clone the repository
git clone https://github.com/your-org/alumate.git
cd alumate

# Checkout the target branch
git checkout develop  # For staging
git checkout main     # For production

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
```

---

## Environment Setup

### Environment Files Configuration

#### Production Environment (.env.production)

```bash
# Application Settings
APP_NAME="Alumni Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.alumate.com
APP_KEY=base64:your-generated-app-key

# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=your-db-host.example.com
DB_PORT=5432
DB_DATABASE=alumni_platform
DB_USERNAME=prod_db_user
DB_PASSWORD=your-secure-password
DB_SSLMODE=require

# Redis Configuration
REDIS_HOST=your-redis-host.example.com
REDIS_PORT=6379
REDIS_PASSWORD=your-redis-password
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2

# Cache Configuration
CACHE_DRIVER=redis
CACHE_STORE=redis
CACHE_PREFIX=alumate_cache_

# Session Configuration
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true

# Queue Configuration
QUEUE_CONNECTION=redis
HORIZON_PREFIX=prod

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=no-reply@alumate.com
MAIL_PASSWORD=your-mail-password
MAIL_ENCRYPTION=tls

# Storage Configuration
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_REGION=us-east-1
AWS_BUCKET=alumate-production
AWS_ENDPOINT=https://s3.amazonaws.com

# Monitoring Configuration
SENTRY_ENABLED=true
SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/project
SENTRY_ENVIRONMENT=production
SENTRY_TRACES_SAMPLE_RATE=0.1

# Alerting Configuration
ALERTS_ENABLED=true
ALERT_EMAIL_RECIPIENTS=ops@alumate.com
ALERT_SLACK_WEBHOOK=https://hooks.slack.com/services/xxx

# Backup Configuration
BACKUP_ENABLED=true
BACKUP_PROVIDER=aws_s3
BACKUP_AWS_BUCKET=alumate-backups
BACKUP_RETENTION_DAYS=30

# Security Configuration
PASSWORD_ROUNDS=12
2FA_ENABLED=true
RATE_LIMITER_MAX_ATTEMPTS=5

# GDPR Configuration
GDPR_COMPLIANCE_MODE=true
DATA_RETENTION_DAYS=365
```

#### Staging Environment (.env.staging)

```bash
# Copy from production and modify
APP_ENV=staging
APP_DEBUG=false
DB_HOST=your-staging-db-host.example.com
REDIS_HOST=your-staging-redis-host.example.com
SENTRY_ENVIRONMENT=staging
```

#### Development Environment (.env)

```bash
# Local development configuration
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=alumni_platform_dev
DB_USERNAME=postgres
DB_PASSWORD=password
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

### Database Setup

#### Create Production Database

```sql
-- Connect to PostgreSQL as superuser
psql -U postgres -h localhost

-- Create database
CREATE DATABASE alumni_platform;

-- Create user
CREATE USER prod_db_user WITH ENCRYPTED PASSWORD 'your-secure-password';

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE alumni_platform TO prod_db_user;

-- Connect to database
\c alumni_platform

-- Grant schema permissions
GRANT ALL ON SCHEMA public TO prod_db_user;

-- Create extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
```

#### Run Migrations

```bash
# System migrations
php artisan migrate --force

# Tenant migrations
php artisan tenants:migrate --force

# Seed required data
php artisan db:seed --force
```

### SSL Certificate Setup

#### Using Let's Encrypt (Recommended)

```bash
# Install certbot
sudo apt install certbot python3-certbot-nginx

# Generate certificate
sudo certbot --nginx -d www.alumate.com -d alumate.com

# Auto-renewal
sudo systemctl enable certbot-renew.timer
sudo certbot renew --dry-run
```

#### Manual SSL Setup

```bash
# Generate self-signed certificate (testing only)
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/private/alumate.key \
  -out /etc/ssl/certs/alumate.crt

# Configure nginx to use certificate
ssl_certificate /etc/ssl/certs/alumate.crt;
ssl_certificate_key /etc/ssl/private/alumate.key;
```

### Docker Configuration

#### Production Docker Compose

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: infrastructure/production/Dockerfile.php
    image: alumate/app:${PHP_VERSION:-8.3}
    container_name: alumate-app
    restart: unless-stopped
    environment:
      - APP_ENV=production
      - DB_CONNECTION=pgsql
      - REDIS_HOST=redis
      - CACHE_DRIVER=redis
      - SESSION_DRIVER=redis
      - QUEUE_CONNECTION=redis
    volumes:
      - app_storage:/var/www/html/storage
      - app_logs:/var/www/html/storage/logs
    depends_on:
      - redis
      - db
    networks:
      - alumate-network
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/health"]
      interval: 30s
      timeout: 10s
      retries: 3

  nginx:
    build:
      context: .
      dockerfile: infrastructure/production/Dockerfile.nginx
    image: alumate/nginx:${NGINX_VERSION:-1.25}
    container_name: alumate-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - app_public:/var/www/html/public:ro
      - ./infrastructure/production/config/nginx:/etc/nginx/conf.d:ro
      - ./infrastructure/production/config/nginx/snippets:/etc/nginx/snippets:ro
    depends_on:
      - app
    networks:
      - alumate-network
    healthcheck:
      test: ["CMD", "nginx", "-t"]
      interval: 30s
      timeout: 10s
      retries: 3

  redis:
    image: redis:7-alpine
    container_name: alumate-redis
    restart: unless-stopped
    command: redis-server --requirepass ${REDIS_PASSWORD} --maxmemory 256mb --maxmemory-policy allkeys-lru
    volumes:
      - redis_data:/data
    networks:
      - alumate-network
    healthcheck:
      test: ["CMD", "redis-cli", "-a", "${REDIS_PASSWORD}", "ping"]
      interval: 30s
      timeout: 10s
      retries: 3

  db:
    image: postgres:17-alpine
    container_name: alumate-db
    restart: unless-stopped
    environment:
      - POSTGRES_DB=alumni_platform
      - POSTGRES_USER=${DB_USERNAME}
      - POSTGRES_PASSWORD=${DB_PASSWORD}
    volumes:
      - pg_data:/var/lib/postgresql/data
    networks:
      - alumate-network
    healthcheck:
      test: ["CMD", "pg_isready", "-U", "${DB_USERNAME}"]
      interval: 30s
      timeout: 10s
      retries: 3

  queue-worker:
    image: alumate/app:${PHP_VERSION:-8.3}
    container_name: alumate-worker
    restart: unless-stopped
    command: php artisan horizon
    environment:
      - APP_ENV=production
    volumes:
      - app_storage:/var/www/html/storage
    depends_on:
      - redis
      - db
    networks:
      - alumate-network

  scheduler:
    image: alumate/app:${PHP_VERSION:-8.3}
    container_name: alumate-scheduler
    restart: unless-stopped
    command: php artisan schedule:run
    environment:
      - APP_ENV=production
    volumes:
      - app_storage:/var/www/html/storage
    depends_on:
      - redis
      - db
    networks:
      - alumate-network

volumes:
  app_storage:
  app_logs:
  redis_data:
  pg_data:

networks:
  alumate-network:
    driver: bridge
```

---

## Deployment Procedures

### Pre-Deployment Checklist

Before deploying to any environment, ensure the following:

```bash
# 1. Code changes committed and pushed
git status
git log --oneline -5

# 2. Tests passing locally
npm run test
php artisan test

# 3. No pending migrations
php artisan migrate:status

# 4. Dependencies up to date
composer update --no-dev
npm update

# 5. Environment variables configured
cat .env.production | grep -v "^#" | grep -v "^$"

# 6. SSL certificates valid
openssl x509 -enddate -noout -in /etc/letsencrypt/live/alumate.com/fullchain.pem

# 7. Database backup available
php artisan backup:status

# 8. Disk space sufficient
df -h /var/www/html
```

### Deployment to Development Environment

#### Manual Deployment

```bash
# SSH into development server
ssh dev-user@dev.alumate.com

# Navigate to application directory
cd /var/www/html/alumate

# Pull latest changes
git fetch origin
git checkout develop
git pull origin develop

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# Build frontend assets
npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# Restart queue workers
php artisan horizon:terminate
php artisan horizon

# Restart scheduler
sudo systemctl restart alumate-scheduler

# Verify deployment
curl -f https://dev.alumate.com/health
```

#### Automated Deployment (GitHub Actions)

```yaml
# Triggers on push to develop branch
on:
  push:
    branches:
      - develop
```

### Deployment to Staging Environment

#### Manual Deployment

```bash
# SSH into staging server
ssh staging-user@staging.alumate.com

# Navigate to application directory
cd /var/www/html/alumate

# Pull latest changes
git fetch origin
git checkout staging
git pull origin staging

# Run deployment script
./infrastructure/production/deploy.production.sh

# Verify deployment
curl -f https://staging.alumate.com/health
curl -f https://staging.alumate.com/health/detailed
```

#### Automated Deployment (GitHub Actions)

```yaml
# Triggers on push to staging branch or merge to develop
on:
  push:
    branches:
      - staging
      - develop
```

#### Deployment Script (deploy.production.sh)

```bash
#!/bin/bash

set -e

# Configuration
DEPLOY_ENV=${DEPLOY_ENV:-staging}
DEPLOY_PATH=/var/www/html/alumate
BACKUP_DATABASE=${BACKUP_DATABASE:-true}
ENABLE_ROLLBACK=${ENABLE_ROLLBACK:-true}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Pre-deployment checks
pre_deploy_checks() {
    log_info "Running pre-deployment checks..."
    
    # Check disk space (require at least 2GB)
    AVAILABLE=$(df -BG /var/www/html | tail -1 | awk '{print $4}' | sed 's/G//')
    if [ "$AVAILABLE" -lt 2 ]; then
        log_error "Insufficient disk space: ${AVAILABLE}GB available"
        exit 1
    fi
    
    # Check database connection
    php artisan db:connect || exit 1
    
    # Check Redis connection
    redis-cli -h ${REDIS_HOST} -a ${REDIS_PASSWORD} ping || exit 1
    
    log_info "Pre-deployment checks passed"
}

# Backup database
backup_database() {
    if [ "$BACKUP_DATABASE" = "true" ]; then
        log_info "Creating database backup..."
        php artisan backup:run --only-db --force
        log_info "Database backup completed"
    fi
}

# Create release directory
create_release() {
    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    RELEASE_DIR="${DEPLOY_PATH}/releases/${TIMESTAMP}"
    
    log_info "Creating release directory: ${RELEASE_DIR}"
    mkdir -p "${RELEASE_DIR}"
    
    # Clone repository
    git clone ${GIT_REPOSITORY:-$(git remote get-url origin)} "${RELEASE_DIR}"
    cd "${RELEASE_DIR}"
    
    # Checkout specific tag/branch if provided
    if [ -n "$DEPLOY_TAG" ]; then
        git checkout "$DEPLOY_TAG"
    fi
    
    # Install dependencies
    composer install --no-dev --optimize-autoloader --no-interaction
    npm ci --silent
    
    # Build frontend
    npm run build --silent
    
    # Copy environment file
    cp "${DEPLOY_PATH}/.env.production" "${RELEASE_DIR}/.env"
    
    log_info "Release created: ${TIMESTAMP}"
}

# Run migrations
run_migrations() {
    log_info "Running database migrations..."
    php artisan migrate --force --path=database/migrations
    php artisan tenants:migrate --force
    log_info "Migrations completed"
}

# Switch symlink
switch_symlink() {
    TIMESTAMP=$1
    RELEASE_DIR="${DEPLOY_PATH}/releases/${TIMESTAMP}"
    
    log_info "Switching to new release..."
    ln -sfn "${RELEASE_DIR}" "${DEPLOY_PATH}/current"
    
    # Clear and rebuild caches
    cd "${DEPLOY_PATH}/current"
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    log_info "Symlink switched to: ${TIMESTAMP}"
}

# Health check
health_check() {
    log_info "Running health checks..."
    
    MAX_RETRIES=3
    RETRY_COUNT=0
    HEALTHY=false
    
    while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
        RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" https://${DEPLOY_DOMAIN:-localhost}/health)
        
        if [ "$RESPONSE" = "200" ]; then
            HEALTHY=true
            break
        fi
        
        RETRY_COUNT=$((RETRY_COUNT + 1))
        log_warn "Health check failed (attempt ${RETRY_COUNT}/${MAX_RETRIES})"
        sleep 10
    done
    
    if [ "$HEALTHY" = "true" ]; then
        log_info "Health check passed"
        return 0
    else
        log_error "Health check failed"
        return 1
    fi
}

# Rollback to previous release
rollback() {
    log_info "Initiating rollback..."
    
    # Get previous release
    PREVIOUS_RELEASE=$(ls -1t "${DEPLOY_PATH}/releases" | head -2 | tail -1)
    
    if [ -z "$PREVIOUS_RELEASE" ]; then
        log_error "No previous release found"
        exit 1
    fi
    
    log_info "Rolling back to: ${PREVIOUS_RELEASE}"
    
    # Switch symlink
    ln -sfn "${DEPLOY_PATH}/releases/${PREVIOUS_RELEASE}" "${DEPLOY_PATH}/current"
    
    # Restore database if needed
    if [ "$BACKUP_DATABASE" = "true" ]; then
        log_info "Restoring database backup..."
        php artisan backup:restore latest --force
    fi
    
    # Clear caches
    cd "${DEPLOY_PATH}/current"
    php artisan optimize:clear
    php artisan config:cache
    
    # Verify
    if health_check; then
        log_info "Rollback successful"
    else
        log_error "Rollback failed - manual intervention required"
        exit 1
    fi
}

# Cleanup old releases
cleanup_releases() {
    log_info "Cleaning up old releases..."
    
    # Keep last 5 releases
    cd "${DEPLOY_PATH}/releases"
    ls -1t | tail -n +6 | xargs -r rm -rf
    
    log_info "Cleanup completed"
}

# Main deployment flow
main() {
    log_info "Starting deployment to ${DEPLOY_ENV}..."
    
    # Parse arguments
    case "$1" in
        rollback)
            rollback
            exit 0
            ;;
        *)
            # Normal deployment
            pre_deploy_checks
            backup_database
            TIMESTAMP=$(date +%Y%m%d_%H%M%S)
            create_release
            run_migrations
            switch_symlink "$TIMESTAMP"
            
            if health_check; then
                cleanup_releases
                log_info "Deployment completed successfully"
            else
                if [ "$ENABLE_ROLLBACK" = "true" ]; then
                    log_warn "Health check failed - initiating rollback"
                    rollback
                else
                    log_error "Deployment failed - manual intervention required"
                    exit 1
                fi
            fi
            ;;
    esac
}

main "$@"
```

### Deployment to Production Environment

#### Pre-Production Checklist

```bash
# 1. Review changes since last deployment
git log --oneline production..staging

# 2. Run full test suite
php artisan test --filter=Production

# 3. Verify all migrations are safe
php artisan migrate:status

# 4. Check for deprecated features
grep -r "deprecated" app/ 2>/dev/null || echo "No deprecation warnings"

# 5. Notify stakeholders
```

#### Production Deployment Steps

```bash
# 1. Enable maintenance mode (optional - for zero-downtime, skip this)
php artisan down --refresh=60

# 2. Run deployment script
./infrastructure/production/deploy.production.sh

# 3. If deployment successful, verify
curl -f https://www.alumate.com/health
curl -f https://www.alumate.com/health/detailed

# 4. Check application logs
tail -f /var/www/html/alumate/current/storage/logs/laravel.log

# 5. Monitor queue status
php artisan horizon:status

# 6. Bring out of maintenance mode (if used)
php artisan up
```

#### Automated Production Deployment

Production deployments are triggered manually through GitHub Actions:

```yaml
# Manual trigger in GitHub Actions
on:
  workflow_dispatch:
    inputs:
      environment:
        description: 'Target environment'
        required: true
        default: 'production'
        type: choice
        options:
          - production
```

---

## Rollback Procedures

### Rollback Scenarios

| Scenario | Trigger | Response Time |
|----------|---------|--------------|
| Critical bug detected | Automated health check | < 5 minutes |
| Performance degradation | Monitoring alert | < 15 minutes |
| Database issue | Manual detection | < 30 minutes |
| Security vulnerability | Security scan | Immediate |

### Automated Rollback

The deployment script automatically triggers rollback if health checks fail:

```bash
# Automatic rollback is built into deploy.production.sh
# Triggered when:
# - Health check fails 3 times
# - Application returns HTTP 5xx
# - Database connection fails
```

### Manual Rollback Procedures

#### Via Deployment Script

```bash
# Rollback to previous release
./infrastructure/production/deploy.production.sh rollback

# Rollback to specific release
./infrastructure/production/deploy.production.sh rollback --target=20240115_120000
```

#### Via GitHub Actions

```yaml
# Manual rollback workflow trigger
on:
  workflow_dispatch:
    inputs:
      environment:
        required: true
        type: choice
        options:
          - production
          - staging
      reason:
        required: true
      target_release:
        required: false
```

#### Manual Server Rollback

```bash
# SSH into server
ssh prod-user@www.alumate.com

# Navigate to application directory
cd /var/www/html/alumate

# Check available releases
ls -la releases/

# Switch to previous release
ln -sfn releases/20240115_120000 current

# Clear caches
cd current
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# Restart workers
php artisan horizon:terminate
php artisan horizon

# Verify
curl -f https://www.alumate.com/health
```

#### Database Rollback

```bash
# List available database backups
php artisan backup:status

# Restore to specific backup
php artisan backup:restore database backups/database/db_2024-01-15_010000.sql.gz --force
```

### Post-Rollback Actions

```bash
# 1. Document the incident
cat > /var/www/html/alumate/current/incidents/$(date +%Y%m%d_%H%M%S).md << EOF
# Incident Report

**Date:** $(date)
**Environment:** Production
**Issue:** [Description]
**Rollback Triggered:** [Yes/No]
**Root Cause:** [Analysis]
**Resolution:** [Steps taken]
**Preventive Measures:** [Actions needed]
EOF

# 2. Notify stakeholders
# 3. Create Jira ticket for fix
# 4. Schedule post-mortem
```

---

## Troubleshooting Guide

### Common Deployment Issues

#### Issue: Deployment Fails at Pre-Checks

**Symptoms:**
- "Insufficient disk space" error
- Database connection failed
- Redis connection failed

**Solutions:**

```bash
# Check disk space
df -h /var/www/html

# Clean up old releases
ls -la releases/
rm -rf releases/20240101_000000

# Check database connection
php artisan db:connect

# Verify credentials
cat .env.production | grep DB_

# Test Redis connection
redis-cli -h ${REDIS_HOST} -p ${REDIS_PORT} -a ${REDIS_PASSWORD} ping
```

#### Issue: Migration Fails

**Symptoms:**
- "Table already exists" error
- "Column already exists" error
- Deadlock or lock wait timeout

**Solutions:**

```bash
# Check migration status
php artisan migrate:status

# Rollback specific migration
php artisan migrate:rollback --path=database/migrations/2024_01_15_add_field.php

# Check for running transactions
php artisan tinker
> DB::select('SELECT * FROM pg_stat_activity WHERE state = ?', ['active']);

# Retry migration
php artisan migrate --force
```

#### Issue: 500 Error After Deployment

**Symptoms:**
- HTTP 500 error on all pages
- Blank white screen
- Application unavailable

**Solutions:**

```bash
# Check application logs
tail -f storage/logs/laravel.log

# Check PHP-FPM logs
docker-compose logs php-fpm

# Verify permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data .

# Clear all caches
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Check environment configuration
php artisan env
php artisan config:show
```

#### Issue: Queue Workers Not Processing

**Symptoms:**
- Jobs accumulating
- No background processing
- Delayed notifications

**Solutions:**

```bash
# Check Horizon status
php artisan horizon:status

# Check for failed jobs
php artisan queue:failed

# Restart Horizon
php artisan horizon:terminate
php artisan horizon

# Check Redis connection
redis-cli -h ${REDIS_HOST} -a ${REDIS_PASSWORD} keys "*horizon*"

# Monitor queue
php artisan horizon:worklist
```

#### Issue: SSL Certificate Issues

**Symptoms:**
- "Certificate expired" warnings
- HTTPS not working
- Mixed content warnings

**Solutions:**

```bash
# Check certificate expiration
openssl x509 -enddate -noout -in /etc/letsencrypt/live/alumate.com/fullchain.pem

# Renew Let's Encrypt certificate
sudo certbot renew --quiet

# Force nginx to reload
sudo nginx -t
sudo systemctl reload nginx

# Verify SSL configuration
openssl s_client -connect alumate.com:443 -servername alumate.com
```

#### Issue: High Memory Usage

**Symptoms:**
- OOM (Out of Memory) errors
- Slow response times
- Container restarts

**Solutions:**

```bash
# Check memory usage
docker stats

# Check PHP memory limit
php -i | grep memory_limit

# Increase memory limit in php.ini
memory_limit = 512M

# Restart containers
docker-compose restart app

# Clear Redis memory
redis-cli FLUSHALL
```

### Log Locations

| Log Type | Location | Purpose |
|----------|----------|---------|
| Application | `storage/logs/laravel.log` | Main application logs |
| Nginx | `/var/log/nginx/` | Web server logs |
| PHP-FPM | `/var/log/php-fpm/` | PHP process logs |
| PostgreSQL | `/var/log/postgresql/` | Database logs |
| Redis | `/var/log/redis/` | Cache server logs |
| Docker | `docker-compose logs` | Container logs |
| Deployment | `storage/logs/deploy.log` | Deployment logs |

### Diagnostic Commands

```bash
# System health check
php artisan health:check

# Database diagnostics
php artisan db:connect
php artisan tinker --execute="DB::select('SELECT version()')"

# Redis diagnostics
redis-cli info
redis-cli memory usage <key>

# Queue diagnostics
php artisan horizon:status
php artisan queue:work --once --verbose

# Application diagnostics
php artisan env
php artisan config:show
php artisan route:list
```

### Performance Issues

#### Slow Response Times

```bash
# Check response time
curl -w "\nTime: %{time_total}s\n" -o /dev/null -s https://alumate.com

# Check database queries
php artisan tinker
> \DB::listen(function($query) { dump($query->sql, $query->bindings, $query->time); });

# Check cache hit rate
redis-cli info stats | grep keyspace_hits

# Check PHP-FPM status
curl https://alumate.com/status
```

#### High CPU Usage

```bash
# Check system load
top -bn1 | head -20

# Check PHP processes
ps aux | grep php

# Check for slow queries
tail -f /var/log/postgresql/postgresql.log | grep 'duration:'
```

---

## Monitoring and Alerting

### Health Check Endpoints

| Endpoint | Description | Response |
|----------|-------------|----------|
| `/health` | Basic health check | 200 OK or 503 Service Unavailable |
| `/health/detailed` | Detailed system status | JSON with all component status |
| `/metrics` | Prometheus metrics | Prometheus format |
| `/ready` | Kubernetes readiness probe | 200 OK when ready |
| `/live` | Kubernetes liveness probe | 200 OK when alive |

### Alert Rules

| Severity | Alert | Condition | Response |
|----------|-------|-----------|----------|
| Critical | Application Down | Health check fails | Immediate rollback |
| Critical | Database Unavailable | Connection fails | Alert + Auto-reconnect |
| Critical | High Memory (>95%) | Memory threshold | Alert + Scale up |
| High | High CPU (>90%) | CPU threshold | Alert |
| High | Disk Space (>85%) | Storage threshold | Alert + Cleanup |
| Medium | Slow Response (>3s) | P99 latency | Alert |
| Medium | High Error Rate (>5%) | Error percentage | Alert |

### Monitoring Setup

#### Configure Monitoring

```bash
# Run monitoring setup script
./infrastructure/production/setup-monitoring.sh

# This configures:
# - Prometheus metrics collection
# - Alert thresholds
# - Dashboard configurations
# - Notification channels
```

#### View Metrics

```bash
# Prometheus
curl http://localhost:9090/api/v1/query?query=alumate_http_requests_total

# Grafana Dashboards
open http://localhost:3000/d/alumate-overview
```

### Alert Notification Channels

#### Email Alerts

```bash
# Configure email recipients
ALERT_EMAIL_RECIPIENTS=ops@alumate.com,dev@alumate.com
ALERT_EMAIL_ENABLED=true
```

#### Slack Alerts

```bash
# Configure Slack webhook
ALERT_SLACK_ENABLED=true
ALERT_SLACK_WEBHOOK=https://hooks.slack.com/services/xxx
ALERT_SLACK_CHANNEL=#alerts
```

#### PagerDuty Alerts

```bash
# Configure PagerDuty
ALERT_PAGERDUTY_ENABLED=true
PAGERDUTY_INTEGRATION_KEY=xxx
```

### Dashboard Access

| Dashboard | URL | Purpose |
|-----------|-----|---------|
| Overview | `/admin/monitoring` | System-wide metrics |
| Grafana | `http://localhost:3000` | Full visualization |
| Prometheus | `http://localhost:9090` | Metrics query |
| Health | `/health/detailed` | System status |

---

## Backup and Recovery

### Backup Types

| Type | Schedule | Retention | Purpose |
|------|----------|-----------|---------|
| Database | Daily 1 AM | 30 days | Data recovery |
| Files | Daily 2 AM | 90 days | File recovery |
| Configuration | Weekly Monday 3 AM | 365 days | Config recovery |

### Backup Commands

```bash
# Full backup
php artisan backup:run --all

# Database only
php artisan backup:run --only-db

# Files only
php artisan backup:run --only-files

# Configuration only
php artisan backup:run --only-config

# Verify backups
php artisan backup:verify

# List backups
php artisan backup:status

# Clean old backups
php artisan backup:cleanup
```

### Recovery Procedures

#### Database Recovery

```bash
# List available backups
php artisan backup:status

# Restore database
php artisan backup:restore database backups/database/db_2024-01-15_010000.sql.gz --force

# Restore to point-in-time (PostgreSQL)
php artisan tinker
> \DB::statement('SELECT pg_restore --jobs=4 --clean --if-exists -d alumni_platform backups/database/db_2024-01-15.dump');
```

#### Files Recovery

```bash
# Restore files
php artisan backup:restore files backups/files/files_2024-01-15_020000.tar.gz --force
```

#### Full Recovery

```bash
# 1. Put application in maintenance mode
php artisan down

# 2. Stop workers
php artisan horizon:terminate

# 3. Restore database
php artisan backup:restore database <backup_file> --force

# 4. Restore files
php artisan backup:restore files <backup_file> --force

# 5. Clear caches
php artisan optimize:clear

# 6. Restart workers
php artisan horizon

# 7. Bring application back online
php artisan up

# 8. Verify
curl -f https://alumate.com/health
```

### Remote Backup Storage

#### AWS S3

```bash
# Configure
BACKUP_PROVIDER=aws_s3
BACKUP_AWS_BUCKET=alumate-backups
BACKUP_AWS_REGION=us-east-1
```

#### Google Cloud Storage

```bash
# Configure
BACKUP_PROVIDER=gcp
BACKUP_GCS_BUCKET=alumate-backups
```

#### Azure Blob Storage

```bash
# Configure
BACKUP_PROVIDER=azure
BACKUP_AZURE_CONTAINER=backups
```

---

## Support and Resources

### Documentation References

- [Production Configuration](docs/PRODUCTION-CONFIGURATION.md)
- [Monitoring Documentation](docs/MONITORING.md)
- [Backup Documentation](docs/BACKUP.md)
- [SSL/TLS Documentation](docs/SSL-TLS.md)
- [Load Balancing Documentation](docs/LOAD-BALANCING.md)

### Emergency Contacts

| Role | Contact | Response |
|------|---------|----------|
| On-call Engineer | See PagerDuty rotation | 15 minutes |
| DevOps Lead | devops@alumate.com | 1 hour |
| Infrastructure | infra@alumate.com | 4 hours |
| CTO | cto@alumate.com | Critical issues |

### Post-Incident Procedures

1. **Document the incident** in the incident log
2. **Create fix ticket** in project management tool
3. **Schedule post-mortem** within 48 hours
4. **Update runbooks** based on lessons learned
5. **Test rollback** procedures after fix

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2024-09-01 | Initial deployment documentation |

---

*Last Updated: 2024-09-01*
