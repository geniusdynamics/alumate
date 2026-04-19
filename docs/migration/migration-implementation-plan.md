# Database Migration Implementation Plan

## Overview

This document provides the detailed implementation plan for migrating Alumate from a hybrid tenancy approach to a pure schema-based multi-tenancy architecture.

## Pre-Migration Checklist

### 1. Environment Preparation
- [ ] Create `db` branch for migration work
- [ ] Set up staging environment identical to production
- [ ] Backup current database
- [ ] Document current tenant count and data volumes
- [ ] Set up monitoring for migration process

### 2. Code Analysis
- [ ] Audit all models using `tenant_id` approach
- [ ] Identify cross-tenant dependencies
- [ ] Document current query patterns
- [ ] List all global scopes in use

## Migration Phases

### Phase 1: Assessment and Preparation (Week 1)

#### Day 1-2: Current State Analysis

**Models to Migrate to Tenant Schemas:**
```php
// Central -> Tenant Schema Migration List
[
    'ComponentTheme',
    'LandingPage', 
    'AnalyticsEvent',
    'TemplatePerformanceDashboard',
    'BrandColor',
    'BrandConfig',
    'BrandFont',
    'Template',
    'TemplateVariant',
    'EmailSequence',
    'SequenceEmail',
    'SequenceEnrollment',
]
```

**Models to Keep Central:**
```php
// Remain in Central Database
[
    'Tenant',
    'Domain', 
    'User', // Authentication
    'Role',
    'Permission',
    'Institution', // Reference data
    'Company', // Reference data
]
```

**Models Requiring Hybrid Approach:**
```php
// Cross-tenant functionality
[
    'Job', // Public job postings
    'Employer', // Cross-tenant employers
    'Lead', // Marketing leads
]
```

#### Day 3-4: Schema Design

**Tenant Schema Structure:**
```sql
-- Schema naming convention: tenant_{tenant_id}
-- Example: tenant_abc123, tenant_def456

-- Core tenant tables (already exist)
CREATE TABLE tenant_abc123.graduates (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(255),
    address TEXT,
    graduation_year INTEGER,
    course_id BIGINT REFERENCES tenant_abc123.courses(id),
    user_id BIGINT, -- Reference to central users table
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- New tenant tables (migrated from central)
CREATE TABLE tenant_abc123.component_themes (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    config JSONB NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE tenant_abc123.landing_pages (
    id BIGSERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content JSONB,
    template_id BIGINT,
    is_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE tenant_abc123.analytics_events (
    id BIGSERIAL PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    event_data JSONB,
    user_id BIGINT,
    session_id VARCHAR(255),
    ip_address INET,
    user_agent TEXT,
    occurred_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP
);
```

#### Day 5: Migration Script Development

**Create Migration Command:**
```php
// app/Console/Commands/MigrateToPureSchema.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateToPureSchema extends Command
{
    protected $signature = 'tenancy:migrate-pure-schema 
                           {--dry-run : Run without making changes}
                           {--tenant= : Migrate specific tenant only}
                           {--batch-size=100 : Records per batch}';
    
    protected $description = 'Migrate from hybrid to pure schema-based tenancy';
    
    protected $migratedTables = [
        'component_themes',
        'landing_pages', 
        'analytics_events',
        'brand_colors',
        'brand_configs',
        'templates',
    ];
    
    public function handle()
    {
        $this->info('Starting migration to pure schema-based tenancy...');
        
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        $tenants = $this->option('tenant') 
            ? [Tenant::find($this->option('tenant'))]
            : Tenant::all();
            
        foreach ($tenants as $tenant) {
            $this->migrateTenant($tenant);
        }
        
        $this->info('Migration completed successfully!');
    }
    
    protected function migrateTenant(Tenant $tenant)
    {
        $this->info("Migrating tenant: {$tenant->id} ({$tenant->name})");
        
        $tenant->run(function () use ($tenant) {
            foreach ($this->migratedTables as $table) {
                $this->migrateTable($tenant, $table);
            }
        });
    }
    
    protected function migrateTable(Tenant $tenant, string $table)
    {
        $this->info("  Migrating table: {$table}");
        
        // Get data from central database
        $records = DB::connection('central')
            ->table($table)
            ->where('tenant_id', $tenant->id)
            ->get();
            
        $this->info("    Found {$records->count()} records");
        
        if ($this->option('dry-run')) {
            return;
        }
        
        // Create table in tenant schema if not exists
        $this->createTenantTable($table);
        
        // Migrate data in batches
        $batchSize = (int) $this->option('batch-size');
        $records->chunk($batchSize)->each(function ($batch) use ($table) {
            $insertData = $batch->map(function ($record) {
                $data = (array) $record;
                unset($data['tenant_id']); // Remove tenant_id column
                return $data;
            })->toArray();
            
            DB::table($table)->insert($insertData);
        });
        
        $this->info("    ✓ Migrated {$records->count()} records");
    }
    
    protected function createTenantTable(string $table)
    {
        if (Schema::hasTable($table)) {
            return;
        }
        
        // Create table based on central schema but without tenant_id
        switch ($table) {
            case 'component_themes':
                $this->createComponentThemesTable();
                break;
            case 'landing_pages':
                $this->createLandingPagesTable();
                break;
            case 'analytics_events':
                $this->createAnalyticsEventsTable();
                break;
            // Add other tables...
        }
    }
    
    protected function createComponentThemesTable()
    {
        Schema::create('component_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->json('config');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->unique('slug');
        });
    }
    
    // Additional table creation methods...
}
```

### Phase 2: Model Updates (Week 2, Days 1-3)

#### Update Models to Remove tenant_id

**ComponentTheme Model:**
```php
// app/Models/ComponentTheme.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComponentTheme extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'slug',
        'config',
        'is_default',
    ];
    
    protected $casts = [
        'config' => 'array',
        'is_default' => 'boolean',
    ];
    
    // REMOVED: Global scope for tenant filtering
    // REMOVED: tenant_id from fillable
    // REMOVED: tenant() relationship
    
    /**
     * Validate theme configuration
     */
    public function validateConfig(): bool
    {
        $required = ['colors', 'fonts', 'spacing'];
        
        foreach ($required as $key) {
            if (!isset($this->config[$key])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get default theme for current tenant
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }
    
    /**
     * Set as default theme (unset others)
     */
    public function setAsDefault(): void
    {
        static::where('is_default', true)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }
}
```

**LandingPage Model:**
```php
// app/Models/LandingPage.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LandingPage extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'title',
        'slug',
        'content',
        'template_id',
        'is_published',
        'published_at',
        'meta_title',
        'meta_description',
    ];
    
    protected $casts = [
        'content' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];
    
    // REMOVED: Global scope for tenant filtering
    // REMOVED: tenant_id from fillable
    // REMOVED: tenant() relationship
    
    /**
     * Get template for this landing page
     */
    public function template()
    {
        return $this->belongsTo(Template::class);
    }
    
    /**
     * Scope for published pages
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }
    
    /**
     * Get analytics for this page
     */
    public function analytics()
    {
        return $this->hasMany(AnalyticsEvent::class, 'page_id');
    }
}
```

#### Update Controllers

**ComponentThemeController:**
```php
// app/Http/Controllers/ComponentThemeController.php
<?php

namespace App\Http\Controllers;

use App\Models\ComponentTheme;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ComponentThemeController extends Controller
{
    public function index()
    {
        // No need for tenant filtering - automatic via schema
        $themes = ComponentTheme::orderBy('name')->get();
        
        return Inertia::render('Admin/Themes/Index', [
            'themes' => $themes,
        ]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:component_themes,slug',
            'config' => 'required|array',
            'is_default' => 'boolean',
        ]);
        
        // No need to add tenant_id - automatic via schema
        $theme = ComponentTheme::create($validated);
        
        if ($validated['is_default'] ?? false) {
            $theme->setAsDefault();
        }
        
        return redirect()->route('admin.themes.index')
            ->with('success', 'Theme created successfully.');
    }
    
    public function update(Request $request, ComponentTheme $theme)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:component_themes,slug,' . $theme->id,
            'config' => 'required|array',
            'is_default' => 'boolean',
        ]);
        
        $theme->update($validated);
        
        if ($validated['is_default'] ?? false) {
            $theme->setAsDefault();
        }
        
        return redirect()->route('admin.themes.index')
            ->with('success', 'Theme updated successfully.');
    }
}
```

### Phase 3: Cross-tenant Services (Week 2, Days 4-5)

#### Federation Service for Cross-tenant Operations

```php
// app/Services/TenantFederationService.php
<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Graduate;
use App\Models\Job;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TenantFederationService
{
    /**
     * Search graduates across multiple tenants
     */
    public function searchGraduatesAcrossTenants(
        array $criteria, 
        array $tenantIds = null
    ): Collection {
        $tenants = $tenantIds 
            ? Tenant::whereIn('id', $tenantIds)->get()
            : Tenant::all();
            
        $results = collect();
        
        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($criteria, $tenant, &$results) {
                $graduates = Graduate::query();
                
                if (isset($criteria['name'])) {
                    $graduates->where('name', 'ILIKE', '%' . $criteria['name'] . '%');
                }
                
                if (isset($criteria['graduation_year'])) {
                    $graduates->where('graduation_year', $criteria['graduation_year']);
                }
                
                if (isset($criteria['course_id'])) {
                    $graduates->where('course_id', $criteria['course_id']);
                }
                
                $tenantGraduates = $graduates->get()->map(function ($graduate) use ($tenant) {
                    $graduate->tenant_info = [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                    ];
                    return $graduate;
                });
                
                $results = $results->merge($tenantGraduates);
            });
        }
        
        return $results;
    }
    
    /**
     * Get public job postings across tenants
     */
    public function getPublicJobs(array $filters = []): Collection
    {
        // Jobs remain in central database for cross-tenant access
        $query = DB::connection('central')
            ->table('jobs')
            ->where('is_public', true)
            ->where('status', 'active');
            
        if (isset($filters['location'])) {
            $query->where('location', 'ILIKE', '%' . $filters['location'] . '%');
        }
        
        if (isset($filters['job_type'])) {
            $query->where('job_type', $filters['job_type']);
        }
        
        if (isset($filters['salary_min'])) {
            $query->where('salary_max', '>=', $filters['salary_min']);
        }
        
        return $query->get();
    }
    
    /**
     * Get graduate profile from specific tenant
     */
    public function getGraduateProfile(string $tenantId, int $graduateId): ?Graduate
    {
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            return null;
        }
        
        return $tenant->run(function () use ($graduateId) {
            return Graduate::with(['course', 'profile'])->find($graduateId);
        });
    }
    
    /**
     * Get analytics summary across tenants
     */
    public function getAnalyticsSummary(array $tenantIds = null): array
    {
        $tenants = $tenantIds 
            ? Tenant::whereIn('id', $tenantIds)->get()
            : Tenant::all();
            
        $summary = [
            'total_graduates' => 0,
            'total_events' => 0,
            'total_page_views' => 0,
            'by_tenant' => [],
        ];
        
        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($tenant, &$summary) {
                $graduateCount = Graduate::count();
                $eventCount = DB::table('analytics_events')->count();
                $pageViews = DB::table('analytics_events')
                    ->where('event_type', 'page_view')
                    ->count();
                    
                $summary['total_graduates'] += $graduateCount;
                $summary['total_events'] += $eventCount;
                $summary['total_page_views'] += $pageViews;
                
                $summary['by_tenant'][$tenant->id] = [
                    'name' => $tenant->name,
                    'graduates' => $graduateCount,
                    'events' => $eventCount,
                    'page_views' => $pageViews,
                ];
            });
        }
        
        return $summary;
    }
}
```

#### Multi-tenant User Authentication

```php
// app/Http/Middleware/HandleMultiTenantAccess.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tenant;

class HandleMultiTenantAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user) {
            return $next($request);
        }
        
        // Get tenants this user has access to
        $accessibleTenants = $this->getUserAccessibleTenants($user);
        
        // Store in session for frontend access
        session(['accessible_tenants' => $accessibleTenants]);
        
        // Add to request for controller access
        $request->merge(['accessible_tenants' => $accessibleTenants]);
        
        return $next($request);
    }
    
    protected function getUserAccessibleTenants(User $user): array
    {
        // Super admin has access to all tenants
        if ($user->hasRole('super-admin')) {
            return Tenant::select('id', 'name')->get()->toArray();
        }
        
        // Regular users have access based on their institution_id
        if ($user->institution_id) {
            $tenant = Tenant::find($user->institution_id);
            return $tenant ? [$tenant->only(['id', 'name'])] : [];
        }
        
        // Alumni might have access to multiple institutions
        if ($user->hasRole('alumni')) {
            return $this->getAlumniAccessibleTenants($user);
        }
        
        return [];
    }
    
    protected function getAlumniAccessibleTenants(User $user): array
    {
        $accessibleTenants = [];
        
        // Check each tenant for graduate records
        foreach (Tenant::all() as $tenant) {
            $hasAccess = $tenant->run(function () use ($user) {
                return DB::table('graduates')
                    ->where('user_id', $user->id)
                    ->exists();
            });
            
            if ($hasAccess) {
                $accessibleTenants[] = $tenant->only(['id', 'name']);
            }
        }
        
        return $accessibleTenants;
    }
}
```

### Phase 4: Testing and Validation (Week 3)

#### Comprehensive Test Suite

```php
// tests/Feature/PureSchemaIsolationTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\ComponentTheme;
use App\Models\Graduate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PureSchemaIsolationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_tenant_data_complete_isolation()
    {
        $tenant1 = Tenant::factory()->create(['name' => 'University A']);
        $tenant2 = Tenant::factory()->create(['name' => 'University B']);
        
        // Create data in tenant 1
        $tenant1->run(function () {
            ComponentTheme::factory()->create(['name' => 'Tenant 1 Theme']);
            Graduate::factory()->create(['name' => 'John Doe']);
        });
        
        // Create data in tenant 2
        $tenant2->run(function () {
            ComponentTheme::factory()->create(['name' => 'Tenant 2 Theme']);
            Graduate::factory()->create(['name' => 'Jane Smith']);
        });
        
        // Verify isolation
        $tenant1->run(function () {
            $this->assertCount(1, ComponentTheme::all());
            $this->assertCount(1, Graduate::all());
            $this->assertEquals('Tenant 1 Theme', ComponentTheme::first()->name);
            $this->assertEquals('John Doe', Graduate::first()->name);
        });
        
        $tenant2->run(function () {
            $this->assertCount(1, ComponentTheme::all());
            $this->assertCount(1, Graduate::all());
            $this->assertEquals('Tenant 2 Theme', ComponentTheme::first()->name);
            $this->assertEquals('Jane Smith', Graduate::first()->name);
        });
    }
    
    public function test_no_tenant_id_columns_in_tenant_tables()
    {
        $tenant = Tenant::factory()->create();
        
        $tenant->run(function () {
            $columns = Schema::getColumnListing('component_themes');
            $this->assertNotContains('tenant_id', $columns);
            
            $columns = Schema::getColumnListing('graduates');
            $this->assertNotContains('tenant_id', $columns);
            
            $columns = Schema::getColumnListing('analytics_events');
            $this->assertNotContains('tenant_id', $columns);
        });
    }
    
    public function test_cross_tenant_federation_service()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        $tenant1->run(function () {
            Graduate::factory()->create([
                'name' => 'Alice Johnson',
                'graduation_year' => 2023,
            ]);
        });
        
        $tenant2->run(function () {
            Graduate::factory()->create([
                'name' => 'Bob Wilson', 
                'graduation_year' => 2023,
            ]);
        });
        
        $federationService = app(TenantFederationService::class);
        $results = $federationService->searchGraduatesAcrossTenants([
            'graduation_year' => 2023,
        ]);
        
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('name', 'Alice Johnson'));
        $this->assertTrue($results->contains('name', 'Bob Wilson'));
    }
}
```

#### Performance Benchmarks

```php
// tests/Performance/SchemaPerformanceBenchmark.php
<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\ComponentTheme;
use Illuminate\Support\Facades\DB;

class SchemaPerformanceBenchmark extends TestCase
{
    public function test_query_performance_without_tenant_filtering()
    {
        $tenant = Tenant::factory()->create();
        
        $tenant->run(function () {
            // Create test data
            ComponentTheme::factory()->count(1000)->create();
            
            // Benchmark query without tenant_id filtering
            $start = microtime(true);
            $themes = ComponentTheme::where('is_default', true)->get();
            $duration = microtime(true) - $start;
            
            $this->assertLessThan(0.05, $duration, 'Query should be fast without tenant_id filtering');
            
            // Benchmark complex query
            $start = microtime(true);
            $themes = ComponentTheme::where('name', 'LIKE', '%theme%')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
            $duration = microtime(true) - $start;
            
            $this->assertLessThan(0.1, $duration, 'Complex query should be optimized');
        });
    }
    
    public function test_index_performance()
    {
        $tenant = Tenant::factory()->create();
        
        $tenant->run(function () {
            ComponentTheme::factory()->count(10000)->create();
            
            // Test index usage
            $explain = DB::select(
                "EXPLAIN (ANALYZE, BUFFERS) SELECT * FROM component_themes WHERE slug = ?",
                ['test-theme-1']
            );
            
            $planText = $explain[0]->{'QUERY PLAN'};
            $this->assertStringContains('Index Scan', $planText, 'Should use index scan');
        });
    }
}
```

## Rollback Strategy

### Emergency Rollback Plan

```bash
#!/bin/bash
# scripts/rollback-migration.sh

echo "Starting emergency rollback..."

# 1. Stop application
sudo systemctl stop nginx
sudo systemctl stop php-fpm

# 2. Restore database from backup
psql -U postgres -d alumate < backup_pre_migration.sql

# 3. Checkout previous code version
git checkout main
composer install --no-dev
npm ci
npm run build

# 4. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 5. Restart services
sudo systemctl start php-fpm
sudo systemctl start nginx

echo "Rollback completed"
```

### Data Validation Script

```php
// scripts/validate-migration.php
<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

echo "Validating migration...\n";

$errors = [];

foreach (Tenant::all() as $tenant) {
    echo "Validating tenant: {$tenant->id}\n";
    
    $tenant->run(function () use ($tenant, &$errors) {
        // Check component themes
        $centralCount = DB::connection('central')
            ->table('component_themes')
            ->where('tenant_id', $tenant->id)
            ->count();
            
        $tenantCount = DB::table('component_themes')->count();
        
        if ($centralCount !== $tenantCount) {
            $errors[] = "Tenant {$tenant->id}: component_themes count mismatch. Central: {$centralCount}, Tenant: {$tenantCount}";
        }
        
        // Check for tenant_id columns (should not exist)
        $tables = ['component_themes', 'landing_pages', 'analytics_events'];
        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'tenant_id')) {
                $errors[] = "Tenant {$tenant->id}: Table {$table} still has tenant_id column";
            }
        }
    });
}

if (empty($errors)) {
    echo "✓ Migration validation passed!\n";
    exit(0);
} else {
    echo "✗ Migration validation failed:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    exit(1);
}
```

## Monitoring and Alerts

### Performance Monitoring

```php
// app/Http/Middleware/MonitorQueryPerformance.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitorQueryPerformance
{
    public function handle($request, Closure $next)
    {
        $startTime = microtime(true);
        $queryCount = 0;
        
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
            
            // Log slow queries
            if ($query->time > 100) {
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'tenant' => tenant('id'),
                    'url' => request()->url(),
                ]);
            }
        });
        
        $response = $next($request);
        
        $totalTime = (microtime(true) - $startTime) * 1000;
        
        // Log request performance
        if ($totalTime > 500 || $queryCount > 20) {
            Log::info('Request performance', [
                'url' => $request->url(),
                'method' => $request->method(),
                'total_time' => $totalTime,
                'query_count' => $queryCount,
                'tenant' => tenant('id'),
            ]);
        }
        
        return $response;
    }
}
```

## Success Metrics

### Key Performance Indicators

1. **Query Performance**
   - Target: 25-40% improvement in average query time
   - Measurement: Before/after benchmarks

2. **Data Isolation**
   - Target: 100% tenant data isolation
   - Measurement: Automated tests

3. **Code Simplification**
   - Target: Remove all global scopes and tenant_id filtering
   - Measurement: Code analysis

4. **Security**
   - Target: Zero cross-tenant data leakage
   - Measurement: Security audit

### Migration Completion Checklist

- [ ] All tenant data migrated to tenant schemas
- [ ] All models updated (no tenant_id columns)
- [ ] All controllers updated (no tenant filtering)
- [ ] Cross-tenant services implemented
- [ ] Tests passing (isolation, performance, functionality)
- [ ] Performance benchmarks met
- [ ] Security audit passed
- [ ] Documentation updated
- [ ] Team training completed
- [ ] Monitoring and alerts configured

## Conclusion

This migration plan provides a comprehensive roadmap for transitioning Alumate to a pure schema-based multi-tenancy architecture. The benefits of improved performance, security, and maintainability justify the 2-3 week implementation effort.

The phased approach minimizes risk while ensuring thorough testing and validation at each step. The rollback strategy provides safety nets, and the monitoring setup ensures ongoing performance optimization.