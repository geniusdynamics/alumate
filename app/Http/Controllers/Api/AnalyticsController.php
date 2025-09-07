<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Services\EmailAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService,
        private EmailAnalyticsService $emailAnalyticsService
    ) {
        $this->middleware(['auth', 'role:admin|super_admin']);
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
}
