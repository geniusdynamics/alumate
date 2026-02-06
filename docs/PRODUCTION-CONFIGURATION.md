# Production Environment Configuration Documentation

This document provides comprehensive documentation for configuring the Alumni Platform production environment.

## Table of Contents

1. [Overview](#overview)
2. [Environment Files](#environment-files)
3. [Configuration Categories](#configuration-categories)
4. [Database Configuration](#database-configuration)
5. [Cache Configuration](#cache-configuration)
6. [Queue Configuration](#queue-configuration)
7. [Session Configuration](#session-configuration)
8. [Mail Configuration](#mail-configuration)
9. [Analytics Configuration](#analytics-configuration)
10. [Security Configuration](#security-configuration)
11. [Performance Configuration](#performance-configuration)
12. [Monitoring Configuration](#monitoring-configuration)
13. [Backup Configuration](#backup-configuration)
14. [External Services](#external-services)
15. [Environment-Specific Settings](#environment-specific-settings)
16. [Troubleshooting](#troubleshooting)

## Overview

The Alumni Platform uses a multi-layered configuration system:
- **Environment Variables** (`.env.production`) - Runtime configuration
- **Configuration Files** (`config/production.php`) - PHP configuration
- **Docker Configuration** (`infrastructure/production/`) - Container configuration

## Environment Files

### Primary Files

| File | Location | Purpose |
|------|----------|---------|
| `.env.production` | Project root | Main production environment variables |
| `.env.production.example` | `infrastructure/production/` | Template for production environment |
| `config/production.php` | `config/` | Production-specific PHP configuration |

### Configuration Categories

The environment is organized into logical categories:

```
# Application Settings (Core)
# Logging Configuration
# Cache Configuration
# Session Configuration
# Queue Configuration
# Database Configuration
# Redis Configuration
# Multi-Tenancy Configuration
# Mail Configuration
# AWS S3 Configuration
# Third-Party Services
# Monitoring & Observability
# Alerting & Notifications
# Security Settings
# Backup & Storage
# Maintenance & Cleanup
# Performance Tuning
# Analytics Configuration
# Health Checks
```

## Database Configuration

### PostgreSQL Settings

```bash
# Required Settings
DB_CONNECTION=pgsql
DB_HOST=your-db-host.example.com
DB_PORT=5432
DB_DATABASE=alumni_platform
DB_USERNAME=prod_db_user
DB_PASSWORD=your-secure-password

# SSL/TLS (Required for production)
DB_SSLMODE=require

# Optional Settings
DB_CHARSET=utf8
DB_SCHEMA=public
```

### Production Database Considerations

1. **SSL/TLS**: Always use `DB_SSLMODE=require` in production
2. **Connection Pooling**: Configure using PgBouncer for high load
3. **Read/Write Splitting**: Use read replicas for read-heavy workloads

```bash
# Read/Write Configuration
DB_READ_HOST=your-read-replica-host
DB_WRITE_HOST=your-primary-host
DB_STICKY_READS=true
```

## Cache Configuration

### Redis Settings

```bash
# Primary Cache
CACHE_DRIVER=redis
CACHE_STORE=redis
CACHE_PREFIX=alumate_cache_

# Redis Connection
REDIS_HOST=your-redis-host.example.com
REDIS_PORT=6379
REDIS_PASSWORD=your-secure-password

# Redis Databases (Isolation)
REDIS_DB=0              # Default
REDIS_CACHE_DB=1         # Cache
REDIS_SESSION_DB=2       # Sessions
REDIS_QUEUE_DB=3         # Queue

# Cluster Configuration
REDIS_CLUSTER=false      # Set true for Redis Cluster
```

### Cache Performance Settings

```bash
# Template Cache
TEMPLATE_CACHE_STORE=redis
TEMPLATE_L2_TTL=3600
TEMPLATE_CACHE_COMPRESSION=true

# Analytics Cache
ANALYTICS_CACHE_STORE=redis
ANALYTICS_CACHE_TTL=300
```

## Queue Configuration

### Redis Queue Settings

```bash
# Queue Connection
QUEUE_CONNECTION=redis
QUEUE_PREFIX=alumate_queue_

# Worker Settings
QUEUE_WORKER_SLEEP=3
QUEUE_WORKER_MAX_TRIES=3
QUEUE_WORKER_TIMEOUT=90
```

### Horizon Configuration

```bash
# Horizon Settings
HORIZON_PREFIX=prod-analytics
HORIZON_WORKERS=20
```

### Queue Priorities

Configure queue priorities in `config/horizon.php`:

```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default', 'low'],
            'balance' => 'auto',
            'processes' => 10,
            'tries' => 3,
            'timeout' => 90,
        ],
    ],
],
```

## Session Configuration

### Redis Session Settings

```bash
# Session Driver
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PREFIX=alumate_sess_

# Session Cookie
SESSION_COOKIE=alumate_session
SESSION_PATH=/
SESSION_DOMAIN=null

# Security
SESSION_SECURE=true
SESSION_HTTPONLY=true
SESSION_SAME_SITE=lax
```

### Session Security Best Practices

1. **Always use HTTPS** (`SESSION_SECURE=true`)
2. **Enable HTTP-only** (`SESSION_HTTPONLY=true`)
3. **Set SameSite** to `lax` or `strict`
4. **Use encryption** (`SESSION_ENCRYPT=true`)

## Mail Configuration

### SMTP Settings

```bash
# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls

# From Address
MAIL_FROM_ADDRESS=no-reply@your-domain.com
MAIL_FROM_NAME="Alumni Platform"
```

### Mail Providers

#### AWS SES
```bash
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
```

#### Mailgun
```bash
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.your-domain.com
MAILGUN_SECRET=your-mailgun-key
MAILGUN_ENDPOINT=api.mailgun.net
```

## Analytics Configuration

### Core Analytics Settings

```bash
# Feature Flags
FEATURE_ANALYTICS=true
FEATURE_NOTIFICATIONS=true
FEATURE_SEARCH=true

# Analytics Cache
ANALYTICS_CACHE_ENABLED=true
ANALYTICS_CACHE_TTL=300
ANALYTICS_CACHE_STORE=redis

# Data Retention
ANALYTICS_DATA_RETENTION_DAYS=90
ANALYTICS_SNAPSHOTS_RETENTION=365

# Performance
ANALYTICS_QUERY_TIMEOUT=60
ANALYTICS_MEMORY_LIMIT=512M
ANALYTICS_CHUNK_SIZE=1000
```

### External Analytics Services

#### Matomo
```bash
MATOMO_URL=https://your-matomo.com
MATOMO_SITE_ID=1
MATOMO_TOKEN=your-api-token
```

#### Google Analytics
```bash
GA_MEASUREMENT_ID=G-XXXXXXXXXX
GA_PROPERTY_ID=your-property-id
GA_API_SECRET=your-api-secret
```

## Security Configuration

### Authentication Security

```bash
# Password Hashing
PASSWORD_HASH=bcrypt
PASSWORD_ROUNDS=12

# Two-Factor Authentication
2FA_ENABLED=true
2FA_FORCE=false

# Rate Limiting
RATE_LIMITER_MAX_ATTEMPTS=5
RATE_LIMITER_DECAY_MINUTES=1

# Login Security
SECURITY_MAX_LOGIN_ATTEMPTS=5
SECURITY_LOCKOUT_DURATION=30
```

### Session Security

```bash
# Session Timeout
SECURITY_SESSION_TIMEOUT=120
SESSION_LIFETIME=120

# Session Tracking
SECURITY_TRACK_SUSPICIOUS=true
```

### GDPR Compliance

```bash
# GDPR Settings
GDPR_COMPLIANCE_MODE=true
DATA_RETENTION_DAYS=365
DATA_ANONYMIZE_AFTER_DAYS=90

# Encryption Keys
GDPR_ENCRYPTION_KEY=your-256-bit-key
GDPR_IV=your-initialization-vector
```

## Performance Configuration

### PHP-FPM Settings

```bash
# Process Manager
PHP_FPM_PM=dynamic
PHP_FPM_PM_MAX_CHILDREN=10
PHP_FPM_PM_START_SERVERS=2
PHP_FPM_PM_MIN_SPARE_SERVERS=1
PHP_FPM_PM_MAX_SPARE_SERVERS=5

# Performance
PHP_FPM_PM_MAX_REQUESTS=500
```

### Redis Performance

```bash
# Connection Pool
REDIS_POOL_SIZE=10
REDIS_POOL_TIMEOUT=10

# Memory
REDIS_MAX_MEMORY=256mb
REDIS_MAX_MEMORY_POLICY=allkeys-lru
```

### Queue Performance

```bash
# Horizon Workers
HORIZON_WORKERS=20

# Queue Settings
QUEUE_WORKER_TIMEOUT=90
QUEUE_WORKER_MAX_TRIES=3
```

## Monitoring Configuration

### Sentry

```bash
SENTRY_ENABLED=true
SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/project
SENTRY_ENVIRONMENT=production
SENTRY_TRACES_SAMPLE_RATE=0.1
```

### New Relic

```bash
NEW_RELIC_ENABLED=true
NEW_RELIC_APP_NAME="Alumni Platform"
NEW_RELIC_LICENSE_KEY=your-license-key
```

### Datadog

```bash
DATADOG_ENABLED=true
DATADOG_SERVICE="Alumni Platform"
DATADOG_ENV=production
DD_AGENT_HOST=localhost
```

### Alert Thresholds

```bash
# Response Times (ms)
ALERT_RESPONSE_WARNING=500
ALERT_RESPONSE_CRITICAL=1000

# Error Rates (%)
ALERT_ERROR_WARNING=1.0
ALERT_ERROR_CRITICAL=5.0

# Memory (MB)
ALERT_MEMORY_WARNING=128
ALERT_MEMORY_CRITICAL=256
```

## Backup Configuration

### AWS S3 Backup

```bash
BACKUP_ENABLED=true
BACKUP_PROVIDER=aws_s3
BACKUP_SCHEDULE=0 1 * * *
BACKUP_RETENTION_DAYS=30
BACKUP_COMPRESSION=lz4

# S3 Bucket
BACKUP_AWS_BUCKET=your-backup-bucket
BACKUP_AWS_REGION=us-east-1
```

### Backup Commands

```bash
# Full backup
php artisan backup:run

# Database backup
php artisan backup:run --only-db

# Clean old backups
php artisan backup:clean
```

## External Services

### OAuth Providers

#### Google
```bash
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=https://your-domain.com/auth/google/callback
```

#### Facebook
```bash
FACEBOOK_CLIENT_ID=your-app-id
FACEBOOK_CLIENT_SECRET=your-app-secret
FACEBOOK_REDIRECT_URI=https://your-domain.com/auth/facebook/callback
```

#### LinkedIn
```bash
LINKEDIN_CLIENT_ID=your-client-id
LINKEDIN_CLIENT_SECRET=your-client-secret
LINKEDIN_REDIRECT_URI=https://your-domain.com/auth/linkedin/callback
```

### CRM Integration

#### HubSpot
```bash
HUBSPOT_API_KEY=your-api-key
```

#### Salesforce
```bash
SALESFORCE_CLIENT_ID=your-client-id
SALESFORCE_CLIENT_SECRET=your-client-secret
SALESFORCE_INSTANCE_URL=https://your-instance.salesforce.com
```

## Environment-Specific Settings

### Development Override

Create `.env` for local development:

```bash
APP_ENV=local
APP_DEBUG=true
DB_HOST=127.0.0.1
REDIS_HOST=127.0.0.1
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

### Staging Environment

```bash
APP_ENV=staging
APP_DEBUG=false
LOG_LEVEL=info
SENTRY_ENVIRONMENT=staging
```

## Troubleshooting

### Common Issues

#### Database Connection Failed

```bash
# Verify credentials
php artisan tinker
> DB::connection()->getPdo();

# Check SSL
psql "host=db_host port=5432 dbname=db_name sslmode=require"
```

#### Redis Connection Timeout

```bash
# Test connection
redis-cli -h your-redis-host ping

# Check memory
redis-cli info memory
```

#### Queue Workers Not Processing

```bash
# Check Horizon status
php artisan horizon:status

# Check for failed jobs
php artisan queue:failed

# Restart workers
php artisan horizon:terminate && php artisan horizon
```

### Log Locations

| Log Type | Location |
|----------|----------|
| Application | `storage/logs/laravel.log` |
| Nginx | `/var/log/nginx/` |
| PHP-FPM | `/var/log/php-fpm/` |
| PostgreSQL | `/var/log/postgresql/` |
| Redis | `/var/log/redis/` |

### Health Check Endpoints

| Endpoint | Description |
|----------|-------------|
| `/health` | Basic health check |
| `/health/detailed` | Detailed system status |
| `/metrics` | Prometheus metrics |

## Security Checklist

Before deploying to production:

- [ ] Generate secure APP_KEY: `php artisan key:generate`
- [ ] Set strong database passwords
- [ ] Enable SSL/TLS certificates
- [ ] Configure rate limiting
- [ ] Enable two-factor authentication
- [ ] Set up backup rotation
- [ ] Configure monitoring alerts
- [ ] Review CORS settings
- [ ] Enable audit logging
- [ ] Test disaster recovery

## References

- [Laravel Documentation](https://laravel.com/docs/)
- [Environment Configuration](https://laravel.com/docs/configuration#environment-configuration)
- [Deployment Guide](https://laravel.com/docs/deployment)
