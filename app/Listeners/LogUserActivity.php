<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;

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
            'description' => 'User logged into the system',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
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
            'description' => 'User logged out of the system',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
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
            'description' => 'New user registered',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
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
