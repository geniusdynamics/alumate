<?php

namespace App\Policies;

use App\Models\FundraisingCampaign;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FundraisingCampaignPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view campaigns
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FundraisingCampaign $fundraisingCampaign): bool
    {
        return true; // All authenticated users can view individual campaigns
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin') || 
               $user->hasRole('institution_admin') || 
               $user->hasRole('alumni');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FundraisingCampaign $fundraisingCampaign): bool
    {
        return $user->id === $fundraisingCampaign->created_by || 
               $user->hasRole('admin') || 
               $user->hasRole('institution_admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FundraisingCampaign $fundraisingCampaign): bool
    {
        return $user->id === $fundraisingCampaign->created_by || 
               $user->hasRole('admin') || 
               $user->hasRole('institution_admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, FundraisingCampaign $fundraisingCampaign): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, FundraisingCampaign $fundraisingCampaign): bool
    {
        return false;
    }
}
