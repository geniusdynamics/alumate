# Database Architecture Analysis & Migration Strategy

## Current State Assessment

### Hybrid Tenancy Implementation

The Alumate application currently uses **Stancl Tenancy** with PostgreSQL, implementing a **hybrid approach** that combines:

1. **Schema-based separation** for core tenant data (graduates, courses, etc.)
2. **tenant_id column approach** for shared/central resources (components, themes, etc.)

### Current Architecture Overview

#### Central Database (public schema)
- **Tenants management**: `tenants`, `domains` tables
- **Shared resources**: `users`, `employers`, `jobs`, `institutions`
- **Cross-tenant features**: `component_themes`, `landing_pages`, `analytics_events`
- **System-wide**: `permissions`, `roles`, `notifications`

#### Tenant-specific Schemas
- **Core tenant data**: `graduates`, `courses`, `graduate_profiles`
- **Social features**: `posts`, `circles`, `groups`
- **Achievements**: `achievements`, `user_achievements`
- **Performance monitoring**: tenant-specific analytics

### Configuration Analysis

```php
// config/tenancy.php
'database' => [
    'central_connection' => 'central',
    'prefix' => 'tenant',
    'managers' => [
        'pgsql' => PostgreSQLSchemaManager::class,
    ],
],
```

**Key Findings:**
- ✅ Proper PostgreSQL schema manager configured
- ✅ Separate central and tenant connections
- ✅ Automatic tenant resolution via domains
- ⚠️ Mixed approach creates complexity
- ⚠️ Some models still use `tenant_id` columns unnecessarily

## Architectural Comparison

### Current Hybrid Approach

**Pros:**
- Flexible for different data types
- Easy cross-tenant queries for shared resources
- Gradual migration possible

**Cons:**
- Increased complexity
- Potential data leakage risks
- Inconsistent isolation patterns
- Performance overhead from tenant_id filtering
- Maintenance burden

### Pure Schema-based Approach (Recommended)

**Pros:**
- **Superior isolation**: Complete data separation at database level
- **Better performance**: No tenant_id filtering overhead
- **Cleaner architecture**: Consistent isolation pattern
- **Enhanced security**: Reduced risk of data leakage
- **Simplified queries**: No need for global scopes
- **Better scalability**: Independent schema optimization

**Cons:**
- Cross-tenant queries require federation
- More complex for truly shared resources
- Migration effort required

### Alternative: Database-per-tenant

**Pros:**
- Maximum isolation
- Independent backups/scaling
- Regulatory compliance friendly

**Cons:**
- High operational overhead
- Connection pool limitations
- Complex cross-tenant operations
- Higher infrastructure costs

## Recommendation: Pure Schema-based Approach

**Why this is the best choice for Alumate:**

1. **Educational institutions need strong data isolation**
2. **Performance benefits** for tenant-specific queries
3. **Regulatory compliance** (FERPA, GDPR)
4. **Scalability** - each tenant schema can be optimized independently
5. **Security** - reduced attack surface
6. **Maintainability** - consistent patterns

## Migration Strategy

### Phase 1: Assessment & Planning (1 week)

#### 1.1 Audit Current Models
```bash
# Identify models using tenant_id approach
grep -r "tenant_id" app/Models/
grep -r "scopeForTenant" app/Models/
grep -r "global.*scope" app/Models/
```

#### 1.2 Categorize Data
- **Move to tenant schemas**: ComponentTheme, LandingPage, AnalyticsEvent
- **Keep central**: Tenant, Domain, User (authentication)
- **Hybrid approach**: Jobs, Employers (with cross-tenant references)

### Phase 2: Schema Design (3-4 days)

#### 2.1 Design Tenant Schema Structure
```sql
-- Each tenant gets schema: tenant_{tenant_id}
CREATE SCHEMA tenant_abc123;

-- Tenant-specific tables
CREATE TABLE tenant_abc123.graduates (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    -- No tenant_id needed!
);

CREATE TABLE tenant_abc123.component_themes (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    config JSONB NOT NULL,
    -- No tenant_id needed!
);
```

#### 2.2 Central Database for Cross-tenant Data
```sql
-- Central schema for shared resources
CREATE TABLE public.tenant_job_postings (
    id BIGSERIAL PRIMARY KEY,
    tenant_id VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    -- Keep tenant_id for cross-tenant job discovery
);
```

### Phase 3: Migration Implementation (1 week)

#### 3.1 Create Migration Scripts
```php
// database/migrations/tenant/2025_01_15_000001_migrate_to_pure_schema.php
public function up()
{
    // Move component_themes from central to tenant schema
    $this->migrateComponentThemes();
    
    // Move landing_pages from central to tenant schema
    $this->migrateLandingPages();
    
    // Move analytics_events from central to tenant schema
    $this->migrateAnalyticsEvents();
}

private function migrateComponentThemes()
{
    Schema::create('component_themes', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug');
        $table->json('config');
        $table->boolean('is_default')->default(false);
        $table->timestamps();
        
        $table->unique('slug'); // No tenant_id needed
    });
}
```

#### 3.2 Data Migration Script
```php
// scripts/migrate-to-pure-schema.php
foreach (Tenant::all() as $tenant) {
    $tenant->run(function () use ($tenant) {
        // Migrate component themes
        $themes = DB::connection('central')
            ->table('component_themes')
            ->where('tenant_id', $tenant->id)
            ->get();
            
        foreach ($themes as $theme) {
            DB::table('component_themes')->insert([
                'name' => $theme->name,
                'slug' => $theme->slug,
                'config' => $theme->config,
                'is_default' => $theme->is_default,
                'created_at' => $theme->created_at,
                'updated_at' => $theme->updated_at,
            ]);
        }
    });
}
```

#### 3.3 Update Models
```php
// app/Models/ComponentTheme.php
class ComponentTheme extends Model
{
    // Remove tenant_id from fillable
    protected $fillable = [
        'name',
        'slug', 
        'config',
        'is_default',
    ];
    
    // Remove global scope
    // protected static function booted()
    // {
    //     static::addGlobalScope('tenant', function (Builder $builder) {
    //         $builder->where('tenant_id', tenant('id'));
    //     });
    // }
    
    // Remove tenant relationship
    // public function tenant()
    // {
    //     return $this->belongsTo(Tenant::class);
    // }
}
```

### Phase 4: Cross-tenant Data Handling (2-3 days)

#### 4.1 Federation Service
```php
// app/Services/TenantFederationService.php
class TenantFederationService
{
    public function searchJobsAcrossTenants(array $criteria): Collection
    {
        // Use central database for cross-tenant job search
        return DB::connection('central')
            ->table('jobs')
            ->where('is_public', true)
            ->where($criteria)
            ->get();
    }
    
    public function getGraduateProfile(string $tenantId, int $graduateId): ?Graduate
    {
        $tenant = Tenant::find($tenantId);
        
        return $tenant->run(function () use ($graduateId) {
            return Graduate::find($graduateId);
        });
    }
}
```

#### 4.2 Cross-tenant Authentication
```php
// app/Http/Middleware/HandleCrossTenantAccess.php
class HandleCrossTenantAccess
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user();
        
        // Check if user has access to multiple tenants
        if ($user && $user->hasMultipleTenantAccess()) {
            // Store available tenants in session
            session(['available_tenants' => $user->accessibleTenants()]);
        }
        
        return $next($request);
    }
}
```

### Phase 5: Testing & Validation (3-4 days)

#### 5.1 Automated Tests
```php
// tests/Feature/TenantIsolationTest.php
class TenantIsolationTest extends TestCase
{
    public function test_tenant_data_isolation()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        $tenant1->run(function () {
            ComponentTheme::factory()->create(['name' => 'Tenant 1 Theme']);
        });
        
        $tenant2->run(function () {
            $themes = ComponentTheme::all();
            $this->assertCount(0, $themes); // Should not see tenant 1's themes
        });
    }
    
    public function test_cross_tenant_job_search()
    {
        // Test federation service
        $jobs = app(TenantFederationService::class)
            ->searchJobsAcrossTenants(['location' => 'New York']);
            
        $this->assertNotEmpty($jobs);
    }
}
```

#### 5.2 Performance Testing
```php
// tests/Performance/SchemaPerformanceTest.php
class SchemaPerformanceTest extends TestCase
{
    public function test_query_performance_without_tenant_id_filtering()
    {
        $tenant = Tenant::factory()->create();
        
        $tenant->run(function () {
            // Create test data
            ComponentTheme::factory()->count(1000)->create();
            
            $start = microtime(true);
            $themes = ComponentTheme::where('is_default', true)->get();
            $duration = microtime(true) - $start;
            
            // Should be faster without tenant_id filtering
            $this->assertLessThan(0.1, $duration);
        });
    }
}
```

## Implementation Timeline

| Phase | Duration | Tasks |
|-------|----------|-------|
| **Phase 1** | 1 week | Audit, categorize, plan |
| **Phase 2** | 3-4 days | Schema design, migration scripts |
| **Phase 3** | 1 week | Implementation, model updates |
| **Phase 4** | 2-3 days | Cross-tenant services |
| **Phase 5** | 3-4 days | Testing, validation |
| **Total** | **2-3 weeks** | Complete migration |

## Risk Mitigation

### 1. Data Loss Prevention
- **Backup strategy**: Full database backup before migration
- **Rollback plan**: Keep original tables with `_backup` suffix
- **Validation**: Compare record counts before/after migration

### 2. Downtime Minimization
- **Blue-green deployment**: Migrate to new schema while keeping old active
- **Feature flags**: Gradual rollout of new architecture
- **Monitoring**: Real-time performance monitoring during migration

### 3. Performance Monitoring
```php
// Monitor query performance
DB::listen(function ($query) {
    if ($query->time > 100) { // Log slow queries
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'time' => $query->time,
            'tenant' => tenant('id'),
        ]);
    }
});
```

## Expected Benefits

### Performance Improvements
- **25-40% faster queries** (no tenant_id filtering)
- **Reduced index overhead** (smaller, tenant-specific indexes)
- **Better query optimization** (PostgreSQL can optimize per-schema)

### Security Enhancements
- **Complete data isolation** at database level
- **Reduced attack surface** (no cross-tenant data leakage)
- **Compliance ready** (FERPA, GDPR requirements)

### Maintenance Benefits
- **Simplified models** (no global scopes)
- **Consistent patterns** (all tenant data in tenant schemas)
- **Easier debugging** (clear data boundaries)

## Conclusion

**Recommendation: Proceed with migration to pure schema-based approach**

The benefits significantly outweigh the migration effort:
- **Superior isolation and security**
- **Better performance and scalability** 
- **Cleaner, more maintainable architecture**
- **Regulatory compliance readiness**

The 2-3 week migration timeline is reasonable for the long-term benefits this architecture provides.