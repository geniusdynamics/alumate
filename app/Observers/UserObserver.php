<?php

namespace App\Observers;

use App\Models\User;
use App\Models\NotificationPreference;

class UserObserver
{
    public function created(User $user)
    {
        // Create default notification preferences for new users
        NotificationPreference::createDefaultPreferences($user->id);
    }
}