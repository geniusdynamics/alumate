<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Job;
use App\Models\Employer;
use App\Models\User;
use Carbon\Carbon;

// First create an employer user
$employerUser = User::firstOrCreate(
    ['email' => 'employer@demo.com'],
    [
        'name' => 'Demo Employer',
        'password' => \Illuminate\Support\Facades\Hash::make('password'),
        'email_verified_at' => now(),
    ]
);

// Assign employer role if it exists
if (\Spatie\Permission\Models\Role::where('name', 'employer')->exists()) {
    $employerUser->assignRole('employer');
}

// Create employer profile
$employer = Employer::firstOrCreate(
    ['user_id' => $employerUser->id],
    [
        'company_name' => 'Demo Tech Company',
        'company_address' => '123 Tech Street, Silicon Valley, CA',
        'company_phone' => '+1-555-0123',
        'company_website' => 'https://demo-tech.com',
        'company_size' => 'medium',
        'industry' => 'Technology',
        'company_description' => 'A leading technology company focused on innovation and growth.',
        'contact_person_name' => 'Demo Employer',
        'contact_person_email' => 'employer@demo.com',
        'verification_status' => 'verified',
        'approved' => true,
        'is_active' => true,
        'can_post_jobs' => true,
        'job_posting_limit' => 50,
        'subscription_plan' => 'premium',
        'subscription_expires_at' => Carbon::now()->addYear(),
    ]
);

echo "Created employer: {$employer->company_name}\n";

// Create sample job listings
$jobs = [
    [
        'employer_id' => $employer->id,
        'title' => 'Senior Software Engineer',
        'company' => 'Tech Innovations Inc.',
        'location' => 'San Francisco, CA',
        'description' => 'We are looking for a senior software engineer to join our dynamic team. You will be responsible for developing scalable web applications and mentoring junior developers.',
        'requirements' => 'Bachelor\'s degree in Computer Science, 5+ years experience, proficiency in PHP, JavaScript, and modern frameworks.',
        'salary_min' => 120000,
        'salary_max' => 180000,
        'job_type' => 'full_time',
        'status' => 'active',
        'application_deadline' => Carbon::now()->addDays(30),
        'posted_at' => Carbon::now(),
    ],
    [
        'employer_id' => $employer->id,
        'title' => 'Marketing Manager',
        'company' => 'Digital Marketing Solutions',
        'location' => 'New York, NY',
        'description' => 'Join our marketing team to develop and execute comprehensive marketing strategies. Lead campaigns across multiple channels and analyze performance metrics.',
        'requirements' => 'Bachelor\'s degree in Marketing or related field, 3+ years experience in digital marketing, strong analytical skills.',
        'salary_min' => 80000,
        'salary_max' => 120000,
        'job_type' => 'full_time',
        'status' => 'active',
        'application_deadline' => Carbon::now()->addDays(25),
        'posted_at' => Carbon::now(),
    ],
    [
        'employer_id' => $employer->id,
        'title' => 'Data Analyst',
        'company' => 'Analytics Corp',
        'location' => 'Austin, TX',
        'description' => 'Analyze large datasets to provide actionable insights for business decisions. Create reports and visualizations to communicate findings to stakeholders.',
        'requirements' => 'Bachelor\'s degree in Statistics, Mathematics, or related field, proficiency in SQL, Python, and data visualization tools.',
        'salary_min' => 70000,
        'salary_max' => 95000,
        'job_type' => 'full_time',
        'status' => 'active',
        'application_deadline' => Carbon::now()->addDays(20),
        'posted_at' => Carbon::now(),
    ],
];

foreach ($jobs as $jobData) {
    $job = Job::firstOrCreate(
        ['title' => $jobData['title'], 'company' => $jobData['company']],
        $jobData
    );
    echo "Created/Updated job: {$job->title} at {$job->company}\n";
}

echo "Job seeding completed!\n";