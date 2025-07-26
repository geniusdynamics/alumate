<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage-users');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Super admins can view all users
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Users can view themselves
        if ($user->id === $model->id) {
            return true;
        }

        // Institution admins can view users in their institution
        if ($user->hasRole('institution-admin') && $user->institution_id === $model->institution_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage-users');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Super admins can update all users
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Users can update themselves (limited fields)
        if ($user->id === $model->id) {
            return true;
        }

        // Institution admins can update users in their institution
        if ($user->hasRole('institution-admin') && 
            $user->institution_id === $model->institution_id &&
            $user->hasPermissionTo('manage-users')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Super admins can delete all users
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Institution admins can delete users in their institution (except other admins)
        if ($user->hasRole('institution-admin') && 
            $user->institution_id === $model->institution_id &&
            !$model->hasRole('institution-admin') &&
            $user->hasPermissionTo('manage-users')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can suspend the model.
     */
    public function suspend(User $user, User $model): bool
    {
        // Users cannot suspend themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Super admins can suspend all users
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Institution admins can suspend users in their institution (except other admins)
        if ($user->hasRole('institution-admin') && 
            $user->institution_id === $model->institution_id &&
            !$model->hasRole('institution-admin') &&
            $user->hasPermissionTo('manage-users')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage roles for the model.
     */
    public function manageRoles(User $user, User $model): bool
    {
        // Super admins can manage all roles
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Institution admins can manage roles for users in their institution (limited roles)
        if ($user->hasRole('institution-admin') && 
            $user->institution_id === $model->institution_id &&
            $user->hasPermissionTo('manage-users')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view activity logs for the model.
     */
    public function viewActivityLogs(User $user, User $model): bool
    {
        // Super admins can view all activity logs
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Users can view their own activity logs
        if ($user->id === $model->id) {
            return true;
        }

        // Institution admins can view activity logs for users in their institution
        if ($user->hasRole('institution-admin') && 
            $user->institution_id === $model->institution_id) {
            return true;
        }

        return false;
    }
}