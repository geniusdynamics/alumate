<?php

namespace App\Policies;

use App\Models\Export;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExportPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any exports.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_exports');
    }

    /**
     * Determine whether the user can view the export.
     *
     * @param User $user
     * @param Export $export
     * @return bool
     */
    public function view(User $user, Export $export): bool
    {
        return $user->tenant_id === $export->tenant_id &&
               ($user->hasPermission('view_exports') || $user->id === $export->user_id);
    }

    /**
     * Determine whether the user can create exports.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_exports');
    }

    /**
     * Determine whether the user can update the export.
     *
     * @param User $user
     * @param Export $export
     * @return bool
     */
    public function update(User $user, Export $export): bool
    {
        return $user->tenant_id === $export->tenant_id &&
               ($user->hasPermission('manage_exports') || $user->id === $export->user_id);
    }

    /**
     * Determine whether the user can delete the export.
     *
     * @param User $user
     * @param Export $export
     * @return bool
     */
    public function delete(User $user, Export $export): bool
    {
        return $user->tenant_id === $export->tenant_id &&
               ($user->hasPermission('manage_exports') || $user->id === $export->user_id);
    }

    /**
     * Determine whether the user can restore the export.
     *
     * @param User $user
     * @param Export $export
     * @return bool
     */
    public function restore(User $user, Export $export): bool
    {
        return $user->tenant_id === $export->tenant_id &&
               $user->hasPermission('manage_exports');
    }

    /**
     * Determine whether the user can permanently delete the export.
     *
     * @param User $user
     * @param Export $export
     * @return bool
     */
    public function forceDelete(User $user, Export $export): bool
    {
        return $user->tenant_id === $export->tenant_id &&
               $user->hasPermission('manage_exports');
    }
}