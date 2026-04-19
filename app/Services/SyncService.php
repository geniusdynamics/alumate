<?php

namespace App\Services;

use App\Services\Analytics\GoogleAnalyticsService;
use App\Services\Analytics\MatomoService;
use App\Services\Analytics\ConsentService;
use Illuminate\Support\Facades\Log;

/**
 * SyncService handles synchronization between internal analytics and external platforms
 */
class SyncService
{
    protected GoogleAnalyticsService $googleAnalyticsService;
    protected MatomoService $matomoService;
    protected ConsentService $consentService;

    public function __construct(
        GoogleAnalyticsService $googleAnalyticsService,
        MatomoService $matomoService,
        ConsentService $consentService
    ) {
        $this->googleAnalyticsService = $googleAnalyticsService;
        $this->matomoService = $matomoService;
        $this->consentService = $consentService;
    }

    /**
     * Sync data to external analytics platforms
     *
     * @param array $eventData The event data to sync
     * @param string|null $tenantId The tenant ID for the event
     * @param string|null $userSegment The user segment for the event
     * @return array Results of the sync operations
     */
    public function syncToExternal(array $eventData, ?string $tenantId = null, ?string $userSegment = null): array
    {
        $results = [
            'google_analytics' => false,
            'matomo' => false,
            'discrepancies' => [],
        ];

        try {
            // Check if user has given consent before syncing
            if (!$this->consentService->hasConsent()) {
                Log::info('External sync skipped due to missing user consent', [
                    'event_name' => $eventData['name'] ?? 'unknown',
                    'tenant_id' => $tenantId,
                ]);
                return $results;
            }

            // Forward event to Google Analytics
            $gaResult = $this->googleAnalyticsService->forwardEvent($eventData, $tenantId, $userSegment);
            $results['google_analytics'] = $gaResult;

            // Track event in Matomo
            $matomoResult = $this->matomoService->trackEvent($eventData, $tenantId);
            $results['matomo'] = $matomoResult;

            // Check for discrepancies between platforms (difference > 5%)
            if ($this->hasDiscrepancy($gaResult, $matomoResult)) {
                $discrepancy = [
                    'timestamp' => now(),
                    'event_data' => $eventData,
                    'tenant_id' => $tenantId,
                    'google_analytics_result' => $gaResult,
                    'matomo_result' => $matomoResult,
                    'discrepancy_detected' => true,
                ];

                $results['discrepancies'][] = $discrepancy;

                Log::warning('Discrepancy detected between external analytics platforms', $discrepancy);

                // Attempt resolution
                $this->resolveDiscrepancy($discrepancy);
            }

            Log::debug('External sync completed', [
                'event_name' => $eventData['name'] ?? 'unknown',
                'tenant_id' => $tenantId,
                'google_analytics' => $gaResult,
                'matomo' => $matomoResult,
            ]);

        } catch (\Exception $e) {
            Log::error('Error during external sync', [
                'error' => $e->getMessage(),
                'event_data' => $eventData,
                'tenant_id' => $tenantId,
            ]);

            // Log the error but don't fail the entire sync process
            // External sync failures shouldn't break the main application flow
        }

        return $results;
    }

    /**
     * Check if there's a significant discrepancy between sync results
     *
     * @param mixed $result1 First sync result
     * @param mixed $result2 Second sync result
     * @return bool Whether a discrepancy exists
     */
    protected function hasDiscrepancy($result1, $result2): bool
    {
        // For now, we'll consider any difference in boolean success/failure as a discrepancy
        // In a more complex implementation, we might compare actual data values
        return $result1 !== $result2;
    }

    /**
     * Attempt to resolve discrepancies between analytics platforms
     *
     * @param array $discrepancy The discrepancy data
     * @return void
     */
    protected function resolveDiscrepancy(array $discrepancy): void
    {
        Log::info('Attempting to resolve analytics discrepancy', [
            'discrepancy' => $discrepancy,
        ]);

        // In a real implementation, this would:
        // 1. Retry failed syncs
        // 2. Compare data values
        // 3. Log detailed resolution attempts
        // 4. Potentially alert administrators for persistent discrepancies

        // For now, we'll just log the discrepancy for review
        // Additional resolution logic would go here
    }

    /**
     * Sync goals from internal funnels to external platforms
     *
     * @param array $funnelData The funnel data to sync as goals
     * @return array Results of the goal sync operations
     */
    public function syncGoalsToExternal(array $funnelData): array
    {
        $results = [
            'google_analytics' => false,
            'matomo' => false,
        ];

        try {
            // Sync goals to Google Analytics
            $results['google_analytics'] = $this->googleAnalyticsService->syncGoals($funnelData);

            // In a real implementation, we would also sync to Matomo
            // $results['matomo'] = $this->matomoService->syncGoals($funnelData);

            Log::debug('Goal sync completed', [
                'funnel_count' => count($funnelData),
                'google_analytics' => $results['google_analytics'],
                'matomo' => $results['matomo'],
            ]);

        } catch (\Exception $e) {
            Log::error('Error during goal sync', [
                'error' => $e->getMessage(),
                'funnel_data' => $funnelData,
            ]);
        }

        return $results;
    }

    /**
     * Sync segments to external platforms
     *
     * @param array $segmentData The segment data to sync
     * @return array Results of the segment sync operations
     */
    public function syncSegmentsToExternal(array $segmentData): array
    {
        $results = [
            'google_analytics' => false,
        ];

        try {
            // Export segments to Google Analytics
            $results['google_analytics'] = $this->googleAnalyticsService->exportSegments($segmentData);

            Log::debug('Segment sync completed', [
                'segment_count' => count($segmentData),
                'google_analytics' => $results['google_analytics'],
            ]);

        } catch (\Exception $e) {
            Log::error('Error during segment sync', [
                'error' => $e->getMessage(),
                'segment_data' => $segmentData,
            ]);
        }

        return $results;
    }
}