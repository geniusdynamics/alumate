<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TenantContextService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    echo "=== Before setting tenant context ===\n";
    echo "Current schema: " . DB::connection()->getDatabaseName() . "\n";
    
    // Check courses in main database
    $mainCourses = DB::table('courses')->get();
    echo "Courses in main database: " . $mainCourses->count() . "\n";
    foreach ($mainCourses as $course) {
        echo "  - ID: {$course->id}, Code: {$course->course_code}, Name: {$course->name}\n";
    }
    
    // Set tenant context
    $tenantService = app(TenantContextService::class);
    $tenantService->setTenant('tech-institute');
    
    echo "\n=== After setting tenant context ===\n";
    echo "Current schema: " . $tenantService->getCurrentSchema() . "\n";
    
    // Check courses in tenant database
    $tenantCourses = DB::table('courses')->get();
    echo "Courses in tenant database: " . $tenantCourses->count() . "\n";
    foreach ($tenantCourses as $course) {
        echo "  - ID: {$course->id}, Code: {$course->course_code}, Name: {$course->name}\n";
    }
    
    // Try to create a course
    echo "\n=== Creating course ===\n";
    $course = \App\Models\Course::firstOrCreate(
        ['course_code' => 'CS101'],
        [
            'name' => 'Computer Science',
            'description' => 'Introduction to Computer Science',
            'credits' => 3,
            'department' => 'Computer Science',
            'status' => 'active'
        ]
    );
    
    echo "Course created/found with ID: {$course->id}\n";
    echo "Course details: {$course->toJson()}\n";
    
    // Check courses again
    $tenantCoursesAfter = DB::table('courses')->get();
    echo "\nCourses in tenant database after creation: " . $tenantCoursesAfter->count() . "\n";
    foreach ($tenantCoursesAfter as $course) {
        echo "  - ID: {$course->id}, Code: {$course->course_code}, Name: {$course->name}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}