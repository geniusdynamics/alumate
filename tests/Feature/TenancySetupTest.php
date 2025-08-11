<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TenancySetupTest extends TestCase
{
    protected function setUp(): void
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

    /** @test */
    public function a_user_cannot_access_data_from_another_tenant()
    {
        $tenant1 = Tenant::create(['id' => 'test1', 'name' => 'Test Tenant 1']);
        $tenant1->domains()->create(['domain' => 'test1.localhost']);

        $tenant2 = Tenant::create(['id' => 'test2', 'name' => 'Test Tenant 2']);
        $tenant2->domains()->create(['domain' => 'test2.localhost']);

        tenancy()->initialize($tenant1);
        $user1 = \App\Models\User::factory()->create();

        tenancy()->initialize($tenant2);
        $user2 = \App\Models\User::factory()->create();

        tenancy()->initialize($tenant1);
        $this->actingAs($user1);

        $this->get(route('dashboard'))->assertSuccessful();

        tenancy()->initialize($tenant2);
        $this->get(route('dashboard'))->assertForbidden();
    }
}
