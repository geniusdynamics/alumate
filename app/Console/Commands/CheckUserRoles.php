<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckUserRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:user-roles {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check user roles for a given email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        // Show all available roles first
        $this->info('=== Available Roles ===');
        $allRoles = \Spatie\Permission\Models\Role::all();
        foreach ($allRoles as $role) {
            $this->line("- {$role->name}");
        }
        $this->line('');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email {$email} not found");

            return;
        }

        $this->info('=== User Details ===');
        $this->info("User found: {$user->name}");
        $this->info("Email: {$user->email}");
        $this->info('Institution ID: '.($user->institution_id ?? 'None'));

        $roles = $user->roles->pluck('name')->toArray();
        if (empty($roles)) {
            $this->warn('No roles assigned to this user');
        } else {
            $this->info('Roles: '.implode(', ', $roles));
        }

        $permissions = $user->getAllPermissions()->pluck('name')->toArray();
        if (empty($permissions)) {
            $this->warn('No permissions assigned to this user');
        } else {
            $this->info('Permissions: '.implode(', ', $permissions));
        }

        // Test role check
        $this->line('');
        $this->info('=== Role Checks ===');
        $this->info("Has 'institution-admin' role: ".($user->hasRole('institution-admin') ? 'YES' : 'NO'));
        $this->info("Has 'institution_admin' role: ".($user->hasRole('institution_admin') ? 'YES' : 'NO'));
    }
}
