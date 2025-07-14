<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use App\Models\ActivityLog;

class LogUserActivity
{
    /**
     * Handle user login events.
     */
    public function handleLogin(Login $event)
    {
        ActivityLog::create([
            'user_id' => $event->user->id,
            'activity' => 'Logged in',
        ]);
    }

    /**
     * Handle user logout events.
     */
    public function handleLogout(Logout $event)
    {
        ActivityLog::create([
            'user_id' => $event->user->id,
            'activity' => 'Logged out',
        ]);
    }

    /**
     * Handle user registration events.
     */
    public function handleRegistration(Registered $event)
    {
        ActivityLog::create([
            'user_id' => $event->user->id,
            'activity' => 'Registered',
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            Login::class,
            [LogUserActivity::class, 'handleLogin']
        );

        $events->listen(
            Logout::class,
            [LogUserActivity::class, 'handleLogout']
        );

        $events->listen(
            Registered::class,
            [LogUserActivity::class, 'handleRegistration']
        );
    }
}
