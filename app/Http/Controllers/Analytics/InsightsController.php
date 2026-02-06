<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Insight;
use App\Services\Analytics\AutomatedInsightsService;
use App\Services\Analytics\InsightsService;
use App\Services\TenantContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Insights API Controller
 *
 * Provides RESTful endpoints for insights management, including
 * CRUD operations, insight generation, export functionality,
 * and dismissal. Implements proper tenant isolation and RBAC.
 */
class InsightsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private readonly InsightsService $insightsService,
        private readonly AutomatedInsightsService $automatedInsightsService,
        private readonly TenantContextService $tenantContextService
    ) {}

    /**
     * Display a paginated list of insights with filtering
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Authorization check
            if (!Gate::allows('view-insights')) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to view insights.',
                ], Response::HTTP_FORBIDDEN);
            }

            $tenantId = $this->getCurrentTenantId();
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);

            // Build query with filters
            $query = Insight::byTenant($tenantId)
                ->when($request->input('type'), function ($query, $type) {
                    return $query->byType($type);
                })
                ->when($request->input('status'), function ($query, $status) {
                    return $query->byStatus($status);
                })
                ->when($request->input('severity'), function ($query, $severity) {
                    return $query->where('data->severity', $severity);
                })
                ->orderBy('created_at', 'desc');

            $insights = $query->paginate($perPage, ['*'], 'page', $page);

            // Get summary statistics
            $summary = $this->getInsightsSummary($tenantId);

            return response()->json([
                'success' => true,
                'data' => $insights->items(),
                'pagination' => [
                    'current_page' => $insights->currentPage(),
                    'per_page' => $insights->perPage(),
                    'total' => $insights->total(),
                    'last_page' => $insights->lastPage(),
                ],
                'summary' => $summary,
                'filters' => $request->only(['type', 'status', 'severity']),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve insights', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve insights',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display a specific insight
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Authorization check
            if (!Gate::allows('view-insights')) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to view insights.',
                ], Response::HTTP_FORBIDDEN);
            }

            $tenantId = $this->getCurrentTenantId();

            $insight = Insight::byTenant($tenantId)->findOrFail($id);

            // Get effectiveness score if available
            $effectiveness = $this->insightsService->getInsightEffectiveness($insight->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'insight' => $insight,
                    'effectiveness' => $effectiveness,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve insight', [
                'error' => $e->getMessage(),
                'insight_id' => $id,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve insight',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a new insight
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Authorization check
            if (!Gate::allows('create-insights')) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to create insights.',
                ], Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validate([
                'type' => 'required|string|in:trend,anomaly,correlation,predictive,retention,engagement,conversion',
                'data' => 'required|array',
                'status' => 'nullable|string|in:active,dismissed,implemented',
            ]);

            $tenantId = $this->getCurrentTenantId();

            $insight = Insight::create([
                'tenant_id' => $tenantId,
                'type' => $validated['type'],
                'data' => $validated['data'],
                'status' => $validated['status'] ?? 'active',
            ]);

            Log::info('Insight created via API', [
                'insight_id' => $insight->id,
                'type' => $insight->type,
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $insight,
                'message' => 'Insight created successfully',
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            Log::error('Failed to create insight', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create insight',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update an insight
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Authorization check
            if (!Gate::allows('edit-insights')) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to update insights.',
                ], Response::HTTP_FORBIDDEN);
            }

            $tenantId = $this->getCurrentTenantId();

            $insight = Insight::byTenant($tenantId)->findOrFail($id);

            $validated = $request->validate([
                'type' => 'nullable|string|in:trend,anomaly,correlation,predictive,retention,engagement,conversion',
                'data' => 'nullable|array',
                'status' => 'nullable|string|in:active,dismissed,implemented',
            ]);

            $insight->update($validated);

            Log::info('Insight updated via API', [
                'insight_id' => $id,
                'updates' => array_keys($validated),
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $insight->fresh(),
                'message' => 'Insight updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update insight', [
                'error' => $e->getMessage(),
                'insight_id' => $id,
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update insight',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete an insight
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Authorization check
            if (!Gate::allows('delete-insights')) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to delete insights.',
                ], Response::HTTP_FORBIDDEN);
            }

            $tenantId = $this->getCurrentTenantId();

            $insight = Insight::byTenant($tenantId)->findOrFail($id);
            $insight->delete();

            Log::info('Insight deleted via API', [
                'insight_id' => $id,
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Insight deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete insight', [
                'error' => $e->getMessage(),
                'insight_id' => $id,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete insight',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate insights for a date range
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generate(Request $request): JsonResponse
    {
        try {
            // Authorization check
            if (!Gate::allows('generate-insights')) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to generate insights.',
                ], Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'metrics' => 'nullable|array',
                'queue' => 'nullable|boolean',
            ]);

            $tenantId = $this->getCurrentTenantId();
            $startDate = $validated['start_date'] ?? now()->subDays(30)->format('Y-m-d');
            $endDate = $validated['end_date'] ?? now()->format('Y-m-d');
            $metrics = $validated['metrics'] ?? [];
            $queue = $validated['queue'] ?? false;

            // Generate insights using AutomatedInsightsService
            $dateRange = [
                'start' => $startDate,
                'end' => $endDate,
            ];

            if ($queue) {
                // Dispatch job for background processing
                \App\Jobs\Analytics\InsightsGenerationJob::dispatch([
                    'tenant_id' => $tenantId,
                    'date_range' => $dateRange,
                    'metrics' => $metrics,
                    'user_id' => auth()->id(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Insights generation queued',
                ], Response::HTTP_ACCEPTED);
            }

            $insights = $this->automatedInsightsService->generateInsights($dateRange, $metrics);

            // Store generated insights
            $storedInsights = [];
            foreach ($insights as $insightData) {
                $storedInsights[] = Insight::create([
                    'tenant_id' => $tenantId,
                    'type' => $insightData['type'] ?? 'trend',
                    'data' => $insightData,
                    'status' => 'active',
                ]);
            }

            Log::info('Insights generated via API', [
                'count' => count($storedInsights),
                'date_range' => $dateRange,
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Insights generated successfully',
                'data' => [
                    'generated' => $storedInsights,
                    'count' => count($storedInsights),
                ],
                'date_range' => $dateRange,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate insights', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getCurrentTenantId(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate insights',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Export insights to CSV or JSON
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        try {
            // Authorization check
            if (!Gate::allows('export-insights')) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to export insights.',
                ], Response::HTTP_FORBIDDEN);
            }

            $tenantId = $this->getCurrentTenantId();

            $validated = $request->validate([
                'format' => 'nullable|string|in:csv,json',
                'type' => 'nullable|string',
                'status' => 'nullable|string',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ]);

            $format = $validated['format'] ?? 'json';

            // Build query with filters
            $query = Insight::byTenant($tenantId)
                ->when($validated['type'] ?? null, function ($query, $type) {
                    return $query->byType($type);
                })
                ->when($validated['status'] ?? null, function ($query, $status) {
                    return $query->byStatus($status);
                })
                ->when($validated['start_date'] ?? null, function ($query, $date) {
                    return $query->where('created_at', '>=', $date);
                })
                ->when($validated['end_date'] ?? null, function ($query, $date) {
                    return $query->where('created_at', '<=', $date);
                })
                ->orderBy('created_at', 'desc');

            $insights = $query->get();

            if ($format === 'csv') {
                return $this->exportToCsv($insights);
            }

            return response()->json([
                'success' => true,
                'data' => $insights,
                'exported_at' => now()->toISOString(),
                'total_count' => $insights->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to export insights', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getCurrentTenantId(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to export insights',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Dismiss an insight
     *
     * @param int $id
     * @return JsonResponse
     */
    public function dismiss(int $id): JsonResponse
    {
        try {
            // Authorization check
            if (!Gate::allows('dismiss-insights')) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to dismiss insights.',
                ], Response::HTTP_FORBIDDEN);
            }

            $tenantId = $this->getCurrentTenantId();

            $insight = Insight::byTenant($tenantId)->findOrFail($id);

            $insight->update(['status' => 'dismissed']);

            Log::info('Insight dismissed via API', [
                'insight_id' => $id,
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $insight->fresh(),
                'message' => 'Insight dismissed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to dismiss insight', [
                'error' => $e->getMessage(),
                'insight_id' => $id,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to dismiss insight',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Track effectiveness feedback for an insight
     *
     * @param Request $request
     * @param string $insightId
     * @return JsonResponse
     */
    public function trackFeedback(Request $request, string $insightId): JsonResponse
    {
        try {
            // Authorization check
            if (!Gate::allows('track-insights')) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to track insight feedback.',
                ], Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validate([
                'effectiveness_score' => 'required|integer|min:1|max:10',
                'notes' => 'nullable|string|max:1000',
                'metadata' => 'nullable|array',
            ]);

            $success = $this->insightsService->trackEffectiveness(
                $insightId,
                $validated['effectiveness_score'],
                [
                    'notes' => $validated['notes'] ?? null,
                    'metadata' => $validated['metadata'] ?? [],
                    'user_id' => auth()->id(),
                ]
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Feedback recorded successfully',
                    'insight_id' => $insightId,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to record feedback',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        } catch (\Exception $e) {
            Log::error('Failed to track insight feedback', [
                'error' => $e->getMessage(),
                'insight_id' => $insightId,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to track feedback',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get summary statistics for insights
     *
     * @param string $tenantId
     * @return array
     */
    private function getInsightsSummary(string $tenantId): array
    {
        return [
            'total' => Insight::byTenant($tenantId)->count(),
            'active' => Insight::byTenant($tenantId)->byStatus('active')->count(),
            'dismissed' => Insight::byTenant($tenantId)->byStatus('dismissed')->count(),
            'implemented' => Insight::byTenant($tenantId)->byStatus('implemented')->count(),
            'by_type' => [
                'trend' => Insight::byTenant($tenantId)->byType('trend')->count(),
                'anomaly' => Insight::byTenant($tenantId)->byType('anomaly')->count(),
                'correlation' => Insight::byTenant($tenantId)->byType('correlation')->count(),
                'predictive' => Insight::byTenant($tenantId)->byType('predictive')->count(),
            ],
        ];
    }

    /**
     * Export insights to CSV format
     *
     * @param \Illuminate\Support\Collection $insights
     * @return JsonResponse
     */
    private function exportToCsv($insights): JsonResponse
    {
        $headers = ['ID', 'Type', 'Status', 'Severity', 'Message', 'Created At'];
        $rows = [];

        foreach ($insights as $insight) {
            $rows[] = [
                $insight->id,
                $insight->type,
                $insight->status,
                $insight->data['severity'] ?? 'N/A',
                $insight->data['message'] ?? 'N/A',
                $insight->created_at->toISOString(),
            ];
        }

        $csv = implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($cell) => '"' . str_replace('"', '""', $cell) . '"', $row)) . "\n";
        }

        return response()->json([
            'success' => true,
            'format' => 'csv',
            'data' => $csv,
            'exported_at' => now()->toISOString(),
            'total_count' => $insights->count(),
        ]);
    }

    /**
     * Get current tenant ID with fallback
     *
     * @return string
     */
    private function getCurrentTenantId(): string
    {
        return $this->tenantContextService->getCurrentTenantId() ?? 'default';
    }
}
