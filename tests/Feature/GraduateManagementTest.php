<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Graduate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class GraduateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $user;
    protected $course;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a tenant
        $this->tenant = Tenant::create(['id' => 'test', 'name' => 'Test Tenant']);
        $this->tenant->domains()->create(['domain' => 'test.localhost']);

        // Switch to the tenant's context
        tenancy()->initialize($this->tenant);

        // Create a user in the tenant's database
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->course = Course::factory()->create();
    }

    /** @test */
    public function it_can_list_graduates()
    {
        Graduate::factory()->count(3)->create(['course_id' => $this->course->id]);

        $response = $this->get(route('graduates.index'));

        $response->assertInertia(function ($page) {
            $this->assertCount(3, $page->component('Graduates/Index')->prop('graduates'));
        });
    }

    /** @test */
    public function it_can_show_the_create_graduate_page()
    {
        $response = $this->get(route('graduates.create'));

        $response->assertInertia(fn ($page) => $page->component('Graduates/Create'));
    }

    /** @test */
    public function it_can_create_a_graduate()
    {
        $response = $this->post(route('graduates.store'), [
            'name' => 'Test Graduate',
            'email' => 'test@example.com',
            'graduation_year' => 2022,
            'course_id' => $this->course->id,
        ]);

        $response->assertRedirect(route('graduates.index'));
        $this->assertDatabaseHas('graduates', ['name' => 'Test Graduate']);
    }

    /** @test */
    public function it_can_show_the_edit_graduate_page()
    {
        $graduate = Graduate::factory()->create(['course_id' => $this->course->id]);

        $response = $this->get(route('graduates.edit', $graduate));

        $response->assertInertia(fn ($page) => $page->component('Graduates/Edit')->has('graduate'));
    }

    /** @test */
    public function it_can_update_a_graduate()
    {
        $graduate = Graduate::factory()->create(['course_id' => $this->course->id]);

        $response = $this->patch(route('graduates.update', $graduate), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'graduation_year' => 2023,
            'course_id' => $this->course->id,
        ]);

        $response->assertRedirect(route('graduates.index'));
        $this->assertEquals('Updated Name', $graduate->fresh()->name);
    }

    /** @test */
    public function it_can_delete_a_graduate()
    {
        $graduate = Graduate::factory()->create(['course_id' => $this->course->id]);

        $response = $this->delete(route('graduates.destroy', $graduate));

        $response->assertRedirect(route('graduates.index'));
        $this->assertDatabaseMissing('graduates', ['id' => $graduate->id]);
    }

    /** @test */
    public function it_can_import_graduates_from_an_excel_file()
    {
        Excel::fake();

        $file = UploadedFile::fake()->create('graduates.xlsx', 100);

        $response = $this->post(route('graduates.import.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('graduates.index'));
        Excel::assertImported('graduates.xlsx');
    }
}
