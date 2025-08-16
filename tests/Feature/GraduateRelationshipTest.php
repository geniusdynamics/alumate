<?php

namespace Tests\Feature;

use App\Models\Graduate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GraduateRelationshipTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant1;

    protected $tenant2;

    protected $graduateUser;

    protected $graduate;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenants
        $this->tenant1 = Tenant::create(['id' => 'test1', 'name' => 'Test Tenant 1']);
        $this->tenant1->domains()->create(['domain' => 'test1.localhost']);

        $this->tenant2 = Tenant::create(['id' => 'test2', 'name' => 'Test Tenant 2']);
        $this->tenant2->domains()->create(['domain' => 'test2.localhost']);

        // Switch to the tenant's context
        tenancy()->initialize($this->tenant1);

        // Create a graduate user
        $this->graduateUser = User::factory()->create();
        $this->graduateUser->assignRole('Graduate');
        $this->graduate = Graduate::factory()->create([
            'email' => $this->graduateUser->email,
            'tenant_id' => $this->tenant1->id,
            'previous_institution_id' => $this->tenant2->id,
        ]);

        $this->actingAs($this->graduateUser);
    }

    /** @test */
    public function a_graduate_belongs_to_a_tenant()
    {
        $this->assertEquals($this->tenant1->id, $this->graduate->tenant->id);
    }

    /** @test */
    public function a_graduate_can_have_a_previous_institution()
    {
        $this->assertEquals($this->tenant2->id, $this->graduate->previousInstitution->id);
    }
}
