<?php

namespace App\Policies;

use App\Models\CampaignDonation;
use App\Models\User;

class CampaignDonationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'fundraising_manager']);
    }

    public function view(User $user, CampaignDonation $donation): bool
    {
        return $user->hasRole(['admin', 'fundraising_manager']) ||
               $user->id === $donation->donor_id;
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can make donations
    }

    public function update(User $user, CampaignDonation $donation): bool
    {
        return $user->hasRole(['admin', 'fundraising_manager']);
    }

    public function delete(User $user, CampaignDonation $donation): bool
    {
        return $user->hasRole(['admin']);
    }

    public function refund(User $user, CampaignDonation $donation): bool
    {
        return $user->hasRole(['admin', 'fundraising_manager']);
    }
}
