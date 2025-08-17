<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class JobPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any jobs.
     */
    public function viewAny(User $user)
    {
        return $user->hasAnyRole(['super-admin', 'institution-admin', 'employer']);
    }

    /**
     * Determine whether the user can view the job.
     */
    public function view(User $user, Job $job)
    {
        // Super admins can view all jobs
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Institution admins can view jobs in their institution
        if ($user->hasRole('institution-admin')) {
            return $job->employer->institution_id === $user->institution_id;
        }

        // Employers can view their own jobs
        if ($user->hasRole('employer') && $user->employer) {
            return $job->employer_id === $user->employer->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create jobs.
     */
    public function create(User $user)
    {
        return $user->hasRole('employer') && $user->employer;
    }

    /**
     * Determine whether the user can update the job.
     */
    public function update(User $user, Job $job)
    {
        // Super admins can update all jobs
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Employers can update their own jobs
        if ($user->hasRole('employer') && $user->employer) {
            return $job->employer_id === $user->employer->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the job.
     */
    public function delete(User $user, Job $job)
    {
        // Super admins can delete all jobs
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Employers can delete their own jobs
        if ($user->hasRole('employer') && $user->employer) {
            return $job->employer_id === $user->employer->id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage job approvals.
     */
    public function manageApprovals(User $user)
    {
        return $user->hasRole('super-admin') || $user->hasRole('institution-admin');
    }

    /**
     * Determine whether the user can approve jobs.
     */
    public function approve(User $user, Job $job)
    {
        return $this->manageApprovals($user);
    }

    /**
     * Determine whether the user can reject jobs.
     */
    public function reject(User $user, Job $job)
    {
        return $this->manageApprovals($user);
    }
}
