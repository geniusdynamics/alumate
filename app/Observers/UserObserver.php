<?php

namespace App\Observers;

use App\Jobs\UpdateUserCirclesJob;
use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Services\CachingStrategyService;
use App\Services\CircleManager;
use App\Services\ComponentCachingService;
use App\Services\GroupManager;
use App\Services\HeatMapService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    protected CircleManager $circleManager;

    protected GroupManager $groupManager;

    protected CachingStrategyService $cachingStrategyService;

    protected ComponentCachingService $componentCachingService;

    protected HeatMapService $heatMapService;

    public function __construct(
        CircleManager $circleManager,
        GroupManager $groupManager,
        CachingStrategyService $cachingStrategyService,
        ComponentCachingService $componentCachingService,
        HeatMapService $heatMapService
    ) {
        $this->circleManager = $circleManager;
        $this->groupManager = $groupManager;
        $this->cachingStrategyService = $cachingStrategyService;
        $this->componentCachingService = $componentCachingService;
        $this->heatMapService = $heatMapService;
    }

    /**
     * Handle the User "saving" event.
     */
    public function saving(User $user): void
    {
        try {
            // Invalidate caches when user data changes
            $this->invalidateUserCaches($user);
        } catch (\Exception $e) {
            Log::error('Failed to handle user saving event', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        try {
            // Generate circles for the new user
            $this->circleManager->generateCirclesForUser($user);

            // Track user registration analytics
            AnalyticsEvent::create([
                'event_type' => 'user_registration',
                'event_category' => 'learning_analytics',
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id ?? null,
                'event_data' => [
                    'user_type' => $user->role,
                    'registration_method' => 'platform',
                    'timestamp' => now(),
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Track heatmap data for registration
            $this->heatMapService->trackEvent('user_registration', [
                'user_id' => $user->id,
                'page' => 'registration',
                'action' => 'complete',
                'coordinates' => ['x' => 0, 'y' => 0], // Default coordinates
            ]);
            // Auto-join school groups
            $this->groupManager->autoJoinSchoolGroups($user);

            Log::info('Successfully processed new user registration', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process new user registration', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        try {
            // Check if education-related data has changed
            if ($this->hasEducationDataChanged($user)) {
                // Dispatch job to update circles in the background
                UpdateUserCirclesJob::dispatch($user);

                // Track career analytics for education updates
                AnalyticsEvent::create([
                    'event_type' => 'career_progress_update',
                    'event_category' => 'career_analytics',
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id ?? null,
                    'event_data' => [
                        'update_type' => 'education_history',
                        'timestamp' => now(),
                        'education_count' => $user->educations()->count(),
                    ],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                Log::info('Dispatched circle update job for user', [
                    'user_id' => $user->id,
                ]);
            }
            // Fire profile updated event for activity logging
            \App\Events\UserProfileUpdated::dispatch($user);
        } catch (\Exception $e) {
            Log::error('Failed to handle user updated event', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Track user login analytics
     */
    public function login(User $user): void
    {
        try {
            // Track login analytics for learning platform
            AnalyticsEvent::create([
                'event_type' => 'user_login',
                'event_category' => 'learning_analytics',
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id ?? null,
                'event_data' => [
                    'user_type' => $user->role,
                    'login_method' => 'platform',
                    'timestamp' => now(),
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Track heatmap data for login
            $this->heatMapService->trackEvent('user_login', [
                'user_id' => $user->id,
                'page' => 'login',
                'action' => 'complete',
                'coordinates' => ['x' => 0, 'y' => 0],
            ]);

            Log::info('Tracked user login analytics', [
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track user login analytics', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        try {
            // Track user deletion analytics
            AnalyticsEvent::create([
                'event_type' => 'user_deletion',
                'event_category' => 'platform_analytics',
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id ?? null,
                'event_data' => [
                    'user_type' => $user->role,
                    'deletion_reason' => 'account_removal',
                    'timestamp' => now(),
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Remove user from all circles
            $circles = $user->circles;
            foreach ($circles as $circle) {
                $circle->removeMember($user);
            }

            // Remove user from all groups
            $groups = $user->groups;
            foreach ($groups as $group) {
                $group->removeMember($user);
            }

            // Invalidate caches for deleted user
            $this->invalidateUserCaches($user);

            Log::info('Successfully cleaned up user circles and groups', [
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clean up user circles and groups', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate caches related to a user
     */
    protected function invalidateUserCaches(User $user): void
    {
        try {
            // Invalidate component caches for this user
            $this->componentCachingService->invalidateComponentCacheForUser($user->id);

            // Invalidate general caches that might be affected by user changes
            $this->cachingStrategyService->invalidateRelatedCaches('graduate', $user->id);

            Log::info('Invalidated caches for user', [
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to invalidate user caches', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if education-related data has changed.
     */
    protected function hasEducationDataChanged(User $user): bool
    {
        // Check if the user's education history has been modified
        // This is a simple check - in a real application, you might want to
        // track specific changes to education records

        // For now, we'll check if the user's profile data has changed
        // which might indicate education updates
        $dirty = $user->getDirty();

        return isset($dirty['profile_data']) ||
               $user->educations()->where('updated_at', '>', now()->subMinutes(5))->exists();
    }
}
