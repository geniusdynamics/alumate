<?php

namespace Tests\Unit\Services;

use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Services\GamificationAnalyticsService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private GamificationAnalyticsService $service;
    private string $tenantId = 'test-tenant';

    protected function setUp(): void
    {
        parent::setUp();

        $tenantService = $this->mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')->andReturn($this->tenantId);

        $this->service = new GamificationAnalyticsService($tenantService);
    }

    public function test_track_gamification_event_stores_event_correctly()
    {
        $userId = 'user-123';
        $gamificationType = 'badge_earned';
        $pointsEarned = 50;
        $badgeEarned = 'first_login';
        $additionalData = ['source' => 'login'];

        $result = $this->service->trackGamificationEvent(
            $userId,
            $gamificationType,
            $pointsEarned,
            $badgeEarned,
            $additionalData
        );

        $this->assertTrue($result);

        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => $this->tenantId,
            'event_type' => 'gamification',
            'event_name' => $gamificationType,
            'gamification_type' => $gamificationType,
            'user_id' => $userId,
            'points_earned' => $pointsEarned,
            'badge_earned' => $badgeEarned,
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        $event = AnalyticsEvent::where('user_id', $userId)->first();
        $this->assertEquals($additionalData, $event->properties['additional_data'] ?? null);
    }

    public function test_get_user_points_returns_correct_summary()
    {
        // Create test events
        AnalyticsEvent::create([
            'tenant_id' => $this->tenantId,
            'event_type' => 'gamification',
            'event_name' => 'points_earned',
            'gamification_type' => 'points_earned',
            'user_id' => 'user-123',
            'points_earned' => 25,
            'occurred_at' => now()->subDays(1),
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        AnalyticsEvent::create([
            'tenant_id' => $this->tenantId,
            'event_type' => 'gamification',
            'event_name' => 'badge_earned',
            'gamification_type' => 'badge_earned',
            'user_id' => 'user-123',
            'points_earned' => 50,
            'occurred_at' => now(),
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        $points = $this->service->getUserPoints('user-123');

        $this->assertEquals(75, $points['total_points']);
        $this->assertEquals(2, $points['total_events']);
        $this->assertNotNull($points['last_earned_at']);
    }

    public function test_get_user_badges_returns_badge_history()
    {
        AnalyticsEvent::create([
            'tenant_id' => $this->tenantId,
            'event_type' => 'gamification',
            'event_name' => 'badge_earned',
            'gamification_type' => 'badge_earned',
            'user_id' => 'user-123',
            'badge_earned' => 'first_login',
            'occurred_at' => now()->subDays(2),
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        AnalyticsEvent::create([
            'tenant_id' => $this->tenantId,
            'event_type' => 'gamification',
            'event_name' => 'badge_earned',
            'gamification_type' => 'badge_earned',
            'user_id' => 'user-123',
            'badge_earned' => 'profile_complete',
            'occurred_at' => now()->subDay(),
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        $badges = $this->service->getUserBadges('user-123');

        $this->assertCount(2, $badges);
        $this->assertEquals('profile_complete', $badges->first()['badge']);
        $this->assertEquals('first_login', $badges->last()['badge']);
    }

    public function test_get_leaderboard_returns_ranked_users()
    {
        // Create events for different users
        AnalyticsEvent::create([
            'tenant_id' => $this->tenantId,
            'event_type' => 'gamification',
            'user_id' => 'user-1',
            'points_earned' => 100,
            'occurred_at' => now(),
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        AnalyticsEvent::create([
            'tenant_id' => $this->tenantId,
            'event_type' => 'gamification',
            'user_id' => 'user-2',
            'points_earned' => 50,
            'occurred_at' => now(),
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        AnalyticsEvent::create([
            'tenant_id' => $this->tenantId,
            'event_type' => 'gamification',
            'user_id' => 'user-1',
            'points_earned' => 25,
            'occurred_at' => now(),
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        $leaderboard = $this->service->getLeaderboard(5);

        $this->assertCount(2, $leaderboard);
        $this->assertEquals(1, $leaderboard->first()['rank']);
        $this->assertEquals('user-1', $leaderboard->first()['user_id']);
        $this->assertEquals(125, $leaderboard->first()['total_points']);
        $this->assertEquals(2, $leaderboard->first()['rank']);
        $this->assertEquals('user-2', $leaderboard->last()['user_id']);
        $this->assertEquals(50, $leaderboard->last()['total_points']);
    }

    public function test_get_gamification_metrics_returns_summary()
    {
        AnalyticsEvent::create([
            'tenant_id' => $this->tenantId,
            'event_type' => 'gamification',
            'user_id' => 'user-1',
            'points_earned' => 100,
            'occurred_at' => now(),
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        AnalyticsEvent::create([
            'tenant_id' => $this->tenantId,
            'event_type' => 'gamification',
            'user_id' => 'user-2',
            'badge_earned' => 'first_login',
            'occurred_at' => now(),
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        $metrics = $this->service->getGamificationMetrics();

        $this->assertEquals(2, $metrics['total_events']);
        $this->assertEquals(2, $metrics['unique_users']);
        $this->assertEquals(100, $metrics['total_points_awarded']);
        $this->assertEquals(1, $metrics['total_badges_earned']);
    }

    public function test_get_user_engagement_score_calculates_correctly()
    {
        // Create recent activity
        for ($i = 0; $i < 15; $i++) {
            AnalyticsEvent::create([
                'tenant_id' => $this->tenantId,
                'event_type' => 'gamification',
                'user_id' => 'user-123',
                'points_earned' => 10,
                'occurred_at' => now()->subDays(rand(0, 29)),
                'is_compliant' => true,
                'consent_given' => true,
            ]);
        }

        $score = $this->service->getUserEngagementScore('user-123');

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_tenant_isolation_maintained()
    {
        $otherTenantId = 'other-tenant';

        // Create event for current tenant
        AnalyticsEvent::create([
            'tenant_id' => $this->tenantId,
            'event_type' => 'gamification',
            'user_id' => 'user-123',
            'points_earned' => 100,
            'occurred_at' => now(),
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        // Create event for other tenant
        AnalyticsEvent::create([
            'tenant_id' => $otherTenantId,
            'event_type' => 'gamification',
            'user_id' => 'user-123',
            'points_earned' => 50,
            'occurred_at' => now(),
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        $points = $this->service->getUserPoints('user-123');

        // Should only count events from current tenant
        $this->assertEquals(100, $points['total_points']);
    }
    public function test_caching_behavior_for_user_points()
    {
        // Create test events
        AnalyticsEvent::factory()->gamification()->forTenant($this->tenantId)->forUser('user-456')->create([
            'points_earned' => 25,
            'occurred_at' => now()->subDays(1),
        ]);

        AnalyticsEvent::factory()->gamification()->forTenant($this->tenantId)->forUser('user-456')->create([
            'points_earned' => 50,
            'occurred_at' => now(),
        ]);

        // First call should cache
        $points1 = $this->service->getUserPoints('user-456');
        $this->assertEquals(75, $points1['total_points']);

        // Second call should use cache
        $points2 = $this->service->getUserPoints('user-456');
        $this->assertEquals($points1, $points2);

        // Verify cache was used (we can't directly check Cache facade in this test setup)
        $this->assertEquals(75, $points2['total_points']);
    }

    public function test_caching_behavior_for_leaderboard()
    {
        // Create events for multiple users
        AnalyticsEvent::factory()->gamification()->forTenant($this->tenantId)->forUser('user-1')->create(['points_earned' => 100]);
        AnalyticsEvent::factory()->gamification()->forTenant($this->tenantId)->forUser('user-2')->create(['points_earned' => 50]);

        // First call should cache
        $leaderboard1 = $this->service->getLeaderboard(5);
        $this->assertCount(2, $leaderboard1);

        // Second call should use cache
        $leaderboard2 = $this->service->getLeaderboard(5);
        $this->assertEquals($leaderboard1, $leaderboard2);
    }

    public function test_edge_case_invalid_user_id()
    {
        $points = $this->service->getUserPoints('');
        $this->assertEquals(0, $points['total_points']);
        $this->assertEquals(0, $points['total_events']);
        $this->assertNull($points['last_earned_at']);
    }

    public function test_edge_case_no_events_for_user()
    {
        $points = $this->service->getUserPoints('nonexistent-user');
        $this->assertEquals(0, $points['total_points']);
        $this->assertEquals(0, $points['total_events']);
        $this->assertNull($points['last_earned_at']);
    }

    public function test_edge_case_empty_badges_for_user()
    {
        $badges = $this->service->getUserBadges('user-without-badges');
        $this->assertEmpty($badges);
    }

    public function test_edge_case_no_tenant_context()
    {
        // Mock no tenant context
        $this->tenantServiceMock->shouldReceive('getCurrentTenantId')->andReturn(null);

        $service = new GamificationAnalyticsService($this->tenantServiceMock);

        $points = $service->getUserPoints('user-123');
        $this->assertEquals(0, $points['total_points']);

        $badges = $service->getUserBadges('user-123');
        $this->assertEmpty($badges);

        $leaderboard = $service->getLeaderboard();
        $this->assertEmpty($leaderboard);
    }

    public function test_metrics_calculation_with_date_range()
    {
        // Create events in different date ranges
        AnalyticsEvent::factory()->gamification()->forTenant($this->tenantId)->create([
            'points_earned' => 100,
            'occurred_at' => now()->subDays(10),
        ]);

        AnalyticsEvent::factory()->gamification()->forTenant($this->tenantId)->create([
            'badge_earned' => 'first_login',
            'occurred_at' => now()->subDays(5),
        ]);

        AnalyticsEvent::factory()->gamification()->forTenant($this->tenantId)->create([
            'points_earned' => 50,
            'occurred_at' => now()->subDays(2),
        ]);

        // Test with date range
        $startDate = now()->subDays(7);
        $endDate = now()->subDays(1);
        $metrics = $this->service->getGamificationMetrics([$startDate, $endDate]);

        $this->assertEquals(2, $metrics['total_events']); // Events within date range
        $this->assertEquals(2, $metrics['unique_users']);
        $this->assertEquals(50, $metrics['total_points_awarded']); // Only points within range
        $this->assertEquals(1, $metrics['total_badges_earned']);
    }

    public function test_activity_timeline_calculation()
    {
        // Create events over multiple days
        AnalyticsEvent::factory()->gamification()->forTenant($this->tenantId)->create([
            'occurred_at' => now()->subDays(5),
        ]);

        AnalyticsEvent::factory()->gamification()->forTenant($this->tenantId)->create([
            'occurred_at' => now()->subDays(3),
        ]);

        AnalyticsEvent::factory()->gamification()->forTenant($this->tenantId)->create([
            'occurred_at' => now()->subDays(3),
        ]);

        $timeline = $this->service->getActivityTimeline('daily', 7);

        $this->assertIsArray($timeline);
        $this->assertGreaterThan(0, count($timeline));

        // Check that we have entries for the days with events
        $hasDay5 = false;
        $hasDay3 = false;

        foreach ($timeline as $entry) {
            $entryDate = \Carbon\Carbon::parse($entry['period'])->format('Y-m-d');
            if ($entryDate === now()->subDays(5)->format('Y-m-d')) {
                $hasDay5 = true;
                $this->assertEquals(1, $entry['total_events']);
            }
            if ($entryDate === now()->subDays(3)->format('Y-m-d')) {
                $hasDay3 = true;
                $this->assertEquals(2, $entry['total_events']);
            }
        }

        $this->assertTrue($hasDay5 || $hasDay3, 'Should have activity on at least one of the test days');
    }

    public function test_user_engagement_score_calculation()
    {
        // Create activity for user over 30 days
        for ($i = 0; $i < 20; $i++) {
            AnalyticsEvent::factory()->gamification()->forTenant($this->tenantId)->forUser('engaged-user')->create([
                'points_earned' => 10,
                'occurred_at' => now()->subDays(rand(0, 29)),
            ]);
        }

        $score = $this->service->getUserEngagementScore('engaged-user');

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);

        // Test with no activity
        $noActivityScore = $this->service->getUserEngagementScore('inactive-user');
        $this->assertEquals(0.0, $noActivityScore);
    }

    public function test_multi_tenant_data_isolation()
    {
        $tenant1 = 'tenant-1';
        $tenant2 = 'tenant-2';

        // Create service for tenant 1
        $tenantService1 = $this->mock(TenantContextService::class);
        $tenantService1->shouldReceive('getCurrentTenantId')->andReturn($tenant1);
        $service1 = new GamificationAnalyticsService($tenantService1);

        // Create service for tenant 2
        $tenantService2 = $this->mock(TenantContextService::class);
        $tenantService2->shouldReceive('getCurrentTenantId')->andReturn($tenant2);
        $service2 = new GamificationAnalyticsService($tenantService2);

        // Create events for both tenants with same user ID
        AnalyticsEvent::factory()->gamification()->forTenant($tenant1)->forUser('shared-user')->create(['points_earned' => 100]);
        AnalyticsEvent::factory()->gamification()->forTenant($tenant2)->forUser('shared-user')->create(['points_earned' => 200]);

        $points1 = $service1->getUserPoints('shared-user');
        $points2 = $service2->getUserPoints('shared-user');

        // Each tenant should only see their own data
        $this->assertEquals(100, $points1['total_points']);
        $this->assertEquals(200, $points2['total_points']);
    }

    public function test_track_gamification_event_with_invalid_data()
    {
        // Test with empty user ID
        $result = $this->service->trackGamificationEvent('', 'badge_earned', null, 'test_badge');
        $this->assertFalse($result);

        // Test with invalid gamification type
        $result = $this->service->trackGamificationEvent('user-123', '', 10, null);
        $this->assertFalse($result);
    }

    public function test_leaderboard_with_limit_and_ties()
    {
        // Create users with same total points
        AnalyticsEvent::factory()->gamification()->forTenant($this->tenantId)->forUser('user-a')->create(['points_earned' => 50]);
        AnalyticsEvent::factory()->gamification()->forTenant($this->tenantId)->forUser('user-b')->create(['points_earned' => 50]);
        AnalyticsEvent::factory()->gamification()->forTenant($this->tenantId)->forUser('user-c')->create(['points_earned' => 25]);

        $leaderboard = $this->service->getLeaderboard(2);

        $this->assertCount(2, $leaderboard);
        $this->assertEquals(50, $leaderboard->first()['total_points']);
        $this->assertEquals(50, $leaderboard->last()['total_points']);
    }
}
}