<?php

namespace App\Policies;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BackupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any backups.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_backups');
    }

    /**
     * Determine whether the user can view the backup.
     *
     * @param User $user
     * @param Backup $backup
     * @return bool
     */
    public function view(User $user, Backup $backup): bool
    {
        return $user->tenant_id === $backup->tenant_id &&
               ($user->hasPermission('view_backups') || $user->id === $backup->user_id);
    }

    /**
     * Determine whether the user can create backups.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_backups');
    }

    /**
     * Determine whether the user can update the backup.
     *
     * @param User $user
     * @param Backup $backup
     * @return bool
     */
    public function update(User $user, Backup $backup): bool
    {
        return $user->tenant_id === $backup->tenant_id &&
               ($user->hasPermission('manage_backups') || $user->id === $backup->user_id);
    }

    /**
     * Determine whether the user can delete the backup.
     *
     * @param User $user
     * @param Backup $backup
     * @return bool
     */
    public function delete(User $user, Backup $backup): bool
    {
        return $user->tenant_id === $backup->tenant_id &&
               ($user->hasPermission('manage_backups') || $user->id === $backup->user_id);
    }

    /**
     * Determine whether the user can restore the backup.
     *
     * @param User $user
     * @param Backup $backup
     * @return bool
     */
    public function restore(User $user, Backup $backup): bool
    {
        return $user->tenant_id === $backup->tenant_id &&
               $user->hasPermission('restore_backups');
    }

    /**
     * Determine whether the user can permanently delete the backup.
     *
     * @param User $user
     * @param Backup $backup
     * @return bool
     */
    public function forceDelete(User $user, Backup $backup): bool
    {
        return $user->tenant_id === $backup->tenant_id &&
               $user->hasPermission('manage_backups');
    }
}