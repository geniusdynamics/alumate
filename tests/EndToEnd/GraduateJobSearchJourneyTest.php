<?php

namespace Tests\EndToEnd;

use App\Models\Course;
use App\Models\Employer;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GraduateJobSearchJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected User $graduateUser;

    protected Graduate $graduate;

    protected Course $course;

    protected Employer $employer;

    protected Job $job;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test data
        $this->course = Course::factory()->create([
            'name' => 'Computer Science',
            'code' => 'CS101',
        ]);

        $this->graduateUser = $this->createUserWithRole('graduate', [
            'name' => 'John Graduate',
            'email' => 'john@graduate.com',
        ]);

        $this->graduate = Graduate::factory()->create([
            'user_id' => $this->graduateUser->id,
            'course_id' => $this->course->id,
            'name' => 'John Graduate',
            'email' => 'john@graduate.com',
            'skills' => ['PHP', 'Laravel', 'JavaScript'],
            'employment_status' => ['status' => 'unemployed'],
            'job_search_active' => true,
            'allow_employer_contact' => true,
            'profile_completion_percentage' => 85,
        ]);

        $this->employer = Employer::factory()->verified()->create([
            'company_name' => 'Tech Solutions Inc',
            'industry' => 'Technology',
        ]);

        $this->job = Job::factory()->active()->create([
            'employer_id' => $this->employer->id,
            'course_id' => $this->course->id,
            'title' => 'Junior PHP Developer',
            'description' => 'We are looking for a junior PHP developer to join our team.',
            'required_skills' => ['PHP', 'Laravel'],
            'salary_min' => 45000,
            'salary_max' => 55000,
            'location' => 'Remote',
            'job_type' => 'full-time',
            'experience_level' => 'entry',
        ]);
    }

    public function test_complete_graduate_job_search_and_application_journey(): void
    {
        Notification::fake();
        Storage::fake('local');

        // Step 1: Graduate logs in and views dashboard
        $this->actingAs($this->graduateUser);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Dashboard')
            ->where('user.name', 'John Graduate')
        );

        // Step 2: Graduate completes profile
        $response = $this->get(route('graduates.edit', $this->graduate));
        $response->assertStatus(200);

        $response = $this->put(route('graduates.update', $this->graduate), [
            'phone' => '123-456-7890',
            'address' => '123 Main Street, City, State',
            'certifications' => ['Laravel Certified Developer'],
            'portfolio_url' => 'https://johndoe.dev',
            'linkedin_url' => 'https://linkedin.com/in/johndoe',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify profile completion increased
        $this->graduate->refresh();
        $this->assertGreaterThan(85, $this->graduate->profile_completion_percentage);

        // Step 3: Graduate browses available jobs
        $response = $this->get(route('graduate.job-browsing'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Graduate/JobBrowsing')
            ->has('jobs')
            ->has('recommendedJobs')
        );

        // Step 4: Graduate searches for specific jobs
        $response = $this->get(route('graduate.job-browsing', [
            'search' => 'PHP',
            'location' => 'Remote',
            'job_type' => 'full-time',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('jobs.data', fn ($jobs) => collect($jobs)->contains('title', 'Junior PHP Developer')
        )
        );

        // Step 5: Graduate views job details
        $response = $this->get(route('jobs.public.show', $this->job));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Jobs/Show')
            ->where('job.title', 'Junior PHP Developer')
            ->where('job.employer.company_name', 'Tech Solutions Inc')
            ->has('matchScore')
        );

        // Step 6: Graduate applies for the job
        $resume = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');
        $coverLetter = 'Dear Hiring Manager, I am very interested in the Junior PHP Developer position...';

        $response = $this->post(route('job-applications.store'), [
            'job_id' => $this->job->id,
            'cover_letter' => $coverLetter,
            'resume' => $resume,
            'additional_documents' => [],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Application submitted successfully!');

        // Verify application was created
        $application = JobApplication::where('job_id', $this->job->id)
            ->where('graduate_id', $this->graduate->id)
            ->first();

        $this->assertNotNull($application);
        $this->assertEquals('pending', $application->status);
        $this->assertEquals($coverLetter, $application->cover_letter);
        $this->assertNotNull($application->resume_path);

        // Step 7: Graduate tracks application status
        $response = $this->get(route('graduate.applications'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Graduate/Applications')
            ->has('applications', 1)
            ->where('applications.0.job.title', 'Junior PHP Developer')
            ->where('applications.0.status', 'pending')
        );

        // Step 8: Graduate views application details
        $response = $this->get(route('graduate.applications.show', $application));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Graduate/ApplicationDetails')
            ->where('application.status', 'pending')
            ->has('application.status_history')
        );

        // Step 9: Simulate employer reviewing application
        $employerUser = $this->createUserWithRole('employer');
        $this->employer->update(['user_id' => $employerUser->id]);

        $this->actingAs($employerUser);

        $response = $this->put(route('job-applications.update', $application), [
            'status' => 'reviewed',
            'notes' => 'Good candidate, matches our requirements',
        ]);

        $response->assertRedirect();

        // Step 10: Graduate receives notification and checks updated status
        $this->actingAs($this->graduateUser);

        $response = $this->get(route('graduate.applications'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('applications.0.status', 'reviewed')
        );

        // Step 11: Employer shortlists candidate
        $this->actingAs($employerUser);

        $response = $this->put(route('job-applications.update', $application), [
            'status' => 'shortlisted',
            'notes' => 'Moving to interview stage',
        ]);

        // Step 12: Employer schedules interview
        $interviewDate = now()->addDays(7);
        $response = $this->post(route('job-applications.schedule-interview', $application), [
            'interview_date' => $interviewDate->format('Y-m-d H:i'),
            'interview_location' => 'Office Conference Room A',
            'interview_notes' => 'Technical interview with development team',
            'interview_type' => 'in-person',
        ]);

        $response->assertRedirect();

        // Step 13: Graduate receives interview notification
        $this->actingAs($this->graduateUser);

        $response = $this->get(route('graduate.applications.show', $application));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('application.status', 'interview_scheduled')
            ->has('application.interview_scheduled_at')
            ->where('application.interview_location', 'Office Conference Room A')
        );

        // Step 14: Graduate confirms interview attendance
        $response = $this->post(route('graduate.applications.confirm-interview', $application), [
            'confirmation' => 'confirmed',
            'message' => 'Thank you for the opportunity. I confirm my attendance.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Step 15: Simulate successful interview and job offer
        $this->actingAs($employerUser);

        $response = $this->post(route('job-applications.make-offer', $application), [
            'offered_salary' => 50000,
            'offer_expiry_date' => now()->addDays(14)->format('Y-m-d'),
            'start_date' => now()->addMonth()->format('Y-m-d'),
            'offer_details' => [
                'benefits' => ['Health Insurance', 'Dental Coverage', 'Remote Work'],
                'vacation_days' => 20,
                'probation_period' => '3 months',
            ],
            'offer_message' => 'We are pleased to offer you the position of Junior PHP Developer.',
        ]);

        $response->assertRedirect();

        // Step 16: Graduate receives and reviews job offer
        $this->actingAs($this->graduateUser);

        $response = $this->get(route('graduate.applications.show', $application));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('application.status', 'offer_made')
            ->where('application.offered_salary', 50000)
            ->has('application.offer_details')
        );

        // Step 17: Graduate accepts job offer
        $response = $this->post(route('graduate.applications.respond-offer', $application), [
            'response' => 'accepted',
            'message' => 'I am excited to accept this position and join your team!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Offer accepted successfully!');

        // Step 18: Verify employment status is updated
        $this->graduate->refresh();
        $this->assertEquals('employed', $this->graduate->employment_status['status']);
        $this->assertEquals('Junior PHP Developer', $this->graduate->employment_status['job_title']);
        $this->assertEquals('Tech Solutions Inc', $this->graduate->employment_status['company']);

        // Step 19: Graduate updates career progress
        $response = $this->get(route('graduate.career-progress'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Graduate/CareerProgress')
            ->where('currentEmployment.job_title', 'Junior PHP Developer')
            ->has('employmentHistory')
        );

        // Step 20: Graduate provides feedback on hiring process
        $response = $this->post(route('graduate.employer-rating'), [
            'employer_id' => $this->employer->id,
            'job_id' => $this->job->id,
            'rating' => 5,
            'review' => 'Excellent hiring process, very professional and transparent.',
            'would_recommend' => true,
            'hiring_process_rating' => 5,
            'communication_rating' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the complete journey was successful
        $application->refresh();
        $this->assertEquals('offer_accepted', $application->status);
        $this->assertNotNull($application->graduate_responded_at);

        // Verify job statistics were updated
        $this->job->refresh();
        $this->assertEquals(1, $this->job->applications_count);
        $this->assertEquals(1, $this->job->successful_hires);

        // Verify course statistics were updated
        $this->course->refresh();
        $courseStats = $this->course->statistics;
        $this->assertGreaterThan(0, $courseStats['employment_rate']);
    }

    public function test_graduate_job_search_with_saved_searches_and_alerts(): void
    {
        $this->actingAs($this->graduateUser);

        // Step 1: Graduate creates saved search
        $response = $this->post(route('search.save'), [
            'name' => 'Remote PHP Jobs',
            'type' => 'jobs',
            'criteria' => [
                'skills' => ['PHP', 'Laravel'],
                'location' => 'Remote',
                'job_type' => 'full-time',
                'salary_min' => 40000,
            ],
            'alert_frequency' => 'daily',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Step 2: Graduate views saved searches
        $response = $this->get(route('search.saved'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Search/SavedSearches')
            ->has('savedSearches', 1)
            ->where('savedSearches.0.name', 'Remote PHP Jobs')
        );

        // Step 3: Graduate executes saved search
        $savedSearch = $this->graduateUser->savedSearches()->first();

        $response = $this->post(route('search.execute', $savedSearch));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Search/Results')
            ->has('results')
        );

        // Step 4: Graduate sets up job alert
        $response = $this->post(route('search.alerts.create'), [
            'name' => 'Laravel Developer Alert',
            'type' => 'jobs',
            'criteria' => [
                'skills' => ['Laravel'],
                'experience_level' => 'entry',
            ],
            'frequency' => 'weekly',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify alert was created
        $this->assertDatabaseHas('search_alerts', [
            'user_id' => $this->graduateUser->id,
            'name' => 'Laravel Developer Alert',
            'type' => 'jobs',
            'frequency' => 'weekly',
            'is_active' => true,
        ]);
    }

    public function test_graduate_networking_and_classmate_connections(): void
    {
        // Create classmates
        $classmates = Graduate::factory()->count(3)->create([
            'course_id' => $this->course->id,
            'graduation_year' => $this->graduate->graduation_year,
        ]);

        $this->actingAs($this->graduateUser);

        // Step 1: Graduate views classmates
        $response = $this->get(route('graduate.classmates'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Graduate/Classmates')
            ->has('classmates', 3)
        );

        // Step 2: Graduate connects with classmate
        $classmate = $classmates->first();

        $response = $this->post(route('graduate.connect', $classmate), [
            'message' => 'Hi! We were in the same class. Would love to connect!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Step 3: Graduate participates in discussion forum
        $response = $this->post(route('discussions.create'), [
            'title' => 'Job Search Tips for CS Graduates',
            'content' => 'What are some effective strategies for finding jobs in tech?',
            'category' => 'career',
            'course_id' => $this->course->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Step 4: Graduate requests assistance from institution
        $response = $this->post(route('graduate.assistance-requests.create'), [
            'type' => 'career_guidance',
            'subject' => 'Need help with interview preparation',
            'description' => 'I have an upcoming technical interview and would like some guidance.',
            'priority' => 'medium',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify assistance request was created
        $this->assertDatabaseHas('assistance_requests', [
            'graduate_id' => $this->graduate->id,
            'type' => 'career_guidance',
            'subject' => 'Need help with interview preparation',
        ]);
    }
}
