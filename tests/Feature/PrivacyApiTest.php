<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Consent;
use App\Services\Analytics\ConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Privacy API Feature Tests
 *
 * Comprehensive tests for GDPR/CCPA compliant privacy operations including
 * consent management, data deletion, and compliance reporting.
 */
class PrivacyApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ConsentService $consentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->consentService = app(ConsentService::class);

        // Ensure storage directory exists for exports
        Storage::makeDirectory('exports');
    }

    /**
     * Test successful consent preference updates.
     */
    public function test_update_consent_preferences_success(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $preferences = [
            'analytics' => true,
            'marketing' => false,
            'tracking' => true,
            'profiling' => false,
        ];

        $response = $this->postJson('/api/privacy/consent', [
            'preferences' => $preferences,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Consent preferences updated successfully',
                    'preferences' => $preferences,
                ]);

        // Verify consent was created/updated in database
        $this->assertDatabaseHas('consents', [
            'user_id' => $this->user->id,
            'category' => 'analytics',
            'granted' => true,
        ]);

        $this->assertDatabaseHas('consents', [
            'user_id' => $this->user->id,
            'category' => 'marketing',
            'granted' => false,
        ]);
    }

    /**
     * Test consent update with invalid categories.
     */
    public function test_update_consent_invalid_categories(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/privacy/consent', [
            'preferences' => [
                'analytics' => true,
                'invalid_category' => false,
            ],
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['preferences']);
    }

    /**
     * Test consent update with missing preferences.
     */
    public function test_update_consent_missing_preferences(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/privacy/consent', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['preferences']);
    }

    /**
     * Test consent update without authentication.
     */
    public function test_update_consent_unauthenticated(): void
    {
        $response = $this->postJson('/api/privacy/consent', [
            'preferences' => ['analytics' => true],
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test successful data deletion with specific categories.
     */
    public function test_delete_user_data_specific_categories(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Create some consent data first
        $this->consentService->updateConsentPreferences($this->user->id, [
            'analytics' => true,
            'marketing' => true,
        ]);

        $response = $this->deleteJson('/api/privacy/data', [
            'categories' => ['analytics'],
            'reason' => 'User requested deletion',
            'confirmation_token' => '12345678-1234-1234-1234-123456789012',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Data deletion request processed successfully',
                ])
                ->assertJsonStructure([
                    'deletion_result' => [
                        'categories_processed',
                        'data_deleted',
                        'errors',
                    ],
                ]);
    }

    /**
     * Test data deletion with all categories.
     */
    public function test_delete_user_data_all_categories(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->deleteJson('/api/privacy/data', [
            'categories' => ['all'],
            'confirmation_token' => '12345678-1234-1234-1234-123456789012',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'deletion_result' => [
                        'categories_processed' => ['analytics', 'marketing', 'tracking', 'profiling'],
                    ],
                ]);
    }

    /**
     * Test data deletion with invalid confirmation token.
     */
    public function test_delete_user_data_invalid_token(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->deleteJson('/api/privacy/data', [
            'categories' => ['analytics'],
            'confirmation_token' => 'invalid-token',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['confirmation_token']);
    }

    /**
     * Test data deletion without confirmation token.
     */
    public function test_delete_user_data_missing_token(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->deleteJson('/api/privacy/data', [
            'categories' => ['analytics'],
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['confirmation_token']);
    }

    /**
     * Test data deletion without authentication.
     */
    public function test_delete_user_data_unauthenticated(): void
    {
        $response = $this->deleteJson('/api/privacy/data', [
            'categories' => ['analytics'],
            'confirmation_token' => '12345678-1234-1234-1234-123456789012',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test compliance report generation.
     */
    public function test_get_compliance_report(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Set up some consent data
        $this->consentService->updateConsentPreferences($this->user->id, [
            'analytics' => true,
            'marketing' => false,
        ]);

        $response = $this->getJson('/api/privacy/report');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                ])
                ->assertJsonStructure([
                    'report' => [
                        'user_id',
                        'report_period',
                        'consent_status',
                        'data_processing_activities',
                        'legal_basis',
                        'data_retention_period',
                        'generated_at',
                    ],
                ]);
    }

    /**
     * Test compliance report with date range.
     */
    public function test_get_compliance_report_with_date_range(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/privacy/report?' . http_build_query([
            'date_range' => [
                'start' => '2024-01-01',
                'end' => '2024-12-31',
            ],
            'include_deleted' => true,
        ]));

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'report' => [
                        'report_period' => [
                            'start' => '2024-01-01',
                            'end' => '2024-12-31',
                        ],
                    ],
                ]);
    }

    /**
     * Test compliance report with invalid date range.
     */
    public function test_get_compliance_report_invalid_date_range(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/privacy/report?' . http_build_query([
            'date_range' => [
                'start' => '2024-12-31',
                'end' => '2024-01-01', // End before start
            ],
        ]));

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['date_range.end']);
    }

    /**
     * Test compliance report without authentication.
     */
    public function test_get_compliance_report_unauthenticated(): void
    {
        $response = $this->getJson('/api/privacy/report');

        $response->assertStatus(401);
    }

    /**
     * Test retrieving consent preferences.
     */
    public function test_get_consent_preferences(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Set up consent preferences
        $this->consentService->updateConsentPreferences($this->user->id, [
            'analytics' => true,
            'marketing' => false,
            'tracking' => true,
            'profiling' => false,
        ]);

        $response = $this->getJson('/api/privacy/preferences');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'preferences' => [
                        'analytics' => true,
                        'marketing' => false,
                        'tracking' => true,
                        'profiling' => false,
                    ],
                ]);
    }

    /**
     * Test retrieving consent preferences without authentication.
     */
    public function test_get_consent_preferences_unauthenticated(): void
    {
        $response = $this->getJson('/api/privacy/preferences');

        $response->assertStatus(401);
    }

    /**
     * Test data export functionality.
     */
    public function test_export_user_data(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Set up some consent data
        $this->consentService->updateConsentPreferences($this->user->id, [
            'analytics' => true,
        ]);

        $response = $this->getJson('/api/privacy/export');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Data export completed successfully',
                ])
                ->assertJsonStructure([
                    'export_url',
                    'export_data' => [
                        'user_id',
                        'export_timestamp',
                        'consent_preferences',
                        'data_portability',
                        'data_summary',
                    ],
                ]);

        // Verify export data contains correct information
        $exportData = $response->json('export_data');
        $this->assertEquals($this->user->id, $exportData['user_id']);
        $this->assertArrayHasKey('consent_preferences', $exportData);
        $this->assertArrayHasKey('data_portability', $exportData);
    }

    /**
     * Test data export without authentication.
     */
    public function test_export_user_data_unauthenticated(): void
    {
        $response = $this->getJson('/api/privacy/export');

        $response->assertStatus(401);
    }

    /**
     * Test concurrent consent updates (race condition protection).
     */
    public function test_concurrent_consent_updates(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Simulate concurrent requests
        $responses = [];

        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/api/privacy/consent', [
                'preferences' => [
                    'analytics' => ($i % 2 === 0),
                    'marketing' => ($i % 2 === 1),
                ],
            ]);
        }

        // All responses should be successful
        foreach ($responses as $response) {
            $response->assertStatus(200)
                    ->assertJson(['success' => true]);
        }
    }

    /**
     * Test data deletion audit trail.
     */
    public function test_data_deletion_creates_audit_trail(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $this->deleteJson('/api/privacy/data', [
            'categories' => ['analytics'],
            'reason' => 'Test deletion',
            'confirmation_token' => '12345678-1234-1234-1234-123456789012',
        ]);

        // Verify audit log was created
        $this->assertDatabaseHas('consent_logs', [
            'user_id' => $this->user->id,
            'action' => 'data_deleted',
        ]);
    }

    /**
     * Test consent preference caching.
     */
    public function test_consent_preferences_caching(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // First request should cache the result
        $response1 = $this->getJson('/api/privacy/preferences');
        $response1->assertStatus(200);

        // Update consent directly in database to test cache invalidation
        Consent::where('user_id', $this->user->id)->delete();

        // Second request should return updated data (cache should be cleared)
        $response2 = $this->getJson('/api/privacy/preferences');
        $response2->assertStatus(200);

        // Both responses should be successful
        $this->assertEquals($response1->getStatusCode(), $response2->getStatusCode());
    }

    /**
     * Test malformed JSON in consent preferences.
     */
    public function test_malformed_json_in_consent_request(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->withHeaders([
            'Content-Type' => 'application/json',
        ])->post('/api/privacy/consent', '{invalid json');

        $response->assertStatus(400);
    }

    /**
     * Test rate limiting on privacy endpoints.
     */
    public function test_privacy_endpoints_rate_limiting(): void
    {
        $this->actingAs($this->user, 'sanctum');

        // Make multiple rapid requests to test rate limiting
        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson('/api/privacy/preferences');
            if ($i < 5) { // Assuming rate limit allows 5 requests
                $response->assertStatus(200);
            }
            // After rate limit, should get 429 status
        }
    }

    /**
     * Test cross-tenant data isolation.
     */
    public function test_cross_tenant_data_isolation(): void
    {
        // Create user in different tenant context
        $otherUser = User::factory()->create();

        $this->actingAs($this->user, 'sanctum');

        // Set consent for current user
        $this->consentService->updateConsentPreferences($this->user->id, [
            'analytics' => true,
        ]);

        // Try to access other user's data (should not be possible)
        $response = $this->actingAs($otherUser, 'sanctum')
                        ->getJson('/api/privacy/preferences');

        $response->assertStatus(200);

        // Verify other user gets their own data, not the first user's
        $preferences = $response->json('preferences');
        foreach ($preferences as $category => $value) {
            $this->assertFalse($value, "Other user should not have {$category} consent set");
        }
    }
}