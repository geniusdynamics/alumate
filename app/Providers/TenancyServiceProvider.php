<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootTenancy();
    }

    protected function bootTenancy()
    {
        $this->makeTenancyMiddlewareHighestPriority();
    }

    protected function makeTenancyMiddlewareHighestPriority()
    {
        // Don't apply tenancy middleware globally
        // It should only be applied to tenant routes, not central routes
        // The routes/web.php file handles this with the 'tenant' middleware group
    }
}
