<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\AttributionTouch;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Attribution Service
 *
 * Provides comprehensive attribution modeling functionality for marketing analytics.
 * Supports multiple attribution models: last-touch, first-touch, linear, time-decay, and position-based.
 */
class AttributionService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const CHUNK_SIZE = 1000;

    /**
     * Track a user touchpoint for attribution analysis
     *
     * @param  array  $touchData  Touch data including user_id, source, event_type, etc.
     * @return AttributionTouch The created touch record
     */
    public function trackTouch(array $touchData): AttributionTouch
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $consentService = app(ConsentService::class);

            // Check consent before tracking
            if (! $consentService->checkConsent($touchData['user_id'], 'analytics')) {
                Log::info('Skipping attribution tracking due to lack of consent', [
                    'user_id' => $touchData['user_id'],
                    'tenant_id' => $tenantId,
                ]);
                throw new Exception('User has not consented to analytics tracking');
            }

            // Validate touch data
            $this->validateTouchData($touchData);

            // Prepare touch data
            $touchData['tenant_id'] = $tenantId;
            $touchData['timestamp'] = $touchData['timestamp'] ?? now();

            // Create touch record
            $touch = AttributionTouch::create($touchData);

            // Clear relevant caches
            $this->clearAttributionCache($touchData['user_id']);

            Log::info('Attribution touch tracked', [
                'touch_id' => $touch->id,
                'user_id' => $touchData['user_id'],
                'source' => $touchData['source'] ?? null,
                'tenant_id' => $tenantId,
            ]);

            return $touch;

        } catch (Exception $e) {
            Log::error('Failed to track attribution touch', [
                'touch_data' => $touchData,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate attribution for a user within a time period
     *
     * @param  int  $userId  User ID to calculate attribution for
     * @param  string  $startDate  Start date for attribution window
     * @param  string  $endDate  End date for attribution window
     * @param  string  $model  Attribution model: 'last_touch', 'first_touch', 'linear', 'time_decay', 'position_based'
     * @return array Attribution results with sources and their attributed values
     */
    public function calculateAttribution(int $userId, string $startDate, string $endDate, string $model = 'last_touch'): array
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $cacheKey = "attribution_{$userId}_{$startDate}_{$endDate}_{$model}";

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $startDate, $endDate, $model, $tenantId) {
                // Get all touches for the user in the period
                $touches = AttributionTouch::byTenant($tenantId)
                    ->byUser($userId)
                    ->byPeriod($startDate, $endDate)
                    ->withValue()
                    ->latest('timestamp')
                    ->get();

                if ($touches->isEmpty()) {
                    return [
                        'user_id' => $userId,
                        'period' => ['start' => $startDate, 'end' => $endDate],
                        'model' => $model,
                        'total_value' => 0,
                        'sources' => [],
                        'touch_count' => 0,
                    ];
                }

                // Calculate total value from touches
                $totalValue = $touches->sum('value');

                // Apply attribution model
                $attributedSources = $this->applyAttributionModel($touches, $totalValue, $model);

                return [
                    'user_id' => $userId,
                    'period' => ['start' => $startDate, 'end' => $endDate],
                    'model' => $model,
                    'total_value' => round($totalValue, 2),
                    'sources' => $attributedSources,
                    'touch_count' => $touches->count(),
                ];
            });

        } catch (Exception $e) {
            Log::error('Failed to calculate attribution', [
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'model' => $model,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get attribution summary for multiple users
     *
     * @param  array  $userIds  Array of user IDs
     * @param  string  $startDate  Start date
     * @param  string  $endDate  End date
     * @param  string  $model  Attribution model
     * @return array Summary of attribution across users
     */
    public function getAttributionSummary(array $userIds, string $startDate, string $endDate, string $model = 'last_touch'): array
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $cacheKey = 'attribution_summary_'.md5(serialize($userIds))."_{$startDate}_{$endDate}_{$model}";

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userIds, $startDate, $endDate, $model) {
                $summary = [
                    'period' => ['start' => $startDate, 'end' => $endDate],
                    'model' => $model,
                    'total_users' => count($userIds),
                    'total_value' => 0,
                    'source_breakdown' => [],
                    'user_attributions' => [],
                ];

                // Process users in chunks for performance
                $userChunks = array_chunk($userIds, self::CHUNK_SIZE);

                foreach ($userChunks as $chunk) {
                    foreach ($chunk as $userId) {
                        $userAttribution = $this->calculateAttribution($userId, $startDate, $endDate, $model);

                        if (! empty($userAttribution)) {
                            $summary['total_value'] += $userAttribution['total_value'];
                            $summary['user_attributions'][] = $userAttribution;

                            // Aggregate source breakdown
                            foreach ($userAttribution['sources'] as $source) {
                                $sourceName = $source['name'];
                                if (! isset($summary['source_breakdown'][$sourceName])) {
                                    $summary['source_breakdown'][$sourceName] = [
                                        'name' => $sourceName,
                                        'total_value' => 0,
                                        'total_percentage' => 0,
                                        'user_count' => 0,
                                    ];
                                }
                                $summary['source_breakdown'][$sourceName]['total_value'] += $source['value'];
                                $summary['source_breakdown'][$sourceName]['total_percentage'] += $source['percentage'];
                                $summary['source_breakdown'][$sourceName]['user_count']++;
                            }
                        }
                    }
                }

                // Calculate averages for source breakdown
                foreach ($summary['source_breakdown'] as &$source) {
                    $source['avg_percentage'] = round($source['total_percentage'] / $source['user_count'], 2);
                    $source['total_value'] = round($source['total_value'], 2);
                }

                $summary['total_value'] = round($summary['total_value'], 2);

                return $summary;
            });

        } catch (Exception $e) {
            Log::error('Failed to get attribution summary', [
                'user_count' => count($userIds),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'model' => $model,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get touch history for a user
     *
     * @param  int  $userId  User ID
     * @param  int  $limit  Maximum number of touches to return
     * @return Collection Collection of attribution touches
     */
    public function getTouchHistory(int $userId, int $limit = 50): Collection
    {
        $tenantId = $this->getCurrentTenantId();

        return AttributionTouch::byTenant($tenantId)
            ->byUser($userId)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get channel contribution analysis
     *
     * @param  string  $channel  Channel name to analyze
     * @param  string  $startDate  Start date
     * @param  string  $endDate  End date
     * @return array Channel contribution data
     */
    public function getChannelContribution(string $channel, string $startDate, string $endDate): array
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $cacheKey = "channel_contribution_{$tenantId}_{$channel}_{$startDate}_{$endDate}";

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($channel, $startDate, $endDate, $tenantId) {
                $touches = AttributionTouch::byTenant($tenantId)
                    ->bySource($channel)
                    ->byPeriod($startDate, $endDate)
                    ->withValue()
                    ->get();

                if ($touches->isEmpty()) {
                    return [
                        'channel' => $channel,
                        'period' => ['start' => $startDate, 'end' => $endDate],
                        'total_touches' => 0,
                        'total_value' => 0,
                        'unique_users' => 0,
                        'conversion_rate' => 0,
                        'avg_value_per_touch' => 0,
                    ];
                }

                $totalValue = $touches->sum('value');
                $uniqueUsers = $touches->pluck('user_id')->unique()->count();
                $conversionTouches = $touches->where('event_type', 'purchase')->count();
                $conversionRate = $touches->count() > 0 ? ($conversionTouches / $touches->count()) * 100 : 0;

                return [
                    'channel' => $channel,
                    'period' => ['start' => $startDate, 'end' => $endDate],
                    'total_touches' => $touches->count(),
                    'total_value' => round($totalValue, 2),
                    'unique_users' => $uniqueUsers,
                    'conversion_rate' => round($conversionRate, 2),
                    'avg_value_per_touch' => $touches->count() > 0 ? round($totalValue / $touches->count(), 2) : 0,
                ];
            });

        } catch (Exception $e) {
            Log::error('Failed to get channel contribution', [
                'channel' => $channel,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Calculate channel ROI
     *
     * @param  string  $channel  Channel name
     * @param  string  $startDate  Start date
     * @param  string  $endDate  End date
     * @param  float  $channelSpend  Amount spent on the channel
     * @return array ROI calculation results
     */
    public function calculateChannelROI(string $channel, string $startDate, string $endDate, float $channelSpend = 0): array
    {
        try {
            $contribution = $this->getChannelContribution($channel, $startDate, $endDate);

            if (empty($contribution)) {
                return [
                    'channel' => $channel,
                    'period' => ['start' => $startDate, 'end' => $endDate],
                    'revenue' => 0,
                    'spend' => $channelSpend,
                    'roi' => 0,
                    'roas' => 0,
                    'profit' => -$channelSpend,
                ];
            }

            $revenue = $contribution['total_value'];
            $profit = $revenue - $channelSpend;
            $roi = $channelSpend > 0 ? (($profit / $channelSpend) * 100) : 0;
            $roas = $channelSpend > 0 ? ($revenue / $channelSpend) : 0;

            return [
                'channel' => $channel,
                'period' => ['start' => $startDate, 'end' => $endDate],
                'revenue' => $revenue,
                'spend' => $channelSpend,
                'roi' => round($roi, 2),
                'roas' => round($roas, 2),
                'profit' => round($profit, 2),
            ];

        } catch (Exception $e) {
            Log::error('Failed to calculate channel ROI', [
                'channel' => $channel,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Generate budget allocation recommendations
     *
     * @param  string  $startDate  Start date for analysis
     * @param  string  $endDate  End date for analysis
     * @param  float  $totalBudget  Total budget to allocate
     * @return array Budget recommendations
     */
    public function generateBudgetRecommendations(string $startDate, string $endDate, float $totalBudget = 0): array
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $cacheKey = "budget_recommendations_{$tenantId}_{$startDate}_{$endDate}_{$totalBudget}";

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($startDate, $endDate, $totalBudget, $tenantId) {
                // Get all unique channels
                $channels = AttributionTouch::byTenant($tenantId)
                    ->byPeriod($startDate, $endDate)
                    ->select('source')
                    ->distinct()
                    ->pluck('source')
                    ->filter()
                    ->values();

                if ($channels->isEmpty()) {
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

                // Calculate ROI for each channel
                $channelData = [];
                $totalRevenue = 0;
                $totalSpend = 0;

                foreach ($channels as $channel) {
                    $contribution = $this->getChannelContribution($channel, $startDate, $endDate);
                    $roi = $this->calculateChannelROI($channel, $startDate, $endDate, 0);

                    if (! empty($contribution) && ! empty($roi)) {
                        $channelData[$channel] = [
                            'channel' => $channel,
                            'revenue' => $contribution['total_value'],
                            'touches' => $contribution['total_touches'],
                            'unique_users' => $contribution['unique_users'],
                            'conversion_rate' => $contribution['conversion_rate'],
                            'roi' => $roi['roi'],
                            'roas' => $roi['roas'],
                            'efficiency_score' => $this->calculateEfficiencyScore($contribution, $roi),
                        ];

                        $totalRevenue += $contribution['total_value'];
                    }
                }

                // Sort channels by efficiency score
                uasort($channelData, function ($a, $b) {
                    return $b['efficiency_score'] <=> $a['efficiency_score'];
                });

                // Generate budget allocation recommendations
                $recommendations = [];
                $remainingBudget = $totalBudget;

                foreach ($channelData as $channel => $data) {
                    $allocation = 0;
                    $recommendation = '';

                    if ($totalBudget > 0) {
                        // Allocate based on efficiency score
                        $totalEfficiency = array_sum(array_column($channelData, 'efficiency_score'));
                        $allocation = ($data['efficiency_score'] / $totalEfficiency) * $totalBudget;

                        // Generate recommendation text
                        if ($data['roi'] > 100) {
                            $recommendation = 'High performing channel - consider increasing budget';
                        } elseif ($data['roi'] > 0) {
                            $recommendation = 'Positive ROI - maintain current allocation';
                        } elseif ($data['roi'] > -50) {
                            $recommendation = 'Low ROI - consider optimizing or reducing spend';
                        } else {
                            $recommendation = 'Negative ROI - consider pausing or significant optimization';
                        }
                    }

                    $recommendations[] = [
                        'channel' => $channel,
                        'current_performance' => [
                            'revenue' => $data['revenue'],
                            'roi' => $data['roi'],
                            'roas' => $data['roas'],
                            'conversion_rate' => $data['conversion_rate'],
                        ],
                        'recommended_budget' => round($allocation, 2),
                        'recommended_percentage' => $totalBudget > 0 ? round(($allocation / $totalBudget) * 100, 2) : 0,
                        'recommendation' => $recommendation,
                        'efficiency_score' => round($data['efficiency_score'], 2),
                    ];
                }

                $avgRoi = count($channelData) > 0 ? array_sum(array_column($channelData, 'roi')) / count($channelData) : 0;

                return [
                    'period' => ['start' => $startDate, 'end' => $endDate],
                    'total_budget' => $totalBudget,
                    'recommendations' => $recommendations,
                    'summary' => [
                        'total_channels' => count($channelData),
                        'total_revenue' => round($totalRevenue, 2),
                        'avg_roi' => round($avgRoi, 2),
                    ],
                ];
            });

        } catch (Exception $e) {
            Log::error('Failed to generate budget recommendations', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_budget' => $totalBudget,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Calculate efficiency score for a channel
     *
     * @param  array  $contribution  Channel contribution data
     * @param  array  $roi  Channel ROI data
     * @return float Efficiency score (0-100)
     */
    private function calculateEfficiencyScore(array $contribution, array $roi): float
    {
        // Weight factors
        $roiWeight = 0.4;
        $conversionRateWeight = 0.3;
        $uniqueUsersWeight = 0.2;
        $touchesWeight = 0.1;

        // Normalize ROI to 0-100 scale (assuming -100% to 300% range)
        $normalizedRoi = max(0, min(100, ($roi['roi'] + 100) / 4));

        // Normalize conversion rate to 0-100 scale
        $normalizedConversion = min(100, $contribution['conversion_rate'] * 10);

        // Normalize unique users (logarithmic scale)
        $normalizedUsers = min(100, log10($contribution['unique_users'] + 1) * 20);

        // Normalize touches (logarithmic scale)
        $normalizedTouches = min(100, log10($contribution['total_touches'] + 1) * 20);

        // Calculate weighted score
        $score = (
            $normalizedRoi * $roiWeight +
            $normalizedConversion * $conversionRateWeight +
            $normalizedUsers * $uniqueUsersWeight +
            $normalizedTouches * $touchesWeight
        );

        return round($score, 2);
    }

    /**
     * Apply attribution model to touches
     *
     * @param  Collection  $touches  Collection of touches
     * @param  float  $totalValue  Total value to attribute
     * @param  string  $model  Attribution model
     * @return array Attributed sources with percentages and values
     */
    private function applyAttributionModel(Collection $touches, float $totalValue, string $model): array
    {
        return match ($model) {
            'first_touch' => $this->applyFirstTouchModel($touches, $totalValue),
            'linear' => $this->applyLinearModel($touches, $totalValue),
            'time_decay' => $this->applyTimeDecayModel($touches, $totalValue),
            'position_based' => $this->applyPositionBasedModel($touches, $totalValue),
            default => $this->applyLastTouchModel($touches, $totalValue),
        };
    }

    /**
     * Apply last-touch attribution model (100% to last source)
     */
    private function applyLastTouchModel(Collection $touches, float $totalValue): array
    {
        $lastTouch = $touches->first();
        $sourceName = $lastTouch->source ?? 'direct';

        return [
            [
                'name' => $sourceName,
                'percentage' => 100.0,
                'value' => round($totalValue, 2),
                'touch_count' => 1,
                'first_touch' => false,
                'last_touch' => true,
            ],
        ];
    }

    /**
     * Apply first-touch attribution model (100% to first source)
     */
    private function applyFirstTouchModel(Collection $touches, float $totalValue): array
    {
        $firstTouch = $touches->last(); // Since we ordered by latest first
        $sourceName = $firstTouch->source ?? 'direct';

        return [
            [
                'name' => $sourceName,
                'percentage' => 100.0,
                'value' => round($totalValue, 2),
                'touch_count' => 1,
                'first_touch' => true,
                'last_touch' => false,
            ],
        ];
    }

    /**
     * Apply linear attribution model (equal distribution)
     */
    private function applyLinearModel(Collection $touches, float $totalValue): array
    {
        $sourceGroups = $touches->groupBy(function ($touch) {
            return $touch->source ?? 'direct';
        });

        $attributedSources = [];
        $equalShare = $totalValue / $sourceGroups->count();

        foreach ($sourceGroups as $sourceName => $sourceTouches) {
            $attributedSources[] = [
                'name' => $sourceName,
                'percentage' => round(100 / $sourceGroups->count(), 2),
                'value' => round($equalShare, 2),
                'touch_count' => $sourceTouches->count(),
                'first_touch' => $sourceTouches->contains($touches->last()),
                'last_touch' => $sourceTouches->contains($touches->first()),
            ];
        }

        return $attributedSources;
    }

    /**
     * Apply time-decay attribution model (exponential decay from most recent)
     */
    private function applyTimeDecayModel(Collection $touches, float $totalValue): array
    {
        $sourceGroups = $touches->groupBy(function ($touch) {
            return $touch->source ?? 'direct';
        });

        $attributedSources = [];
        $totalWeight = 0;
        $weights = [];

        // Calculate weights using exponential decay
        foreach ($touches as $index => $touch) {
            $weight = exp(-$index * 0.5); // Decay factor
            $sourceName = $touch->source ?? 'direct';
            $weights[$sourceName] = ($weights[$sourceName] ?? 0) + $weight;
            $totalWeight += $weight;
        }

        foreach ($weights as $sourceName => $weight) {
            $percentage = ($weight / $totalWeight) * 100;
            $sourceTouches = $sourceGroups[$sourceName];

            $attributedSources[] = [
                'name' => $sourceName,
                'percentage' => round($percentage, 2),
                'value' => round(($percentage / 100) * $totalValue, 2),
                'touch_count' => $sourceTouches->count(),
                'first_touch' => $sourceTouches->contains($touches->last()),
                'last_touch' => $sourceTouches->contains($touches->first()),
            ];
        }

        return $attributedSources;
    }

    /**
     * Apply position-based attribution model (40% first, 40% last, 20% middle)
     */
    private function applyPositionBasedModel(Collection $touches, float $totalValue): array
    {
        $sourceGroups = $touches->groupBy(function ($touch) {
            return $touch->source ?? 'direct';
        });

        $attributedSources = [];
        $touchCount = $touches->count();

        if ($touchCount === 0) {
            return [];
        }

        // Calculate position weights
        $firstTouch = $touches->last();
        $lastTouch = $touches->first();
        $middleTouches = $touches->slice(1, -1);

        $firstWeight = 0.4; // 40% to first touch
        $lastWeight = 0.4;  // 40% to last touch
        $middleWeight = $touchCount > 2 ? 0.2 : 0; // 20% to middle touches (if any)

        // Distribute middle weight among middle touches
        $middleTouchCount = $middleTouches->count();
        $weightPerMiddleTouch = $middleTouchCount > 0 ? $middleWeight / $middleTouchCount : 0;

        // Calculate attribution for each source
        foreach ($sourceGroups as $sourceName => $sourceTouches) {
            $attributionValue = 0;
            $touchCountForSource = $sourceTouches->count();

            // Check if this source has the first touch
            if ($sourceTouches->contains($firstTouch)) {
                $attributionValue += $totalValue * $firstWeight;
            }

            // Check if this source has the last touch
            if ($sourceTouches->contains($lastTouch)) {
                $attributionValue += $totalValue * $lastWeight;
            }

            // Check if this source has middle touches
            $middleTouchesForSource = $sourceTouches->filter(function ($touch) use ($firstTouch, $lastTouch) {
                return $touch->id !== $firstTouch->id && $touch->id !== $lastTouch->id;
            });

            if ($middleTouchesForSource->count() > 0) {
                $attributionValue += $totalValue * $weightPerMiddleTouch * $middleTouchesForSource->count();
            }

            $percentage = ($attributionValue / $totalValue) * 100;

            $attributedSources[] = [
                'name' => $sourceName,
                'percentage' => round($percentage, 2),
                'value' => round($attributionValue, 2),
                'touch_count' => $touchCountForSource,
                'first_touch' => $sourceTouches->contains($firstTouch),
                'last_touch' => $sourceTouches->contains($lastTouch),
            ];
        }

        return $attributedSources;
    }

    /**
     * Clear attribution cache for a user
     */
    private function clearAttributionCache(int $userId): void
    {
        // Clear all attribution-related cache keys for this user
        Cache::forget("attribution_user_touches_{$userId}");
        // Note: Other cache keys with dates will naturally expire
    }

    /**
     * Get current tenant ID
     */
    private function getCurrentTenantId(): string
    {
        return session('tenant_id', 'default');
    }

    /**
     * Validate touch data
     */
    private function validateTouchData(array $touchData): void
    {
        if (! isset($touchData['user_id'])) {
            throw new Exception('User ID is required for attribution touch');
        }

        if (! isset($touchData['event_type'])) {
            throw new Exception('Event type is required for attribution touch');
        }

        if (isset($touchData['value']) && $touchData['value'] < 0) {
            throw new Exception('Touch value cannot be negative');
        }

        $validEventTypes = ['page_view', 'click', 'form_submit', 'purchase', 'signup', 'login'];
        if (! in_array($touchData['event_type'], $validEventTypes)) {
            throw new Exception('Invalid event type for attribution touch');
        }
    }
}
