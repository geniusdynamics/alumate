<?php

namespace App\Policies;

use App\Models\RecurringDonation;
use App\Models\User;

class RecurringDonationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'fundraising_manager']);
    }

    public function view(User $user, RecurringDonation $recurringDonation): bool
    {
        return $user->hasRole(['admin', 'fundraising_manager']) ||
               $user->id === $recurringDonation->donor_id;
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create recurring donations
    }

    public function update(User $user, RecurringDonation $recurringDonation): bool
    {
        return $user->hasRole(['admin', 'fundraising_manager']) ||
               $user->id === $recurringDonation->donor_id;
    }

    public function delete(User $user, RecurringDonation $recurringDonation): bool
    {
        return $user->hasRole(['admin', 'fundraising_manager']) ||
               $user->id === $recurringDonation->donor_id;
    }
}
