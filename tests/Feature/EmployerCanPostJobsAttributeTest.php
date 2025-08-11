<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployerCanPostJobsAttributeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function employer_json_includes_can_post_jobs_attribute_when_verified_and_active()
    {
        // Create a user for the employer
        $user = User::factory()->create();
        $user->assignRole('Employer');

        // Create an employer with conditions that should allow posting jobs
        $employer = Employer::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'can_post_jobs' => true,
            'verification_status' => 'verified',
            'subscription_plan' => 'premium',
            'jobs_posted_this_month' => 10,
            'job_posting_limit' => 100,
        ]);

        // Convert to JSON and assert can_post_jobs is present and true
        $jsonData = $employer->toArray();

        $this->assertArrayHasKey('can_post_jobs', $jsonData);
        $this->assertTrue($jsonData['can_post_jobs']);
    }

    /** @test */
    public function employer_json_shows_can_post_jobs_false_when_not_verified()
    {
        // Create a user for the employer
        $user = User::factory()->create();
        $user->assignRole('Employer');

        // Create an employer that should NOT be able to post jobs (not verified)
        $employer = Employer::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'can_post_jobs' => true,
            'verification_status' => 'pending', // Not verified
        ]);

        // Convert to JSON and assert can_post_jobs is present but false
        $jsonData = $employer->toArray();

        $this->assertArrayHasKey('can_post_jobs', $jsonData);
        $this->assertFalse($jsonData['can_post_jobs']);
    }

    /** @test */
    public function employer_json_shows_can_post_jobs_false_when_inactive()
    {
        // Create a user for the employer
        $user = User::factory()->create();
        $user->assignRole('Employer');

        // Create an inactive employer
        $employer = Employer::factory()->create([
            'user_id' => $user->id,
            'is_active' => false, // Inactive
            'can_post_jobs' => true,
            'verification_status' => 'verified',
        ]);

        // Convert to JSON and assert can_post_jobs is present but false
        $jsonData = $employer->toArray();

        $this->assertArrayHasKey('can_post_jobs', $jsonData);
        $this->assertFalse($jsonData['can_post_jobs']);
    }

    /** @test */
    public function employer_json_shows_can_post_jobs_false_when_database_field_is_false()
    {
        // Create a user for the employer
        $user = User::factory()->create();
        $user->assignRole('Employer');

        // Create an employer with can_post_jobs set to false in database
        $employer = Employer::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'can_post_jobs' => false, // Database field is false
            'verification_status' => 'verified',
        ]);

        // Convert to JSON and assert can_post_jobs is present and false
        $jsonData = $employer->toArray();

        $this->assertArrayHasKey('can_post_jobs', $jsonData);
        $this->assertFalse($jsonData['can_post_jobs']);
    }

    /** @test */
    public function employer_respects_subscription_plan_limits()
    {
        // Create a user for the employer
        $user = User::factory()->create();
        $user->assignRole('Employer');

        // Create an employer with basic plan that has reached the limit
        $employer = Employer::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
            'can_post_jobs' => true,
            'verification_status' => 'verified',
            'subscription_plan' => 'basic',
            'jobs_posted_this_month' => 5, // At basic plan limit
        ]);

        // Should not be able to post jobs due to subscription limit
        $jsonData = $employer->toArray();
        $this->assertArrayHasKey('can_post_jobs', $jsonData);
        $this->assertFalse($jsonData['can_post_jobs']);

        // Update to premium plan - should now be able to post
        $employer->update(['subscription_plan' => 'premium']);
        $employer->refresh();

        $jsonData = $employer->toArray();
        $this->assertTrue($jsonData['can_post_jobs']);
    }
}
