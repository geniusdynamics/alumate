<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\EmailPreference;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ComplianceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * ComplianceServiceTest
 *
 * Unit tests for the ComplianceService
 */
class ComplianceServiceTest extends TestCase
{
    use RefreshDatabase;

    private ComplianceService $service;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ComplianceService();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
    }

    public function test_can_create_or_update_preferences(): void
    {
        $email = 'test@example.com';
        $preferences = [
            'newsletters' => true,
            'promotions' => false,
        ];

        $preference = $this->service->createOrUpdatePreferences(
            $email,
            $this->tenant,
            $preferences,
            $this->user
        );

        $this->assertInstanceOf(EmailPreference::class, $preference);
        $this->assertEquals($email, $preference->email);
        $this->assertEquals($this->tenant->id, $preference->tenant_id);
        $this->assertEquals($this->user->id, $preference->user_id);
        $this->assertEquals($preferences, $preference->preferences);
        $this->assertNotNull($preference->consent_given_at);
        $this->assertTrue($preference->gdpr_compliant);
        $this->assertTrue($preference->can_spam_compliant);
    }

    public function test_can_update_existing_preferences(): void
    {
        $email = 'test@example.com';
        $initialPreferences = ['newsletters' => true];
        $updatedPreferences = ['newsletters' => false, 'promotions' => true];

        // Create initial preference
        $preference = $this->service->createOrUpdatePreferences(
            $email,
            $this->tenant,
            $initialPreferences
        );

        // Update preferences
        $updatedPreference = $this->service->createOrUpdatePreferences(
            $email,
            $this->tenant,
            $updatedPreferences
        );

        $this->assertEquals($preference->id, $updatedPreference->id);
        $this->assertEquals($updatedPreferences, $updatedPreference->preferences);
        $this->assertCount(2, $updatedPreference->audit_trail);
    }

    public function test_can_process_unsubscribe_request(): void
    {
        $preference = EmailPreference::factory()->forTenant($this->tenant)->create([
            'email' => 'test@example.com',
        ]);

        $token = $preference->generateUnsubscribeToken();

        $result = $this->service->processUnsubscribe($token, 'test@example.com');

        $this->assertTrue($result['success']);
        $this->assertStringContains('unsubscribed', $result['message']);

        $preference->refresh();
        $this->assertNotNull($preference->consent_withdrawn_at);
        $this->assertNull($preference->unsubscribe_token);
    }

    public function test_can_process_partial_unsubscribe(): void
    {
        $preference = EmailPreference::factory()->forTenant($this->tenant)->create([
            'email' => 'test@example.com',
            'preferences' => [
                'newsletters' => true,
                'promotions' => true,
            ]
        ]);

        $token = $preference->generateUnsubscribeToken();

        $result = $this->service->processUnsubscribe($token, 'test@example.com', ['newsletters']);

        $this->assertTrue($result['success']);
        $this->assertEquals(['newsletters'], $result['categories']);

        $preference->refresh();
        $this->assertNull($preference->consent_withdrawn_at);
        $this->assertFalse($preference->preferences['newsletters']);
        $this->assertTrue($preference->preferences['promotions']);
    }

    public function test_handles_invalid_unsubscribe_token(): void
    {
        $result = $this->service->processUnsubscribe('invalid-token', 'test@example.com');

        $this->assertFalse($result['success']);
        $this->assertStringContains('Invalid', $result['message']);
    }

    public function test_can_generate_unsubscribe_link(): void
    {
        $preference = EmailPreference::factory()->forTenant($this->tenant)->create();

        $link = $this->service->generateUnsubscribeLink($preference);

        $this->assertStringContains('unsubscribe/confirm', $link);
        $this->assertStringContains('token=', $link);
        $this->assertStringContains('email=', $link);

        $preference->refresh();
        $this->assertNotNull($preference->unsubscribe_token);
    }

    public function test_can_initiate_double_opt_in(): void
    {
        $email = 'test@example.com';

        $preference = $this->service->initiateDoubleOptIn($email, $this->tenant, $this->user);

        $this->assertInstanceOf(EmailPreference::class, $preference);
        $this->assertEquals($email, $preference->email);
        $this->assertNotNull($preference->double_opt_in_token);
        $this->assertCount(1, $preference->audit_trail);
        $this->assertEquals('double_opt_in_initiated', $preference->audit_trail[0]['action']);
    }

    public function test_can_confirm_double_opt_in(): void
    {
        $preference = EmailPreference::factory()->create([
            'double_opt_in_token' => 'valid-token',
        ]);

        $result = $this->service->confirmDoubleOptIn('valid-token');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['preference']);

        $preference->refresh();
        $this->assertNotNull($preference->double_opt_in_verified_at);
        $this->assertNull($preference->double_opt_in_token);
    }

    public function test_handles_invalid_double_opt_in_token(): void
    {
        $result = $this->service->confirmDoubleOptIn('invalid-token');

        $this->assertFalse($result['success']);
        $this->assertStringContains('Invalid', $result['message']);
    }

    public function test_can_get_preference_center_data(): void
    {
        $email = 'test@example.com';
        $preferences = ['newsletters' => true, 'promotions' => false];

        $preference = $this->service->createOrUpdatePreferences(
            $email,
            $this->tenant,
            $preferences
        );

        $data = $this->service->getPreferenceCenterData($email, $this->tenant);

        $this->assertEquals($email, $data['email']);
        $this->assertTrue($data['has_consent']);
        $this->assertEquals($preferences, $data['preferences']);
        $this->assertTrue($data['compliance_status']['gdpr']);
        $this->assertTrue($data['compliance_status']['can_spam']);
    }

    public function test_returns_default_data_for_nonexistent_preference(): void
    {
        $email = 'nonexistent@example.com';

        $data = $this->service->getPreferenceCenterData($email, $this->tenant);

        $this->assertEquals($email, $data['email']);
        $this->assertFalse($data['has_consent']);
        $this->assertArrayHasKey('newsletters', $data['preferences']);
        $this->assertArrayHasKey('promotions', $data['preferences']);
    }

    public function test_can_validate_compliance(): void
    {
        $compliantPreference = EmailPreference::factory()->create([
            'gdpr_compliant' => true,
            'can_spam_compliant' => true,
            'consent_given_at' => now(),
            'double_opt_in_verified_at' => now(),
        ]);

        $result = $this->service->validateCompliance($compliantPreference);

        $this->assertTrue($result['compliant']);
        $this->assertEmpty($result['issues']);
    }

    public function test_detects_compliance_issues(): void
    {
        $nonCompliantPreference = EmailPreference::factory()->create([
            'gdpr_compliant' => false,
            'can_spam_compliant' => false,
            'consent_given_at' => null,
        ]);

        $result = $this->service->validateCompliance($nonCompliantPreference);

        $this->assertFalse($result['compliant']);
        $this->assertCount(3, $result['issues']);
        $this->assertStringContains('GDPR', $result['issues'][0]);
        $this->assertStringContains('CAN-SPAM', $result['issues'][1]);
        $this->assertStringContains('consent', $result['issues'][2]);
    }

    public function test_can_generate_compliance_report(): void
    {
        // Create test data
        EmailPreference::factory()->count(10)->forTenant($this->tenant)->create();
        EmailPreference::factory()->count(3)->forTenant($this->tenant)->unsubscribed()->create();

        $report = $this->service->generateComplianceReport($this->tenant);

        $this->assertEquals(13, $report['totals']['total_subscribers']);
        $this->assertEquals(10, $report['totals']['active_consent']);
        $this->assertEquals(3, $report['totals']['unsubscribed']);
        $this->assertArrayHasKey('consent_rate', $report['percentages']);
        $this->assertArrayHasKey('unsubscribe_rate', $report['percentages']);
    }

    public function test_preference_model_has_consented_method(): void
    {
        $consentedPreference = EmailPreference::factory()->create([
            'consent_given_at' => now(),
            'consent_withdrawn_at' => null,
            'double_opt_in_verified_at' => now(),
        ]);

        $this->assertTrue($consentedPreference->hasConsented());

        $unconsentedPreference = EmailPreference::factory()->create([
            'consent_given_at' => null,
        ]);

        $this->assertFalse($unconsentedPreference->hasConsented());
    }

    public function test_preference_model_is_subscribed_to_method(): void
    {
        $preference = EmailPreference::factory()->create([
            'preferences' => [
                'newsletters' => true,
                'promotions' => false,
            ],
            'consent_given_at' => now(),
            'double_opt_in_verified_at' => now(),
        ]);

        $this->assertTrue($preference->isSubscribedTo('newsletters'));
        $this->assertFalse($preference->isSubscribedTo('promotions'));
    }

    public function test_preference_model_withdraw_consent_method(): void
    {
        $preference = EmailPreference::factory()->create([
            'consent_given_at' => now(),
        ]);

        $preference->withdrawConsent();

        $this->assertNotNull($preference->consent_withdrawn_at);
        $this->assertNull($preference->unsubscribe_token);
        $this->assertCount(1, $preference->audit_trail);
        $this->assertEquals('consent_withdrawn', $preference->audit_trail[0]['action']);
    }

    public function test_preference_model_update_preferences_method(): void
    {
        $preference = EmailPreference::factory()->create([
            'preferences' => ['newsletters' => true],
        ]);

        $preference->updatePreferences(['newsletters' => false, 'promotions' => true]);

        $this->assertFalse($preference->preferences['newsletters']);
        $this->assertTrue($preference->preferences['promotions']);
        $this->assertCount(1, $preference->audit_trail);
        $this->assertEquals('preferences_updated', $preference->audit_trail[0]['action']);
    }
}