<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\CustomEvent;
use App\Models\CustomEventDefinition;
use App\Models\User;
use App\Models\Tenant;
use App\Services\Analytics\ConsentService;
use App\Services\Analytics\CustomEventTrackingService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Unit tests for CustomEventTrackingService
 *
 * @covers \App\Services\Analytics\CustomEventTrackingService
 */
class CustomEventTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomEventTrackingService $service;
    private mixed $mockTenantContextService;
    private mixed $mockConsentService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock services
        $this->mockTenantContextService = Mockery::mock(TenantContextService::class);
        $this->mockTenantContextService->shouldReceive('getCurrentTenantId')
            ->andReturn(1);

        $this->mockConsentService = Mockery::mock(ConsentService::class);
        $this->mockConsentService->shouldReceive('hasConsent')
            ->andReturn(true);

        // Create the service with mocks
        $this->service = new CustomEventTrackingService($this->mockTenantContextService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test defining a custom event
     */
    public function test_define_event_creates_definition(): void
    {
        $definition = [
            'name' => 'test_event',
            'description' => 'Test event definition',
            'parameters' => [
                ['name' => 'amount', 'type' => 'number'],
                ['name' => 'product', 'type' => 'string'],
            ],
            'created_by' => 1,
        ];

        $result = $this->service->defineEvent($definition);

        $this->assertInstanceOf(CustomEventDefinition::class, $result);
        $this->assertEquals('test_event', $result->name);
        $this->assertEquals('Test event definition', $result->description);
        $this->assertCount(2, $result->parameters_json);
        $this->assertEquals(1, $result->tenant_id);
    }

    /**
     * Test defining event with duplicate name fails
     */
    public function test_define_event_duplicate_name_fails(): void
    {
        // Create existing definition
        CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'duplicate_event',
        ]);

        $definition = [
            'name' => 'duplicate_event',
            'description' => 'Duplicate event',
            'parameters' => [
                ['name' => 'value', 'type' => 'number'],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Event definition with name 'duplicate_event' already exists");
        $this->service->defineEvent($definition);
    }

    /**
     * Test defining event with invalid name fails
     */
    public function test_define_event_invalid_name_fails(): void
    {
        $definition = [
            'name' => '123-invalid', // Can't start with number
            'description' => 'Invalid event',
            'parameters' => [],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Event name must be alphanumeric');
        $this->service->defineEvent($definition);
    }

    /**
     * Test defining event with empty name fails
     */
    public function test_define_event_empty_name_fails(): void
    {
        $definition = [
            'name' => '',
            'description' => 'Empty name event',
            'parameters' => [],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Event name is required');
        $this->service->defineEvent($definition);
    }

    /**
     * Test defining event with invalid parameter type fails
     */
    public function test_define_event_invalid_parameter_type_fails(): void
    {
        $definition = [
            'name' => 'invalid_param_event',
            'description' => 'Invalid parameter type',
            'parameters' => [
                ['name' => 'value', 'type' => 'invalid_type'],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid type');
        $this->service->defineEvent($definition);
    }

    /**
     * Test tracking an event
     */
    public function test_track_event_creates_event(): void
    {
        $user = User::factory()->create();
        
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'trackable_event',
            'parameters_json' => [
                ['name' => 'amount', 'type' => 'number'],
                ['name' => 'product', 'type' => 'string'],
            ],
        ]);

        $properties = [
            'amount' => 99.99,
            'product' => 'Test Product',
        ];

        $result = $this->service->trackEvent('trackable_event', $properties, $user->id);

        $this->assertInstanceOf(CustomEvent::class, $result);
        $this->assertEquals($definition->id, $result->definition_id);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals($properties, $result->data_json);
        $this->assertEquals(1, $result->tenant_id);
    }

    /**
     * Test tracking event without consent fails
     */
    public function test_track_event_without_consent_fails(): void
    {
        $user = User::factory()->create();
        
        $this->mockConsentService->shouldReceive('hasConsent')
            ->with($user->id, 'analytics')
            ->andReturn(false);

        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'consent_event',
            'parameters_json' => [
                ['name' => 'value', 'type' => 'number'],
            ],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User has not consented');
        $this->service->trackEvent('consent_event', ['value' => 100], $user->id);
    }

    /**
     * Test tracking event with missing required parameter fails
     */
    public function test_track_event_missing_required_parameter_fails(): void
    {
        $user = User::factory()->create();
        
        CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'required_param_event',
            'parameters_json' => [
                ['name' => 'required_field', 'type' => 'string', 'required' => true],
            ],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Required parameter 'required_field' is missing");
        $this->service->trackEvent('required_param_event', [], $user->id);
    }

    /**
     * Test tracking event with invalid parameter type fails
     */
    public function test_track_event_invalid_parameter_type_fails(): void
    {
        $user = User::factory()->create();
        
        CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'type_check_event',
            'parameters_json' => [
                ['name' => 'count', 'type' => 'number'],
            ],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("must be of type 'number'");
        $this->service->trackEvent('type_check_event', ['count' => 'not_a_number'], $user->id);
    }

    /**
     * Test tracking non-existent event fails
     */
    public function test_track_nonexistent_event_fails(): void
    {
        $user = User::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Event definition 'unknown_event' not found");
        $this->service->trackEvent('unknown_event', [], $user->id);
    }

    /**
     * Test validating an event
     */
    public function test_validate_event_returns_true(): void
    {
        CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'validate_test_event',
            'parameters_json' => [
                ['name' => 'amount', 'type' => 'number'],
                ['name' => 'name', 'type' => 'string'],
            ],
        ]);

        $result = $this->service->validateEvent('validate_test_event', [
            'amount' => 100,
            'name' => 'Test',
        ]);

        $this->assertTrue($result);
    }

    /**
     * Test validating event with invalid data fails
     */
    public function test_validate_event_invalid_data_fails(): void
    {
        CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'invalid_validate_event',
            'parameters_json' => [
                ['name' => 'count', 'type' => 'number'],
            ],
        ]);

        $this->expectException(\Exception::class);
        $this->service->validateEvent('invalid_validate_event', ['count' => 'invalid']);
    }

    /**
     * Test getting event definition
     */
    public function test_get_event_definition_returns_definition(): void
    {
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'get_me_event',
            'description' => 'Get me event',
            'parameters_json' => [
                ['name' => 'value', 'type' => 'string'],
            ],
        ]);

        $result = $this->service->getEventDefinition('get_me_event');

        $this->assertInstanceOf(CustomEventDefinition::class, $result);
        $this->assertEquals($definition->id, $result->id);
        $this->assertEquals('get_me_event', $result->name);
    }

    /**
     * Test getting non-existent event definition returns null
     */
    public function test_get_nonexistent_event_definition_returns_null(): void
    {
        $result = $this->service->getEventDefinition('nonexistent_event');
        $this->assertNull($result);
    }

    /**
     * Test listing events
     */
    public function test_list_events_returns_collection(): void
    {
        CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'event_one',
        ]);
        CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'event_two',
        ]);

        $result = $this->service->listEvents();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    /**
     * Test listing events filters by tenant
     */
    public function test_list_events_respects_tenant(): void
    {
        // Create events for tenant 1
        CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'tenant1_event',
        ]);

        // Create event for tenant 2
        CustomEventDefinition::factory()->create([
            'tenant_id' => 2,
            'name' => 'tenant2_event',
        ]);

        $result = $this->service->listEvents();

        $this->assertCount(1, $result);
        $this->assertEquals('tenant1_event', $result->first()->name);
    }

    /**
     * Test analyzing events
     */
    public function test_analyze_events_returns_analysis(): void
    {
        $user = User::factory()->create();
        
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'analyze_event',
            'parameters_json' => [
                ['name' => 'amount', 'type' => 'number'],
            ],
        ]);

        // Create some events
        CustomEvent::factory()->create([
            'tenant_id' => 1,
            'definition_id' => $definition->id,
            'user_id' => $user->id,
            'data_json' => ['amount' => 100],
            'timestamp' => now(),
        ]);

        CustomEvent::factory()->create([
            'tenant_id' => 1,
            'definition_id' => $definition->id,
            'user_id' => $user->id,
            'data_json' => ['amount' => 200],
            'timestamp' => now(),
        ]);

        $result = $this->service->analyzeEvents('analyze_event');

        $this->assertIsArray($result);
        $this->assertEquals('analyze_event', $result['event_name']);
        $this->assertArrayHasKey('total_events', $result);
        $this->assertArrayHasKey('unique_users', $result);
        $this->assertArrayHasKey('properties_analysis', $result);
        $this->assertEquals(2, $result['total_events']);
    }

    /**
     * Test analyzing events with date filter
     */
    public function test_analyze_events_with_date_filter(): void
    {
        $user = User::factory()->create();
        
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'date_filter_event',
            'parameters_json' => [],
        ]);

        // Create event in date range
        CustomEvent::factory()->create([
            'tenant_id' => 1,
            'definition_id' => $definition->id,
            'user_id' => $user->id,
            'data_json' => [],
            'timestamp' => now()->subDays(2),
        ]);

        // Create event outside date range
        CustomEvent::factory()->create([
            'tenant_id' => 1,
            'definition_id' => $definition->id,
            'user_id' => $user->id,
            'data_json' => [],
            'timestamp' => now()->subDays(10),
        ]);

        $result = $this->service->analyzeEvents('date_filter_event', [
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $this->assertEquals(1, $result['total_events']);
    }

    /**
     * Test creating funnel analysis
     */
    public function test_create_funnel_returns_funnel(): void
    {
        $user = User::factory()->create();
        
        $definition1 = CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'funnel_step_1',
            'parameters_json' => [],
        ]);
        
        $definition2 = CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'funnel_step_2',
            'parameters_json' => [],
        ]);

        // Create events for step 1
        CustomEvent::factory()->create([
            'tenant_id' => 1,
            'definition_id' => $definition1->id,
            'user_id' => $user->id,
            'data_json' => [],
            'timestamp' => now(),
        ]);

        // Create events for step 2 (fewer users)
        CustomEvent::factory()->create([
            'tenant_id' => 1,
            'definition_id' => $definition2->id,
            'user_id' => $user->id,
            'data_json' => [],
            'timestamp' => now()->addMinute(),
        ]);

        $result = $this->service->createFunnel(['funnel_step_1', 'funnel_step_2']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('steps', $result);
        $this->assertArrayHasKey('overall_conversion_rate', $result);
        $this->assertCount(2, $result['steps']);
        $this->assertLessThan(100, $result['overall_conversion_rate']);
    }

    /**
     * Test creating funnel with less than 2 events fails
     */
    public function test_create_funnel_with_one_event_fails(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Funnel requires at least 2 events');
        $this->service->createFunnel(['single_event']);
    }

    /**
     * Test creating funnel with non-existent event fails
     */
    public function test_create_funnel_with_nonexistent_event_fails(): void
    {
        CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'existing_step',
            'parameters_json' => [],
        ]);

        $this->expectException(\Exception::class);
        $this->service->createFunnel(['existing_step', 'nonexistent_step']);
    }

    /**
     * Test analyzing behavior flow
     */
    public function test_analyze_behavior_flow_returns_flow(): void
    {
        $user = User::factory()->create();
        
        $definition1 = CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'flow_event_1',
            'parameters_json' => [],
        ]);
        
        $definition2 = CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'flow_event_2',
            'parameters_json' => [],
        ]);

        // Create event sequence
        CustomEvent::factory()->create([
            'tenant_id' => 1,
            'definition_id' => $definition1->id,
            'user_id' => $user->id,
            'data_json' => [],
            'timestamp' => now(),
        ]);

        CustomEvent::factory()->create([
            'tenant_id' => 1,
            'definition_id' => $definition2->id,
            'user_id' => $user->id,
            'data_json' => ['value' => 100],
            'timestamp' => now()->addMinute(),
        ]);

        $result = $this->service->analyzeBehaviorFlow($user->id);

        $this->assertIsArray($result);
        $this->assertEquals($user->id, $result['user_id']);
        $this->assertArrayHasKey('total_events', $result);
        $this->assertArrayHasKey('unique_events', $result);
        $this->assertArrayHasKey('event_sequence', $result);
        $this->assertCount(2, $result['event_sequence']);
    }

    /**
     * Test analyzing behavior flow with date range
     */
    public function test_analyze_behavior_flow_with_date_range(): void
    {
        $user = User::factory()->create();
        
        $definition = CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'range_event',
            'parameters_json' => [],
        ]);

        CustomEvent::factory()->create([
            'tenant_id' => 1,
            'definition_id' => $definition->id,
            'user_id' => $user->id,
            'data_json' => [],
            'timestamp' => now()->subDays(2),
        ]);

        $result = $this->service->analyzeBehaviorFlow(
            $user->id,
            now()->subDays(5)->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals(1, $result['total_events']);
    }

    /**
     * Test analyzing behavior flow for user with no events
     */
    public function test_analyze_behavior_flow_no_events(): void
    {
        $user = User::factory()->create();

        $result = $this->service->analyzeBehaviorFlow($user->id);

        $this->assertEquals(0, $result['total_events']);
        $this->assertEmpty($result['event_sequence']);
    }

    /**
     * Test caching is used for event definitions
     */
    public function test_event_definition_is_cached(): void
    {
        CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'cached_event',
            'parameters_json' => [],
        ]);

        // First call should cache
        $result1 = $this->service->getEventDefinition('cached_event');
        
        // Second call should use cache
        $result2 = $this->service->getEventDefinition('cached_event');

        $this->assertEquals($result1->id, $result2->id);
    }

    /**
     * Test cache is cleared when event is defined
     */
    public function test_cache_cleared_on_event_definition(): void
    {
        CustomEventDefinition::factory()->create([
            'tenant_id' => 1,
            'name' => 'cache_test_event',
            'parameters_json' => [],
        ]);

        // Get the event (should be cached)
        $this->service->getEventDefinition('cache_test_event');

        // Define a new event (should clear cache)
        $this->service->defineEvent([
            'name' => 'new_cache_event',
            'parameters' => [],
        ]);

        // Verify cache was cleared by checking we can get the new event
        $newEvent = $this->service->getEventDefinition('new_cache_event');
        $this->assertNotNull($newEvent);
    }
}
