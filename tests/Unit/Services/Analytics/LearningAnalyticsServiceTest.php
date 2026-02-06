<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Course;
use App\Models\LearningProgress;
use App\Models\User;
use App\Models\Tenant;
use App\Services\Analytics\LearningAnalyticsService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for LearningAnalyticsService
 *
 * @covers \App\Services\Analytics\LearningAnalyticsService
 */
class LearningAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private LearningAnalyticsService $service;
    private TenantContextService $tenantContext;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->tenantContext = Mockery::mock(TenantContextService::class);
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andReturn($this->tenant->id);

        $this->service = new LearningAnalyticsService($this->tenantContext);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test tracking learning progress
     */
    public function test_track_learning_progress(): void
    {
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $progressData = [
            'progress_percentage' => 50,
            'modules_completed' => 5,
            'engagement_duration' => 120,
            'interactions_count' => 25,
            'total_score' => 85,
        ];

        $progress = $this->service->trackLearningProgress($this->user->id, $course->id, $progressData);

        $this->assertInstanceOf(LearningProgress::class, $progress);
        $this->assertEquals($this->user->id, $progress->user_id);
        $this->assertEquals($course->id, $progress->course_id);
        $this->assertEquals(50, $progress->progress_percentage);
        $this->assertEquals(5, $progress->modules_completed);
        $this->assertEquals(120, $progress->engagement_duration);
        $this->assertEquals(85, $progress->total_score);
    }

    /**
     * Test getting learning progress
     */
    public function test_get_learning_progress(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => $this->tenant->id,
            'modules_count' => 10,
        ]);

        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 75,
            'modules_completed' => 7,
            'engagement_duration' => 300,
            'interactions_count' => 50,
            'total_score' => 88,
            'engagement_score' => 72.5,
        ]);

        $progress = $this->service->getLearningProgress($this->user->id, $course->id);

        $this->assertIsArray($progress);
        $this->assertEquals($this->user->id, $progress['user_id']);
        $this->assertEquals($course->id, $progress['course_id']);
        $this->assertEquals(75, $progress['progress_percentage']);
        $this->assertEquals(7, $progress['modules_completed']);
        $this->assertEquals(10, $progress['total_modules']);
    }

    /**
     * Test getting learning progress when not exists
     */
    public function test_get_learning_progress_returns_null_when_not_exists(): void
    {
        $progress = $this->service->getLearningProgress($this->user->id, 999);
        $this->assertNull($progress);
    }

    /**
     * Test analyzing learning outcomes
     */
    public function test_analyze_learning_outcomes(): void
    {
        $courses = Course::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        // Completed course
        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $courses[0]->id,
            'progress_percentage' => 100,
            'modules_completed' => 10,
            'total_score' => 92,
            'engagement_score' => 85,
            'certified' => true,
        ]);

        // In-progress course
        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $courses[1]->id,
            'progress_percentage' => 60,
            'modules_completed' => 6,
            'total_score' => 78,
            'engagement_score' => 65,
        ]);

        // Another in-progress course
        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $courses[2]->id,
            'progress_percentage' => 30,
            'modules_completed' => 3,
            'total_score' => 70,
            'engagement_score' => 45,
        ]);

        $outcomes = $this->service->analyzeLearningOutcomes($this->user->id);

        $this->assertEquals($this->user->id, $outcomes['user_id']);
        $this->assertEquals(3, $outcomes['total_courses']);
        $this->assertEquals(1, $outcomes['completed_courses']);
        $this->assertEquals(2, $outcomes['in_progress_courses']);
        $this->assertEquals(1, $outcomes['certifications_earned']);
        $this->assertGreaterThan(0, $outcomes['completion_rate']);
        $this->assertGreaterThan(0, $outcomes['average_score']);
        $this->assertGreaterThan(0, $outcomes['average_engagement']);
        $this->assertNotEmpty($outcomes['strengths']);
    }

    /**
     * Test analyzing learning outcomes with no data
     */
    public function test_analyze_learning_outcomes_with_no_data(): void
    {
        $outcomes = $this->service->analyzeLearningOutcomes($this->user->id);

        $this->assertEquals($this->user->id, $outcomes['user_id']);
        $this->assertEquals(0, $outcomes['total_courses']);
        $this->assertEquals('insufficient_data', $outcomes['overall_performance']);
    }

    /**
     * Test getting learning metrics
     */
    public function test_get_learning_metrics(): void
    {
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);

        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 50,
            'modules_completed' => 5,
            'engagement_duration' => 200,
            'interactions_count' => 30,
            'engagement_score' => 60,
        ]);

        AnalyticsEvent::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'event_type' => 'learning',
            'event_name' => 'course_view',
            'properties' => ['duration' => 30],
            'occurred_at' => now(),
        ]);

        $metrics = $this->service->getLearningMetrics($this->user->id, [
            'start' => now()->subDays(30),
            'end' => now(),
        ]);

        $this->assertEquals($this->user->id, $metrics['user_id']);
        $this->assertArrayHasKey('period', $metrics);
        $this->assertArrayHasKey('events_summary', $metrics);
        $this->assertArrayHasKey('time_metrics', $metrics);
        $this->assertArrayHasKey('progress_metrics', $metrics);
        $this->assertArrayHasKey('engagement_metrics', $metrics);
        $this->assertArrayHasKey('trends', $metrics);
    }

    /**
     * Test comparing learning performance
     */
    public function test_compare_learning_performance(): void
    {
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);
        $user2 = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user3 = User::factory()->create(['tenant_id' => $this->tenant->id]);

        // Top performer
        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 100,
            'modules_completed' => 10,
            'total_score' => 95,
            'engagement_score' => 90,
            'certified' => true,
        ]);

        // Average performer
        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user2->id,
            'course_id' => $course->id,
            'progress_percentage' => 75,
            'modules_completed' => 7,
            'total_score' => 80,
            'engagement_score' => 70,
        ]);

        // Lower performer
        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user3->id,
            'course_id' => $course->id,
            'progress_percentage' => 40,
            'modules_completed' => 4,
            'total_score' => 65,
            'engagement_score' => 50,
        ]);

        $comparison = $this->service->compareLearningPerformance([
            $this->user->id,
            $user2->id,
            $user3->id,
        ]);

        $this->assertEquals(3, $comparison['compared_users']);
        $this->assertCount(3, $comparison['rankings']);
        $this->assertArrayHasKey('statistics', $comparison);
        $this->assertArrayHasKey('average_completion_rate', $comparison['statistics']);
        $this->assertArrayHasKey('average_score', $comparison['statistics']);
        $this->assertArrayHasKey('average_engagement', $comparison['statistics']);
        
        // First ranked user should be the top performer
        $this->assertEquals($this->user->id, $comparison['rankings'][0]['user_id']);
        $this->assertEquals(1, $comparison['rankings'][0]['rank']);
    }

    /**
     * Test predicting learning completion
     */
    public function test_predict_learning_completion(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => $this->tenant->id,
            'modules_count' => 10,
        ]);

        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 50,
            'modules_completed' => 5,
            'engagement_duration' => 300,
            'engagement_score' => 75,
        ]);

        $prediction = $this->service->predictLearningCompletion($this->user->id, $course->id);

        $this->assertEquals($this->user->id, $prediction['user_id']);
        $this->assertEquals($course->id, $prediction['course_id']);
        $this->assertEquals('in_progress', $prediction['status']);
        $this->assertEquals(50, $prediction['current_progress']);
        $this->assertEquals(5, $prediction['completed_modules']);
        $this->assertEquals(10, $prediction['total_modules']);
        $this->assertArrayHasKey('predicted_completion_date', $prediction);
        $this->assertArrayHasKey('days_remaining', $prediction);
        $this->assertArrayHasKey('confidence', $prediction);
        $this->assertArrayHasKey('factors', $prediction);
        $this->assertArrayHasKey('recommendations', $prediction);
    }

    /**
     * Test predicting completion for already completed course
     */
    public function test_predict_learning_completion_when_completed(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => $this->tenant->id,
            'modules_count' => 10,
        ]);

        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 100,
            'modules_completed' => 10,
        ]);

        $prediction = $this->service->predictLearningCompletion($this->user->id, $course->id);

        $this->assertEquals('completed', $prediction['status']);
        $this->assertEquals(100, $prediction['completion_percentage']);
        $this->assertEquals(0, $prediction['days_remaining']);
        $this->assertEquals(1.0, $prediction['confidence']);
    }

    /**
     * Test getting learning recommendations
     */
    public function test_get_learning_recommendations(): void
    {
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);

        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 50,
            'modules_completed' => 5,
            'total_score' => 55, // Low score to trigger improvement recommendation
            'engagement_score' => 35, // Low engagement to trigger improvement recommendation
        ]);

        $recommendations = $this->service->getLearningRecommendations($this->user->id);

        $this->assertEquals($this->user->id, $recommendations['user_id']);
        $this->assertGreaterThan(0, $recommendations['total_recommendations']);
        $this->assertNotEmpty($recommendations['recommendations']);
        $this->assertArrayHasKey('generated_at', $recommendations);
    }

    /**
     * Test tracking learning activity
     */
    public function test_track_learning_activity(): void
    {
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);

        $activity = [
            'event_name' => 'video_watch',
            'activity_type' => 'video',
            'duration' => 45,
            'module_id' => 1,
            'resource_type' => 'video',
            'resource_id' => 10,
            'course_id' => $course->id,
            'progress_update' => [
                'progress_percentage' => 55,
                'modules_completed' => 5,
            ],
        ];

        $event = $this->service->trackLearningActivity($this->user->id, $activity);

        $this->assertInstanceOf(AnalyticsEvent::class, $event);
        $this->assertEquals($this->user->id, $event->user_id);
        $this->assertEquals('learning', $event->event_type);
        $this->assertEquals('video_watch', $event->event_name);
    }

    /**
     * Test calculating engagement score
     */
    public function test_calculate_engagement_score(): void
    {
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);

        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 80,
            'engagement_duration' => 300, // 5 hours
            'interactions_count' => 50,
        ]);

        $score = $this->service->calculateEngagementScore($this->user->id, $course->id);

        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * Test engagement score returns 0 when no progress
     */
    public function test_calculate_engagement_score_returns_zero_when_no_progress(): void
    {
        $score = $this->service->calculateEngagementScore($this->user->id, 999);
        $this->assertEquals(0.0, $score);
    }

    /**
     * Test verifying certification eligibility
     */
    public function test_verify_certification_eligible(): void
    {
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);

        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 100,
            'modules_completed' => 10,
            'total_score' => 85,
            'engagement_score' => 75,
        ]);

        $result = $this->service->verifyCertification($this->user->id, [
            'course_id' => $course->id,
            'min_score' => 80,
            'modules_completed' => 5,
            'min_engagement' => 50,
        ]);

        $this->assertTrue($result['eligible']);
        $this->assertEquals(85, $result['score']);
        $this->assertEquals(10, $result['modules_completed']);
        $this->assertTrue($result['criteria_met']['min_score']);
        $this->assertTrue($result['criteria_met']['modules_completed']);
        $this->assertTrue($result['criteria_met']['min_engagement']);
    }

    /**
     * Test verifying certification not eligible
     */
    public function test_verify_certification_not_eligible(): void
    {
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);

        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 50,
            'modules_completed' => 3,
            'total_score' => 65,
            'engagement_score' => 40,
        ]);

        $result = $this->service->verifyCertification($this->user->id, [
            'course_id' => $course->id,
            'min_score' => 80,
            'modules_completed' => 5,
            'min_engagement' => 50,
        ]);

        $this->assertFalse($result['eligible']);
        $this->assertEquals('No progress data found', $result['reason']);
    }

    /**
     * Test generating learning insights
     */
    public function test_generate_learning_insights(): void
    {
        AnalyticsEvent::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'event_type' => 'learning',
            'event_name' => 'course_view',
            'properties' => ['duration' => 30, 'interaction_type' => 'view'],
            'occurred_at' => now()->subDays(5),
        ]);

        AnalyticsEvent::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'event_type' => 'learning',
            'event_name' => 'course_completion',
            'properties' => ['duration' => 60, 'interaction_type' => 'completion'],
            'occurred_at' => now()->subDays(5),
        ]);

        $insights = $this->service->generateLearningInsights([
            'start_date' => now()->subDays(30),
            'end_date' => now(),
        ]);

        $this->assertArrayHasKey('total_interactions', $insights);
        $this->assertArrayHasKey('unique_users', $insights);
        $this->assertArrayHasKey('avg_duration_per_session', $insights);
        $this->assertArrayHasKey('completion_rate', $insights);
        $this->assertArrayHasKey('dropout_rate', $insights);
        $this->assertArrayHasKey('engagement_trends', $insights);
        $this->assertArrayHasKey('anomalies', $insights);
    }

    /**
     * Test processing batch scores
     */
    public function test_process_batch_scores(): void
    {
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);
        $user2 = User::factory()->create(['tenant_id' => $this->tenant->id]);

        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 60,
            'engagement_duration' => 200,
            'interactions_count' => 40,
        ]);

        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user2->id,
            'course_id' => $course->id,
            'progress_percentage' => 80,
            'engagement_duration' => 350,
            'interactions_count' => 60,
        ]);

        $pairs = [
            ['user_id' => $this->user->id, 'course_id' => $course->id],
            ['user_id' => $user2->id, 'course_id' => $course->id],
        ];

        $results = $this->service->processBatchScores($pairs);

        $this->assertEquals(2, $results['processed']);
        $this->assertEquals(0, $results['errors']);
    }

    /**
     * Test tenant isolation in learning progress
     */
    public function test_tenant_isolation_in_learning_progress(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create progress for other user in other tenant
        LearningProgress::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $otherUser->id,
            'course_id' => $course->id,
            'progress_percentage' => 100,
            'modules_completed' => 10,
            'total_score' => 95,
        ]);

        // Create progress for our user
        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 50,
            'modules_completed' => 5,
            'total_score' => 75,
        ]);

        // Get progress for our user - should only see our data
        $progress = $this->service->getLearningProgress($this->user->id, $course->id);

        $this->assertEquals(50, $progress['progress_percentage']);
        $this->assertEquals(5, $progress['modules_completed']);
        $this->assertEquals(75, $progress['total_score']);
    }

    /**
     * Test tenant isolation in learning outcomes
     */
    public function test_tenant_isolation_in_learning_outcomes(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create progress for other user
        LearningProgress::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $otherUser->id,
            'course_id' => $course->id,
            'progress_percentage' => 100,
            'modules_completed' => 10,
            'total_score' => 95,
            'engagement_score' => 90,
            'certified' => true,
        ]);

        // Create progress for our user
        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 50,
            'modules_completed' => 5,
            'total_score' => 70,
            'engagement_score' => 60,
        ]);

        // Get outcomes for our user - should only see our data
        $outcomes = $this->service->analyzeLearningOutcomes($this->user->id);

        $this->assertEquals(1, $outcomes['total_courses']);
        $this->assertEquals(0, $outcomes['completed_courses']);
        $this->assertEquals(0, $outcomes['certifications_earned']);
    }

    /**
     * Test performance rating calculation
     */
    public function test_performance_rating_calculation(): void
    {
        // Test excellent performance
        $excellent = $this->determineTestPerformance(90, 85, 5, 5);
        $this->assertEquals('excellent', $excellent);

        // Test good performance
        $good = $this->determineTestPerformance(70, 75, 4, 5);
        $this->assertEquals('good', $good);

        // Test average performance
        $average = $this->determineTestPerformance(55, 60, 3, 5);
        $this->assertEquals('average', $average);

        // Test below average performance
        $belowAverage = $this->determineTestPerformance(40, 45, 2, 5);
        $this->assertEquals('below_average', $belowAverage);

        // Test needs improvement
        $needsImprovement = $this->determineTestPerformance(25, 30, 1, 5);
        $this->assertEquals('needs_improvement', $needsImprovement);
    }

    /**
     * Helper method to test performance rating
     */
    private function determineTestPerformance(float $avgScore, float $avgEngagement, int $completed, int $total): string
    {
        $completionRate = $completed / $total;
        $compositeScore = ($avgScore * 0.4) + ($avgEngagement * 0.3) + ($completionRate * 100 * 0.3);

        if ($compositeScore >= 80) {
            return 'excellent';
        } elseif ($compositeScore >= 65) {
            return 'good';
        } elseif ($compositeScore >= 50) {
            return 'average';
        } elseif ($compositeScore >= 35) {
            return 'below_average';
        } else {
            return 'needs_improvement';
        }
    }

    /**
     * Test cache is used for progress retrieval
     */
    public function test_cache_is_used_for_progress_retrieval(): void
    {
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);

        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 75,
            'modules_completed' => 7,
            'engagement_score' => 70,
        ]);

        // First call - should hit database
        $progress1 = $this->service->getLearningProgress($this->user->id, $course->id);

        // Update the progress
        LearningProgress::where('user_id', $this->user->id)
            ->where('course_id', $course->id)
            ->update(['progress_percentage' => 80]);

        // Second call within cache TTL - should return cached value (75)
        $progress2 = $this->service->getLearningProgress($this->user->id, $course->id);

        $this->assertEquals($progress1['progress_percentage'], $progress2['progress_percentage']);
    }
}
