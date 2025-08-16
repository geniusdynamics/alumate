<?php

namespace Tests\Performance;

use App\Models\Circle;
use App\Models\Group;
use App\Models\Post;
use App\Models\PostEngagement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TimelinePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected $performanceThresholds = [
        'timeline_generation' => 2.0, // seconds
        'post_creation' => 0.5, // seconds
        'engagement_update' => 0.3, // seconds
        'search_query' => 1.0, // seconds
        'database_queries' => 50, // max queries per request
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create test data for performance testing
        $this->createLargeDataset();
    }

    protected function createLargeDataset(): void
    {
        // Create circles and groups
        $circles = Circle::factory()->count(10)->create();
        $groups = Group::factory()->count(15)->create();

        // Create users and add them to circles/groups
        $users = User::factory()->count(100)->create();

        foreach ($users as $user) {
            // Add to random circles
            $userCircles = $circles->random(rand(2, 5));
            foreach ($userCircles as $circle) {
                $user->circles()->attach($circle->id);
            }

            // Add to random groups
            $userGroups = $groups->random(rand(1, 3));
            foreach ($userGroups as $group) {
                $user->groups()->attach($group->id);
            }
        }

        // Add test user to some circles and groups
        $this->user->circles()->attach($circles->random(3)->pluck('id'));
        $this->user->groups()->attach($groups->random(2)->pluck('id'));

        // Create posts with various visibility settings
        foreach ($users->take(50) as $user) {
            Post::factory()->count(rand(5, 15))->create([
                'user_id' => $user->id,
                'visibility' => collect(['public', 'circles', 'groups'])->random(),
                'circle_ids' => $user->circles()->pluck('circles.id')->toArray(),
                'group_ids' => $user->groups()->pluck('groups.id')->toArray(),
                'created_at' => now()->subDays(rand(0, 30)),
            ]);
        }

        // Create engagements
        $posts = Post::all();
        foreach ($posts->take(200) as $post) {
            PostEngagement::factory()->count(rand(0, 10))->create([
                'post_id' => $post->id,
                'user_id' => $users->random()->id,
                'type' => collect(['like', 'love', 'comment', 'share'])->random(),
            ]);
        }
    }

    public function test_timeline_generation_performance()
    {
        // Measure timeline generation time
        $startTime = microtime(true);
        $queryCount = DB::getQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline?per_page=20');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        $response->assertStatus(200);

        // Performance assertions
        $this->assertLessThan(
            $this->performanceThresholds['timeline_generation'],
            $executionTime,
            "Timeline generation took {$executionTime}s, exceeds threshold of {$this->performanceThresholds['timeline_generation']}s"
        );

        $this->assertLessThan(
            $this->performanceThresholds['database_queries'],
            $queryCount,
            "Timeline generation used {$queryCount} queries, exceeds threshold of {$this->performanceThresholds['database_queries']}"
        );

        // Verify response structure and data quality
        $timeline = $response->json('data.posts.data');
        $this->assertGreaterThan(0, count($timeline));
        $this->assertLessThanOrEqual(20, count($timeline));

        // Verify posts are properly ordered by creation date
        for ($i = 0; $i < count($timeline) - 1; $i++) {
            $currentTime = strtotime($timeline[$i]['created_at']);
            $nextTime = strtotime($timeline[$i + 1]['created_at']);
            $this->assertGreaterThanOrEqual($nextTime, $currentTime);
        }
    }

    public function test_timeline_pagination_performance()
    {
        $pages = [1, 2, 3, 5, 10];
        $executionTimes = [];

        foreach ($pages as $page) {
            $startTime = microtime(true);

            $response = $this->actingAs($this->user)
                ->getJson("/api/timeline?per_page=20&page={$page}");

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            $executionTimes[$page] = $executionTime;

            $response->assertStatus(200);

            // Each page should load within threshold
            $this->assertLessThan(
                $this->performanceThresholds['timeline_generation'],
                $executionTime,
                "Timeline page {$page} took {$executionTime}s, exceeds threshold"
            );
        }

        // Performance should not degrade significantly with higher page numbers
        $firstPageTime = $executionTimes[1];
        $lastPageTime = $executionTimes[10];
        $degradationRatio = $lastPageTime / $firstPageTime;

        $this->assertLessThan(
            3.0,
            $degradationRatio,
            "Performance degraded by {$degradationRatio}x from page 1 to page 10"
        );
    }

    public function test_post_creation_performance()
    {
        $postData = [
            'content' => 'This is a performance test post with some content to measure creation speed.',
            'post_type' => 'text',
            'visibility' => 'public',
        ];

        $startTime = microtime(true);
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', $postData);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        $response->assertStatus(201);

        // Performance assertions
        $this->assertLessThan(
            $this->performanceThresholds['post_creation'],
            $executionTime,
            "Post creation took {$executionTime}s, exceeds threshold of {$this->performanceThresholds['post_creation']}s"
        );

        $this->assertLessThan(
            20,
            $queryCount,
            "Post creation used {$queryCount} queries, should be optimized"
        );
    }

    public function test_post_engagement_performance()
    {
        $post = Post::factory()->create([
            'user_id' => User::factory()->create()->id,
            'visibility' => 'public',
        ]);

        $engagementTypes = ['like', 'love', 'comment', 'share', 'bookmark'];
        $executionTimes = [];

        foreach ($engagementTypes as $type) {
            $startTime = microtime(true);
            DB::enableQueryLog();

            $response = $this->actingAs($this->user)
                ->postJson("/api/posts/{$post->id}/engage", [
                    'type' => $type,
                    'metadata' => $type === 'comment' ? ['comment' => 'Test comment'] : null,
                ]);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            $executionTimes[$type] = $executionTime;
            $queries = DB::getQueryLog();

            $response->assertStatus(200);

            // Each engagement should be fast
            $this->assertLessThan(
                $this->performanceThresholds['engagement_update'],
                $executionTime,
                "Engagement '{$type}' took {$executionTime}s, exceeds threshold"
            );

            $this->assertLessThan(
                15,
                count($queries),
                "Engagement '{$type}' used too many queries"
            );

            // Remove engagement for next test
            $this->actingAs($this->user)
                ->deleteJson("/api/posts/{$post->id}/engage", ['type' => $type]);
        }

        // All engagement types should have similar performance
        $avgTime = array_sum($executionTimes) / count($executionTimes);
        foreach ($executionTimes as $type => $time) {
            $this->assertLessThan(
                $avgTime * 2,
                $time,
                "Engagement '{$type}' is significantly slower than average"
            );
        }
    }

    public function test_timeline_filtering_performance()
    {
        $filters = [
            ['post_type' => 'text'],
            ['post_type' => 'career_update'],
            ['date_from' => now()->subWeek()->format('Y-m-d')],
            ['date_to' => now()->format('Y-m-d')],
            ['post_type' => 'text', 'date_from' => now()->subWeek()->format('Y-m-d')],
        ];

        foreach ($filters as $filter) {
            $queryString = http_build_query($filter);

            $startTime = microtime(true);
            DB::enableQueryLog();

            $response = $this->actingAs($this->user)
                ->getJson("/api/timeline?{$queryString}");

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            $queries = DB::getQueryLog();

            $response->assertStatus(200);

            // Filtered queries should still be fast
            $this->assertLessThan(
                $this->performanceThresholds['timeline_generation'] * 1.5,
                $executionTime,
                'Filtered timeline with '.json_encode($filter)." took {$executionTime}s"
            );

            $this->assertLessThan(
                $this->performanceThresholds['database_queries'] * 1.2,
                count($queries),
                'Filtered timeline used too many queries'
            );
        }
    }

    public function test_search_performance()
    {
        $searchQueries = [
            'software',
            'career development',
            'tech university',
            'javascript react',
            'product manager',
        ];

        foreach ($searchQueries as $query) {
            $startTime = microtime(true);
            DB::enableQueryLog();

            $response = $this->actingAs($this->user)
                ->getJson('/api/search?query='.urlencode($query));

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            $queries = DB::getQueryLog();

            $response->assertStatus(200);

            // Search should be fast
            $this->assertLessThan(
                $this->performanceThresholds['search_query'],
                $executionTime,
                "Search for '{$query}' took {$executionTime}s, exceeds threshold"
            );

            $this->assertLessThan(
                30,
                count($queries),
                "Search for '{$query}' used too many queries"
            );
        }
    }

    public function test_caching_effectiveness()
    {
        // Clear cache
        Cache::flush();

        // First timeline request (should be slow - no cache)
        $startTime = microtime(true);
        $response1 = $this->actingAs($this->user)
            ->getJson('/api/timeline');
        $firstRequestTime = microtime(true) - $startTime;

        $response1->assertStatus(200);

        // Second timeline request (should be faster - cached)
        $startTime = microtime(true);
        $response2 = $this->actingAs($this->user)
            ->getJson('/api/timeline');
        $secondRequestTime = microtime(true) - $startTime;

        $response2->assertStatus(200);

        // Cached request should be significantly faster
        $this->assertLessThan(
            $firstRequestTime * 0.5,
            $secondRequestTime,
            'Cached timeline request should be at least 50% faster'
        );

        // Verify same data is returned
        $this->assertEquals(
            $response1->json('data.posts.data'),
            $response2->json('data.posts.data')
        );
    }

    public function test_concurrent_user_performance()
    {
        // Simulate multiple users accessing timeline simultaneously
        $users = User::factory()->count(10)->create();
        $executionTimes = [];

        foreach ($users as $index => $user) {
            $startTime = microtime(true);

            $response = $this->actingAs($user)
                ->getJson('/api/timeline');

            $endTime = microtime(true);
            $executionTimes[] = $endTime - $startTime;

            $response->assertStatus(200);
        }

        // Calculate performance metrics
        $avgTime = array_sum($executionTimes) / count($executionTimes);
        $maxTime = max($executionTimes);
        $minTime = min($executionTimes);

        // Performance should be consistent across users
        $this->assertLessThan(
            $this->performanceThresholds['timeline_generation'],
            $avgTime,
            "Average timeline generation time {$avgTime}s exceeds threshold"
        );

        $this->assertLessThan(
            $avgTime * 3,
            $maxTime,
            "Maximum timeline generation time {$maxTime}s is too much higher than average {$avgTime}s"
        );

        // Performance variance should be reasonable
        $variance = ($maxTime - $minTime) / $avgTime;
        $this->assertLessThan(
            2.0,
            $variance,
            "Performance variance is too high: {$variance}"
        );
    }

    public function test_memory_usage_during_timeline_generation()
    {
        $memoryBefore = memory_get_usage(true);

        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline?per_page=50');

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;

        $response->assertStatus(200);

        // Memory usage should be reasonable (less than 50MB for timeline generation)
        $this->assertLessThan(
            50 * 1024 * 1024,
            $memoryUsed,
            "Timeline generation used {$memoryUsed} bytes of memory, which is excessive"
        );
    }

    public function test_database_query_optimization()
    {
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline?per_page=20');

        $queries = DB::getQueryLog();
        $response->assertStatus(200);

        // Analyze query patterns
        $selectQueries = array_filter($queries, function ($query) {
            return stripos($query['query'], 'select') === 0;
        });

        $updateQueries = array_filter($queries, function ($query) {
            return stripos($query['query'], 'update') === 0;
        });

        $insertQueries = array_filter($queries, function ($query) {
            return stripos($query['query'], 'insert') === 0;
        });

        // Timeline generation should mostly be SELECT queries
        $this->assertGreaterThan(
            count($updateQueries) + count($insertQueries),
            count($selectQueries),
            'Timeline should primarily use SELECT queries'
        );

        // Check for N+1 query problems
        $duplicateQueries = [];
        foreach ($queries as $query) {
            $normalizedQuery = preg_replace('/\d+/', '?', $query['query']);
            $duplicateQueries[$normalizedQuery] = ($duplicateQueries[$normalizedQuery] ?? 0) + 1;
        }

        $maxDuplicates = max($duplicateQueries);
        $this->assertLessThan(
            10,
            $maxDuplicates,
            "Potential N+1 query problem detected: {$maxDuplicates} similar queries"
        );
    }

    public function test_large_dataset_scalability()
    {
        // Create even larger dataset
        $largeUsers = User::factory()->count(500)->create();
        $circles = Circle::all();

        // Add users to circles
        foreach ($largeUsers as $user) {
            $user->circles()->attach($circles->random(2)->pluck('id'));
        }

        // Create many posts
        foreach ($largeUsers->take(100) as $user) {
            Post::factory()->count(20)->create([
                'user_id' => $user->id,
                'visibility' => 'public',
                'created_at' => now()->subDays(rand(0, 60)),
            ]);
        }

        // Test performance with large dataset
        $startTime = microtime(true);

        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline?per_page=20');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);

        // Performance should still be acceptable with large dataset
        $this->assertLessThan(
            $this->performanceThresholds['timeline_generation'] * 2,
            $executionTime,
            "Timeline generation with large dataset took {$executionTime}s"
        );

        // Verify data quality is maintained
        $timeline = $response->json('data.posts.data');
        $this->assertCount(20, $timeline);
    }

    protected function tearDown(): void
    {
        // Clean up performance test data
        DB::disableQueryLog();
        Cache::flush();

        parent::tearDown();
    }
}
