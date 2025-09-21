<?php

namespace Tests\Feature\Api;

use App\Models\AnalyticsEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsEventApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenant
        $this->tenant = Tenant::factory()->create([
            'id' => 'test-tenant-123',
            'name' => 'Test Tenant',
        ]);

        // Create test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_can_store_valid_batch_of_analytics_events()
    {
        $events = [
            [
                'tenant_id' => 'test-tenant-123',
                'event_type' => 'page_view',
                'properties' => [
                    'page' => '/dashboard',
                    'duration' => 1500,
                ],
                'session_id' => 'session-123',
                'timestamp' => now()->toISOString(),
                'user_id' => $this->user->id,
                'consent_flags' => ['analytics', 'marketing'],
            ],
            [
                'tenant_id' => 'test-tenant-123',
                'event_type' => 'button_click',
                'properties' => [
                    'button_id' => 'cta-button',
                    'page' => '/dashboard',
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

        // Verify events were stored
        $this->assertDatabaseCount('analytics_events', 2);

        $storedEvents = AnalyticsEvent::all();
        $this->assertEquals('test-tenant-123', $storedEvents[0]->tenant_id);
        $this->assertEquals('page_view', $storedEvents[0]->event_type);
        $this->assertEquals(['page' => '/dashboard', 'duration' => 1500], $storedEvents[0]->properties);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $invalidEvents = [
            [
                // Missing tenant_id
                'event_type' => 'page_view',
                'properties' => [],
                'session_id' => 'session-123',
                'timestamp' => now()->toISOString(),
            ],
        ];

        $response = $this->postJson('/api/analytics/events', [
            'events' => $invalidEvents,
        ]);

        $response->assertStatus(400)
                ->assertJsonStructure([
                    'success',
                    'errors',
                ]);

        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function it_handles_events_without_consent_by_anonymizing_data()
    {
        $events = [
            [
                'tenant_id' => 'test-tenant-123',
                'event_type' => 'page_view',
                'properties' => [
                    'page' => '/dashboard',
                    'email' => 'user@example.com', // Should be anonymized
                    'name' => 'John Doe', // Should be anonymized
                ],
                'session_id' => 'session-123',
                'timestamp' => now()->toISOString(),
                'user_id' => $this->user->id,
                'consent_flags' => [], // No consent
            ],
        ];

        $response = $this->postJson('/api/analytics/events', [
            'events' => $events,
        ]);

        $response->assertStatus(200);

        // Verify user_id is nullified and properties anonymized
        $storedEvent = AnalyticsEvent::first();
        $this->assertNull($storedEvent->user_id);
        $this->assertEquals('anonymized', $storedEvent->properties['email']);
        $this->assertEquals('anonymized', $storedEvent->properties['name']);
        $this->assertFalse($storedEvent->consent_given);
    }

    /** @test */
    public function it_limits_batch_size_to_100_events()
    {
        $events = [];
        for ($i = 0; $i < 101; $i++) {
            $events[] = [
                'tenant_id' => 'test-tenant-123',
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
    }

    /** @test */
    public function it_handles_invalid_tenant_id()
    {
        $events = [
            [
                'tenant_id' => 'non-existent-tenant',
                'event_type' => 'page_view',
                'properties' => ['page' => '/dashboard'],
                'session_id' => 'session-123',
                'timestamp' => now()->toISOString(),
                'user_id' => $this->user->id,
                'consent_flags' => ['analytics'],
            ],
        ];

        $response = $this->postJson('/api/analytics/events', [
            'events' => $events,
        ]);

        // Should still return success but with errors for the invalid event
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'processed' => 0,
                    'errors' => [
                        [
                            'index' => 0,
                            'event_type' => 'page_view',
                        ],
                    ],
                ]);
    }

    /** @test */
    public function it_processes_events_with_proper_tenant_isolation()
    {
        // Create another tenant
        $otherTenant = Tenant::factory()->create([
            'id' => 'other-tenant-456',
            'name' => 'Other Tenant',
        ]);

        $events = [
            [
                'tenant_id' => 'test-tenant-123',
                'event_type' => 'page_view',
                'properties' => ['page' => '/dashboard'],
                'session_id' => 'session-123',
                'timestamp' => now()->toISOString(),
                'user_id' => $this->user->id,
                'consent_flags' => ['analytics'],
            ],
            [
                'tenant_id' => 'other-tenant-456',
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

        // Verify both tenants have their events
        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => 'test-tenant-123',
            'event_type' => 'page_view',
        ]);

        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => 'other-tenant-456',
            'event_type' => 'page_view',
        ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $this->withoutMiddleware();

        $events = [
            [
                'tenant_id' => 'test-tenant-123',
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

        // Should require authentication
        $response->assertStatus(401);
    }
}