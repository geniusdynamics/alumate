<?php

namespace App\Providers;

use App\Events\CareerMilestoneCreated;
use App\Events\ConnectionAccepted;
use App\Events\InstitutionAdminCreated;
use App\Events\PostCreated;
use App\Events\UserProfileUpdated;
use App\Listeners\CheckAchievementsListener;
use App\Listeners\LogUserActivity;
use App\Listeners\SendInstitutionAdminCreationNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            CheckAchievementsListener::class,
        ],
        InstitutionAdminCreated::class => [
            SendInstitutionAdminCreationNotification::class,
        ],
        CareerMilestoneCreated::class => [
            CheckAchievementsListener::class,
        ],
        UserProfileUpdated::class => [
            CheckAchievementsListener::class,
        ],
        ConnectionAccepted::class => [
            CheckAchievementsListener::class,
        ],
        PostCreated::class => [
            CheckAchievementsListener::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        LogUserActivity::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
