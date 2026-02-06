<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cohort;
use App\Models\AnalyticsEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CohortAnalysisApiTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->adminUser = User::factory()->create([
            'is_super_admin' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'is_super_admin' => false,
        ]);

        // Set up tenant context
        session(['tenant_id' => 1]);
    }

    /**
     * Test creating cohorts with different criteria
     */
    public function test_can_create_cohorts_with_different_criteria(): void
    {
        // Test acquisition date cohort
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/analytics/cohorts/create', [
                'name' => 'Q4 2024 Acquisition Cohort',
                'grouping_criteria' => [
                    'type' => 'acquisition_date',
                    'params' => [
                        'date_range' => [
                            'start' => '2024-10-01',
                            'end' => '2024-12-31',
                        ],
                    ],
                ],
                'description' => 'Users acquired in Q4 2024',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'cohort' => [
                    'id',
                    'cohort_id',
                    'name',
                    'user_count',
                    'criteria',
                    'created_at',
                ],
            ]);

        // Test acquisition source cohort
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/analytics/cohorts/create', [
                'name' => 'Organic Traffic Cohort',
                'grouping_criteria' => [
                    'type' => 'acquisition_source',
                    'params' => [
                        'sources' => ['organic_search', 'direct'],
                    ],
                ],
            ]);

        $response->assertStatus(201);

        // Test characteristics cohort
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/analytics/cohorts/create', [
                'name' => 'High-Value Users',
                'grouping_criteria' => [
                    'type' => 'characteristics',
                    'params' => [
                        'filters' => [
                            'subscription_tier' => 'premium',
                            'engagement_score' => ['>', 80],
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(201);
    }

    /**
     * Test authorization - only admins can create cohorts
     */
    public function test_only_admins_can_create_cohorts(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->postJson('/api/analytics/cohorts/create', [
                'name' => 'Test Cohort',
                'grouping_criteria' => [
                    'type' => 'acquisition_date',
                    'params' => [
                        'date_range' => [
                            'start' => '2024-01-01',
                            'end' => '2024-12-31',
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test cohort validation
     */
    public function test_cohort_validation(): void
    {
        // Test missing name
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/analytics/cohorts/create', [
                'grouping_criteria' => [
                    'type' => 'acquisition_date',
                    'params' => ['date_range' => ['start' => '2024-01-01', 'end' => '2024-12-31']],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        // Test invalid criteria type
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/analytics/cohorts/create', [
                'name' => 'Test Cohort',
                'grouping_criteria' => [
                    'type' => 'invalid_type',
                    'params' => [],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['grouping_criteria.type']);
    }

    /**
     * Test retrieving cohort metrics
     */
    public function test_can_retrieve_cohort_metrics(): void
    {
        // Create a test cohort
        $cohort = Cohort::factory()->create([
            'tenant_id' => 1,
            'name' => 'Test Cohort',
            'criteria' => [
                'type' => 'acquisition_date',
                'params' => ['date_range' => ['start' => '2024-01-01', 'end' => '2024-12-31']],
            ],
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/analytics/cohorts/{$cohort->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'cohort' => [
                    'id',
                    'name',
                    'criteria',
                    'status',
                    'user_count',
                    'created_at',
                    'created_by',
                ],
                'metrics' => [
                    'retention',
                    'engagement',
                    'conversion',
                ],
                'insights',
            ]);
    }

    /**
     * Test cohort comparison
     */
    public function test_can_compare_cohorts(): void
    {
        // Create test cohorts
        $cohort1 = Cohort::factory()->create([
            'tenant_id' => 1,
            'name' => 'Cohort A',
            'created_by' => $this->adminUser->id,
        ]);

        $cohort2 = Cohort::factory()->create([
            'tenant_id' => 1,
            'name' => 'Cohort B',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/analytics/cohorts/compare', [
                'cohort_ids' => [$cohort1->id, $cohort2->id],
                'metrics' => ['retention', 'engagement'],
                'include_statistical_significance' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'comparison' => [
                    'cohorts',
                    'statistical_significance',
                    'best_performing',
                ],
                'metadata',
            ]);
    }

    /**
     * Test cohort comparison validation
     */
    public function test_cohort_comparison_validation(): void
    {
        // Test minimum cohorts
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/analytics/cohorts/compare', [
                'cohort_ids' => ['non-existent-id'],
                'metrics' => ['retention'],
            ]);

        $response->assertStatus(422);

        // Test maximum cohorts
        $cohortIds = range(1, 6); // More than 5
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/analytics/cohorts/compare', [
                'cohort_ids' => $cohortIds,
                'metrics' => ['retention'],
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test listing cohorts with pagination
     */
    public function test_can_list_cohorts_with_pagination(): void
    {
        // Create multiple cohorts
        Cohort::factory()->count(15)->create([
            'tenant_id' => 1,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/analytics/cohorts?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'cohorts' => [
                    'data',
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);

        // Verify pagination
        $this->assertCount(10, $response->json('cohorts.data'));
        $this->assertEquals(15, $response->json('cohorts.total'));
    }

    /**
     * Test cohort filtering
     */
    public function test_can_filter_cohorts(): void
    {
        Cohort::factory()->create([
            'tenant_id' => 1,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        Cohort::factory()->create([
            'tenant_id' => 1,
            'status' => 'archived',
            'created_by' => $this->adminUser->id,
        ]);

        // Filter by status
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/analytics/cohorts?status=active');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('cohorts.data'));
        $this->assertEquals('active', $response->json('cohorts.data.0.status'));
    }

    /**
     * Test error handling for non-existent cohort
     */
    public function test_returns_404_for_non_existent_cohort(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/analytics/cohorts/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => 'Cohort not found',
            ]);
    }

    /**
     * Test duplicate cohort names are not allowed
     */
    public function test_cannot_create_duplicate_cohort_names(): void
    {
        Cohort::factory()->create([
            'tenant_id' => 1,
            'name' => 'Duplicate Cohort',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/analytics/cohorts/create', [
                'name' => 'Duplicate Cohort',
                'grouping_criteria' => [
                    'type' => 'acquisition_date',
                    'params' => [
                        'date_range' => [
                            'start' => '2024-01-01',
                            'end' => '2024-12-31',
                        ],
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test cohort data includes metrics calculations
     */
    public function test_cohort_data_includes_calculated_metrics(): void
    {
        $cohort = Cohort::factory()->create([
            'tenant_id' => 1,
            'created_by' => $this->adminUser->id,
        ]);

        // Create some test analytics events
        AnalyticsEvent::factory()->count(50)->create([
            'user_id' => $this->adminUser->id,
            'tenant_id' => 1,
            'event_name' => 'page_view',
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/analytics/cohorts/{$cohort->id}");

        $response->assertStatus(200);

        // Verify metrics structure
        $metrics = $response->json('metrics');
        $this->assertArrayHasKey('retention', $metrics);
        $this->assertArrayHasKey('engagement', $metrics);
        $this->assertArrayHasKey('conversion', $metrics);

        // Verify insights are present
        $this->assertArrayHasKey('insights', $response->json());
    }

    /**
     * Test large dataset pagination (>1000 data points)
     */
    public function test_handles_large_datasets_with_pagination(): void
    {
        // Create many cohorts to test pagination
        Cohort::factory()->count(150)->create([
            'tenant_id' => 1,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/analytics/cohorts?per_page=50&page=2');

        $response->assertStatus(200);
        $this->assertCount(50, $response->json('cohorts.data'));
        $this->assertEquals(2, $response->json('cohorts.current_page'));
        $this->assertEquals(150, $response->json('cohorts.total'));
    }

    /**
     * Test cohort comparison with statistical significance
     */
    public function test_cohort_comparison_includes_statistical_significance(): void
    {
        $cohort1 = Cohort::factory()->create([
            'tenant_id' => 1,
            'created_by' => $this->adminUser->id,
        ]);

        $cohort2 = Cohort::factory()->create([
            'tenant_id' => 1,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/analytics/cohorts/compare', [
                'cohort_ids' => [$cohort1->id, $cohort2->id],
                'metrics' => ['retention'],
                'include_statistical_significance' => true,
            ]);

        $response->assertStatus(200);

        $comparison = $response->json('comparison');
        $this->assertArrayHasKey('statistical_significance', $comparison);
        $this->assertArrayHasKey('best_performing', $comparison);
    }
}
