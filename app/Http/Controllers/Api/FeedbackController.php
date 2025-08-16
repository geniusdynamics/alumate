<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserTestingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function __construct(
        private UserTestingService $userTestingService
    ) {}

    /**
     * Store user feedback
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:bug_report,feature_request,general_feedback,usability_issue',
            'content' => 'required|string|max:2000',
            'rating' => 'nullable|integer|min:1|max:5',
            'metadata' => 'nullable|array',
        ]);

        $feedback = $this->userTestingService->recordFeedback(
            $request->user(),
            $validated['type'],
            $validated['content'],
            $validated['rating'] ?? null,
            $validated['metadata'] ?? []
        );

        return response()->json([
            'message' => 'Feedback submitted successfully',
            'data' => $feedback,
        ], 201);
    }

    /**
     * Get user's feedback history
     */
    public function index(Request $request): JsonResponse
    {
        $feedback = $request->user()
            ->feedback()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($feedback);
    }

    /**
     * Get A/B test variant for user
     */
    public function getABTestVariant(Request $request, string $testName): JsonResponse
    {
        $variant = $this->userTestingService->getABTestVariant(
            $request->user(),
            $testName
        );

        return response()->json([
            'test_name' => $testName,
            'variant' => $variant,
        ]);
    }

    /**
     * Track A/B test conversion
     */
    public function trackConversion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'test_name' => 'required|string',
            'event' => 'required|string',
            'data' => 'nullable|array',
        ]);

        $this->userTestingService->trackConversion(
            $request->user(),
            $validated['test_name'],
            $validated['event'],
            $validated['data'] ?? []
        );

        return response()->json([
            'message' => 'Conversion tracked successfully',
        ]);
    }
}
