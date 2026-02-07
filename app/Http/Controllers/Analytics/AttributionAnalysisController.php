<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attribution\BudgetRecommendationsRequest;
use App\Http\Requests\Attribution\CalculateAttributionRequest;
use App\Http\Requests\Attribution\ChannelPerformanceRequest;
use App\Http\Requests\Attribution\CompareModelsRequest;
use App\Http\Requests\Attribution\ConversionPathRequest;
use App\Http\Requests\Attribution\StoreTouchpointRequest;
use App\Models\AttributionTouch;
use App\Services\Analytics\AttributionTrackingService;
use App\Services\TenantContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Attribution Analysis API Controller
 *
 * Provides RESTful endpoints for attribution modeling, including touch tracking,
 * attribution calculation, model comparison, channel performance analysis,
 * budget allocation recommendations, and conversion path visualization.
 */
class AttributionAnalysisController extends Controller
{
    /**
     * Valid attribution models
     */
    public const MODELS = [
        'first_click',
        'last_click',
        'linear',
        'time_decay',
        'position_based',
    ];

    public function __construct(
        private readonly AttributionTrackingService $attributionService,
        private readonly TenantContextService $tenantContextService
    ) {}

    /**
     * List all attribution touchpoints with optional filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->validateTenantIsolation();

            $page = $request->input('page', 1);
            $perPage = min($request->input('per_page', 50), 100);
            $userId = $request->input('user_id');
            $source = $request->input('source');
            $eventType = $request->input('event_type');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $sessionId = $request->input('session_id');

            $query = AttributionTouch::byTenant($this->getCurrentTenantId())
                ->with(['user:id,name,email'])
                ->latest('timestamp');

            // Apply filters
            if ($userId) {
                $query->byUser((int) $userId);
            }

            if ($source) {
                $query->where('source', $source);
            }

            if ($eventType) {
                $query->where('event_type', $eventType);
            }

            if ($sessionId) {
                $query->where('session_id', $sessionId);
            }

            if ($startDate && $endDate) {
                $query->byPeriod($startDate, $endDate);
            }

            $touches = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $touches->items(),
                'pagination' => [
                    'current_page' => $touches->currentPage(),
                    'per_page' => $touches->perPage(),
                    'total' => $touches->total(),
                    'last_page' => $touches->lastPage(),
                ],
                'filters_applied' => [
                    'user_id' => $userId,
                    'source' => $source,
                    'event_type' => $eventType,
                    'session_id' => $sessionId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve attribution touchpoints', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getCurrentTenantId(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve attribution touchpoints',
            ], 500);
        }
    }

    /**
     * Track a new attribution touchpoint
     */
    public function store(StoreTouchpointRequest $request): JsonResponse
    {
        try {
            $this->validateTenantIsolation();

            $validated = $request->validated();

            // Add user_id from authenticated user if not provided
            $userId = Auth::id();
            if (! isset($validated['user_id']) && $userId) {
                $validated['user_id'] = $userId;
            }

            $touch = $this->attributionService->trackTouchpoint(
                (int) $validated['user_id'],
                $validated['source'],
                [
                    'session_id' => $validated['session_id'] ?? null,
                    'event_type' => $validated['event_type'] ?? 'page_view',
                    'medium' => $validated['medium'] ?? null,
                    'campaign' => $validated['campaign'] ?? null,
                    'value' => $validated['value'] ?? 0,
                    'timestamp' => $validated['timestamp'] ?? now(),
                ]
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $touch->id,
                    'user_id' => $touch->user_id,
                    'source' => $touch->source,
                    'event_type' => $touch->event_type,
                    'timestamp' => $touch->timestamp->toIso8601String(),
                    'value' => (float) $touch->value,
                ],
                'message' => 'Attribution touchpoint tracked successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to track attribution touchpoint', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to track attribution touchpoint',
            ], 500);
        }
    }

    /**
     * Get touchpoint details by ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $this->validateTenantIsolation();

            $touch = AttributionTouch::byTenant($this->getCurrentTenantId())
                ->with(['user:id,name,email'])
                ->find($id);

            if (! $touch) {
                return response()->json([
                    'success' => false,
                    'error' => 'Touchpoint not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $touch->id,
                    'user_id' => $touch->user_id,
                    'user' => $touch->user,
                    'session_id' => $touch->session_id,
                    'source' => $touch->source,
                    'medium' => $touch->medium,
                    'campaign' => $touch->campaign,
                    'event_type' => $touch->event_type,
                    'value' => (float) $touch->value,
                    'timestamp' => $touch->timestamp->toIso8601String(),
                    'created_at' => $touch->created_at->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve touchpoint details', [
                'error' => $e->getMessage(),
                'touch_id' => $id,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve touchpoint details',
            ], 500);
        }
    }

    /**
     * Calculate attribution for a user using specified model
     */
    public function calculate(int $userId, CalculateAttributionRequest $request): JsonResponse
    {
        try {
            $this->validateTenantIsolation();

            $validated = $request->validated();

            $model = $validated['model'] ?? 'last_click';
            $startDate = $validated['start_date'] ?? now()->subDays(30)->toDateString();
            $endDate = $validated['end_date'] ?? now()->toDateString();

            $attribution = $this->attributionService->calculateAttribution(
                $userId,
                $model,
                $startDate,
                $endDate
            );

            // Get touch history
            $touchHistory = $this->attributionService->getConversionPath($userId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => [
                    'attribution' => $attribution,
                    'touch_history' => $touchHistory['touchpoints'] ?? [],
                    'period' => ['start' => $startDate, 'end' => $endDate],
                    'model' => $model,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to calculate attribution', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to calculate attribution',
            ], 500);
        }
    }

    /**
     * Compare different attribution models for a user
     */
    public function compareModels(int $userId, CompareModelsRequest $request): JsonResponse
    {
        try {
            $this->validateTenantIsolation();

            $validated = $request->validated();

            $models = $validated['models'] ?? self::MODELS;
            $startDate = $validated['start_date'] ?? now()->subDays(30)->toDateString();
            $endDate = $validated['end_date'] ?? now()->toDateString();

            // Calculate attribution for each model
            $modelResults = [];
            foreach ($models as $model) {
                $modelResults[$model] = $this->attributionService->calculateAttribution(
                    $userId,
                    $model,
                    $startDate,
                    $endDate
                );
            }

            // Generate comparison analysis
            $comparison = $this->generateModelComparison($modelResults);

            // Get user's full conversion path
            $conversionPath = $this->attributionService->getConversionPath($userId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'period' => ['start' => $startDate, 'end' => $endDate],
                    'models_compared' => $models,
                    'model_results' => $modelResults,
                    'comparison' => $comparison,
                    'conversion_path' => $conversionPath,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to compare attribution models', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to compare attribution models',
            ], 500);
        }
    }

    /**
     * Get channel performance analysis
     */
    public function channelPerformance(ChannelPerformanceRequest $request): JsonResponse
    {
        try {
            $this->validateTenantIsolation();

            $validated = $request->validated();

            $startDate = $validated['start_date'] ?? now()->subDays(90)->toDateString();
            $endDate = $validated['end_date'] ?? now()->toDateString();
            $channels = $validated['channels'] ?? null;

            $performance = $this->attributionService->analyzeChannelPerformance(
                $startDate,
                $endDate,
                $channels
            );

            // Add ROI analysis if channel costs are provided
            if (isset($validated['channel_costs']) && is_array($validated['channel_costs'])) {
                $performance['roi_analysis'] = $this->calculateChannelROI($performance['channels'] ?? [], $validated['channel_costs']);
            }

            return response()->json([
                'success' => true,
                'data' => $performance,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get channel performance', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve channel performance data',
            ], 500);
        }
    }

    /**
     * Get budget allocation recommendations
     */
    public function budgetRecommendations(BudgetRecommendationsRequest $request): JsonResponse
    {
        try {
            $this->validateTenantIsolation();

            $validated = $request->validated();

            $startDate = $validated['start_date'] ?? now()->subDays(90)->toDateString();
            $endDate = $validated['end_date'] ?? now()->toDateString();
            $totalBudget = $validated['total_budget'] ?? 0;

            $recommendations = $this->attributionService->generateBudgetRecommendations(
                $startDate,
                $endDate,
                (float) $totalBudget
            );

            if (empty($recommendations) || empty($recommendations['recommendations'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'No budget recommendation data available',
                ], 404);
            }

            // Generate insights
            $insights = $this->generateBudgetInsights($recommendations);

            return response()->json([
                'success' => true,
                'data' => $recommendations,
                'insights' => $insights,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get budget recommendations', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve budget recommendations',
            ], 500);
        }
    }

    /**
     * Get conversion path for a user
     */
    public function conversionPath(int $userId, ConversionPathRequest $request): JsonResponse
    {
        try {
            $this->validateTenantIsolation();

            $validated = $request->validated();

            $startDate = $validated['start_date'] ?? now()->subDays(30)->toDateString();
            $endDate = $validated['end_date'] ?? now()->toDateString();

            $conversionPath = $this->attributionService->getConversionPath(
                $userId,
                $startDate,
                $endDate
            );

            // Add attribution calculation if requested
            if ($validated['include_attribution'] ?? false) {
                $models = $validated['models'] ?? self::MODELS;
                $attributions = [];

                foreach ($models as $model) {
                    $attributions[$model] = $this->attributionService->calculateAttribution(
                        $userId,
                        $model,
                        $startDate,
                        $endDate
                    );
                }

                $conversionPath['attributions'] = $attributions;
            }

            return response()->json([
                'success' => true,
                'data' => $conversionPath,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get conversion path', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve conversion path',
            ], 500);
        }
    }

    /**
     * Get current tenant ID
     */
    private function getCurrentTenantId(): int
    {
        return $this->tenantContextService->getCurrentTenantId() ?? 1;
    }

    /**
     * Validate tenant isolation
     */
    private function validateTenantIsolation(): void
    {
        $tenantId = $this->getCurrentTenantId();

        if (! $tenantId || $tenantId === 1) {
            throw new \Exception('Tenant context is required for this operation');
        }
    }

    /**
     * Generate model comparison analysis
     */
    private function generateModelComparison(array $modelResults): array
    {
        $comparison = [
            'channel_attribution_differences' => [],
            'winner_by_channel' => [],
            'total_value_by_model' => [],
            'insights' => [],
        ];

        // Get unique channels across all models
        $allChannels = [];
        foreach ($modelResults as $model => $result) {
            $comparison['total_value_by_model'][$model] = $result['total_value'] ?? 0;

            if (isset($result['attribution'])) {
                foreach ($result['attribution'] as $attribution) {
                    $channel = $attribution['channel'] ?? 'direct';
                    $allChannels[$channel] = true;
                }
            }
        }

        // Compare channel attribution across models
        foreach ($allChannels as $channel => $_) {
            $channelValues = [];
            foreach ($modelResults as $model => $result) {
                if (isset($result['attribution'])) {
                    foreach ($result['attribution'] as $attribution) {
                        if (($attribution['channel'] ?? 'direct') === $channel) {
                            $channelValues[$model] = $attribution['value'] ?? 0;
                            break;
                        }
                    }
                }
            }

            $comparison['channel_attribution_differences'][$channel] = $channelValues;

            // Find winner for this channel
            if (! empty($channelValues)) {
                $winner = array_keys($channelValues, max($channelValues))[0];
                $comparison['winner_by_channel'][$channel] = [
                    'model' => $winner,
                    'value' => $channelValues[$winner],
                ];
            }
        }

        // Generate insights
        $comparison['insights'] = $this->generateComparisonInsights($modelResults, $comparison);

        return $comparison;
    }

    /**
     * Generate insights from model comparison
     */
    private function generateComparisonInsights(array $modelResults, array $comparison): array
    {
        $insights = [];

        // Check for significant differences between models
        $totalValues = $comparison['total_value_by_model'];
        $minValue = min($totalValues);
        $maxValue = max($totalValues);

        if ($maxValue > 0 && ($maxValue - $minValue) / $maxValue > 0.1) {
            $minModel = array_search($minValue, $totalValues, true);
            $maxModel = array_search($maxValue, $totalValues, true);
            $insights[] = "Attribution model choice significantly impacts results: {$minModel} attributes ".
                round(($maxValue - $minValue) / $maxValue * 100)."% less value than {$maxModel}";
        }

        // Check for first vs last click differences
        if (isset($totalValues['first_click']) && isset($totalValues['last_click'])) {
            $difference = abs($totalValues['first_click'] - $totalValues['last_click']);
            if ($difference > 0) {
                $insights[] = 'First-touch attributes more value to initial engagement, while last-touch focuses on final conversion point';
            }
        }

        return $insights;
    }

    /**
     * Calculate ROI for channels
     */
    private function calculateChannelROI(array $channels, array $channelCosts): array
    {
        $roiAnalysis = [];

        foreach ($channels as $channel) {
            $channelName = $channel['channel'] ?? null;
            $revenue = $channel['total_value'] ?? 0;
            $cost = $channelCosts[$channelName] ?? 0;

            $roi = $cost > 0 ? ($revenue - $cost) / $cost * 100 : ($revenue > 0 ? 1000 : 0);
            $roas = $cost > 0 ? $revenue / $cost : ($revenue > 0 ? PHP_FLOAT_MAX : 0);

            $roiAnalysis[$channelName] = [
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => $revenue - $cost,
                'roi' => round($roi, 2),
                'roas' => round($roas, 2),
                'roi_category' => $this->categorizeROI($roi),
            ];
        }

        return $roiAnalysis;
    }

    /**
     * Categorize ROI values
     */
    private function categorizeROI(float $roi): string
    {
        if ($roi >= 300) {
            return 'excellent';
        } elseif ($roi >= 200) {
            return 'good';
        } elseif ($roi >= 100) {
            return 'fair';
        } elseif ($roi >= 0) {
            return 'poor';
        } else {
            return 'negative';
        }
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
            $channel = $data['channel'] ?? 'Unknown';
            $recommendedPercentage = $data['recommended_percentage'] ?? 0;
            $efficiencyScore = $data['efficiency_score'] ?? 0;

            if ($recommendedPercentage > 25 && $efficiencyScore > 70) {
                $insights[] = "Consider increasing {$channel} budget significantly - high efficiency score ({$efficiencyScore})";
            } elseif ($recommendedPercentage < 10 && $efficiencyScore > 50) {
                $insights[] = "{$channel} may be underfunded despite solid performance - consider increasing allocation";
            } elseif ($efficiencyScore < 30) {
                $insights[] = "Review {$channel} strategy - low efficiency score ({$efficiencyScore}) indicates optimization needed";
            }
        }

        // Overall insight
        if (isset($recommendations['summary']['avg_efficiency_score'])) {
            $avgScore = $recommendations['summary']['avg_efficiency_score'];
            if ($avgScore > 70) {
                $insights[] = 'Overall channel efficiency is strong - current allocation strategy is working well';
            } elseif ($avgScore < 40) {
                $insights[] = 'Channel efficiency is below average - consider reviewing marketing mix strategy';
            }
        }

        return $insights;
    }
}
