<?php

namespace App\Services;

use App\Models\CampaignDonation;
use App\Models\CampaignUpdate;
use App\Models\FundraisingCampaign;
use App\Models\PeerFundraiser;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FundraisingService
{
    public function createCampaign(array $data, User $creator): FundraisingCampaign
    {
        return DB::transaction(function () use ($data, $creator) {
            $campaign = FundraisingCampaign::create([
                ...$data,
                'created_by' => $creator->id,
                'raised_amount' => 0,
                'donor_count' => 0,
            ]);

            // Create initial update if story is provided
            if (! empty($data['story'])) {
                $this->createUpdate($campaign, $creator, [
                    'title' => 'Campaign Launch',
                    'content' => $data['story'],
                    'published_at' => now(),
                ]);
            }

            return $campaign;
        });
    }

    public function updateCampaign(FundraisingCampaign $campaign, array $data): FundraisingCampaign
    {
        $campaign->update($data);

        return $campaign->fresh();
    }

    public function createUpdate(FundraisingCampaign $campaign, User $author, array $data): CampaignUpdate
    {
        return CampaignUpdate::create([
            'campaign_id' => $campaign->id,
            'created_by' => $author->id,
            ...$data,
        ]);
    }

    public function processDonation(array $donationData): CampaignDonation
    {
        return DB::transaction(function () use ($donationData) {
            $donation = CampaignDonation::create($donationData);

            // Update campaign totals
            $this->updateCampaignTotals($donation->campaign);

            // Update peer fundraiser totals if applicable
            if ($donation->peer_fundraiser_id) {
                $this->updatePeerFundraiserTotals($donation->peerFundraiser);
            }

            return $donation;
        });
    }

    public function createPeerFundraiser(FundraisingCampaign $campaign, User $user, array $data): PeerFundraiser
    {
        if (! $campaign->allow_peer_fundraising) {
            throw new \Exception('Peer fundraising is not allowed for this campaign.');
        }

        return PeerFundraiser::create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            ...$data,
            'raised_amount' => 0,
            'donor_count' => 0,
        ]);
    }

    public function getCampaignAnalytics(FundraisingCampaign $campaign): array
    {
        $donations = $campaign->donations()->completed()->get();
        $peerFundraisers = $campaign->peerFundraisers;

        return [
            'total_raised' => $campaign->raised_amount,
            'goal_amount' => $campaign->goal_amount,
            'progress_percentage' => $campaign->progress_percentage,
            'donor_count' => $campaign->donor_count,
            'average_donation' => $donations->count() > 0 ? $donations->avg('amount') : 0,
            'largest_donation' => $donations->max('amount') ?? 0,
            'recent_donations' => $donations->recent()->take(10),
            'peer_fundraisers_count' => $peerFundraisers->count(),
            'active_peer_fundraisers' => $peerFundraisers->where('status', 'active')->count(),
            'peer_fundraising_total' => $peerFundraisers->sum('raised_amount'),
            'donation_frequency' => $this->getDonationFrequency($donations),
            'top_peer_fundraisers' => $peerFundraisers->sortByDesc('raised_amount')->take(5),
        ];
    }

    public function getDonationFrequency(Collection $donations): array
    {
        $frequency = [];
        $donations->groupBy(function ($donation) {
            return $donation->processed_at->format('Y-m-d');
        })->each(function ($dayDonations, $date) use (&$frequency) {
            $frequency[$date] = [
                'count' => $dayDonations->count(),
                'amount' => $dayDonations->sum('amount'),
            ];
        });

        return $frequency;
    }

    public function getTopDonors(FundraisingCampaign $campaign, int $limit = 10): Collection
    {
        return $campaign->donations()
            ->completed()
            ->where('is_anonymous', false)
            ->with('donor')
            ->select('donor_id', DB::raw('SUM(amount) as total_donated'), DB::raw('COUNT(*) as donation_count'))
            ->groupBy('donor_id')
            ->orderByDesc('total_donated')
            ->limit($limit)
            ->get();
    }

    public function getRecentDonations(FundraisingCampaign $campaign, int $limit = 20): Collection
    {
        return $campaign->donations()
            ->completed()
            ->with(['donor', 'peerFundraiser.user'])
            ->recent()
            ->limit($limit)
            ->get();
    }

    public function generateSocialShareContent(FundraisingCampaign $campaign): array
    {
        $progress = round($campaign->progress_percentage);
        $raised = number_format($campaign->raised_amount, 2);
        $goal = number_format($campaign->goal_amount, 2);

        return [
            'title' => $campaign->title,
            'description' => "Help us reach our goal! We've raised ${raised} of our ${goal} target ({$progress}% complete).",
            'url' => route('campaigns.show', $campaign),
            'image' => $campaign->media_urls[0] ?? null,
            'hashtags' => ['fundraising', 'alumni', 'giving'],
        ];
    }

    protected function updateCampaignTotals(FundraisingCampaign $campaign): void
    {
        $totals = $campaign->donations()
            ->completed()
            ->selectRaw('SUM(amount) as total_amount, COUNT(DISTINCT COALESCE(donor_id, donor_email)) as unique_donors')
            ->first();

        $campaign->update([
            'raised_amount' => $totals->total_amount ?? 0,
            'donor_count' => $totals->unique_donors ?? 0,
        ]);
    }

    protected function updatePeerFundraiserTotals(PeerFundraiser $peerFundraiser): void
    {
        $totals = $peerFundraiser->donations()
            ->completed()
            ->selectRaw('SUM(amount) as total_amount, COUNT(DISTINCT COALESCE(donor_id, donor_email)) as unique_donors')
            ->first();

        $peerFundraiser->update([
            'raised_amount' => $totals->total_amount ?? 0,
            'donor_count' => $totals->unique_donors ?? 0,
        ]);
    }
}
