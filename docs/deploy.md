# Advanced Analytics Deployment Guide

This guide covers the deployment process for the Advanced Analytics System in the Laravel 12 multi-tenant platform.

## Prerequisites

- **PHP 8.3+** (Path: `D:\DevCenter\xampp\php-8.3.23\php.exe`)
- **Node.js 18+**
- **Composer 2.x**
- **PostgreSQL 17+**
- **Redis** (optional but recommended for production)
- **Supervisor** or similar process manager for queue workers

## Environment Setup

### 1. Clone Repository
```bash
git clone <repository-url>
cd alumate
```

### 2. Environment Configuration
```bash
# Copy production environment file
cp .env.example .env.prod

# Edit .env.prod with production values
nano .env.prod
```

**Required Production Environment Variables:**
- `APP_ENV=production`
- `APP_DEBUG=false`
- `DB_CONNECTION=pgsql`
- `DB_HOST=prod-db-host`
- `DB_DATABASE=analytics_prod`
- `DB_USERNAME=prod_user`
- `DB_PASSWORD=prod_password`
- `REDIS_HOST=prod-redis`
- `QUEUE_CONNECTION=redis`
- `HORIZON_PREFIX=prod-analytics`

**API Keys and Integrations:**
- `HUBSPOT_API_KEY=your_hubspot_api_key_here`
- `SALESFORCE_CLIENT_ID=your_salesforce_client_id`
- `SALESFORCE_CLIENT_SECRET=your_salesforce_client_secret`
- `FRAPPE_API_KEY=your_frappe_api_key`
- `ZOHOCRM_CLIENT_ID=your_zoho_client_id`
- `GOOGLE_ANALYTICS_TRACKING_ID=your_ga_tracking_id`
- `MATOMO_SITE_ID=your_matomo_site_id`
- `NEW_RELIC_LICENSE_KEY=your_new_relic_license_key`

**Production Scaling and Monitoring:**
- `HORIZON_WORKERS=20`
- `REDIS_CLUSTER=true`
- `TELESCOPE_ENABLED=true`
- `TELESCOPE_DRIVER=database`

## Deploy Steps

### Automated Deployment
```bash
# Run the deployment script
./scripts/deploy/deploy.sh
```

### Manual Deployment Steps

1. **Install Dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

2. **Environment Setup**
   ```bash
   cp .env.prod .env
   php artisan key:generate
   ```

3. **Cache Configuration**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

4. **Database Migration**
   ```bash
   php artisan migrate --force
   php artisan tenants:migrate --force
   ```

5. **Queue Setup**
   ```bash
   php artisan queue:restart
   ```

6. **Build Assets** (if applicable)
   ```bash
   npm run build
   ```

## Queue Management

### Horizon Dashboard
Access the queue monitoring dashboard at `/horizon` (requires super-admin authentication).

### Supervisor Configuration
Create `/etc/supervisor/conf.d/horizon.conf`:

```ini
[program:horizon]
process_name=%(program_name)s
command=php /path/to/alumate/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/horizon.log
```

### Scaling Workers
```bash
# Scale specific queue
php artisan horizon:scale analytics 10

# List current workers
php artisan horizon:list

# Terminate workers
php artisan horizon:terminate
```

## Telescope Monitoring

### Access Monitoring
- **Telescope Dashboard**: `/telescope` - Application monitoring and debugging
- **Features**: Request monitoring, database queries, cache operations, queue jobs, exceptions, mail, notifications

### Telescope Configuration
Configure in `.env.prod`:
- `TELESCOPE_ENABLED=true`
- `TELESCOPE_DRIVER=database`
- `TELESCOPE_CONNECTION=telescope`
- `TELESCOPE_PRUNING_ENABLED=true`
- `TELESCOPE_PRUNING_RETENTION=7`

### Telescope Alerts
Telescope monitors and can alert on:
- Slow database queries (>500ms)
- Failed queue jobs
- Application exceptions
- High memory usage
- Performance bottlenecks

## Monitoring and Alerting

### Access Monitoring
- **Horizon Dashboard**: `/horizon` - Queue monitoring and metrics
- **System Health**: Check `/health-check/homepage`
- **Performance Metrics**: Monitor via New Relic or configured alerting channels

### Alert Thresholds
- **Queue Backlog**: Warning >50 jobs, Critical >100 jobs
- **Error Rate**: Warning >0.5%, Critical >1%
- **Response Time**: Warning >1000ms, Critical >3000ms
- **System Resources**: Warning >80%, Critical >95%

### Alert Channels
Configure in `.env.prod`:
- `MONITORING_ALERT_EMAIL=alerts@yourdomain.com`
- `MONITORING_SLACK_WEBHOOK=https://hooks.slack.com/...`
- `PAGERDUTY_INTEGRATION_KEY=your_pagerduty_key`

## Backup and Recovery

### Database Backup
```bash
# PostgreSQL backup
pg_dump -h prod-db-host -U prod_user -d analytics_prod > backup_$(date +%Y%m%d_%H%M%S).sql

# With compression
pg_dump -h prod-db-host -U prod_user -d analytics_prod | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

### Redis Backup
```bash
# Redis save
redis-cli -h prod-redis BGSAVE

# Copy dump file
scp prod-redis:/var/lib/redis/dump.rdb /backups/redis_$(date +%Y%m%d_%H%M%S).rdb
```

### Recovery Procedures

1. **Database Recovery**
   ```bash
   # Stop application
   sudo systemctl stop horizon
   sudo systemctl stop nginx

   # Restore database
   psql -h prod-db-host -U prod_user -d analytics_prod < backup.sql

   # Restart services
   sudo systemctl start nginx
   sudo systemctl start horizon
   ```

2. **Redis Recovery**
   ```bash
   # Stop Redis
   sudo systemctl stop redis

   # Restore dump file
   cp /backups/redis_backup.rdb /var/lib/redis/dump.rdb

   # Start Redis
   sudo systemctl start redis
   ```

### Automated Backups
Set up cron jobs for regular backups:

```bash
# Daily database backup at 2 AM
0 2 * * * /path/to/backup-scripts/db-backup.sh

# Redis backup every 6 hours
0 */6 * * * /path/to/backup-scripts/redis-backup.sh
```

## Scaling Considerations

### Horizontal Scaling
- **Multiple App Servers**: Use load balancer with session affinity
- **Redis Cluster**: For high-availability caching and queues
- **Database Read Replicas**: Distribute read load

### Vertical Scaling
- **Increase Worker Processes**: Scale Horizon workers based on load
- **Memory Optimization**: Monitor and adjust PHP memory limits
- **Database Optimization**: Add indexes and optimize queries

### Multi-Tenant Considerations
- **Database Isolation**: Each tenant uses separate database/schema
- **Resource Allocation**: Monitor per-tenant resource usage
- **Backup Strategy**: Include tenant-specific backups
- **Scaling**: Scale based on aggregate tenant load

## Security Checklist

- [ ] Environment variables configured securely
- [ ] Database credentials rotated regularly
- [ ] SSL/TLS certificates installed
- [ ] Firewall rules configured
- [ ] File permissions set correctly (755 for directories, 644 for files)
- [ ] `.env` files not committed to version control
- [ ] Debug mode disabled in production
- [ ] CSRF protection enabled
- [ ] Rate limiting configured

## Troubleshooting

### Common Issues

1. **Queue Not Processing**
   ```bash
   # Check supervisor status
   sudo supervisorctl status horizon

   # Restart horizon
   php artisan horizon:terminate
   php artisan horizon
   ```

2. **High Memory Usage**
   - Check PHP memory limit in `php.ini`
   - Monitor with `php artisan tinker` and `memory_get_peak_usage()`
   - Optimize queries and reduce eager loading

3. **Slow Response Times**
   - Check database indexes
   - Enable query logging: `DB_LOG_QUERIES=true`
   - Monitor with New Relic or similar APM

### Logs
- **Application Logs**: `storage/logs/laravel.log`
- **Queue Logs**: `/var/log/horizon.log`
- **Web Server Logs**: `/var/log/nginx/error.log`

## Rollback Procedures

1. **Code Rollback**
   ```bash
   git checkout <previous-commit>
   ./scripts/deploy/deploy.sh
   ```

2. **Database Rollback**
   ```bash
   php artisan migrate:rollback --step=1
   ```

3. **Full Rollback**
   - Restore from backup
   - Redeploy previous version
   - Update DNS if necessary

## Performance Benchmarks

- **Response Time**: <300ms for API endpoints
- **Queue Processing**: <500ms per job
- **Cache Hit Rate**: >95%
- **Database Query Time**: <100ms average
- **Memory Usage**: <512MB per worker

## Support

For deployment issues, check:
1. Application logs
2. Horizon dashboard
3. System monitoring tools
4. Database performance
5. External service status (Redis, PostgreSQL, APIs)