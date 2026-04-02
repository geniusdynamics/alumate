<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Analytics\PrivacyController;
use App\Models\User;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\Consent;
use App\Services\Analytics\PrivacyComplianceService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Privacy Controller Feature Tests
 *
 * Tests for privacy controls API endpoints including:
 * - Consent management
 * - Data rights (export, delete, anonymize)
 * - RBAC authorization
 * - Tenant isolation
 */
class PrivacyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected PrivacyController $controller;
    protected PrivacyComplianceService $privacyComplianceService;
    protected TenantContextService $tenantContextService;
    protected Tenant $tenant;
    protected User $user;
    protected User $adminUser;
    protected User $superAdminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->privacyComplianceService = app(PrivacyComplianceService::class);
        $this->tenantContextService = app(TenantContextService::class);
        $this->controller = new PrivacyController(
            $this->privacyComplianceService,
            $this->tenantContextService
        );

        // Create tenant
        $this->tenant = Tenant::factory()->create();

        // Create regular user
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_super_admin' => false,
        ]);

        // Create admin user
        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_super_admin' => false,
        ]);
        TenantUser::factory()->create([
            'user_id' => $this->adminUser->id,
            'tenant_id' => $this->tenant->id,
            'role' => User::ROLE_TENANT_ADMIN,
            'is_active' => true,
        ]);

        // Create super admin user
        $this->superAdminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_super_admin' => true,
        ]);
    }

    /**
     * Test unauthenticated user cannot access privacy endpoints
     */
    public function test_unauthenticated_user_cannot_access_endpoints(): void
    {
        Auth::logout();

        $response = $this->controller->index(request());
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test user can get their own privacy settings
     */
    public function test_user_can_get_own_privacy_settings(): void
    {
        Auth::login($this->user);

        // Grant some consents
        Consent::create([
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'granted_at' => now(),
        ]);

        $response = $this->controller->index(request());

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertEquals($this->user->id, $data['data']['user_id']);
        $this->assertArrayHasKey('consent_status', $data['data']);
    }

    /**
     * Test user can get specific user privacy settings with permission
     */
    public function test_user_can_get_specific_user_privacy_settings(): void
    {
        Auth::login($this->adminUser);

        $response = $this->controller->show($this->user->id);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals($this->user->id, $data['data']['user_id']);
    }

    /**
     * Test user cannot access other user's privacy settings without permission
     */
    public function test_user_cannot_access_other_user_privacy_settings(): void
    {
        $otherUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_super_admin' => false,
        ]);

        Auth::login($this->user);

        $response = $this->controller->show($otherUser->id);

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    /**
     * Test super admin can access any user's privacy settings
     */
    public function test_super_admin_can_access_any_user_privacy_settings(): void
    {
        Auth::login($this->superAdminUser);

        $response = $this->controller->show($this->user->id);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    /**
     * Test user can update their own privacy settings
     */
    public function test_user_can_update_own_privacy_settings(): void
    {
        Auth::login($this->user);

        $request = request()->merge([
            'consent' => [
                'analytics' => true,
                'marketing' => false,
            ],
        ]);

        $response = $this->controller->update($request, $this->user->id);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('updated_consent_status', $data['data']);
    }

    /**
     * Test user can grant consent
     */
    public function test_user_can_grant_consent(): void
    {
        Auth::login($this->user);

        $request = request()->merge([
            'consent_type' => 'analytics',
            'consented' => true,
        ]);

        $response = $this->controller->consent($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('analytics', $data['data']['consent_type']);
        $this->assertTrue($data['data']['consented']);
        $this->assertNotNull($data['data']['granted_at']);

        // Verify consent was recorded in database
        $this->assertDatabaseHas('consents', [
            'user_id' => $this->user->id,
            'type' => 'analytics',
        ]);
    }

    /**
     * Test user can revoke consent
     */
    public function test_user_can_revoke_consent(): void
    {
        Auth::login($this->user);

        // First grant consent
        Consent::create([
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'granted_at' => now(),
        ]);

        $request = request()->merge([
            'consent_type' => 'analytics',
        ]);

        $response = $this->controller->revokeConsent($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('revoked_at', $data['data']);
    }

    /**
     * Test get consent status for a user
     */
    public function test_get_consent_status_for_user(): void
    {
        Auth::login($this->user);

        // Create some consents
        Consent::create([
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'granted_at' => now(),
        ]);
        Consent::create([
            'user_id' => $this->user->id,
            'type' => 'marketing',
            'granted_at' => now(),
        ]);

        $response = $this->controller->getConsentStatus($this->user->id);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('consent_status', $data['data']);
        $this->assertTrue($data['data']['consent_status']['analytics']['has_consent']);
        $this->assertTrue($data['data']['consent_status']['marketing']['has_consent']);
        $this->assertFalse($data['data']['consent_status']['personalization']['has_consent']);
    }

    /**
     * Test user can export their own data
     */
    public function test_user_can_export_own_data(): void
    {
        Auth::login($this->user);

        // Create some test data
        Consent::create([
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'granted_at' => now(),
        ]);

        DB::table('analytics_events')->insert([
            'user_id' => $this->user->id,
            'event_type' => 'page_view',
            'event_data' => json_encode(['page' => '/dashboard']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->controller->exportData($this->user->id);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('export_timestamp', $data['data']);
        $this->assertArrayHasKey('user_id', $data['data']);
        $this->assertArrayHasKey('consent_records', $data['data']);
        $this->assertArrayHasKey('analytics_data', $data['data']);
    }

    /**
     * Test user can delete their own data
     */
    public function test_user_can_delete_own_data(): void
    {
        Auth::login($this->user);

        // Create test data
        DB::table('analytics_events')->insert([
            'user_id' => $this->user->id,
            'event_type' => 'page_view',
            'event_data' => json_encode(['page' => '/dashboard']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Consent::create([
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'granted_at' => now(),
        ]);

        $response = $this->controller->deleteData($this->user->id);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('deleted_at', $data['data']);

        // Verify data was deleted
        $this->assertDatabaseMissing('analytics_events', [
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test user can anonymize their own data
     */
    public function test_user_can_anonymize_own_data(): void
    {
        Auth::login($this->user);

        // Create test data
        DB::table('analytics_events')->insert([
            'user_id' => $this->user->id,
            'event_type' => 'page_view',
            'event_data' => json_encode(['page' => '/dashboard']),
            'ip_address' => '192.168.1.1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->controller->anonymizeData($this->user->id);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('anonymized_at', $data['data']);

        // Verify data was anonymized (user_id should be null)
        $event = DB::table('analytics_events')
            ->where('user_id', $this->user->id)
            ->first();

        $this->assertNull($event->user_id);
        $this->assertNull($event->ip_address);
    }

    /**
     * Test super admin can anonymize any user's data
     */
    public function test_super_admin_can_anonymize_any_user_data(): void
    {
        Auth::login($this->superAdminUser);

        $response = $this->controller->anonymizeData($this->user->id);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    /**
     * Test regular user cannot anonymize another user's data
     */
    public function test_regular_user_cannot_anonymize_other_user_data(): void
    {
        $otherUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_super_admin' => false,
        ]);

        Auth::login($this->user);

        $response = $this->controller->anonymizeData($otherUser->id);

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
    }

    /**
     * Test validation fails for invalid consent type
     */
    public function test_validation_fails_for_invalid_consent_type(): void
    {
        Auth::login($this->user);

        $request = request()->merge([
            'consent_type' => 'invalid_type',
            'consented' => true,
        ]);

        $response = $this->controller->consent($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test validation fails for missing consent type
     */
    public function test_validation_fails_for_missing_consent_type(): void
    {
        Auth::login($this->user);

        $request = request()->merge([
            'consented' => true,
        ]);

        $response = $this->controller->consent($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * Test index method returns user consent status
     */
    public function test_index_returns_user_consent_status(): void
    {
        Auth::login($this->user);

        // Grant consents
        $this->privacyComplianceService->recordConsent($this->user->id, 'analytics', true);
        $this->privacyComplianceService->recordConsent($this->user->id, 'marketing', true);

        $response = $this->controller->index(request());

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('available_consent_types', $data['data']);
        $this->assertEquals(['analytics', 'marketing', 'personalization', 'third_party'], $data['data']['available_consent_types']);
    }

    /**
     * Test export returns restricted message when no consent
     */
    public function test_export_returns_restricted_when_no_consent(): void
    {
        Auth::login($this->user);

        // Create analytics data but no consent
        DB::table('analytics_events')->insert([
            'user_id' => $this->user->id,
            'event_type' => 'page_view',
            'event_data' => json_encode(['page' => '/dashboard']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->controller->exportData($this->user->id);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals(['status' => 'consent_not_granted'], $data['data']['analytics_data']);
    }

    /**
     * Test consent with all consent types
     */
    public function test_consent_with_all_consent_types(): void
    {
        Auth::login($this->user);

        $consentTypes = ['analytics', 'marketing', 'personalization', 'third_party'];

        foreach ($consentTypes as $type) {
            $request = request()->merge([
                'consent_type' => $type,
                'consented' => true,
            ]);

            $response = $this->controller->consent($request);

            $this->assertEquals(200, $response->getStatusCode(), "Failed for consent type: {$type}");
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($data['success'], "Failed for consent type: {$type}");
        }
    }

    /**
     * Test tenant isolation for privacy data
     */
    public function test_tenant_isolation_for_privacy_data(): void
    {
        // Create another tenant with a user
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'is_super_admin' => false,
        ]);

        // Grant consent to other user
        Consent::create([
            'user_id' => $otherUser->id,
            'type' => 'analytics',
            'granted_at' => now(),
        ]);

        Auth::login($this->user);

        // Try to access other tenant user's data
        $response = $this->controller->show($otherUser->id);

        // Should fail due to tenant isolation
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test admin can access tenant user privacy settings
     */
    public function test_admin_can_access_tenant_user_privacy_settings(): void
    {
        Auth::login($this->adminUser);

        $response = $this->controller->show($this->user->id);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    /**
     * Test update returns validation error for invalid consent values
     */
    public function test_update_returns_validation_error_for_invalid_consent_values(): void
    {
        Auth::login($this->user);

        $request = request()->merge([
            'consent' => [
                'analytics' => 'not_a_boolean',
            ],
        ]);

        $response = $this->controller->update($request, $this->user->id);

        $this->assertEquals(422, $response->getStatusCode());
    }
}
