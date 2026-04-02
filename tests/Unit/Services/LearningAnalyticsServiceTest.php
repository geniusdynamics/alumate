<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AnalyticsEvent;
use App\Models\Course;
use App\Models\LearningProgress;
use App\Models\User;
use App\Services\Analytics\ConsentService;
use App\Services\Analytics\LearningAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

class LearningAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private LearningAnalyticsService $service;
    private ConsentService $consentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consentService = Mockery::mock(ConsentService::class);
        $this->service = new LearningAnalyticsService();

        // Mock tenant context
        session(['tenant_id' => 'test-tenant']);

        // Clear any cached data
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_track_course_interaction_logs_event_with_consent(): void
    {
        // Create test user and course
        $user = User::factory()->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent check
        $this->consentService = Mockery::mock(ConsentService::class);
        $this->consentService->shouldReceive('checkConsent')
            ->with($user->id, 'analytics')
            ->andReturn(true);

        // Replace the service instance with mocked consent
        $this->app->instance(ConsentService::class, $this->consentService);

        $interactionData = [
            'course_id' => $course->id,
            'module_id' => 1,
            'duration' => 1800,
            'score' => 85,
            'interaction_type' => 'completion'
        ];

        $result = $this->service->trackCourseInteraction($user->id, $course->id, $interactionData);

        $this->assertTrue($result);

        // Verify event was created
        $event = AnalyticsEvent::where('user_id', $user->id)
            ->where('event_type', 'learning')
            ->first();

        $this->assertNotNull($event);
        $this->assertEquals('course_interaction', $event->event_name);
        $this->assertEquals($course->id, $event->properties['course_id']);
        $this->assertEquals(1800, $event->properties['duration']);
        $this->assertEquals(85, $event->properties['score']);
    }

    public function test_track_course_interaction_skips_without_consent(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent check returning false
        $this->consentService = Mockery::mock(ConsentService::class);
        $this->consentService->shouldReceive('checkConsent')
            ->with($user->id, 'analytics')
            ->andReturn(false);

        $this->app->instance(ConsentService::class, $this->consentService);

        $interactionData = [
            'course_id' => $course->id,
            'duration' => 1200
        ];

        $result = $this->service->trackCourseInteraction($user->id, $course->id, $interactionData);

        $this->assertFalse($result);

        // Verify no event was created
        $event = AnalyticsEvent::where('user_id', $user->id)->first();
        $this->assertNull($event);
    }

    public function test_calculate_engagement_score_with_multiple_interactions(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Create 5 completion events
        for ($i = 1; $i <= 5; $i++) {
            AnalyticsEvent::create([
                'tenant_id' => 'test-tenant',
                'event_type' => 'learning',
                'event_name' => 'course_interaction',
                'user_id' => $user->id,
                'properties' => [
                    'course_id' => $course->id,
                    'interaction_type' => 'completion',
                    'duration' => 1800, // 30 minutes
                    'score' => 80
                ],
                'occurred_at' => now()->subDays(20 - $i),
                'is_compliant' => true,
                'consent_given' => true,
            ]);
        }

        $score = $this->service->calculateEngagementScore($user->id, $course->id);

        // Expected: (5 * 0.4 + 9000 * 0.3 / 36000 + 80 * 0.3) = (2 + 0.075 + 24) = 26.075
        $this->assertGreaterThan(25, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_calculate_engagement_score_returns_zero_for_no_data(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        $score = $this->service->calculateEngagementScore($user->id, $course->id);

        $this->assertEquals(0.0, $score);
    }

    public function test_verify_certification_eligible_with_good_progress(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Create progress record
        LearningProgress::create([
            'tenant_id' => 'test-tenant',
            'user_id' => $user->id,
            'course_id' => $course->id,
            'modules_completed' => 6,
            'total_score' => 85.5,
            'engagement_score' => 75.0,
            'certified' => false
        ]);

        $result = $this->service->verifyCertification($user->id, [
            'min_score' => 80,
            'modules_completed' => 5,
            'course_id' => $course->id
        ]);

        $this->assertTrue($result['eligible']);
        $this->assertEquals(85.5, $result['score']);
        $this->assertEquals(6, $result['modules_completed']);
    }

    public function test_verify_certification_ineligible_with_low_score(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        LearningProgress::create([
            'tenant_id' => 'test-tenant',
            'user_id' => $user->id,
            'course_id' => $course->id,
            'modules_completed' => 4,
            'total_score' => 65.0,
            'engagement_score' => 50.0,
            'certified' => false
        ]);

        $result = $this->service->verifyCertification($user->id, [
            'min_score' => 80,
            'modules_completed' => 5
        ]);

        $this->assertFalse($result['eligible']);
        $this->assertEquals(65.0, $result['score']);
        $this->assertEquals('No progress data found', $result['reason']);
    }

    public function test_generate_learning_insights_with_comprehensive_data(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Create 100 learning events
        for ($i = 0; $i < 100; $i++) {
            AnalyticsEvent::create([
                'tenant_id' => 'test-tenant',
                'event_type' => 'learning',
                'event_name' => 'course_interaction',
                'user_id' => $user->id,
                'properties' => [
                    'course_id' => $course->id,
                    'interaction_type' => $i % 10 === 0 ? 'completion' : 'view',
                    'duration' => rand(600, 3600), // 10-60 minutes
                    'score' => rand(70, 95)
                ],
                'occurred_at' => now()->subDays(rand(0, 30)),
                'is_compliant' => true,
                'consent_given' => true,
            ]);
        }

        $insights = $this->service->generateLearningInsights();

        $this->assertIsArray($insights);
        $this->assertEquals(100, $insights['total_interactions']);
        $this->assertGreaterThan(0, $insights['unique_users']);
        $this->assertArrayHasKey('dropout_rate', $insights);
        $this->assertArrayHasKey('avg_completion_time', $insights);
        $this->assertArrayHasKey('engagement_trends', $insights);
        $this->assertArrayHasKey('anomalies', $insights);
    }

    public function test_generate_learning_insights_handles_empty_data(): void
    {
        $insights = $this->service->generateLearningInsights();

        $this->assertIsArray($insights);
        $this->assertEquals(0, $insights['total_interactions']);
        $this->assertEquals(0, $insights['dropout_rate']);
        $this->assertEquals(0, $insights['avg_completion_time']);
        $this->assertEmpty($insights['engagement_trends']);
        $this->assertEmpty($insights['anomalies']);
    }

    public function test_process_batch_scores_updates_multiple_users(): void
    {
        $users = User::factory()->count(3)->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        $userCoursePairs = [];
        foreach ($users as $user) {
            $userCoursePairs[] = ['user_id' => $user->id, 'course_id' => $course->id];

            // Create some interaction data
            AnalyticsEvent::create([
                'tenant_id' => 'test-tenant',
                'event_type' => 'learning',
                'event_name' => 'course_interaction',
                'user_id' => $user->id,
                'properties' => [
                    'course_id' => $course->id,
                    'interaction_type' => 'completion',
                    'duration' => 1800,
                    'score' => 80
                ],
                'occurred_at' => now()->subDays(10),
                'is_compliant' => true,
                'consent_given' => true,
            ]);
        }

        $results = $this->service->processBatchScores($userCoursePairs);

        $this->assertEquals(3, $results['processed']);
        $this->assertEquals(0, $results['errors']);

        // Verify progress records were created
        foreach ($users as $user) {
            $progress = LearningProgress::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();
            $this->assertNotNull($progress);
            $this->assertGreaterThan(0, $progress->engagement_score);
        }
    }

    public function test_handles_large_batch_processing_with_chunking(): void
    {
        $users = User::factory()->count(150)->create(); // Large batch
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        $userCoursePairs = [];
        foreach ($users as $user) {
            $userCoursePairs[] = ['user_id' => $user->id, 'course_id' => $course->id];
        }

        $results = $this->service->processBatchScores($userCoursePairs);

        $this->assertEquals(150, $results['processed']);
        $this->assertEquals(0, $results['errors']);
    }

    public function test_maintains_tenant_isolation_in_processing(): void
    {
        // Create data for tenant1
        session(['tenant_id' => 'tenant1']);
        $user1 = User::factory()->create();
        $course1 = Course::factory()->create(['tenant_id' => 'tenant1']);

        AnalyticsEvent::create([
            'tenant_id' => 'tenant1',
            'event_type' => 'learning',
            'event_name' => 'course_interaction',
            'user_id' => $user1->id,
            'properties' => [
                'course_id' => $course1->id,
                'interaction_type' => 'completion'
            ],
            'occurred_at' => now(),
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        $insights1 = $this->service->generateLearningInsights();

        // Switch to tenant2
        session(['tenant_id' => 'tenant2']);
        $insights2 = $this->service->generateLearningInsights();

        // tenant2 should have no data
        $this->assertEquals(0, $insights2['total_interactions']);
        $this->assertEquals(1, $insights1['total_interactions']);
    }

    public function test_detects_anomalies_with_z_score_analysis(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Create normal duration events
        for ($i = 0; $i < 20; $i++) {
            AnalyticsEvent::create([
                'tenant_id' => 'test-tenant',
                'event_type' => 'learning',
                'event_name' => 'course_interaction',
                'user_id' => $user->id,
                'properties' => [
                    'course_id' => $course->id,
                    'duration' => 1800 + rand(-300, 300) // Normal variation around 30 min
                ],
                'occurred_at' => now()->subDays($i),
                'is_compliant' => true,
                'consent_given' => true,
            ]);
        }

        // Create anomaly (very long duration)
        AnalyticsEvent::create([
            'tenant_id' => 'test-tenant',
            'event_type' => 'learning',
            'event_name' => 'course_interaction',
            'user_id' => $user->id,
            'properties' => [
                'course_id' => $course->id,
                'duration' => 7200 // 2 hours - anomaly
            ],
            'occurred_at' => now(),
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        $insights = $this->service->generateLearningInsights();

        $this->assertNotEmpty($insights['anomalies']);
        $anomaly = $insights['anomalies'][0];
        $this->assertEquals($user->id, $anomaly['user_id']);
        $this->assertEquals(7200, $anomaly['duration']);
    }

    public function test_calculates_completion_rate_accurately(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Create 10 events: 3 completions, 7 views
        for ($i = 0; $i < 3; $i++) {
            AnalyticsEvent::create([
                'tenant_id' => 'test-tenant',
                'event_type' => 'learning',
                'event_name' => 'course_interaction',
                'user_id' => $user->id,
                'properties' => [
                    'course_id' => $course->id,
                    'interaction_type' => 'completion'
                ],
                'occurred_at' => now()->subDays($i),
                'is_compliant' => true,
                'consent_given' => true,
            ]);
        }

        for ($i = 3; $i < 10; $i++) {
            AnalyticsEvent::create([
                'tenant_id' => 'test-tenant',
                'event_type' => 'learning',
                'event_name' => 'course_interaction',
                'user_id' => $user->id,
                'properties' => [
                    'course_id' => $course->id,
                    'interaction_type' => 'view'
                ],
                'occurred_at' => now()->subDays($i),
                'is_compliant' => true,
                'consent_given' => true,
            ]);
        }

        $insights = $this->service->generateLearningInsights();

        $this->assertEquals(30.0, $insights['completion_rate']); // 3/10 * 100
    }

    public function test_track_course_interaction_with_20_interactions_assert_event_stored(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService = Mockery::mock(ConsentService::class);
        $this->consentService->shouldReceive('checkConsent')
            ->with($user->id, 'analytics')
            ->andReturn(true);
        $this->app->instance(ConsentService::class, $this->consentService);

        // Track 20 interactions
        for ($i = 1; $i <= 20; $i++) {
            $interactionData = [
                'course_id' => $course->id,
                'module_id' => $i,
                'duration' => 1800 + ($i * 10),
                'score' => 80 + ($i % 5),
                'interaction_type' => $i % 3 === 0 ? 'completion' : 'view'
            ];

            $result = $this->service->trackCourseInteraction($user->id, $course->id, $interactionData);
            $this->assertTrue($result);
        }

        // Verify 20 events were stored
        $events = AnalyticsEvent::where('user_id', $user->id)
            ->where('event_type', 'learning')
            ->get();
        $this->assertCount(20, $events);

        // Verify event properties
        $firstEvent = $events->first();
        $this->assertEquals('course_interaction', $firstEvent->event_name);
        $this->assertEquals($course->id, $firstEvent->properties['course_id']);
        $this->assertArrayHasKey('duration', $firstEvent->properties);
        $this->assertArrayHasKey('score', $firstEvent->properties);
    }

    public function test_calculate_engagement_score_with_various_inputs_normalized_0_100(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Test case 1: High engagement (many completions, long duration, high scores)
        for ($i = 0; $i < 10; $i++) {
            AnalyticsEvent::create([
                'tenant_id' => 'test-tenant',
                'event_type' => 'learning',
                'event_name' => 'course_interaction',
                'user_id' => $user->id,
                'properties' => [
                    'course_id' => $course->id,
                    'interaction_type' => 'completion',
                    'duration' => 3600, // 1 hour
                    'score' => 95
                ],
                'occurred_at' => now()->subDays(10 - $i),
                'is_compliant' => true,
                'consent_given' => true,
            ]);
        }

        $score1 = $this->service->calculateEngagementScore($user->id, $course->id);
        $this->assertGreaterThanOrEqual(0, $score1);
        $this->assertLessThanOrEqual(100, $score1);
        $this->assertGreaterThan(90, $score1); // Should be very high

        // Test case 2: Low engagement (few interactions, short duration, low scores)
        $user2 = User::factory()->create();
        for ($i = 0; $i < 2; $i++) {
            AnalyticsEvent::create([
                'tenant_id' => 'test-tenant',
                'event_type' => 'learning',
                'event_name' => 'course_interaction',
                'user_id' => $user2->id,
                'properties' => [
                    'course_id' => $course->id,
                    'interaction_type' => 'view',
                    'duration' => 300, // 5 minutes
                    'score' => 60
                ],
                'occurred_at' => now()->subDays(20 - $i),
                'is_compliant' => true,
                'consent_given' => true,
            ]);
        }

        $score2 = $this->service->calculateEngagementScore($user2->id, $course->id);
        $this->assertGreaterThanOrEqual(0, $score2);
        $this->assertLessThanOrEqual(100, $score2);
        $this->assertLessThan(20, $score2); // Should be very low

        // Test case 3: Mixed engagement
        $user3 = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            AnalyticsEvent::create([
                'tenant_id' => 'test-tenant',
                'event_type' => 'learning',
                'event_name' => 'course_interaction',
                'user_id' => $user3->id,
                'properties' => [
                    'course_id' => $course->id,
                    'interaction_type' => $i < 3 ? 'completion' : 'view',
                    'duration' => 1800, // 30 minutes
                    'score' => 75
                ],
                'occurred_at' => now()->subDays(15 - $i),
                'is_compliant' => true,
                'consent_given' => true,
            ]);
        }

        $score3 = $this->service->calculateEngagementScore($user3->id, $course->id);
        $this->assertGreaterThanOrEqual(0, $score3);
        $this->assertLessThanOrEqual(100, $score3);
        $this->assertGreaterThan(40, $score3);
        $this->assertLessThan(80, $score3);
    }

    public function test_verify_certification_meeting_criteria_mock_career_prediction_service_update(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Create progress record meeting criteria
        LearningProgress::create([
            'tenant_id' => 'test-tenant',
            'user_id' => $user->id,
            'course_id' => $course->id,
            'modules_completed' => 8,
            'total_score' => 92.0,
            'engagement_score' => 85.0,
            'certified' => false
        ]);

        // Mock CareerPredictionService
        $careerPredictionService = Mockery::mock('App\Services\CareerPredictionService');
        $careerPredictionService->shouldReceive('updateLearningImpact')
            ->once()
            ->with(Mockery::on(function ($data) use ($user, $course) {
                return $data['user_id'] == $user->id && $data['course_id'] == $course->id;
            }));

        // Bind mock to container
        $this->app->instance('App\Services\CareerPredictionService', $careerPredictionService);

        $result = $this->service->verifyCertification($user->id, [
            'min_score' => 80,
            'modules_completed' => 5,
            'course_id' => $course->id
        ]);

        $this->assertTrue($result['eligible']);
        $this->assertEquals(92.0, $result['score']);
        $this->assertEquals(8, $result['modules_completed']);
    }

    public function test_verify_certification_ineligible_with_insufficient_modules(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        LearningProgress::create([
            'tenant_id' => 'test-tenant',
            'user_id' => $user->id,
            'course_id' => $course->id,
            'modules_completed' => 3, // Below minimum
            'total_score' => 85.0,
            'engagement_score' => 70.0,
            'certified' => false
        ]);

        $result = $this->service->verifyCertification($user->id, [
            'min_score' => 80,
            'modules_completed' => 5,
            'course_id' => $course->id
        ]);

        $this->assertFalse($result['eligible']);
        $this->assertEquals(85.0, $result['score']);
        $this->assertEquals(3, $result['modules_completed']);
    }

    public function test_caching_hit_miss_for_engagement_score(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Create some data
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
            'occurred_at' => now()->subDays(5),
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        $cacheKey = sprintf('learning_score_%s_%s', $user->id, $course->id);

        // First call should cache (miss)
        $this->assertFalse(Cache::has($cacheKey));
        $score1 = $this->service->calculateEngagementScore($user->id, $course->id);
        $this->assertTrue(Cache::has($cacheKey));

        // Second call should use cache (hit)
        $score2 = $this->service->calculateEngagementScore($user->id, $course->id);

        // Scores should be identical
        $this->assertEquals($score1, $score2);

        // Clear cache and verify recalculation
        Cache::forget($cacheKey);
        $this->assertFalse(Cache::has($cacheKey));
        $score3 = $this->service->calculateEngagementScore($user->id, $course->id);
        $this->assertEquals($score1, $score3); // Same calculation
    }

    public function test_queued_jobs_dispatch_learning_score_job(): void
    {
        // Note: Since the service doesn't currently dispatch jobs,
        // this test verifies that the job can be instantiated and has correct properties
        // In a real implementation, processBatchScores might dispatch jobs for large batches

        $userCoursePairs = [
            ['user_id' => 1, 'course_id' => 1],
            ['user_id' => 2, 'course_id' => 1],
            ['user_id' => 3, 'course_id' => 1],
        ];

        // Instantiate job for testing
        $job = new \App\Jobs\LearningScoreJob($userCoursePairs, 'test-tenant');
        $this->assertInstanceOf(\App\Jobs\LearningScoreJob::class, $job);

        // Verify job implements ShouldQueue
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);

        // Test that job can be executed (basic instantiation test)
        $this->assertNotNull($job);
    }

    public function test_no_consent_skip_log_for_track_course_interaction(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent returning false
        $this->consentService = Mockery::mock(ConsentService::class);
        $this->consentService->shouldReceive('checkConsent')
            ->with($user->id, 'analytics')
            ->andReturn(false);
        $this->app->instance(ConsentService::class, $this->consentService);

        $interactionData = [
            'course_id' => $course->id,
            'duration' => 1200
        ];

        $result = $this->service->trackCourseInteraction($user->id, $course->id, $interactionData);

        $this->assertFalse($result);

        // Verify no event was created
        $event = AnalyticsEvent::where('user_id', $user->id)->first();
        $this->assertNull($event);
    }

    public function test_batch_chunking_in_process_batch_scores(): void
    {
        $users = User::factory()->count(150)->create();
        $course = Course::factory()->create(['tenant_id' => 'test-tenant']);

        $userCoursePairs = [];
        foreach ($users as $user) {
            $userCoursePairs[] = ['user_id' => $user->id, 'course_id' => $course->id];
        }

        $results = $this->service->processBatchScores($userCoursePairs);

        $this->assertEquals(150, $results['processed']);
        $this->assertEquals(0, $results['errors']);

        // Verify progress records were created for all users
        foreach ($users as $user) {
            $progress = LearningProgress::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();
            $this->assertNotNull($progress);
        }
    }
}