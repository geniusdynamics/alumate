<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected $graduateUser;

    protected $graduate;

    protected $employerUser;

    protected $employer;

    protected $job;

    protected $application;

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
        $this->employer = Employer::factory()->create(['user_id' => $this->employerUser->id]);
        $this->job = Job::factory()->create(['employer_id' => $this->employer->id]);
        $this->application = JobApplication::factory()->create([
            'job_id' => $this->job->id,
            'graduate_id' => $this->graduate->id,
        ]);
    }

    /** @test */
    public function an_employer_can_mark_an_applicant_as_hired()
    {
        $this->actingAs($this->employerUser);

        $response = $this->post(route('applications.hire', $this->application));

        $response->assertRedirect();
        $this->assertEquals('hired', $this->application->fresh()->status);
    }

    /** @test */
    public function a_user_can_recommend_a_job_to_another_user()
    {
        $this->actingAs($this->graduateUser);
        $recommendedUser = User::factory()->create();

        $response = $this->post(route('jobs.recommend', $this->job), [
            'recommended_id' => $recommendedUser->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('recommendations', [
            'job_id' => $this->job->id,
            'recommender_id' => $this->graduateUser->id,
            'recommended_id' => $recommendedUser->id,
        ]);
    }
}
