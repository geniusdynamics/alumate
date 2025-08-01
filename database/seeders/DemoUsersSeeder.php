<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles if they don't exist
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);
        $institutionAdminRole = Role::firstOrCreate(['name' => 'institution-admin']);
        $employerRole = Role::firstOrCreate(['name' => 'employer']);
        $graduateRole = Role::firstOrCreate(['name' => 'graduate']);

        // Create permissions
        $permissions = [
            'manage-users',
            'manage-institutions',
            'manage-courses',
            'manage-graduates',
            'manage-jobs',
            'view-dashboard',
            'post-jobs',
            'view-applications',
            'approve-employers',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $superAdminRole->syncPermissions($permissions);
        $institutionAdminRole->syncPermissions([
            'manage-courses',
            'manage-graduates',
            'view-dashboard',
        ]);
        $employerRole->syncPermissions([
            'post-jobs',
            'view-applications',
            'view-dashboard',
        ]);
        $graduateRole->syncPermissions([
            'view-dashboard',
        ]);

        // Create Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@system.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole($superAdminRole);

        // Create Institution Admin
        $institutionAdmin = User::firstOrCreate(
            ['email' => 'admin@tech-institute.edu'],
            [
                'name' => 'Institution Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'institution_id' => 'tech-institute',
            ]
        );
        $institutionAdmin->assignRole($institutionAdminRole);

        // Create Employer
        $employer = User::firstOrCreate(
            ['email' => 'techcorp@company.com'],
            [
                'name' => 'TechCorp Recruiter',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $employer->assignRole($employerRole);

        // Create Graduate
        $graduate = User::firstOrCreate(
            ['email' => 'john.smith@student.edu'],
            [
                'name' => 'John Smith',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'institution_id' => 'tech-institute',
            ]
        );
        $graduate->assignRole($graduateRole);

        $this->command->info('Demo users created successfully!');
        $this->command->info('Super Admin: admin@system.com / password');
        $this->command->info('Institution Admin: admin@tech-institute.edu / password');
        $this->command->info('Employer: techcorp@company.com / password');
        $this->command->info('Graduate: john.smith@student.edu / password');
    }
}