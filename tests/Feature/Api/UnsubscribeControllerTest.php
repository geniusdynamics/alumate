<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\EmailPreference;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UnsubscribeControllerTest
 *
 * Feature tests for the UnsubscribeController API endpoints
 */
class UnsubscribeControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
    }

    public function test_can_confirm_unsubscribe(): void
    {
        $preference = EmailPreference::factory()->forTenant($this->tenant)->create([
            'email' => 'test@example.com',
        ]);

        $token = $preference->generateUnsubscribeToken();

        $response = $this->postJson('/api/unsubscribe/confirm', [
            'token' => $token,
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully unsubscribed from all communications.',
            ]);

        $preference->refresh();
        $this->assertNotNull($preference->consent_withdrawn_at);
        $this->assertNull($preference->unsubscribe_token);
    }

    public function test_can_confirm_partial_unsubscribe(): void
    {
        $preference = EmailPreference::factory()->forTenant($this->tenant)->create([
            'email' => 'test@example.com',
            'preferences' => [
                'newsletters' => true,
                'promotions' => true,
            ]
        ]);

        $token = $preference->generateUnsubscribeToken();

        $response = $this->postJson('/api/unsubscribe/confirm', [
            'token' => $token,
            'email' => 'test@example.com',
            'categories' => ['newsletters'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'categories' => ['newsletters'],
            ]);

        $preference->refresh();
        $this->assertNull($preference->consent_withdrawn_at);
        $this->assertFalse($preference->preferences['newsletters']);
        $this->assertTrue($preference->preferences['promotions']);
    }

    public function test_handles_invalid_unsubscribe_token(): void
    {
        $response = $this->postJson('/api/unsubscribe/confirm', [
            'token' => 'invalid-token',
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired unsubscribe link.',
            ]);
    }

    public function test_validates_unsubscribe_request(): void
    {
        $response = $this->postJson('/api/unsubscribe/confirm', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token', 'email']);
    }

    public function test_can_get_preferences(): void
    {
        $preference = EmailPreference::factory()->forTenant($this->tenant)->create([
            'email' => 'test@example.com',
            'preferences' => [
                'newsletters' => true,
                'promotions' => false,
            ],
        ]);

        $response = $this->getJson('/api/unsubscribe/preferences?email=test@example.com');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'test@example.com',
                    'has_consent' => true,
                    'preferences' => [
                        'newsletters' => true,
                        'promotions' => false,
                    ],
                ],
            ]);
    }

    public function test_returns_default_preferences_for_new_email(): void
    {
        $response = $this->getJson('/api/unsubscribe/preferences?email=new@example.com');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'new@example.com',
                    'has_consent' => false,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'email',
                    'has_consent',
                    'preferences' => [
                        'newsletters',
                        'promotions',
                        'announcements',
                        'events',
                        'surveys',
                    ],
                ],
            ]);
    }

    public function test_validates_preferences_request(): void
    {
        $response = $this->getJson('/api/unsubscribe/preferences');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_can_update_preferences(): void
    {
        $preference = EmailPreference::factory()->forTenant($this->tenant)->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->putJson('/api/unsubscribe/preferences', [
            'email' => 'test@example.com',
            'preferences' => [
                'newsletters' => false,
                'promotions' => true,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Preferences updated successfully.',
            ]);

        $preference->refresh();
        $this->assertFalse($preference->preferences['newsletters']);
        $this->assertTrue($preference->preferences['promotions']);
    }

    public function test_validates_preferences_update(): void
    {
        $response = $this->putJson('/api/unsubscribe/preferences', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'preferences']);
    }

    public function test_can_initiate_double_opt_in(): void
    {
        $response = $this->postJson('/api/unsubscribe/initiate-double-opt-in', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Double opt-in email sent. Please check your email to confirm.',
            ]);

        $preference = EmailPreference::where('email', 'test@example.com')
            ->where('tenant_id', $this->tenant->id)
            ->first();

        $this->assertNotNull($preference);
        $this->assertNotNull($preference->double_opt_in_token);
    }

    public function test_validates_double_opt_in_request(): void
    {
        $response = $this->postJson('/api/unsubscribe/initiate-double-opt-in', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_can_confirm_double_opt_in(): void
    {
        $preference = EmailPreference::factory()->forTenant($this->tenant)->create([
            'double_opt_in_token' => 'valid-token',
        ]);

        $response = $this->postJson('/api/unsubscribe/confirm-double-opt-in', [
            'token' => 'valid-token',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Email address successfully verified.',
            ]);

        $preference->refresh();
        $this->assertNotNull($preference->double_opt_in_verified_at);
        $this->assertNull($preference->double_opt_in_token);
    }

    public function test_handles_invalid_double_opt_in_token(): void
    {
        $response = $this->postJson('/api/unsubscribe/confirm-double-opt-in', [
            'token' => 'invalid-token',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid or expired double opt-in token.',
            ]);
    }

    public function test_validates_double_opt_in_confirmation(): void
    {
        $response = $this->postJson('/api/unsubscribe/confirm-double-opt-in', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    public function test_can_generate_unsubscribe_link(): void
    {
        $preference = EmailPreference::factory()->forTenant($this->tenant)->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/unsubscribe/generate-link', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'test@example.com',
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'email',
                    'unsubscribe_link',
                    'expires_at',
                ],
            ]);

        $preference->refresh();
        $this->assertNotNull($preference->unsubscribe_token);
    }

    public function test_can_get_compliance_report(): void
    {
        EmailPreference::factory()->count(5)->forTenant($this->tenant)->create();
        EmailPreference::factory()->count(2)->forTenant($this->tenant)->unsubscribed()->create();

        $response = $this->getJson('/api/unsubscribe/compliance-report');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'totals' => [
                        'total_subscribers' => 7,
                        'active_consent' => 5,
                        'unsubscribed' => 2,
                    ],
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period',
                    'totals',
                    'percentages',
                ],
            ]);
    }

    public function test_compliance_report_with_date_filters(): void
    {
        EmailPreference::factory()->forTenant($this->tenant)->create([
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->getJson('/api/unsubscribe/compliance-report?' . http_build_query([
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'totals' => [
                        'total_subscribers' => 0, // Should not include the older record
                    ],
                ],
            ]);
    }

    public function test_validates_compliance_report_dates(): void
    {
        $response = $this->getJson('/api/unsubscribe/compliance-report?' . http_build_query([
            'start_date' => 'invalid-date',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    public function test_handles_authenticated_user_context(): void
    {
        $this->actingAs($this->user);

        $response = $this->putJson('/api/unsubscribe/preferences', [
            'email' => 'test@example.com',
            'preferences' => [
                'newsletters' => true,
            ],
        ]);

        $response->assertStatus(200);

        $preference = EmailPreference::where('email', 'test@example.com')
            ->where('tenant_id', $this->tenant->id)
            ->first();

        $this->assertEquals($this->user->id, $preference->user_id);
    }

    public function test_handles_cross_tenant_isolation(): void
    {
        $otherTenant = Tenant::factory()->create();
        $preference = EmailPreference::factory()->forTenant($otherTenant)->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->getJson('/api/unsubscribe/preferences?email=test@example.com');

        // Should return default data, not the other tenant's preference
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'test@example.com',
                    'has_consent' => false,
                ],
            ]);
    }
}