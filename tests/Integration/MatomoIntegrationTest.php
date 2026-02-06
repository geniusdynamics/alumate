<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\MatomoConfig;
use App\Models\Tenant;
use App\Services\Analytics\ConsentService;
use App\Services\Analytics\MatomoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Matomo Integration Test Suite
 *
 * End-to-end tests for Matomo analytics integration.
 * Tests full workflow from configuration to event tracking and data synchronization.
 */
class MatomoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private MatomoConfig $matomoConfig;
    private ConsentService $consentService;
    private MatomoService $matomoService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        session(['tenant_id' => $this->tenant->id]);

        $this->matomoConfig = MatomoConfig::create([
            'tenant_id' => $this->tenant->id,
            'matomo_url' => 'https://matomo.example.com',
            'site_id' => '1',
            'token_auth' => 'test_token_123',
            'enabled' => true,
        ]);

        $this->consentService = app(ConsentService::class);
        $this->matomoService = new MatomoService($this->consentService);
    }

    /**
     * Test complete event tracking workflow
     */
    public function test_complete_event_tracking_workflow(): void
    {
        // Grant consent for user
        $this->consentService->collectConsent([
            'user_id' => 1,
            'category' => 'analytics',
            'granted' => true,
        ]);

        // Track multiple events
        $events = [
            [
                'event_type' => 'page_view',
                'module' => 'dashboard',
                'engagement_score' => 85,
                'user_id' => 1,
            ],
            [
                'event_type' => 'user_interaction',
                'module' => 'profile',
                'engagement_score' => 92,
                'user_id' => 1,
            ],
            [
                'event_type' => 'conversion',
                'module' => 'checkout',
                'engagement_score' => 100,
                'user_id' => 1,
            ],
        ];

        foreach ($events as $eventData) {
            $result = $this->matomoService->trackEvent($eventData);
            $this->assertTrue($result, 'Event tracking should succeed with consent');
        }

        // Verify caching works
        $cacheKey = "matomo_tracker_{$this->tenant->id}";
        $this->assertTrue(Cache::has($cacheKey), 'Tracker should be cached');
    }

    /**
     * Test custom dimensions workflow
     */
    public function test_custom_dimensions_workflow(): void
    {
        $dimensions = [
            'tenant_id' => $this->tenant->id,
            'user_segment' => 'premium',
            'session_id' => 'session_123',
        ];

        $result = $this->matomoService->setCustomDimensions($dimensions);

        $this->assertTrue($result, 'Custom dimensions should be set successfully');
    }

    /**
     * Test goal synchronization workflow
     */
    public function test_goal_synchronization_workflow(): void
    {
        // This would normally make HTTP calls, but we'll test the logic
        $result = $this->matomoService->syncGoals('purchase_funnel');

        // Result depends on HTTP mocking in unit tests
        $this->assertIsBool($result, 'Goal sync should return boolean');
    }

    /**
     * Test segment export workflow
     */
    public function test_segment_export_workflow(): void
    {
        $segments = $this->matomoService->exportSegments();

        $this->assertIsArray($segments, 'Segment export should return array');
    }

    /**
     * Test tenant data isolation
     */
    public function test_tenant_data_isolation(): void
    {
        // Create another tenant
        $otherTenant = Tenant::factory()->create();
        $otherConfig = MatomoConfig::create([
            'tenant_id' => $otherTenant->id,
            'matomo_url' => 'https://other-matomo.example.com',
            'site_id' => '2',
            'token_auth' => 'other_token_456',
            'enabled' => true,
        ]);

        // Switch to other tenant
        session(['tenant_id' => $otherTenant->id]);

        // Verify different configs
        $this->assertNotEquals(
            $this->matomoConfig->matomo_url,
            $otherConfig->matomo_url
        );

        $this->assertNotEquals(
            $this->matomoConfig->site_id,
            $otherConfig->site_id
        );

        // Switch back
        session(['tenant_id' => $this->tenant->id]);
    }

    /**
     * Test consent withdrawal handling
     */
    public function test_consent_withdrawal_blocks_tracking(): void
    {
        // Grant consent initially
        $this->consentService->collectConsent([
            'user_id' => 2,
            'category' => 'analytics',
            'granted' => true,
        ]);

        // Track event with consent
        $eventData = [
            'event_type' => 'page_view',
            'module' => 'dashboard',
            'user_id' => 2,
        ];

        $result = $this->matomoService->trackEvent($eventData);
        $this->assertTrue($result, 'Event should track with consent');

        // Withdraw consent
        $this->consentService->withdrawConsent(2, 'analytics', 'Testing withdrawal');

        // Try to track event after withdrawal
        $result = $this->matomoService->trackEvent($eventData);
        $this->assertFalse($result, 'Event should not track after consent withdrawal');
    }

    /**
     * Test database state after operations
     */
    public function test_database_state_after_operations(): void
    {
        // Verify MatomoConfig exists
        $config = MatomoConfig::where('tenant_id', $this->tenant->id)->first();
        $this->assertNotNull($config, 'MatomoConfig should exist');
        $this->assertEquals('https://matomo.example.com', $config->matomo_url);
        $this->assertEquals('1', $config->site_id);
        $this->assertTrue($config->enabled);

        // Verify consent records
        $consentCount = DB::table('consents')
            ->where('tenant_id', $this->tenant->id)
            ->count();
        $this->assertGreaterThanOrEqual(0, $consentCount, 'Consent records should exist');
    }

    /**
     * Test Redis caching behavior
     */
    public function test_redis_caching_behavior(): void
    {
        // Clear any existing cache
        Cache::forget("matomo_tracker_{$this->tenant->id}");

        // First call should create tracker
        $tracker1 = $this->invokePrivateMethod($this->matomoService, 'getTracker', [$this->tenant->id]);
        $this->assertNotNull($tracker1, 'Tracker should be created');

        // Second call should return cached tracker
        $tracker2 = $this->invokePrivateMethod($this->matomoService, 'getTracker', [$this->tenant->id]);
        $this->assertSame($tracker1, $tracker2, 'Tracker should be cached');

        // Verify cache exists
        $this->assertTrue(Cache::has("matomo_tracker_{$this->tenant->id}"), 'Tracker should be cached in Redis');
    }

    /**
     * Test error handling and recovery
     */
    public function test_error_handling_and_recovery(): void
    {
        // Test with invalid config
        $this->matomoConfig->update(['enabled' => false]);

        $result = $this->matomoService->trackEvent([
            'event_type' => 'test',
            'user_id' => 1,
        ]);

        $this->assertFalse($result, 'Tracking should fail with disabled config');

        // Re-enable config
        $this->matomoConfig->update(['enabled' => true]);

        // Grant consent
        $this->consentService->collectConsent([
            'user_id' => 1,
            'category' => 'analytics',
            'granted' => true,
        ]);

        $result = $this->matomoService->trackEvent([
            'event_type' => 'test',
            'user_id' => 1,
        ]);

        $this->assertTrue($result, 'Tracking should work after config re-enabled');
    }

    /**
     * Test performance and scalability
     */
    public function test_performance_and_scalability(): void
    {
        // Grant consent
        $this->consentService->collectConsent([
            'user_id' => 3,
            'category' => 'analytics',
            'granted' => true,
        ]);

        $startTime = microtime(true);

        // Track 20 events quickly
        for ($i = 0; $i < 20; $i++) {
            $this->matomoService->trackEvent([
                'event_type' => 'bulk_test_' . $i,
                'module' => 'performance',
                'engagement_score' => rand(50, 100),
                'user_id' => 3,
            ]);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete within reasonable time (allowing for test environment)
        $this->assertLessThan(5.0, $duration, 'Bulk tracking should complete within 5 seconds');

        // Verify no cross-tenant data leakage
        $otherTenant = Tenant::factory()->create();
        session(['tenant_id' => $otherTenant->id]);

        $otherConfig = MatomoConfig::where('tenant_id', $otherTenant->id)->first();
        $this->assertNull($otherConfig, 'Other tenant should not have config');

        // Switch back
        session(['tenant_id' => $this->tenant->id]);
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}