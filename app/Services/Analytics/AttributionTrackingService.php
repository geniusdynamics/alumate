<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\AttributionTouch;
use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Services\BaseService;
use App\Services\TenantContextService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * AttributionTrackingService
 *
 * Provides comprehensive multi-touch customer journey tracking and attribution modeling.
 * Supports first-click, last-click, linear, time-decay, and position-based attribution models.
 * Implements proper tenant isolation and caching for optimized performance.
 */
class AttributionTrackingService extends BaseService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_TTL_SHORT = 300; // 5 minutes for frequently accessed data
    private const CHUNK_SIZE = 1000;

    /**
     * Valid attribution models
     */
    public const MODEL_FIRST_CLICK = 'first_click';
    public const MODEL_LAST_CLICK = 'last_click';
    public const MODEL_LINEAR = 'linear';
    public const MODEL_TIME_DECAY = 'time_decay';
    public const MODEL_POSITION_BASED = 'position_based';

    /**
     * Valid event types for touchpoints
     */
    public const EVENT_TYPES = [
        'page_view',
        'click',
        'form_submit',
        'signup',
        'login',
        'purchase',
        'subscription',
        'download',
        'share',
    ];

    /**
     * Channel definitions with default weights
     */
    public const CHANNELS = [
        'organic_search' => ['display_name' => 'Organic Search', 'default_cost' => 0],
        'paid_search' => ['display_name' => 'Paid Search', 'default_cost' => 1.50],
        'social_organic' => ['display_name' => 'Organic Social', 'default_cost' => 0],
        'social_paid' => ['display_name' => 'Paid Social', 'default_cost' => 2.00],
        'email' => ['display_name' => 'Email', 'default_cost' => 0.10],
        'referral' => ['display_name' => 'Referral', 'default_cost' => 0],
        'direct' => ['display_name' => 'Direct', 'default_cost' => 0],
        'display' => ['display_name' => 'Display Advertising', 'default_cost' => 3.00],
        'affiliate' => ['display_name' => 'Affiliate', 'default_cost' => 2.50],
        'video' => ['display_name' => 'Video', 'default_cost' => 4.00],
    ];

    public function __construct(TenantContextService $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    /**
     * Track a touchpoint in the user journey
     *
     * @param int $userId User ID
     * @param string $channel Marketing channel (e.g., 'paid_search', 'email')
     * @param array $data Additional touchpoint data
     * @return AttributionTouch
     */
    public function trackTouchpoint(int $userId, string $channel, array $data = []): AttributionTouch
    {
        $this->ensureTenantContext();

        try {
            $validatedData = $this->validateTouchpointData($data);

            $touchData = [
                'tenant_id' => $this->getCurrentTenantId(),
                'user_id' => $userId,
                'session_id' => $validatedData['session_id'] ?? $this->generateSessionId(),
                'event_type' => $validatedData['event_type'] ?? 'page_view',
                'source' => $channel,
                'medium' => $validatedData['medium'] ?? null,
                'campaign' => $validatedData['campaign'] ?? null,
                'value' => $validatedData['value'] ?? 0,
                'timestamp' => $validatedData['timestamp'] ?? now(),
            ];

            $touch = AttributionTouch::create($touchData);

            // Clear relevant caches
            $this->clearUserAttributionCache($userId);

            Log::info('Touchpoint tracked', [
                'touch_id' => $touch->id,
                'user_id' => $userId,
                'channel' => $channel,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return $touch;

        } catch (Exception $e) {
            $this->handleServiceError($e, 'trackTouchpoint', ['user_id' => $userId, 'channel' => $channel]);
            throw $e;
        }
    }

    /**
     * Calculate attribution for a conversion using specified model
     *
     * @param int $conversionId Conversion/Event ID
     * @param string $model Attribution model to use
     * @param string $startDate Start date for attribution window
     * @param string $endDate End date for attribution window
     * @return array Attribution results
     */
    public function calculateAttribution(
        int $userId,
        string $model = self::MODEL_LAST_CLICK,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $this->ensureTenantContext();

        $startDate = $startDate ?? Carbon::now()->subDays(30)->toDateString();
        $endDate = $endDate ?? Carbon::now()->toDateString();

        $cacheKey = $this->getAttributionCacheKey($userId, $model, $startDate, $endDate);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $model, $startDate, $endDate) {
            $touches = $this->getUserTouchesInPeriod($userId, $startDate, $endDate);

            if ($touches->isEmpty()) {
                return $this->emptyAttributionResult($userId, $model, $startDate, $endDate);
            }

            $totalValue = $touches->sum('value');
            $attribution = $this->applyMultiTouchModel($touches, $totalValue, $model);

            return [
                'user_id' => $userId,
                'model' => $model,
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_value' => round($totalValue, 2),
                'touch_count' => $touches->count(),
                'attribution' => $attribution,
            ];
        });
    }

    /**
     * First-click attribution model - 100% credit to first touchpoint
     *
     * @param int $userId User ID
     * @param string|null $startDate Start date
     * @param string|null $endDate End date
     * @return array
     */
    public function firstClickAttribution(
        int $userId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $startDate = $startDate ?? Carbon::now()->subDays(30)->toDateString();
        $endDate = $endDate ?? Carbon::now()->toDateString();

        $cacheKey = $this->getAttributionCacheKey($userId, 'first_click', $startDate, $endDate);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $startDate, $endDate) {
            $touches = $this->getUserTouchesInPeriod($userId, $startDate, $endDate)
                ->sortBy('timestamp');

            if ($touches->isEmpty()) {
                return $this->emptyAttributionResult($userId, self::MODEL_FIRST_CLICK, $startDate, $endDate);
            }

            $firstTouch = $touches->first();
            $totalValue = $touches->sum('value');

            return [
                'user_id' => $userId,
                'model' => self::MODEL_FIRST_CLICK,
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_value' => round($totalValue, 2),
                'touch_count' => $touches->count(),
                'attribution' => [
                    [
                        'channel' => $firstTouch->source ?? 'direct',
                        'medium' => $firstTouch->medium ?? null,
                        'campaign' => $firstTouch->campaign ?? null,
                        'percentage' => 100.0,
                        'value' => round($totalValue, 2),
                        'touch_position' => 'first',
                        'touch_timestamp' => $firstTouch->timestamp->toIso8601String(),
                    ],
                ],
            ];
        });
    }

    /**
     * Last-click attribution model - 100% credit to last touchpoint
     *
     * @param int $userId User ID
     * @param string|null $startDate Start date
     * @param string|null $endDate End date
     * @return array
     */
    public function lastClickAttribution(
        int $userId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $startDate = $startDate ?? Carbon::now()->subDays(30)->toDateString();
        $endDate = $endDate ?? Carbon::now()->toDateString();

        $cacheKey = $this->getAttributionCacheKey($userId, 'last_click', $startDate, $endDate);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $startDate, $endDate) {
            $touches = $this->getUserTouchesInPeriod($userId, $startDate, $endDate)
                ->sortByDesc('timestamp');

            if ($touches->isEmpty()) {
                return $this->emptyAttributionResult($userId, self::MODEL_LAST_CLICK, $startDate, $endDate);
            }

            $lastTouch = $touches->first();
            $totalValue = $touches->sum('value');

            return [
                'user_id' => $userId,
                'model' => self::MODEL_LAST_CLICK,
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_value' => round($totalValue, 2),
                'touch_count' => $touches->count(),
                'attribution' => [
                    [
                        'channel' => $lastTouch->source ?? 'direct',
                        'medium' => $lastTouch->medium ?? null,
                        'campaign' => $lastTouch->campaign ?? null,
                        'percentage' => 100.0,
                        'value' => round($totalValue, 2),
                        'touch_position' => 'last',
                        'touch_timestamp' => $lastTouch->timestamp->toIso8601String(),
                    ],
                ],
            ];
        });
    }

    /**
     * Multi-touch attribution model with configurable algorithm
     *
     * @param int $userId User ID
     * @param string $algorithm Linear, time-decay, or position-based
     * @param string|null $startDate Start date
     * @param string|null $endDate End date
     * @return array
     */
    public function multiTouchAttribution(
        int $userId,
        string $algorithm = self::MODEL_LINEAR,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $this->ensureTenantContext();

        $startDate = $startDate ?? Carbon::now()->subDays(30)->toDateString();
        $endDate = $endDate ?? Carbon::now()->toDateString();

        $cacheKey = $this->getAttributionCacheKey($userId, "multi_{$algorithm}", $startDate, $endDate);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $algorithm, $startDate, $endDate) {
            $touches = $this->getUserTouchesInPeriod($userId, $startDate, $endDate)
                ->sortByDesc('timestamp');

            if ($touches->isEmpty()) {
                return $this->emptyAttributionResult($userId, $algorithm, $startDate, $endDate);
            }

            $totalValue = $touches->sum('value');
            $attribution = $this->applyMultiTouchModel($touches, $totalValue, $algorithm);

            return [
                'user_id' => $userId,
                'model' => $algorithm,
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_value' => round($totalValue, 2),
                'touch_count' => $touches->count(),
                'attribution' => $attribution,
            ];
        });
    }

    /**
     * Analyze channel performance across all users
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @param array $channels Optional list of channels to analyze
     * @return array Channel performance data
     */
    public function analyzeChannelPerformance(
        string $startDate,
        string $endDate,
        ?array $channels = null
    ): array {
        $this->ensureTenantContext();

        $cacheKey = $this->getChannelPerformanceCacheKey($startDate, $endDate, $channels);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate, $channels) {
            $query = AttributionTouch::byTenant($this->getCurrentTenantId())
                ->byPeriod($startDate, $endDate);

            if ($channels) {
                $query->bySources($channels);
            }

            $touches = $query->get();

            if ($touches->isEmpty()) {
                return $this->emptyChannelPerformanceResult($startDate, $endDate);
            }

            $channelData = $touches->groupBy('source')
                ->map(function (Collection $channelTouches) use ($touches) {
                    $totalValue = $channelTouches->sum('value');
                    $uniqueUsers = $channelTouches->pluck('user_id')->unique()->count();
                    $conversionTouches = $channelTouches->whereIn('event_type', ['purchase', 'subscription'])->count();
                    $totalTouches = $channelTouches->count();

                    return [
                        'channel' => $channelTouches->first()->source ?? 'unknown',
                        'display_name' => self::CHANNELS[$channelTouches->first()->source ?? 'direct']['display_name'] ?? 'Unknown',
                        'total_touches' => $totalTouches,
                        'unique_users' => $uniqueUsers,
                        'total_value' => round($totalValue, 2),
                        'avg_value_per_touch' => $totalTouches > 0 ? round($totalValue / $totalTouches, 2) : 0,
                        'avg_value_per_user' => $uniqueUsers > 0 ? round($totalValue / $uniqueUsers, 2) : 0,
                        'conversion_rate' => $totalTouches > 0 ? round(($conversionTouches / $totalTouches) * 100, 2) : 0,
                        'touches_percentage' => round(($totalTouches / $touches->count()) * 100, 2),
                        'value_percentage' => $touches->sum('value') > 0 
                            ? round(($totalValue / $touches->sum('value')) * 100, 2) 
                            : 0,
                    ];
                })
                ->sortByDesc('total_value')
                ->values()
                ->toArray();

            return [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_touches' => $touches->count(),
                'total_users' => $touches->pluck('user_id')->unique()->count(),
                'total_value' => round($touches->sum('value'), 2),
                'channels' => $channelData,
                'summary' => [
                    'top_channel' => $channelData[0]['channel'] ?? null,
                    'highest_conversion_rate' => collect($channelData)->max('conversion_rate'),
                    'most_touches' => $channelData[0]['channel'] ?? null,
                ],
            ];
        });
    }

    /**
     * Generate budget allocation recommendations based on channel performance
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @param float $totalBudget Total budget to allocate
     * @return array Budget recommendations
     */
    public function generateBudgetRecommendations(
        string $startDate,
        string $endDate,
        float $totalBudget = 0
    ): array {
        $this->ensureTenantContext();

        $cacheKey = $this->getBudgetRecommendationsCacheKey($startDate, $endDate, $totalBudget);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate, $totalBudget) {
            $performance = $this->analyzeChannelPerformance($startDate, $endDate);

            if (empty($performance['channels'])) {
                return [
                    'period' => ['start' => $startDate, 'end' => $endDate],
                    'total_budget' => $totalBudget,
                    'recommendations' => [],
                    'summary' => [
                        'total_channels' => 0,
                        'total_revenue' => 0,
                        'avg_roi' => 0,
                    ],
                ];
            }

            $channels = $performance['channels'];
            $totalRevenue = $performance['total_value'];

            // Calculate efficiency scores
            $efficiencyScores = [];
            $totalScore = 0;

            foreach ($channels as $channel) {
                $efficiencyScore = $this->calculateChannelEfficiencyScore($channel, $totalRevenue);
                $efficiencyScores[$channel['channel']] = $efficiencyScore;
                $totalScore += $efficiencyScore;
            }

            // Generate recommendations
            $recommendations = [];
            $remainingBudget = $totalBudget;

            foreach ($channels as $channel) {
                $score = $efficiencyScores[$channel['channel']];
                $allocationPercentage = $totalScore > 0 ? ($score / $totalScore) : (1 / count($channels));
                $recommendedBudget = $totalBudget > 0 ? ($allocationPercentage * $totalBudget) : 0;

                $recommendations[] = [
                    'channel' => $channel['channel'],
                    'display_name' => $channel['display_name'],
                    'current_performance' => [
                        'total_value' => $channel['total_value'],
                        'conversion_rate' => $channel['conversion_rate'],
                        'touches_percentage' => $channel['touches_percentage'],
                        'value_percentage' => $channel['value_percentage'],
                    ],
                    'efficiency_score' => round($score, 2),
                    'recommended_budget' => round($recommendedBudget, 2),
                    'recommended_percentage' => $totalBudget > 0 ? round($allocationPercentage * 100, 2) : 0,
                    'recommendation' => $this->generateChannelRecommendation($channel, $score),
                ];
            }

            // Sort by efficiency score descending
            usort($recommendations, fn($a, $b) => $b['efficiency_score'] <=> $a['efficiency_score']);

            return [
                'period' => ['start' => $startDate, 'end' => $endDate],
                'total_budget' => $totalBudget,
                'recommendations' => $recommendations,
                'summary' => [
                    'total_channels' => count($channels),
                    'total_revenue' => round($totalRevenue, 2),
                    'avg_efficiency_score' => round($totalScore / count($channels), 2),
                ],
            ];
        });
    }

    /**
     * Get full conversion path for a user
     *
     * @param int $userId User ID
     * @param string|null $startDate Start date
     * @param string|null $endDate End date
     * @return array Conversion path with all touchpoints
     */
    public function getConversionPath(
        int $userId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $this->ensureTenantContext();

        $startDate = $startDate ?? Carbon::now()->subDays(30)->toDateString();
        $endDate = $endDate ?? Carbon::now()->toDateString();

        $cacheKey = $this->getConversionPathCacheKey($userId, $startDate, $endDate);

        return Cache::remember($cacheKey, self::CACHE_TTL_SHORT, function () use ($userId, $startDate, $endDate) {
            $touches = $this->getUserTouchesInPeriod($userId, $startDate, $endDate)
                ->sortBy('timestamp')
                ->values();

            if ($touches->isEmpty()) {
                return [
                    'user_id' => $userId,
                    'period' => ['start' => $startDate, 'end' => $endDate],
                    'touchpoints' => [],
                    'path_length' => 0,
                    'channels' => [],
                    'total_value' => 0,
                ];
            }

            $channels = $touches->pluck('source')->unique()->values()->toArray();
            $totalValue = $touches->sum('value');

            $touchpoints = $touches->map(function ($touch, $index) {
                return [
                    'position' => $index + 1,
                    'timestamp' => $touch->timestamp->toIso8601String(),
                    'channel' => $touch->source ?? 'direct',
                    'medium' => $touch->medium ?? null,
                    'campaign' => $touch->campaign ?? null,
                    'event_type' => $touch->event_type,
                    'value' => (float) $touch->value,
                    'session_id' => $touch->session_id,
                ];
            })->toArray();

            return [
                'user_id' => $userId,
                'period' => ['start' => $startDate, 'end' => $endDate],
                'touchpoints' => $touchpoints,
                'path_length' => count($touchpoints),
                'channels' => $channels,
                'unique_channels' => count($channels),
                'total_value' => round($totalValue, 2),
                'first_touch_timestamp' => $touches->first()->timestamp->toIso8601String(),
                'last_touch_timestamp' => $touches->last()->timestamp->toIso8601String(),
                'journey_duration_days' => (int) $touches->last()->timestamp->diffInDays($touches->first()->timestamp),
            ];
        });
    }

    /**
     * Get user touches within a date period
     */
    private function getUserTouchesInPeriod(int $userId, string $startDate, string $endDate): Collection
    {
        return AttributionTouch::byTenant($this->getCurrentTenantId())
            ->byUser($userId)
            ->byPeriod($startDate, $endDate)
            ->orderBy('timestamp', 'asc')
            ->get();
    }

    /**
     * Apply multi-touch attribution model
     */
    private function applyMultiTouchModel(Collection $touches, float $totalValue, string $algorithm): array
    {
        return match ($algorithm) {
            self::MODEL_LINEAR => $this->applyLinearModel($touches, $totalValue),
            self::MODEL_TIME_DECAY => $this->applyTimeDecayModel($touches, $totalValue),
            self::MODEL_POSITION_BASED => $this->applyPositionBasedModel($touches, $totalValue),
            default => $this->applyLinearModel($touches, $totalValue),
        };
    }

    /**
     * Apply linear attribution model - equal distribution across all touchpoints
     */
    private function applyLinearModel(Collection $touches, float $totalValue): array
    {
        $channelGroups = $touches->groupBy(fn($t) => $t->source ?? 'direct');
        $channelCount = $channelGroups->count();
        $equalShare = $channelCount > 0 ? $totalValue / $channelCount : 0;

        return $channelGroups->map(function (Collection $groupTouches, $channel) use ($equalShare, $channelCount, $touches) {
            return [
                'channel' => $channel,
                'medium' => $groupTouches->first()->medium ?? null,
                'campaign' => $groupTouches->first()->campaign ?? null,
                'percentage' => round(100 / $channelCount, 2),
                'value' => round($equalShare, 2),
                'touch_count' => $groupTouches->count(),
            ];
        })->values()->toArray();
    }

    /**
     * Apply time-decay model - more recent touches get more credit
     */
    private function applyTimeDecayModel(Collection $touches, float $totalValue): array
    {
        $decayHalfLife = 7; // 7-day half-life
        $latestTimestamp = $touches->max('timestamp');

        $weights = [];
        $totalWeight = 0;

        foreach ($touches as $touch) {
            $daysAgo = $latestTimestamp->diffInDays($touch->timestamp);
            $weight = pow(2, -$daysAgo / $decayHalfLife);
            $channel = $touch->source ?? 'direct';

            $weights[$channel] = ($weights[$channel] ?? 0) + $weight;
            $totalWeight += $weight;
        }

        return collect($weights)->map(function ($weight, $channel) use ($totalWeight, $totalValue, $touches) {
            $percentage = $totalWeight > 0 ? ($weight / $totalWeight) * 100 : 0;
            $channelTouches = $touches->where('source', $channel);

            return [
                'channel' => $channel,
                'medium' => $channelTouches->first()->medium ?? null,
                'campaign' => $channelTouches->first()->campaign ?? null,
                'percentage' => round($percentage, 2),
                'value' => round(($percentage / 100) * $totalValue, 2),
                'touch_count' => $channelTouches->count(),
            ];
        })->values()->toArray();
    }

    /**
     * Apply position-based model (40% first, 40% last, 20% middle)
     */
    private function applyPositionBasedModel(Collection $touches, float $totalValue): array
    {
        $channelGroups = $touches->groupBy(fn($t) => $t->source ?? 'direct');
        $touchCount = $touches->count();

        if ($touchCount === 0) {
            return [];
        }

        $firstTouch = $touches->first();
        $lastTouch = $touches->last();
        $middleTouches = $touches->slice(1, -1);

        $results = [];

        foreach ($channelGroups as $channel => $channelTouches) {
            $attributionValue = 0;
            $channelTouchCount = $channelTouches->count();

            // 40% to first touch
            if ($channelTouches->contains('id', $firstTouch->id)) {
                $attributionValue += $totalValue * 0.4;
            }

            // 40% to last touch
            if ($channelTouches->contains('id', $lastTouch->id)) {
                $attributionValue += $totalValue * 0.4;
            }

            // 20% to middle touches (distributed)
            if ($middleTouches->count() > 0) {
                $middlePerTouch = ($totalValue * 0.2) / $middleTouches->count();
                $middleTouchesForChannel = $channelTouches->filter(function ($t) use ($firstTouch, $lastTouch) {
                    return $t->id !== $firstTouch->id && $t->id !== $lastTouch->id;
                });
                $attributionValue += $middlePerTouch * $middleTouchesForChannel->count();
            }

            $percentage = $totalValue > 0 ? ($attributionValue / $totalValue) * 100 : 0;

            $results[] = [
                'channel' => $channel,
                'medium' => $channelTouches->first()->medium ?? null,
                'campaign' => $channelTouches->first()->campaign ?? null,
                'percentage' => round($percentage, 2),
                'value' => round($attributionValue, 2),
                'touch_count' => $channelTouchCount,
            ];
        }

        return $results;
    }

    /**
     * Calculate channel efficiency score for budget allocation
     */
    private function calculateChannelEfficiencyScore(array $channel, float $totalRevenue): float
    {
        $weights = [
            'value_percentage' => 0.35,
            'conversion_rate' => 0.30,
            'value_per_touch' => 0.20,
            'efficiency' => 0.15,
        ];

        $valuePercentage = min(100, ($channel['value_percentage'] * 2)); // Scale to 0-100
        $conversionScore = min(100, $channel['conversion_rate'] * 10); // Scale to 0-100
        $valuePerTouchScore = min(100, ($channel['avg_value_per_touch'] / max($channel['avg_value_per_touch'], 1)) * 20);
        $efficiencyScore = min(100, $channel['value_percentage'] * 2);

        return round(
            ($valuePercentage * $weights['value_percentage']) +
            ($conversionScore * $weights['conversion_rate']) +
            ($valuePerTouchScore * $weights['value_per_touch']) +
            ($efficiencyScore * $weights['efficiency']),
            2
        );
    }

    /**
     * Generate text recommendation for a channel
     */
    private function generateChannelRecommendation(array $channel, float $efficiencyScore): string
    {
        $valuePercentage = $channel['value_percentage'];
        $conversionRate = $channel['conversion_rate'];

        if ($efficiencyScore >= 70) {
            return 'High-performing channel - consider increasing investment';
        } elseif ($efficiencyScore >= 50 && $conversionRate > 5) {
            return 'Solid performer - maintain current allocation';
        } elseif ($efficiencyScore >= 30) {
            return 'Moderate performance - may benefit from optimization';
        } elseif ($conversionRate > $valuePercentage) {
            return 'Good conversion but low overall value - analyze for quality improvements';
        } else {
            return 'Underperforming channel - recommend reducing spend or pausing';
        }
    }

    /**
     * Validate touchpoint data
     */
    private function validateTouchpointData(array $data): array
    {
        $validated = [];

        if (isset($data['session_id']) && is_string($data['session_id'])) {
            $validated['session_id'] = $data['session_id'];
        }

        if (isset($data['event_type']) && in_array($data['event_type'], self::EVENT_TYPES)) {
            $validated['event_type'] = $data['event_type'];
        }

        if (isset($data['medium']) && is_string($data['medium'])) {
            $validated['medium'] = $data['medium'];
        }

        if (isset($data['campaign']) && is_string($data['campaign'])) {
            $validated['campaign'] = $data['campaign'];
        }

        if (isset($data['value']) && is_numeric($data['value']) && $data['value'] >= 0) {
            $validated['value'] = (float) $data['value'];
        }

        if (isset($data['timestamp']) && $data['timestamp'] instanceof Carbon) {
            $validated['timestamp'] = $data['timestamp'];
        }

        return $validated;
    }

    /**
     * Generate session ID for touchpoints
     */
    private function generateSessionId(): string
    {
        return sprintf(
            '%s-%s',
            substr(md5(uniqid()), 0, 8),
            substr(md5(random_bytes(8)), 0, 8)
        );
    }

    /**
     * Clear user attribution cache
     */
    private function clearUserAttributionCache(int $userId): void
    {
        $pattern = "tenant:{$this->getCurrentTenantId()}:attribution:*";
        Cache::flush($pattern);
    }

    /**
     * Get empty attribution result structure
     */
    private function emptyAttributionResult(int $userId, string $model, string $startDate, string $endDate): array
    {
        return [
            'user_id' => $userId,
            'model' => $model,
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_value' => 0,
            'touch_count' => 0,
            'attribution' => [],
        ];
    }

    /**
     * Get empty channel performance result
     */
    private function emptyChannelPerformanceResult(string $startDate, string $endDate): array
    {
        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_touches' => 0,
            'total_users' => 0,
            'total_value' => 0,
            'channels' => [],
            'summary' => [
                'top_channel' => null,
                'highest_conversion_rate' => 0,
                'most_touches' => null,
            ],
        ];
    }

    /**
     * Cache key helpers
     */
    private function getAttributionCacheKey(int $userId, string $model, string $startDate, string $endDate): string
    {
        return $this->getTenantCacheKey("attribution:{$userId}:{$model}:{$startDate}:{$endDate}");
    }

    private function getChannelPerformanceCacheKey(string $startDate, string $endDate, ?array $channels): string
    {
        $channelsHash = $channels ? md5(implode(',', $channels)) : 'all';
        return $this->getTenantCacheKey("channel_performance:{$startDate}:{$endDate}:{$channelsHash}");
    }

    private function getBudgetRecommendationsCacheKey(string $startDate, string $endDate, float $totalBudget): string
    {
        return $this->getTenantCacheKey("budget_recommendations:{$startDate}:{$endDate}:{$totalBudget}");
    }

    private function getConversionPathCacheKey(int $userId, string $startDate, string $endDate): string
    {
        return $this->getTenantCacheKey("conversion_path:{$userId}:{$startDate}:{$endDate}");
    }
}
