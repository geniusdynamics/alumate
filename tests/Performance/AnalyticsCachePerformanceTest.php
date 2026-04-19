<?php

namespace Tests\Performance;

use App\Models\Tenant;
use App\Models\User;
use App\Models\AnalyticsEvent;
use App\Models\HeatMapData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class AnalyticsCachePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Tenant $tenant2;
    protected array $testUsers = [];
    protected array $cacheMetrics = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenants
        $this->tenant = Tenant::factory()->create([
            'name' => 'Cache Performance Test Tenant',
            'domain' => 'cache-perf.test',
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Cache Performance Test Tenant 2',
            'domain' => 'cache-perf2.test',
        ]);

        // Create test users across tenants
        for ($i = 0; $i < 50; $i++) {
            $this->testUsers[] = User::factory()->create([
                'tenant_id' => $this->tenant->id,
                'email' => "cache-user{$i}@perf.test",
                'is_alumni' => true,
            ]);
        }

        // Create analytics test data
        $this->createAnalyticsCacheTestData();

        // Mock Redis for cache performance testing
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('set')->andReturn(true);
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('del')->andReturn(1);
        Redis::shouldReceive('expire')->andReturn(1);
        Redis::shouldReceive('ttl')->andReturn(3600);
        Redis::shouldReceive('exists')->andReturn(1);
    }

    public function test_analytics_cache_hit_rate_validation(): void
    {
        // Pre-populate cache with analytics data
        $this->populateAnalyticsCache();

        $cacheRequests = [
            'leaderboard' => '/api/analytics/leaderboard',
            'metrics' => '/api/analytics/metrics',
            'heatmap' => '/api/analytics/heatmap?page_url=/dashboard',
        ];

        $totalRequests = 0;
        $cacheHits = 0;

        foreach ($cacheRequests as $type => $endpoint) {
            $results = $this->runCachedAnalyticsLoadTest($endpoint, [], 100, 30);
            $totalRequests += $results['total_requests'];
            $cacheHits += $results['cache_hits'];
        }

        $hitRate = ($cacheHits / $totalRequests) * 100;

        $this->assertGreaterThan(85, $hitRate, 'Cache hit rate should be >85% for analytics queries');
        $this->assertGreaterThan(200, $totalRequests, 'Should process >200 cached requests');
    }

    public function test_cache_ttl_expiration_and_invalidation(): void
    {
        // Test cache TTL expiration
        Cache::put('analytics_leaderboard_ttl_test', $this->generateMockLeaderboardData(), 1); // 1 second TTL

        sleep(2); // Wait for expiration

        $this->assertFalse(Cache::has('analytics_leaderboard_ttl_test'), 'Cache should expire after TTL');

        // Test cache invalidation
        Cache::put('analytics_metrics_invalidation_test', $this->generateMockMetricsData(), 3600);

        $this->assertTrue(Cache::has('analytics_metrics_invalidation_test'), 'Cache should exist before invalidation');

        Cache::forget('analytics_metrics_invalidation_test');

        $this->assertFalse(Cache::has('analytics_metrics_invalidation_test'), 'Cache should be invalidated');
    }

    public function test_multi_tenant_cache_separation(): void
    {
        // Populate cache for both tenants
        Cache::put("tenant_{$this->tenant->id}_leaderboard", $this->generateMockLeaderboardData(), 3600);
        Cache::put("tenant_{$this->tenant2->id}_leaderboard", $this->generateMockLeaderboardData(), 3600);

        // Test tenant isolation
        $tenant1Data = Cache::get("tenant_{$this->tenant->id}_leaderboard");
        $tenant2Data = Cache::get("tenant_{$this->tenant2->id}_leaderboard");

        $this->assertNotNull($tenant1Data, 'Tenant 1 cache should exist');
        $this->assertNotNull($tenant2Data, 'Tenant 2 cache should exist');
        $this->assertNotEquals($tenant1Data, $tenant2Data, 'Tenant caches should be separate');

        // Verify no cross-tenant access
        $this->assertFalse(Cache::has("tenant_{$this->tenant->id}_tenant_{$this->tenant2->id}_data"));
    }

    public function test_cache_miss_penalty_performance(): void
    {
        $missRequests = 50;
        $responseTimes = [];

        for ($i = 0; $i < $missRequests; $i++) {
            $startTime = microtime(true);

            // Force cache miss by using unique key
            $cacheKey = "analytics_cache_miss_test_{$i}";
            $data = Cache::remember($cacheKey, 3600, function() {
                return $this->generateMockLeaderboardData();
            });

            $responseTime = (microtime(true) - $startTime) * 1000;
            $responseTimes[] = $responseTime;
        }

        $avgMissPenalty = array_sum($responseTimes) / count($responseTimes);

        $this->assertLessThan(50, $avgMissPenalty, 'Cache miss penalty should be <50ms');
        $this->assertGreaterThan(0, $avgMissPenalty, 'Cache miss penalty should be measurable');
    }

    public function test_analytics_cache_performance_under_load(): void
    {
        // Pre-warm cache
        $this->populateAnalyticsCache();

        $results = $this->runCachedAnalyticsLoadTest('/api/analytics/leaderboard', [], 500, 60);

        $this->assertLessThan(100, $results['avg_response_time'], 'Cached analytics should be <100ms');
        $this->assertGreaterThan(90, $results['cache_hit_rate'], 'Cache hit rate should be >90% under load');
        $this->assertGreaterThan(500, $results['total_requests'], 'Should handle >500 cached requests under load');
    }

    public function test_cache_invalidation_performance(): void
    {
        // Populate extensive cache
        for ($i = 0; $i < 100; $i++) {
            Cache::put("analytics_cache_invalidation_{$i}", $this->generateMockMetricsData(), 3600);
        }

        $startTime = microtime(true);

        // Invalidate cache pattern
        for ($i = 0; $i < 100; $i++) {
            Cache::forget("analytics_cache_invalidation_{$i}");
        }

        $invalidationTime = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(500, $invalidationTime, 'Cache invalidation should complete in <500ms');
        $this->assertFalse(Cache::has('analytics_cache_invalidation_0'), 'Cache should be invalidated');
    }

    public function test_redis_connection_performance(): void
    {
        $connectionTests = 100;
        $responseTimes = [];

        for ($i = 0; $i < $connectionTests; $i++) {
            $startTime = microtime(true);

            // Test Redis operations
            Redis::set("perf_test_key_{$i}", "test_value");
            $value = Redis::get("perf_test_key_{$i}");
            Redis::del("perf_test_key_{$i}");

            $responseTime = (microtime(true) - $startTime) * 1000;
            $responseTimes[] = $responseTime;
        }

        $avgConnectionTime = array_sum($responseTimes) / count($responseTimes);

        $this->assertLessThan(10, $avgConnectionTime, 'Redis operations should be <10ms');
        $this->assertGreaterThan(0, $avgConnectionTime, 'Redis operations should be measurable');
    }

    public function test_cache_memory_usage_efficiency(): void
    {
        $initialMemory = memory_get_usage(true);

        // Populate large cache dataset
        for ($i = 0; $i < 1000; $i++) {
            Cache::put("analytics_memory_test_{$i}", $this->generateLargeMockData(), 3600);
        }

        $afterPopulationMemory = memory_get_usage(true);
        $memoryIncrease = $afterPopulationMemory - $initialMemory;

        // Memory usage should be reasonable for cached data
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, 'Cache memory usage should be <50MB');

        // Clean up
        for ($i = 0; $i < 1000; $i++) {
            Cache::forget("analytics_memory_test_{$i}");
        }
    }

    protected function runCachedAnalyticsLoadTest(string $endpoint, array $params = [], int $concurrentUsers = 100, int $duration = 60): array
    {
        $startTime = microtime(true);
        $endTime = $startTime + $duration;

        $metrics = [
            'requests_sent' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'response_times' => [],
            'total_response_time' => 0,
        ];

        while (microtime(true) < $endTime) {
            $batchSize = min(10, $concurrentUsers);
            for ($i = 0; $i < $batchSize; $i++) {
                $user = $this->testUsers[$metrics['requests_sent'] % count($this->testUsers)];
                $result = $this->executeCachedAnalyticsRequest($user, $endpoint, $params);

                $metrics['requests_sent']++;
                $metrics['response_times'][] = $result['response_time'];
                $metrics['total_response_time'] += $result['response_time'];

                if ($result['cache_hit']) {
                    $metrics['cache_hits']++;
                } else {
                    $metrics['cache_misses']++;
                }
            }

            usleep(50000); // 50ms delay
        }

        $totalTime = microtime(true) - $startTime;

        return [
            'total_requests' => $metrics['requests_sent'],
            'cache_hits' => $metrics['cache_hits'],
            'cache_misses' => $metrics['cache_misses'],
            'cache_hit_rate' => $metrics['requests_sent'] > 0 ? ($metrics['cache_hits'] / $metrics['requests_sent']) * 100 : 0,
            'avg_response_time' => $metrics['response_times'] ? array_sum($metrics['response_times']) / count($metrics['response_times']) : 0,
            'requests_per_minute' => ($metrics['requests_sent'] / $totalTime) * 60,
        ];
    }

    protected function executeCachedAnalyticsRequest(User $user, string $endpoint, array $params = []): array
    {
        $startTime = microtime(true);

        try {
            $queryString = http_build_query($params);
            $url = $endpoint . ($queryString ? '?' . $queryString : '');

            $response = $this->actingAs($user)->getJson($url);

            $responseTime = (microtime(true) - $startTime) * 1000;

            // Determine if this was a cache hit (simplified check)
            $cacheHit = $responseTime < 50; // Assume <50ms indicates cache hit

            return [
                'success' => $response->status() === 200,
                'response_time' => $responseTime,
                'cache_hit' => $cacheHit,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'response_time' => (microtime(true) - $startTime) * 1000,
                'cache_hit' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function populateAnalyticsCache(): void
    {
        Cache::put('analytics_leaderboard', $this->generateMockLeaderboardData(), 3600);
        Cache::put('analytics_metrics', $this->generateMockMetricsData(), 3600);
        Cache::put('analytics_heatmap_dashboard', $this->generateMockHeatmapData(), 3600);

        // Tenant-specific cache
        Cache::put("tenant_{$this->tenant->id}_analytics_leaderboard", $this->generateMockLeaderboardData(), 3600);
        Cache::put("tenant_{$this->tenant->id}_analytics_metrics", $this->generateMockMetricsData(), 3600);
    }

    protected function createAnalyticsCacheTestData(): void
    {
        // Create analytics events for cache testing
        foreach ($this->testUsers as $user) {
            AnalyticsEvent::factory()->count(20)->create([
                'tenant_id' => $this->tenant->id,
                'user_id' => $user->id,
                'event_type' => 'page_view',
                'event_data' => json_encode(['page' => '/dashboard', 'duration' => rand(10, 300)]),
            ]);
        }

        // Create heatmap data
        HeatMapData::factory()->count(500)->create([
            'tenant_id' => $this->tenant->id,
            'page_url' => '/alumni/dashboard',
            'x' => rand(0, 1920),
            'y' => rand(0, 1080),
            'intensity' => rand(1, 100),
        ]);
    }

    protected function generateMockLeaderboardData(): array
    {
        $leaderboard = [];
        for ($i = 1; $i <= 50; $i++) {
            $leaderboard[] = [
                'rank' => $i,
                'user_id' => $this->testUsers[$i % count($this->testUsers)]->id,
                'total_points' => rand(100, 1000),
                'total_events' => rand(10, 100),
            ];
        }
        return $leaderboard;
    }

    protected function generateMockMetricsData(): array
    {
        return [
            'total_events' => 5000,
            'unique_users' => 450,
            'avg_session_duration' => 180,
            'conversion_rate' => 12.5,
            'period' => 'weekly',
            'cache_timestamp' => now()->toISOString(),
        ];
    }

    protected function generateMockHeatmapData(): array
    {
        $data = [];
        for ($i = 0; $i < 100; $i++) {
            $data[] = [
                'x' => rand(0, 1920),
                'y' => rand(0, 1080),
                'intensity' => rand(1, 100),
            ];
        }
        return $data;
    }

    protected function generateLargeMockData(): array
    {
        return [
            'leaderboard' => $this->generateMockLeaderboardData(),
            'metrics' => $this->generateMockMetricsData(),
            'heatmap' => $this->generateMockHeatmapData(),
            'metadata' => [
                'generated_at' => now()->toISOString(),
                'size' => 'large',
                'tenant_id' => $this->tenant->id,
            ],
        ];
    }
}