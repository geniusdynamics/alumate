<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Course;
use App\Models\Graduate;
use App\Models\Employer;
use App\Models\Job;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = Application::configure(basePath: __DIR__ . '/../..')
    ->withRouting(
        web: __DIR__ . '/../../routes/web.php',
        commands: __DIR__ . '/../../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Creating tenant-aware sample data for Graduate Tracking System...\n";

try {
    // Create central system admin user
    $adminUser = User::firstOrCreate([
        'email' => 'admin@system.com'
    ], [
        'name' => 'System Administrator',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
        'status' => 'active',
    ]);

    // Ensure Super Admin role exists and assign it
    $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
    if (!$adminUser->hasRole('Super Admin')) {
        $adminUser->assignRole('Super Admin');
    }

    echo "✓ Created system admin user: admin@system.com (password: password)\n";

    // Get existing tenants
    $tenants = Tenant::all();

    if ($tenants->isEmpty()) {
        echo "❌ No tenants found. Please run the seeder first.\n";
        exit(1);
    }

    foreach ($tenants as $tenant) {
        if ($tenant->id === 'default') {
            continue; // Skip default tenant
        }

        echo "\n--- Working with tenant: {$tenant->name} ({$tenant->id}) ---\n";

        // Switch to tenant context
        tenancy()->initialize($tenant);

        // Create institution admin for this tenant
        $adminUser = User::firstOrCreate([
            'email' => "admin@{$tenant->id}.edu"
        ], [
            'name' => "{$tenant->name} Administrator",
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $institutionAdminRole = Role::firstOrCreate(['name' => 'Institution Admin']);
        if (!$adminUser->hasRole('Institution Admin')) {
            $adminUser->assignRole('Institution Admin');
        }

        echo "✓ Created institution admin: admin@{$tenant->id}.edu\n";

        // Create sample courses for this tenant
        $courses = [
            [
                'name' => 'Computer Science',
                'code' => 'CS101',
                'level' => 'degree',
                'department' => 'Technology',
                'duration_months' => 48,
                'study_mode' => 'full_time',
            ],
            [
                'name' => 'Business Administration',
                'code' => 'BA101',
                'level' => 'degree',
                'department' => 'Business',
                'duration_months' => 36,
                'study_mode' => 'full_time',
            ],
        ];

        $createdCourses = [];
        foreach ($courses as $courseData) {
            $course = Course::firstOrCreate([
                'code' => $courseData['code']
            ], array_merge($courseData, [
                    'description' => "Sample course: {$courseData['name']}",
                    'skills_gained' => json_encode(['Communication', 'Problem Solving', 'Teamwork']),
                    'career_paths' => json_encode(['Analyst', 'Specialist', 'Manager']),
                    'is_active' => true,
                ]));
            $createdCourses[] = $course;
        }
        echo "✓ Created " . count($createdCourses) . " courses\n";

        // Create sample graduates for this tenant
        $graduateData = [
            [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@student.edu',
                'student_id' => 'STU001',
            ],
            [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane.doe@student.edu',
                'student_id' => 'STU002',
            ],
        ];

        $createdGraduates = [];
        foreach ($graduateData as $index => $gradData) {
            // Create user for graduate
            $graduateUser = User::firstOrCreate([
                'email' => $gradData['email']
            ], [
                'name' => $gradData['first_name'] . ' ' . $gradData['last_name'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => 'active',
            ]);

            $graduateRole = Role::firstOrCreate(['name' => 'Graduate']);
            if (!$graduateUser->hasRole('Graduate')) {
                $graduateUser->assignRole('Graduate');
            }

            // Create graduate record
            $graduate = Graduate::firstOrCreate([
                'student_id' => $gradData['student_id']
            ], [
                'tenant_id' => $tenant->id,
                'user_id' => $graduateUser->id,
                'first_name' => $gradData['first_name'],
                'last_name' => $gradData['last_name'],
                'email' => $gradData['email'],
                'course_id' => $createdCourses[$index % count($createdCourses)]->id,
                'graduation_year' => 2023,
                'employment_status' => 'seeking',
                'phone' => '+1-555-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'date_of_birth' => '1995-01-01',
                'gender' => ['male', 'female'][rand(0, 1)],
            ]);
            $createdGraduates[] = $graduate;
        }
        echo "✓ Created " . count($createdGraduates) . " graduates\n";

        // End tenant context
        tenancy()->end();
    }

    // Back to central context - create employers
    $employers = [
        ['company_name' => 'Tech Corp', 'industry' => 'Technology'],
        ['company_name' => 'Business Solutions Ltd', 'industry' => 'Consulting'],
    ];

    $createdEmployers = [];
    foreach ($employers as $employerData) {
        $employerUser = User::firstOrCreate([
            'email' => strtolower(str_replace(' ', '', $employerData['company_name'])) . '@company.com'
        ], [
            'name' => $employerData['company_name'],
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $employerRole = Role::firstOrCreate(['name' => 'Employer']);
        if (!$employerUser->hasRole('Employer')) {
            $employerUser->assignRole('Employer');
        }

        $employer = Employer::firstOrCreate([
            'user_id' => $employerUser->id
        ], [
            'company_name' => $employerData['company_name'],
            'industry' => $employerData['industry'],
            'company_size' => 'medium',
            'verification_status' => 'verified',
            'total_hires' => rand(5, 25),
            'is_active' => true,
            'can_post_jobs' => true,
        ]);
        $createdEmployers[] = $employer;
    }
    echo "\n✓ Created " . count($createdEmployers) . " employers\n";

    // Create sample jobs
    $jobs = [
        [
            'title' => 'Software Developer',
            'description' => 'We are looking for a skilled software developer...',
            'employment_type' => 'full_time',
            'location' => 'New York, NY',
        ],
        [
            'title' => 'Business Analyst',
            'description' => 'Join our team as a business analyst...',
            'employment_type' => 'full_time',
            'location' => 'Boston, MA',
        ],
    ];

    $createdJobs = [];
    foreach ($jobs as $index => $jobData) {
        $job = Job::firstOrCreate([
            'title' => $jobData['title'],
            'employer_id' => $createdEmployers[$index % count($createdEmployers)]->id
        ], array_merge($jobData, [
                'requirements' => 'Bachelor degree required',
                'responsibilities' => 'Various responsibilities...',
                'salary_min' => 50000,
                'salary_max' => 80000,
                'application_deadline' => now()->addDays(30),
                'status' => 'active',
                'is_published' => true,
                'published_at' => now(),
            ]));
        $createdJobs[] = $job;
    }
    echo "✓ Created " . count($createdJobs) . " jobs\n";

    echo "\n🎉 Sample data created successfully!\n";
    echo "\nYou can now login with:\n";
    echo "- System Admin: admin@system.com / password\n";

    foreach ($tenants as $tenant) {
        if ($tenant->id !== 'default') {
            echo "- {$tenant->name} Admin: admin@{$tenant->id}.edu / password\n";
        }
    }

    foreach ($createdEmployers as $employer) {
        $email = strtolower(str_replace(' ', '', $employer->company_name)) . '@company.com';
        echo "- {$employer->company_name}: {$email} / password\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}