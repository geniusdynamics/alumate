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
        // Validate PHP version requirement
        $this->validateEnvironment();

        // Register model observers with error handling
        $this->registerObservers();

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\MonitorHomepage::class,
            ]);
        }
    }

    /**
     * Validate the application environment requirements.
     *
     * @throws \RuntimeException
     */
    protected function validateEnvironment(): void
    {
        // Check PHP version requirement
        if (! version_compare(PHP_VERSION, '8.3.0', '>=')) {
            throw new \RuntimeException(
                sprintf(
                    'This application requires PHP 8.3.0 or higher. Current version: %s',
                    PHP_VERSION
                )
            );
        }

        // Additional environment validations can be added here
    }

    /**
     * Register model observers with proper error handling.
     */
    protected function registerObservers(): void
    {
        try {
            \App\Models\User::observe(\App\Observers\UserObserver::class);
            \App\Models\EducationHistory::observe(\App\Observers\EducationHistoryObserver::class);

            \Illuminate\Support\Facades\Log::info('Model observers registered successfully');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to register model observers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // In production, you might want to throw the exception or handle it differently
            // For now, we'll log the error and continue
        }
    }
}
