<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSnapshot;
use App\Models\AttributionTouch;
use App\Models\Backup;
use App\Models\BackupLog;
use App\Models\Cohort;
use App\Models\Prediction;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Analytics\AnalyticsBackupRecoveryService;
use App\Services\TenantContextService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AnalyticsBackupRecoveryServiceTest extends TestCase
{
    private AnalyticsBackupRecoveryService $service;
    private TenantContextService|MockInterface $tenantContext;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        $this->tenantContext = Mockery::mock(TenantContextService::class);
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andReturn($this->tenant->id);
        $this->tenantContext->shouldReceive('getCurrentTenant')
            ->andReturn($this->tenant);

        Auth::shouldReceive('id')
            ->andReturn(1);

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

        $this->service = new AnalyticsBackupRecoveryService($this->tenantContext);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_backup_returns_backup_record(): void
    {
        $result = $this->service->createBackup([
            'type' => 'full',
            'compress' => false,
        ]);

        $this->assertInstanceOf(Backup::class, $result);
        $this->assertEquals($this->tenant->id, $result->tenant_id);
        $this->assertEquals('full', $result->type);
        $this->assertEquals(1, $result->user_id);
    }

    public function test_create_backup_with_date_range(): void
    {
        $result = $this->service->createBackup([
            'type' => 'incremental',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'compress' => true,
        ]);

        $this->assertInstanceOf(Backup::class, $result);
        $this->assertEquals('incremental', $result->type);
        $this->assertTrue($result->compress);
    }

    public function test_create_backup_with_custom_options(): void
    {
        $result = $this->service->createBackup([
            'name' => 'Custom Backup',
            'description' => 'Test backup',
            'include_events' => true,
            'include_snapshots' => true,
            'include_attributions' => false,
            'include_cohorts' => true,
            'include_predictions' => false,
            'include_custom_events' => true,
            'retention_days' => 30,
        ]);

        $this->assertInstanceOf(Backup::class, $result);
        $this->assertEquals('Custom Backup', $result->name);
        $this->assertEquals('Test backup', $result->description);
        $this->assertEquals(30, $result->retention_days);
    }

    public function test_create_backup_generates_unique_name(): void
    {
        $backup1 = $this->service->createBackup(['compress' => false]);
        $backup2 = $this->service->createBackup(['compress' => false]);

        $this->assertNotEquals($backup1->name, $backup2->name);
    }

    public function test_schedule_backup_returns_schedule_configuration(): void
    {
        $result = $this->service->scheduleBackup([
            'frequency' => 'daily',
            'time' => '03:00',
            'enabled' => true,
            'retention_days' => 90,
        ]);

        $this->assertIsArray($result);
        $this->assertEquals('daily', $result['frequency']);
        $this->assertEquals('03:00', $result['time']);
        $this->assertTrue($result['enabled']);
        $this->assertEquals(90, $result['retention_days']);
        $this->assertArrayHasKey('next_run_at', $result);
    }

    public function test_schedule_backup_weekly(): void
    {
        $result = $this->service->scheduleBackup([
            'frequency' => 'weekly',
            'day_of_week' => 5,
            'time' => '02:00',
        ]);

        $this->assertEquals('weekly', $result['frequency']);
        $this->assertEquals(5, $result['day_of_week']);
    }

    public function test_schedule_backup_monthly(): void
    {
        $result = $this->service->scheduleBackup([
            'frequency' => 'monthly',
            'day_of_month' => 15,
            'time' => '01:00',
        ]);

        $this->assertEquals('monthly', $result['frequency']);
        $this->assertEquals(15, $result['day_of_month']);
    }

    public function test_restore_backup_returns_backup_record(): void
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'completed',
            'compress' => false,
            'file_path' => 'test/backup.json',
            'file_size' => 1000,
        ]);

        $backupData = json_encode([
            'metadata' => [
                'tenant_id' => $this->tenant->id,
                'created_at' => now()->toIso8601String(),
                'version' => '1.0',
            ],
            'events' => [],
            'snapshots' => [],
            'attributions' => [],
            'cohorts' => [],
            'predictions' => [],
            'custom_events' => [],
            'custom_event_definitions' => [],
        ]);

        Storage::shouldReceive('get')->andReturn($backupData);

        $result = $this->service->restoreBackup($backup->id, [
            'restore_events' => true,
            'restore_snapshots' => true,
            'clear_existing' => false,
        ]);

        $this->assertInstanceOf(Backup::class, $result);
        $this->assertEquals('restored', $result->status);
    }

    public function test_restore_backup_requires_completed_status(): void
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Backup must be completed or verified before restoration');

        $this->service->restoreBackup($backup->id);
    }

    public function test_verify_backup_returns_verification_result(): void
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'completed',
            'compress' => false,
            'file_path' => 'test/backup.json',
            'file_size' => 1000,
        ]);

        $backupData = json_encode([
            'metadata' => [
                'tenant_id' => $this->tenant->id,
                'created_at' => now()->toIso8601String(),
                'version' => '1.0',
            ],
            'events' => [],
            'snapshots' => [],
            'attributions' => [],
            'cohorts' => [],
            'predictions' => [],
            'custom_events' => [],
            'custom_event_definitions' => [],
        ]);

        Storage::shouldReceive('get')->andReturn($backupData);

        $result = $this->service->verifyBackup($backup->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('backup_id', $result);
        $this->assertArrayHasKey('verified_at', $result);
        $this->assertArrayHasKey('checksums', $result);
        $this->assertArrayHasKey('record_counts', $result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertTrue($result['valid']);
    }

    public function test_verify_backup_detects_file_missing(): void
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'completed',
            'file_path' => 'test/missing.json',
        ]);

        Storage::shouldReceive('exists')->andReturn(false);

        $result = $this->service->verifyBackup($backup->id);

        $this->assertFalse($result['valid']);
        $this->assertContains('Backup file does not exist', $result['errors']);
    }

    public function test_delete_backup_removes_record_and_file(): void
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'file_path' => 'test/backup.json',
        ]);

        Storage::shouldReceive('delete')->andReturn(true);

        $result = $this->service->deleteBackup($backup->id);

        $this->assertTrue($result);
        $this->assertNull(Backup::find($backup->id));
    }

    public function test_get_backup_history_returns_collection(): void
    {
        Backup::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $result = $this->service->getBackupHistory();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(3, $result);
    }

    public function test_get_backup_history_with_filters(): void
    {
        Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'full',
            'status' => 'completed',
        ]);

        Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'incremental',
            'status' => 'completed',
        ]);

        $result = $this->service->getBackupHistory([
            'type' => 'full',
        ]);

        $this->assertCount(1, $result);
        $this->assertEquals('full', $result->first()->type);
    }

    public function test_get_backup_status_returns_status_information(): void
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Backup',
            'type' => 'full',
            'status' => 'completed',
            'file_size' => 2048,
            'retention_days' => 30,
            'compress' => true,
            'completed_at' => now(),
        ]);

        $result = $this->service->getBackupStatus($backup->id);

        $this->assertIsArray($result);
        $this->assertEquals($backup->id, $result['id']);
        $this->assertEquals('Test Backup', $result['name']);
        $this->assertEquals('completed', $result['status']);
        $this->assertArrayHasKey('file_size_formatted', $result);
        $this->assertArrayHasKey('expires_at', $result);
    }

    public function test_estimate_backup_size_returns_estimate(): void
    {
        $result = $this->service->estimateBackupSize([
            'compress' => true,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('estimated_size', $result);
        $this->assertArrayHasKey('estimated_size_formatted', $result);
        $this->assertArrayHasKey('total_records', $result);
        $this->assertArrayHasKey('record_counts', $result);
        $this->assertArrayHasKey('estimated_duration_seconds', $result);
    }

    public function test_compress_backup_compresses_existing_backup(): void
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'completed',
            'compress' => false,
            'file_path' => 'test/backup.json',
            'file_size' => 2048,
        ]);

        $backupData = json_encode([
            'metadata' => [
                'tenant_id' => $this->tenant->id,
                'created_at' => now()->toIso8601String(),
                'version' => '1.0',
            ],
            'events' => [],
            'snapshots' => [],
            'attributions' => [],
            'cohorts' => [],
            'predictions' => [],
            'custom_events' => [],
            'custom_event_definitions' => [],
        ]);

        Storage::shouldReceive('get')->andReturn($backupData);
        Storage::shouldReceive('size')->andReturn(600);

        $result = $this->service->compressBackup($backup->id);

        $this->assertTrue($result->compress);
        $this->assertStringEndsWith('.zip', $result->file_name);
    }

    public function test_compress_backup_throws_exception_if_already_compressed(): void
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'completed',
            'compress' => true,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Backup is already compressed');

        $this->service->compressBackup($backup->id);
    }

    public function test_decompress_backup_decompresses_existing_backup(): void
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'completed',
            'compress' => true,
            'file_path' => 'test/backup.zip',
            'file_size' => 600,
        ]);

        $backupData = json_encode([
            'metadata' => [
                'tenant_id' => $this->tenant->id,
                'created_at' => now()->toIso8601String(),
                'version' => '1.0',
            ],
            'events' => [],
            'snapshots' => [],
            'attributions' => [],
            'cohorts' => [],
            'predictions' => [],
            'custom_events' => [],
            'custom_event_definitions' => [],
        ]);

        // Mock ZipArchive behavior
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, $backupData);

        $zipContent = file_get_contents($tempFile);
        Storage::shouldReceive('get')->andReturn($zipContent);
        Storage::shouldReceive('size')->andReturn(2048);

        $result = $this->service->decompressBackup($backup->id);

        $this->assertFalse($result->compress);
        $this->assertStringEndsWith('.json', $result->file_name);

        unlink($tempFile);
    }

    public function test_cleanup_expired_backups_deletes_old_backups(): void
    {
        // Create expired backup
        Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'completed_at' => now()->subDays(400),
            'retention_days' => 30,
        ]);

        // Create valid backup
        Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'completed_at' => now()->subDays(10),
            'retention_days' => 30,
        ]);

        Storage::shouldReceive('delete')->andReturn(true);

        $count = $this->service->cleanupExpiredBackups();

        $this->assertEquals(1, $count);
        $this->assertCount(1, Backup::where('tenant_id', $this->tenant->id)->get());
    }

    public function test_tenant_isolation_in_backup_operations(): void
    {
        $this->service->createBackup(['compress' => false]);

        $tenant2 = Tenant::factory()->create();
        $this->tenantContext->shouldReceive('getCurrentTenantId')->andReturn($tenant2->id);

        $service2 = new AnalyticsBackupRecoveryService($this->tenantContext);
        $history2 = $service2->getBackupHistory();

        $this->assertEquals($tenant2->id, $history2->first()?->tenant_id ?? $tenant2->id);
        $this->assertEquals(0, $history2->count());
    }

    public function test_backup_constants_are_defined(): void
    {
        $this->assertEquals('full', AnalyticsBackupRecoveryService::BACKUP_TYPE_FULL);
        $this->assertEquals('incremental', AnalyticsBackupRecoveryService::BACKUP_TYPE_INCREMENTAL);
        $this->assertEquals('snapshot', AnalyticsBackupRecoveryService::BACKUP_TYPE_SNAPSHOT);
    }

    public function test_status_constants_are_defined(): void
    {
        $this->assertEquals('pending', AnalyticsBackupRecoveryService::STATUS_PENDING);
        $this->assertEquals('in_progress', AnalyticsBackupRecoveryService::STATUS_IN_PROGRESS);
        $this->assertEquals('completed', AnalyticsBackupRecoveryService::STATUS_COMPLETED);
        $this->assertEquals('failed', AnalyticsBackupRecoveryService::STATUS_FAILED);
        $this->assertEquals('verified', AnalyticsBackupRecoveryService::STATUS_VERIFIED);
        $this->assertEquals('restored', AnalyticsBackupRecoveryService::STATUS_RESTORED);
    }

    public function test_create_backup_handles_error_gracefully(): void
    {
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andThrow(new \Exception('Test error'));

        $service = new AnalyticsBackupRecoveryService($this->tenantContext);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test error');

        $service->createBackup();
    }

    public function test_get_backup_status_throws_exception_for_nonexistent_backup(): void
    {
        $this->expectException(\Exception::class);

        $this->service->getBackupStatus(99999);
    }

    public function test_delete_backup_throws_exception_for_nonexistent_backup(): void
    {
        $this->expectException(\Exception::class);

        $this->service->deleteBackup(99999);
    }

    public function test_create_backup_with_selective_data_inclusion(): void
    {
        $result = $this->service->createBackup([
            'include_events' => true,
            'include_snapshots' => false,
            'include_attributions' => true,
            'include_cohorts' => false,
            'include_predictions' => true,
            'include_custom_events' => false,
            'compress' => false,
        ]);

        $this->assertInstanceOf(Backup::class, $result);
    }

    public function test_verify_backup_validates_metadata_structure(): void
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'completed',
            'compress' => false,
            'file_path' => 'test/backup.json',
            'file_size' => 1000,
        ]);

        $backupData = json_encode([
            'events' => [],
            'snapshots' => [],
        ]);

        Storage::shouldReceive('get')->andReturn($backupData);

        $result = $this->service->verifyBackup($backup->id);

        $this->assertFalse($result['valid']);
        $this->assertContains('Missing metadata in backup', $result['errors']);
    }

    public function test_schedule_backup_disabled(): void
    {
        $result = $this->service->scheduleBackup([
            'frequency' => 'daily',
            'enabled' => false,
        ]);

        $this->assertFalse($result['enabled']);
        $this->assertArrayHasKey('next_run_at', $result);
    }

    public function test_get_backup_history_with_date_range_filter(): void
    {
        Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subDays(5),
        ]);

        Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_at' => now()->subDays(40),
        ]);

        $result = $this->service->getBackupHistory([
            'start_date' => now()->subDays(30)->toDateString(),
        ]);

        $this->assertCount(1, $result);
    }

    public function test_get_backup_history_with_limit(): void
    {
        Backup::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $result = $this->service->getBackupHistory([
            'limit' => 5,
        ]);

        $this->assertCount(5, $result);
    }

    public function test_estimate_backup_size_includes_all_data_types(): void
    {
        $result = $this->service->estimateBackupSize();

        $this->assertIsArray($result['record_counts']);
        $this->assertArrayHasKey('events', $result['record_counts']);
        $this->assertArrayHasKey('snapshots', $result['record_counts']);
        $this->assertArrayHasKey('attributions', $result['record_counts']);
        $this->assertArrayHasKey('cohorts', $result['record_counts']);
        $this->assertArrayHasKey('predictions', $result['record_counts']);
        $this->assertArrayHasKey('custom_events', $result['record_counts']);
    }

    public function test_restore_backup_with_clear_existing_option(): void
    {
        $backup = Backup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'completed',
            'compress' => false,
            'file_path' => 'test/backup.json',
            'file_size' => 1000,
        ]);

        $backupData = json_encode([
            'metadata' => [
                'tenant_id' => $this->tenant->id,
                'created_at' => now()->toIso8601String(),
                'version' => '1.0',
            ],
            'events' => [],
            'snapshots' => [],
            'attributions' => [],
            'cohorts' => [],
            'predictions' => [],
            'custom_events' => [],
            'custom_event_definitions' => [],
        ]);

        Storage::shouldReceive('get')->andReturn($backupData);

        $result = $this->service->restoreBackup($backup->id, [
            'clear_existing' => true,
        ]);

        $this->assertInstanceOf(Backup::class, $result);
        $this->assertEquals('restored', $result->status);
    }
}
