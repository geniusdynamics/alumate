<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Services\TenantContextService;
use App\Models\Course;
use App\Models\Graduate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

try {
    echo "=== Debug Seeder Issue ===\n";
    
    // Get tenant
    $tenant = Tenant::find('tech-institute');
    if (!$tenant) {
        echo "ERROR: Tenant 'tech-institute' not found\n";
        exit(1);
    }
    echo "Found tenant: {$tenant->id}\n";
    
    // Set tenant context
    $tenantContextService = app(TenantContextService::class);
    $tenantContextService->setTenant('tech-institute');
    echo "Set tenant context to: {$tenantContextService->getCurrentTenantId()}\n";
    echo "Current schema: {$tenantContextService->getCurrentSchema()}\n";
    
    // Check current courses
    echo "\n=== Current Courses ===\n";
    $courses = Course::all();
    echo "Found {$courses->count()} courses:\n";
    foreach ($courses as $course) {
        echo "- ID: {$course->id}, Code: {$course->course_code}, Name: {$course->name}\n";
    }
    
    // Try to create/find the course
    echo "\n=== Creating/Finding Course ===\n";
    $course = Course::firstOrCreate(
        ['course_code' => 'CS101'],
        [
            'name' => 'Computer Science',
            'description' => 'Introduction to Computer Science',
            'credits' => 3,
            'department' => 'Computer Science',
            'status' => 'active'
        ]
    );
    echo "Course created/found: ID={$course->id}, Code={$course->course_code}\n";
    
    // Check courses again
    echo "\n=== Courses After Creation ===\n";
    $courses = Course::all();
    echo "Found {$courses->count()} courses:\n";
    foreach ($courses as $course) {
        echo "- ID: {$course->id}, Code: {$course->course_code}, Name: {$course->name}\n";
    }
    
    // Check if user exists
    echo "\n=== Checking User ===\n";
    $tenantContextService->clearContext(); // Switch to public schema to find user
    $user = User::where('email', 'john.smith@student.edu')->first();
    if ($user) {
        echo "Found user: ID={$user->id}, Email={$user->email}\n";
    } else {
        echo "User not found\n";
    }
    
    // Set tenant context again
    $tenantContextService->setTenant('tech-institute');
    
    // Check current graduates
    echo "\n=== Current Graduates ===\n";
    $graduates = Graduate::all();
    echo "Found {$graduates->count()} graduates:\n";
    foreach ($graduates as $graduate) {
        echo "- ID: {$graduate->id}, Email: {$graduate->email}, Course ID: {$graduate->course_id}\n";
    }
    
    // Try to create graduate
    echo "\n=== Creating Graduate ===\n";
    if ($user && $course) {
        try {
            $graduate = Graduate::firstOrCreate(
                ['email' => 'john.smith@student.edu'],
                [
                    'tenant_id' => 'tech-institute',
                    'name' => 'John Smith',
                    'email' => 'john.smith@student.edu',
                    'phone' => '+1234567890',
                    'graduation_year' => 2023,
                    'course_id' => $course->id,
                    'user_id' => $user->id,
                ]
            );
            echo "Graduate created/found: ID={$graduate->id}, Email={$graduate->email}\n";
        } catch (Exception $e) {
            echo "ERROR creating graduate: {$e->getMessage()}\n";
            echo "Course ID being used: {$course->id}\n";
            
            // Check if course exists in current context
            $courseCheck = Course::find($course->id);
            if ($courseCheck) {
                echo "Course {$course->id} exists in current context\n";
            } else {
                echo "Course {$course->id} NOT found in current context\n";
            }
        }
    }
    
    $tenantContextService->clearContext();
    echo "\nDebug completed successfully\n";
    
} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}\n";
    echo "Line: {$e->getLine()}\n";
}