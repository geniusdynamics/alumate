<?php

namespace Tests\Feature;

use App\Models\Graduate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected $graduateUser;

    protected $graduate;

    protected $employerUser;

    protected $employer;

    protected $job;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a tenant
        $this->tenant = Tenant::create(['id' => 'test', 'name' => 'Test Tenant']);
        $this->tenant->domains()->create(['domain' => 'test.localhost']);

        // Switch to the tenant's context
        tenancy()->initialize($this->tenant);

        // Create a graduate user
        $this->graduateUser = User::factory()->create();
        $this->graduateUser->assignRole('Graduate');
        $this->graduate = Graduate::factory()->create(['email' => $this->graduateUser->email]);

        // Create an employer user
        $this->employerUser = User::factory()->create();
        $this->employerUser->assignRole('Employer');
        $this->employer = \App\Models\Employer::factory()->create(['user_id' => $this->employerUser->id]);
        $this->job = Job::factory()->create(['employer_id' => $this->employer->id]);
    }

    /** @test */
    public function a_graduate_can_apply_for_a_job()
    {
        $this->actingAs($this->graduateUser);

        $response = $this->post(route('jobs.apply', $this->job), [
            'cover_letter' => 'This is my cover letter.',
        ]);

        $response->assertRedirect(route('jobs.public.index'));
        $this->assertDatabaseHas('job_applications', [
            'job_id' => $this->job->id,
            'graduate_id' => $this->graduate->id,
        ]);
    }

    /** @test */
    public function an_employer_can_view_applications_for_their_job()
    {
        $this->actingAs($this->employerUser);

        JobApplication::factory()->create([
            'job_id' => $this->job->id,
            'graduate_id' => $this->graduate->id,
        ]);

        $response = $this->get(route('jobs.applications.index', $this->job));

        $response->assertInertia(function ($page) {
            $this->assertCount(1, $page->component('Jobs/Applications')->prop('applications'));
        });
    }

    /** @test */
    public function a_graduate_can_view_their_applications()
    {
        $this->actingAs($this->graduateUser);

        JobApplication::factory()->create([
            'job_id' => $this->job->id,
            'graduate_id' => $this->graduate->id,
        ]);

        $response = $this->get(route('my.applications'));

        $response->assertInertia(function ($page) {
            $this->assertCount(1, $page->component('MyApplications/Index')->prop('applications'));
        });
    }
}
