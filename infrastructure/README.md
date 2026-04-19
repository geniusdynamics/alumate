# Production Deployment Infrastructure

This directory contains the complete production deployment infrastructure for the Alumni Platform multi-tenant application.

## Overview

The production infrastructure is designed for:
- **Multi-tenant Laravel application** with Vue.js frontend
- **Zero-downtime deployments** with automated rollback
- **Docker containerization** for reliable environment consistency
- **Complete CI/CD pipeline** with testing and monitoring
- **Security hardening** and performance optimization
- **Automated monitoring** and alerting
- **Comprehensive logging** with rotation and structured logs
- **Backup and disaster recovery** capabilities

## Directory Structure

```
infrastructure/
├── README.md                          # This file
├── production/                        # Production deployment configuration
│   ├── docker-compose.prod.yml        # Production Docker containers
│   ├── Dockerfile.php                 # PHP/Laravel application container
│   ├── Dockerfile.nginx               # Nginx web server container
│   ├── .env.production.example        # Production environment template
│   ├── .env.staging                   # Staging environment configuration
│   ├── deploy.production.sh          # Advanced deployment script
│   ├── backup-config.sh              # Backup management script
│   ├── monitoring-config.php          # Monitoring configuration
│   ├── setup-monitoring.sh            # Monitoring setup script
│   ├── scripts/
│   │   └── start.sh                  # Container startup script
│   └── config/                       # Service configurations
│       ├── nginx/                     # Nginx reverse proxy configs
│       │   ├── nginx.conf            # Main nginx configuration
│       │   ├── conf.d/
│       │       └── default.conf       # Default server configuration
│       │   └── snippets/             # Nginx configuration snippets
│       │       ├── security-headers.conf
│       │       └── ssl-params.conf
│       ├── php/
│       │   └── php.ini               # PHP production configuration
│       └── postgres/
│           └── init.sql               # PostgreSQL initialization
├── staging/                          # Staging-specific configurations
└── development/                      # Development overrides
```

## Quick Start

### Prerequisites

- Docker and Docker Compose
- PostgreSQL database server (17+)
- Redis server
- SSL certificates (Let's Encrypt recommended)
- Production domain configuration
- PHP 8.3+ for local commands
- Node.js 18+ for frontend builds

### Basic Deployment

1. **Configure Environment**
   ```bash
   cp infrastructure/production/.env.production.example .env.production
   # Edit with your production values
   ```

2. **Generate SSL Certificates (Let's Encrypt)**
   ```bash
   # Using certbot
   certbot certonly --nginx -d your-domain.com -d www.your-domain.com
   
   # Or generate self-signed for testing
   openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
     -keyout /etc/letsencrypt/live/your-domain.com/privkey.pem \
     -out /etc/letsencrypt/live/your-domain.com/fullchain.pem
   ```

3. **Start Production Stack**
   ```bash
   cd infrastructure/production
   docker-compose -f docker-compose.prod.yml up -d
   ```

4. **Initial Deployment**
   ```bash
   ./deploy.production.sh
   ```

### Advanced Usage

#### Deploy via CI/CD

The application includes a complete GitHub Actions pipeline:
- Automatic testing on push/PR
- Security scanning with SAST tools
- Database backup before deployment
- Zero-downtime deployment with health checks
- Automated rollback on failure
- Deployment notifications to Slack/Teams

#### Multi-Tenant Configuration

The infrastructure is designed for multiple tenant isolation:
- Separate database schemas per tenant
- Dedicated file storage per tenant
- Domain-based tenant routing (`{tenant}.your-domain.com`)
- Shared application resources
- Tenant-aware logging and monitoring

## Configuration Files

### Environment Variables

See `.env.production.example` for complete configuration options including:

| Category | Variables |
|----------|-----------|
| Application | `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_KEY`, `APP_URL` |
| Database | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| Redis | `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT` |
| Multi-tenancy | `TENANCY_ENABLED`, `TENANCY_ISOLATE_DATA` |
| Storage | `FILESYSTEM_DISK`, `AWS_ACCESS_KEY_ID`, `AWS_BUCKET` |
| Monitoring | `SENTRY_ENABLED`, `NEW_RELIC_ENABLED`, `DATADOG_ENABLED` |
| Alerting | `ALERTS_ENABLED`, `ALERT_EMAIL_RECIPIENTS`, `ALERT_SLACK_WEBHOOK_URL` |
| Backup | `BACKUP_ENABLED`, `BACKUP_PROVIDER`, `BACKUP_RETENTION_DAYS` |

### Docker Configuration

| Service | Image | Purpose |
|---------|-------|---------|
| app | PHP 8.3-FPM | Laravel application container |
| nginx | Nginx Alpine | Web server with SSL termination |
| db | PostgreSQL 17 | Primary database |
| redis | Redis 7 | Cache and session storage |
| queue-worker | PHP 8.3-FPM | Background job processing |
| scheduler | PHP 8.3-FPM | Task scheduling |
| prometheus | Prometheus | Metrics collection |
| node-exporter | Node Exporter | System metrics |
| postgres-exporter | Postgres Exporter | Database metrics |

### Nginx Configuration

The nginx configuration includes:
- HTTP to HTTPS redirect
- SSL/TLS hardening with modern protocols
- Gzip compression for text assets
- Rate limiting for API endpoints
- Security headers (HSTS, CSP, X-Frame-Options)
- Static asset caching with long expiry
- PHP-FPM upstream configuration
- Tenant subdomain routing

### PHP Configuration

Production PHP settings include:
- OPcache enabled with 256MB memory
- Security hardening (disabled functions)
- Error handling optimized for production
- Session configuration for Redis
- File upload limits (20MB max)
- Memory limit (256MB)

## Monitoring & Logging

### Monitoring Setup

Run the monitoring setup script:
```bash
./setup-monitoring.sh
```

This configures:
- Prometheus metrics collection
- Alert thresholds and channels
- Dashboard configurations
- Health check endpoints
- Performance monitoring

### Available Metrics

| Metric | Endpoint | Description |
|--------|----------|-------------|
| Application Health | `/health` | Basic health check |
| Detailed Health | `/health/detailed` | Full system status |
| Metrics | `/metrics` | Prometheus-compatible metrics |

### Log Configuration

See `config/logging-prod.php` for production logging settings:

| Log Channel | Location | Purpose |
|-------------|----------|---------|
| laravel.log | storage/logs/laravel.log | Main application log |
| error.log | storage/logs/error.log | Error-level logs only |
| access.log | storage/logs/access.log | HTTP access logs |
| security.log | storage/logs/security.log | Security events |
| auth.log | storage/logs/auth.log | Authentication events |
| queries.log | storage/logs/queries.log | Database queries (debug) |
| monitoring.log | storage/logs/monitoring.log | Monitoring events |
| api.log | storage/logs/api.log | API request/response |

### Log Rotation

Logs are automatically rotated daily with configurable retention:
- Application logs: 30 days
- Error logs: 30 days
- Security logs: 365 days
- Access logs: 90 days
- Monitoring logs: 90 days

Configure via `/etc/logrotate.d/alumate`:
```bash
/var/www/html/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

## Backup & Recovery

### Backup Types

The system supports multiple backup types:
- **Database Backups**: PostgreSQL with compression
- **File Backups**: Storage directory with incremental support
- **Configuration Backups**: Environment and config files

### Backup Commands

```bash
# Full backup (database + files + config)
./backup-config.sh full

# Database backup only
./backup-config.sh db

# Files backup only
./backup-config.sh files

# Configuration backup only
./backup-config.sh config

# Cleanup old backups
./backup-config.sh cleanup

# Check backup status
./backup-config.sh status

# Verify backup integrity
./backup-config.sh verify /path/to/backup.tar.gz

# Restore database
./backup-config.sh restore-db /path/to/backup.sql.gz

# Restore files
./backup-config.sh restore-files /path/to/backup.tar.gz
```

### Backup Schedule

Default backup schedule (configured via cron):
- Database: Daily at 1 AM (`0 1 * * *`)
- Files: Daily at 2 AM (`0 2 * * *`)
- Configuration: Weekly Monday at 3 AM (`0 3 * * 1`)
- Cleanup: Daily at 5 AM (`0 5 * * *`)

### Remote Storage

Backups can be uploaded to:
- **AWS S3**: Set `BACKUP_PROVIDER=aws_s3`
- **Google Cloud Storage**: Set `BACKUP_PROVIDER=gcp`
- **Azure Blob Storage**: Set `BACKUP_PROVIDER=azure`

## Deployment

### Zero-Downtime Deployment

The deployment script (`deploy.production.sh`) implements:
1. Pre-flight checks and validation
2. Database backup before changes
3. Repository clone to new release directory
4. Dependency installation
5. Database migrations (including tenant migrations)
6. Cache optimization
7. Symlink-based release switch
8. Health checks with automatic rollback
9. Post-deployment cleanup

### Deployment Commands

```bash
# Standard deployment
./deploy.production.sh

# Deploy specific tag
DEPLOY_TAG=v2.0.0 ./deploy.production.sh

# Skip database backup
BACKUP_DATABASE=false ./deploy.production.sh

# Disable rollback
ENABLE_ROLLBACK=false ./deploy.production.sh
```

### Rollback Procedure

If deployment fails or issues are detected:
1. Automatic rollback triggers on health check failure
2. Previous release is restored via symlink switch
3. Database restored from pre-deployment backup
4. Notifications sent to configured channels

Manual rollback:
```bash
./deploy.production.sh --rollback
```

## Security

### Security Headers

All responses include:
- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `X-XSS-Protection: 1; mode=block`
- `Strict-Transport-Security: max-age=31536000`
- `Content-Security-Policy: ...`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: ...`

### SSL/TLS Configuration

Modern TLS configuration:
- Protocols: TLSv1.2, TLSv1.3 only
- Cipher suites: Mozilla Intermediate recommendations
- HSTS: Enabled with 1-year max-age
- OCSP Stapling: Enabled
- DH Parameters: 2048-bit

### Firewall Configuration

Recommended rules:
```bash
# Allow HTTP/HTTPS
ufw allow 80/tcp
ufw allow 443/tcp

# Allow SSH (limit rate)
ufw allow 22/tcp

# Allow monitoring ports (internal only)
ufw allow from 10.0.0.0/8 to any port 9090 proto tcp
ufw allow from 10.0.0.0/8 to any port 9187 proto tcp
```

## Performance Tuning

### Database

```sql
-- PostgreSQL performance tuning
ALTER SYSTEM SET shared_buffers = '256MB';
ALTER SYSTEM SET effective_cache_size = '1GB';
ALTER SYSTEM SET work_mem = '16MB';
ALTER SYSTEM SET maintenance_work_mem = '256MB';
ALTER SYSTEM SET max_connections = 200;
```

### PHP-FPM

Configure in `docker-compose.prod.yml`:
```yaml
environment:
  - PHP_FPM_PM=dynamic
  - PHP_FPM_PM_MAX_CHILDREN=10
  - PHP_FPM_PM_START_SERVERS=2
  - PHP_FPM_PM_MIN_SPARE_SERVERS=1
  - PHP_FPM_PM_MAX_SPARE_SERVERS=5
  - PHP_FPM_PM_MAX_REQUESTS=500
```

### Redis

Configure in `docker-compose.prod.yml`:
```yaml
command: redis-server 
  --maxmemory 256mb 
  --maxmemory-policy allkeys-lru 
  --appendonly yes
```

## Troubleshooting

### Common Issues

**Container Startup Failures**
```bash
# Check container logs
docker-compose -f docker-compose.prod.yml logs

# Validate configuration
docker-compose -f docker-compose.prod.yml config

# Restart with verbose logging
docker-compose -f docker-compose.prod.yml up --verbose
```

**Health Check Failures**
```bash
# Check application health
curl http://localhost/health

# Verify database connectivity
docker-compose -f docker-compose.prod.yml exec app php artisan db:connect

# Check Redis connection
docker-compose -f docker-compose.prod.yml exec redis redis-cli ping

# Review application logs
tail -f storage/logs/laravel.log
```

**Database Connection Issues**
```bash
# Check database status
docker-compose -f docker-compose.prod.yml exec db pg_isready

# Verify credentials
docker-compose -f docker-compose.prod.yml exec app env | grep DB_

# Test connection from app container
docker-compose -f docker-compose.prod.yml exec app php artisan tinker
```

**SSL Certificate Issues**
```bash
# Check certificate expiration
openssl x509 -enddate -noout -in /etc/letsencrypt/live/your-domain.com/fullchain.pem

# Renew certificates
certbot renew --quiet

# Test SSL configuration
ssltest.sh -h your-domain.com
```

### Logs Location

| Log Type | Location |
|----------|----------|
| Application | `/var/www/html/storage/logs/` |
| Nginx | `/var/log/nginx/` |
| Database | `/var/log/postgresql/postgresql.log` |
| Redis | `/var/log/redis/redis.log` |
| Deployment | `storage/logs/deploy.log` |
| Backups | `backups/` |

### Monitoring Endpoints

| Endpoint | Purpose |
|----------|---------|
| `/health` | Basic health check |
| `/health/detailed` | Detailed system status |
| `/metrics` | Prometheus metrics |
| `/ready` | Readiness probe |

## Admin Commands

### Container Management
```bash
# View container status
docker-compose -f docker-compose.prod.yml ps

# View logs
docker-compose -f docker-compose.prod.yml logs -f app

# Restart specific service
docker-compose -f docker-compose.prod.yml restart nginx

# Scale services
docker-compose -f docker-compose.prod.yml up -d --scale queue-worker=3

# Stop all services
docker-compose -f docker-compose.prod.yml down

# Stop and remove volumes
docker-compose -f docker-compose.prod.yml down -v
```

### Laravel Commands
```bash
# Execute artisan commands
docker-compose -f docker-compose.prod.yml exec app php artisan migrate

# Run tests
docker-compose -f docker-compose.prod.yml exec app php artisan test

# Clear caches
docker-compose -f docker-compose.prod.yml exec app php artisan optimize:clear

# Run tenant migrations
docker-compose -f docker-compose.prod.yml exec app php artisan tenants:migrate

# Clear Redis cache
docker-compose -f docker-compose.prod.yml exec redis redis-cli FLUSHALL
```

### Monitoring
```bash
# Check health
curl https://your-domain.com/health

# Check metrics
curl http://localhost:9090

# View Prometheus targets
curl http://localhost:9090/api/v1/targets

# Check Grafana dashboards
open http://localhost:3000
```

## Security Checklist

Before going to production:

- [ ] SSL certificates installed and valid
- [ ] Password policies enforced in application
- [ ] Two-factor authentication enabled
- [ ] Security headers configured and tested
- [ ] File permissions verified (644/755)
- [ ] Database connections use SSL/TLS
- [ ] Backup encryption enabled
- [ ] Monitoring alerts configured and tested
- [ ] Access controls validated
- [ ] Rate limiting enabled
- [ ] Audit logging enabled
- [ ] GDPR compliance measures in place
- [ ] Environment variables secured
- [ ] API rate limits configured
- [ ] CORS settings reviewed
- [ ] XSS protection enabled

## Support

For deployment issues:

1. Review container logs: `docker-compose logs`
2. Check health check endpoints
3. Verify configuration files
4. Review monitoring dashboards
5. Check backup integrity
6. Consult deployment runbooks

**Note**: Always test deployments in staging environment before production deployment.
