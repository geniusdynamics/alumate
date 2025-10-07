<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\User;
use App\Models\AnalyticsEvent;
use App\Models\LearningProgress;
use App\Services\Analytics\ConsentService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

/**
 * Cohort Analysis Service
 *
 * Provides comprehensive cohort analysis functionality for user behavior analysis
 * over time, including retention, engagement, and conversion rate calculations.
 */
class CohortAnalysisService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CHUNK_SIZE = 1000;
    private const CONFIDENCE_LEVEL_95 = 1.96;

    /**
     * Create a cohort based on specified criteria
     *
     * @param array $criteria Cohort definition criteria
     * @return array Cohort data with user IDs and metadata
     */
    public function createCohort(array $criteria): array
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $consentService = app(ConsentService::class);

            // Validate criteria
            $this->validateCohortCriteria($criteria);

            $query = User::query();

            // Apply tenant scoping
            if (!$this->isSuperAdmin()) {
                $query->whereHas('tenants', function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId);
                });
            }

            // Apply consent filtering for analytics
            $query->where(function ($q) use ($consentService) {
                $q->whereDoesntHave('consents', function ($consentQuery) {
                    $consentQuery->where('category', 'analytics')
                                ->where('granted', false);
                })->orWhereHas('consents', function ($consentQuery) {
                    $consentQuery->where('category', 'analytics')
                                ->where('granted', true);
                });
            });

            // Apply cohort criteria
            if (isset($criteria['grad_year'])) {
                $query->where('graduation_year', $criteria['grad_year']);
            }

            if (isset($criteria['degree'])) {
                $query->where('degree', $criteria['degree']);
            }

            // Add more criteria as needed
            foreach ($criteria as $key => $value) {
                if (in_array($key, ['grad_year', 'degree'])) {
                    continue; // Already handled above
                }
                $query->where($key, $value);
            }

            $users = $query->select(['id', 'created_at', 'graduation_year', 'degree'])
                ->get()
                ->map(function ($user) {
                    return [
                        'user_id' => $user->id,
                        'acquisition_date' => $user->created_at->toDateString(),
                        'metadata' => [
                            'grad_year' => $user->graduation_year,
                            'degree' => $user->degree,
                        ],
                    ];
                });

            $cohortId = $this->generateCohortId($criteria);

            $cohortData = [
                'cohort_id' => $cohortId,
                'criteria' => $criteria,
                'user_count' => $users->count(),
                'users' => $users,
                'created_at' => now(),
                'tenant_id' => $tenantId,
            ];

            // Cache cohort data
            $this->cacheCohortData($cohortId, $cohortData);

            return $cohortData;

        } catch (Exception $e) {
            Log::error('Failed to create cohort', [
                'criteria' => $criteria,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate retention rate for a cohort
     *
     * @param string $cohortId
     * @param int $daysAfter Number of days after acquisition
     * @return float Retention rate as percentage (0-100)
     */
    public function calculateRetention(string $cohortId, int $daysAfter): float
    {
        try {
            $cohortData = $this->getCohortData($cohortId);
            if (!$cohortData) {
                throw new Exception("Cohort not found: {$cohortId}");
            }

            $cacheKey = "cohort_retention_{$cohortId}_{$daysAfter}";
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cohortData, $daysAfter) {
                $totalUsers = $cohortData['user_count'];
                if ($totalUsers === 0) {
                    return 0.0;
                }

                $tenantId = $cohortData['tenant_id'];
                $userIds = collect($cohortData['users'])->pluck('user_id')->toArray();

                // Find users who were active within the retention window
                $activeUsers = AnalyticsEvent::byTenant($tenantId)
                    ->whereIn('user_id', $userIds)
                    ->where('is_compliant', true)
                    ->where('occurred_at', '>=', Carbon::parse($cohortData['criteria']['acquisition_date'] ?? now()->subDays(30))->addDays($daysAfter))
                    ->where('occurred_at', '<=', Carbon::parse($cohortData['criteria']['acquisition_date'] ?? now()->subDays(30))->addDays($daysAfter + 1))
                    ->distinct('user_id')
                    ->count('user_id');

                return round(($activeUsers / $totalUsers) * 100, 2);
            });

        } catch (Exception $e) {
            Log::error('Failed to calculate retention', [
                'cohort_id' => $cohortId,
                'days_after' => $daysAfter,
                'error' => $e->getMessage(),
            ]);
            return 0.0;
        }
    }

    /**
     * Calculate engagement score for a cohort
     *
     * @param string $cohortId
     * @return array Engagement metrics
     */
    public function calculateEngagement(string $cohortId): array
    {
        try {
            $cohortData = $this->getCohortData($cohortId);
            if (!$cohortData) {
                throw new Exception("Cohort not found: {$cohortId}");
            }

            $cacheKey = "cohort_engagement_{$cohortId}";
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cohortData) {
                $tenantId = $cohortData['tenant_id'];
                $userIds = collect($cohortData['users'])->pluck('user_id')->toArray();

                // Calculate engagement metrics from analytics events
                $engagementData = AnalyticsEvent::byTenant($tenantId)
                    ->whereIn('user_id', $userIds)
                    ->where('is_compliant', true)
                    ->where('occurred_at', '>=', Carbon::parse($cohortData['criteria']['acquisition_date'] ?? now()->subDays(30)))
                    ->selectRaw('
                        user_id,
                        COUNT(*) as total_events,
                        COUNT(DISTINCT DATE(occurred_at)) as active_days,
                        AVG(CASE WHEN event_name = "page_view" THEN 1 ELSE 0 END) as page_views_per_session
                    ')
                    ->groupBy('user_id')
                    ->get();

                if ($engagementData->isEmpty()) {
                    return [
                        'avg_sessions_per_week' => 0.0,
                        'avg_pages_per_session' => 0.0,
                        'avg_active_days_per_week' => 0.0,
                        'engagement_score' => 0.0,
                    ];
                }

                $avgSessionsPerWeek = $engagementData->avg('total_events') / 7;
                $avgPagesPerSession = $engagementData->avg('page_views_per_session');
                $avgActiveDaysPerWeek = $engagementData->avg('active_days') / 7;

                // Calculate engagement score (0-100)
                $engagementScore = min(100, (
                    ($avgSessionsPerWeek * 20) +
                    ($avgPagesPerSession * 30) +
                    ($avgActiveDaysPerWeek * 50)
                ));

                return [
                    'avg_sessions_per_week' => round($avgSessionsPerWeek, 2),
                    'avg_pages_per_session' => round($avgPagesPerSession, 2),
                    'avg_active_days_per_week' => round($avgActiveDaysPerWeek, 2),
                    'engagement_score' => round($engagementScore, 2),
                ];
            });

        } catch (Exception $e) {
            Log::error('Failed to calculate engagement', [
                'cohort_id' => $cohortId,
                'error' => $e->getMessage(),
            ]);
            return [
                'avg_sessions_per_week' => 0.0,
                'avg_pages_per_session' => 0.0,
                'avg_active_days_per_week' => 0.0,
                'engagement_score' => 0.0,
            ];
        }
    }

    /**
     * Calculate conversion rates for a cohort
     *
     * @param string $cohortId
     * @return array Conversion funnel data
     */
    public function calculateConversionRates(string $cohortId): array
    {
        try {
            $cohortData = $this->getCohortData($cohortId);
            if (!$cohortData) {
                throw new Exception("Cohort not found: {$cohortId}");
            }

            $cacheKey = "cohort_conversion_{$cohortId}";
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cohortData) {
                $tenantId = $cohortData['tenant_id'];
                $userIds = collect($cohortData['users'])->pluck('user_id')->toArray();
                $totalUsers = count($userIds);

                // Define conversion funnel stages
                $funnelStages = [
                    'signup' => $totalUsers,
                    'first_login' => 0,
                    'profile_complete' => 0,
                    'first_purchase' => 0,
                    'repeat_purchase' => 0,
                ];

                // Calculate each stage
                $funnelStages['first_login'] = AnalyticsEvent::byTenant($tenantId)
                    ->whereIn('user_id', $userIds)
                    ->where('event_name', 'login')
                    ->distinct('user_id')
                    ->count('user_id');

                $funnelStages['profile_complete'] = AnalyticsEvent::byTenant($tenantId)
                    ->whereIn('user_id', $userIds)
                    ->where('event_name', 'profile_complete')
                    ->distinct('user_id')
                    ->count('user_id');

                $funnelStages['first_purchase'] = AnalyticsEvent::byTenant($tenantId)
                    ->whereIn('user_id', $userIds)
                    ->where('event_name', 'purchase')
                    ->distinct('user_id')
                    ->count('user_id');

                $funnelStages['repeat_purchase'] = AnalyticsEvent::byTenant($tenantId)
                    ->whereIn('user_id', $userIds)
                    ->where('event_name', 'purchase')
                    ->havingRaw('COUNT(*) > 1')
                    ->groupBy('user_id')
                    ->get()
                    ->count();

                // Calculate conversion rates
                $conversionRates = [];
                $previousCount = $totalUsers;

                foreach ($funnelStages as $stage => $count) {
                    $rate = $previousCount > 0 ? round(($count / $previousCount) * 100, 2) : 0.0;
                    $conversionRates[$stage] = [
                        'count' => $count,
                        'rate' => $rate,
                    ];
                    $previousCount = $count;
                }

                return $conversionRates;
            });

        } catch (Exception $e) {
            Log::error('Failed to calculate conversion rates', [
                'cohort_id' => $cohortId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Analyze a cohort with comprehensive metrics
     *
     * @param string $cohortId
     * @return array Analysis results with size, retention, churn, and engagement metrics
     */
    public function analyzeCohort(string $cohortId): array
    {
        try {
            $cohortData = $this->getCohortData($cohortId);
            if (!$cohortData) {
                throw new Exception("Cohort not found: {$cohortId}");
            }

            $cacheKey = "cohort_analysis_{$cohortId}";
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cohortData, $cohortId) {
                $userIds = collect($cohortData['users'])->pluck('user_id')->toArray();
                $tenantId = $cohortData['tenant_id'];

                // Size metrics
                $size = count($userIds);

                // Retention metrics (30, 90, 180 days)
                $retention30 = $this->calculateRetention($cohortId, 30);
                $retention90 = $this->calculateRetention($cohortId, 90);
                $retention180 = $this->calculateRetention($cohortId, 180);

                // Churn rate = 1 - retention
                $churn30 = 1 - ($retention30 / 100);
                $churn90 = 1 - ($retention90 / 100);
                $churn180 = 1 - ($retention180 / 100);

                // Engagement metrics from LearningProgress
                $engagementMetrics = $this->calculateEngagementFromLearningProgress($userIds, $tenantId);

                return [
                    'cohort_id' => $cohortId,
                    'size' => $size,
                    'retention' => [
                        '30_days' => $retention30,
                        '90_days' => $retention90,
                        '180_days' => $retention180,
                    ],
                    'churn_rate' => [
                        '30_days' => round($churn30 * 100, 2),
                        '90_days' => round($churn90 * 100, 2),
                        '180_days' => round($churn180 * 100, 2),
                    ],
                    'engagement' => $engagementMetrics,
                    'analyzed_at' => now(),
                ];
            });

        } catch (Exception $e) {
            Log::error('Failed to analyze cohort', [
                'cohort_id' => $cohortId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Compare retention rates between cohorts with insights
     *
     * @param array $cohortIds Array of cohort IDs to compare
     * @return array Comparison results with retention deltas and insights
     */
    public function compareCohorts(array $cohortIds): array
    {
        try {
            $comparisonData = [];
            $retentionRates = [];

            // Collect retention data for each cohort
            foreach ($cohortIds as $cohortId) {
                $cohortData = $this->getCohortData($cohortId);
                if (!$cohortData) {
                    continue;
                }

                $day7Retention = $this->calculateRetention($cohortId, 7);
                $day30Retention = $this->calculateRetention($cohortId, 30);

                $comparisonData[$cohortId] = [
                    'cohort_id' => $cohortId,
                    'user_count' => $cohortData['user_count'],
                    'day7_retention' => $day7Retention,
                    'day30_retention' => $day30Retention,
                    'engagement' => $this->calculateEngagement($cohortId),
                ];

                $retentionRates[$cohortId] = [
                    'day7' => $day7Retention,
                    'day30' => $day30Retention,
                ];
            }

            // Calculate retention deltas
            $retentionDeltas = $this->calculateRetentionDeltas($comparisonData);

            // Generate insights
            $insights = $this->generateComparisonInsights($comparisonData, $retentionDeltas);

            // Perform statistical comparisons
            $statisticalComparisons = $this->performStatisticalComparisons($retentionRates);

            return [
                'cohorts' => $comparisonData,
                'retention_deltas' => $retentionDeltas,
                'insights' => $insights,
                'statistical_significance' => $statisticalComparisons,
                'best_performing' => $this->identifyBestPerforming($comparisonData),
            ];

        } catch (Exception $e) {
            Log::error('Failed to compare cohorts', [
                'cohort_ids' => $cohortIds,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Generate automated insights for a cohort
     *
     * @param string $cohortId
     * @return array Array of actionable insights
     */
    public function generateInsights(string $cohortId): array
    {
        try {
            $insights = [];

            $retention7 = $this->calculateRetention($cohortId, 7);
            $retention30 = $this->calculateRetention($cohortId, 30);
            $engagement = $this->calculateEngagement($cohortId);
            $conversion = $this->calculateConversionRates($cohortId);

            // Retention insights
            if ($retention7 < 20) {
                $insights[] = [
                    'type' => 'retention',
                    'severity' => 'high',
                    'message' => 'Low 7-day retention detected. Consider improving onboarding flow.',
                    'recommendation' => 'Review user onboarding experience and identify friction points.',
                    'metric' => 'day7_retention',
                    'value' => $retention7,
                ];
            } elseif ($retention7 > 40) {
                $insights[] = [
                    'type' => 'retention',
                    'severity' => 'positive',
                    'message' => 'Excellent 7-day retention rate.',
                    'recommendation' => 'Analyze successful onboarding patterns for replication.',
                    'metric' => 'day7_retention',
                    'value' => $retention7,
                ];
            }

            if ($retention30 < 10) {
                $insights[] = [
                    'type' => 'retention',
                    'severity' => 'critical',
                    'message' => 'Very low 30-day retention. Immediate action required.',
                    'recommendation' => 'Implement re-engagement campaigns and analyze churn reasons.',
                    'metric' => 'day30_retention',
                    'value' => $retention30,
                ];
            }

            // Engagement insights
            if ($engagement['engagement_score'] < 30) {
                $insights[] = [
                    'type' => 'engagement',
                    'severity' => 'high',
                    'message' => 'Low engagement score detected.',
                    'recommendation' => 'Review content strategy and user experience improvements.',
                    'metric' => 'engagement_score',
                    'value' => $engagement['engagement_score'],
                ];
            }

            // Conversion insights
            $signupToPurchaseRate = $conversion['first_purchase']['rate'] ?? 0;
            if ($signupToPurchaseRate < 5) {
                $insights[] = [
                    'type' => 'conversion',
                    'severity' => 'high',
                    'message' => 'Low conversion rate from signup to purchase.',
                    'recommendation' => 'Optimize conversion funnel and reduce friction in purchase flow.',
                    'metric' => 'signup_to_purchase_rate',
                    'value' => $signupToPurchaseRate,
                ];
            }

            return $insights;

        } catch (Exception $e) {
            Log::error('Failed to generate insights', [
                'cohort_id' => $cohortId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Group users by acquisition date
     *
     * @param Carbon $startDate
     * @param string $period 'day', 'week', 'month'
     * @return Collection
     */
    public function groupUsersByAcquisitionDate(Carbon $startDate, string $period = 'week'): Collection
    {
        $tenantId = $this->getCurrentTenantId();

        $query = User::query();

        if (!$this->isSuperAdmin()) {
            $query->whereHas('tenants', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            });
        }

        $dateFormat = match($period) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        return $query->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as period, COUNT(*) as user_count")
            ->where('created_at', '>=', $startDate)
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    /**
     * Group users by acquisition source
     *
     * @param array $sources Optional filter for specific sources
     * @return Collection
     */
    public function groupUsersBySource(array $sources = []): Collection
    {
        $tenantId = $this->getCurrentTenantId();

        $query = User::query();

        if (!$this->isSuperAdmin()) {
            $query->whereHas('tenants', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            });
        }

        if (!empty($sources)) {
            $query->whereIn('metadata->acquisition_source', $sources);
        }

        return $query->selectRaw("COALESCE(metadata->>'$.acquisition_source', 'unknown') as source, COUNT(*) as user_count")
            ->groupBy('source')
            ->orderBy('user_count', 'desc')
            ->get();
    }

    /**
     * Group users by characteristics
     *
     * @param array $characteristics Key-value pairs of characteristics to group by
     * @return Collection
     */
    public function groupUsersByCharacteristics(array $characteristics): Collection
    {
        $tenantId = $this->getCurrentTenantId();

        $query = User::query();

        if (!$this->isSuperAdmin()) {
            $query->whereHas('tenants', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            });
        }

        $selectFields = [];
        $groupByFields = [];

        foreach ($characteristics as $key => $value) {
            $columnName = "characteristic_{$key}";
            $selectFields[] = "COALESCE(metadata->>'$." . $key . "', 'unknown') as {$columnName}";
            $groupByFields[] = $columnName;

            if ($value !== null) {
                $query->where('metadata->' . $key, $value);
            }
        }

        $selectFields[] = 'COUNT(*) as user_count';

        return $query->selectRaw(implode(', ', $selectFields))
            ->groupBy($groupByFields)
            ->orderBy('user_count', 'desc')
            ->get();
    }

    /**
     * Calculate retention rate for a specific cohort
     *
     * @param int $cohortId
     * @param int $daysAfter
     * @return float
     */
    public function calculateRetentionRate(int $cohortId, int $daysAfter): float
    {
        return $this->calculateRetention((string)$cohortId, $daysAfter);
    }

    /**
     * Calculate engagement score for a specific cohort
     *
     * @param int $cohortId
     * @return array
     */
    public function calculateEngagementScore(int $cohortId): array
    {
        return $this->calculateEngagement((string)$cohortId);
    }

    /**
     * Calculate conversion funnel for a specific cohort
     *
     * @param int $cohortId
     * @return array
     */
    public function calculateConversionFunnel(int $cohortId): array
    {
        return $this->calculateConversionRates((string)$cohortId);
    }

    /**
     * Perform statistical significance testing between cohorts
     *
     * @param array $cohortIds
     * @return array
     */
    public function compareRetentionRates(array $cohortIds): array
    {
        return $this->compareCohorts($cohortIds);
    }

    /**
     * Calculate statistical significance between two data sets
     *
     * @param array $data1
     * @param array $data2
     * @return array
     */
    public function calculateStatisticalSignificance(array $data1, array $data2): array
    {
        // Chi-square test for proportions
        $n1 = count($data1);
        $n2 = count($data2);

        if ($n1 === 0 || $n2 === 0) {
            return [
                'significant' => false,
                'p_value' => 1.0,
                'confidence_level' => 0.0,
            ];
        }

        $p1 = array_sum($data1) / $n1;
        $p2 = array_sum($data2) / $n2;
        $p = ($p1 * $n1 + $p2 * $n2) / ($n1 + $n2);

        if ($p === 0 || $p === 1) {
            return [
                'significant' => false,
                'p_value' => 1.0,
                'confidence_level' => 0.0,
            ];
        }

        $chiSquare = (($n1 + $n2) * pow($p1 - $p2, 2)) / ($p * (1 - $p) * ($n1 + $n2) / ($n1 * $n2));

        // Approximate p-value using chi-square distribution
        $pValue = $this->approximateChiSquarePValue($chiSquare, 1);

        return [
            'significant' => $pValue < 0.05,
            'p_value' => round($pValue, 4),
            'chi_square' => round($chiSquare, 4),
            'confidence_level' => $pValue < 0.05 ? 95 : 0,
        ];
    }

    // Private helper methods

    private function getCurrentTenantId(): string
    {
        return session('tenant_id', 'default');
    }

    private function isSuperAdmin(): bool
    {
        return Auth::check() && Auth::user()->hasRole('super_admin');
    }

    private function generateCohortId(array $criteria): string
    {
        return 'cohort_' . md5(serialize($criteria) . now()->timestamp);
    }

    private function cacheCohortData(string $cohortId, array $data): void
    {
        Cache::put("cohort_data_{$cohortId}", $data, self::CACHE_TTL);
    }

    private function getCohortData(string $cohortId): ?array
    {
        return Cache::get("cohort_data_{$cohortId}");
    }

    private function performStatisticalComparisons(array $retentionRates): array
    {
        $comparisons = [];

        $cohortIds = array_keys($retentionRates);
        for ($i = 0; $i < count($cohortIds); $i++) {
            for ($j = $i + 1; $j < count($cohortIds); $j++) {
                $cohort1 = $cohortIds[$i];
                $cohort2 = $cohortIds[$j];

                $comparison = [
                    'cohort_1' => $cohort1,
                    'cohort_2' => $cohort2,
                    'day7_significance' => $this->calculateStatisticalSignificance(
                        [$retentionRates[$cohort1]['day7']],
                        [$retentionRates[$cohort2]['day7']]
                    ),
                    'day30_significance' => $this->calculateStatisticalSignificance(
                        [$retentionRates[$cohort1]['day30']],
                        [$retentionRates[$cohort2]['day30']]
                    ),
                ];

                $comparisons[] = $comparison;
            }
        }

        return $comparisons;
    }

    private function identifyBestPerforming(array $comparisonData): array
    {
        if (empty($comparisonData)) {
            return [];
        }

        $bestRetention7 = collect($comparisonData)->max('day7_retention');
        $bestRetention30 = collect($comparisonData)->max('day30_retention');
        $bestEngagement = collect($comparisonData)->max(function ($cohort) {
            return $cohort['engagement']['engagement_score'];
        });

        return [
            'best_day7_retention' => collect($comparisonData)->first(function ($cohort) use ($bestRetention7) {
                return $cohort['day7_retention'] === $bestRetention7;
            })['cohort_id'] ?? null,
            'best_day30_retention' => collect($comparisonData)->first(function ($cohort) use ($bestRetention30) {
                return $cohort['day30_retention'] === $bestRetention30;
            })['cohort_id'] ?? null,
            'best_engagement' => collect($comparisonData)->first(function ($cohort) use ($bestEngagement) {
                return $cohort['engagement']['engagement_score'] === $bestEngagement;
            })['cohort_id'] ?? null,
        ];
    }

    private function approximateChiSquarePValue(float $chiSquare, int $degreesOfFreedom): float
    {
        // Simplified approximation using normal distribution for df=1
        if ($degreesOfFreedom === 1) {
            // For 1 degree of freedom, chi-square follows approximately normal distribution
            $z = sqrt($chiSquare) - sqrt($degreesOfFreedom - 0.5);
            // Use approximation of p-value for normal distribution
            return 1 - $this->normalCDF($z);
        }

        // For other degrees of freedom, use a simple approximation
        return exp(-$chiSquare / 2) / sqrt(2 * M_PI * $chiSquare);
    }

    private function normalCDF(float $z): float
    {
        // Abramowitz & Stegun approximation
        $t = 1 / (1 + 0.2316419 * abs($z));
        $d = 0.3989423 * exp(-$z * $z / 2);
        $p = $d * $t * (0.3193815 + $t * (-0.3565638 + $t * (1.781478 + $t * (-1.821256 + $t * 1.330274))));

        return $z > 0 ? 1 - $p : $p;
    }

    /**
     * Calculate retention deltas between cohorts
     *
     * @param array $comparisonData
     * @return array
     */
    private function calculateRetentionDeltas(array $comparisonData): array
    {
        $deltas = [];
        $cohortIds = array_keys($comparisonData);

        for ($i = 0; $i < count($cohortIds); $i++) {
            for ($j = $i + 1; $j < count($cohortIds); $j++) {
                $cohort1 = $cohortIds[$i];
                $cohort2 = $cohortIds[$j];

                $deltas["{$cohort1}_vs_{$cohort2}"] = [
                    'day7_delta' => $comparisonData[$cohort1]['day7_retention'] - $comparisonData[$cohort2]['day7_retention'],
                    'day30_delta' => $comparisonData[$cohort1]['day30_retention'] - $comparisonData[$cohort2]['day30_retention'],
                ];
            }
        }

        return $deltas;
    }

    /**
     * Generate insights from cohort comparison
     *
     * @param array $comparisonData
     * @param array $retentionDeltas
     * @return array
     */
    private function generateComparisonInsights(array $comparisonData, array $retentionDeltas): array
    {
        $insights = [];

        // Find significant retention differences
        foreach ($retentionDeltas as $comparison => $deltas) {
            if (abs($deltas['day7_delta']) > 10) {
                $insights[] = [
                    'type' => 'retention_comparison',
                    'message' => "Significant 7-day retention difference of " . round($deltas['day7_delta'], 1) . "% in {$comparison}",
                    'recommendation' => 'Analyze onboarding processes and user engagement strategies',
                ];
            }
        }

        // Identify best performing cohort
        $bestRetention = collect($comparisonData)->max('day30_retention');
        $bestCohort = collect($comparisonData)->first(function ($cohort) use ($bestRetention) {
            return $cohort['day30_retention'] === $bestRetention;
        });

        if ($bestCohort) {
            $insights[] = [
                'type' => 'best_performer',
                'message' => "Cohort {$bestCohort['cohort_id']} shows best 30-day retention at {$bestRetention}%",
                'recommendation' => 'Study successful patterns and apply to other cohorts',
            ];
        }

        return $insights;
    }

    /**
     * Calculate engagement metrics from LearningProgress
     *
     * @param array $userIds
     * @param string $tenantId
     * @return array
     */
    private function calculateEngagementFromLearningProgress(array $userIds, string $tenantId): array
    {
        $progressData = LearningProgress::byTenant($tenantId)
            ->whereIn('user_id', $userIds)
            ->selectRaw('
                AVG(modules_completed) as avg_modules_completed,
                AVG(engagement_score) as avg_engagement_score,
                COUNT(*) as total_progress_records
            ')
            ->first();

        return [
            'avg_modules_completed' => round($progressData->avg_modules_completed ?? 0, 2),
            'avg_engagement_score' => round(($progressData->avg_engagement_score ?? 0) * 100, 2), // Convert to percentage
            'total_progress_records' => $progressData->total_progress_records ?? 0,
        ];
    }

    /**
     * Validate cohort criteria
     *
     * @param array $criteria
     * @throws Exception
     */
    private function validateCohortCriteria(array $criteria): void
    {
        if (empty($criteria)) {
            throw new Exception('Cohort criteria cannot be empty');
        }

        $allowedKeys = ['grad_year', 'degree'];
        foreach ($criteria as $key => $value) {
            if (!in_array($key, $allowedKeys)) {
                throw new Exception("Invalid cohort criteria key: {$key}");
            }
            if (empty($value)) {
                throw new Exception("Cohort criteria value for {$key} cannot be empty");
            }
        }
    }
}