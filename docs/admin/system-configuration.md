# System Configuration Guide

**ABOUTME:** Comprehensive guide for configuring the Alumate platform including application settings, feature flags, integrations, email, and storage configuration.

## Table of Contents

1. [Overview](#overview)
2. [Application Settings](#application-settings)
3. [Feature Flags](#feature-flags)
4. [Integration Settings](#integration-settings)
5. [Email Configuration](#email-configuration)
6. [Storage Configuration](#storage-configuration)
7. [Environment Variables](#environment-variables)
8. [Configuration Troubleshooting](#configuration-troubleshooting)

---

## Overview

The Alumate platform uses a comprehensive configuration system supporting:

- **Environment Variables**: Sensitive configuration via `.env` files
- **Config Files**: PHP-based configuration in `config/` directory
- **Database Settings**: Dynamic settings stored in database
- **Feature Flags**: Toggle features without deployment
- **Tenant Configuration**: Per-tenant settings

### Configuration Priority

```
1. Environment Variables (highest priority)
2. Database Settings
3. Config Files
4. Default Values (lowest priority)
```

---

## Application Settings

### Core Settings

Configure in [`config/app.php`](config/app.php):

```php
return [
    'name' => env('APP_NAME', 'Alumate'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'https://alumate.com'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
];
```

### Authentication Settings

Configure in [`config/auth.php`](config/auth.php):

```php
return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
            'expiration' => 60 * 24 * 7, // 1 week
        ],
    ],
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
];
```

### Session Settings

```php
// config/session.php
return [
    'driver' => env('SESSION_DRIVER', 'redis'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => true,
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION', 'default'),
    'store' => env('SESSION_STORE', null),
    'lottery' => [2, 100],
    'cookie' => env('SESSION_COOKIE', 'alumate_session'),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN', null),
    'secure' => env('SESSION_SECURE_COOKIE', true),
    'http_only' => true,
    'same_site' => 'lax',
];
```

### Cache Settings

```php
// config/cache.php
return [
    'default' => env('CACHE_DRIVER', 'redis'),
    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'lock_connection' => 'default',
        ],
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],
    ],
    'prefix' => env('CACHE_PREFIX', 'alumate_'),
];
```

---

## Feature Flags

### Available Features

| Feature | Description | Default |
|---------|-------------|---------|
| analytics | Advanced analytics | Enabled |
| jobs | Job posting system | Enabled |
| mentorship | Mentorship features | Enabled |
| events | Event management | Enabled |
| groups | Community groups | Enabled |
| messaging | Direct messaging | Enabled |
| achievements | Gamification | Enabled |
| fundraising | Fundraising features | Disabled |
| premium_features | Premium subscription | Disabled |

### Managing Feature Flags

```bash
# List all features
php artisan features:list

# Enable feature globally
php artisan features:enable analytics

# Disable feature globally
php artisan features:disable fundraising

# Enable for specific tenant
php artisan features:enable mentorship --tenant=tenant-id

# Disable for specific tenant
php artisan features:disable premium --tenant=tenant-id
```

### Feature Configuration File

```php
// config/features.php
return [
    'analytics' => [
        'enabled' => env('FEATURE_ANALYTICS', true),
        'cohort_analysis' => true,
        'attribution' => true,
        'heatmaps' => true,
        'ab_testing' => true,
    ],
    'jobs' => [
        'enabled' => env('FEATURE_JOBS', true),
        'max_per_employer' => 50,
        'approval_required' => true,
    ],
    'mentorship' => [
        'enabled' => env('FEATURE_MENTORSHIP', true),
        'auto_match' => true,
        'max_mentees_per_mentor' => 5,
    ],
];
```

### Feature Rollout

```bash
# Enable for percentage of users
php artisan features:rollout \
    --feature=analytics \
    --percentage=50

# Enable for specific user segment
php artisan features:segment \
    --feature=new_dashboard \
    --segment="beta_testers"

# Check rollout status
php artisan features:status --feature=analytics
```

---

## Integration Settings

### Third-Party Integrations

#### Google Analytics

```bash
# Configure Google Analytics
GA_MEASUREMENT_ID=G-XXXXXXXXXX
GA_API_SECRET=xxxxxxxx
GA_ENABLED=true
```

```php
// config/integrations/google-analytics.php
return [
    'measurement_id' => env('GA_MEASUREMENT_ID'),
    'api_secret' => env('GA_API_SECRET'),
    'enabled' => env('GA_ENABLED', false),
    'events' => [
        'page_view' => true,
        'sign_up' => true,
        'login' => true,
    ],
];
```

#### Matomo Analytics

```bash
# Configure Matomo
MATOMO_URL=https://matomo.example.com
MATOMO_SITE_ID=1
MATOMO_API_TOKEN=xxxxxxxx
MATOMO_ENABLED=true
```

#### CRM Integration

```bash
# Configure CRM
CRM_TYPE=hubspot
CRM_API_KEY=xxxxxxxx
CRM_ENABLED=true

# Configure Salesforce (alternative)
CRM_TYPE=salesforce
CRM_CLIENT_ID=xxxxxxxx
CRM_CLIENT_SECRET=xxxxxxxx
CRM_REDIRECT_URI=https://your-domain.com/api/integrations/salesforce/callback
```

#### Email Providers

```bash
# SendGrid
MAIL_MAILER=sendgrid
SENDGRID_API_KEY=xxxxxxxx
SENDGRID_FROM_ADDRESS=noreply@example.com
SENDGRID_FROM_NAME="Alumate"

# Mailgun
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.example.com
MAILGUN_API_KEY=key-xxxxxxxx
MAILGUN_ENDPOINT=api.mailgun.net

# AWS SES
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=xxxxxxxx
AWS_SECRET_ACCESS_KEY=xxxxxxxx
AWS_DEFAULT_REGION=us-east-1
```

### Managing Integrations

```bash
# List configured integrations
php artisan integrations:list

# Test integration
php artisan integrations:test --type=google-analytics

# Sync integration data
php artisan integrations:sync --type=crm

# Disable integration
php artisan integrations:disable --type=all
```

---

## Email Configuration

### Basic Email Settings

```bash
# .env file
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@example.com
MAIL_PASSWORD=xxxxxxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Alumate"
```

### Email Templates

Configure in [`config/mail.php`](config/mail.php):

```php
return [
    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
        ],
    ],
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Alumate'),
    ],
];
```

### Email Queue Configuration

```php
// config/queue.php
return [
    'default' => env('QUEUE_CONNECTION', 'redis'),
    'connections' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'emails',
            'retry_after' => 90,
            'max_attempts' => 3,
        ],
    ],
];
```

### Managing Email

```bash
# Send test email
php artisan mail:test --to=admin@example.com

# View email queue
php artisan mail:queue --status=pending

# Clear stuck emails
php artisan mail:clear --older-than=1day

# Test email delivery
php artisan mail:delivery:check
```

---

## Storage Configuration

### Local Storage

```bash
# .env file
FILESYSTEM_DISK=local
STORAGE_PATH=/var/www/alumate/storage
```

### Cloud Storage (AWS S3)

```bash
# Configure AWS S3
AWS_ACCESS_KEY_ID=xxxxxxxx
AWS_SECRET_ACCESS_KEY=xxxxxxxx
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=alumate-files
AWS_ENDPOINT=https://s3.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### Google Cloud Storage

```bash
# Configure GCS
GCS_PROJECT_ID=alumate-project
GCS_KEY_FILE=/path/to/service-account.json
GCS_BUCKET=alumate-files
GCS_DRIVER=gcs
```

### Storage Configuration

```php
// config/filesystems.php
return [
    'default' => env('FILESYSTEM_DISIVER', 's3'),
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'throw' => false,
        ],
    ],
];
```

### Storage Management

```bash
# Check storage usage
php artisan storage:usage

# Clean temporary files
php artisan storage:cleanup --older-than=7days

# Sync storage between providers
php artisan storage:sync --from=local --to=s3

# Verify storage integrity
php artisan storage:verify
```

---

## Environment Variables

### Required Variables

```bash
# Application
APP_NAME="Alumate"
APP_ENV=production
APP_KEY=base64:xxxxxxxx
APP_URL=https://alumate.com

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=alumate
DB_USERNAME=alumate
DB_PASSWORD=xxxxxxxx

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=xxxxxxxx
REDIS_PORT=6379
```

### Optional Variables

```bash
# Cache
CACHE_DRIVER=redis
CACHE_PREFIX=alumate_

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Queue
QUEUE_CONNECTION=redis

# Filesystem
FILESYSTEM_DISK=s3

# Email
MAIL_MAILER=sendgrid

# Monitoring
MONITORING_ENABLED=true
SENTRY_DSN=https://xxx@sentry.io/xxx
```

### Managing Environment

```bash
# Copy example env file
cp .env.example .env

# Generate app key
php artisan key:generate

# Cache configuration
php artisan config:cache

# Clear configuration cache
php artisan config:clear

# View current configuration
php artisan env
```

---

## Configuration Troubleshooting

### Common Issues

#### 1. Configuration Not Applying

**Symptom**: Changes to config files not reflected

**Solution**:
```bash
# Clear configuration cache
php artisan config:clear

# Rebuild configuration cache
php artisan config:cache

# Verify configuration
php artisan config:show app.name
```

#### 2. Environment Variables Not Loading

**Symptom**: Environment variables return null

**Solution**:
```bash
# Check .env file exists
ls -la .env

# Verify .env syntax
php artisan env

# Reload environment
php artisan config:clear

# Check variable format
grep "^APP_NAME=" .env
```

#### 3. Database Connection Failed

**Symptom**: Cannot connect to database

**Solution**:
```bash
# Test database connection
php artisan db:connect

# Check credentials
php artisan db:show

# Verify database exists
php artisan db:check --database=alumate

# Run migrations
php artisan migrate:status
```

#### 4. Cache Driver Issues

**Symptom**: Cache not working properly

**Solution**:
```bash
# Check cache connection
php artisan cache:connection

# Clear all cache
php artisan cache:clear-all

# Test cache functionality
php artisan cache:test

# View cache statistics
php artisan cache:stats
```

### Diagnostic Commands

```bash
# Run full system diagnostic
php artisan system:diagnostic

# Check configuration integrity
php artisan config:validate

# Verify environment
php artisan env:verify

# Test all services
php artisan services:test
```

### Support Procedures

1. Gather diagnostic information:
   ```bash
   php artisan system:diagnostic --output=json > diagnostic.json
   ```

2. Review error logs:
   ```bash
   tail -100 storage/logs/laravel.log
   ```

3. Check monitoring dashboard

4. Contact support with diagnostic output

---

## Additional Resources

### Related Documentation

- [Administrator Training Guide](../admin-training/administrator-guide.md)
- [Deployment Documentation](../deployment/)
- [Security Documentation](../security/)
- [Environment Configuration](../.env.example)

### Configuration Files Reference

| File | Purpose |
|------|---------|
| `config/app.php` | Core application settings |
| `config/auth.php` | Authentication settings |
| `config/cache.php` | Cache configuration |
| `config/database.php` | Database configuration |
| `config/filesystems.php` | Storage configuration |
| `config/mail.php` | Email configuration |
| `config/queue.php` | Queue configuration |
| `config/session.php` | Session configuration |

---

**Last Updated:** February 2025  
**Documentation Version:** 1.0.0
