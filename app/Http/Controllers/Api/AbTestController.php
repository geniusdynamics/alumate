<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAbTestRequest;
use App\Http\Requests\Api\UpdateAbTestRequest;
use App\Services\ABTestingService;
use App\Services\TenantContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A/B Test API Controller
 *
 * Handles A/B test management endpoints with tenant isolation
 */
class AbTestController extends Controller
{
    public function __construct(
        private ABTestingService $abTestingService,
        private TenantContextService $tenantContext
    ) {}

    /**
     * List A/B tests with pagination and filtering
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeTenantAccess();

        $status = $request->query('status');
        $perPage = $request->query('per_page', 15);

        // For now, return empty array as we need to implement listing in service
        // This would typically query ABTest model with tenant context
        return response()->json([
            'data' => [],
            'meta' => [
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => 1,
                'last_page' => 1
            ]
        ]);
    }

    /**
     * Create a new A/B test
     */
    public function store(StoreAbTestRequest $request): JsonResponse
    {
        $this->authorizeTenantAccess();

        $validated = $request->validated();

        $testId = $this->abTestingService->createTest($validated);

        return response()->json([
            'data' => [
                'id' => $testId,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? '',
                'variants' => $validated['variants'],
                'goal_event' => $validated['goal_event'],
                'status' => 'active'
            ],
            'message' => 'A/B test created successfully'
        ], 201);
    }

    /**
     * Get A/B test details
     */
    public function show(int $id): JsonResponse
    {
        $this->authorizeTenantAccess();

        $test = $this->abTestingService->getTest($id);

        if (!$test) {
            return response()->json([
                'message' => 'A/B test not found'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $test->id,
                'name' => $test->name,
                'description' => $test->description,
                'variants' => $test->variants,
                'status' => $test->status,
                'goal_event' => $test->goal_metric,
                'started_at' => $test->started_at,
                'created_at' => $test->created_at,
                'updated_at' => $test->updated_at
            ]
        ]);
    }

    /**
     * Update A/B test
     */
    public function update(UpdateAbTestRequest $request, int $id): JsonResponse
    {
        $this->authorizeTenantAccess();

        $test = $this->abTestingService->getTest($id);

        if (!$test) {
            return response()->json([
                'message' => 'A/B test not found'
            ], 404);
        }

        $validated = $request->validated();

        $success = $this->abTestingService->updateTest($id, $validated);

        if (!$success) {
            return response()->json([
                'message' => 'Failed to update A/B test'
            ], 422);
        }

        return response()->json([
            'data' => [
                'id' => $test->id,
                'name' => $validated['name'] ?? $test->name,
                'description' => $validated['description'] ?? $test->description,
                'variants' => $validated['variants'] ?? $test->variants,
                'status' => $validated['status'] ?? $test->status
            ],
            'message' => 'A/B test updated successfully'
        ]);
    }

    /**
     * Delete A/B test
     */
    public function destroy(int $id): JsonResponse
    {
        $this->authorizeTenantAccess();

        $test = $this->abTestingService->getTest($id);

        if (!$test) {
            return response()->json([
                'message' => 'A/B test not found'
            ], 404);
        }

        $success = $this->abTestingService->deleteTest($id);

        if (!$success) {
            return response()->json([
                'message' => 'Failed to delete A/B test'
            ], 422);
        }

        return response()->json([
            'message' => 'A/B test deleted successfully'
        ]);
    }

    /**
     * Get A/B test results
     */
    public function results(Request $request, int $id): JsonResponse
    {
        $this->authorizeTenantAccess();

        $dateRange = [];
        if ($request->has('date_from')) {
            $dateRange['start_date'] = $request->query('date_from');
        }
        if ($request->has('date_to')) {
            $dateRange['end_date'] = $request->query('date_to');
        }

        $results = $this->abTestingService->getResults($id, $dateRange);

        if (!$results['test']) {
            return response()->json([
                'message' => 'A/B test not found'
            ], 404);
        }

        return response()->json([
            'data' => [
                'test' => [
                    'id' => $results['test']->id,
                    'name' => $results['test']->name,
                    'goal_event' => $results['test']->goal_metric
                ],
                'variants' => $results['variants'],
                'significance' => $results['overall_significance']
            ]
        ]);
    }

    /**
     * Authorize tenant access for A/B test operations
     */
    private function authorizeTenantAccess(): void
    {
        $user = auth()->user();

        // Check if user has admin/owner role for the current tenant
        if (!$user || !$user->hasRole(['admin', 'super-admin', 'tenant-owner'])) {
            abort(403, 'Unauthorized access to A/B testing');
        }

        // Ensure tenant context is set
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if (!$tenantId) {
            abort(400, 'No tenant context available');
        }
    }
}