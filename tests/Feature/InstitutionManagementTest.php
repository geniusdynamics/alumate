<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    /** @test */
    public function it_can_list_institutions()
    {
        Tenant::factory()->count(3)->create();

        $response = $this->get(route('institutions.index'));

        $response->assertInertia(function ($page) {
            $this->assertCount(3, $page->component('Institutions/Index')->prop('institutions'));
        });
    }

    /** @test */
    public function it_can_show_the_create_institution_page()
    {
        $response = $this->get(route('institutions.create'));

        $response->assertInertia(fn ($page) => $page->component('Institutions/Create'));
    }

    /** @test */
    public function it_can_create_an_institution()
    {
        $response = $this->post(route('institutions.store'), [
            'id' => 'test',
            'name' => 'Test Institution',
            'address' => '123 Test Street',
            'contact_information' => 'test@example.com',
            'plan' => 'basic',
        ]);

        $response->assertRedirect(route('institutions.index'));
        $this->assertDatabaseHas('tenants', ['id' => 'test']);
    }

    /** @test */
    public function it_can_show_the_edit_institution_page()
    {
        $tenant = Tenant::factory()->create();

        $response = $this->get(route('institutions.edit', $tenant));

        $response->assertInertia(fn ($page) => $page->component('Institutions/Edit')->has('institution'));
    }

    /** @test */
    public function it_can_update_an_institution()
    {
        $tenant = Tenant::factory()->create();

        $response = $this->patch(route('institutions.update', $tenant), [
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect(route('institutions.index'));
        $this->assertEquals('Updated Name', $tenant->fresh()->name);
    }

    /** @test */
    public function it_can_delete_an_institution()
    {
        $tenant = Tenant::factory()->create();

        $response = $this->delete(route('institutions.destroy', $tenant));

        $response->assertRedirect(route('institutions.index'));
        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
    }
}
