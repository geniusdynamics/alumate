<?php

namespace Tests\Unit;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_tenant_with_the_new_attributes()
    {
        $tenant = Tenant::create([
            'id' => 'foo',
            'name' => 'Test Tenant',
            'address' => '123 Test Street',
            'contact_information' => 'test@example.com',
            'plan' => 'basic',
        ]);

        $this->assertDatabaseHas('tenants', [
            'id' => 'foo',
            'name' => 'Test Tenant',
            'address' => '123 Test Street',
            'contact_information' => 'test@example.com',
            'plan' => 'basic',
        ]);
    }
}
