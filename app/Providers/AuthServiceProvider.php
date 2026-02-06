<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Job::class => \App\Policies\JobPolicy::class,
        \App\Models\Export::class => \App\Policies\ExportPolicy::class,
        \App\Models\Backup::class => \App\Policies\BackupPolicy::class,
        \App\Models\Migration::class => \App\Policies\MigrationPolicy::class,
        \App\Models\Cohort::class => \App\Policies\CohortPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
