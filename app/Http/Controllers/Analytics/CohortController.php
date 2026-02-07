<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCohortRequest;
use App\Http\Requests\UpdateCohortRequest;
use App\Models\Cohort;
use App\Services\Analytics\CohortAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Cohort Analytics API Controller
 *
 * Provides RESTful endpoints for cohort analysis, including creation,
 * analysis, comparison, and management of user cohorts.
 */
class CohortController extends Controller
{
    public function __construct(
        private readonly CohortAnalysisService $cohortService
    ) {}

    /**
     * Retrieve paginated list of cohorts with metrics
     */
    public function index(): JsonResponse
    {
        try {
            $tenantId = session('tenant_id', 'default');
            $page = request()->input('page', 1);
            $perPage = request()->input('per_page', 20);

            $cohorts = Cohort::byTenant($tenantId)
                ->with(['createdBy:id,name,email'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            // Add analysis metrics to each cohort
            $cohorts->getCollection()->transform(function ($cohort) {
                $analysis = $this->cohortService->analyzeCohort($cohort->id);
                $cohort->analysis = $analysis ?: [];

                return $cohort;
            });

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
                'tenant_id' => session('tenant_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve cohorts',
            ], 500);
        }
    }

    /**
     * Create a new cohort
     */
    public function store(CreateCohortRequest $request): JsonResponse
    {
        try {
            $tenantId = session('tenant_id', 'default');
            $validated = $request->validated();

            // Add tenant and creator info
            $cohortData = array_merge($validated, [
                'tenant_id' => $tenantId,
                'created_by' => auth()->id(),
            ]);

            // Create cohort using service
            $cohortResult = $this->cohortService->createCohort($cohortData['criteria']);

            if (! $cohortResult) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to create cohort',
                ], 500);
            }

            // Save cohort to database
            $cohort = Cohort::create([
                'tenant_id' => $tenantId,
                'name' => $cohortData['name'],
                'criteria_json' => json_encode($cohortData['criteria']),
                'created_by' => auth()->id(),
                'members_count' => $cohortResult['user_count'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $cohort->load(['createdBy:id,name,email']),
                'message' => 'Cohort created successfully',
            ], 201);

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
     * Get detailed cohort analysis
     */
    public function show(string $cohortId): JsonResponse
    {
        try {
            $tenantId = session('tenant_id', 'default');

            $cohort = Cohort::byTenant($tenantId)
                ->with(['createdBy:id,name,email'])
                ->findOrFail($cohortId);

            // Get detailed analysis
            $analysis = $this->cohortService->analyzeCohort($cohortId);
            $insights = $this->cohortService->generateInsights($cohortId);

            return response()->json([
                'success' => true,
                'data' => [
                    'cohort' => $cohort,
                    'analysis' => $analysis ?: [],
                    'insights' => $insights ?: [],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve cohort analysis', [
                'error' => $e->getMessage(),
                'cohort_id' => $cohortId,
                'tenant_id' => session('tenant_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve cohort analysis',
            ], 500);
        }
    }

    /**
     * Update cohort and recalculate members
     */
    public function update(UpdateCohortRequest $request, string $cohortId): JsonResponse
    {
        try {
            $tenantId = session('tenant_id', 'default');

            $cohort = Cohort::byTenant($tenantId)->findOrFail($cohortId);

            $validated = $request->validated();

            // Update cohort data
            $updateData = [];
            if (isset($validated['name'])) {
                $updateData['name'] = $validated['name'];
            }
            if (isset($validated['criteria'])) {
                $updateData['criteria_json'] = json_encode($validated['criteria']);

                // Recalculate members with new criteria
                $cohortResult = $this->cohortService->createCohort($validated['criteria']);
                if ($cohortResult) {
                    $updateData['members_count'] = $cohortResult['user_count'];
                }
            }

            $cohort->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $cohort->fresh()->load(['createdBy:id,name,email']),
                'message' => 'Cohort updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update cohort', [
                'error' => $e->getMessage(),
                'cohort_id' => $cohortId,
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
    public function destroy(string $cohortId): JsonResponse
    {
        try {
            $tenantId = session('tenant_id', 'default');

            $cohort = Cohort::byTenant($tenantId)->findOrFail($cohortId);

            $cohort->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cohort deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete cohort', [
                'error' => $e->getMessage(),
                'cohort_id' => $cohortId,
                'tenant_id' => session('tenant_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete cohort',
            ], 500);
        }
    }

    /**
     * Compare multiple cohorts
     */
    public function compare(): JsonResponse
    {
        try {
            $cohortIds = request()->input('cohort_ids', []);

            if (empty($cohortIds) || count($cohortIds) < 2) {
                return response()->json([
                    'success' => false,
                    'error' => 'At least 2 cohort IDs are required for comparison',
                ], 400);
            }

            $comparison = $this->cohortService->compareCohorts($cohortIds);

            return response()->json([
                'success' => true,
                'data' => $comparison ?: [],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to compare cohorts', [
                'error' => $e->getMessage(),
                'cohort_ids' => request()->input('cohort_ids', []),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to compare cohorts',
            ], 500);
        }
    }
}
