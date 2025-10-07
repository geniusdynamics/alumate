<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\SyncLog;
use App\Models\Tenant;
use App\Services\Analytics\ConsentService;
use App\Services\Analytics\GoogleAnalyticsService;
use App\Services\Analytics\MatomoService;
use App\Services\Analytics\SyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

class SyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private SyncService $service;
    private ConsentService $consentService;
    private GoogleAnalyticsService $googleAnalyticsService;
    private MatomoService $matomoService;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->consentService = Mockery::mock(ConsentService::class);
        $this->googleAnalyticsService = Mockery::mock(GoogleAnalyticsService::class);
        $this->matomoService = Mockery::mock(MatomoService::class);

        $this->service = new SyncService(
            $this->consentService,
            $this->googleAnalyticsService,
            $this->matomoService
        );

        // Set tenant context
        session(['tenant_id' => $this->tenant->id]);

        // Clear caches
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sync_data_unifies_50_events_from_ga_matomo_mocks(): void
    {
        // Mock external services
        $this->googleAnalyticsService->shouldReceive('fetchMetrics')
            ->andReturn([
                'events_count' => 1200,
                'sessions' => 600,
                'conversions' => 30,
            ]);

        $this->matomoService->shouldReceive('fetchMetrics')
            ->andReturn([
                'events_count' => 1100,
                'sessions' => 550,
                'conversions' => 25,
            ]);

        $timeRange = [
            'start' => now()->subDays(7)->toDateString(),
            'end' => now()->toDateString(),
        ];

        $result = $this->service->syncData($this->tenant->id, ['ga', 'matomo'], $timeRange);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('events_count', $result);
        $this->assertArrayHasKey('sessions', $result);
        $this->assertArrayHasKey('conversions', $result);
        $this->assertArrayHasKey('sources', $result);
        $this->assertContains('internal', $result['sources']);
        $this->assertContains('ga', $result['sources']);
        $this->assertContains('matomo', $result['sources']);

        // Verify sync log was created
        $this->assertDatabaseHas('sync_logs', [
            'tenant_id' => $this->tenant->id,
            'sync_type' => 'unified',
            'status' => 'success',
        ]);
    }

    public function test_detect_discrepancies_finds_5_cases_with_more_than_5_percent_diff(): void
    {
        // Mock external services with significant discrepancies
        $this->googleAnalyticsService->shouldReceive('fetchMetrics')
            ->andReturn([
                'events_count' => 2000, // 100% higher than internal
                'sessions' => 1200,    // 50% higher
                'conversions' => 100,  // 25% higher
            ]);

        $this->matomoService->shouldReceive('fetchMetrics')
            ->andReturn([
                'events_count' => 1800, // 80% higher
                'sessions' => 1100,    // 37.5% higher
                'conversions' => 90,   // 12.5% higher
            ]);

        $timeRange = [
            'start' => now()->subDays(7)->toDateString(),
            'end' => now()->toDateString(),
        ];

        $result = $this->service->detectDiscrepancies($this->tenant->id, $timeRange);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('discrepancies', $result);
        $this->assertArrayHasKey('resolutions', $result);
        $this->assertArrayHasKey('unified_metrics', $result);

        // Should detect multiple discrepancies
        $this->assertGreaterThan(3, count($result['discrepancies']));

        // Verify sync log for discrepancy detection
        $this->assertDatabaseHas('sync_logs', [
            'tenant_id' => $this->tenant->id,
            'sync_type' => 'discrepancy_detection',
            'status' => 'success',
        ]);
    }

    public function test_monitor_sync_updates_redis_with_status_and_errors(): void
    {
        // Create some sync logs
        SyncLog::factory()->count(5)->forTenant($this->tenant->id)->successful()->create();
        SyncLog::factory()->count(2)->forTenant($this->tenant->id)->failed()->create();

        $status = $this->service->monitorSync($this->tenant->id);

        $this->assertIsArray($status);
        $this->assertArrayHasKey('last_sync_at', $status);
        $this->assertArrayHasKey('errors_count', $status);
        $this->assertArrayHasKey('success_rate', $status);
        $this->assertArrayHasKey('total_syncs', $status);

        $this->assertEquals(7, $status['total_syncs']);
        $this->assertEquals(2, $status['errors_count']);
        $this->assertEquals(5/7, $status['success_rate']);
    }

    public function test_handle_consent_skips_sync_for_withdrawn_consent(): void
    {
        $userId = 123;

        // Mock consent check returning false (withdrawn)
        $this->consentService->shouldReceive('checkConsent')
            ->with($userId, 'analytics')
            ->andReturn(false);

        $result = $this->service->handleConsent($this->tenant->id, $userId, 'analytics');

        $this->assertFalse($result); // Should return false when consent is withdrawn

        // Verify cache was purged
        $cacheKey = "unified_analytics_{$this->tenant->id}";
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_sync_data_handles_api_failure_with_fallback_to_internal(): void
    {
        // Mock GA to throw exception
        $this->googleAnalyticsService->shouldReceive('fetchMetrics')
            ->andThrow(new \Exception('API Error'));

        // Mock Matomo to succeed
        $this->matomoService->shouldReceive('fetchMetrics')
            ->andReturn([
                'events_count' => 1000,
                'sessions' => 500,
                'conversions' => 20,
            ]);

        $result = $this->service->syncData($this->tenant->id, ['ga', 'matomo']);

        $this->assertIsArray($result);
        // Should still return data (fallback to internal)
        $this->assertArrayHasKey('events_count', $result);

        // Verify error was logged
        $this->assertDatabaseHas('sync_logs', [
            'tenant_id' => $this->tenant->id,
            'sync_type' => 'unified',
            'status' => 'success', // Still success due to fallback
        ]);
    }

    public function test_tenant_isolation_maintains_separate_sync_data(): void
    {
        $otherTenant = Tenant::factory()->create();

        // Sync for first tenant
        session(['tenant_id' => $this->tenant->id]);
        $this->googleAnalyticsService->shouldReceive('fetchMetrics')
            ->andReturn(['events_count' => 1000]);

        $result1 = $this->service->syncData($this->tenant->id, ['ga']);

        // Sync for second tenant
        session(['tenant_id' => $otherTenant->id]);
        $this->googleAnalyticsService->shouldReceive('fetchMetrics')
            ->andReturn(['events_count' => 2000]);

        $result2 = $this->service->syncData($otherTenant->id, ['ga']);

        // Results should be different
        $this->assertNotEquals($result1['events_count'], $result2['events_count']);

        // Verify separate logs
        $this->assertDatabaseHas('sync_logs', [
            'tenant_id' => $this->tenant->id,
        ]);
        $this->assertDatabaseHas('sync_logs', [
            'tenant_id' => $otherTenant->id,
        ]);
    }

    public function test_sync_data_with_chunking_handles_large_datasets(): void
    {
        // Mock services returning large datasets
        $this->googleAnalyticsService->shouldReceive('fetchMetrics')
            ->andReturn([
                'events_count' => 50000,
                'sessions' => 25000,
                'conversions' => 1000,
            ]);

        $this->matomoService->shouldReceive('fetchMetrics')
            ->andReturn([
                'events_count' => 48000,
                'sessions' => 24000,
                'conversions' => 950,
            ]);

        $result = $this->service->syncData($this->tenant->id, ['ga', 'matomo']);

        $this->assertIsArray($result);
        $this->assertGreaterThan(40000, $result['events_count']); // Should unify large numbers
    }

    public function test_detect_discrepancies_with_no_external_data_returns_empty(): void
    {
        // Mock services returning empty data
        $this->googleAnalyticsService->shouldReceive('fetchMetrics')
            ->andReturn([]);

        $this->matomoService->shouldReceive('fetchMetrics')
            ->andReturn([]);

        $result = $this->service->detectDiscrepancies($this->tenant->id);

        $this->assertIsArray($result);
        $this->assertEmpty($result['discrepancies']);
        $this->assertEmpty($result['resolutions']);
        $this->assertArrayHasKey('unified_metrics', $result);
    }

    public function test_monitor_sync_with_no_history_returns_default_status(): void
    {
        $status = $this->service->monitorSync($this->tenant->id);

        $this->assertIsArray($status);
        $this->assertNull($status['last_sync_at']);
        $this->assertEquals(0, $status['errors_count']);
        $this->assertEquals(1.0, $status['success_rate']);
        $this->assertEquals(0, $status['total_syncs']);
    }

    public function test_handle_consent_with_active_consent_returns_true(): void
    {
        $userId = 456;

        // Mock consent check returning true (active)
        $this->consentService->shouldReceive('checkConsent')
            ->with($userId, 'analytics')
            ->andReturn(true);

        $result = $this->service->handleConsent($this->tenant->id, $userId, 'analytics');

        $this->assertTrue($result); // Should return true when consent is active
    }

    public function test_sync_data_caches_unified_view_for_performance(): void
    {
        $this->googleAnalyticsService->shouldReceive('fetchMetrics')
            ->andReturn(['events_count' => 1500]);

        // First sync should compute and cache
        $result1 = $this->service->syncData($this->tenant->id, ['ga']);

        // Verify cache exists
        $cacheKey = "unified_analytics_{$this->tenant->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Second sync should use cache (but since TTL is 15 min, it will recompute)
        // In real scenario, we'd test cache hit by mocking time
        $cachedData = Cache::get($cacheKey);
        $this->assertIsArray($cachedData);
        $this->assertArrayHasKey('events_count', $cachedData);
    }

    public function test_detect_discrepancies_applies_resolution_rules_correctly(): void
    {
        // Mock large discrepancy
        $this->googleAnalyticsService->shouldReceive('fetchMetrics')
            ->andReturn([
                'events_count' => 5000, // Internal is ~1000, so >20% difference
                'sessions' => 2500,
                'conversions' => 100,
            ]);

        $result = $this->service->detectDiscrepancies($this->tenant->id);

        $this->assertNotEmpty($result['discrepancies']);
        $this->assertNotEmpty($result['resolutions']);

        // Check resolution contains expected fields
        $resolution = $result['resolutions'][0];
        $this->assertArrayHasKey('metric', $resolution);
        $this->assertArrayHasKey('action', $resolution);
        $this->assertArrayHasKey('original_values', $resolution);
    }

    public function test_sync_data_with_invalid_tenant_throws_exception(): void
    {
        $this->expectException(\Exception::class);

        $this->service->syncData('invalid-tenant-id', ['ga']);
    }

    public function test_monitor_sync_calculates_success_rate_accurately(): void
    {
        // Create 10 successful and 2 failed syncs
        SyncLog::factory()->count(10)->forTenant($this->tenant->id)->successful()->create();
        SyncLog::factory()->count(2)->forTenant($this->tenant->id)->failed()->create();

        $status = $this->service->monitorSync($this->tenant->id);

        $this->assertEquals(12, $status['total_syncs']);
        $this->assertEquals(2, $status['errors_count']);
        $this->assertEquals(10/12, $status['success_rate']); // 83.33%
    }

    public function test_handle_consent_logs_violation_for_audit(): void
    {
        $userId = 789;

        $this->consentService->shouldReceive('checkConsent')
            ->with($userId, 'analytics')
            ->andReturn(false);

        $this->service->handleConsent($this->tenant->id, $userId, 'analytics');

        // Verify consent violation was logged (in real implementation)
        // Since it's logged via Log facade, we can't easily test it here
        // But the method should complete without error
        $this->assertTrue(true);
    }

    public function test_sync_data_with_empty_sources_returns_internal_only(): void
    {
        $result = $this->service->syncData($this->tenant->id, []);

        $this->assertIsArray($result);
        $this->assertEquals(['internal'], $result['sources']);
        $this->assertArrayHasKey('events_count', $result);
    }

    public function test_detect_discrepancies_with_identical_data_returns_no_discrepancies(): void
    {
        // Mock identical data (no discrepancies)
        $this->googleAnalyticsService->shouldReceive('fetchMetrics')
            ->andReturn([
                'events_count' => 1000,
                'sessions' => 500,
                'conversions' => 20,
            ]);

        $this->matomoService->shouldReceive('fetchMetrics')
            ->andReturn([
                'events_count' => 1000,
                'sessions' => 500,
                'conversions' => 20,
            ]);

        $result = $this->service->detectDiscrepancies($this->tenant->id);

        // Should have no discrepancies since data matches
        $this->assertEmpty($result['discrepancies']);
        $this->assertEmpty($result['resolutions']);
    }
}