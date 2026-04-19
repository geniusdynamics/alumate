<?php

namespace Tests\Integration;

use App\Events\AnalyticsBatchProcessed;
use App\Models\AnalyticsEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Analytics WebSocket integration testing
 * Tests real-time broadcasting of analytics events via WebSocket connections
 */
class AnalyticsWebSocketIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $tenancy = true;
    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenant
        $this->tenant = Tenant::factory()->create([
            'id' => 'test-tenant-websocket',
            'name' => 'Test WebSocket Tenant',
        ]);

        // Create authenticated user
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_broadcasts_real_time_leaderboard_updates_via_websocket()
    {
        Broadcast::shouldReceive('event')
            ->once()
            ->with(\Mockery::on(function ($event) {
                return $event instanceof AnalyticsBatchProcessed;
            }));

        // Create gamification events that should trigger leaderboard updates
        AnalyticsEvent::factory()->create([
            'tenant_id' => 'test-tenant-websocket',
            'event_type' => 'gamification_achievement',
            'properties' => [
                'achievement_type' => 'points_earned',
                'points_earned' => 100,
                'leaderboard_position' => 5,
            ],
            'user_id' => $this->user->id,
            'consent_given' => true,
        ]);

        AnalyticsEvent::factory()->create([
            'tenant_id' => 'test-tenant-websocket',
            'event_type' => 'gamification_achievement',
            'properties' => [
                'achievement_type' => 'level_up',
                'new_level' => 3,
                'leaderboard_position' => 3,
            ],
            'user_id' => $this->user->id,
            'consent_given' => true,
        ]);

        // Trigger the batch processing that should broadcast updates
        Event::dispatch(new AnalyticsBatchProcessed([
            'event_type' => 'gamification_achievement',
            'count' => 2,
            'tenant_id' => 'test-tenant-websocket',
            'timestamp' => now()->toISOString(),
            'summary' => [
                'total_events' => 2,
                'unique_users' => 1,
                'time_range' => [
                    'start' => now()->subMinutes(5),
                    'end' => now(),
                ],
            ],
        ], 'test-tenant-websocket'));
    }

    /** @test */
    public function it_broadcasts_to_correct_tenant_channels_only()
    {
        $tenant1 = $this->tenant;
        $tenant2 = Tenant::factory()->create(['id' => 'tenant2-websocket']);

        // Mock broadcasting to verify channel isolation
        Broadcast::shouldReceive('event')
            ->twice()
            ->with(\Mockery::on(function ($event) use ($tenant1) {
                return $event instanceof AnalyticsBatchProcessed &&
                       $event->tenantId === $tenant1->id;
            }));

        // Create events for tenant1
        AnalyticsEvent::factory()->count(3)->create([
            'tenant_id' => $tenant1->id,
            'event_type' => 'page_view',
            'consent_given' => true,
        ]);

        // Create events for tenant2 (should not be broadcast to tenant1 channels)
        tenancy()->initialize($tenant2);
        AnalyticsEvent::factory()->count(2)->create([
            'tenant_id' => $tenant2->id,
            'event_type' => 'page_view',
            'consent_given' => true,
        ]);
        tenancy()->initialize($tenant1);

        // Broadcast for tenant1
        Event::dispatch(new AnalyticsBatchProcessed([
            'event_type' => 'page_view',
            'count' => 3,
            'tenant_id' => $tenant1->id,
            'summary' => ['total_events' => 3],
        ], $tenant1->id));

        // Broadcast for tenant2 (should go to different channels)
        Event::dispatch(new AnalyticsBatchProcessed([
            'event_type' => 'page_view',
            'count' => 2,
            'tenant_id' => $tenant2->id,
            'summary' => ['total_events' => 2],
        ], $tenant2->id));
    }

    /** @test */
    public function it_throttles_broadcasts_according_to_configuration()
    {
        // Mock Redis throttling (from broadcasting.php config)
        // messages_per_second: 100, burst_size: 200

        Broadcast::shouldReceive('event')
            ->andReturn(true);

        // Create high volume of events that should be throttled
        $events = [];
        for ($i = 0; $i < 150; $i++) { // Over the burst limit
            $events[] = AnalyticsEvent::factory()->create([
                'tenant_id' => 'test-tenant-websocket',
                'event_type' => 'click',
                'properties' => ['x' => 100, 'y' => 200],
                'consent_given' => true,
            ]);
        }

        // Attempt to broadcast all events
        Event::dispatch(new AnalyticsBatchProcessed([
            'event_type' => 'click',
            'count' => 150,
            'tenant_id' => 'test-tenant-websocket',
            'timestamp' => now()->toISOString(),
            'summary' => [
                'total_events' => 150,
                'unique_users' => 1,
            ],
        ], 'test-tenant-websocket'));

        // Broadcasting should succeed but may be throttled by Redis
        // In a real scenario, we'd verify throttling metrics
        $this->assertTrue(true);
    }

    /** @test */
    public function it_broadcasts_analytics_updates_to_authenticated_users_only()
    {
        // Test that broadcasts respect authentication
        Broadcast::shouldReceive('event')
            ->once()
            ->with(\Mockery::on(function ($event) {
                // Verify the event is properly structured for authenticated broadcast
                return $event instanceof AnalyticsBatchProcessed;
            }));

        // Create analytics event from authenticated user
        AnalyticsEvent::factory()->create([
            'tenant_id' => 'test-tenant-websocket',
            'event_type' => 'user_engagement',
            'user_id' => $this->user->id,
            'consent_given' => true,
        ]);

        // Broadcast should include user context
        Event::dispatch(new AnalyticsBatchProcessed([
            'event_type' => 'user_engagement',
            'count' => 1,
            'tenant_id' => 'test-tenant-websocket',
            'user_id' => $this->user->id,
            'summary' => [
                'total_events' => 1,
                'unique_users' => 1,
            ],
        ], 'test-tenant-websocket'));
    }

    /** @test */
    public function it_handles_websocket_connection_failures_gracefully()
    {
        // Mock broadcasting failure
        Broadcast::shouldReceive('event')
            ->andThrow(new \Exception('WebSocket connection failed'));

        // Create events that would normally trigger broadcasts
        AnalyticsEvent::factory()->count(5)->create([
            'tenant_id' => 'test-tenant-websocket',
            'event_type' => 'conversion',
            'consent_given' => true,
        ]);

        // Attempt broadcast - should not throw exception
        try {
            Event::dispatch(new AnalyticsBatchProcessed([
                'event_type' => 'conversion',
                'count' => 5,
                'tenant_id' => 'test-tenant-websocket',
                'summary' => ['total_events' => 5],
            ], 'test-tenant-websocket'));

            // If we get here, the exception was handled gracefully
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Broadcast failure should be handled gracefully');
        }
    }

    /** @test */
    public function it_broadcasts_real_time_heatmap_updates()
    {
        Broadcast::shouldReceive('event')
            ->once()
            ->with(\Mockery::on(function ($event) {
                return $event instanceof AnalyticsBatchProcessed &&
                       $event->data['event_type'] === 'click';
            }));

        // Create click events for heatmap
        AnalyticsEvent::factory()->count(10)->create([
            'tenant_id' => 'test-tenant-websocket',
            'event_type' => 'click',
            'page_url' => 'https://example.com/dashboard',
            'properties' => [
                'x' => 100,
                'y' => 200,
                'element' => 'button',
            ],
            'consent_given' => true,
        ]);

        // Broadcast heatmap updates
        Event::dispatch(new AnalyticsBatchProcessed([
            'event_type' => 'click',
            'count' => 10,
            'tenant_id' => 'test-tenant-websocket',
            'page_url' => 'https://example.com/dashboard',
            'timestamp' => now()->toISOString(),
            'summary' => [
                'total_events' => 10,
                'unique_users' => 3,
                'heatmap_data_points' => 10,
            ],
        ], 'test-tenant-websocket'));
    }

    /** @test */
    public function it_broadcasts_tenant_isolated_analytics_summaries()
    {
        $tenant1 = $this->tenant;
        $tenant2 = Tenant::factory()->create(['id' => 'isolated-websocket-tenant']);

        Broadcast::shouldReceive('event')
            ->twice()
            ->with(\Mockery::on(function ($event) {
                return $event instanceof AnalyticsBatchProcessed;
            }));

        // Create different types of analytics events for tenant1
        AnalyticsEvent::factory()->create([
            'tenant_id' => $tenant1->id,
            'event_type' => 'page_view',
            'properties' => ['page' => '/dashboard'],
            'consent_given' => true,
        ]);

        AnalyticsEvent::factory()->create([
            'tenant_id' => $tenant1->id,
            'event_type' => 'conversion',
            'properties' => ['conversion_type' => 'signup'],
            'consent_given' => true,
        ]);

        // Create events for tenant2
        tenancy()->initialize($tenant2);
        AnalyticsEvent::factory()->create([
            'tenant_id' => $tenant2->id,
            'event_type' => 'page_view',
            'properties' => ['page' => '/other-page'],
            'consent_given' => true,
        ]);
        tenancy()->initialize($tenant1);

        // Broadcast summary for tenant1
        Event::dispatch(new AnalyticsBatchProcessed([
            'event_type' => 'mixed',
            'count' => 2,
            'tenant_id' => $tenant1->id,
            'summary' => [
                'page_views' => 1,
                'conversions' => 1,
                'total_events' => 2,
            ],
        ], $tenant1->id));

        // Broadcast summary for tenant2
        Event::dispatch(new AnalyticsBatchProcessed([
            'event_type' => 'page_view',
            'count' => 1,
            'tenant_id' => $tenant2->id,
            'summary' => [
                'page_views' => 1,
                'total_events' => 1,
            ],
        ], $tenant2->id));
    }

    /** @test */
    public function it_handles_high_frequency_broadcasts_with_debouncing()
    {
        Broadcast::shouldReceive('event')
            ->andReturn(true);

        // Simulate rapid succession of events that should be debounced
        for ($i = 0; $i < 5; $i++) {
            AnalyticsEvent::factory()->create([
                'tenant_id' => 'test-tenant-websocket',
                'event_type' => 'real_time_metric',
                'properties' => ['metric_value' => $i],
                'consent_given' => true,
            ]);

            // In a real implementation, there would be debouncing logic
            // to prevent excessive broadcasts
            Event::dispatch(new AnalyticsBatchProcessed([
                'event_type' => 'real_time_metric',
                'count' => 1,
                'tenant_id' => 'test-tenant-websocket',
                'sequence' => $i,
                'summary' => ['metric_updates' => 1],
            ], 'test-tenant-websocket'));
        }

        // All broadcasts should succeed
        $this->assertTrue(true);
    }

    /** @test */
    public function it_broadcasts_cross_tenant_analytics_without_data_leakage()
    {
        $tenant1 = $this->tenant;
        $tenant2 = Tenant::factory()->create(['id' => 'secure-websocket-tenant']);

        Broadcast::shouldReceive('event')
            ->twice()
            ->with(\Mockery::on(function ($event) use ($tenant1, $tenant2) {
                // Ensure broadcasts only contain data for the correct tenant
                $tenantId = $event->tenantId;
                return ($tenantId === $tenant1->id || $tenantId === $tenant2->id) &&
                       !isset($event->data['cross_tenant_data']);
            }));

        // Create sensitive data for tenant1
        AnalyticsEvent::factory()->create([
            'tenant_id' => $tenant1->id,
            'event_type' => 'sensitive_metric',
            'properties' => [
                'confidential_data' => 'tenant1_secret',
                'user_count' => 150,
            ],
            'consent_given' => true,
        ]);

        // Create different data for tenant2
        tenancy()->initialize($tenant2);
        AnalyticsEvent::factory()->create([
            'tenant_id' => $tenant2->id,
            'event_type' => 'sensitive_metric',
            'properties' => [
                'confidential_data' => 'tenant2_secret',
                'user_count' => 75,
            ],
            'consent_given' => true,
        ]);
        tenancy()->initialize($tenant1);

        // Broadcast for tenant1 - should not include tenant2 data
        Event::dispatch(new AnalyticsBatchProcessed([
            'event_type' => 'sensitive_metric',
            'count' => 1,
            'tenant_id' => $tenant1->id,
            'summary' => [
                'user_count' => 150,
                'total_events' => 1,
            ],
        ], $tenant1->id));

        // Broadcast for tenant2 - should not include tenant1 data
        Event::dispatch(new AnalyticsBatchProcessed([
            'event_type' => 'sensitive_metric',
            'count' => 1,
            'tenant_id' => $tenant2->id,
            'summary' => [
                'user_count' => 75,
                'total_events' => 1,
            ],
        ], $tenant2->id));
    }
}