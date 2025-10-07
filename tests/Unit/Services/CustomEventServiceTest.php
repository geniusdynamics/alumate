<?php

namespace Tests\Unit\Services;

use App\Models\CustomEvent;
use App\Models\CustomEventDefinition;
use App\Models\User;
use App\Services\Analytics\CustomEventService;
use App\Services\ConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class CustomEventServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $customEventService;
    protected $mockConsentService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockConsentService = Mockery::mock(ConsentService::class);
        $this->customEventService = new CustomEventService($this->mockConsentService);
    }

    public function test_define_event_creates_definition()
    {
        $tenantId = 1;
        $data = [
            'name' => 'purchase',
            'description' => 'Purchase event',
            'parameters_json' => [
                ['name' => 'amount', 'type' => 'number'],
                ['name' => 'product', 'type' => 'string']
            ],
            'created_by' => 1
        ];

        $result = $this->customEventService->defineEvent($tenantId, $data);

        $this->assertInstanceOf(CustomEventDefinition::class, $result);
        $this->assertEquals('purchase', $result->name);
        $this->assertEquals('Purchase event', $result->description);
        $this->assertCount(2, $result->parameters_json);
        $this->assertEquals($tenantId, $result->tenant_id);
    }

    public function test_define_event_with_duplicate_name_fails()
    {
        $tenantId = 1;
        
        // Create first definition
        CustomEventDefinition::factory()->create([
            'tenant_id' => $tenantId,
            'name' => 'purchase'
        ]);

        $data = [
            'name' => 'purchase', // Duplicate name
            'description' => 'Another purchase event',
            'parameters_json' => [
                ['name' => 'amount', 'type' => 'number']
            ],
            'created_by' => 1
        ];

        $this->expectException(\Exception::class);
        $this->customEventService->defineEvent($tenantId, $data);
    }

    public function test_track_event_stores_event()
    {
        $tenantId = 1;
        $user = User::factory()->create();
        
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $tenantId,
            'parameters_json' => json_encode([
                ['name' => 'amount', 'type' => 'number'],
                ['name' => 'product', 'type' => 'string']
            ])
        ]);

        $data = [
            'definition_id' => $definition->id,
            'user_id' => $user->id,
            'data_json' => [
                'amount' => 99.99,
                'product' => 'widget'
            ]
        ];

        // Mock consent service to return true
        $this->mockConsentService
            ->shouldReceive('checkConsent')
            ->with($user->id, 'analytics')
            ->andReturn(true);

        $result = $this->customEventService->trackEvent($tenantId, $data);

        $this->assertInstanceOf(CustomEvent::class, $result);
        $this->assertEquals($definition->id, $result->definition_id);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals(['amount' => 99.99, 'product' => 'widget'], $result->data_json);
        $this->assertEquals($tenantId, $result->tenant_id);
    }

    public function test_track_event_fails_without_consent()
    {
        $tenantId = 1;
        $user = User::factory()->create();
        
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $tenantId,
            'parameters_json' => json_encode([
                ['name' => 'amount', 'type' => 'number']
            ])
        ]);

        $data = [
            'definition_id' => $definition->id,
            'user_id' => $user->id,
            'data_json' => [
                'amount' => 99.99
            ]
        ];

        // Mock consent service to return false
        $this->mockConsentService
            ->shouldReceive('checkConsent')
            ->with($user->id, 'analytics')
            ->andReturn(false);

        $this->expectException(\Exception::class);
        $this->customEventService->trackEvent($tenantId, $data);
    }

    public function test_track_event_fails_with_invalid_data()
    {
        $tenantId = 1;
        $user = User::factory()->create();
        
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $tenantId,
            'parameters_json' => json_encode([
                ['name' => 'amount', 'type' => 'number']
            ])
        ]);

        $data = [
            'definition_id' => $definition->id,
            'user_id' => $user->id,
            'data_json' => [
                'invalid_param' => 'value' // Parameter not defined in definition
            ]
        ];

        // Mock consent service to return true
        $this->mockConsentService
            ->shouldReceive('checkConsent')
            ->with($user->id, 'analytics')
            ->andReturn(true);

        $this->expectException(\Exception::class);
        $this->customEventService->trackEvent($tenantId, $data);
    }

    public function test_aggregate_events_returns_metrics()
    {
        $tenantId = 1;
        $user = User::factory()->create();
        
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $tenantId,
            'parameters_json' => json_encode([
                ['name' => 'amount', 'type' => 'number']
            ])
        ]);

        // Create multiple events for aggregation
        CustomEvent::factory()->create([
            'tenant_id' => $tenantId,
            'definition_id' => $definition->id,
            'user_id' => $user->id,
            'data_json' => ['amount' => 100]
        ]);
        
        CustomEvent::factory()->create([
            'tenant_id' => $tenantId,
            'definition_id' => $definition->id,
            'user_id' => $user->id,
            'data_json' => ['amount' => 200]
        ]);

        $result = $this->customEventService->aggregateEvents($definition->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_events', $result);
        $this->assertArrayHasKey('unique_users', $result);
        $this->assertArrayHasKey('aggregates', $result);
        $this->assertEquals(2, $result['total_events']);
        $this->assertEquals(1, $result['unique_users']);
    }

    public function test_aggregate_events_with_period_filter()
    {
        $tenantId = 1;
        $user = User::factory()->create();
        
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $tenantId,
            'parameters_json' => json_encode([
                ['name' => 'amount', 'type' => 'number']
            ])
        ]);

        // Create events with different timestamps
        CustomEvent::factory()->create([
            'tenant_id' => $tenantId,
            'definition_id' => $definition->id,
            'user_id' => $user->id,
            'data_json' => ['amount' => 100],
            'timestamp' => now()->subDays(10)
        ]);
        
        CustomEvent::factory()->create([
            'tenant_id' => $tenantId,
            'definition_id' => $definition->id,
            'user_id' => $user->id,
            'data_json' => ['amount' => 200],
            'timestamp' => now()->subDays(2)
        ]);

        $result = $this->customEventService->aggregateEvents(
            $definition->id,
            ['start_date' => now()->subDays(5)->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]
        );

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total_events']); // Only the recent event should be counted
    }

    public function test_aggregate_events_with_user_filter()
    {
        $tenantId = 1;
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => $tenantId,
            'parameters_json' => json_encode([
                ['name' => 'amount', 'type' => 'number']
            ])
        ]);

        // Create events for different users
        CustomEvent::factory()->create([
            'tenant_id' => $tenantId,
            'definition_id' => $definition->id,
            'user_id' => $user1->id,
            'data_json' => ['amount' => 100]
        ]);
        
        CustomEvent::factory()->create([
            'tenant_id' => $tenantId,
            'definition_id' => $definition->id,
            'user_id' => $user2->id,
            'data_json' => ['amount' => 200]
        ]);

        $result = $this->customEventService->aggregateEvents($definition->id, [], $user1->id);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['total_events']); // Only user1's event should be counted
        $this->assertEquals(1, $result['unique_users']);
    }
}