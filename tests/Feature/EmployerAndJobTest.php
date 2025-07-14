<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployerAndJobTest extends TestCase
{
    use RefreshDatabase;

    protected $employerUser;
    protected $employer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employerUser = User::factory()->create();
        $this->employerUser->assignRole('Employer');
        $this->employer = Employer::factory()->create(['user_id' => $this->employerUser->id]);

        $this->actingAs($this->employerUser);
    }

    /** @test */
    public function an_employer_can_post_a_job()
    {
        $response = $this->post(route('jobs.store'), [
            'title' => 'Software Engineer',
            'description' => 'We are looking for a software engineer.',
        ]);

        $response->assertRedirect(route('jobs.index'));
        $this->assertDatabaseHas('jobs', ['title' => 'Software Engineer']);
    }

    /** @test */
    public function an_employer_can_view_their_jobs()
    {
        Job::factory()->count(3)->create(['employer_id' => $this->employer->id]);

        $response = $this->get(route('jobs.index'));

        $response->assertInertia(function ($page) {
            $this->assertCount(3, $page->component('Jobs/Index')->prop('jobs'));
        });
    }

    /** @test */
    public function an_employer_can_edit_their_job()
    {
        $job = Job::factory()->create(['employer_id' => $this->employer->id]);

        $response = $this->patch(route('jobs.update', $job), [
            'title' => 'Senior Software Engineer',
            'description' => 'We are looking for a senior software engineer.',
        ]);

        $response->assertRedirect(route('jobs.index'));
        $this->assertEquals('Senior Software Engineer', $job->fresh()->title);
    }

    /** @test */
    public function an_employer_can_delete_their_job()
    {
        $job = Job::factory()->create(['employer_id' => $this->employer->id]);

        $response = $this->delete(route('jobs.destroy', $job));

        $response->assertRedirect(route('jobs.index'));
        $this->assertDatabaseMissing('jobs', ['id' => $job->id]);
    }

    /** @test */
    public function a_user_can_register_as_an_employer()
    {
        $response = $this->post(route('employer.register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'company_name' => 'Acme Inc.',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $this->assertDatabaseHas('employers', ['company_name' => 'Acme Inc.']);
    }
}
