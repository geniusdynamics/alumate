<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Models\AttributionTouch;
use App\Models\User;
use App\Models\Tenant;
use App\Services\Analytics\AttributionTrackingService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for AttributionTrackingService
 *
 * @covers \App\Services\Analytics\AttributionTrackingService
 */
class AttributionTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttributionTrackingService $service;
    private TenantContextService $tenantContext;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->tenantContext = Mockery::mock(TenantContextService::class);
        $this->tenantContext->shouldReceive('getCurrentTenantId')
            ->andReturn($this->tenant->id);
        $this->tenantContext->shouldReceive('getCurrentSchema')
            ->andReturn('tenant_' . $this->tenant->id);

        $this->service = new AttributionTrackingService($this->tenantContext);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test tracking a touchpoint with valid data
     */
    public function test_track_touchpoint_with_valid_data(): void
    {
        $user = User::factory()->create();
        $channel = 'paid_search';
        $data = [
            'event_type' => 'page_view',
            'medium' => 'cpc',
            'campaign' => 'summer_sale',
            'value' => 10.50,
            'session_id' => 'test-session-123',
        ];

        $touch = $this->service->trackTouchpoint($user->id, $channel, $data);

        $this->assertInstanceOf(AttributionTouch::class, $touch);
        $this->assertEquals($user->id, $touch->user_id);
        $this->assertEquals($channel, $touch->source);
        $this->assertEquals('page_view', $touch->event_type);
        $this->assertEquals('cpc', $touch->medium);
        $this->assertEquals('summer_sale', $touch->campaign);
        $this->assertEquals(10.50, $touch->value);
        $this->assertEquals($this->tenant->id, $touch->tenant_id);
    }

    /**
     * Test tracking touchpoint with minimal data
     */
    public function test_track_touchpoint_with_minimal_data(): void
    {
        $user = User::factory()->create();
        $channel = 'email';

        $touch = $this->service->trackTouchpoint($user->id, $channel);

        $this->assertInstanceOf(AttributionTouch::class, $touch);
        $this->assertEquals($user->id, $touch->user_id);
        $this->assertEquals($channel, $touch->source);
        $this->assertEquals('page_view', $touch->event_type); // Default
        $this->assertEquals(0, $touch->value); // Default
        $this->assertNotNull($touch->session_id);
    }

    /**
     * Test first-click attribution model
     */
    public function test_first_click_attribution(): void
    {
        $user = User::factory()->create();

        // Create touches with different timestamps
        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'organic_search',
            'value' => 10.00,
            'timestamp' => now()->subDays(5),
            'event_type' => 'signup',
        ]);

        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'paid_social',
            'value' => 25.00,
            'timestamp' => now()->subDay(),
            'event_type' => 'purchase',
        ]);

        $result = $this->service->firstClickAttribution(
            $user->id,
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals($user->id, $result['user_id']);
        $this->assertEquals('first_click', $result['model']);
        $this->assertEquals(35.00, $result['total_value']);
        $this->assertCount(1, $result['attribution']);

        $attribution = $result['attribution'][0];
        $this->assertEquals('organic_search', $attribution['channel']);
        $this->assertEquals(100.0, $attribution['percentage']);
        $this->assertEquals(35.00, $attribution['value']);
        $this->assertEquals('first', $attribution['touch_position']);
    }

    /**
     * Test last-click attribution model
     */
    public function test_last_click_attribution(): void
    {
        $user = User::factory()->create();

        // Create touches with different timestamps
        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'organic_search',
            'value' => 10.00,
            'timestamp' => now()->subDays(5),
        ]);

        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'email',
            'value' => 25.00,
            'timestamp' => now()->subHours(2),
        ]);

        $result = $this->service->lastClickAttribution(
            $user->id,
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals('last_click', $result['model']);
        $this->assertCount(1, $result['attribution']);

        $attribution = $result['attribution'][0];
        $this->assertEquals('email', $attribution['channel']);
        $this->assertEquals(100.0, $attribution['percentage']);
        $this->assertEquals(35.00, $attribution['value']);
        $this->assertEquals('last', $attribution['touch_position']);
    }

    /**
     * Test linear multi-touch attribution model
     */
    public function test_linear_multi_touch_attribution(): void
    {
        $user = User::factory()->create();

        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'google',
            'value' => 10.00,
            'timestamp' => now()->subDays(3),
        ]);

        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'facebook',
            'value' => 10.00,
            'timestamp' => now()->subDays(2),
        ]);

        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'email',
            'value' => 10.00,
            'timestamp' => now()->subDay(),
        ]);

        $result = $this->service->multiTouchAttribution(
            $user->id,
            AttributionTrackingService::MODEL_LINEAR,
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals('linear', $result['model']);
        $this->assertEquals(30.00, $result['total_value']);
        $this->assertCount(3, $result['attribution']);

        // Each channel should get equal 33.33%
        foreach ($result['attribution'] as $attribution) {
            $this->assertEquals(33.33, $attribution['percentage']);
            $this->assertEquals(10.00, $attribution['value']);
        }
    }

    /**
     * Test time-decay multi-touch attribution model
     */
    public function test_time_decay_multi_touch_attribution(): void
    {
        $user = User::factory()->create();

        // Older touch
        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'google',
            'value' => 10.00,
            'timestamp' => now()->subDays(14),
        ]);

        // Recent touch
        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'facebook',
            'value' => 10.00,
            'timestamp' => now()->subHours(1),
        ]);

        $result = $this->service->multiTouchAttribution(
            $user->id,
            AttributionTrackingService::MODEL_TIME_DECAY,
            now()->subDays(30)->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals('time_decay', $result['model']);
        $this->assertCount(2, $result['attribution']);

        // Recent touch (facebook) should have higher percentage
        $facebookAttribution = collect($result['attribution'])->firstWhere('channel', 'facebook');
        $googleAttribution = collect($result['attribution'])->firstWhere('channel', 'google');

        $this->assertGreaterThan($googleAttribution['percentage'], $facebookAttribution['percentage']);
        $this->assertGreaterThan($googleAttribution['value'], $facebookAttribution['value']);
    }

    /**
     * Test position-based multi-touch attribution model
     */
    public function test_position_based_multi_touch_attribution(): void
    {
        $user = User::factory()->create();

        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'google',
            'value' => 10.00,
            'timestamp' => now()->subDays(3),
        ]);

        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'facebook',
            'value' => 10.00,
            'timestamp' => now()->subDays(2),
        ]);

        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'email',
            'value' => 10.00,
            'timestamp' => now()->subDay(),
        ]);

        $result = $this->service->multiTouchAttribution(
            $user->id,
            AttributionTrackingService::MODEL_POSITION_BASED,
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals('position_based', $result['model']);
        $this->assertCount(3, $result['attribution']);

        $googleAttribution = collect($result['attribution'])->firstWhere('channel', 'google');
        $facebookAttribution = collect($result['attribution'])->firstWhere('channel', 'facebook');
        $emailAttribution = collect($result['attribution'])->firstWhere('channel', 'email');

        // Google (first) should get 40%
        $this->assertEquals(40.0, $googleAttribution['percentage']);
        $this->assertEquals(12.00, $googleAttribution['value']);

        // Email (last) should get 40%
        $this->assertEquals(40.0, $emailAttribution['percentage']);
        $this->assertEquals(12.00, $emailAttribution['value']);

        // Facebook (middle) should get 20%
        $this->assertEquals(20.0, $facebookAttribution['percentage']);
        $this->assertEquals(6.00, $facebookAttribution['value']);
    }

    /**
     * Test channel performance analysis
     */
    public function test_analyze_channel_performance(): void
    {
        $user = User::factory()->create();

        // Create touches for different channels
        AttributionTouch::factory()->count(10)->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'organic_search',
            'event_type' => 'page_view',
            'value' => 5.00,
            'timestamp' => now()->subDays(5),
        ]);

        AttributionTouch::factory()->count(5)->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'paid_search',
            'event_type' => 'purchase',
            'value' => 50.00,
            'timestamp' => now()->subDays(2),
        ]);

        AttributionTouch::factory()->count(3)->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'email',
            'event_type' => 'purchase',
            'value' => 30.00,
            'timestamp' => now()->subDay(),
        ]);

        $result = $this->service->analyzeChannelPerformance(
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('total_touches', $result);
        $this->assertArrayHasKey('total_users', $result);
        $this->assertArrayHasKey('total_value', $result);
        $this->assertArrayHasKey('channels', $result);
        $this->assertArrayHasKey('summary', $result);

        $this->assertEquals(18, $result['total_touches']); // 10 + 5 + 3
        $this->assertEquals(1, $result['total_users']); // Only one user
        $this->assertEquals(300.00, $result['total_value']); // (10*5) + (5*50) + (3*30)
        $this->assertCount(3, $result['channels']);

        // Top channel should be paid_search by value
        $topChannel = $result['channels'][0];
        $this->assertEquals('paid_search', $topChannel['channel']);
        $this->assertEquals(250.00, $topChannel['total_value']);
    }

    /**
     * Test budget recommendations generation
     */
    public function test_generate_budget_recommendations(): void
    {
        $user = User::factory()->create();

        // High performing channel
        AttributionTouch::factory()->count(10)->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'paid_search',
            'event_type' => 'purchase',
            'value' => 100.00,
            'timestamp' => now()->subDays(5),
        ]);

        // Lower performing channel
        AttributionTouch::factory()->count(5)->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'display',
            'event_type' => 'page_view',
            'value' => 1.00,
            'timestamp' => now()->subDays(3),
        ]);

        $result = $this->service->generateBudgetRecommendations(
            now()->subDays(7)->toDateString(),
            now()->toDateString(),
            1000.00 // $1000 total budget
        );

        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('total_budget', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('summary', $result);

        $this->assertEquals(1000.00, $result['total_budget']);
        $this->assertCount(2, $result['recommendations']);

        // Paid search should have higher efficiency score
        $paidSearchRec = collect($result['recommendations'])->firstWhere('channel', 'paid_search');
        $displayRec = collect($result['recommendations'])->firstWhere('channel', 'display');

        $this->assertGreaterThan($displayRec['efficiency_score'], $paidSearchRec['efficiency_score']);
        $this->assertGreaterThan($paidSearchRec['recommended_budget'], $displayRec['recommended_budget']);
        $this->assertStringContainsString('High-performing', $paidSearchRec['recommendation']);
    }

    /**
     * Test conversion path retrieval
     */
    public function test_get_conversion_path(): void
    {
        $user = User::factory()->create();

        $timestamps = [
            now()->subDays(5),
            now()->subDays(4),
            now()->subDays(2),
            now()->subHours(12),
        ];

        $channels = ['organic_search', 'social_organic', 'email', 'direct'];

        foreach ($timestamps as $index => $timestamp) {
            AttributionTouch::factory()->create([
                'user_id' => $user->id,
                'tenant_id' => $this->tenant->id,
                'source' => $channels[$index],
                'event_type' => 'page_view',
                'value' => 5.00 * ($index + 1),
                'timestamp' => $timestamp,
                'session_id' => 'session-' . $user->id,
            ]);
        }

        $result = $this->service->getConversionPath(
            $user->id,
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals($user->id, $result['user_id']);
        $this->assertArrayHasKey('touchpoints', $result);
        $this->assertArrayHasKey('path_length', $result);
        $this->assertArrayHasKey('channels', $result);
        $this->assertArrayHasKey('unique_channels', $result);
        $this->assertArrayHasKey('total_value', $result);
        $this->assertArrayHasKey('journey_duration_days', $result);

        $this->assertEquals(4, $result['path_length']);
        $this->assertEquals(4, $result['unique_channels']);
        $this->assertEquals(50.00, $result['total_value']); // 5+10+15+20
        $this->assertGreaterThan(0, $result['journey_duration_days']);

        // Verify chronological order
        $touchpoints = $result['touchpoints'];
        $this->assertEquals('organic_search', $touchpoints[0]['channel']); // First
        $this->assertEquals('direct', $touchpoints[3]['channel']); // Last

        // Verify positions
        $this->assertEquals(1, $touchpoints[0]['position']);
        $this->assertEquals(2, $touchpoints[1]['position']);
        $this->assertEquals(3, $touchpoints[2]['position']);
        $this->assertEquals(4, $touchpoints[3]['position']);
    }

    /**
     * Test calculate attribution with user with no touchpoints
     */
    public function test_calculate_attribution_with_no_touchpoints(): void
    {
        $user = User::factory()->create();

        $result = $this->service->calculateAttribution($user->id);

        $this->assertEquals($user->id, $result['user_id']);
        $this->assertEquals(0, $result['total_value']);
        $this->assertEquals(0, $result['touch_count']);
        $this->assertEmpty($result['attribution']);
    }

    /**
     * Test first-click attribution with no touchpoints
     */
    public function test_first_click_attribution_with_no_touchpoints(): void
    {
        $user = User::factory()->create();

        $result = $this->service->firstClickAttribution($user->id);

        $this->assertEquals($user->id, $result['user_id']);
        $this->assertEquals(0, $result['total_value']);
        $this->assertEmpty($result['attribution']);
    }

    /**
     * Test last-click attribution with no touchpoints
     */
    public function test_last_click_attribution_with_no_touchpoints(): void
    {
        $user = User::factory()->create();

        $result = $this->service->lastClickAttribution($user->id);

        $this->assertEquals($user->id, $result['user_id']);
        $this->assertEquals(0, $result['total_value']);
        $this->assertEmpty($result['attribution']);
    }

    /**
     * Test multi-touch attribution with no touchpoints
     */
    public function test_multi_touch_attribution_with_no_touchpoints(): void
    {
        $user = User::factory()->create();

        $result = $this->service->multiTouchAttribution($user->id, AttributionTrackingService::MODEL_LINEAR);

        $this->assertEquals($user->id, $result['user_id']);
        $this->assertEquals(0, $result['total_value']);
        $this->assertEmpty($result['attribution']);
    }

    /**
     * Test analyze channel performance with no touchpoints
     */
    public function test_analyze_channel_performance_with_no_touchpoints(): void
    {
        $result = $this->service->analyzeChannelPerformance(
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals(0, $result['total_touches']);
        $this->assertEquals(0, $result['total_users']);
        $this->assertEquals(0, $result['total_value']);
        $this->assertEmpty($result['channels']);
    }

    /**
     * Test get conversion path with no touchpoints
     */
    public function test_get_conversion_path_with_no_touchpoints(): void
    {
        $user = User::factory()->create();

        $result = $this->service->getConversionPath($user->id);

        $this->assertEquals($user->id, $result['user_id']);
        $this->assertEmpty($result['touchpoints']);
        $this->assertEquals(0, $result['path_length']);
        $this->assertEmpty($result['channels']);
    }

    /**
     * Test tenant isolation - user from another tenant should not see our data
     */
    public function test_tenant_isolation_in_attribution_tracking(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $thisUser = User::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create touchpoint for other user in other tenant
        AttributionTouch::factory()->create([
            'user_id' => $otherUser->id,
            'tenant_id' => $otherTenant->id,
            'source' => 'other_channel',
            'value' => 100.00,
            'timestamp' => now()->subDay(),
        ]);

        // Create touchpoint for this user
        AttributionTouch::factory()->create([
            'user_id' => $thisUser->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'our_channel',
            'value' => 50.00,
            'timestamp' => now()->subDay(),
        ]);

        // This user's attribution should only show our_channel
        $result = $this->service->calculateAttribution($thisUser->id);

        $this->assertEquals(50.00, $result['total_value']);
        $this->assertCount(1, $result['attribution']);
        $this->assertEquals('our_channel', $result['attribution'][0]['channel']);
    }

    /**
     * Test channel performance isolation by tenant
     */
    public function test_channel_performance_tenant_isolation(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

        // Create data for other tenant
        AttributionTouch::factory()->count(20)->create([
            'user_id' => $otherUser->id,
            'tenant_id' => $otherTenant->id,
            'source' => 'other_channel',
            'event_type' => 'purchase',
            'value' => 100.00,
            'timestamp' => now()->subDays(5),
        ]);

        // Create data for this tenant
        AttributionTouch::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'our_channel',
            'event_type' => 'purchase',
            'value' => 50.00,
            'timestamp' => now()->subDays(2),
        ]);

        $result = $this->service->analyzeChannelPerformance(
            now()->subDays(7)->toDateString(),
            now()->toDateString()
        );

        $this->assertEquals(5, $result['total_touches']);
        $this->assertEquals(1, $result['total_users']);
        $this->assertEquals(250.00, $result['total_value']);
        $this->assertCount(1, $result['channels']);
        $this->assertEquals('our_channel', $result['channels'][0]['channel']);
    }

    /**
     * Test touchpoint event type validation
     */
    public function test_track_touchpoint_with_invalid_event_type(): void
    {
        $user = User::factory()->create();

        $data = [
            'event_type' => 'invalid_event_type',
            'value' => 10.00,
        ];

        // This should still work as validation is permissive
        $touch = $this->service->trackTouchpoint($user->id, 'test_channel', $data);

        // The invalid event type should be replaced with default
        $this->assertEquals('page_view', $touch->event_type);
    }

    /**
     * Test touchpoint with negative value is handled
     */
    public function test_track_touchpoint_with_negative_value(): void
    {
        $user = User::factory()->create();

        $data = [
            'event_type' => 'purchase',
            'value' => -10.00,
        ];

        // Negative value should be replaced with 0
        $touch = $this->service->trackTouchpoint($user->id, 'test_channel', $data);

        $this->assertEquals(0, $touch->value);
    }

    /**
     * Test calculate attribution with different models returns different results
     */
    public function test_different_models_return_different_results(): void
    {
        $user = User::factory()->create();

        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'channel_a',
            'value' => 10.00,
            'timestamp' => now()->subDays(5),
        ]);

        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $this->tenant->id,
            'source' => 'channel_b',
            'value' => 10.00,
            'timestamp' => now()->subDay(),
        ]);

        $firstClick = $this->service->firstClickAttribution($user->id);
        $lastClick = $this->service->lastClickAttribution($user->id);
        $linear = $this->service->multiTouchAttribution($user->id, AttributionTrackingService::MODEL_LINEAR);

        // First click should attribute to channel_a
        $this->assertEquals('channel_a', $firstClick['attribution'][0]['channel']);

        // Last click should attribute to channel_b
        $this->assertEquals('channel_b', $lastClick['attribution'][0]['channel']);

        // Linear should distribute equally
        $this->assertCount(2, $linear['attribution']);
    }
}
