<?php

namespace App\Services;

use App\Models\CampaignDonation;
use App\Models\DonorProfile;
use App\Models\FundraisingCampaign;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FundraisingAnalyticsService
{
    /**
     * Get comprehensive giving pattern analysis
     */
    public function getGivingPatternAnalysis(array $filters = []): array
    {
        $query = CampaignDonation::query()->completed();
        
        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('processed_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('processed_at', '<=', $filters['date_to']);
        }
        
        if (isset($filters['institution_id'])) {
            $query->whereHas('campaign', function ($q) use ($filters) {
                $q->where('institution_id', $filters['institution_id']);
            });
        }

        $donations = $query->with(['campaign', 'donor'])->get();
        
        return [
            'total_donations' => $donations->count(),
            'total_amount' => $donations->sum('amount'),
            'average_donation' => $donations->avg('amount'),
            'median_donation' => $this->calculateMedian($donations->pluck('amount')),
            'giving_frequency' => $this->analyzeGivingFrequency($donations),
            'seasonal_patterns' => $this->analyzeSeasonalPatterns($donations),
            'donor_retention' => $this->analyzeDonorRetention($donations),
            'gift_size_distribution' => $this->analyzeGiftSizeDistribution($donations),
            'recurring_vs_one_time' => $this->analyzeRecurringVsOneTime($donations),
            'campaign_type_performance' => $this->analyzeCampaignTypePerformance($donations),
        ];
    }

    /**
     * Get campaign performance tracking and ROI metrics
     */
    public function getCampaignPerformanceMetrics(FundraisingCampaign $campaign): array
    {
        $donations = $campaign->donations()->completed()->get();
        $startDate = $campaign->start_date;
        $endDate = $campaign->end_date ?? now();
        $daysActive = $startDate->diffInDays($endDate);
        
        // Calculate acquisition costs (estimated based on campaign type)
        $estimatedAcquisitionCost = $this->estimateAcquisitionCost($campaign);
        
        return [
            'campaign_id' => $campaign->id,
            'campaign_title' => $campaign->title,
            'performance_metrics' => [
                'total_raised' => $campaign->raised_amount,
                'goal_amount' => $campaign->goal_amount,
                'goal_achievement_percentage' => $campaign->progress_percentage,
                'donor_count' => $campaign->donor_count,
                'average_donation' => $donations->avg('amount') ?? 0,
                'largest_donation' => $donations->max('amount') ?? 0,
                'days_active' => $daysActive,
                'daily_average' => $daysActive > 0 ? $campaign->raised_amount / $daysActive : 0,
            ],
            'roi_metrics' => [
                'estimated_cost' => $estimatedAcquisitionCost,
                'roi_percentage' => $estimatedAcquisitionCost > 0 ? 
                    (($campaign->raised_amount - $estimatedAcquisitionCost) / $estimatedAcquisitionCost) * 100 : 0,
                'cost_per_donor' => $campaign->donor_count > 0 ? 
                    $estimatedAcquisitionCost / $campaign->donor_count : 0,
                'cost_per_dollar_raised' => $campaign->raised_amount > 0 ? 
                    $estimatedAcquisitionCost / $campaign->raised_amount : 0,
            ],
            'engagement_metrics' => [
                'peer_fundraisers_count' => $campaign->peerFundraisers()->count(),
                'peer_fundraising_percentage' => $campaign->raised_amount > 0 ? 
                    ($campaign->peerFundraisers()->sum('raised_amount') / $campaign->raised_amount) * 100 : 0,
                'social_shares' => $this->getSocialShareCount($campaign),
                'page_views' => $this->getPageViewCount($campaign),
            ],
            'timeline_performance' => $this->getCampaignTimelinePerformance($campaign),
        ];
    }

    /**
     * Get donor analytics and engagement scoring
     */
    public function getDonorAnalytics(array $filters = []): array
    {
        $query = DonorProfile::query()->with(['user', 'donations']);
        
        // Apply filters
        if (isset($filters['institution_id'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('institution_id', $filters['institution_id']);
            });
        }
        
        if (isset($filters['donor_tier'])) {
            $query->where('donor_tier', $filters['donor_tier']);
        }

        $donors = $query->get();
        
        return [
            'total_donors' => $donors->count(),
            'donor_tiers' => $this->analyzeDonorTiers($donors),
            'engagement_scores' => $this->calculateEngagementScores($donors),
            'giving_capacity' => $this->analyzeGivingCapacity($donors),
            'contact_preferences' => $this->analyzeContactPreferences($donors),
            'geographic_distribution' => $this->analyzeGeographicDistribution($donors),
            'wealth_indicators' => $this->analyzeWealthIndicators($donors),
            'stewardship_pipeline' => $this->analyzeStewardshipPipeline($donors),
            'major_gift_prospects' => $this->identifyMajorGiftProspects($donors),
        ];
    }

    /**
     * Get predictive analytics for giving potential
     */
    public function getPredictiveAnalytics(array $filters = []): array
    {
        $donors = DonorProfile::query()
            ->with(['user', 'donations', 'interactions'])
            ->get();
            
        return [
            'giving_likelihood' => $this->predictGivingLikelihood($donors),
            'upgrade_potential' => $this->predictUpgradePotential($donors),
            'lapse_risk' => $this->predictLapseRisk($donors),
            'optimal_ask_amounts' => $this->predictOptimalAskAmounts($donors),
            'best_contact_timing' => $this->predictBestContactTiming($donors),
            'campaign_affinity' => $this->predictCampaignAffinity($donors),
            'peer_influence_network' => $this->analyzePeerInfluenceNetwork($donors),
        ];
    }

    /**
     * Get comprehensive fundraising dashboard data
     */
    public function getFundraisingDashboard(array $filters = []): array
    {
        return [
            'overview_metrics' => $this->getOverviewMetrics($filters),
            'giving_patterns' => $this->getGivingPatternAnalysis($filters),
            'campaign_performance' => $this->getAllCampaignPerformance($filters),
            'donor_analytics' => $this->getDonorAnalytics($filters),
            'predictive_insights' => $this->getPredictiveAnalytics($filters),
            'trends' => $this->getFundraisingTrends($filters),
        ];
    }

    // Private helper methods

    private function calculateMedian(Collection $values): float
    {
        $sorted = $values->sort()->values();
        $count = $sorted->count();
        
        if ($count === 0) {
            return 0;
        }
        
        if ($count % 2 === 0) {
            return ($sorted[$count / 2 - 1] + $sorted[$count / 2]) / 2;
        }
        
        return $sorted[intval($count / 2)];
    }

    private function analyzeGivingFrequency(Collection $donations): array
    {
        $donorFrequency = $donations->groupBy('donor_id')
            ->map(function ($donorDonations) {
                return $donorDonations->count();
            });
            
        return [
            'one_time_donors' => $donorFrequency->filter(fn($count) => $count === 1)->count(),
            'repeat_donors' => $donorFrequency->filter(fn($count) => $count > 1)->count(),
            'average_gifts_per_donor' => $donorFrequency->avg(),
            'most_frequent_donor_gifts' => $donorFrequency->max(),
        ];
    }

    private function analyzeSeasonalPatterns(Collection $donations): array
    {
        $monthlyData = $donations->groupBy(function ($donation) {
            return $donation->processed_at->format('Y-m');
        })->map(function ($monthDonations) {
            return [
                'count' => $monthDonations->count(),
                'amount' => $monthDonations->sum('amount'),
            ];
        });
        
        $quarterlyData = $donations->groupBy(function ($donation) {
            return 'Q' . $donation->processed_at->quarter . ' ' . $donation->processed_at->year;
        })->map(function ($quarterDonations) {
            return [
                'count' => $quarterDonations->count(),
                'amount' => $quarterDonations->sum('amount'),
            ];
        });
        
        return [
            'monthly' => $monthlyData,
            'quarterly' => $quarterlyData,
            'peak_month' => $monthlyData->sortByDesc('amount')->keys()->first(),
            'peak_quarter' => $quarterlyData->sortByDesc('amount')->keys()->first(),
        ];
    }

    private function analyzeDonorRetention(Collection $donations): array
    {
        $donorsByYear = $donations->groupBy(function ($donation) {
            return $donation->processed_at->year;
        })->map(function ($yearDonations) {
            return $yearDonations->pluck('donor_id')->unique();
        });
        
        $retentionRates = [];
        $years = $donorsByYear->keys()->sort();
        
        for ($i = 0; $i < $years->count() - 1; $i++) {
            $currentYear = $years[$i];
            $nextYear = $years[$i + 1];
            
            $currentYearDonors = $donorsByYear[$currentYear];
            $nextYearDonors = $donorsByYear[$nextYear];
            
            $retained = $currentYearDonors->intersect($nextYearDonors)->count();
            $retentionRate = $currentYearDonors->count() > 0 ? 
                ($retained / $currentYearDonors->count()) * 100 : 0;
                
            $retentionRates["{$currentYear}-{$nextYear}"] = $retentionRate;
        }
        
        return [
            'retention_rates' => $retentionRates,
            'average_retention_rate' => collect($retentionRates)->avg(),
        ];
    }

    private function analyzeGiftSizeDistribution(Collection $donations): array
    {
        $amounts = $donations->pluck('amount');
        
        return [
            'under_100' => $amounts->filter(fn($amount) => $amount < 100)->count(),
            '100_to_500' => $amounts->filter(fn($amount) => $amount >= 100 && $amount < 500)->count(),
            '500_to_1000' => $amounts->filter(fn($amount) => $amount >= 500 && $amount < 1000)->count(),
            '1000_to_5000' => $amounts->filter(fn($amount) => $amount >= 1000 && $amount < 5000)->count(),
            '5000_plus' => $amounts->filter(fn($amount) => $amount >= 5000)->count(),
        ];
    }

    private function analyzeRecurringVsOneTime(Collection $donations): array
    {
        $recurring = $donations->where('is_recurring', true);
        $oneTime = $donations->where('is_recurring', false);
        
        return [
            'recurring_count' => $recurring->count(),
            'recurring_amount' => $recurring->sum('amount'),
            'one_time_count' => $oneTime->count(),
            'one_time_amount' => $oneTime->sum('amount'),
            'recurring_percentage' => $donations->count() > 0 ? 
                ($recurring->count() / $donations->count()) * 100 : 0,
        ];
    }

    private function analyzeCampaignTypePerformance(Collection $donations): array
    {
        return $donations->groupBy('campaign.type')
            ->map(function ($typeDonations) {
                return [
                    'count' => $typeDonations->count(),
                    'amount' => $typeDonations->sum('amount'),
                    'average' => $typeDonations->avg('amount'),
                ];
            });
    }

    private function estimateAcquisitionCost(FundraisingCampaign $campaign): float
    {
        // Simplified estimation based on campaign type and size
        $baseCost = match ($campaign->type) {
            'general' => 500,
            'scholarship' => 1000,
            'emergency' => 200,
            'project' => 1500,
            default => 500,
        };
        
        // Scale based on goal amount
        $scaleFactor = min(5, $campaign->goal_amount / 10000);
        
        return $baseCost * $scaleFactor;
    }

    private function getSocialShareCount(FundraisingCampaign $campaign): int
    {
        // This would integrate with actual social media tracking
        // For now, return a placeholder
        return rand(10, 100);
    }

    private function getPageViewCount(FundraisingCampaign $campaign): int
    {
        // This would integrate with actual analytics tracking
        // For now, return a placeholder
        return rand(100, 1000);
    }

    private function getCampaignTimelinePerformance(FundraisingCampaign $campaign): array
    {
        $donations = $campaign->donations()->completed()
            ->orderBy('processed_at')
            ->get();
            
        $timeline = [];
        $runningTotal = 0;
        
        foreach ($donations as $donation) {
            $runningTotal += $donation->amount;
            $date = $donation->processed_at->format('Y-m-d');
            
            if (!isset($timeline[$date])) {
                $timeline[$date] = [
                    'date' => $date,
                    'daily_amount' => 0,
                    'daily_count' => 0,
                    'cumulative_amount' => 0,
                ];
            }
            
            $timeline[$date]['daily_amount'] += $donation->amount;
            $timeline[$date]['daily_count']++;
            $timeline[$date]['cumulative_amount'] = $runningTotal;
        }
        
        return array_values($timeline);
    }

    private function analyzeDonorTiers(Collection $donors): array
    {
        return $donors->groupBy('donor_tier')
            ->map(function ($tierDonors) {
                return [
                    'count' => $tierDonors->count(),
                    'total_giving' => $tierDonors->sum('lifetime_giving'),
                    'average_giving' => $tierDonors->avg('lifetime_giving'),
                ];
            });
    }

    private function calculateEngagementScores(Collection $donors): array
    {
        $scores = $donors->map(function ($donor) {
            return $donor->engagement_score;
        });
        
        return [
            'average_score' => $scores->avg(),
            'high_engagement' => $scores->filter(fn($score) => $score >= 80)->count(),
            'medium_engagement' => $scores->filter(fn($score) => $score >= 50 && $score < 80)->count(),
            'low_engagement' => $scores->filter(fn($score) => $score < 50)->count(),
        ];
    }

    private function analyzeGivingCapacity(Collection $donors): array
    {
        return $donors->groupBy(function ($donor) {
            if ($donor->capacity_rating >= 10000) return 'major';
            if ($donor->capacity_rating >= 5000) return 'mid-level';
            if ($donor->capacity_rating >= 1000) return 'regular';
            return 'small';
        })->map(function ($capacityDonors) {
            return [
                'count' => $capacityDonors->count(),
                'average_capacity' => $capacityDonors->avg('capacity_rating'),
                'total_potential' => $capacityDonors->sum('capacity_rating'),
            ];
        });
    }

    private function analyzeContactPreferences(Collection $donors): array
    {
        $preferences = $donors->pluck('preferred_contact_methods')
            ->flatten()
            ->countBy();
            
        return $preferences->toArray();
    }

    private function analyzeGeographicDistribution(Collection $donors): array
    {
        // This would analyze based on user location data
        // Placeholder implementation
        return [
            'by_state' => [],
            'by_country' => [],
            'international_percentage' => 0,
        ];
    }

    private function analyzeWealthIndicators(Collection $donors): array
    {
        $indicators = $donors->pluck('wealth_indicators')
            ->flatten()
            ->countBy();
            
        return $indicators->toArray();
    }

    private function analyzeStewardshipPipeline(Collection $donors): array
    {
        return [
            'needs_contact' => $donors->filter(fn($donor) => $donor->next_contact_due)->count(),
            'overdue_contact' => $donors->filter(function ($donor) {
                return $donor->next_contact_date && $donor->next_contact_date < now()->subDays(7);
            })->count(),
            'active_stewardship' => $donors->whereNotNull('assigned_officer_id')->count(),
        ];
    }

    private function identifyMajorGiftProspects(Collection $donors): Collection
    {
        return $donors->filter(function ($donor) {
            return $donor->capacity_rating >= 10000 && 
                   $donor->inclination_score >= 70 &&
                   $donor->engagement_score >= 60;
        })->sortByDesc('capacity_rating')->take(20);
    }

    private function predictGivingLikelihood(Collection $donors): array
    {
        // Simplified predictive model
        return $donors->map(function ($donor) {
            $score = 0;
            
            // Recent giving history
            $recentGifts = $donor->donations()
                ->where('created_at', '>=', now()->subYear())
                ->count();
            $score += min(40, $recentGifts * 10);
            
            // Engagement score
            $score += $donor->engagement_score * 0.3;
            
            // Capacity vs current giving
            if ($donor->capacity_rating > 0 && $donor->lifetime_giving > 0) {
                $utilizationRate = $donor->lifetime_giving / $donor->capacity_rating;
                if ($utilizationRate < 0.5) {
                    $score += 20; // High potential
                }
            }
            
            return [
                'donor_id' => $donor->user_id,
                'likelihood_score' => min(100, $score),
                'category' => $score >= 80 ? 'high' : ($score >= 50 ? 'medium' : 'low'),
            ];
        })->sortByDesc('likelihood_score')->values()->toArray();
    }

    private function predictUpgradePotential(Collection $donors): array
    {
        return $donors->filter(function ($donor) {
            return $donor->capacity_rating > $donor->lifetime_giving * 2;
        })->map(function ($donor) {
            return [
                'donor_id' => $donor->user_id,
                'current_giving' => $donor->lifetime_giving,
                'capacity' => $donor->capacity_rating,
                'upgrade_potential' => $donor->capacity_rating - $donor->lifetime_giving,
            ];
        })->sortByDesc('upgrade_potential')->values()->toArray();
    }

    private function predictLapseRisk(Collection $donors): array
    {
        return $donors->map(function ($donor) {
            $daysSinceLastGift = $donor->donations()
                ->latest()
                ->first()
                ?->created_at
                ?->diffInDays(now()) ?? 999;
                
            $riskScore = min(100, $daysSinceLastGift / 3.65); // Risk increases over time
            
            return [
                'donor_id' => $donor->user_id,
                'days_since_last_gift' => $daysSinceLastGift,
                'risk_score' => $riskScore,
                'risk_category' => $riskScore >= 80 ? 'high' : ($riskScore >= 50 ? 'medium' : 'low'),
            ];
        })->sortByDesc('risk_score')->values()->toArray();
    }

    private function predictOptimalAskAmounts(Collection $donors): array
    {
        return $donors->map(function ($donor) {
            $averageGift = $donor->donations()->avg('amount') ?? 0;
            $largestGift = $donor->donations()->max('amount') ?? 0;
            
            // Suggest 1.5x average or 1.2x largest, whichever is higher
            $suggestedAsk = max($averageGift * 1.5, $largestGift * 1.2);
            
            // Cap at capacity rating
            $suggestedAsk = min($suggestedAsk, $donor->capacity_rating * 0.1);
            
            return [
                'donor_id' => $donor->user_id,
                'suggested_ask' => round($suggestedAsk, 2),
                'confidence' => $donor->donations()->count() >= 3 ? 'high' : 'medium',
            ];
        })->sortByDesc('suggested_ask')->values()->toArray();
    }

    private function predictBestContactTiming(Collection $donors): array
    {
        // Analyze historical giving patterns to suggest optimal contact timing
        return $donors->map(function ($donor) {
            $donations = $donor->donations()->get();
            
            if ($donations->isEmpty()) {
                return [
                    'donor_id' => $donor->user_id,
                    'best_month' => null,
                    'best_day_of_week' => null,
                    'confidence' => 'low',
                ];
            }
            
            $monthCounts = $donations->groupBy(fn($d) => $d->created_at->month)->map->count();
            $dayOfWeekCounts = $donations->groupBy(fn($d) => $d->created_at->dayOfWeek)->map->count();
            
            return [
                'donor_id' => $donor->user_id,
                'best_month' => $monthCounts->keys()->sortByDesc(fn($month) => $monthCounts[$month])->first(),
                'best_day_of_week' => $dayOfWeekCounts->keys()->sortByDesc(fn($day) => $dayOfWeekCounts[$day])->first(),
                'confidence' => $donations->count() >= 5 ? 'high' : 'medium',
            ];
        })->values()->toArray();
    }

    private function predictCampaignAffinity(Collection $donors): array
    {
        return $donors->map(function ($donor) {
            $campaignTypes = $donor->donations()
                ->with('campaign')
                ->get()
                ->pluck('campaign.type')
                ->countBy();
                
            return [
                'donor_id' => $donor->user_id,
                'preferred_types' => $campaignTypes->sortDesc()->keys()->take(3)->toArray(),
                'giving_interests' => $donor->giving_interests ?? [],
            ];
        })->values()->toArray();
    }

    private function analyzePeerInfluenceNetwork(Collection $donors): array
    {
        // Simplified peer influence analysis
        return [
            'influence_clusters' => [],
            'key_influencers' => $donors->sortByDesc('lifetime_giving')->take(10)->pluck('user_id')->toArray(),
            'network_effects' => [],
        ];
    }

    private function getOverviewMetrics(array $filters): array
    {
        $query = CampaignDonation::query()->completed();
        
        if (isset($filters['date_from'])) {
            $query->where('processed_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('processed_at', '<=', $filters['date_to']);
        }
        
        $donations = $query->get();
        
        return [
            'total_raised' => $donations->sum('amount'),
            'total_donations' => $donations->count(),
            'unique_donors' => $donations->pluck('donor_id')->unique()->count(),
            'average_gift' => $donations->avg('amount'),
            'active_campaigns' => FundraisingCampaign::active()->count(),
        ];
    }

    private function getAllCampaignPerformance(array $filters): array
    {
        $query = FundraisingCampaign::query();
        
        if (isset($filters['institution_id'])) {
            $query->where('institution_id', $filters['institution_id']);
        }
        
        return $query->get()->map(function ($campaign) {
            return $this->getCampaignPerformanceMetrics($campaign);
        })->toArray();
    }

    private function getFundraisingTrends(array $filters): array
    {
        $query = CampaignDonation::query()->completed();
        
        if (isset($filters['date_from'])) {
            $query->where('processed_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('processed_at', '<=', $filters['date_to']);
        }
        
        $donations = $query->get();
        
        $monthlyTrends = $donations->groupBy(function ($donation) {
            return $donation->processed_at->format('Y-m');
        })->map(function ($monthDonations) {
            return [
                'amount' => $monthDonations->sum('amount'),
                'count' => $monthDonations->count(),
                'unique_donors' => $monthDonations->pluck('donor_id')->unique()->count(),
            ];
        });
        
        return [
            'monthly_trends' => $monthlyTrends,
            'growth_rate' => $this->calculateGrowthRate($monthlyTrends),
            'forecasted_next_month' => $this->forecastNextMonth($monthlyTrends),
        ];
    }

    private function calculateGrowthRate(Collection $monthlyTrends): float
    {
        $values = $monthlyTrends->pluck('amount')->values();
        
        if ($values->count() < 2) {
            return 0;
        }
        
        $firstValue = $values->first();
        $lastValue = $values->last();
        
        if ($firstValue == 0) {
            return 0;
        }
        
        return (($lastValue - $firstValue) / $firstValue) * 100;
    }

    private function forecastNextMonth(Collection $monthlyTrends): array
    {
        $values = $monthlyTrends->pluck('amount')->values();
        
        if ($values->count() < 3) {
            return [
                'forecasted_amount' => $values->last() ?? 0,
                'confidence' => 'low',
            ];
        }
        
        // Simple linear trend forecast
        $recentValues = $values->slice(-3);
        $trend = ($recentValues->last() - $recentValues->first()) / 2;
        $forecast = $values->last() + $trend;
        
        return [
            'forecasted_amount' => max(0, $forecast),
            'confidence' => 'medium',
        ];
    }
}