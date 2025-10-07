<?php

namespace Tests\Integration;

use App\Jobs\ProcessAnalyticsEvents;
use App\Models\AnalyticsEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Analytics API integration testing
 * Tests end-to-end API flows including job dispatch, database operations, and tenant isolation
 */
class AnalyticsApiIntegrationTest extends TestCase
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
            'id' => 'test-tenant-analytics',
            'name' => 'Test Analytics Tenant',
        ]);

        // Create authenticated user
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_processes_analytics_events_end_to_end_with_job_dispatch()
    {
        Queue::fake();

        $events = [
            [
                'tenant_id' => 'test-tenant-analytics',
                'event_type' => 'page_view',
                'properties' => [
                    'page' => '/dashboard',
                    'duration' => 1500,
                    'session_id' => 'session-123',
                ],
                'session_id' => 'session-123',
                'timestamp' => now()->toISOString(),
                'user_id' => $this->user->id,
                'consent_flags' => ['analytics', 'marketing'],
            ],
            [
                'tenant_id' => 'test-tenant-analytics',
                'event_type' => 'gamification_achievement',
                'properties' => [
                    'achievement_type' => 'first_login',
                    'points_earned' => 100,
                    'badge_earned' => 'welcome_badge',
                ],
                'session_id' => 'session-123',
                'timestamp' => now()->toISOString(),
                'user_id' => $this->user->id,
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
                    'errors' => [],
                ]);

        // Assert job was dispatched with correct parameters
        Queue::assertPushed(ProcessAnalyticsEvents::class, function ($job) {
            return count($job->eventIds) === 2;
        });

        // Verify events were stored
        $this->assertDatabaseCount('analytics_events', 2);
        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => 'test-tenant-analytics',
            'event_type' => 'page_view',
        ]);
        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => 'test-tenant-analytics',
            'event_type' => 'gamification_achievement',
        ]);
    }

    /** @test */
    public function it_enforces_tenant_isolation_in_analytics_events()
    {
        // Create another tenant
        $otherTenant = Tenant::factory()->create([
            'id' => 'other-tenant-analytics',
            'name' => 'Other Analytics Tenant',
        ]);

        $events = [
            [
                'tenant_id' => 'test-tenant-analytics',
                'event_type' => 'page_view',
                'properties' => ['page' => '/dashboard'],
                'session_id' => 'session-123',
                'timestamp' => now()->toISOString(),
                'user_id' => $this->user->id,
                'consent_flags' => ['analytics'],
            ],
            [
                'tenant_id' => 'other-tenant-analytics',
                'event_type' => 'page_view',
                'properties' => ['page' => '/other-page'],
                'session_id' => 'session-456',
                'timestamp' => now()->toISOString(),
                'user_id' => $this->user->id,
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

        // Verify tenant isolation
        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => 'test-tenant-analytics',
            'event_type' => 'page_view',
        ]);
        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => 'other-tenant-analytics',
            'event_type' => 'page_view',
        ]);
    }

    /** @test */
    public function it_returns_gamification_metrics_with_tenant_isolation()
    {
        // Create analytics events for gamification
        AnalyticsEvent::factory()->create([
            'tenant_id' => 'test-tenant-analytics',
            'event_type' => 'gamification_achievement',
            'properties' => [
                'achievement_type' => 'first_login',
                'points_earned' => 100,
            ],
            'user_id' => $this->user->id,
            'consent_given' => true,
        ]);

        AnalyticsEvent::factory()->create([
            'tenant_id' => 'test-tenant-analytics',
            'event_type' => 'gamification_achievement',
            'properties' => [
                'achievement_type' => 'profile_complete',
                'points_earned' => 200,
            ],
            'user_id' => $this->user->id,
            'consent_given' => true,
        ]);

        // Create event for different tenant (should not be included)
        $otherTenant = Tenant::factory()->create(['id' => 'other-tenant']);
        AnalyticsEvent::factory()->create([
            'tenant_id' => 'other-tenant',
            'event_type' => 'gamification_achievement',
            'properties' => [
                'achievement_type' => 'other_achievement',
                'points_earned' => 50,
            ],
            'user_id' => $this->user->id,
            'consent_given' => true,
        ]);

        $response = $this->getJson('/api/analytics/gamification/metrics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'total_points',
                        'achievements_count',
                        'leaderboard_position',
                        'recent_achievements',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'total_points' => 300, // Only current tenant's points
                        'achievements_count' => 2,
                    ],
                ]);
    }

    /** @test */
    public function it_returns_heatmap_data_with_caching_validation()
    {
        $pageUrl = 'https://example.com/test-page';

        // Create analytics events for heatmap
        AnalyticsEvent::factory()->create([
            'tenant_id' => 'test-tenant-analytics',
            'event_type' => 'click',
            'page_url' => $pageUrl,
            'properties' => [
                'x' => 100,
                'y' => 200,
                'element' => 'button',
            ],
            'consent_given' => true,
        ]);

        AnalyticsEvent::factory()->create([
            'tenant_id' => 'test-tenant-analytics',
            'event_type' => 'click',
            'page_url' => $pageUrl,
            'properties' => [
                'x' => 150,
                'y' => 250,
                'element' => 'link',
            ],
            'consent_given' => true,
        ]);

        // Clear any existing cache
        Cache::flush();

        $response = $this->getJson('/api/analytics/heatmaps/' . urlencode($pageUrl));

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'heatMapData',
                        'pageUrl',
                        'dateRange',
                        'totalClicks',
                    ],
                ])
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'pageUrl' => $pageUrl,
                        'totalClicks' => 2,
                    ],
                ]);

        // Verify cache was set
        $cacheKey = "heatmap:test-tenant-analytics:{$pageUrl}:" . md5(serialize([
            'start' => now()->subDays(30)->toDateString(),
            'end' => now()->toDateString(),
        ]));
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_requires_authentication_for_analytics_endpoints()
    {
        $this->withoutMiddleware();

        $events = [
            [
                'tenant_id' => 'test-tenant-analytics',
                'event_type' => 'page_view',
                'properties' => ['page' => '/dashboard'],
                'session_id' => 'session-123',
                'timestamp' => now()->toISOString(),
                'consent_flags' => ['analytics'],
            ],
        ];

        $response = $this->postJson('/api/analytics/events', [
            'events' => $events,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_high_volume_event_batches()
    {
        Queue::fake();

        // Create 50 events (under the 100 limit)
        $events = [];
        for ($i = 0; $i < 50; $i++) {
            $events[] = [
                'tenant_id' => 'test-tenant-analytics',
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

        $response = $this->postJson('/api/analytics/events', [
            'events' => $events,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => 50,
                ]);

        // Verify all events were stored
        $this->assertDatabaseCount('analytics_events', 50);
    }

    /** @test */
    public function it_rejects_batches_over_100_events()
    {
        $events = [];
        for ($i = 0; $i < 101; $i++) {
            $events[] = [
                'tenant_id' => 'test-tenant-analytics',
                'event_type' => 'page_view',
                'properties' => ['page' => '/test'],
                'session_id' => 'session-' . $i,
                'timestamp' => now()->toISOString(),
                'user_id' => $this->user->id,
                'consent_flags' => ['analytics'],
            ];
        }

        $response = $this->postJson('/api/analytics/events', [
            'events' => $events,
        ]);

        $response->assertStatus(400)
                ->assertJsonStructure([
                    'success',
                    'errors',
                ]);

        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function it_handles_mixed_valid_and_invalid_events_in_batch()
    {
        Queue::fake();

        $events = [
            [
                'tenant_id' => 'test-tenant-analytics',
                'event_type' => 'page_view',
                'properties' => ['page' => '/valid-page'],
                'session_id' => 'session-123',
                'timestamp' => now()->toISOString(),
                'user_id' => $this->user->id,
                'consent_flags' => ['analytics'],
            ],
            [
                // Invalid event - missing tenant_id
                'event_type' => 'page_view',
                'properties' => ['page' => '/invalid-page'],
                'session_id' => 'session-456',
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
                    'processed' => 1, // Only valid event processed
                    'errors' => [
                        [
                            'index' => 1,
                            'event_type' => 'page_view',
                        ],
                    ],
                ]);

        // Verify only valid event was stored
        $this->assertDatabaseCount('analytics_events', 1);
        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => 'test-tenant-analytics',
            'event_type' => 'page_view',
        ]);
    }
}