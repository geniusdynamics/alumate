# Disaster Recovery Plan for Schema-Based Tenancy

## Overview
This document outlines the disaster recovery procedures for the schema-based tenancy system, covering various failure scenarios and recovery strategies.

## Disaster Recovery Objectives

### Recovery Time Objective (RTO)
- **Critical Systems**: 2 hours maximum downtime
- **Non-Critical Systems**: 8 hours maximum downtime
- **Data Recovery**: 1 hour maximum for recent data

### Recovery Point Objective (RPO)
- **Database**: Maximum 15 minutes of data loss
- **Application State**: Maximum 5 minutes of data loss
- **File Storage**: Maximum 1 hour of data loss

## Disaster Scenarios

### Scenario 1: Complete Database Failure

#### Symptoms
- Database server unresponsive
- Connection timeouts
- Data corruption detected
- Hardware failure

#### Recovery Procedure

**Step 1: Immediate Response (0-15 minutes)**
```bash
# Enable maintenance mode
php artisan down --message="Database maintenance in progress"

# Assess database status
psql -h database_host -U username -d database_name -c "SELECT 1;"

# Check database logs
tail -f /var/log/postgresql/postgresql.log
```

**Step 2: Database Recovery (15-60 minutes)**
```bash
# Option A: Restore from latest backup
psql -U username -d database_name < backups/latest_full_backup.sql

# Option B: Point-in-time recovery
pg_restore -U username -d database_name -t "2024-01-15 14:30:00" backups/continuous_backup/

# Verify database integrity
psql -U username -d database_name -c "SELECT schemaname, tablename FROM pg_tables WHERE schemaname LIKE 'tenant_%';"
```

**Step 3: Schema Validation (60-90 minutes)**
```php
<?php
// validate_schemas.php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

$tenants = Tenant::all();

foreach ($tenants as $tenant) {
    $schemaName = "tenant_{$tenant->id}";
    
    // Check schema exists
    $schemaExists = DB::select(
        "SELECT 1 FROM information_schema.schemata WHERE schema_name = ?",
        [$schemaName]
    );
    
    if (empty($schemaExists)) {
        echo "ERROR: Schema {$schemaName} missing for tenant {$tenant->id}\n";
        // Recreate schema
        DB::statement("CREATE SCHEMA IF NOT EXISTS {$schemaName}");
        // Run migrations
        Artisan::call('migrate', ['--path' => 'database/migrations', '--database' => $schemaName]);
    } else {
        echo "OK: Schema {$schemaName} exists\n";
    }
}
```

### Scenario 2: Tenant Schema Corruption

#### Symptoms
- Specific tenant unable to access data
- Schema-specific errors
- Data inconsistencies for one tenant

#### Recovery Procedure

**Step 1: Isolate Affected Tenant**
```php
// Temporarily disable affected tenant
use App\Models\Tenant;

$affectedTenant = Tenant::find($tenantId);
$affectedTenant->update(['status' => 'maintenance']);
```

**Step 2: Schema Recovery**
```bash
# Backup current corrupted schema
pg_dump -U username -n tenant_123 database_name > corrupted_schema_backup.sql

# Drop corrupted schema
psql -U username -d database_name -c "DROP SCHEMA IF EXISTS tenant_123 CASCADE;"

# Restore from backup
psql -U username -d database_name < backups/tenant_123_latest.sql

# Or recreate and migrate
psql -U username -d database_name -c "CREATE SCHEMA tenant_123;"
php artisan migrate --path=database/migrations --database=tenant_123
```

**Step 3: Data Recovery**
```sql
-- Restore tenant data from backup
INSERT INTO tenant_123.users 
SELECT * FROM backup_tenant_123.users;

INSERT INTO tenant_123.courses 
SELECT * FROM backup_tenant_123.courses;

-- Continue for all tables
```

### Scenario 3: Application Server Failure

#### Symptoms
- Web server unresponsive
- 500 errors across all tenants
- Server hardware failure

#### Recovery Procedure

**Step 1: Failover to Backup Server**
```bash
# Update DNS to point to backup server
# Or use load balancer to redirect traffic

# On backup server, ensure latest code is deployed
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Step 2: Database Connection Update**
```bash
# Update .env on backup server
DB_HOST=backup_database_host
DB_DATABASE=alumate_backup

# Test database connectivity
php artisan tinker
>>> DB::connection()->getPdo();
```

**Step 3: Verify Tenant Operations**
```php
// Test tenant context switching
use App\Services\TenantContextService;

$tenantContext = app(TenantContextService::class);
$tenant = Tenant::first();
$tenantContext->setCurrentTenant($tenant);

// Verify data access
$users = User::all();
echo "Tenant {$tenant->id} has {$users->count()} users\n";
```

### Scenario 4: Data Center Outage

#### Symptoms
- Complete loss of primary infrastructure
- Network connectivity issues
- Extended downtime expected

#### Recovery Procedure

**Step 1: Activate DR Site**
```bash
# Switch to disaster recovery data center
# Update DNS records to point to DR site
# Activate standby database servers
```

**Step 2: Database Synchronization**
```bash
# Restore from latest backup at DR site
psql -U username -d alumate_dr < backups/latest_full_backup.sql

# Apply any missing transactions
psql -U username -d alumate_dr < wal_logs/recent_transactions.sql
```

**Step 3: Application Deployment**
```bash
# Deploy application to DR servers
git clone https://github.com/company/alumate.git
cd alumate
composer install --no-dev
cp .env.dr .env
php artisan key:generate
php artisan config:cache
```

## Backup and Recovery Procedures

### Automated Backup Strategy

#### Daily Full Backups
```bash
#!/bin/bash
# daily_backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/daily"

# Full database backup
pg_dump -U postgres -h localhost alumate > "$BACKUP_DIR/full_backup_$DATE.sql"

# Individual tenant schema backups
psql -U postgres -d alumate -t -c "SELECT nspname FROM pg_namespace WHERE nspname LIKE 'tenant_%'" | while read schema; do
    if [ ! -z "$schema" ]; then
        pg_dump -U postgres -n "$schema" alumate > "$BACKUP_DIR/tenant_${schema}_$DATE.sql"
    fi
done

# Compress backups
gzip "$BACKUP_DIR"/*.sql

# Clean old backups (keep 30 days)
find "$BACKUP_DIR" -name "*.gz" -mtime +30 -delete
```

#### Continuous WAL Archiving
```bash
# postgresql.conf
wal_level = replica
archive_mode = on
archive_command = 'cp %p /backups/wal/%f'

# Recovery script
#!/bin/bash
# point_in_time_recovery.sh

TARGET_TIME="$1"
BACKUP_FILE="$2"

# Restore base backup
psql -U postgres -d alumate < "$BACKUP_FILE"

# Create recovery.conf
cat > recovery.conf << EOF
restore_command = 'cp /backups/wal/%f %p'
recovery_target_time = '$TARGET_TIME'
EOF

# Start recovery
pg_ctl start -D /var/lib/postgresql/data
```

### Backup Verification

```php
<?php
// verify_backup.php

require_once 'vendor/autoload.php';

function verifyBackup($backupFile) {
    // Create temporary database
    $tempDb = 'alumate_backup_test_' . time();
    
    exec("createdb -U postgres $tempDb");
    
    // Restore backup
    exec("psql -U postgres -d $tempDb < $backupFile", $output, $returnCode);
    
    if ($returnCode !== 0) {
        echo "Backup verification failed: Unable to restore\n";
        return false;
    }
    
    // Verify data integrity
    $pdo = new PDO("pgsql:host=localhost;dbname=$tempDb", 'postgres', 'password');
    
    // Check tenant schemas
    $stmt = $pdo->query("SELECT nspname FROM pg_namespace WHERE nspname LIKE 'tenant_%'");
    $schemas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($schemas) . " tenant schemas\n";
    
    // Verify each schema has required tables
    foreach ($schemas as $schema) {
        $stmt = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = '$schema'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredTables = ['users', 'courses', 'graduates', 'jobs', 'leads'];
        $missingTables = array_diff($requiredTables, $tables);
        
        if (!empty($missingTables)) {
            echo "Schema $schema missing tables: " . implode(', ', $missingTables) . "\n";
            return false;
        }
    }
    
    // Cleanup
    exec("dropdb -U postgres $tempDb");
    
    echo "Backup verification successful\n";
    return true;
}

// Verify latest backup
$latestBackup = glob('/backups/daily/full_backup_*.sql.gz')[0];
if ($latestBackup) {
    // Decompress for verification
    exec("gunzip -c $latestBackup > /tmp/backup_verify.sql");
    verifyBackup('/tmp/backup_verify.sql');
    unlink('/tmp/backup_verify.sql');
}
```

## Monitoring and Alerting

### Health Check Endpoints

```php
// routes/web.php
Route::get('/health/database', function () {
    try {
        DB::connection()->getPdo();
        return response()->json(['status' => 'healthy', 'timestamp' => now()]);
    } catch (Exception $e) {
        return response()->json(['status' => 'unhealthy', 'error' => $e->getMessage()], 500);
    }
});

Route::get('/health/tenants', function () {
    $tenantContext = app(TenantContextService::class);
    $healthyTenants = 0;
    $unhealthyTenants = [];
    
    foreach (Tenant::active()->get() as $tenant) {
        try {
            $tenantContext->setCurrentTenant($tenant);
            User::count(); // Test query
            $healthyTenants++;
        } catch (Exception $e) {
            $unhealthyTenants[] = [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage()
            ];
        }
    }
    
    return response()->json([
        'healthy_tenants' => $healthyTenants,
        'unhealthy_tenants' => $unhealthyTenants,
        'timestamp' => now()
    ]);
});
```

### Automated Monitoring Script

```bash
#!/bin/bash
# monitor_system.sh

SLACK_WEBHOOK="https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK"
EMAIL_ALERT="admin@company.com"

# Check database connectivity
if ! psql -U postgres -d alumate -c "SELECT 1" > /dev/null 2>&1; then
    MESSAGE="ALERT: Database connection failed at $(date)"
    curl -X POST -H 'Content-type: application/json' \
        --data "{\"text\":\"$MESSAGE\"}" \
        "$SLACK_WEBHOOK"
    echo "$MESSAGE" | mail -s "Database Alert" "$EMAIL_ALERT"
fi

# Check tenant schemas
SCHEMA_COUNT=$(psql -U postgres -d alumate -t -c "SELECT COUNT(*) FROM pg_namespace WHERE nspname LIKE 'tenant_%'")
EXPECTED_SCHEMAS=$(psql -U postgres -d alumate -t -c "SELECT COUNT(*) FROM tenants WHERE status = 'active'")

if [ "$SCHEMA_COUNT" -ne "$EXPECTED_SCHEMAS" ]; then
    MESSAGE="ALERT: Schema count mismatch. Expected: $EXPECTED_SCHEMAS, Found: $SCHEMA_COUNT"
    curl -X POST -H 'Content-type: application/json' \
        --data "{\"text\":\"$MESSAGE\"}" \
        "$SLACK_WEBHOOK"
fi

# Check application health
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health/database)
if [ "$HTTP_STATUS" -ne "200" ]; then
    MESSAGE="ALERT: Application health check failed with status $HTTP_STATUS"
    curl -X POST -H 'Content-type: application/json' \
        --data "{\"text\":\"$MESSAGE\"}" \
        "$SLACK_WEBHOOK"
fi
```

## Recovery Testing

### Monthly DR Drills

```bash
#!/bin/bash
# dr_drill.sh

echo "Starting Disaster Recovery Drill - $(date)"

# 1. Test backup restoration
echo "Testing backup restoration..."
createdb -U postgres alumate_dr_test
psql -U postgres -d alumate_dr_test < /backups/daily/latest_backup.sql

# 2. Test application deployment
echo "Testing application deployment..."
git clone https://github.com/company/alumate.git /tmp/alumate_dr_test
cd /tmp/alumate_dr_test
composer install --no-dev

# 3. Test tenant operations
echo "Testing tenant operations..."
php artisan tinker --execute="
    use App\Models\Tenant;
    use App\Services\TenantContextService;
    
    \$tenantContext = app(TenantContextService::class);
    \$tenant = Tenant::first();
    \$tenantContext->setCurrentTenant(\$tenant);
    
    echo 'Tenant context test: ' . (\$tenantContext->getCurrentTenant() ? 'PASS' : 'FAIL') . PHP_EOL;
"

# 4. Cleanup
echo "Cleaning up..."
dropdb -U postgres alumate_dr_test
rm -rf /tmp/alumate_dr_test

echo "DR Drill completed - $(date)"
```

## Communication Plan

### Incident Response Team

1. **Incident Commander**: Technical Lead
2. **Database Administrator**: Database recovery
3. **DevOps Engineer**: Infrastructure recovery
4. **Application Developer**: Code-related issues
5. **Communications Lead**: Stakeholder updates

### Communication Templates

#### Initial Incident Notification
```
Subject: [INCIDENT] System Outage - Schema-Based Tenancy

We are currently experiencing issues with our schema-based tenancy system.

Impact: [Describe impact]
Estimated Resolution: [Time estimate]
Next Update: [Time for next update]

We are actively working to resolve this issue and will provide updates every 30 minutes.

Incident Commander: [Name]
Incident ID: [ID]
```

#### Resolution Notification
```
Subject: [RESOLVED] System Outage - Schema-Based Tenancy

The incident has been resolved. All systems are now operational.

Root Cause: [Brief description]
Resolution: [What was done]
Duration: [Total downtime]

A full post-incident review will be conducted and shared within 48 hours.

Thank you for your patience.
```

## Post-Incident Procedures

### 1. Post-Incident Review
- Document timeline of events
- Identify root cause
- Review response effectiveness
- Identify improvement opportunities

### 2. System Hardening
- Implement additional monitoring
- Update backup procedures
- Enhance alerting systems
- Update documentation

### 3. Training Updates
- Update team training materials
- Conduct additional DR drills
- Review and update procedures
- Share lessons learned

---

**Emergency Contacts:**
- On-Call Engineer: [Phone]
- Database Administrator: [Phone]
- Infrastructure Team: [Phone]
- Management Escalation: [Phone]

**Last Updated:** [Date]
**Next Review:** [Date]