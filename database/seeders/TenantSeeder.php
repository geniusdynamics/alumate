<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Create a demo tenant for the institution admin
        $tenant = Tenant::firstOrCreate(
            ['id' => 'tech-institute'],
            [
                'name' => 'Tech Institute',
                'address' => '123 University Ave, Tech City, TC 12345',
                'contact_information' => 'Phone: +1-555-123-4567, Email: contact@tech-institute.edu, Website: https://tech-institute.edu',
                'plan' => 'premium',
                'data' => [
                    'name' => 'Tech Institute',
                    'status' => 'active',
                    'type' => 'university',
                    'established' => '1985',
                    'student_count' => 5000,
                    'faculty_count' => 300,
                    'programs' => [
                        'Computer Science',
                        'Engineering',
                        'Business Administration',
                        'Data Science',
                    ],
                ],
            ]
        );

        // Create domain for the tenant
        $tenant->domains()->firstOrCreate([
            'domain' => 'tech-institute.localhost',
        ]);

        $this->command->info('Demo tenant created successfully!');
        $this->command->info('Tenant ID: tech-institute');
        $this->command->info('Domain: tech-institute.localhost');
    }
}
