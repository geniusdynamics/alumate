<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employer;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Graduate;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;

class JobManagementIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $employerUser;
    protected Employer $employer;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->employerUser = $this->createUserWithRole('employer');
        $this->employer = Employer::factory()->verified()->create([
            'user_id' => $this->employerUser->id
        ]);
        $this->course = Course::factory()->create();
    }

    public function test_complete_job_posting_workflow(): void
    {
        $this->actingAs($this->employerUser);

        // 1. Create job posting
        $jobData = [
            'title' => 'Senior PHP Developer',
            'description' => 'We are looking for an experienced PHP developer...',
            'requirements' => 'Bachelor degree in Computer Science, 3+ years experience',
            'location' => 'Remote',
            'job_type' => 'full-time',
            'experience_level' => 'senior',
            'salary_min' => 70000,
            'salary_max' => 90000,
            'course_id' => $this->course->id,
            'required_skills' => ['PHP', 'Laravel', 'MySQL'],
            'benefits' => ['Health Insurance', 'Remote Work', 'Flexible Hours'],
            'application_deadline' => now()->addDays(30)->format('Y-m-d')
        ];

        $response = $this->post(route('jobs.store'), $jobData);
        $response->assertRedirect(route('jobs.index'));
        
        $job = Job::where('title', 'Senior PHP Developer')->first();
        $this->assertNotNull($job);
        $this->assertEquals($this->employer->id, $job->employer_id);

        // 2. View job in management interface
        $response = $this->get(route('jobs.show', $job));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Jobs/Show')
                ->where('job.title', 'Senior PHP Developer')
        );

        // 3. Update job posting
        $response = $this->put(route('jobs.update', $job), [
            'title' => 'Senior PHP Developer (Updated)',
            'salary_max' => 95000
        ]);

        $response->assertRedirect(route('jobs.show', $job));
        
        $job->refresh();
        $this->assertEquals('Senior PHP Developer (Updated)', $job->title);
        $this->assertEquals(95000, $job->salary_max);

        // 4. View job analytics
        $response = $this->get(route('jobs.analytics', $job));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Jobs/Analytics')
                ->has('analytics.views')
                ->has('analytics.applications')
        );
    }

    public function test_job_application_management_workflow(): void
    {
        Notification::fake();
        
        // Create job and graduates
        $job = Job::factory()->active()->create([
            'employer_id' => $this->employer->id,
            'course_id' => $this->course->id
        ]);

        $graduates = Graduate::factory()->count(5)->create([
            'course_id' => $this->course->id
        ]);

        // Create applications
        foreach ($graduates as $graduate) {
            JobApplication::factory()->create([
                'job_id' => $job->id,
                'graduate_id' => $graduate->id,
                'status' => 'pending'
            ]);
        }

        $this->actingAs($this->employerUser);

        // 1. View applications
        $response = $this->get(route('jobs.applications.index', $job));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Jobs/Applications/Index')
                ->has('applications', 5)
        );

        // 2. Review application
        $application = $job->applications->first();
        $response = $this->get(route('job-applications.show', $application));
        $response->assertStatus(200);

        // 3. Update application status
        $response = $this->put(route('job-applications.update', $application), [
            'status' => 'reviewed',
            'notes' => 'Good candidate, moving to next stage'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $application->refresh();
        $this->assertEquals('reviewed', $application->status);

        // 4. Shortlist candidate
        $response = $this->put(route('job-applications.update', $application), [
            'status' => 'shortlisted',
            'notes' => 'Shortlisted for interview'
        ]);

        $application->refresh();
        $this->assertEquals('shortlisted', $application->status);

        // 5. Schedule interview
        $response = $this->post(route('job-applications.schedule-interview', $application), [
            'interview_date' => now()->addDays(7)->format('Y-m-d H:i'),
            'interview_location' => 'Office Conference Room A',
            'interview_notes' => 'Technical interview with team lead'
        ]);

        $response->assertRedirect();
        $application->refresh();
        $this->assertEquals('interview_scheduled', $application->status);
        $this->assertNotNull($application->interview_scheduled_at);

        // 6. Make job offer
        $response = $this->post(route('job-applications.make-offer', $application), [
            'offered_salary' => 75000,
            'offer_expiry_date' => now()->addDays(14)->format('Y-m-d'),
            'offer_details' => [
                'start_date' => now()->addMonth()->format('Y-m-d'),
                'benefits' => ['Health Insurance', 'Dental Coverage']
            ]
        ]);

        $response->assertRedirect();
        $application->refresh();
        $this->assertEquals('offer_made', $application->status);
        $this->assertEquals(75000, $application->offered_salary);
    }

    public function test_job_approval_workflow_for_unverified_employer(): void
    {
        // Create unverified employer
        $unverifiedEmployer = Employer::factory()->pending()->create();
        $unverifiedUser = $this->createUserWithRole('employer');
        $unverifiedEmployer->update(['user_id' => $unverifiedUser->id]);

        $this->actingAs($unverifiedUser);

        // 1. Create job (should require approval)
        $jobData = [
            'title' => 'Junior Developer',
            'description' => 'Entry level position',
            'location' => 'New York',
            'course_id' => $this->course->id,
            'salary_min' => 45000,
            'salary_max' => 55000
        ];

        $response = $this->post(route('jobs.store'), $jobData);
        $response->assertRedirect();

        $job = Job::where('title', 'Junior Developer')->first();
        $this->assertEquals('pending', $job->status);

        // 2. Super admin reviews job
        $superAdmin = $this->createUserWithRole('super-admin');
        $this->actingAs($superAdmin);

        $response = $this->get(route('admin.job-approval.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/JobApproval/Index')
                ->has('pendingJobs')
        );

        // 3. Approve job
        $response = $this->post(route('admin.job-approval.approve', $job), [
            'notes' => 'Job approved after review'
        ]);

        $response->assertRedirect();
        $job->refresh();
        $this->assertEquals('active', $job->status);
    }

    public function test_job_matching_and_recommendations(): void
    {
        // Create graduates with specific skills
        $phpGraduates = Graduate::factory()->count(3)->create([
            'course_id' => $this->course->id,
            'skills' => ['PHP', 'Laravel', 'MySQL'],
            'job_search_active' => true,
            'allow_employer_contact' => true
        ]);

        $jsGraduates = Graduate::factory()->count(2)->create([
            'course_id' => $this->course->id,
            'skills' => ['JavaScript', 'React', 'Node.js'],
            'job_search_active' => true,
            'allow_employer_contact' => true
        ]);

        // Create PHP job
        $phpJob = Job::factory()->active()->create([
            'employer_id' => $this->employer->id,
            'course_id' => $this->course->id,
            'title' => 'PHP Developer',
            'required_skills' => ['PHP', 'Laravel']
        ]);

        $this->actingAs($this->employerUser);

        // 1. View job matches
        $response = $this->get(route('jobs.matches', $phpJob));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Jobs/Matches')
                ->has('matches', 3) // Should match PHP graduates
        );

        // 2. Send job recommendations to graduates
        $response = $this->post(route('jobs.send-recommendations', $phpJob), [
            'graduate_ids' => $phpGraduates->pluck('id')->toArray(),
            'message' => 'We think you would be a great fit for this position!'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_job_search_and_filtering(): void
    {
        // Create various jobs
        Job::factory()->count(3)->create([
            'employer_id' => $this->employer->id,
            'course_id' => $this->course->id,
            'location' => 'Remote',
            'job_type' => 'full-time',
            'required_skills' => ['PHP', 'Laravel']
        ]);

        Job::factory()->count(2)->create([
            'employer_id' => $this->employer->id,
            'course_id' => $this->course->id,
            'location' => 'New York',
            'job_type' => 'part-time',
            'required_skills' => ['JavaScript', 'React']
        ]);

        // Test public job search
        $response = $this->get(route('jobs.public.index', [
            'location' => 'Remote',
            'job_type' => 'full-time',
            'skills' => ['PHP']
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Jobs/PublicIndex')
                ->has('jobs.data', 3)
        );

        // Test advanced search
        $response = $this->get(route('jobs.public.index', [
            'course_id' => $this->course->id,
            'salary_min' => 50000,
            'experience_level' => 'entry'
        ]));

        $response->assertStatus(200);
    }

    public function test_job_expiry_and_renewal(): void
    {
        $this->actingAs($this->employerUser);

        // Create job with near expiry
        $job = Job::factory()->create([
            'employer_id' => $this->employer->id,
            'application_deadline' => now()->addDays(2),
            'status' => 'active'
        ]);

        // 1. View expiring jobs
        $response = $this->get(route('employer.dashboard'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Dashboard/Employer')
                ->has('expiringJobs')
        );

        // 2. Renew job
        $response = $this->post(route('jobs.renew', $job), [
            'new_deadline' => now()->addDays(30)->format('Y-m-d')
        ]);

        $response->assertRedirect();
        $job->refresh();
        $this->assertTrue($job->application_deadline->isFuture());

        // 3. Close job manually
        $response = $this->post(route('jobs.close', $job), [
            'reason' => 'Position filled'
        ]);

        $response->assertRedirect();
        $job->refresh();
        $this->assertEquals('closed', $job->status);
    }

    public function test_bulk_job_operations(): void
    {
        $this->actingAs($this->employerUser);

        // Create multiple jobs
        $jobs = Job::factory()->count(5)->create([
            'employer_id' => $this->employer->id,
            'status' => 'active'
        ]);

        // 1. Bulk update job status
        $response = $this->post(route('jobs.bulk-update'), [
            'job_ids' => $jobs->pluck('id')->toArray(),
            'action' => 'pause',
            'reason' => 'Temporary pause for review'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        foreach ($jobs as $job) {
            $job->refresh();
            $this->assertEquals('paused', $job->status);
        }

        // 2. Bulk delete jobs
        $response = $this->post(route('jobs.bulk-delete'), [
            'job_ids' => $jobs->take(2)->pluck('id')->toArray(),
            'confirm' => true
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('jobs', ['id' => $jobs->first()->id]);
    }
}