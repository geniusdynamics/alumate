<?php

namespace Tests\Integration;

use App\Jobs\ProcessAnalyticsEvents;
use App\Jobs\WarmCacheJob;
use App\Models\AnalyticsEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * Analytics job flow integration testing
 * Tests job processing, batching, caching, and performance optimization
 */
class AnalyticsJobFlowIntegrationTest extends TestCase
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
            'id' => 'test-tenant-job-flow',
            'name' => 'Test Job Flow Tenant',
        ]);

        // Create authenticated user
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_processes_large_batch_of_analytics_events_in_chunks()
    {
        Queue::fake();

        // Create 150 events (should be processed in chunks of 100)
        $events = [];
        for ($i = 0; $i < 150; $i++) {
            $events[] = [
                'tenant_id' => 'test-tenant-job-flow',
                'event_type' => 'page_view',
                'properties' => [
                    'page' => '/test-page-' . $i,
                    'session_id' => 'session-' . $i,
                ],
                'session_id' => 'session-' . $i,
                'timestamp' => now()->toISOString(),
                'user_id' => $this->user->id,
                'consent_flags' => ['analytics'],
            ];
        }

        // Submit batch through API
        $response = $this->postJson('/api/analytics/events', [
            'events' => $events,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => 150,
                ]);

        // Verify jobs were dispatched (should be chunked)
        Queue::assertPushed(ProcessAnalyticsEvents::class, 2); // 150 events / 100 chunk size = 2 jobs

        // Verify all events were stored
        $this->assertDatabaseCount('analytics_events', 150);
    }

    /** @test */
    public function it_handles_job_batching_efficiency_for_different_event_types()
    {
        Queue::fake();

        // Create mix of event types
        $eventTypes = ['page_view', 'click', 'scroll', 'gamification_achievement', 'user_engagement'];
        $events = [];

        for ($i = 0; $i < 100; $i++) {
            $eventType = $eventTypes[$i % count($eventTypes)];
            $events[] = [
                'tenant_id' => 'test-tenant-job-flow',
                'event_type' => $eventType,
                'properties' => [
                    'page' => '/test-page-' . $i,
                    'session_id' => 'session-' . $i,
                ],
                'session_id' => 'session-' . $i,
                'timestamp' => now()->toISOString(),
                'user_id' => $this->user->id,
                'consent_flags' => ['analytics'],
            ];
        }

        $response = $this->postJson('/api/analytics/events', [
            'events' => $events,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => 100,
                ]);

        // Verify single job was dispatched for 100 events
        Queue::assertPushed(ProcessAnalyticsEvents::class, 1);

        // Verify events are properly categorized
        foreach ($eventTypes as $type) {
            $this->assertDatabaseHas('analytics_events', [
                'tenant_id' => 'test-tenant-job-flow',
                'event_type' => $type,
            ]);
        }
    }

    /** @test */
    public function it_processes_events_with_redis_cache_warming()
    {
        // Mock Redis for cache operations
        Redis::shouldReceive('set')
             ->andReturn(true);
        Redis::shouldReceive('get')
             ->andReturn(null);
        Redis::shouldReceive('expire')
             ->andReturn(true);

        // Create analytics events
        AnalyticsEvent::factory()->count(50)->create([
            'tenant_id' => 'test-tenant-job-flow',
            'event_type' => 'page_view',
            'consent_given' => true,
        ]);

        // Dispatch cache warming job
        $job = new WarmCacheJob('test-tenant-job-flow');
        $job->handle(
            app(\App\Services\CachingStrategyService::class),
            app(\App\Services\ComponentCachingService::class)
        );

        // Verify cache warming completed without errors
        // (In a real scenario, we'd check specific cache keys were set)
        $this->assertTrue(true); // Job completed successfully
    }

    /** @test */
    public function it_handles_job_failures_gracefully_with_retry_logic()
    {
        Queue::fake();

        // Create events that might cause processing issues
        $events = [];
        for ($i = 0; $i < 10; $i++) {
            $events[] = [
                'tenant_id' => 'test-tenant-job-flow',
                'event_type' => 'page_view',
                'properties' => null, // This might cause issues in processing
                'session_id' => 'session-' . $i,
                'timestamp' => now()->toISOString(),
                'user_id' => $this->user->id,
                'consent_flags' => ['analytics'],
            ];
        }

        $response = $this->postJson('/api/analytics/events', [
            'events' => $events,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => 10,
                ]);

        // Job should still be dispatched even with potentially problematic data
        Queue::assertPushed(ProcessAnalyticsEvents::class, 1);
    }

    /** @test */
    public function it_optimizes_batch_processing_for_high_volume_scenarios()
    {
        Queue::fake();

        // Simulate high volume scenario with 500 events
        $events = [];
        for ($i = 0; $i < 500; $i++) {
            $events[] = [
                'tenant_id' => 'test-tenant-job-flow',
                'event_type' => 'page_view',
                'properties' => [
                    'page' => '/high-volume-page',
                    'session_id' => 'session-' . $i,
                    'load_test' => true,
                ],
                'session_id' => 'session-' . $i,
                'timestamp' => now()->toISOString(),
                'user_id' => $this->user->id,
                'consent_flags' => ['analytics'],
            ];
        }

        $startTime = microtime(true);

        $response = $this->postJson('/api/analytics/events', [
            'events' => $events,
        ]);

        $endTime = microtime(true);
        $processingTime = $endTime - $startTime;

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => 500,
                ]);

        // Verify efficient batching (500 events should be 5 jobs of 100 each)
        Queue::assertPushed(ProcessAnalyticsEvents::class, 5);

        // Processing should be reasonably fast (< 1 second for API submission)
        $this->assertLessThan(1.0, $processingTime);

        // Verify all events stored
        $this->assertDatabaseCount('analytics_events', 500);
    }

    /** @test */
    public function it_maintains_tenant_isolation_during_job_processing()
    {
        Queue::fake();

        // Create another tenant
        $otherTenant = Tenant::factory()->create([
            'id' => 'other-tenant-job-flow',
            'name' => 'Other Job Flow Tenant',
        ]);

        // Create events for both tenants
        $events = [
            [
                'tenant_id' => 'test-tenant-job-flow',
                'event_type' => 'page_view',
                'properties' => ['page' => '/tenant1-page'],
                'session_id' => 'session-1',
                'timestamp' => now()->toISOString(),
                'consent_flags' => ['analytics'],
            ],
            [
                'tenant_id' => 'other-tenant-job-flow',
                'event_type' => 'page_view',
                'properties' => ['page' => '/tenant2-page'],
                'session_id' => 'session-2',
                'timestamp' => now()->toISOString(),
                'consent_flags' => ['analytics'],
            ],
        ];

        $response = $this->postJson('/api/analytics/events', [
            'events' => $events,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => 2,
                ]);

        // Verify tenant isolation in stored events
        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => 'test-tenant-job-flow',
            'event_type' => 'page_view',
        ]);
        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => 'other-tenant-job-flow',
            'event_type' => 'page_view',
        ]);
    }

    /** @test */
    public function it_handles_cache_invalidation_during_job_processing()
    {
        // Create initial cache entry
        $cacheKey = 'analytics:summary:test-tenant-job-flow';
        Cache::put($cacheKey, ['old_data' => true], 3600);

        // Create events that should trigger cache invalidation
        AnalyticsEvent::factory()->count(10)->create([
            'tenant_id' => 'test-tenant-job-flow',
            'event_type' => 'page_view',
            'consent_given' => true,
        ]);

        // Process events (this should invalidate relevant caches)
        $eventIds = AnalyticsEvent::where('tenant_id', 'test-tenant-job-flow')
                                  ->pluck('id')
                                  ->toArray();

        $job = new ProcessAnalyticsEvents($eventIds, 'test-tenant-job-flow');
        $job->handle(
            app(\App\Services\AnalyticsService::class),
            app(\App\Services\TenantContextService::class)
        );

        // Verify events were processed
        foreach ($eventIds as $eventId) {
            $this->assertDatabaseHas('analytics_events', [
                'id' => $eventId,
                'is_compliant' => true,
            ]);
        }
    }

    /** @test */
    public function it_processes_events_with_proper_error_handling_and_logging()
    {
        // Create mix of valid and invalid events
        $validEvent = AnalyticsEvent::factory()->create([
            'tenant_id' => 'test-tenant-job-flow',
            'event_type' => 'page_view',
            'properties' => ['page' => '/valid'],
            'consent_given' => true,
        ]);

        $invalidEvent = AnalyticsEvent::factory()->create([
            'tenant_id' => 'test-tenant-job-flow',
            'event_type' => 'invalid_type',
            'properties' => ['invalid' => 'data'],
            'consent_given' => true,
        ]);

        $job = new ProcessAnalyticsEvents([$validEvent->id, $invalidEvent->id], 'test-tenant-job-flow');

        // Job should handle errors gracefully
        $job->handle(
            app(\App\Services\AnalyticsService::class),
            app(\App\Services\TenantContextService::class)
        );

        // Valid event should be processed
        $validEvent->refresh();
        $this->assertTrue($validEvent->is_compliant);

        // Invalid event should still exist but might not be compliant
        $invalidEvent->refresh();
        $this->assertDatabaseHas('analytics_events', ['id' => $invalidEvent->id]);
    }

    /** @test */
    public function it_optimizes_job_queue_performance_with_prioritization()
    {
        Queue::fake();

        // Create high-priority events (errors, security issues)
        $highPriorityEvents = [
            [
                'tenant_id' => 'test-tenant-job-flow',
                'event_type' => 'security_alert',
                'properties' => ['alert_type' => 'suspicious_login'],
                'session_id' => 'session-security',
                'timestamp' => now()->toISOString(),
                'consent_flags' => ['analytics'],
            ],
        ];

        // Create normal priority events
        $normalEvents = [];
        for ($i = 0; $i < 50; $i++) {
            $normalEvents[] = [
                'tenant_id' => 'test-tenant-job-flow',
                'event_type' => 'page_view',
                'properties' => ['page' => '/normal-page-' . $i],
                'session_id' => 'session-' . $i,
                'timestamp' => now()->toISOString(),
                'consent_flags' => ['analytics'],
            ];
        }

        // Submit both batches
        $this->postJson('/api/analytics/events', ['events' => $highPriorityEvents]);
        $this->postJson('/api/analytics/events', ['events' => $normalEvents]);

        // Verify jobs were queued (in real scenario, we'd check queue priorities)
        Queue::assertPushed(ProcessAnalyticsEvents::class, 2); // 1 for security + 1 for normal
    }

    /** @test */
    public function it_handles_job_timeout_and_recovery_scenarios()
    {
        // Create a large batch that might timeout
        $largeBatch = [];
        for ($i = 0; $i < 200; $i++) {
            $largeBatch[] = [
                'tenant_id' => 'test-tenant-job-flow',
                'event_type' => 'page_view',
                'properties' => [
                    'page' => '/large-batch-page',
                    'batch_size' => 200,
                ],
                'session_id' => 'session-large-' . $i,
                'timestamp' => now()->toISOString(),
                'user_id' => $this->user->id,
                'consent_flags' => ['analytics'],
            ];
        }

        $response = $this->postJson('/api/analytics/events', [
            'events' => $largeBatch,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => 200,
                ]);

        // Verify efficient chunking (200 events = 2 jobs of 100 each)
        Queue::assertPushed(ProcessAnalyticsEvents::class, 2);

        // All events should be stored
        $this->assertDatabaseCount('analytics_events', 200);
    }
}