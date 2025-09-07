<?php

namespace Tests\Feature\Api;

use App\Models\EmailAnalytics;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for AnalyticsController email analytics endpoints
 */
class AnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Template $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->template = Template::factory()->create(['tenant_id' => $this->tenant->id]);

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_get_email_performance_metrics()
    {
        // Create test data
        EmailAnalytics::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'delivered_at' => now(),
        ]);

        EmailAnalytics::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'opened_at' => now(),
            'delivered_at' => now()->subMinutes(5),
        ]);

        $response = $this->getJson('/api/analytics/email/performance');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'total_sent',
                        'total_delivered',
                        'total_opened',
                        'delivery_rate',
                        'open_rate',
                    ],
                    'message'
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(15, $response->json('data.total_sent'));
        $this->assertEquals(15, $response->json('data.total_delivered'));
        $this->assertEquals(5, $response->json('data.total_opened'));
    }

    /** @test */
    public function it_can_get_email_funnel_analytics()
    {
        // Create funnel test data
        EmailAnalytics::factory()->count(100)->create(['tenant_id' => $this->tenant->id]);
        EmailAnalytics::factory()->count(80)->create([
            'tenant_id' => $this->tenant->id,
            'delivered_at' => now(),
        ]);
        EmailAnalytics::factory()->count(60)->create([
            'tenant_id' => $this->tenant->id,
            'opened_at' => now(),
            'delivered_at' => now()->subMinutes(5),
        ]);
        EmailAnalytics::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id,
            'clicked_at' => now(),
            'opened_at' => now()->subMinutes(5),
            'delivered_at' => now()->subMinutes(10),
        ]);

        $response = $this->getJson('/api/analytics/email/funnel');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'sent',
                        'delivered',
                        'opened',
                        'clicked',
                        'converted',
                        'delivered_rate',
                        'opened_rate',
                        'clicked_rate',
                        'converted_rate',
                    ],
                    'message'
                ]);

        $this->assertEquals(260, $response->json('data.sent'));
        $this->assertEquals(160, $response->json('data.delivered'));
        $this->assertEquals(80, $response->json('data.opened'));
        $this->assertEquals(20, $response->json('data.clicked'));
    }

    /** @test */
    public function it_can_generate_email_engagement_report()
    {
        EmailAnalytics::factory()->count(50)->create([
            'tenant_id' => $this->tenant->id,
            'device_type' => 'desktop',
        ]);

        EmailAnalytics::factory()->count(30)->create([
            'tenant_id' => $this->tenant->id,
            'device_type' => 'mobile',
        ]);

        $response = $this->getJson('/api/analytics/email/engagement');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'daily_trends',
                        'device_breakdown',
                        'browser_breakdown',
                        'engagement_score',
                        'generated_at',
                    ],
                    'message'
                ]);
    }

    /** @test */
    public function it_can_get_ab_test_results()
    {
        // Create A/B test data
        EmailAnalytics::factory()->count(100)->create([
            'tenant_id' => $this->tenant->id,
            'ab_test_variant' => 'A',
            'opened_at' => now(),
            'delivered_at' => now()->subMinutes(5),
        ]);

        EmailAnalytics::factory()->count(100)->create([
            'tenant_id' => $this->tenant->id,
            'ab_test_variant' => 'B',
            'opened_at' => now(),
            'delivered_at' => now()->subMinutes(5),
        ]);

        $response = $this->getJson('/api/analytics/email/ab-test');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'variants' => [
                            'A' => [
                                'sent',
                                'opened',
                                'clicked',
                                'open_rate',
                                'click_rate',
                            ],
                            'B' => [
                                'sent',
                                'opened',
                                'clicked',
                                'open_rate',
                                'click_rate',
                            ],
                        ],
                        'winner',
                        'confidence_level',
                    ],
                    'message'
                ]);

        $this->assertEquals(100, $response->json('data.variants.A.sent'));
        $this->assertEquals(100, $response->json('data.variants.B.sent'));
    }

    /** @test */
    public function it_can_get_real_time_email_analytics()
    {
        // Create recent activity
        EmailAnalytics::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'opened_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);

        EmailAnalytics::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'clicked_at' => now()->subMinutes(1),
            'updated_at' => now()->subMinutes(1),
        ]);

        $response = $this->getJson('/api/analytics/email/realtime?minutes=5');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'stats' => [
                            'opens_last_5_minutes',
                            'clicks_last_5_minutes',
                            'conversions_last_5_minutes',
                        ],
                        'recent_activity',
                        'timestamp',
                    ],
                    'message'
                ]);

        $this->assertEquals(5, $response->json('data.stats.opens_last_5_minutes'));
        $this->assertEquals(3, $response->json('data.stats.clicks_last_5_minutes'));
    }

    /** @test */
    public function it_can_generate_automated_email_report()
    {
        EmailAnalytics::factory()->count(100)->create([
            'tenant_id' => $this->tenant->id,
            'send_date' => now(),
        ]);

        $response = $this->getJson('/api/analytics/email/report/daily');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'period',
                        'date_range',
                        'performance_metrics',
                        'funnel_analytics',
                        'engagement_report',
                        'ab_test_results',
                        'recommendations',
                        'generated_at',
                    ],
                    'message'
                ]);

        $this->assertEquals('daily', $response->json('data.period'));
    }

    /** @test */
    public function it_can_track_email_event()
    {
        $analytics = EmailAnalytics::factory()->create([
            'tenant_id' => $this->tenant->id,
            'recipient_id' => $this->user->id,
        ]);

        $eventData = [
            'email_analytics_id' => $analytics->id,
            'event_type' => 'open',
            'metadata' => [
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0...',
                'device_type' => 'desktop',
            ]
        ];

        $response = $this->postJson('/api/analytics/email/track', $eventData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Email event tracked successfully'
                ]);

        $analytics->refresh();
        $this->assertNotNull($analytics->opened_at);
        $this->assertEquals('desktop', $analytics->device_type);
    }

    /** @test */
    public function it_can_get_email_analytics_dashboard()
    {
        EmailAnalytics::factory()->count(100)->create([
            'tenant_id' => $this->tenant->id,
            'send_date' => now(),
        ]);

        $response = $this->getJson('/api/analytics/email/dashboard');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'performance_metrics',
                        'funnel_analytics',
                        'engagement_report',
                        'ab_test_results',
                        'real_time',
                    ],
                    'message'
                ]);
    }

    /** @test */
    public function it_filters_by_date_range()
    {
        $startDate = now()->subDays(7);
        $endDate = now();

        EmailAnalytics::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'send_date' => $startDate->copy()->subDays(1), // Before range
        ]);

        EmailAnalytics::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id,
            'send_date' => $startDate->copy()->addDays(1), // Within range
        ]);

        EmailAnalytics::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'send_date' => $endDate->copy()->addDays(1), // After range
        ]);

        $response = $this->getJson('/api/analytics/email/performance?' . http_build_query([
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $this->assertEquals(20, $response->json('data.total_sent'));
    }

    /** @test */
    public function it_filters_by_campaign()
    {
        EmailAnalytics::factory()->count(15)->create([
            'tenant_id' => $this->tenant->id,
            'email_campaign_id' => 1,
        ]);

        EmailAnalytics::factory()->count(25)->create([
            'tenant_id' => $this->tenant->id,
            'email_campaign_id' => 2,
        ]);

        $response = $this->getJson('/api/analytics/email/performance?campaign_id=1');

        $response->assertStatus(200);
        $this->assertEquals(15, $response->json('data.total_sent'));
    }

    /** @test */
    public function it_filters_by_template()
    {
        EmailAnalytics::factory()->count(12)->create([
            'tenant_id' => $this->tenant->id,
            'email_template_id' => $this->template->id,
        ]);

        EmailAnalytics::factory()->count(18)->create([
            'tenant_id' => $this->tenant->id,
            'email_template_id' => Template::factory()->create(['tenant_id' => $this->tenant->id])->id,
        ]);

        $response = $this->getJson('/api/analytics/email/performance?template_id=' . $this->template->id);

        $response->assertStatus(200);
        $this->assertEquals(12, $response->json('data.total_sent'));
    }

    /** @test */
    public function it_handles_invalid_email_analytics_id()
    {
        $eventData = [
            'email_analytics_id' => 999999,
            'event_type' => 'open',
            'metadata' => []
        ];

        $response = $this->postJson('/api/analytics/email/track', $eventData);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => false,
                    'message' => 'Failed to track email event'
                ]);
    }

    /** @test */
    public function it_validates_track_event_request()
    {
        $response = $this->postJson('/api/analytics/email/track', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email_analytics_id', 'event_type']);
    }

    /** @test */
    public function it_validates_automated_report_period()
    {
        $response = $this->getJson('/api/analytics/email/report/invalid');

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['period']);
    }

    /** @test */
    public function it_validates_real_time_minutes_parameter()
    {
        $response = $this->getJson('/api/analytics/email/realtime?minutes=70');

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['minutes']);
    }

    /** @test */
    public function it_handles_empty_analytics_data()
    {
        $response = $this->getJson('/api/analytics/email/performance');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'total_sent' => 0,
                        'total_delivered' => 0,
                        'total_opened' => 0,
                        'total_clicked' => 0,
                        'total_converted' => 0,
                        'delivery_rate' => 0,
                        'open_rate' => 0,
                        'click_rate' => 0,
                        'conversion_rate' => 0,
                    ]
                ]);
    }

    /** @test */
    public function it_handles_ab_test_with_no_data()
    {
        $response = $this->getJson('/api/analytics/email/ab-test');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'variants' => [],
                        'winner' => null,
                        'confidence_level' => 0,
                    ]
                ]);
    }

    /** @test */
    public function it_tracks_conversion_with_value()
    {
        $analytics = EmailAnalytics::factory()->create([
            'tenant_id' => $this->tenant->id,
            'recipient_id' => $this->user->id,
            'clicked_at' => now(),
        ]);

        $eventData = [
            'email_analytics_id' => $analytics->id,
            'event_type' => 'conversion',
            'metadata' => [
                'type' => 'purchase',
                'value' => 99.99,
            ]
        ];

        $response = $this->postJson('/api/analytics/email/track', $eventData);

        $response->assertStatus(200);

        $analytics->refresh();
        $this->assertNotNull($analytics->converted_at);
        $this->assertEquals('purchase', $analytics->conversion_type);
        $this->assertEquals(99.99, $analytics->conversion_value);
        $this->assertEquals(5, $analytics->funnel_stage);
    }

    /** @test */
    public function it_requires_authentication()
    {
        Sanctum::actingAs($this->user, [], ''); // Clear authentication

        $response = $this->getJson('/api/analytics/email/performance');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_enforces_tenant_isolation()
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

        EmailAnalytics::factory()->count(10)->create([
            'tenant_id' => $otherTenant->id,
        ]);

        EmailAnalytics::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->getJson('/api/analytics/email/performance');

        $response->assertStatus(200);
        $this->assertEquals(5, $response->json('data.total_sent'));
    }
}