# Troubleshooting Guide

**ABOUTME:** Comprehensive troubleshooting guide for the Alumate platform covering common issues, diagnostic procedures, and resolution steps for all administrative concerns.

## Table of Contents

1. [Overview](#overview)
2. [Getting Started](#getting-started)
3. [Application Issues](#application-issues)
4. [Database Issues](#database-issues)
5. [Performance Issues](#performance-issues)
6. [User Issues](#user-issues)
7. [Tenant Issues](#tenant-issues)
8. [Integration Issues](#integration-issues)
9. [Diagnostic Procedures](#diagnostic-procedures)
10. [Support Procedures](#support-procedures)

---

## Overview

This troubleshooting guide provides comprehensive solutions for common issues encountered while administering the Alumate platform. Use this guide to quickly diagnose and resolve problems.

### Before You Begin

1. **Check Monitoring Dashboard** - Many issues are visible in `/admin/monitoring`
2. **Review Recent Logs** - Check logs in `storage/logs/`
3. **Check Alerts** - Review active alerts
4. **Reproduce Issue** - Document steps to reproduce

---

## Getting Started

### Initial Diagnostics

```bash
# Run full system diagnostic
php artisan system:diagnostic

# Check application status
php artisan system:status

# Verify environment
php artisan env

# Check dependencies
php artisan deps:check
```

### Log Locations

| Log File | Description |
|----------|-------------|
| `storage/logs/laravel.log` | Application logs |
| `storage/logs/queue.log` | Queue job logs |
| `storage/logs/auth.log` | Authentication logs |
| `storage/logs/security.log` | Security events |

### Common Diagnostic Commands

```bash
# View recent errors
php artisan log:view --level=error --lines=50

# Check database health
php artisan db:health

# Check Redis health
php artisan redis:health

# Check queue status
php artisan queue:status

# Check scheduled tasks
php artisan schedule:list
```

---

## Application Issues

### Application Won't Start

**Symptoms**:
- 500 Error on all pages
- White screen of death
- Application unreachable

**Diagnosis**:
```bash
# Check PHP errors
php -i | grep -i error

# Verify .env file
cat .env | grep -v "^#" | head -20

# Check storage permissions
ls -la storage/

# Verify bootstrap cache
ls -la bootstrap/cache/
```

**Solutions**:
```bash
# Clear configuration cache
php artisan config:clear

# Clear application cache
php artisan cache:clear

# Clear route cache
php artisan route:clear

# Regenerate autoloader
composer dump-autoload

# Fix storage permissions
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/

# Check PHP version
php -v
```

### Blank White Screen

**Diagnosis**:
```bash
# Enable debug mode
APP_DEBUG=true

# Check PHP error log
tail -100 /var/log/php_errors.log

# Check Laravel log
tail -100 storage/logs/laravel.log
```

**Solutions**:
```bash
# Increase memory limit
php -d memory_limit=512M artisan cache:clear

# Enable error display
php -d display_errors=1 artisan tinker

# Check for syntax errors
php -l app/Http/Controllers/HomeController.php
```

### 404 Errors

**Diagnosis**:
```bash
# Check route cache
php artisan route:list | grep "page_name"

# Verify file exists
ls -la public/page_name/

# Check .htaccess (Apache)
cat public/.htaccess
```

**Solutions**:
```bash
# Clear route cache
php artisan route:clear

# Rebuild route cache
php artisan route:cache

# Verify mod_rewrite enabled (Apache)
a2enmod rewrite

# Restart web server
systemctl restart apache2
```

### Slow Page Loads

**Diagnosis**:
```bash
# Profile page load
php artisan debug:profile --uri=/admin

# Check query count
php artisan debug:queries --uri=/admin

# Check cache status
php artisan cache:stats
```

**Solutions**:
```bash
# Clear application cache
php artisan cache:clear

# Optimize application
php artisan optimize

# Clear view cache
php artisan view:clear

# Rebuild configuration cache
php artisan config:cache
```

---

## Database Issues

### Connection Failed

**Symptoms**:
- "Connection refused" errors
- Database timeout messages
- Application crashes on data operations

**Diagnosis**:
```bash
# Test database connection
php artisan db:connect

# Check database status
php artisan db:status

# Verify credentials
php artisan db:show

# Check PostgreSQL service
systemctl status postgresql
```

**Solutions**:
```bash
# Restart database service
systemctl restart postgresql

# Verify .env database settings
cat .env | grep DB_

# Test connection manually
php artisan tinker
> DB::connection()->select('SELECT 1');

# Check connection limits
php artisan db:connections
```

### Migration Issues

**Diagnosis**:
```bash
# Check migration status
php artisan migrate:status

# View pending migrations
php artisan migrate --pretend
```

**Solutions**:
```bash
# Run migrations
php artisan migrate --force

# Rollback last migration
php artisan migrate:rollback

# Refresh all migrations
php artisan migrate:refresh --seed

# Reset and re-run migrations
php artisan migrate:reset
php artisan migrate --force
```

### Duplicate Key Errors

**Symptoms**:
- "Duplicate entry" errors
- Unique constraint violations

**Diagnosis**:
```bash
# Find duplicate entries
php artisan db:duplicates --table=users

# Check indexes
php artisan db:indexes --table=users
```

**Solutions**:
```bash
# Remove duplicates
php artisan db:deduplicate --table=users

# Rebuild indexes
php artisan db:reindex --table=users

# Extend column length
php artisan db:alter --table=users --column=email --type=varchar(255)
```

### Slow Queries

**Diagnosis**:
```bash
# Check slow queries
php artisan db:slow-queries --threshold=1s

# Enable query logging
php artisan db:log-queries --duration=1000

# Analyze query performance
php artisan db:explain --query="SELECT * FROM users..."
```

**Solutions**:
```bash
# Add missing indexes
php artisan db:add-index --table=users --columns=email

# Optimize tables
php artisan db:optimize --table=users

# Update statistics
php artisan db:stats --table=users
```

---

## Performance Issues

### High Memory Usage

**Diagnosis**:
```bash
# Check memory usage
php artisan system:memory

# Profile memory usage
php artisan debug:memory --duration=30

# Check for memory leaks
php artisan debug:memory-leaks
```

**Solutions**:
```bash
# Clear application cache
php artisan cache:clear

# Clear session data
php artisan session:clear

# Clear old queue jobs
php artisan queue:flush

# Increase PHP memory limit
php -d memory_limit=512M artisan optimize
```

### High CPU Usage

**Diagnosis**:
```bash
# Check CPU usage
php artisan system:cpu

# Find slow processes
php artisan system:processes --cpu

# Check queue backlog
php artisan queue:backlog
```

**Solutions**:
```bash
# Restart queue workers
php artisan queue:restart

# Clear cache
php artisan cache:clear

# Optimize routes
php artisan route:cache

# Restart PHP-FPM
systemctl restart php8.3-fpm
```

### Disk Space Issues

**Diagnosis**:
```bash
# Check disk usage
df -h

# Find large files
find . -type f -size +100M -exec ls -lh {} \;

# Check log sizes
ls -lh storage/logs/

# Check cache size
du -sh storage/framework/cache/
```

**Solutions**:
```bash
# Clear logs
php artisan log:rotate

# Clean old cache
php artisan cache:clear

# Remove temporary files
php artisan tmp:clean

# Clean old backups
php artisan backup:cleanup --older-than=30days
```

### Slow API Responses

**Diagnosis**:
```bash
# Test API response time
curl -w "\nTime: %{time_total}s\n" https://api.example.com/endpoint

# Check API logs
tail -100 storage/logs/api.log

# Monitor API requests
php artisan api:monitor --duration=60
```

**Solutions**:
```bash
# Enable API caching
php artisan api:cache

# Optimize database queries
php artisan db:optimize

# Increase API rate limits
php artisan api:rate-limit --max=120

# Enable query caching
php artisan db:cache:enable
```

---

## User Issues

### Login Problems

**Diagnosis**:
```bash
# Check user status
php artisan user:status --email=user@example.com

# Check authentication logs
php artisan log:view --filter="login" --lines=50

# Verify 2FA status
php artisan security:2fa:status --email=user@example.com
```

**Solutions**:
```bash
# Reset user password
php artisan user:reset-password --email=user@example.com

# Unlock user account
php artisan user:unlock --email=user@example.com

# Reset login attempts
php artisan user:reset-attempts --email=user@example.com

# Reset 2FA
php artisan security:2fa:reset --email=user@example.com
```

### Permission Denied

**Diagnosis**:
```bash
# Check user roles
php artisan user:roles --email=user@example.com

# Check role permissions
php artisan role:permissions --role=role-name

# Verify user tenant
php artisan user:tenant --email=user@example.com
```

**Solutions**:
```bash
# Assign role to user
php artisan user:assign-role \
    --email=user@example.com \
    --role=role-name

# Clear permission cache
php artisan permission:clear-cache

# Rebuild permission cache
php artisan permission:rebuild
```

### Account Creation Failed

**Diagnosis**:
```bash
# Check email uniqueness
php artisan user:check-email --email=user@example.com

# Validate input data
php artisan user:validate --input='{"email":"..."}'

# Check registration settings
php artisan settings:get registration.enabled
```

**Solutions**:
```bash
# Enable registration
php artisan settings:set registration.enabled=true

# Update email uniqueness rules
php artisan user:validate --rules=email.unique

# Clear user cache
php artisan cache:clear --tags=users
```

---

## Tenant Issues

### Tenant Access Problems

**Diagnosis**:
```bash
# Check tenant status
php artisan tenant:status --tenant=tenant-id

# Verify tenant domain
php artisan tenant:domain-check --domain=subdomain.example.com

# Check tenant database
php artisan tenant:db-check --tenant=tenant-id
```

**Solutions**:
```bash
# Reactivate tenant
php artisan tenant:reactivate --tenant=tenant-id

# Update tenant domain
php artisan tenant:domain-update \
    --tenant=tenant-id \
    --domain=new.example.com

# Verify tenant isolation
php artisan tenant:verify-isolation --tenant=tenant-id
```

### Tenant Resource Limits

**Diagnosis**:
```bash
# Check tenant resource usage
php artisan tenant:usage --tenant=tenant-id

# View resource limits
php artisan tenant:limits --tenant=tenant-id
```

**Solutions**:
```bash
# Increase user quota
php artisan tenant:limits \
    --tenant=tenant-id \
    --max-users=15000

# Increase storage
php artisan tenant:allocate \
    --tenant=tenant-id \
    --storage=100GB

# Upgrade tenant plan
php artisan tenant:plan \
    --tenant=tenant-id \
    --plan=enterprise
```

---

## Integration Issues

### External Service Down

**Diagnosis**:
```bash
# Test external service
php artisan integration:test --service=google

# Check service status
php artisan integration:status --service=google

# View integration logs
tail -100 storage/logs/integrations/google.log
```

**Solutions**:
```bash
# Disable integration
php artisan integration:disable --service=google

# Update credentials
php artisan integration:credentials \
    --service=google \
    --api-key=NEW_KEY

# Enable integration
php artisan integration:enable --service=google

# Retry failed operations
php artisan integration:retry --service=google
```

### Webhook Failures

**Diagnosis**:
```bash
# Check webhook queue
php artisan webhook:status

# View failed webhooks
php artisan webhook:failed --service=all

# Test webhook endpoint
curl -X POST https://endpoint.com/webhook -d '{}'
```

**Solutions**:
```bash
# Retry failed webhooks
php artisan webhook:retry --all

# Clear stuck webhooks
php artisan webhook:clear

# Update webhook URL
php artisan webhook:update --id=xxx --url=new-url
```

### Email Not Sending

**Diagnosis**:
```bash
# Check mail queue
php artisan mail:queue --status=pending

# Test email delivery
php artisan mail:test --to=admin@example.com

# Check mail configuration
php artisan mail:config
```

**Solutions**:
```bash
# Clear mail queue
php artisan mail:clear

# Test mail driver
php artisan mail:test-driver

# Restart queue worker
php artisan queue:restart

# Update mail settings
php artisan mail:config --driver=sendgrid
```

---

## Diagnostic Procedures

### Full System Diagnostic

```bash
#!/bin/bash
# Full diagnostic script

echo "=== Alumate System Diagnostic ==="

echo ""
echo "1. Application Status"
php artisan system:status

echo ""
echo "2. Database Health"
php artisan db:health

echo ""
echo "3. Redis Health"
php artisan redis:health

echo ""
echo "4. Queue Status"
php artisan queue:status

echo ""
echo "5. Cache Statistics"
php artisan cache:stats

echo ""
echo "6. Recent Errors"
php artisan log:view --level=error --lines=20

echo ""
echo "7. Active Alerts"
php artisan monitoring:alerts --active

echo ""
echo "8. Resource Usage"
php artisan system:resources
```

### Performance Profiling

```bash
# Profile request
php artisan debug:profile --uri=/admin --duration=60

# Profile database queries
php artisan debug:queries --uri=/api/users

# Profile memory usage
php artisan debug:memory --duration=30

# Profile queue jobs
php artisan debug:queue --jobs=100
```

### Log Analysis

```bash
# Search for errors
grep -r "ERROR" storage/logs/ --since="24 hours ago"

# Search for specific user
grep -r "user_id: 123" storage/logs/ --since="1 hour ago"

# Search for slow queries
grep -r "slow query" storage/logs/ --since="7 days ago"

# Analyze error patterns
php artisan log:analyze --period=7days
```

---

## Support Procedures

### When to Escalate

| Issue Type | Self-Service | Escalate |
|------------|--------------|----------|
| Known issue in guide | ✅ | After 30 min |
| Unknown issue | ✅ | After 1 hour |
| Security incident | ❌ | Immediate |
| Data loss | ❌ | Immediate |
| System down | ❌ | Immediate |

### Information to Gather

Before contacting support, gather:

```bash
# 1. Generate diagnostic report
php artisan system:diagnostic --output=json > diagnostic_$(date +%Y%m%d_%H%M%S).json

# 2. Export recent logs
php artisan log:export \
    --since="24 hours ago" \
    --output=logs_$(date +%Y%m%d).zip

# 3. Capture current state
php artisan system:capture \
    --output=state_$(date +%Y%m%d_%H%M%S).json
```

### Support Contact Information

**For Issues:**

1. **Check Documentation**: This troubleshooting guide
2. **Check Monitoring**: `/admin/monitoring`
3. **Check Runbooks**: https://wiki.alumate.com/runbooks
4. **Contact Support**:
   - Email: support@alumate.com
   - Slack: #devops-support
   - Emergency: +1-800-ALUMATE

### Escalation Path

1. **Self-Service** (0-30 min)
   - Review this troubleshooting guide
   - Check monitoring dashboard
   - Run diagnostic commands

2. **On-Call Engineer** (30 min - 2 hours)
   - Contact via Slack #devops
   - Provide diagnostic output
   - Describe issue and steps taken

3. **Platform Lead** (2+ hours)
   - Escalate via Slack @platform-lead
   - Include incident timeline
   - Provide full diagnostic report

4. **Engineering Manager** (4+ hours)
   - Email: engineering-manager@alumate.com
   - Include root cause analysis
   - Provide recovery status

5. **CTO** (Critical Issues Only)
   - Email: cto@alumate.com
   - Include impact assessment
   - Provide business continuity status

---

## Additional Resources

### Related Documentation

- [Administrator Training Guide](../admin-training/administrator-guide.md)
- [Monitoring Documentation](monitoring-alerting.md)
- [Security Documentation](security-administration.md)
- [Backup Documentation](backup-recovery.md)

### Quick Reference

| Command | Description |
|---------|-------------|
| `php artisan system:diagnostic` | Full system check |
| `php artisan db:health` | Database health |
| `php artisan log:view` | View logs |
| `php artisan cache:clear` | Clear cache |
| `php artisan queue:restart` | Restart queues |
| `php artisan optimize` | Optimize application |

### Common Solutions

| Issue | Quick Fix |
|-------|-----------|
| Blank screen | `php artisan config:clear` |
| 500 Error | `APP_DEBUG=true` |
| Slow response | `php artisan optimize` |
| Login fails | `php artisan cache:clear` |
| Queue stuck | `php artisan queue:restart` |
| Cache issues | `php artisan cache:clear-all` |

---

**Last Updated:** February 2025  
**Documentation Version:** 1.0.0
