<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\AnalyticsEvent;
use App\Models\AttributionTouch;
use App\Models\Cohort;
use App\Models\CustomEvent;
use App\Models\LearningProgress;
use App\Models\User;
use App\Services\Analytics\AttributionService;
use App\Services\Analytics\CohortAnalysisService;
use App\Services\Analytics\ConsentService;
use App\Services\Analytics\CustomEventService;
use App\Services\Analytics\InsightsService;
use App\Services\Analytics\LearningAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WorkflowValidationTest extends TestCase
{
    use RefreshDatabase;

    private Mockery\MockInterface $consentService;
    private CohortAnalysisService $cohortService;
    private AttributionService $attributionService;
    private CustomEventService $customEventService;
    private LearningAnalyticsService $learningService;
    private InsightsService $insightsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consentService = Mockery::mock(ConsentService::class);
        $this->cohortService = new CohortAnalysisService($this->consentService);
        $this->attributionService = new AttributionService();
        $this->customEventService = new CustomEventService();
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
     * Test complete custom event workflow: define -> track -> attribution -> cohort -> insights
     */
    public function test_complete_custom_event_workflow(): void
    {
        $user = User::factory()->create();

        // Step 1: Define custom event
        $eventDefinition = [
            'name' => 'user_engagement',
            'properties' => [
                'action' => 'string',
                'duration' => 'integer',
                'source' => 'string'
            ],
            'validation_rules' => [
                'action' => 'required|string',
                'duration' => 'integer|min:0'
            ]
        ];

        $definedEvent = $this->customEventService->defineEvent($eventDefinition);
        $this->assertEquals('user_engagement', $definedEvent->name);

        // Step 2: Track custom event
        $eventData = [
            'action' => 'video_watch',
            'duration' => 300,
            'source' => 'dashboard'
        ];

        $trackedEvent = $this->customEventService->trackEvent('user_engagement', $user->id, $eventData);
        $this->assertEquals('user_engagement', $trackedEvent->name);
        $this->assertEquals($user->id, $trackedEvent->user_id);

        // Step 3: Track attribution touch
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        $touch = $this->attributionService->trackTouch([
            'user_id' => $user->id,
            'event_type' => 'custom_event',
            'source' => 'dashboard',
            'value' => 25.00
        ]);
        $this->assertInstanceOf(AttributionTouch::class, $touch);

        // Step 4: Create cohort based on event
        $this->consentService->shouldReceive('hasConsentForAnalytics')->andReturn(true);

        $cohort = $this->cohortService->createCohort('Engaged Users', [
            'has_custom_events' => true,
            'event_name' => 'user_engagement'
        ], $user->id);
        $this->assertEquals('Engaged Users', $cohort->name);

        // Step 5: Generate insights
        $insights = $this->insightsService->generateInsights();
        $this->assertIsArray($insights);

        // Verify workflow completion
        $customEvent = CustomEvent::where('name', 'user_engagement')->first();
        $this->assertNotNull($customEvent);

        $attributionTouch = AttributionTouch::where('user_id', $user->id)->first();
        $this->assertNotNull($attributionTouch);
    }

    /**
     * Test learning analytics workflow: track -> progress -> insights -> recommendations
     */
    public function test_learning_analytics_workflow(): void
    {
        $user = User::factory()->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Step 1: Track learning interactions
        $interactions = [
            ['duration' => 1800, 'score' => 85, 'interaction_type' => 'completion'],
            ['duration' => 1200, 'score' => 90, 'interaction_type' => 'completion'],
            ['duration' => 2400, 'score' => 88, 'interaction_type' => 'completion'],
        ];

        foreach ($interactions as $interaction) {
            $result = $this->learningService->trackCourseInteraction($user->id, $course->id, $interaction);
            $this->assertTrue($result);
        }

        // Step 2: Process batch scores
        $batchResult = $this->learningService->processBatchScores([
            ['user_id' => $user->id, 'course_id' => $course->id]
        ]);
        $this->assertEquals(1, $batchResult['processed']);

        // Step 3: Verify learning progress
        $progress = LearningProgress::where('user_id', $user->id)->first();
        $this->assertNotNull($progress);
        $this->assertGreaterThan(0, $progress->engagement_score);

        // Step 4: Generate learning insights
        $insights = $this->learningService->generateLearningInsights();
        $this->assertGreaterThan(0, $insights['total_interactions']);

        // Step 5: Generate general insights that include learning data
        $generalInsights = $this->insightsService->generateInsights();
        $learningInsight = collect($generalInsights)->firstWhere('type', 'learning_progress');
        $this->assertNotNull($learningInsight);
    }

    /**
     * Test attribution workflow: touches -> model calculation -> insights
     */
    public function test_attribution_workflow(): void
    {
        $user = User::factory()->create();

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Step 1: Track multiple attribution touches
        $touches = [
            ['source' => 'google', 'event_type' => 'page_view', 'value' => 10.00],
            ['source' => 'facebook', 'event_type' => 'click', 'value' => 15.00],
            ['source' => 'google', 'event_type' => 'page_view', 'value' => 5.00],
            ['source' => 'email', 'event_type' => 'signup', 'value' => 50.00],
        ];

        foreach ($touches as $touchData) {
            $touchData['user_id'] = $user->id;
            $touch = $this->attributionService->trackTouch($touchData);
            $this->assertInstanceOf(AttributionTouch::class, $touch);
        }

        // Step 2: Calculate attribution with different models
        $startDate = now()->subDays(7)->toDateString();
        $endDate = now()->toDateString();

        $lastTouch = $this->attributionService->calculateAttribution($user->id, $startDate, $endDate, 'last_touch');
        $firstTouch = $this->attributionService->calculateAttribution($user->id, $startDate, $endDate, 'first_touch');
        $linear = $this->attributionService->calculateAttribution($user->id, $startDate, $endDate, 'linear');

        // Verify different models produce different results
        $this->assertNotEquals($lastTouch['sources'], $firstTouch['sources']);
        $this->assertEquals(80.00, $lastTouch['total_value']); // Sum of all touch values

        // Step 3: Verify attribution data flows to insights
        $insights = $this->insightsService->generateInsights();
        $this->assertIsArray($insights);
    }

    /**
     * Test cohort analysis workflow: create -> analyze -> insights
     */
    public function test_cohort_analysis_workflow(): void
    {
        // Create test users with different graduation years
        $users2023 = User::factory()->count(5)->create(['graduation_year' => 2023]);
        $users2024 = User::factory()->count(3)->create(['graduation_year' => 2024]);

        // Mock consent for all users
        $this->consentService->shouldReceive('hasConsentForAnalytics')->andReturn(true);

        // Step 1: Create cohort
        $cohort = $this->cohortService->createCohort('Class of 2023', [
            'grad_year' => 2023
        ], 1);
        $this->assertEquals('Class of 2023', $cohort->name);
        $this->assertEquals(5, $cohort->members_count);

        // Step 2: Analyze cohort
        $analysis = $this->cohortService->analyzeCohort($cohort->id);
        $this->assertEquals(5, $analysis['size']);
        $this->assertArrayHasKey('retention', $analysis);
        $this->assertArrayHasKey('engagement', $analysis);

        // Step 3: Compare cohorts
        $cohort2 = $this->cohortService->createCohort('Class of 2024', [
            'grad_year' => 2024
        ], 1);

        $comparison = $this->cohortService->compareCohorts([$cohort->id, $cohort2->id]);
        $this->assertCount(2, $comparison['cohorts']);
        $this->assertArrayHasKey('statistical_significance', $comparison);

        // Step 4: Verify cohort data in insights
        $insights = $this->insightsService->generateInsights();
        $this->assertIsArray($insights);
    }

    /**
     * Test consent workflow: grant -> track -> revoke -> purge
     */
    public function test_consent_workflow(): void
    {
        $user = User::factory()->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Step 1: Start with consent granted
        $this->consentService->shouldReceive('checkConsent')
            ->with($user->id, 'analytics')
            ->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Track learning data
        $result = $this->learningService->trackCourseInteraction($user->id, $course->id, [
            'duration' => 1800,
            'score' => 85,
            'interaction_type' => 'completion'
        ]);
        $this->assertTrue($result);

        // Verify data was tracked
        $events = AnalyticsEvent::where('user_id', $user->id)->get();
        $this->assertCount(1, $events);

        // Step 2: Revoke consent
        $this->consentService->shouldReceive('checkConsent')
            ->with($user->id, 'analytics')
            ->andReturn(false);

        // Try to track more data - should fail
        $result2 = $this->learningService->trackCourseInteraction($user->id, $course->id, [
            'duration' => 1200,
            'score' => 80,
            'interaction_type' => 'view'
        ]);
        $this->assertFalse($result2);

        // Step 3: Simulate data purge
        AnalyticsEvent::where('user_id', $user->id)->delete();
        LearningProgress::where('user_id', $user->id)->delete();

        // Verify data was purged
        $eventsAfter = AnalyticsEvent::where('user_id', $user->id)->get();
        $progressAfter = LearningProgress::where('user_id', $user->id)->first();

        $this->assertCount(0, $eventsAfter);
        $this->assertNull($progressAfter);
    }

    /**
     * Test end-to-end analytics pipeline performance
     */
    public function test_end_to_end_analytics_pipeline_performance(): void
    {
        $users = User::factory()->count(10)->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        $startTime = microtime(true);

        // Step 1: Track learning interactions for multiple users
        foreach ($users as $user) {
            for ($i = 0; $i < 5; $i++) {
                $this->learningService->trackCourseInteraction($user->id, $course->id, [
                    'duration' => rand(600, 3600),
                    'score' => rand(70, 95),
                    'interaction_type' => 'completion'
                ]);
            }
        }

        // Step 2: Process batch scores
        $userCoursePairs = $users->map(fn($user) => [
            'user_id' => $user->id,
            'course_id' => $course->id
        ])->toArray();

        $batchResult = $this->learningService->processBatchScores($userCoursePairs);

        // Step 3: Generate insights
        $learningInsights = $this->learningService->generateLearningInsights();
        $generalInsights = $this->insightsService->generateInsights();

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // Verify pipeline completion
        $this->assertEquals(10, $batchResult['processed']);
        $this->assertGreaterThan(0, $learningInsights['total_interactions']);
        $this->assertIsArray($generalInsights);

        // Performance check - should complete within reasonable time
        $this->assertLessThan(10.0, $totalTime, 'End-to-end pipeline took too long');
    }

    /**
     * Test error handling in workflow
     */
    public function test_error_handling_in_workflow(): void
    {
        $user = User::factory()->create();

        // Test invalid custom event tracking
        try {
            $this->customEventService->trackEvent('non_existent_event', $user->id, []);
            $this->fail('Expected exception for non-existent event');
        } catch (\Exception $e) {
            $this->assertStringContains('not found', strtolower($e->getMessage()));
        }

        // Test invalid attribution data
        try {
            $this->attributionService->trackTouch([
                'user_id' => $user->id,
                'event_type' => 'invalid_type',
                'value' => -10 // Negative value
            ]);
            $this->fail('Expected exception for invalid attribution data');
        } catch (\Exception $e) {
            $this->assertStringContains('invalid', strtolower($e->getMessage()));
        }

        // Test cohort creation with invalid criteria
        try {
            $this->cohortService->createCohort('Test', ['invalid_field' => 'value'], 1);
            $this->fail('Expected exception for invalid cohort criteria');
        } catch (\Exception $e) {
            $this->assertStringContains('invalid', strtolower($e->getMessage()));
        }
    }

    /**
     * Test concurrent workflow operations
     */
    public function test_concurrent_workflow_operations(): void
    {
        $users = User::factory()->count(5)->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Simulate concurrent operations
        $results = [];

        foreach ($users as $user) {
            // Track learning
            $trackResult = $this->learningService->trackCourseInteraction($user->id, $course->id, [
                'duration' => 1800,
                'score' => 85,
                'interaction_type' => 'completion'
            ]);

            // Track attribution
            $touchResult = $this->attributionService->trackTouch([
                'user_id' => $user->id,
                'event_type' => 'page_view',
                'source' => 'dashboard',
                'value' => 10.00
            ]);

            $results[] = [
                'user_id' => $user->id,
                'tracking_success' => $trackResult,
                'touch_success' => $touchResult instanceof AttributionTouch
            ];
        }

        // Verify all operations succeeded
        foreach ($results as $result) {
            $this->assertTrue($result['tracking_success']);
            $this->assertTrue($result['touch_success']);
        }

        // Verify data integrity
        $totalEvents = AnalyticsEvent::whereIn('user_id', $users->pluck('id'))->count();
        $totalTouches = AttributionTouch::whereIn('user_id', $users->pluck('id'))->count();

        $this->assertEquals(5, $totalEvents);
        $this->assertEquals(5, $totalTouches);
    }
}