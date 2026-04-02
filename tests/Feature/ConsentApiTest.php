<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_grant_consent_returns_success(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/consent/grant', [
                'type' => 'analytics'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Consent granted successfully'
            ]);

        // Verify consent was created in database
        $this->assertDatabaseHas('consents', [
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'revoked_at' => null
        ]);
    }

    public function test_grant_consent_with_invalid_type_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/consent/grant', [
                'type' => 'invalid_type'
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'errors' => [
                    'type'
                ]
            ]);
    }

    public function test_revoke_consent_returns_success(): void
    {
        // First grant consent
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/consent/grant', [
                'type' => 'analytics'
            ]);

        // Then revoke it
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/consent/revoke', [
                'type' => 'analytics'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Consent revoked successfully'
            ]);

        // Verify consent was revoked in database
        $this->assertDatabaseHas('consents', [
            'user_id' => $this->user->id,
            'type' => 'analytics'
        ]);

        $this->assertDatabaseMissing('consents', [
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'revoked_at' => null
        ]);
    }

    public function test_revoke_consent_with_invalid_type_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/consent/revoke', [
                'type' => 'invalid_type'
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'errors' => [
                    'type'
                ]
            ]);
    }

    public function test_grant_consent_unauthenticated_returns_unauthorized(): void
    {
        $response = $this->postJson('/api/consent/grant', [
            'type' => 'analytics'
        ]);

        $response->assertStatus(401);
    }

    public function test_revoke_consent_unauthenticated_returns_unauthorized(): void
    {
        $response = $this->postJson('/api/consent/revoke', [
            'type' => 'analytics'
        ]);

        $response->assertStatus(401);
    }

    public function test_consent_middleware_blocks_analytics_without_consent(): void
    {
        // Mock consent service to return false
        $mockConsentService = \Mockery::mock(\App\Services\Analytics\ConsentService::class);
        $mockConsentService->shouldReceive('hasConsent')
            ->with($this->user->id, 'analytics')
            ->andReturn(false);
        $this->app->instance(\App\Services\Analytics\ConsentService::class, $mockConsentService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/analytics/learning');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Consent required for data access']);
    }

    public function test_consent_middleware_allows_analytics_with_consent(): void
    {
        // Mock consent service to return true
        $mockConsentService = \Mockery::mock(\App\Services\Analytics\ConsentService::class);
        $mockConsentService->shouldReceive('hasConsent')
            ->with($this->user->id, 'analytics')
            ->andReturn(true);
        $this->app->instance(\App\Services\Analytics\ConsentService::class, $mockConsentService);

        // Mock learning service to return empty data
        $mockLearningService = \Mockery::mock(\App\Services\Analytics\LearningAnalyticsService::class);
        $mockLearningService->shouldReceive('getUserLearningProgress')
            ->andReturn([]);
        $this->app->instance(\App\Services\Analytics\LearningAnalyticsService::class, $mockLearningService);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/analytics/learning');

        $response->assertStatus(200);
    }

    public function test_consent_isolation_between_tenants(): void
    {
        // Create another user (simulated tenant isolation)
        $anotherUser = User::factory()->create();

        // Grant consent for first user
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/consent/grant', [
                'type' => 'analytics'
            ]);

        // Verify second user doesn't have consent
        $response = $this->actingAs($anotherUser, 'sanctum')
            ->getJson('/api/analytics/learning');

        // Should be blocked since no consent granted for this user
        $response->assertStatus(403);
    }

    public function test_grant_consent_rate_limited(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Make multiple requests to trigger rate limiting
        $responses = [];
        for ($i = 0; $i < 31; $i++) { // Assuming throttle:api is 30/min
            $response = $this->postJson('/api/consent/grant', [
                'type' => 'analytics'
            ]);
            $responses[] = $response;
        }

        $lastResponse = end($responses);
        $lastResponse->assertStatus(429); // Too Many Requests
    }

    public function test_revoke_consent_rate_limited(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Make multiple requests to trigger rate limiting
        $responses = [];
        for ($i = 0; $i < 31; $i++) { // Assuming throttle:api is 30/min
            $response = $this->postJson('/api/consent/revoke', [
                'type' => 'analytics'
            ]);
            $responses[] = $response;
        }

        $lastResponse = end($responses);
        $lastResponse->assertStatus(429); // Too Many Requests
    }

    public function test_grant_consent_with_missing_type_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/consent/grant', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'errors' => [
                    'type'
                ]
            ]);
    }

    public function test_revoke_consent_with_missing_type_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/consent/revoke', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'errors' => [
                    'type'
                ]
            ]);
    }
}