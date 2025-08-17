<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Graduate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployerEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;

    protected $employerUser;

    protected $employer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');

        $this->employerUser = User::factory()->create();
        $this->employerUser->assignRole('Employer');
        $this->employer = Employer::factory()->create(['user_id' => $this->employerUser->id]);
    }

    /** @test */
    public function a_super_admin_can_approve_a_company()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->post(route('companies.approve', $this->employer));

        $response->assertRedirect(route('companies.index'));
        $this->assertTrue($this->employer->fresh()->approved);
    }

    /** @test */
    public function an_employer_can_search_for_graduates()
    {
        $this->actingAs($this->employerUser);

        $tenant = Tenant::create(['id' => 'test', 'name' => 'Test Tenant']);
        tenancy()->initialize($tenant);
        Graduate::factory()->create(['name' => 'John Doe']);

        $response = $this->get(route('graduates.search', ['search' => 'John']));

        $response->assertInertia(function ($page) {
            $this->assertCount(1, $page->component('Graduates/Search')->prop('graduates.data'));
            $this->assertEquals('John Doe', $page->component('Graduates/Search')->prop('graduates.data.0.name'));
        });
    }
}
