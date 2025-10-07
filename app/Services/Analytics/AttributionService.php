<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\AttributionTouch;
use App\Models\User;
use App\Services\Analytics\ConsentService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

/**
 * Attribution Service
 *
 * Provides comprehensive attribution modeling functionality for marketing analytics.
 * Supports multiple attribution models: last-touch, first-touch, linear, and time-decay.
 */
class AttributionService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CHUNK_SIZE = 1000;

    /**
     * Track a user touchpoint for attribution analysis
     *
     * @param array $touchData Touch data including user_id, source, event_type, etc.
     * @return AttributionTouch The created touch record
     */
    public function trackTouch(array $touchData): AttributionTouch
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $consentService = app(ConsentService::class);

            // Check consent before tracking
            if (!$consentService->checkConsent($touchData['user_id'], 'analytics')) {
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
     * @param int $userId User ID to calculate attribution for
     * @param string $startDate Start date for attribution window
     * @param string $endDate End date for attribution window
     * @param string $model Attribution model: 'last_touch', 'first_touch', 'linear', 'time_decay'
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
     * @param array $userIds Array of user IDs
     * @param string $startDate Start date
     * @param string $endDate End date
     * @param string $model Attribution model
     * @return array Summary of attribution across users
     */
    public function getAttributionSummary(array $userIds, string $startDate, string $endDate, string $model = 'last_touch'): array
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $cacheKey = "attribution_summary_" . md5(serialize($userIds)) . "_{$startDate}_{$endDate}_{$model}";

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userIds, $startDate, $endDate, $model, $tenantId) {
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

                        if (!empty($userAttribution)) {
                            $summary['total_value'] += $userAttribution['total_value'];
                            $summary['user_attributions'][] = $userAttribution;

                            // Aggregate source breakdown
                            foreach ($userAttribution['sources'] as $source) {
                                $sourceName = $source['name'];
                                if (!isset($summary['source_breakdown'][$sourceName])) {
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
     * @param int $userId User ID
     * @param int $limit Maximum number of touches to return
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
     * Apply attribution model to touches
     *
     * @param Collection $touches Collection of touches
     * @param float $totalValue Total value to attribute
     * @param string $model Attribution model
     * @return array Attributed sources with percentages and values
     */
    private function applyAttributionModel(Collection $touches, float $totalValue, string $model): array
    {
        return match ($model) {
            'first_touch' => $this->applyFirstTouchModel($touches, $totalValue),
            'linear' => $this->applyLinearModel($touches, $totalValue),
            'time_decay' => $this->applyTimeDecayModel($touches, $totalValue),
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
            ]
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
            ]
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
        if (!isset($touchData['user_id'])) {
            throw new Exception('User ID is required for attribution touch');
        }

        if (!isset($touchData['event_type'])) {
            throw new Exception('Event type is required for attribution touch');
        }

        if (isset($touchData['value']) && $touchData['value'] < 0) {
            throw new Exception('Touch value cannot be negative');
        }

        $validEventTypes = ['page_view', 'click', 'form_submit', 'purchase', 'signup', 'login'];
        if (!in_array($touchData['event_type'], $validEventTypes)) {
            throw new Exception('Invalid event type for attribution touch');
        }
    }
}