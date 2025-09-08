# Cross-Tenant Relationships Analysis

## Overview
This document addresses complex scenarios in our schema-based tenancy migration, specifically handling cross-tenant relationships, global data management, and hybrid data models.

## Key Challenges Addressed

### 1. Multi-Institutional Students
**Problem**: Students who belong to multiple institutions need access across tenant schemas.

**Solution**: Hybrid Data Model with Central User Registry

```php
// Central database (public schema)
class User extends Model {
    protected $connection = 'central';
    protected $fillable = ['email', 'name', 'global_id'];
    
    public function tenantMemberships() {
        return $this->hasMany(TenantMembership::class, 'user_global_id', 'global_id');
    }
}

class TenantMembership extends Model {
    protected $connection = 'central';
    protected $fillable = ['user_global_id', 'tenant_schema', 'role', 'status'];
}

// Tenant schema
class Student extends Model {
    protected $fillable = ['user_global_id', 'student_number', 'enrollment_date'];
    
    public function globalUser() {
        return $this->belongsTo(User::class, 'user_global_id', 'global_id')
                   ->setConnection('central');
    }
}
```

### 2. Super Admin Analytics
**Problem**: Super admins need aggregated data across all tenant schemas.

**Solution**: Cross-Schema Analytics Service

```php
class SuperAdminAnalyticsService {
    public function getGlobalMetrics() {
        $tenants = Tenant::all();
        $metrics = [];
        
        foreach ($tenants as $tenant) {
            DB::connection('tenant')->statement(
                "SET search_path TO {$tenant->schema_name}"
            );
            
            $metrics[$tenant->schema_name] = [
                'students' => Student::count(),
                'courses' => Course::count(),
                'enrollments' => Enrollment::count(),
                'revenue' => Payment::sum('amount'),
            ];
        }
        
        return $metrics;
    }
    
    public function getCrossInstitutionStudents() {
        return User::whereHas('tenantMemberships', function($query) {
            $query->havingRaw('COUNT(DISTINCT tenant_schema) > 1');
        })->with('tenantMemberships')->get();
    }
}
```

### 3. Global vs Institution-Specific Courses
**Problem**: Courses exist globally but institutions offer specific instances.

**Solution**: Global Course Catalog with Tenant Offerings

```php
// Central database - Global course catalog
class GlobalCourse extends Model {
    protected $connection = 'central';
    protected $fillable = ['code', 'title', 'description', 'credits'];
    
    public function institutionOfferings() {
        return $this->hasMany(CourseOffering::class, 'global_course_id');
    }
}

// Tenant schema - Institution-specific course offerings
class Course extends Model {
    protected $fillable = [
        'global_course_id', 
        'local_code', 
        'instructor_id', 
        'semester', 
        'capacity',
        'custom_requirements'
    ];
    
    public function globalCourse() {
        return $this->belongsTo(GlobalCourse::class, 'global_course_id')
                   ->setConnection('central');
    }
    
    public function enrollments() {
        return $this->hasMany(Enrollment::class);
    }
}
```

## Data Access Patterns

### Cross-Tenant User Authentication
```php
class TenantAwareAuthService {
    public function authenticateUser($email, $password, $tenantSchema = null) {
        // 1. Authenticate against central user registry
        $user = User::where('email', $email)->first();
        
        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }
        
        // 2. Get user's tenant memberships
        $memberships = $user->tenantMemberships;
        
        if ($tenantSchema) {
            // Specific tenant login
            $membership = $memberships->where('tenant_schema', $tenantSchema)->first();
            if (!$membership || $membership->status !== 'active') {
                throw new UnauthorizedException('No access to this institution');
            }
        }
        
        return [
            'user' => $user,
            'memberships' => $memberships,
            'current_tenant' => $tenantSchema
        ];
    }
}
```

### Cross-Schema Data Synchronization
```php
class CrossTenantSyncService {
    public function syncStudentEnrollment($userGlobalId, $fromSchema, $toSchema) {
        DB::transaction(function() use ($userGlobalId, $fromSchema, $toSchema) {
            // Get student data from source schema
            DB::statement("SET search_path TO {$fromSchema}");
            $sourceStudent = Student::where('user_global_id', $userGlobalId)->first();
            
            // Create/update in target schema
            DB::statement("SET search_path TO {$toSchema}");
            Student::updateOrCreate(
                ['user_global_id' => $userGlobalId],
                [
                    'transfer_credits' => $sourceStudent->total_credits,
                    'previous_institution' => $fromSchema,
                    'transfer_date' => now()
                ]
            );
        });
    }
}
```

## Migration Strategy

### Phase 1: Establish Central Registry
1. Create central database for global entities
2. Migrate users to central registry with global IDs
3. Create tenant membership mapping

### Phase 2: Implement Hybrid Model
1. Update authentication to use central registry
2. Maintain tenant-specific data in schemas
3. Implement cross-tenant relationship patterns

### Phase 3: Analytics & Reporting
1. Build cross-schema analytics services
2. Implement global reporting dashboards
3. Create tenant-specific views

## Performance Considerations

### Connection Pooling
```php
// config/database.php
'connections' => [
    'central' => [
        'driver' => 'pgsql',
        'host' => env('DB_HOST'),
        'database' => env('DB_DATABASE'),
        'schema' => 'public',
        'pool' => [
            'min' => 2,
            'max' => 10
        ]
    ],
    'tenant' => [
        'driver' => 'pgsql',
        'host' => env('DB_HOST'),
        'database' => env('DB_DATABASE'),
        'schema' => 'dynamic', // Set at runtime
        'pool' => [
            'min' => 5,
            'max' => 20
        ]
    ]
]
```

### Caching Strategy
```php
class TenantAwareCacheService {
    public function remember($key, $ttl, $callback, $scope = 'tenant') {
        $cacheKey = match($scope) {
            'global' => "global:{$key}",
            'tenant' => "tenant:" . app('tenant.schema') . ":{$key}",
            'user' => "user:" . auth()->id() . ":{$key}"
        };
        
        return Cache::remember($cacheKey, $ttl, $callback);
    }
}
```

## Security Considerations

### Row Level Security for Cross-Tenant Access
```sql
-- Enable RLS on central tables
ALTER TABLE tenant_memberships ENABLE ROW LEVEL SECURITY;

-- Policy for user access to their own memberships
CREATE POLICY user_membership_policy ON tenant_memberships
    FOR ALL TO authenticated
    USING (user_global_id = current_setting('app.user_global_id')::uuid);

-- Policy for super admin access
CREATE POLICY admin_membership_policy ON tenant_memberships
    FOR ALL TO authenticated
    USING (current_setting('app.user_role') = 'super_admin');
```

### API Security
```php
class CrossTenantMiddleware {
    public function handle($request, Closure $next) {
        $user = auth()->user();
        $requestedSchema = $request->header('X-Tenant-Schema');
        
        // Verify user has access to requested tenant
        $membership = $user->tenantMemberships
            ->where('tenant_schema', $requestedSchema)
            ->where('status', 'active')
            ->first();
            
        if (!$membership) {
            abort(403, 'Access denied to tenant');
        }
        
        // Set tenant context
        app()->instance('tenant.schema', $requestedSchema);
        DB::statement("SET search_path TO {$requestedSchema}");
        
        return $next($request);
    }
}
```

## Implementation Examples

### Multi-Tenant Course Enrollment
```php
class EnrollmentService {
    public function enrollStudentInCourse($userGlobalId, $courseId, $tenantSchema) {
        DB::transaction(function() use ($userGlobalId, $courseId, $tenantSchema) {
            // Set tenant context
            DB::statement("SET search_path TO {$tenantSchema}");
            
            // Verify student exists in this tenant
            $student = Student::where('user_global_id', $userGlobalId)->firstOrFail();
            
            // Verify course exists and has capacity
            $course = Course::findOrFail($courseId);
            
            if ($course->enrollments()->count() >= $course->capacity) {
                throw new Exception('Course is full');
            }
            
            // Create enrollment
            Enrollment::create([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'enrollment_date' => now(),
                'status' => 'active'
            ]);
            
            // Log cross-tenant activity
            $this->logCrossTenantActivity($userGlobalId, 'enrollment', [
                'tenant' => $tenantSchema,
                'course_id' => $courseId
            ]);
        });
    }
}
```

### Global Search Across Tenants
```php
class GlobalSearchService {
    public function searchStudents($query, $userTenants = null) {
        $results = [];
        $tenants = $userTenants ?? Tenant::all();
        
        foreach ($tenants as $tenant) {
            DB::statement("SET search_path TO {$tenant->schema_name}");
            
            $students = Student::join('users', 'students.user_global_id', '=', 'users.global_id')
                ->where('users.name', 'ILIKE', "%{$query}%")
                ->orWhere('students.student_number', 'ILIKE', "%{$query}%")
                ->select('students.*', 'users.name', 'users.email')
                ->get();
                
            $results[$tenant->name] = $students;
        }
        
        return $results;
    }
}
```

## Conclusion

This hybrid approach allows us to:
- Maintain tenant isolation while enabling cross-tenant relationships
- Support multi-institutional students seamlessly
- Provide comprehensive super admin analytics
- Handle global course catalogs with institution-specific offerings
- Ensure security and performance at scale

The key is separating global identity and relationships (central database) from tenant-specific operational data (schema-based isolation).