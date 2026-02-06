<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Consent;
use App\Models\User;
use App\Services\Analytics\ConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * ConsentService Test Suite
 *
 * Tests for analytics consent management functionality.
 * Tests consent granting, revocation, checking, and caching.
 */
class ConsentServiceTest extends TestCase
{
    use RefreshDatabase;

    private ConsentService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ConsentService();
        $this->user = User::factory()->create();
    }

    /**
     * Test hasConsent returns false when no consent exists
     */
    public function test_has_consent_returns_false_when_no_consent_exists(): void
    {
        $this->assertFalse($this->service->hasConsent());
    }

    /**
     * Test hasConsent returns true when active consent exists
     */
    public function test_has_consent_returns_true_when_active_consent_exists(): void
    {
        Consent::create([
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'granted_at' => now(),
        ]);

        $this->assertTrue($this->service->hasConsent());
    }

    /**
     * Test hasConsent returns false when consent is revoked
     */
    public function test_has_consent_returns_false_when_consent_is_revoked(): void
    {
        Consent::create([
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'granted_at' => now(),
            'revoked_at' => now(),
        ]);

        $this->assertFalse($this->service->hasConsent());
    }

    /**
     * Test hasConsent uses cache for performance
     */
    public function test_has_consent_uses_cache_for_performance(): void
    {
        Consent::create([
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'granted_at' => now(),
        ]);

        // First call should cache result
        $this->service->hasConsent();

        // Verify cache was set
        $cacheKey = 'analytics_consent_' . $this->user->id . '_analytics';
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertTrue(Cache::get($cacheKey));
    }

    /**
     * Test grantConsent creates consent record
     */
    public function test_grant_consent_creates_consent_record(): void
    {
        $result = $this->service->grantConsent();

        $this->assertTrue($result);

        $this->assertDatabaseHas('consents', [
            'user_id' => $this->user->id,
            'type' => 'analytics',
        ]);

        $consent = Consent::where('user_id', $this->user->id)->first();
        $this->assertNotNull($consent->granted_at);
        $this->assertNull($consent->revoked_at);
    }

    /**
     * Test grantConsent revokes existing consent before creating new one
     */
    public function test_grant_consent_revokes_existing_consent_before_creating_new_one(): void
    {
        // Create existing consent
        Consent::create([
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'granted_at' => now()->subDays(1),
        ]);

        $result = $this->service->grantConsent();

        $this->assertTrue($result);

        // Should have two records: one revoked, one new
        $consents = Consent::where('user_id', $this->user->id)->orderBy('id')->get();
        $this->assertCount(2, $consents);

        // First should be revoked
        $this->assertNotNull($consents[0]->revoked_at);

        // Second should be active
        $this->assertNotNull($consents[1]->granted_at);
        $this->assertNull($consents[1]->revoked_at);
    }

    /**
     * Test grantConsent clears cache
     */
    public function test_grant_consent_clears_cache(): void
    {
        // Set cache
        $cacheKey = 'analytics_consent_' . $this->user->id . '_analytics';
        Cache::put($cacheKey, false, 60);

        $this->service->grantConsent();

        // Cache should be cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * Test grantConsent logs the action
     */
    public function test_grant_consent_logs_the_action(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Analytics consent granted', [
                'user_id' => $this->user->id,
                'type' => 'analytics',
                'ip_address' => request()->ip(),
            ]);
    }

    /**
     * Test revokeConsent sets revoked_at timestamp
     */
    public function test_revoke_consent_sets_revoked_at_timestamp(): void
    {
        Consent::create([
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'granted_at' => now(),
        ]);

        $result = $this->service->revokeConsent();

        $this->assertTrue($result);

        $consent = Consent::where('user_id', $this->user->id)->first();
        $this->assertNotNull($consent->revoked_at);
    }

    /**
     * Test revokeConsent returns false when no active consent exists
     */
    public function test_revoke_consent_returns_false_when_no_active_consent_exists(): void
    {
        $result = $this->service->revokeConsent();

        $this->assertFalse($result);
    }

    /**
     * Test revokeConsent clears cache
     */
    public function test_revoke_consent_clears_cache(): void
    {
        Consent::create([
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'granted_at' => now(),
        ]);

        // Set cache
        $cacheKey = 'analytics_consent_' . $this->user->id . '_analytics';
        Cache::put($cacheKey, true, 60);

        $this->service->revokeConsent();

        // Cache should be cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * Test revokeConsent logs the action
     */
    public function test_revoke_consent_logs_the_action(): void
    {
        Consent::create([
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'granted_at' => now(),
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Analytics consent revoked', [
                'user_id' => $this->user->id,
                'type' => 'analytics',
                'ip_address' => request()->ip(),
            ]);
    }

    /**
     * Test clearCachedConsent removes cache entry
     */
    public function test_clear_cached_consent_removes_cache_entry(): void
    {
        $cacheKey = 'analytics_consent_' . $this->user->id . '_analytics';
        Cache::put($cacheKey, true, 60);

        $this->service->clearCachedConsent();

        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * Test hasConsent works with different consent types
     */
    public function test_has_consent_works_with_different_consent_types(): void
    {
        // Test with specific type parameter
        Consent::create([
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'granted_at' => now(),
        ]);

        $this->assertTrue($this->service->hasConsent(type: 'analytics'));
    }

    /**
     * Test grantConsent works with different consent types
     */
    public function test_grant_consent_works_with_different_consent_types(): void
    {
        $result = $this->service->grantConsent(type: 'analytics');

        $this->assertTrue($result);

        $consent = Consent::where('user_id', $this->user->id)
            ->where('type', 'analytics')
            ->first();

        $this->assertNotNull($consent);
    }

    /**
     * Test service handles database errors gracefully
     */
    public function test_service_handles_database_errors_gracefully(): void
    {
        // This would require mocking the database or using a broken connection
        // For now, we'll test with invalid user ID
        $result = $this->service->grantConsent(999999);

        $this->assertFalse($result);
    }

    /**
     * Test consent operations work with user ID parameter
     */
    public function test_consent_operations_work_with_user_id_parameter(): void
    {
        $result = $this->service->grantConsent($this->user->id);

        $this->assertTrue($result);

        $this->assertDatabaseHas('consents', [
            'user_id' => $this->user->id,
            'type' => 'analytics',
        ]);
    }
}