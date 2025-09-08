<?php
// ABOUTME: Comprehensive test suite for hybrid tenancy system functionality
// ABOUTME: Tests tenant context resolution, schema operations, cross-tenant sync, and data isolation

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\GlobalUser;
use App\Models\UserTenantMembership;
use App\Models\GlobalCourse;
use App\Models\TenantCourseOffering;
use App\Services\TenantContextService;
use App\Services\TenantSchemaService;
use App\Services\CrossTenantSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenancySystemTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected TenantContextService $tenantContext;
    protected TenantSchemaService $tenantSchema;
    protected CrossTenantSyncService $syncService;
    protected Tenant $testTenant1;
    protected Tenant $testTenant2;
    protected GlobalUser $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenantContext = app(TenantContextService::class);
        $this->tenantSchema = app(TenantSchemaService::class);
        $this->syncService = app(CrossTenantSyncService::class);
        
        $this->createTestTenants();
        $this->createTestUser();
    }

    protected function tearDown(): void
    {
        // Clean up test schemas
        if (isset($this->testTenant1)) {
            $this->tenantSchema->dropSchema($this->testTenant1->id);
        }
        if (isset($this->testTenant2)) {
            $this->tenantSchema->dropSchema($this->testTenant2->id);
        }
        
        parent::tearDown();
    }

    protected function createTestTenants(): void
    {
        $this->testTenant1 = Tenant::create([
            'name' => 'Test University 1',
            'slug' => 'test-uni-1',
            'domain' => 'test1.example.com',
            'status' => 'active',
            'settings' => [
                'timezone' => 'UTC',
                'locale' => 'en',
                'features' => ['courses', 'assignments', 'grades'],
            ],
        ]);

        $this->testTenant2 = Tenant::create([
            'name' => 'Test University 2',
            'slug' => 'test-uni-2',
            'domain' => 'test2.example.com',
            'status' => 'active',
            'settings' => [
                'timezone' => 'America/New_York',
                'locale' => 'en',
                'features' => ['courses', 'assignments'],
            ],
        ]);
    }

    protected function createTestUser(): void
    {
        $this->testUser = GlobalUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'global_roles' => ['student'],
            'preferences' => [
                'language' => 'en',
                'notifications' => true,
            ],
        ]);

        // Create memberships in both tenants
        UserTenantMembership::create([
            'global_user_id' => $this->testUser->id,
            'tenant_id' => $this->testTenant1->id,
            'roles' => ['student'],
            'status' => 'active',
            'joined_at' => now(),
        ]);

        UserTenantMembership::create([
            'global_user_id' => $this->testUser->id,
            'tenant_id' => $this->testTenant2->id,
            'roles' => ['instructor'],
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    /** @test */
    public function it_can_resolve_tenant_from_subdomain(): void
    {
        $request = $this->createRequestWithSubdomain('test1');
        
        $tenant = $this->tenantContext->resolveFromRequest($request);
        
        $this->assertNotNull($tenant);
        $this->assertEquals($this->testTenant1->id, $tenant->id);
        $this->assertEquals('test-uni-1', $tenant->slug);
    }

    /** @test */
    public function it_can_resolve_tenant_from_header(): void
    {
        $request = $this->createRequest();
        $request->headers->set('X-Tenant-ID', $this->testTenant2->id);
        
        $tenant = $this->tenantContext->resolveFromRequest($request);
        
        $this->assertNotNull($tenant);
        $this->assertEquals($this->testTenant2->id, $tenant->id);
    }

    /** @test */
    public function it_can_resolve_tenant_from_parameter(): void
    {
        $request = $this->createRequest();
        $request->merge(['tenant_id' => $this->testTenant1->id]);
        
        $tenant = $this->tenantContext->resolveFromRequest($request);
        
        $this->assertNotNull($tenant);
        $this->assertEquals($this->testTenant1->id, $tenant->id);
    }

    /** @test */
    public function it_caches_tenant_context(): void
    {
        $request = $this->createRequestWithSubdomain('test1');
        
        // First resolution
        $tenant1 = $this->tenantContext->resolveFromRequest($request);
        
        // Second resolution should use cache
        $tenant2 = $this->tenantContext->resolveFromRequest($request);
        
        $this->assertEquals($tenant1->id, $tenant2->id);
        
        // Verify cache was used
        $cacheKey = 'tenant:context:' . md5('test1.example.com');
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_can_switch_tenant_context(): void
    {
        // Set initial context
        $this->tenantContext->setCurrentTenant($this->testTenant1);
        $this->assertEquals($this->testTenant1->id, $this->tenantContext->getCurrentTenant()->id);
        
        // Switch context
        $this->tenantContext->setCurrentTenant($this->testTenant2);
        $this->assertEquals($this->testTenant2->id, $this->tenantContext->getCurrentTenant()->id);
        
        // Verify switch history
        $history = $this->tenantContext->getSwitchHistory();
        $this->assertCount(2, $history);
        $this->assertEquals($this->testTenant1->id, $history[0]['tenant_id']);
        $this->assertEquals($this->testTenant2->id, $history[1]['tenant_id']);
    }

    /** @test */
    public function it_validates_user_access_to_tenant(): void
    {
        // User has access to both test tenants
        $this->assertTrue(
            $this->tenantContext->validateUserAccess($this->testUser, $this->testTenant1)
        );
        $this->assertTrue(
            $this->tenantContext->validateUserAccess($this->testUser, $this->testTenant2)
        );
        
        // Create tenant without user membership
        $restrictedTenant = Tenant::create([
            'name' => 'Restricted University',
            'slug' => 'restricted',
            'domain' => 'restricted.example.com',
            'status' => 'active',
        ]);
        
        $this->assertFalse(
            $this->tenantContext->validateUserAccess($this->testUser, $restrictedTenant)
        );
    }

    /** @test */
    public function it_can_create_tenant_schema(): void
    {
        $schemaName = $this->tenantSchema->createSchema($this->testTenant1->id);
        
        $this->assertNotNull($schemaName);
        $this->assertEquals('tenant_' . $this->testTenant1->id, $schemaName);
        
        // Verify schema exists in database
        $schemas = DB::select(
            "SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?",
            [$schemaName]
        );
        $this->assertCount(1, $schemas);
    }

    /** @test */
    public function it_can_migrate_tenant_schema(): void
    {
        $schemaName = $this->tenantSchema->createSchema($this->testTenant1->id);
        $this->tenantSchema->migrateSchema($this->testTenant1->id);
        
        // Verify required tables exist
        $requiredTables = config('tenancy.schema.required_tables');
        
        foreach ($requiredTables as $table) {
            $exists = DB::select(
                "SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name = ?",
                [$schemaName, $table]
            );
            $this->assertCount(1, $exists, "Table {$table} should exist in schema {$schemaName}");
        }
    }

    /** @test */
    public function it_can_switch_database_schema(): void
    {
        // Create and migrate schemas
        $schema1 = $this->tenantSchema->createSchema($this->testTenant1->id);
        $schema2 = $this->tenantSchema->createSchema($this->testTenant2->id);
        $this->tenantSchema->migrateSchema($this->testTenant1->id);
        $this->tenantSchema->migrateSchema($this->testTenant2->id);
        
        // Switch to tenant 1 schema
        $this->tenantSchema->switchToSchema($this->testTenant1->id);
        $currentSchema = DB::select('SELECT current_schema()')[0]->current_schema;
        $this->assertEquals($schema1, $currentSchema);
        
        // Switch to tenant 2 schema
        $this->tenantSchema->switchToSchema($this->testTenant2->id);
        $currentSchema = DB::select('SELECT current_schema()')[0]->current_schema;
        $this->assertEquals($schema2, $currentSchema);
    }

    /** @test */
    public function it_validates_schema_integrity(): void
    {
        $schemaName = $this->tenantSchema->createSchema($this->testTenant1->id);
        $this->tenantSchema->migrateSchema($this->testTenant1->id);
        
        $isValid = $this->tenantSchema->validateSchema($this->testTenant1->id);
        $this->assertTrue($isValid);
        
        // Drop a required table to make schema invalid
        DB::statement("DROP TABLE {$schemaName}.users");
        
        $isValid = $this->tenantSchema->validateSchema($this->testTenant1->id);
        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_can_sync_global_user_data(): void
    {
        // Create schemas
        $this->tenantSchema->createSchema($this->testTenant1->id);
        $this->tenantSchema->createSchema($this->testTenant2->id);
        $this->tenantSchema->migrateSchema($this->testTenant1->id);
        $this->tenantSchema->migrateSchema($this->testTenant2->id);
        
        // Sync user to tenant schemas
        $result = $this->syncService->syncUserToTenants($this->testUser);
        
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['synced_count']);
        
        // Verify user exists in both tenant schemas
        $this->tenantSchema->switchToSchema($this->testTenant1->id);
        $user1 = DB::table('users')->where('global_user_id', $this->testUser->id)->first();
        $this->assertNotNull($user1);
        
        $this->tenantSchema->switchToSchema($this->testTenant2->id);
        $user2 = DB::table('users')->where('global_user_id', $this->testUser->id)->first();
        $this->assertNotNull($user2);
    }

    /** @test */
    public function it_can_sync_global_course_data(): void
    {
        // Create a global course
        $globalCourse = GlobalCourse::create([
            'title' => 'Introduction to Computer Science',
            'code' => 'CS101',
            'description' => 'Basic computer science concepts',
            'level' => 'undergraduate',
            'subject_area' => 'Computer Science',
            'credits' => 3,
            'prerequisites' => [],
            'learning_outcomes' => [
                'Understand basic programming concepts',
                'Learn problem-solving techniques',
            ],
            'tags' => ['programming', 'fundamentals'],
            'created_by' => $this->testUser->id,
        ]);
        
        // Create tenant offering
        $offering = TenantCourseOffering::create([
            'tenant_id' => $this->testTenant1->id,
            'global_course_id' => $globalCourse->id,
            'local_title' => 'CS 101: Intro to Programming',
            'instructor_id' => $this->testUser->id,
            'semester' => 'Fall 2024',
            'max_enrollment' => 30,
            'status' => 'active',
            'customizations' => [
                'grading_scale' => 'A-F',
                'attendance_required' => true,
            ],
        ]);
        
        // Create schemas and sync
        $this->tenantSchema->createSchema($this->testTenant1->id);
        $this->tenantSchema->migrateSchema($this->testTenant1->id);
        
        $result = $this->syncService->syncCourseToTenant($globalCourse, $this->testTenant1->id);
        
        $this->assertTrue($result['success']);
        
        // Verify course exists in tenant schema
        $this->tenantSchema->switchToSchema($this->testTenant1->id);
        $course = DB::table('courses')->where('global_course_id', $globalCourse->id)->first();
        $this->assertNotNull($course);
        $this->assertEquals('CS 101: Intro to Programming', $course->title);
    }

    /** @test */
    public function it_handles_sync_conflicts(): void
    {
        // Create schemas
        $this->tenantSchema->createSchema($this->testTenant1->id);
        $this->tenantSchema->migrateSchema($this->testTenant1->id);
        
        // Sync user initially
        $this->syncService->syncUserToTenants($this->testUser);
        
        // Modify user in tenant schema
        $this->tenantSchema->switchToSchema($this->testTenant1->id);
        DB::table('users')
            ->where('global_user_id', $this->testUser->id)
            ->update([
                'name' => 'Modified Name',
                'updated_at' => now()->addHour(),
            ]);
        
        // Modify global user
        $this->testUser->update([
            'name' => 'Global Modified Name',
        ]);
        
        // Sync again - should detect conflict
        $result = $this->syncService->syncUserToTenants($this->testUser);
        
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['conflicts_resolved']);
        
        // Verify conflict resolution (latest_wins strategy)
        $this->tenantSchema->switchToSchema($this->testTenant1->id);
        $user = DB::table('users')->where('global_user_id', $this->testUser->id)->first();
        $this->assertEquals('Modified Name', $user->name); // Tenant version should win (newer)
    }

    /** @test */
    public function it_logs_sync_operations(): void
    {
        $this->tenantSchema->createSchema($this->testTenant1->id);
        $this->tenantSchema->migrateSchema($this->testTenant1->id);
        
        $result = $this->syncService->syncUserToTenants($this->testUser);
        
        // Verify sync log was created
        $log = DB::table('data_sync_logs')
            ->where('operation_type', 'user_sync')
            ->where('source_id', $this->testUser->id)
            ->first();
        
        $this->assertNotNull($log);
        $this->assertEquals('completed', $log->status);
        $this->assertNotNull($log->operation_data);
    }

    /** @test */
    public function it_enforces_tenant_data_isolation(): void
    {
        // Create schemas and add test data
        $this->tenantSchema->createSchema($this->testTenant1->id);
        $this->tenantSchema->createSchema($this->testTenant2->id);
        $this->tenantSchema->migrateSchema($this->testTenant1->id);
        $this->tenantSchema->migrateSchema($this->testTenant2->id);
        
        // Add data to tenant 1
        $this->tenantSchema->switchToSchema($this->testTenant1->id);
        DB::table('courses')->insert([
            'title' => 'Tenant 1 Course',
            'code' => 'T1C001',
            'description' => 'Course for tenant 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Add data to tenant 2
        $this->tenantSchema->switchToSchema($this->testTenant2->id);
        DB::table('courses')->insert([
            'title' => 'Tenant 2 Course',
            'code' => 'T2C001',
            'description' => 'Course for tenant 2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Verify isolation - tenant 1 should only see its data
        $this->tenantSchema->switchToSchema($this->testTenant1->id);
        $courses1 = DB::table('courses')->get();
        $this->assertCount(1, $courses1);
        $this->assertEquals('Tenant 1 Course', $courses1[0]->title);
        
        // Verify isolation - tenant 2 should only see its data
        $this->tenantSchema->switchToSchema($this->testTenant2->id);
        $courses2 = DB::table('courses')->get();
        $this->assertCount(1, $courses2);
        $this->assertEquals('Tenant 2 Course', $courses2[0]->title);
    }

    /** @test */
    public function it_can_generate_analytics_across_tenants(): void
    {
        // Create schemas and add test data
        $this->tenantSchema->createSchema($this->testTenant1->id);
        $this->tenantSchema->createSchema($this->testTenant2->id);
        $this->tenantSchema->migrateSchema($this->testTenant1->id);
        $this->tenantSchema->migrateSchema($this->testTenant2->id);
        
        // Add users to tenant schemas
        $this->syncService->syncUserToTenants($this->testUser);
        
        // Generate analytics
        $result = $this->syncService->generateAnalytics();
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('metrics_generated', $result);
        
        // Verify analytics data was created
        $analytics = DB::table('super_admin_analytics')
            ->where('metric_type', 'user_count')
            ->where('period', 'daily')
            ->first();
        
        $this->assertNotNull($analytics);
        $this->assertGreaterThan(0, $analytics->value);
    }

    /** @test */
    public function it_handles_schema_backup_and_restore(): void
    {
        $schemaName = $this->tenantSchema->createSchema($this->testTenant1->id);
        $this->tenantSchema->migrateSchema($this->testTenant1->id);
        
        // Add test data
        $this->tenantSchema->switchToSchema($this->testTenant1->id);
        DB::table('courses')->insert([
            'title' => 'Test Course',
            'code' => 'TEST001',
            'description' => 'Test course for backup',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create backup
        $backupResult = $this->tenantSchema->backupSchema($this->testTenant1->id);
        $this->assertTrue($backupResult['success']);
        $this->assertNotNull($backupResult['backup_file']);
        
        // Verify backup file exists
        $this->assertFileExists($backupResult['backup_file']);
        
        // Drop and recreate schema
        $this->tenantSchema->dropSchema($this->testTenant1->id);
        $this->tenantSchema->createSchema($this->testTenant1->id);
        $this->tenantSchema->migrateSchema($this->testTenant1->id);
        
        // Verify data is gone
        $this->tenantSchema->switchToSchema($this->testTenant1->id);
        $courses = DB::table('courses')->get();
        $this->assertCount(0, $courses);
        
        // Restore from backup
        $restoreResult = $this->tenantSchema->restoreSchema(
            $this->testTenant1->id,
            $backupResult['backup_file']
        );
        $this->assertTrue($restoreResult['success']);
        
        // Verify data is restored
        $courses = DB::table('courses')->get();
        $this->assertCount(1, $courses);
        $this->assertEquals('Test Course', $courses[0]->title);
        
        // Clean up backup file
        unlink($backupResult['backup_file']);
    }

    protected function createRequest(): \Illuminate\Http\Request
    {
        return \Illuminate\Http\Request::create('/', 'GET');
    }

    protected function createRequestWithSubdomain(string $subdomain): \Illuminate\Http\Request
    {
        $request = \Illuminate\Http\Request::create('/', 'GET');
        $request->headers->set('Host', $subdomain . '.example.com');
        return $request;
    }
}