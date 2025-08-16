<?php

namespace Tests\Feature;

use App\Models\Circle;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TimelineControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_get_timeline()
    {
        // Create some public posts
        Post::factory()->count(3)->create(['visibility' => 'public']);

        $response = $this->getJson('/api/timeline');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'posts',
                    'next_cursor',
                    'has_more',
                ],
                'message',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(3, $response->json('data.posts'));
    }

    public function test_can_get_timeline_with_limit()
    {
        Post::factory()->count(5)->create(['visibility' => 'public']);

        $response = $this->getJson('/api/timeline?limit=2');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.posts'));
        $this->assertTrue($response->json('data.has_more'));
    }

    public function test_can_get_timeline_with_cursor()
    {
        $posts = Post::factory()->count(3)->create(['visibility' => 'public']);

        // Get first page
        $firstResponse = $this->getJson('/api/timeline?limit=1');
        $cursor = $firstResponse->json('data.next_cursor');

        // Get second page with cursor
        $response = $this->getJson("/api/timeline?cursor={$cursor}&limit=1");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.posts'));
    }

    public function test_can_refresh_timeline()
    {
        Post::factory()->count(2)->create(['visibility' => 'public']);

        $response = $this->getJson('/api/timeline/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'posts',
                    'next_cursor',
                    'has_more',
                ],
                'message',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Timeline refreshed successfully', $response->json('message'));
    }

    public function test_can_load_more_posts()
    {
        Post::factory()->count(3)->create(['visibility' => 'public']);

        // Get first page to get cursor
        $firstResponse = $this->getJson('/api/timeline?limit=1');
        $cursor = $firstResponse->json('data.next_cursor');

        $response = $this->getJson("/api/timeline/load-more?cursor={$cursor}");

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('More posts loaded successfully', $response->json('message'));
    }

    public function test_load_more_requires_cursor()
    {
        $response = $this->getJson('/api/timeline/load-more');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cursor']);
    }

    public function test_can_get_circle_posts()
    {
        // Create circle and add user
        $circle = Circle::factory()->create();
        $circle->addMember($this->user);

        // Create circle posts
        Post::factory()->count(2)->create([
            'visibility' => 'circles',
            'circle_ids' => [$circle->id],
        ]);

        $response = $this->getJson('/api/timeline/circles');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.posts'));
        $this->assertEquals('Circle posts retrieved successfully', $response->json('message'));
    }

    public function test_can_get_group_posts()
    {
        // Create group and add user
        $group = Group::factory()->create();
        $group->addMember($this->user);

        // Create group posts
        Post::factory()->count(2)->create([
            'visibility' => 'groups',
            'group_ids' => [$group->id],
        ]);

        $response = $this->getJson('/api/timeline/groups');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.posts'));
        $this->assertEquals('Group posts retrieved successfully', $response->json('message'));
    }

    public function test_validates_limit_parameter()
    {
        $response = $this->getJson('/api/timeline?limit=0');
        $response->assertStatus(422)->assertJsonValidationErrors(['limit']);

        $response = $this->getJson('/api/timeline?limit=100');
        $response->assertStatus(422)->assertJsonValidationErrors(['limit']);

        $response = $this->getJson('/api/timeline?limit=invalid');
        $response->assertStatus(422)->assertJsonValidationErrors(['limit']);
    }

    public function test_requires_authentication()
    {
        // Remove authentication
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/timeline');
        $response->assertStatus(401);
    }

    public function test_timeline_only_shows_visible_posts()
    {
        // Create posts with different visibility
        $publicPost = Post::factory()->create(['visibility' => 'public']);
        $privatePost = Post::factory()->create(['visibility' => 'private']);

        // Create circle post but user is not in circle
        $circle = Circle::factory()->create();
        $circlePost = Post::factory()->create([
            'visibility' => 'circles',
            'circle_ids' => [$circle->id],
        ]);

        $response = $this->getJson('/api/timeline');

        $response->assertStatus(200);
        $postIds = collect($response->json('data.posts'))->pluck('id')->toArray();

        $this->assertContains($publicPost->id, $postIds);
        $this->assertNotContains($privatePost->id, $postIds);
        $this->assertNotContains($circlePost->id, $postIds);
    }

    public function test_timeline_includes_own_posts()
    {
        $ownPost = Post::factory()->create([
            'user_id' => $this->user->id,
            'visibility' => 'private',
        ]);

        $response = $this->getJson('/api/timeline');

        $response->assertStatus(200);
        $postIds = collect($response->json('data.posts'))->pluck('id')->toArray();

        $this->assertContains($ownPost->id, $postIds);
    }

    public function test_handles_empty_timeline()
    {
        $response = $this->getJson('/api/timeline');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data.posts'));
        $this->assertFalse($response->json('data.has_more'));
        $this->assertNull($response->json('data.next_cursor'));
    }

    public function test_timeline_posts_are_ordered_by_relevance()
    {
        // Create posts at different times
        $olderPost = Post::factory()->create([
            'visibility' => 'public',
            'created_at' => now()->subHours(2),
        ]);

        $newerPost = Post::factory()->create([
            'visibility' => 'public',
            'created_at' => now()->subHour(),
        ]);

        $response = $this->getJson('/api/timeline');

        $response->assertStatus(200);
        $posts = $response->json('data.posts');

        // Newer post should come first (higher relevance score)
        $this->assertEquals($newerPost->id, $posts[0]['id']);
        $this->assertEquals($olderPost->id, $posts[1]['id']);
    }
}
