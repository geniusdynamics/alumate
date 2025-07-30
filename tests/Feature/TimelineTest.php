<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Circle;
use App\Models\Group;
use App\Models\Tenant;
use App\Services\TimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class TimelineTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private TimelineService $timelineService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->timelineService = app(TimelineService::class);
        
        // Clear Redis cache
        Redis::flushall();
    }

    /** @test */
    public function it_can_get_timeline_for_authenticated_user()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->getJson('/api/timeline');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'posts',
                        'next_cursor',
                        'has_more',
                        'generated_at'
                    ]
                ]);
    }

    /** @test */
    public function it_requires_authentication_for_timeline()
    {
        $response = $this->getJson('/api/timeline');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_refresh_timeline()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/timeline/refresh');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'posts',
                        'next_cursor',
                        'has_more',
                        'generated_at'
                    ],
                    'refreshed_at'
                ]);
    }

    /** @test */
    public function it_can_load_more_posts_with_cursor()
    {
        $this->actingAs($this->user, 'sanctum');

        // Create some posts first
        $circle = Circle::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->circles()->attach($circle);
        
        Post::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'circle_ids' => [$circle->id]
        ]);

        // Get initial timeline
        $initialResponse = $this->getJson('/api/timeline?limit=2');
        $cursor = $initialResponse->json('data.next_cursor');

        // Load more with cursor
        $response = $this->getJson("/api/timeline/load-more?cursor={$cursor}&limit=2");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'posts',
                        'next_cursor',
                        'has_more'
                    ]
                ]);
    }

    /** @test */
    public function it_validates_timeline_request_parameters()
    {
        $this->actingAs($this->user, 'sanctum');

        // Test invalid limit
        $response = $this->getJson('/api/timeline?limit=100');
        $response->assertStatus(422);

        // Test invalid cursor for load more
        $response = $this->getJson('/api/timeline/load-more');
        $response->assertStatus(422);
    }

    /** @test */
    public function timeline_service_generates_personalized_timeline()
    {
        // Create circles and groups for user
        $circle = Circle::factory()->create(['tenant_id' => $this->tenant->id]);
        $group = Group::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $this->user->circles()->attach($circle);
        $this->user->groups()->attach($group);

        // Create posts in circle and group
        $circlePosts = Post::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'circle_ids' => [$circle->id]
        ]);
        
        $groupPosts = Post::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'group_ids' => [$group->id]
        ]);

        $timeline = $this->timelineService->generateTimelineForUser($this->user);

        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('posts', $timeline);
        $this->assertArrayHasKey('next_cursor', $timeline);
        $this->assertArrayHasKey('has_more', $timeline);
        $this->assertCount(5, $timeline['posts']);
    }

    /** @test */
    public function timeline_service_gets_circle_posts()
    {
        $circle = Circle::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->circles()->attach($circle);

        $posts = Post::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'circle_ids' => [$circle->id]
        ]);

        $circlePosts = $this->timelineService->getCirclePosts($this->user);

        $this->assertCount(3, $circlePosts);
        $this->assertEquals($posts->pluck('id')->sort(), $circlePosts->pluck('id')->sort());
    }

    /** @test */
    public function timeline_service_gets_group_posts()
    {
        $group = Group::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->groups()->attach($group);

        $posts = Post::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'group_ids' => [$group->id]
        ]);

        $groupPosts = $this->timelineService->getGroupPosts($this->user);

        $this->assertCount(2, $groupPosts);
        $this->assertEquals($posts->pluck('id')->sort(), $groupPosts->pluck('id')->sort());
    }

    /** @test */
    public function timeline_service_scores_posts_correctly()
    {
        $otherUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $post = Post::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $otherUser->id
        ]);

        $score = $this->timelineService->scorePost($post, $this->user);

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
    }

    /** @test */
    public function timeline_service_caches_timeline()
    {
        $timeline = [
            'posts' => [],
            'next_cursor' => null,
            'has_more' => false,
            'generated_at' => now()->toISOString()
        ];

        $this->timelineService->cacheTimeline($this->user, $timeline);

        // Check if timeline is cached
        $cacheKey = "timeline:user:{$this->user->id}";
        $cached = Redis::get($cacheKey);
        
        $this->assertNotNull($cached);
        $this->assertEquals($timeline, json_decode($cached, true));
    }

    /** @test */
    public function timeline_service_invalidates_cache()
    {
        // Cache a timeline first
        $timeline = [
            'posts' => [],
            'next_cursor' => null,
            'has_more' => false,
            'generated_at' => now()->toISOString()
        ];
        
        $this->timelineService->cacheTimeline($this->user, $timeline);

        // Verify it's cached
        $cacheKey = "timeline:user:{$this->user->id}";
        $this->assertNotNull(Redis::get($cacheKey));

        // Invalidate cache
        $this->timelineService->invalidateTimelineCache($this->user);

        // Verify it's cleared
        $this->assertNull(Redis::get($cacheKey));
    }

    /** @test */
    public function timeline_supports_cursor_based_pagination()
    {
        $circle = Circle::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->circles()->attach($circle);

        // Create posts with different timestamps
        $posts = collect();
        for ($i = 0; $i < 5; $i++) {
            $posts->push(Post::factory()->create([
                'tenant_id' => $this->tenant->id,
                'circle_ids' => [$circle->id],
                'created_at' => now()->subMinutes($i)
            ]));
        }

        // Get first page
        $firstPage = $this->timelineService->generateTimelineForUser($this->user, 2);
        $this->assertCount(2, $firstPage['posts']);
        $this->assertTrue($firstPage['has_more']);
        $this->assertNotNull($firstPage['next_cursor']);

        // Get second page with cursor
        $secondPage = $this->timelineService->generateTimelineForUser(
            $this->user, 
            2, 
            $firstPage['next_cursor']
        );
        
        $this->assertCount(2, $secondPage['posts']);
        
        // Ensure no duplicate posts
        $firstPageIds = collect($firstPage['posts'])->pluck('id');
        $secondPageIds = collect($secondPage['posts'])->pluck('id');
        $this->assertTrue($firstPageIds->intersect($secondPageIds)->isEmpty());
    }

    /** @test */
    public function timeline_returns_empty_for_user_with_no_circles_or_groups()
    {
        $timeline = $this->timelineService->generateTimelineForUser($this->user);

        $this->assertIsArray($timeline);
        $this->assertEmpty($timeline['posts']);
        $this->assertFalse($timeline['has_more']);
        $this->assertNull($timeline['next_cursor']);
    }

    /** @test */
    public function timeline_includes_post_engagement_data()
    {
        $circle = Circle::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->circles()->attach($circle);

        $post = Post::factory()->create([
            'tenant_id' => $this->tenant->id,
            'circle_ids' => [$circle->id]
        ]);

        // Add some engagements
        $post->engagements()->create([
            'user_id' => $this->user->id,
            'type' => 'like'
        ]);

        $timeline = $this->timelineService->generateTimelineForUser($this->user);

        $this->assertCount(1, $timeline['posts']);
        $timelinePost = $timeline['posts'][0];
        $this->assertArrayHasKey('engagements', $timelinePost);
    }
}