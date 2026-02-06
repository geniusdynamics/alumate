<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\CustomEventDefinition;
use App\Models\CustomEvent;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Feature tests for CustomEventController
 *
 * Tests the custom event API endpoints including CRUD operations,
 * tracking, analytics, funnel analysis, and behavior flow.
 */
class CustomEventControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a tenant
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => 'active',
        ]);

        // Create a user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'tenant_id' => $this->tenant->id,
        ]);

        // Set tenant context
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant->id);

        // Login the user
        Auth::login($this->user);
    }

    /**
     * Test listing custom event definitions
     */
    public function test_can_list_custom_event_definitions()
    {
        // Create some event definitions
        CustomEventDefinition::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/analytics/custom-events/definitions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', function ($data) {
                return count($data) === 3;
            });
    }

    /**
     * Test creating a custom event definition
     */
    public function test_can_create_custom_event_definition()
    {
        $payload = [
            'name' => 'button_click',
            'description' => 'Track button clicks',
            'parameters_json' => [
                ['name' => 'button_id', 'type' => 'string'],
                ['name' => 'page', 'type' => 'string'],
            ],
        ];

        $response = $this->postJson('/api/analytics/custom-events/definitions', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'parameters_json',
                    'status',
                ],
                'message',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'button_click')
            ->assertJsonPath('data.status', 'active');

        // Verify in database
        $this->assertDatabaseHas('custom_event_definitions', [
            'name' => 'button_click',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /**
     * Test validation when creating custom event definition
     */
    public function test_validation_error_when_creating_invalid_definition()
    {
        $payload = [
            'name' => 'invalid name with spaces', // Invalid: contains spaces
            'parameters_json' => [
                ['name' => 'button_id', 'type' => 'string'],
            ],
        ];

        $response = $this->postJson('/api/analytics/custom-events/definitions', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * Test showing a specific custom event definition
     */
    public function test_can_show_custom_event_definition()
    {
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'page_view',
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/analytics/custom-events/definitions/{$definition->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'definition' => [
                        'id',
                        'name',
                        'description',
                        'parameters_json',
                        'status',
                    ],
                    'aggregates',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.definition.name', 'page_view');
    }

    /**
     * Test updating a custom event definition
     */
    public function test_can_update_custom_event_definition()
    {
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'old_name',
            'description' => 'Old description',
            'status' => 'active',
        ]);

        $payload = [
            'name' => 'new_name',
            'description' => 'Updated description',
            'status' => 'inactive',
        ];

        $response = $this->putJson("/api/analytics/custom-events/definitions/{$definition->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'new_name')
            ->assertJsonPath('data.description', 'Updated description')
            ->assertJsonPath('data.status', 'inactive');

        // Verify in database
        $this->assertDatabaseHas('custom_event_definitions', [
            'id' => $definition->id,
            'name' => 'new_name',
            'status' => 'inactive',
        ]);
    }

    /**
     * Test deleting a custom event definition
     */
    public function test_can_delete_custom_event_definition()
    {
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'to_delete',
            'status' => 'active',
        ]);

        $response = $this->deleteJson("/api/analytics/custom-events/definitions/{$definition->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Custom event definition deleted successfully');

        // Verify soft deleted
        $this->assertSoftDeleted('custom_event_definitions', [
            'id' => $definition->id,
        ]);
    }

    /**
     * Test tracking a custom event
     */
    public function test_can_track_custom_event()
    {
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'test_event',
            'parameters_json' => [
                ['name' => 'value', 'type' => 'number'],
            ],
            'status' => 'active',
        ]);

        $payload = [
            'definition_id' => $definition->id,
            'user_id' => $this->user->id,
            'data_json' => [
                'value' => 42,
            ],
        ];

        $response = $this->postJson('/api/analytics/custom-events/track', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Custom event tracked successfully');

        // Verify in database
        $this->assertDatabaseHas('custom_events', [
            'definition_id' => $definition->id,
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /**
     * Test getting analytics for an event definition
     */
    public function test_can_get_event_analytics()
    {
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'analytics_test',
            'parameters_json' => [
                ['name' => 'score', 'type' => 'number'],
            ],
            'status' => 'active',
        ]);

        // Create some events
        CustomEvent::factory()->count(5)->create([
            'definition_id' => $definition->id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'data_json' => ['score' => 10],
        ]);

        $response = $this->getJson("/api/analytics/custom-events/{$definition->id}/analytics");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'total_events',
                    'unique_users',
                    'aggregates',
                    'time_series',
                ],
            ]);
    }

    /**
     * Test getting behavior flow for an event definition
     */
    public function test_can_get_behavior_flow()
    {
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'flow_test',
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/analytics/custom-events/{$definition->id}/behavior-flow");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * Test getting behavior flow for a specific user
     */
    public function test_can_get_user_behavior_flow()
    {
        // Create some events for the user
        CustomEvent::factory()->count(3)->create([
            'definition_id' => CustomEventDefinition::factory()->create([
                'tenant_id' => $this->tenant->id,
                'status' => 'active',
            ])->id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'data_json' => [],
        ]);

        $response = $this->getJson("/api/analytics/custom-events/user/{$this->user->id}/behavior-flow");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'user_id',
                    'total_events',
                    'unique_events',
                    'event_sequence',
                ],
            ]);
    }

    /**
     * Test creating funnel analysis
     */
    public function test_can_create_funnel_analysis()
    {
        // Create event definitions for funnel
        $event1 = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'funnel_step_1',
            'status' => 'active',
        ]);
        $event2 = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'funnel_step_2',
            'status' => 'active',
        ]);

        $payload = [
            'event_sequence' => [$event1->id, $event2->id],
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->toDateString(),
        ];

        $response = $this->postJson('/api/analytics/custom-events/funnel', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'steps',
                    'overall_conversion_rate',
                    'total_steps',
                    'created_at',
                ],
            ]);
    }

    /**
     * Test getting optimization suggestions
     */
    public function test_can_get_optimization_suggestions()
    {
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'optimize_test',
            'status' => 'active',
        ]);

        // Create some events
        CustomEvent::factory()->count(10)->create([
            'definition_id' => $definition->id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'data_json' => ['value' => 'test'],
        ]);

        $response = $this->getJson("/api/analytics/custom-events/{$definition->id}/optimization-suggestions");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'definition_id',
                    'suggestions',
                    'generated_at',
                ],
            ]);
    }

    /**
     * Test tenant isolation - events are isolated between tenants
     */
    public function test_events_are_isolated_between_tenants()
    {
        // Create an event for tenant 1
        $event1 = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'tenant1_event',
            'status' => 'active',
        ]);

        // Create a second tenant
        $tenant2 = Tenant::factory()->create([
            'name' => 'Tenant 2',
            'slug' => 'tenant-2',
            'status' => 'active',
        ]);

        $user2 = User::factory()->create([
            'email' => 'user2@example.com',
            'tenant_id' => $tenant2->id,
        ]);

        // Switch to tenant 2
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($tenant2->id);
        Auth::login($user2);

        // Create event for tenant 2
        $event2 = CustomEventDefinition::factory()->create([
            'tenant_id' => $tenant2->id,
            'name' => 'tenant2_event',
            'status' => 'active',
        ]);

        // Verify tenant 1 sees only its events
        $tenantContextService->setTenant($this->tenant->id);
        $response1 = $this->getJson('/api/analytics/custom-events/definitions');
        $this->assertCount(1, $response1->json('data'));
        $this->assertEquals('tenant1_event', $response1->json('data.0.name'));

        // Verify tenant 2 sees only its events
        $tenantContextService->setTenant($tenant2->id);
        $response2 = $this->getJson('/api/analytics/custom-events/definitions');
        $this->assertCount(1, $response2->json('data'));
        $this->assertEquals('tenant2_event', $response2->json('data.0.name'));
    }

    /**
     * Test that unauthenticated requests are rejected
     */
    public function test_unauthenticated_requests_are_rejected()
    {
        Auth::logout();

        $response = $this->getJson('/api/analytics/custom-events/definitions');

        $response->assertStatus(401);
    }

    /**
     * Test pagination parameters
     */
    public function test_pagination_parameters_work()
    {
        // Create 10 event definitions
        CustomEventDefinition::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'active',
        ]);

        // Request page 1 with 5 items per page
        $response = $this->getJson('/api/analytics/custom-events/definitions?page=1&per_page=5');

        $response->assertStatus(200)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.per_page', 5)
            ->assertJsonPath('pagination.total', 10)
            ->assertJsonPath('pagination.last_page', 2)
            ->assertJsonPath('data', function ($data) {
                return count($data) === 5;
            });
    }

    /**
     * Test that inactive tracking event definition fails
     */
    public function test_cannot_track_inactive_event_definition()
    {
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'inactive_event',
            'status' => 'inactive',
        ]);

        $payload = [
            'definition_id' => $definition->id,
            'user_id' => $this->user->id,
            'data_json' => ['value' => 'test'],
        ];

        $response = $this->postJson('/api/analytics/custom-events/track', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * Test funnel requires at least 2 events
     */
    public function test_funnel_requires_minimum_two_events()
    {
        $event = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'single_event',
            'status' => 'active',
        ]);

        $payload = [
            'event_sequence' => [$event->id], // Only 1 event
        ];

        $response = $this->postJson('/api/analytics/custom-events/funnel', $payload);

        $response->assertStatus(422);
    }
}
