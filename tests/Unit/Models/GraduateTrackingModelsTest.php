<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Graduate;
use App\Models\Course;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Employer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GraduateTrackingModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_graduate_model_has_correct_fillable_attributes(): void
    {
        $graduate = new Graduate();
        
        $expectedFillable = [
            'tenant_id', 'student_id', 'name', 'email', 'phone', 'address',
            'graduation_year', 'course_id', 'gpa', 'academic_standing',
            'employment_status', 'current_job_title', 'current_company',
            'current_salary', 'employment_start_date', 'profile_completion_percentage',
            'profile_completion_fields', 'privacy_settings', 'skills',
            'certifications', 'allow_employer_contact', 'job_search_active',
            'last_profile_update', 'last_employment_update'
        ];
        
        $this->assertEquals($expectedFillable, $graduate->getFillable());
    }

    public function test_graduate_relationships(): void
    {
        $tenant = Tenant::factory()->create();
        $course = Course::factory()->create(['institution_id' => $tenant->id]);
        $graduate = Graduate::factory()->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id
        ]);

        $this->assertInstanceOf(Course::class, $graduate->course);
        $this->assertInstanceOf(Tenant::class, $graduate->tenant);
        $this->assertEquals($course->id, $graduate->course->id);
        $this->assertEquals($tenant->id, $graduate->tenant->id);
    }

    public function test_graduate_profile_completion_calculation(): void
    {
        $graduate = Graduate::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123-456-7890',
            'address' => '123 Main St',
            'graduation_year' => 2024,
            'employment_status' => 'employed'
        ]);

        $completionPercentage = $graduate->updateProfileCompletion();
        
        $this->assertGreaterThan(0, $completionPercentage);
        $this->assertLessThanOrEqual(100, $completionPercentage);
        $this->assertEquals($completionPercentage, $graduate->profile_completion_percentage);
    }

    public function test_graduate_employment_status_update(): void
    {
        $graduate = Graduate::factory()->create(['employment_status' => 'unemployed']);

        $jobDetails = [
            'job_title' => 'Software Developer',
            'company' => 'Tech Corp',
            'salary' => 75000,
            'start_date' => now()
        ];

        $graduate->updateEmploymentStatus('employed', $jobDetails);

        $this->assertEquals('employed', $graduate->employment_status);
        $this->assertEquals('Software Developer', $graduate->current_job_title);
        $this->assertEquals('Tech Corp', $graduate->current_company);
        $this->assertEquals(75000, $graduate->current_salary);
    }

    public function test_course_model_has_correct_relationships(): void
    {
        $tenant = Tenant::factory()->create();
        $course = Course::factory()->create(['institution_id' => $tenant->id]);
        $graduates = Graduate::factory()->count(3)->create([
            'tenant_id' => $tenant->id,
            'course_id' => $course->id
        ]);

        $this->assertInstanceOf(Tenant::class, $course->institution);
        $this->assertCount(3, $course->graduates);
        $this->assertEquals($tenant->id, $course->institution->id);
    }

    public function test_course_statistics_update(): void
    {
        $course = Course::factory()->create();
        
        // Create graduates with different employment statuses
        Graduate::factory()->count(5)->employed()->create(['course_id' => $course->id]);
        Graduate::factory()->count(3)->unemployed()->create(['course_id' => $course->id]);

        $stats = $course->updateStatistics();

        $this->assertEquals(8, $stats['total_graduated']);
        $this->assertEquals(62.5, $stats['employment_rate']); // 5/8 * 100
        $this->assertGreaterThan(0, $stats['average_salary']);
    }

    public function test_employer_model_verification_methods(): void
    {
        $employer = Employer::factory()->pending()->create();
        $verifierId = 1;

        // Test verification
        $employer->verify($verifierId, 'Company verified successfully');

        $this->assertEquals('verified', $employer->verification_status);
        $this->assertTrue($employer->can_post_jobs);
        $this->assertTrue($employer->can_search_graduates);
        $this->assertEquals($verifierId, $employer->verified_by);

        // Test rejection
        $employer->reject('Invalid documentation', $verifierId);

        $this->assertEquals('rejected', $employer->verification_status);
        $this->assertFalse($employer->can_post_jobs);
        $this->assertFalse($employer->can_search_graduates);
        $this->assertEquals('Invalid documentation', $employer->rejection_reason);
    }

    public function test_employer_job_statistics_update(): void
    {
        $employer = Employer::factory()->verified()->create();
        
        // Create jobs for the employer
        Job::factory()->count(5)->active()->create(['employer_id' => $employer->id]);
        Job::factory()->count(2)->state(['status' => 'filled'])->create(['employer_id' => $employer->id]);

        $stats = $employer->updateJobStats();

        $this->assertEquals(5, $stats['active_jobs']);
        $this->assertEquals(7, $stats['total_jobs']);
    }

    public function test_job_model_application_tracking(): void
    {
        $job = Job::factory()->active()->create();
        
        // Create applications with different statuses
        JobApplication::factory()->count(10)->pending()->create(['job_id' => $job->id]);
        JobApplication::factory()->count(5)->state(['status' => 'reviewed'])->create(['job_id' => $job->id]);
        JobApplication::factory()->count(3)->state(['status' => 'shortlisted'])->create(['job_id' => $job->id]);
        JobApplication::factory()->count(1)->hired()->create(['job_id' => $job->id]);

        $stats = $job->updateApplicationStats();

        $this->assertEquals(19, $stats->total);
        $this->assertEquals(9, $stats->viewed); // reviewed + shortlisted + hired
        $this->assertEquals(4, $stats->shortlisted); // shortlisted + hired
    }

    public function test_job_matching_graduates(): void
    {
        $course = Course::factory()->create();
        $job = Job::factory()->active()->create([
            'course_id' => $course->id,
            'required_skills' => ['PHP', 'JavaScript', 'MySQL']
        ]);

        // Create graduates with matching skills
        Graduate::factory()->count(5)->create([
            'course_id' => $course->id,
            'skills' => ['PHP', 'JavaScript', 'HTML', 'CSS'],
            'job_search_active' => true,
            'allow_employer_contact' => true
        ]);

        $matchingGraduates = $job->getMatchingGraduates();

        $this->assertCount(5, $matchingGraduates);
        foreach ($matchingGraduates as $graduate) {
            $this->assertEquals($course->id, $graduate->course_id);
            $this->assertTrue($graduate->job_search_active);
            $this->assertTrue($graduate->allow_employer_contact);
        }
    }

    public function test_job_application_status_workflow(): void
    {
        $application = JobApplication::factory()->pending()->create();
        $userId = 1;

        // Test status progression
        $application->updateStatus('reviewed', $userId, 'Initial review completed');
        $this->assertEquals('reviewed', $application->status);
        $this->assertCount(2, $application->status_history); // pending + reviewed

        $application->updateStatus('shortlisted', $userId, 'Candidate shortlisted');
        $this->assertEquals('shortlisted', $application->status);
        $this->assertCount(3, $application->status_history);

        // Test interview scheduling
        $interviewDate = now()->addDays(7);
        $application->scheduleInterview($interviewDate, 'Office Conference Room', 'Technical interview');
        
        $this->assertEquals('interview_scheduled', $application->status);
        $this->assertEquals($interviewDate, $application->interview_scheduled_at);
        $this->assertEquals('Office Conference Room', $application->interview_location);

        // Test offer making
        $application->makeOffer(75000, now()->addDays(14), ['start_date' => now()->addMonth()]);
        
        $this->assertEquals('offer_made', $application->status);
        $this->assertEquals(75000, $application->offered_salary);
        $this->assertNotNull($application->offer_expiry_date);

        // Test offer acceptance
        $application->acceptOffer('Thank you for the opportunity!');
        
        $this->assertEquals('offer_accepted', $application->status);
        $this->assertEquals('Thank you for the opportunity!', $application->graduate_response);
        $this->assertNotNull($application->graduate_responded_at);
    }

    public function test_job_application_match_score_calculation(): void
    {
        $course = Course::factory()->create();
        $job = Job::factory()->create([
            'course_id' => $course->id,
            'required_skills' => ['PHP', 'JavaScript', 'MySQL', 'Laravel']
        ]);
        
        $graduate = Graduate::factory()->create([
            'course_id' => $course->id,
            'skills' => ['PHP', 'JavaScript', 'HTML'], // 2 out of 4 skills match
            'profile_completion_percentage' => 90,
            'gpa' => 3.5
        ]);

        $application = JobApplication::factory()->create([
            'job_id' => $job->id,
            'graduate_id' => $graduate->id
        ]);

        $matchResult = $application->calculateMatchScore();

        $this->assertArrayHasKey('score', $matchResult);
        $this->assertArrayHasKey('factors', $matchResult);
        $this->assertGreaterThan(0, $matchResult['score']);
        $this->assertLessThanOrEqual(100, $matchResult['score']);
        
        // Course match should contribute 40 points
        $this->assertTrue($matchResult['factors']['course_match']);
        
        // Skills match should contribute some points (2 out of 4 skills = 15 points)
        $this->assertEquals(2, $matchResult['factors']['skills_match']);
        
        // Profile completion should contribute 18 points (90% of 20)
        $this->assertEquals(90, $matchResult['factors']['profile_completion']);
        
        // GPA should contribute 8.75 points (3.5/4.0 * 10)
        $this->assertEquals(3.5, $matchResult['factors']['gpa']);
    }

    public function test_model_scopes_work_correctly(): void
    {
        // Test Graduate scopes
        Graduate::factory()->count(5)->employed()->create();
        Graduate::factory()->count(3)->unemployed()->create();
        Graduate::factory()->count(2)->state(['job_search_active' => false])->create();

        $this->assertCount(5, Graduate::employed()->get());
        $this->assertCount(3, Graduate::unemployed()->get());
        $this->assertCount(8, Graduate::jobSearchActive()->get()); // employed + unemployed with job_search_active = true

        // Test Job scopes
        Job::factory()->count(4)->active()->create();
        Job::factory()->count(2)->pending()->create();
        Job::factory()->count(1)->remote()->create();

        $this->assertCount(4, Job::active()->get());
        $this->assertCount(2, Job::pending()->get());
        $this->assertCount(1, Job::remote()->get());

        // Test Employer scopes
        Employer::factory()->count(3)->verified()->create();
        Employer::factory()->count(2)->pending()->create();
        Employer::factory()->count(1)->rejected()->create();

        $this->assertCount(3, Employer::verified()->get());
        $this->assertCount(2, Employer::pending()->get());
        $this->assertCount(1, Employer::rejected()->get());
    }

    public function test_model_accessors_work_correctly(): void
    {
        // Test Graduate accessors
        $graduate = Graduate::factory()->create(['profile_completion_percentage' => 100]);
        $this->assertTrue($graduate->is_profile_complete);

        $employedGraduate = Graduate::factory()->employed()->create();
        $this->assertTrue($employedGraduate->is_employed);

        // Test Job accessors
        $activeJob = Job::factory()->active()->create();
        $this->assertTrue($activeJob->is_active);

        $jobWithSalary = Job::factory()->create(['salary_min' => 50000, 'salary_max' => 70000]);
        $this->assertEquals('50,000 - 70,000', $jobWithSalary->salary_range);

        // Test Employer accessors
        $verifiedEmployer = Employer::factory()->verified()->create();
        $this->assertTrue($verifiedEmployer->is_verified);
        $this->assertTrue($verifiedEmployer->can_post_jobs);

        $employerAtLimit = Employer::factory()->create([
            'job_posting_limit' => 5,
            'jobs_posted_this_month' => 5
        ]);
        $this->assertTrue($employerAtLimit->has_reached_job_limit);
    }
}