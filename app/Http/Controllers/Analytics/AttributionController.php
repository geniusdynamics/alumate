<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrackTouchRequest;
use App\Models\AttributionTouch;
use App\Services\Analytics\AttributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Attribution Analytics API Controller
 *
 * Provides RESTful endpoints for attribution modeling, including touch tracking,
 * attribution calculation, and reporting for marketing analytics.
 */
class AttributionController extends Controller
{
    public function __construct(
        private readonly AttributionService $attributionService
    ) {}

    /**
     * Retrieve paginated list of attribution touches with optional filtering
     */
    public function index(): JsonResponse
    {
        try {
            $tenantId = session('tenant_id', 'default');
            $page = request()->input('page', 1);
            $perPage = request()->input('per_page', 50);
            $userId = request()->input('user_id');
            $source = request()->input('source');
            $startDate = request()->input('start_date');
            $endDate = request()->input('end_date');
            $model = request()->input('model', 'last_touch');

            $query = AttributionTouch::byTenant($tenantId)
                ->with(['user:id,name,email'])
                ->latest();

            // Apply filters
            if ($userId) {
                $query->byUser($userId);
            }

            if ($source) {
                $query->where('source', $source);
            }

            if ($startDate && $endDate) {
                $query->byPeriod($startDate, $endDate);
            }

            $touches = $query->paginate($perPage, ['*'], 'page', $page);

            // Calculate attribution for filtered data if user_id is specified
            $attribution = null;
            if ($userId && $startDate && $endDate) {
                $attribution = $this->attributionService->calculateAttribution(
                    (int) $userId,
                    $startDate,
                    $endDate,
                    $model
                );
            }

            return response()->json([
                'success' => true,
                'data' => $touches->items(),
                'attribution' => $attribution,
                'pagination' => [
                    'current_page' => $touches->currentPage(),
                    'per_page' => $touches->perPage(),
                    'total' => $touches->total(),
                    'last_page' => $touches->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve attribution touches', [
                'error' => $e->getMessage(),
                'tenant_id' => session('tenant_id'),
                'filters' => request()->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve attribution touches',
            ], 500);
        }
    }

    /**
     * Track a new attribution touch
     */
    public function store(TrackTouchRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Add user_id from authenticated user if not provided
            if (! isset($validated['user_id'])) {
                $validated['user_id'] = auth()->id();
            }

            // Track touch using service
            $touch = $this->attributionService->trackTouch($validated);

            return response()->json([
                'success' => true,
                'data' => $touch->load(['user:id,name,email']),
                'message' => 'Attribution touch tracked successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to track attribution touch', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to track attribution touch',
            ], 500);
        }
    }

    /**
     * Get attribution report for a specific user
     */
    public function show(?int $userId = null): JsonResponse
    {
        try {
            $tenantId = session('tenant_id', 'default');
            $startDate = request()->input('start_date', now()->subDays(30)->toDateString());
            $endDate = request()->input('end_date', now()->toDateString());
            $model = request()->input('model', 'last_touch');

            // Use authenticated user if no user_id provided
            $targetUserId = $userId ?: auth()->id();

            if (! $targetUserId) {
                return response()->json([
                    'success' => false,
                    'error' => 'User ID is required',
                ], 400);
            }

            // Get attribution calculation
            $attribution = $this->attributionService->calculateAttribution(
                $targetUserId,
                $startDate,
                $endDate,
                $model
            );

            // Get touch history
            $touchHistory = $this->attributionService->getTouchHistory($targetUserId, 20);

            return response()->json([
                'success' => true,
                'data' => [
                    'attribution' => $attribution ?: [],
                    'touch_history' => $touchHistory,
                    'period' => ['start' => $startDate, 'end' => $endDate],
                    'model' => $model,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve attribution report', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'tenant_id' => session('tenant_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve attribution report',
            ], 500);
        }
    }

    /**
     * Get attribution summary for multiple users
     */
    public function summary(): JsonResponse
    {
        try {
            $userIds = request()->input('user_ids', []);
            $startDate = request()->input('start_date', now()->subDays(30)->toDateString());
            $endDate = request()->input('end_date', now()->toDateString());
            $model = request()->input('model', 'last_touch');

            if (empty($userIds)) {
                return response()->json([
                    'success' => false,
                    'error' => 'User IDs are required for summary',
                ], 400);
            }

            $summary = $this->attributionService->getAttributionSummary(
                $userIds,
                $startDate,
                $endDate,
                $model
            );

            return response()->json([
                'success' => true,
                'data' => $summary ?: [],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve attribution summary', [
                'error' => $e->getMessage(),
                'user_ids' => request()->input('user_ids', []),
                'tenant_id' => session('tenant_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve attribution summary',
            ], 500);
        }
    }

    /**
     * Get channel performance metrics with ROI analysis
     */
    public function channelPerformance(): JsonResponse
    {
        try {
            $startDate = request()->input('start_date', now()->subDays(90)->toDateString());
            $endDate = request()->input('end_date', now()->toDateString());
            $channels = request()->input('channels', ['google', 'facebook', 'linkedin', 'organic', 'direct', 'email']);

            $performance = [];

            foreach ($channels as $channel) {
                $contribution = $this->attributionService->getChannelContribution(
                    $channel,
                    $startDate,
                    $endDate
                );

                if (! empty($contribution)) {
                    $roiData = $this->attributionService->calculateChannelROI($channel, $startDate, $endDate, 0);
                    $performance[$channel] = array_merge($contribution, [
                        'roi' => round($roiData['roi'], 2),
                        'roi_category' => $this->categorizeROI($roiData['roi']),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $performance,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
                'summary' => $this->calculatePerformanceSummary($performance),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get channel performance', [
                'error' => $e->getMessage(),
                'tenant_id' => session('tenant_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve channel performance data',
            ], 500);
        }
    }

    /**
     * Get budget allocation recommendations based on performance
     */
    public function budgetRecommendations(): JsonResponse
    {
        try {
            $startDate = request()->input('start_date', now()->subDays(90)->toDateString());
            $endDate = request()->input('end_date', now()->toDateString());
            $totalBudget = request()->input('total_budget', 0);

            $recommendations = $this->attributionService->generateBudgetRecommendations($startDate, $endDate, $totalBudget);

            if (empty($recommendations)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No budget recommendation data available',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $recommendations,
                'insights' => $this->generateBudgetInsights($recommendations),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get budget recommendations', [
                'error' => $e->getMessage(),
                'tenant_id' => session('tenant_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve budget recommendations',
            ], 500);
        }
    }

    /**
     * Categorize ROI values
     */
    private function categorizeROI(float $roi): string
    {
        if ($roi >= 3.0) {
            return 'excellent';
        } elseif ($roi >= 2.0) {
            return 'good';
        } elseif ($roi >= 1.0) {
            return 'fair';
        } elseif ($roi >= 0.5) {
            return 'poor';
        } else {
            return 'negative';
        }
    }

    /**
     * Calculate performance summary statistics
     */
    private function calculatePerformanceSummary(array $performance): array
    {
        if (empty($performance)) {
            return [
                'total_conversions' => 0,
                'total_conversion_value' => 0,
                'average_engagement' => 0,
                'roi_distribution' => [],
                'best_performing_channel' => null,
            ];
        }

        $totalConversions = array_sum(array_column($performance, 'total_touches'));
        $totalValue = array_sum(array_column($performance, 'total_value'));
        $avgEngagement = array_sum(array_column($performance, 'conversion_rate')) / count($performance);

        $roiCategories = array_count_values(array_column($performance, 'roi_category'));

        return [
            'total_conversions' => $totalConversions,
            'total_conversion_value' => round($totalValue, 2),
            'average_engagement' => round($avgEngagement, 2),
            'roi_distribution' => $roiCategories,
            'best_performing_channel' => $this->findBestChannel($performance),
        ];
    }

    /**
     * Find best performing channel based on ROI
     */
    private function findBestChannel(array $performance): ?string
    {
        $bestChannel = null;
        $bestROI = -1;

        foreach ($performance as $channel => $data) {
            if ($data['roi'] > $bestROI) {
                $bestROI = $data['roi'];
                $bestChannel = $channel;
            }
        }

        return $bestChannel;
    }

    /**
     * Generate insights from budget recommendations
     */
    private function generateBudgetInsights(array $recommendations): array
    {
        $insights = [];

        if (! isset($recommendations['recommendations'])) {
            return $insights;
        }

        foreach ($recommendations['recommendations'] as $data) {
            $channel = $data['channel'];
            $changePercentage = $data['recommended_percentage'] - 100; // Calculate change from current

            if ($changePercentage > 15) {
                $insights[] = "Consider increasing {$channel} budget by {$changePercentage}% due to strong ROI performance.";
            } elseif ($changePercentage < -10) {
                $insights[] = "Consider reducing {$channel} budget by ".abs($changePercentage).'% due to low ROI performance.';
            }
        }

        // Add overall insight
        if (isset($recommendations['summary']['avg_roi'])) {
            $insights[] = "Average ROI across all channels: {$recommendations['summary']['avg_roi']}%";
        }

        return $insights;
    }
}
