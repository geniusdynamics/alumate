<?php

namespace AlumniPlatform\ApiClient;

use Illuminate\Support\ServiceProvider;

class AlumniPlatformServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function ($app) {
            $config = $app['config']['services.alumni_platform'] ?? [];

            if (empty($config['base_uri']) || empty($config['token'])) {
                throw new \InvalidArgumentException(
                    'Alumni Platform configuration is missing. Please set ALUMNI_PLATFORM_BASE_URI and ALUMNI_PLATFORM_TOKEN in your .env file.'
                );
            }

            return new Client($config);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
