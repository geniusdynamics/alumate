<?php

namespace Tests\Unit;

use App\Models\Circle;
use App\Models\Connection;
use App\Models\Group;
use App\Models\Post;
use App\Models\PostEngagement;
use App\Models\User;
use App\Services\TimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TimelineServiceTest extends TestCase
{
    use RefreshDatabase;

    private TimelineService $timelineService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->timelineService = new TimelineService();
        $this->user = User::factory()->create();
    }

    public function test_generates_timeline_for_user()
    {
        // Create some posts
        $posts = Post::factory()->count(5)->create([
            'visibility' => 'public'
        ]);

        $timeline = $this->timelineService->generateTimelineForUser($this->user, 10);

        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('posts', $timeline);
        $this->assertArrayHasKey('next_cursor', $timeline);
        $this->assertArrayHasKey('has_more', $timeline);
        $this->assertCount(5, $timeline['posts']);
    }

    public function test_gets_circle_posts_for_user()
    {
        // Create a circle and add user to it
        $circle = Circle::factory()->create();
        $circle->addMember($this->user);

        // Create posts for the circle
        $posts = Post::factory()->count(3)->create([
            'visibility' => 'circles',
            'circle_ids' => [$circle->id]
        ]);

        $circlePosts = $this->timelineService->getCirclePosts($this->user, 10);

        $this->assertCount(3, $circlePosts);
    }

    public function test_gets_group_posts_for_user()
    {
        // Create a group and add user to it
        $group = Group::factory()->create();
        $group->addMember($this->user);

        // Create posts for the group
        $posts = Post::factory()->count(2)->create([
            'visibility' => 'groups',
            'group_ids' => [$group->id]
        ]);

        $groupPosts = $this->timelineService->getGroupPosts($this->user, 10);

        $this->assertCount(2, $groupPosts);
    }

    public function test_scores_post_correctly()
    {
        $post = Post::factory()->create([
            'visibility' => 'public',
            'created_at' => now()->subHour()
        ]);

        $score = $this->timelineService->scorePost($post, $this->user);

        $this->assertIsFloat($score);
        $this->assertGreaterThan(0, $score);
    }

    public function test_scores_post_higher_for_connections()
    {
        $connectedUser = User::factory()->create();
        
        // Create connection
        Connection::create([
            'user_id' => $this->user->id,
            'connected_user_id' => $connectedUser->id,
            'status' => 'accepted'
        ]);

        $postFromConnection = Post::factory()->create([
            'user_id' => $connectedUser->id,
            'visibility' => 'public',
            'created_at' => now()->subHour()
        ]);

        $postFromStranger = Post::factory()->create([
            'visibility' => 'public',
            'created_at' => now()->subHour()
        ]);

        $connectionScore = $this->timelineService->scorePost($postFromConnection, $this->user);
        $strangerScore = $this->timelineService->scorePost($postFromStranger, $this->user);

        $this->assertGreaterThan($strangerScore, $connectionScore);
    }

    public function test_scores_post_higher_with_more_engagement()
    {
        $popularPost = Post::factory()->create([
            'visibility' => 'public',
            'created_at' => now()->subHour()
        ]);

        $unpopularPost = Post::factory()->create([
            'visibility' => 'public',
            'created_at' => now()->subHour()
        ]);

        // Add engagements to popular post
        PostEngagement::factory()->count(5)->create([
            'post_id' => $popularPost->id,
            'type' => 'like'
        ]);

        $popularScore = $this->timelineService->scorePost($popularPost, $this->user);
        $unpopularScore = $this->timelineService->scorePost($unpopularPost, $this->user);

        $this->assertGreaterThan($unpopularScore, $popularScore);
    }

    public function test_caches_timeline()
    {
        Cache::shouldReceive('put')
            ->once()
            ->with(
                $this->stringContains('timeline:user:' . $this->user->id),
                $this->anything(),
                $this->anything()
            );

        $timeline = ['posts' => [], 'next_cursor' => null, 'has_more' => false];
        $this->timelineService->cacheTimeline($this->user, $timeline);
    }

    public function test_invalidates_timeline_cache()
    {
        $mockRedis = $this->createMock(\Illuminate\Redis\Connections\Connection::class);
        $mockRedis->expects($this->once())
                  ->method('keys')
                  ->with('timeline:user:' . $this->user->id . ':*')
                  ->willReturn(['key1', 'key2']);

        $mockRedis->expects($this->once())
                  ->method('del')
                  ->with(['key1', 'key2']);

        Cache::shouldReceive('getRedis')
            ->twice()
            ->andReturn($mockRedis);

        $this->timelineService->invalidateTimelineCache($this->user);
    }

    public function test_returns_empty_collection_for_user_with_no_circles()
    {
        $circlePosts = $this->timelineService->getCirclePosts($this->user, 10);
        
        $this->assertCount(0, $circlePosts);
    }

    public function test_returns_empty_collection_for_user_with_no_groups()
    {
        $groupPosts = $this->timelineService->getGroupPosts($this->user, 10);
        
        $this->assertCount(0, $groupPosts);
    }

    public function test_applies_cursor_pagination()
    {
        // Create posts with different timestamps
        $olderPost = Post::factory()->create([
            'visibility' => 'public',
            'created_at' => now()->subHours(2)
        ]);

        $newerPost = Post::factory()->create([
            'visibility' => 'public',
            'created_at' => now()->subHour()
        ]);

        // Get first page
        $firstPage = $this->timelineService->generateTimelineForUser($this->user, 1);
        
        $this->assertCount(1, $firstPage['posts']);
        $this->assertEquals($newerPost->id, $firstPage['posts'][0]['id']);
        $this->assertNotNull($firstPage['next_cursor']);

        // Get second page using cursor
        $secondPage = $this->timelineService->generateTimelineForUser(
            $this->user, 
            1, 
            $firstPage['next_cursor']
        );

        $this->assertCount(1, $secondPage['posts']);
        $this->assertEquals($olderPost->id, $secondPage['posts'][0]['id']);
    }

    public function test_timeline_includes_posts_from_multiple_sources()
    {
        // Create a circle and add user
        $circle = Circle::factory()->create();
        $circle->addMember($this->user);

        // Create a group and add user
        $group = Group::factory()->create();
        $group->addMember($this->user);

        // Create posts from different sources
        $publicPost = Post::factory()->create(['visibility' => 'public']);
        $circlePost = Post::factory()->create([
            'visibility' => 'circles',
            'circle_ids' => [$circle->id]
        ]);
        $groupPost = Post::factory()->create([
            'visibility' => 'groups',
            'group_ids' => [$group->id]
        ]);

        $timeline = $this->timelineService->generateTimelineForUser($this->user, 10);

        $postIds = collect($timeline['posts'])->pluck('id')->toArray();
        
        $this->assertContains($publicPost->id, $postIds);
        $this->assertContains($circlePost->id, $postIds);
        $this->assertContains($groupPost->id, $postIds);
    }
}