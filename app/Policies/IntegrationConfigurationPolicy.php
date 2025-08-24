<?php

namespace App\Policies;

use App\Models\IntegrationConfiguration;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class IntegrationConfigurationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Institution Admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, IntegrationConfiguration $integrationConfiguration): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('Institution Admin')) {
            return $integrationConfiguration->institution_id === tenant()->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Institution Admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, IntegrationConfiguration $integrationConfiguration): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('Institution Admin')) {
            return $integrationConfiguration->institution_id === tenant()->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, IntegrationConfiguration $integrationConfiguration): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('Institution Admin')) {
            return $integrationConfiguration->institution_id === tenant()->id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, IntegrationConfiguration $integrationConfiguration): bool
    {
        return $this->delete($user, $integrationConfiguration);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, IntegrationConfiguration $integrationConfiguration): bool
    {
        return $user->hasRole('Super Admin');
    }
}
