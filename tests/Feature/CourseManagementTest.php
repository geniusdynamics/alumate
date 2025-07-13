<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $user;

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
    }

    /** @test */
    public function it_can_list_courses()
    {
        Course::factory()->count(3)->create();

        $response = $this->get(route('courses.index'));

        $response->assertInertia(function ($page) {
            $this->assertCount(3, $page->component('Courses/Index')->prop('courses'));
        });
    }

    /** @test */
    public function it_can_show_the_create_course_page()
    {
        $response = $this->get(route('courses.create'));

        $response->assertInertia(fn ($page) => $page->component('Courses/Create'));
    }

    /** @test */
    public function it_can_create_a_course()
    {
        $response = $this->post(route('courses.store'), [
            'name' => 'Test Course',
            'description' => 'Test Description',
        ]);

        $response->assertRedirect(route('courses.index'));
        $this->assertDatabaseHas('courses', ['name' => 'Test Course']);
    }

    /** @test */
    public function it_can_show_the_edit_course_page()
    {
        $course = Course::factory()->create();

        $response = $this->get(route('courses.edit', $course));

        $response->assertInertia(fn ($page) => $page->component('Courses/Edit')->has('course'));
    }

    /** @test */
    public function it_can_update_a_course()
    {
        $course = Course::factory()->create();

        $response = $this->patch(route('courses.update', $course), [
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect(route('courses.index'));
        $this->assertEquals('Updated Name', $course->fresh()->name);
    }

    /** @test */
    public function it_can_delete_a_course()
    {
        $course = Course::factory()->create();

        $response = $this->delete(route('courses.destroy', $course));

        $response->assertRedirect(route('courses.index'));
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }
}
