<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\TenantContextService;
use App\Models\Course;
use App\Models\Graduate;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

echo "=== Detailed Seeder Debug ===\n";

// Check current database connection
echo "Default database connection: " . config('database.default') . "\n";
echo "Database name: " . config('database.connections.pgsql.database') . "\n";
echo "Current schema: " . DB::select('SELECT current_schema()')[0]->current_schema . "\n";

$tenantService = app(TenantContextService::class);
echo "Current tenant before setting: " . ($tenantService->getCurrentTenantId() ?? 'null') . "\n";

// Set tenant context
echo "\nSetting tenant context...\n";
$tenantService->setTenant('tech-institute');
echo "Current tenant after setting: " . $tenantService->getCurrentTenantId() . "\n";
echo "Current schema after setting: " . DB::select('SELECT current_schema()')[0]->current_schema . "\n";

// Check courses in tenant schema
echo "\nChecking courses in tenant schema...\n";
try {
    $courses = Course::all();
    echo "Found " . $courses->count() . " courses:\n";
    foreach ($courses as $course) {
        echo "  Course ID: {$course->id}, Code: {$course->course_code}, Name: {$course->name}\n";
    }
} catch (Exception $e) {
    echo "Error querying courses: " . $e->getMessage() . "\n";
}

// Check if we can query graduates
echo "\nChecking graduates in tenant schema...\n";
try {
    $graduates = Graduate::all();
    echo "Found " . $graduates->count() . " graduates\n";
} catch (Exception $e) {
    echo "Error querying graduates: " . $e->getMessage() . "\n";
}

// Check the actual database schema for foreign key constraints
echo "\nChecking foreign key constraints...\n";
try {
    $constraints = DB::select("
        SELECT 
            tc.constraint_name, 
            tc.table_name, 
            kcu.column_name, 
            ccu.table_name AS foreign_table_name,
            ccu.column_name AS foreign_column_name 
        FROM 
            information_schema.table_constraints AS tc 
            JOIN information_schema.key_column_usage AS kcu
              ON tc.constraint_name = kcu.constraint_name
              AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu
              ON ccu.constraint_name = tc.constraint_name
              AND ccu.table_schema = tc.table_schema
        WHERE tc.constraint_type = 'FOREIGN KEY' 
        AND tc.table_name = 'graduates'
        AND tc.table_schema = current_schema()
    ");
    
    foreach ($constraints as $constraint) {
        echo "  Constraint: {$constraint->constraint_name}\n";
        echo "    Table: {$constraint->table_name}.{$constraint->column_name}\n";
        echo "    References: {$constraint->foreign_table_name}.{$constraint->foreign_column_name}\n";
    }
} catch (Exception $e) {
    echo "Error checking constraints: " . $e->getMessage() . "\n";
}

// Try to create a graduate manually
echo "\nTrying to create graduate manually...\n";
try {
    // First, get a user
    $user = User::where('email', 'john.smith@student.edu')->first();
    if (!$user) {
        echo "Creating user first...\n";
        $user = User::create([
            'name' => 'John Smith',
            'email' => 'john.smith@student.edu',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
        echo "User created with ID: {$user->id}\n";
    } else {
        echo "User found with ID: {$user->id}\n";
    }
    
    // Get the course
    $course = Course::where('course_code', 'CS101')->first();
    if ($course) {
        echo "Course found with ID: {$course->id}\n";
        
        // Try to create graduate
        $graduate = Graduate::create([
            'email' => 'john.smith@student.edu',
            'name' => 'John Smith',
            'phone' => '+1234567890',
            'graduation_year' => 2023,
            'course_id' => $course->id,
            'user_id' => $user->id,
        ]);
        
        echo "Graduate created successfully with ID: {$graduate->id}\n";
    } else {
        echo "Course not found!\n";
    }
} catch (Exception $e) {
    echo "Error creating graduate: " . $e->getMessage() . "\n";
    echo "Error trace: " . $e->getTraceAsString() . "\n";
}

$tenantService->clearContext();
echo "\nContext cleared.\n";