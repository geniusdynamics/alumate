<?php
// ABOUTME: Unit tests for CrossTenantSyncService class functionality
// ABOUTME: Tests cross-tenant data synchronization, conflict resolution, and monitoring

namespace Tests\Unit;

use App\Models\Tenant;
use App\Models\GlobalUser;
use App\Models\GlobalCourse;
use App\Models\DataSyncLog;
use App\Services\CrossTenantSyncService;
use App\Services\TenantSchemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Mockery;

class CrossTenantSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CrossTenantSyncService $service;
    protected TenantSchemaService $schemaService;
    protected Tenant $sourceTenant;
    protected Tenant $targetTenant;
    protected GlobalUser $globalUser;
    protected GlobalCourse $globalCourse;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new CrossTenantSyncService();
        $this->schemaService = new TenantSchemaService();
        $this->createTestData();
    }

    protected function tearDown(): void
    {
        // Clean up test schemas
        try {
            $this->schemaService->dropSchema($this->sourceTenant->id);
            $this->schemaService->dropSchema($this->targetTenant->id);
        } catch (\Exception $e) {
            // Schemas might not exist, ignore
        }
        
        parent::tearDown();
    }

    protected function createTestData(): void
    {
        $this->sourceTenant = Tenant::create([
            'name' => 'Source University',
            'slug' => 'source-uni',
            'domain' => 'source.example.com',
            'status' => 'active',
            'settings' => ['timezone' => 'UTC'],
        ]);

        $this->targetTenant = Tenant::create([
            'name' => 'Target University',
            'slug' => 'target-uni',
            'domain' => 'target.example.com',
            'status' => 'active',
            'settings' => ['timezone' => 'UTC'],
        ]);

        $this->globalUser = GlobalUser::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'preferences' => ['theme' => 'light'],
        ]);

        $this->globalCourse = GlobalCourse::create([
            'title' => 'Test Course',
            'description' => 'A test course',
            'level' => 'beginner',
            'subject_area' => 'technology',
            'duration_hours' => 40,
            'status' => 'published',
            'created_by' => $this->globalUser->id,
            'metadata' => ['difficulty' => 'easy'],
        ]);

        // Set up tenant schemas
        $this->schemaService->createSchema($this->sourceTenant->id);
        $this->schemaService->migrateSchema($this->sourceTenant->id);
        $this->schemaService->createSchema($this->targetTenant->id);
        $this->schemaService->migrateSchema($this->targetTenant->id);
    }

    /** @test */
    public function it_syncs_user_data_between_tenants(): void
    {
        // Add user to source tenant
        $this->schemaService->switchToSchema($this->sourceTenant->id);
        DB::table('users')->insert([
            'global_user_id' => $this->globalUser->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->syncUserData(
            $this->globalUser->id,
            $this->sourceTenant->id,
            $this->targetTenant->id
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('user_sync', $result['operation']);

        // Verify user exists in target tenant
        $this->schemaService->switchToSchema($this->targetTenant->id);
        $user = DB::table('users')->where('global_user_id', $this->globalUser->id)->first();
        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user->name);
    }

    /** @test */
    public function it_syncs_course_data_between_tenants(): void
    {
        // Add course to source tenant
        $this->schemaService->switchToSchema($this->sourceTenant->id);
        DB::table('courses')->insert([
            'global_course_id' => $this->globalCourse->id,
            'title' => 'Test Course',
            'description' => 'A test course',
            'instructor_id' => 1,
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->syncCourseData(
            $this->globalCourse->id,
            $this->sourceTenant->id,
            $this->targetTenant->id
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('course_sync', $result['operation']);

        // Verify course exists in target tenant
        $this->schemaService->switchToSchema($this->targetTenant->id);
        $course = DB::table('courses')->where('global_course_id', $this->globalCourse->id)->first();
        $this->assertNotNull($course);
        $this->assertEquals('Test Course', $course->title);
    }

    /** @test */
    public function it_handles_sync_conflicts_with_merge_strategy(): void
    {
        // Add different versions of user in both tenants
        $this->schemaService->switchToSchema($this->sourceTenant->id);
        DB::table('users')->insert([
            'global_user_id' => $this->globalUser->id,
            'name' => 'Source User Name',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $this->schemaService->switchToSchema($this->targetTenant->id);
        DB::table('users')->insert([
            'global_user_id' => $this->globalUser->id,
            'name' => 'Target User Name',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->syncUserData(
            $this->globalUser->id,
            $this->sourceTenant->id,
            $this->targetTenant->id,
            ['conflict_resolution' => 'merge']
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('conflicts_resolved', $result);
        $this->assertGreaterThan(0, $result['conflicts_resolved']);
    }

    /** @test */
    public function it_handles_sync_conflicts_with_source_wins_strategy(): void
    {
        // Add different versions of user in both tenants
        $this->schemaService->switchToSchema($this->sourceTenant->id);
        DB::table('users')->insert([
            'global_user_id' => $this->globalUser->id,
            'name' => 'Source User Name',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->schemaService->switchToSchema($this->targetTenant->id);
        DB::table('users')->insert([
            'global_user_id' => $this->globalUser->id,
            'name' => 'Target User Name',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->syncUserData(
            $this->globalUser->id,
            $this->sourceTenant->id,
            $this->targetTenant->id,
            ['conflict_resolution' => 'source_wins']
        );

        $this->assertTrue($result['success']);

        // Verify target has source data
        $this->schemaService->switchToSchema($this->targetTenant->id);
        $user = DB::table('users')->where('global_user_id', $this->globalUser->id)->first();
        $this->assertEquals('Source User Name', $user->name);
    }

    /** @test */
    public function it_logs_sync_operations(): void
    {
        $this->schemaService->switchToSchema($this->sourceTenant->id);
        DB::table('users')->insert([
            'global_user_id' => $this->globalUser->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->service->syncUserData(
            $this->globalUser->id,
            $this->sourceTenant->id,
            $this->targetTenant->id
        );

        // Verify sync log was created
        $this->assertDatabaseHas('data_sync_logs', [
            'operation_type' => 'user_sync',
            'source_tenant_id' => $this->sourceTenant->id,
            'target_tenant_id' => $this->targetTenant->id,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_handles_batch_sync_operations(): void
    {
        // Add multiple users to source tenant
        $this->schemaService->switchToSchema($this->sourceTenant->id);
        for ($i = 1; $i <= 5; $i++) {
            $globalUser = GlobalUser::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => bcrypt('password'),
                'status' => 'active',
            ]);

            DB::table('users')->insert([
                'global_user_id' => $globalUser->id,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $result = $this->service->batchSyncUsers(
            $this->sourceTenant->id,
            $this->targetTenant->id,
            ['batch_size' => 3]
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['total_processed']);
        $this->assertGreaterThanOrEqual(2, $result['batches_processed']);

        // Verify all users were synced
        $this->schemaService->switchToSchema($this->targetTenant->id);
        $userCount = DB::table('users')->count();
        $this->assertEquals(5, $userCount);
    }

    /** @test */
    public function it_handles_sync_failures_with_retry(): void
    {
        // Mock database failure
        DB::shouldReceive('table')
            ->with('users')
            ->once()
            ->andThrow(new \Exception('Database connection failed'));

        DB::shouldReceive('table')
            ->with('users')
            ->once()
            ->andReturn(DB::table('users')); // Success on retry

        $this->schemaService->switchToSchema($this->sourceTenant->id);
        DB::table('users')->insert([
            'global_user_id' => $this->globalUser->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->syncUserData(
            $this->globalUser->id,
            $this->sourceTenant->id,
            $this->targetTenant->id,
            ['max_retries' => 2]
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('retries_attempted', $result);
    }

    /** @test */
    public function it_validates_data_integrity_after_sync(): void
    {
        $this->schemaService->switchToSchema($this->sourceTenant->id);
        DB::table('users')->insert([
            'global_user_id' => $this->globalUser->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->syncUserData(
            $this->globalUser->id,
            $this->sourceTenant->id,
            $this->targetTenant->id,
            ['validate_integrity' => true]
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('integrity_check', $result);
        $this->assertTrue($result['integrity_check']['passed']);
    }

    /** @test */
    public function it_detects_data_integrity_violations(): void
    {
        $this->schemaService->switchToSchema($this->sourceTenant->id);
        DB::table('users')->insert([
            'global_user_id' => $this->globalUser->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sync data
        $this->service->syncUserData(
            $this->globalUser->id,
            $this->sourceTenant->id,
            $this->targetTenant->id
        );

        // Corrupt target data
        $this->schemaService->switchToSchema($this->targetTenant->id);
        DB::table('users')
            ->where('global_user_id', $this->globalUser->id)
            ->update(['email' => 'corrupted@example.com']);

        $integrityResult = $this->service->validateDataIntegrity(
            $this->globalUser->id,
            $this->sourceTenant->id,
            $this->targetTenant->id,
            'users'
        );

        $this->assertFalse($integrityResult['passed']);
        $this->assertArrayHasKey('violations', $integrityResult);
        $this->assertGreaterThan(0, count($integrityResult['violations']));
    }

    /** @test */
    public function it_monitors_sync_performance(): void
    {
        $this->schemaService->switchToSchema($this->sourceTenant->id);
        DB::table('users')->insert([
            'global_user_id' => $this->globalUser->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->syncUserData(
            $this->globalUser->id,
            $this->sourceTenant->id,
            $this->targetTenant->id,
            ['monitor_performance' => true]
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('performance_metrics', $result);
        $this->assertArrayHasKey('execution_time', $result['performance_metrics']);
        $this->assertArrayHasKey('memory_usage', $result['performance_metrics']);
        $this->assertArrayHasKey('records_processed', $result['performance_metrics']);
    }

    /** @test */
    public function it_gets_sync_statistics(): void
    {
        // Create some sync logs
        DataSyncLog::create([
            'operation_type' => 'user_sync',
            'source_tenant_id' => $this->sourceTenant->id,
            'target_tenant_id' => $this->targetTenant->id,
            'status' => 'completed',
            'records_processed' => 1,
            'execution_time' => 1.5,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour()->addMinutes(2),
        ]);

        DataSyncLog::create([
            'operation_type' => 'course_sync',
            'source_tenant_id' => $this->sourceTenant->id,
            'target_tenant_id' => $this->targetTenant->id,
            'status' => 'failed',
            'error_message' => 'Connection timeout',
            'started_at' => now()->subMinutes(30),
        ]);

        $stats = $this->service->getSyncStatistics($this->sourceTenant->id);

        $this->assertArrayHasKey('total_operations', $stats);
        $this->assertArrayHasKey('successful_operations', $stats);
        $this->assertArrayHasKey('failed_operations', $stats);
        $this->assertArrayHasKey('average_execution_time', $stats);
        $this->assertArrayHasKey('operations_by_type', $stats);
        
        $this->assertEquals(2, $stats['total_operations']);
        $this->assertEquals(1, $stats['successful_operations']);
        $this->assertEquals(1, $stats['failed_operations']);
    }

    /** @test */
    public function it_cleans_up_old_sync_logs(): void
    {
        // Create old sync logs
        DataSyncLog::create([
            'operation_type' => 'user_sync',
            'source_tenant_id' => $this->sourceTenant->id,
            'target_tenant_id' => $this->targetTenant->id,
            'status' => 'completed',
            'started_at' => now()->subDays(35),
            'completed_at' => now()->subDays(35)->addMinutes(2),
        ]);

        DataSyncLog::create([
            'operation_type' => 'user_sync',
            'source_tenant_id' => $this->sourceTenant->id,
            'target_tenant_id' => $this->targetTenant->id,
            'status' => 'completed',
            'started_at' => now()->subDays(5),
            'completed_at' => now()->subDays(5)->addMinutes(2),
        ]);

        $this->assertEquals(2, DataSyncLog::count());

        $result = $this->service->cleanupSyncLogs(['days_to_keep' => 30]);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['deleted_count']);
        $this->assertEquals(1, DataSyncLog::count());
    }

    /** @test */
    public function it_handles_incremental_sync(): void
    {
        // Add initial data
        $this->schemaService->switchToSchema($this->sourceTenant->id);
        DB::table('users')->insert([
            'global_user_id' => $this->globalUser->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        // Initial sync
        $this->service->syncUserData(
            $this->globalUser->id,
            $this->sourceTenant->id,
            $this->targetTenant->id
        );

        // Update source data
        DB::table('users')
            ->where('global_user_id', $this->globalUser->id)
            ->update([
                'name' => 'Updated User',
                'updated_at' => now(),
            ]);

        // Incremental sync
        $result = $this->service->incrementalSyncUsers(
            $this->sourceTenant->id,
            $this->targetTenant->id,
            ['since' => now()->subMinutes(30)]
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['records_processed']);

        // Verify target was updated
        $this->schemaService->switchToSchema($this->targetTenant->id);
        $user = DB::table('users')->where('global_user_id', $this->globalUser->id)->first();
        $this->assertEquals('Updated User', $user->name);
    }

    /** @test */
    public function it_handles_bidirectional_sync(): void
    {
        // Add data to both tenants
        $this->schemaService->switchToSchema($this->sourceTenant->id);
        DB::table('users')->insert([
            'global_user_id' => $this->globalUser->id,
            'name' => 'Source User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $globalUser2 = GlobalUser::create([
            'name' => 'Target User',
            'email' => 'target@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->schemaService->switchToSchema($this->targetTenant->id);
        DB::table('users')->insert([
            'global_user_id' => $globalUser2->id,
            'name' => 'Target User',
            'email' => 'target@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->bidirectionalSyncUsers(
            $this->sourceTenant->id,
            $this->targetTenant->id
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['total_synced']);

        // Verify both users exist in both tenants
        $this->schemaService->switchToSchema($this->sourceTenant->id);
        $this->assertEquals(2, DB::table('users')->count());

        $this->schemaService->switchToSchema($this->targetTenant->id);
        $this->assertEquals(2, DB::table('users')->count());
    }

    /** @test */
    public function it_handles_sync_with_data_transformation(): void
    {
        $this->schemaService->switchToSchema($this->sourceTenant->id);
        DB::table('users')->insert([
            'global_user_id' => $this->globalUser->id,
            'name' => 'test user', // lowercase
            'email' => 'TEST@EXAMPLE.COM', // uppercase
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->syncUserData(
            $this->globalUser->id,
            $this->sourceTenant->id,
            $this->targetTenant->id,
            [
                'transformations' => [
                    'name' => 'title_case',
                    'email' => 'lowercase',
                ]
            ]
        );

        $this->assertTrue($result['success']);

        // Verify transformations were applied
        $this->schemaService->switchToSchema($this->targetTenant->id);
        $user = DB::table('users')->where('global_user_id', $this->globalUser->id)->first();
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }

    /** @test */
    public function it_queues_large_sync_operations(): void
    {
        Queue::fake();

        $result = $this->service->queueBatchSync(
            $this->sourceTenant->id,
            $this->targetTenant->id,
            'users',
            ['batch_size' => 1000]
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('job_id', $result);

        Queue::assertPushed(\App\Jobs\BatchSyncJob::class);
    }
}