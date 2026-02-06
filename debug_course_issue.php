<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TenantContextService;
use App\Models\Course;
use App\Models\Tenant;

echo "=== Debugging Course Creation Issue ===\n";

// Check current context
$tenantService = app(TenantContextService::class);
echo "Current schema before setting tenant: " . $tenantService->getCurrentSchema() . "\n";
echo "Current tenant before setting: " . ($tenantService->getCurrentTenantId() ?? 'null') . "\n";

// Skip checking courses in main schema since Course model requires tenant context
echo "Skipping main schema course check (Course model requires tenant context)\n";

// Set tenant context
echo "\nSetting tenant context...\n";
$tenantService->setTenant('tech-institute');
echo "After setting tenant context:\n";
echo "Current schema: " . $tenantService->getCurrentSchema() . "\n";
echo "Current tenant: " . $tenantService->getCurrentTenantId() . "\n";

// Check courses in tenant schema
$coursesInTenant = Course::count();
echo "Courses in tenant schema: $coursesInTenant\n";

if ($coursesInTenant > 0) {
    $tenantCourses = Course::all();
    foreach ($tenantCourses as $course) {
        echo "Tenant course: ID={$course->id}, Code={$course->course_code}, Name={$course->name}\n";
    }
}

// Try to create/find course using firstOrCreate
echo "\nTrying to create/find course using firstOrCreate...\n";
try {
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
    echo "Course created/found: ID={$course->id}, Code={$course->course_code}, Name={$course->name}\n";
    echo "Course was just created: " . ($course->wasRecentlyCreated ? 'Yes' : 'No') . "\n";
} catch (Exception $e) {
    echo "Error creating course: " . $e->getMessage() . "\n";
}

// Check courses again after creation
$coursesAfter = Course::count();
echo "\nCourses in tenant schema after creation: $coursesAfter\n";

if ($coursesAfter > 0) {
    $coursesAfterCreation = Course::all();
    foreach ($coursesAfterCreation as $course) {
        echo "Course after creation: ID={$course->id}, Code={$course->course_code}, Name={$course->name}\n";
    }
}

$tenantService->clearContext();
echo "\nContext cleared.\n";