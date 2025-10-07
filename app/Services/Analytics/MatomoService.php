<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Matomo Service for handling event tracking and data synchronization
 */
class MatomoService
{
    private Client $httpClient;
    private ConsentService $consentService;

    public function __construct(ConsentService $consentService)
    {
        $this->consentService = $consentService;
        $this->httpClient = new Client([
            'timeout' => config('services.matomo.timeout', 10),
            'connect_timeout' => config('services.matomo.connect_timeout', 5),
        ]);
    }

    /**
     * Track an event in Matomo
     *
     * @param array $eventData Event data to track
     * @param string|null $tenantId Tenant identifier for custom dimension mapping
     * @return bool Success status
     */
    public function trackEvent(array $eventData, ?string $tenantId = null): bool
    {
        // Check if user has given consent before tracking
        if (!$this->consentService->hasConsent()) {
            Log::info('Matomo event tracking skipped due to missing user consent', [
                'event_name' => $eventData['name'] ?? 'unknown',
                'tenant_id' => $tenantId,
            ]);
            return false;
        }

        try {
            $matomoUrl = config('services.matomo.url');
            $siteId = config('services.matomo.site_id');
            $tokenAuth = config('services.matomo.token_auth');

            if (empty($matomoUrl) || empty($siteId) || empty($tokenAuth)) {
                Log::warning('Matomo credentials not configured', [
                    'event_name' => $eventData['name'] ?? 'unknown',
                    'tenant_id' => $tenantId,
                ]);
                return false;
            }

            // Prepare parameters for Matomo API
            $params = [
                'idsite' => $siteId,
                'rec' => 1, // Record the visit
                'url' => $eventData['url'] ?? url()->current(),
                'action_name' => $eventData['name'] ?? 'Custom Event',
                'ua' => $eventData['user_agent'] ?? request()->userAgent(),
                '_id' => $eventData['client_id'] ?? Str::uuid()->toString(),
                'cid' => $eventData['client_id'] ?? Str::uuid()->toString(),
                'lang' => $eventData['language'] ?? app()->getLocale(),
                // Event-specific parameters
                'e_c' => $eventData['category'] ?? 'alumni_engagement', // Event category
                'e_a' => $eventData['action'] ?? 'action', // Event action
                'e_n' => $eventData['name'] ?? 'event_name', // Event name
                'e_v' => $eventData['value'] ?? null, // Event value
            ];

            // Add custom dimensions for tenant
            if ($tenantId) {
                $params['dimension1'] = $tenantId; // Assuming dimension1 is for tenant_id
            }

            // Add any additional custom parameters from the original event
            if (isset($eventData['custom_params']) && is_array($eventData['custom_params'])) {
                foreach ($eventData['custom_params'] as $key => $value) {
                    $params[$key] = $value;
                }
            }

            // Build the URL for Matomo tracking
            $trackingUrl = rtrim($matomoUrl, '/') . '/piwik.php?' . http_build_query($params);

            $response = $this->httpClient->get($trackingUrl);

            // Log successful tracking
            Log::debug('Matomo event tracked successfully', [
                'event_name' => $eventData['name'] ?? 'unknown',
                'tenant_id' => $tenantId,
                'status_code' => $response->getStatusCode(),
            ]);

            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            Log::error('Failed to track event in Matomo', [
                'error' => $e->getMessage(),
                'event_name' => $eventData['name'] ?? 'unknown',
                'tenant_id' => $tenantId,
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Unexpected error during Matomo event tracking', [
                'error' => $e->getMessage(),
                'event_name' => $eventData['name'] ?? 'unknown',
                'tenant_id' => $tenantId,
            ]);
            return false;
        }
    }

    /**
     * Sync data between our system and Matomo
     *
     * @param array $syncData Data to sync
     * @return bool Success status
     */
    public function syncData(array $syncData): bool
    {
        Log::info('Matomo data sync initiated', [
            'sync_items_count' => count($syncData),
        ]);

        try {
            $matomoUrl = config('services.matomo.url');
            $siteId = config('services.matomo.site_id');
            $tokenAuth = config('services.matomo.token_auth');

            if (empty($matomoUrl) || empty($siteId) || empty($tokenAuth)) {
                Log::warning('Matomo credentials not configured for data sync');
                return false;
            }

            // Dispatch job for heavy sync operation
            \App\Jobs\MatomoSyncJob::dispatch($syncData, 'sync');

            Log::info('Matomo data sync job dispatched', [
                'sync_items_count' => count($syncData),
                'site_id' => $siteId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch Matomo data sync job', [
                'error' => $e->getMessage(),
                'sync_data' => $syncData,
            ]);
            return false;
        }
    }

    /**
     * Get report data from Matomo
     *
     * @param string $method API method to call
     * @param array $params Additional parameters for the API call
     * @return array|null API response data or null on failure
     */
    public function getReport(string $method, array $params = []): ?array
    {
        try {
            $matomoUrl = config('services.matomo.url');
            $siteId = config('services.matomo.site_id');
            $tokenAuth = config('services.matomo.token_auth');

            if (empty($matomoUrl) || empty($siteId) || empty($tokenAuth)) {
                Log::warning('Matomo credentials not configured for report retrieval');
                return null;
            }

            $apiParams = array_merge([
                'module' => 'API',
                'method' => $method,
                'idSite' => $siteId,
                'period' => 'day',
                'date' => 'today',
                'format' => 'JSON',
                'token_auth' => $tokenAuth,
            ], $params);

            $apiUrl = rtrim($matomoUrl, '/') . '/index.php?' . http_build_query($apiParams);

            $response = $this->httpClient->get($apiUrl);
            $responseData = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode Matomo API response', [
                    'method' => $method,
                    'error' => json_last_error_msg(),
                ]);
                return null;
            }

            return $responseData;
        } catch (GuzzleException $e) {
            Log::error('Failed to retrieve report from Matomo', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Unexpected error during Matomo report retrieval', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}