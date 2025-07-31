<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class SampleDataSeeder extends Seeder
{
    public function run()
    {
        // Create sample institutions
        $institutions = [
            [
                'name' => 'University of Technology',
                'type' => 'University',
                'location' => 'New York, NY',
                'website' => 'https://utech.edu',
                'description' => 'Leading technology university',
                'is_active' => true,
            ],
            [
                'name' => 'Business College',
                'type' => 'College',
                'location' => 'Los Angeles, CA',
                'website' => 'https://bizcollege.edu',
                'description' => 'Premier business education',
                'is_active' => true,
            ],
        ];

        foreach ($institutions as $institution) {
            DB::table('institutions')->insert(array_merge($institution, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Create sample companies
        $companies = [
            [
                'name' => 'Tech Innovations Inc',
                'description' => 'Leading technology company',
                'industry' => 'Technology',
                'location' => 'San Francisco, CA',
                'size' => 'large',
                'founded_year' => 2010,
                'is_verified' => true,
                'website' => 'https://techinnovations.com',
            ],
            [
                'name' => 'StartupCo',
                'description' => 'Innovative startup',
                'industry' => 'Software',
                'location' => 'Austin, TX',
                'size' => 'startup',
                'founded_year' => 2020,
                'is_verified' => true,
                'website' => 'https://startupco.com',
            ],
            [
                'name' => 'Global Corp',
                'description' => 'Multinational corporation',
                'industry' => 'Finance',
                'location' => 'New York, NY',
                'size' => 'enterprise',
                'founded_year' => 1995,
                'is_verified' => true,
                'website' => 'https://globalcorp.com',
            ],
        ];

        foreach ($companies as $company) {
            Company::create($company);
        }

        // Create sample skills
        $skills = [
            ['name' => 'JavaScript', 'category' => 'Programming', 'description' => 'JavaScript programming language', 'is_verified' => true],
            ['name' => 'Python', 'category' => 'Programming', 'description' => 'Python programming language', 'is_verified' => true],
            ['name' => 'React', 'category' => 'Frontend', 'description' => 'React JavaScript library', 'is_verified' => true],
            ['name' => 'Laravel', 'category' => 'Backend', 'description' => 'Laravel PHP framework', 'is_verified' => true],
            ['name' => 'Project Management', 'category' => 'Management', 'description' => 'Project management skills', 'is_verified' => true],
            ['name' => 'Data Analysis', 'category' => 'Analytics', 'description' => 'Data analysis and visualization', 'is_verified' => true],
        ];

        foreach ($skills as $skill) {
            DB::table('skills')->insert(array_merge($skill, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->command->info('Sample data created successfully!');
    }
}