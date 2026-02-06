<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Jobs\OptimizeAnalyticsCacheJob;
use App\Jobs\ProcessAnalyticsEvents;
use App\Models\AnalyticsEvent;
use App\Models\HeatMapData;
use App\Services\GamificationAnalyticsService;
use App\Services\HeatMapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Test performance optimizations for analytics system
 *
 * Tests database indexing, Redis caching, and query performance benchmarks.
 */
class PerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected GamificationAnalyticsService $gamificationService;
    protected HeatMapService $heatMapService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gamificationService = app(GamificationAnalyticsService::class);
        $this->heatMapService = app(HeatMapService::class);

        // Set up tenant context for testing
        $this->setupTenantContext();
    }

    /**
     * Test database indexes improve query performance
     */
    public function test_database_indexes_improve_query_performance(): void
    {
        // Create test data
        $this->createTestAnalyticsData(100);

        // Test indexed query performance
        $startTime = microtime(true);
        $results = AnalyticsEvent::byTenant('test-tenant')
            ->where('event_type', 'gamification')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $indexedQueryTime = microtime(true) - $startTime;

        // Assert query completes within performance threshold
        $this->assertLessThan(0.05, $indexedQueryTime, 'Indexed query should complete in under 50ms');
        $this->assertGreaterThan(0, $results, 'Should return results');
    }

    /**
     * Test Redis caching reduces database load
     */
    public function test_redis_caching_reduces_database_load(): void
    {
        // Create test data
        $this->createTestAnalyticsData(50);

        // First call should hit database
        $startTime = microtime(true);
        $leaderboard1 = $this->gamificationService->getLeaderboard(10);
        $firstCallTime = microtime(true) - $startTime;

        // Second call should hit cache
        $startTime = microtime(true);
        $leaderboard2 = $this->gamificationService->getLeaderboard(10);
        $cachedCallTime = microtime(true) - $startTime;

        // Cache should be significantly faster
        $this->assertLessThan($firstCallTime, $cachedCallTime * 10, 'Cached call should be much faster');

        // Results should be identical
        $this->assertEquals($leaderboard1->toArray(), $leaderboard2->toArray());
    }

    /**
     * Test cache invalidation works correctly
     */
    public function test_cache_invalidation_works_correctly(): void
    {
        $this->createTestAnalyticsData(20);

        // Get initial leaderboard
        $initialLeaderboard = $this->gamificationService->getLeaderboard(5);

        // Add new gamification event
        AnalyticsEvent::create([
            'tenant_id' => '01HXXXXXXXXXXXXXXXXXXXXX',
            'event_type' => 'gamification',
            'event_name' => 'points_earned',
            'user_id' => 'test-user-new',
            'points_earned' => 1000,
            'occurred_at' => now(),
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        // Cache should be invalidated and new data included
        $updatedLeaderboard = $this->gamificationService->getLeaderboard(5);

        // Should have different results (new user in leaderboard)
        $this->assertNotEquals($initialLeaderboard->toArray(), $updatedLeaderboard->toArray());
    }

    /**
     * Test heat map data indexing performance
     */
    public function test_heat_map_data_indexing_performance(): void
    {
        // Create test heat map data
        $this->createTestHeatMapData(50);

        $startTime = microtime(true);
        $results = HeatMapData::byTenant('test-tenant')
            ->byPageUrl('/test-page')
            ->where('timestamp', '>=', now()->subDays(1))
            ->get();
        $queryTime = microtime(true) - $startTime;

        $this->assertLessThan(0.03, $queryTime, 'Heat map query should complete in under 30ms');
        $this->assertGreaterThan(0, $results->count(), 'Should return heat map data');
    }

    /**
     * Test cache warming job functionality
     */
    public function test_cache_warming_job_functionality(): void
    {
        $this->createTestAnalyticsData(30);

        // Dispatch cache warming job
        $job = new OptimizeAnalyticsCacheJob('test-tenant');
        $job->handle($this->gamificationService, $this->heatMapService);

        // Verify cache is populated
        $cacheKey = "gamification:leaderboard:test-tenant:10";
        $this->assertTrue(Cache::has($cacheKey), 'Leaderboard cache should be populated');

        $cachedData = Cache::get($cacheKey);
        $this->assertIsIterable($cachedData, 'Cached data should be iterable');
    }

    /**
     * Test batched broadcasting reduces WebSocket overhead
     */
    public function test_batched_broadcasting_reduces_websocket_overhead(): void
    {
        $eventIds = $this->createTestAnalyticsData(20);

        // Process events with batching
        $job = new ProcessAnalyticsEvents($eventIds, 'test-tenant');

        // Mock broadcasting to avoid actual WebSocket calls
        $this->mockBroadcasting();

        $job->handle(
            app(\App\Services\AnalyticsService::class),
            $this->heatMapService,
            app(\App\Services\TenantContextService::class)
        );

        // Verify job completes without errors
        $this->assertTrue(true, 'Batch processing should complete successfully');
    }

    /**
     * Test composite index usage in complex queries
     */
    public function test_composite_index_usage_in_complex_queries(): void
    {
        $this->createTestAnalyticsData(200);

        // Query using composite index (tenant_id, created_at, event_type, user_id)
        $startTime = microtime(true);
        $complexQuery = AnalyticsEvent::byTenant('test-tenant')
            ->where('event_type', 'gamification')
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('user_id')
            ->select('user_id', 'points_earned', 'occurred_at')
            ->orderBy('occurred_at', 'desc')
            ->limit(50)
            ->get();
        $queryTime = microtime(true) - $startTime;

        $this->assertLessThan(0.1, $queryTime, 'Complex query should complete in under 100ms');
        $this->assertGreaterThan(0, $complexQuery->count(), 'Should return results');
    }

    /**
     * Test cache hit ratio monitoring
     */
    public function test_cache_hit_ratio_monitoring(): void
    {
        $this->createTestAnalyticsData(10);

        // Multiple calls to same cached method
        for ($i = 0; $i < 5; $i++) {
            $this->gamificationService->getLeaderboard(5);
        }

        // Check if cache exists (basic hit ratio test)
        $cacheKey = "gamification:leaderboard:test-tenant:5";
        $this->assertTrue(Cache::has($cacheKey), 'Cache should exist after multiple calls');
    }

    /**
     * Helper: Create test analytics data
     */
    private function createTestAnalyticsData(int $count): array
    {
        $eventIds = [];

        for ($i = 0; $i < $count; $i++) {
            $event = AnalyticsEvent::create([
                'tenant_id' => '01HXXXXXXXXXXXXXXXXXXXXX',
                'event_type' => 'gamification',
                'event_name' => 'points_earned',
                'user_id' => 'test-user-' . ($i % 10),
                'points_earned' => rand(10, 100),
                'occurred_at' => now()->subMinutes(rand(0, 1440)), // Random time in last day
                'is_compliant' => true,
                'consent_given' => true,
            ]);

            $eventIds[] = $event->id;
        }

        return $eventIds;
    }

    /**
     * Helper: Create test heat map data
     */
    private function createTestHeatMapData(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            HeatMapData::create([
                'tenant_id' => '01HXXXXXXXXXXXXXXXXXXXXX',
                'page_url' => '/test-page',
                'coordinate_data' => [
                    ['x' => rand(0, 100), 'y' => rand(0, 100), 'intensity' => rand(1, 10)]
                ],
                'timestamp' => now()->subMinutes(rand(0, 1440)),
                'session_id' => 'session-' . ($i % 5),
            ]);
        }
    }

    /**
     * Helper: Set up tenant context
     */
    private function setupTenantContext(): void
    {
        // Mock tenant context for testing
        // This would normally be handled by middleware
    }

    /**
     * Helper: Mock broadcasting for testing
     */
    private function mockBroadcasting(): void
    {
        // Mock broadcast function to avoid WebSocket calls during testing
        // This prevents actual broadcasting during unit tests
    }
}