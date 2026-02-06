<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Models\Discrepancy;
use App\Models\SyncHistory;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Analytics\AnalyticsDataSyncService;
use App\Services\Analytics\GoogleAnalyticsService;
use App\Services\Analytics\MatomoService;
use App\Services\CacheService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for AnalyticsDataSyncService
 *
 * @covers \App\Services\Analytics\AnalyticsDataSyncService
 */
class AnalyticsDataSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private AnalyticsDataSyncService $service;
    /** @var GoogleAnalyticsService */
    private $googleAnalyticsService;
    /** @var MatomoService */
    private $matomoService;
    /** @var CacheService */
    private $cacheService;
    /** @var TenantContextService */
    private $tenantContextService;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create mocked services
        $this->googleAnalyticsService = Mockery::mock(GoogleAnalyticsService::class);
        $this->matomoService = Mockery::mock(MatomoService::class);
        $this->cacheService = Mockery::mock(CacheService::class);
        $this->tenantContextService = Mockery::mock(TenantContextService::class);

        // Configure tenant context mock
        $this->tenantContextService->shouldReceive('getCurrentTenantId')
            ->andReturn($this->tenant->id);

        // Configure cache service mock
        $this->cacheService->shouldReceive('get')->andReturn(null);
        $this->cacheService->shouldReceive('put')->andReturn(true);
        $this->cacheService->shouldReceive('forget')->andReturn(true);

        $this->service = new AnalyticsDataSyncService(
            $this->googleAnalyticsService,
            $this->matomoService,
            $this->cacheService,
            $this->tenantContextService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test sync data between sources
     */
    public function test_sync_data_creates_sync_record(): void
    {
        $dateRange = [
            'start' => now()->subDays(7)->format('Y-m-d'),
            'end' => now()->format('Y-m-d'),
        ];

        // Mock Google Analytics to return data
        $this->googleAnalyticsService->shouldReceive('getReport')
            ->andReturn([
                'rows' => [
                    ['metricValues' => [
                        ['value' => '100'],
                        ['value' => '80'],
                        ['value' => '300'],
                        ['value' => '50'],
                    ]],
                ],
                'metricHeaders' => [
                    ['name' => 'sessions'],
                    ['name' => 'activeUsers'],
                    ['name' => 'screenPageViews'],
                    ['name' => 'eventCount'],
                ],
            ]);

        $this->matomoService->shouldReceive('getReport')
            ->andReturn([
                'nb_visits' => 100,
                'nb_uniq_visitors' => 80,
                'nb_actions' => 300,
                'nb_events' => 50,
            ]);

        $result = $this->service->syncData(
            AnalyticsDataSyncService::SOURCE_GOOGLE_ANALYTICS,
            AnalyticsDataSyncService::SOURCE_MATOMO,
            $dateRange
        );

        // Verify sync record was created
        $this->assertDatabaseHas('sync_histories', [
            'source' => AnalyticsDataSyncService::SOURCE_GOOGLE_ANALYTICS,
            'target' => AnalyticsDataSyncService::SOURCE_MATOMO,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /**
     * Test detect discrepancies between two data sources
     */
    public function test_detect_discrepancies_returns_calculated_discrepancies(): void
    {
        $dateRange = [
            'start' => now()->subDays(7)->format('Y-m-d'),
            'end' => now()->format('Y-m-d'),
        ];

        // Mock Google Analytics to return data
        $this->googleAnalyticsService->shouldReceive('getReport')
            ->andReturn([
                'rows' => [
                    ['metricValues' => [
                        ['value' => '1000'],
                        ['value' => '800'],
                        ['value' => '3000'],
                        ['value' => '500'],
                    ]],
                ],
                'metricHeaders' => [
                    ['name' => 'sessions'],
                    ['name' => 'activeUsers'],
                    ['name' => 'screenPageViews'],
                    ['name' => 'eventCount'],
                ],
            ]);

        // Mock Matomo to return different data (will create discrepancy)
        $this->matomoService->shouldReceive('getReport')
            ->andReturn([
                'nb_visits' => 800, // 20% different
                'nb_uniq_visitors' => 600,
                'nb_actions' => 2400,
                'nb_events' => 400,
            ]);

        $discrepancies = $this->service->detectDiscrepancies(
            AnalyticsDataSyncService::SOURCE_GOOGLE_ANALYTICS,
            AnalyticsDataSyncService::SOURCE_MATOMO,
            $dateRange
        );

        $this->assertNotEmpty($discrepancies);
        
        // Find sessions discrepancy
        $sessionsDiscrepancy = collect($discrepancies)->firstWhere('metric', 'sessions');
        $this->assertNotNull($sessionsDiscrepancy);
        $this->assertEquals('sessions', $sessionsDiscrepancy['metric']);
        $this->assertEquals(1000, $sessionsDiscrepancy['value1']);
        $this->assertEquals(800, $sessionsDiscrepancy['value2']);
        $this->assertEquals('medium', $sessionsDiscrepancy['severity']);
    }

    /**
     * Test detect discrepancies with matching data
     */
    public function test_detect_discrepancies_with_matching_data(): void
    {
        $dateRange = [
            'start' => now()->subDays(7)->format('Y-m-d'),
            'end' => now()->format('Y-m-d'),
        ];

        // Mock both services to return similar data (within threshold)
        $gaData = [
            'rows' => [
                ['metricValues' => [
                    ['value' => '1000'],
                    ['value' => '800'],
                    ['value' => '3000'],
                    ['value' => '500'],
                ]],
            ],
            'metricHeaders' => [
                ['name' => 'sessions'],
                ['name' => 'activeUsers'],
                ['name' => 'screenPageViews'],
                ['name' => 'eventCount'],
            ],
        ];

        $this->googleAnalyticsService->shouldReceive('getReport')->andReturn($gaData);
        $this->matomoService->shouldReceive('getReport')
            ->andReturn([
                'nb_visits' => 1000, // Same as GA
                'nb_uniq_visitors' => 800,
                'nb_actions' => 3000,
                'nb_events' => 500,
            ]);

        $discrepancies = $this->service->detectDiscrepancies(
            AnalyticsDataSyncService::SOURCE_GOOGLE_ANALYTICS,
            AnalyticsDataSyncService::SOURCE_MATOMO,
            $dateRange
        );

        // Should have no discrepancies when data matches
        $this->assertEmpty($discrepancies);
    }

    /**
     * Test resolve discrepancy
     */
    public function test_resolve_discrepancy(): void
    {
        $discrepancy = Discrepancy::create([
            'discrepancy_id' => 'test_disc_123',
            'metric' => 'sessions',
            'source1' => AnalyticsDataSyncService::SOURCE_GOOGLE_ANALYTICS,
            'source2' => AnalyticsDataSyncService::SOURCE_MATOMO,
            'value1' => 1000,
            'value2' => 800,
            'difference_percentage' => 22.22,
            'severity' => 'medium',
            'tenant_id' => $this->tenant->id,
            'resolved' => false,
        ]);

        $result = $this->service->resolveDiscrepancy(
            'test_disc_123',
            AnalyticsDataSyncService::RESOLUTION_AVERAGE
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('test_disc_123', $result['discrepancy_id']);
        $this->assertEquals(AnalyticsDataSyncService::RESOLUTION_AVERAGE, $result['resolution']);
        $this->assertEquals(900, $result['resolved_value']); // (1000 + 800) / 2

        // Verify database was updated
        $this->assertDatabaseHas('discrepancies', [
            'discrepancy_id' => 'test_disc_123',
            'resolved' => true,
            'resolution' => AnalyticsDataSyncService::RESOLUTION_AVERAGE,
            'resolved_value' => 900,
        ]);
    }

    /**
     * Test resolve discrepancy with max strategy
     */
    public function test_resolve_discrepancy_with_max_strategy(): void
    {
        $discrepancy = Discrepancy::create([
            'discrepancy_id' => 'test_disc_456',
            'metric' => 'users',
            'source1' => AnalyticsDataSyncService::SOURCE_GOOGLE_ANALYTICS,
            'source2' => AnalyticsDataSyncService::SOURCE_MATOMO,
            'value1' => 500,
            'value2' => 800,
            'difference_percentage' => 46.15,
            'severity' => 'medium',
            'tenant_id' => $this->tenant->id,
            'resolved' => false,
        ]);

        $result = $this->service->resolveDiscrepancy(
            'test_disc_456',
            AnalyticsDataSyncService::RESOLUTION_MAX
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(800, $result['resolved_value']); // max(500, 800)
    }

    /**
     * Test resolve non-existent discrepancy
     */
    public function test_resolve_non_existent_discrepancy(): void
    {
        $result = $this->service->resolveDiscrepancy(
            'non_existent_id',
            AnalyticsDataSyncService::RESOLUTION_AVERAGE
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('Discrepancy not found', $result['error']);
    }

    /**
     * Test get sync status
     */
    public function test_get_sync_status_returns_status(): void
    {
        // Create some sync history
        SyncHistory::create([
            'source' => AnalyticsDataSyncService::SOURCE_GOOGLE_ANALYTICS,
            'target' => AnalyticsDataSyncService::SOURCE_MATOMO,
            'status' => AnalyticsDataSyncService::STATUS_COMPLETED,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHours(2)->addMinutes(5),
            'records_synced' => 150,
            'tenant_id' => $this->tenant->id,
        ]);

        $status = $this->service->getSyncStatus();

        $this->assertArrayHasKey('last_sync', $status);
        $this->assertArrayHasKey('google_analytics', $status);
        $this->assertArrayHasKey('matomo', $status);
        $this->assertArrayHasKey('internal', $status);
        $this->assertArrayHasKey('pending_discrepancies', $status);
        $this->assertArrayHasKey('updated_at', $status);

        // Verify Google Analytics status
        $this->assertEquals(AnalyticsDataSyncService::STATUS_COMPLETED, $status['google_analytics']['status']);
    }

    /**
     * Test get sync history
     */
    public function test_get_sync_history_returns_history(): void
    {
        // Create multiple sync history records
        SyncHistory::create([
            'source' => AnalyticsDataSyncService::SOURCE_GOOGLE_ANALYTICS,
            'target' => AnalyticsDataSyncService::SOURCE_MATOMO,
            'status' => AnalyticsDataSyncService::STATUS_COMPLETED,
            'started_at' => now()->subDays(1),
            'completed_at' => now()->subDays(1)->addMinutes(10),
            'records_synced' => 100,
            'tenant_id' => $this->tenant->id,
        ]);

        SyncHistory::create([
            'source' => AnalyticsDataSyncService::SOURCE_MATOMO,
            'target' => AnalyticsDataSyncService::SOURCE_INTERNAL,
            'status' => AnalyticsDataSyncService::STATUS_FAILED,
            'started_at' => now()->subHours(5),
            'error_message' => 'Connection timeout',
            'tenant_id' => $this->tenant->id,
        ]);

        $history = $this->service->getSyncHistory(10);

        $this->assertCount(2, $history);
        
        // Verify first record (most recent)
        $this->assertEquals(AnalyticsDataSyncService::STATUS_FAILED, $history[0]['status']);
        $this->assertEquals('Connection timeout', $history[0]['error_message']);
    }

    /**
     * Test get unified view
     */
    public function test_get_unified_view_returns_combined_data(): void
    {
        $dateRange = [
            'start' => now()->subDays(7)->format('Y-m-d'),
            'end' => now()->format('Y-m-d'),
        ];

        // Mock services to return data
        $this->googleAnalyticsService->shouldReceive('getReport')
            ->andReturn([
                'rows' => [
                    ['metricValues' => [
                        ['value' => '1000'],
                        ['value' => '800'],
                        ['value' => '3000'],
                        ['value' => '500'],
                    ]],
                ],
                'metricHeaders' => [
                    ['name' => 'sessions'],
                    ['name' => 'activeUsers'],
                    ['name' => 'screenPageViews'],
                    ['name' => 'eventCount'],
                ],
            ]);

        $this->matomoService->shouldReceive('getReport')
            ->andReturn([
                'nb_visits' => 1000,
                'nb_uniq_visitors' => 800,
                'nb_actions' => 3000,
                'nb_events' => 500,
                'bounce_rate' => 45.5,
                'avg_time_on_site' => 120.5,
            ]);

        $view = $this->service->getUnifiedView($dateRange);

        $this->assertArrayHasKey('date_range', $view);
        $this->assertArrayHasKey('metrics', $view);
        $this->assertArrayHasKey('sources', $view);
        $this->assertArrayHasKey('discrepancies', $view);
        $this->assertArrayHasKey('summary', $view);
        $this->assertArrayHasKey('generated_at', $view);

        // Verify sources are populated
        $this->assertTrue($view['sources'][AnalyticsDataSyncService::SOURCE_GOOGLE_ANALYTICS]['available']);
        $this->assertTrue($view['sources'][AnalyticsDataSyncService::SOURCE_MATOMO]['available']);

        // Verify metrics have calculated averages
        $this->assertArrayHasKey('sessions', $view['metrics']);
        $this->assertArrayHasKey('average', $view['metrics']['sessions']);
    }

    /**
     * Test monitor sync health
     */
    public function test_monitor_sync_returns_health_status(): void
    {
        // Mock service validations
        $this->googleAnalyticsService->shouldReceive('validateConfiguration')
            ->andReturn([
                'valid' => true,
                'healthy' => true,
            ]);

        $this->matomoService->shouldReceive('validateConfiguration')
            ->andReturn([
                'valid' => true,
                'healthy' => true,
            ]);

        $health = $this->service->monitorSync();

        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('checks', $health);
        $this->assertArrayHasKey('alerts', $health);
        $this->assertArrayHasKey('timestamp', $health);

        // Verify checks are present
        $this->assertArrayHasKey('google_analytics', $health['checks']);
        $this->assertArrayHasKey('matomo', $health['checks']);
        $this->assertArrayHasKey('internal', $health['checks']);
    }

    /**
     * Test handle sync error
     */
    public function test_handle_sync_error_logs_and_returns_result(): void
    {
        $error = new \Exception('Test error message');
        $context = ['test_context' => 'value'];

        $result = $this->service->handleSyncError($error, $context);

        $this->assertTrue($result['handled']);
        $this->assertEquals('Test error message', $result['error']);
        $this->assertArrayHasKey('severity', $result);
        $this->assertArrayHasKey('timestamp', $result);
    }

    /**
     * Test handle authentication error with critical severity
     */
    public function test_handle_sync_error_with_auth_error_is_critical(): void
    {
        $error = new \Exception('Authentication failed');
        $context = ['source' => 'google_analytics'];

        $result = $this->service->handleSyncError($error, $context);

        $this->assertEquals('critical', $result['severity']);
    }

    /**
     * Test handle timeout error with high severity
     */
    public function test_handle_sync_error_with_timeout_is_high(): void
    {
        $error = new \Exception('Connection timeout after 30 seconds');
        $context = ['source' => 'matomo'];

        $result = $this->service->handleSyncError($error, $context);

        $this->assertEquals('high', $result['severity']);
    }

    /**
     * Test discrepancy detection stores in database
     */
    public function test_detect_discrepancies_stores_in_database(): void
    {
        $dateRange = [
            'start' => now()->subDays(7)->format('Y-m-d'),
            'end' => now()->format('Y-m-d'),
        ];

        // Mock services with significantly different data
        $this->googleAnalyticsService->shouldReceive('getReport')
            ->andReturn([
                'rows' => [
                    ['metricValues' => [
                        ['value' => '1000'],
                        ['value' => '800'],
                        ['value' => '3000'],
                        ['value' => '500'],
                    ]],
                ],
                'metricHeaders' => [
                    ['name' => 'sessions'],
                    ['name' => 'activeUsers'],
                    ['name' => 'screenPageViews'],
                    ['name' => 'eventCount'],
                ],
            ]);

        $this->matomoService->shouldReceive('getReport')
            ->andReturn([
                'nb_visits' => 500, // 50% different - will create discrepancy
                'nb_uniq_visitors' => 400,
                'nb_actions' => 1500,
                'nb_events' => 250,
            ]);

        $this->service->detectDiscrepancies(
            AnalyticsDataSyncService::SOURCE_GOOGLE_ANALYTICS,
            AnalyticsDataSyncService::SOURCE_MATOMO,
            $dateRange
        );

        // Verify discrepancies were stored
        $this->assertDatabaseHas('discrepancies', [
            'metric' => 'sessions',
            'severity' => 'high',
            'resolved' => false,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /**
     * Test tenant isolation in sync history
     */
    public function test_sync_history_isolated_by_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        
        // Create sync history for other tenant
        SyncHistory::create([
            'source' => AnalyticsDataSyncService::SOURCE_GOOGLE_ANALYTICS,
            'target' => AnalyticsDataSyncService::SOURCE_MATOMO,
            'status' => AnalyticsDataSyncService::STATUS_COMPLETED,
            'started_at' => now()->subDays(1),
            'completed_at' => now()->subDays(1)->addMinutes(5),
            'records_synced' => 200,
            'tenant_id' => $otherTenant->id,
        ]);

        // Create sync history for current tenant
        SyncHistory::create([
            'source' => AnalyticsDataSyncService::SOURCE_GOOGLE_ANALYTICS,
            'target' => AnalyticsDataSyncService::SOURCE_MATOMO,
            'status' => AnalyticsDataSyncService::STATUS_COMPLETED,
            'started_at' => now()->subHours(1),
            'completed_at' => now()->subHours(1)->addMinutes(5),
            'records_synced' => 100,
            'tenant_id' => $this->tenant->id,
        ]);

        $history = $this->service->getSyncHistory(10);

        // Should only return current tenant's history
        $this->assertCount(1, $history);
        $this->assertEquals(100, $history[0]['records_synced']);
    }

    /**
     * Test discrepancy severity calculation
     */
    public function test_discrepancy_severity_levels(): void
    {
        $dateRange = [
            'start' => now()->subDays(7)->format('Y-m-d'),
            'end' => now()->format('Y-m-d'),
        ];

        // Mock services with very different data (high severity)
        $this->googleAnalyticsService->shouldReceive('getReport')
            ->andReturn([
                'rows' => [
                    ['metricValues' => [
                        ['value' => '1000'],
                        ['value' => '800'],
                        ['value' => '3000'],
                        ['value' => '500'],
                    ]],
                ],
                'metricHeaders' => [
                    ['name' => 'sessions'],
                    ['name' => 'activeUsers'],
                    ['name' => 'screenPageViews'],
                    ['name' => 'eventCount'],
                ],
            ]);

        $this->matomoService->shouldReceive('getReport')
            ->andReturn([
                'nb_visits' => 100, // 900 difference - 180% - high severity
                'nb_uniq_visitors' => 80,
                'nb_actions' => 300,
                'nb_events' => 50,
            ]);

        $discrepancies = $this->service->detectDiscrepancies(
            AnalyticsDataSyncService::SOURCE_GOOGLE_ANALYTICS,
            AnalyticsDataSyncService::SOURCE_MATOMO,
            $dateRange
        );

        $sessionsDiscrepancy = collect($discrepancies)->firstWhere('metric', 'sessions');
        $this->assertNotNull($sessionsDiscrepancy);
        $this->assertEquals('high', $sessionsDiscrepancy['severity']);
    }

    /**
     * Test validate configuration returns validation results
     */
    public function test_validate_configuration(): void
    {
        $this->googleAnalyticsService->shouldReceive('validateConfiguration')
            ->andReturn([
                'valid' => true,
                'errors' => [],
                'warnings' => [],
            ]);

        $this->matomoService->shouldReceive('validateConfiguration')
            ->andReturn([
                'valid' => true,
                'errors' => [],
                'warnings' => ['Matomo URL not configured'],
            ]);

        $result = $this->service->validateConfiguration();

        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertArrayHasKey('platforms', $result);
        $this->assertTrue($result['valid']);
    }

    /**
     * Test sync to external platforms
     */
    public function test_sync_to_external(): void
    {
        $events = [
            ['name' => 'test_event_1', 'value' => 100],
            ['name' => 'test_event_2', 'value' => 200],
        ];

        $this->googleAnalyticsService->shouldReceive('batchForwardEvents')
            ->andReturn([
                'total' => 2,
                'success' => 2,
                'failed' => 0,
                'errors' => [],
            ]);

        $this->matomoService->shouldReceive('batchForwardEvents')
            ->andReturn([
                'total' => 2,
                'success' => 1,
                'failed' => 1,
                'errors' => [['index' => 1, 'error' => 'Failed']],
            ]);

        $result = $this->service->syncToExternal($events);

        $this->assertArrayHasKey('google_analytics', $result);
        $this->assertArrayHasKey('matomo', $result);
        $this->assertEquals(2, $result['google_analytics']['success']);
        $this->assertEquals(1, $result['matomo']['success']);
        $this->assertCount(1, $result['errors']);
    }
}
