<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FundraisingCampaign;
use App\Services\FundraisingAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FundraisingAnalyticsController extends Controller
{
    public function __construct(
        private FundraisingAnalyticsService $analyticsService
    ) {}

    /**
     * Get comprehensive fundraising dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'institution_id' => 'nullable|exists:institutions,id',
        ]);

        $dashboard = $this->analyticsService->getFundraisingDashboard($filters);

        return response()->json($dashboard);
    }

    /**
     * Get giving pattern analysis
     */
    public function givingPatterns(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'institution_id' => 'nullable|exists:institutions,id',
        ]);

        $patterns = $this->analyticsService->getGivingPatternAnalysis($filters);

        return response()->json($patterns);
    }

    /**
     * Get campaign performance metrics
     */
    public function campaignPerformance(Request $request, FundraisingCampaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        $performance = $this->analyticsService->getCampaignPerformanceMetrics($campaign);

        return response()->json($performance);
    }

    /**
     * Get donor analytics
     */
    public function donorAnalytics(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'institution_id' => 'nullable|exists:institutions,id',
            'donor_tier' => 'nullable|string|in:bronze,silver,gold,platinum,diamond',
        ]);

        $analytics = $this->analyticsService->getDonorAnalytics($filters);

        return response()->json($analytics);
    }

    /**
     * Get predictive analytics
     */
    public function predictiveAnalytics(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'institution_id' => 'nullable|exists:institutions,id',
        ]);

        $predictions = $this->analyticsService->getPredictiveAnalytics($filters);

        return response()->json($predictions);
    }

    /**
     * Get ROI metrics for campaigns
     */
    public function roiMetrics(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'institution_id' => 'nullable|exists:institutions,id',
            'campaign_type' => 'nullable|string|in:general,scholarship,emergency,project',
        ]);

        $query = FundraisingCampaign::query();

        if (isset($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('end_date', '<=', $filters['date_to']);
        }

        if (isset($filters['institution_id'])) {
            $query->where('institution_id', $filters['institution_id']);
        }

        if (isset($filters['campaign_type'])) {
            $query->where('type', $filters['campaign_type']);
        }

        $campaigns = $query->get();

        $roiMetrics = $campaigns->map(function ($campaign) {
            $performance = $this->analyticsService->getCampaignPerformanceMetrics($campaign);

            return [
                'campaign_id' => $campaign->id,
                'campaign_title' => $campaign->title,
                'campaign_type' => $campaign->type,
                'roi_metrics' => $performance['roi_metrics'],
                'performance_metrics' => $performance['performance_metrics'],
            ];
        });

        return response()->json([
            'campaigns' => $roiMetrics,
            'summary' => [
                'total_campaigns' => $campaigns->count(),
                'average_roi' => $roiMetrics->avg('roi_metrics.roi_percentage'),
                'total_raised' => $campaigns->sum('raised_amount'),
                'total_estimated_cost' => $roiMetrics->sum('roi_metrics.estimated_cost'),
            ],
        ]);
    }

    /**
     * Get donor engagement scoring
     */
    public function donorEngagement(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'institution_id' => 'nullable|exists:institutions,id',
            'engagement_level' => 'nullable|string|in:high,medium,low',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $analytics = $this->analyticsService->getDonorAnalytics($filters);

        $engagementData = [
            'engagement_scores' => $analytics['engagement_scores'],
            'donor_tiers' => $analytics['donor_tiers'],
            'stewardship_pipeline' => $analytics['stewardship_pipeline'],
            'major_gift_prospects' => $analytics['major_gift_prospects'],
        ];

        return response()->json($engagementData);
    }

    /**
     * Get fundraising trends and forecasting
     */
    public function trends(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'institution_id' => 'nullable|exists:institutions,id',
        ]);

        $dashboard = $this->analyticsService->getFundraisingDashboard($filters);

        return response()->json([
            'trends' => $dashboard['trends'],
            'giving_patterns' => $dashboard['giving_patterns'],
            'overview_metrics' => $dashboard['overview_metrics'],
        ]);
    }

    /**
     * Export analytics data
     */
    public function export(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:dashboard,giving_patterns,donor_analytics,predictive,roi',
            'format' => 'nullable|string|in:json,csv,xlsx',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'institution_id' => 'nullable|exists:institutions,id',
        ]);

        $filters = collect($validated)->except(['type', 'format'])->toArray();
        $format = $validated['format'] ?? 'json';

        $data = match ($validated['type']) {
            'dashboard' => $this->analyticsService->getFundraisingDashboard($filters),
            'giving_patterns' => $this->analyticsService->getGivingPatternAnalysis($filters),
            'donor_analytics' => $this->analyticsService->getDonorAnalytics($filters),
            'predictive' => $this->analyticsService->getPredictiveAnalytics($filters),
            'roi' => $this->getRoiData($filters),
        };

        // For now, return JSON. In a full implementation, you'd handle CSV/Excel export
        return response()->json([
            'data' => $data,
            'export_type' => $validated['type'],
            'format' => $format,
            'generated_at' => now()->toISOString(),
        ]);
    }

    private function getRoiData(array $filters): array
    {
        $query = FundraisingCampaign::query();

        if (isset($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('end_date', '<=', $filters['date_to']);
        }

        if (isset($filters['institution_id'])) {
            $query->where('institution_id', $filters['institution_id']);
        }

        return $query->get()->map(function ($campaign) {
            return $this->analyticsService->getCampaignPerformanceMetrics($campaign);
        })->toArray();
    }
}
