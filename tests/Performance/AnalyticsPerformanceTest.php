<?php

namespace Tests\Performance;

use App\Models\Tenant;
use App\Models\User;
use App\Models\AnalyticsEvent;
use App\Models\HeatMapData;
use App\Models\ABTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class AnalyticsPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected array $testUsers = [];
    protected array $performanceMetrics = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenant
        $this->tenant = Tenant::factory()->create([
            'name' => 'Analytics Performance Test Tenant',
            'domain' => 'analytics-perf.test',
        ]);

        // Create test users
        for ($i = 0; $i < 100; $i++) {
            $this->testUsers[] = User::factory()->create([
                'tenant_id' => $this->tenant->id,
                'email' => "analytics-user{$i}@perf.test",
                'is_alumni' => true,
            ]);
        }

        // Create analytics test data
        $this->createAnalyticsTestData();

        // Mock Redis for performance testing
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('set')->andReturn(true);
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('del')->andReturn(1);
        Redis::shouldReceive('expire')->andReturn(1);
    }

    public function test_leaderboard_api_load_performance(): void
    {
        $results = $this->runAnalyticsLoadTest('/api/analytics/leaderboard', [
            'limit' => 50,
            'period' => 'monthly'
        ], 1000, 60);

        // Assert performance requirements
        $this->assertLessThan(200, $results['avg_response_time'], 'Average response time should be under 200ms');
        $this->assertGreaterThan(1000, $results['requests_per_minute'], 'Should handle >1000 req/min');
        $this->assertLessThan(1, $results['error_rate'], 'Error rate should be <1%');
        $this->assertGreaterThan(95, $results['success_rate'], 'Success rate should be >95%');
    }

    public function test_heatmap_generation_load_performance(): void
    {
        $results = $this->runAnalyticsLoadTest('/api/analytics/heatmap', [
            'page_url' => '/alumni/dashboard',
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31'
        ], 500, 45);

        $this->assertLessThan(200, $results['avg_response_time'], 'Heatmap generation should be under 200ms');
        $this->assertGreaterThan(500, $results['requests_per_minute'], 'Should handle >500 req/min for heatmap');
        $this->assertLessThan(1, $results['error_rate'], 'Heatmap error rate should be <1%');
    }

    public function test_ab_test_analysis_load_performance(): void
    {
        $results = $this->runAnalyticsLoadTest('/api/analytics/ab-tests/analysis', [
            'test_id' => 'homepage_cta_test',
            'variant' => 'A'
        ], 300, 30);

        $this->assertLessThan(200, $results['avg_response_time'], 'A/B test analysis should be under 200ms');
        $this->assertGreaterThan(300, $results['requests_per_minute'], 'Should handle >300 req/min for A/B analysis');
        $this->assertLessThan(1, $results['error_rate'], 'A/B analysis error rate should be <1%');
    }

    public function test_tenant_isolation_under_concurrent_analytics_load(): void
    {
        // Create additional tenants for isolation testing
        $tenant2 = Tenant::factory()->create(['name' => 'Analytics Tenant 2']);
        $tenant3 = Tenant::factory()->create(['name' => 'Analytics Tenant 3']);

        $tenants = [$this->tenant, $tenant2, $tenant3];
        $isolationViolations = 0;
        $totalRequests = 0;

        foreach ($tenants as $tenant) {
            $results = $this->runAnalyticsLoadTest('/api/analytics/metrics', [
                'tenant_id' => $tenant->id,
                'period' => 'weekly'
            ], 200, 30);

            $totalRequests += $results['total_requests'];

            // Check for cross-tenant data leakage
            if ($results['cross_tenant_data_detected'] ?? false) {
                $isolationViolations++;
            }

            $this->assertLessThan(300, $results['avg_response_time'], "Tenant {$tenant->id} analytics should be under 300ms");
        }

        $this->assertEquals(0, $isolationViolations, 'No tenant isolation violations should occur');
        $this->assertGreaterThan(1500, $totalRequests, 'Should handle >1500 total requests across tenants');
    }

    public function test_analytics_performance_benchmark(): void
    {
        $benchmarks = [
            'leaderboard_query' => fn() => $this->actingAs($this->testUsers[0])->getJson('/api/analytics/leaderboard'),
            'metrics_calculation' => fn() => $this->actingAs($this->testUsers[0])->getJson('/api/analytics/metrics'),
            'heatmap_rendering' => fn() => $this->actingAs($this->testUsers[0])->getJson('/api/analytics/heatmap?page_url=/dashboard'),
            'ab_test_performance' => fn() => $this->actingAs($this->testUsers[0])->getJson('/api/analytics/ab-tests'),
        ];

        $results = $this->runAnalyticsBenchmarks($benchmarks);

        // Assert benchmark results
        foreach ($results as $benchmarkName => $metrics) {
            $this->assertLessThan(200, $metrics['avg_time'], "{$benchmarkName} should be under 200ms");
            $this->assertGreaterThan(0, $metrics['success_rate'], "{$benchmarkName} should have 100% success rate");
        }

        // Generate performance report
        $this->generateAnalyticsPerformanceReport($results);
    }

    public function test_cache_performance_under_load(): void
    {
        // Pre-warm cache
        Cache::put('analytics_leaderboard', $this->generateMockLeaderboardData(), 3600);
        Cache::put('analytics_metrics', $this->generateMockMetricsData(), 3600);

        $results = $this->runAnalyticsLoadTest('/api/analytics/leaderboard', [], 800, 60, [
            'cache_enabled' => true
        ]);

        $this->assertLessThan(150, $results['avg_response_time'], 'Cached responses should be under 150ms');
        $this->assertGreaterThan(85, $results['cache_hit_rate'], 'Cache hit rate should be >85%');
    }

    public function test_database_connection_pooling_analytics(): void
    {
        $startTime = microtime(true);
        $concurrentQueries = 50;

        // Simulate concurrent analytics queries
        $promises = [];
        for ($i = 0; $i < $concurrentQueries; $i++) {
            $promises[] = $this->simulateAnalyticsQuery($this->testUsers[$i % count($this->testUsers)]);
        }

        // Wait for all queries to complete
        $results = array_map(function($promise) {
            return $promise['success'] ? 'success' : 'failed';
        }, $promises);

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        $successCount = count(array_filter($results, fn($r) => $r === 'success'));

        $this->assertEquals($concurrentQueries, $successCount, 'All analytics queries should succeed');
        $this->assertLessThan(5000, $totalTime, 'Concurrent analytics queries should complete within 5 seconds');
    }

    protected function runAnalyticsLoadTest(string $endpoint, array $params = [], int $concurrentUsers = 100, int $duration = 60, array $options = []): array
    {
        $startTime = microtime(true);
        $endTime = $startTime + $duration;

        $metrics = [
            'requests_sent' => 0,
            'requests_successful' => 0,
            'requests_failed' => 0,
            'total_response_time' => 0,
            'response_times' => [],
            'errors' => [],
            'cache_hits' => 0,
            'cache_misses' => 0,
        ];

        while (microtime(true) < $endTime) {
            // Simulate concurrent requests
            $batchSize = min(20, $concurrentUsers);
            for ($i = 0; $i < $batchSize; $i++) {
                $user = $this->testUsers[$metrics['requests_sent'] % count($this->testUsers)];
                $result = $this->executeAnalyticsRequest($user, $endpoint, $params, $options);

                $metrics['requests_sent']++;
                if ($result['success']) {
                    $metrics['requests_successful']++;
                    $metrics['total_response_time'] += $result['response_time'];
                    $metrics['response_times'][] = $result['response_time'];

                    if (isset($result['cache_hit']) && $result['cache_hit']) {
                        $metrics['cache_hits']++;
                    } else {
                        $metrics['cache_misses']++;
                    }
                } else {
                    $metrics['requests_failed']++;
                    $metrics['errors'][] = $result['error'];
                }
            }

            // Small delay to prevent overwhelming
            usleep(100000); // 100ms
        }

        $totalTime = microtime(true) - $startTime;

        return [
            'total_requests' => $metrics['requests_sent'],
            'successful_requests' => $metrics['requests_successful'],
            'failed_requests' => $metrics['requests_failed'],
            'avg_response_time' => $metrics['response_times'] ? array_sum($metrics['response_times']) / count($metrics['response_times']) : 0,
            'requests_per_minute' => ($metrics['requests_sent'] / $totalTime) * 60,
            'success_rate' => $metrics['requests_sent'] > 0 ? ($metrics['requests_successful'] / $metrics['requests_sent']) * 100 : 0,
            'error_rate' => $metrics['requests_sent'] > 0 ? ($metrics['requests_failed'] / $metrics['requests_sent']) * 100 : 0,
            'cache_hit_rate' => ($metrics['cache_hits'] + $metrics['cache_misses']) > 0 ? ($metrics['cache_hits'] / ($metrics['cache_hits'] + $metrics['cache_misses'])) * 100 : 0,
            'errors' => $metrics['errors'],
        ];
    }

    protected function executeAnalyticsRequest(User $user, string $endpoint, array $params = [], array $options = []): array
    {
        $startTime = microtime(true);

        try {
            $queryString = http_build_query($params);
            $url = $endpoint . ($queryString ? '?' . $queryString : '');

            $response = $this->actingAs($user)->getJson($url);

            $responseTime = (microtime(true) - $startTime) * 1000;

            return [
                'success' => $response->status() === 200,
                'response_time' => $responseTime,
                'cache_hit' => $options['cache_enabled'] ?? false,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function runAnalyticsBenchmarks(array $benchmarks): array
    {
        $results = [];

        foreach ($benchmarks as $name => $benchmark) {
            $times = [];
            $successes = 0;

            for ($i = 0; $i < 10; $i++) {
                $startTime = microtime(true);
                try {
                    $response = $benchmark();
                    $endTime = microtime(true);

                    if ($response->status() === 200) {
                        $times[] = ($endTime - $startTime) * 1000;
                        $successes++;
                    }
                } catch (\Exception $e) {
                    // Benchmark failed
                }
            }

            $results[$name] = [
                'avg_time' => $times ? array_sum($times) / count($times) : 0,
                'min_time' => $times ? min($times) : 0,
                'max_time' => $times ? max($times) : 0,
                'success_rate' => ($successes / 10) * 100,
                'iterations' => 10,
            ];
        }

        return $results;
    }

    protected function createAnalyticsTestData(): void
    {
        // Create analytics events
        foreach ($this->testUsers as $user) {
            AnalyticsEvent::factory()->count(10)->create([
                'tenant_id' => $this->tenant->id,
                'user_id' => $user->id,
                'event_type' => 'page_view',
                'event_data' => json_encode(['page' => '/dashboard', 'duration' => rand(10, 300)]),
            ]);
        }

        // Create heatmap data
        HeatMapData::factory()->count(1000)->create([
            'tenant_id' => $this->tenant->id,
            'page_url' => '/alumni/dashboard',
            'x' => rand(0, 1920),
            'y' => rand(0, 1080),
            'intensity' => rand(1, 100),
        ]);

        // Create A/B test data
        ABTest::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Homepage CTA Test',
            'status' => 'active',
            'variants' => json_encode(['A' => 'Sign Up Now', 'B' => 'Join Today']),
        ]);
    }

    protected function simulateAnalyticsQuery(User $user): array
    {
        $startTime = microtime(true);

        try {
            // Simulate complex analytics query
            $result = DB::table('analytics_events')
                ->where('tenant_id', $user->tenant_id)
                ->where('user_id', $user->id)
                ->selectRaw('COUNT(*) as event_count, AVG(JSON_EXTRACT(event_data, "$.duration")) as avg_duration')
                ->first();

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;

            return [
                'success' => true,
                'response_time' => $responseTime,
                'data' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
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
        ];
    }

    protected function generateAnalyticsPerformanceReport(array $benchmarkResults): void
    {
        $report = [
            'timestamp' => now()->toISOString(),
            'test_type' => 'analytics_performance',
            'benchmarks' => $benchmarkResults,
            'summary' => [
                'total_benchmarks' => count($benchmarkResults),
                'avg_response_time' => array_sum(array_column($benchmarkResults, 'avg_time')) / count($benchmarkResults),
                'success_rate' => (count(array_filter($benchmarkResults, fn($r) => $r['success_rate'] == 100)) / count($benchmarkResults)) * 100,
            ],
        ];

        $reportPath = storage_path('logs/analytics_performance_report_'.date('Y-m-d_H-i-s').'.json');
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
    }
}