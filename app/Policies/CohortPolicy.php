<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Cohort;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

/**
 * Cohort Policy
 *
 * Defines authorization rules for cohort operations.
 * Ensures tenant isolation and proper role-based access control using Spatie permissions.
 */
class CohortPolicy
{
    use HandlesAuthorization;

    /**
     * Register our custom permissions with the Gate.
     * This ensures consistent authorization across the application.
     */
    public function __construct()
    {
        // Define custom cohort comparison permission
        Gate::define('cohort.compare', function (User $user) {
            return $this->canCompareCohorts($user);
        });

        // Define custom cohort analytics permission
        Gate::define('cohort.viewAnalytics', function (User $user, Cohort $cohort) {
            return $this->viewAnalytics($user, $cohort);
        });
    }

    /**
     * Determine whether user can view any cohorts.
     */
    public function viewAny(User $user): bool
    {
        // Super admins can view all cohorts
        if ($user->is_super_admin) {
            return true;
        }

        // Users with analytics permission can view cohorts in their tenant
        return $this->canViewCohorts($user);
    }

    /**
     * Determine whether user can view a specific cohort.
     */
    public function view(User $user, Cohort $cohort): bool
    {
        // Super admins can view any cohort
        if ($user->is_super_admin) {
            return true;
        }

        // Check if user has analytics permission
        if (!$this->canViewCohorts($user)) {
            return false;
        }

        // Check if cohort belongs to user's current tenant
        $currentTenant = app(\App\Services\TenantContextService::class)->getCurrentTenant();
        if (!$currentTenant) {
            return false;
        }

        return (string) $cohort->tenant_id === (string) $currentTenant->id;
    }

    /**
     * Determine whether user can create cohorts.
     */
    public function create(User $user): bool
    {
        // Super admins can create cohorts in any tenant
        if ($user->is_super_admin) {
            return true;
        }

        // Users with analytics permission can create cohorts in their tenant
        return $this->canViewCohorts($user);
    }

    /**
     * Determine whether user can update a cohort.
     */
    public function update(User $user, Cohort $cohort): bool
    {
        // Super admins can update any cohort
        if ($user->is_super_admin) {
            return true;
        }

        // Check if user has analytics permission
        if (!$this->canViewCohorts($user)) {
            return false;
        }

        // Check if cohort belongs to user's current tenant
        $currentTenant = app(\App\Services\TenantContextService::class)->getCurrentTenant();
        if (!$currentTenant) {
            return false;
        }

        // Only allow updating cohorts in user's tenant
        if ((string) $cohort->tenant_id !== (string) $currentTenant->id) {
            return false;
        }

        // Tenant admins and instructors can update any cohort in their tenant
        if ($user->hasRoleInCurrentTenant(User::ROLE_TENANT_ADMIN) ||
            $user->hasRoleInCurrentTenant(User::ROLE_INSTRUCTOR)) {
            return true;
        }

        // Staff can only update cohorts they created
        if ($user->hasRoleInCurrentTenant(User::ROLE_STAFF)) {
            return (string) $cohort->created_by === (string) $user->id;
        }

        return false;
    }

    /**
     * Determine whether user can delete a cohort.
     */
    public function delete(User $user, Cohort $cohort): bool
    {
        // Super admins can delete any cohort
        if ($user->is_super_admin) {
            return true;
        }

        // Check if user has analytics permission
        if (!$this->canViewCohorts($user)) {
            return false;
        }

        // Check if cohort belongs to user's current tenant
        $currentTenant = app(\App\Services\TenantContextService::class)->getCurrentTenant();
        if (!$currentTenant) {
            return false;
        }

        // Only allow deleting cohorts in user's tenant
        if ((string) $cohort->tenant_id !== (string) $currentTenant->id) {
            return false;
        }

        // Only tenant admins and instructors can delete cohorts
        return $user->hasRoleInCurrentTenant(User::ROLE_TENANT_ADMIN) ||
               $user->hasRoleInCurrentTenant(User::ROLE_INSTRUCTOR);
    }

    /**
     * Determine whether user can compare cohorts.
     *
     * Uses Spatie's permission system for role-based access control.
     * Super admins can compare cohorts across all tenants.
     * Regular users can compare cohorts within their tenant if they have the required role.
     */
    public function compare(User $user): bool
    {
        return $this->canCompareCohorts($user);
    }

    /**
     * Core logic for comparing cohorts.
     *
     * @param User $user
     * @return bool
     */
    protected function canCompareCohorts(User $user): bool
    {
        // Super admins can compare cohorts across all tenants
        if ($user->is_super_admin) {
            return true;
        }

        // Check if user has the required role to compare cohorts
        // Roles allowed: tenant_admin, instructor, staff
        return $user->hasRoleInCurrentTenant(User::ROLE_TENANT_ADMIN) ||
               $user->hasRoleInCurrentTenant(User::ROLE_INSTRUCTOR) ||
               $user->hasRoleInCurrentTenant(User::ROLE_STAFF);
    }

    /**
     * Core logic for viewing cohorts.
     *
     * @param User $user
     * @return bool
     */
    protected function canViewCohorts(User $user): bool
    {
        // Check if user has the required role to view cohorts
        return $user->hasRoleInCurrentTenant(User::ROLE_TENANT_ADMIN) ||
               $user->hasRoleInCurrentTenant(User::ROLE_INSTRUCTOR) ||
               $user->hasRoleInCurrentTenant(User::ROLE_STAFF);
    }

    /**
     * Determine whether user can view cohort analytics.
     */
    public function viewAnalytics(User $user, Cohort $cohort): bool
    {
        // Super admins can view analytics for any cohort
        if ($user->is_super_admin) {
            return true;
        }

        // Check if user has analytics permission
        if (!$this->canViewCohorts($user)) {
            return false;
        }

        // Check if cohort belongs to user's current tenant
        $currentTenant = app(\App\Services\TenantContextService::class)->getCurrentTenant();
        if (!$currentTenant) {
            return false;
        }

        return (string) $cohort->tenant_id === (string) $currentTenant->id;
    }
}
