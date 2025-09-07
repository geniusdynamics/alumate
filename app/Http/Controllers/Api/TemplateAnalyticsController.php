<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TemplateAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Template Analytics Controller
 *
 * Handles template analytics reporting and dashboard functionality
 */
class TemplateAnalyticsController extends Controller
{
    public function __construct(
        private TemplateAnalyticsService $analyticsService
    ) {}

    /**
     * Get template analytics data
     *
     * @param Request $request
     * @param int $templateId
     * @return JsonResponse
     */
    public function getTemplateAnalytics(Request $request, int $templateId): JsonResponse
    {
        try {
            $filters = $request->only(['start_date', 'end_date', 'tenant_id']);
            $analytics = $this->analyticsService->getTemplateAnalytics($templateId, $filters);

            return response()->json([
                'status' => 'success',
                'data' => $analytics,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get template analytics', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve template analytics',
            ], 500);
        }
    }

    /**
     * Get analytics dashboard data for templates
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAnalyticsDashboard(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['start_date', 'end_date', 'tenant_id']);
            $dashboardData = $this->analyticsService->getAnalyticsDashboard($filters);

            return response()->json([
                'status' => 'success',
                'data' => $dashboardData,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get analytics dashboard', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard data',
            ], 500);
        }
    }

    /**
     * Generate template performance report
     *
     * @param Request $request
     * @param int $templateId
     * @return JsonResponse
     */
    public function generateTemplateReport(Request $request, int $templateId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'period' => 'sometimes|string|in:daily,weekly,monthly,quarterly,yearly',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date',
            ]);

            $period = $validated['period'] ?? 'monthly';
            $filters = $request->only(['start_date', 'end_date', 'tenant_id']);

            $report = $this->analyticsService->generateTemplateReport($templateId, $period, $filters);

            return response()->json([
                'status' => 'success',
                'data' => $report,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate template report', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate report',
            ], 500);
        }
    }

    /**
     * Generate comparative analysis between templates
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateComparativeAnalysis(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'template_ids' => 'required|array|min:2|max:10',
                'template_ids.*' => 'integer|exists:templates,id',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date',
            ]);

            $templateIds = $validated['template_ids'];
            $filters = $request->only(['start_date', 'end_date', 'tenant_id']);

            $analysis = $this->analyticsService->generateComparativeAnalysis($templateIds, $filters);

            return response()->json([
                'status' => 'success',
                'data' => $analysis,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate comparative analysis', [
                'template_ids' => $request->input('template_ids'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate comparative analysis',
            ], 500);
        }
    }

    /**
     * Get template earnings data
     *
     * @param Request $request
     * @param int $templateId
     * @return JsonResponse
     */
    public function getTemplateEarnings(Request $request, int $templateId): JsonResponse
    {
        try {
            $filters = $request->only(['start_date', 'end_date', 'tenant_id']);

            // Placeholder for earnings calculation
            $earnings = [
                'template_id' => $templateId,
                'period' => [
                    'start_date' => $filters['start_date'] ?? now()->subMonth()->toDateString(),
                    'end_date' => $filters['end_date'] ?? now()->toDateString(),
                ],
                'total_earnings' => 0.00,
                'conversions' => 0,
                'average_value_per_conversion' => 0.00,
                'roi_percentage' => 0.0,
                'breakdown' => [],
                'generated_at' => now()->toISOString(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $earnings,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get template earnings', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve earnings data',
            ], 500);
        }
    }

    /**
     * Export template analytics data
     *
     * @param Request $request
     * @param int $templateId
     * @return JsonResponse
     */
    public function exportTemplateAnalytics(Request $request, int $templateId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'format' => 'required|string|in:csv,json,excel',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date',
            ]);

            $format = $validated['format'];
            $filters = $request->only(['start_date', 'end_date', 'tenant_id']);

            // Get analytics data
            $analytics = $this->analyticsService->getTemplateAnalytics($templateId, $filters);

            // Export the data (placeholder implementation)
            $exportedData = [
                'template_id' => $templateId,
                'export_format' => $format,
                'export_timestamp' => now()->toISOString(),
                'data' => $analytics,
            ];

            return response()->json([
                'status' => 'success',
                'export_data' => $exportedData,
                'message' => 'Analytics data exported successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to export template analytics', [
                'template_id' => $templateId,
                'format' => $request->input('format'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export analytics data',
            ], 500);
        }
    }

    /**
     * Get real-time analytics metrics
     *
     * @param Request $request
     * @param int $templateId
     * @return JsonResponse
     */
    public function getRealTimeMetrics(Request $request, int $templateId): JsonResponse
    {
        try {
            $metrics = $this->analyticsService->getRealTimeMetrics($templateId);

            return response()->json([
                'status' => 'success',
                'data' => $metrics,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get real-time metrics', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve real-time metrics',
            ], 500);
        }
    }

    /**
     * Get performance metrics report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPerformanceMetrics(Request $request): JsonResponse
    {
        try {
            $report = $this->analyticsService->getPerformanceMetricsReport();

            return response()->json([
                'status' => 'success',
                'data' => $report,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get performance metrics', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve performance metrics',
            ], 500);
        }
    }

    /**
     * Get GDPR compliance statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getGdprComplianceStats(Request $request): JsonResponse
    {
        try {
            $stats = $this->analyticsService->getGdprComplianceStats();

            return response()->json([
                'status' => 'success',
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get GDPR compliance stats', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve GDPR compliance statistics',
            ], 500);
        }
    }

    /**
     * Export user data for GDPR portability
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportUserData(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_identifier' => 'required|string|max:255',
            ]);

            $userIdentifier = $validated['user_identifier'];
            $exportData = $this->analyticsService->exportUserData($userIdentifier);

            return response()->json([
                'status' => 'success',
                'data' => $exportData,
                'message' => 'User data exported successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to export user data', [
                'user_identifier' => $request->input('user_identifier'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export user data',
            ], 500);
        }
    }

    /**
     * Delete user data for GDPR right to erasure
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteUserData(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_identifier' => 'required|string|max:255',
            ]);

            $userIdentifier = $validated['user_identifier'];
            $deletedCount = $this->analyticsService->deleteUserData($userIdentifier);

            return response()->json([
                'status' => 'success',
                'deleted_count' => $deletedCount,
                'message' => 'User data deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete user data', [
                'user_identifier' => $request->input('user_identifier'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete user data',
            ], 500);
        }
    }
}