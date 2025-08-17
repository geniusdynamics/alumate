<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GraduateTrackingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create basic tenants for testing
        $tenants = [
            [
                'id' => 'tech-institute',
                'name' => 'Tech Institute of Excellence',
                'address' => '123 Technology Street, Tech City',
                'contact_information' => json_encode(['phone' => '+1-555-0101', 'email' => 'info@techexcellence.edu']),
                'plan' => 'premium',
                'data' => json_encode(['name' => 'Tech Institute of Excellence']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'business-college',
                'name' => 'Business Leadership College',
                'address' => '456 Business Avenue, Commerce City',
                'contact_information' => json_encode(['phone' => '+1-555-0102', 'email' => 'info@businessleadership.edu']),
                'plan' => 'premium',
                'data' => json_encode(['name' => 'Business Leadership College']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($tenants as $tenant) {
            DB::table('tenants')->insertOrIgnore($tenant);
        }

        // Create basic courses
        $courses = [
            [
                'name' => 'Computer Science',
                'code' => 'CS101',
                'level' => 'degree',
                'duration_months' => 48,
                'study_mode' => 'full_time',
                'description' => 'Introduction to Computer Science',
                'required_skills' => json_encode(['Mathematics', 'Problem Solving']),
                'skills_gained' => json_encode(['Programming', 'Algorithms', 'Data Structures']),
                'career_paths' => json_encode(['Software Developer', 'Data Scientist', 'System Analyst']),
                'is_active' => true,
                'is_featured' => true,
                'institution_id' => 'tech-institute',
            ],
            [
                'name' => 'Business Administration',
                'code' => 'BUS201',
                'level' => 'degree',
                'duration_months' => 36,
                'study_mode' => 'full_time',
                'description' => 'Business fundamentals and management',
                'required_skills' => json_encode(['Communication', 'Analytical Thinking']),
                'skills_gained' => json_encode(['Management', 'Marketing', 'Finance']),
                'career_paths' => json_encode(['Business Analyst', 'Marketing Manager', 'Operations Manager']),
                'is_active' => true,
                'is_featured' => false,
                'institution_id' => 'business-college',
            ],
        ];

        foreach ($courses as $course) {
            DB::table('courses')->insertOrIgnore($course);
        }
    }
}
