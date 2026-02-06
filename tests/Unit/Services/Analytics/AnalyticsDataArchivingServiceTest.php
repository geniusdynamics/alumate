<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Models\Tenant;
use App\Services\Analytics\AnalyticsDataArchivingService;
use App\Services\TenantContextService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AnalyticsDataArchivingServiceTest extends TestCase
{
    private AnalyticsDataArchivingService $service;
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

        $this->service = new AnalyticsDataArchivingService($this->tenantContext);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_archive_data_returns_success_response(): void
    {
        $result = $this->service->archiveData([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('archive_id', $result);
        $this->assertArrayHasKey('start_date', $result);
        $this->assertArrayHasKey('end_date', $result);
        $this->assertArrayHasKey('file_name', $result);
        $this->assertArrayHasKey('file_size', $result);
        $this->assertArrayHasKey('compressed_size', $result);
        $this->assertArrayHasKey('record_count', $result);
        $this->assertArrayHasKey('compression', $result);
    }

    public function test_archive_data_with_custom_options(): void
    {
        $result = $this->service->archiveData([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ], [
            'compression' => 'gzip',
            'data_types' => ['events', 'sessions'],
            'type' => 'custom',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('gzip', $result['compression']);
    }

    public function test_archive_data_validates_date_range(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Date range must include start_date and end_date');

        $this->service->archiveData([
            'start_date' => '2024-01-01',
        ]);
    }

    public function test_archive_data_rejects_future_end_date(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('End date cannot be in the future');

        $this->service->archiveData([
            'start_date' => '2024-01-01',
            'end_date' => now()->addDays(10)->format('Y-m-d'),
        ]);
    }

    public function test_archive_data_rejects_start_date_after_end_date(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Start date must be before end date');

        $this->service->archiveData([
            'start_date' => '2024-02-01',
            'end_date' => '2024-01-01',
        ]);
    }

    public function test_archive_old_data_returns_success_with_no_data(): void
    {
        $result = $this->service->archiveOldData(90);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['archives_created']);
    }

    public function test_archive_old_data_rejects_permanent_retention(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot archive data with PERMANENT retention period');

        $this->service->archiveOldData(AnalyticsDataArchivingService::RETENTION_PERMANENT);
    }

    public function test_restore_data_returns_success_response(): void
    {
        $archiveResult = $this->service->archiveData([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $archiveContent = json_encode([
            'archive_info' => ['version' => '1.0', 'compression' => 'none'],
            'data' => ['events' => []],
        ]);
        Storage::shouldReceive('get')->andReturn($archiveContent);

        $result = $this->service->restoreData($archiveResult['archive_id']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('archive_id', $result);
        $this->assertArrayHasKey('record_count', $result);
    }

    public function test_restore_data_throws_exception_for_non_existent_archive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Archive not found');

        $this->service->restoreData('non_existent_archive_id');
    }

    public function test_delete_archived_data_returns_success_response(): void
    {
        $archiveResult = $this->service->archiveData([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $result = $this->service->deleteArchivedData($archiveResult['archive_id']);

        $this->assertTrue($result['success']);
        $this->assertEquals(AnalyticsDataArchivingService::STATUS_DELETED, $result['status']);
    }

    public function test_get_archive_history_returns_correct_structure(): void
    {
        $this->service->archiveData([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $result = $this->service->getArchiveHistory();

        $this->assertArrayHasKey('archives', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('tenant_id', $result);
    }

    public function test_get_archive_history_with_pagination(): void
    {
        $result = $this->service->getArchiveHistory([
            'limit' => 10,
            'offset' => 5,
        ]);

        $this->assertEquals(10, $result['limit']);
        $this->assertEquals(5, $result['offset']);
    }

    public function test_get_retention_policies_returns_default_policies(): void
    {
        $result = $this->service->getRetentionPolicies();

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        $policyNames = array_column($result, 'name');
        $this->assertContains('Events Retention', $policyNames);
        $this->assertContains('Sessions Retention', $policyNames);
    }

    public function test_set_retention_policy_returns_success(): void
    {
        $result = $this->service->setRetentionPolicy([
            'name' => 'Custom Policy',
            'data_type' => 'events',
            'retention_period' => 180,
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('policy_id', $result);
    }

    public function test_set_retention_policy_validates_required_fields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Retention period is required');

        $this->service->setRetentionPolicy(['name' => 'Invalid Policy']);
    }

    public function test_set_retention_policy_validates_retention_period_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Retention period must be an integer');

        $this->service->setRetentionPolicy([
            'name' => 'Invalid Policy',
            'retention_period' => 'invalid',
        ]);
    }

    public function test_check_retention_compliance_returns_correct_structure(): void
    {
        $result = $this->service->checkRetentionCompliance();

        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('checked_at', $result);
        $this->assertArrayHasKey('policies', $result);
        $this->assertArrayHasKey('overall_compliant', $result);
    }

    public function test_check_retention_compliance_is_compliant_for_new_tenant(): void
    {
        $result = $this->service->checkRetentionCompliance();

        $this->assertTrue($result['overall_compliant']);
        $this->assertEquals(0, $result['summary']['non_compliant']);
    }

    public function test_get_storage_usage_returns_correct_structure(): void
    {
        $this->service->archiveData([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $result = $this->service->getStorageUsage();

        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('total_archives', $result);
        $this->assertArrayHasKey('total_size', $result);
        $this->assertArrayHasKey('compression_ratio', $result);
    }

    public function test_status_constants_are_defined(): void
    {
        $this->assertEquals('pending', AnalyticsDataArchivingService::STATUS_PENDING);
        $this->assertEquals('processing', AnalyticsDataArchivingService::STATUS_PROCESSING);
        $this->assertEquals('completed', AnalyticsDataArchivingService::STATUS_COMPLETED);
        $this->assertEquals('failed', AnalyticsDataArchivingService::STATUS_FAILED);
        $this->assertEquals('restored', AnalyticsDataArchivingService::STATUS_RESTORED);
        $this->assertEquals('deleted', AnalyticsDataArchivingService::STATUS_DELETED);
    }

    public function test_archive_type_constants_are_defined(): void
    {
        $this->assertEquals('daily', AnalyticsDataArchivingService::TYPE_DAILY);
        $this->assertEquals('weekly', AnalyticsDataArchivingService::TYPE_WEEKLY);
        $this->assertEquals('monthly', AnalyticsDataArchivingService::TYPE_MONTHLY);
        $this->assertEquals('custom', AnalyticsDataArchivingService::TYPE_CUSTOM);
    }

    public function test_retention_period_constants_are_defined(): void
    {
        $this->assertEquals(90, AnalyticsDataArchivingService::RETENTION_SHORT);
        $this->assertEquals(365, AnalyticsDataArchivingService::RETENTION_MEDIUM);
        $this->assertEquals(730, AnalyticsDataArchivingService::RETENTION_LONG);
        $this->assertEquals(-1, AnalyticsDataArchivingService::RETENTION_PERMANENT);
    }

    public function test_compression_type_constants_are_defined(): void
    {
        $this->assertEquals('gzip', AnalyticsDataArchivingService::COMPRESSION_GZIP);
        $this->assertEquals('none', AnalyticsDataArchivingService::COMPRESSION_NONE);
    }

    public function test_tenant_isolation_in_archive_operations(): void
    {
        $this->service->archiveData([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $tenant2 = Tenant::factory()->create();
        $this->tenantContext->shouldReceive('getCurrentTenantId')->andReturn($tenant2->id);

        $service2 = new AnalyticsDataArchivingService($this->tenantContext);
        $history2 = $service2->getArchiveHistory();

        $this->assertEquals($tenant2->id, $history2['tenant_id']);
        $this->assertEquals(0, $history2['total_count']);
    }

    public function test_error_handling_in_archive_data(): void
    {
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andThrow(new \Exception('Test error'));

        $service = new AnalyticsDataArchivingService($this->tenantContext);
        $result = $service->archiveData([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_error_handling_in_get_archive_history(): void
    {
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andThrow(new \Exception('Test error'));

        $service = new AnalyticsDataArchivingService($this->tenantContext);
        $result = $service->getArchiveHistory();

        $this->assertArrayHasKey('error', $result);
    }

    public function test_archive_generates_unique_id(): void
    {
        $result1 = $this->service->archiveData([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $result2 = $this->service->archiveData([
            'start_date' => '2024-02-01',
            'end_date' => '2024-02-28',
        ]);

        $this->assertNotEquals($result1['archive_id'], $result2['archive_id']);
    }

    public function test_archive_creates_compressed_file(): void
    {
        $result = $this->service->archiveData([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ], ['compression' => 'gzip']);

        $this->assertStringEndsWith('.gz', $result['file_name']);
    }

    public function test_restore_data_with_deleted_archive_throws_exception(): void
    {
        $archiveResult = $this->service->archiveData([
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);
        $this->service->deleteArchivedData($archiveResult['archive_id']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot restore a deleted archive');

        $this->service->restoreData($archiveResult['archive_id']);
    }
}
