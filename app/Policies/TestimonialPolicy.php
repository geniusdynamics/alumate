<?php

namespace App\Policies;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TestimonialPolicy
{
    /**
     * Determine whether the user can view any testimonials.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'testimonials.view',
            'testimonials.manage',
            'testimonials.moderate'
        ]);
    }

    /**
     * Determine whether the user can view the testimonial.
     */
    public function view(User $user, Testimonial $testimonial): bool
    {
        // Users can view testimonials from their own tenant
        if ($user->tenant_id !== $testimonial->tenant_id) {
            return false;
        }

        return $user->hasAnyPermission([
            'testimonials.view',
            'testimonials.manage',
            'testimonials.moderate'
        ]);
    }

    /**
     * Determine whether the user can create testimonials.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission([
            'testimonials.create',
            'testimonials.manage'
        ]);
    }

    /**
     * Determine whether the user can update the testimonial.
     */
    public function update(User $user, Testimonial $testimonial): bool
    {
        // Users can only update testimonials from their own tenant
        if ($user->tenant_id !== $testimonial->tenant_id) {
            return false;
        }

        return $user->hasAnyPermission([
            'testimonials.update',
            'testimonials.manage'
        ]);
    }

    /**
     * Determine whether the user can delete the testimonial.
     */
    public function delete(User $user, Testimonial $testimonial): bool
    {
        // Users can only delete testimonials from their own tenant
        if ($user->tenant_id !== $testimonial->tenant_id) {
            return false;
        }

        return $user->hasAnyPermission([
            'testimonials.delete',
            'testimonials.manage'
        ]);
    }

    /**
     * Determine whether the user can moderate testimonials (approve/reject/archive).
     */
    public function moderate(User $user, Testimonial $testimonial): bool
    {
        // Users can only moderate testimonials from their own tenant
        if ($user->tenant_id !== $testimonial->tenant_id) {
            return false;
        }

        return $user->hasAnyPermission([
            'testimonials.moderate',
            'testimonials.manage'
        ]);
    }

    /**
     * Determine whether the user can view analytics.
     */
    public function viewAnalytics(User $user): bool
    {
        return $user->hasAnyPermission([
            'testimonials.analytics',
            'testimonials.manage',
            'analytics.view'
        ]);
    }

    /**
     * Determine whether the user can export testimonials.
     */
    public function export(User $user): bool
    {
        return $user->hasAnyPermission([
            'testimonials.export',
            'testimonials.manage'
        ]);
    }

    /**
     * Determine whether the user can import testimonials.
     */
    public function import(User $user): bool
    {
        return $user->hasAnyPermission([
            'testimonials.import',
            'testimonials.manage'
        ]);
    }

    /**
     * Determine whether the user can restore the testimonial.
     */
    public function restore(User $user, Testimonial $testimonial): bool
    {
        // Users can only restore testimonials from their own tenant
        if ($user->tenant_id !== $testimonial->tenant_id) {
            return false;
        }

        return $user->hasAnyPermission([
            'testimonials.restore',
            'testimonials.manage'
        ]);
    }

    /**
     * Determine whether the user can permanently delete the testimonial.
     */
    public function forceDelete(User $user, Testimonial $testimonial): bool
    {
        // Users can only force delete testimonials from their own tenant
        if ($user->tenant_id !== $testimonial->tenant_id) {
            return false;
        }

        return $user->hasAnyPermission([
            'testimonials.force-delete',
            'testimonials.manage'
        ]);
    }
}