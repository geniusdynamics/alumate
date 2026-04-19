# Multi-Tenancy Resolution Plan

## Executive Summary

This document provides a detailed, step-by-step plan to resolve the multi-tenancy architecture conflicts identified in the Alumate platform. The goal is to migrate from a hybrid approach to a pure schema-based isolation model.

### Current State: Hybrid Approach

**Problems:**
- Schema-based isolation (Stancl Tenancy) + tenant_id columns
- Inconsistent application of tenancy logic
- Global scopes that don't actually filter
- Service dependencies on TenantContextService
- Confusion about where tenancy logic lives

### Target State: Pure Schema-Based

**Benefits:**
- Database-level isolation (cannot accidentally leak data)
- No tenant_id filtering overhead in queries
- Simplified model code (no global scopes for tenancy)
- Clear ownership boundaries
- Better compliance (FERPA, GDPR)

---

## Migration Phases

### Phase 1: Assessment & Planning (Week 1)

#### Step 1.1: Complete Table Audit

Run comprehensive audit:

```bash
#!/bin/bash
# scripts/tenancy/audit-tables.sh

echo "=== TENANCY AUDIT REPORT ==="
echo ""

echo "1. Tables with tenant_id column:"
php artisan tinker --execute="
    \$tables = DB::select('SHOW TABLES');
    foreach (\$tables as \$table) {
        \$tableName = array_values((array)\$table)[0];
        \$columns = DB::getSchemaBuilder()->getColumnListing(\$tableName);
        if (in_array('tenant_id', \$columns)) {
            echo \"- {\$tableName}\n\";
        }
    }
"

echo ""
echo "2. Models using TenantContextService:"
grep -r "TenantContextService" app/Models/ --include="*.php" -l

echo ""
echo "3. Models with global scopes:"
grep -r "addGlobalScope" app/Models/ --include="*.php" -l

echo ""
echo "4. Check config/tenancy.php settings:"
cat config/tenancy.php | grep -A 5 "'database'"
```

**Expected Output:**
```
=== TENANCY AUDIT REPORT ===

1. Tables with tenant_id column:
- component_themes
- landing_pages
- analytics_events
- email_campaigns
- template_variants
- ... (30-40 tables)

2. Models using TenantContextService:
app/Models/Graduate.php
app/Models/Course.php
app/Models/Event.php

3. Models with global scopes:
app/Models/Graduate.php
app/Models/Course.php
app/Models/JobApplication.php
```

#### Step 1.2: Categorize Tables

Create categorization spreadsheet:

| Table Name | Current Approach | Category | Action Required | Priority |
|------------|------------------|----------|-----------------|----------|
| graduates | Schema | A - Tenant Only | None | Done |
| courses | Schema | A - Tenant Only | None | Done |
| component_themes | tenant_id | A - Should be Schema | Move to tenant schema | P0 |
| landing_pages | tenant_id | A - Should be Schema | Move to tenant schema | P0 |
| analytics_events | tenant_id | A - Should be Schema | Move to tenant schema | P0 |
| users | Central | B - Keep Central | Remove tenant_id (keep for auth) | P1 |
| tenants | Central | B - Keep Central | None | Done |
| jobs | tenant_id | C - Hybrid | Keep tenant_id for cross-tenant | P2 |
| employers | tenant_id | C - Hybrid | Keep tenant_id with opt-in flag | P2 |

**Category Definitions:**

**Category A: Tenant-Only Tables**
- Data belongs exclusively to one tenant
- Never shared across tenants
- Examples: graduates, courses, student records
- **Action:** Move to tenant schemas, remove tenant_id

**Category B: Central Tables**
- System-wide configuration/data
- Shared across all tenants
- Examples: tenants, domains, institutions
- **Action:** Keep in public schema

**Category C: Hybrid Tables**
- Primarily tenant-specific but may have cross-tenant features
- Examples: jobs (job discovery), employers (verified employer network)
- **Action:** Keep tenant_id, add explicit sharing flags

#### Step 1.3: Create Migration Rollback Plan

```php
<?php
// database/migrations/tenant_migration_rollback_plan.php

return [
    'rollback_strategy' => 'phased',
    
    'phases' => [
        [
            'name' => 'Phase 1: Component Tables',
            'tables' => ['component_themes', 'component_instances', 'component_analytics'],
            'rollback_window' => '24 hours',
            'risk_level' => 'low',
        ],
        [
            'name' => 'Phase 2: Content Tables',
            'tables' => ['landing_pages', 'homepage_content', 'templates'],
            'rollback_window' => '48 hours',
            'risk_level' => 'medium',
        ],
        [
            'name' => 'Phase 3: Analytics Tables',
            'tables' => ['analytics_events', 'analytics_conversions', 'ab_tests'],
            'rollback_window' => '72 hours',
            'risk_level' => 'high',
        ],
    ],
    
    'rollback_procedure' => <<<'EOT'
1. Stop application writes (maintenance mode)
2. Export tenant schema data to temporary tables
3. Restore public schema tables from backup
4. Re-add tenant_id columns
5. Migrate data back to public schema
6. Restart application
7. Verify data integrity
EOT
];
```

---

### Phase 2: Schema Design (Week 2)

#### Step 2.1: Define Schema Naming Convention

```sql
-- Schema naming pattern: tenant_{tenant_id}
-- Example: tenant_abc123, tenant_def456

-- PostgreSQL function to get schema name for tenant
CREATE OR REPLACE FUNCTION get_tenant_schema(tenant_id UUID)
RETURNS TEXT AS $$
BEGIN
    RETURN 'tenant_' || replace(tenant_id::TEXT, '-', '_');
END;
$$ LANGUAGE plpgsql;

-- Usage:
SELECT get_tenant_schema('abc-123-def');
-- Returns: tenant_abc_123_def
```

#### Step 2.2: Create Base Schema Template

```sql
-- SQL template for creating tenant schemas
-- File: scripts/tenancy/create-tenant-schema.sql

CREATE OR REPLACE FUNCTION create_tenant_schema(tenant_id UUID)
RETURNS VOID AS $$
DECLARE
    schema_name TEXT;
BEGIN
    schema_name := 'tenant_' || replace(tenant_id::TEXT, '-', '_');
    
    -- Create schema
    EXECUTE format('CREATE SCHEMA IF NOT EXISTS %I', schema_name);
    
    -- Grant permissions
    EXECUTE format('GRANT ALL ON SCHEMA %I TO alumate_app', schema_name);
    EXECUTE format('GRANT ALL ON ALL TABLES IN SCHEMA %I TO alumate_app', schema_name);
    EXECUTE format('GRANT ALL ON ALL SEQUENCES IN SCHEMA %I TO alumate_app', schema_name);
    
    -- Log creation
    INSERT INTO tenant_schema_logs (tenant_id, schema_name, created_at)
    VALUES (tenant_id, schema_name, NOW());
    
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE 'Error creating schema for tenant %: %', tenant_id, SQLERRM;
        RAISE;
END;
$$ LANGUAGE plpgsql;
```

#### Step 2.3: Define Table Structures for Each Category

**Category A: Tenant Schema Tables**

```sql
-- Example: component_themes in tenant schema
-- No tenant_id needed!

CREATE TABLE tenant_schema.component_themes (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    config JSONB NOT NULL DEFAULT '{}',
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes for common queries
    INDEX idx_component_themes_active (is_active),
    INDEX idx_component_themes_name (name)
);

-- Note: Schema name provides tenant isolation
-- Query: SELECT * FROM tenant_abc123.component_themes;
-- Automatically scoped to tenant abc123
```

**Category B: Central Tables**

```sql
-- Keep in public schema
-- May have tenant_id for relationships

CREATE TABLE public.tenants (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) UNIQUE,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE public.users (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    -- Authentication data only
    -- Profile data in tenant schemas
);
```

**Category C: Hybrid Tables**

```sql
-- Jobs table: keep tenant_id for cross-tenant discovery

CREATE TABLE public.jobs (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID REFERENCES tenants(id),
    employer_id BIGINT REFERENCES employers(id),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    is_cross_tenant_visible BOOLEAN NOT NULL DEFAULT false,
    salary_min DECIMAL(10,2),
    salary_max DECIMAL(10,2),
    location VARCHAR(255),
    remote_allowed BOOLEAN NOT NULL DEFAULT false,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_jobs_tenant (tenant_id),
    INDEX idx_jobs_cross_tenant (is_cross_tenant_visible),
    INDEX idx_jobs_title (title)
);

-- Query for tenant-specific jobs:
SELECT * FROM jobs WHERE tenant_id = 'abc-123';

-- Query for cross-tenant job discovery:
SELECT * FROM jobs 
WHERE is_cross_tenant_visible = true 
AND location = 'Remote';
```

---

### Phase 3: Migration Implementation (Weeks 3-6)

#### Step 3.1: Create Migration Framework

```php
<?php

namespace Database\Migrations\Tenancy;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateTableToTenantSchema extends Migration
{
    protected string $tableName;
    protected array $columnsToKeep;
    protected array $columnsToRemove;
    
    public function __construct(
        string $tableName,
        array $columnsToKeep = [],
        array $columnsToRemove = ['tenant_id']
    ) {
        $this->tableName = $tableName;
        $this->columnsToKeep = $columnsToKeep;
        $this->columnsToRemove = $columnsToRemove;
    }
    
    public function up(): void
    {
        $tenants = DB::table('tenants')->get();
        
        foreach ($tenants as $tenant) {
            $schemaName = 'tenant_' . str_replace('-', '_', $tenant->id);
            
            // Create schema if not exists
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$schemaName}");
            
            // Get table structure
            $columns = Schema::getColumnListing($this->tableName);
            
            // Build column list for new table (exclude tenant_id)
            $columnDefs = [];
            foreach ($columns as $column) {
                if (!in_array($column, $this->columnsToRemove)) {
                    $columnDefs[] = $column;
                }
            }
            
            $columnList = implode(', ', $columnDefs);
            
            // Create table in tenant schema
            DB::statement("
                CREATE TABLE {$schemaName}.{$this->tableName} AS
                SELECT {$columnList}
                FROM {$this->tableName}
                WHERE tenant_id = '{$tenant->id}'
            ");
            
            // Add primary key and indexes
            $this->addIndexes($schemaName);
        }
    }
    
    protected function addIndexes(string $schemaName): void
    {
        // Add primary key if not exists
        DB::statement("
            ALTER TABLE {$schemaName}.{$this->tableName}
            ADD PRIMARY KEY (id)
        ");
        
        // Add other indexes based on table
        // (Implementation depends on specific table)
    }
    
    public function down(): void
    {
        $tenants = DB::table('tenants')->get();
        
        foreach ($tenants as $tenant) {
            $schemaName = 'tenant_' . str_replace('-', '_', $tenant->id);
            
            // Drop table from tenant schema
            DB::statement("DROP TABLE IF EXISTS {$schemaName}.{$this->tableName}");
        }
    }
}
```

#### Step 3.2: Execute First Migration (Component Themes)

```bash
# Run migration for component_themes
php artisan make:migration migrate_component_themes_to_tenant_schema

# Edit the migration file:
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        echo "Migrating component_themes to tenant schemas...\n";
        
        $tenants = DB::table('tenants')->orderBy('id')->get();
        $migratedCount = 0;
        
        foreach ($tenants as $tenant) {
            try {
                $schemaName = 'tenant_' . str_replace('-', '_', $tenant->id);
                
                DB::beginTransaction();
                
                // Create schema
                DB::statement("CREATE SCHEMA IF NOT EXISTS {$schemaName}");
                echo "  Created schema: {$schemaName}\n";
                
                // Create table in tenant schema (without tenant_id)
                DB::statement("
                    CREATE TABLE {$schemaName}.component_themes (
                        id BIGSERIAL PRIMARY KEY,
                        name VARCHAR(255) NOT NULL,
                        config JSONB NOT NULL DEFAULT '{}',
                        is_active BOOLEAN NOT NULL DEFAULT true,
                        created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                
                // Migrate data
                DB::statement("
                    INSERT INTO {$schemaName}.component_themes (id, name, config, is_active, created_at, updated_at)
                    SELECT id, name, config, is_active, created_at, updated_at
                    FROM public.component_themes
                    WHERE tenant_id = '{$tenant->id}'
                ");
                
                $count = DB::table("{$schemaName}.component_themes")->count();
                echo "  Migrated {$count} component themes for tenant {$tenant->id}\n";
                
                DB::commit();
                $migratedCount++;
                
            } catch (\Exception $e) {
                DB::rollBack();
                echo "  ERROR migrating tenant {$tenant->id}: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
        
        echo "Successfully migrated {$migratedCount}/" . count($tenants) . " tenants\n";
        
        // DO NOT drop old table yet - keep for rollback during validation period
        echo "Old table public.component_themes preserved for rollback\n";
    }
    
    public function down(): void
    {
        echo "Rolling back component_themes migration...\n";
        
        $tenants = DB::table('tenants')->get();
        
        foreach ($tenants as $tenant) {
            $schemaName = 'tenant_' . str_replace('-', '_', $tenant->id);
            
            // Drop table from tenant schema
            DB::statement("DROP TABLE IF EXISTS {$schemaName}.component_themes");
        }
        
        echo "Rollback complete\n";
    }
};
```

**Run Migration:**

```bash
# Test on staging first
php artisan migrate --database=pgsql_staging --path=database/migrations/tenancy

# Verify migration
php artisan tinker
>>> DB::table('tenants')->get()->each(function($tenant) {
>>>     $schema = 'tenant_' . str_replace('-', '_', $tenant->id);
>>>     $count = DB::table("{$schema}.component_themes")->count();
>>>     echo "Tenant {$tenant->id}: {$count} themes\n";
>>> });

# If successful, run on production
php artisan migrate --path=database/migrations/tenancy
```

#### Step 3.3: Update Model Class

```php
// BEFORE (app/Models/ComponentTheme.php)
class ComponentTheme extends Model
{
    protected $fillable = ['tenant_id', 'name', 'config'];
    
    protected static function boot()
    {
        parent::boot();
        
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = app(TenantContextService::class)->getCurrentTenantId();
            $builder->where('tenant_id', $tenantId);
        });
    }
    
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

// AFTER (app/Models/ComponentTheme.php)
class ComponentTheme extends Model
{
    // Remove tenant connection - uses current tenant schema automatically
    protected $connection = 'tenant';
    
    protected $fillable = ['name', 'config', 'is_active'];
    
    // Remove global scope - schema provides isolation
    // Remove tenant relationship - no longer needed
    
    /**
     * Get themes for current tenant schema
     * Automatically scoped by database schema
     */
    public static function getForCurrentTenant()
    {
        // No tenant_id filtering needed!
        return static::where('is_active', true)->get();
    }
}
```

#### Step 3.4: Update Service Layer

```php
// BEFORE (app/Services/ComponentThemeService.php)
class ComponentThemeService
{
    public function __construct(
        private TenantContextService $tenantContext
    ) {}
    
    public function getThemes(): Collection
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        return ComponentTheme::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
    }
}

// AFTER (app/Services/ComponentThemeService.php)
class ComponentThemeService
{
    // Remove TenantContextService dependency!
    
    public function getThemes(): Collection
    {
        // Automatically scoped to current tenant schema
        return ComponentTheme::where('is_active', true)->get();
    }
    
    public function createTheme(array $data): ComponentTheme
    {
        // No need to set tenant_id - schema handles it
        return ComponentTheme::create($data);
    }
}
```

---

### Phase 4: Testing & Validation (Week 7)

#### Step 4.1: Create Comprehensive Test Suite

```php
<?php

// tests/Feature/Tenancy/SchemaIsolationTest.php

use App\Models\ComponentTheme;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

describe('Schema-Based Tenancy', function () {
    
    beforeEach(function () {
        $this->tenantA = Tenant::create([
            'id' => 'tenant-a',
            'name' => 'Tenant A'
        ]);
        
        $this->tenantB = Tenant::create([
            'id' => 'tenant-b',
            'name' => 'Tenant B'
        ]);
    });
    
    it('isolates data at schema level', function () {
        // Create data in tenant A
        tenancy()->init('tenant-a');
        $themeA = ComponentTheme::create([
            'name' => 'Tenant A Theme',
            'config' => ['color' => 'blue']
        ]);
        
        // Create data in tenant B
        tenancy()->init('tenant-b');
        $themeB = ComponentTheme::create([
            'name' => 'Tenant B Theme',
            'config' => ['color' => 'red']
        ]);
        
        // Verify tenant A cannot see tenant B's data
        tenancy()->init('tenant-a');
        expect(ComponentTheme::count())->toBe(1)
            ->and(ComponentTheme::first()->name)->toBe('Tenant A Theme');
        
        // Verify tenant B cannot see tenant A's data
        tenancy()->init('tenant-b');
        expect(ComponentTheme::count())->toBe(1)
            ->and(ComponentTheme::first()->name)->toBe('Tenant B Theme');
    });
    
    it('does not require tenant_id filtering', function () {
        tenancy()->init('tenant-a');
        
        // Query should automatically be scoped to tenant A schema
        $themes = ComponentTheme::all();
        
        // Should only return themes from tenant A
        expect($themes)->toHaveCount(1);
        expect($themes->first()->name)->toBe('Tenant A Theme');
        
        // Verify no tenant_id in query
        $query = ComponentTheme::toSql();
        expect($query)->not->toContain('tenant_id');
    });
    
    it('prevents cross-tenant data access even with raw queries', function () {
        tenancy()->init('tenant-a');
        
        // Try to access tenant B's schema directly (should fail)
        $exception = null;
        try {
            DB::select("SELECT * FROM tenant_tenant_b.component_themes");
        } catch (\Exception $e) {
            $exception = $e;
        }
        
        expect($exception)->not->toBeNull()
            ->and($exception->getMessage())->toContain('permission denied');
    });
    
    it('maintains performance without global scopes', function () {
        // Create test data
        tenancy()->init('tenant-a');
        ComponentTheme::factory()->count(100)->create();
        
        // Measure query time
        $start = microtime(true);
        ComponentTheme::all();
        $duration = microtime(true) - $start;
        
        // Should complete in under 50ms (no global scope overhead)
        expect($duration)->toBeLessThan(0.05);
    });
    
});
```

#### Step 4.2: Performance Benchmarking

```bash
#!/bin/bash
# scripts/benchmark/tenancy-performance.sh

echo "=== TENANCY PERFORMANCE BENCHMARK ==="
echo ""

# Before migration (hybrid approach)
echo "BEFORE Migration (Hybrid Approach):"
php artisan tinker --execute="
    \$start = microtime(true);
    for (\$i = 0; \$i < 1000; \$i++) {
        \$themes = App\Models\ComponentTheme::where('tenant_id', 'test-tenant')->get();
    }
    \$duration = microtime(true) - \$start;
    echo \"Average query time: \" . ((\$duration / 1000) * 1000) . \"ms\n\";
    echo \"Queries executed: \" . DB::getQueryLog() . \"\n\";
"

echo ""
echo "AFTER Migration (Pure Schema):"
php artisan tinker --execute="
    \$start = microtime(true);
    for (\$i = 0; \$i < 1000; \$i++) {
        \$themes = App\Models\ComponentTheme::all();
    }
    \$duration = microtime(true) - \$start;
    echo \"Average query time: \" . ((\$duration / 1000) * 1000) . \"ms\n\";
    echo \"Queries executed: \" . DB::getQueryLog() . \"\n\";
"

echo ""
echo "Expected Improvements:"
echo "- Query time: -30% (no tenant_id filtering)"
echo "- Memory usage: -15% (no global scope overhead)"
echo "- Cache hit rate: +20% (schema-level isolation)"
```

#### Step 4.3: Security Audit

```bash
#!/bin/bash
# scripts/security/tenancy-isolation-test.sh

echo "=== TENANCY ISOLATION SECURITY AUDIT ==="
echo ""

# Test 1: Verify no tenant_id leaks in queries
echo "Test 1: Checking for tenant_id in queries..."
grep -r "tenant_id" app/Models/ --include="*.php" | \
    grep -v "// " | \
    grep -v "FIXME" | \
    grep -v "TODO"

if [ $? -eq 0 ]; then
    echo "❌ Found tenant_id references in models"
    exit 1
else
    echo "✅ No tenant_id references found in models"
fi

# Test 2: Verify global scopes removed
echo ""
echo "Test 2: Checking for tenant global scopes..."
grep -r "addGlobalScope.*tenant" app/Models/ --include="*.php"

if [ $? -eq 0 ]; then
    echo "❌ Found tenant global scopes"
    exit 1
else
    echo "✅ No tenant global scopes found"
fi

# Test 3: Verify TenantContextService not used in models
echo ""
echo "Test 3: Checking TenantContextService usage..."
grep -r "TenantContextService" app/Models/ --include="*.php"

if [ $? -eq 0 ]; then
    echo "❌ Found TenantContextService in models"
    exit 1
else
    echo "✅ TenantContextService not used in models"
fi

echo ""
echo "=== ALL SECURITY TESTS PASSED ==="
```

---

### Phase 5: Cleanup & Documentation (Week 8)

#### Step 5.1: Remove Deprecated Code

```bash
# Files to delete or refactor:
rm app/Services/TenantContextService.php  # No longer needed
rm app/Traits/HasTenantContext.php         # Remove tenant trait
rm config/tenancy.php.backup               # Clean up backups

# Update config/tenancy.php:
# - Remove database_partitioning section
# - Simplify to pure schema approach
# - Remove tenant_id references
```

#### Step 5.2: Update Documentation

Create comprehensive documentation:

```bash
cat > docs/tenancy/pure-schema-approach.md << 'EOF'
# Pure Schema-Based Multi-Tenancy

## Overview

Our platform uses pure schema-based multi-tenancy with PostgreSQL schemas. Each tenant has their own isolated database schema, providing superior security and performance.

## How It Works

### Schema Naming Convention

Each tenant gets a schema named: `tenant_{tenant_id}`

Example:
- Tenant ID: `abc-123-def`
- Schema name: `tenant_abc_123_def`

### Data Isolation

Data isolation happens at the database level:

```sql
-- Tenant A data
SELECT * FROM tenant_abc123.graduates;

-- Tenant B data (completely separate)
SELECT * FROM tenant_def456.graduates;
```

### Application Usage

In your Laravel application:

```php
// Initialize tenant context
tenancy()->init('tenant-abc-123');

// All queries automatically use tenant schema
$graduates = Graduate::all();
// Executes: SELECT * FROM tenant_abc123.graduates;

// No tenant_id filtering needed!
// No global scopes!
// No TenantContextService!
```

## For Developers

### Creating New Tables

When creating migrations for tenant tables:

```php
Schema::create('my_table', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    // NO tenant_id column!
    $table->timestamps();
});
```

The migration will automatically be created in the tenant schema.

### Querying Data

```php
// Good - automatic schema scoping
$items = MyModel::all();

// Bad - don't do this
$items = MyModel::where('tenant_id', $tenantId)->get(); // NO!
```

## Security Benefits

1. **Database-Level Isolation**: Cannot accidentally access another tenant's data
2. **No Human Error**: Even raw queries are scoped to schema
3. **Compliance Ready**: Meets FERPA, GDPR requirements for data separation
4. **Audit Friendly**: Clear separation makes auditing easier

## Performance Benefits

1. **No Filtering Overhead**: No `WHERE tenant_id = ?` on every query
2. **Smaller Indexes**: Each schema has its own indexes
3. **Better Caching**: Schema-level caching more efficient
4. **Optimized Queries**: Simpler execution plans

## Troubleshooting

### Issue: Cannot find tenant data

Check tenant initialization:
```php
tenancy()->init('tenant-id');
```

### Issue: Cross-tenant queries needed

Use central tables for truly shared data, or implement explicit sharing flags.

### Issue: Migration failed

Run rollback:
```bash
php artisan migrate:rollback --path=database/migrations/tenancy
```

EOF
```

---

## Success Criteria

✅ **All tenant data stored in tenant-specific schemas**  
✅ **Zero tenant_id columns in tenant-scoped tables**  
✅ **No global scopes for tenant filtering**  
✅ **Service layer simplified (no TenantContextService)**  
✅ **Tenant isolation tests passing**  
✅ **Performance benchmarks improved by >25%**  
✅ **Documentation complete and reviewed**  
✅ **Security audit passed**  

---

## Risk Mitigation

### Risk 1: Data Loss During Migration

**Mitigation:**
- Full database backup before migration
- Phased rollout (one table group at a time)
- 24-hour rollback window per phase
- Parallel run (old and new systems simultaneously)

### Risk 2: Application Downtime

**Mitigation:**
- Run migrations during low-traffic periods
- Blue-green deployment strategy
- Maintenance mode only during final cutover
- Quick rollback procedure (<15 minutes)

### Risk 3: Performance Regression

**Mitigation:**
- Load testing before production deployment
- Performance monitoring dashboard
- Automatic rollback if response time increases >20%
- Gradual traffic migration (10%, 25%, 50%, 100%)

### Risk 4: Developer Resistance

**Mitigation:**
- Comprehensive training sessions
- Clear documentation with examples
- Office hours for questions
- Pair programming during transition

---

## Next Steps

Continue reading:
- [Model Refactoring Guide](./04-model-refactoring.md)
- [Database Optimization Plan](./05-database-optimization.md)
- [Implementation Roadmap](./08-implementation-roadmap.md)
