<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class InstitutionAdminEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected $institutionAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a tenant
        $this->tenant = Tenant::create(['id' => 'test', 'name' => 'Test Tenant']);
        $this->tenant->domains()->create(['domain' => 'test.localhost']);

        // Switch to the tenant's context
        tenancy()->initialize($this->tenant);

        // Create an institution admin user
        $this->institutionAdmin = User::factory()->create();
        $this->institutionAdmin->assignRole('Institution Admin');
        $this->actingAs($this->institutionAdmin);
    }

    /** @test */
    public function an_institution_admin_can_manage_tutors()
    {
        $tutor = Tutor::factory()->create();

        $this->get(route('tutors.index'))->assertInertia(fn ($page) => $page->component('Tutors/Index'));
        $this->get(route('tutors.create'))->assertInertia(fn ($page) => $page->component('Tutors/Create'));
        $this->post(route('tutors.store'), ['name' => 'John Doe', 'email' => 'john@example.com']);
        $this->get(route('tutors.edit', $tutor))->assertInertia(fn ($page) => $page->component('Tutors/Edit'));
        $this->patch(route('tutors.update', $tutor), ['name' => 'Jane Doe']);
        $this->delete(route('tutors.destroy', $tutor));
    }

    /** @test */
    public function an_institution_admin_can_import_courses_from_an_excel_file()
    {
        Excel::fake();

        $file = UploadedFile::fake()->create('courses.xlsx', 100);

        $response = $this->post(route('courses.import.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('courses.index'));
        Excel::assertImported('courses.xlsx');
    }

    /** @test */
    public function an_institution_admin_can_edit_their_institution_details()
    {
        $response = $this->patch(route('institution.update'), [
            'name' => 'New Institution Name',
        ]);

        $response->assertRedirect(route('institution.edit'));
        $this->assertEquals('New Institution Name', $this->tenant->fresh()->name);
    }
}
