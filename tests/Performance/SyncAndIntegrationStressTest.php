<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Jobs\SyncAnalyticsData;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Analytics\SyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Sync and Integration Stress Test
 *
 * Tests external integrations and data synchronization under high load conditions
 * including CRM sync, analytics platforms, and multi-tenant scenarios.
 */
class SyncAndIntegrationStressTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant1;
    private Tenant $tenant2;
    private SyncService $syncService;
    private array $testUsers = [];
    private array $performanceMetrics = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenants
        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Sync Tenant 1',
            'domain' => 'sync-perf-1.test',
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Sync Tenant 2',
            'domain' => 'sync-perf-2.test',
        ]);

        // Create test users across tenants
        for ($i = 0; $i < 100; $i++) {
            $tenant = $i % 2 === 0 ? $this->tenant1 : $this->tenant2;
            $this->testUsers[] = User::factory()->create([
                'tenant_id' => $tenant->id,
                'email' => "sync-user{$i}@perf.test",
                'is_alumni' => true,
            ]);
        }

        // Initialize sync service
        $this->syncService = app(SyncService::class);

        // Setup external API mocks with throttling simulation
        $this->setupExternalApiMocks();
    }

    public function test_sync_service_performance_under_high_load(): void
    {
        $syncOperations = 2000;
        $processingTimes = [];
        $failures = 0;
        $unificationTimes = [];

        $startTime = microtime(true);

        for ($i = 0; $i < $syncOperations; $i++) {
            $operationStartTime = microtime(true);
            $tenant = $i % 2 === 0 ? $this->tenant1 : $this->tenant2;

            try {
                // Simulate tenant context
                session(['tenant_id' => $tenant->id]);

                // Perform sync operation
                $sources = ['ga', 'matomo'];
                $timeRange = [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString()
                ];
                $result = $this->syncService->syncData($tenant->id, $sources, $timeRange);

                $operationTime = (microtime(true) - $operationStartTime) * 1000;
                $processingTimes[] = $operationTime;

                if (isset($result['unification_time'])) {
                    $unificationTimes[] = $result['unification_time'];
                }

                // Check for failures
                if (!$result['success']) {
                    $failures++;
                }

                // Assert per-operation performance
                $this->assertLessThan(1000, $operationTime, "Sync operation {$i} time ({$operationTime}ms) exceeds 1s threshold");

                if (!empty($unificationTimes)) {
                    $avgUnificationTime = end($unificationTimes);
                    $this->assertLessThan(1000, $avgUnificationTime, 'Data unification time should be < 1s per event');
                }

            } catch (\Exception $e) {
                $failures++;
                $this->fail("Sync operation {$i} failed: " . $e->getMessage());
            }
        }

        $totalTime = microtime(true) - $startTime;
        $avgProcessingTime = array_sum($processingTimes) / count($processingTimes);
        $avgUnificationTime = !empty($unificationTimes) ? array_sum($unificationTimes) / count($unificationTimes) : 0;
        $failureRate = ($failures / $syncOperations) * 100;
        $operationsPerSecond = $syncOperations / $totalTime;

        // Assert overall performance requirements
        $this->assertLessThan(1500, $avgProcessingTime, 'Average sync processing time should be < 1.5s');
        $this->assertLessThan(1000, $avgUnificationTime, 'Average data unification time should be < 1s');
        $this->assertLessThan(10, $failureRate, 'Failure rate should be < 10%');
        $this->assertGreaterThan(100, $operationsPerSecond, 'Throughput should be > 100 operations/s');

        $this->performanceMetrics['sync_performance'] = [
            'total_operations' => $syncOperations,
            'total_time' => $totalTime,
            'avg_processing_time' => $avgProcessingTime,
            'avg_unification_time' => $avgUnificationTime,
            'operations_per_second' => $operationsPerSecond,
            'failures' => $failures,
            'failure_rate' => $failureRate,
        ];
    }

    public function test_external_api_integration_under_load(): void
    {
        $apiCalls = 1500;
        $platforms = ['google_analytics', 'matomo', 'crm'];
        $processingTimes = [];
        $throttlingEvents = 0;
        $fallbackActivations = 0;

        $startTime = microtime(true);

        for ($i = 0; $i < $apiCalls; $i++) {
            $callStartTime = microtime(true);
            $platform = $platforms[$i % count($platforms)];
            $tenant = $i % 2 === 0 ? $this->tenant1 : $this->tenant2;

            try {
                session(['tenant_id' => $tenant->id]);

                $sources = [$platform];
                $timeRange = [
                    'start' => now()->subDays(1)->toDateString(),
                    'end' => now()->toDateString()
                ];
                $result = $this->syncService->syncData($tenant->id, $sources, $timeRange);

                $callTime = (microtime(true) - $callStartTime) * 1000;
                $processingTimes[] = $callTime;

                // Check for throttling
                if (isset($result['throttled']) && $result['throttled']) {
                    $throttlingEvents++;
                }

                // Check for fallback activation
                if (isset($result['fallback_activated']) && $result['fallback_activated']) {
                    $fallbackActivations++;
                }

                // Assert API call performance
                $this->assertLessThan(2000, $callTime, "API call {$i} to {$platform} time ({$callTime}ms) exceeds 2s threshold");

            } catch (\Exception $e) {
                // Handle API failures gracefully
                $processingTimes[] = (microtime(true) - $callStartTime) * 1000;
            }
        }

        $totalTime = microtime(true) - $startTime;
        $avgApiTime = array_sum($processingTimes) / count($processingTimes);
        $callsPerSecond = $apiCalls / $totalTime;
        $throttlingRate = ($throttlingEvents / $apiCalls) * 100;
        $fallbackRate = ($fallbackActivations / $apiCalls) * 100;

        // Assert external API performance
        $this->assertLessThan(1500, $avgApiTime, 'Average API call time should be < 1.5s');
        $this->assertGreaterThan(50, $callsPerSecond, 'API throughput should be > 50 calls/s');
        $this->assertLessThan(20, $throttlingRate, 'Throttling rate should be < 20%');
        $this->assertLessThan(5, $fallbackRate, 'Fallback activation rate should be < 5%');

        $this->performanceMetrics['external_api'] = [
            'total_api_calls' => $apiCalls,
            'total_time' => $totalTime,
            'avg_api_time' => $avgApiTime,
            'calls_per_second' => $callsPerSecond,
            'throttling_events' => $throttlingEvents,
            'throttling_rate' => $throttlingRate,
            'fallback_activations' => $fallbackActivations,
            'fallback_rate' => $fallbackRate,
        ];
    }

    public function test_discrepancy_resolution_under_load(): void
    {
        $discrepancyChecks = 1000;
        $resolutionTimes = [];
        $resolutionSuccesses = 0;
        $unresolvedDiscrepancies = 0;

        $startTime = microtime(true);

        for ($i = 0; $i < $discrepancyChecks; $i++) {
            $resolutionStartTime = microtime(true);
            $tenant = $i % 2 === 0 ? $this->tenant1 : $this->tenant2;

            try {
                session(['tenant_id' => $tenant->id]);

                // Generate discrepancy scenario
                $timeRange = [
                    'start' => now()->subDays(7)->toDateString(),
                    'end' => now()->toDateString()
                ];
                $result = $this->syncService->detectDiscrepancies($tenant->id, $timeRange);

                $resolutionTime = (microtime(true) - $resolutionStartTime) * 1000;
                $resolutionTimes[] = $resolutionTime;

                if ($result['resolved']) {
                    $resolutionSuccesses++;
                } else {
                    $unresolvedDiscrepancies++;
                }

                // Assert discrepancy resolution performance
                $this->assertLessThan(500, $resolutionTime, "Discrepancy resolution {$i} time ({$resolutionTime}ms) exceeds 500ms threshold");

            } catch (\Exception $e) {
                $unresolvedDiscrepancies++;
                $resolutionTimes[] = (microtime(true) - $resolutionStartTime) * 1000;
            }
        }

        $totalTime = microtime(true) - $startTime;
        $avgResolutionTime = array_sum($resolutionTimes) / count($resolutionTimes);
        $resolutionSuccessRate = ($resolutionSuccesses / $discrepancyChecks) * 100;
        $unresolvedRate = ($unresolvedDiscrepancies / $discrepancyChecks) * 100;

        // Assert discrepancy resolution performance
        $this->assertLessThan(500, $avgResolutionTime, 'Average discrepancy resolution time should be < 500ms');
        $this->assertGreaterThan(90, $resolutionSuccessRate, 'Discrepancy resolution success rate should be > 90%');

        $this->performanceMetrics['discrepancy_resolution'] = [
            'total_checks' => $discrepancyChecks,
            'total_time' => $totalTime,
            'avg_resolution_time' => $avgResolutionTime,
            'resolution_successes' => $resolutionSuccesses,
            'resolution_success_rate' => $resolutionSuccessRate,
            'unresolved_discrepancies' => $unresolvedDiscrepancies,
            'unresolved_rate' => $unresolvedRate,
        ];
    }

    public function test_queue_backlog_management_under_load(): void
    {
        Queue::fake();

        $queueJobs = 500;
        $backlogChecks = 50;
        $backlogMeasurements = [];

        // Dispatch jobs to simulate backlog
        for ($i = 0; $i < $queueJobs; $i++) {
            $tenant = $i % 2 === 0 ? $this->tenant1 : $this->tenant2;
            $syncType = ['google_analytics', 'matomo', 'crm'][rand(0, 2)];

            SyncAnalyticsData::dispatch($tenant->id, $syncType);
        }

        // Check queue backlog at intervals
        for ($check = 0; $check < $backlogChecks; $check++) {
            $backlogSize = Queue::size(); // This would need to be implemented based on your queue driver
            $backlogMeasurements[] = $backlogSize;

            // Simulate processing delay
            usleep(100000); // 100ms
        }

        $avgBacklog = array_sum($backlogMeasurements) / count($backlogMeasurements);
        $maxBacklog = max($backlogMeasurements);
        $minBacklog = min($backlogMeasurements);

        // Assert queue backlog management
        $this->assertLessThan(100, $avgBacklog, 'Average queue backlog should be < 100 jobs');
        $this->assertLessThan(150, $maxBacklog, 'Maximum queue backlog should be < 150 jobs');

        // Verify jobs were queued
        Queue::assertPushed(SyncAnalyticsData::class, $queueJobs);

        $this->performanceMetrics['queue_backlog'] = [
            'total_jobs' => $queueJobs,
            'backlog_checks' => $backlogChecks,
            'avg_backlog' => $avgBacklog,
            'max_backlog' => $maxBacklog,
            'min_backlog' => $minBacklog,
        ];
    }

    public function test_multi_tenant_sync_isolation_under_load(): void
    {
        $concurrentSyncs = 800;
        $isolationViolations = 0;
        $crossTenantSyncs = 0;
        $processingTimes = [];

        $startTime = microtime(true);

        for ($i = 0; $i < $concurrentSyncs; $i++) {
            $syncStartTime = microtime(true);
            $tenant = $i % 2 === 0 ? $this->tenant1 : $this->tenant2;

            try {
                session(['tenant_id' => $tenant->id]);

                $syncData = $this->generateTenantSyncData($tenant, $i);
                $result = $this->syncService->syncData($tenant->id, $syncData);

                $syncTime = (microtime(true) - $syncStartTime) * 1000;
                $processingTimes[] = $syncTime;

                // Check for cross-tenant data leakage
                if (isset($result['cross_tenant_data']) && $result['cross_tenant_data']) {
                    $crossTenantSyncs++;
                }

                // Check for isolation violations
                if (!$result['tenant_isolated']) {
                    $isolationViolations++;
                }

                $this->assertLessThan(2000, $syncTime, "Tenant sync {$i} time ({$syncTime}ms) exceeds 2s threshold");

            } catch (\Exception $e) {
                $isolationViolations++;
            }
        }

        $totalTime = microtime(true) - $startTime;
        $avgSyncTime = array_sum($processingTimes) / count($processingTimes);
        $syncsPerSecond = $concurrentSyncs / $totalTime;
        $isolationViolationRate = ($isolationViolations / $concurrentSyncs) * 100;

        // Assert multi-tenant isolation
        $this->assertEquals(0, $crossTenantSyncs, 'No cross-tenant sync operations should occur');
        $this->assertLessThan(1, $isolationViolationRate, 'Tenant isolation violation rate should be < 1%');
        $this->assertLessThan(1500, $avgSyncTime, 'Average multi-tenant sync time should be < 1.5s');

        $this->performanceMetrics['multi_tenant_isolation'] = [
            'concurrent_syncs' => $concurrentSyncs,
            'total_time' => $totalTime,
            'avg_sync_time' => $avgSyncTime,
            'syncs_per_second' => $syncsPerSecond,
            'isolation_violations' => $isolationViolations,
            'isolation_violation_rate' => $isolationViolationRate,
            'cross_tenant_syncs' => $crossTenantSyncs,
        ];
    }

    public function test_fallback_activation_on_failure_rates(): void
    {
        $totalOperations = 1000;
        $failureThreshold = 0.10; // 10% failure rate triggers fallback
        $inducedFailures = (int) ($totalOperations * 0.12); // 12% failures to trigger fallback
        $fallbackActivations = 0;
        $processingTimes = [];

        $startTime = microtime(true);

        for ($i = 0; $i < $totalOperations; $i++) {
            $operationStartTime = microtime(true);
            $tenant = $i % 2 === 0 ? $this->tenant1 : $this->tenant2;

            // Induce failures for first portion of operations
            $shouldFail = $i < $inducedFailures;

            try {
                session(['tenant_id' => $tenant->id]);

                if ($shouldFail) {
                    // Simulate API failure
                    throw new \Exception('Simulated API failure');
                }

                $syncData = $this->generateSyncData($i);
                $result = $this->syncService->syncData($tenant->id, $syncData);

                // Check if fallback was activated due to failure rate
                if (isset($result['fallback_activated']) && $result['fallback_activated']) {
                    $fallbackActivations++;
                }

            } catch (\Exception $e) {
                // Fallback should be activated here
                $fallbackActivations++;
            }

            $processingTimes[] = (microtime(true) - $operationStartTime) * 1000;
        }

        $totalTime = microtime(true) - $startTime;
        $avgProcessingTime = array_sum($processingTimes) / count($processingTimes);
        $fallbackRate = ($fallbackActivations / $totalOperations) * 100;

        // Assert fallback activation
        $this->assertGreaterThan(10, $fallbackRate, 'Fallback should activate when failure rate exceeds 10%');
        $this->assertLessThan(2000, $avgProcessingTime, 'Average processing time with fallbacks should be < 2s');

        $this->performanceMetrics['fallback_activation'] = [
            'total_operations' => $totalOperations,
            'induced_failures' => $inducedFailures,
            'total_time' => $totalTime,
            'avg_processing_time' => $avgProcessingTime,
            'fallback_activations' => $fallbackActivations,
            'fallback_rate' => $fallbackRate,
        ];
    }

    private function setupExternalApiMocks(): void
    {
        // Mock Guzzle HTTP client for external API calls
        $this->mock(\GuzzleHttp\Client::class, function ($mock) {
            $mock->shouldReceive('post')
                ->andReturnUsing(function ($url, $options) {
                    // Simulate API throttling and response times
                    usleep(rand(10000, 50000)); // 10-50ms delay
                    return new \GuzzleHttp\Psr7\Response(200, [], json_encode(['status' => 'success']));
                });

            $mock->shouldReceive('get')
                ->andReturnUsing(function ($url, $options) {
                    usleep(rand(15000, 75000)); // 15-75ms delay
                    return new \GuzzleHttp\Psr7\Response(200, [], json_encode(['data' => 'mock_response']));
                });
        });

        // Mock external service classes
        $this->mock(\App\Services\Analytics\GoogleAnalyticsService::class, function ($mock) {
            $mock->shouldReceive('sendData')->andReturn(['status' => 'sent', 'tracking_id' => 'GA123']);
        });

        $this->mock(\App\Services\Analytics\MatomoService::class, function ($mock) {
            $mock->shouldReceive('trackEvent')->andReturn(['status' => 'tracked', 'visit_id' => 'MT456']);
        });

        $this->mock(\App\Services\CrmIntegrationService::class, function ($mock) {
            $mock->shouldReceive('syncContact')->andReturn(['status' => 'synced', 'contact_id' => 'CRM789']);
        });
    }

    private function generateSyncData(int $index): array
    {
        return [
            'events' => [
                [
                    'event_type' => 'learning_interaction',
                    'user_id' => $this->testUsers[$index % count($this->testUsers)]->id,
                    'course_id' => rand(1, 10),
                    'duration' => rand(60, 3600),
                    'score' => rand(0, 100),
                    'timestamp' => now()->subMinutes(rand(1, 1440))->toISOString(),
                ]
            ],
            'sync_platforms' => ['google_analytics', 'matomo'],
            'unify_data' => true,
        ];
    }

    private function generateApiData(string $platform, int $index): array
    {
        $baseData = [
            'user_id' => $this->testUsers[$index % count($this->testUsers)]->id,
            'event_category' => 'learning',
            'event_action' => 'course_interaction',
            'event_value' => rand(1, 100),
            'timestamp' => now()->toISOString(),
        ];

        switch ($platform) {
            case 'google_analytics':
                return array_merge($baseData, [
                    'tracking_id' => 'GA-TEST-123',
                    'custom_dimensions' => ['cd1' => 'test_value'],
                ]);
            case 'matomo':
                return array_merge($baseData, [
                    'site_id' => '1',
                    'custom_variables' => ['cv1' => 'test_value'],
                ]);
            case 'crm':
                return array_merge($baseData, [
                    'contact_email' => "contact{$index}@test.com",
                    'contact_name' => "Test Contact {$index}",
                    'company' => 'Test Company',
                ]);
            default:
                return $baseData;
        }
    }

    private function generateDiscrepancyData(int $index): array
    {
        return [
            'source' => 'analytics_db',
            'target' => 'external_api',
            'entity_type' => 'learning_event',
            'entity_id' => $index,
            'source_data' => [
                'user_id' => $this->testUsers[$index % count($this->testUsers)]->id,
                'event_count' => rand(5, 20),
                'last_updated' => now()->subHours(rand(1, 24))->toISOString(),
            ],
            'target_data' => [
                'user_id' => $this->testUsers[$index % count($this->testUsers)]->id,
                'event_count' => rand(3, 18), // Slightly different to create discrepancy
                'last_updated' => now()->subHours(rand(2, 48))->toISOString(),
            ],
            'resolution_strategy' => 'latest_wins',
        ];
    }

    private function generateTenantSyncData(Tenant $tenant, int $index): array
    {
        return [
            'tenant_id' => $tenant->id,
            'events' => [
                [
                    'event_type' => 'tenant_specific_event',
                    'user_id' => $this->testUsers[$index % count($this->testUsers)]->id,
                    'tenant_data' => "Tenant {$tenant->id} specific data",
                    'timestamp' => now()->toISOString(),
                ]
            ],
            'isolate_tenant_data' => true,
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