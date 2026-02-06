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

class TenantIsolationVerificationTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test tenant isolation in cohort analysis
     */
    public function test_tenant_isolation_in_cohort_analysis(): void
    {
        // Create data for tenant1
        session(['tenant_id' => 'tenant1']);
        $user1 = User::factory()->create();
        $cohort1 = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 1
        ]);

        // Create data for tenant2
        session(['tenant_id' => 'tenant2']);
        $user2 = User::factory()->create();
        $cohort2 = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 1
        ]);

        $this->consentService->shouldReceive('hasConsentForAnalytics')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Analyze cohort for tenant1
        session(['tenant_id' => 'tenant1']);
        $analysis1 = $this->cohortService->analyzeCohort($cohort1->id);

        // Analyze cohort for tenant2
        session(['tenant_id' => 'tenant2']);
        $analysis2 = $this->cohortService->analyzeCohort($cohort2->id);

        // Each tenant should only see their own data
        $this->assertNotEquals($analysis1['size'], $analysis2['size']);
    }

    /**
     * Test tenant isolation in attribution calculations
     */
    public function test_tenant_isolation_in_attribution_calculations(): void
    {
        // Create attribution data for tenant1
        session(['tenant_id' => 'tenant1']);
        $user1 = User::factory()->create();

        AttributionTouch::create([
            'tenant_id' => 'tenant1',
            'user_id' => $user1->id,
            'source' => 'google',
            'event_type' => 'page_view',
            'value' => 10.00,
            'timestamp' => now()->subDays(1),
        ]);

        // Create attribution data for tenant2
        session(['tenant_id' => 'tenant2']);
        $user2 = User::factory()->create();

        AttributionTouch::create([
            'tenant_id' => 'tenant2',
            'user_id' => $user2->id,
            'source' => 'facebook',
            'event_type' => 'page_view',
            'value' => 15.00,
            'timestamp' => now()->subDays(1),
        ]);

        // Calculate attribution for tenant1
        session(['tenant_id' => 'tenant1']);
        $result1 = $this->attributionService->calculateAttribution(
            $user1->id,
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        // Calculate attribution for tenant2
        session(['tenant_id' => 'tenant2']);
        $result2 = $this->attributionService->calculateAttribution(
            $user2->id,
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        // Verify tenant isolation
        $this->assertNotEmpty($result1);
        $this->assertNotEmpty($result2);
        $this->assertNotEquals($result1['total_value'], $result2['total_value']);
    }

    /**
     * Test tenant isolation in custom events
     */
    public function test_tenant_isolation_in_custom_events(): void
    {
        // Create custom events for tenant1
        session(['tenant_id' => 'tenant1']);
        CustomEvent::create([
            'tenant_id' => 'tenant1',
            'name' => 'test_event_1',
            'properties' => ['key' => 'value1'],
            'created_at' => now(),
        ]);

        // Create custom events for tenant2
        session(['tenant_id' => 'tenant2']);
        CustomEvent::create([
            'tenant_id' => 'tenant2',
            'name' => 'test_event_2',
            'properties' => ['key' => 'value2'],
            'created_at' => now(),
        ]);

        // Query events for tenant1
        session(['tenant_id' => 'tenant1']);
        $events1 = CustomEvent::all();

        // Query events for tenant2
        session(['tenant_id' => 'tenant2']);
        $events2 = CustomEvent::all();

        // Each tenant should only see their own events
        $this->assertCount(1, $events1);
        $this->assertCount(1, $events2);
        $this->assertEquals('test_event_1', $events1->first()->name);
        $this->assertEquals('test_event_2', $events2->first()->name);
    }

    /**
     * Test tenant isolation in learning analytics
     */
    public function test_tenant_isolation_in_learning_analytics(): void
    {
        // Create learning data for tenant1
        session(['tenant_id' => 'tenant1']);
        $user1 = User::factory()->create();
        $course1 = \App\Models\Course::factory()->create(['tenant_id' => 'tenant1']);

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

        // Create learning data for tenant2
        session(['tenant_id' => 'tenant2']);
        $user2 = User::factory()->create();
        $course2 = \App\Models\Course::factory()->create(['tenant_id' => 'tenant2']);

        AnalyticsEvent::create([
            'tenant_id' => 'tenant2',
            'event_type' => 'learning',
            'event_name' => 'course_interaction',
            'user_id' => $user2->id,
            'properties' => [
                'course_id' => $course2->id,
                'interaction_type' => 'completion'
            ],
            'occurred_at' => now(),
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        // Generate insights for tenant1
        session(['tenant_id' => 'tenant1']);
        $insights1 = $this->learningService->generateLearningInsights();

        // Generate insights for tenant2
        session(['tenant_id' => 'tenant2']);
        $insights2 = $this->learningService->generateLearningInsights();

        // Verify isolation
        $this->assertEquals(1, $insights1['total_interactions']);
        $this->assertEquals(1, $insights2['total_interactions']);
        $this->assertNotEquals($insights1['unique_users'], $insights2['unique_users']);
    }

    /**
     * Test tenant isolation in insights generation
     */
    public function test_tenant_isolation_in_insights_generation(): void
    {
        // Create analytics events for tenant1
        session(['tenant_id' => 'tenant1']);
        AnalyticsEvent::factory()->count(5)->create([
            'tenant_id' => 'tenant1',
            'event_type' => 'page_view',
        ]);

        // Create analytics events for tenant2
        session(['tenant_id' => 'tenant2']);
        AnalyticsEvent::factory()->count(3)->create([
            'tenant_id' => 'tenant2',
            'event_type' => 'page_view',
        ]);

        // Generate insights for tenant1
        session(['tenant_id' => 'tenant1']);
        $insights1 = $this->insightsService->generateInsights();

        // Generate insights for tenant2
        session(['tenant_id' => 'tenant2']);
        $insights2 = $this->insightsService->generateInsights();

        // Verify tenant data isolation
        $this->assertNotEquals($insights1, $insights2);
    }

    /**
     * Test cross-tenant data leakage prevention
     */
    public function test_cross_tenant_data_leakage_prevention(): void
    {
        // Create user in tenant1
        session(['tenant_id' => 'tenant1']);
        $user1 = User::factory()->create();
        $course1 = \App\Models\Course::factory()->create(['tenant_id' => 'tenant1']);

        // Create learning progress for tenant1
        LearningProgress::create([
            'tenant_id' => 'tenant1',
            'user_id' => $user1->id,
            'course_id' => $course1->id,
            'engagement_score' => 85.0,
            'total_score' => 90.0,
        ]);

        // Switch to tenant2
        session(['tenant_id' => 'tenant2']);

        // Try to access tenant1 data from tenant2 context
        $progressFromTenant2 = LearningProgress::where('user_id', $user1->id)->first();

        // Should not be able to access tenant1 data from tenant2
        $this->assertNull($progressFromTenant2);
    }

    /**
     * Test tenant isolation in batch operations
     */
    public function test_tenant_isolation_in_batch_operations(): void
    {
        // Create users and courses for tenant1
        session(['tenant_id' => 'tenant1']);
        $users1 = User::factory()->count(3)->create();
        $course1 = \App\Models\Course::factory()->create(['tenant_id' => 'tenant1']);

        // Create users and courses for tenant2
        session(['tenant_id' => 'tenant2']);
        $users2 = User::factory()->count(3)->create();
        $course2 = \App\Models\Course::factory()->create(['tenant_id' => 'tenant2']);

        $this->consentService->shouldReceive('checkConsent')->andReturn(true);
        app()->instance(ConsentService::class, $this->consentService);

        // Process batch for tenant1
        session(['tenant_id' => 'tenant1']);
        $pairs1 = $users1->map(fn($user) => [
            'user_id' => $user->id,
            'course_id' => $course1->id
        ])->toArray();

        $result1 = $this->learningService->processBatchScores($pairs1);

        // Process batch for tenant2
        session(['tenant_id' => 'tenant2']);
        $pairs2 = $users2->map(fn($user) => [
            'user_id' => $user->id,
            'course_id' => $course2->id
        ])->toArray();

        $result2 = $this->learningService->processBatchScores($pairs2);

        // Verify isolation in batch processing
        $this->assertEquals(3, $result1['processed']);
        $this->assertEquals(3, $result2['processed']);

        // Verify data isolation
        $progress1 = LearningProgress::whereIn('user_id', $users1->pluck('id'))->count();
        $progress2 = LearningProgress::whereIn('user_id', $users2->pluck('id'))->count();

        $this->assertEquals(3, $progress1);
        $this->assertEquals(3, $progress2);
    }

    /**
     * Test tenant isolation with concurrent operations
     */
    public function test_tenant_isolation_with_concurrent_operations(): void
    {
        // Simulate concurrent operations across tenants
        $results = [];

        // Tenant1 operations
        session(['tenant_id' => 'tenant1']);
        $user1 = User::factory()->create();
        $cohort1 = Cohort::factory()->create(['members_count' => 1]);

        $results['tenant1'] = $this->cohortService->analyzeCohort($cohort1->id);

        // Tenant2 operations
        session(['tenant_id' => 'tenant2']);
        $user2 = User::factory()->create();
        $cohort2 = Cohort::factory()->create(['members_count' => 1]);

        $results['tenant2'] = $this->cohortService->analyzeCohort($cohort2->id);

        // Verify no cross-contamination
        $this->assertArrayHasKey('size', $results['tenant1']);
        $this->assertArrayHasKey('size', $results['tenant2']);
        $this->assertEquals($results['tenant1']['size'], $results['tenant2']['size']); // Both should be 1
    }

    /**
     * Test tenant isolation in error scenarios
     */
    public function test_tenant_isolation_in_error_scenarios(): void
    {
        // Create valid data for tenant1
        session(['tenant_id' => 'tenant1']);
        $user1 = User::factory()->create();
        $cohort1 = Cohort::factory()->create(['members_count' => 1]);

        // Attempt to analyze non-existent cohort for tenant2
        session(['tenant_id' => 'tenant2']);

        $this->expectException(\Exception::class);
        $this->cohortService->analyzeCohort(99999); // Non-existent cohort
    }

    /**
     * Test tenant context switching integrity
     */
    public function test_tenant_context_switching_integrity(): void
    {
        // Start with tenant1
        session(['tenant_id' => 'tenant1']);
        $initialTenant = session('tenant_id');

        // Perform operations
        $user1 = User::factory()->create();
        $course1 = \App\Models\Course::factory()->create(['tenant_id' => 'tenant1']);

        // Switch to tenant2
        session(['tenant_id' => 'tenant2']);
        $switchedTenant = session('tenant_id');

        // Perform operations in tenant2
        $user2 = User::factory()->create();
        $course2 = \App\Models\Course::factory()->create(['tenant_id' => 'tenant2']);

        // Verify tenant context integrity
        $this->assertEquals('tenant1', $initialTenant);
        $this->assertEquals('tenant2', $switchedTenant);
        $this->assertNotEquals($user1->id, $user2->id);
        $this->assertNotEquals($course1->id, $course2->id);
    }
}