# Backup and Recovery Documentation

This document describes the comprehensive backup and disaster recovery system for the Alumni Platform.

## Table of Contents

1. [Overview](#overview)
2. [Backup Types](#backup-types)
3. [Automated Backup Schedule](#automated-backup-schedule)
4. [Retention Policies](#retention-policies)
5. [Backup Storage](#backup-storage)
6. [Backup Verification](#backup-verification)
7. [Notifications](#notifications)
8. [Recovery Procedures](#recovery-procedures)
9. [Command Reference](#command-reference)
10. [Troubleshooting](#troubleshooting)

---

## Overview

The Alumni Platform implements a multi-layered backup system that includes:

- **Database Backups**: Complete PostgreSQL database backups with compression
- **File Backups**: Storage directory backups with incremental support
- **Configuration Backups**: Environment and configuration file backups
- **Remote Storage**: Automatic replication to cloud storage (AWS S3, GCP, Azure)
- **Verification**: Automated integrity checks and checksum validation
- **Notifications**: Real-time alerts for backup success/failure

---

## Backup Types

### Database Backups

PostgreSQL database dumps are created using `pg_dump` with custom format and compression.

**What is backed up:**
- All database tables and data
- Indexes and constraints
- Views and functions
- Sequences and triggers

**Retention:** 30 days (configurable)

### Files Backups

The storage directory is archived using tar with gzip compression.

**What is backed up:**
- `storage/app/public` - User-uploaded files
- `storage/framework/cache` - Cache data
- `storage/framework/sessions` - Session data
- `storage/framework/views` - Compiled views
- `storage/logs` - Application logs

**Retention:** 90 days (configurable)

### Configuration Backups

Critical configuration files are archived for disaster recovery.

**What is backed up:**
- `.env.production` - Environment configuration
- `config/` - Laravel configuration files
- `infrastructure/production/` - Infrastructure configuration
- `docker-compose.prod.yml` - Docker configuration
- `nginx.conf` - Web server configuration

**Retention:** 365 days (configurable)

---

## Automated Backup Schedule

The backup system is fully automated using Laravel's task scheduler.

### Default Schedule

| Backup Type | Schedule | Time |
|-------------|----------|------|
| Database | Daily | 1:00 AM UTC |
| Files | Daily | 2:00 AM UTC |
| Configuration | Weekly (Monday) | 3:00 AM UTC |
| Cleanup | Daily | 5:00 AM UTC |
| Verification | Weekly (Sunday) | 6:00 AM UTC |

### Scheduler Configuration

The schedule is defined in [`app/Console/Kernel.php`](app/Console/Kernel.php):

```php
protected function schedule(Schedule $schedule): void
{
    // Database backup - Daily at 1:00 AM
    $schedule->command('backup:schedule --type=database')
        ->dailyAt('01:00')
        ->withoutOverlapping()
        ->runInBackground()
        ->onOneServer();

    // Files backup - Daily at 2:00 AM
    $schedule->command('backup:schedule --type=files')
        ->dailyAt('02:00')
        ->withoutOverlapping()
        ->runInBackground()
        ->onOneServer();

    // Configuration backup - Weekly on Monday at 3:00 AM
    $schedule->command('backup:schedule --type=config')
        ->weeklyOn(Schedule::MONDAY, '03:00')
        ->withoutOverlapping()
        ->runInBackground()
        ->onOneServer();
}
```

---

## Retention Policies

The system automatically manages backup retention based on configurable policies.

### Default Retention Periods

| Backup Type | Retention | Reason |
|-------------|-----------|--------|
| Database | 30 days | Daily operational recovery |
| Files | 90 days | File recovery needs longer retention |
| Configuration | 1 year | Compliance and audit requirements |

### Configuration

Environment variables control retention:

```bash
BACKUP_RETENTION_DATABASE=30      # Days to keep database backups
BACKUP_RETENTION_FILES=90         # Days to keep file backups
BACKUP_RETENTION_CONFIG=365       # Days to keep configuration backups
BACKUP_MAX_AGE_DAYS=90            # Maximum age for any backup
BACKUP_MAX_COUNT=100              # Maximum number of backups to keep
BACKUP_KEEP_CRITICAL=true         # Always keep critical backups
```

### Manual Cleanup

Run manual cleanup with dry-run option:

```bash
# Preview what would be deleted
php artisan backup:cleanup --dry-run

# Execute cleanup
php artisan backup:cleanup
```

---

## Backup Storage

### Local Storage

Backups are stored in the application directory:

```
backups/
├── database/
│   └── database_YYYY-MM-DD_HH-MM-SS.sql.gz
├── files/
│   └── files_YYYY-MM-DD_HH-MM-SS.tar.gz
└── config/
    └── config_YYYY-MM-DD_HH-MM-SS.tar.gz
```

### Remote Storage

Backups can be replicated to cloud storage providers:

#### AWS S3

```bash
BACKUP_PROVIDER=aws_s3
BACKUP_AWS_BUCKET=your-backup-bucket
BACKUP_AWS_STORAGE_CLASS=STANDARD_IA
```

#### Google Cloud Storage

```bash
BACKUP_PROVIDER=gcp
BACKUP_GCS_BUCKET=your-backup-bucket
```

#### Azure Blob Storage

```bash
BACKUP_PROVIDER=azure
BACKUP_AZURE_CONTAINER=backups
```

---

## Backup Verification

All backups undergo integrity verification before being marked as complete.

### Verification Checks

1. **File Existence**: Backup file must exist
2. **Minimum Size**: Files must be at least 1KB (except empty database dumps)
3. **Compression Integrity**: Gzip files pass integrity test
4. **Checksum Validation**: SHA-256 checksum verification
5. **Age Check**: Backups older than 1 year trigger warnings

### Manual Verification

```bash
# Verify specific backup
php artisan backup:verify --path=backups/database/db_2024-01-15.sql.gz

# Verify all backups
php artisan backup:verify
```

### Verification in Shell Script

```bash
./backup-config.sh verify /path/to/backup.tar.gz
```

---

## Notifications

The system sends notifications for backup events.

### Notification Channels

- **Email**: Configured recipients receive detailed backup reports
- **Database**: Notifications stored for admin dashboard display
- **Slack**: Optional Slack integration via webhook

### Configuration

```bash
BACKUP_NOTIFICATIONS_ENABLED=true
BACKUP_NOTIFY_ON_SUCCESS=true
BACKUP_NOTIFY_ON_FAILURE=true
BACKUP_NOTIFICATION_CHANNELS=mail
ALERT_EMAIL_RECIPIENTS=admin@example.com,ops@example.com
```

### Notification Classes

- [`BackupCompletedNotification`](app/Notifications/BackupCompletedNotification.php) - Success notifications
- [`BackupFailedNotification`](app/Notifications/BackupFailedNotification.php) - Failure alerts with troubleshooting info

---

## Recovery Procedures

### Pre-Recovery Checklist

Before performing any recovery:

1. [ ] Put application in maintenance mode
2. [ ] Notify users of planned downtime
3. [ ] Verify backup integrity
4. [ ] Prepare rollback plan
5. [ ] Document current state

### Database Recovery

#### Using Laravel Command

```bash
# List available backups
php artisan backup:status

# Restore database from specific backup
php artisan backup:restore database /path/to/backup.sql.gz --force
```

#### Using Shell Script

```bash
./backup-config.sh restore-db backups/database/db_2024-01-15_010000.sql.gz
```

#### Manual Recovery

```bash
# Stop application
php artisan down

# Restore database
pg_restore -h db_host -U username -d database_name --clean --if-exists backup.dump

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Bring application back online
php artisan up
```

### Files Recovery

#### Using Laravel Command

```bash
php artisan backup:restore files /path/to/backup.tar.gz --force
```

#### Using Shell Script

```bash
./backup-config.sh restore-files backups/files/files_2024-01-15_020000.tar.gz
```

### Configuration Recovery

```bash
# Extract config backup
tar -xzf config_2024-01-15_030000.tar.gz -C /tmp/

# Restore specific files
cp /tmp/config/.env.production /path/to/.env.production
```

---

## Command Reference

### Laravel Commands

| Command | Description |
|---------|-------------|
| `php artisan backup:schedule --type=all` | Run full backup |
| `php artisan backup:schedule --type=database` | Backup database only |
| `php artisan backup:schedule --type=files` | Backup files only |
| `php artisan backup:schedule --type=config` | Backup configuration only |
| `php artisan backup:restore <type> <path>` | Restore from backup |
| `php artisan backup:verify` | Verify backup integrity |
| `php artisan backup:verify --path=<path>` | Verify specific backup |
| `php artisan backup:cleanup` | Clean old backups |
| `php artisan backup:cleanup --dry-run` | Preview cleanup |
| `php artisan backup:status` | Show backup status |

### Shell Script Commands

| Command | Description |
|---------|-------------|
| `./backup-config.sh full` | Run full backup |
| `./backup-config.sh db` | Backup database |
| `./backup-config.sh files` | Backup files |
| `./backup-config.sh config` | Backup configuration |
| `./backup-config.sh cleanup` | Clean old backups |
| `./backup-config.sh status` | Show backup status |
| `./backup-config.sh verify <path>` | Verify backup |
| `./backup-config.sh restore-db <path>` | Restore database |
| `./backup-config.sh restore-files <path>` | Restore files |

---

## Troubleshooting

### Common Issues

#### Backup Fails - Disk Full

```bash
# Check disk space
df -h

# Clean up old backups
php artisan backup:cleanup

# Check backup directory size
du -sh backups/
```

#### Database Connection Failed

```bash
# Verify database connection
php artisan db:connect

# Check environment variables
env | grep DB_

# Test PostgreSQL connection
psql -h hostname -U username -d database_name
```

#### Remote Upload Fails

```bash
# Check AWS credentials
aws sts get-caller-identity

# Verify bucket access
aws s3 ls s3://bucket-name/

# Check network connectivity
curl -I https://s3.amazonaws.com
```

#### Restore Fails - Permission Denied

```bash
# Fix file permissions
chmod -R 755 storage/
chown -R www-data:www-data storage/

# Check SELinux context (if applicable)
semanage fcontext -a -t httpd_sys_content_t "/path/to/storage(/.*)?"
```

### Logs Location

| Log | Location |
|-----|----------|
| Backup Logs | `storage/logs/backup.log` |
| Scheduler Logs | `storage/logs/scheduler.log` |
| Notification Logs | `storage/logs/backup_notifications.log` |
| Laravel Logs | `storage/logs/laravel.log` |

### Monitoring

Check backup health endpoints:

```bash
# View recent backups
php artisan backup:status

# Check for failed backups
grep -i "failed" storage/logs/backup.log
```

---

## Best Practices

1. **Test Recovery Regularly**: Restore backups to test environment monthly
2. **Monitor Disk Space**: Set up alerts for backup directory size
3. **Verify Backups**: Regular verification prevents restore surprises
4. **Document Changes**: Log all recovery operations
5. **Review Retention**: Adjust policies based on compliance requirements
6. **Multi-Region Storage**: Consider cross-region replication for critical backups
7. **Access Control**: Limit backup access to authorized personnel only

---

## Emergency Contacts

For backup-related emergencies:

- **Primary**: DevOps Team - devops@alumni-platform.com
- **Secondary**: Infrastructure Lead
- **Escalation**: CTO

---

## References

- [Infrastructure README](infrastructure/README.md)
- [Production Configuration](docs/PRODUCTION-CONFIGURATION.md)
- [Monitoring Documentation](docs/MONITORING.md)
- [PostgreSQL Backup Documentation](https://www.postgresql.org/docs/current/backup.html)
- [Laravel Task Scheduling](https://laravel.com/docs/scheduling)
