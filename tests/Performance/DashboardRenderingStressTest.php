<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * Dashboard Rendering Stress Test
 *
 * Tests dashboard rendering performance under high concurrent load including
 * analytics endpoints, insights API, multi-tenant scenarios, and caching effects.
 */
class DashboardRenderingStressTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant1;
    private Tenant $tenant2;
    private array $testUsers = [];
    private array $performanceMetrics = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenants
        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Dashboard Tenant 1',
            'domain' => 'dashboard-perf-1.test',
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Dashboard Tenant 2',
            'domain' => 'dashboard-perf-2.test',
        ]);

        // Create test users across tenants
        for ($i = 0; $i < 200; $i++) {
            $tenant = $i % 2 === 0 ? $this->tenant1 : $this->tenant2;
            $this->testUsers[] = User::factory()->create([
                'tenant_id' => $tenant->id,
                'email' => "dashboard-user{$i}@perf.test",
                'is_alumni' => true,
            ]);
        }

        // Pre-warm caches with realistic data
        $this->setupDashboardCache();

        // Mock Redis for performance testing
        Redis::shouldReceive('publish')->andReturn(1); // WebSocket emissions
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('set')->andReturn(true);
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('expire')->andReturn(1);
    }

    public function test_learning_analytics_dashboard_concurrent_load(): void
    {
        $concurrentRequests = 100;
        $endpoint = '/api/analytics/learning/index';

        $results = $this->runConcurrentDashboardLoadTest($endpoint, $concurrentRequests, [
            'period' => 'weekly',
            'include_trends' => true,
            'metrics' => ['engagement', 'completion', 'certification']
        ]);

        // Assert performance requirements
        $this->assertLessThan(300, $results['avg_response_time'], 'Average response time should be < 300ms');
        $this->assertGreaterThan(50, $results['requests_per_second'], 'Throughput should be > 50 req/s');
        $this->assertEquals(0, $results['error_rate'], 'Error rate should be 0%');
        $this->assertGreaterThan(95, $results['success_rate'], 'Success rate should be > 95%');

        $this->performanceMetrics['learning_dashboard'] = $results;
    }

    public function test_insights_dashboard_concurrent_load(): void
    {
        $concurrentRequests = 100;
        $endpoint = '/api/insights';

        $results = $this->runConcurrentDashboardLoadTest($endpoint, $concurrentRequests, [
            'date_from' => now()->subDays(30)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
            'group_by' => 'week',
            'include_anomalies' => true
        ]);

        // Assert performance requirements
        $this->assertLessThan(300, $results['avg_response_time'], 'Insights response time should be < 300ms');
        $this->assertGreaterThan(50, $results['requests_per_second'], 'Insights throughput should be > 50 req/s');
        $this->assertEquals(0, $results['error_rate'], 'Insights error rate should be 0%');

        $this->performanceMetrics['insights_dashboard'] = $results;
    }

    public function test_multi_tenant_dashboard_isolation_under_load(): void
    {
        $concurrentRequests = 150;
        $isolationViolations = 0;
        $crossTenantDataLeaks = 0;

        $startTime = microtime(true);
        $responses = [];
        $errors = [];

        // Run concurrent requests across both tenants
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $tenant = $i % 2 === 0 ? $this->tenant1 : $this->tenant2;
            $user = $this->testUsers[$i % count($this->testUsers)];

            try {
                // Simulate tenant context
                session(['tenant_id' => $tenant->id]);

                $response = $this->actingAs($user)->getJson('/api/analytics/learning/index?period=monthly');
                $responses[] = [
                    'status' => $response->status(),
                    'tenant_id' => $tenant->id,
                    'response_time' => microtime(true) - $startTime,
                    'data_count' => count($response->json('data') ?? [])
                ];

                // Check for cross-tenant data leakage
                if ($response->status() === 200) {
                    $responseData = $response->json();
                    if (isset($responseData['cross_tenant_data']) && $responseData['cross_tenant_data']) {
                        $crossTenantDataLeaks++;
                    }
                }

            } catch (\Exception $e) {
                $errors[] = [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage()
                ];
                $isolationViolations++;
            }
        }

        $totalTime = microtime(true) - $startTime;
        $avgResponseTime = ($totalTime / $concurrentRequests) * 1000;
        $requestsPerSecond = $concurrentRequests / $totalTime;
        $errorRate = (count($errors) / $concurrentRequests) * 100;

        // Assert tenant isolation
        $this->assertEquals(0, $crossTenantDataLeaks, 'No cross-tenant data leaks should occur');
        $this->assertLessThan(5, $isolationViolations, 'Isolation violations should be minimal');

        // Assert performance
        $this->assertLessThan(400, $avgResponseTime, 'Multi-tenant response time should be < 400ms');
        $this->assertGreaterThan(30, $requestsPerSecond, 'Multi-tenant throughput should be > 30 req/s');

        $this->performanceMetrics['multi_tenant_isolation'] = [
            'concurrent_requests' => $concurrentRequests,
            'total_time' => $totalTime,
            'avg_response_time' => $avgResponseTime,
            'requests_per_second' => $requestsPerSecond,
            'error_rate' => $errorRate,
            'isolation_violations' => $isolationViolations,
            'cross_tenant_leaks' => $crossTenantDataLeaks,
        ];
    }

    public function test_dashboard_caching_performance_under_load(): void
    {
        // Pre-warm caches
        Cache::put('dashboard_learning_tenant_1', $this->generateMockDashboardData(), 1800);
        Cache::put('dashboard_insights_tenant_1', $this->generateMockInsightsData(), 1800);
        Cache::put('dashboard_learning_tenant_2', $this->generateMockDashboardData(), 1800);
        Cache::put('dashboard_insights_tenant_2', $this->generateMockInsightsData(), 1800);

        $concurrentRequests = 200;
        $cacheHits = 0;
        $cacheMisses = 0;
        $processingTimes = [];

        $startTime = microtime(true);

        for ($i = 0; $i < $concurrentRequests; $i++) {
            $requestStartTime = microtime(true);
            $tenant = $i % 2 === 0 ? $this->tenant1 : $this->tenant2;
            $user = $this->testUsers[$i % count($this->testUsers)];
            $endpoint = $i % 2 === 0 ? '/api/analytics/learning/index' : '/api/insights';

            try {
                session(['tenant_id' => $tenant->id]);
                $response = $this->actingAs($user)->getJson($endpoint);

                if ($response->status() === 200) {
                    $headers = $response->headers();
                    if (isset($headers['X-Cache']) && $headers['X-Cache'] === 'HIT') {
                        $cacheHits++;
                    } else {
                        $cacheMisses++;
                    }
                }

            } catch (\Exception $e) {
                // Handle errors
            }

            $processingTimes[] = (microtime(true) - $requestStartTime) * 1000;
        }

        $totalTime = microtime(true) - $startTime;
        $avgResponseTime = array_sum($processingTimes) / count($processingTimes);
        $requestsPerSecond = $concurrentRequests / $totalTime;
        $cacheHitRate = $concurrentRequests > 0 ? (($cacheHits + $cacheMisses) > 0 ? ($cacheHits / ($cacheHits + $cacheMisses)) * 100 : 0) : 0;

        // Assert caching performance
        $this->assertLessThan(200, $avgResponseTime, 'Cached dashboard response time should be < 200ms');
        $this->assertGreaterThan(80, $cacheHitRate, 'Cache hit rate should be > 80%');
        $this->assertGreaterThan(80, $requestsPerSecond, 'Cached throughput should be > 80 req/s');

        $this->performanceMetrics['caching_performance'] = [
            'concurrent_requests' => $concurrentRequests,
            'total_time' => $totalTime,
            'avg_response_time' => $avgResponseTime,
            'requests_per_second' => $requestsPerSecond,
            'cache_hits' => $cacheHits,
            'cache_misses' => $cacheMisses,
            'cache_hit_rate' => $cacheHitRate,
        ];
    }

    public function test_websocket_emissions_under_dashboard_load(): void
    {
        $concurrentRequests = 100;
        $websocketEmissions = 0;
        $emissionFailures = 0;

        // Mock WebSocket broadcasting
        $this->mock(\Illuminate\Broadcasting\Broadcasters\RedisBroadcaster::class, function ($mock) {
            $mock->shouldReceive('broadcast')->andReturn(true);
        });

        $startTime = microtime(true);

        for ($i = 0; $i < $concurrentRequests; $i++) {
            $tenant = $i % 2 === 0 ? $this->tenant1 : $this->tenant2;
            $user = $this->testUsers[$i % count($this->testUsers)];

            try {
                session(['tenant_id' => $tenant->id]);

                // Request that should trigger WebSocket emissions
                $response = $this->actingAs($user)->getJson('/api/analytics/learning/index?realtime=true');

                if ($response->status() === 200) {
                    // Check if WebSocket emission was attempted
                    $responseData = $response->json();
                    if (isset($responseData['websocket_emitted']) && $responseData['websocket_emitted']) {
                        $websocketEmissions++;
                    }
                }

            } catch (\Exception $e) {
                $emissionFailures++;
            }
        }

        $totalTime = microtime(true) - $startTime;
        $avgResponseTime = ($totalTime / $concurrentRequests) * 1000;
        $emissionSuccessRate = ($websocketEmissions / $concurrentRequests) * 100;

        // Assert WebSocket performance under load
        $this->assertLessThan(350, $avgResponseTime, 'WebSocket-enabled response time should be < 350ms');
        $this->assertGreaterThan(90, $emissionSuccessRate, 'WebSocket emission success rate should be > 90%');

        $this->performanceMetrics['websocket_emissions'] = [
            'concurrent_requests' => $concurrentRequests,
            'total_time' => $totalTime,
            'avg_response_time' => $avgResponseTime,
            'websocket_emissions' => $websocketEmissions,
            'emission_failures' => $emissionFailures,
            'emission_success_rate' => $emissionSuccessRate,
        ];
    }

    public function test_dashboard_memory_usage_under_load(): void
    {
        $initialMemory = memory_get_usage(true);
        $concurrentRequests = 150;

        $startTime = microtime(true);
        $memoryPeaks = [];

        for ($i = 0; $i < $concurrentRequests; $i++) {
            $requestStartMemory = memory_get_usage(true);
            $tenant = $i % 2 === 0 ? $this->tenant1 : $this->tenant2;
            $user = $this->testUsers[$i % count($this->testUsers)];

            try {
                session(['tenant_id' => $tenant->id]);
                $this->actingAs($user)->getJson('/api/analytics/learning/index');
            } catch (\Exception $e) {
                // Continue testing
            }

            $requestMemory = memory_get_usage(true);
            $memoryPeaks[] = $requestMemory - $requestStartMemory;
        }

        $endMemory = memory_get_usage(true);
        $totalTime = microtime(true) - $startTime;
        $avgMemoryIncrease = array_sum($memoryPeaks) / count($memoryPeaks);
        $maxMemoryIncrease = max($memoryPeaks);
        $totalMemoryIncrease = $endMemory - $initialMemory;

        // Assert memory usage
        $this->assertLessThan(50 * 1024 * 1024, $maxMemoryIncrease, 'Max memory increase per request should be < 50MB');
        $this->assertLessThan(100 * 1024 * 1024, $totalMemoryIncrease, 'Total memory increase should be < 100MB');

        $this->performanceMetrics['memory_usage'] = [
            'concurrent_requests' => $concurrentRequests,
            'total_time' => $totalTime,
            'initial_memory' => $initialMemory,
            'end_memory' => $endMemory,
            'total_memory_increase' => $totalMemoryIncrease,
            'avg_memory_increase_per_request' => $avgMemoryIncrease,
            'max_memory_increase_per_request' => $maxMemoryIncrease,
        ];
    }

    protected function runConcurrentDashboardLoadTest(string $endpoint, int $concurrentRequests, array $params = []): array
    {
        $startTime = microtime(true);
        $responses = [];
        $errors = [];
        $responseTimes = [];

        $queryString = http_build_query($params);
        $fullEndpoint = $endpoint . ($queryString ? '?' . $queryString : '');

        // Simulate concurrent requests
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $requestStartTime = microtime(true);
            $user = $this->testUsers[$i % count($this->testUsers)];
            $tenant = $user->tenant_id === $this->tenant1->id ? $this->tenant1 : $this->tenant2;

            try {
                session(['tenant_id' => $tenant->id]);
                $response = $this->actingAs($user)->getJson($fullEndpoint);

                $responseTime = (microtime(true) - $requestStartTime) * 1000;
                $responseTimes[] = $responseTime;

                $responses[] = [
                    'status' => $response->status(),
                    'response_time' => $responseTime,
                    'success' => $response->status() === 200,
                ];

            } catch (\Exception $e) {
                $errors[] = [
                    'error' => $e->getMessage(),
                    'response_time' => (microtime(true) - $requestStartTime) * 1000,
                ];
            }
        }

        $totalTime = microtime(true) - $startTime;
        $successfulRequests = count(array_filter($responses, fn($r) => $r['success']));
        $avgResponseTime = count($responseTimes) > 0 ? array_sum($responseTimes) / count($responseTimes) : 0;

        return [
            'total_requests' => $concurrentRequests,
            'successful_requests' => $successfulRequests,
            'failed_requests' => count($errors),
            'avg_response_time' => $avgResponseTime,
            'requests_per_second' => $concurrentRequests / $totalTime,
            'success_rate' => ($successfulRequests / $concurrentRequests) * 100,
            'error_rate' => (count($errors) / $concurrentRequests) * 100,
            'total_time' => $totalTime,
            'endpoint' => $endpoint,
        ];
    }

    private function setupDashboardCache(): void
    {
        // Pre-populate dashboard caches with realistic data
        foreach ([$this->tenant1, $this->tenant2] as $tenant) {
            Cache::put("dashboard_learning_{$tenant->id}", $this->generateMockDashboardData(), 1800);
            Cache::put("dashboard_insights_{$tenant->id}", $this->generateMockInsightsData(), 1800);
            Cache::put("dashboard_metrics_{$tenant->id}", $this->generateMockMetricsData(), 1800);
        }
    }

    private function generateMockDashboardData(): array
    {
        return [
            'total_users' => rand(1000, 5000),
            'active_users' => rand(500, 2000),
            'total_interactions' => rand(5000, 25000),
            'avg_session_duration' => rand(300, 1800),
            'completion_rate' => rand(60, 95),
            'engagement_score' => rand(70, 95),
            'certification_rate' => rand(40, 80),
            'trends' => [
                'interactions' => array_map(fn() => rand(100, 500), range(1, 7)),
                'completions' => array_map(fn() => rand(50, 200), range(1, 7)),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    private function generateMockInsightsData(): array
    {
        return [
            'total_events' => rand(10000, 50000),
            'unique_users' => rand(800, 3000),
            'avg_events_per_user' => rand(5, 25),
            'peak_hours' => array_map(fn() => rand(10, 100), range(0, 23)),
            'top_events' => array_map(fn($i) => [
                'event_name' => "event_{$i}",
                'count' => rand(100, 1000),
                'unique_users' => rand(50, 500),
            ], range(1, 10)),
            'anomalies' => [],
            'generated_at' => now()->toISOString(),
        ];
    }

    private function generateMockMetricsData(): array
    {
        return [
            'page_views' => rand(5000, 20000),
            'unique_visitors' => rand(1000, 5000),
            'bounce_rate' => rand(20, 60),
            'conversion_rate' => rand(2, 15),
            'avg_time_on_page' => rand(120, 600),
            'generated_at' => now()->toISOString(),
        ];
    }

    protected function tearDown(): void
    {
        // Clean up performance metrics
        $this->performanceMetrics = [];

        // Clear cache
        Cache::flush();

        parent::tearDown();
    }
}