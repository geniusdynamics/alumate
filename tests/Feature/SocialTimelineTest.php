<?php

namespace Tests\Feature;

use App\Events\PostCreated;
use App\Events\PostEngagement;
use App\Models\Circle;
use App\Models\Group;
use App\Models\Post;
use App\Models\PostEngagement as PostEngagementModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SocialTimelineTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected User $otherUser;

    protected Circle $circle;

    protected Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->circle = Circle::factory()->create();
        $this->group = Group::factory()->create();

        // Add users to circle and group
        $this->user->circles()->attach($this->circle->id);
        $this->otherUser->circles()->attach($this->circle->id);

        $this->user->groups()->attach($this->group->id);
        $this->otherUser->groups()->attach($this->group->id);
    }

    public function test_user_can_create_text_post()
    {
        Event::fake();

        $postData = [
            'content' => 'This is my first post on the alumni platform!',
            'post_type' => 'text',
            'visibility' => 'public',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', $postData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Post created successfully',
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $this->user->id,
            'content' => 'This is my first post on the alumni platform!',
            'post_type' => 'text',
            'visibility' => 'public',
        ]);

        Event::assertDispatched(PostCreated::class);
    }

    public function test_user_can_create_post_with_media()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('test-image.jpg');

        $postData = [
            'content' => 'Check out this amazing photo!',
            'post_type' => 'media',
            'visibility' => 'public',
            'media_files' => [$file],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', $postData);

        $response->assertStatus(201);

        $post = Post::where('user_id', $this->user->id)->first();
        $this->assertNotEmpty($post->media_urls);
        $this->assertEquals('media', $post->post_type);

        Storage::disk('public')->assertExists('posts/'.$file->hashName());
    }

    public function test_user_can_create_career_update_post()
    {
        $postData = [
            'content' => 'Excited to announce my new role as Senior Developer at TechCorp!',
            'post_type' => 'career_update',
            'visibility' => 'public',
            'metadata' => [
                'career_update' => [
                    'type' => 'new_job',
                    'company' => 'TechCorp',
                    'title' => 'Senior Developer',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', $postData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('posts', [
            'user_id' => $this->user->id,
            'post_type' => 'career_update',
        ]);
    }

    public function test_user_can_create_post_visible_to_circles()
    {
        $postData = [
            'content' => 'This post is only for my circle members',
            'post_type' => 'text',
            'visibility' => 'circles',
            'circle_ids' => [$this->circle->id],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', $postData);

        $response->assertStatus(201);

        $post = Post::where('user_id', $this->user->id)->first();
        $this->assertEquals('circles', $post->visibility);
        $this->assertContains($this->circle->id, $post->circle_ids);
    }

    public function test_user_can_create_post_visible_to_groups()
    {
        $postData = [
            'content' => 'This post is only for my group members',
            'post_type' => 'text',
            'visibility' => 'groups',
            'group_ids' => [$this->group->id],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', $postData);

        $response->assertStatus(201);

        $post = Post::where('user_id', $this->user->id)->first();
        $this->assertEquals('groups', $post->visibility);
        $this->assertContains($this->group->id, $post->group_ids);
    }

    public function test_user_can_like_post()
    {
        Event::fake();

        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$post->id}/engage", [
                'type' => 'like',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Post engagement updated successfully',
            ]);

        $this->assertDatabaseHas('post_engagements', [
            'post_id' => $post->id,
            'user_id' => $this->user->id,
            'type' => 'like',
        ]);

        Event::assertDispatched(PostEngagement::class);
    }

    public function test_user_can_comment_on_post()
    {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$post->id}/engage", [
                'type' => 'comment',
                'metadata' => [
                    'comment' => 'Great post! Thanks for sharing.',
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('post_engagements', [
            'post_id' => $post->id,
            'user_id' => $this->user->id,
            'type' => 'comment',
        ]);
    }

    public function test_user_can_share_post()
    {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$post->id}/engage", [
                'type' => 'share',
                'metadata' => [
                    'commentary' => 'This is exactly what I was thinking!',
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('post_engagements', [
            'post_id' => $post->id,
            'user_id' => $this->user->id,
            'type' => 'share',
        ]);
    }

    public function test_user_can_bookmark_post()
    {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$post->id}/engage", [
                'type' => 'bookmark',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('post_engagements', [
            'post_id' => $post->id,
            'user_id' => $this->user->id,
            'type' => 'bookmark',
        ]);
    }

    public function test_user_can_remove_engagement()
    {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);

        // First, create an engagement
        PostEngagementModel::factory()->create([
            'post_id' => $post->id,
            'user_id' => $this->user->id,
            'type' => 'like',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/posts/{$post->id}/engage", [
                'type' => 'like',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('post_engagements', [
            'post_id' => $post->id,
            'user_id' => $this->user->id,
            'type' => 'like',
        ]);
    }

    public function test_user_can_view_post_details()
    {
        $post = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'visibility' => 'public',
        ]);

        // Add some engagements
        PostEngagementModel::factory()->count(3)->create([
            'post_id' => $post->id,
            'type' => 'like',
        ]);

        PostEngagementModel::factory()->count(2)->create([
            'post_id' => $post->id,
            'type' => 'comment',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'post' => [
                        'id',
                        'content',
                        'user',
                        'engagements',
                        'comments',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_user_can_get_timeline_posts()
    {
        // Create posts from different users
        Post::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'visibility' => 'public',
        ]);

        Post::factory()->count(2)->create([
            'user_id' => $this->otherUser->id,
            'visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'posts' => [
                        'data' => [
                            '*' => [
                                'id',
                                'content',
                                'user',
                                'engagement_counts',
                                'user_engagement',
                                'created_at',
                            ],
                        ],
                        'meta',
                    ],
                ],
            ]);
    }

    public function test_user_can_filter_timeline_by_post_type()
    {
        Post::factory()->create([
            'user_id' => $this->user->id,
            'post_type' => 'career_update',
            'visibility' => 'public',
        ]);

        Post::factory()->create([
            'user_id' => $this->user->id,
            'post_type' => 'text',
            'visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline?post_type=career_update');

        $response->assertStatus(200);

        $posts = $response->json('data.posts.data');
        $this->assertCount(1, $posts);
        $this->assertEquals('career_update', $posts[0]['post_type']);
    }

    public function test_user_cannot_view_private_posts_from_others()
    {
        $privatePost = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'visibility' => 'private',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$privatePost->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'You do not have permission to view this post',
            ]);
    }

    public function test_user_can_view_circle_posts_if_member()
    {
        $circlePost = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'visibility' => 'circles',
            'circle_ids' => [$this->circle->id],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$circlePost->id}");

        $response->assertStatus(200);
    }

    public function test_user_cannot_view_circle_posts_if_not_member()
    {
        $otherCircle = Circle::factory()->create();
        $circlePost = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'visibility' => 'circles',
            'circle_ids' => [$otherCircle->id],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$circlePost->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_own_post()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $updateData = [
            'content' => 'Updated post content',
            'visibility' => 'circles',
            'circle_ids' => [$this->circle->id],
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/posts/{$post->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => 'Post updated successfully',
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'content' => 'Updated post content',
            'visibility' => 'circles',
        ]);
    }

    public function test_user_cannot_update_others_post()
    {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);

        $updateData = [
            'content' => 'Trying to update someone else\'s post',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/posts/{$post->id}", $updateData);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_post()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Post deleted successfully',
            ]);

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_user_cannot_delete_others_post()
    {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(403);
    }

    public function test_post_engagement_counts_are_accurate()
    {
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);

        // Create various engagements
        PostEngagementModel::factory()->count(5)->create([
            'post_id' => $post->id,
            'type' => 'like',
        ]);

        PostEngagementModel::factory()->count(3)->create([
            'post_id' => $post->id,
            'type' => 'comment',
        ]);

        PostEngagementModel::factory()->count(2)->create([
            'post_id' => $post->id,
            'type' => 'share',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200);

        $postData = $response->json('data.post');
        $engagementCounts = collect($postData['engagements'])->countBy('type');

        $this->assertEquals(5, $engagementCounts['like']);
        $this->assertEquals(3, $engagementCounts['comment']);
        $this->assertEquals(2, $engagementCounts['share']);
    }

    public function test_user_can_save_post_draft()
    {
        $draftData = [
            'content' => 'This is a draft post',
            'post_type' => 'text',
            'visibility' => 'public',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/posts/drafts', $draftData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('post_drafts', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_get_saved_drafts()
    {
        // Create some drafts in the database
        \DB::table('post_drafts')->insert([
            [
                'user_id' => $this->user->id,
                'content' => 'Draft 1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $this->user->id,
                'content' => 'Draft 2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/posts/drafts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'drafts' => [
                        '*' => [
                            'user_id',
                            'content',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
            ]);

        $drafts = $response->json('data.drafts');
        $this->assertCount(2, $drafts);
    }

    public function test_timeline_pagination_works()
    {
        // Create many posts
        Post::factory()->count(25)->create([
            'visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline?per_page=10&page=1');

        $response->assertStatus(200);

        $meta = $response->json('data.posts.meta');
        $this->assertEquals(1, $meta['current_page']);
        $this->assertEquals(10, $meta['per_page']);
        $this->assertGreaterThan(1, $meta['last_page']);
    }

    public function test_timeline_shows_recent_posts_first()
    {
        $oldPost = Post::factory()->create([
            'visibility' => 'public',
            'created_at' => now()->subDays(5),
        ]);

        $newPost = Post::factory()->create([
            'visibility' => 'public',
            'created_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline');

        $response->assertStatus(200);

        $posts = $response->json('data.posts.data');
        $this->assertEquals($newPost->id, $posts[0]['id']);
        $this->assertEquals($oldPost->id, $posts[1]['id']);
    }
}
