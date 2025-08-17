<?php

namespace App\Policies;

use App\Models\EmailCampaign;
use App\Models\User;

class EmailCampaignPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Allow all authenticated users to view campaigns
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EmailCampaign $emailCampaign): bool
    {
        return $user->tenant_id === $emailCampaign->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Allow all authenticated users to create campaigns
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EmailCampaign $emailCampaign): bool
    {
        return $user->tenant_id === $emailCampaign->tenant_id &&
               $emailCampaign->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmailCampaign $emailCampaign): bool
    {
        return $user->tenant_id === $emailCampaign->tenant_id &&
               $emailCampaign->created_by === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EmailCampaign $emailCampaign): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EmailCampaign $emailCampaign): bool
    {
        return false;
    }
}
