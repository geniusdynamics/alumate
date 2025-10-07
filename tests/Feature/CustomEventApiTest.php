<?php

namespace Tests\Feature;

use App\Models\CustomEvent;
use App\Models\CustomEventDefinition;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CustomEventApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up tenant and user
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        
        // Associate user with tenant
        $this->user->tenants()->attach($this->tenant->id);
    }

    public function test_get_custom_event_definitions_list()
    {
        // Create some custom event definitions
        $definition1 = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id
        ]);
        
        $definition2 = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'page_view',
            'created_by' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/analytics/custom-events/definitions");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'id' => $definition1->id,
                        'name' => $definition1->name,
                        'description' => $definition1->description,
                    ],
                    [
                        'id' => $definition2->id,
                        'name' => $definition2->name,
                        'description' => $definition2->description,
                    ]
                ]
            ]);
    }

    public function test_create_custom_event_definition()
    {
        $data = [
            'name' => 'purchase',
            'description' => 'Purchase event',
            'parameters_json' => [
                ['name' => 'amount', 'type' => 'number'],
                ['name' => 'product', 'type' => 'string']
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/analytics/custom-events/definitions", $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'purchase',
                    'description' => 'Purchase event',
                ]
            ]);

        $this->assertDatabaseHas('custom_event_definitions', [
            'tenant_id' => $this->tenant->id,
            'name' => 'purchase',
            'description' => 'Purchase event',
        ]);
    }

    public function test_create_custom_event_definition_fails_with_duplicate_name()
    {
        // Create first definition
        CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'purchase',
            'created_by' => $this->user->id
        ]);

        $data = [
            'name' => 'purchase', // Duplicate name
            'description' => 'Another purchase event',
            'parameters_json' => [
                ['name' => 'amount', 'type' => 'number']
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/analytics/custom-events/definitions", $data);

        $response->assertStatus(42)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'name' => ['The name has already been taken.']
                ]
            ]);
    }

    public function test_track_custom_event()
    {
        // Create a custom event definition
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parameters_json' => json_encode([
                ['name' => 'amount', 'type' => 'number'],
                ['name' => 'product', 'type' => 'string']
            ]),
            'created_by' => $this->user->id
        ]);

        $data = [
            'definition_id' => $definition->id,
            'user_id' => $this->user->id,
            'data_json' => [
                'amount' => 99.99,
                'product' => 'widget'
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/analytics/custom-events/track", $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Event tracked successfully'
            ]);

        $this->assertDatabaseHas('custom_events', [
            'tenant_id' => $this->tenant->id,
            'definition_id' => $definition->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_track_custom_event_fails_with_invalid_data()
    {
        // Create a custom event definition
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parameters_json' => json_encode([
                ['name' => 'amount', 'type' => 'number']
            ]),
            'created_by' => $this->user->id
        ]);

        $data = [
            'definition_id' => $definition->id,
            'user_id' => $this->user->id,
            'data_json' => [
                'invalid_param' => 'value' // Parameter not defined in definition
            ]
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/analytics/custom-events/track", $data);

        $response->assertStatus(42)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => [
                    'data_json' => ['Invalid parameter: invalid_param']
                ]
            ]);
    }

    public function test_get_custom_event_analytics()
    {
        // Create a custom event definition
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parameters_json' => json_encode([
                ['name' => 'amount', 'type' => 'number']
            ]),
            'created_by' => $this->user->id
        ]);

        // Create some custom events
        CustomEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'definition_id' => $definition->id,
            'user_id' => $this->user->id,
            'data_json' => ['amount' => 100]
        ]);
        
        CustomEvent::factory()->create([
            'tenant_id' => $this->tenant->id,
            'definition_id' => $definition->id,
            'user_id' => $this->user->id,
            'data_json' => ['amount' => 200]
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/analytics/custom-events/{$definition->id}/analytics");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_events' => 2,
                    'unique_users' => 1,
                ]
            ]);
    }

    public function test_tenant_isolation_for_custom_events()
    {
        // Create a second tenant and user
        $tenant2 = Tenant::factory()->create();
        $user2 = User::factory()->create();
        $user2->tenants()->attach($tenant2->id);

        // Create a custom event definition in first tenant
        $definition1 = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'tenant1_event',
            'created_by' => $this->user->id
        ]);

        // Create a custom event definition in second tenant
        $definition2 = CustomEventDefinition::factory()->create([
            'tenant_id' => $tenant2->id,
            'name' => 'tenant2_event',
            'created_by' => $user2->id
        ]);

        // User from first tenant should only see their own definition
        $response = $this->actingAs($this->user)
            ->getJson("/api/analytics/custom-events/definitions");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'id' => $definition1->id,
                        'name' => 'tenant1_event',
                    ]
                ]
            ]);

        // Verify the response doesn't contain tenant2's definition
        $response->assertJsonMissing([
            'name' => 'tenant2_event'
        ]);
    }

    public function test_api_requires_authentication()
    {
        $response = $this->getJson("/api/analytics/custom-events/definitions");
        $response->assertStatus(401);

        $response = $this->postJson("/api/analytics/custom-events/definitions", []);
        $response->assertStatus(401);

        $response = $this->postJson("/api/analytics/custom-events/track", []);
        $response->assertStatus(401);

        $response = $this->getJson("/api/analytics/custom-events/1/analytics");
        $response->assertStatus(401);
    }

    public function test_api_rate_limiting()
    {
        // Configure rate limiter for testing
        Config::set('api.throttle.custom-event', '300,1'); // 300 requests per minute

        // Create a definition
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id
        ]);

        // Make multiple requests within the rate limit
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($this->user)
                ->getJson("/api/analytics/custom-events/{$definition->id}/analytics");
            $response->assertStatus(200);
        }
    }
}