<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Cohort;
use App\Models\LearningProgress;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cohort Analysis Service
 *
 * Provides comprehensive cohort analysis functionality for user behavior analysis
 * over time, including retention, engagement, and conversion rate calculations.
 * Supports cohort creation, comparison, trend analysis, and automated insight generation.
 */
class CohortAnalysisService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const CHUNK_SIZE = 1000;

    private const CONFIDENCE_LEVEL_95 = 1.96;

    private ConsentService $consentService;

    public function __construct(ConsentService $consentService)
    {
        $this->consentService = $consentService;
    }

    /**
     * Create a cohort based on specified criteria
     *
     * @param  string  $name  Cohort name
     * @param  array  $criteria  Cohort definition criteria
     * @param  int|null  $createdBy  User ID who creates the cohort
     * @return Cohort The created cohort model
     */
    public function createCohort(string $name, array $criteria, ?int $createdBy = null): Cohort
    {
        try {
            $tenantId = $this->getCurrentTenantId();

            // Validate criteria
            $this->validateCohortCriteria($criteria);

            $query = User::query();

            // Apply tenant scoping
            if (! $this->isSuperAdmin()) {
                $query->whereHas('tenants', function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId);
                });
            }

            // Apply consent filtering for analytics
            $query->where(function ($q) {
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

            // Apply additional criteria
            foreach ($criteria as $key => $value) {
                if (in_array($key, ['grad_year', 'degree'])) {
                    continue; // Already handled above
                }
                if (is_array($value)) {
                    // Handle array criteria (IN queries)
                    $query->whereIn($key, $value);
                } else {
                    $query->where($key, $value);
                }
            }

            $userCount = $query->count();

            // Create cohort
            $cohort = Cohort::create([
                'tenant_id' => $tenantId,
                'name' => $name,
                'criteria_json' => $criteria,
                'created_by' => $createdBy ?? Auth::id(),
                'members_count' => $userCount,
            ]);

            Log::info('Cohort created', [
                'cohort_id' => $cohort->id,
                'name' => $name,
                'criteria' => $criteria,
                'members_count' => $userCount,
                'tenant_id' => $tenantId,
            ]);

            return $cohort;

        } catch (Exception $e) {
            Log::error('Failed to create cohort', [
                'name' => $name,
                'criteria' => $criteria,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate retention rate for a cohort
     *
     * @param  int  $cohortId  Cohort ID
     * @param  int  $daysAfter  Number of days after acquisition
     * @return float Retention rate as percentage (0-100)
     */
    public function calculateRetention(int $cohortId, int $daysAfter): float
    {
        try {
            $cohort = Cohort::byTenant($this->getCurrentTenantId())->findOrFail($cohortId);

            $cacheKey = "cohort_retention_{$cohortId}_{$daysAfter}";

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cohort, $daysAfter) {
                $totalUsers = $cohort->members_count;
                if ($totalUsers === 0) {
                    return 0.0;
                }

                $tenantId = $cohort->tenant_id;
                $criteria = $cohort->criteria_json ?? [];
                $acquisitionDate = Carbon::parse($criteria['acquisition_date'] ?? now()->subDays(30));

                // Find users who were active within the retention window
                $activeUsers = AnalyticsEvent::byTenant($tenantId)
                    ->where('occurred_at', '>=', $acquisitionDate->copy()->addDays($daysAfter))
                    ->where('occurred_at', '<=', $acquisitionDate->copy()->addDays($daysAfter + 1))
                    ->whereHas('user', function ($query) use ($criteria, $acquisitionDate) {
                        if (isset($criteria['grad_year'])) {
                            $query->where('graduation_year', $criteria['grad_year']);
                        }
                        if (isset($criteria['degree'])) {
                            $query->where('degree', $criteria['degree']);
                        }
                        $query->where('created_at', '>=', $acquisitionDate);
                    })
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
     * Calculate engagement metrics for a cohort
     *
     * @param  int  $cohortId  Cohort ID
     * @return array Engagement metrics
     */
    public function calculateEngagement(int $cohortId): array
    {
        try {
            $cohort = Cohort::byTenant($this->getCurrentTenantId())->findOrFail($cohortId);

            $cacheKey = "cohort_engagement_{$cohortId}";

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cohort) {
                $tenantId = $cohort->tenant_id;
                $criteria = $cohort->criteria_json ?? [];
                $acquisitionDate = Carbon::parse($criteria['acquisition_date'] ?? now()->subDays(30));

                // Calculate engagement metrics from analytics events
                $engagementData = AnalyticsEvent::byTenant($tenantId)
                    ->where('occurred_at', '>=', $acquisitionDate)
                    ->whereHas('user', function ($query) use ($criteria) {
                        if (isset($criteria['grad_year'])) {
                            $query->where('graduation_year', $criteria['grad_year']);
                        }
                        if (isset($criteria['degree'])) {
                            $query->where('degree', $criteria['degree']);
                        }
                    })
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
     * @param  int  $cohortId  Cohort ID
     * @return array Conversion funnel data
     */
    public function calculateConversionRate(int $cohortId): array
    {
        try {
            $cohort = Cohort::byTenant($this->getCurrentTenantId())->findOrFail($cohortId);

            $cacheKey = "cohort_conversion_{$cohortId}";

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cohort) {
                $tenantId = $cohort->tenant_id;
                $criteria = $cohort->criteria_json ?? [];
                $totalUsers = $cohort->members_count;

                // Define conversion funnel stages
                $funnelStages = [
                    'signup' => $totalUsers,
                    'first_login' => 0,
                    'profile_complete' => 0,
                    'first_purchase' => 0,
                    'repeat_purchase' => 0,
                ];

                // Build user query for consistent filtering
                $userQuery = function ($query) use ($criteria) {
                    if (isset($criteria['grad_year'])) {
                        $query->where('graduation_year', $criteria['grad_year']);
                    }
                    if (isset($criteria['degree'])) {
                        $query->where('degree', $criteria['degree']);
                    }
                };

                // Calculate each stage
                $funnelStages['first_login'] = AnalyticsEvent::byTenant($tenantId)
                    ->where('event_name', 'login')
                    ->whereHas('user', $userQuery)
                    ->distinct('user_id')
                    ->count('user_id');

                $funnelStages['profile_complete'] = AnalyticsEvent::byTenant($tenantId)
                    ->where('event_name', 'profile_complete')
                    ->whereHas('user', $userQuery)
                    ->distinct('user_id')
                    ->count('user_id');

                $funnelStages['first_purchase'] = AnalyticsEvent::byTenant($tenantId)
                    ->where('event_name', 'purchase')
                    ->whereHas('user', $userQuery)
                    ->distinct('user_id')
                    ->count('user_id');

                $repeatPurchasers = AnalyticsEvent::byTenant($tenantId)
                    ->where('event_name', 'purchase')
                    ->whereHas('user', $userQuery)
                    ->groupBy('user_id')
                    ->havingRaw('COUNT(*) > 1')
                    ->pluck('user_id');

                $funnelStages['repeat_purchase'] = $repeatPurchasers->count();

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
     * @param  int  $cohortId  Cohort ID
     * @param  array  $options  Analysis options (date_range, etc.)
     * @return array Analysis results with size, retention, churn, and engagement metrics
     */
    public function analyzeCohort(int $cohortId, array $options = []): array
    {
        try {
            $cohort = Cohort::byTenant($this->getCurrentTenantId())->findOrFail($cohortId);

            $cacheKey = "cohort_analysis_{$cohortId}_".md5(serialize($options));
            $currentCohortId = $cohortId;

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cohort, $options, $currentCohortId) {
                $size = $cohort->members_count;
                $criteria = $cohort->criteria_json ?? [];

                // Retention metrics (7, 30, 90 days)
                $retention7 = $this->calculateRetention($currentCohortId, 7);
                $retention30 = $this->calculateRetention($currentCohortId, 30);
                $retention90 = $this->calculateRetention($currentCohortId, 90);

                // Churn rate = 1 - retention
                $churn7 = 1 - ($retention7 / 100);
                $churn30 = 1 - ($retention30 / 100);
                $churn90 = 1 - ($retention90 / 100);

                // Engagement metrics
                $engagement = $this->calculateEngagement($currentCohortId);

                // Engagement from LearningProgress
                $engagementMetrics = $this->calculateEngagementFromLearningProgress($criteria);

                // Conversion rates
                $conversionRates = $this->calculateConversionRate($currentCohortId);

                $result = [
                    'cohort_id' => $currentCohortId,
                    'cohort_name' => $cohort->name,
                    'size' => $size,
                    'retention' => [
                        'day7' => $retention7,
                        'day30' => $retention30,
                        'day90' => $retention90,
                    ],
                    'churn_rate' => [
                        'day7' => round($churn7 * 100, 2),
                        'day30' => round($churn30 * 100, 2),
                        'day90' => round($churn90 * 100, 2),
                    ],
                    'engagement' => array_merge($engagement, $engagementMetrics),
                    'conversions' => $conversionRates,
                    'criteria' => $criteria,
                    'analyzed_at' => now(),
                ];

                // Add date range if provided
                if (isset($options['start_date']) && isset($options['end_date'])) {
                    $result['date_range'] = [
                        'start' => $options['start_date'],
                        'end' => $options['end_date'],
                    ];
                }

                return $result;
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
     * @param  array  $cohortIds  Array of cohort IDs to compare
     * @return array Comparison results with retention deltas and insights
     */
    public function compareCohorts(array $cohortIds): array
    {
        try {
            if (count($cohortIds) < 2) {
                throw new \InvalidArgumentException('At least two cohorts are required for comparison');
            }

            $comparisonData = [];
            $retentionRates = [];

            // Collect retention data for each cohort
            foreach ($cohortIds as $cohortId) {
                $cohort = Cohort::byTenant($this->getCurrentTenantId())->find($cohortId);
                if (! $cohort) {
                    continue;
                }

                $day7Retention = $this->calculateRetention($cohortId, 7);
                $day30Retention = $this->calculateRetention($cohortId, 30);
                $day90Retention = $this->calculateRetention($cohortId, 90);
                $engagement = $this->calculateEngagement($cohortId);

                $comparisonData[$cohortId] = [
                    'cohort_id' => $cohortId,
                    'cohort_name' => $cohort->name,
                    'size' => $cohort->members_count,
                    'day7_retention' => $day7Retention,
                    'day30_retention' => $day30Retention,
                    'day90_retention' => $day90Retention,
                    'engagement_score' => $engagement['engagement_score'],
                ];

                $retentionRates[$cohortId] = [
                    'day7' => $day7Retention,
                    'day30' => $day30Retention,
                    'day90' => $day90Retention,
                ];
            }

            // Calculate retention deltas
            $retentionDeltas = $this->calculateRetentionDeltas($comparisonData);

            // Generate insights
            $insights = $this->generateComparisonInsights($comparisonData, $retentionDeltas);

            // Perform statistical comparisons
            $statisticalComparisons = $this->performStatisticalComparisons($retentionRates);

            // Identify best performing cohort
            $bestPerforming = $this->identifyBestPerforming($comparisonData);

            return [
                'cohorts' => array_values($comparisonData),
                'retention_deltas' => $retentionDeltas,
                'insights' => $insights,
                'statistical_significance' => $statisticalComparisons,
                'best_performing' => $bestPerforming,
                'compared_at' => now(),
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
     * Analyze trends over time for a cohort
     *
     * @param  int  $cohortId  Cohort ID
     * @param  string  $period  'day', 'week', 'month'
     * @param  int  $periods  Number of periods to analyze
     * @return array Trend analysis data
     */
    public function analyzeTrends(int $cohortId, string $period = 'week', int $periods = 12): array
    {
        try {
            $cohort = Cohort::byTenant($this->getCurrentTenantId())->findOrFail($cohortId);

            $cacheKey = "cohort_trends_{$cohortId}_{$period}_{$periods}";
            $currentCohortId = $cohortId;

            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cohort, $period, $periods, $currentCohortId) {
                $criteria = $cohort->criteria_json ?? [];
                $tenantId = $cohort->tenant_id;

                $trends = [];
                $periodFormat = match ($period) {
                    'day' => 'Y-m-d',
                    'week' => 'Y-W',
                    'month' => 'Y-m',
                    default => 'Y-m'
                };

                $startDate = now()->subPeriods($periods, $period);

                for ($i = $periods; $i >= 0; $i--) {
                    $periodStart = now()->subPeriods($i, $period);
                    $periodEnd = now()->subPeriods($i - 1, $period);

                    // Count active users in period
                    $activeUsers = AnalyticsEvent::byTenant($tenantId)
                        ->where('occurred_at', '>=', $periodStart)
                        ->where('occurred_at', '<', $periodEnd)
                        ->whereHas('user', function ($query) use ($criteria) {
                            if (isset($criteria['grad_year'])) {
                                $query->where('graduation_year', $criteria['grad_year']);
                            }
                            if (isset($criteria['degree'])) {
                                $query->where('degree', $criteria['degree']);
                            }
                        })
                        ->distinct('user_id')
                        ->count('user_id');

                    // Count events in period
                    $eventCount = AnalyticsEvent::byTenant($tenantId)
                        ->where('occurred_at', '>=', $periodStart)
                        ->where('occurred_at', '<', $periodEnd)
                        ->whereHas('user', function ($query) use ($criteria) {
                            if (isset($criteria['grad_year'])) {
                                $query->where('graduation_year', $criteria['grad_year']);
                            }
                            if (isset($criteria['degree'])) {
                                $query->where('degree', $criteria['degree']);
                            }
                        })
                        ->count();

                    $periodKey = $periodStart->format($periodFormat);

                    $trends[$periodKey] = [
                        'period' => $periodKey,
                        'active_users' => $activeUsers,
                        'event_count' => $eventCount,
                        'avg_events_per_user' => $activeUsers > 0 ? round($eventCount / $activeUsers, 2) : 0,
                    ];
                }

                // Calculate trend indicators
                $trendData = array_values($trends);
                $trendsWithIndicators = $this->calculateTrendIndicators($trendData);

                return [
                    'cohort_id' => $currentCohortId,
                    'cohort_name' => $cohort->name,
                    'period' => $period,
                    'periods_analyzed' => $periods,
                    'trends' => $trendsWithIndicators,
                    'summary' => $this->summarizeTrends($trendsWithIndicators),
                    'analyzed_at' => now(),
                ];
            });

        } catch (Exception $e) {
            Log::error('Failed to analyze trends', [
                'cohort_id' => $cohortId,
                'period' => $period,
                'periods' => $periods,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Generate automated insights for a cohort
     *
     * @param  int  $cohortId  Cohort ID
     * @return array Array of actionable insights
     */
    public function generateInsights(int $cohortId): array
    {
        try {
            $insights = [];

            $retention7 = $this->calculateRetention($cohortId, 7);
            $retention30 = $this->calculateRetention($cohortId, 30);
            $retention90 = $this->calculateRetention($cohortId, 90);
            $engagement = $this->calculateEngagement($cohortId);
            $conversion = $this->calculateConversionRate($cohortId);
            $trends = $this->analyzeTrends($cohortId, 'week', 8);

            // Retention insights
            if ($retention7 < 20) {
                $insights[] = [
                    'type' => 'retention',
                    'severity' => 'critical',
                    'message' => 'Low 7-day retention detected. Consider improving onboarding flow.',
                    'recommendation' => 'Review user onboarding experience and identify friction points.',
                    'metric' => 'day7_retention',
                    'value' => $retention7,
                    'benchmark' => 40,
                ];
            } elseif ($retention7 < 40) {
                $insights[] = [
                    'type' => 'retention',
                    'severity' => 'medium',
                    'message' => 'Below average 7-day retention. Room for improvement.',
                    'recommendation' => 'Analyze successful user paths and replicate in onboarding.',
                    'metric' => 'day7_retention',
                    'value' => $retention7,
                    'benchmark' => 40,
                ];
            } elseif ($retention7 >= 60) {
                $insights[] = [
                    'type' => 'retention',
                    'severity' => 'positive',
                    'message' => 'Excellent 7-day retention rate.',
                    'recommendation' => 'Analyze successful onboarding patterns for replication.',
                    'metric' => 'day7_retention',
                    'value' => $retention7,
                    'benchmark' => 40,
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
                    'benchmark' => 25,
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
                    'benchmark' => 50,
                ];
            } elseif ($engagement['engagement_score'] >= 70) {
                $insights[] = [
                    'type' => 'engagement',
                    'severity' => 'positive',
                    'message' => 'High engagement score achieved.',
                    'recommendation' => 'Identify what drives engagement and apply to other cohorts.',
                    'metric' => 'engagement_score',
                    'value' => $engagement['engagement_score'],
                    'benchmark' => 50,
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
                    'benchmark' => 15,
                ];
            }

            // Trend insights
            if (! empty($trends['trends'])) {
                $recentTrend = end($trends['trends']);
                $previousTrend = $trends['trends'][count($trends['trends']) - 2] ?? null;

                if ($previousTrend && $recentTrend['active_users'] < $previousTrend['active_users'] * 0.8) {
                    $insights[] = [
                        'type' => 'trend',
                        'severity' => 'medium',
                        'message' => 'Declining active users trend detected.',
                        'recommendation' => 'Investigate factors causing user decline and implement retention strategies.',
                        'metric' => 'active_users_trend',
                        'value' => $recentTrend['active_users'],
                        'previous_value' => $previousTrend['active_users'],
                    ];
                }
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
     * @param  string  $period  'day', 'week', 'month'
     */
    public function groupUsersByAcquisitionDate(Carbon $startDate, string $period = 'week'): Collection
    {
        $tenantId = $this->getCurrentTenantId();

        $query = User::query();

        if (! $this->isSuperAdmin()) {
            $query->whereHas('tenants', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            });
        }

        $dateFormat = match ($period) {
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
     * Calculate statistical significance between two data sets
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

    private function getCurrentTenantId(): int
    {
        return (int) session('tenant_id', 1);
    }

    private function isSuperAdmin(): bool
    {
        // Default to false for security - super admin check requires proper role system
        // In a real application, this would check for the actual role
        return false;
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
        $bestEngagement = collect($comparisonData)->max('engagement_score');

        return [
            'best_day7_retention' => collect($comparisonData)->first(function ($cohort) use ($bestRetention7) {
                return $cohort['day7_retention'] === $bestRetention7;
            })['cohort_id'] ?? null,
            'best_day30_retention' => collect($comparisonData)->first(function ($cohort) use ($bestRetention30) {
                return $cohort['day30_retention'] === $bestRetention30;
            })['cohort_id'] ?? null,
            'best_engagement' => collect($comparisonData)->first(function ($cohort) use ($bestEngagement) {
                return $cohort['engagement_score'] === $bestEngagement;
            })['cohort_id'] ?? null,
        ];
    }

    private function approximateChiSquarePValue(float $chiSquare, int $degreesOfFreedom): float
    {
        // Simplified approximation using normal distribution for df=1
        if ($degreesOfFreedom === 1) {
            $z = sqrt($chiSquare) - sqrt($degreesOfFreedom - 0.5);

            return 1 - $this->normalCDF($z);
        }

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

    private function generateComparisonInsights(array $comparisonData, array $retentionDeltas): array
    {
        $insights = [];

        // Find significant retention differences
        foreach ($retentionDeltas as $comparison => $deltas) {
            if (abs($deltas['day7_delta']) > 10) {
                $insights[] = [
                    'type' => 'retention_comparison',
                    'message' => 'Significant 7-day retention difference of '.round($deltas['day7_delta'], 1)."% in {$comparison}",
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
                'message' => "Cohort {$bestCohort['cohort_name']} shows best 30-day retention at {$bestRetention}%",
                'recommendation' => 'Study successful patterns and apply to other cohorts',
            ];
        }

        return $insights;
    }

    private function calculateEngagementFromLearningProgress(array $criteria): array
    {
        $tenantId = $this->getCurrentTenantId();

        $query = LearningProgress::byTenant($tenantId)->whereHas('user', function ($query) use ($criteria) {
            if (isset($criteria['grad_year'])) {
                $query->where('graduation_year', $criteria['grad_year']);
            }
            if (isset($criteria['degree'])) {
                $query->where('degree', $criteria['degree']);
            }
        });

        $progressData = $query->selectRaw('
            AVG(modules_completed) as avg_modules_completed,
            AVG(engagement_score) as avg_engagement_score,
            COUNT(*) as total_progress_records
        ')->first();

        return [
            'avg_modules_completed' => round($progressData->avg_modules_completed ?? 0, 2),
            'avg_learning_engagement' => round(($progressData->avg_engagement_score ?? 0) * 100, 2),
            'total_progress_records' => $progressData->total_progress_records ?? 0,
        ];
    }

    private function calculateTrendIndicators(array $trends): array
    {
        if (count($trends) < 2) {
            return $trends;
        }

        $result = [];
        for ($i = 0; $i < count($trends); $i++) {
            $trend = $trends[$i];

            if ($i === 0) {
                $trend['indicator'] = 'neutral';
                $trend['change_percent'] = 0;
            } else {
                $previous = $trends[$i - 1];
                $change = $previous['active_users'] > 0
                    ? (($trend['active_users'] - $previous['active_users']) / $previous['active_users']) * 100
                    : 0;

                $trend['change_percent'] = round($change, 2);
                $trend['indicator'] = match (true) {
                    $change > 10 => 'up',
                    $change > 0 => 'slight_up',
                    $change < -10 => 'down',
                    $change < 0 => 'slight_down',
                    default => 'neutral',
                };
            }

            $result[] = $trend;
        }

        return $result;
    }

    private function summarizeTrends(array $trends): array
    {
        if (empty($trends)) {
            return [
                'overall_trend' => 'neutral',
                'avg_active_users' => 0,
                'total_events' => 0,
            ];
        }

        $activeUsers = array_column($trends, 'active_users');
        $events = array_column($trends, 'event_count');

        $recentPeriods = array_slice($trends, -4);
        $olderPeriods = array_slice($trends, -8, 4);

        $recentAvg = ! empty($recentPeriods) ? array_sum(array_column($recentPeriods, 'active_users')) / count($recentPeriods) : 0;
        $olderAvg = ! empty($olderPeriods) ? array_sum(array_column($olderPeriods, 'active_users')) / count($olderPeriods) : 0;

        $overallTrend = match (true) {
            $recentAvg > $olderAvg * 1.1 => 'improving',
            $recentAvg < $olderAvg * 0.9 => 'declining',
            default => 'stable',
        };

        return [
            'overall_trend' => $overallTrend,
            'avg_active_users' => round(array_sum($activeUsers) / count($activeUsers), 2),
            'total_events' => array_sum($events),
            'periods_with_growth' => count(array_filter($trends, fn ($t) => in_array($t['indicator'], ['up', 'slight_up']))),
            'periods_with_decline' => count(array_filter($trends, fn ($t) => in_array($t['indicator'], ['down', 'slight_down']))),
        ];
    }

    /**
     * Calculate conversion rates for a cohort (alias)
     *
     * @param  int  $cohortId  Cohort ID
     * @return array Conversion funnel data
     */
    public function calculateConversionRates(int $cohortId): array
    {
        return $this->calculateConversionRate($cohortId);
    }

    /**
     * Validate cohort criteria
     *
     * @throws \InvalidArgumentException
     */
    private function validateCohortCriteria(array $criteria): void
    {
        if (empty($criteria)) {
            throw new \InvalidArgumentException('Cohort criteria cannot be empty');
        }

        $allowedKeys = ['grad_year', 'degree', 'acquisition_date', 'acquisition_source', 'major', 'metadata'];
        foreach ($criteria as $key => $value) {
            if (! in_array($key, $allowedKeys)) {
                throw new \InvalidArgumentException("Invalid cohort criteria key: {$key}");
            }
            if (empty($value) && $value !== 0 && $value !== false) {
                throw new \InvalidArgumentException("Cohort criteria value for {$key} cannot be empty");
            }
        }
    }
}
