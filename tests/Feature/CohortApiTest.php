<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for Cohort API endpoints
 *
 * @covers \App\Http\Controllers\Analytics\CohortController
 */
class CohortApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        Sanctum::actingAs($this->user);
    }

    /**
     * Test listing cohorts with pagination
     */
    public function test_list_cohorts_with_pagination(): void
    {
        Cohort::factory()->count(15)->create([
            'tenant_id' => $this->tenant->id
        ]);

        $response = $this->getJson('/api/analytics/cohorts?per_page=10');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'criteria_json',
                            'members_count',
                            'created_at'
                        ]
                    ],
                    'meta' => [
                        'current_page',
                        'per_page',
                        'total'
                    ]
                ]);

        $this->assertCount(10, $response->json('data'));
    }

    /**
     * Test creating a cohort
     */
    public function test_create_cohort(): void
    {
        $cohortData = [
            'name' => 'Test Cohort 2023',
            'criteria' => [
                'grad_year' => 2023,
                'degree' => 'CS'
            ]
        ];

        $response = $this->postJson('/api/analytics/cohorts', $cohortData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'criteria_json',
                        'members_count'
                    ]
                ]);

        $this->assertDatabaseHas('cohorts', [
            'name' => 'Test Cohort 2023',
            'tenant_id' => $this->tenant->id
        ]);
    }

    /**
     * Test creating cohort with invalid data
     */
    public function test_create_cohort_with_invalid_data(): void
    {
        $invalidData = [
            'name' => '', // Empty name
            'criteria' => ['invalid' => 'criteria']
        ];

        $response = $this->postJson('/api/analytics/cohorts', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'criteria']);
    }

    /**
     * Test showing single cohort
     */
    public function test_show_cohort(): void
    {
        $cohort = Cohort::factory()->create([
            'tenant_id' => $this->tenant->id,
            'members_count' => 5
        ]);

        $response = $this->getJson("/api/analytics/cohorts/{$cohort->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'metrics' => [
                            'size',
                            'retention_30d',
                            'churn'
                        ]
                    ]
                ]);
    }

    /**
     * Test updating cohort
     */
    public function test_update_cohort(): void
    {
        $cohort = Cohort::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Old Name'
        ]);

        $updateData = [
            'name' => 'Updated Cohort Name'
        ];

        $response = $this->putJson("/api/analytics/cohorts/{$cohort->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'name' => 'Updated Cohort Name'
                    ]
                ]);

        $this->assertDatabaseHas('cohorts', [
            'id' => $cohort->id,
            'name' => 'Updated Cohort Name'
        ]);
    }

    /**
     * Test deleting cohort
     */
    public function test_delete_cohort(): void
    {
        $cohort = Cohort::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $response = $this->deleteJson("/api/analytics/cohorts/{$cohort->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('cohorts', [
            'id' => $cohort->id
        ]);
    }

    /**
     * Test comparing cohorts
     */
    public function test_compare_cohorts(): void
    {
        $cohort1 = Cohort::factory()->create([
            'tenant_id' => $this->tenant->id,
            'members_count' => 10
        ]);
        $cohort2 = Cohort::factory()->create([
            'tenant_id' => $this->tenant->id,
            'members_count' => 8
        ]);

        $response = $this->getJson("/api/analytics/cohorts/compare?cohort_ids[]={$cohort1->id}&cohort_ids[]={$cohort2->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'cohorts' => [
                            '*' => [
                                'id',
                                'name',
                                'metrics'
                            ]
                        ],
                        'statistical_significance',
                        'insights'
                    ]
                ]);
    }

    /**
     * Test tenant isolation - users cannot access other tenants' cohorts
     */
    public function test_tenant_isolation(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherCohort = Cohort::factory()->create([
            'tenant_id' => $otherTenant->id
        ]);

        $response = $this->getJson("/api/analytics/cohorts/{$otherCohort->id}");

        $response->assertStatus(404);
    }

    /**
     * Test rate limiting on cohort endpoints
     */
    public function test_rate_limiting(): void
    {
        // Make multiple requests to trigger rate limit
        for ($i = 0; $i < 60; $i++) {
            $response = $this->getJson('/api/analytics/cohorts');
            if ($response->getStatusCode() === 429) {
                $this->assertEquals(429, $response->getStatusCode());
                return;
            }
        }

        $this->fail('Rate limiting was not triggered');
    }

    /**
     * Test unauthenticated access is blocked
     */
    public function test_unauthenticated_access_blocked(): void
    {
        Sanctum::actingAs(User::factory()->create(), []); // Clear tokens

        $response = $this->getJson('/api/analytics/cohorts');

        $response->assertStatus(401);
    }

    /**
     * Test cohort analysis with large dataset
     */
    public function test_cohort_analysis_with_large_dataset(): void
    {
        $cohort = Cohort::factory()->create([
            'tenant_id' => $this->tenant->id,
            'members_count' => 1000
        ]);

        $response = $this->getJson("/api/analytics/cohorts/{$cohort->id}");

        $response->assertStatus(200);

        // Should return analysis data even with large member count
        $this->assertArrayHasKey('data', $response->json());
    }

    /**
     * Test cohort creation with duplicate names allowed
     */
    public function test_cohort_creation_allows_duplicate_names(): void
    {
        Cohort::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Duplicate Name'
        ]);

        $cohortData = [
            'name' => 'Duplicate Name',
            'criteria' => ['grad_year' => 2024]
        ];

        $response = $this->postJson('/api/analytics/cohorts', $cohortData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('cohorts', [
            'name' => 'Duplicate Name',
            'tenant_id' => $this->tenant->id
        ]);
    }

    /**
     * Test API returns proper error for non-existent cohort
     */
    public function test_non_existent_cohort_returns_404(): void
    {
        $response = $this->getJson('/api/analytics/cohorts/99999');

        $response->assertStatus(404)
                ->assertJson([
                    'message' => 'Cohort not found'
                ]);
    }

    /**
     * Test cohort comparison with single cohort returns error
     */
    public function test_compare_single_cohort_returns_error(): void
    {
        $cohort = Cohort::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $response = $this->getJson("/api/analytics/cohorts/compare?cohort_ids[]={$cohort->id}");

        $response->assertStatus(400)
                ->assertJson([
                    'message' => 'At least two cohorts are required for comparison'
                ]);
    }
}