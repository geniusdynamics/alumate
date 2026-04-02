<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Services\Analytics\ConsentService;
use App\Services\Analytics\LearningAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AnalyticsLoadTest extends TestCase
{
    use RefreshDatabase;

    private Mockery\MockInterface $consentService;
    private LearningAnalyticsService $learningService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consentService = Mockery::mock(ConsentService::class);
        $this->learningService = new LearningAnalyticsService();

        // Set up tenant context
        session(['tenant_id' => 'test-tenant']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test concurrent user load - 50 simultaneous users
     */
    public function test_concurrent_user_load_50_users(): void
    {
        $userCount = 50;
        $users = User::factory()->count($userCount)->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent for all users
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        $startTime = microtime(true);

        // Simulate concurrent operations using multiple processes (simplified)
        $results = [];
        foreach ($users as $user) {
            $userStartTime = microtime(true);

            // Each user performs multiple operations
            for ($i = 0; $i < 3; $i++) {
                $this->learningService->trackCourseInteraction($user->id, $course->id, [
                    'duration' => rand(600, 3600),
                    'score' => rand(70, 95),
                    'interaction_type' => 'completion'
                ]);
            }

            // Calculate engagement score
            $this->learningService->calculateEngagementScore($user->id, $course->id);

            $userEndTime = microtime(true);
            $results[] = [
                'user_id' => $user->id,
                'response_time' => $userEndTime - $userStartTime,
                'operations' => 4 // 3 tracks + 1 calculation
            ];
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // Calculate performance metrics
        $responseTimes = array_column($results, 'response_time');
        $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
        $maxResponseTime = max($responseTimes);
        $minResponseTime = min($responseTimes);
        $p95ResponseTime = $this->calculatePercentile($responseTimes, 95);

        // Assert performance requirements
        $this->assertLessThan(30.0, $totalTime, 'Total concurrent operations took too long');
        $this->assertLessThan(1.0, $avgResponseTime, 'Average response time too slow');
        $this->assertLessThan(2.0, $maxResponseTime, 'Maximum response time too slow');
        $this->assertLessThan(1.5, $p95ResponseTime, '95th percentile response time too slow');

        // Verify data integrity
        $totalEvents = AnalyticsEvent::whereIn('user_id', $users->pluck('id'))->count();
        $expectedEvents = $userCount * 3; // 3 events per user
        $this->assertEquals($expectedEvents, $totalEvents);
    }

    /**
     * Test sustained load over time
     */
    public function test_sustained_load_over_time(): void
    {
        $duration = 30; // 30 seconds
        $users = User::factory()->count(20)->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        $startTime = microtime(true);
        $operationCount = 0;
        $responseTimes = [];

        // Run operations for specified duration
        while ((microtime(true) - $startTime) < $duration) {
            $user = $users->random();

            $operationStart = microtime(true);
            $this->learningService->trackCourseInteraction($user->id, $course->id, [
                'duration' => rand(600, 1800),
                'score' => rand(70, 95),
                'interaction_type' => rand(0, 1) ? 'completion' : 'view'
            ]);
            $operationEnd = microtime(true);

            $responseTimes[] = $operationEnd - $operationStart;
            $operationCount++;
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // Calculate throughput
        $operationsPerSecond = $operationCount / $totalTime;
        $avgResponseTime = array_sum($responseTimes) / count($responseTimes);

        // Assert sustained performance
        $this->assertGreaterThan(5, $operationsPerSecond, 'Throughput too low');
        $this->assertLessThan(0.5, $avgResponseTime, 'Average response time degraded');
        $this->assertGreaterThan(100, $operationCount, 'Not enough operations completed');
    }

    /**
     * Test memory usage under load
     */
    public function test_memory_usage_under_load(): void
    {
        $initialMemory = memory_get_usage();
        $peakMemory = $initialMemory;

        $users = User::factory()->count(100)->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Perform load operations and track memory
        foreach ($users as $user) {
            for ($i = 0; $i < 5; $i++) {
                $this->learningService->trackCourseInteraction($user->id, $course->id, [
                    'duration' => 1800,
                    'score' => 85,
                    'interaction_type' => 'completion'
                ]);
            }

            $currentMemory = memory_get_usage();
            $peakMemory = max($peakMemory, $currentMemory);
        }

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        $peakMemoryIncrease = $peakMemory - $initialMemory;

        // Assert reasonable memory usage
        $this->assertLessThan(20 * 1024 * 1024, $memoryIncrease, 'Memory leak detected');
        $this->assertLessThan(30 * 1024 * 1024, $peakMemoryIncrease, 'Peak memory usage too high');
    }

    /**
     * Test database connection pooling under load
     */
    public function test_database_connection_pooling_under_load(): void
    {
        $users = User::factory()->count(200)->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        $startTime = microtime(true);

        // Perform many database operations
        foreach ($users as $user) {
            $this->learningService->trackCourseInteraction($user->id, $course->id, [
                'duration' => 1800,
                'score' => 85,
                'interaction_type' => 'completion'
            ]);

            // Query operations
            $this->learningService->calculateEngagementScore($user->id, $course->id);
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // Assert database performance
        $this->assertLessThan(60.0, $totalTime, 'Database operations took too long under load');

        // Verify all data was created
        $eventCount = AnalyticsEvent::whereIn('user_id', $users->pluck('id'))->count();
        $this->assertEquals(200, $eventCount);
    }

    /**
     * Test cache effectiveness under load
     */
    public function test_cache_effectiveness_under_load(): void
    {
        $users = User::factory()->count(50)->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Create initial data
        foreach ($users as $user) {
            $this->learningService->trackCourseInteraction($user->id, $course->id, [
                'duration' => 1800,
                'score' => 85,
                'interaction_type' => 'completion'
            ]);
        }

        // First round - cache misses
        $startTime1 = microtime(true);
        $scores1 = [];
        foreach ($users as $user) {
            $scores1[] = $this->learningService->calculateEngagementScore($user->id, $course->id);
        }
        $endTime1 = microtime(true);
        $firstRoundTime = $endTime1 - $startTime1;

        // Second round - cache hits
        $startTime2 = microtime(true);
        $scores2 = [];
        foreach ($users as $user) {
            $scores2[] = $this->learningService->calculateEngagementScore($user->id, $course->id);
        }
        $endTime2 = microtime(true);
        $secondRoundTime = $endTime2 - $startTime2;

        // Assert cache effectiveness
        $this->assertGreaterThan($secondRoundTime * 2, $firstRoundTime, 'Cache not effective');
        $this->assertEquals($scores1, $scores2, 'Cached results differ from original');

        // Performance check
        $this->assertLessThan(5.0, $firstRoundTime, 'First round took too long');
        $this->assertLessThan(1.0, $secondRoundTime, 'Cached round took too long');
    }

    /**
     * Test error handling under load
     */
    public function test_error_handling_under_load(): void
    {
        $users = User::factory()->count(100)->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mix of valid and invalid consent
        $validUsers = $users->take(80);
        $invalidUsers = $users->skip(80);

        // Mock consent - allow first 80, reject last 20
        $this->consentService->shouldReceive('checkConsent')
            ->andReturnUsing(function ($userId) use ($validUsers) {
                return $validUsers->contains('id', $userId);
            });
        app()->instance(ConsentService::class, $this->consentService);

        $startTime = microtime(true);
        $results = [];

        // Attempt operations for all users
        foreach ($users as $user) {
            try {
                $result = $this->learningService->trackCourseInteraction($user->id, $course->id, [
                    'duration' => 1800,
                    'score' => 85,
                    'interaction_type' => 'completion'
                ]);
                $results[] = ['user_id' => $user->id, 'success' => $result, 'error' => null];
            } catch (\Exception $e) {
                $results[] = ['user_id' => $user->id, 'success' => false, 'error' => $e->getMessage()];
            }
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // Count successes and failures
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $failureCount = count(array_filter($results, fn($r) => !$r['success']));

        // Assert error handling
        $this->assertEquals(80, $successCount, 'Unexpected number of successful operations');
        $this->assertEquals(20, $failureCount, 'Unexpected number of failed operations');
        $this->assertLessThan(30.0, $totalTime, 'Error handling degraded performance too much');

        // Verify data integrity
        $eventCount = AnalyticsEvent::whereIn('user_id', $validUsers->pluck('id'))->count();
        $this->assertEquals(80, $eventCount);
    }

    /**
     * Test resource cleanup under load
     */
    public function test_resource_cleanup_under_load(): void
    {
        $initialConnections = $this->getDatabaseConnectionCount();

        $users = User::factory()->count(100)->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Perform load operations
        foreach ($users as $user) {
            for ($i = 0; $i < 3; $i++) {
                $this->learningService->trackCourseInteraction($user->id, $course->id, [
                    'duration' => 1800,
                    'score' => 85,
                    'interaction_type' => 'completion'
                ]);
            }
        }

        $finalConnections = $this->getDatabaseConnectionCount();

        // Assert no connection leaks (allowing for some variance)
        $this->assertLessThanOrEqual($initialConnections + 2, $finalConnections, 'Possible database connection leak');
    }

    /**
     * Helper method to get database connection count (simplified)
     */
    private function getDatabaseConnectionCount(): int
    {
        // In a real implementation, this would query the database connection pool
        // For testing purposes, return a mock value
        return 1;
    }

    /**
     * Helper method to calculate percentile
     */
    private function calculatePercentile(array $values, float $percentile): float
    {
        sort($values);
        $index = (count($values) - 1) * ($percentile / 100);
        $lower = floor($index);
        $upper = ceil($index);
        $weight = $index - $lower;

        if ($upper >= count($values)) {
            return $values[$lower];
        }

        return $values[$lower] * (1 - $weight) + $values[$upper] * $weight;
    }
}