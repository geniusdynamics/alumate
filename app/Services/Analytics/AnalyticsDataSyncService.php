<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Analytics\SyncHistory;
use App\Models\Analytics\Discrepancy;
use App\Services\CacheService;
use App\Services\TenantContextService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

/**
 * Analytics Data Sync Service for unified data view and discrepancy detection
 * 
 * This service provides comprehensive data synchronization between internal
 * analytics and external platforms (Google Analytics, Matomo), including
 * discrepancy detection, resolution, and monitoring capabilities.
 */
class AnalyticsDataSyncService
{
    /**
     * Available data sources
     */
    public const SOURCE_INTERNAL = 'internal';
    public const SOURCE_GOOGLE_ANALYTICS = 'google_analytics';
    public const SOURCE_MATOMO = 'matomo';

    /**
     * Sync status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Resolution strategies
     */
    public const RESOLUTION_AVERAGE = 'average';
    public const RESOLUTION_MAX = 'max';
    public const RESOLUTION_MIN = 'min';
    public const RESOLUTION_SOURCE = 'source';

    /**
     * Cache TTL constants
     */
    private const CACHE_TTL_STATUS = 300; // 5 minutes
    private const CACHE_TTL_UNIFIED_VIEW = 180; // 3 minutes
    private const CACHE_TTL_DISCREPANCIES = 600; // 10 minutes

    private GoogleAnalyticsService $googleAnalyticsService;
    private MatomoService $matomoService;
    private CacheService $cacheService;
    private ?TenantContextService $tenantContextService;

    public function __construct(
        GoogleAnalyticsService $googleAnalyticsService,
        MatomoService $matomoService,
        CacheService $cacheService,
        ?TenantContextService $tenantContextService = null
    ) {
        $this->googleAnalyticsService = $googleAnalyticsService;
        $this->matomoService = $matomoService;
        $this->cacheService = $cacheService;
        $this->tenantContextService = $tenantContextService;
    }

    /**
     * Get the current tenant ID
     */
    protected function getCurrentTenantId(): ?string
    {
        if ($this->tenantContextService !== null) {
            return $this->tenantContextService->getCurrentTenantId();
        }
        return request()->header('X-Tenant');
    }

    /**
     * Get cache key with tenant prefix
     */
    protected function getCacheKey(string $key): string
    {
        $tenantId = $this->getCurrentTenantId();
        return 'analytics:sync:' . ($tenantId ? "{$tenantId}:" : '') . $key;
    }

    /**
     * Synchronize data between sources
     * 
     * @param string $source Source data source
     * @param string $target Target data source
     * @param array $dateRange Date range ['start' => 'Y-m-d', 'end' => 'Y-m-d']
     * @return array Sync results
     */
    public function syncData(string $source, string $target, array $dateRange): array
    {
        $tenantId = $this->getCurrentTenantId();
        
        Log::info('Starting data synchronization', [
            'source' => $source,
            'target' => $target,
            'date_range' => $dateRange,
            'tenant_id' => $tenantId,
        ]);

        $syncHistory = $this->createSyncRecord($source, $target, $dateRange);

        try {
            // Get data from source
            $sourceData = $this->fetchDataFromSource($source, $dateRange);
            
            if ($sourceData === null) {
                $this->updateSyncRecord($syncHistory, self::STATUS_FAILED, 'Failed to fetch data from source');
                return ['success' => false, 'error' => 'Failed to fetch data from source'];
            }

            // Transform and push to target
            $transformedData = $this->transformData($sourceData, $source, $target);
            $pushResult = $this->pushDataToTarget($target, $transformedData);

            if ($pushResult['success']) {
                $this->updateSyncRecord($syncHistory, self::STATUS_COMPLETED, null, $pushResult);
                
                // Invalidate relevant caches
                $this->invalidateRelatedCaches();
                
                Log::info('Data synchronization completed successfully', [
                    'source' => $source,
                    'target' => $target,
                    'records_synced' => $pushResult['records'] ?? 0,
                    'tenant_id' => $tenantId,
                ]);
                
                return [
                    'success' => true,
                    'records_synced' => $pushResult['records'] ?? 0,
                    'sync_id' => $syncHistory->id,
                ];
            } else {
                $this->updateSyncRecord($syncHistory, self::STATUS_FAILED, $pushResult['error'] ?? 'Unknown error');
                return ['success' => false, 'error' => $pushResult['error'] ?? 'Unknown error'];
            }
        } catch (\Exception $e) {
            $this->updateSyncRecord($syncHistory, self::STATUS_FAILED, $e->getMessage());
            $this->handleSyncError($e);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'sync_id' => $syncHistory->id,
            ];
        }
    }

    /**
     * Detect discrepancies between two data sources
     * 
     * @param string $source1 First data source
     * @param string $source2 Second data source
     * @param array $dateRange Date range ['start' => 'Y-m-d', 'end' => 'Y-m-d']
     * @return array Detected discrepancies
     */
    public function detectDiscrepancies(string $source1, string $source2, array $dateRange): array
    {
        $cacheKey = $this->getCacheKey('discrepancies:' . md5($source1 . $source2 . serialize($dateRange)));
        
        // Try cache first
        $cachedDiscrepancies = $this->cacheService->get($cacheKey);
        if ($cachedDiscrepancies !== null) {
            return $cachedDiscrepancies;
        }

        $tenantId = $this->getCurrentTenantId();
        $threshold = config('analytics.sync.discrepancy_threshold', 0.1); // 10% default threshold

        Log::info('Detecting discrepancies between sources', [
            'source1' => $source1,
            'source2' => $source2,
            'date_range' => $dateRange,
            'tenant_id' => $tenantId,
        ]);

        // Fetch data from both sources
        $data1 = $this->fetchDataFromSource($source1, $dateRange);
        $data2 = $this->fetchDataFromSource($source2, $dateRange);

        if ($data1 === null || $data2 === null) {
            return [];
        }

        $discrepancies = [];
        $metrics = ['sessions', 'users', 'pageviews', 'events', 'bounce_rate', 'avg_session_duration'];

        foreach ($metrics as $metric) {
            $value1 = $data1[$metric] ?? 0;
            $value2 = $data2[$metric] ?? 0;

            if ($value1 === 0 && $value2 === 0) {
                continue;
            }

            // Calculate discrepancy
            $discrepancy = $this->calculateDiscrepancy($metric, $value1, $value2, $source1, $source2, $threshold);

            if ($discrepancy !== null) {
                $discrepancies[] = $discrepancy;
                
                // Store discrepancy in database
                $this->storeDiscrepancy($discrepancy, $dateRange);
            }
        }

        // Cache the discrepancies
        $this->cacheService->put($cacheKey, $discrepancies, self::CACHE_TTL_DISCREPANCIES);

        return $discrepancies;
    }

    /**
     * Calculate discrepancy between two values
     */
    protected function calculateDiscrepancy(
        string $metric,
        $value1,
        $value2,
        string $source1,
        string $source2,
        float $threshold
    ): ?array {
        $average = ($value1 + $value2) / 2;
        
        if ($average === 0) {
            return null;
        }

        $difference = abs($value1 - $value2);
        $percentage = ($difference / $average) * 100;

        if ($percentage > ($threshold * 100)) {
            return [
                'id' => uniqid('disc_', true),
                'metric' => $metric,
                'source1' => $source1,
                'source2' => $source2,
                'value1' => $value1,
                'value2' => $value2,
                'average' => $average,
                'difference' => $difference,
                'percentage' => round($percentage, 2),
                'severity' => $this->getDiscrepancySeverity($percentage),
                'detected_at' => now()->toIso8601String(),
            ];
        }

        return null;
    }

    /**
     * Get discrepancy severity level
     */
    protected function getDiscrepancySeverity(float $percentage): string
    {
        if ($percentage < 20) {
            return 'low';
        } elseif ($percentage < 50) {
            return 'medium';
        } else {
            return 'high';
        }
    }

    /**
     * Store discrepancy in database
     */
    protected function storeDiscrepancy(array $discrepancy, array $dateRange): void
    {
        try {
            Discrepancy::create([
                'discrepancy_id' => $discrepancy['id'],
                'metric' => $discrepancy['metric'],
                'source1' => $discrepancy['source1'],
                'source2' => $discrepancy['source2'],
                'value1' => $discrepancy['value1'],
                'value2' => $discrepancy['value2'],
                'difference_percentage' => $discrepancy['percentage'],
                'severity' => $discrepancy['severity'],
                'date_range_start' => $dateRange['start'],
                'date_range_end' => $dateRange['end'],
                'tenant_id' => $this->getCurrentTenantId(),
                'resolved' => false,
                'resolution' => null,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to store discrepancy', [
                'error' => $e->getMessage(),
                'discrepancy' => $discrepancy,
            ]);
        }
    }

    /**
     * Resolve a discrepancy
     * 
     * @param string $discrepancyId Discrepancy ID to resolve
     * @param string $resolution Resolution strategy
     * @return array Resolution result
     */
    public function resolveDiscrepancy(string $discrepancyId, string $resolution = self::RESOLUTION_AVERAGE): array
    {
        $tenantId = $this->getCurrentTenantId();

        Log::info('Resolving discrepancy', [
            'discrepancy_id' => $discrepancyId,
            'resolution' => $resolution,
            'tenant_id' => $tenantId,
        ]);

        // Find the discrepancy
        $dbDiscrepancy = Discrepancy::where('discrepancy_id', $discrepancyId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$dbDiscrepancy) {
            return [
                'success' => false,
                'error' => 'Discrepancy not found',
                'discrepancy_id' => $discrepancyId,
            ];
        }

        // Apply resolution
        $resolvedValue = match ($resolution) {
            self::RESOLUTION_MAX => max($dbDiscrepancy->value1, $dbDiscrepancy->value2),
            self::RESOLUTION_MIN => min($dbDiscrepancy->value1, $dbDiscrepancy->value2),
            self::RESOLUTION_SOURCE => $dbDiscrepancy->value1, // Keep source1 value
            self::RESOLUTION_AVERAGE => ($dbDiscrepancy->value1 + $dbDiscrepancy->value2) / 2,
            default => ($dbDiscrepancy->value1 + $dbDiscrepancy->value2) / 2,
        };

        // Update discrepancy record
        $dbDiscrepancy->update([
            'resolved' => true,
            'resolved_at' => now(),
            'resolution' => $resolution,
            'resolved_value' => $resolvedValue,
        ]);

        // Invalidate discrepancy cache
        $this->cacheService->forget($this->getCacheKey('discrepancies:*'));

        Log::info('Discrepancy resolved successfully', [
            'discrepancy_id' => $discrepancyId,
            'resolution' => $resolution,
            'resolved_value' => $resolvedValue,
            'tenant_id' => $tenantId,
        ]);

        return [
            'success' => true,
            'discrepancy_id' => $discrepancyId,
            'resolution' => $resolution,
            'resolved_value' => $resolvedValue,
            'resolved_at' => $dbDiscrepancy->resolved_at->toIso8601String(),
        ];
    }

    /**
     * Get current synchronization status
     * 
     * @return array Sync status
     */
    public function getSyncStatus(): array
    {
        $cacheKey = $this->getCacheKey('status');

        // Try cache first
        $cachedStatus = $this->cacheService->get($cacheKey);
        if ($cachedStatus !== null) {
            return $cachedStatus;
        }

        $status = [
            'last_sync' => $this->getLastSyncTime(),
            'google_analytics' => [
                'enabled' => !empty(config('services.google.analytics.measurement_id')),
                'last_sync' => $this->getLastSyncTimeForSource(self::SOURCE_GOOGLE_ANALYTICS),
                'status' => $this->getSourceStatus(self::SOURCE_GOOGLE_ANALYTICS),
                'health' => $this->checkSourceHealth(self::SOURCE_GOOGLE_ANALYTICS),
            ],
            'matomo' => [
                'enabled' => !empty(config('services.matomo.url')),
                'last_sync' => $this->getLastSyncTimeForSource(self::SOURCE_MATOMO),
                'status' => $this->getSourceStatus(self::SOURCE_MATOMO),
                'health' => $this->checkSourceHealth(self::SOURCE_MATOMO),
            ],
            'internal' => [
                'enabled' => true,
                'last_sync' => $this->getLastSyncTimeForSource(self::SOURCE_INTERNAL),
                'status' => $this->getSourceStatus(self::SOURCE_INTERNAL),
                'health' => $this->checkSourceHealth(self::SOURCE_INTERNAL),
            ],
            'pending_discrepancies' => $this->countPendingDiscrepancies(),
            'updated_at' => now()->toIso8601String(),
        ];

        // Cache the status
        $this->cacheService->put($cacheKey, $status, self::CACHE_TTL_STATUS);

        return $status;
    }

    /**
     * Get synchronization history
     * 
     * @param int $limit Maximum number of records to return
     * @return array Sync history
     */
    public function getSyncHistory(int $limit = 50): array
    {
        $tenantId = $this->getCurrentTenantId();

        $query = SyncHistory::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        return $query->get()->map(function ($record) {
            return [
                'id' => $record->id,
                'source' => $record->source,
                'target' => $record->target,
                'status' => $record->status,
                'records_synced' => $record->records_synced,
                'error_message' => $record->error_message,
                'started_at' => $record->started_at->toIso8601String(),
                'completed_at' => $record->completed_at?->toIso8601String(),
                'duration_seconds' => $record->completed_at 
                    ? $record->started_at->diffInSeconds($record->completed_at) 
                    : null,
            ];
        })->toArray();
    }

    /**
     * Get unified data view combining all sources
     * 
     * @param array $dateRange Date range ['start' => 'Y-m-d', 'end' => 'Y-m-d']
     * @param array $metrics Metrics to include
     * @return array Unified data view
     */
    public function getUnifiedView(array $dateRange, array $metrics = []): array
    {
        $cacheKey = $this->getCacheKey('unified:' . md5(serialize($dateRange) . serialize($metrics)));

        // Try cache first
        $cachedView = $this->cacheService->get($cacheKey);
        if ($cachedView !== null) {
            return $cachedView;
        }

        $defaultMetrics = ['sessions', 'users', 'pageviews', 'events', 'bounce_rate', 'avg_session_duration'];
        $metrics = !empty($metrics) ? $metrics : $defaultMetrics;

        $unifiedData = [
            'date_range' => $dateRange,
            'metrics' => [],
            'sources' => [],
            'discrepancies' => [],
            'summary' => [],
            'generated_at' => now()->toIso8601String(),
        ];

        // Fetch data from each source
        $sources = [self::SOURCE_INTERNAL, self::SOURCE_GOOGLE_ANALYTICS, self::SOURCE_MATOMO];

        foreach ($sources as $source) {
            $sourceData = $this->fetchDataFromSource($source, $dateRange);
            
            if ($sourceData !== null) {
                $unifiedData['sources'][$source] = [
                    'available' => true,
                    'data' => $sourceData,
                    'fetched_at' => now()->toIso8601String(),
                ];

                // Extract requested metrics
                foreach ($metrics as $metric) {
                    if (!isset($unifiedData['metrics'][$metric])) {
                        $unifiedData['metrics'][$metric] = [
                            'values' => [],
                            'average' => null,
                            'min' => null,
                            'max' => null,
                        ];
                    }
                    
                    $unifiedData['metrics'][$metric]['values'][$source] = $sourceData[$metric] ?? 0;
                }
            } else {
                $unifiedData['sources'][$source] = [
                    'available' => false,
                    'error' => 'Failed to fetch data',
                ];
            }
        }

        // Calculate metric summaries
        foreach ($unifiedData['metrics'] as $metric => &$metricData) {
            $values = array_values($metricData['values']);
            $metricData['average'] = !empty($values) ? round(array_sum($values) / count($values), 2) : 0;
            $metricData['min'] = !empty($values) ? min($values) : 0;
            $metricData['max'] = !empty($values) ? max($values) : 0;
        }

        // Detect discrepancies across all sources
        if (count($unifiedData['sources']) >= 2) {
            $unifiedData['discrepancies'] = $this->detectDiscrepancies(
                self::SOURCE_GOOGLE_ANALYTICS,
                self::SOURCE_MATOMO,
                $dateRange
            );
        }

        // Generate summary
        $unifiedData['summary'] = $this->generateUnifiedSummary($unifiedData);

        // Cache the result
        $this->cacheService->put($cacheKey, $unifiedData, self::CACHE_TTL_UNIFIED_VIEW);

        return $unifiedData;
    }

    /**
     * Generate summary for unified data view
     */
    protected function generateUnifiedSummary(array $unifiedData): array
    {
        $summary = [
            'total_sources' => count($unifiedData['sources']),
            'active_sources' => count(array_filter($unifiedData['sources'], fn($s) => $s['available'] ?? false)),
            'discrepancy_count' => count($unifiedData['discrepancies']),
            'high_severity_count' => 0,
            'medium_severity_count' => 0,
            'low_severity_count' => 0,
        ];

        foreach ($unifiedData['discrepancies'] as $discrepancy) {
            $severity = $discrepancy['severity'] ?? 'low';
            $summary[$severity . '_severity_count']++;
        }

        return $summary;
    }

    /**
     * Monitor synchronization health
     * 
     * @return array Health monitoring data
     */
    public function monitorSync(): array
    {
        $tenantId = $this->getCurrentTenantId();

        Log::info('Monitoring sync health', ['tenant_id' => $tenantId]);

        $health = [
            'status' => 'healthy',
            'checks' => [],
            'alerts' => [],
            'timestamp' => now()->toIso8601String(),
        ];

        // Check Google Analytics health
        $gaHealth = $this->checkSourceHealth(self::SOURCE_GOOGLE_ANALYTICS);
        $health['checks']['google_analytics'] = $gaHealth;
        
        if (!$gaHealth['healthy']) {
            $health['status'] = 'degraded';
            $health['alerts'][] = [
                'source' => 'google_analytics',
                'message' => $gaHealth['message'] ?? 'Source is unhealthy',
                'severity' => 'warning',
            ];
        }

        // Check Matomo health
        $matomoHealth = $this->checkSourceHealth(self::SOURCE_MATOMO);
        $health['checks']['matomo'] = $matomoHealth;
        
        if (!$matomoHealth['healthy']) {
            $health['status'] = 'degraded';
            $health['alerts'][] = [
                'source' => 'matomo',
                'message' => $matomoHealth['message'] ?? 'Source is unhealthy',
                'severity' => 'warning',
            ];
        }

        // Check internal data health
        $internalHealth = $this->checkSourceHealth(self::SOURCE_INTERNAL);
        $health['checks']['internal'] = $internalHealth;

        // Check for stale data
        $lastSync = $this->getLastSyncTime();
        if ($lastSync) {
            $hoursSinceSync = now()->diffInHours($lastSync);
            if ($hoursSinceSync > 24) {
                $health['status'] = 'warning';
                $health['alerts'][] = [
                    'source' => 'sync',
                    'message' => "No sync in {$hoursSinceSync} hours",
                    'severity' => 'warning',
                ];
            }
        }

        // Check pending discrepancies
        $pendingCount = $this->countPendingDiscrepancies();
        if ($pendingCount > 10) {
            $health['alerts'][] = [
                'source' => 'discrepancies',
                'message' => "{$pendingCount} pending discrepancies",
                'severity' => $pendingCount > 50 ? 'critical' : 'warning',
            ];
        }

        return $health;
    }

    /**
     * Handle synchronization errors
     * 
     * @param \Exception|\Throwable $error The error to handle
     * @param array $context Additional context
     * @return array Error handling result
     */
    public function handleSyncError(\Throwable $error, array $context = []): array
    {
        $tenantId = $this->getCurrentTenantId();

        Log::error('Sync error occurred', [
            'error' => $error->getMessage(),
            'trace' => $error->getTraceAsString(),
            'tenant_id' => $tenantId,
            'context' => $context,
        ]);

        // Determine error severity
        $severity = $this->determineErrorSeverity($error);

        // Update sync status to reflect error
        $this->updateSyncStatus('error', $severity);

        return [
            'handled' => true,
            'error' => $error->getMessage(),
            'severity' => $severity,
            'timestamp' => now()->toIso8601String(),
            'tenant_id' => $tenantId,
        ];
    }

    /**
     * Determine error severity
     */
    protected function determineErrorSeverity(\Throwable $error): string
    {
        $errorMessage = strtolower($error->getMessage());

        // Critical errors
        if (
            str_contains($errorMessage, 'authentication') ||
            str_contains($errorMessage, 'authorization') ||
            str_contains($errorMessage, 'connection refused')
        ) {
            return 'critical';
        }

        // High severity errors
        if (
            str_contains($errorMessage, 'timeout') ||
            str_contains($errorMessage, 'rate limit')
        ) {
            return 'high';
        }

        // Medium severity
        if (
            str_contains($errorMessage, 'validation') ||
            str_contains($errorMessage, 'invalid data')
        ) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Update sync status in cache
     */
    protected function updateSyncStatus(string $status, string $severity = 'low'): void
    {
        $cacheKey = $this->getCacheKey('status');
        $currentStatus = $this->cacheService->get($cacheKey) ?? [];
        
        $currentStatus['last_error'] = [
            'status' => $status,
            'severity' => $severity,
            'timestamp' => now()->toIso8601String(),
        ];
        
        $this->cacheService->put($cacheKey, $currentStatus, self::CACHE_TTL_STATUS);
    }

    /**
     * Fetch data from a specific source
     */
    protected function fetchDataFromSource(string $source, array $dateRange): ?array
    {
        $startDate = $dateRange['start'] ?? '30daysAgo';
        $endDate = $dateRange['end'] ?? 'today';
        $metrics = ['sessions', 'users', 'pageviews', 'events'];
        $dimensions = [];

        return match ($source) {
            self::SOURCE_GOOGLE_ANALYTICS => $this->getGoogleAnalyticsData($startDate, $endDate, $metrics, $dimensions),
            self::SOURCE_MATOMO => $this->getMatomoData($startDate, $endDate, $metrics, $dimensions),
            self::SOURCE_INTERNAL => $this->getInternalData($dateRange),
            default => null,
        };
    }

    /**
     * Get Google Analytics data
     */
    protected function getGoogleAnalyticsData(string $startDate, string $endDate, array $metrics, array $dimensions): ?array
    {
        try {
            $reportRequest = [
                'date_ranges' => [
                    ['startDate' => $startDate, 'endDate' => $endDate],
                ],
                'metrics' => array_map(fn($m) => ['name' => $m], $metrics),
                'dimensions' => array_map(fn($d) => ['name' => $d], $dimensions),
            ];

            $report = $this->googleAnalyticsService->getReport($reportRequest);

            if ($report === null) {
                return null;
            }

            return $this->parseGoogleAnalyticsReport($report);
        } catch (\Exception $e) {
            Log::warning('Failed to fetch Google Analytics data', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get Matomo data
     */
    protected function getMatomoData(string $startDate, string $endDate, array $metrics, array $dimensions): ?array
    {
        try {
            $method = 'API.get';
            $params = [
                'period' => 'range',
                'date' => $startDate . ',' . $endDate,
            ];

            $report = $this->matomoService->getReport($method, $params);

            if ($report === null) {
                return null;
            }

            return $this->parseMatomoReport($report);
        } catch (\Exception $e) {
            Log::warning('Failed to fetch Matomo data', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get internal analytics data
     */
    protected function getInternalData(array $dateRange): array
    {
        // This would query the internal analytics tables
        // For now, return a placeholder structure
        return [
            'sessions' => 0,
            'users' => 0,
            'pageviews' => 0,
            'events' => 0,
            'bounce_rate' => 0,
            'avg_session_duration' => 0,
        ];
    }

    /**
     * Transform data for target source
     */
    protected function transformData(array $data, string $source, string $target): array
    {
        // Transform internal data to external platform format
        // This is a placeholder implementation
        return $data;
    }

    /**
     * Push data to target source
     */
    protected function pushDataToTarget(string $target, array $data): array
    {
        return match ($target) {
            self::SOURCE_GOOGLE_ANALYTICS => $this->pushToGoogleAnalytics($data),
            self::SOURCE_MATOMO => $this->pushToMatomo($data),
            default => ['success' => false, 'error' => 'Unknown target source'],
        };
    }

    /**
     * Push data to Google Analytics
     */
    protected function pushToGoogleAnalytics(array $data): array
    {
        // This would use the Google Analytics Measurement Protocol
        // For now, return success
        return ['success' => true, 'records' => count($data)];
    }

    /**
     * Push data to Matomo
     */
    protected function pushToMatomo(array $data): array
    {
        // This would use the Matomo Tracking API
        // For now, return success
        return ['success' => true, 'records' => count($data)];
    }

    /**
     * Parse Google Analytics report response
     */
    protected function parseGoogleAnalyticsReport(array $report): array
    {
        $data = [
            'sessions' => 0,
            'users' => 0,
            'pageviews' => 0,
            'events' => 0,
            'bounce_rate' => 0,
            'avg_session_duration' => 0,
        ];

        if (!isset($report['rows'])) {
            return $data;
        }

        foreach ($report['rows'] as $row) {
            if (isset($row['metricValues'])) {
                foreach ($row['metricValues'] as $index => $value) {
                    $metricName = $report['metricHeaders'][$index]['name'] ?? '';
                    $metricValue = $value['value'] ?? 0;

                    switch ($metricName) {
                        case 'sessions':
                            $data['sessions'] = (int) $metricValue;
                            break;
                        case 'activeUsers':
                            $data['users'] = (int) $metricValue;
                            break;
                        case 'screenPageViews':
                            $data['pageviews'] = (int) $metricValue;
                            break;
                        case 'eventCount':
                            $data['events'] = (int) $metricValue;
                            break;
                        case 'bounceRate':
                            $data['bounce_rate'] = (float) $metricValue;
                            break;
                        case 'averageSessionDuration':
                            $data['avg_session_duration'] = (float) $metricValue;
                            break;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Parse Matomo report response
     */
    protected function parseMatomoReport(array $report): array
    {
        $data = [
            'sessions' => 0,
            'users' => 0,
            'pageviews' => 0,
            'events' => 0,
            'bounce_rate' => 0,
            'avg_session_duration' => 0,
        ];

        if (isset($report['nb_visits'])) {
            $data['sessions'] = (int) $report['nb_visits'];
        }

        if (isset($report['nb_uniq_visitors'])) {
            $data['users'] = (int) $report['nb_uniq_visitors'];
        }

        if (isset($report['nb_actions'])) {
            $data['pageviews'] = (int) $report['nb_actions'];
        }

        if (isset($report['nb_events'])) {
            $data['events'] = (int) $report['nb_events'];
        }

        if (isset($report['bounce_rate'])) {
            $data['bounce_rate'] = (float) $report['bounce_rate'];
        }

        if (isset($report['avg_time_on_site'])) {
            $data['avg_session_duration'] = (float) $report['avg_time_on_site'];
        }

        return $data;
    }

    /**
     * Create sync history record
     */
    protected function createSyncRecord(string $source, string $target, array $dateRange): SyncHistory
    {
        return SyncHistory::create([
            'source' => $source,
            'target' => $target,
            'date_range_start' => $dateRange['start'] ?? null,
            'date_range_end' => $dateRange['end'] ?? null,
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'tenant_id' => $this->getCurrentTenantId(),
        ]);
    }

    /**
     * Update sync history record
     */
    protected function updateSyncRecord(SyncHistory $record, string $status, ?string $errorMessage = null, array $result = []): void
    {
        $updateData = [
            'status' => $status,
            'completed_at' => now(),
            'error_message' => $errorMessage,
            'records_synced' => $result['records'] ?? 0,
        ];

        $record->update($updateData);
    }

    /**
     * Get last sync time
     */
    protected function getLastSyncTime(): ?\Carbon\Carbon
    {
        $tenantId = $this->getCurrentTenantId();

        $lastSync = SyncHistory::where('tenant_id', $tenantId)
            ->where('status', self::STATUS_COMPLETED)
            ->orderBy('completed_at', 'desc')
            ->first();

        return $lastSync?->completed_at;
    }

    /**
     * Get last sync time for a specific source
     */
    protected function getLastSyncTimeForSource(string $source): ?\Carbon\Carbon
    {
        $tenantId = $this->getCurrentTenantId();

        $lastSync = SyncHistory::where('tenant_id', $tenantId)
            ->where(function ($query) use ($source) {
                $query->where('source', $source)
                    ->orWhere('target', $source);
            })
            ->where('status', self::STATUS_COMPLETED)
            ->orderBy('completed_at', 'desc')
            ->first();

        return $lastSync?->completed_at;
    }

    /**
     * Get status for a specific source
     */
    protected function getSourceStatus(string $source): string
    {
        $tenantId = $this->getCurrentTenantId();

        $recentSync = SyncHistory::where('tenant_id', $tenantId)
            ->where(function ($query) use ($source) {
                $query->where('source', $source)
                    ->orWhere('target', $source);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        return $recentSync?->status ?? 'never_synced';
    }

    /**
     * Check health of a data source
     */
    protected function checkSourceHealth(string $source): array
    {
        return match ($source) {
            self::SOURCE_GOOGLE_ANALYTICS => $this->googleAnalyticsService->validateConfiguration(),
            self::SOURCE_MATOMO => $this->matomoService->validateConfiguration(),
            self::SOURCE_INTERNAL => [
                'healthy' => true,
                'message' => 'Internal data source is healthy',
            ],
            default => [
                'healthy' => false,
                'message' => 'Unknown data source',
            ],
        };
    }

    /**
     * Count pending discrepancies
     */
    protected function countPendingDiscrepancies(): int
    {
        $tenantId = $this->getCurrentTenantId();

        return Discrepancy::where('tenant_id', $tenantId)
            ->where('resolved', false)
            ->count();
    }

    /**
     * Invalidate caches related to sync
     */
    protected function invalidateRelatedCaches(): void
    {
        $this->cacheService->forget($this->getCacheKey('status'));
        $this->cacheService->forget($this->getCacheKey('unified:*'));
        $this->cacheService->forget($this->getCacheKey('discrepancies:*'));
    }

    /**
     * Validate sync configuration
     * 
     * @return array Validation results
     */
    public function validateConfiguration(): array
    {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'platforms' => [],
        ];

        // Validate Google Analytics
        $gaValidation = $this->googleAnalyticsService->validateConfiguration();
        $results['platforms']['google_analytics'] = $gaValidation;
        if (!$gaValidation['valid']) {
            $results['valid'] = false;
            $results['errors'] = array_merge($results['errors'], $gaValidation['errors']);
        }
        $results['warnings'] = array_merge($results['warnings'], $gaValidation['warnings'] ?? []);

        // Validate Matomo
        $matomoValidation = $this->matomoService->validateConfiguration();
        $results['platforms']['matomo'] = $matomoValidation;
        if (!$matomoValidation['valid']) {
            $results['valid'] = false;
            $results['errors'] = array_merge($results['errors'], $matomoValidation['errors']);
        }
        $results['warnings'] = array_merge($results['warnings'], $matomoValidation['warnings'] ?? []);

        return $results;
    }

    /**
     * Sync data to external platforms
     * 
     * @param array $events Events to sync
     * @param array $options Sync options
     * @return array Sync results
     */
    public function syncToExternal(array $events, array $options = []): array
    {
        $results = [
            'google_analytics' => ['success' => 0, 'failed' => 0],
            'matomo' => ['success' => 0, 'failed' => 0],
            'errors' => [],
        ];

        $syncToGoogle = $options['sync_to_google'] ?? true;
        $syncToMatomo = $options['sync_to_matomo'] ?? true;
        $tenantId = $options['tenant_id'] ?? $this->getCurrentTenantId();

        // Sync to Google Analytics
        if ($syncToGoogle) {
            $gaResults = $this->googleAnalyticsService->batchForwardEvents($events, $tenantId);
            $results['google_analytics'] = [
                'success' => $gaResults['success'],
                'failed' => $gaResults['failed'],
            ];
            $results['errors'] = array_merge($results['errors'], $gaResults['errors'] ?? []);
        }

        // Sync to Matomo
        if ($syncToMatomo) {
            $matomoResults = $this->matomoService->batchForwardEvents($events, $tenantId);
            $results['matomo'] = [
                'success' => $matomoResults['success'],
                'failed' => $matomoResults['failed'],
            ];
            $results['errors'] = array_merge($results['errors'], $matomoResults['errors'] ?? []);
        }

        return $results;
    }
}
