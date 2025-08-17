<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions if they don't exist
        $permissions = [
            'manage-users',
            'manage-institutions',
            'manage-graduates',
            'manage-courses',
            'post-jobs',
            'view-applications',
            'approve-employers',
            'view-analytics',
            'manage-settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles if they don't exist
        $roles = [
            'super-admin' => $permissions, // Super admin gets all permissions
            'institution-admin' => [
                'manage-graduates',
                'manage-courses',
                'view-analytics',
                'manage-settings',
            ],
            'employer' => [
                'post-jobs',
                'view-applications',
            ],
            'graduate' => [],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }

        // Create super admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@graduatetracking.com'],
            [
                'name' => 'Super Administrator',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'status' => 'active',
                'profile_data' => [
                    'bio' => 'System administrator with full access to all features.',
                    'location' => 'System',
                ],
                'preferences' => [
                    'notifications' => [
                        'email' => true,
                        'sms' => false,
                        'push' => true,
                    ],
                    'dashboard' => [
                        'theme' => 'light',
                        'compact_mode' => false,
                    ],
                ],
                'timezone' => 'UTC',
                'language' => 'en',
            ]
        );
        $superAdmin->assignRole('super-admin');

        // Create demo institutions
        $institutions = [
            [
                'id' => 'tech-university',
                'name' => 'Tech University',
                'domain' => 'tech-university.edu',
                'status' => 'active',
            ],
            [
                'id' => 'business-college',
                'name' => 'Business College',
                'domain' => 'business-college.edu',
                'status' => 'active',
            ],
            [
                'id' => 'arts-institute',
                'name' => 'Arts Institute',
                'domain' => 'arts-institute.edu',
                'status' => 'active',
            ],
        ];

        foreach ($institutions as $institutionData) {
            $institution = Tenant::firstOrCreate(
                ['id' => $institutionData['id']],
                $institutionData
            );

            // Create institution admin
            $adminUser = User::firstOrCreate(
                ['email' => "admin@{$institution->id}.edu"],
                [
                    'name' => "{$institution->name} Administrator",
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'institution_id' => $institution->id,
                    'status' => 'active',
                    'profile_data' => [
                        'bio' => "Administrator for {$institution->name}",
                        'location' => $institution->name,
                    ],
                    'timezone' => 'UTC',
                    'language' => 'en',
                ]
            );
            $adminUser->assignRole('institution-admin');

            // Create sample graduates for each institution
            $graduates = User::factory()
                ->count(10)
                ->graduate()
                ->forInstitution($institution->id)
                ->create();

            // Create sample employers
            $employers = User::factory()
                ->count(5)
                ->employer()
                ->create();

            // Create some suspended users
            User::factory()
                ->count(2)
                ->suspended()
                ->forInstitution($institution->id)
                ->create();

            // Create some inactive users
            User::factory()
                ->count(3)
                ->inactive()
                ->forInstitution($institution->id)
                ->create();
        }

        // Create additional demo users with various states
        $this->createDemoUsers();

        $this->command->info('Users seeded successfully!');
        $this->command->info('Super Admin: admin@graduatetracking.com / password');
        $this->command->info('Institution Admins: admin@{institution-id}.edu / password');
    }

    private function createDemoUsers(): void
    {
        // Create users with complete profiles
        User::factory()
            ->count(5)
            ->withCompleteProfile()
            ->recentlyActive()
            ->create()
            ->each(function ($user) {
                $user->assignRole('graduate');
            });

        // Create users with two-factor authentication
        User::factory()
            ->count(3)
            ->withTwoFactor()
            ->create()
            ->each(function ($user) {
                $user->assignRole('employer');
            });

        // Create users with different timezones and languages
        $timezones = ['America/New_York', 'Europe/London', 'Asia/Tokyo', 'Australia/Sydney'];
        $languages = ['en', 'es', 'fr', 'de', 'ja'];

        foreach ($timezones as $timezone) {
            User::factory()
                ->count(2)
                ->state([
                    'timezone' => $timezone,
                    'language' => fake()->randomElement($languages),
                ])
                ->create()
                ->each(function ($user) {
                    $user->assignRole('graduate');
                });
        }

        // Create users with custom preferences
        User::factory()
            ->count(3)
            ->withPreferences([
                'notifications' => [
                    'email' => false,
                    'sms' => true,
                    'push' => false,
                ],
                'privacy' => [
                    'profile_visible' => false,
                    'show_email' => true,
                    'show_phone' => true,
                ],
                'dashboard' => [
                    'theme' => 'dark',
                    'compact_mode' => true,
                ],
            ])
            ->create()
            ->each(function ($user) {
                $user->assignRole('employer');
            });
    }
}
