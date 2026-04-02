<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Services\TenantContextService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Matomo Service for handling event tracking, data synchronization, and segment tracking
 *
 * This service provides comprehensive Matomo Analytics integration including:
 * - Event forwarding to Matomo Tracking API
 * - Custom dimensions mapping for tenant isolation
 * - Goals synchronization
 * - Segment management
 * - Report retrieval
 * - Configuration validation
 */
class MatomoService
{
    private const CACHE_TTL_MINUTES = 60;

    private const SEGMENTS_CACHE_KEY = 'matomo_segments_';

    private const GOALS_CACHE_KEY = 'matomo_goals_';

    private const CONFIG_CACHE_KEY = 'matomo_config_validated_';

    private Client $httpClient;

    private ConsentService $consentService;

    private ?TenantContextService $tenantContextService;

    public function __construct(
        ConsentService $consentService,
        ?TenantContextService $tenantContextService = null
    ) {
        $this->consentService = $consentService;
        $this->tenantContextService = $tenantContextService;
        $this->httpClient = new Client([
            'timeout' => config('services.matomo.timeout', 10),
            'connect_timeout' => config('services.matomo.connect_timeout', 5),
        ]);
    }

    /**
     * Forward an analytics event to Matomo
     *
     * @param  array  $event  Event data to forward
     * @param  string|null  $tenantId  Tenant identifier for custom dimension mapping
     * @return bool Success status
     */
    public function forwardEvent(array $event, ?string $tenantId = null): bool
    {
        // Resolve tenant ID from context if not provided
        $resolvedTenantId = $tenantId ?? $this->resolveTenantId();

        // Check if user has given consent before tracking
        if (! $this->consentService->hasConsent()) {
            Log::info('Matomo event tracking skipped due to missing user consent', [
                'event_name' => $event['name'] ?? 'unknown',
                'tenant_id' => $resolvedTenantId,
            ]);

            return false;
        }

        try {
            $matomoUrl = config('services.matomo.url');
            $siteId = config('services.matomo.site_id');
            $tokenAuth = config('services.matomo.token_auth');

            if (empty($matomoUrl) || empty($siteId) || empty($tokenAuth)) {
                Log::warning('Matomo credentials not configured', [
                    'event_name' => $event['name'] ?? 'unknown',
                    'tenant_id' => $resolvedTenantId,
                ]);

                return false;
            }

            // Prepare parameters for Matomo Tracking API
            $params = [
                'idsite' => $siteId,
                'rec' => 1, // Record the visit
                'url' => $event['url'] ?? url()->current(),
                'action_name' => $event['name'] ?? 'Custom Event',
                'ua' => $event['user_agent'] ?? request()->userAgent(),
                '_id' => $event['client_id'] ?? Str::uuid()->toString(),
                'cid' => $event['client_id'] ?? Str::uuid()->toString(),
                'lang' => $event['language'] ?? app()->getLocale(),
                // Event-specific parameters
                'e_c' => $event['category'] ?? 'alumni_engagement', // Event category
                'e_a' => $event['action'] ?? 'action', // Event action
                'e_n' => $event['name'] ?? 'event_name', // Event name
                'e_v' => $event['value'] ?? null, // Event value
            ];

            // Map and add custom dimensions for tenant
            $customDimensions = $this->mapCustomDimensions($event, $resolvedTenantId);
            foreach ($customDimensions as $dimensionIndex => $dimensionValue) {
                $params['dimension'.$dimensionIndex] = $dimensionValue;
            }

            // Add any additional custom parameters from the original event
            if (isset($event['custom_params']) && is_array($event['custom_params'])) {
                foreach ($event['custom_params'] as $key => $value) {
                    $params[$key] = $value;
                }
            }

            // Build the URL for Matomo tracking
            $trackingUrl = rtrim($matomoUrl, '/').'/piwik.php?'.http_build_query($params);

            $response = $this->httpClient->get($trackingUrl);

            // Log successful tracking
            Log::debug('Matomo event forwarded successfully', [
                'event_name' => $event['name'] ?? 'unknown',
                'tenant_id' => $resolvedTenantId,
                'status_code' => $response->getStatusCode(),
            ]);

            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            Log::error('Failed to forward event to Matomo', [
                'error' => $e->getMessage(),
                'event_name' => $event['name'] ?? 'unknown',
                'tenant_id' => $resolvedTenantId,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Unexpected error during Matomo event forwarding', [
                'error' => $e->getMessage(),
                'event_name' => $event['name'] ?? 'unknown',
                'tenant_id' => $resolvedTenantId,
            ]);

            return false;
        }
    }

    /**
     * Track an event in Matomo (alias for backward compatibility)
     *
     * @param  array  $eventData  Event data to track
     * @param  string|null  $tenantId  Tenant identifier for custom dimension mapping
     * @return bool Success status
     */
    public function trackEvent(array $eventData, ?string $tenantId = null): bool
    {
        return $this->forwardEvent($eventData, $tenantId);
    }

    /**
     * Batch forward multiple events to Matomo
     *
     * @param  array  $events  Array of events to forward
     * @param  string|null  $tenantId  Tenant identifier for custom dimension mapping
     * @return array Results with success/failure counts
     */
    public function batchForwardEvents(array $events, ?string $tenantId = null): array
    {
        $resolvedTenantId = $tenantId ?? $this->resolveTenantId();

        $results = [
            'total' => count($events),
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($events as $index => $event) {
            $success = $this->forwardEvent($event, $resolvedTenantId);

            if ($success) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'index' => $index,
                    'event_name' => $event['name'] ?? 'unknown',
                    'error' => 'Failed to forward event',
                ];
            }
        }

        Log::info('Matomo batch event forwarding completed', [
            'total' => $results['total'],
            'success' => $results['success'],
            'failed' => $results['failed'],
            'tenant_id' => $resolvedTenantId,
        ]);

        return $results;
    }

    /**
     * Track multiple events in batch (alias for backward compatibility)
     *
     * @param  array  $events  Array of event data to track
     * @param  string|null  $tenantId  Tenant identifier for custom dimension mapping
     * @return array Results with success/failure counts
     */
    public function trackBatchEvents(array $events, ?string $tenantId = null): array
    {
        return $this->batchForwardEvents($events, $tenantId);
    }

    /**
     * Map event properties to Matomo custom dimensions
     *
     * @param  array  $event  Event data
     * @param  string|null  $tenantId  Tenant identifier
     * @return array Mapped custom dimensions [dimensionIndex => value]
     */
    public function mapCustomDimensions(array $event, ?string $tenantId = null): array
    {
        $resolvedTenantId = $tenantId ?? $this->resolveTenantId();
        $customDimensions = [];

        // Get custom dimension mapping from config
        $dimensionMapping = config('services.matomo.custom_dimensions', [
            'tenant_id' => 1,
            'user_segment' => 2,
            'cohort_id' => 3,
            'graduation_year' => 4,
            'program_of_study' => 5,
            'engagement_level' => 6,
            'alumni_status' => 7,
        ]);

        // Map tenant ID to dimension
        if ($resolvedTenantId && isset($dimensionMapping['tenant_id'])) {
            $customDimensions[$dimensionMapping['tenant_id']] = $resolvedTenantId;
        }

        // Map user segment
        if (isset($event['user_segment']) && isset($dimensionMapping['user_segment'])) {
            $customDimensions[$dimensionMapping['user_segment']] = $event['user_segment'];
        }

        // Map cohort ID
        if (isset($event['cohort_id']) && isset($dimensionMapping['cohort_id'])) {
            $customDimensions[$dimensionMapping['cohort_id']] = (string) $event['cohort_id'];
        }

        // Map graduation year
        if (isset($event['graduation_year']) && isset($dimensionMapping['graduation_year'])) {
            $customDimensions[$dimensionMapping['graduation_year']] = (string) $event['graduation_year'];
        }

        // Map program of study
        if (isset($event['program_of_study']) && isset($dimensionMapping['program_of_study'])) {
            $customDimensions[$dimensionMapping['program_of_study']] = $event['program_of_study'];
        }

        // Map engagement level
        if (isset($event['engagement_level']) && isset($dimensionMapping['engagement_level'])) {
            $customDimensions[$dimensionMapping['engagement_level']] = $event['engagement_level'];
        }

        // Map alumni status
        if (isset($event['alumni_status']) && isset($dimensionMapping['alumni_status'])) {
            $customDimensions[$dimensionMapping['alumni_status']] = $event['alumni_status'];
        }

        return $customDimensions;
    }

    /**
     * Synchronize goals with Matomo
     *
     * @param  array  $goals  Array of goal data to sync
     * @return bool Success status
     */
    public function syncGoals(array $goals = []): bool
    {
        try {
            $matomoUrl = config('services.matomo.url');
            $siteId = config('services.matomo.site_id');
            $tokenAuth = config('services.matomo.token_auth');

            if (empty($matomoUrl) || empty($siteId) || empty($tokenAuth)) {
                Log::warning('Matomo credentials not configured for goals sync');

                return false;
            }

            // Check consent before syncing
            if (! $this->consentService->hasConsent()) {
                Log::info('Matomo goals sync skipped due to missing user consent');

                return false;
            }

            $syncedGoals = [];
            $defaultGoals = [
                [
                    'name' => 'Profile Completion',
                    'event_category' => 'profile',
                    'event_action' => 'complete',
                    'description' => 'User completed their profile',
                ],
                [
                    'name' => 'Connection Made',
                    'event_category' => 'connections',
                    'event_action' => 'new_connection',
                    'description' => 'User made a new connection',
                ],
                [
                    'name' => 'Event Registration',
                    'event_category' => 'events',
                    'event_action' => 'register',
                    'description' => 'User registered for an event',
                ],
            ];

            $goalsToSync = ! empty($goals) ? $goals : $defaultGoals;

            foreach ($goalsToSync as $goal) {
                $result = $this->createGoal($goal);
                if ($result !== null) {
                    $syncedGoals[] = $result;
                }
            }

            // Cache the synced goals
            $this->cacheGoals($syncedGoals);

            Log::info('Matomo goals synchronized successfully', [
                'goals_count' => count($syncedGoals),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync goals with Matomo', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create a goal in Matomo
     *
     * @param  array  $goalData  Goal data to create
     * @return array|null Created goal data or null on failure
     */
    public function createGoal(array $goalData): ?array
    {
        Log::info('Creating Matomo goal', [
            'goal_name' => $goalData['name'] ?? 'unknown',
        ]);

        try {
            $matomoUrl = config('services.matomo.url');
            $siteId = config('services.matomo.site_id');
            $tokenAuth = config('services.matomo.token_auth');

            if (empty($matomoUrl) || empty($siteId) || empty($tokenAuth)) {
                Log::warning('Matomo credentials not configured for goal creation');

                return null;
            }

            // Prepare goal parameters
            $params = [
                'module' => 'API',
                'method' => 'Goals.addGoal',
                'idSite' => $siteId,
                'token_auth' => $tokenAuth,
                'format' => 'JSON',
                'name' => $goalData['name'] ?? 'Custom Goal',
                'goalType' => 'event', // Event-based goal
                'eventCategory' => $goalData['event_category'] ?? 'alumni_engagement',
                'eventAction' => $goalData['event_action'] ?? 'funnel_complete',
            ];

            // Add pattern if specified
            if (isset($goalData['pattern'])) {
                $params['pattern'] = $goalData['pattern'];
            }

            // Add value if specified
            if (isset($goalData['value'])) {
                $params['revenue'] = $goalData['value'];
            }

            // Add description
            if (isset($goalData['description'])) {
                $params['description'] = $goalData['description'];
            }

            $apiUrl = rtrim($matomoUrl, '/').'/index.php?'.http_build_query($params);

            $response = $this->httpClient->get($apiUrl);
            $responseData = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode Matomo goal creation response', [
                    'error' => json_last_error_msg(),
                ]);

                return null;
            }

            $goalId = $responseData['value'] ?? null;

            Log::info('Matomo goal created successfully', [
                'goal_id' => $goalId,
                'goal_name' => $goalData['name'] ?? 'unknown',
            ]);

            return [
                'id' => $goalId,
                'name' => $goalData['name'] ?? 'Custom Goal',
                'event_category' => $goalData['event_category'] ?? 'alumni_engagement',
                'event_action' => $goalData['event_action'] ?? 'funnel_complete',
                'created_at' => now()->toIso8601String(),
            ];
        } catch (GuzzleException $e) {
            Log::error('Failed to create Matomo goal', [
                'error' => $e->getMessage(),
                'goal_data' => $goalData,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Unexpected error during Matomo goal creation', [
                'error' => $e->getMessage(),
                'goal_data' => $goalData,
            ]);

            return null;
        }
    }

    /**
     * Create a segment in Matomo
     *
     * @param  array  $criteria  Segment criteria
     * @return array|null Created segment data or null on failure
     */
    public function createSegment(array $criteria): ?array
    {
        Log::info('Creating Matomo segment', [
            'segment_name' => $criteria['name'] ?? 'unknown',
        ]);

        try {
            $matomoUrl = config('services.matomo.url');
            $siteId = config('services.matomo.site_id');
            $tokenAuth = config('services.matomo.token_auth');

            if (empty($matomoUrl) || empty($siteId) || empty($tokenAuth)) {
                Log::warning('Matomo credentials not configured for segment creation');

                return null;
            }

            // Build segment definition
            $segmentDefinition = $this->mapSegmentToMatomoDefinition($criteria);

            // Prepare segment parameters
            $params = [
                'module' => 'API',
                'method' => 'Segments.add',
                'idSite' => $siteId,
                'token_auth' => $tokenAuth,
                'format' => 'JSON',
                'name' => $criteria['name'] ?? 'Custom Segment',
                'definition' => $segmentDefinition,
                'autoArchive' => $criteria['auto_archive'] ?? 1,
                'enabledAllUsers' => $criteria['enabled_all_users'] ?? 0,
            ];

            $apiUrl = rtrim($matomoUrl, '/').'/index.php?'.http_build_query($params);

            $response = $this->httpClient->get($apiUrl);
            $responseData = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode Matomo segment creation response', [
                    'error' => json_last_error_msg(),
                ]);

                return null;
            }

            $segmentId = $responseData['value'] ?? null;

            // Invalidate segments cache
            $this->invalidateSegmentsCache();

            Log::info('Matomo segment created successfully', [
                'segment_id' => $segmentId,
                'segment_name' => $criteria['name'] ?? 'unknown',
            ]);

            return [
                'id' => $segmentId,
                'name' => $criteria['name'] ?? 'Custom Segment',
                'definition' => $segmentDefinition,
                'created_at' => now()->toIso8601String(),
            ];
        } catch (GuzzleException $e) {
            Log::error('Failed to create Matomo segment', [
                'error' => $e->getMessage(),
                'criteria' => $criteria,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Unexpected error during Matomo segment creation', [
                'error' => $e->getMessage(),
                'criteria' => $criteria,
            ]);

            return null;
        }
    }

    /**
     * Get all segments from Matomo
     *
     * @return array List of segments
     */
    public function getSegments(): array
    {
        $cacheKey = self::SEGMENTS_CACHE_KEY.config('services.matomo.site_id', 'default');

        // Try to get from cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $matomoUrl = config('services.matomo.url');
            $siteId = config('services.matomo.site_id');
            $tokenAuth = config('services.matomo.token_auth');

            if (empty($matomoUrl) || empty($siteId) || empty($tokenAuth)) {
                Log::warning('Matomo credentials not configured for segments retrieval');

                return [];
            }

            $params = [
                'module' => 'API',
                'method' => 'Segments.getAll',
                'idSite' => $siteId,
                'token_auth' => $tokenAuth,
                'format' => 'JSON',
            ];

            $apiUrl = rtrim($matomoUrl, '/').'/index.php?'.http_build_query($params);

            $response = $this->httpClient->get($apiUrl);
            $responseData = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode Matomo segments response', [
                    'error' => json_last_error_msg(),
                ]);

                return [];
            }

            // Format segments for easier consumption
            $segments = [];
            if (is_array($responseData)) {
                foreach ($responseData as $segment) {
                    $segments[] = [
                        'id' => $segment['idsegment'] ?? null,
                        'name' => $segment['name'] ?? 'Unknown',
                        'definition' => $segment['definition'] ?? '',
                        'enabled' => (bool) ($segment['enabled'] ?? false),
                        'created_at' => $segment['created'] ?? null,
                        'updated_at' => $segment['updated'] ?? null,
                    ];
                }
            }

            // Cache the segments
            Cache::put($cacheKey, $segments, now()->addMinutes(self::CACHE_TTL_MINUTES));

            return $segments;
        } catch (GuzzleException $e) {
            Log::error('Failed to retrieve segments from Matomo', [
                'error' => $e->getMessage(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Unexpected error during Matomo segments retrieval', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Export segments from Matomo
     *
     * @param  array  $filters  Filters to apply
     * @return array List of segments
     */
    public function exportSegments(array $filters = []): array
    {
        $segments = $this->getSegments();

        // Apply filters if provided
        if (! empty($filters)) {
            if (isset($filters['date_range']['start'])) {
                $startDate = $filters['date_range']['start'];
                $segments = array_filter($segments, function ($segment) use ($startDate) {
                    return isset($segment['created_at']) && $segment['created_at'] >= $startDate;
                });
            }

            if (isset($filters['date_range']['end'])) {
                $endDate = $filters['date_range']['end'];
                $segments = array_filter($segments, function ($segment) use ($endDate) {
                    return isset($segment['created_at']) && $segment['created_at'] <= $endDate;
                });
            }
        }

        return array_values($segments);
    }

    /**
     * Validate Matomo configuration
     *
     * @return array Validation results
     */
    public function validateConfiguration(): array
    {
        $cacheKey = self::CONFIG_CACHE_KEY;

        // Try to get from cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'details' => [],
        ];

        $matomoUrl = config('services.matomo.url');
        $siteId = config('services.matomo.site_id');
        $tokenAuth = config('services.matomo.token_auth');

        // Validate URL
        if (empty($matomoUrl)) {
            $results['valid'] = false;
            $results['errors'][] = 'Matomo URL is not configured';
        } else {
            $results['details']['url'] = $matomoUrl;

            // Validate URL format
            if (! filter_var($matomoUrl, FILTER_VALIDATE_URL)) {
                $results['valid'] = false;
                $results['errors'][] = 'Matomo URL format is invalid';
            }
        }

        // Validate Site ID
        if (empty($siteId)) {
            $results['valid'] = false;
            $results['errors'][] = 'Matomo Site ID is not configured';
        } else {
            $results['details']['site_id'] = $siteId;
        }

        // Validate Token Auth
        if (empty($tokenAuth)) {
            $results['valid'] = false;
            $results['errors'][] = 'Matomo Token Auth is not configured';
        } elseif (strlen($tokenAuth) < 10) {
            $results['warnings'][] = 'Matomo Token Auth seems too short';
        } else {
            $results['details']['token_auth_set'] = true;
        }

        // Test API connectivity if credentials are provided
        if (! empty($matomoUrl) && ! empty($siteId) && ! empty($tokenAuth)) {
            $apiTestResult = $this->testApiConnectivity();
            $results['details']['api_connectivity'] = $apiTestResult;

            if (! $apiTestResult['success']) {
                $results['valid'] = false;
                $results['errors'][] = 'Failed to connect to Matomo API: '.($apiTestResult['error'] ?? 'Unknown error');
            }
        }

        // Cache the validation results
        Cache::put($cacheKey, $results, now()->addMinutes(self::CACHE_TTL_MINUTES));

        return $results;
    }

    /**
     * Test API connectivity to Matomo
     *
     * @return array Connectivity test results
     */
    private function testApiConnectivity(): array
    {
        try {
            $matomoUrl = config('services.matomo.url');
            $siteId = config('services.matomo.site_id');
            $tokenAuth = config('services.matomo.token_auth');

            $params = [
                'module' => 'API',
                'method' => 'API.getMatomoVersion',
                'idSite' => $siteId,
                'token_auth' => $tokenAuth,
                'format' => 'JSON',
            ];

            $apiUrl = rtrim($matomoUrl, '/').'/index.php?'.http_build_query($params);

            $response = $this->httpClient->get($apiUrl);

            if ($response->getStatusCode() === 200) {
                $responseData = json_decode($response->getBody()->getContents(), true);

                return [
                    'success' => true,
                    'version' => $responseData['value'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => 'API returned status code: '.$response->getStatusCode(),
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
     * Get a report from Matomo
     *
     * @param  string  $reportType  Type of report to retrieve
     * @param  array  $parameters  Additional parameters for the report
     * @return array|null Report data or null on failure
     */
    public function getReport(string $reportType, array $parameters = []): ?array
    {
        $resolvedTenantId = $this->resolveTenantId();
        $cacheKey = 'matomo_report_'.md5($reportType.serialize($parameters).$resolvedTenantId);

        // Try to get from cache for GET requests
        $method = strtoupper($parameters['method'] ?? 'GET');
        $useCache = ($method === 'GET');

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $matomoUrl = config('services.matomo.url');
            $siteId = config('services.matomo.site_id');
            $tokenAuth = config('services.matomo.token_auth');

            if (empty($matomoUrl) || empty($siteId) || empty($tokenAuth)) {
                Log::warning('Matomo credentials not configured for report retrieval');

                return null;
            }

            $defaultParams = [
                'module' => 'API',
                'method' => $reportType,
                'idSite' => $siteId,
                'period' => $parameters['period'] ?? 'day',
                'date' => $parameters['date'] ?? 'today',
                'format' => 'JSON',
                'token_auth' => $tokenAuth,
            ];

            $apiParams = array_merge($defaultParams, $parameters);

            $apiUrl = rtrim($matomoUrl, '/').'/index.php?'.http_build_query($apiParams);

            $response = $this->httpClient->get($apiUrl);
            $responseData = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode Matomo API response', [
                    'report_type' => $reportType,
                    'error' => json_last_error_msg(),
                ]);

                return null;
            }

            // Add tenant context to response
            $responseData['_tenant_id'] = $resolvedTenantId;
            $responseData['_retrieved_at'] = now()->toIso8601String();

            // Cache the report data
            $cacheTtl = $parameters['cache_ttl'] ?? config('analytics.cache.ttl', 300);
            if ($useCache) {
                Cache::put($cacheKey, $responseData, now()->addSeconds($cacheTtl));
            }

            return $responseData;
        } catch (GuzzleException $e) {
            Log::error('Failed to retrieve report from Matomo', [
                'report_type' => $reportType,
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Unexpected error during Matomo report retrieval', [
                'report_type' => $reportType,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get real-time visitor data from Matomo
     *
     * @return array|null Real-time data or null on failure
     */
    public function getRealtimeData(): ?array
    {
        try {
            $matomoUrl = config('services.matomo.url');
            $siteId = config('services.matomo.site_id');
            $tokenAuth = config('services.matomo.token_auth');

            if (empty($matomoUrl) || empty($siteId) || empty($tokenAuth)) {
                Log::warning('Matomo credentials not configured for realtime data');

                return null;
            }

            $apiParams = [
                'module' => 'API',
                'method' => 'Live.getLastVisitsDetails',
                'idSite' => $siteId,
                'period' => 'day',
                'date' => 'today',
                'format' => 'JSON',
                'token_auth' => $tokenAuth,
                'filter_limit' => 10, // Get last 10 visitors
            ];

            $apiUrl = rtrim($matomoUrl, '/').'/index.php?'.http_build_query($apiParams);

            $response = $this->httpClient->get($apiUrl);
            $responseData = json_decode($response->getBody()->getContents(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode Matomo realtime response', [
                    'error' => json_last_error_msg(),
                ]);

                return null;
            }

            return $responseData;
        } catch (GuzzleException $e) {
            Log::error('Failed to retrieve realtime data from Matomo', [
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Unexpected error during Matomo realtime retrieval', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Sync data between our system and Matomo
     *
     * @param  array  $syncData  Data to sync
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
     * Clear all Matomo-related caches
     */
    public function clearCaches(): void
    {
        $this->invalidateSegmentsCache();
        $this->invalidateGoalsCache();
        Cache::forget(self::CONFIG_CACHE_KEY);

        Log::info('Matomo caches cleared');
    }

    /**
     * Resolve tenant ID from context or parameter
     *
     * @return string|null Resolved tenant ID
     */
    private function resolveTenantId(): ?string
    {
        // Try to get from tenant context service
        if ($this->tenantContextService !== null) {
            $tenantId = $this->tenantContextService->getCurrentTenantId();
            if ($tenantId) {
                return $tenantId;
            }
        }

        // Fallback to header-based resolution
        $headerTenantId = request()->header('X-Tenant');
        if ($headerTenantId) {
            return $headerTenantId;
        }

        return null;
    }

    /**
     * Map segment criteria to Matomo segment definition
     *
     * @param  array  $criteria  Segment criteria
     * @return string Matomo segment definition
     */
    private function mapSegmentToMatomoDefinition(array $criteria): string
    {
        $segments = [];

        // Map user segment criteria
        if (isset($criteria['user_segment'])) {
            $segments[] = 'customDimension2=='.$criteria['user_segment'];
        }

        // Map engagement criteria
        if (isset($criteria['min_events'])) {
            $segments[] = 'events>='.$criteria['min_events'];
        }

        // Map time-based criteria
        if (isset($criteria['days_since_last_visit'])) {
            $segments[] = 'daysSinceLastVisit<='.$criteria['days_since_last_visit'];
        }

        // Map cohort criteria
        if (isset($criteria['cohort_id'])) {
            $segments[] = 'customDimension3=='.$criteria['cohort_id'];
        }

        // Map graduation year criteria
        if (isset($criteria['graduation_year'])) {
            $segments[] = 'customDimension4=='.$criteria['graduation_year'];
        }

        // Map tenant criteria
        if (isset($criteria['tenant_id'])) {
            $segments[] = 'customDimension1=='.$criteria['tenant_id'];
        }

        return implode(',', $segments);
    }

    /**
     * Cache goals
     *
     * @param  array  $goals  Goals to cache
     */
    private function cacheGoals(array $goals): void
    {
        $cacheKey = self::GOALS_CACHE_KEY.config('services.matomo.site_id', 'default');
        Cache::put($cacheKey, $goals, now()->addMinutes(self::CACHE_TTL_MINUTES));
    }

    /**
     * Invalidate segments cache
     */
    private function invalidateSegmentsCache(): void
    {
        $cacheKey = self::SEGMENTS_CACHE_KEY.config('services.matomo.site_id', 'default');
        Cache::forget($cacheKey);
    }

    /**
     * Invalidate goals cache
     */
    private function invalidateGoalsCache(): void
    {
        $cacheKey = self::GOALS_CACHE_KEY.config('services.matomo.site_id', 'default');
        Cache::forget($cacheKey);
    }
}
