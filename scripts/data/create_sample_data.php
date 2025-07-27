<?php

require_once '../../vendor/autoload.php';

use App\Models\User;
use App\Models\Course;
use App\Models\Graduate;
use App\Models\Employer;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\KpiDefinition;
use App\Models\PredictionModel;
use Spatie\Permission\Models\Role;

// Bootstrap Laravel
$app = require_once '../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Creating sample data for analytics testing...\n";

try {
    // Create roles if they don't exist
    $roles = ['super-admin', 'institution-admin', 'graduate', 'employer'];
    foreach ($roles as $roleName) {
        Role::firstOrCreate(['name' => $roleName]);
    }

    // Create a test user
    $user = User::firstOrCreate([
        'email' => 'admin@test.com'
    ], [
        'name' => 'Test Admin',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    
    $user->assignRole('super-admin');
    echo "✓ Created admin user: admin@test.com (password: password)\n";

    // Create courses
    $courses = [
        ['name' => 'Computer Science', 'code' => 'CS101', 'department' => 'Technology'],
        ['name' => 'Business Administration', 'code' => 'BA101', 'department' => 'Business'],
        ['name' => 'Engineering', 'code' => 'ENG101', 'department' => 'Engineering'],
        ['name' => 'Marketing', 'code' => 'MKT101', 'department' => 'Business'],
        ['name' => 'Data Science', 'code' => 'DS101', 'department' => 'Technology'],
    ];

    $createdCourses = [];
    foreach ($courses as $courseData) {
        $course = Course::firstOrCreate([
            'code' => $courseData['code']
        ], [
            'name' => $courseData['name'],
            'department' => $courseData['department'],
            'description' => "Sample course: {$courseData['name']}",
            'duration_months' => 12,
            'level' => 'degree',
            'study_mode' => 'full_time',
            'skills_gained' => json_encode(['Communication', 'Problem Solving', 'Teamwork']),
            'career_paths' => json_encode(['Analyst', 'Specialist', 'Manager']),
            'is_active' => true,
        ]);
        $createdCourses[] = $course;
    }
    echo "✓ Created " . count($createdCourses) . " courses\n";

    // Create employers
    $employers = [
        ['company_name' => 'Tech Corp', 'industry' => 'Technology'],
        ['company_name' => 'Business Solutions Ltd', 'industry' => 'Consulting'],
        ['company_name' => 'Engineering Works', 'industry' => 'Engineering'],
        ['company_name' => 'Marketing Agency', 'industry' => 'Marketing'],
        ['company_name' => 'Data Analytics Inc', 'industry' => 'Technology'],
    ];

    $createdEmployers = [];
    foreach ($employers as $employerData) {
        $employerUser = User::firstOrCreate([
            'email' => strtolower(str_replace(' ', '', $employerData['company_name'])) . '@company.com'
        ], [
            'name' => $employerData['company_name'],
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        
        $employerUser->assignRole('employer');

        $employer = Employer::firstOrCreate([
            'user_id' => $employerUser->id
        ], [
            'company_name' => $employerData['company_name'],
            'industry' => $employerData['industry'],
            'company_size' => 'medium',
            'location' => 'New York',
            'verification_status' => 'verified',
            'total_hires' => rand(5, 25),
        ]);
        $createdEmployers[] = $employer;
    }
    echo "✓ Created " . count($createdEmployers) . " employers\n";

    // Create graduates
    $graduateCount = 50;
    $createdGraduates = [];
    
    for ($i = 1; $i <= $graduateCount; $i++) {
        $graduateUser = User::firstOrCreate([
            'email' => "graduate{$i}@test.com"
        ], [
            'name' => "Graduate {$i}",
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        
        $graduateUser->assignRole('graduate');

        $course = $createdCourses[array_rand($createdCourses)];
        $graduationYear = rand(2022, 2024);
        
        // Random employment status
        $employmentStatuses = ['employed', 'unemployed', 'seeking'];
        $status = $employmentStatuses[array_rand($employmentStatuses)];
        
        $employmentStatus = ['status' => $status];
        if ($status === 'employed') {
            $employer = $createdEmployers[array_rand($createdEmployers)];
            $employmentStatus['company'] = $employer->company_name;
            $employmentStatus['job_title'] = 'Software Developer';
            $employmentStatus['salary_range'] = ['30k_40k', '40k_50k', '50k_75k'][array_rand(['30k_40k', '40k_50k', '50k_75k'])];
            $employmentStatus['start_date'] = now()->subDays(rand(30, 365))->toDateString();
        }

        $graduate = Graduate::firstOrCreate([
            'user_id' => $graduateUser->id
        ], [
            'course_id' => $course->id,
            'student_id' => "STU{$i}",
            'graduation_date' => "{$graduationYear}-06-15",
            'graduation_year' => $graduationYear,
            'gpa' => rand(250, 400) / 100, // 2.5 to 4.0
            'employment_status' => $employmentStatus,
            'skills' => ['PHP', 'Laravel', 'JavaScript', 'MySQL'],
            'certifications' => rand(0, 1) ? ['Laravel Certified Developer'] : [],
            'profile_completion' => rand(60, 100),
            'location' => 'New York',
        ]);
        $createdGraduates[] = $graduate;
    }
    echo "✓ Created {$graduateCount} graduates\n";

    // Create jobs
    $jobCount = 30;
    $createdJobs = [];
    
    for ($i = 1; $i <= $jobCount; $i++) {
        $employer = $createdEmployers[array_rand($createdEmployers)];
        $course = $createdCourses[array_rand($createdCourses)];
        
        $job = Job::firstOrCreate([
            'title' => "Job Position {$i}",
            'employer_id' => $employer->id
        ], [
            'course_id' => $course->id,
            'description' => "Sample job description for position {$i}",
            'requirements' => 'Bachelor degree required',
            'location' => 'New York',
            'job_type' => ['full-time', 'part-time', 'contract'][array_rand(['full-time', 'part-time', 'contract'])],
            'salary_min' => rand(30000, 50000),
            'salary_max' => rand(60000, 80000),
            'required_skills' => ['PHP', 'Laravel', 'JavaScript'],
            'status' => ['active', 'filled', 'closed'][array_rand(['active', 'filled', 'closed'])],
            'deadline' => now()->addDays(rand(7, 30)),
        ]);
        $createdJobs[] = $job;
    }
    echo "✓ Created {$jobCount} jobs\n";

    // Create job applications
    $applicationCount = 100;
    
    for ($i = 1; $i <= $applicationCount; $i++) {
        $graduate = $createdGraduates[array_rand($createdGraduates)];
        $job = $createdJobs[array_rand($createdJobs)];
        
        // Check if application already exists
        $existingApplication = JobApplication::where('graduate_id', $graduate->id)
            ->where('job_id', $job->id)
            ->first();
            
        if (!$existingApplication) {
            $status = ['pending', 'reviewed', 'interviewed', 'hired', 'rejected'][array_rand(['pending', 'reviewed', 'interviewed', 'hired', 'rejected'])];
            
            JobApplication::create([
                'graduate_id' => $graduate->id,
                'job_id' => $job->id,
                'status' => $status,
                'cover_letter' => "Sample cover letter for application {$i}",
                'applied_at' => now()->subDays(rand(1, 60)),
            ]);
        }
    }
    echo "✓ Created job applications\n";

    // Seed KPI definitions
    $kpis = [
        [
            'name' => 'Employment Rate',
            'key' => 'employment_rate',
            'description' => 'Percentage of graduates who are currently employed',
            'category' => 'employment',
            'calculation_method' => 'percentage',
            'calculation_config' => [
                'numerator' => [
                    'model' => 'App\\Models\\Graduate',
                    'filters' => [
                        ['field' => 'employment_status->status', 'operator' => '=', 'value' => 'employed']
                    ]
                ],
                'denominator' => [
                    'model' => 'App\\Models\\Graduate',
                    'filters' => []
                ]
            ],
            'target_type' => 'minimum',
            'target_value' => 80.0,
            'warning_threshold' => 70.0,
            'is_active' => true,
        ],
        [
            'name' => 'Job Placement Rate',
            'key' => 'job_placement_rate',
            'description' => 'Percentage of job applications that result in employment',
            'category' => 'employment',
            'calculation_method' => 'percentage',
            'calculation_config' => [
                'numerator' => [
                    'model' => 'App\\Models\\JobApplication',
                    'filters' => [
                        ['field' => 'status', 'operator' => '=', 'value' => 'hired']
                    ]
                ],
                'denominator' => [
                    'model' => 'App\\Models\\JobApplication',
                    'filters' => []
                ]
            ],
            'target_type' => 'minimum',
            'target_value' => 25.0,
            'warning_threshold' => 15.0,
            'is_active' => true,
        ],
    ];

    foreach ($kpis as $kpiData) {
        KpiDefinition::firstOrCreate([
            'key' => $kpiData['key']
        ], $kpiData);
    }
    echo "✓ Created KPI definitions\n";

    // Seed prediction models
    $models = [
        [
            'name' => 'Job Placement Predictor',
            'type' => 'job_placement',
            'description' => 'Predicts the likelihood of a graduate finding employment',
            'features' => [
                'graduation_year',
                'course_employment_rate',
                'gpa',
                'skills_count',
                'certifications_count',
                'profile_completion',
            ],
            'model_config' => [
                'feature_weights' => [
                    'graduation_year' => 0.1,
                    'course_employment_rate' => 0.3,
                    'gpa' => 0.2,
                    'skills_count' => 0.15,
                    'certifications_count' => 0.1,
                    'profile_completion' => 0.15,
                ],
                'max_score' => 100,
                'prediction_horizon' => 90,
                'retraining_interval' => 30,
            ],
            'accuracy' => 0.75,
            'is_active' => true,
        ],
    ];

    foreach ($models as $modelData) {
        PredictionModel::firstOrCreate([
            'type' => $modelData['type']
        ], $modelData);
    }
    echo "✓ Created prediction models\n";

    echo "\n🎉 Sample data created successfully!\n";
    echo "\nYou can now:\n";
    echo "1. Login with: admin@test.com / password\n";
    echo "2. Visit /analytics/dashboard to see the analytics\n";
    echo "3. Visit /analytics/kpis to see KPI tracking\n";
    echo "4. Visit /analytics/reports to create custom reports\n";
    echo "5. Visit /analytics/predictions to see predictive analytics\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}