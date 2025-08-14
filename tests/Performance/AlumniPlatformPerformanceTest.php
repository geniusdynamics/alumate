<?php

namespace Tests\Performance;

use App\Models\Circle;
use App\Models\Group;
use App\Models\JobPosting;
use App\Models\Post;
use App\Models\PostEngagement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AlumniPlatformPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected array $performanceMetrics = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->performanceMetrics = [];
    }

    protected function tearDown(): void
    {
        // Log performance metrics
        if (!empty($this->performanceMetrics)) {
            $this->logPerformanceResults();
        }
        
        parent::tearDown();
    }

    public function test_timeline_generation_performance_with_large_dataset()
    {
        // Create large dataset
        $users = User::factory()->count(100)->create();
        $circles = Circle::factory()->count(10)->create();
        $groups = Group::factory()->count(5)->create();

        // Add users to circles and groups
        foreach ($users as $user) {
            $user->circles()->attach($circles->random(3)->pluck('id'));
            $user->groups()->attach($groups->random(2)->pluck('id'));
        }

        // Create many posts
        $posts = collect();
        foreach ($users->take(50) as $user) {
            $userPosts = Post::factory()->count(20)->create([
                'user_id' => $user->id,
                'visibility' => 'public',
            ]);
            $posts = $posts->merge($userPosts);
        }

        // Add engagements
        foreach ($posts->take(500) as $post) {
            PostEngagement::factory()->count(rand(1, 10))->create([
                'post_id' => $post->id,
            ]);
        }

        // Test timeline generation performance
        $startTime = microtime(true);
        $startQueries = DB::getQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline?per_page=20');

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $queryCount = count($queries) - count($startQueries);

        $this->performanceMetrics['timeline_generation'] = [
            'execution_time_ms' => $executionTime,
            'query_count' => $queryCount,
            'posts_count' => $posts->count(),
            'users_count' => $users->count(),
        ];

        $response->assertStatus(200);
        
        // Performance assertions
        $this->assertLessThan(2000, $executionTime, 'Timeline generation should complete within 2 seconds');
        $this->assertLessThan(50, $queryCount, 'Timeline generation should use fewer than 50 queries');
    }

    public function test_alumni_directory_search_performance()
    {
        // Create large alumni dataset
        $alumni = User::factory()->count(1000)->create();
        
        // Add varied data for search testing
        foreach ($alumni as $user) {
            $user->update([
                'skills' => $this->faker->randomElements([
                    'PHP', 'Laravel', 'Vue.js', 'React', 'Node.js', 'Python', 
                    'Java', 'C++', 'JavaScript', 'TypeScript', 'Go', 'Rust'
                ], rand(2, 6)),
                'location' => $this->faker->city . ', ' . $this->faker->stateAbbr,
            ]);
        }

        // Test search performance
        $searchQueries = [
            '/api/alumni?search=John',
            '/api/alumni?skills[]=PHP&skills[]=Laravel',
            '/api/alumni?location=San Francisco',
            '/api/alumni?graduation_year_from=2020&graduation_year_to=2022',
            '/api/alumni?search=developer&skills[]=JavaScript&location=New York',
        ];

        foreach ($searchQueries as $index => $query) {
            $startTime = microtime(true);
            DB::enableQueryLog();

            $response = $this->actingAs($this->user)
                ->getJson($query);

            $endTime = microtime(true);
            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            $executionTime = ($endTime - $startTime) * 1000;
            $queryCount = count($queries);

            $this->performanceMetrics["alumni_search_$index"] = [
                'query' => $query,
                'execution_time_ms' => $executionTime,
                'query_count' => $queryCount,
                'results_count' => count($response->json('data')),
            ];

            $response->assertStatus(200);
            
            // Performance assertions
            $this->assertLessThan(1500, $executionTime, "Alumni search should complete within 1.5 seconds for query: $query");
            $this->assertLessThan(20, $queryCount, "Alumni search should use fewer than 20 queries for query: $query");
        }
    }

    public function test_job_matching_algorithm_performance()
    {
        // Create large dataset
        $users = User::factory()->count(500)->create();
        $companies = \App\Models\Company::factory()->count(50)->create();
        
        // Create job postings
        $jobs = collect();
        foreach ($companies as $company) {
            $companyJobs = JobPosting::factory()->count(rand(5, 15))->create([
                'company_id' => $company->id,
                'is_active' => true,
            ]);
            $jobs = $jobs->merge($companyJobs);
        }

        // Create job match scores for realistic matching
        foreach ($users->take(100) as $user) {
            foreach ($jobs->random(rand(10, 30)) as $job) {
                \App\Models\JobMatchScore::factory()->create([
                    'user_id' => $user->id,
                    'job_id' => $job->id,
                    'score' => $this->faker->numberBetween(40, 95),
                ]);
            }
        }

        // Test job matching performance
        $startTime = microtime(true);
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)
            ->getJson('/api/jobs/recommendations?per_page=20');

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $executionTime = ($endTime - $startTime) * 1000;
        $queryCount = count($queries);

        $this->performanceMetrics['job_matching'] = [
            'execution_time_ms' => $executionTime,
            'query_count' => $queryCount,
            'jobs_count' => $jobs->count(),
            'users_count' => $users->count(),
        ];

        $response->assertStatus(200);
        
        // Performance assertions
        $this->assertLessThan(3000, $executionTime, 'Job matching should complete within 3 seconds');
        $this->assertLessThan(30, $queryCount, 'Job matching should use fewer than 30 queries');
    }

    public function test_post_engagement_performance_under_load()
    {
        // Create a popular post with many engagements
        $popularPost = Post::factory()->create([
            'user_id' => User::factory()->create()->id,
            'visibility' => 'public',
        ]);

        // Create many existing engagements
        PostEngagement::factory()->count(1000)->create([
            'post_id' => $popularPost->id,
        ]);

        // Test engagement creation performance
        $startTime = microtime(true);
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$popularPost->id}/engage", [
                'type' => 'like',
            ]);

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $executionTime = ($endTime - $startTime) * 1000;
        $queryCount = count($queries);

        $this->performanceMetrics['post_engagement'] = [
            'execution_time_ms' => $executionTime,
            'query_count' => $queryCount,
            'existing_engagements' => 1000,
        ];

        $response->assertStatus(200);
        
        // Performance assertions
        $this->assertLessThan(500, $executionTime, 'Post engagement should complete within 500ms');
        $this->assertLessThan(10, $queryCount, 'Post engagement should use fewer than 10 queries');

        // Test post retrieval with many engagements
        $startTime = microtime(true);
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$popularPost->id}");

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $executionTime = ($endTime - $startTime) * 1000;
        $queryCount = count($queries);

        $this->performanceMetrics['post_retrieval_with_engagements'] = [
            'execution_time_ms' => $executionTime,
            'query_count' => $queryCount,
            'engagements_count' => 1001, // Including the one we just added
        ];

        $response->assertStatus(200);
        
        // Performance assertions
        $this->assertLessThan(1000, $executionTime, 'Post retrieval with engagements should complete within 1 second');
        $this->assertLessThan(15, $queryCount, 'Post retrieval should use fewer than 15 queries');
    }

    public function test_dashboard_loading_performance()
    {
        // Create user's social network
        $connections = User::factory()->count(50)->create();
        foreach ($connections as $connection) {
            $this->user->connections()->attach($connection->id, [
                'status' => 'accepted',
                'connected_at' => now(),
            ]);
        }

        // Create circles and groups
        $circles = Circle::factory()->count(5)->create();
        $groups = Group::factory()->count(3)->create();
        
        foreach ($circles as $circle) {
            $this->user->circles()->attach($circle->id);
        }
        
        foreach ($groups as $group) {
            $this->user->groups()->attach($group->id);
        }

        // Create posts from connections
        foreach ($connections->take(20) as $connection) {
            Post::factory()->count(5)->create([
                'user_id' => $connection->id,
                'visibility' => 'public',
            ]);
        }

        // Create job recommendations
        $companies = \App\Models\Company::factory()->count(10)->create();
        foreach ($companies as $company) {
            $jobs = JobPosting::factory()->count(3)->create([
                'company_id' => $company->id,
                'is_active' => true,
            ]);
            
            foreach ($jobs as $job) {
                \App\Models\JobMatchScore::factory()->create([
                    'user_id' => $this->user->id,
                    'job_id' => $job->id,
                    'score' => $this->faker->numberBetween(70, 95),
                ]);
            }
        }

        // Test dashboard loading performance
        $startTime = microtime(true);
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)
            ->getJson('/api/dashboard');

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $executionTime = ($endTime - $startTime) * 1000;
        $queryCount = count($queries);

        $this->performanceMetrics['dashboard_loading'] = [
            'execution_time_ms' => $executionTime,
            'query_count' => $queryCount,
            'connections_count' => $connections->count(),
            'circles_count' => $circles->count(),
            'groups_count' => $groups->count(),
        ];

        $response->assertStatus(200);
        
        // Performance assertions
        $this->assertLessThan(2500, $executionTime, 'Dashboard loading should complete within 2.5 seconds');
        $this->assertLessThan(40, $queryCount, 'Dashboard loading should use fewer than 40 queries');
    }

    public function test_memory_usage_during_large_operations()
    {
        $initialMemory = memory_get_usage(true);

        // Create large dataset
        $users = User::factory()->count(200)->create();
        $posts = collect();
        
        foreach ($users as $user) {
            $userPosts = Post::factory()->count(10)->create([
                'user_id' => $user->id,
                'visibility' => 'public',
            ]);
            $posts = $posts->merge($userPosts);
        }

        $afterDataCreation = memory_get_usage(true);

        // Perform memory-intensive operation (timeline generation)
        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline?per_page=50');

        $afterOperation = memory_get_usage(true);

        $this->performanceMetrics['memory_usage'] = [
            'initial_memory_mb' => round($initialMemory / 1024 / 1024, 2),
            'after_data_creation_mb' => round($afterDataCreation / 1024 / 1024, 2),
            'after_operation_mb' => round($afterOperation / 1024 / 1024, 2),
            'memory_increase_mb' => round(($afterOperation - $initialMemory) / 1024 / 1024, 2),
        ];

        $response->assertStatus(200);

        // Memory usage assertions
        $memoryIncrease = $afterOperation - $initialMemory;
        $this->assertLessThan(100 * 1024 * 1024, $memoryIncrease, 'Memory usage should not increase by more than 100MB');
    }

    public function test_concurrent_user_simulation()
    {
        // Create multiple users for concurrent testing
        $users = User::factory()->count(10)->create();
        $posts = Post::factory()->count(50)->create(['visibility' => 'public']);

        $startTime = microtime(true);
        $responses = [];

        // Simulate concurrent requests
        foreach ($users as $user) {
            $responses[] = $this->actingAs($user)
                ->getJson('/api/timeline?per_page=10');
        }

        $endTime = microtime(true);
        $totalExecutionTime = ($endTime - $startTime) * 1000;

        $this->performanceMetrics['concurrent_users'] = [
            'total_execution_time_ms' => $totalExecutionTime,
            'concurrent_users' => count($users),
            'average_response_time_ms' => $totalExecutionTime / count($users),
        ];

        // Verify all responses are successful
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Performance assertions
        $this->assertLessThan(5000, $totalExecutionTime, 'Concurrent user requests should complete within 5 seconds');
    }

    protected function logPerformanceResults(): void
    {
        $logFile = storage_path('logs/performance_test_results.log');
        $timestamp = now()->toDateTimeString();
        
        $logEntry = "\n=== Performance Test Results - $timestamp ===\n";
        
        foreach ($this->performanceMetrics as $testName => $metrics) {
            $logEntry .= "\n$testName:\n";
            foreach ($metrics as $key => $value) {
                $logEntry .= "  $key: $value\n";
            }
        }
        
        $logEntry .= "\n" . str_repeat('=', 60) . "\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also output to console during testing
        echo $logEntry;
    }

    protected function faker()
    {
        return $this->faker ?? \Faker\Factory::create();
    }
}