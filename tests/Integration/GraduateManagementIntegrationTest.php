<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Graduate;
use App\Models\Course;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Employer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class GraduateManagementIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $institutionAdmin;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->institutionAdmin = $this->createUserWithRole('institution-admin');
        $this->course = Course::factory()->create();
    }

    public function test_complete_graduate_management_workflow(): void
    {
        $this->actingAs($this->institutionAdmin);

        // 1. Create a graduate
        $graduateData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123-456-7890',
            'student_id' => 'STU001',
            'course_id' => $this->course->id,
            'graduation_year' => 2024,
            'gpa' => 3.5
        ];

        $response = $this->post(route('graduates.store'), $graduateData);
        $response->assertRedirect(route('graduates.index'));
        
        $graduate = Graduate::where('email', 'john@example.com')->first();
        $this->assertNotNull($graduate);

        // 2. Update graduate profile
        $updateData = [
            'employment_status' => [
                'status' => 'employed',
                'job_title' => 'Software Developer',
                'company' => 'Tech Corp',
                'salary' => 75000
            ],
            'skills' => ['PHP', 'Laravel', 'JavaScript'],
            'certifications' => ['Laravel Certified Developer']
        ];

        $response = $this->put(route('graduates.update', $graduate), $updateData);
        $response->assertRedirect(route('graduates.show', $graduate));

        $graduate->refresh();
        $this->assertEquals('employed', $graduate->employment_status['status']);
        $this->assertContains('PHP', $graduate->skills);

        // 3. Search and filter graduates
        $response = $this->get(route('graduates.index', [
            'search' => 'John',
            'employment_status' => 'employed',
            'course_id' => $this->course->id
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Graduates/Index')
                ->has('graduates.data', 1)
        );

        // 4. Export graduates
        $response = $this->post(route('graduates.export'), [
            'format' => 'excel',
            'filters' => ['course_id' => $this->course->id]
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_graduate_import_workflow(): void
    {
        $this->actingAs($this->institutionAdmin);

        Storage::fake('local');

        // Create a mock Excel file
        $file = UploadedFile::fake()->create('graduates.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // 1. Upload import file
        $response = $this->post(route('graduates.import.upload'), [
            'file' => $file
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('import_file');

        // 2. Preview import
        $response = $this->get(route('graduates.import.preview'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Graduates/ImportPreview'));

        // 3. Process import
        $response = $this->post(route('graduates.import.process'), [
            'confirm' => true,
            'skip_duplicates' => true
        ]);

        $response->assertRedirect(route('graduates.import.history'));
        $response->assertSessionHas('success');
    }

    public function test_graduate_profile_completion_tracking(): void
    {
        $graduate = Graduate::factory()->create([
            'course_id' => $this->course->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => null, // Missing phone
            'address' => null, // Missing address
            'skills' => null, // Missing skills
            'employment_status' => null // Missing employment status
        ]);

        $this->actingAs($this->institutionAdmin);

        // Check initial completion percentage
        $response = $this->get(route('graduates.show', $graduate));
        $response->assertStatus(200);
        
        $graduate->refresh();
        $this->assertLessThan(100, $graduate->profile_completion_percentage);

        // Update profile to increase completion
        $response = $this->put(route('graduates.update', $graduate), [
            'phone' => '123-456-7890',
            'address' => '123 Main St',
            'skills' => ['PHP', 'JavaScript'],
            'employment_status' => ['status' => 'unemployed']
        ]);

        $graduate->refresh();
        $this->assertGreaterThan(50, $graduate->profile_completion_percentage);
    }

    public function test_graduate_job_application_workflow(): void
    {
        // Create graduate user
        $graduateUser = $this->createUserWithRole('graduate');
        $graduate = Graduate::factory()->create([
            'user_id' => $graduateUser->id,
            'course_id' => $this->course->id
        ]);

        // Create employer and job
        $employer = Employer::factory()->verified()->create();
        $job = Job::factory()->active()->create([
            'employer_id' => $employer->id,
            'course_id' => $this->course->id
        ]);

        $this->actingAs($graduateUser);

        // 1. Browse jobs
        $response = $this->get(route('jobs.public.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Jobs/PublicIndex'));

        // 2. View job details
        $response = $this->get(route('jobs.public.show', $job));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Jobs/Show'));

        // 3. Apply for job
        Storage::fake('local');
        $resume = UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

        $response = $this->post(route('job-applications.store'), [
            'job_id' => $job->id,
            'cover_letter' => 'I am interested in this position...',
            'resume' => $resume
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $application = JobApplication::where('job_id', $job->id)
                                   ->where('graduate_id', $graduate->id)
                                   ->first();
        $this->assertNotNull($application);
        $this->assertEquals('pending', $application->status);

        // 4. Track application status
        $response = $this->get(route('graduate.applications'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Graduate/Applications')
                ->has('applications', 1)
        );
    }

    public function test_graduate_analytics_integration(): void
    {
        $this->actingAs($this->institutionAdmin);

        // Create graduates with different statuses
        Graduate::factory()->count(5)->create([
            'course_id' => $this->course->id,
            'employment_status' => ['status' => 'employed']
        ]);
        Graduate::factory()->count(3)->create([
            'course_id' => $this->course->id,
            'employment_status' => ['status' => 'unemployed']
        ]);

        // View course analytics
        $response = $this->get(route('courses.analytics', $this->course));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Courses/Analytics')
                ->has('analytics.employment_rate')
                ->has('analytics.total_graduates')
        );

        // View institution dashboard with analytics
        $response = $this->get(route('institution-admin.dashboard'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('InstitutionAdmin/Dashboard')
                ->has('analytics')
        );
    }

    public function test_graduate_privacy_settings_workflow(): void
    {
        $graduateUser = $this->createUserWithRole('graduate');
        $graduate = Graduate::factory()->create([
            'user_id' => $graduateUser->id,
            'course_id' => $this->course->id,
            'privacy_settings' => [
                'profile_visible' => true,
                'allow_employer_contact' => true,
                'show_employment_status' => true
            ]
        ]);

        $this->actingAs($graduateUser);

        // Update privacy settings
        $response = $this->put(route('graduates.privacy', $graduate), [
            'privacy_settings' => [
                'profile_visible' => false,
                'allow_employer_contact' => false,
                'show_employment_status' => false
            ]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $graduate->refresh();
        $this->assertFalse($graduate->privacy_settings['profile_visible']);
        $this->assertFalse($graduate->privacy_settings['allow_employer_contact']);

        // Test that graduate is not visible in employer searches
        $employerUser = $this->createUserWithRole('employer');
        $this->actingAs($employerUser);

        $response = $this->get(route('search.graduates', [
            'course_id' => $this->course->id
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Search/Index')
                ->where('results', fn ($results) => 
                    collect($results)->doesntContain('id', $graduate->id)
                )
        );
    }

    public function test_graduate_audit_trail_integration(): void
    {
        $this->actingAs($this->institutionAdmin);

        $graduate = Graduate::factory()->create([
            'course_id' => $this->course->id,
            'name' => 'John Doe',
            'employment_status' => ['status' => 'unemployed']
        ]);

        // Update graduate employment status
        $response = $this->put(route('graduates.employment', $graduate), [
            'employment_status' => [
                'status' => 'employed',
                'job_title' => 'Developer',
                'company' => 'Tech Corp'
            ]
        ]);

        $response->assertRedirect();

        // Check audit trail
        $this->assertDatabaseHas('graduate_audit_logs', [
            'graduate_id' => $graduate->id,
            'user_id' => $this->institutionAdmin->id,
            'action' => 'employment_updated',
            'field_name' => 'employment_status'
        ]);

        // View audit history
        $response = $this->get(route('graduates.audit', $graduate));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Graduates/AuditHistory')
                ->has('auditLogs')
        );
    }
}