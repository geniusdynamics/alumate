<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Facades\Tenancy;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "=== Creating Missing Tenant Tables ===\n";
    
    // Set tenant context
    $tenant = \App\Models\Tenant::where('id', 'tech-institute')->first();
    if (!$tenant) {
        throw new Exception('Tenant tech-institute not found');
    }
    
    Tenancy::initialize($tenant);
    echo "Tenant context set to: {$tenant->id}\n\n";
    
    // Create students table if missing
    if (!Schema::hasTable('students')) {
        echo "Creating students table...\n";
        DB::statement('
            CREATE TABLE students (
                id BIGSERIAL PRIMARY KEY,
                student_id VARCHAR(255) UNIQUE NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                phone VARCHAR(255),
                address TEXT,
                date_of_birth DATE,
                gender VARCHAR(50),
                enrollment_date DATE,
                status VARCHAR(50) DEFAULT \'active\' CHECK (status IN (\'active\', \'inactive\', \'graduated\', \'suspended\')),
                course_id BIGINT,
                emergency_contact_name VARCHAR(255),
                emergency_contact_phone VARCHAR(255),
                metadata JSON,
                created_at TIMESTAMP,
                updated_at TIMESTAMP,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL
            )
        ');
        
        // Record migration
        DB::table('migrations')->insert([
            'migration' => '2024_01_01_000002_create_students_table',
            'batch' => 1
        ]);
        echo "✓ Students table created\n";
    } else {
        echo "Students table already exists\n";
    }
    
    // Create enrollments table if missing
    if (!Schema::hasTable('enrollments')) {
        echo "Creating enrollments table...\n";
        DB::statement('
            CREATE TABLE enrollments (
                id BIGSERIAL PRIMARY KEY,
                student_id BIGINT NOT NULL,
                course_id BIGINT NOT NULL,
                enrollment_date DATE NOT NULL,
                completion_date DATE,
                status VARCHAR(50) DEFAULT \'enrolled\' CHECK (status IN (\'enrolled\', \'completed\', \'dropped\', \'suspended\')),
                grade VARCHAR(10),
                credits_earned INTEGER DEFAULT 0,
                payment_status VARCHAR(50) DEFAULT \'pending\' CHECK (payment_status IN (\'pending\', \'paid\', \'partial\', \'overdue\')),
                amount_paid DECIMAL(10,2) DEFAULT 0,
                notes TEXT,
                created_at TIMESTAMP,
                updated_at TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                UNIQUE(student_id, course_id)
            )
        ');
        
        // Record migration
        DB::table('migrations')->insert([
            'migration' => '2024_01_01_000003_create_enrollments_table',
            'batch' => 1
        ]);
        echo "✓ Enrollments table created\n";
    } else {
        echo "Enrollments table already exists\n";
    }
    
    // Create grades table if missing
    if (!Schema::hasTable('grades')) {
        echo "Creating grades table...\n";
        DB::statement('
            CREATE TABLE grades (
                id BIGSERIAL PRIMARY KEY,
                student_id BIGINT NOT NULL,
                course_id BIGINT NOT NULL,
                assignment_name VARCHAR(255) NOT NULL,
                assignment_type VARCHAR(100) DEFAULT \'assignment\' CHECK (assignment_type IN (\'assignment\', \'quiz\', \'exam\', \'project\', \'participation\')),
                points_earned DECIMAL(5,2) NOT NULL,
                points_possible DECIMAL(5,2) NOT NULL,
                percentage DECIMAL(5,2) GENERATED ALWAYS AS ((points_earned / points_possible) * 100) STORED,
                letter_grade VARCHAR(5),
                graded_date DATE,
                due_date DATE,
                submitted_date DATE,
                late_submission BOOLEAN DEFAULT FALSE,
                feedback TEXT,
                grader_name VARCHAR(255),
                created_at TIMESTAMP,
                updated_at TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
            )
        ');
        
        // Record migration
        DB::table('migrations')->insert([
            'migration' => '2024_01_01_000005_create_grades_table',
            'batch' => 1
        ]);
        echo "✓ Grades table created\n";
    } else {
        echo "Grades table already exists\n";
    }
    
    echo "\n=== Verifying Tables ===\n";
    $tables = ['courses', 'students', 'enrollments', 'graduates', 'grades'];
    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            $count = DB::table($table)->count();
            echo "✓ {$table}: {$count} records\n";
        } else {
            echo "✗ {$table}: missing\n";
        }
    }
    
    echo "\n=== Missing Tenant Tables Creation Complete ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>