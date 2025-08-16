<?php

namespace Database\Seeders;

use App\Models\Employer;
use App\Models\User;
use Illuminate\Database\Seeder;

class FixEmployerProfiles extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find employer users without employer profiles
        $employerUsers = User::role('employer')->get();

        foreach ($employerUsers as $user) {
            $employer = Employer::where('user_id', $user->id)->first();

            if (! $employer) {
                $this->command->info("Creating employer profile for: {$user->email}");

                Employer::create([
                    'user_id' => $user->id,
                    'company_name' => $user->name.' Company',
                    'company_address' => '123 Business Street, City, State 12345',
                    'company_phone' => '+1-555-0123',
                    'industry' => 'Technology',
                    'company_description' => 'A leading technology company focused on innovation.',
                    'contact_person_name' => $user->name,
                    'contact_person_email' => $user->email,
                    'contact_person_phone' => '+1-555-0123',
                    'contact_person_title' => 'HR Manager',
                    'established_year' => 2020,
                    'employee_count' => 50,
                    'company_size' => 'medium',
                    'verification_status' => 'verified',
                    'approved' => true,
                    'is_active' => true,
                    'can_post_jobs' => true,
                    'can_search_graduates' => true,
                    'total_jobs_posted' => 0,
                    'active_jobs_count' => 0,
                    'total_hires' => 0,
                    'job_posting_limit' => 10,
                    'jobs_posted_this_month' => 0,
                    'subscription_plan' => 'basic',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now(),
                    'privacy_policy_accepted' => true,
                    'privacy_policy_accepted_at' => now(),
                ]);

                $this->command->info("✓ Created employer profile for {$user->email}");
            } else {
                $this->command->info("✓ Employer profile already exists for {$user->email}");
            }
        }
    }
}
