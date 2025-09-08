# Schema-Based Tenancy Rollback Procedures

## Overview
This document outlines the complete rollback procedures for reverting from schema-based tenancy back to the original tenant_id column-based system in case of critical issues.

## Pre-Rollback Checklist

### 1. Assess the Situation
- [ ] Document the specific issues encountered
- [ ] Determine if the issue is critical enough to warrant a full rollback
- [ ] Check if a hotfix or partial rollback could resolve the issue
- [ ] Notify all stakeholders about the rollback decision

### 2. Backup Current State
- [ ] Create a full database backup of the current schema-based state
- [ ] Export all tenant schemas and their data
- [ ] Backup application code and configuration files
- [ ] Document current tenant-to-schema mappings

### 3. Prepare Rollback Environment
- [ ] Ensure the original backup (pre-migration) is accessible
- [ ] Verify rollback scripts are tested and ready
- [ ] Prepare maintenance mode notifications
- [ ] Coordinate with the development team

## Rollback Procedures

### Phase 1: Application Rollback

#### 1.1 Enable Maintenance Mode
```bash
php artisan down --message="System maintenance in progress" --retry=60
```

#### 1.2 Revert Application Code
```bash
# Checkout to the last stable commit before schema-based migration
git checkout <pre-migration-commit-hash>

# Or revert specific commits
git revert <schema-migration-commit-1> <schema-migration-commit-2>

# Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### 1.3 Restore Original Service Files
```bash
# Restore original service files from backup
cp backup/services/LeadScoringService.php app/Services/
cp backup/services/LandingPageService.php app/Services/
cp backup/services/AnalyticsService.php app/Services/

# Remove schema-based services
rm app/Services/TenantContextService.php
rm app/Services/TenantSchemaService.php
rm app/Services/BaseService.php
```

#### 1.4 Restore Original Models
```bash
# Restore models with tenant_id columns and global scopes
cp backup/models/* app/Models/
```

### Phase 2: Database Rollback

#### 2.1 Data Consolidation Script
Create and run the data consolidation script:

```php
<?php
// rollback_data_consolidation.php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

echo "Starting data consolidation rollback...\n";

// Get all tenants
$tenants = Tenant::all();

// Tables to consolidate
$tables = [
    'users', 'courses', 'graduates', 'jobs', 'leads', 
    'templates', 'landing_pages', 'analytics_snapshots'
];

foreach ($tenants as $tenant) {
    $schemaName = "tenant_{$tenant->id}";
    
    echo "Processing tenant {$tenant->id} (schema: {$schemaName})...\n";
    
    foreach ($tables as $table) {
        try {
            // Check if schema table exists
            $exists = DB::select(
                "SELECT 1 FROM information_schema.tables 
                 WHERE table_schema = ? AND table_name = ?",
                [$schemaName, $table]
            );
            
            if (!empty($exists)) {
                // Copy data back to main schema with tenant_id
                DB::statement(
                    "INSERT INTO public.{$table} 
                     SELECT *, {$tenant->id} as tenant_id 
                     FROM {$schemaName}.{$table}"
                );
                
                echo "  - Consolidated {$table}\n";
            }
        } catch (Exception $e) {
            echo "  - Error consolidating {$table}: " . $e->getMessage() . "\n";
        }
    }
}

echo "Data consolidation completed.\n";
```

#### 2.2 Schema Cleanup
```sql
-- Drop all tenant schemas
DO $$
DECLARE
    schema_name TEXT;
BEGIN
    FOR schema_name IN 
        SELECT nspname FROM pg_namespace 
        WHERE nspname LIKE 'tenant_%'
    LOOP
        EXECUTE 'DROP SCHEMA IF EXISTS ' || quote_ident(schema_name) || ' CASCADE';
        RAISE NOTICE 'Dropped schema: %', schema_name;
    END LOOP;
END $$;
```

#### 2.3 Restore Original Database Structure
```bash
# Run rollback migrations to restore tenant_id columns
php artisan migrate:rollback --step=10

# Or restore from backup
psql -U username -d database_name < backup/pre_migration_backup.sql
```

### Phase 3: Configuration Rollback

#### 3.1 Update Environment Configuration
```bash
# Restore original .env file
cp backup/.env.original .env

# Update configuration cache
php artisan config:cache
```

#### 3.2 Restore Service Providers
```php
// Remove schema-based service providers from config/app.php
// Restore original middleware configuration
```

### Phase 4: Verification and Testing

#### 4.1 Data Integrity Checks
```sql
-- Verify all tenant data is present
SELECT tenant_id, COUNT(*) as record_count 
FROM users 
GROUP BY tenant_id;

SELECT tenant_id, COUNT(*) as record_count 
FROM courses 
GROUP BY tenant_id;

-- Check for data consistency
SELECT 
    t.id as tenant_id,
    t.name as tenant_name,
    COUNT(u.id) as user_count,
    COUNT(c.id) as course_count
FROM tenants t
LEFT JOIN users u ON u.tenant_id = t.id
LEFT JOIN courses c ON c.tenant_id = t.id
GROUP BY t.id, t.name;
```

#### 4.2 Application Testing
```bash
# Run original test suite
php artisan test

# Test tenant isolation
php artisan test tests/Feature/TenantIsolationTest.php

# Test core functionality
php artisan test tests/Feature/
```

#### 4.3 Manual Verification
- [ ] Login to each tenant and verify data accessibility
- [ ] Test lead scoring functionality
- [ ] Verify landing page creation and management
- [ ] Check analytics and reporting features
- [ ] Test user management and permissions

### Phase 5: Post-Rollback Actions

#### 5.1 Disable Maintenance Mode
```bash
php artisan up
```

#### 5.2 Monitor System Health
- [ ] Monitor application logs for errors
- [ ] Check database performance metrics
- [ ] Verify all tenant operations are working
- [ ] Monitor user feedback and support tickets

#### 5.3 Documentation and Communication
- [ ] Document the rollback process and lessons learned
- [ ] Communicate rollback completion to stakeholders
- [ ] Update project documentation
- [ ] Plan for addressing the original migration issues

## Emergency Rollback (Critical Situations)

For critical situations requiring immediate rollback:

### 1. Immediate Database Restore
```bash
# Stop application
php artisan down

# Restore from pre-migration backup
psql -U username -d database_name < backup/pre_migration_full_backup.sql

# Restore application code
git checkout <pre-migration-stable-commit>

# Clear caches and restart
php artisan cache:clear
php artisan config:clear
php artisan up
```

### 2. Data Loss Mitigation
If data was created during the schema-based period:

```bash
# Export new data before rollback
php artisan export:tenant-data --since="migration-date"

# After rollback, import the new data
php artisan import:tenant-data --file="new_data_export.json"
```

## Rollback Testing

### Pre-Production Testing
1. Test rollback procedures on staging environment
2. Verify data integrity after rollback
3. Test application functionality
4. Measure rollback time and identify bottlenecks

### Rollback Simulation
```bash
# Create rollback simulation script
php artisan make:command SimulateRollback

# Test rollback procedures without affecting production
php artisan simulate:rollback --dry-run
```

## Prevention Measures

### 1. Monitoring and Alerts
- Set up monitoring for schema-based operations
- Create alerts for tenant isolation failures
- Monitor database performance metrics
- Track application error rates

### 2. Gradual Migration
- Implement feature flags for schema-based tenancy
- Allow gradual tenant migration
- Maintain dual compatibility during transition

### 3. Automated Testing
- Comprehensive test suite for schema-based operations
- Automated rollback testing
- Performance regression testing

## Contact Information

**Emergency Contacts:**
- Database Administrator: [contact info]
- Lead Developer: [contact info]
- DevOps Engineer: [contact info]
- Project Manager: [contact info]

**Escalation Path:**
1. Development Team Lead
2. Technical Director
3. CTO

---

**Note:** This rollback procedure should be tested thoroughly in a staging environment before any production rollback is attempted. Always ensure you have recent backups before starting any rollback process.