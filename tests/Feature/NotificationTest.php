<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
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
        $this->employer = Employer::factory()->create(['user_id' => $this->employerUser->id]);
        $this->job = Job::factory()->create(['employer_id' => $this->employer->id]);
    }

    /** @test */
    public function a_notification_is_sent_when_a_graduate_applies_for_a_job()
    {
        Notification::fake();

        $this->actingAs($this->graduateUser);

        $this->post(route('jobs.apply', $this->job), [
            'cover_letter' => 'This is my cover letter.',
        ]);

        Notification::assertSentTo(
            [$this->employerUser],
            \App\Notifications\JobApplicationNotification::class
        );
    }
}
