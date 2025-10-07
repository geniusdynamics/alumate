<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\AnalyticsEvent;
use App\Models\LearningProgress;
use App\Models\User;
use App\Services\Analytics\ConsentService;
use App\Services\Analytics\InsightsService;
use App\Services\Analytics\LearningAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AnalyticsCrossModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Mockery\MockInterface $consentService;
    private LearningAnalyticsService $learningService;
    private InsightsService $insightsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consentService = Mockery::mock(ConsentService::class);
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
     * Test learning progress tracking triggers insights generation
     */
    public function test_learning_progress_triggers_insights_generation(): void
    {
        $user = User::factory()->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Track learning progress
        $interactionData = [
            'course_id' => $course->id,
            'duration' => 1800,
            'score' => 75,
            'interaction_type' => 'completion'
        ];

        $result = $this->learningService->trackCourseInteraction($user->id, $course->id, $interactionData);
        $this->assertTrue($result);

        // Generate insights
        $insights = $this->insightsService->generateInsights();

        // Verify insights contain learning-related data
        $this->assertIsArray($insights);
        $learningInsight = collect($insights)->firstWhere('type', 'learning_progress');
        $this->assertNotNull($learningInsight);
        $this->assertEquals('learning_progress', $learningInsight['metric']);
    }

    /**
     * Test low learning engagement triggers recommendation insights
     */
    public function test_low_learning_engagement_triggers_recommendations(): void
    {
        $user = User::factory()->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Create low engagement data (few interactions, low scores)
        for ($i = 0; $i < 3; $i++) {
            AnalyticsEvent::create([
                'tenant_id' => 'test-tenant',
                'event_type' => 'learning',
                'event_name' => 'course_interaction',
                'user_id' => $user->id,
                'properties' => [
                    'course_id' => $course->id,
                    'interaction_type' => 'view',
                    'duration' => 300, // 5 minutes
                    'score' => 45
                ],
                'occurred_at' => now()->subDays($i),
                'is_compliant' => true,
                'consent_given' => true,
            ]);
        }

        // Generate insights
        $insights = $this->insightsService->generateInsights();

        // Should trigger recommendation for low engagement
        $recommendation = collect($insights)->firstWhere('type', 'recommendation');
        $this->assertNotNull($recommendation);
        $this->assertArrayHasKey('recommendation', $recommendation);
        $this->assertEquals('engagement_campaign', $recommendation['recommendation']['type']);
    }

    /**
     * Test learning analytics integrates with external sync
     */
    public function test_learning_analytics_integrates_with_external_sync(): void
    {
        $user = User::factory()->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Track learning interactions
        $this->learningService->trackCourseInteraction($user->id, $course->id, [
            'course_id' => $course->id,
            'duration' => 2400,
            'score' => 90,
            'interaction_type' => 'completion'
        ]);

        // Process batch scores
        $batchResult = $this->learningService->processBatchScores([
            ['user_id' => $user->id, 'course_id' => $course->id]
        ]);

        $this->assertEquals(1, $batchResult['processed']);
        $this->assertEquals(0, $batchResult['errors']);

        // Verify learning progress was created
        $progress = LearningProgress::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
        $this->assertNotNull($progress);
        $this->assertGreaterThan(0, $progress->engagement_score);
    }

    /**
     * Test cross-module data consistency
     */
    public function test_cross_module_data_consistency(): void
    {
        $user = User::factory()->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Track learning data
        $this->learningService->trackCourseInteraction($user->id, $course->id, [
            'course_id' => $course->id,
            'duration' => 1800,
            'score' => 85,
            'interaction_type' => 'completion'
        ]);

        // Get learning insights
        $learningInsights = $this->learningService->generateLearningInsights();

        // Get general insights
        $generalInsights = $this->insightsService->generateInsights();

        // Verify data consistency
        $this->assertEquals($learningInsights['total_interactions'],
            collect($generalInsights)->where('type', 'learning_progress')->sum('value') ?? 0);
    }

    /**
     * Test tenant isolation across modules
     */
    public function test_tenant_isolation_across_modules(): void
    {
        // Create data for tenant1
        session(['tenant_id' => 'tenant1']);
        $user1 = User::factory()->create();
        $course1 = \App\Models\Course::factory()->create(['tenant_id' => 'tenant1']);

        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        $this->learningService->trackCourseInteraction($user1->id, $course1->id, [
            'course_id' => $course1->id,
            'duration' => 1200,
            'interaction_type' => 'view'
        ]);

        $insights1 = $this->learningService->generateLearningInsights();

        // Switch to tenant2
        session(['tenant_id' => 'tenant2']);
        $insights2 = $this->learningService->generateLearningInsights();

        // tenant2 should have no data
        $this->assertEquals(0, $insights2['total_interactions']);
        $this->assertEquals(1, $insights1['total_interactions']);
    }

    /**
     * Test learning progress affects career predictions
     */
    public function test_learning_progress_affects_career_predictions(): void
    {
        $user = User::factory()->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Create high-performing learning data
        for ($i = 0; $i < 10; $i++) {
            $this->learningService->trackCourseInteraction($user->id, $course->id, [
                'course_id' => $course->id,
                'duration' => 3600,
                'score' => 95,
                'interaction_type' => 'completion'
            ]);
        }

        // Process batch scores
        $this->learningService->processBatchScores([
            ['user_id' => $user->id, 'course_id' => $course->id]
        ]);

        // Verify high engagement score
        $progress = LearningProgress::where('user_id', $user->id)->first();
        $this->assertGreaterThan(90, $progress->engagement_score);
    }

    /**
     * Test analytics event flow from learning to insights
     */
    public function test_analytics_event_flow_learning_to_insights(): void
    {
        $user = User::factory()->create();
        $course = \App\Models\Course::factory()->create(['tenant_id' => 'test-tenant']);

        // Mock consent
        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Track multiple learning events
        for ($i = 0; $i < 5; $i++) {
            $this->learningService->trackCourseInteraction($user->id, $course->id, [
                'course_id' => $course->id,
                'duration' => 1800 + ($i * 100),
                'score' => 80 + $i,
                'interaction_type' => $i % 2 === 0 ? 'completion' : 'view'
            ]);
        }

        // Verify events were created
        $events = AnalyticsEvent::where('user_id', $user->id)->get();
        $this->assertCount(5, $events);

        // Generate insights
        $insights = $this->insightsService->generateInsights();

        // Should detect learning patterns
        $learningInsights = collect($insights)->where('type', 'learning_progress');
        $this->assertGreaterThan(0, $learningInsights->count());
    }
}