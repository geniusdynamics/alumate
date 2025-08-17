<?php

namespace Tests\Feature;

use App\Events\PostCreated;
use App\Events\PostEngagement;
use App\Models\Circle;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SocialTimelineFlowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected User $connectedUser;

    protected User $circleUser;

    protected Circle $circle;

    protected Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->connectedUser = User::factory()->create();
        $this->circleUser = User::factory()->create();

        $this->circle = Circle::factory()->create();
        $this->group = Group::factory()->create();

        // Set up circle memberships
        $this->user->circles()->attach($this->circle->id);
        $this->circleUser->circles()->attach($this->circle->id);

        // Set up group memberships
        $this->user->groups()->attach($this->group->id);
        $this->connectedUser->groups()->attach($this->group->id);

        // Create connection between users
        $this->user->sendConnectionRequest($this->connectedUser);
        $this->connectedUser->acceptConnectionRequest(
            $this->connectedUser->receivedConnectionRequests()->first()->id
        );
    }

    public function test_complete_post_creation_and_engagement_flow()
    {
        Event::fake();
        Storage::fake('public');

        // Step 1: Create a text post
        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', [
                'content' => 'Excited to share my latest project!',
                'post_type' => 'text',
                'visibility' => 'public',
            ]);

        $response->assertStatus(201);
        $post = Post::where('user_id', $this->user->id)->first();
        $this->assertNotNull($post);
        Event::assertDispatched(PostCreated::class);

        // Step 2: Another user likes the post
        $response = $this->actingAs($this->connectedUser)
            ->postJson("/api/posts/{$post->id}/engage", [
                'type' => 'like',
            ]);

        $response->assertStatus(200);
        Event::assertDispatched(PostEngagement::class);

        // Step 3: User comments on the post
        $response = $this->actingAs($this->circleUser)
            ->postJson("/api/posts/{$post->id}/engage", [
                'type' => 'comment',
                'metadata' => [
                    'comment' => 'This looks amazing! Great work!',
                ],
            ]);

        $response->assertStatus(200);

        // Step 4: Verify engagement counts
        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
        $postData = $response->json('data.post');

        $this->assertArrayHasKey('engagements', $postData);
        $engagementCounts = collect($postData['engagements'])->countBy('type');
        $this->assertEquals(1, $engagementCounts['like']);
        $this->assertEquals(1, $engagementCounts['comment']);

        // Step 5: User shares the post
        $response = $this->actingAs($this->connectedUser)
            ->postJson("/api/posts/{$post->id}/engage", [
                'type' => 'share',
                'metadata' => [
                    'commentary' => 'Everyone should see this!',
                ],
            ]);

        $response->assertStatus(200);

        // Step 6: Verify final engagement state
        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
        $postData = $response->json('data.post');
        $engagementCounts = collect($postData['engagements'])->countBy('type');

        $this->assertEquals(1, $engagementCounts['like']);
        $this->assertEquals(1, $engagementCounts['comment']);
        $this->assertEquals(1, $engagementCounts['share']);
    }

    public function test_timeline_visibility_and_filtering_flow()
    {
        // Create posts with different visibility levels
        $publicPost = Post::factory()->create([
            'user_id' => $this->connectedUser->id,
            'visibility' => 'public',
            'post_type' => 'text',
        ]);

        $circlePost = Post::factory()->create([
            'user_id' => $this->circleUser->id,
            'visibility' => 'circles',
            'circle_ids' => [$this->circle->id],
            'post_type' => 'career_update',
        ]);

        $groupPost = Post::factory()->create([
            'user_id' => $this->connectedUser->id,
            'visibility' => 'groups',
            'group_ids' => [$this->group->id],
            'post_type' => 'text',
        ]);

        $privatePost = Post::factory()->create([
            'user_id' => $this->connectedUser->id,
            'visibility' => 'private',
            'post_type' => 'text',
        ]);

        // Test timeline shows appropriate posts
        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline');

        $response->assertStatus(200);
        $posts = $response->json('data.posts.data');
        $postIds = collect($posts)->pluck('id')->toArray();

        // Should see public, circle, and group posts but not private
        $this->assertContains($publicPost->id, $postIds);
        $this->assertContains($circlePost->id, $postIds);
        $this->assertContains($groupPost->id, $postIds);
        $this->assertNotContains($privatePost->id, $postIds);

        // Test filtering by post type
        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline?post_type=career_update');

        $response->assertStatus(200);
        $posts = $response->json('data.posts.data');

        $this->assertCount(1, $posts);
        $this->assertEquals('career_update', $posts[0]['post_type']);
        $this->assertEquals($circlePost->id, $posts[0]['id']);
    }

    public function test_media_post_creation_and_display_flow()
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('test-image.jpg', 800, 600);
        $video = UploadedFile::fake()->create('test-video.mp4', 5000, 'video/mp4');

        // Create media post
        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', [
                'content' => 'Check out these amazing memories!',
                'post_type' => 'media',
                'visibility' => 'public',
                'media_files' => [$image, $video],
            ]);

        $response->assertStatus(201);

        // Verify files were stored
        Storage::disk('public')->assertExists('posts/'.$image->hashName());
        Storage::disk('public')->assertExists('posts/'.$video->hashName());

        // Verify post data
        $post = Post::where('user_id', $this->user->id)->first();
        $this->assertEquals('media', $post->post_type);
        $this->assertNotEmpty($post->media_urls);
        $this->assertCount(2, $post->media_urls);

        // Test media display in timeline
        $response = $this->actingAs($this->connectedUser)
            ->getJson('/api/timeline');

        $response->assertStatus(200);
        $posts = $response->json('data.posts.data');
        $mediaPost = collect($posts)->firstWhere('id', $post->id);

        $this->assertNotNull($mediaPost);
        $this->assertArrayHasKey('media_urls', $mediaPost);
        $this->assertCount(2, $mediaPost['media_urls']);
    }

    public function test_post_draft_workflow()
    {
        // Save draft
        $draftData = [
            'content' => 'This is my draft post about career changes...',
            'post_type' => 'career_update',
            'visibility' => 'public',
            'metadata' => [
                'career_update' => [
                    'type' => 'new_job',
                    'company' => 'TechCorp',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/posts/drafts', $draftData);

        $response->assertStatus(200);

        // Retrieve drafts
        $response = $this->actingAs($this->user)
            ->getJson('/api/posts/drafts');

        $response->assertStatus(200);
        $drafts = $response->json('data.drafts');
        $this->assertCount(1, $drafts);
        $this->assertEquals($draftData['content'], $drafts[0]['content']);

        // Publish draft
        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', $draftData);

        $response->assertStatus(201);

        // Verify post was created
        $this->assertDatabaseHas('posts', [
            'user_id' => $this->user->id,
            'content' => $draftData['content'],
            'post_type' => 'career_update',
        ]);
    }

    public function test_post_update_and_deletion_flow()
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'content' => 'Original content',
            'visibility' => 'public',
        ]);

        // Update post
        $updateData = [
            'content' => 'Updated content with more details',
            'visibility' => 'circles',
            'circle_ids' => [$this->circle->id],
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/posts/{$post->id}", $updateData);

        $response->assertStatus(200);

        // Verify update
        $post->refresh();
        $this->assertEquals('Updated content with more details', $post->content);
        $this->assertEquals('circles', $post->visibility);
        $this->assertContains($this->circle->id, $post->circle_ids);

        // Delete post
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(200);

        // Verify soft deletion
        $this->assertSoftDeleted('posts', ['id' => $post->id]);

        // Verify post no longer appears in timeline
        $response = $this->actingAs($this->circleUser)
            ->getJson('/api/timeline');

        $response->assertStatus(200);
        $posts = $response->json('data.posts.data');
        $postIds = collect($posts)->pluck('id')->toArray();
        $this->assertNotContains($post->id, $postIds);
    }

    public function test_engagement_removal_and_modification_flow()
    {
        $post = Post::factory()->create([
            'user_id' => $this->connectedUser->id,
            'visibility' => 'public',
        ]);

        // Like post
        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$post->id}/engage", [
                'type' => 'like',
            ]);

        $response->assertStatus(200);

        // Verify like exists
        $this->assertDatabaseHas('post_engagements', [
            'post_id' => $post->id,
            'user_id' => $this->user->id,
            'type' => 'like',
        ]);

        // Remove like
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/posts/{$post->id}/engage", [
                'type' => 'like',
            ]);

        $response->assertStatus(200);

        // Verify like removed
        $this->assertDatabaseMissing('post_engagements', [
            'post_id' => $post->id,
            'user_id' => $this->user->id,
            'type' => 'like',
        ]);

        // Change to different engagement type
        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$post->id}/engage", [
                'type' => 'love',
            ]);

        $response->assertStatus(200);

        // Verify new engagement
        $this->assertDatabaseHas('post_engagements', [
            'post_id' => $post->id,
            'user_id' => $this->user->id,
            'type' => 'love',
        ]);
    }

    public function test_timeline_pagination_and_performance()
    {
        // Create many posts for pagination testing
        Post::factory()->count(50)->create([
            'visibility' => 'public',
            'created_at' => now()->subMinutes(rand(1, 1000)),
        ]);

        // Test first page
        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline?per_page=10&page=1');

        $response->assertStatus(200);

        $data = $response->json('data.posts');
        $this->assertCount(10, $data['data']);
        $this->assertEquals(1, $data['meta']['current_page']);
        $this->assertGreaterThan(1, $data['meta']['last_page']);

        // Test second page
        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline?per_page=10&page=2');

        $response->assertStatus(200);

        $data = $response->json('data.posts');
        $this->assertCount(10, $data['data']);
        $this->assertEquals(2, $data['meta']['current_page']);

        // Verify posts are ordered by creation date (newest first)
        $posts = $data['data'];
        for ($i = 0; $i < count($posts) - 1; $i++) {
            $currentPostTime = strtotime($posts[$i]['created_at']);
            $nextPostTime = strtotime($posts[$i + 1]['created_at']);
            $this->assertGreaterThanOrEqual($nextPostTime, $currentPostTime);
        }
    }

    public function test_real_time_timeline_updates()
    {
        Event::fake();

        // Get initial timeline
        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline');

        $response->assertStatus(200);
        $initialCount = count($response->json('data.posts.data'));

        // Create new post
        $newPost = Post::factory()->create([
            'user_id' => $this->connectedUser->id,
            'visibility' => 'public',
            'created_at' => now(),
        ]);

        // Verify event was dispatched
        Event::assertDispatched(PostCreated::class, function ($event) use ($newPost) {
            return $event->post->id === $newPost->id;
        });

        // Get updated timeline
        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline');

        $response->assertStatus(200);
        $updatedPosts = $response->json('data.posts.data');

        // Should have one more post
        $this->assertCount($initialCount + 1, $updatedPosts);

        // New post should be first (most recent)
        $this->assertEquals($newPost->id, $updatedPosts[0]['id']);
    }
}
