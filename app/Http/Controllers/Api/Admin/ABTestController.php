<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ABTest;
use App\Services\UserTestingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ABTestController extends Controller
{
    public function __construct(
        private UserTestingService $userTestingService
    ) {}

    /**
     * Display a listing of A/B tests
     */
    public function index(): JsonResponse
    {
        $tests = ABTest::with(['assignments', 'conversions'])
            ->withCount(['assignments as participants_count', 'conversions as conversions_count'])
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total_tests' => ABTest::count(),
            'active_tests' => ABTest::where('status', 'active')->count(),
            'total_participants' => ABTest::withCount('assignments')->get()->sum('assignments_count'),
            'total_conversions' => ABTest::withCount('conversions')->get()->sum('conversions_count'),
        ];

        return response()->json([
            'tests' => $tests,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created A/B test
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:a_b_tests,name',
            'description' => 'required|string',
            'variants' => 'required|array|min:2',
            'variants.*' => 'required|string|distinct',
        ]);

        $test = $this->userTestingService->createABTest(
            $validated['name'],
            $validated['description'],
            $validated['variants']
        );

        return response()->json([
            'message' => 'A/B test created successfully',
            'data' => $test,
        ], 201);
    }

    /**
     * Display the specified A/B test
     */
    public function show(ABTest $abTest): JsonResponse
    {
        $abTest->load(['assignments', 'conversions']);

        // Calculate variant statistics
        $variantStats = [];
        foreach ($abTest->variants as $variant) {
            $participants = $abTest->assignments()->where('variant', $variant)->count();
            $conversions = $abTest->conversions()->where('variant', $variant)->count();

            $variantStats[$variant] = [
                'participants' => $participants,
                'conversions' => $conversions,
                'conversion_rate' => $participants > 0 ? ($conversions / $participants) * 100 : 0,
            ];
        }

        $abTest->variant_stats = $variantStats;

        return response()->json([
            'data' => $abTest,
        ]);
    }

    /**
     * Update the specified A/B test
     */
    public function update(Request $request, ABTest $abTest): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:draft,active,paused,completed',
            'description' => 'sometimes|string',
            'distribution' => 'sometimes|array',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'active' && $abTest->status === 'draft') {
            $validated['started_at'] = now();
        }

        if (isset($validated['status']) && $validated['status'] === 'completed') {
            $validated['ended_at'] = now();
        }

        $abTest->update($validated);

        return response()->json([
            'message' => 'A/B test updated successfully',
            'data' => $abTest,
        ]);
    }

    /**
     * Remove the specified A/B test
     */
    public function destroy(ABTest $abTest): JsonResponse
    {
        if ($abTest->status === 'active') {
            return response()->json([
                'message' => 'Cannot delete an active A/B test',
            ], 422);
        }

        $abTest->delete();

        return response()->json([
            'message' => 'A/B test deleted successfully',
        ]);
    }

    /**
     * Get A/B test analytics
     */
    public function analytics(): JsonResponse
    {
        $analytics = $this->userTestingService->getTestingAnalytics();

        return response()->json($analytics);
    }
}
