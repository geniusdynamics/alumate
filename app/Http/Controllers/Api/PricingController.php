<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function __construct(
        private PricingService $pricingService
    ) {}

    /**
     * Get pricing plans for a specific audience
     */
    public function getPlans(Request $request): JsonResponse
    {
        $request->validate([
            'audience' => 'required|in:individual,institutional',
        ]);

        $audience = $request->input('audience');
        $plans = $this->pricingService->getPlansForAudience($audience);

        return response()->json([
            'success' => true,
            'data' => [
                'audience' => $audience,
                'plans' => $plans,
                'comparison_features' => $this->pricingService->getComparisonFeatures($audience),
            ],
        ]);
    }

    /**
     * Get feature comparison matrix
     */
    public function getFeatureComparison(Request $request): JsonResponse
    {
        $request->validate([
            'audience' => 'required|in:individual,institutional',
        ]);

        $audience = $request->input('audience');
        $comparison = $this->pricingService->getFeatureComparison($audience);

        return response()->json([
            'success' => true,
            'data' => $comparison,
        ]);
    }

    /**
     * Track pricing interaction
     */
    public function trackInteraction(Request $request): JsonResponse
    {
        $request->validate([
            'event' => 'required|string',
            'audience' => 'required|in:individual,institutional',
            'plan_id' => 'nullable|string',
            'section' => 'nullable|string',
            'additional_data' => 'nullable|array',
        ]);

        $this->pricingService->trackPricingInteraction([
            'event' => $request->input('event'),
            'audience' => $request->input('audience'),
            'plan_id' => $request->input('plan_id'),
            'section' => $request->input('section'),
            'additional_data' => $request->input('additional_data', []),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'timestamp' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Interaction tracked successfully',
        ]);
    }

    /**
     * Get pricing statistics for analytics
     */
    public function getStatistics(): JsonResponse
    {
        $statistics = $this->pricingService->getPricingStatistics();

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }
}
