<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Services\CacheService;
use App\Services\TenantContextService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Google Analytics Service for handling event forwarding, goal sync, and segment export
 *
 * This service provides integration with Google Analytics 4 (GA4) via the Measurement Protocol
 * and Analytics Data API. It supports tenant isolation, custom dimension mapping, audience segments,
 * and comprehensive caching for improved performance.
 */
class GoogleAnalyticsService
{
    private Client $httpClient;

    private ConsentService $consentService;

    private ?TenantContextService $tenantContextService = null;

    private CacheService $cacheService;

    // Cache TTL constants
    private const CACHE_TTL_SEGMENTS = 1800; // 30 minutes

    private const CACHE_TTL_GOALS = 1800; // 30 minutes

    private const CACHE_TTL_CONFIG = 300; // 5 minutes

    private const CACHE_TTL_REALTIME = 60; // 1 minute

    /**
     * Constructor for GoogleAnalyticsService
     *
     * @param  ConsentService  $consentService  Service for checking user consent
     * @param  TenantContextService|null  $tenantContextService  Optional tenant context for isolation
     * @param  CacheService|null  $cacheService  Optional cache service for performance
     */
    public function __construct(
        ConsentService $consentService,
        ?TenantContextService $tenantContextService = null,
        ?CacheService $cacheService = null
    ) {
        $this->consentService = $consentService;
        $this->tenantContextService = $tenantContextService;
        $this->cacheService = $cacheService ?? resolve(CacheService::class);

        $this->httpClient = new Client([
            'timeout' => config('services.google.analytics.timeout', 30),
            'connect_timeout' => config('services.google.analytics.connect_timeout', 10),
            'verify' => config('services.google.analytics.verify_ssl', true),
        ]);
    }

    /**
     * Get the current tenant ID from context
     *
     * @return string|null Current tenant ID
     */
    protected function getCurrentTenantId(): ?string
    {
        if ($this->tenantContextService !== null) {
            return $this->tenantContextService->getCurrentTenantId();
        }

        // Fallback to request header if tenant context service is not available
        return request()->header('X-Tenant');
    }

    /**
     * Get HTTP client (for testing purposes)
     *
     * @return Client HTTP client instance
     */
    public function getHttpClient(): Client
    {
        return $this->httpClient;
    }

    /**
     * Forward an internal event to Google Analytics 4 via Measurement Protocol
     *
     * This method forwards analytics events to GA4 using the Measurement Protocol API.
     * It includes tenant isolation, custom dimension mapping, and proper consent handling.
     *
     * @param  array  $eventData  Event data to forward containing name, category, label, value, etc.
     * @param  string|null  $tenantId  Tenant identifier for custom dimension mapping (auto-detected if null)
     * @param  string|null  $userSegment  User segment for custom dimension mapping
     * @return bool Success status
     */
    public function forwardEvent(array $eventData, ?string $tenantId = null, ?string $userSegment = null): bool
    {
        // Get tenant ID from context if not provided
        $tenantId = $tenantId ?? $this->getCurrentTenantId();

        // Check if user has given consent before forwarding
        if (! $this->consentService->hasConsent()) {
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
                'name' => $this->normalizeEventName($eventData['name'] ?? 'custom_event'),
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

            if ($userSegment ?? isset($eventData['user_segment'])) {
                $gaEvent['params']['custom_parameter_user_segment'] = $userSegment ?? $eventData['user_segment'];
            }

            // Map additional custom dimensions from the event
            $customDimensions = $this->mapCustomDimensions($eventData, $tenantId);
            foreach ($customDimensions as $key => $value) {
                if ($value !== null) {
                    $gaEvent['params'][$key] = $value;
                }
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
     * Batch forward multiple events to Google Analytics
     *
     * This method efficiently forwards multiple events in a single batch request
     * to reduce API calls and improve performance.
     *
     * @param  array  $events  Array of event data to forward
     * @param  string|null  $tenantId  Tenant identifier for custom dimension mapping
     * @param  string|null  $userSegment  User segment for custom dimension mapping
     * @return array Results with success/failure counts and details
     */
    public function batchForwardEvents(array $events, ?string $tenantId = null, ?string $userSegment = null): array
    {
        $results = [
            'total' => count($events),
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Get tenant ID from context if not provided
        $tenantId = $tenantId ?? $this->getCurrentTenantId();

        foreach ($events as $index => $eventData) {
            $success = $this->forwardEvent($eventData, $tenantId, $userSegment);

            if ($success) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'index' => $index,
                    'event_name' => $eventData['name'] ?? 'unknown',
                    'error' => 'Failed to forward event',
                ];
            }
        }

        Log::info('Google Analytics batch event forwarding completed', [
            'total' => count($events),
            'success' => $results['success'],
            'failed' => $results['failed'],
            'tenant_id' => $tenantId,
        ]);

        return $results;
    }

    /**
     * Map custom dimensions from internal data to GA4 format
     *
     * This method maps internal analytics data properties to Google Analytics
     * custom dimensions based on the configured dimension mapping.
     *
     * @param  array  $internalData  Internal analytics data containing various properties
     * @param  string|null  $tenantId  Tenant identifier
     * @return array Mapped custom dimensions in GA4 format
     */
    public function mapCustomDimensions(array $internalData, ?string $tenantId = null): array
    {
        $customDimensions = [];

        // Get tenant ID from context if not provided
        $tenantId = $tenantId ?? $this->getCurrentTenantId();

        // Get custom dimension mapping from config
        $dimensionMapping = config('services.google.analytics.custom_dimensions', [
            'tenant_id' => 'custom_dimension_tenant',
            'user_segment' => 'custom_dimension_user_segment',
            'cohort_id' => 'custom_dimension_cohort',
            'graduation_year' => 'custom_dimension_graduation_year',
            'program_of_study' => 'custom_dimension_program',
            'engagement_level' => 'custom_dimension_engagement',
            'alumni_status' => 'custom_dimension_alumni_status',
            'connection_count' => 'custom_dimension_connections',
        ]);

        // Map tenant ID
        if ($tenantId && isset($dimensionMapping['tenant_id'])) {
            $customDimensions[$dimensionMapping['tenant_id']] = $tenantId;
        }

        // Map user segment
        if (isset($internalData['user_segment']) && isset($dimensionMapping['user_segment'])) {
            $customDimensions[$dimensionMapping['user_segment']] = $internalData['user_segment'];
        }

        // Map cohort ID
        if (isset($internalData['cohort_id']) && isset($dimensionMapping['cohort_id'])) {
            $customDimensions[$dimensionMapping['cohort_id']] = (string) $internalData['cohort_id'];
        }

        // Map graduation year
        if (isset($internalData['graduation_year']) && isset($dimensionMapping['graduation_year'])) {
            $customDimensions[$dimensionMapping['graduation_year']] = (string) $internalData['graduation_year'];
        }

        // Map program of study
        if (isset($internalData['program_of_study']) && isset($dimensionMapping['program_of_study'])) {
            $customDimensions[$dimensionMapping['program_of_study']] = $internalData['program_of_study'];
        }

        // Map engagement level
        if (isset($internalData['engagement_level']) && isset($dimensionMapping['engagement_level'])) {
            $customDimensions[$dimensionMapping['engagement_level']] = $internalData['engagement_level'];
        }

        // Map alumni status
        if (isset($internalData['alumni_status']) && isset($dimensionMapping['alumni_status'])) {
            $customDimensions[$dimensionMapping['alumni_status']] = $internalData['alumni_status'];
        }

        // Map connection count
        if (isset($internalData['connection_count']) && isset($dimensionMapping['connection_count'])) {
            $customDimensions[$dimensionMapping['connection_count']] = (int) $internalData['connection_count'];
        }

        return $customDimensions;
    }

    /**
     * Sync goals from application funnels to Google Analytics
     *
     * This method synchronizes conversion goals from the application to GA4
     * using the Admin API for property-level configuration.
     *
     * @param  array  $funnelData  Array of funnel data to sync as GA goals
     * @return bool Success status
     */
    public function syncGoals(array $funnelData): bool
    {
        Log::info('Google Analytics goal sync initiated', [
            'funnel_count' => count($funnelData),
            'tenant_id' => $this->getCurrentTenantId(),
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
     * Create an audience segment in Google Analytics
     *
     * This method creates a new audience segment in GA4 based on the provided
     * criteria including user segments, engagement levels, and behavioral filters.
     *
     * @param  array  $criteria  Segment criteria including name, description, and filters
     * @return array|null Created segment data or null on failure
     */
    public function createAudienceSegment(array $criteria): ?array
    {
        Log::info('Creating Google Analytics audience segment', [
            'segment_name' => $criteria['name'] ?? 'unknown',
            'tenant_id' => $this->getCurrentTenantId(),
        ]);

        try {
            $propertyId = config('services.google.analytics.property_id');
            $apiSecret = config('services.google.analytics.api_secret');

            if (empty($propertyId) || empty($apiSecret)) {
                Log::warning('Google Analytics Admin API credentials not configured for segment creation');

                return null;
            }

            // Build segment definition from criteria
            $segmentData = [
                'displayName' => $criteria['name'] ?? 'Custom Audience Segment',
                'description' => $criteria['description'] ?? '',
                'segmentFilters' => $this->buildSegmentFilters($criteria['criteria'] ?? []),
            ];

            // Add audience type if specified
            if (isset($criteria['audience_type'])) {
                $segmentData['audienceType'] = $criteria['audience_type'];
            }

            // Dispatch job for segment creation
            \App\Jobs\GoogleSyncJob::dispatch([$segmentData], 'create_segment');

            Log::info('Google Analytics audience segment creation job dispatched', [
                'segment_name' => $segmentData['displayName'],
            ]);

            // Invalidate segments cache
            $this->invalidateSegmentsCache();

            return $segmentData;
        } catch (\Exception $e) {
            Log::error('Failed to create Google Analytics audience segment', [
                'error' => $e->getMessage(),
                'criteria' => $criteria,
            ]);

            return null;
        }
    }

    /**
     * Build segment filters from criteria array
     *
     * @param  array  $criteria  Segment criteria
     * @return array GA4 segment filters
     */
    protected function buildSegmentFilters(array $criteria): array
    {
        $filters = [];

        // User segment filter
        if (isset($criteria['user_segment'])) {
            $filters[] = [
                'filterExpression' => [
                    'filter' => [
                        'fieldName' => 'customEvent:user_segment',
                        'stringFilter' => [
                            'matchType' => 'EXACT',
                            'value' => $criteria['user_segment'],
                        ],
                    ],
                ],
            ];
        }

        // Engagement level filter
        if (isset($criteria['min_engagement'])) {
            $filters[] = [
                'filterExpression' => [
                    'filter' => [
                        'fieldName' => 'eventCount',
                        'numericFilter' => [
                            'operation' => 'GREATER_THAN_OR_EQUAL',
                            'value' => [
                                'int64Value' => (int) $criteria['min_engagement'],
                            ],
                        ],
                    ],
                ],
            ];
        }

        // Days since last activity filter
        if (isset($criteria['days_since_last_activity'])) {
            $filters[] = [
                'filterExpression' => [
                    'filter' => [
                        'fieldName' => 'daysSinceLastEvent',
                        'numericFilter' => [
                            'operation' => 'LESS_THAN_OR_EQUAL',
                            'value' => [
                                'int64Value' => (int) $criteria['days_since_last_activity'],
                            ],
                        ],
                    ],
                ],
            ];
        }

        // Cohort filter
        if (isset($criteria['cohort_id'])) {
            $filters[] = [
                'filterExpression' => [
                    'filter' => [
                        'fieldName' => 'customEvent:cohort_id',
                        'stringFilter' => [
                            'matchType' => 'EXACT',
                            'value' => (string) $criteria['cohort_id'],
                        ],
                    ],
                ],
            ];
        }

        // Graduation year filter
        if (isset($criteria['graduation_year'])) {
            $filters[] = [
                'filterExpression' => [
                    'filter' => [
                        'fieldName' => 'customEvent:graduation_year',
                        'stringFilter' => [
                            'matchType' => 'EXACT',
                            'value' => (string) $criteria['graduation_year'],
                        ],
                    ],
                ],
            ];
        }

        // Alumni status filter
        if (isset($criteria['alumni_status'])) {
            $filters[] = [
                'filterExpression' => [
                    'filter' => [
                        'fieldName' => 'customEvent:alumni_status',
                        'stringFilter' => [
                            'matchType' => 'EXACT',
                            'value' => $criteria['alumni_status'],
                        ],
                    ],
                ],
            ];
        }

        return $filters;
    }

    /**
     * Share an audience segment for broader access
     *
     * This method shares an existing audience segment with additional users,
     * user groups, or makes it available across the property.
     *
     * @param  string  $segmentId  The ID of the segment to share
     * @param  array  $shareOptions  Sharing options including recipients and permissions
     * @return bool Success status
     */
    public function shareAudienceSegment(string $segmentId, array $shareOptions = []): bool
    {
        Log::info('Sharing Google Analytics audience segment', [
            'segment_id' => $segmentId,
            'share_options' => $shareOptions,
            'tenant_id' => $this->getCurrentTenantId(),
        ]);

        try {
            $propertyId = config('services.google.analytics.property_id');
            $apiSecret = config('services.google.analytics.api_secret');

            if (empty($propertyId) || empty($apiSecret)) {
                Log::warning('Google Analytics Admin API credentials not configured for segment sharing');

                return false;
            }

            // Prepare share configuration
            $shareConfig = [
                'segmentId' => $segmentId,
                'sharedWith' => $shareOptions['shared_with'] ?? [],
                'permissions' => $shareOptions['permissions'] ?? ['view'],
                'notifyUsers' => $shareOptions['notify_users'] ?? true,
            ];

            // Dispatch job for segment sharing
            \App\Jobs\GoogleSyncJob::dispatch([$shareConfig], 'share_segment');

            Log::info('Google Analytics audience segment share job dispatched', [
                'segment_id' => $segmentId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to share Google Analytics audience segment', [
                'error' => $e->getMessage(),
                'segment_id' => $segmentId,
            ]);

            return false;
        }
    }

    /**
     * Get all audience segments from Google Analytics
     *
     * This method retrieves all audience segments configured in GA4,
     * with caching for improved performance.
     *
     * @return array List of audience segments
     */
    public function getAudienceSegments(): array
    {
        $tenantId = $this->getCurrentTenantId();
        $cacheKey = "ga_segments_{$tenantId}";

        // Try to get from cache first
        $cachedSegments = $this->cacheService->get($cacheKey);
        if ($cachedSegments !== null) {
            return $cachedSegments;
        }

        try {
            $propertyId = config('services.google.analytics.property_id');
            $apiSecret = config('services.google.analytics.api_secret');

            if (empty($propertyId) || empty($apiSecret)) {
                Log::warning('Google Analytics Admin API credentials not configured for segment retrieval');

                return [];
            }

            // Build API URL for listing audiences
            $url = "https://analyticsadmin.googleapis.com/v1beta/properties/{$propertyId}/audiences";

            $response = $this->httpClient->get($url, [
                'headers' => [
                    'Authorization' => "Bearer {$apiSecret}",
                    'Content-Type' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode Google Analytics segments response', [
                    'error' => json_last_error_msg(),
                ]);

                return [];
            }

            // Extract and format segments
            $segments = [];
            foreach ($responseData['audiences'] ?? [] as $audience) {
                $segments[] = [
                    'id' => $audience['name'] ?? '',
                    'display_name' => $audience['displayName'] ?? '',
                    'description' => $audience['description'] ?? '',
                    'created_at' => $audience['createTime'] ?? null,
                    'updated_at' => $audience['updateTime'] ?? null,
                ];
            }

            // Cache the segments
            $this->cacheService->put($cacheKey, $segments, self::CACHE_TTL_SEGMENTS);

            Log::debug('Google Analytics audience segments retrieved', [
                'segment_count' => count($segments),
                'tenant_id' => $tenantId,
            ]);

            return $segments;
        } catch (GuzzleException $e) {
            Log::error('Failed to retrieve Google Analytics audience segments', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Unexpected error during Google Analytics segment retrieval', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
            ]);

            return [];
        }
    }

    /**
     * Invalidate the segments cache
     *
     * @return bool Success status
     */
    protected function invalidateSegmentsCache(): bool
    {
        $tenantId = $this->getCurrentTenantId();
        $cacheKey = "ga_segments_{$tenantId}";

        return $this->cacheService->forget($cacheKey);
    }

    /**
     * Create a goal in Google Analytics from funnel data
     *
     * @param  array  $funnelData  Funnel data to create goal from
     * @return array|null Created goal data or null on failure
     */
    public function createGoal(array $funnelData): ?array
    {
        Log::info('Creating Google Analytics goal from funnel', [
            'funnel_name' => $funnelData['name'] ?? 'unknown',
            'tenant_id' => $this->getCurrentTenantId(),
        ]);

        try {
            $propertyId = config('services.google.analytics.property_id');
            $apiSecret = config('services.google.analytics.api_secret');

            if (empty($propertyId) || empty($apiSecret)) {
                Log::warning('Google Analytics Admin API credentials not configured for goal creation');

                return null;
            }

            // Map funnel to GA4 event
            $goalData = [
                'displayName' => $funnelData['name'] ?? 'Custom Goal',
                'eventConditions' => [
                    [
                        'eventName' => $funnelData['event_name'] ?? 'funnel_complete',
                    ],
                ],
            ];

            // Add value if specified
            if (isset($funnelData['value'])) {
                $goalData['value'] = $funnelData['value'];
            }

            // Dispatch job for goal creation
            \App\Jobs\GoogleSyncJob::dispatch([$goalData], 'create_goal');

            Log::info('Google Analytics goal creation job dispatched', [
                'goal_name' => $goalData['displayName'],
            ]);

            return $goalData;
        } catch (\Exception $e) {
            Log::error('Failed to create Google Analytics goal', [
                'error' => $e->getMessage(),
                'funnel_data' => $funnelData,
            ]);

            return null;
        }
    }

    /**
     * Create an audience in Google Analytics from segment data
     *
     * @param  array  $segmentData  Segment data to create audience from
     * @return array|null Created audience data or null on failure
     */
    public function createAudience(array $segmentData): ?array
    {
        Log::info('Creating Google Analytics audience from segment', [
            'segment_name' => $segmentData['name'] ?? 'unknown',
            'tenant_id' => $this->getCurrentTenantId(),
        ]);

        try {
            $propertyId = config('services.google.analytics.property_id');
            $apiSecret = config('services.google.analytics.api_secret');

            if (empty($propertyId) || empty($apiSecret)) {
                Log::warning('Google Analytics Admin API credentials not configured for audience creation');

                return null;
            }

            // Map segment to GA4 audience
            $audienceData = [
                'displayName' => $segmentData['name'] ?? 'Custom Audience',
                'description' => $segmentData['description'] ?? '',
                'filterClauses' => $this->mapSegmentToAudienceFilter($segmentData),
            ];

            // Dispatch job for audience creation
            \App\Jobs\GoogleSyncJob::dispatch([$audienceData], 'create_audience');

            Log::info('Google Analytics audience creation job dispatched', [
                'audience_name' => $audienceData['displayName'],
            ]);

            return $audienceData;
        } catch (\Exception $e) {
            Log::error('Failed to create Google Analytics audience', [
                'error' => $e->getMessage(),
                'segment_data' => $segmentData,
            ]);

            return null;
        }
    }

    /**
     * Map segment criteria to GA4 audience filter
     *
     * @param  array  $segmentData  Segment data
     * @return array Filter clauses for GA4 audience
     */
    private function mapSegmentToAudienceFilter(array $segmentData): array
    {
        $filterClauses = [];

        // Map user segment criteria
        if (isset($segmentData['criteria']['user_segment'])) {
            $filterClauses[] = [
                'filterExpression' => [
                    'filter' => [
                        'fieldName' => 'customEvent:user_segment',
                        'stringFilter' => [
                            'matchType' => 'EXACT',
                            'value' => $segmentData['criteria']['user_segment'],
                        ],
                    ],
                ],
            ];
        }

        // Map engagement criteria
        if (isset($segmentData['criteria']['min_engagement'])) {
            $filterClauses[] = [
                'filterExpression' => [
                    'filter' => [
                        'fieldName' => 'eventCount',
                        'numericFilter' => [
                            'operation' => 'GREATER_THAN_OR_EQUAL',
                            'value' => [
                                'int64Value' => $segmentData['criteria']['min_engagement'],
                            ],
                        ],
                    ],
                ],
            ];
        }

        // Map time-based criteria
        if (isset($segmentData['criteria']['days_since_last_activity'])) {
            $filterClauses[] = [
                'filterExpression' => [
                    'filter' => [
                        'fieldName' => 'daysSinceLastEvent',
                        'numericFilter' => [
                            'operation' => 'LESS_THAN_OR_EQUAL',
                            'value' => [
                                'int64Value' => $segmentData['criteria']['days_since_last_activity'],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return $filterClauses;
    }

    /**
     * Export segments to Google Analytics for audience sharing
     *
     * @param  array  $segmentData  Segment data to export
     * @return bool Success status
     */
    public function exportSegments(array $segmentData): bool
    {
        Log::info('Google Analytics segment export initiated', [
            'segment_count' => count($segmentData),
            'tenant_id' => $this->getCurrentTenantId(),
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

    /**
     * Retrieve report data from Google Analytics
     *
     * @param  array  $reportRequest  Report request parameters
     * @return array|null Report data or null on failure
     */
    public function getReport(array $reportRequest): ?array
    {
        try {
            $propertyId = config('services.google.analytics.property_id');
            $apiSecret = config('services.google.analytics.api_secret');

            if (empty($propertyId) || empty($apiSecret)) {
                Log::warning('Google Analytics credentials not configured for report retrieval');

                return null;
            }

            // Build API URL for report
            $url = "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runReport";

            // Prepare request body
            $requestBody = [
                'dateRanges' => $reportRequest['date_ranges'] ?? [
                    ['startDate' => '30daysAgo', 'endDate' => 'today'],
                ],
                'metrics' => $reportRequest['metrics'] ?? [],
                'dimensions' => $reportRequest['dimensions'] ?? [],
            ];

            // Add dimension filters if specified
            if (isset($reportRequest['dimension_filter'])) {
                $requestBody['dimensionFilter'] = $reportRequest['dimension_filter'];
            }

            // Add metric filters if specified
            if (isset($reportRequest['metric_filter'])) {
                $requestBody['metricFilter'] = $reportRequest['metric_filter'];
            }

            // Add tenant filter if tenant context exists
            $tenantId = $this->getCurrentTenantId();
            if ($tenantId && ! isset($reportRequest['dimension_filter'])) {
                $requestBody['dimensionFilter'] = [
                    'filter' => [
                        'fieldName' => 'customEvent:custom_parameter_tenant_id',
                        'stringFilter' => [
                            'matchType' => 'EXACT',
                            'value' => $tenantId,
                        ],
                    ],
                ];
            }

            $response = $this->httpClient->post($url, [
                'json' => $requestBody,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode Google Analytics report response', [
                    'error' => json_last_error_msg(),
                ]);

                return null;
            }

            Log::debug('Google Analytics report retrieved successfully', [
                'row_count' => count($responseData['rows'] ?? []),
                'tenant_id' => $tenantId,
            ]);

            return $responseData;
        } catch (GuzzleException $e) {
            Log::error('Failed to retrieve report from Google Analytics', [
                'error' => $e->getMessage(),
                'report_request' => $reportRequest,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Unexpected error during Google Analytics report retrieval', [
                'error' => $e->getMessage(),
                'report_request' => $reportRequest,
            ]);

            return null;
        }
    }

    /**
     * Get real-time user data from Google Analytics
     *
     * @return array|null Real-time data or null on failure
     */
    public function getRealtimeData(): ?array
    {
        $tenantId = $this->getCurrentTenantId();
        $cacheKey = "ga_realtime_{$tenantId}";

        // Try to get from cache first
        $cachedData = $this->cacheService->get($cacheKey);
        if ($cachedData !== null) {
            return $cachedData;
        }

        try {
            $propertyId = config('services.google.analytics.property_id');
            $apiSecret = config('services.google.analytics.api_secret');

            if (empty($propertyId) || empty($apiSecret)) {
                Log::warning('Google Analytics credentials not configured for realtime data');

                return null;
            }

            $url = "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runRealtimeReport";

            $requestBody = [
                'metrics' => [
                    ['name' => 'activeUsers'],
                ],
                'dimensions' => [
                    ['name' => 'country'],
                    ['name' => 'city'],
                ],
            ];

            $response = $this->httpClient->post($url, [
                'json' => $requestBody,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode Google Analytics realtime response', [
                    'error' => json_last_error_msg(),
                ]);

                return null;
            }

            // Cache the realtime data
            $this->cacheService->put($cacheKey, $responseData, self::CACHE_TTL_REALTIME);

            return $responseData;
        } catch (GuzzleException $e) {
            Log::error('Failed to retrieve realtime data from Google Analytics', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Unexpected error during Google Analytics realtime retrieval', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
            ]);

            return null;
        }
    }

    /**
     * Validate Google Analytics configuration
     *
     * This method validates that all required configuration settings are properly
     * configured and accessible. It checks credentials, connectivity, and permissions.
     *
     * @return array Validation results with valid status, errors, and warnings
     */
    public function validateConfiguration(): array
    {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'details' => [],
        ];

        $measurementId = config('services.google.analytics.measurement_id');
        $apiSecret = config('services.google.analytics.api_secret');
        $propertyId = config('services.google.analytics.property_id');

        // Validate Measurement ID
        if (empty($measurementId)) {
            $results['valid'] = false;
            $results['errors'][] = 'Google Analytics Measurement ID is not configured';
        } elseif (! preg_match('/^G-[A-Z0-9]+$/', $measurementId)) {
            $results['valid'] = false;
            $results['errors'][] = 'Google Analytics Measurement ID format is invalid';
        } else {
            $results['details']['measurement_id'] = [
                'configured' => true,
                'format_valid' => true,
            ];
        }

        // Validate API Secret
        if (empty($apiSecret)) {
            $results['valid'] = false;
            $results['errors'][] = 'Google Analytics API Secret is not configured';
        } elseif (strlen($apiSecret) < 10) {
            $results['warnings'][] = 'Google Analytics API Secret seems too short';
        } else {
            $results['details']['api_secret'] = [
                'configured' => true,
                'length_valid' => true,
            ];
        }

        // Validate Property ID (optional but recommended)
        if (empty($propertyId)) {
            $results['warnings'][] = 'Google Analytics Property ID is not configured (required for Admin API operations)';
        } elseif (! preg_match('/^[0-9]+$/', $propertyId)) {
            $results['warnings'][] = 'Google Analytics Property ID format may be invalid';
        } else {
            $results['details']['property_id'] = [
                'configured' => true,
                'format_valid' => true,
            ];
        }

        // Test API connectivity if credentials are configured
        if (! empty($measurementId) && ! empty($apiSecret)) {
            $connectivityResult = $this->testApiConnectivity();
            $results['details']['connectivity'] = $connectivityResult;

            if (! $connectivityResult['success']) {
                $results['warnings'][] = 'Failed to connect to Google Analytics API';
            }
        }

        // Cache validation result
        $tenantId = $this->getCurrentTenantId();
        $cacheKey = "ga_config_validation_{$tenantId}";
        $this->cacheService->put($cacheKey, $results, self::CACHE_TTL_CONFIG);

        return $results;
    }

    /**
     * Test API connectivity to Google Analytics
     *
     * @return array Connectivity test results
     */
    protected function testApiConnectivity(): array
    {
        try {
            $propertyId = config('services.google.analytics.property_id');
            $apiSecret = config('services.google.analytics.api_secret');

            if (empty($propertyId) || empty($apiSecret)) {
                return [
                    'success' => false,
                    'error' => 'Property ID or API secret not configured',
                ];
            }

            // Try to make a simple request to verify connectivity
            $url = "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runReport";

            $response = $this->httpClient->post($url, [
                'json' => [
                    'dateRanges' => [
                        ['startDate' => 'today', 'endDate' => 'today'],
                    ],
                    'metrics' => [
                        ['name' => 'activeUsers'],
                    ],
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            return [
                'success' => $response->getStatusCode() === 200,
                'status_code' => $response->getStatusCode(),
            ];
        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Normalize event name to comply with GA4 requirements
     *
     * GA4 has specific requirements for event names (no spaces, special chars, etc.)
     *
     * @param  string  $eventName  Original event name
     * @return string Normalized event name
     */
    protected function normalizeEventName(string $eventName): string
    {
        // Replace spaces and special characters with underscores
        $normalized = preg_replace('/[^a-zA-Z0-9_]/', '_', $eventName);

        // Remove consecutive underscores
        $normalized = preg_replace('/_+/', '_', $normalized);

        // Trim underscores from start and end
        $normalized = trim($normalized, '_');

        // Convert to lowercase
        $normalized = strtolower($normalized);

        // Ensure it's not empty
        if (empty($normalized)) {
            $normalized = 'custom_event';
        }

        return $normalized;
    }
}
