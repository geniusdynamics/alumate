<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AttributionTouch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Attribution API Feature Tests
 *
 * Tests the attribution analysis API endpoints including touchpoint tracking,
 * user journey analysis, channel performance, and budget recommendations.
 */
class AttributionApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test touchpoint tracking endpoint
     */
    public function test_track_touchpoint_success(): void
    {
        $user = User::factory()->create();
        $sessionId = (string) Str::uuid();

        $touchpointData = [
            'session_id' => $sessionId,
            'touch_type' => 'email',
            'channel' => 'google',
            'value' => 75.0,
            'metadata' => ['campaign_id' => 'summer_2024'],
            'conversion_value' => 150.00,
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/analytics/attribution/track-touch', $touchpointData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'touchpoint' => [
                    'id',
                    'channel',
                    'touch_type',
                    'timestamp',
                    'value',
                ],
            ]);

        $this->assertDatabaseHas('attribution_touches', [
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'touch_type' => 'email',
            'channel' => 'google',
            'value' => 75.0,
            'conversion_value' => 150.00,
        ]);
    }

    /**
     * Test touchpoint tracking validation
     */
    public function test_track_touchpoint_validation(): void
    {
        $user = User::factory()->create();

        $invalidData = [
            'session_id' => 'not-a-uuid',
            'touch_type' => 'invalid_type',
            'channel' => 'invalid_channel',
            'value' => 150, // Over max
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/analytics/attribution/track-touch', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'errors' => [
                    'session_id',
                    'touch_type',
                    'channel',
                    'value',
                ],
            ]);
    }

    /**
     * Test user attribution endpoint
     */
    public function test_get_user_attribution(): void
    {
        $user = User::factory()->create();

        // Create touchpoints for the user
        AttributionTouch::factory()->count(5)->create([
            'user_id' => $user->id,
            'channel' => 'google',
            'touch_type' => 'ad',
        ]);

        AttributionTouch::factory()->count(3)->create([
            'user_id' => $user->id,
            'channel' => 'facebook',
            'touch_type' => 'social',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/analytics/attribution/models/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'user_id',
                'attributions' => [
                    'first-click',
                    'last-click',
                    'linear',
                    'time-decay',
                ],
                'model_comparison',
            ]);
    }

    /**
     * Test channel performance endpoint
     */
    public function test_get_channel_performance(): void
    {
        $user = User::factory()->create();

        // Create touchpoints across different channels
        $channels = ['google', 'facebook', 'linkedin', 'organic', 'direct'];

        foreach ($channels as $channel) {
            AttributionTouch::factory()->count(10)->create([
                'channel' => $channel,
                'conversion_value' => rand(50, 500),
            ]);
        }

        $response = $this->actingAs($user)
            ->getJson('/api/analytics/attribution/channels');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'performance',
                'period',
                'summary' => [
                    'total_conversions',
                    'total_conversion_value',
                    'average_engagement',
                    'roi_distribution',
                    'best_performing_channel',
                ],
            ]);
    }

    /**
     * Test budget recommendations endpoint
     */
    public function test_get_budget_recommendations(): void
    {
        $user = User::factory()->create();

        // Create touchpoints with conversion data
        AttributionTouch::factory()->count(50)->create([
            'channel' => 'google',
            'conversion_value' => 200,
        ]);

        AttributionTouch::factory()->count(30)->create([
            'channel' => 'facebook',
            'conversion_value' => 150,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/analytics/attribution/budget-recommendations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'recommendations' => [
                    'channels',
                    'total_current_spend',
                    'total_recommended_spend',
                    'overall_change_percentage',
                    'generated_at',
                ],
                'insights',
            ]);
    }

    /**
     * Test complete user journey simulation
     */
    public function test_complete_user_journey_simulation(): void
    {
        $user = User::factory()->create();
        $sessionId = (string) Str::uuid();

        // Simulate a complete user journey with varied touchpoints
        $journey = [
            ['touch_type' => 'ad', 'channel' => 'google', 'value' => 60.0],
            ['touch_type' => 'social', 'channel' => 'facebook', 'value' => 45.0],
            ['touch_type' => 'email', 'channel' => 'google', 'value' => 80.0, 'conversion_value' => 299.99],
            ['touch_type' => 'direct', 'channel' => 'direct', 'value' => 90.0],
            ['touch_type' => 'referral', 'channel' => 'organic', 'value' => 70.0],
        ];

        foreach ($journey as $touchpoint) {
            $this->actingAs($user)
                ->postJson('/api/analytics/attribution/track-touch', array_merge($touchpoint, [
                    'session_id' => $sessionId,
                ]));
        }

        // Verify attribution calculation
        $response = $this->actingAs($user)
            ->getJson("/api/analytics/attribution/models/{$user->id}");

        $response->assertStatus(200);

        $attributions = $response->json('attributions');

        // Verify all models are calculated
        $this->assertArrayHasKey('first-click', $attributions);
        $this->assertArrayHasKey('last-click', $attributions);
        $this->assertArrayHasKey('linear', $attributions);
        $this->assertArrayHasKey('time-decay', $attributions);

        // Verify first-click gives 100% to first touchpoint (google)
        $this->assertEquals(1.0, $attributions['first-click']['attribution']['google'] ?? 0);

        // Verify last-click gives 100% to last touchpoint (organic)
        $this->assertEquals(1.0, $attributions['last-click']['attribution']['organic'] ?? 0);

        // Verify linear model distributes equally
        $linearWeights = $attributions['linear']['attribution'];
        $this->assertCount(4, $linearWeights); // 4 unique channels
        $this->assertEquals(1.0, array_sum($linearWeights));
    }

    /**
     * Test attribution model comparison
     */
    public function test_attribution_model_comparison(): void
    {
        $user = User::factory()->create();

        // Create a simple journey: google -> facebook -> conversion
        $this->actingAs($user)->postJson('/api/analytics/attribution/track-touch', [
            'session_id' => (string) Str::uuid(),
            'touch_type' => 'ad',
            'channel' => 'google',
            'value' => 50.0,
        ]);

        $this->actingAs($user)->postJson('/api/analytics/attribution/track-touch', [
            'session_id' => (string) Str::uuid(),
            'touch_type' => 'social',
            'channel' => 'facebook',
            'value' => 60.0,
            'conversion_value' => 199.99,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/analytics/attribution/models/{$user->id}");

        $response->assertStatus(200);

        $comparison = $response->json('model_comparison');

        // Verify comparison structure
        $this->assertArrayHasKey('model_weights', $comparison);
        $this->assertArrayHasKey('differences', $comparison);

        // Verify differences are calculated
        $this->assertArrayHasKey('first-click_vs_linear', $comparison['differences']);
        $this->assertArrayHasKey('last-click_vs_linear', $comparison['differences']);
        $this->assertArrayHasKey('time-decay_vs_linear', $comparison['differences']);
    }

    /**
     * Test channel performance with ROI calculations
     */
    public function test_channel_performance_with_roi(): void
    {
        $user = User::factory()->create();

        // Create touchpoints with high conversion values for google
        AttributionTouch::factory()->count(20)->create([
            'channel' => 'google',
            'conversion_value' => 300, // High value
        ]);

        // Create touchpoints with lower conversion values for facebook
        AttributionTouch::factory()->count(20)->create([
            'channel' => 'facebook',
            'conversion_value' => 100, // Lower value
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/analytics/attribution/channels');

        $response->assertStatus(200);

        $performance = $response->json('performance');

        // Google should have better ROI than Facebook
        $this->assertGreaterThan(
            $performance['facebook']['roi'] ?? 0,
            $performance['google']['roi'] ?? 0
        );

        // Verify ROI categories
        $this->assertContains($performance['google']['roi_category'], ['excellent', 'good', 'fair', 'poor', 'negative']);
        $this->assertContains($performance['facebook']['roi_category'], ['excellent', 'good', 'fair', 'poor', 'negative']);
    }

    /**
     * Test budget recommendations with ROI triggers
     */
    public function test_budget_recommendations_roi_triggers(): void
    {
        $user = User::factory()->create();

        // Create high-ROI channel (google)
        AttributionTouch::factory()->count(30)->create([
            'channel' => 'google',
            'conversion_value' => 500, // Very high ROI
        ]);

        // Create low-ROI channel (facebook)
        AttributionTouch::factory()->count(30)->create([
            'channel' => 'facebook',
            'conversion_value' => 50, // Low ROI
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/analytics/attribution/budget-recommendations');

        $response->assertStatus(200);

        $recommendations = $response->json('recommendations');

        // High ROI channel should get budget increase
        $googleRec = $recommendations['channels']['google'];
        $this->assertGreaterThan(0, $googleRec['change_percentage']);

        // Low ROI channel should get budget decrease
        $facebookRec = $recommendations['channels']['facebook'];
        $this->assertLessThan(0, $facebookRec['change_percentage']);
    }

    /**
     * Test error handling for invalid user ID
     */
    public function test_get_user_attribution_invalid_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/analytics/attribution/models/99999');

        $response->assertStatus(200); // Should return empty attribution data
    }

    /**
     * Test rate limiting on touchpoint tracking
     */
    public function test_touchpoint_tracking_rate_limiting(): void
    {
        $user = User::factory()->create();

        // Make multiple requests quickly (more than rate limit allows)
        for ($i = 0; $i < 120; $i++) {
            $response = $this->actingAs($user)
                ->postJson('/api/analytics/attribution/track-touch', [
                    'session_id' => (string) Str::uuid(),
                    'touch_type' => 'ad',
                    'channel' => 'google',
                    'value' => 50.0,
                ]);

            if ($i < 100) {
                $response->assertStatus(201);
            } else {
                // Should be rate limited after 100 requests
                $response->assertStatus(429);
                break;
            }
        }
    }

    /**
     * Test authentication requirements
     */
    public function test_attribution_endpoints_require_authentication(): void
    {
        // Test user attribution endpoint
        $this->getJson('/api/analytics/attribution/models/1')
            ->assertStatus(401);

        // Test channel performance endpoint
        $this->getJson('/api/analytics/attribution/channels')
            ->assertStatus(401);

        // Test budget recommendations endpoint
        $this->getJson('/api/analytics/attribution/budget-recommendations')
            ->assertStatus(401);
    }

    /**
     * Test touchpoint tracking allows unauthenticated requests
     */
    public function test_touchpoint_tracking_allows_unauthenticated(): void
    {
        $response = $this->postJson('/api/analytics/attribution/track-touch', [
            'session_id' => (string) Str::uuid(),
            'touch_type' => 'ad',
            'channel' => 'google',
            'value' => 50.0,
        ]);

        $response->assertStatus(201);
    }
}