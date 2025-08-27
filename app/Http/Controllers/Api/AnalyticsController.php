<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService
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
}
