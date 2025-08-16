<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\EducationHistory::observe(\App\Observers\EducationHistoryObserver::class);

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\MonitorHomepage::class,
            ]);
        }
    }
}
