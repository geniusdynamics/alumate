<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Stancl\Tenancy\Tenancy;
use Tests\TestCase;

class TenancySetupTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Config::set('tenancy.bootstrappers', [
            \Stancl\Tenancy\TenancyBootstrappers\DatabaseTenancyBootstrapper::class,
        ]);

        Artisan::call('migrate:fresh');
    }

    /** @test */
    public function a_tenant_can_be_created()
    {
        $tenant = Tenant::create([
            'id' => 'foo',
        ]);

        $this->assertNotNull($tenant);
    }

    /** @test */
    public function tenancy_can_be_initialized()
    {
        $tenant = Tenant::create([
            'id' => 'foo',
        ]);

        tenancy()->initialize($tenant);

        $this->assertTrue(tenancy()->initialized);
    }
}
