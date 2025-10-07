<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Google Analytics Service for handling event forwarding, goal sync, and segment export
 */
class GoogleAnalyticsService
{
    private Client $httpClient;
    private ConsentService $consentService;

    public function __construct(ConsentService $consentService)
    {
        $this->consentService = $consentService;
        $this->httpClient = new Client([
            'timeout' => config('services.google.analytics.timeout', 10),
            'connect_timeout' => config('services.google.analytics.connect_timeout', 5),
        ]);
    }

    /**
     * Forward an internal event to Google Analytics 4 via Measurement Protocol
     *
     * @param array $eventData Event data to forward
     * @param string|null $tenantId Tenant identifier for custom dimension mapping
     * @param string|null $userSegment User segment for custom dimension mapping
     * @return bool Success status
     */
    public function forwardEvent(array $eventData, ?string $tenantId = null, ?string $userSegment = null): bool
    {
        // Check if user has given consent before forwarding
        if (!$this->consentService->hasConsent()) {
            Log::info('Google Analytics event forwarding skipped due to missing user consent', [
                'event_name' => $eventData['name'] ?? 'unknown',
                'tenant_id' => $tenantId,
            ]);
            return false;
        }

        try {
            $measurementId = config('services.google.analytics.measurement_id');
            $apiSecret = config('services.google.analytics.api_secret');

            if (empty($measurementId) || empty($apiSecret)) {
                Log::warning('Google Analytics credentials not configured', [
                    'event_name' => $eventData['name'] ?? 'unknown',
                    'tenant_id' => $tenantId,
                ]);
                return false;
            }

            // Map internal event to GA4 parameters
            $gaEvent = [
                'name' => $eventData['name'] ?? 'custom_event',
                'params' => [
                    'event_category' => $eventData['category'] ?? 'alumni_engagement',
                    'event_label' => $eventData['label'] ?? null,
                    'value' => $eventData['value'] ?? null,
                ],
            ];

            // Add custom dimensions for tenant and user segment
            if ($tenantId) {
                $gaEvent['params']['custom_parameter_tenant_id'] = $tenantId;
            }
            
            if ($userSegment) {
                $gaEvent['params']['custom_parameter_user_segment'] = $userSegment;
            }

            // Add any additional custom parameters from the original event
            if (isset($eventData['custom_params']) && is_array($eventData['custom_params'])) {
                foreach ($eventData['custom_params'] as $key => $value) {
                    $gaEvent['params'][$key] = $value;
                }
            }

            // Prepare the payload for GA4 Measurement Protocol
            $payload = [
                'client_id' => $eventData['client_id'] ?? Str::uuid()->toString(),
                'events' => [$gaEvent],
            ];

            // Add user properties if available
            if (isset($eventData['user_properties']) && is_array($eventData['user_properties'])) {
                $payload['user_properties'] = $eventData['user_properties'];
            }

            $url = "https://www.google-analytics.com/mp/collect?measurement_id={$measurementId}&api_secret={$apiSecret}";

            $response = $this->httpClient->post($url, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            // Log successful forwarding
            Log::debug('Google Analytics event forwarded successfully', [
                'event_name' => $eventData['name'] ?? 'unknown',
                'tenant_id' => $tenantId,
                'status_code' => $response->getStatusCode(),
            ]);

            return $response->getStatusCode() === 204;
        } catch (GuzzleException $e) {
            Log::error('Failed to forward event to Google Analytics', [
                'error' => $e->getMessage(),
                'event_name' => $eventData['name'] ?? 'unknown',
                'tenant_id' => $tenantId,
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Unexpected error during Google Analytics event forwarding', [
                'error' => $e->getMessage(),
                'event_name' => $eventData['name'] ?? 'unknown',
                'tenant_id' => $tenantId,
            ]);
            return false;
        }
    }

    /**
     * Sync goals from application funnels to Google Analytics
     *
     * @param array $funnelData Funnel data to sync as GA goals
     * @return bool Success status
     */
    public function syncGoals(array $funnelData): bool
    {
        Log::info('Google Analytics goal sync initiated', [
            'funnel_count' => count($funnelData),
        ]);

        try {
            $propertyId = config('services.google.analytics.property_id');
            $apiSecret = config('services.google.analytics.api_secret');

            if (empty($propertyId) || empty($apiSecret)) {
                Log::warning('Google Analytics Admin API credentials not configured for goal sync');
                return false;
            }

            // Dispatch job for heavy sync operation
            \App\Jobs\GoogleSyncJob::dispatch($funnelData, 'goals');

            Log::info('Google Analytics goal sync job dispatched', [
                'funnel_count' => count($funnelData),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch Google Analytics goal sync job', [
                'error' => $e->getMessage(),
                'funnel_data' => $funnelData,
            ]);
            return false;
        }
    }

    /**
     * Export segments to Google Analytics for audience sharing
     *
     * @param array $segmentData Segment data to export
     * @return bool Success status
     */
    public function exportSegments(array $segmentData): bool
    {
        Log::info('Google Analytics segment export initiated', [
            'segment_count' => count($segmentData),
        ]);

        try {
            $propertyId = config('services.google.analytics.property_id');
            $apiSecret = config('services.google.analytics.api_secret');

            if (empty($propertyId) || empty($apiSecret)) {
                Log::warning('Google Analytics Admin API credentials not configured for segment export');
                return false;
            }

            // Dispatch job for heavy sync operation
            \App\Jobs\GoogleSyncJob::dispatch($segmentData, 'segments');

            Log::info('Google Analytics segment export job dispatched', [
                'segment_count' => count($segmentData),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch Google Analytics segment export job', [
                'error' => $e->getMessage(),
                'segment_data' => $segmentData,
            ]);
            return false;
        }
    }
}