<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AbTestService;
use App\Http\Requests\Api\StoreAbTestRequest;
use App\Http\Requests\Api\UpdateAbTestRequest;
use App\Http\Resources\AbTestResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A/B Test Controller
 *
 * API endpoints for managing A/B tests
 */
class AbTestController extends Controller
{
    public function __construct(
        private AbTestService $abTestService
    ) {}

    /**
     * Get all A/B tests
     */
    public function index(Request $request): JsonResponse
    {
        $templateId = $request->query('template_id');

        if ($templateId) {
            $abTests = $this->abTestService->getAbTestsForTemplate((int) $templateId);
        } else {
            $abTests = $this->abTestService->getActiveAbTests();
        }

        return response()->json([
            'data' => AbTestResource::collection($abTests),
            'meta' => [
                'count' => $abTests->count()
            ]
        ]);
    }

    /**
     * Create a new A/B test
     */
    public function store(StoreAbTestRequest $request): JsonResponse
    {
        $abTest = $this->abTestService->createAbTest($request->validated());

        return response()->json([
            'data' => new AbTestResource($abTest),
            'message' => 'A/B test created successfully'
        ], 201);
    }

    /**
     * Get A/B test details
     */
    public function show(int $abTestId): JsonResponse
    {
        $abTest = $this->abTestService->getAbTestById($abTestId);

        return response()->json([
            'data' => new AbTestResource($abTest)
        ]);
    }

    /**
     * Update A/B test
     */
    public function update(UpdateAbTestRequest $request, int $abTestId): JsonResponse
    {
        $abTest = $this->abTestService->getAbTestById($abTestId);

        // Only allow updates for draft tests
        if ($abTest->status !== 'draft') {
            return response()->json([
                'message' => 'Cannot update A/B test that is not in draft status'
            ], 422);
        }

        $abTest->update($request->validated());

        return response()->json([
            'data' => new AbTestResource($abTest),
            'message' => 'A/B test updated successfully'
        ]);
    }

    /**
     * Start A/B test
     */
    public function start(int $abTestId): JsonResponse
    {
        $success = $this->abTestService->startAbTest($abTestId);

        if (!$success) {
            return response()->json([
                'message' => 'Failed to start A/B test'
            ], 422);
        }

        return response()->json([
            'message' => 'A/B test started successfully'
        ]);
    }

    /**
     * Stop A/B test
     */
    public function stop(int $abTestId): JsonResponse
    {
        $success = $this->abTestService->stopAbTest($abTestId);

        if (!$success) {
            return response()->json([
                'message' => 'Failed to stop A/B test'
            ], 422);
        }

        return response()->json([
            'message' => 'A/B test stopped successfully'
        ]);
    }

    /**
     * Get A/B test results
     */
    public function results(int $abTestId): JsonResponse
    {
        $results = $this->abTestService->getAbTestResults($abTestId);

        return response()->json([
            'data' => $results
        ]);
    }

    /**
     * Get A/B test statistics
     */
    public function statistics(): JsonResponse
    {
        $statistics = $this->abTestService->getAbTestStatistics();

        return response()->json([
            'data' => $statistics
        ]);
    }

    /**
     * Delete A/B test
     */
    public function destroy(int $abTestId): JsonResponse
    {
        $abTest = $this->abTestService->getAbTestById($abTestId);

        // Only allow deletion of draft tests
        if ($abTest->status !== 'draft') {
            return response()->json([
                'message' => 'Cannot delete A/B test that is not in draft status'
            ], 422);
        }

        $abTest->delete();

        return response()->json([
            'message' => 'A/B test deleted successfully'
        ]);
    }
}