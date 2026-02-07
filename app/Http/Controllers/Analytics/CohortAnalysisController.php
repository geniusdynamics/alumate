<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Requests\CohortConversionRequest;
use App\Http\Requests\CohortEngagementRequest;
use App\Http\Requests\CohortRetentionRequest;
use App\Http\Requests\CohortTrendsRequest;
use App\Http\Requests\CompareCohortAnalysisRequest;
use App\Http\Requests\StoreCohortAnalysisRequest;
use App\Http\Requests\UpdateCohortAnalysisRequest;
use App\Models\Cohort;
use App\Services\Analytics\CohortAnalysisService;
use App\Services\TenantContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Cohort Analysis API Controller
 *
 * Provides RESTful endpoints for comprehensive cohort analysis, including
 * retention metrics, engagement analysis, conversion rates, trend analysis,
 * and automated insights generation. Implements proper tenant isolation
 * and role-based access control.
 */
class CohortAnalysisController extends Controller
{
    public function __construct(
        private readonly CohortAnalysisService $cohortAnalysisService,
        private readonly TenantContextService $tenantContextService
    ) {}

    /**
     * Retrieve paginated list of all cohorts with summary metrics
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);
            $includeMetrics = $request->input('include_metrics', true);

            $query = Cohort::byTenant($tenantId)
                ->with(['createdBy:id,name,email'])
                ->orderBy('created_at', 'desc');

            $cohorts = $query->paginate($perPage, ['*'], 'page', $page);

            if ($includeMetrics) {
                $cohorts->getCollection()->transform(function ($cohort) {
                    $analysis = $this->cohortAnalysisService->analyzeCohort($cohort->id);
                    $cohort->summary = [
                        'size' => $cohort->members_count,
                        'day7_retention' => $analysis['retention']['day7'] ?? 0,
                        'day30_retention' => $analysis['retention']['day30'] ?? 0,
                        'engagement_score' => $analysis['engagement']['engagement_score'] ?? 0,
                    ];

                    return $cohort;
                });
            }

            return response()->json([
                'success' => true,
                'data' => $cohorts->items(),
                'pagination' => [
                    'current_page' => $cohorts->currentPage(),
                    'per_page' => $cohorts->perPage(),
                    'total' => $cohorts->total(),
                    'last_page' => $cohorts->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve cohorts', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve cohorts',
            ], 500);
        }
    }

    /**
     * Create a new cohort with specified criteria
     */
    public function store(StoreCohortAnalysisRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $tenantId = $this->getCurrentTenantId();

            $cohort = $this->cohortAnalysisService->createCohort(
                $validated['name'],
                $validated['criteria'],
                auth()->id()
            );

            Log::info('Cohort created via API', [
                'cohort_id' => $cohort->id,
                'name' => $cohort->name,
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $cohort->load(['createdBy:id,name,email']),
                'message' => 'Cohort created successfully',
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to create cohort', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create cohort',
            ], 500);
        }
    }

    /**
     * Retrieve detailed cohort information with full analysis
     */
    public function show(int $id): JsonResponse
    {
        try {
            $tenantId = $this->getCurrentTenantId();

            $cohort = Cohort::byTenant($tenantId)
                ->with(['createdBy:id,name,email'])
                ->findOrFail($id);

            // Get comprehensive analysis
            $analysis = $this->cohortAnalysisService->analyzeCohort($id);
            $insights = $this->cohortAnalysisService->generateInsights($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'cohort' => $cohort,
                    'analysis' => $analysis ?: [],
                    'insights' => $insights ?: [],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve cohort', [
                'error' => $e->getMessage(),
                'cohort_id' => $id,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve cohort',
            ], 500);
        }
    }

    /**
     * Update cohort configuration and criteria
     */
    public function update(UpdateCohortAnalysisRequest $request, int $id): JsonResponse
    {
        try {
            $tenantId = $this->getCurrentTenantId();

            $cohort = Cohort::byTenant($tenantId)->findOrFail($id);

            $validated = $request->validated();

            $updateData = [];

            if (isset($validated['name'])) {
                $updateData['name'] = $validated['name'];
            }

            if (isset($validated['criteria'])) {
                $updateData['criteria_json'] = $validated['criteria'];

                // Recalculate members count with new criteria
                $newCohort = $this->cohortAnalysisService->createCohort(
                    $cohort->name,
                    $validated['criteria'],
                    auth()->id()
                );
                $updateData['members_count'] = $newCohort->members_count ?? 0;
            }

            $cohort->update($updateData);

            Log::info('Cohort updated via API', [
                'cohort_id' => $id,
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
                'updates' => array_keys($updateData),
            ]);

            return response()->json([
                'success' => true,
                'data' => $cohort->fresh()->load(['createdBy:id,name,email']),
                'message' => 'Cohort updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update cohort', [
                'error' => $e->getMessage(),
                'cohort_id' => $id,
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update cohort',
            ], 500);
        }
    }

    /**
     * Delete a cohort
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $tenantId = $this->getCurrentTenantId();

            $cohort = Cohort::byTenant($tenantId)->findOrFail($id);
            $cohortName = $cohort->name;

            $cohort->delete();

            Log::info('Cohort deleted via API', [
                'cohort_id' => $id,
                'name' => $cohortName,
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cohort deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete cohort', [
                'error' => $e->getMessage(),
                'cohort_id' => $id,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete cohort',
            ], 500);
        }
    }

    /**
     * Get retention metrics for a cohort
     */
    public function retention(CohortRetentionRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $tenantId = $this->getCurrentTenantId();

            // Verify cohort access
            $cohort = Cohort::byTenant($tenantId)->findOrFail($id);

            $daysAfter = $validated['days_after'] ?? [7, 30, 90];
            $retentionData = [];

            foreach ($daysAfter as $day) {
                $retentionData["day{$day}"] = $this->cohortAnalysisService->calculateRetention($id, $day);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'cohort_id' => $id,
                    'cohort_name' => $cohort->name,
                    'retention' => $retentionData,
                    'churn_rate' => [
                        'day7' => round(100 - ($retentionData['day7'] ?? 0), 2),
                        'day30' => round(100 - ($retentionData['day30'] ?? 0), 2),
                        'day90' => round(100 - ($retentionData['day90'] ?? 0), 2),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get retention metrics', [
                'error' => $e->getMessage(),
                'cohort_id' => $id,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve retention metrics',
            ], 500);
        }
    }

    /**
     * Get engagement metrics for a cohort
     */
    public function engagement(CohortEngagementRequest $request, int $id): JsonResponse
    {
        try {
            $tenantId = $this->getCurrentTenantId();

            // Verify cohort access
            $cohort = Cohort::byTenant($tenantId)->findOrFail($id);

            $engagement = $this->cohortAnalysisService->calculateEngagement($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'cohort_id' => $id,
                    'cohort_name' => $cohort->name,
                    'engagement' => $engagement,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get engagement metrics', [
                'error' => $e->getMessage(),
                'cohort_id' => $id,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve engagement metrics',
            ], 500);
        }
    }

    /**
     * Get conversion rates for a cohort
     */
    public function conversion(CohortConversionRequest $request, int $id): JsonResponse
    {
        try {
            $tenantId = $this->getCurrentTenantId();

            // Verify cohort access
            $cohort = Cohort::byTenant($tenantId)->findOrFail($id);

            $conversions = $this->cohortAnalysisService->calculateConversionRates($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'cohort_id' => $id,
                    'cohort_name' => $cohort->name,
                    'conversions' => $conversions,
                    'total_users' => $cohort->members_count,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get conversion rates', [
                'error' => $e->getMessage(),
                'cohort_id' => $id,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve conversion rates',
            ], 500);
        }
    }

    /**
     * Compare multiple cohorts
     */
    public function compare(CompareCohortAnalysisRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $tenantId = $this->getCurrentTenantId();

            // Authorization check
            if (! Gate::allows('cohort.compare')) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to compare cohorts.',
                ], 403);
            }

            $cohortIds = $validated['cohort_ids'];
            $metrics = $validated['metrics'] ?? ['retention', 'engagement'];

            $comparison = $this->cohortAnalysisService->compareCohorts($cohortIds);

            if (empty($comparison)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unable to compare cohorts. Please verify cohort IDs.',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $comparison,
                'metrics_analyzed' => $metrics,
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to compare cohorts', [
                'error' => $e->getMessage(),
                'cohort_ids' => $request->input('cohort_ids', []),
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to compare cohorts',
            ], 500);
        }
    }

    /**
     * Get trend analysis for a cohort
     */
    public function trends(CohortTrendsRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $tenantId = $this->getCurrentTenantId();

            // Verify cohort access
            $cohort = Cohort::byTenant($tenantId)->findOrFail($id);

            $period = $validated['period'] ?? 'week';
            $periods = $validated['periods'] ?? 12;

            $trends = $this->cohortAnalysisService->analyzeTrends($id, $period, $periods);

            return response()->json([
                'success' => true,
                'data' => $trends,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to analyze trends', [
                'error' => $e->getMessage(),
                'cohort_id' => $id,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve trend analysis',
            ], 500);
        }
    }

    /**
     * Get automated insights for a cohort
     */
    public function insights(Request $request, int $id): JsonResponse
    {
        try {
            $tenantId = $this->getCurrentTenantId();

            // Verify cohort access
            $cohort = Cohort::byTenant($tenantId)->findOrFail($id);

            $insights = $this->cohortAnalysisService->generateInsights($id);

            // Categorize insights by severity
            $categorizedInsights = [
                'critical' => [],
                'high' => [],
                'medium' => [],
                'positive' => [],
            ];

            foreach ($insights as $insight) {
                $severity = $insight['severity'] ?? 'medium';
                if (isset($categorizedInsights[$severity])) {
                    $categorizedInsights[$severity][] = $insight;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'cohort_id' => $id,
                    'cohort_name' => $cohort->name,
                    'insights' => $insights,
                    'categorized' => $categorizedInsights,
                    'total_insights' => count($insights),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate insights', [
                'error' => $e->getMessage(),
                'cohort_id' => $id,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate insights',
            ], 500);
        }
    }

    /**
     * Get current tenant ID with fallback
     */
    private function getCurrentTenantId(): int
    {
        return $this->tenantContextService->getCurrentTenantId() ?? 1;
    }
}
