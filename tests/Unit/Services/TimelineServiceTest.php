<?php

namespace Tests\Unit\Services;

use App\Models\Circle;
use App\Models\Connection;
use App\Models\Group;
use App\Models\Post;
use App\Models\PostEngagement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class TimelineServiceTest extends TestCase
{
    use RefreshDatabase;

    private TimelineService $timelineService;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->timelineService = new TimelineService;

        // Clear Redis cache
        Redis::flushall();
    }

    /** @test */
    public function it_generates_timeline_with_circle_and_group_posts()
    {
        // Create circle and group
        $circle = Circle::factory()->create(['tenant_id' => $this->tenant->id]);
        $group = Group::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->user->circles()->attach($circle);
        $this->user->groups()->attach($group);

        // Create posts
        $circlePost = Post::factory()->create([
            'tenant_id' => $this->tenant->id,
            'circle_ids' => [$circle->id],
        ]);

        $groupPost = Post::factory()->create([
            'tenant_id' => $this->tenant->id,
            'group_ids' => [$group->id],
        ]);

        $timeline = $this->timelineService->generateTimelineForUser($this->user);

        $this->assertCount(2, $timeline['posts']);
        $postIds = collect($timeline['posts'])->pluck('id');
        $this->assertTrue($postIds->contains($circlePost->id));
        $this->assertTrue($postIds->contains($groupPost->id));
    }

    /** @test */
    public function it_deduplicates_posts_from_multiple_sources()
    {
        $circle = Circle::factory()->create(['tenant_id' => $this->tenant->id]);
        $group = Group::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->user->circles()->attach($circle);
        $this->user->groups()->attach($group);

        // Create post that appears in both circle and group
        $post = Post::factory()->create([
            'tenant_id' => $this->tenant->id,
            'circle_ids' => [$circle->id],
            'group_ids' => [$group->id],
        ]);

        $timeline = $this->timelineService->generateTimelineForUser($this->user);

        $this->assertCount(1, $timeline['posts']);
        $this->assertEquals($post->id, $timeline['posts'][0]['id']);
    }

    /** @test */
    public function it_scores_posts_based_on_recency()
    {
        $circle = Circle::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->circles()->attach($circle);

        $oldPost = Post::factory()->create([
            'tenant_id' => $this->tenant->id,
            'circle_ids' => [$circle->id],
            'created_at' => now()->subDays(2),
        ]);

        $newPost = Post::factory()->create([
            'tenant_id' => $this->tenant->id,
            'circle_ids' => [$circle->id],
            'created_at' => now()->subHour(),
        ]);

        $oldScore = $this->timelineService->scorePost($oldPost, $this->user);
        $newScore = $this->timelineService->scorePost($newPost, $this->user);

        $this->assertGreaterThan($oldScore, $newScore);
    }

    /** @test */
    public function it_scores_posts_based_on_engagement()
    {
        $circle = Circle::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->circles()->attach($circle);

        $lowEngagementPost = Post::factory()->create([
            'tenant_id' => $this->tenant->id,
            'circle_ids' => [$circle->id],
        ]);

        $highEngagementPost = Post::factory()->create([
            'tenant_id' => $this->tenant->id,
            'circle_ids' => [$circle->id],
        ]);

        // Add engagements to high engagement post
        PostEngagement::factory()->count(5)->create([
            'post_id' => $highEngagementPost->id,
            'type' => 'like',
        ]);

        $lowScore = $this->timelineService->scorePost($lowEngagementPost, $this->user);
        $highScore = $this->timelineService->scorePost($highEngagementPost, $this->user);

        $this->assertGreaterThan($lowScore, $highScore);
    }

    /** @test */
    public function it_boosts_posts_from_connections()
    {
        $circle = Circle::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->circles()->attach($circle);

        $connectedUser = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $strangerUser = User::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create connection
        Connection::factory()->create([
            'user_id' => $this->user->id,
            'connected_user_id' => $connectedUser->id,
            'status' => 'accepted',
        ]);

        $connectionPost = Post::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $connectedUser->id,
            'circle_ids' => [$circle->id],
        ]);

        $strangerPost = Post::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $strangerUser->id,
            'circle_ids' => [$circle->id],
        ]);

        $connectionScore = $this->timelineService->scorePost($connectionPost, $this->user);
        $strangerScore = $this->timelineService->scorePost($strangerPost, $this->user);

        $this->assertGreaterThan($strangerScore, $connectionScore);
    }

    /** @test */
    public function it_boosts_posts_with_circle_overlap()
    {
        $circle1 = Circle::factory()->create(['tenant_id' => $this->tenant->id]);
        $circle2 = Circle::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->user->circles()->attach([$circle1->id, $circle2->id]);

        $singleCirclePost = Post::factory()->create([
            'tenant_id' => $this->tenant->id,
            'circle_ids' => [$circle1->id],
        ]);

        $multiCirclePost = Post::factory()->create([
            'tenant_id' => $this->tenant->id,
            'circle_ids' => [$circle1->id, $circle2->id],
        ]);

        $singleScore = $this->timelineService->scorePost($singleCirclePost, $this->user);
        $multiScore = $this->timelineService->scorePost($multiCirclePost, $this->user);

        $this->assertGreaterThan($singleScore, $multiScore);
    }

    /** @test */
    public function it_boosts_users_own_posts()
    {
        $circle = Circle::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->circles()->attach($circle);

        $otherUser = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $ownPost = Post::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'circle_ids' => [$circle->id],
        ]);

        $otherPost = Post::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $otherUser->id,
            'circle_ids' => [$circle->id],
        ]);

        $ownScore = $this->timelineService->scorePost($ownPost, $this->user);
        $otherScore = $this->timelineService->scorePost($otherPost, $this->user);

        $this->assertGreaterThan($otherScore, $ownScore);
    }

    /** @test */
    public function it_caches_timeline_with_correct_ttl()
    {
        $timeline = [
            'posts' => [],
            'next_cursor' => null,
            'has_more' => false,
            'generated_at' => now()->toISOString(),
        ];

        // Test active user (updated recently)
        $this->user->update(['updated_at' => now()->subMinutes(30)]);
        $this->timelineService->cacheTimeline($this->user, $timeline);

        $cacheKey = "timeline:user:{$this->user->id}";
        $ttl = Redis::ttl($cacheKey);

        // Should be around 15 minutes (900 seconds) for active user
        $this->assertGreaterThan(800, $ttl);
        $this->assertLessThan(900, $ttl);

        // Clear cache
        Redis::del($cacheKey);

        // Test inactive user
        $this->user->update(['updated_at' => now()->subHours(2)]);
        $this->timelineService->cacheTimeline($this->user, $timeline);

        $ttl = Redis::ttl($cacheKey);

        // Should be around 1 hour (3600 seconds) for inactive user
        $this->assertGreaterThan(3500, $ttl);
        $this->assertLessThan(3600, $ttl);
    }

    /** @test */
    public function it_invalidates_cache_for_user_and_connections()
    {
        $connectedUser = User::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create connection
        Connection::factory()->create([
            'user_id' => $this->user->id,
            'connected_user_id' => $connectedUser->id,
            'status' => 'accepted',
        ]);

        // Cache timelines for both users
        $timeline = ['posts' => [], 'next_cursor' => null, 'has_more' => false];
        $this->timelineService->cacheTimeline($this->user, $timeline);
        $this->timelineService->cacheTimeline($connectedUser, $timeline);

        // Verify both are cached
        $userCacheKey = "timeline:user:{$this->user->id}";
        $connectedCacheKey = "timeline:user:{$connectedUser->id}";

        $this->assertNotNull(Redis::get($userCacheKey));
        $this->assertNotNull(Redis::get($connectedCacheKey));

        // Invalidate user's cache
        $this->timelineService->invalidateTimelineCache($this->user);

        // Both should be cleared
        $this->assertNull(Redis::get($userCacheKey));
        $this->assertNull(Redis::get($connectedCacheKey));
    }

    /** @test */
    public function it_handles_cursor_based_pagination()
    {
        $circle = Circle::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->circles()->attach($circle);

        // Create posts with specific timestamps
        $posts = collect();
        for ($i = 0; $i < 5; $i++) {
            $posts->push(Post::factory()->create([
                'tenant_id' => $this->tenant->id,
                'circle_ids' => [$circle->id],
                'created_at' => now()->subMinutes($i),
            ]));
        }

        // Get first page
        $firstPage = $this->timelineService->generateTimelineForUser($this->user, 2);

        $this->assertCount(2, $firstPage['posts']);
        $this->assertTrue($firstPage['has_more']);
        $this->assertNotNull($firstPage['next_cursor']);

        // Get second page
        $secondPage = $this->timelineService->generateTimelineForUser(
            $this->user,
            2,
            $firstPage['next_cursor']
        );

        $this->assertCount(2, $secondPage['posts']);

        // Verify no duplicates
        $firstIds = collect($firstPage['posts'])->pluck('id');
        $secondIds = collect($secondPage['posts'])->pluck('id');
        $this->assertTrue($firstIds->intersect($secondIds)->isEmpty());
    }

    /** @test */
    public function it_returns_empty_timeline_for_user_without_circles_or_groups()
    {
        $timeline = $this->timelineService->generateTimelineForUser($this->user);

        $this->assertEmpty($timeline['posts']);
        $this->assertFalse($timeline['has_more']);
        $this->assertNull($timeline['next_cursor']);
    }

    /** @test */
    public function it_limits_posts_correctly()
    {
        $circle = Circle::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user->circles()->attach($circle);

        // Create more posts than limit
        Post::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'circle_ids' => [$circle->id],
        ]);

        $timeline = $this->timelineService->generateTimelineForUser($this->user, 5);

        $this->assertCount(5, $timeline['posts']);
        $this->assertTrue($timeline['has_more']);
    }
}
