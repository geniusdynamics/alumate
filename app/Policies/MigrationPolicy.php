<?php

namespace App\Policies;

use App\Models\Migration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MigrationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any migrations.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_migrations');
    }

    /**
     * Determine whether the user can view the migration.
     *
     * @param User $user
     * @param Migration $migration
     * @return bool
     */
    public function view(User $user, Migration $migration): bool
    {
        return $user->tenant_id === $migration->tenant_id &&
               ($user->hasPermission('view_migrations') || $user->id === $migration->user_id);
    }

    /**
     * Determine whether the user can create migrations.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_migrations');
    }

    /**
     * Determine whether the user can update the migration.
     *
     * @param User $user
     * @param Migration $migration
     * @return bool
     */
    public function update(User $user, Migration $migration): bool
    {
        return $user->tenant_id === $migration->tenant_id &&
               ($user->hasPermission('manage_migrations') || $user->id === $migration->user_id);
    }

    /**
     * Determine whether the user can execute the migration.
     *
     * @param User $user
     * @param Migration $migration
     * @return bool
     */
    public function execute(User $user, Migration $migration): bool
    {
        return $user->tenant_id === $migration->tenant_id &&
               $user->hasPermission('execute_migrations');
    }

    /**
     * Determine whether the user can delete the migration.
     *
     * @param User $user
     * @param Migration $migration
     * @return bool
     */
    public function delete(User $user, Migration $migration): bool
    {
        return $user->tenant_id === $migration->tenant_id &&
               ($user->hasPermission('manage_migrations') || $user->id === $migration->user_id);
    }

    /**
     * Determine whether the user can restore the migration.
     *
     * @param User $user
     * @param Migration $migration
     * @return bool
     */
    public function restore(User $user, Migration $migration): bool
    {
        return $user->tenant_id === $migration->tenant_id &&
               $user->hasPermission('manage_migrations');
    }

    /**
     * Determine whether the user can permanently delete the migration.
     *
     * @param User $user
     * @param Migration $migration
     * @return bool
     */
    public function forceDelete(User $user, Migration $migration): bool
    {
        return $user->tenant_id === $migration->tenant_id &&
               $user->hasPermission('manage_migrations');
    }
}