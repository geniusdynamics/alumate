# Schema-Based Tenancy Migration Rollback Guide

## Overview

This document provides comprehensive procedures for rolling back schema-based tenancy migrations to the hybrid tenant_id approach. The rollback functionality is a critical disaster recovery mechanism for failed migrations.

## Prerequisites

Before performing a rollback, ensure:

1. **Database Access**: You have direct access to the PostgreSQL database
2. **Backup Available**: A recent backup exists (created automatically before rollback)
3. **Tenant Information**: You know the tenant ID to rollback
4. **Sufficient Disk Space**: At least 2x the current database size for backup operations
5. **Maintenance Window**: Schedule rollback during low-traffic periods

## Rollback Command Options

The rollback command provides several options for safe execution:

```bash
php artisan tenancy:migrate-to-schema --rollback-tenant=<tenant-id> [options]
```

### Available Options

| Option | Description | Default |
|--------|-------------|----------|
| `--rollback-tenant` | Tenant ID to rollback (required) | - |
| `--skip-backup` | Skip automatic backup creation | false |
| `--force` | Force rollback without confirmation prompts | false |
| `--batch-size` | Number of records to process per batch | 100 |

## Rollback Procedure

### Step 1: Verify Tenant Status

Before initiating rollback, verify the tenant is eligible:

```bash
# Check tenant migration status
php artisan tinker
>>> $tenant = App\Models\Tenant::find('<tenant-id>');
>>> $tenant->schema_name;
>>> $tenant->is_schema_migrated;
```

The tenant must have:
- `schema_name` set (not null)
- `is_schema_migrated` = true
- Existing schema in the database

### Step 2: Create Manual Backup (Optional but Recommended)

Although rollback creates automatic backups, creating a manual backup is recommended:

```bash
php artisan backup:create --type=full --compress
```

### Step 3: Perform Dry Run

Test the rollback without making changes:

```bash
php artisan tenancy:migrate-to-schema --rollback-tenant=<tenant-id> --dry-run
```

This will:
- Verify tenant eligibility
- Check schema existence
- Validate main tables
- Display what would be rolled back

### Step 4: Execute Rollback

Execute the actual rollback:

```bash
# Interactive mode (requires confirmation)
php artisan tenancy:migrate-to-schema --rollback-tenant=<tenant-id>

# Force mode (no confirmation)
php artisan tenancy:migrate-to-schema --rollback-tenant=<tenant-id> --force

# Skip backup (not recommended)
php artisan tenancy:migrate-to-schema --rollback-tenant=<tenant-id> --skip-backup
```

### Step 5: Verify Rollback

After rollback completes, verify:

```bash
php artisan tinker
>>> $tenant = App\Models\Tenant::find('<tenant-id>');
>>> $tenant->schema_name;  // Should be null
>>> $tenant->is_schema_migrated;  // Should be false
```

Check data integrity:

```bash
php artisan tenancy:migrate-to-schema --verify-only
```

## Rollback Process Details

### What Happens During Rollback

1. **Verification Phase**
   - Tenant exists and is schema-migrated
   - Tenant schema exists in database
   - Main tables exist with tenant_id columns

2. **Backup Phase**
   - Creates full database backup
   - Verifies backup file exists and is not empty
   - Logs backup details

3. **Data Migration Phase**
   - Deletes existing records for tenant from main tables
   - Copies all data from tenant schema to main tables
   - Adds tenant_id to each record
   - Processes in batches (configurable size)

4. **Verification Phase**
   - Compares record counts between schema and main tables
   - Ensures data integrity
   - Fails if counts don't match

5. **Cleanup Phase**
   - Updates tenant record (removes schema info)
   - Drops tenant schema
   - Logs completion

### Tables Affected

The following tables are rolled back:

| Table | Description |
|-------|-------------|
| `students` | Student records |
| `courses` | Course records |
| `enrollments` | Enrollment records |
| `grades` | Grade records |
| `activity_logs` | Activity log records |

## Error Handling

### Common Errors and Solutions

#### Error: Tenant not found

```
❌ Tenant not found: <tenant-id>
```

**Solution**: Verify the tenant ID is correct and exists in the tenants table.

#### Error: Tenant is not schema-migrated

```
❌ Tenant is not schema-migrated: <tenant-id>
```

**Solution**: This tenant cannot be rolled back. Check if migration was completed successfully.

#### Error: Tenant schema does not exist

```
❌ Tenant schema does not exist: <schema_name>
```

**Solution**: The schema may have been manually deleted. Check database schemas.

#### Error: Main table missing tenant_id column

```
❌ Main table missing tenant_id column: <table>
```

**Solution**: Add tenant_id column to the main table before rollback.

#### Error: Rollback verification failed

```
❌ Rollback verification failed for <table>: Schema(<count>) != Main(<count>)
```

**Solution**: Data integrity issue. Restore from backup and investigate.

### Rollback Failure Recovery

If rollback fails:

1. **Check Logs**: Review detailed logs in `storage/logs/schema-migration-*.json`
2. **Restore Backup**: Use the pre-rollback backup to restore database
3. **Investigate**: Check for data inconsistencies or schema issues
4. **Retry**: After fixing issues, attempt rollback again

## Monitoring and Logging

### Log Files

Rollback operations create detailed logs:

```
storage/logs/schema-migration-<timestamp>.json
```

Log entries include:
- Timestamp
- Migration ID
- Operation performed
- Context data
- Success/failure status

### Database Logs

Backup operations are logged in the `backup_logs` table:

```sql
SELECT * FROM backup_logs 
WHERE backup_type = 'full' 
ORDER BY created_at DESC 
LIMIT 1;
```

## Best Practices

### Before Rollback

1. **Test in Staging**: Always test rollback in staging environment first
2. **Notify Users**: Inform users of potential downtime
3. **Monitor Resources**: Watch CPU, memory, and disk usage
4. **Document State**: Record current tenant state before rollback

### During Rollback

1. **Monitor Progress**: Watch console output for errors
2. **Check Disk Space**: Ensure sufficient space for backups
3. **Verify Batches**: Monitor batch processing progress
4. **Keep Terminal Open**: Don't interrupt the process

### After Rollback

1. **Verify Data**: Check data integrity and counts
2. **Test Application**: Ensure application works correctly
3. **Monitor Performance**: Watch for performance issues
4. **Clean Up**: Remove old backups after verification

## Performance Considerations

### Batch Size

Adjust batch size based on data volume:

| Data Volume | Recommended Batch Size |
|-------------|----------------------|
| < 10,000 records | 100 (default) |
| 10,000 - 100,000 records | 500 |
| 100,000 - 1,000,000 records | 1,000 |
| > 1,000,000 records | 5,000 |

### Estimated Rollback Time

Approximate rollback times based on data volume:

| Records | Estimated Time |
|---------|----------------|
| 1,000 | 1-2 minutes |
| 10,000 | 5-10 minutes |
| 100,000 | 30-60 minutes |
| 1,000,000 | 2-4 hours |

## Security Considerations

### Access Control

- Only administrators should perform rollbacks
- Use environment-specific credentials
- Restrict database access during rollback
- Audit all rollback operations

### Data Privacy

- Backups contain sensitive data
- Encrypt backup files
- Secure backup storage location
- Follow data retention policies

## Troubleshooting

### Rollback Stuck

If rollback appears stuck:

1. Check database connections
2. Verify batch processing is active
3. Check for long-running queries
4. Review system resources

### Partial Rollback

If rollback completes partially:

1. Verify which tables were rolled back
2. Check data counts in affected tables
3. Manually complete remaining rollback
4. Update tenant record accordingly

### Schema Won't Drop

If schema won't drop:

```sql
-- Check for active connections
SELECT * FROM pg_stat_activity WHERE datname = current_database();

-- Terminate connections if needed
SELECT pg_terminate_backend(pid) 
FROM pg_stat_activity 
WHERE datname = current_database() 
AND pid <> pg_backend_pid();

-- Drop schema manually
DROP SCHEMA IF EXISTS <schema_name> CASCADE;
```

## Support and Escalation

### When to Escalate

- Rollback fails repeatedly
- Data corruption detected
- Performance severely degraded
- Unexpected errors occur

### Information to Provide

When seeking support, provide:

1. Tenant ID and name
2. Error messages and stack traces
3. Log files from rollback attempt
4. Database size and record counts
5. Steps taken before rollback

## Related Documentation

- [Migration Guide](./MIGRATION_GUIDE.md)
- [Backup Procedures](./BACKUP_PROCEDURES.md)
- [Database Schema](./DATABASE_SCHEMA.md)
- [Troubleshooting Guide](./TROUBLESHOOTING.md)

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2026-01-29 | Initial rollback implementation |
