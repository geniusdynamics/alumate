<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Models\AnalyticsEvent;
use App\Services\TenantContextService;
use App\Services\EmailAnalyticsService;
use App\Services\HeatMapService;
use App\Services\GamificationAnalyticsService;
use App\Jobs\ProcessAnalyticsEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService,
        private EmailAnalyticsService $emailAnalyticsService,
        private GamificationAnalyticsService $gamificationService
    ) {
        $this->middleware(['auth', 'role:admin|super_admin']);
    }
    /**
     * Store analytics events in batch
     *
     * Accepts a batch of analytics events from client-side tracking.
     * Validates input, processes events with tenant isolation, and stores them efficiently.
     *
     * @param Request $request The HTTP request containing events array
     * @return JsonResponse JSON response with processing results
     */
    public function storeEvents(Request $request): JsonResponse
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'events' => 'required|array|max:100',
            'events.*.tenant_id' => 'required|string|max:100',
            'events.*.event_type' => 'required|string|max:100',
            'events.*.properties' => 'required|array',
            'events.*.session_id' => 'required|string|max:100',
            'events.*.timestamp' => 'required|date',
            'events.*.user_id' => 'nullable|string|max:100',
            'events.*.consent_flags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $events = $request->input('events');
            $processedEventIds = [];
            $processed = 0;
            $errors = [];

            // Get tenant context service
            $tenantService = app(TenantContextService::class);

            foreach ($events as $index => $eventData) {
                try {
                    // Set tenant context
                    $tenantService->setTenant($eventData['tenant_id']);

                    // Check consent and anonymize if needed
                    if (isset($eventData['consent_flags']) && !$this->hasRequiredConsent($eventData['consent_flags'])) {
                        $eventData['user_id'] = null;
                        $eventData['properties'] = $this->anonymizeProperties($eventData['properties']);
                    }

                    // Create analytics event
                    $event = AnalyticsEvent::create([
                        'tenant_id' => $eventData['tenant_id'],
                        'event_type' => $eventData['event_type'],
                        'event_name' => $eventData['event_type'], // Map to existing field
                        'user_id' => $eventData['user_id'],
                        'properties' => $eventData['properties'],
                        'session_id' => $eventData['session_id'],
                        'occurred_at' => $eventData['timestamp'],
                        'is_compliant' => $this->isCompliant($eventData),
                        'consent_given' => $this->hasRequiredConsent($eventData['consent_flags'] ?? []),
                        'user_agent' => $request->header('User-Agent'),
                        'ip_address' => $this->anonymizeIp($request->ip()),
                        'page_url' => $request->header('Referer'),
                    ]);
                    $processedEventIds[] = $event->id;

                    $processed++;
                } catch (\Exception $e) {
                    Log::error('Failed to process analytics event', [
                        'event_index' => $index,
                        'event_data' => $eventData,
                        'error' => $e->getMessage(),
                    ]);

                    $errors[] = [
                        'index' => $index,
                        'event_type' => $eventData['event_type'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            // Dispatch async processing job if events were successfully created
            if (!empty($processedEventIds)) {
                $tenantId = $events[0]['tenant_id'] ?? null; // Use first event's tenant_id
                if ($tenantId) {
                    ProcessAnalyticsEvents::dispatch($processedEventIds, $tenantId)->onQueue('analytics');
                }
            }
            }

            return response()->json([
                'success' => true,
                'processed' => $processed,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to store analytics events', [
                'error' => $e->getMessage(),
                'events_count' => count($request->input('events', [])),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process events',
            ], 500);
        }
    }

    /**
     * Check if user has given required consent for data collection
     */
    private function hasRequiredConsent(array $consentFlags): bool
    {
        // Check for analytics consent
        return in_array('analytics', $consentFlags) || in_array('all', $consentFlags);
    }

    /**
     * Anonymize sensitive properties
     */
    private function anonymizeProperties(array $properties): array
    {
        $sensitiveKeys = ['email', 'name', 'phone', 'address', 'personal_info'];

        foreach ($sensitiveKeys as $key) {
            if (isset($properties[$key])) {
                $properties[$key] = 'anonymized';
            }
        }

        return $properties;
    }

    /**
     * Check if event data is compliant with privacy regulations
     */
    private function isCompliant(array $eventData): bool
    {
        // Basic compliance check - can be extended for GDPR/CCPA
        return isset($eventData['consent_flags']) && $this->hasRequiredConsent($eventData['consent_flags']);
    }

    /**
     * Anonymize IP address for privacy compliance
     */
    private function anonymizeIp(?string $ip): ?string
    {
        if (!$ip) {
            return null;
        }

        // IPv4: Remove last octet
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }

        // IPv6: Remove last 64 bits
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            for ($i = 4; $i < 8; $i++) {
                $parts[$i] = '0';
            }
            return implode(':', $parts);
        }

        return null;
    }

    /**
     * Get engagement metrics dashboard data
     */
    public function getEngagementMetrics(Request $request): JsonResponse
    {
        $filters = $this->validateFilters($request);

        try {
            $metrics = $this->analyticsService->getEngagementMetrics($filters);

            return response()->json([
                'success' => true,
                'data' => $metrics,
                'message' => 'Engagement metrics retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve engagement metrics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get alumni activity tracking data
     */
    public function getAlumniActivity(Request $request): JsonResponse
    {
        $filters = $this->validateFilters($request);

        try {
            $activity = $this->analyticsService->getAlumniActivity($filters);

            return response()->json([
                'success' => true,
                'data' => $activity,
                'message' => 'Alumni activity data retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve alumni activity data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get community health indicators
     */
    public function getCommunityHealth(Request $request): JsonResponse
    {
        $filters = $this->validateFilters($request);

        try {
            $health = $this->analyticsService->getCommunityHealth($filters);

            return response()->json([
                'success' => true,
                'data' => $health,
                'message' => 'Community health indicators retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve community health indicators',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get platform usage statistics
     */
    public function getPlatformUsage(Request $request): JsonResponse
    {
        $filters = $this->validateFilters($request);

        try {
            $usage = $this->analyticsService->getPlatformUsage($filters);

            return response()->json([
                'success' => true,
                'data' => $usage,
                'message' => 'Platform usage statistics retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve platform usage statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get comprehensive dashboard data
     */
    public function getDashboardData(Request $request): JsonResponse
    {
        $filters = $this->validateFilters($request);

        try {
            $data = [
                'engagement_metrics' => $this->analyticsService->getEngagementMetrics($filters),
                'alumni_activity' => $this->analyticsService->getAlumniActivity($filters),
                'community_health' => $this->analyticsService->getCommunityHealth($filters),
                'platform_usage' => $this->analyticsService->getPlatformUsage($filters),
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Dashboard data retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate custom report
     */
    public function generateCustomReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'metrics' => 'required|array',
            'metrics.*' => 'string|in:engagement_rate,active_users,new_users,posts_created,connections_made,events_attended',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'filters' => 'nullable|array',
        ]);

        try {
            $report = $this->analyticsService->generateCustomReport(
                $validated['metrics'],
                $validated['filters'] ?? []
            );

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Custom report generated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate custom report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export analytics data
     */
    public function exportData(Request $request)
    {
        $validated = $request->validate([
            'data_type' => 'required|string|in:engagement_metrics,alumni_activity,community_health,platform_usage',
            'format' => 'required|string|in:csv,json,xlsx',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'filters' => 'nullable|array',
        ]);

        try {
            $filters = $validated['filters'] ?? [];
            if (isset($validated['start_date'])) {
                $filters['start_date'] = $validated['start_date'];
            }
            if (isset($validated['end_date'])) {
                $filters['end_date'] = $validated['end_date'];
            }

            // Get the data based on type
            $data = match ($validated['data_type']) {
                'engagement_metrics' => $this->analyticsService->getEngagementMetrics($filters),
                'alumni_activity' => $this->analyticsService->getAlumniActivity($filters),
                'community_health' => $this->analyticsService->getCommunityHealth($filters),
                'platform_usage' => $this->analyticsService->getPlatformUsage($filters),
            };

            // Export the data
            $exportedData = $this->analyticsService->exportData($data, $validated['format']);

            // Set appropriate headers
            $headers = $this->getExportHeaders($validated['format'], $validated['data_type']);

            return Response::make($exportedData, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available metrics for custom reports
     */
    public function getAvailableMetrics(): JsonResponse
    {
        $metrics = [
            'engagement_metrics' => [
                'total_users' => 'Total Users',
                'active_users' => 'Active Users',
                'new_users' => 'New Users',
                'posts_created' => 'Posts Created',
                'engagement_rate' => 'Engagement Rate',
                'connections_made' => 'Connections Made',
                'events_attended' => 'Events Attended',
                'user_retention' => 'User Retention',
            ],
            'alumni_activity' => [
                'daily_active_users' => 'Daily Active Users',
                'post_activity' => 'Post Activity',
                'engagement_trends' => 'Engagement Trends',
                'feature_usage' => 'Feature Usage',
                'geographic_distribution' => 'Geographic Distribution',
                'graduation_year_activity' => 'Graduation Year Activity',
            ],
            'community_health' => [
                'network_density' => 'Network Density',
                'group_participation' => 'Group Participation',
                'circle_engagement' => 'Circle Engagement',
                'content_quality_score' => 'Content Quality Score',
                'user_satisfaction' => 'User Satisfaction',
                'platform_growth_rate' => 'Platform Growth Rate',
            ],
            'platform_usage' => [
                'page_views' => 'Page Views',
                'session_duration' => 'Session Duration',
                'bounce_rate' => 'Bounce Rate',
                'device_breakdown' => 'Device Breakdown',
                'browser_breakdown' => 'Browser Breakdown',
                'peak_usage_times' => 'Peak Usage Times',
                'feature_adoption' => 'Feature Adoption',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $metrics,
            'message' => 'Available metrics retrieved successfully',
        ]);
    }

    /**
     * Get analytics summary for quick overview
     */
    public function getAnalyticsSummary(Request $request): JsonResponse
    {
        $filters = $this->validateFilters($request);

        try {
            $engagement = $this->analyticsService->getEngagementMetrics($filters);
            $activity = $this->analyticsService->getAlumniActivity($filters);
            $health = $this->analyticsService->getCommunityHealth($filters);

            $summary = [
                'key_metrics' => [
                    'total_users' => $engagement['total_users'],
                    'active_users' => $engagement['active_users'],
                    'engagement_rate' => $engagement['engagement_rate'],
                    'network_density' => $health['network_density'],
                ],
                'trends' => [
                    'user_growth' => $health['platform_growth_rate'],
                    'engagement_trend' => $this->calculateTrend($activity['engagement_trends']),
                    'activity_trend' => $this->calculateTrend($activity['daily_active_users']),
                ],
                'alerts' => $this->generateAlerts($engagement, $health),
            ];

            return response()->json([
                'success' => true,
                'data' => $summary,
                'message' => 'Analytics summary retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve analytics summary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate common filters
     */
    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'institution_id' => 'nullable|exists:institutions,id',
            'graduation_year' => 'nullable|integer|min:1900|max:'.(date('Y') + 10),
            'location' => 'nullable|string|max:255',
            'program' => 'nullable|string|max:255',
        ]);
    }

    /**
     * Get export headers based on format
     */
    private function getExportHeaders(string $format, string $dataType): array
    {
        $filename = $dataType.'_'.date('Y-m-d_H-i-s');

        return match ($format) {
            'csv' => [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            ],
            'json' => [
                'Content-Type' => 'application/json',
                'Content-Disposition' => "attachment; filename=\"{$filename}.json\"",
            ],
            'xlsx' => [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$filename}.xlsx\"",
            ],
            default => [],
        };
    }

    /**
     * Calculate trend from time series data
     */
    private function calculateTrend(array $data): string
    {
        if (count($data) < 2) {
            return 'stable';
        }

        $first = reset($data);
        $last = end($data);

        if (is_array($first) && isset($first['count'])) {
            $firstValue = $first['count'];
            $lastValue = $last['count'];
        } else {
            $firstValue = $first;
            $lastValue = $last;
        }

        $change = (($lastValue - $firstValue) / $firstValue) * 100;

        if ($change > 5) {
            return 'increasing';
        } elseif ($change < -5) {
            return 'decreasing';
        } else {
            return 'stable';
        }
    }

    /**
     * Generate alerts based on metrics
     */
    private function generateAlerts(array $engagement, array $health): array
    {
        $alerts = [];

        // Low engagement alert
        if ($engagement['engagement_rate'] < 10) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Engagement rate is below 10%',
                'metric' => 'engagement_rate',
                'value' => $engagement['engagement_rate'],
            ];
        }

        // Low network density alert
        if ($health['network_density'] < 5) {
            $alerts[] = [
                'type' => 'info',
                'message' => 'Network density is low - consider connection campaigns',
                'metric' => 'network_density',
                'value' => $health['network_density'],
            ];
        }

        // High growth alert
        if ($health['platform_growth_rate'] > 50) {
            $alerts[] = [
                'type' => 'success',
                'message' => 'Exceptional platform growth detected',
                'metric' => 'platform_growth_rate',
                'value' => $health['platform_growth_rate'],
            ];
        }

        return $alerts;
    }

    // Email Analytics Endpoints

    /**
     * Get email performance metrics
     */
    public function getEmailPerformanceMetrics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'campaign_id' => 'nullable|exists:email_campaigns,id',
            'template_id' => 'nullable|exists:templates,id',
        ]);

        try {
            $tenantId = tenant()->id ?? 1;
            $metrics = $this->emailAnalyticsService->getEmailPerformanceMetrics($tenantId, $validated);

            return response()->json([
                'success' => true,
                'data' => $metrics,
                'message' => 'Email performance metrics retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve email performance metrics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get email funnel analytics
     */
    public function getEmailFunnelAnalytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'campaign_id' => 'nullable|exists:email_campaigns,id',
        ]);

        try {
            $tenantId = tenant()->id ?? 1;
            $funnel = $this->emailAnalyticsService->getFunnelAnalytics($tenantId, $validated);

            return response()->json([
                'success' => true,
                'data' => $funnel,
                'message' => 'Email funnel analytics retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve email funnel analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate email engagement report
     */
    public function generateEmailEngagementReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            $tenantId = tenant()->id ?? 1;
            $report = $this->emailAnalyticsService->generateEngagementReport($tenantId, $validated);

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Email engagement report generated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate email engagement report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get A/B testing results
     */
    public function getABTestResults(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'campaign_id' => 'nullable|exists:email_campaigns,id',
        ]);

        try {
            $tenantId = tenant()->id ?? 1;
            $results = $this->emailAnalyticsService->getABTestResults($tenantId, $validated);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'A/B test results retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve A/B test results',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get real-time email analytics
     */
    public function getRealTimeEmailAnalytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'minutes' => 'nullable|integer|min:1|max:60',
        ]);

        try {
            $tenantId = auth()->user()->current_tenant_id ?? 1;
            $minutes = $validated['minutes'] ?? 5;
            $analytics = $this->emailAnalyticsService->getRealTimeAnalytics($tenantId, $minutes);

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'message' => 'Real-time email analytics retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve real-time email analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate automated email report
     */
    public function generateAutomatedEmailReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'required|string|in:hourly,daily,weekly,monthly',
        ]);

        try {
            $tenantId = auth()->user()->current_tenant_id ?? 1;
            $report = $this->emailAnalyticsService->generateAutomatedReport($tenantId, $validated['period']);

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Automated email report generated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate automated email report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Track email event (for external integrations)
     */
    public function trackEmailEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_analytics_id' => 'required|exists:email_analytics,id',
            'event_type' => 'required|string|in:delivery,open,click,conversion,bounce,complaint,unsubscribe',
            'metadata' => 'nullable|array',
        ]);

        try {
            $result = match ($validated['event_type']) {
                'delivery' => $this->emailAnalyticsService->trackDelivery($validated['email_analytics_id'], $validated['metadata'] ?? []),
                'open' => $this->emailAnalyticsService->trackOpen($validated['email_analytics_id'], $validated['metadata'] ?? []),
                'click' => $this->emailAnalyticsService->trackClick($validated['email_analytics_id'], $validated['metadata']['url'] ?? '', $validated['metadata'] ?? []),
                'conversion' => $this->emailAnalyticsService->trackConversion($validated['email_analytics_id'], $validated['metadata']['type'] ?? 'custom', $validated['metadata']['value'] ?? 0.00, $validated['metadata'] ?? []),
                'bounce' => $this->emailAnalyticsService->trackBounce($validated['email_analytics_id'], $validated['metadata']['reason'] ?? 'Unknown'),
                'complaint' => $this->emailAnalyticsService->trackComplaint($validated['email_analytics_id'], $validated['metadata']['reason'] ?? 'Unknown'),
                'unsubscribe' => $this->emailAnalyticsService->trackUnsubscribe($validated['email_analytics_id']),
            };

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Email event tracked successfully' : 'Failed to track email event',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track email event',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get email analytics dashboard data
     */
    public function getEmailAnalyticsDashboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            $tenantId = auth()->user()->current_tenant_id ?? 1;

            $dashboard = [
                'performance_metrics' => $this->emailAnalyticsService->getEmailPerformanceMetrics($tenantId, $validated),
                'funnel_analytics' => $this->emailAnalyticsService->getFunnelAnalytics($tenantId, $validated),
                'engagement_report' => $this->emailAnalyticsService->generateEngagementReport($tenantId, $validated),
                'ab_test_results' => $this->emailAnalyticsService->getABTestResults($tenantId, $validated),
                'real_time' => $this->emailAnalyticsService->getRealTimeAnalytics($tenantId),
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboard,
                'message' => 'Email analytics dashboard data retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve email analytics dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get heat map data for a specific page URL
     *
     * @param Request $request
     * @param string $pageUrl
     * @return JsonResponse
     */
    public function getHeatMapData(Request $request, string $pageUrl): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        try {
            $tenantService = app(TenantContextService::class);
            $currentTenant = $tenantService->getCurrentTenant();

            $dateRange = [
                'start' => $validated['date_from'] ?? now()->subDays(30)->toDateString(),
                'end' => $validated['date_to'] ?? now()->toDateString(),
            ];

            // Try to get from cache first
            $cacheKey = "heatmap:{$currentTenant->id}:{$pageUrl}:" . md5(serialize($dateRange));
            $cachedData = Cache::get($cacheKey);

            if ($cachedData) {
                return response()->json([
                    'success' => true,
                    'data' => $cachedData,
                ]);
            }

            // Get heat map data from service
            $heatMapService = app(HeatMapService::class);
            $heatMapData = $heatMapService->collectHeatMapData($pageUrl, $dateRange);

            $responseData = [
                'heatMapData' => $heatMapData,
                'pageUrl' => $pageUrl,
                'dateRange' => $dateRange,
                'totalClicks' => array_sum(array_column($heatMapData, 'intensity')),
            ];

            // Cache the result for 1 hour
            Cache::put($cacheKey, $responseData, 3600);

            return response()->json([
                'success' => true,
                'data' => $responseData,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve heat map data', [
                'pageUrl' => $pageUrl,
                'dateRange' => $dateRange ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve heat map data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate heat map data for a specific page URL
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateHeatMapData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page_url' => 'required|string|max:500',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        try {
            $tenantService = app(TenantContextService::class);
            $currentTenant = $tenantService->getCurrentTenant();

            $dateRange = [
                'start' => $validated['date_from'] ?? now()->subDays(30)->toDateString(),
                'end' => $validated['date_to'] ?? now()->toDateString(),
            ];

            // Force regeneration of heat map data
            $heatMapService = app(HeatMapService::class);
            $heatMapData = $heatMapService->collectHeatMapData($validated['page_url'], $dateRange, true);

            $responseData = [
                'heatMapData' => $heatMapData,
                'pageUrl' => $validated['page_url'],
                'dateRange' => $dateRange,
                'totalClicks' => array_sum(array_column($heatMapData, 'intensity')),
            ];

            // Update cache
            $cacheKey = "heatmap:{$currentTenant->id}:{$validated['page_url']}:" . md5(serialize($dateRange));
            Cache::put($cacheKey, $responseData, 3600);

            return response()->json([
                'success' => true,
                'data' => $responseData,
                'message' => 'Heat map data generated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate heat map data', [
                'pageUrl' => $validated['page_url'] ?? null,
                'dateRange' => $dateRange ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate heat map data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Get gamification metrics
     */
    public function getGamificationMetrics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            $dateRange = [];
            if (isset($validated['start_date'])) {
                $dateRange[] = $validated['start_date'];
            }
            if (isset($validated['end_date'])) {
                $dateRange[] = $validated['end_date'];
            }

            $metrics = $this->gamificationService->getGamificationMetrics($dateRange);

            return response()->json([
                'success' => true,
                'data' => $metrics,
                'message' => 'Gamification metrics retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve gamification metrics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Track gamification event
     */
    public function trackGamificationEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|string',
            'gamification_type' => 'required|string|max:100',
            'points_earned' => 'nullable|integer|min:0',
            'badge_earned' => 'nullable|string|max:100',
            'additional_data' => 'nullable|array',
        ]);

        try {
            $result = $this->gamificationService->trackGamificationEvent(
                $validated['user_id'],
                $validated['gamification_type'],
                $validated['points_earned'] ?? null,
                $validated['badge_earned'] ?? null,
                $validated['additional_data'] ?? []
            );

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Gamification event tracked successfully' : 'Failed to track gamification event',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track gamification event',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get leaderboard
     */
    public function getLeaderboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $limit = $validated['limit'] ?? 10;
            $leaderboard = $this->gamificationService->getLeaderboard($limit);

            return response()->json([
                'success' => true,
                'data' => $leaderboard,
                'message' => 'Leaderboard retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve leaderboard',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
