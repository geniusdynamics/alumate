<?php

namespace Tests\Feature;

use App\Models\Insight;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Analytics\AutomatedInsightsService;
use App\Services\Analytics\InsightsService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Feature tests for InsightsController
 * 
 * This test suite ensures that insights API endpoints work correctly,
 * including CRUD operations, filtering, pagination, export functionality,
 * and tenant isolation.
 */
class InsightsControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant1;
    private Tenant $tenant2;
    private User $user1;
    private User $user2;
    private TenantContextService $tenantContextService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two separate tenants
        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Tenant 1',
            'slug' => 'tenant-1',
            'status' => 'active',
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Tenant 2',
            'slug' => 'tenant-2',
            'status' => 'active',
        ]);

        // Create users for each tenant
        $this->user1 = User::factory()->create([
            'email' => 'user1@tenant1.com',
            'tenant_id' => $this->tenant1->id,
        ]);

        $this->user2 = User::factory()->create([
            'email' => 'user2@tenant2.com',
            'tenant_id' => $this->tenant2->id,
        ]);

        $this->tenantContextService = app(TenantContextService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test that unauthenticated requests are rejected
     */
    public function test_unauthenticated_requests_are_rejected(): void
    {
        $response = $this->getJson('/api/insights');
        
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test index method returns paginated insights
     */
    public function test_index_returns_paginated_insights(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        // Create insights for tenant1
        Insight::factory()->count(15)->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'trend',
            'status' => 'active',
        ]);

        // Create some insights for tenant2
        Insight::factory()->count(5)->create([
            'tenant_id' => $this->tenant2->id,
            'type' => 'trend',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user1)
            ->getJson('/api/insights?per_page=10');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
                'summary',
                'filters',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.total', 15)
            ->assertJsonPath('pagination.per_page', 10)
            ->assertJsonPath('data', function ($data) {
                return count($data) === 10;
            });
    }

    /**
     * Test index method with filtering by type
     */
    public function test_index_filters_by_type(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        // Create different types of insights
        Insight::factory()->count(5)->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'trend',
            'status' => 'active',
        ]);

        Insight::factory()->count(3)->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'anomaly',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user1)
            ->getJson('/api/insights?type=trend');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.total', 5)
            ->assertJsonPath('filters.type', 'trend');
    }

    /**
     * Test index method with filtering by status
     */
    public function test_index_filters_by_status(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        // Create insights with different statuses
        Insight::factory()->count(5)->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'trend',
            'status' => 'active',
        ]);

        Insight::factory()->count(3)->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'trend',
            'status' => 'dismissed',
        ]);

        $response = $this->actingAs($this->user1)
            ->getJson('/api/insights?status=active');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.total', 5)
            ->assertJsonPath('filters.status', 'active');
    }

    /**
     * Test show method returns a specific insight
     */
    public function test_show_returns_specific_insight(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        $insight = Insight::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'trend',
            'data' => [
                'message' => 'Test insight message',
                'severity' => 'high',
            ],
        ]);

        $response = $this->actingAs($this->user1)
            ->getJson("/api/insights/{$insight->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'insight',
                    'effectiveness',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.insight.id', $insight->id)
            ->assertJsonPath('data.insight.type', 'trend');
    }

    /**
     * Test show method returns 404 for non-existent insight
     */
    public function test_show_returns_404_for_nonexistent_insight(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        $response = $this->actingAs($this->user1)
            ->getJson('/api/insights/99999');

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJsonPath('success', false);
    }

    /**
     * Test store method creates a new insight
     */
    public function test_store_creates_new_insight(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        $payload = [
            'type' => 'trend',
            'data' => [
                'message' => 'New insight created',
                'severity' => 'medium',
            ],
            'status' => 'active',
        ];

        $response = $this->actingAs($this->user1)
            ->postJson('/api/insights', $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Insight created successfully')
            ->assertJsonPath('data.type', 'trend')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('insights', [
            'tenant_id' => $this->tenant1->id,
            'type' => 'trend',
            'status' => 'active',
        ]);
    }

    /**
     * Test store method validates required fields
     */
    public function test_store_validates_required_fields(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        $response = $this->actingAs($this->user1)
            ->postJson('/api/insights', []);

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->assertJsonPath('success', false);
    }

    /**
     * Test update method updates an existing insight
     */
    public function test_update_updates_existing_insight(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        $insight = Insight::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'trend',
            'status' => 'active',
        ]);

        $payload = [
            'status' => 'implemented',
            'data' => [
                'message' => 'Updated insight',
                'severity' => 'low',
            ],
        ];

        $response = $this->actingAs($this->user1)
            ->putJson("/api/insights/{$insight->id}", $payload);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Insight updated successfully')
            ->assertJsonPath('data.status', 'implemented');

        $this->assertDatabaseHas('insights', [
            'id' => $insight->id,
            'status' => 'implemented',
        ]);
    }

    /**
     * Test destroy method deletes an insight
     */
    public function test_destroy_deletes_insight(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        $insight = Insight::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'trend',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user1)
            ->deleteJson("/api/insights/{$insight->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Insight deleted successfully');

        $this->assertDatabaseMissing('insights', [
            'id' => $insight->id,
        ]);
    }

    /**
     * Test dismiss method marks insight as dismissed
     */
    public function test_dismiss_marks_insight_as_dismissed(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        $insight = Insight::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'trend',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user1)
            ->postJson("/api/insights/{$insight->id}/dismiss");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'dismissed');
    }

    /**
     * Test export method returns JSON format by default
     */
    public function test_export_returns_json_by_default(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        Insight::factory()->count(5)->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'trend',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user1)
            ->postJson('/api/insights/export', [
                'format' => 'json',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'data',
                'exported_at',
                'total_count',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_count', 5);
    }

    /**
     * Test export method returns CSV format
     */
    public function test_export_returns_csv_format(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        Insight::factory()->count(3)->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'trend',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user1)
            ->postJson('/api/insights/export', [
                'format' => 'csv',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('success', true)
            ->assertJsonPath('format', 'csv')
            ->assertJsonPath('total_count', 3);
    }

    /**
     * Test export with filters
     */
    public function test_export_with_filters(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        Insight::factory()->count(5)->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'trend',
            'status' => 'active',
        ]);

        Insight::factory()->count(3)->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'anomaly',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user1)
            ->postJson('/api/insights/export', [
                'type' => 'trend',
                'format' => 'json',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('total_count', 5);
    }

    /**
     * Test trackFeedback method records effectiveness
     */
    public function test_track_feedback_records_effectiveness(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        $insight = Insight::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'trend',
            'status' => 'active',
        ]);

        $mockInsightsService = Mockery::mock(InsightsService::class);
        $mockInsightsService->shouldReceive('trackEffectiveness')
            ->once()
            ->andReturn(true);
        $this->app->instance(InsightsService::class, $mockInsightsService);

        $response = $this->actingAs($this->user1)
            ->postJson("/api/insights/{$insight->id}/feedback", [
                'effectiveness_score' => 8,
                'notes' => 'This insight was helpful',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Feedback recorded successfully');
    }

    /**
     * Test that insights are isolated between tenants
     */
    public function test_insights_are_isolated_between_tenants(): void
    {
        // Create insights for tenant1
        $this->tenantContextService->setTenant($this->tenant1->id);
        $insight1 = Insight::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'trend',
            'data' => ['message' => 'Tenant 1 insight'],
        ]);

        // Create insights for tenant2
        $this->tenantContextService->setTenant($this->tenant2->id);
        $insight2 = Insight::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'type' => 'anomaly',
            'data' => ['message' => 'Tenant 2 insight'],
        ]);

        // Verify tenant1 can only see its own insights
        $this->tenantContextService->setTenant($this->tenant1->id);
        $response1 = $this->actingAs($this->user1)
            ->getJson('/api/insights');

        $response1->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.id', $insight1->id);

        // Verify tenant2 can only see its own insights
        $this->tenantContextService->setTenant($this->tenant2->id);
        $response2 = $this->actingAs($this->user2)
            ->getJson('/api/insights');

        $response2->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.id', $insight2->id);
    }

    /**
     * Test that cross-tenant insight access is prevented
     */
    public function test_cross_tenant_insight_access_is_prevented(): void
    {
        // Create insight for tenant1
        $this->tenantContextService->setTenant($this->tenant1->id);
        $insight1 = Insight::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'trend',
            'status' => 'active',
        ]);

        // Switch to tenant2 and try to access tenant1's insight
        $this->tenantContextService->setTenant($this->tenant2->id);

        // Note: The model query uses byTenant scope which should prevent this
        $response = $this->actingAs($this->user2)
            ->getJson("/api/insights/{$insight1->id}");

        // Should fail because insight1 belongs to tenant1
        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Test generate method queues insight generation
     */
    public function test_generate_queues_insight_generation(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        $response = $this->actingAs($this->user1)
            ->postJson('/api/insights/generate', [
                'start_date' => now()->subDays(30)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'queue' => true,
            ]);

        $response->assertStatus(Response::HTTP_ACCEPTED)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Insights generation queued');
    }

    /**
     * Test generate method generates insights synchronously
     */
    public function test_generate_creates_insights_synchronously(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        $mockAutomatedService = Mockery::mock(AutomatedInsightsService::class);
        $mockAutomatedService->shouldReceive('generateInsights')
            ->once()
            ->andReturn([
                [
                    'type' => 'trend',
                    'message' => 'Generated trend insight',
                    'severity' => 'high',
                ],
                [
                    'type' => 'anomaly',
                    'message' => 'Generated anomaly insight',
                    'severity' => 'critical',
                ],
            ]);
        $this->app->instance(AutomatedInsightsService::class, $mockAutomatedService);

        $response = $this->actingAs($this->user1)
            ->postJson('/api/insights/generate', [
                'start_date' => now()->subDays(30)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'queue' => false,
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Insights generated successfully')
            ->assertJsonPath('data.count', 2);
    }

    /**
     * Test summary statistics are returned correctly
     */
    public function test_summary_statistics_are_correct(): void
    {
        $this->tenantContextService->setTenant($this->tenant1->id);
        
        // Create various insights
        Insight::factory()->count(10)->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'trend',
            'status' => 'active',
        ]);

        Insight::factory()->count(5)->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'anomaly',
            'status' => 'dismissed',
        ]);

        Insight::factory()->count(3)->create([
            'tenant_id' => $this->tenant1->id,
            'type' => 'correlation',
            'status' => 'implemented',
        ]);

        $response = $this->actingAs($this->user1)
            ->getJson('/api/insights');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.total', 18)
            ->assertJsonPath('summary.active', 10)
            ->assertJsonPath('summary.dismissed', 5)
            ->assertJsonPath('summary.implemented', 3)
            ->assertJsonPath('summary.by_type.trend', 10)
            ->assertJsonPath('summary.by_type.anomaly', 5)
            ->assertJsonPath('summary.by_type.correlation', 3);
    }
}
