# Pre-Launch Readiness Assessment

## 02 - Deployment Checklist

**Project:** Alumate - Alumni Platform MVP  
**Environment:** Laravel 12 + Vue 3 + PostgreSQL 17 + Multi-tenant  
**Target:** Production VPS/Cloud Server

---

## 🔐 Environment Configuration

### Required Environment Variables

#### Database (Critical)

```bash
DB_CONNECTION=pgsql
DB_HOST=
DB_PORT=5432
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

# Tenant Database (for multi-tenancy)
DB_TENANT_HOST=
DB_TENANT_PORT=5432
DB_TENANT_DATABASE=
DB_TENANT_USERNAME=
DB_TENANT_PASSWORD=
```

#### Application (Critical)

```bash
APP_NAME="Alumate"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=UTC
```

#### Security (Critical)

```bash
BCRYPT_ROUNDS=12
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=your-domain.com
```

#### Cache & Queue (High)

```bash
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=
REDIS_PORT=6379
```

#### Mail (High)

```bash
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

#### Third-Party Services (Medium)

```bash
# Analytics
GOOGLE_ANALYTICS_ID=
MATOMO_URL=
MATOMO_SITE_ID=

# Social Login
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
LINKEDIN_CLIENT_ID=
LINKEDIN_CLIENT_SECRET=

# File Storage
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false
```

---

## 🖥️ Server Requirements

### Hardware (Minimum)

- **CPU:** 2 cores
- **RAM:** 4GB (8GB recommended for multi-tenant)
- **Storage:** 50GB SSD (scales with user content)
- **Network:** 100Mbps

### Software Stack

```bash
# PHP 8.3+
php --version  # Must be 8.3 or higher

# Required PHP Extensions
php -m | grep -E "pdo|pgsql|mbstring|xml|curl|zip|intl|bcmath|redis|opcache"

# Node.js 22+
node --version  # Must be 22.x

# PostgreSQL 17+
psql --version  # Must be 17.x

# Redis 7+
redis-server --version

# Nginx/Apache
nginx -v  # OR apache2 -v

# Composer 2.x
composer --version
```

### PHP Extensions Checklist

- [x] pdo
- [x] pdo_pgsql
- [x] pgsql
- [x] mbstring
- [x] xml
- [x] curl
- [x] zip
- [x] bcmath
- [x] intl
- [x] redis
- [x] opcache
- [x] exif
- [x] pcntl (for queues)

---

## 📦 Deployment Steps

### Pre-Deployment

1. **Code Preparation**

    ```bash
    # Run tests locally
    php artisan test --testsuite=Unit
    php artisan test --testsuite=Feature

    # Build frontend assets
    npm ci
    npm run build

    # Code quality checks
    vendor/bin/pint
    npm run lint
    ```

2. **Database Backup**

    ```bash
    # Create backup before deployment
    pg_dump -Fc -v -h $DB_HOST -U $DB_USERNAME -d $DB_DATABASE > backup_$(date +%Y%m%d_%H%M%S).dump

    # Store backup securely
    aws s3 cp backup_*.dump s3://your-backup-bucket/ --storage-class STANDARD_IA
    ```

3. **Enable Maintenance Mode**
    ```bash
    php artisan down --message="We're performing maintenance. Please check back soon." --retry=60
    ```

### Deployment

4. **Pull Latest Code**

    ```bash
    cd /var/www/alumate
    git pull origin main
    ```

5. **Install Dependencies**

    ```bash
    # PHP dependencies
    composer install --no-dev --optimize-autoloader --no-interaction

    # Node dependencies (if building on server)
    npm ci --production
    ```

6. **Run Migrations**

    ```bash
    # System migrations
    php artisan migrate --force

    # Tenant migrations
    php artisan tenants:migrate --force
    ```

7. **Cache Optimization**

    ```bash
    # Clear caches
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

    # Rebuild caches
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache

    # Optimize class loader
    composer dump-autoload --optimize
    ```

8. **Build Frontend (if not pre-built)**

    ```bash
    npm run build
    ```

9. **Restart Services**

    ```bash
    # Restart PHP-FPM
    sudo systemctl restart php8.3-fpm

    # Restart queue workers
    php artisan queue:restart

    # Restart scheduler (if using systemd)
    sudo systemctl restart alumate-scheduler
    ```

10. **Disable Maintenance Mode**
    ```bash
    php artisan up
    ```

---

## 🔍 Post-Deployment Verification

### Health Checks

```bash
# Test application health
curl -f https://your-domain.com/health-check

# Test database connectivity
curl -f https://your-domain.com/api/health/database

# Test Redis connectivity
curl -f https://your-domain.com/api/health/cache

# Test queue processing
php artisan queue:monitor default
```

### Critical Routes Test

- [ ] Homepage loads correctly
- [ ] Login page accessible
- [ ] Registration works
- [ ] Dashboard loads after login
- [ ] Tenant switching works (if applicable)
- [ ] File uploads work
- [ ] Email sending verified

### Performance Checks

```bash
# Check response times
curl -o /dev/null -s -w "%{time_total}\n" https://your-domain.com

# Should be < 500ms for homepage

# Check asset loading
# Open browser dev tools and verify:
# - No 404 errors
# - Assets served with proper caching headers
# - Total page load < 2s
```

---

## 🔄 CI/CD Pipeline

### GitHub Actions Workflow

The project includes automated CI/CD via `.github/workflows/`:

1. **ci.yml** - Runs on PR/push
    - Code quality checks
    - Unit/Integration/Feature tests
    - Security audits
    - TypeScript type checking

2. **staging-deployment.yml** - Auto-deploys develop branch
3. **production-deployment.yml** - Manual deployment to production
4. **rollback.yml** - Emergency rollback procedures

### Required GitHub Secrets

```
PROD_HOST
PROD_USER
PROD_SSH_KEY
PROD_PATH
PROD_DB_HOST
PROD_DB_USER
PROD_DB_PASSWORD
STAGING_HOST
STAGING_USER
STAGING_SSH_KEY
SLACK_WEBHOOK_URL
```

---

## 📊 Monitoring Setup

### Essential Monitoring

1. **Application Health**

    ```bash
    # Add to crontab for health checks
    */5 * * * * curl -f https://your-domain.com/health-check || alert-admin
    ```

2. **Log Monitoring**

    ```bash
    # Laravel logs
    tail -f storage/logs/laravel.log

    # Nginx error logs
    tail -f /var/log/nginx/error.log

    # PHP-FPM logs
    tail -f /var/log/php8.3-fpm.log
    ```

3. **Database Monitoring**
    - Connection count
    - Slow query log
    - Disk usage

4. **Queue Monitoring**
    ```bash
    php artisan queue:monitor default --max=100
    ```

### Recommended Tools

- **Laravel Pulse** - Application performance metrics
- **Sentry** - Error tracking
- **Uptime Robot** - External uptime monitoring
- **Grafana + Prometheus** - Infrastructure metrics

---

## 🚨 Emergency Procedures

### Rollback

```bash
# Option 1: Git rollback
git reset --hard HEAD~1
git push origin main --force  # Use with caution

# Option 2: Restore backup
pg_restore -h $DB_HOST -U $DB_USERNAME -d $DB_DATABASE backup_file.dump

# Option 3: Use rollback workflow
# Trigger via GitHub Actions: rollback.yml
```

### Database Recovery

```bash
# Restore from backup
pg_restore --clean --if-exists -h $DB_HOST -U $DB_USERNAME -d $DB_DATABASE backup.dump

# After restore, run any missing migrations
php artisan migrate
```

---

## ✅ Pre-Launch Checklist

### Code

- [ ] All tests passing
- [ ] Code coverage > 80%
- [ ] No critical security vulnerabilities
- [ ] Frontend assets built and optimized
- [ ] No debug code left in production

### Infrastructure

- [ ] SSL certificate installed and valid
- [ ] Domain DNS configured
- [ ] Server firewall configured
- [ ] Backup automation in place
- [ ] Monitoring alerts configured
- [ ] CDN configured (if using)

### Database

- [ ] Migrations tested on fresh database
- [ ] Indexes optimized
- [ ] Connection pooling configured
- [ ] Backup schedule configured
- [ ] Disaster recovery plan tested

### Security

- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] All secrets in .env (not in code)
- [ ] CSRF protection enabled
- [ ] Rate limiting configured
- [ ] SQL injection prevention verified
- [ ] XSS prevention verified

### Communication

- [ ] Team notified of deployment window
- [ ] Rollback plan communicated
- [ ] Support team briefed on new features
- [ ] Status page updated (if applicable)

---

**Last Updated:** 2026-02-06  
**Owner:** DevOps / Tech Lead  
**Next Review:** Before every production deployment
