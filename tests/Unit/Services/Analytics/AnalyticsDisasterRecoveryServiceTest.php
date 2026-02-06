<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Models\Backup;
use App\Models\RecoveryPlan;
use App\Models\RecoveryPlanExecution;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Analytics\AnalyticsBackupRecoveryService;
use App\Services\Analytics\AnalyticsDisasterRecoveryService;
use App\Services\TenantContextService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AnalyticsDisasterRecoveryServiceTest extends TestCase
{
    private AnalyticsDisasterRecoveryService $service;
    private TenantContextService|MockInterface $tenantContext;
    private AnalyticsBackupRecoveryService|MockInterface $backupService;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();

        $this->tenantContext = Mockery::mock(TenantContextService::class);
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andReturn($this->tenant->id);
        $this->tenantContext->shouldReceive('getCurrentTenant')
            ->andReturn($this->tenant);
        $this->tenantContext->shouldReceive('tenantSchemaExists')
            ->andReturn(true);

        $this->backupService = Mockery::mock(AnalyticsBackupRecoveryService::class);

        Auth::shouldReceive('check')
            ->andReturn(true);
        Auth::shouldReceive('id')
            ->andReturn($this->user->id);

        Storage::shouldReceive('disk')
            ->andReturnSelf();
        Storage::shouldReceive('put')
            ->andReturn(true);
        Storage::shouldReceive('exists')
            ->andReturn(true);
        Storage::shouldReceive('size')
            ->andReturn(1000);
        Storage::shouldReceive('get')
            ->andReturn('{}');
        Storage::shouldReceive('delete')
            ->andReturn(true);

        DB::shouldReceive('connection')
            ->andReturnSelf();
        DB::shouldReceive('getPdo')
            ->andReturn(true);

        Cache::shouldReceive('get')
            ->andReturn([]);
        Cache::shouldReceive('put')
            ->andReturn(true);
        Cache::shouldReceive('remember')
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });
        Cache::shouldReceive('forget')
            ->andReturn(true);

        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();

        $this->service = new AnalyticsDisasterRecoveryService(
            $this->tenantContext,
            $this->backupService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_recovery_plan_returns_recovery_plan(): void
    {
        $result = $this->service->createRecoveryPlan([
            'name' => 'Test Recovery Plan',
            'description' => 'A test recovery plan',
            'type' => AnalyticsDisasterRecoveryService::TYPE_FULL_RECOVERY,
            'priority' => AnalyticsDisasterRecoveryService::PRIORITY_HIGH,
        ]);

        $this->assertInstanceOf(RecoveryPlan::class, $result);
        $this->assertEquals($this->tenant->id, $result->tenant_id);
        $this->assertEquals('Test Recovery Plan', $result->name);
        $this->assertEquals(AnalyticsDisasterRecoveryService::STATUS_DRAFT, $result->status);
        $this->assertEquals(AnalyticsDisasterRecoveryService::TYPE_FULL_RECOVERY, $result->type);
    }

    public function test_create_recovery_plan_with_default_values(): void
    {
        $result = $this->service->createRecoveryPlan([]);

        $this->assertInstanceOf(RecoveryPlan::class, $result);
        $this->assertEquals(AnalyticsDisasterRecoveryService::STATUS_DRAFT, $result->status);
        $this->assertEquals(AnalyticsDisasterRecoveryService::PRIORITY_MEDIUM, $result->priority);
        $this->assertFalse($result->is_automatic);
        $this->assertTrue($result->notify_on_completion);
    }

    public function test_create_recovery_plan_generates_recovery_steps(): void
    {
        $result = $this->service->createRecoveryPlan([
            'name' => 'Test Plan',
            'type' => AnalyticsDisasterRecoveryService::TYPE_FAILOVER,
        ]);

        $steps = $result->steps;
        $this->assertIsArray($steps);
        $this->assertNotEmpty($steps);
        $this->assertGreaterThan(5, count($steps));

        // Verify step structure
        $firstStep = $steps[0];
        $this->assertArrayHasKey('order', $firstStep);
        $this->assertArrayHasKey('name', $firstStep);
        $this->assertArrayHasKey('action', $firstStep);
        $this->assertArrayHasKey('timeout_minutes', $firstStep);
    }

    public function test_create_recovery_plan_includes_configuration(): void
    {
        $result = $this->service->createRecoveryPlan([
            'name' => 'Test Plan',
            'restore_events' => true,
            'clear_existing' => true,
        ]);

        $config = $result->configuration;
        $this->assertIsArray($config);
        $this->assertArrayHasKey('data_restore_options', $config);
        $this->assertArrayHasKey('verification_options', $config);
        $this->assertArrayHasKey('rollback_options', $config);
        $this->assertTrue($config['data_restore_options']['restore_events']);
        $this->assertTrue($config['data_restore_options']['clear_existing']);
    }

    public function test_execute_recovery_plan_creates_execution_record(): void
    {
        $plan = RecoveryPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => AnalyticsDisasterRecoveryService::STATUS_ACTIVE,
            'steps' => [
                [
                    'order' => 1,
                    'name' => 'Preflight Checks',
                    'action' => 'preflight_checks',
                    'timeout_minutes' => 5,
                ],
            ],
        ]);

        $this->backupService->shouldReceive('verifyBackup')
            ->andReturn(['valid' => true, 'errors' => []]);
        $this->backupService->shouldReceive('restoreBackup')
            ->andReturn(new Backup());

        $result = $this->service->executeRecoveryPlan($plan->id);

        $this->assertInstanceOf(RecoveryPlanExecution::class, $result);
        $this->assertEquals($plan->id, $result->recovery_plan_id);
        $this->assertEquals(AnalyticsDisasterRecoveryService::EXECUTION_STATUS_COMPLETED, $result->status);
    }

    public function test_execute_recovery_plan_updates_plan_status(): void
    {
        $plan = RecoveryPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => AnalyticsDisasterRecoveryService::STATUS_ACTIVE,
            'steps' => [
                [
                    'order' => 1,
                    'name' => 'Preflight Checks',
                    'action' => 'preflight_checks',
                    'timeout_minutes' => 5,
                ],
            ],
        ]);

        $this->backupService->shouldReceive('verifyBackup')
            ->andReturn(['valid' => true, 'errors' => []]);
        $this->backupService->shouldReceive('restoreBackup')
            ->andReturn(new Backup());

        $this->service->executeRecoveryPlan($plan->id);

        $plan->refresh();
        $this->assertEquals(AnalyticsDisasterRecoveryService::STATUS_COMPLETED, $plan->status);
    }

    public function test_execute_recovery_plan_fails_on_invalid_plan(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Recovery plan not found');

        $this->service->executeRecoveryPlan(99999);
    }

    public function test_execute_recovery_plan_fails_with_backup_validation_error(): void
    {
        $plan = RecoveryPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => AnalyticsDisasterRecoveryService::STATUS_ACTIVE,
            'backup_id' => 1,
            'steps' => [
                [
                    'order' => 1,
                    'name' => 'Validate Backup',
                    'action' => 'validate_backup',
                    'timeout_minutes' => 5,
                ],
            ],
        ]);

        $this->backupService->shouldReceive('verifyBackup')
            ->andReturn(['valid' => false, 'errors' => ['Backup is corrupted']]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Backup validation failed');

        $this->service->executeRecoveryPlan($plan->id);
    }

    public function test_test_recovery_plan_returns_test_results(): void
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'completed',
            'file_path' => 'test/backup.json',
            'file_size' => 1000,
        ]);

        $plan = RecoveryPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'backup_id' => $backup->id,
            'status' => AnalyticsDisasterRecoveryService::STATUS_ACTIVE,
        ]);

        $this->backupService->shouldReceive('verifyBackup')
            ->andReturn(['valid' => true, 'errors' => [], 'record_counts' => []]);

        $result = $this->service->testRecoveryPlan($plan->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('plan_id', $result);
        $this->assertArrayHasKey('tested_at', $result);
        $this->assertArrayHasKey('tests', $result);
        $this->assertArrayHasKey('overall_success', $result);
        $this->assertTrue($result['overall_success']);
    }

    public function test_test_recovery_plan_validates_backup_exists(): void
    {
        $plan = RecoveryPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'backup_id' => 99999, // Non-existent backup
            'status' => AnalyticsDisasterRecoveryService::STATUS_ACTIVE,
        ]);

        $result = $this->service->testRecoveryPlan($plan->id);

        $this->assertFalse($result['overall_success']);
        $this->assertFalse($result['tests']['backup_exists']['passed']);
    }

    public function test_get_recovery_status_returns_status_information(): void
    {
        RecoveryPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => AnalyticsDisasterRecoveryService::STATUS_ACTIVE,
            'name' => 'Active Plan',
        ]);

        RecoveryPlanExecution::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => AnalyticsDisasterRecoveryService::EXECUTION_STATUS_COMPLETED,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'duration_seconds' => 3600,
        ]);

        $result = $this->service->getRecoveryStatus();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('system_status', $result);
        $this->assertArrayHasKey('active_plan', $result);
        $this->assertArrayHasKey('recent_executions', $result);
        $this->assertArrayHasKey('auto_failover_enabled', $result);
        $this->assertNotNull($result['active_plan']);
        $this->assertEquals('Active Plan', $result['active_plan']['name']);
    }

    public function test_get_recovery_status_handles_no_active_plan(): void
    {
        $result = $this->service->getRecoveryStatus();

        $this->assertIsArray($result);
        $this->assertNull($result['active_plan']);
    }

    public function test_failover_to_backup_creates_execution(): void
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'completed',
        ]);

        $this->backupService->shouldReceive('verifyBackup')
            ->andReturn(['valid' => true, 'errors' => []]);
        $this->backupService->shouldReceive('restoreBackup')
            ->andReturn($backup);

        $result = $this->service->failoverToBackup();

        $this->assertInstanceOf(RecoveryPlanExecution::class, $result);
        $this->assertEquals(AnalyticsDisasterRecoveryService::EXECUTION_STATUS_COMPLETED, $result->status);
    }

    public function test_failover_to_backup_throws_exception_without_backup(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No suitable backup found for failover');

        $this->service->failoverToBackup();
    }

    public function test_failback_to_primary_creates_execution(): void
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'verified',
        ]);

        $this->backupService->shouldReceive('verifyBackup')
            ->andReturn(['valid' => true, 'errors' => []]);
        $this->backupService->shouldReceive('restoreBackup')
            ->andReturn($backup);

        $result = $this->service->failbackToPrimary();

        $this->assertInstanceOf(RecoveryPlanExecution::class, $result);
        $this->assertEquals(AnalyticsDisasterRecoveryService::EXECUTION_STATUS_COMPLETED, $result->status);
    }

    public function test_validate_system_integrity_returns_validation_results(): void
    {
        $result = $this->service->validateSystemIntegrity();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('validated_at', $result);
        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('overall_status', $result);
        $this->assertArrayHasKey('issues', $result);

        // Verify checks structure
        $this->assertArrayHasKey('database_connectivity', $result['checks']);
        $this->assertArrayHasKey('storage_accessibility', $result['checks']);
        $this->assertArrayHasKey('backup_availability', $result['checks']);
        $this->assertArrayHasKey('tenant_schema', $result['checks']);
    }

    public function test_validate_system_integrity_detects_critical_issues(): void
    {
        // Mock storage failure
        Storage::shouldReceive('put')
            ->andThrow(new \Exception('Storage write failed'));

        $result = $this->service->validateSystemIntegrity();

        $this->assertEquals(
            AnalyticsDisasterRecoveryService::SYSTEM_STATUS_CRITICAL,
            $result['overall_status']
        );
        $this->assertNotEmpty($result['issues']);
    }

    public function test_get_recovery_metrics_returns_metrics(): void
    {
        RecoveryPlan::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'status' => AnalyticsDisasterRecoveryService::STATUS_COMPLETED,
        ]);

        RecoveryPlanExecution::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => AnalyticsDisasterRecoveryService::EXECUTION_STATUS_COMPLETED,
            'duration_seconds' => 3600,
            'data_restored_count' => 5000,
        ]);

        $result = $this->service->getRecoveryMetrics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('plans', $result);
        $this->assertArrayHasKey('executions', $result);
        $this->assertArrayHasKey('success_rate', $result);
        $this->assertArrayHasKey('recovery_time_trend', $result);
        $this->assertGreaterThan(0, $result['executions']['total_executions']);
    }

    public function test_update_recovery_plan_updates_fields(): void
    {
        $plan = RecoveryPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Name',
            'priority' => AnalyticsDisasterRecoveryService::PRIORITY_LOW,
        ]);

        $result = $this->service->updateRecoveryPlan($plan->id, [
            'name' => 'Updated Name',
            'priority' => AnalyticsDisasterRecoveryService::PRIORITY_HIGH,
        ]);

        $this->assertEquals('Updated Name', $result->name);
        $this->assertEquals(AnalyticsDisasterRecoveryService::PRIORITY_HIGH, $result->priority);
    }

    public function test_update_recovery_plan_fails_for_executing_plan(): void
    {
        $plan = RecoveryPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => AnalyticsDisasterRecoveryService::STATUS_EXECUTING,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot update a plan that is currently executing');

        $this->service->updateRecoveryPlan($plan->id, [
            'name' => 'Updated Name',
        ]);
    }

    public function test_get_recovery_plans_returns_collection(): void
    {
        RecoveryPlan::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $result = $this->service->getRecoveryPlans();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(5, $result);
    }

    public function test_get_recovery_plans_with_filters(): void
    {
        RecoveryPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => AnalyticsDisasterRecoveryService::STATUS_ACTIVE,
            'type' => AnalyticsDisasterRecoveryService::TYPE_FULL_RECOVERY,
        ]);

        RecoveryPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => AnalyticsDisasterRecoveryService::STATUS_DRAFT,
            'type' => AnalyticsDisasterRecoveryService::TYPE_FAILOVER,
        ]);

        $result = $this->service->getRecoveryPlans([
            'status' => AnalyticsDisasterRecoveryService::STATUS_ACTIVE,
        ]);

        $this->assertCount(1, $result);
        $this->assertEquals(
            AnalyticsDisasterRecoveryService::STATUS_ACTIVE,
            $result->first()->status
        );
    }

    public function test_get_recovery_plans_with_type_filter(): void
    {
        RecoveryPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => AnalyticsDisasterRecoveryService::TYPE_FAILOVER,
        ]);

        RecoveryPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => AnalyticsDisasterRecoveryService::TYPE_FAILBACK,
        ]);

        $result = $this->service->getRecoveryPlans([
            'type' => AnalyticsDisasterRecoveryService::TYPE_FAILOVER,
        ]);

        $this->assertCount(1, $result);
        $this->assertEquals(
            AnalyticsDisasterRecoveryService::TYPE_FAILOVER,
            $result->first()->type
        );
    }

    public function test_constants_are_defined(): void
    {
        // Status constants
        $this->assertEquals('draft', AnalyticsDisasterRecoveryService::STATUS_DRAFT);
        $this->assertEquals('active', AnalyticsDisasterRecoveryService::STATUS_ACTIVE);
        $this->assertEquals('testing', AnalyticsDisasterRecoveryService::STATUS_TESTING);
        $this->assertEquals('executing', AnalyticsDisasterRecoveryService::STATUS_EXECUTING);
        $this->assertEquals('completed', AnalyticsDisasterRecoveryService::STATUS_COMPLETED);
        $this->assertEquals('failed', AnalyticsDisasterRecoveryService::STATUS_FAILED);
        $this->assertEquals('archived', AnalyticsDisasterRecoveryService::STATUS_ARCHIVED);

        // Type constants
        $this->assertEquals('full_recovery', AnalyticsDisasterRecoveryService::TYPE_FULL_RECOVERY);
        $this->assertEquals('partial_recovery', AnalyticsDisasterRecoveryService::TYPE_PARTIAL_RECOVERY);
        $this->assertEquals('point_in_time', AnalyticsDisasterRecoveryService::TYPE_POINT_IN_TIME);
        $this->assertEquals('failover', AnalyticsDisasterRecoveryService::TYPE_FAILOVER);
        $this->assertEquals('failback', AnalyticsDisasterRecoveryService::TYPE_FAILBACK);

        // Priority constants
        $this->assertEquals('critical', AnalyticsDisasterRecoveryService::PRIORITY_CRITICAL);
        $this->assertEquals('high', AnalyticsDisasterRecoveryService::PRIORITY_HIGH);
        $this->assertEquals('medium', AnalyticsDisasterRecoveryService::PRIORITY_MEDIUM);
        $this->assertEquals('low', AnalyticsDisasterRecoveryService::PRIORITY_LOW);

        // System status constants
        $this->assertEquals('healthy', AnalyticsDisasterRecoveryService::SYSTEM_STATUS_HEALTHY);
        $this->assertEquals('degraded', AnalyticsDisasterRecoveryService::SYSTEM_STATUS_DEGRADED);
        $this->assertEquals('critical', AnalyticsDisasterRecoveryService::SYSTEM_STATUS_CRITICAL);
        $this->assertEquals('recovering', AnalyticsDisasterRecoveryService::SYSTEM_STATUS_RECOVERING);

        // Execution status constants
        $this->assertEquals('pending', AnalyticsDisasterRecoveryService::EXECUTION_STATUS_PENDING);
        $this->assertEquals('running', AnalyticsDisasterRecoveryService::EXECUTION_STATUS_RUNNING);
        $this->assertEquals('completed', AnalyticsDisasterRecoveryService::EXECUTION_STATUS_COMPLETED);
        $this->assertEquals('failed', AnalyticsDisasterRecoveryService::EXECUTION_STATUS_FAILED);
        $this->assertEquals('rolled_back', AnalyticsDisasterRecoveryService::EXECUTION_STATUS_ROLLED_BACK);
        $this->assertEquals('cancelled', AnalyticsDisasterRecoveryService::EXECUTION_STATUS_CANCELLED);
    }

    public function test_tenant_isolation_in_recovery_operations(): void
    {
        $plan1 = $this->service->createRecoveryPlan([
            'name' => 'Tenant 1 Plan',
        ]);

        $tenant2 = Tenant::factory()->create();
        $this->tenantContext->shouldReceive('getCurrentTenantId')->andReturn($tenant2->id);

        $service2 = new AnalyticsDisasterRecoveryService(
            $this->tenantContext,
            $this->backupService
        );

        $plans2 = $service2->getRecoveryPlans();

        $this->assertEquals($this->tenant->id, $plan1->tenant_id);
        $this->assertEquals(0, $plans2->count());
    }

    public function test_recovery_plan_estimated_duration_based_on_type(): void
    {
        $fullRecoveryPlan = $this->service->createRecoveryPlan([
            'name' => 'Full Recovery',
            'type' => AnalyticsDisasterRecoveryService::TYPE_FULL_RECOVERY,
        ]);

        $failoverPlan = $this->service->createRecoveryPlan([
            'name' => 'Failover',
            'type' => AnalyticsDisasterRecoveryService::TYPE_FAILOVER,
        ]);

        $partialPlan = $this->service->createRecoveryPlan([
            'name' => 'Partial',
            'type' => AnalyticsDisasterRecoveryService::TYPE_PARTIAL_RECOVERY,
        ]);

        // Full recovery should take longer than partial
        $this->assertGreaterThan(
            $partialPlan->estimated_duration_minutes,
            $fullRecoveryPlan->estimated_duration_minutes
        );
    }

    public function test_recovery_plan_with_automatic_flag(): void
    {
        $plan = $this->service->createRecoveryPlan([
            'name' => 'Auto Plan',
            'is_automatic' => true,
        ]);

        $this->assertTrue($plan->is_automatic);
    }

    public function test_recovery_plan_with_notification_channels(): void
    {
        $plan = $this->service->createRecoveryPlan([
            'name' => 'Notified Plan',
            'notification_channels' => ['email', 'slack', 'webhook'],
        ]);

        $this->assertIsArray($plan->notification_channels);
        $this->assertContains('email', $plan->notification_channels);
        $this->assertContains('slack', $plan->notification_channels);
        $this->assertContains('webhook', $plan->notification_channels);
    }

    public function test_test_recovery_plan_updates_last_tested_at(): void
    {
        $plan = RecoveryPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => AnalyticsDisasterRecoveryService::STATUS_ACTIVE,
            'backup_id' => Backup::factory()->create([
                'tenant_id' => $this->tenant->id,
                'status' => 'completed',
            ])->id,
        ]);

        $this->backupService->shouldReceive('verifyBackup')
            ->andReturn(['valid' => true, 'errors' => [], 'record_counts' => []]);

        $this->assertNull($plan->last_tested_at);

        $this->service->testRecoveryPlan($plan->id);

        $plan->refresh();
        $this->assertNotNull($plan->last_tested_at);
    }

    public function test_get_recovery_metrics_with_date_range(): void
    {
        // Create old executions
        RecoveryPlanExecution::factory()->create([
            'tenant_id' => $this->tenant->id,
            'started_at' => now()->subDays(40),
            'completed_at' => now()->subDays(40),
        ]);

        // Create recent executions
        RecoveryPlanExecution::factory()->create([
            'tenant_id' => $this->tenant->id,
            'started_at' => now()->subDays(5),
            'completed_at' => now()->subDays(5),
            'status' => AnalyticsDisasterRecoveryService::EXECUTION_STATUS_COMPLETED,
        ]);

        $result = $this->service->getRecoveryMetrics([
            'start_date' => now()->subDays(30),
            'end_date' => now(),
        ]);

        $this->assertEquals(1, $result['executions']['total_executions']);
    }

    public function test_recovery_plan_status_transitions(): void
    {
        $plan = $this->service->createRecoveryPlan([
            'name' => 'Test Plan',
        ]);

        $this->assertEquals(AnalyticsDisasterRecoveryService::STATUS_DRAFT, $plan->status);
        $this->assertTrue($plan->isDraft());

        // After testing, it should become active
        $plan = RecoveryPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => AnalyticsDisasterRecoveryService::STATUS_ACTIVE,
            'test_results' => ['overall_success' => true],
        ]);

        $this->assertTrue($plan->isActive());
        $this->assertFalse($plan->isDraft());
    }

    public function test_recovery_execution_progress_calculation(): void
    {
        $execution = RecoveryPlanExecution::factory()->create([
            'tenant_id' => $this->tenant->id,
            'steps_completed' => 3,
            'steps_total' => 10,
        ]);

        $this->assertEquals(30, $execution->getProgressPercentage());
    }

    public function test_recovery_execution_with_no_steps(): void
    {
        $execution = RecoveryPlanExecution::factory()->create([
            'tenant_id' => $this->tenant->id,
            'steps_completed' => 0,
            'steps_total' => 0,
        ]);

        $this->assertEquals(0, $execution->getProgressPercentage());
    }
}
