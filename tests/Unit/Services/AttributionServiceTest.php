<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AttributionTouch;
use App\Models\User;
use App\Services\Analytics\AttributionService;
use App\Services\Analytics\ConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for AttributionService
 *
 * @covers \App\Services\Analytics\AttributionService
 */
class AttributionServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttributionService $service;
    private ConsentService $consentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consentService = Mockery::mock(ConsentService::class);
        $this->service = new AttributionService();
        $this->tenant = ['id' => 'test-tenant'];
        $this->user = User::factory()->create();
    }

    /**
     * Test tracking a touch with valid data
     */
    public function test_track_touch_with_valid_data(): void
    {
        $user = User::factory()->create();
        $touchData = [
            'user_id' => $user->id,
            'event_type' => 'page_view',
            'source' => 'google',
            'medium' => 'organic',
            'value' => 10.50,
        ];

        $this->consentService
            ->shouldReceive('checkConsent')
            ->with($user->id, 'analytics')
            ->andReturn(true);

        $touch = $this->service->trackTouch($touchData);

        $this->assertInstanceOf(AttributionTouch::class, $touch);
        $this->assertEquals($user->id, $touch->user_id);
        $this->assertEquals('page_view', $touch->event_type);
        $this->assertEquals('google', $touch->source);
        $this->assertEquals(10.50, $touch->value);
    }

    /**
     * Test tracking touch without consent throws exception
     */
    public function test_track_touch_without_consent_throws_exception(): void
    {
        $user = User::factory()->create();
        $touchData = [
            'user_id' => $user->id,
            'event_type' => 'page_view',
            'value' => 5.00,
        ];

        $this->consentService
            ->shouldReceive('checkConsent')
            ->with($user->id, 'analytics')
            ->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User has not consented to analytics tracking');

        $this->service->trackTouch($touchData);
    }

    /**
     * Test tracking touch with invalid event type
     */
    public function test_track_touch_with_invalid_event_type(): void
    {
        $touchData = [
            'user_id' => 1,
            'event_type' => 'invalid_event',
            'value' => 5.00,
        ];

        $this->expectException(\Exception::class);
        $this->service->trackTouch($touchData);
    }

    /**
     * Test tracking touch with negative value
     */
    public function test_track_touch_with_negative_value(): void
    {
        $touchData = [
            'user_id' => 1,
            'event_type' => 'page_view',
            'value' => -5.00,
        ];

        $this->expectException(\Exception::class);
        $this->service->trackTouch($touchData);
    }

    /**
     * Test calculating attribution with last touch model
     */
    public function test_calculate_attribution_last_touch(): void
    {
        $user = User::factory()->create();

        // Create touches with different sources
        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'source' => 'google',
            'value' => 10.00,
            'timestamp' => now()->subDays(2),
        ]);

        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'source' => 'facebook',
            'value' => 15.00,
            'timestamp' => now()->subDay(),
        ]);

        $result = $this->service->calculateAttribution(
            $user->id,
            now()->subDays(7)->toDateString(),
            now()->toDateString(),
            'last_touch'
        );

        $this->assertEquals($user->id, $result['user_id']);
        $this->assertEquals(25.00, $result['total_value']);
        $this->assertEquals('last_touch', $result['model']);
        $this->assertCount(1, $result['sources']);
        $this->assertEquals('facebook', $result['sources'][0]['name']);
        $this->assertEquals(100.0, $result['sources'][0]['percentage']);
        $this->assertEquals(25.00, $result['sources'][0]['value']);
        $this->assertTrue($result['sources'][0]['last_touch']);
    }

    /**
     * Test calculating attribution with first touch model
     */
    public function test_calculate_attribution_first_touch(): void
    {
        $user = User::factory()->create();

        // Create touches with different sources
        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'source' => 'google',
            'value' => 10.00,
            'timestamp' => now()->subDays(2),
        ]);

        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'source' => 'facebook',
            'value' => 15.00,
            'timestamp' => now()->subDay(),
        ]);

        $result = $this->service->calculateAttribution(
            $user->id,
            now()->subDays(7)->toDateString(),
            now()->toDateString(),
            'first_touch'
        );

        $this->assertEquals('first_touch', $result['model']);
        $this->assertCount(1, $result['sources']);
        $this->assertEquals('google', $result['sources'][0]['name']);
        $this->assertEquals(100.0, $result['sources'][0]['percentage']);
        $this->assertTrue($result['sources'][0]['first_touch']);
    }

    /**
     * Test calculating attribution with linear model
     */
    public function test_calculate_attribution_linear(): void
    {
        $user = User::factory()->create();

        // Create touches with different sources
        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'source' => 'google',
            'value' => 10.00,
            'timestamp' => now()->subDays(2),
        ]);

        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'source' => 'facebook',
            'value' => 15.00,
            'timestamp' => now()->subDay(),
        ]);

        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'source' => 'google',
            'value' => 5.00,
            'timestamp' => now()->subHours(1),
        ]);

        $result = $this->service->calculateAttribution(
            $user->id,
            now()->subDays(7)->toDateString(),
            now()->toDateString(),
            'linear'
        );

        $this->assertEquals('linear', $result['model']);
        $this->assertEquals(30.00, $result['total_value']);

        // Should have two sources: google and facebook
        $this->assertCount(2, $result['sources']);

        $googleSource = $result['sources']->firstWhere('name', 'google');
        $facebookSource = $result['sources']->firstWhere('name', 'facebook');

        $this->assertEquals(50.0, $googleSource['percentage']); // (15+5)/30 = 50%
        $this->assertEquals(15.00, $googleSource['value']);
        $this->assertEquals(50.0, $facebookSource['percentage']); // 15/30 = 50%
        $this->assertEquals(15.00, $facebookSource['value']);
    }

    /**
     * Test calculating attribution with time decay model
     */
    public function test_calculate_attribution_time_decay(): void
    {
        $user = User::factory()->create();

        // Create touches with different timestamps
        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'source' => 'google',
            'value' => 10.00,
            'timestamp' => now()->subDays(2), // Older touch
        ]);

        AttributionTouch::factory()->create([
            'user_id' => $user->id,
            'source' => 'facebook',
            'value' => 10.00,
            'timestamp' => now()->subHours(1), // Recent touch
        ]);

        $result = $this->service->calculateAttribution(
            $user->id,
            now()->subDays(7)->toDateString(),
            now()->toDateString(),
            'time_decay'
        );

        $this->assertEquals('time_decay', $result['model']);

        $roi = $this->service->calculateChannelROI('google');

        // With $1000 revenue and $5000 spend (mock), ROI should be 0.2
        $this->assertEquals(0.2, $roi);
    }

    /**
     * Test budget recommendations generation
     */
    public function test_generate_budget_recommendations_returns_allocations(): void
    {
        // Create touchpoints for multiple channels
        $channels = ['google', 'facebook', 'email'];

        foreach ($channels as $channel) {
            AttributionTouch::factory()->count(5)->create([
                'tenant_id' => $this->tenant->id,
                'channel' => $channel,
                'timestamp' => now()->subDays(rand(1, 90)),
                'conversion_value' => 1000.00, // High conversion value for good ROI
            ]);
        }

        $recommendations = $this->service->generateBudgetRecommendations();

        $this->assertIsArray($recommendations['channels']);
        $this->assertIsFloat($recommendations['total_current_spend']);
        $this->assertIsFloat($recommendations['total_recommended_spend']);
        $this->assertIsFloat($recommendations['overall_change_percentage']);

        foreach ($channels as $channel) {
            $this->assertArrayHasKey($channel, $recommendations['channels']);
            $this->assertArrayHasKey('current_spend', $recommendations['channels'][$channel]);
            $this->assertArrayHasKey('recommended_spend', $recommendations['channels'][$channel]);
            $this->assertArrayHasKey('change_percentage', $recommendations['channels'][$channel]);
            $this->assertArrayHasKey('roi', $recommendations['channels'][$channel]);
            $this->assertArrayHasKey('priority', $recommendations['channels'][$channel]);
        }
    }

    /**
     * Test conversion path analysis
     */
    public function test_analyze_conversion_paths_identifies_common_sequences(): void
    {
        // Create multiple users with similar conversion paths
        for ($i = 0; $i < 5; $i++) {
            $user = User::factory()->create();

            // Path: google -> facebook -> email -> conversion
            AttributionTouch::factory()->create([
                'tenant_id' => $this->tenant->id,
                'user_id' => $user->id,
                'channel' => 'google',
                'timestamp' => now()->subDays(10),
            ]);

            AttributionTouch::factory()->create([
                'tenant_id' => $this->tenant->id,
                'user_id' => $user->id,
                'channel' => 'facebook',
                'timestamp' => now()->subDays(5),
            ]);

            AttributionTouch::factory()->create([
                'tenant_id' => $this->tenant->id,
                'user_id' => $user->id,
                'channel' => 'email',
                'timestamp' => now()->subDays(1),
                'conversion_value' => 100.00,
            ]);
        }

        $analysis = $this->service->analyzeConversionPaths();

        $this->assertGreaterThanOrEqual(5, $analysis['total_converted_users']);
        $this->assertGreaterThan(0, $analysis['unique_paths']);
        $this->assertIsArray($analysis['top_paths']);

        // The most common path should be "google -> facebook -> email"
        $topPath = array_key_first($analysis['top_paths']);
        $this->assertStringContainsString('google -> facebook -> email', $topPath);
    }

    /**
     * Test edge case: user with no touchpoints
     */
    public function test_calculate_attribution_handles_empty_touchpoints(): void
    {
        $result = $this->service->calculateAttribution($this->user->id);

        $this->assertEquals(0, $result['total_touchpoints']);
        $this->assertEmpty($result['attribution']);
        $this->assertEmpty($result['touchpoints']);
    }

    /**
     * Test edge case: single touchpoint attribution
     */
    public function test_single_touchpoint_attribution_models(): void
    {
        AttributionTouch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'channel' => 'direct',
            'timestamp' => now(),
        ]);

        $firstClick = $this->service->calculateAttribution($this->user->id, 'first-click');
        $lastClick = $this->service->calculateAttribution($this->user->id, 'last-click');
        $linear = $this->service->calculateAttribution($this->user->id, 'linear');

        $this->assertEquals(['direct' => 1.0], $firstClick['attribution']);
        $this->assertEquals(['direct' => 1.0], $lastClick['attribution']);
        $this->assertEquals(['direct' => 1.0], $linear['attribution']);
    }

    /**
     * Test invalid attribution model defaults to linear
     */
    public function test_invalid_attribution_model_defaults_to_linear(): void
    {
        AttributionTouch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'channel' => 'google',
            'timestamp' => now()->subDays(1),
        ]);

        AttributionTouch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'channel' => 'facebook',
            'timestamp' => now()->subDays(2),
        ]);

        $result = $this->service->calculateAttribution($this->user->id, 'invalid-model');

        $this->assertEquals('invalid-model', $result['attribution_model']);
        $this->assertEquals(['google' => 0.5, 'facebook' => 0.5], $result['attribution']);
    }

    /**
     * Test tenant isolation in attribution calculations
     */
    public function test_tenant_isolation_in_attribution_calculations(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create();

        // Create touchpoints for both tenants
        AttributionTouch::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'channel' => 'google',
        ]);

        AttributionTouch::factory()->create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $otherUser->id,
            'channel' => 'facebook',
        ]);

        $result = $this->service->calculateAttribution($this->user->id);

        // Should only see touchpoints from current tenant
        $this->assertEquals(1, $result['total_touchpoints']);
        $this->assertEquals(['google' => 1.0], $result['attribution']);
    }

    /**
     * Test identify top paths returns limited results
     */
    public function test_identify_top_paths_respects_limit(): void
    {
        // Create multiple different paths
        for ($i = 0; $i < 15; $i++) {
            $user = User::factory()->create();

            AttributionTouch::factory()->create([
                'tenant_id' => $this->tenant->id,
                'user_id' => $user->id,
                'channel' => 'channel_' . $i,
                'timestamp' => now()->subDays(1),
                'conversion_value' => 100.00,
            ]);
        }

        $topPaths = $this->service->identifyTopPaths(5);

        $this->assertLessThanOrEqual(5, count($topPaths));
    }

    /**
     * Test time-decay with custom decay rate
     */
    public function test_time_decay_with_custom_decay_rate(): void
    {
        $touchpoints = collect([
            AttributionTouch::factory()->create([
                'tenant_id' => $this->tenant->id,
                'user_id' => $this->user->id,
                'channel' => 'old_channel',
                'timestamp' => now()->subDays(10),
            ]),
            AttributionTouch::factory()->create([
                'tenant_id' => $this->tenant->id,
                'user_id' => $this->user->id,
                'channel' => 'new_channel',
                'timestamp' => now()->subDays(1),
            ]),
        ]);

        $highDecay = $this->service->applyTimeDecayModel($touchpoints, 0.9);
        $lowDecay = $this->service->applyTimeDecayModel($touchpoints, 0.1);

        // With lower decay rate (0.1), the weighting difference should be more pronounced
        // (recent touchpoints get much higher weight compared to older ones)
        $this->assertGreaterThan(
            $lowDecay['new_channel'] / $lowDecay['old_channel'],
            $highDecay['new_channel'] / $highDecay['old_channel']
        );
    }
}