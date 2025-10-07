<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Models\AnalyticsEvent;
use App\Models\AttributionTouch;
use App\Models\Cohort;
use App\Models\LearningProgress;
use App\Models\User;
use App\Services\Analytics\AttributionService;
use App\Services\Analytics\CohortAnalysisService;
use App\Services\Analytics\ConsentService;
use App\Services\Analytics\InsightsService;
use App\Services\Analytics\LearningAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AnalyticsPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private Mockery\MockInterface $consentService;
    private CohortAnalysisService $cohortService;
    private AttributionService $attributionService;
    private LearningAnalyticsService $learningService;
    private InsightsService $insightsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consentService = Mockery::mock(ConsentService::class);
        $this->cohortService = new CohortAnalysisService($this->consentService);
        $this->attributionService = new AttributionService();
        $this->learningService = new LearningAnalyticsService();
        $this->insightsService = new InsightsService();

        // Set up tenant context
        session(['tenant_id' => 'test-tenant']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test learning score job performance with large batch
     */
    public function test_learning_score_job_performance_large_batch(): void
    {
        // Create large dataset
        $users = User::factory()->count(1000)->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Create learning events for each user
        foreach ($users as $user) {
            AnalyticsEvent::create([
                'tenant_id' => 'test-tenant',
                'event_type' => 'learning',
                'event_name' => 'course_interaction',
                'user_id' => $user->id,
                'properties' => [
                    'course_id' => $course->id,
                    'interaction_type' => 'completion',
                    'duration' => 1800,
                    'score' => 85
                ],
                'occurred_at' => now(),
                'is_compliant' => true,
                'consent_given' => true,
            ]);
        }

        // Prepare batch data
        $userCoursePairs = $users->map(fn($user) => [
            'user_id' => $user->id,
            'course_id' => $course->id
        ])->toArray();

        // Measure batch processing performance
        $startTime = microtime(true);
        $results = $this->learningService->processBatchScores($userCoursePairs);
        $endTime = microtime(true);

        $processingTime = $endTime - $startTime;

        // Assert performance requirements
        $this->assertEquals(1000, $results['processed']);
        $this->assertEquals(0, $results['errors']);
        $this->assertLessThan(10.0, $processingTime, 'Batch processing took too long');

        // Verify all progress records were created
        $progressCount = LearningProgress::whereIn('user_id', $users->pluck('id'))->count();
        $this->assertEquals(1000, $progressCount);
    }

    /**
     * Test consent purge job performance
     */
    public function test_consent_purge_job_performance(): void
    {
        // Create large dataset to purge
        $users = User::factory()->count(1000)->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Create learning data for all users
        foreach ($users as $user) {
            AnalyticsEvent::create([
                'tenant_id' => 'test-tenant',
                'event_type' => 'learning',
                'event_name' => 'course_interaction',
                'user_id' => $user->id,
                'properties' => [
                    'course_id' => $course->id,
                    'interaction_type' => 'completion'
                ],
                'occurred_at' => now(),
                'is_compliant' => true,
                'consent_given' => true,
            ]);

            LearningProgress::create([
                'tenant_id' => 'test-tenant',
                'user_id' => $user->id,
                'course_id' => $course->id,
                'engagement_score' => 85.0,
            ]);
        }

        // Verify data exists
        $eventCountBefore = AnalyticsEvent::whereIn('user_id', $users->pluck('id'))->count();
        $progressCountBefore = LearningProgress::whereIn('user_id', $users->pluck('id'))->count();

        $this->assertEquals(1000, $eventCountBefore);
        $this->assertEquals(1000, $progressCountBefore);

        // Measure purge performance
        $startTime = microtime(true);

        // Simulate purge operation
        AnalyticsEvent::whereIn('user_id', $users->pluck('id'))->delete();
        LearningProgress::whereIn('user_id', $users->pluck('id'))->delete();

        $endTime = microtime(true);
        $purgeTime = $endTime - $startTime;

        // Assert performance requirements
        $this->assertLessThan(5.0, $purgeTime, 'Data purge took too long');

        // Verify data was purged
        $eventCountAfter = AnalyticsEvent::whereIn('user_id', $users->pluck('id'))->count();
        $progressCountAfter = LearningProgress::whereIn('user_id', $users->pluck('id'))->count();

        $this->assertEquals(0, $eventCountAfter);
        $this->assertEquals(0, $progressCountAfter);
    }

    /**
     * Test cohort analysis performance with large dataset
     */
    public function test_cohort_analysis_performance_large_dataset(): void
    {
        // Create large cohort
        $cohort = Cohort::factory()->create(['members_count' => 5000]);

        // Create users and learning data
        $users = User::factory()->count(5000)->create(['graduation_year' => 2023]);

        // Mock consent
        $this->consentService->shouldReceive('hasConsentForAnalytics')->andReturn(true);

        // Create learning progress for all users
        foreach ($users as $user) {
            LearningProgress::create([
                'user_id' => $user->id,
                'engagement_score' => rand(60, 95),
                'created_at' => now()->subDays(rand(0, 90))
            ]);
        }

        // Measure analysis performance
        $startTime = microtime(true);
        $analysis = $this->cohortService->analyzeCohort($cohort->id);
        $endTime = microtime(true);

        $analysisTime = $endTime - $startTime;

        // Assert performance and correctness
        $this->assertLessThan(15.0, $analysisTime, 'Cohort analysis took too long');
        $this->assertEquals(5000, $analysis['size']);
        $this->assertArrayHasKey('retention', $analysis);
        $this->assertArrayHasKey('engagement', $analysis);
    }

    /**
     * Test attribution calculation performance
     */
    public function test_attribution_calculation_performance(): void
    {
        $user = User::factory()->create();

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Create many attribution touches
        for ($i = 0; $i < 100; $i++) {
            AttributionTouch::create([
                'tenant_id' => 'test-tenant',
                'user_id' => $user->id,
                'source' => ['google', 'facebook', 'email', 'direct'][rand(0, 3)],
                'event_type' => 'page_view',
                'value' => rand(5, 50),
                'timestamp' => now()->subDays(rand(0, 30)),
            ]);
        }

        // Measure attribution calculation performance
        $startTime = microtime(true);

        $lastTouch = $this->attributionService->calculateAttribution(
            $user->id,
            now()->subDays(30)->toDateString(),
            now()->toDateString(),
            'last_touch'
        );

        $linear = $this->attributionService->calculateAttribution(
            $user->id,
            now()->subDays(30)->toDateString(),
            now()->toDateString(),
            'linear'
        );

        $endTime = microtime(true);
        $calculationTime = $endTime - $startTime;

        // Assert performance and results
        $this->assertLessThan(2.0, $calculationTime, 'Attribution calculation took too long');
        $this->assertGreaterThan(0, $lastTouch['total_value']);
        $this->assertGreaterThan(0, $linear['total_value']);
        $this->assertNotEquals($lastTouch['sources'], $linear['sources']);
    }

    /**
     * Test insights generation performance
     */
    public function test_insights_generation_performance(): void
    {
        // Create substantial analytics data
        AnalyticsEvent::factory()->count(10000)->create([
            'tenant_id' => 'test-tenant',
            'event_type' => 'page_view',
            'created_at' => now()->subDays(rand(0, 30))
        ]);

        // Measure insights generation performance
        $startTime = microtime(true);
        $insights = $this->insightsService->generateInsights();
        $endTime = microtime(true);

        $generationTime = $endTime - $startTime;

        // Assert performance
        $this->assertLessThan(5.0, $generationTime, 'Insights generation took too long');
        $this->assertIsArray($insights);
    }

    /**
     * Test concurrent operations performance
     */
    public function test_concurrent_operations_performance(): void
    {
        $users = User::factory()->count(100)->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Measure concurrent operations
        $startTime = microtime(true);

        // Simulate concurrent learning tracking
        foreach ($users as $user) {
            $this->learningService->trackCourseInteraction($user->id, $course->id, [
                'duration' => 1800,
                'score' => 85,
                'interaction_type' => 'completion'
            ]);

            // Also track attribution
            $this->attributionService->trackTouch([
                'user_id' => $user->id,
                'event_type' => 'page_view',
                'source' => 'dashboard',
                'value' => 10.00
            ]);
        }

        $endTime = microtime(true);
        $concurrentTime = $endTime - $startTime;

        // Assert performance
        $this->assertLessThan(20.0, $concurrentTime, 'Concurrent operations took too long');

        // Verify data integrity
        $eventCount = AnalyticsEvent::whereIn('user_id', $users->pluck('id'))->count();
        $touchCount = AttributionTouch::whereIn('user_id', $users->pluck('id'))->count();

        $this->assertEquals(100, $eventCount);
        $this->assertEquals(100, $touchCount);
    }

    /**
     * Test cache performance for repeated operations
     */
    public function test_cache_performance_repeated_operations(): void
    {
        $user = User::factory()->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Create initial data
        $this->learningService->trackCourseInteraction($user->id, $course->id, [
            'duration' => 1800,
            'score' => 85,
            'interaction_type' => 'completion'
        ]);

        // Measure first calculation (cache miss)
        $startTime1 = microtime(true);
        $score1 = $this->learningService->calculateEngagementScore($user->id, $course->id);
        $endTime1 = microtime(true);
        $firstCallTime = $endTime1 - $startTime1;

        // Measure second calculation (cache hit)
        $startTime2 = microtime(true);
        $score2 = $this->learningService->calculateEngagementScore($user->id, $course->id);
        $endTime2 = microtime(true);
        $secondCallTime = $endTime2 - $startTime2;

        // Assert caching improves performance
        $this->assertGreaterThan($secondCallTime, $firstCallTime * 0.5, 'Caching should improve performance');
        $this->assertEquals($score1, $score2, 'Cached result should match original');
    }

    /**
     * Test memory usage during large operations
     */
    public function test_memory_usage_large_operations(): void
    {
        $initialMemory = memory_get_usage();

        // Create large dataset
        $users = User::factory()->count(2000)->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Perform memory-intensive operation
        $userCoursePairs = $users->map(fn($user) => [
            'user_id' => $user->id,
            'course_id' => $course->id
        ])->toArray();

        $this->learningService->processBatchScores($userCoursePairs);

        $finalMemory = memory_get_usage();
        $memoryUsed = $finalMemory - $initialMemory;

        // Assert reasonable memory usage (less than 50MB)
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Memory usage too high');
    }

    /**
     * Test database query performance
     */
    public function test_database_query_performance(): void
    {
        // Create large dataset
        AnalyticsEvent::factory()->count(50000)->create([
            'tenant_id' => 'test-tenant',
            'event_type' => 'page_view'
        ]);

        // Measure query performance
        $startTime = microtime(true);

        $result = AnalyticsEvent::where('tenant_id', 'test-tenant')
            ->where('event_type', 'page_view')
            ->count();

        $endTime = microtime(true);
        $queryTime = $endTime - $startTime;

        // Assert query performance
        $this->assertLessThan(1.0, $queryTime, 'Database query took too long');
        $this->assertEquals(50000, $result);
    }

    /**
     * Test API response time simulation
     */
    public function test_api_response_time_simulation(): void
    {
        // Create test data
        $users = User::factory()->count(100)->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Simulate API calls
        $responseTimes = [];

        foreach ($users as $user) {
            $startTime = microtime(true);

            // Simulate API operations
            $this->learningService->trackCourseInteraction($user->id, $course->id, [
                'duration' => 1800,
                'score' => 85,
                'interaction_type' => 'completion'
            ]);

            $this->learningService->calculateEngagementScore($user->id, $course->id);

            $endTime = microtime(true);
            $responseTimes[] = $endTime - $startTime;
        }

        // Calculate statistics
        $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
        $maxResponseTime = max($responseTimes);
        $p95ResponseTime = $this->calculatePercentile($responseTimes, 95);

        // Assert performance requirements
        $this->assertLessThan(0.5, $avgResponseTime, 'Average API response time too slow');
        $this->assertLessThan(1.0, $maxResponseTime, 'Maximum API response time too slow');
        $this->assertLessThan(0.8, $p95ResponseTime, '95th percentile response time too slow');
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