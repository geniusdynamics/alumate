<?php

namespace App\Policies;

use App\Models\CalendarConnection;
use App\Models\User;

class CalendarConnectionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own calendar connections
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CalendarConnection $calendarConnection): bool
    {
        return $user->id === $calendarConnection->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Users can create their own calendar connections
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CalendarConnection $calendarConnection): bool
    {
        return $user->id === $calendarConnection->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CalendarConnection $calendarConnection): bool
    {
        return $user->id === $calendarConnection->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CalendarConnection $calendarConnection): bool
    {
        return $user->id === $calendarConnection->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CalendarConnection $calendarConnection): bool
    {
        return $user->id === $calendarConnection->user_id;
    }
}
