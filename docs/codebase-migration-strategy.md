# Codebase Migration Strategy for Schema-Based Tenancy

## Overview
Yes, Mr GENIUS, the entire system's codebase needs significant modifications to work with the new schema-based tenancy structure. This document outlines the comprehensive changes required across all layers of the application.

## 1. Database Connection Management

### Current State
```php
// Current: Single connection with tenant_id filtering
DB::table('students')->where('tenant_id', $tenantId)->get();
```

### New Schema-Based Approach
```php
// New: Dynamic schema switching
DB::connection('tenant')->table('students')->get();
// OR
DB::statement('SET search_path TO tenant_abc');
DB::table('students')->get();
```

### Implementation Strategy
1. **Dynamic Database Configuration**
   - Create tenant-aware database configuration
   - Implement schema switching middleware
   - Add tenant context resolution

2. **Connection Pool Management**
   - Manage multiple schema connections
   - Implement connection caching
   - Handle connection cleanup

## 2. Eloquent Model Changes

### Remove Tenant Scoping
```php
// BEFORE: Models with tenant_id
class Student extends Model
{
    protected $fillable = ['tenant_id', 'first_name', 'last_name', 'email'];
    
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
    
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

// AFTER: Schema-based models
class Student extends Model
{
    protected $fillable = ['first_name', 'last_name', 'email']; // No tenant_id
    
    // Remove global tenant scope
    // Remove tenant relationship
    
    public function getConnectionName()
    {
        return TenantContext::getConnection();
    }
}
```

### Model Factory Updates
```php
// BEFORE
Student::factory()->create(['tenant_id' => $tenant->id]);

// AFTER
TenantContext::setTenant($tenant);
Student::factory()->create(); // No tenant_id needed
```

## 3. Service Layer Modifications

### Tenant Context Service
```php
class TenantContextService
{
    private static $currentTenant;
    private static $currentSchema;
    
    public static function setTenant(Tenant $tenant)
    {
        self::$currentTenant = $tenant;
        self::$currentSchema = $tenant->schema_name;
        
        // Switch database schema
        DB::statement("SET search_path TO {$tenant->schema_name}");
        
        // Update default connection
        config(['database.connections.tenant.search_path' => $tenant->schema_name]);
        DB::purge('tenant');
    }
    
    public static function getCurrentTenant(): ?Tenant
    {
        return self::$currentTenant;
    }
    
    public static function getCurrentSchema(): ?string
    {
        return self::$currentSchema;
    }
    
    public static function withTenant(Tenant $tenant, callable $callback)
    {
        $previousTenant = self::$currentTenant;
        $previousSchema = self::$currentSchema;
        
        try {
            self::setTenant($tenant);
            return $callback();
        } finally {
            if ($previousTenant) {
                self::setTenant($previousTenant);
            } else {
                self::clearTenant();
            }
        }
    }
    
    public static function clearTenant()
    {
        self::$currentTenant = null;
        self::$currentSchema = null;
        DB::statement("SET search_path TO public");
    }
}
```

### Service Classes Updates
```php
// BEFORE: Services with tenant filtering
class StudentService
{
    public function getStudents($tenantId)
    {
        return Student::where('tenant_id', $tenantId)->get();
    }
    
    public function createStudent($tenantId, array $data)
    {
        $data['tenant_id'] = $tenantId;
        return Student::create($data);
    }
}

// AFTER: Schema-aware services
class StudentService
{
    public function getStudents()
    {
        // Tenant context is already set by middleware
        return Student::all();
    }
    
    public function createStudent(array $data)
    {
        // No tenant_id needed - schema isolation handles it
        return Student::create($data);
    }
    
    public function getStudentsForTenant(Tenant $tenant)
    {
        return TenantContextService::withTenant($tenant, function() {
            return Student::all();
        });
    }
}
```

## 4. Middleware Changes

### Tenant Resolution Middleware
```php
class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = $this->resolveTenant($request);
        
        if (!$tenant) {
            abort(404, 'Tenant not found');
        }
        
        // Set tenant context for the request
        TenantContextService::setTenant($tenant);
        
        // Add tenant to request for easy access
        $request->merge(['tenant' => $tenant]);
        
        $response = $next($request);
        
        // Clean up tenant context after request
        TenantContextService::clearTenant();
        
        return $response;
    }
    
    private function resolveTenant(Request $request): ?Tenant
    {
        // Multiple resolution strategies
        
        // 1. Subdomain-based
        if ($subdomain = $this->getSubdomain($request)) {
            return Tenant::where('subdomain', $subdomain)->first();
        }
        
        // 2. Domain-based
        if ($domain = $request->getHost()) {
            return Tenant::where('domain', $domain)->first();
        }
        
        // 3. Header-based (for API)
        if ($tenantId = $request->header('X-Tenant-ID')) {
            return Tenant::find($tenantId);
        }
        
        // 4. User-based (for authenticated users)
        if ($user = $request->user()) {
            return $user->tenant;
        }
        
        return null;
    }
}
```

## 5. Controller Updates

### Remove Tenant ID Parameters
```php
// BEFORE: Controllers with tenant parameters
class StudentController extends Controller
{
    public function index($tenantId)
    {
        $students = Student::where('tenant_id', $tenantId)->get();
        return response()->json($students);
    }
    
    public function store($tenantId, Request $request)
    {
        $data = $request->validated();
        $data['tenant_id'] = $tenantId;
        $student = Student::create($data);
        return response()->json($student, 201);
    }
}

// AFTER: Schema-aware controllers
class StudentController extends Controller
{
    public function index(Request $request)
    {
        // Tenant context is set by middleware
        $students = Student::all();
        return response()->json($students);
    }
    
    public function store(Request $request)
    {
        $data = $request->validated();
        // No tenant_id needed
        $student = Student::create($data);
        return response()->json($student, 201);
    }
    
    // Access current tenant if needed
    public function getCurrentTenant()
    {
        return TenantContextService::getCurrentTenant();
    }
}
```

## 6. Route Changes

### Remove Tenant ID from Routes
```php
// BEFORE: Routes with tenant parameters
Route::prefix('tenants/{tenant}')->group(function () {
    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::get('/courses', [CourseController::class, 'index']);
});

// AFTER: Clean routes with tenant middleware
Route::middleware(['tenant'])->group(function () {
    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::get('/courses', [CourseController::class, 'index']);
});

// OR subdomain-based routing
Route::domain('{tenant}.alumate.com')->middleware(['tenant'])->group(function () {
    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students', [StudentController::class, 'store']);
});
```

## 7. Authentication & Authorization

### User Model Updates
```php
class User extends Authenticatable
{
    // Add tenant relationship for cross-tenant users
    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'user_tenants')
                    ->withPivot(['role', 'permissions', 'status']);
    }
    
    public function currentTenant()
    {
        return TenantContextService::getCurrentTenant();
    }
    
    public function hasAccessToTenant(Tenant $tenant): bool
    {
        return $this->tenants()->where('tenant_id', $tenant->id)->exists();
    }
}
```

### Policy Updates
```php
// BEFORE: Policies with tenant checks
class StudentPolicy
{
    public function view(User $user, Student $student)
    {
        return $user->tenant_id === $student->tenant_id;
    }
}

// AFTER: Schema-based policies
class StudentPolicy
{
    public function view(User $user, Student $student)
    {
        // Student is already in correct schema context
        // Check if user has access to current tenant
        $currentTenant = TenantContextService::getCurrentTenant();
        return $user->hasAccessToTenant($currentTenant);
    }
}
```

## 8. Queue Jobs & Background Processing

### Tenant-Aware Jobs
```php
class ProcessStudentData implements ShouldQueue
{
    private $tenantId;
    private $studentData;
    
    public function __construct($tenantId, $studentData)
    {
        $this->tenantId = $tenantId;
        $this->studentData = $studentData;
    }
    
    public function handle()
    {
        $tenant = Tenant::find($this->tenantId);
        
        TenantContextService::withTenant($tenant, function() {
            // Process student data in tenant context
            Student::create($this->studentData);
        });
    }
}
```

## 9. Testing Strategy

### Test Database Setup
```php
class TenantTestCase extends TestCase
{
    protected $tenant;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test tenant
        $this->tenant = Tenant::factory()->create();
        
        // Set up tenant schema
        $this->createTenantSchema($this->tenant);
        
        // Set tenant context
        TenantContextService::setTenant($this->tenant);
    }
    
    protected function tearDown(): void
    {
        // Clean up tenant schema
        $this->dropTenantSchema($this->tenant);
        
        TenantContextService::clearTenant();
        
        parent::tearDown();
    }
}
```

## 10. Migration Phases

### Phase 1: Preparation
- [ ] Create tenant context service
- [ ] Implement schema switching logic
- [ ] Create tenant middleware
- [ ] Update database configuration

### Phase 2: Model Updates
- [ ] Remove tenant_id from fillable arrays
- [ ] Remove global tenant scopes
- [ ] Update model relationships
- [ ] Update factory definitions

### Phase 3: Service Layer
- [ ] Update service classes
- [ ] Remove tenant_id parameters
- [ ] Implement tenant context usage
- [ ] Update business logic

### Phase 4: Controller & Routes
- [ ] Update controller methods
- [ ] Remove tenant parameters from routes
- [ ] Update API endpoints
- [ ] Update form requests

### Phase 5: Frontend Updates
- [ ] Update API calls
- [ ] Remove tenant_id from forms
- [ ] Update JavaScript/Vue components
- [ ] Update routing logic

### Phase 6: Testing & Validation
- [ ] Update test suites
- [ ] Create tenant-specific tests
- [ ] Performance testing
- [ ] Security validation

## 11. Rollback Strategy

If rollback is needed:
1. Restore database from backup
2. Revert code changes using Git
3. Re-add tenant_id columns
4. Restore global scopes
5. Update routes and controllers

## 12. Performance Considerations

### Connection Pooling
```php
class TenantConnectionManager
{
    private static $connections = [];
    
    public static function getConnection($schemaName)
    {
        if (!isset(self::$connections[$schemaName])) {
            self::$connections[$schemaName] = self::createConnection($schemaName);
        }
        
        return self::$connections[$schemaName];
    }
}
```

### Query Optimization
- Remove tenant_id from WHERE clauses
- Update indexes for schema-based queries
- Optimize cross-tenant analytics queries

## Conclusion

This migration requires comprehensive changes across the entire application stack. The key is to implement it in phases, with thorough testing at each stage. The tenant context service becomes the central piece that manages schema switching and maintains tenant isolation.

The benefits include:
- Better data isolation
- Improved performance (no tenant_id filtering)
- Cleaner code architecture
- Better scalability
- Enhanced security

However, it requires careful planning and execution to ensure no data loss and minimal downtime.