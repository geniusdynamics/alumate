<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Models\Consent;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Analytics\ConsentService;
use App\Services\Analytics\PrivacyAuditService;
use App\Services\Analytics\PrivacyComplianceService;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * PrivacyComplianceService Test Suite
 *
 * Unit tests for privacy compliance functionality including:
 * - Consent management
 * - Data anonymization
 * - Data deletion
 * - Data export
 * - Data retention policies
 */
class PrivacyComplianceServiceTest extends TestCase
{
    use RefreshDatabase;

    private PrivacyComplianceService $service;
    private ConsentService $consentService;
    private PrivacyAuditService $auditService;
    private TenantContextService $tenantContextService;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consentService = app(ConsentService::class);
        $this->auditService = app(PrivacyAuditService::class);
        $this->tenantContextService = app(TenantContextService::class);
        $this->service = new PrivacyComplianceService(
            $this->consentService,
            $this->auditService,
            $this->tenantContextService
        );

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /**
     * Test checkConsent returns false when no consent exists
     */
    public function test_check_consent_returns_false_when_no_consent_exists(): void
    {
        $result = $this->service->checkConsent($this->user->id, 'analytics');

        $this->assertFalse($result);
    }

    /**
     * Test checkConsent returns true when consent exists
     */
    public function test_check_consent_returns_true_when_consent_exists(): void
    {
        Consent::create([
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'granted_at' => now(),
        ]);

        $result = $this->service->checkConsent($this->user->id, 'analytics');

        $this->assertTrue($result);
    }

    /**
     * Test recordConsent grants consent
     */
    public function test_record_consent_grants_consent(): void
    {
        $result = $this->service->recordConsent($this->user->id, 'analytics', true);

        $this->assertTrue($result);

        $this->assertDatabaseHas('consents', [
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'revoked_at' => null,
        ]);
    }

    /**
     * Test recordConsent revokes consent
     */
    public function test_record_consent_revokes_consent(): void
    {
        // First grant consent
        Consent::create([
            'user_id' => $this->user->id,
            'type' => 'analytics',
            'granted_at' => now(),
        ]);

        $result = $this->service->recordConsent($this->user->id, 'analytics', false);

        $this->assertTrue($result);

        $consent = Consent::where('user_id', $this->user->id)
            ->where('type', 'analytics')
            ->whereNotNull('revoked_at')
            ->first();

        $this->assertNotNull($consent);
    }

    /**
     * Test revokeConsent removes consent and logs event
     */
    public function test_revoke_consent_removes_consent_and_logs_event(): void
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

        $result = $this->service->revokeConsent($this->user->id, 'analytics');

        $this->assertTrue($result);

        $consent = Consent::where('user_id', $this->user->id)
            ->where('type', 'analytics')
            ->first();

        $this->assertNotNull($consent->revoked_at);
    }

    /**
     * Test getConsentStatus returns all consent statuses
     */
    public function test_get_consent_status_returns_all_consent_statuses(): void
    {
        // Grant some consents
        $this->service->recordConsent($this->user->id, 'analytics', true);
        $this->service->recordConsent($this->user->id, 'marketing', false);

        $status = $this->service->getConsentStatus($this->user->id);

        $this->assertIsArray($status);
        $this->assertArrayHasKey('analytics', $status);
        $this->assertArrayHasKey('marketing', $status);
        $this->assertArrayHasKey('personalization', $status);
        $this->assertArrayHasKey('third_party', $status);

        $this->assertTrue($status['analytics']['has_consent']);
        $this->assertFalse($status['marketing']['has_consent']);
    }

    /**
     * Test anonymizeData replaces user identifiers
     */
    public function test_anonymize_data_replaces_user_identifiers(): void
    {
        // Create test data
        DB::table('analytics_events')->insert([
            'user_id' => $this->user->id,
            'event_type' => 'page_view',
            'event_data' => json_encode(['page' => '/dashboard']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_activity_logs')->insert([
            'user_id' => $this->user->id,
            'action' => 'login',
            'ip_address' => '192.168.1.1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->anonymizeData($this->user->id);

        $this->assertTrue($result);

        // Verify data was anonymized
        $event = DB::table('analytics_events')
            ->where('user_id', $this->user->id)
            ->first();

        $this->assertNull($event->user_id);
        $this->assertNull($event->ip_address);

        $log = DB::table('user_activity_logs')
            ->where('user_id', $this->user->id)
            ->first();

        $this->assertNull($log->user_id);
        $this->assertNull($log->ip_address);
    }

    /**
     * Test deleteData removes all user data
     */
    public function test_delete_data_removes_all_user_data(): void
    {
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

        $result = $this->service->deleteData($this->user->id);

        $this->assertTrue($result);

        // Verify data was deleted
        $this->assertDatabaseMissing('analytics_events', [
            'user_id' => $this->user->id,
        ]);

        $consent = Consent::where('user_id', $this->user->id)
            ->where('type', 'analytics')
            ->whereNull('revoked_at')
            ->first();

        $this->assertNull($consent);
    }

    /**
     * Test exportData returns user data
     */
    public function test_export_data_returns_user_data(): void
    {
        // Create test data
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

        $export = $this->service->exportData($this->user->id);

        $this->assertArrayHasKey('export_timestamp', $export);
        $this->assertArrayHasKey('user_id', $export);
        $this->assertArrayHasKey('consent_records', $export);
        $this->assertArrayHasKey('analytics_data', $export);
        $this->assertArrayHasKey('activity_data', $export);
        $this->assertArrayHasKey('insights_data', $export);

        $this->assertNotEmpty($export['consent_records']);
        $this->assertNotEmpty($export['analytics_data']);
    }

    /**
     * Test exportData returns restricted message when no consent
     */
    public function test_export_data_returns_restricted_when_no_consent(): void
    {
        DB::table('analytics_events')->insert([
            'user_id' => $this->user->id,
            'event_type' => 'page_view',
            'event_data' => json_encode(['page' => '/dashboard']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $export = $this->service->exportData($this->user->id);

        $this->assertEquals(['status' => 'consent_not_granted'], $export['analytics_data']);
    }

    /**
     * Test checkDataRetention returns compliance status
     */
    public function test_check_data_retention_returns_compliance_status(): void
    {
        // Clear cache first
        Cache::flush();

        $result = $this->service->checkDataRetention();

        $this->assertArrayHasKey('checked_at', $result);
        $this->assertArrayHasKey('compliance_status', $result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('total_records_checked', $result['summary']);
        $this->assertArrayHasKey('records_to_purge', $result['summary']);
    }

    /**
     * Test applyRetentionPolicies purges old data
     */
    public function test_apply_retention_policies_purges_old_data(): void
    {
        // Create old data that should be purged
        DB::table('analytics_events')->insert([
            'user_id' => $this->user->id,
            'event_type' => 'page_view',
            'event_data' => json_encode(['page' => '/old']),
            'created_at' => now()->subYears(2),
            'updated_at' => now()->subYears(2),
        ]);

        // Create recent data that should be kept
        DB::table('analytics_events')->insert([
            'user_id' => $this->user->id,
            'event_type' => 'page_view',
            'event_data' => json_encode(['page' => '/recent']),
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);

        $result = $this->service->applyRetentionPolicies();

        $this->assertArrayHasKey('applied_at', $result);
        $this->assertArrayHasKey('purged_records', $result);
        $this->assertGreaterThanOrEqual(1, $result['purged_records']);

        // Verify recent data still exists
        $this->assertDatabaseHas('analytics_events', [
            'event_data' => json_encode(['page' => '/recent']),
        ]);
    }

    /**
     * Test consent operations work with different consent types
     */
    public function test_consent_operations_work_with_different_types(): void
    {
        $result = $this->service->recordConsent($this->user->id, 'marketing', true);

        $this->assertTrue($result);
        $this->assertTrue($this->service->checkConsent($this->user->id, 'marketing'));

        $status = $this->service->getConsentStatus($this->user->id);
        $this->assertTrue($status['marketing']['has_consent']);
    }

    /**
     * Test anonymizeData handles empty data gracefully
     */
    public function test_anonymize_data_handles_empty_data_gracefully(): void
    {
        $result = $this->service->anonymizeData($this->user->id);

        $this->assertTrue($result);
    }

    /**
     * Test deleteData handles missing user gracefully
     */
    public function test_delete_data_handles_missing_user_gracefully(): void
    {
        $result = $this->service->deleteData(999999);

        $this->assertTrue($result);
    }

    /**
     * Test exportData handles missing user gracefully
     */
    public function test_export_data_handles_missing_user_gracefully(): void
    {
        $export = $this->service->exportData(999999);

        $this->assertArrayHasKey('export_timestamp', $export);
        $this->assertArrayHasKey('user_id', $export);
        $this->assertEquals(999999, $export['user_id']);
        $this->assertEmpty($export['consent_records']);
        $this->assertEmpty($export['analytics_data']);
    }

    /**
     * Test data retention caching works
     */
    public function test_data_retention_caching_works(): void
    {
        // Clear cache
        Cache::flush();

        // First call should cache
        $this->service->checkDataRetention();

        // Second call should use cache
        $cacheKey = 'retention_check_compliance_' . date('Ymd');
        $this->assertTrue(Cache::has($cacheKey));
    }
}
