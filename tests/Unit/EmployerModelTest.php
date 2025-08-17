<?php

namespace Tests\Unit;

use App\Models\Employer;
use PHPUnit\Framework\TestCase;

class EmployerModelTest extends TestCase
{
    /** @test */
    public function employer_model_has_appends_array_with_can_post_jobs()
    {
        $employer = new Employer;

        // Test that can_post_jobs is in the $appends array
        $reflection = new \ReflectionClass($employer);
        $appendsProperty = $reflection->getProperty('appends');
        $appendsProperty->setAccessible(true);
        $appends = $appendsProperty->getValue($employer);

        $this->assertContains('can_post_jobs', $appends);
    }

    /** @test */
    public function employer_model_has_get_can_post_jobs_attribute_method()
    {
        $employer = new Employer;

        // Test that the getCanPostJobsAttribute method exists
        $this->assertTrue(method_exists($employer, 'getCanPostJobsAttribute'));
    }

    /** @test */
    public function employer_can_post_jobs_returns_true_when_conditions_met()
    {
        // Create an employer with all conditions met
        $employer = new Employer;
        $employer->is_active = true;
        $employer->verification_status = 'verified';
        $employer->setAttribute('can_post_jobs', true); // Set the database field
        $employer->subscription_plan = 'premium';
        $employer->jobs_posted_this_month = 10; // Under premium limit
        $employer->job_posting_limit = 100;

        // Test the accessor returns true
        $this->assertTrue($employer->can_post_jobs);
    }

    /** @test */
    public function employer_can_post_jobs_returns_false_when_inactive()
    {
        // Create an inactive employer
        $employer = new Employer;
        $employer->is_active = false; // Inactive
        $employer->verification_status = 'verified';
        $employer->setAttribute('can_post_jobs', true);

        // Test the accessor returns false
        $this->assertFalse($employer->can_post_jobs);
    }

    /** @test */
    public function employer_can_post_jobs_returns_false_when_not_verified()
    {
        // Create an unverified employer
        $employer = new Employer;
        $employer->is_active = true;
        $employer->verification_status = 'pending'; // Not verified
        $employer->setAttribute('can_post_jobs', true);

        // Test the accessor returns false
        $this->assertFalse($employer->can_post_jobs);
    }

    /** @test */
    public function employer_can_post_jobs_returns_false_when_database_field_false()
    {
        // Create an employer with database field set to false
        $employer = new Employer;
        $employer->is_active = true;
        $employer->verification_status = 'verified';
        $employer->setAttribute('can_post_jobs', false); // Database field is false

        // Test the accessor returns false
        $this->assertFalse($employer->can_post_jobs);
    }

    /** @test */
    public function employer_can_post_jobs_includes_in_json_output()
    {
        // Create an employer with conditions to post jobs
        $employer = new Employer;
        $employer->is_active = true;
        $employer->verification_status = 'verified';
        $employer->setAttribute('can_post_jobs', true);
        $employer->company_name = 'Test Company';

        // Convert to array (simulates JSON output)
        $array = $employer->toArray();

        // Assert can_post_jobs is present in the array
        $this->assertArrayHasKey('can_post_jobs', $array);
        $this->assertTrue($array['can_post_jobs']);
    }

    /** @test */
    public function employer_can_post_jobs_respects_subscription_limits()
    {
        // Test basic plan limit (5 jobs)
        $employer = new Employer;
        $employer->is_active = true;
        $employer->verification_status = 'verified';
        $employer->setAttribute('can_post_jobs', true);
        $employer->subscription_plan = 'basic';
        $employer->jobs_posted_this_month = 5; // At limit

        $this->assertFalse($employer->can_post_jobs);

        // Test under limit
        $employer->jobs_posted_this_month = 4; // Under limit
        $this->assertTrue($employer->can_post_jobs);
    }
}
