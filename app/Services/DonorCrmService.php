<?php

namespace App\Services;

use App\Models\CampaignDonation;
use App\Models\DonorInteraction;
use App\Models\DonorProfile;
use App\Models\DonorStewardshipPlan;
use App\Models\MajorGiftProspect;
use App\Models\User;
use Illuminate\Support\Collection;

class DonorCrmService
{
    public function createDonorProfile(User $user, array $data): DonorProfile
    {
        // Calculate initial metrics from existing donations
        $donations = CampaignDonation::where('user_id', $user->id)->get();
        $lifetimeGiving = $donations->sum('amount');
        $largestGift = $donations->max('amount') ?? 0;

        // Determine initial tier based on giving history
        $tier = $this->determineDonorTier($lifetimeGiving);

        return DonorProfile::create(array_merge($data, [
            'user_id' => $user->id,
            'donor_tier' => $tier,
            'lifetime_giving' => $lifetimeGiving,
            'largest_gift' => $largestGift,
        ]));
    }

    public function updateDonorProfile(DonorProfile $profile, array $data): DonorProfile
    {
        $profile->update($data);

        // Recalculate tier if giving amounts changed
        if (isset($data['lifetime_giving'])) {
            $profile->update([
                'donor_tier' => $this->determineDonorTier($data['lifetime_giving']),
            ]);
        }

        return $profile->fresh();
    }

    public function logInteraction(DonorProfile $profile, User $staff, array $data): DonorInteraction
    {
        $interaction = DonorInteraction::create(array_merge($data, [
            'donor_profile_id' => $profile->id,
            'user_id' => $staff->id,
        ]));

        // Update last contact date on profile
        $profile->update([
            'last_contact_date' => $data['interaction_date'],
            'next_contact_date' => $data['next_follow_up_date'] ?? null,
        ]);

        return $interaction;
    }

    public function createStewardshipPlan(DonorProfile $profile, User $creator, array $data): DonorStewardshipPlan
    {
        return DonorStewardshipPlan::create(array_merge($data, [
            'donor_profile_id' => $profile->id,
            'created_by' => $creator->id,
        ]));
    }

    public function createMajorGiftProspect(DonorProfile $profile, User $officer, array $data): MajorGiftProspect
    {
        return MajorGiftProspect::create(array_merge($data, [
            'donor_profile_id' => $profile->id,
            'assigned_officer_id' => $officer->id,
        ]));
    }

    public function getDonorDashboard(User $officer): array
    {
        $assignedProfiles = DonorProfile::where('assigned_officer_id', $officer->id)->get();

        return [
            'total_donors' => $assignedProfiles->count(),
            'major_donors' => $assignedProfiles->where('donor_tier', 'major')->count(),
            'principal_donors' => $assignedProfiles->where('donor_tier', 'principal')->count(),
            'contacts_due' => $assignedProfiles->where('next_contact_date', '<=', now())->count(),
            'total_portfolio_value' => $assignedProfiles->sum('lifetime_giving'),
            'active_prospects' => MajorGiftProspect::where('assigned_officer_id', $officer->id)
                ->active()
                ->count(),
            'prospects_closing_soon' => MajorGiftProspect::where('assigned_officer_id', $officer->id)
                ->closingSoon()
                ->count(),
            'pipeline_value' => MajorGiftProspect::where('assigned_officer_id', $officer->id)
                ->active()
                ->sum('ask_amount'),
            'weighted_pipeline' => MajorGiftProspect::where('assigned_officer_id', $officer->id)
                ->active()
                ->get()
                ->sum('weighted_value'),
        ];
    }

    public function getContactsNeedingAttention(User $officer): Collection
    {
        return DonorProfile::where('assigned_officer_id', $officer->id)
            ->needsContact()
            ->with(['user', 'interactions' => function ($query) {
                $query->latest()->limit(3);
            }])
            ->orderBy('next_contact_date')
            ->get();
    }

    public function getProspectPipeline(User $officer): Collection
    {
        return MajorGiftProspect::where('assigned_officer_id', $officer->id)
            ->active()
            ->with(['donorProfile.user'])
            ->orderBy('expected_close_date')
            ->get()
            ->groupBy('stage');
    }

    public function generateDonorInsights(DonorProfile $profile): array
    {
        $interactions = $profile->interactions()->recent(365)->get();
        $donations = $profile->donations()->where('created_at', '>=', now()->subYear())->get();

        return [
            'engagement_score' => $profile->engagement_score,
            'giving_trend' => $this->calculateGivingTrend($donations),
            'interaction_frequency' => $interactions->count(),
            'preferred_contact_method' => $this->getPreferredContactMethod($interactions),
            'best_contact_time' => $this->getBestContactTime($interactions),
            'giving_seasonality' => $this->analyzeGivingSeasonality($donations),
            'response_rate' => $this->calculateResponseRate($interactions),
            'next_ask_readiness' => $this->assessAskReadiness($profile),
        ];
    }

    public function bulkUpdateProfiles(array $profileIds, array $updates): int
    {
        return DonorProfile::whereIn('id', $profileIds)->update($updates);
    }

    public function searchDonors(array $filters): Collection
    {
        $query = DonorProfile::with(['user', 'assignedOfficer']);

        if (isset($filters['tier'])) {
            $query->where('donor_tier', $filters['tier']);
        }

        if (isset($filters['officer_id'])) {
            $query->where('assigned_officer_id', $filters['officer_id']);
        }

        if (isset($filters['capacity_min'])) {
            $query->where('capacity_rating', '>=', $filters['capacity_min']);
        }

        if (isset($filters['capacity_max'])) {
            $query->where('capacity_rating', '<=', $filters['capacity_max']);
        }

        if (isset($filters['giving_interests'])) {
            $query->whereJsonContains('giving_interests', $filters['giving_interests']);
        }

        if (isset($filters['needs_contact']) && $filters['needs_contact']) {
            $query->needsContact();
        }

        return $query->orderBy('lifetime_giving', 'desc')->get();
    }

    private function determineDonorTier(float $lifetimeGiving): string
    {
        return match (true) {
            $lifetimeGiving >= 1000000 => 'legacy',
            $lifetimeGiving >= 100000 => 'principal',
            $lifetimeGiving >= 10000 => 'major',
            default => 'prospect'
        };
    }

    private function calculateGivingTrend(Collection $donations): string
    {
        if ($donations->count() < 2) {
            return 'insufficient_data';
        }

        $recent = $donations->where('created_at', '>=', now()->subMonths(6))->sum('amount');
        $previous = $donations->where('created_at', '<', now()->subMonths(6))
            ->where('created_at', '>=', now()->subYear())
            ->sum('amount');

        if ($previous == 0) {
            return $recent > 0 ? 'increasing' : 'stable';
        }

        $change = ($recent - $previous) / $previous;

        return match (true) {
            $change > 0.2 => 'increasing',
            $change < -0.2 => 'decreasing',
            default => 'stable'
        };
    }

    private function getPreferredContactMethod(Collection $interactions): ?string
    {
        return $interactions->groupBy('type')
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();
    }

    private function getBestContactTime(Collection $interactions): ?string
    {
        $successful = $interactions->whereIn('outcome', ['positive', 'neutral']);

        if ($successful->isEmpty()) {
            return null;
        }

        // Analyze interaction dates to find patterns
        $dayOfWeek = $successful->groupBy(function ($interaction) {
            return $interaction->interaction_date->format('l');
        })->map->count()->sortDesc()->keys()->first();

        return $dayOfWeek;
    }

    private function analyzeGivingSeasonality(Collection $donations): array
    {
        return $donations->groupBy(function ($donation) {
            return $donation->created_at->format('M');
        })->map->sum('amount')->toArray();
    }

    private function calculateResponseRate(Collection $interactions): float
    {
        $total = $interactions->count();
        if ($total === 0) {
            return 0;
        }

        $positive = $interactions->whereIn('outcome', ['positive', 'neutral'])->count();

        return round(($positive / $total) * 100, 1);
    }

    private function assessAskReadiness(DonorProfile $profile): string
    {
        $score = 0;

        // Recent engagement
        if ($profile->interactions()->recent(90)->count() >= 3) {
            $score += 25;
        }

        // Positive interactions
        $recentPositive = $profile->interactions()
            ->recent(180)
            ->where('outcome', 'positive')
            ->count();

        if ($recentPositive >= 2) {
            $score += 25;
        }

        // Giving history
        if ($profile->lifetime_giving > 0) {
            $score += 25;
        }

        // Capacity and inclination
        if ($profile->capacity_rating && $profile->inclination_score) {
            if ($profile->capacity_rating >= 50000 && $profile->inclination_score >= 0.6) {
                $score += 25;
            }
        }

        return match (true) {
            $score >= 75 => 'high',
            $score >= 50 => 'medium',
            $score >= 25 => 'low',
            default => 'not_ready'
        };
    }
}
