<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TestUsersSeeder extends Seeder
{
    public function run()
    {
        // Create test users for each role
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@test.com',
                'password' => Hash::make('password'),
                'role' => 'super-admin',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Institution Admin',
                'email' => 'admin@test.com',
                'password' => Hash::make('password'),
                'role' => 'institution-admin',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Test Employer',
                'email' => 'employer@test.com',
                'password' => Hash::make('password'),
                'role' => 'employer',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Test Graduate',
                'email' => 'graduate@test.com',
                'password' => Hash::make('password'),
                'role' => 'graduate',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'John Alumni',
                'email' => 'john@test.com',
                'password' => Hash::make('password'),
                'role' => 'graduate',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Jane Alumni',
                'email' => 'jane@test.com',
                'password' => Hash::make('password'),
                'role' => 'graduate',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $user = User::create($userData);

            // Assign role if it exists
            if (Role::where('name', $role)->exists()) {
                $user->assignRole($role);
            }

            $this->command->info("Created user: {$user->email} with role: {$role}");
        }
    }
}
