# Backup and Recovery Guide

**ABOUTME:** Comprehensive guide for backing up and recovering the Alumate platform including backup strategies, restore procedures, and disaster recovery planning.

## Table of Contents

1. [Overview](#overview)
2. [Backup Strategies](#backup-strategies)
3. [Database Backups](#database-backups)
4. [File Backups](#file-backups)
5. [Restore Procedures](#restore-procedures)
6. [Disaster Recovery](#disaster-recovery)
7. [Business Continuity](#business-continuity)
8. [Backup Troubleshooting](#backup-troubleshooting)

---

## Overview

The Alumate backup and recovery system provides:

- **Automated Backups**: Scheduled backup jobs with retention policies
- **Multiple Backup Types**: Full, incremental, and differential backups
- **Offsite Storage**: Secure backup storage in multiple locations
- **Encryption**: Encrypted backups for data protection
- **Testing**: Regular backup restoration testing
- **Disaster Recovery**: Comprehensive recovery procedures

### Backup Components

| Component | Description | Priority |
|-----------|-------------|----------|
| Database | PostgreSQL database | Critical |
| User Files | Uploaded files and media | High |
| Configuration | System configuration files | High |
| Application Code | Application source code | Medium |
| Logs | Application and system logs | Low |

---

## Backup Strategies

### Backup Types

| Type | Description | Use Case |
|------|-------------|----------|
| Full | Complete backup of all data | Weekly, before major changes |
| Incremental | Changes since last backup | Daily, for efficiency |
| Differential | Changes since last full backup | Alternative to incremental |

### Recommended Schedule

| Frequency | Type | Retention |
|-----------|------|-----------|
| Daily | Incremental | 7 days |
| Weekly | Full | 4 weeks |
| Monthly | Full | 12 months |
| Yearly | Full | 7 years |

### Backup Configuration

```php
// config/backup.php
return [
    'enabled' => env('BACKUP_ENABLED', true),
    'schedule' => [
        'daily' => '2:00 AM',
        'weekly' => 'Sunday 3:00 AM',
        'monthly' => '1st of month 4:00 AM',
    ],
    'retention' => [
        'daily' => 7,
        'weekly' => 4,
        'monthly' => 12,
        'yearly' => 7,
    ],
    'storage' => [
        'local' => [
            'driver' => 'local',
            'path' => storage_path('backups'),
        ],
        's3' => [
            'driver' => 's3',
            'bucket' => 'alumate-backups',
            'region' => 'us-east-1',
        ],
    ],
];
```

---

## Database Backups

### Creating Database Backups

```bash
# Create full database backup
php artisan backup:create --type=full

# Create incremental backup
php artisan backup:create --type=incremental

# Create backup of specific tables
php artisan backup:create \
    --type=tables \
    --tables="users,graduates,jobs"

# Create backup with compression
php artisan backup:create \
    --type=full \
    --compression=gzip
```

### Database Backup Options

```bash
# Backup with verbose output
php artisan backup:create --type=full --verbose

# Backup to specific destination
php artisan backup:create \
    --type=full \
    --destination=s3://bucket/backups

# Backup with encryption
php artisan backup:create \
    --type=full \
    --encrypt \
    --encryption-key=xxx
```

### PostgreSQL-Specific Backups

```bash
# pg_dump full backup
pg_dump -h localhost -U alumate -Fc -f backup.dump alumate

# Backup with custom format
pg_dump -h localhost -U alumate \
    --format=custom \
    --compress=9 \
    --file=backup.dump.gz \
    alumate

# Incremental backup (using WAL)
php artisan backup:wal:enable
php artisan backup:wal:archive
```

### Backup Management

```bash
# List all backups
php artisan backup:list

# List backups by type
php artisan backup:list --type=full

# Verify backup integrity
php artisan backup:verify --backup=backup-id

# Check backup size
php artisan backup:size --backup=backup-id

# Delete old backups
php artisan backup:cleanup --older-than="30 days"

# Delete specific backup
php artisan backup:delete --backup=backup-id
```

---

## File Backups

### Media Assets Backup

```bash
# Backup all media files
php artisan backup:media --destination=s3://bucket/media

# Backup media with timestamp
php artisan backup:media \
    --destination=s3://bucket/media \
    --timestamp

# Sync assets between storage providers
php artisan media:sync --source=local --target=s3

# Verify asset integrity
php artisan media:verify --check-checksums
```

### Configuration Backup

```bash
# Backup configuration files
php artisan backup:config --files=".env,config/*.php"

# Export tenant configurations
php artisan backup:tenants --format=json

# Backup all .env files
php artisan backup:env --destination=secure/
```

### Custom File Backups

```bash
# Backup custom directories
php artisan backup:files \
    --paths="storage/app/public,resources/views/custom" \
    --destination=s3://bucket/custom

# Backup with exclusions
php artisan backup:files \
    --paths="storage" \
    --exclude="storage/logs,storage/cache" \
    --destination=s3://bucket/storage
```

### File Backup Options

```bash
# Compress files
php artisan backup:files --compression=gzip

# Encrypt files
php artisan backup:files --encrypt

# Verify after backup
php artisan backup:files --verify
```

---

## Restore Procedures

### Database Restoration

```bash
# Restore database from backup
php artisan backup:restore \
    --backup=backup-id \
    --target=database

# Restore specific tables
php artisan backup:restore \
    --backup=backup-id \
    --tables="users,graduates"

# Restore to point-in-time
php artisan backup:restore \
    --backup=backup-id \
    --point-in-time="2024-01-15 12:00:00"

# Restore with verification
php artisan backup:restore \
    --backup=backup-id \
    --verify
```

### Manual pg_restore

```bash
# Stop application
php artisan down

# Drop and recreate database
php artisan db:wipe
php artisan db:create

# Restore from pg_dump backup
pg_restore -h localhost -U alumate -d alumate backup.dump

# Run migrations
php artisan migrate --force

# Bring application up
php artisan up
```

### File Restoration

```bash
# Restore all files
php artisan backup:restore \
    --backup=backup-id \
    --target=files

# Restore specific directory
php artisan backup:restore \
    --backup=backup-id \
    --target=files \
    --path="storage/app/public"

# Restore configuration
php artisan backup:restore \
    --backup=config-backup-id \
    --target=config
```

### Tenant Data Restoration

```bash
# Restore specific tenant
php artisan tenant:restore \
    --tenant=tenant-id \
    --backup=backup-id

# Restore tenant to specific point
php artisan tenant:restore \
    --tenant=tenant-id \
    --backup=backup-id \
    --point-in-time="2024-01-15"
```

### Restoration Verification

```bash
# Verify database integrity
php artisan db:verify

# Check record counts
php artisan backup:verify \
    --backup=backup-id \
    --tables="users,posts,comments"

# Run smoke tests
php artisan backup:smoke-test
```

---

## Disaster Recovery

### Recovery Procedures

#### Database Failure

```bash
# 1. Check database status
php artisan db:status

# 2. Identify the issue
php artisan db:diagnostic

# 3. If unrecoverable, restore from backup
php artisan backup:restore \
    --backup=latest \
    --target=database

# 4. Verify restoration
php artisan db:verify
```

#### Server Failure

```bash
# 1. Bring up backup server
php artisan ha:failover --service=database

# 2. Verify data integrity
php artisan db:verify

# 3. Update DNS if needed
php artisan dns:update --record=db.example.com

# 4. Monitor for issues
php artisan monitoring:watch --service=database
```

#### Ransomware Attack

```bash
# 1. Isolate affected systems
php artisan security:isolate --all

# 2. Identify affected data
php artisan security:assess --full

# 3. Restore from clean backup
php artisan backup:restore \
    --backup=pre-incident-backup \
    --type=full

# 4. Security scan
php artisan security:scan --full
```

### Recovery Testing

```bash
# Test disaster recovery plan
php artisan recovery:test --scenario=database_failure

# Validate backup restoration
php artisan recovery:validate --backup=backup-id

# Generate recovery report
php artisan recovery:report --format=pdf

# Schedule regular DR tests
php artisan recovery:schedule --frequency=monthly
```

### Recovery Time Objectives

| Scenario | RTO (Recovery Time Objective) | RPO (Recovery Point Objective) |
|----------|-------------------------------|-------------------------------|
| Database Failure | 1 hour | 1 hour |
| Server Failure | 4 hours | 1 hour |
| Data Corruption | 4 hours | 1 hour |
| Complete Disaster | 24 hours | 24 hours |

---

## Business Continuity

### High Availability

```bash
# Configure database replication
php artisan ha:configure-db \
    --master="db1.example.com" \
    --slaves="db2.example.com,db3.example.com"

# Set up load balancing
php artisan ha:configure-lb \
    --servers="web1.example.com,web2.example.com"

# Check HA status
php artisan ha:status
```

### Failover Management

```bash
# Trigger failover
php artisan ha:failover --service=database

# Check failover status
php artisan ha:status

# Configure failover alerts
php artisan ha:alert \
    --service=web \
    --action="email:admin@example.com"

# Test failover
php artisan ha:test --service=database
```

### Backup Locations

| Location | Type | Purpose |
|----------|------|---------|
| Primary | On-site | Quick recovery |
| Secondary | Off-site (region) | Regional disaster |
| Tertiary | Cloud storage | Long-term retention |

---

## Backup Troubleshooting

### Common Issues

#### 1. Backup Fails

**Symptom**: Backup job fails without clear error

**Solution**:
```bash
# Check backup logs
tail -100 storage/logs/backup.log

# Verify disk space
df -h

# Test database connection
php artisan db:connect

# Retry backup
php artisan backup:retry --id=backup-id
```

#### 2. Backup Too Large

**Symptom**: Backup files exceed expected size

**Solution**:
```bash
# Check what's included
php artisan backup:contents --id=backup-id

# Enable compression
php artisan backup:create --compression=gzip

# Exclude unnecessary data
php artisan backup:create \
    --exclude="logs,cache,tmp"

# Implement incremental backups
php artisan backup:wal:enable
```

#### 3. Restore Fails

**Symptom**: Restore process fails mid-operation

**Solution**:
```bash
# Verify backup integrity
php artisan backup:verify --id=backup-id

# Check available disk space
df -h /destination/path

# Try manual restore
php artisan backup:restore:manual --id=backup-id

# Check logs for specific error
grep "ERROR" storage/logs/backup.log
```

#### 4. Backup Not Completing

**Symptom**: Backup job hangs or times out

**Solution**:
```bash
# Check running jobs
php artisan queue:work --once

# Increase timeout
php artisan backup:create \
    --type=full \
    --timeout=3600

# Check for locks
php artisan backup:clear-locks

# Kill stuck process
php artisan backup:kill --pid=12345
```

### Diagnostic Commands

```bash
# Full backup diagnostic
php artisan backup:diagnostic

# Check backup health
php artisan backup:health

# Verify storage connectivity
php artisan backup:test-storage

# Check backup schedule
php artisan backup:schedule --show
```

### Support Procedures

1. Gather diagnostic information:
   ```bash
   php artisan backup:diagnostic --output=json > backup_diagnostic.json
   ```

2. Review recent backup logs

3. Check monitoring history

4. Contact support with:
   - Backup ID
   - Error messages
   - Diagnostic output
   - Affected data

---

## Additional Resources

### Related Documentation

- [Administrator Training Guide](../admin-training/administrator-guide.md)
- [Deployment Documentation](../deployment/)
- [Database Configuration](../config/database.php)
- [Infrastructure Documentation](../infrastructure/)

### Command Reference

| Command | Description |
|---------|-------------|
| `php artisan backup:create` | Create new backup |
| `php artisan backup:list` | List all backups |
| `php artisan backup:restore` | Restore from backup |
| `php artisan backup:verify` | Verify backup integrity |
| `php artisan backup:cleanup` | Clean old backups |
| `php artisan recovery:test` | Test recovery procedures |

### Backup Retention Summary

| Backup Type | Frequency | Retention | Storage |
|-------------|-----------|-----------|---------|
| Transaction Logs | Continuous | 7 days | S3 |
| Incremental | Daily | 7 days | S3 |
| Full (Weekly) | Weekly | 4 weeks | S3 + Glacier |
| Full (Monthly) | Monthly | 12 months | S3 + Glacier |
| Full (Yearly) | Yearly | 7 years | S3 + Glacier |

---

**Last Updated:** February 2025  
**Documentation Version:** 1.0.0
