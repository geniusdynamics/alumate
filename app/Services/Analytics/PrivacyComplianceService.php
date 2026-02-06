<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Jobs\ConsentPurgeJob;
use App\Models\Consent;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Privacy Compliance Service for managing user data privacy and compliance
 *
 * Handles GDPR, CCPA, and other privacy regulation requirements including:
 * - Consent management
 * - Data retention policies
 * - Data anonymization
 * - Data deletion (right to be forgotten)
 * - Data export (data portability)
 */
class PrivacyComplianceService
{
    /**
     * Retention periods in days for different data types
     */
    private const RETENTION_PERIODS = [
        'analytics_events' => 365,
        'session_data' => 90,
        'user_activity' => 180,
        'insights' => 365,
        'export_logs' => 730,
    ];

    /**
     * Cache key prefix for retention checks
     */
    private const RETENTION_CACHE_KEY = 'retention_check_';

    public function __construct(
        private ConsentService $consentService,
        private PrivacyAuditService $auditService,
        private TenantContextService $tenantContextService
    ) {}

    /**
     * Check if user has given consent for a specific type
     *
     * @param int $userId User ID to check
     * @param string $consentType Consent type (default: 'analytics')
     * @return bool True if consent is given
     */
    public function checkConsent(int $userId, string $consentType = 'analytics'): bool
    {
        return $this->consentService->hasConsent($userId, $consentType);
    }

    /**
     * Record user consent for a specific type
     *
     * @param int $userId User ID
     * @param string $consentType Consent type
     * @param bool $consented Whether consent is granted
     * @return bool True if successfully recorded
     */
    public function recordConsent(int $userId, string $consentType, bool $consented): bool
    {
        if ($consented) {
            $result = $this->consentService->grantConsent($userId, $consentType);
        } else {
            $result = $this->consentService->revokeConsent($userId, $consentType);
        }

        if ($result) {
            $this->auditService->logPrivacyEvent('consent_recorded', $userId, [
                'consent_type' => $consentType,
                'consented' => $consented,
            ]);
        }

        return $result;
    }

    /**
     * Revoke user consent for a specific type
     *
     * @param int $userId User ID
     * @param string $consentType Consent type
     * @return bool True if successfully revoked
     */
    public function revokeConsent(int $userId, string $consentType): bool
    {
        $result = $this->consentService->revokeConsent($userId, $consentType);

        if ($result) {
            $this->auditService->logPrivacyEvent('consent_revoked', $userId, [
                'consent_type' => $consentType,
            ]);

            // Queue data purge for revoked consent
            ConsentPurgeJob::dispatch($userId, $consentType);
        }

        return $result;
    }

    /**
     * Get all consent status for a user
     *
     * @param int $userId User ID
     * @return array Array of consent statuses
     */
    public function getConsentStatus(int $userId): array
    {
        $consentTypes = ['analytics', 'marketing', 'personalization', 'third_party'];

        $status = [];
        foreach ($consentTypes as $type) {
            $status[$type] = [
                'type' => $type,
                'has_consent' => $this->consentService->hasConsent($userId, $type),
                'last_updated' => $this->getLastConsentUpdate($userId, $type),
            ];
        }

        return $status;
    }

    /**
     * Anonymize user data while preserving consent records
     *
     * @param int $userId User ID
     * @return bool True if successfully anonymized
     */
    public function anonymizeData(int $userId): bool
    {
        try {
            $this->auditService->logPrivacyEvent('anonymization_started', $userId);

            // Anonymize analytics events
            $this->anonymizeAnalyticsEvents($userId);

            // Anonymize user activity logs
            $this->anonymizeActivityLogs($userId);

            // Anonymize insights
            $this->anonymizeInsights($userId);

            // Keep consent records but anonymize user reference if required
            $this->anonymizeConsentRecords($userId);

            $this->auditService->logPrivacyEvent('anonymization_completed', $userId);

            Log::info('User data anonymized', ['user_id' => $userId]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to anonymize user data', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete all user data (right to be forgotten)
     *
     * @param int $userId User ID
     * @return bool True if successfully deleted
     */
    public function deleteData(int $userId): bool
    {
        try {
            $this->auditService->logPrivacyEvent('data_deletion_started', $userId);

            // Run in tenant context to ensure proper data isolation
            $this->tenantContextService->runInTenantContext(
                $this->getUserTenantId($userId),
                function () use ($userId) {
                    // Delete analytics events
                    DB::table('analytics_events')
                        ->where('user_id', $userId)
                        ->delete();

                    // Delete session data
                    DB::table('sessions')
                        ->where('user_id', $userId)
                        ->delete();

                    // Delete user activity logs
                    DB::table('user_activity_logs')
                        ->where('user_id', $userId)
                        ->delete();

                    // Delete insights
                    DB::table('insights')
                        ->where('user_id', $userId)
                        ->delete();
                }
            );

            // Revoke all consents
            $consentTypes = ['analytics', 'marketing', 'personalization', 'third_party'];
            foreach ($consentTypes as $type) {
                $this->consentService->revokeConsent($userId, $type);
            }

            $this->auditService->logPrivacyEvent('data_deletion_completed', $userId);

            Log::info('User data deleted', ['user_id' => $userId]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete user data', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Export all user data (data portability)
     *
     * @param int $userId User ID
     * @return array Array of exported data
     */
    public function exportData(int $userId): array
    {
        $this->auditService->logPrivacyEvent('data_export_started', $userId);

        $exportData = [
            'export_timestamp' => now()->toIso8601String(),
            'user_id' => $userId,
            'consent_records' => $this->exportConsentRecords($userId),
            'analytics_data' => $this->exportAnalyticsData($userId),
            'activity_data' => $this->exportActivityData($userId),
            'insights_data' => $this->exportInsightsData($userId),
        ];

        $this->auditService->logPrivacyEvent('data_export_completed', $userId);

        Log::info('User data exported', ['user_id' => $userId]);

        return $exportData;
    }

    /**
     * Check data retention compliance for all users
     *
     * @return array Array of compliance results
     */
    public function checkDataRetention(): array
    {
        $cacheKey = self::RETENTION_CACHE_KEY . 'compliance_' . date('Ymd');

        // Check if compliance check was done today
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $results = [
            'checked_at' => now()->toIso8601String(),
            'compliance_status' => 'compliant',
            'issues' => [],
            'summary' => [
                'total_records_checked' => 0,
                'records_to_purge' => 0,
                'tenants_processed' => 0,
            ],
        ];

        // Get all tenants
        $tenants = Tenant::where('status', 'active')->get();

        foreach ($tenants as $tenant) {
            $tenantResults = $this->checkTenantRetention($tenant->id);
            $results['summary']['total_records_checked'] += $tenantResults['records_checked'];
            $results['summary']['records_to_purge'] += $tenantResults['records_to_purge'];
            $results['summary']['tenants_processed']++;

            if (!empty($tenantResults['issues'])) {
                $results['issues'][] = [
                    'tenant_id' => $tenant->id,
                    'issues' => $tenantResults['issues'],
                ];
            }
        }

        if ($results['summary']['records_to_purge'] > 0) {
            $results['compliance_status'] = 'attention_required';
        }

        // Cache results for 24 hours
        Cache::put($cacheKey, $results, 1440);

        return $results;
    }

    /**
     * Apply retention policies and purge expired data
     *
     * @return array Array of purge results
     */
    public function applyRetentionPolicies(): array
    {
        $results = [
            'applied_at' => now()->toIso8601String(),
            'purged_records' => 0,
            'errors' => [],
            'details' => [],
        ];

        $tenants = Tenant::where('status', 'active')->get();

        foreach ($tenants as $tenant) {
            $tenantResults = $this->applyTenantRetentionPolicies($tenant->id);

            $results['purged_records'] += $tenantResults['purged_count'];
            $results['details'][] = [
                'tenant_id' => $tenant->id,
                'purged_count' => $tenantResults['purged_count'],
            ];

            if (!empty($tenantResults['errors'])) {
                $results['errors'] = array_merge(
                    $results['errors'],
                    $tenantResults['errors']
                );
            }
        }

        $this->auditService->logPrivacyEvent('retention_policies_applied', 0, [
            'purged_records' => $results['purged_records'],
            'tenants_processed' => count($tenants),
        ]);

        Log::info('Retention policies applied', $results);

        return $results;
    }

    /**
     * Get last consent update for a user
     *
     * @param int $userId User ID
     * @param string $consentType Consent type
     * @return string|null ISO8601 timestamp
     */
    private function getLastConsentUpdate(int $userId, string $consentType): ?string
    {
        $consent = Consent::byUser($userId)
            ->byType($consentType)
            ->orderByDesc('granted_at')
            ->first();

        return $consent?->granted_at?->toIso8601String();
    }

    /**
     * Anonymize analytics events for a user
     *
     * @param int $userId User ID
     */
    private function anonymizeAnalyticsEvents(int $userId): void
    {
        DB::table('analytics_events')
            ->where('user_id', $userId)
            ->update([
                'user_id' => null,
                'session_id' => hash('sha256', $userId . '_anonymized'),
                'ip_address' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Anonymize activity logs for a user
     *
     * @param int $userId User ID
     */
    private function anonymizeActivityLogs(int $userId): void
    {
        DB::table('user_activity_logs')
            ->where('user_id', $userId)
            ->update([
                'user_id' => null,
                'ip_address' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Anonymize insights for a user
     *
     * @param int $userId User ID
     */
    private function anonymizeInsights(int $userId): void
    {
        DB::table('insights')
            ->where('user_id', $userId)
            ->update([
                'user_id' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Anonymize consent records for a user
     *
     * @param int $userId User ID
     */
    private function anonymizeConsentRecords(int $userId): void
    {
        // Keep consent history for audit purposes but mark as anonymized
        Consent::byUser($userId)->update([
            'ip_address' => null,
        ]);
    }

    /**
     * Get tenant ID for a user
     *
     * @param int $userId User ID
     * @return string Tenant ID
     */
    private function getUserTenantId(int $userId): string
    {
        $user = User::find($userId);
        return $user?->tenant_id ?? 'default';
    }

    /**
     * Export consent records for a user
     *
     * @param int $userId User ID
     * @return array
     */
    private function exportConsentRecords(int $userId): array
    {
        return Consent::byUser($userId)
            ->get()
            ->toArray();
    }

    /**
     * Export analytics data for a user
     *
     * @param int $userId User ID
     * @return array
     */
    private function exportAnalyticsData(int $userId): array
    {
        if (!$this->consentService->hasConsent($userId, 'analytics')) {
            return ['status' => 'consent_not_granted'];
        }

        return DB::table('analytics_events')
            ->where('user_id', $userId)
            ->get()
            ->toArray();
    }

    /**
     * Export activity data for a user
     *
     * @param int $userId User ID
     * @return array
     */
    private function exportActivityData(int $userId): array
    {
        return DB::table('user_activity_logs')
            ->where('user_id', $userId)
            ->get()
            ->toArray();
    }

    /**
     * Export insights data for a user
     *
     * @param int $userId User ID
     * @return array
     */
    private function exportInsightsData(int $userId): array
    {
        return DB::table('insights')
            ->where('user_id', $userId)
            ->get()
            ->toArray();
    }

    /**
     * Check retention compliance for a tenant
     *
     * @param string $tenantId Tenant ID
     * @return array
     */
    private function checkTenantRetention(string $tenantId): array
    {
        $results = [
            'records_checked' => 0,
            'records_to_purge' => 0,
            'issues' => [],
        ];

        $this->tenantContextService->runInTenantContext($tenantId, function () use (&$results) {
            foreach (self::RETENTION_PERIODS as $table => $days) {
                $cutoff = now()->subDays($days);

                $count = DB::table($table)
                    ->where('created_at', '<', $cutoff)
                    ->count();

                $results['records_checked'] += $count;

                if ($count > 0) {
                    $results['records_to_purge'] += $count;
                }
            }
        });

        return $results;
    }

    /**
     * Apply retention policies for a tenant
     *
     * @param string $tenantId Tenant ID
     * @return array
     */
    private function applyTenantRetentionPolicies(string $tenantId): array
    {
        $results = [
            'purged_count' => 0,
            'errors' => [],
        ];

        $this->tenantContextService->runInTenantContext($tenantId, function () use (&$results) {
            foreach (self::RETENTION_PERIODS as $table => $days) {
                $cutoff = now()->subDays($days);

                try {
                    $deleted = DB::table($table)
                        ->where('created_at', '<', $cutoff)
                        ->delete();

                    $results['purged_count'] += $deleted;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'table' => $table,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        return $results;
    }
}
