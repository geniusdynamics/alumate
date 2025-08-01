<?php

namespace App\Policies;

use App\Models\PeerFundraiser;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PeerFundraiserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PeerFundraiser $peerFundraiser): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PeerFundraiser $peerFundraiser): bool
    {
        return $user->id === $peerFundraiser->user_id || 
               $user->hasRole('admin') || 
               $user->hasRole('institution_admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PeerFundraiser $peerFundraiser): bool
    {
        return $user->id === $peerFundraiser->user_id || 
               $user->hasRole('admin') || 
               $user->hasRole('institution_admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PeerFundraiser $peerFundraiser): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PeerFundraiser $peerFundraiser): bool
    {
        return false;
    }
}
