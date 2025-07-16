<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Course;
use App\Models\Graduate;
use App\Models\Employer;
use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Hash;

class GraduateTrackingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test institutions (tenants)
        $institutions = [
            [
                'id' => 'tech-institute',
                'name' => 'Tech Institute of Excellence',
                'address' => '123 Technology Street, Tech City',
                'contact_information' => ['phone' => '+1-555-0101', 'email' => 'info@techexcellence.edu'],
            ],
            [
                'id' => 'business-college',
                'name' => 'Business Leadership College',
                'address' => '456 Business Avenue, Commerce City',
                'contact_information' => ['phone' => '+1-555-0102', 'email' => 'info@businessleadership.edu'],
            ],
            [
                'id' => 'health-academy',
                'name' => 'Health Sciences Academy',
                'address' => '789 Medical Drive, Health City',
                'contact_information' => ['phone' => '+1-555-0103', 'email' => 'info@healthacademy.edu'],
            ],
        ];

        foreach ($institutions as $institutionData) {
            $tenant = Tenant::create($institutionData);
            
            // Create courses for each institution
            $this->createCoursesForInstitution($tenant);
            
            // Create graduates for each institution
            $this->createGraduatesForInstitution($tenant);
        }

        // Create employers (these are global, not tenant-specific)
        $this->createEmployers();

        // Create jobs
        $this->createJobs();

        // Create job applications
        $this->createJobApplications();

        $this->command->info('Graduate Tracking System seeded successfully!');
    }

    private function createCoursesForInstitution(Tenant $tenant): void
    {
        $coursesByInstitution = [
            'tech-institute' => [
                'Software Development',
                'Web Development',
                'Mobile App Development',
                'Data Science',
                'Cybersecurity',
                'Network Administration',
            ],
            'business-college' => [
                'Business Administration',
                'Digital Marketing',
                'Project Management',
                'Accounting',
                'Human Resources',
                'Supply Chain Management',
            ],
            'health-academy' => [
                'Nursing',
                'Medical Assistant',
                'Pharmacy Technician',
                'Health Information Management',
                'Medical Laboratory Technology',
                'Radiologic Technology',
            ],
        ];

        $courses = $coursesByInstitution[$tenant->id] ?? ['General Studies'];

        foreach ($courses as $courseName) {
            Course::factory()
                ->state([
                    'institution_id' => $tenant->id,
                    'name' => $courseName,
                ])
                ->active()
                ->create();
        }

        // Create some featured courses
        Course::where('institution_id', $tenant->id)
            ->inRandomOrder()
            ->limit(2)
            ->update(['is_featured' => true]);
    }

    private function createGraduatesForInstitution(Tenant $tenant): void
    {
        $courses = Course::where('institution_id', $tenant->id)->get();

        foreach ($courses as $course) {
            // Create graduates for different years
            for ($year = 2020; $year <= 2024; $year++) {
                $graduateCount = rand(15, 40);
                
                Graduate::factory()
                    ->count($graduateCount)
                    ->state([
                        'tenant_id' => $tenant->id,
                        'course_id' => $course->id,
                        'graduation_year' => $year,
                    ])
                    ->create();
            }

            // Create some high-performing graduates
            Graduate::factory()
                ->count(5)
                ->highPerformer()
                ->employed()
                ->state([
                    'tenant_id' => $tenant->id,
                    'course_id' => $course->id,
                    'graduation_year' => 2024,
                ])
                ->create();

            // Update course statistics
            $course->updateStatistics();
        }
    }

    private function createEmployers(): void
    {
        // Create verified employers
        Employer::factory()
            ->count(20)
            ->verified()
            ->create();

        // Create pending employers
        Employer::factory()
            ->count(8)
            ->pending()
            ->create();

        // Create rejected employers
        Employer::factory()
            ->count(3)
            ->rejected()
            ->create();

        // Create premium employers
        Employer::factory()
            ->count(5)
            ->premium()
            ->verified()
            ->create();
    }

    private function createJobs(): void
    {
        $verifiedEmployers = Employer::verified()->get();
        $courses = Course::all();

        foreach ($verifiedEmployers as $employer) {
            // Create active jobs
            Job::factory()
                ->count(rand(2, 8))
                ->active()
                ->state([
                    'employer_id' => $employer->id,
                    'course_id' => $courses->random()->id,
                ])
                ->create();

            // Create some entry-level jobs
            Job::factory()
                ->count(rand(1, 3))
                ->entryLevel()
                ->active()
                ->state([
                    'employer_id' => $employer->id,
                    'course_id' => $courses->random()->id,
                ])
                ->create();

            // Create some remote jobs
            Job::factory()
                ->count(rand(0, 2))
                ->remote()
                ->active()
                ->state([
                    'employer_id' => $employer->id,
                    'course_id' => $courses->random()->id,
                ])
                ->create();

            // Update employer job statistics
            $employer->updateJobStats();
        }

        // Create some pending jobs for unverified employers
        $pendingEmployers = Employer::pending()->limit(5)->get();
        foreach ($pendingEmployers as $employer) {
            Job::factory()
                ->count(rand(1, 3))
                ->pending()
                ->state([
                    'employer_id' => $employer->id,
                    'course_id' => $courses->random()->id,
                ])
                ->create();
        }
    }

    private function createJobApplications(): void
    {
        $activeJobs = Job::active()->get();
        $graduates = Graduate::jobSearchActive()->get();

        foreach ($activeJobs as $job) {
            $applicationCount = rand(5, 25);
            $applicants = $graduates->random(min($applicationCount, $graduates->count()));

            foreach ($applicants as $graduate) {
                // Avoid duplicate applications
                if (!JobApplication::where('job_id', $job->id)
                    ->where('graduate_id', $graduate->id)
                    ->exists()) {
                    
                    JobApplication::factory()
                        ->state([
                            'job_id' => $job->id,
                            'graduate_id' => $graduate->id,
                        ])
                        ->create();
                }
            }

            // Update job application statistics
            $job->updateApplicationStats();
        }

        // Create some high-priority applications
        JobApplication::factory()
            ->count(20)
            ->highPriority()
            ->state([
                'job_id' => $activeJobs->random()->id,
                'graduate_id' => $graduates->random()->id,
            ])
            ->create();

        // Create some hired applications
        JobApplication::factory()
            ->count(15)
            ->hired()
            ->state([
                'job_id' => $activeJobs->random()->id,
                'graduate_id' => $graduates->random()->id,
            ])
            ->create();
    }
}