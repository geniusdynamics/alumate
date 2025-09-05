<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TemplatePerformanceDashboardService;
use App\Models\TemplatePerformanceDashboard;
use App\Models\TemplatePerformanceReport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Template Performance Dashboard Controller
 *
 * Handles API endpoints for template performance analytics dashboard
 */
class TemplatePerformanceDashboardController extends Controller
{
    public function __construct(
        private TemplatePerformanceDashboardService $dashboardService
    ) {}

    /**
     * Get dashboard overview
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOverview(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $filters = $request->only(['date_from', 'date_to', 'days', 'template_ids']);

            $overview = $this->dashboardService->getDashboardOverview($tenantId, $filters);

            return response()->json([
                'status' => 'success',
                'data' => $overview,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get dashboard overview', [
                'tenant_id' => $this->getTenantId(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard overview',
            ], 500);
        }
    }

    /**
     * Get real-time metrics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRealTimeMetrics(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $metrics = $this->dashboardService->getRealTimeMetrics($tenantId);

            return response()->json([
                'status' => 'success',
                'data' => $metrics,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get real-time metrics', [
                'tenant_id' => $this->getTenantId(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve real-time metrics',
            ], 500);
        }
    }

    /**
     * Get template comparison analytics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTemplateComparison(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'template_ids' => 'required|array|min:2|max:10',
                'template_ids.*' => 'integer|exists:templates,id',
                'date_from' => 'sometimes|date',
                'date_to' => 'sometimes|date',
            ]);

            $templateIds = $validated['template_ids'];
            $filters = $request->only(['date_from', 'date_to']);

            $comparison = $this->dashboardService->getTemplateComparison($templateIds, $filters);

            return response()->json([
                'status' => 'success',
                'data' => $comparison,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get template comparison', [
                'template_ids' => $request->input('template_ids'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve template comparison',
            ], 500);
        }
    }

    /**
     * Get performance bottleneck analysis
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getBottleneckAnalysis(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->getTenantId();
            $filters = $request->only(['date_from', 'date_to', 'template_ids']);

            $analysis = $this->dashboardService->getBottleneckAnalysis($tenantId, $filters);

            return response()->json([
                'status' => 'success',
                'data' => $analysis,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get bottleneck analysis', [
                'tenant_id' => $this->getTenantId(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve bottleneck analysis',
            ], 500);
        }
    }

    /**
     * Generate performance report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateReport(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'report_type' => 'required|string|in:template_performance,comparison,trend_analysis,bottleneck_analysis',
                'template_ids' => 'sometimes|array',
                'template_ids.*' => 'integer|exists:templates,id',
                'date_from' => 'sometimes|date',
                'date_to' => 'sometimes|date',
                'parameters' => 'nullable|array',
            ]);

            $parameters = array_merge($validated, [
                'tenant_id' => $this->getTenantId(),
            ]);

            $report = $this->dashboardService->generateReport($parameters);

            return response()->json([
                'status' => 'success',
                'data' => $report,
                'message' => 'Report generation started successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate report', [
                'parameters' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate report',
            ], 500);
        }
    }

    /**
     * Get report status
     *
     * @param Request $request
     * @param int $reportId
     * @return JsonResponse
     */
    public function getReportStatus(Request $request, int $reportId): JsonResponse
    {
        try {
            $report = TemplatePerformanceReport::forTenant($this->getTenantId())
                ->findOrFail($reportId);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $report->id,
                    'status' => $report->status,
                    'generated_at' => $report->generated_at?->toISOString(),
                    'error_message' => $report->error_message,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get report status', [
                'report_id' => $reportId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve report status',
            ], 500);
        }
    }

    /**
     * Get report data
     *
     * @param Request $request
     * @param int $reportId
     * @return JsonResponse
     */
    public function getReportData(Request $request, int $reportId): JsonResponse
    {
        try {
            $report = TemplatePerformanceReport::forTenant($this->getTenantId())
                ->findOrFail($reportId);

            if (!$report->isValid()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Report is not available or has expired',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $report->getFormattedReport(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get report data', [
                'report_id' => $reportId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve report data',
            ], 500);
        }
    }

    /**
     * List user reports
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listReports(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'sometimes|string|in:pending,processing,completed,failed',
                'report_type' => 'sometimes|string',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]);

            $query = TemplatePerformanceReport::forTenant($this->getTenantId())
                ->orderBy('created_at', 'desc');

            if (isset($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (isset($validated['report_type'])) {
                $query->where('report_type', $validated['report_type']);
            }

            $perPage = $validated['per_page'] ?? 20;
            $reports = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $reports,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to list reports', [
                'tenant_id' => $this->getTenantId(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve reports',
            ], 500);
        }
    }

    /**
     * Export dashboard data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportDashboard(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'format' => 'required|string|in:json,csv,excel,pdf',
                'date_from' => 'sometimes|date',
                'date_to' => 'sometimes|date',
                'include_trends' => 'sometimes|boolean',
                'include_comparison' => 'sometimes|boolean',
            ]);

            $tenantId = $this->getTenantId();
            $format = $validated['format'];
            $filters = $request->only(['date_from', 'date_to', 'include_trends', 'include_comparison']);

            $exportData = $this->dashboardService->exportDashboardData($tenantId, $format, $filters);

            return response()->json([
                'status' => 'success',
                'data' => $exportData,
                'message' => 'Dashboard data exported successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to export dashboard data', [
                'tenant_id' => $this->getTenantId(),
                'format' => $request->input('format'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export dashboard data',
            ], 500);
        }
    }

    /**
     * Create custom dashboard
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createDashboard(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'configuration' => 'nullable|array',
                'filters' => 'nullable|array',
                'is_default' => 'boolean',
            ]);

            $dashboard = TemplatePerformanceDashboard::create([
                'tenant_id' => $this->getTenantId(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'configuration' => $validated['configuration'] ?? [],
                'filters' => $validated['filters'] ?? [],
                'is_default' => $validated['is_default'] ?? false,
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $dashboard,
                'message' => 'Dashboard created successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create dashboard', [
                'tenant_id' => $this->getTenantId(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create dashboard',
            ], 500);
        }
    }

    /**
     * Update dashboard configuration
     *
     * @param Request $request
     * @param int $dashboardId
     * @return JsonResponse
     */
    public function updateDashboard(Request $request, int $dashboardId): JsonResponse
    {
        try {
            $dashboard = TemplatePerformanceDashboard::forTenant($this->getTenantId())
                ->findOrFail($dashboardId);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string|max:1000',
                'configuration' => 'nullable|array',
                'filters' => 'nullable|array',
                'is_default' => 'boolean',
                'is_active' => 'boolean',
            ]);

            $dashboard->update($validated);

            return response()->json([
                'status' => 'success',
                'data' => $dashboard,
                'message' => 'Dashboard updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update dashboard', [
                'dashboard_id' => $dashboardId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update dashboard',
            ], 500);
        }
    }

    /**
     * Get dashboard configuration
     *
     * @param Request $request
     * @param int $dashboardId
     * @return JsonResponse
     */
    public function getDashboard(Request $request, int $dashboardId): JsonResponse
    {
        try {
            $dashboard = TemplatePerformanceDashboard::forTenant($this->getTenantId())
                ->findOrFail($dashboardId);

            return response()->json([
                'status' => 'success',
                'data' => $dashboard,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get dashboard', [
                'dashboard_id' => $dashboardId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard',
            ], 500);
        }
    }

    /**
     * List user dashboards
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listDashboards(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'is_active' => 'sometimes|boolean',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]);

            $query = TemplatePerformanceDashboard::forTenant($this->getTenantId())
                ->orderBy('created_at', 'desc');

            if (isset($validated['is_active'])) {
                $query->where('is_active', $validated['is_active']);
            }

            $perPage = $validated['per_page'] ?? 20;
            $dashboards = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $dashboards,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to list dashboards', [
                'tenant_id' => $this->getTenantId(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboards',
            ], 500);
        }
    }

    /**
     * Delete dashboard
     *
     * @param Request $request
     * @param int $dashboardId
     * @return JsonResponse
     */
    public function deleteDashboard(Request $request, int $dashboardId): JsonResponse
    {
        try {
            $dashboard = TemplatePerformanceDashboard::forTenant($this->getTenantId())
                ->findOrFail($dashboardId);

            $dashboard->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Dashboard deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete dashboard', [
                'dashboard_id' => $dashboardId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete dashboard',
            ], 500);
        }
    }

    /**
     * Get dashboard widget data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWidgetData(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'widget_type' => 'required|string',
                'parameters' => 'nullable|array',
            ]);

            $tenantId = $this->getTenantId();
            $widgetType = $validated['widget_type'];
            $parameters = $validated['parameters'] ?? [];

            $widgetData = $this->getWidgetDataByType($widgetType, $tenantId, $parameters);

            return response()->json([
                'status' => 'success',
                'data' => $widgetData,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get widget data', [
                'widget_type' => $request->input('widget_type'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve widget data',
            ], 500);
        }
    }

    /**
     * Get widget data by type
     */
    private function getWidgetDataByType(string $widgetType, int $tenantId, array $parameters = []): array
    {
        return match ($widgetType) {
            'metric_card' => $this->getMetricCardData($tenantId, $parameters),
            'chart' => $this->getChartData($tenantId, $parameters),
            'table' => $this->getTableData($tenantId, $parameters),
            default => [],
        };
    }

    /**
     * Get metric card data
     */
    private function getMetricCardData(int $tenantId, array $parameters): array
    {
        $metric = $parameters['metric'] ?? 'total_templates';

        return match ($metric) {
            'total_templates' => [
                'value' => \App\Models\Template::forTenant($tenantId)->active()->count(),
                'label' => 'Total Templates',
                'change' => 0,
                'change_type' => 'neutral',
            ],
            'total_conversions' => [
                'value' => \App\Models\TemplateAnalyticsEvent::forTenant($tenantId)->conversions()->count(),
                'label' => 'Total Conversions',
                'change' => 0,
                'change_type' => 'neutral',
            ],
            default => [
                'value' => 0,
                'label' => 'Unknown Metric',
                'change' => 0,
                'change_type' => 'neutral',
            ],
        };
    }

    /**
     * Get chart data
     */
    private function getChartData(int $tenantId, array $parameters): array
    {
        $chartType = $parameters['chart_type'] ?? 'line';
        $metric = $parameters['metric'] ?? 'conversion_rate_trend';

        // Return sample chart data structure
        return [
            'type' => $chartType,
            'data' => [],
            'labels' => [],
            'datasets' => [],
        ];
    }

    /**
     * Get table data
     */
    private function getTableData(int $tenantId, array $parameters): array
    {
        // Return sample table data structure
        return [
            'headers' => [],
            'rows' => [],
            'pagination' => [
                'current_page' => 1,
                'per_page' => 10,
                'total' => 0,
            ],
        ];
    }

    /**
     * Get current tenant ID
     */
    private function getTenantId(): int
    {
        // In a real multi-tenant setup, this would get the tenant from the current context
        // For now, return a default tenant ID
        return tenant()->id ?? 1;
    }
}