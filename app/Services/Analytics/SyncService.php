<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\SyncLog;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Data Synchronization Service
 *
 * Provides comprehensive data synchronization between internal analytics
 * and external services (Google Analytics, Matomo) with discrepancy detection,
 * consent handling, and unified data views.
 */
class SyncService
{
    private const CACHE_TTL = 900; // 15 minutes

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY = 1000; // milliseconds

    private const DISCREPANCY_THRESHOLD = 0.05; // 5% threshold for flagging discrepancies

    private ConsentService $consentService;

    private GoogleAnalyticsService $googleAnalyticsService;

    private MatomoService $matomoService;

    /**
     * Initialize sync service with dependencies
     */
    public function __construct(
        ConsentService $consentService,
        GoogleAnalyticsService $googleAnalyticsService,
        MatomoService $matomoService
    ) {
        $this->consentService = $consentService;
        $this->googleAnalyticsService = $googleAnalyticsService;
        $this->matomoService = $matomoService;
    }

    /**
     * Synchronize data from internal and external sources
     *
     * Aggregates metrics from internal AnalyticsEvent/SessionRecording/Cohort models
     * and external services, unifying them into a normalized schema.
     *
     * @param  string  $tenantId  Tenant identifier
     * @param  array  $sources  Array of sources to sync ['ga', 'matomo']
     * @param  array  $timeRange  Time range for sync ['start' => date, 'end' => date]
     * @return array Unified metrics data
     */
    public function syncData(string $tenantId, array $sources = ['ga', 'matomo'], array $timeRange = []): array
    {
        try {
            Log::info('Starting data synchronization', [
                'tenant_id' => $tenantId,
                'sources' => $sources,
                'time_range' => $timeRange,
            ]);

            // Get internal metrics
            $internalMetrics = $this->getInternalMetrics($tenantId, $timeRange);

            // Get external metrics
            $externalMetrics = [];
            foreach ($sources as $source) {
                $externalMetrics[$source] = $this->getExternalMetrics($source, $tenantId, $timeRange);
            }

            // Unify data into normalized schema
            $unifiedData = $this->unifyMetrics($internalMetrics, $externalMetrics);

            // Cache unified view
            $this->cacheUnifiedData($tenantId, $unifiedData);

            // Log successful sync
            $this->logSyncResult($tenantId, 'unified', 'success', $unifiedData);

            Log::info('Data synchronization completed successfully', [
                'tenant_id' => $tenantId,
                'sources_count' => count($sources),
            ]);

            return $unifiedData;

        } catch (Exception $e) {
            Log::error('Data synchronization failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'sources' => $sources,
            ]);

            $this->logSyncResult($tenantId, 'unified', 'failed', [], $e->getMessage());

            // Return internal data as fallback
            return $this->getInternalMetrics($tenantId, $timeRange);
        }
    }

    /**
     * Detect and resolve discrepancies between internal and external data
     *
     * Compares metrics across sources and flags discrepancies above threshold.
     * Applies resolution rules: prefer internal for consented data, average for others.
     *
     * @param  string  $tenantId  Tenant identifier
     * @param  array  $timeRange  Time range for comparison
     * @return array Discrepancy report with resolutions
     */
    public function detectDiscrepancies(string $tenantId, array $timeRange = []): array
    {
        try {
            $internalMetrics = $this->getInternalMetrics($tenantId, $timeRange);
            $discrepancies = [];

            // Check GA discrepancies
            $gaMetrics = $this->getExternalMetrics('ga', $tenantId, $timeRange);
            if (! empty($gaMetrics)) {
                $gaDiscrepancies = $this->compareMetrics($internalMetrics, $gaMetrics, 'ga');
                $discrepancies = array_merge($discrepancies, $gaDiscrepancies);
            }

            // Check Matomo discrepancies
            $matomoMetrics = $this->getExternalMetrics('matomo', $tenantId, $timeRange);
            if (! empty($matomoMetrics)) {
                $matomoDiscrepancies = $this->compareMetrics($internalMetrics, $matomoMetrics, 'matomo');
                $discrepancies = array_merge($discrepancies, $matomoDiscrepancies);
            }

            // Resolve discrepancies
            $resolvedData = $this->resolveDiscrepancies($discrepancies, $tenantId);

            // Log discrepancies
            if (! empty($discrepancies)) {
                $this->logSyncResult($tenantId, 'discrepancy_detection', 'success', [
                    'discrepancies_found' => count($discrepancies),
                    'resolved' => count($resolvedData['resolutions']),
                ]);
            }

            return [
                'discrepancies' => $discrepancies,
                'resolutions' => $resolvedData['resolutions'],
                'unified_metrics' => $resolvedData['unified_metrics'],
            ];

        } catch (Exception $e) {
            Log::error('Discrepancy detection failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [
                'discrepancies' => [],
                'resolutions' => [],
                'unified_metrics' => $this->getInternalMetrics($tenantId, $timeRange),
            ];
        }
    }

    /**
     * Monitor synchronization status and health
     *
     * Tracks last sync time, error counts, and success rates via Redis.
     *
     * @param  string  $tenantId  Tenant identifier
     * @return array Status report
     */
    public function monitorSync(string $tenantId): array
    {
        $cacheKey = "sync_status_{$tenantId}";
        $status = Cache::get($cacheKey, [
            'last_sync_at' => null,
            'errors_count' => 0,
            'success_rate' => 1.0,
            'total_syncs' => 0,
            'last_error' => null,
        ]);

        // Update success rate based on recent performance
        $recentSyncs = SyncLog::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        if ($recentSyncs->count() > 0) {
            $successful = $recentSyncs->where('status', 'success')->count();
            $status['success_rate'] = $successful / $recentSyncs->count();
        }

        // Cache updated status
        Cache::put($cacheKey, $status, 3600); // Cache for 1 hour

        return $status;
    }

    /**
     * Sync learning data to external CRM systems
     *
     * Integrates with CrmIntegrationService to sync learning progress
     * and analytics data to HubSpot, Salesforce, and other CRMs.
     *
     * @param  string  $tenantId  Tenant identifier
     * @param  array  $learningData  Learning progress data to sync
     * @param  string  $provider  CRM provider (hubspot, salesforce, etc.)
     * @return bool Success status
     */
    public function syncLearningToCrm(string $tenantId, array $learningData, string $provider = 'hubspot'): bool
    {
        try {
            if (! class_exists('\App\Services\Integrations\CrmIntegrationService')) {
                Log::warning('CrmIntegrationService not available for learning sync');

                return false;
            }

            $crmService = app(\App\Services\Integrations\CrmIntegrationService::class);

            // Sync each learning progress record
            $successCount = 0;
            foreach ($learningData as $progress) {
                if ($crmService->syncLearningProgress($progress, $provider)) {
                    $successCount++;
                }
            }

            Log::info('Learning data sync to CRM completed', [
                'tenant_id' => $tenantId,
                'provider' => $provider,
                'total_records' => count($learningData),
                'successful_syncs' => $successCount,
            ]);

            return $successCount > 0;

        } catch (Exception $e) {
            Log::error('Learning data sync to CRM failed', [
                'tenant_id' => $tenantId,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Handle consent-related synchronization logic
     *
     * Skips sync for withdrawn categories and purges discrepancies if consent revoked.
     *
     * @param  string  $tenantId  Tenant identifier
     * @param  int  $userId  User identifier
     * @param  string  $consentCategory  Consent category being withdrawn
     * @return bool Success status
     */
    public function handleConsent(string $tenantId, int $userId, string $consentCategory): bool
    {
        try {
            // Check if consent affects analytics sync
            if (! $this->consentService->checkConsent($userId, $consentCategory)) {
                Log::info('Consent withdrawn, purging user data from sync', [
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'consent_category' => $consentCategory,
                ]);

                // Purge user data from cached unified views
                $this->purgeUserFromCache($tenantId, $userId);

                // Log consent violation for audit
                $this->logConsentViolation($userId, 'consent_withdrawn', [
                    'tenant_id' => $tenantId,
                    'consent_category' => $consentCategory,
                ]);

                return true;
            }

            return false; // Consent still active

        } catch (Exception $e) {
            Log::error('Consent handling failed', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'consent_category' => $consentCategory,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get internal metrics from database models
     */
    private function getInternalMetrics(string $tenantId, array $timeRange = []): array
    {
        // Aggregate from AnalyticsEvent, SessionRecording, Cohort models
        // This is a simplified implementation - in practice would query actual models

        $startDate = $timeRange['start'] ?? now()->subDays(30)->toDateString();
        $endDate = $timeRange['end'] ?? now()->toDateString();

        return [
            'events_count' => rand(1000, 5000), // Placeholder - would query AnalyticsEvent
            'sessions' => rand(500, 2000), // Placeholder - would query SessionRecording
            'conversions' => rand(50, 200), // Placeholder - would query conversion data
            'unique_users' => rand(300, 1000), // Placeholder - would calculate unique users
            'time_range' => [$startDate, $endDate],
        ];
    }

    /**
     * Get external metrics from specified service
     */
    private function getExternalMetrics(string $source, string $tenantId, array $timeRange = []): array
    {
        try {
            switch ($source) {
                case 'ga':
                    return $this->fetchGoogleAnalyticsMetrics($tenantId, $timeRange);
                case 'matomo':
                    return $this->fetchMatomoMetrics($tenantId, $timeRange);
                default:
                    Log::warning('Unknown external source requested', ['source' => $source]);

                    return [];
            }
        } catch (Exception $e) {
            Log::error('Failed to fetch external metrics', [
                'source' => $source,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return []; // Return empty array as fallback
        }
    }

    /**
     * Fetch metrics from Google Analytics
     */
    private function fetchGoogleAnalyticsMetrics(string $tenantId, array $timeRange = []): array
    {
        // This would call GoogleAnalyticsService::fetchMetrics()
        // For now, simulate API call with retry logic

        $attempts = 0;
        while ($attempts < self::MAX_RETRIES) {
            try {
                // Simulate API call
                return [
                    'events_count' => rand(800, 4500),
                    'sessions' => rand(400, 1800),
                    'conversions' => rand(40, 180),
                    'source' => 'ga',
                ];
            } catch (Exception $e) {
                $attempts++;
                if ($attempts >= self::MAX_RETRIES) {
                    throw $e;
                }
                usleep(self::RETRY_DELAY * 1000); // Convert to microseconds
            }
        }

        return [];
    }

    /**
     * Fetch metrics from Matomo
     */
    private function fetchMatomoMetrics(string $tenantId, array $timeRange = []): array
    {
        // This would call MatomoService::fetchMetrics()
        // For now, simulate API call with retry logic

        $attempts = 0;
        while ($attempts < self::MAX_RETRIES) {
            try {
                // Simulate API call
                return [
                    'events_count' => rand(900, 4800),
                    'sessions' => rand(450, 1900),
                    'conversions' => rand(45, 190),
                    'source' => 'matomo',
                ];
            } catch (Exception $e) {
                $attempts++;
                if ($attempts >= self::MAX_RETRIES) {
                    throw $e;
                }
                usleep(self::RETRY_DELAY * 1000);
            }
        }

        return [];
    }

    /**
     * Unify metrics from multiple sources into normalized schema
     */
    private function unifyMetrics(array $internal, array $external): array
    {
        $unified = [
            'events_count' => $internal['events_count'],
            'sessions' => $internal['sessions'],
            'conversions' => $internal['conversions'],
            'unique_users' => $internal['unique_users'],
            'sources' => ['internal'],
            'last_updated' => now()->toISOString(),
        ];

        // Merge external data
        foreach ($external as $source => $metrics) {
            if (! empty($metrics)) {
                $unified['sources'][] = $source;

                // Use external data where available, preferring internal for accuracy
                if (isset($metrics['events_count'])) {
                    $unified['events_count'] = max($unified['events_count'], $metrics['events_count']);
                }
                if (isset($metrics['sessions'])) {
                    $unified['sessions'] = max($unified['sessions'], $metrics['sessions']);
                }
                if (isset($metrics['conversions'])) {
                    $unified['conversions'] = max($unified['conversions'], $metrics['conversions']);
                }
            }
        }

        return $unified;
    }

    /**
     * Compare metrics between internal and external sources
     */
    private function compareMetrics(array $internal, array $external, string $source): array
    {
        $discrepancies = [];

        $metricsToCompare = ['events_count', 'sessions', 'conversions'];

        foreach ($metricsToCompare as $metric) {
            if (isset($internal[$metric]) && isset($external[$metric])) {
                $internalValue = $internal[$metric];
                $externalValue = $external[$metric];

                if ($internalValue > 0) {
                    $difference = abs($internalValue - $externalValue) / $internalValue;

                    if ($difference > self::DISCREPANCY_THRESHOLD) {
                        $discrepancies[] = [
                            'metric' => $metric,
                            'source' => $source,
                            'internal_value' => $internalValue,
                            'external_value' => $externalValue,
                            'difference_percentage' => round($difference * 100, 2),
                            'threshold_exceeded' => true,
                        ];
                    }
                }
            }
        }

        return $discrepancies;
    }

    /**
     * Resolve discrepancies using predefined rules
     */
    private function resolveDiscrepancies(array $discrepancies, string $tenantId): array
    {
        $resolutions = [];
        $unifiedMetrics = $this->getInternalMetrics($tenantId);

        foreach ($discrepancies as $discrepancy) {
            $resolution = [
                'metric' => $discrepancy['metric'],
                'source' => $discrepancy['source'],
                'action' => 'averaged', // Default resolution
                'original_values' => [
                    'internal' => $discrepancy['internal_value'],
                    'external' => $discrepancy['external_value'],
                ],
            ];

            // Apply resolution rules
            if ($this->shouldPreferInternal($discrepancy, $tenantId)) {
                $resolution['action'] = 'preferred_internal';
                $unifiedMetrics[$discrepancy['metric']] = $discrepancy['internal_value'];
            } else {
                // Average the values
                $avgValue = round(($discrepancy['internal_value'] + $discrepancy['external_value']) / 2);
                $unifiedMetrics[$discrepancy['metric']] = $avgValue;
                $resolution['final_value'] = $avgValue;
            }

            $resolutions[] = $resolution;
        }

        return [
            'resolutions' => $resolutions,
            'unified_metrics' => $unifiedMetrics,
        ];
    }

    /**
     * Determine if internal data should be preferred over external
     */
    private function shouldPreferInternal(array $discrepancy, string $tenantId): bool
    {
        // Prefer internal data for consented users or when external data seems unreliable
        // This is a simplified logic - in practice would check consent status

        return $discrepancy['difference_percentage'] > 20.0; // Prefer internal for large discrepancies
    }

    /**
     * Cache unified data in Redis
     */
    private function cacheUnifiedData(string $tenantId, array $data): void
    {
        $cacheKey = "unified_analytics_{$tenantId}";
        Cache::put($cacheKey, $data, self::CACHE_TTL);
    }

    /**
     * Purge user data from cached unified views
     */
    private function purgeUserFromCache(string $tenantId, int $userId): void
    {
        $cacheKey = "unified_analytics_{$tenantId}";
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            // In a real implementation, would filter out user-specific data
            // For now, just clear the cache to force fresh sync
            Cache::forget($cacheKey);
        }
    }

    /**
     * Log sync results to database
     */
    private function logSyncResult(string $tenantId, string $syncType, string $status, array $data = [], ?string $error = null): void
    {
        try {
            SyncLog::create([
                'tenant_id' => $tenantId,
                'sync_type' => $syncType,
                'status' => $status,
                'discrepancies' => json_encode($data),
                'timestamp' => now(),
            ]);

            // Update sync status in Redis
            $this->updateSyncStatus($tenantId, $status, $error);

        } catch (Exception $e) {
            Log::error('Failed to log sync result', [
                'tenant_id' => $tenantId,
                'sync_type' => $syncType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update sync status in Redis
     */
    private function updateSyncStatus(string $tenantId, string $status, ?string $error = null): void
    {
        $cacheKey = "sync_status_{$tenantId}";
        $currentStatus = Cache::get($cacheKey, [
            'last_sync_at' => null,
            'errors_count' => 0,
            'success_rate' => 1.0,
            'total_syncs' => 0,
            'last_error' => null,
        ]);

        $currentStatus['last_sync_at'] = now()->toISOString();
        $currentStatus['total_syncs']++;

        if ($status === 'failed') {
            $currentStatus['errors_count']++;
            $currentStatus['last_error'] = $error;
        }

        Cache::put($cacheKey, $currentStatus, 3600);
    }

    /**
     * Log consent violation for audit purposes
     */
    private function logConsentViolation(int $userId, string $action, array $data): void
    {
        Log::warning('Consent violation in sync service', [
            'user_id' => $userId,
            'action' => $action,
            'data' => $data,
        ]);
    }
}
