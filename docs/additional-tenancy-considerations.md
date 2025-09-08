# Additional Schema-Based Tenancy Considerations

This document covers advanced considerations and best practices for implementing and maintaining schema-based multi-tenancy in the Alumate system.

## Table of Contents

1. [Data Synchronization & Consistency](#data-synchronization--consistency)
2. [Backup & Disaster Recovery](#backup--disaster-recovery)
3. [API Design Patterns](#api-design-patterns)
4. [Monitoring & Observability](#monitoring--observability)
5. [Cost & Resource Management](#cost--resource-management)
6. [Compliance & Audit Requirements](#compliance--audit-requirements)
7. [Development & Testing Strategies](#development--testing-strategies)
8. [Migration Complexity & Rollback Scenarios](#migration-complexity--rollback-scenarios)

## Data Synchronization & Consistency

### Cross-Tenant Data Synchronization

Some data needs to be synchronized across tenants or maintained globally:

```php
// Global course catalog synchronization
class GlobalCourseSyncService
{
    public function syncCourseToTenants(GlobalCourse $globalCourse, array $tenantIds = null)
    {
        $tenants = $tenantIds ? Tenant::whereIn('id', $tenantIds)->get() : Tenant::all();
        
        foreach ($tenants as $tenant) {
            $this->tenantService->executeInTenantContext($tenant, function() use ($globalCourse) {
                Course::updateOrCreate(
                    ['global_course_id' => $globalCourse->id],
                    [
                        'course_code' => $globalCourse->course_code,
                        'title' => $globalCourse->title,
                        'credits' => $globalCourse->credits,
                        'description' => $globalCourse->description,
                        'last_synced_at' => now()
                    ]
                );
            });
        }
    }
    
    public function detectSyncConflicts()
    {
        $conflicts = [];
        $globalCourses = GlobalCourse::all();
        
        foreach (Tenant::all() as $tenant) {
            $this->tenantService->executeInTenantContext($tenant, function() use (&$conflicts, $globalCourses, $tenant) {
                foreach ($globalCourses as $globalCourse) {
                    $localCourse = Course::where('global_course_id', $globalCourse->id)->first();
                    
                    if ($localCourse && $localCourse->updated_at > $globalCourse->updated_at) {
                        $conflicts[] = [
                            'tenant_id' => $tenant->id,
                            'course_id' => $localCourse->id,
                            'global_course_id' => $globalCourse->id,
                            'conflict_type' => 'local_newer_than_global'
                        ];
                    }
                }
            });
        }
        
        return $conflicts;
    }
}
```

### Data Consistency Patterns

```php
// Event-driven consistency
class TenantDataConsistencyService
{
    public function ensureReferentialIntegrity(Tenant $tenant)
    {
        return $this->tenantService->executeInTenantContext($tenant, function() {
            $issues = [];
            
            // Check orphaned enrollments
            $orphanedEnrollments = DB::select("
                SELECT e.id, e.student_id, e.course_id 
                FROM enrollments e 
                LEFT JOIN students s ON e.student_id = s.id 
                LEFT JOIN courses c ON e.course_id = c.id 
                WHERE s.id IS NULL OR c.id IS NULL
            ");
            
            if (!empty($orphanedEnrollments)) {
                $issues['orphaned_enrollments'] = $orphanedEnrollments;
            }
            
            // Check grade consistency
            $inconsistentGrades = DB::select("
                SELECT g.id, g.enrollment_id, g.student_id, g.course_id
                FROM grades g
                LEFT JOIN enrollments e ON g.enrollment_id = e.id
                WHERE e.id IS NULL 
                   OR g.student_id != e.student_id 
                   OR g.course_id != e.course_id
            ");
            
            if (!empty($inconsistentGrades)) {
                $issues['inconsistent_grades'] = $inconsistentGrades;
            }
            
            return $issues;
        });
    }
}
```

## Backup & Disaster Recovery

### Schema-Specific Backup Strategy

```php
// Enhanced backup service for schema-based tenancy
class SchemaTenantBackupService
{
    public function backupTenant(Tenant $tenant, array $options = [])
    {
        $backupId = 'tenant_' . $tenant->id . '_' . now()->format('Y_m_d_H_i_s');
        $backupPath = storage_path('backups/tenants/' . $backupId);
        
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        // Backup schema structure
        $this->backupSchemaStructure($tenant, $backupPath);
        
        // Backup data
        $this->backupSchemaData($tenant, $backupPath, $options);
        
        // Create metadata
        $this->createBackupMetadata($tenant, $backupPath, $options);
        
        // Compress backup
        if ($options['compress'] ?? true) {
            $this->compressBackup($backupPath);
        }
        
        return $backupId;
    }
    
    protected function backupSchemaStructure(Tenant $tenant, string $backupPath)
    {
        $schemaName = $tenant->schema_name;
        $structureFile = $backupPath . '/schema_structure.sql';
        
        $command = sprintf(
            'pg_dump --host=%s --port=%s --username=%s --schema=%s --schema-only --no-owner --no-privileges %s > %s',
            config('database.connections.pgsql.host'),
            config('database.connections.pgsql.port'),
            config('database.connections.pgsql.username'),
            $schemaName,
            config('database.connections.pgsql.database'),
            $structureFile
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception('Schema structure backup failed: ' . implode('\n', $output));
        }
    }
    
    protected function backupSchemaData(Tenant $tenant, string $backupPath, array $options)
    {
        $schemaName = $tenant->schema_name;
        $dataFile = $backupPath . '/schema_data.sql';
        
        $excludeTables = $options['exclude_tables'] ?? [];
        $excludeOptions = '';
        
        foreach ($excludeTables as $table) {
            $excludeOptions .= " --exclude-table={$schemaName}.{$table}";
        }
        
        $command = sprintf(
            'pg_dump --host=%s --port=%s --username=%s --schema=%s --data-only --no-owner --no-privileges %s %s > %s',
            config('database.connections.pgsql.host'),
            config('database.connections.pgsql.port'),
            config('database.connections.pgsql.username'),
            $schemaName,
            $excludeOptions,
            config('database.connections.pgsql.database'),
            $dataFile
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception('Schema data backup failed: ' . implode('\n', $output));
        }
    }
    
    public function restoreTenant(string $backupId, Tenant $tenant = null)
    {
        $backupPath = storage_path('backups/tenants/' . $backupId);
        
        if (!is_dir($backupPath)) {
            throw new Exception('Backup not found: ' . $backupId);
        }
        
        $metadata = json_decode(file_get_contents($backupPath . '/metadata.json'), true);
        
        if (!$tenant) {
            $tenant = Tenant::find($metadata['tenant_id']);
        }
        
        // Drop existing schema if it exists
        if ($tenant->schema_name) {
            DB::statement("DROP SCHEMA IF EXISTS {$tenant->schema_name} CASCADE");
        }
        
        // Restore schema structure
        $this->restoreSchemaStructure($backupPath, $tenant->schema_name);
        
        // Restore data
        $this->restoreSchemaData($backupPath, $tenant->schema_name);
        
        return true;
    }
}
```

## API Design Patterns

### Cross-Tenant Operations

```php
// API controller for cross-tenant operations
class CrossTenantApiController extends Controller
{
    public function __construct(
        protected TenantContextService $tenantService,
        protected CrossTenantAuthService $crossTenantAuth
    ) {}
    
    /**
     * Get aggregated statistics across multiple tenants
     */
    public function getAggregatedStats(Request $request)
    {
        $user = $request->user();
        $tenantIds = $this->crossTenantAuth->getAuthorizedTenants($user);
        
        $stats = [];
        
        foreach ($tenantIds as $tenantId) {
            $tenant = Tenant::find($tenantId);
            
            $tenantStats = $this->tenantService->executeInTenantContext($tenant, function() {
                return [
                    'students_count' => Student::count(),
                    'courses_count' => Course::count(),
                    'enrollments_count' => Enrollment::count(),
                    'active_enrollments' => Enrollment::where('status', 'enrolled')->count()
                ];
            });
            
            $stats[$tenantId] = array_merge($tenantStats, [
                'tenant_name' => $tenant->name,
                'tenant_slug' => $tenant->slug
            ]);
        }
        
        return response()->json([
            'data' => $stats,
            'aggregated' => [
                'total_students' => array_sum(array_column($stats, 'students_count')),
                'total_courses' => array_sum(array_column($stats, 'courses_count')),
                'total_enrollments' => array_sum(array_column($stats, 'enrollments_count')),
                'total_active_enrollments' => array_sum(array_column($stats, 'active_enrollments'))
            ]
        ]);
    }
    
    /**
     * Bulk operations across tenants
     */
    public function bulkUpdateCourses(Request $request)
    {
        $validated = $request->validate([
            'tenant_ids' => 'required|array',
            'course_updates' => 'required|array',
            'global_course_id' => 'required|exists:global_courses,id'
        ]);
        
        $results = [];
        
        foreach ($validated['tenant_ids'] as $tenantId) {
            $tenant = Tenant::find($tenantId);
            
            if (!$this->crossTenantAuth->canAccessTenant($request->user(), $tenant)) {
                $results[$tenantId] = ['error' => 'Access denied'];
                continue;
            }
            
            try {
                $result = $this->tenantService->executeInTenantContext($tenant, function() use ($validated) {
                    return Course::where('global_course_id', $validated['global_course_id'])
                        ->update($validated['course_updates']);
                });
                
                $results[$tenantId] = ['updated' => $result];
            } catch (Exception $e) {
                $results[$tenantId] = ['error' => $e->getMessage()];
            }
        }
        
        return response()->json(['results' => $results]);
    }
}
```

### Tenant-Aware API Middleware

```php
class ApiTenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // For API routes, tenant can be specified via:
        // 1. Subdomain (api.tenant1.alumate.com)
        // 2. Header (X-Tenant-ID)
        // 3. URL parameter (/api/v1/tenants/{tenant}/students)
        
        $tenant = $this->resolveTenantFromRequest($request);
        
        if (!$tenant) {
            return response()->json(['error' => 'Tenant not specified'], 400);
        }
        
        // Verify user has access to this tenant
        if (!$this->userCanAccessTenant($request->user(), $tenant)) {
            return response()->json(['error' => 'Access denied to tenant'], 403);
        }
        
        // Set tenant context
        app(TenantContextService::class)->setTenant($tenant);
        
        $response = $next($request);
        
        // Add tenant info to response headers
        $response->headers->set('X-Tenant-ID', $tenant->id);
        $response->headers->set('X-Tenant-Schema', $tenant->schema_name);
        
        return $response;
    }
}
```

## Monitoring & Observability

### Schema-Specific Monitoring

```php
class TenantMonitoringService
{
    public function collectTenantMetrics()
    {
        $metrics = [];
        
        foreach (Tenant::all() as $tenant) {
            $tenantMetrics = $this->tenantService->executeInTenantContext($tenant, function() {
                return [
                    'schema_size' => $this->getSchemaSize(),
                    'table_counts' => $this->getTableCounts(),
                    'query_performance' => $this->getQueryPerformanceMetrics(),
                    'connection_count' => $this->getActiveConnections(),
                    'last_activity' => $this->getLastActivity()
                ];
            });
            
            $metrics[$tenant->id] = array_merge($tenantMetrics, [
                'tenant_name' => $tenant->name,
                'schema_name' => $tenant->schema_name
            ]);
        }
        
        return $metrics;
    }
    
    protected function getSchemaSize(): array
    {
        $result = DB::select("
            SELECT 
                schemaname,
                tablename,
                pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size,
                pg_total_relation_size(schemaname||'.'||tablename) as size_bytes
            FROM pg_tables 
            WHERE schemaname = current_schema()
            ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
        ");
        
        return [
            'total_size_bytes' => array_sum(array_column($result, 'size_bytes')),
            'tables' => $result
        ];
    }
    
    protected function getQueryPerformanceMetrics(): array
    {
        // Requires pg_stat_statements extension
        $slowQueries = DB::select("
            SELECT 
                query,
                calls,
                total_time,
                mean_time,
                rows
            FROM pg_stat_statements 
            WHERE query LIKE '%' || current_schema() || '%'
            ORDER BY mean_time DESC 
            LIMIT 10
        ");
        
        return [
            'slow_queries' => $slowQueries,
            'avg_query_time' => collect($slowQueries)->avg('mean_time')
        ];
    }
}
```

### Health Check Endpoints

```php
class TenantHealthController extends Controller
{
    public function checkTenantHealth(Tenant $tenant)
    {
        $health = [
            'tenant_id' => $tenant->id,
            'schema_name' => $tenant->schema_name,
            'status' => 'healthy',
            'checks' => []
        ];
        
        try {
            // Check schema exists
            $schemaExists = DB::select(
                "SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?",
                [$tenant->schema_name]
            );
            
            $health['checks']['schema_exists'] = !empty($schemaExists);
            
            // Check tables exist
            $this->tenantService->executeInTenantContext($tenant, function() use (&$health) {
                $requiredTables = ['students', 'courses', 'enrollments', 'grades', 'activity_logs'];
                
                foreach ($requiredTables as $table) {
                    $health['checks']['table_' . $table] = Schema::hasTable($table);
                }
                
                // Check data integrity
                $health['checks']['data_integrity'] = $this->checkDataIntegrity();
                
                // Check recent activity
                $recentActivity = ActivityLog::where('created_at', '>=', now()->subHours(24))->count();
                $health['checks']['recent_activity'] = $recentActivity > 0;
            });
            
        } catch (Exception $e) {
            $health['status'] = 'unhealthy';
            $health['error'] = $e->getMessage();
        }
        
        // Determine overall health
        $failedChecks = array_filter($health['checks'], fn($check) => !$check);
        if (!empty($failedChecks)) {
            $health['status'] = 'degraded';
            $health['failed_checks'] = array_keys($failedChecks);
        }
        
        return response()->json($health);
    }
}
```

## Cost & Resource Management

### Resource Usage Tracking

```php
class TenantResourceTracker
{
    public function trackResourceUsage(Tenant $tenant): array
    {
        return $this->tenantService->executeInTenantContext($tenant, function() use ($tenant) {
            $usage = [
                'tenant_id' => $tenant->id,
                'period' => now()->format('Y-m'),
                'storage' => $this->calculateStorageUsage(),
                'queries' => $this->calculateQueryUsage(),
                'api_calls' => $this->calculateApiUsage(),
                'users' => $this->calculateUserMetrics()
            ];
            
            // Store usage data for billing
            TenantUsage::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'period' => $usage['period']
                ],
                $usage
            );
            
            return $usage;
        });
    }
    
    protected function calculateStorageUsage(): array
    {
        $tables = DB::select("
            SELECT 
                tablename,
                pg_total_relation_size(schemaname||'.'||tablename) as size_bytes,
                n_tup_ins + n_tup_upd + n_tup_del as total_operations
            FROM pg_tables t
            LEFT JOIN pg_stat_user_tables s ON t.tablename = s.relname
            WHERE schemaname = current_schema()
        ");
        
        return [
            'total_bytes' => array_sum(array_column($tables, 'size_bytes')),
            'total_operations' => array_sum(array_column($tables, 'total_operations')),
            'tables' => $tables
        ];
    }
    
    public function generateUsageReport(Tenant $tenant, string $period = null): array
    {
        $period = $period ?? now()->format('Y-m');
        
        $usage = TenantUsage::where('tenant_id', $tenant->id)
            ->where('period', $period)
            ->first();
            
        if (!$usage) {
            return ['error' => 'No usage data found for period'];
        }
        
        // Calculate costs based on usage
        $costs = [
            'storage_cost' => ($usage->storage['total_bytes'] / 1024 / 1024 / 1024) * 0.10, // $0.10 per GB
            'query_cost' => $usage->queries['total_queries'] * 0.001, // $0.001 per query
            'api_cost' => $usage->api_calls['total_calls'] * 0.0001, // $0.0001 per API call
        ];
        
        return [
            'tenant' => $tenant->name,
            'period' => $period,
            'usage' => $usage->toArray(),
            'costs' => $costs,
            'total_cost' => array_sum($costs)
        ];
    }
}
```

## Compliance & Audit Requirements

### Data Retention Policies

```php
class TenantDataRetentionService
{
    public function applyRetentionPolicies(Tenant $tenant)
    {
        return $this->tenantService->executeInTenantContext($tenant, function() use ($tenant) {
            $policies = $tenant->data_retention_policies ?? $this->getDefaultPolicies();
            $results = [];
            
            foreach ($policies as $table => $policy) {
                $results[$table] = $this->applyTableRetentionPolicy($table, $policy);
            }
            
            // Log retention activity
            ActivityLog::create([
                'log_name' => 'data_retention',
                'description' => 'Applied data retention policies',
                'properties' => $results,
                'event' => 'retention_applied'
            ]);
            
            return $results;
        });
    }
    
    protected function applyTableRetentionPolicy(string $table, array $policy): array
    {
        $retentionDays = $policy['retention_days'];
        $dateColumn = $policy['date_column'] ?? 'created_at';
        $archiveBeforeDelete = $policy['archive'] ?? false;
        
        $cutoffDate = now()->subDays($retentionDays);
        
        $query = DB::table($table)->where($dateColumn, '<', $cutoffDate);
        $recordsToDelete = $query->count();
        
        if ($recordsToDelete === 0) {
            return ['deleted' => 0, 'archived' => 0];
        }
        
        $archived = 0;
        if ($archiveBeforeDelete) {
            $archived = $this->archiveRecords($table, $query->get());
        }
        
        $deleted = $query->delete();
        
        return [
            'deleted' => $deleted,
            'archived' => $archived,
            'cutoff_date' => $cutoffDate->toDateString()
        ];
    }
    
    protected function getDefaultPolicies(): array
    {
        return [
            'activity_logs' => [
                'retention_days' => 365,
                'date_column' => 'created_at',
                'archive' => true
            ],
            'grades' => [
                'retention_days' => 2555, // 7 years
                'date_column' => 'created_at',
                'archive' => true
            ]
        ];
    }
}
```

### Audit Trail Implementation

```php
class TenantAuditService
{
    public function generateAuditReport(Tenant $tenant, array $options = []): array
    {
        return $this->tenantService->executeInTenantContext($tenant, function() use ($options) {
            $startDate = $options['start_date'] ?? now()->subMonth();
            $endDate = $options['end_date'] ?? now();
            
            return [
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString()
                ],
                'user_activities' => $this->getUserActivities($startDate, $endDate),
                'data_changes' => $this->getDataChanges($startDate, $endDate),
                'system_events' => $this->getSystemEvents($startDate, $endDate),
                'security_events' => $this->getSecurityEvents($startDate, $endDate)
            ];
        });
    }
    
    protected function getUserActivities($startDate, $endDate): array
    {
        return ActivityLog::whereBetween('created_at', [$startDate, $endDate])
            ->where('causer_type', 'App\\Models\\User')
            ->selectRaw('causer_id, COUNT(*) as activity_count, MAX(created_at) as last_activity')
            ->groupBy('causer_id')
            ->with('causer:id,name,email')
            ->get()
            ->toArray();
    }
    
    protected function getDataChanges($startDate, $endDate): array
    {
        return ActivityLog::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('event', ['created', 'updated', 'deleted'])
            ->selectRaw('subject_type, event, COUNT(*) as count')
            ->groupBy(['subject_type', 'event'])
            ->get()
            ->toArray();
    }
}
```

## Development & Testing Strategies

### Tenant-Aware Testing

```php
// Base test class for tenant-aware tests
abstract class TenantTestCase extends TestCase
{
    protected Tenant $testTenant;
    protected TenantContextService $tenantService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenantService = app(TenantContextService::class);
        $this->testTenant = $this->createTestTenant();
        $this->tenantService->setTenant($this->testTenant);
    }
    
    protected function createTestTenant(): Tenant
    {
        $tenant = Tenant::factory()->create([
            'schema_name' => 'test_tenant_' . uniqid(),
            'is_schema_migrated' => true
        ]);
        
        // Create tenant schema and run migrations
        $this->tenantService->createTenantSchema($tenant->schema_name);
        $this->tenantService->runTenantMigrations($tenant->schema_name);
        
        return $tenant;
    }
    
    protected function tearDown(): void
    {
        // Clean up test tenant schema
        if ($this->testTenant) {
            DB::statement("DROP SCHEMA IF EXISTS {$this->testTenant->schema_name} CASCADE");
            $this->testTenant->delete();
        }
        
        parent::tearDown();
    }
    
    protected function actingAsTenantUser($user = null)
    {
        $user = $user ?? User::factory()->create();
        
        // Associate user with test tenant
        $this->testTenant->users()->attach($user->id, [
            'role' => 'admin',
            'permissions' => ['*']
        ]);
        
        return $this->actingAs($user);
    }
}
```

### Multi-Tenant Integration Tests

```php
class MultiTenantIntegrationTest extends TenantTestCase
{
    /** @test */
    public function it_isolates_data_between_tenants()
    {
        // Create second tenant
        $tenant2 = Tenant::factory()->create([
            'schema_name' => 'test_tenant_2_' . uniqid(),
            'is_schema_migrated' => true
        ]);
        
        $this->tenantService->createTenantSchema($tenant2->schema_name);
        $this->tenantService->runTenantMigrations($tenant2->schema_name);
        
        // Create data in first tenant
        $this->tenantService->setTenant($this->testTenant);
        $student1 = Student::factory()->create(['first_name' => 'John']);
        
        // Create data in second tenant
        $this->tenantService->setTenant($tenant2);
        $student2 = Student::factory()->create(['first_name' => 'Jane']);
        
        // Verify isolation
        $this->tenantService->setTenant($this->testTenant);
        $this->assertEquals(1, Student::count());
        $this->assertEquals('John', Student::first()->first_name);
        
        $this->tenantService->setTenant($tenant2);
        $this->assertEquals(1, Student::count());
        $this->assertEquals('Jane', Student::first()->first_name);
        
        // Cleanup
        DB::statement("DROP SCHEMA IF EXISTS {$tenant2->schema_name} CASCADE");
        $tenant2->delete();
    }
}
```

## Migration Complexity & Rollback Scenarios

### Complex Migration Scenarios

```php
class ComplexMigrationHandler
{
    public function handleLargeTenantMigration(Tenant $tenant, array $options = [])
    {
        $batchSize = $options['batch_size'] ?? 1000;
        $maxExecutionTime = $options['max_execution_time'] ?? 3600; // 1 hour
        $startTime = time();
        
        DB::transaction(function() use ($tenant, $batchSize, $maxExecutionTime, $startTime) {
            $tables = ['students', 'courses', 'enrollments', 'grades', 'activity_logs'];
            
            foreach ($tables as $table) {
                $this->migrateLargeTable($tenant, $table, $batchSize, $maxExecutionTime, $startTime);
                
                // Check if we're approaching time limit
                if (time() - $startTime > $maxExecutionTime * 0.9) {
                    throw new Exception('Migration approaching time limit, stopping for safety');
                }
            }
        });
    }
    
    protected function migrateLargeTable(Tenant $tenant, string $table, int $batchSize, int $maxExecutionTime, int $startTime)
    {
        $totalRecords = DB::table($table)->where('tenant_id', $tenant->id)->count();
        $processedRecords = 0;
        
        while ($processedRecords < $totalRecords) {
            $batch = DB::table($table)
                ->where('tenant_id', $tenant->id)
                ->offset($processedRecords)
                ->limit($batchSize)
                ->get();
                
            if ($batch->isEmpty()) {
                break;
            }
            
            foreach ($batch as $record) {
                $recordArray = (array) $record;
                unset($recordArray['tenant_id']);
                
                DB::table("{$tenant->schema_name}.{$table}")->insert($recordArray);
            }
            
            $processedRecords += $batch->count();
            
            // Progress reporting
            $progress = ($processedRecords / $totalRecords) * 100;
            Log::info("Migration progress for {$table}: {$progress}%");
            
            // Time check
            if (time() - $startTime > $maxExecutionTime * 0.8) {
                Log::warning('Migration approaching time limit');
                break;
            }
        }
    }
}
```

### Rollback Implementation

```php
class TenantRollbackService
{
    public function rollbackTenantToHybrid(Tenant $tenant, array $options = [])
    {
        if (!$tenant->is_schema_migrated) {
            throw new Exception('Tenant is not schema-migrated, cannot rollback');
        }
        
        $backupId = $options['backup_id'] ?? $this->createPreRollbackBackup($tenant);
        
        DB::transaction(function() use ($tenant) {
            // Copy data back from schema to main tables with tenant_id
            $this->copySchemaDataToMainTables($tenant);
            
            // Update tenant record
            $tenant->update([
                'schema_name' => null,
                'is_schema_migrated' => false,
                'schema_migrated_at' => null,
                'rollback_completed_at' => now()
            ]);
            
            // Drop tenant schema
            DB::statement("DROP SCHEMA IF EXISTS {$tenant->schema_name} CASCADE");
        });
        
        return $backupId;
    }
    
    protected function copySchemaDataToMainTables(Tenant $tenant)
    {
        $tables = ['students', 'courses', 'enrollments', 'grades', 'activity_logs'];
        
        foreach ($tables as $table) {
            $schemaTable = "{$tenant->schema_name}.{$table}";
            
            // Get all records from schema table
            $records = DB::table($schemaTable)->get();
            
            foreach ($records as $record) {
                $recordArray = (array) $record;
                $recordArray['tenant_id'] = $tenant->id; // Add tenant_id back
                
                DB::table($table)->insert($recordArray);
            }
        }
    }
}
```

This comprehensive document covers the advanced considerations for implementing and maintaining schema-based multi-tenancy in the Alumate system. Each section provides practical examples and implementation strategies for real-world scenarios.