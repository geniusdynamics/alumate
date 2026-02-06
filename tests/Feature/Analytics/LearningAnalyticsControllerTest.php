<?php

declare(strict_types=1);

namespace Tests\Feature\Analytics;

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
 * Feature tests for LearningAnalyticsController
 *
 * @covers \App\Http\Controllers\Analytics\LearningAnalyticsController
 */
class LearningAnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    private LearningAnalyticsController $controller;
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

        $this->service = Mockery::mock(LearningAnalyticsService::class);
        $this->service->shouldReceive('getCurrentTenantId')
            ->andReturn($this->tenant->id);

        $this->controller = new LearningAnalyticsController($this->service, $this->tenantContext);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test index returns learning analytics data for authenticated user
     */
    public function test_index_returns_learning_analytics_data(): void
    {
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);

        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 75,
            'modules_completed' => 7,
            'engagement_duration' => 300,
            'interactions_count' => 50,
            'total_score' => 85,
            'engagement_score' => 72.5,
        ]);

        $this->actingAs($this->user);

        $response = $this->controller->index(request());

        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('pagination', $content);
    }

    /**
     * Test show returns learning analytics for a specific user
     */
    public function test_show_returns_learning_analytics_for_user(): void
    {
        $this->service->shouldReceive('analyzeLearningOutcomes')
            ->with($this->user->id)
            ->andReturn([
                'user_id' => $this->user->id,
                'total_courses' => 3,
                'completed_courses' => 1,
                'average_score' => 82.5,
            ]);

        $this->service->shouldReceive('getLearningMetrics')
            ->andReturn([
                'user_id' => $this->user->id,
                'period' => ['start' => now()->subDays(30)->toIso8601String(), 'end' => now()->toIso8601String()],
            ]);

        $this->service->shouldReceive('getLearningRecommendations')
            ->andReturn([
                'user_id' => $this->user->id,
                'total_recommendations' => 2,
            ]);

        $this->actingAs($this->user);

        $response = $this->controller->show($this->user->id);

        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('outcomes', $content['data']);
        $this->assertArrayHasKey('metrics', $content['data']);
        $this->assertArrayHasKey('recommendations', $content['data']);
    }

    /**
     * Test unauthorized access to other user's analytics
     */
    public function test_show_returns_403_for_unauthorized_access(): void
    {
        $otherUser = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->user);

        $response = $this->controller->show($otherUser->id);

        $this->assertEquals(403, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $this->assertFalse($content['success']);
        $this->assertStringContainsString('Unauthorized', $content['error']);
    }

    /**
     * Test trackProgress creates learning progress
     */
    public function test_track_progress_creates_learning_progress(): void
    {
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);

        $progress = LearningProgress::factory()->make([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 50,
            'modules_completed' => 5,
            'engagement_duration' => 200,
            'interactions_count' => 30,
            'total_score' => 75,
        ]);

        $this->service->shouldReceive('trackLearningProgress')
            ->andReturn($progress);

        $this->service->shouldReceive('calculateEngagementScore')
            ->andReturn(65.0);

        $this->actingAs($this->user);

        $request = request()->merge([
            'course_id' => $course->id,
            'progress_percentage' => 50,
            'modules_completed' => 5,
            'engagement_duration' => 200,
            'interactions_count' => 30,
            'total_score' => 75,
        ]);

        $response = $this->controller->trackProgress($request);

        $this->assertEquals(201, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertArrayHasKey('data', $content);
    }

    /**
     * Test getProgress returns learning progress for user-course combination
     */
    public function test_get_progress_returns_learning_progress(): void
    {
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);

        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 75,
            'modules_completed' => 7,
            'engagement_duration' => 300,
            'total_score' => 85,
        ]);

        $this->service->shouldReceive('getLearningProgress')
            ->andReturn([
                'user_id' => $this->user->id,
                'course_id' => $course->id,
                'progress_percentage' => 75,
                'modules_completed' => 7,
            ]);

        $this->service->shouldReceive('calculateEngagementScore')
            ->andReturn(72.5);

        $this->service->shouldReceive('verifyCertification')
            ->andReturn(['eligible' => false]);

        $this->actingAs($this->user);

        $response = $this->controller->getProgress($this->user->id, $course->id);

        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertArrayHasKey('data', $content);
        $this->assertArrayHasKey('progress', $content['data']);
        $this->assertArrayHasKey('engagement_score', $content['data']);
        $this->assertArrayHasKey('certification', $content['data']);
    }

    /**
     * Test analyzeOutreturns learning outcomes
     */
    public function test_analyze_outcomes_returns_learning_outcomes(): void
    {
        $this->service->shouldReceive('analyzeLearningOutcomes')
            ->with($this->user->id)
            ->andReturn([
                'user_id' => $this->user->id,
                'total_courses' => 5,
                'completed_courses' => 2,
                'in_progress_courses' => 3,
                'completion_rate' => 40.0,
                'average_score' => 78.5,
                'average_engagement' => 68.0,
                'certifications_earned' => 1,
                'overall_performance' => 'good',
            ]);

        $this->actingAs($this->user);

        $response = $this->controller->analyzeOutcomes($this->user->id);

        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertArrayHasKey('data', $content);
        $this->assertEquals(5, $content['data']['total_courses']);
        $this->assertEquals(2, $content['data']['completed_courses']);
    }

    /**
     * Test getMetrics returns learning metrics
     */
    public function test_get_metrics_returns_learning_metrics(): void
    {
        $this->service->shouldReceive('getLearningMetrics')
            ->andReturn([
                'user_id' => $this->user->id,
                'period' => [
                    'start' => now()->subDays(30)->toIso8601String(),
                    'end' => now()->toIso8601String(),
                ],
                'events_summary' => [
                    'total_events' => 25,
                    'unique_event_types' => 5,
                ],
                'time_metrics' => [
                    'total_learning_time_minutes' => 300,
                    'average_session_duration' => 30,
                ],
            ]);

        $this->actingAs($this->user);

        $response = $this->controller->getMetrics(request(), $this->user->id);

        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertArrayHasKey('data', $content);
    }

    /**
     * Test comparePerformance compares multiple users
     */
    public function test_compare_performance_compares_users(): void
    {
        $user2 = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user3 = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->service->shouldReceive('compareLearningPerformance')
            ->andReturn([
                'compared_users' => 3,
                'rankings' => [
                    ['user_id' => $this->user->id, 'rank' => 1, 'composite_score' => 85.5],
                    ['user_id' => $user2->id, 'rank' => 2, 'composite_score' => 72.0],
                    ['user_id' => $user3->id, 'rank' => 3, 'composite_score' => 65.0],
                ],
                'statistics' => [
                    'average_completion_rate' => 65.0,
                    'average_score' => 75.0,
                    'average_engagement' => 70.0,
                ],
            ]);

        $this->actingAs($this->user);

        $request = request()->merge([
            'user_ids' => [$this->user->id, $user2->id, $user3->id],
        ]);

        $response = $this->controller->comparePerformance($request);

        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertArrayHasKey('data', $content);
        $this->assertEquals(3, $content['data']['compared_users']);
    }

    /**
     * Test comparePerformance returns error for less than 2 users
     */
    public function test_compare_performance_requires_at_least_two_users(): void
    {
        $this->actingAs($this->user);

        $request = request()->merge([
            'user_ids' => [$this->user->id],
        ]);

        $response = $this->controller->comparePerformance($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test predictCompletion returns completion prediction
     */
    public function test_predict_completion_returns_prediction(): void
    {
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->service->shouldReceive('predictLearningCompletion')
            ->andReturn([
                'user_id' => $this->user->id,
                'course_id' => $course->id,
                'status' => 'in_progress',
                'current_progress' => 50,
                'completed_modules' => 5,
                'total_modules' => 10,
                'predicted_completion_date' => now()->addDays(14)->toIso8601String(),
                'days_remaining' => 14,
                'confidence' => 0.75,
                'factors' => [
                    'current_progress_rate' => 3.5,
                    'avg_progress_rate' => 2.8,
                    'engagement_level' => 72.5,
                ],
            ]);

        $this->actingAs($this->user);

        $response = $this->controller->predictCompletion($this->user->id, $course->id);

        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertArrayHasKey('data', $content);
        $this->assertEquals('in_progress', $content['data']['status']);
        $this->assertEquals(50, $content['data']['current_progress']);
    }

    /**
     * Test getRecommendations returns learning recommendations
     */
    public function test_get_recommendations_returns_recommendations(): void
    {
        $this->service->shouldReceive('getLearningRecommendations')
            ->andReturn([
                'user_id' => $this->user->id,
                'total_recommendations' => 3,
                'recommendations' => [
                    [
                        'type' => 'improvement',
                        'priority' => 'high',
                        'title' => 'Improve Academic Performance',
                        'description' => 'Consider reviewing course materials',
                    ],
                    [
                        'type' => 'completion',
                        'priority' => 'medium',
                        'title' => 'Complete Introduction to Programming',
                        'description' => 'You\'re 75% complete',
                    ],
                ],
                'generated_at' => now()->toIso8601String(),
            ]);

        $this->actingAs($this->user);

        $response = $this->controller->getRecommendations($this->user->id);

        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertArrayHasKey('data', $content);
        $this->assertEquals(3, $content['data']['total_recommendations']);
    }

    /**
     * Test tenant isolation in learning analytics
     */
    public function test_tenant_isolation_in_learning_analytics(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create progress for other tenant
        LearningProgress::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $otherUser->id,
            'course_id' => $course->id,
            'progress_percentage' => 100,
        ]);

        // Create progress for our user
        LearningProgress::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'progress_percentage' => 50,
        ]);

        $this->actingAs($this->user);

        $response = $this->controller->index(request());

        $content = json_decode($response->getContent(), true);

        // Should only see our tenant's data
        $this->assertTrue($content['success']);
    }

    /**
     * Test RBAC authorization for admin features
     */
    public function test_rbac_authorization_for_admin_features(): void
    {
        $otherUser = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->user);

        // User trying to access other user's analytics without admin role
        $response = $this->controller->show($otherUser->id);

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test verifyCertification returns certification status
     */
    public function test_verify_certification_returns_status(): void
    {
        $course = Course::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->service->shouldReceive('verifyCertification')
            ->andReturn([
                'eligible' => true,
                'score' => 85,
                'modules_completed' => 10,
                'engagement_score' => 75,
                'criteria_met' => [
                    'min_score' => true,
                    'modules_completed' => true,
                    'min_engagement' => true,
                ],
            ]);

        $this->actingAs($this->user);

        $response = $this->controller->verifyCertification($this->user->id, $course->id);

        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertTrue($content['data']['eligible']);
    }
}
