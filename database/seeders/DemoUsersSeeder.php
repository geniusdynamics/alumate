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
            ['email' => 'skimani@polytrack.ga'],
            [
                'name' => 'Super Admin Kimani',
                'password' => Hash::make('5esurE_skimani'),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole($superAdminRole);

        // Create Institution Admin
        $institutionAdmin = User::firstOrCreate(
            ['email' => 'ikimani@polytrack.ga'],
            [
                'name' => 'Institution Admin Kimani',
                'password' => Hash::make('5esurE_ikimani'),
                'email_verified_at' => now(),
            ]
        );
        $institutionAdmin->assignRole($institutionAdminRole);

        // Create Employer
        $employer = User::firstOrCreate(
            ['email' => 'ekimani@polytrack.ga'],
            [
                'name' => 'Employer Kimani',
                'password' => Hash::make('5esurE_ekimani'),
                'email_verified_at' => now(),
            ]
        );
        $employer->assignRole($employerRole);

        // Create Graduate
        $graduate = User::firstOrCreate(
            ['email' => 'gkimani@polytrack.ga'],
            [
                'name' => 'Graduate Kimani',
                'password' => Hash::make('5esurE_gkimani'),
                'email_verified_at' => now(),
            ]
        );
        $graduate->assignRole($graduateRole);

        $this->command->info('Demo users created successfully!');
        $this->command->info('Super Admin: skimani@polytrack.ga / 5esurE_skimani');
        $this->command->info('Institution Admin: ikimani@polytrack.ga / 5esurE_ikimani');
        $this->command->info('Employer: ekimani@polytrack.ga / 5esurE_ekimani');
        $this->command->info('Graduate: gkimani@polytrack.ga / 5esurE_gkimani');
    }
}