<?php

namespace Database\Seeders;

use App\Models\SavedSearch;
use App\Models\SearchAnalytics;
use App\Models\User;
use App\Services\MatchingService;
use Illuminate\Database\Seeder;

class SearchSystemSeeder extends Seeder
{
    public function run()
    {
        $this->seedSavedSearches();
        $this->seedSearchAnalytics();
        $this->calculateJobMatches();
    }

    private function seedSavedSearches()
    {
        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['graduate', 'employer']);
        })->get();

        foreach ($users as $user) {
            if ($user->hasRole('graduate')) {
                // Create job searches for graduates
                SavedSearch::create([
                    'user_id' => $user->id,
                    'name' => 'Software Developer Jobs',
                    'search_type' => 'jobs',
                    'search_criteria' => [
                        'keywords' => 'software developer',
                        'job_type' => 'full_time',
                        'skills' => ['JavaScript', 'PHP', 'Laravel'],
                        'salary_min' => 50000,
                    ],
                    'alert_enabled' => true,
                    'alert_frequency' => 'daily',
                ]);

                SavedSearch::create([
                    'user_id' => $user->id,
                    'name' => 'Remote Opportunities',
                    'search_type' => 'jobs',
                    'search_criteria' => [
                        'work_arrangement' => 'remote',
                        'job_type' => 'full_time',
                    ],
                    'alert_enabled' => false,
                    'alert_frequency' => 'weekly',
                ]);
            }

            if ($user->hasRole('employer')) {
                // Create graduate searches for employers
                SavedSearch::create([
                    'user_id' => $user->id,
                    'name' => 'Computer Science Graduates',
                    'search_type' => 'graduates',
                    'search_criteria' => [
                        'course_id' => 1, // Assuming course ID 1 exists
                        'employment_status' => 'unemployed',
                        'min_gpa' => 3.0,
                        'skills' => ['Programming', 'Database'],
                    ],
                    'alert_enabled' => true,
                    'alert_frequency' => 'weekly',
                ]);
            }
        }
    }

    private function seedSearchAnalytics()
    {
        $users = User::limit(10)->get();

        foreach ($users as $user) {
            // Create some search analytics data
            for ($i = 0; $i < rand(3, 8); $i++) {
                SearchAnalytics::create([
                    'user_id' => $user->id,
                    'search_type' => ['jobs', 'graduates', 'courses'][rand(0, 2)],
                    'search_criteria' => [
                        'keywords' => ['developer', 'manager', 'analyst', 'engineer'][rand(0, 3)],
                        'location' => ['New York', 'San Francisco', 'Remote', 'Chicago'][rand(0, 3)],
                    ],
                    'results_count' => rand(0, 50),
                    'searched_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }
    }

    private function calculateJobMatches()
    {
        $matchingService = app(MatchingService::class);

        // This would normally be run as a background job
        // For seeding purposes, we'll just calculate a few matches
        $jobs = \App\Models\Job::active()->limit(5)->get();

        foreach ($jobs as $job) {
            $matchingService->batchCalculateMatches($job);
        }
    }
}
