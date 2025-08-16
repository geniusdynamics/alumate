<?php

namespace App\Observers;

use App\Jobs\UpdateUserCirclesJob;
use App\Models\User;
use App\Services\CircleManager;
use App\Services\GroupManager;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    protected CircleManager $circleManager;

    protected GroupManager $groupManager;

    public function __construct(CircleManager $circleManager, GroupManager $groupManager)
    {
        $this->circleManager = $circleManager;
        $this->groupManager = $groupManager;
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        try {
            // Generate circles for the new user
            $this->circleManager->generateCirclesForUser($user);

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
        // Check if education-related data has changed
        if ($this->hasEducationDataChanged($user)) {
            try {
                // Dispatch job to update circles in the background
                UpdateUserCirclesJob::dispatch($user);

                Log::info('Dispatched circle update job for user', [
                    'user_id' => $user->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to dispatch circle update job', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        try {
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
