<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\PostEngagement;
use App\Services\PostEngagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PostEngagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected User $otherUser;
    protected Post $post;
    protected PostEngagementService $engagementService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->post = Post::factory()->create(['user_id' => $this->otherUser->id]);
        $this->engagementService = app(PostEngagementService::class);
    }

    /** @test */
    public function user_can_react_to_post()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$this->post->id}/react", [
                'type' => 'like'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Reaction added successfully'
            ]);

        $this->assertDatabaseHas('post_engagements', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'type' => 'like'
        ]);
    }

    /** @test */
    public function user_can_remove_reaction_from_post()
    {
        // First add a reaction
        $this->engagementService->addReaction($this->post, $this->user, 'like');

        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$this->post->id}/unreact", [
                'type' => 'like'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $this->assertDatabaseMissing('post_engagements', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'type' => 'like'
        ]);
    }

    /** @test */
    public function user_can_comment_on_post()
    {
        $commentContent = 'This is a test comment';

        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$this->post->id}/comment", [
                'content' => $commentContent
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Comment added successfully'
            ]);

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'content' => $commentContent,
            'parent_id' => null
        ]);
    }

    /** @test */
    public function user_can_reply_to_comment()
    {
        // Create a parent comment
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->otherUser->id
        ]);

        $replyContent = 'This is a reply';

        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$this->post->id}/comment", [
                'content' => $replyContent,
                'parent_id' => $parentComment->id
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'content' => $replyContent,
            'parent_id' => $parentComment->id
        ]);
    }

    /** @test */
    public function user_can_share_post()
    {
        $commentary = 'Great post!';

        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$this->post->id}/share", [
                'commentary' => $commentary
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Post shared successfully'
            ]);

        // Check that a share engagement was created
        $this->assertDatabaseHas('post_engagements', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'type' => 'share'
        ]);

        // Check that a new shared post was created
        $this->assertDatabaseHas('posts', [
            'user_id' => $this->user->id,
            'content' => $commentary,
            'post_type' => 'share'
        ]);
    }

    /** @test */
    public function user_can_bookmark_post()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$this->post->id}/bookmark");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'bookmarked' => true
            ]);

        $this->assertDatabaseHas('post_engagements', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'type' => 'bookmark'
        ]);
    }

    /** @test */
    public function user_can_remove_bookmark()
    {
        // First bookmark the post
        $this->engagementService->bookmarkPost($this->post, $this->user);

        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$this->post->id}/bookmark");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'bookmarked' => false
            ]);

        $this->assertDatabaseMissing('post_engagements', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'type' => 'bookmark'
        ]);
    }

    /** @test */
    public function can_get_post_engagement_stats()
    {
        // Add some engagements
        $this->engagementService->addReaction($this->post, $this->user, 'like');
        $this->engagementService->addReaction($this->post, $this->otherUser, 'love');
        $this->engagementService->addComment($this->post, $this->user, 'Test comment');

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$this->post->id}/stats");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'stats' => [
                    'like' => 1,
                    'love' => 1,
                    'comment' => 1
                ]
            ]);
    }

    /** @test */
    public function can_get_comments_for_post()
    {
        // Create some comments
        Comment::factory()->count(3)->create([
            'post_id' => $this->post->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$this->post->id}/comments");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'comments' => [
                    'data' => [
                        '*' => [
                            'id',
                            'content',
                            'user' => [
                                'id',
                                'name',
                                'username'
                            ],
                            'created_at'
                        ]
                    ]
                ]
            ]);
    }

    /** @test */
    public function comment_mentions_are_parsed_correctly()
    {
        $mentionedUser = User::factory()->create(['username' => 'testuser']);
        $content = 'Hello @testuser, how are you?';

        $comment = $this->engagementService->addComment($this->post, $this->user, $content);

        $this->assertEquals(['testuser'], $comment->mentions);
    }

    /** @test */
    public function can_search_users_for_mentions()
    {
        $user1 = User::factory()->create(['username' => 'johndoe', 'name' => 'John Doe']);
        $user2 = User::factory()->create(['username' => 'janedoe', 'name' => 'Jane Doe']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/posts/mentions/search?query=doe');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonCount(2, 'users');
    }

    /** @test */
    public function reaction_validation_works()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$this->post->id}/react", [
                'type' => 'invalid_reaction'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function comment_validation_works()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/posts/{$this->post->id}/comment", [
                'content' => '' // Empty content
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    /** @test */
    public function user_cannot_react_to_nonexistent_post()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/posts/99999/react', [
                'type' => 'like'
            ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function unauthenticated_user_cannot_engage_with_posts()
    {
        $response = $this->postJson("/api/posts/{$this->post->id}/react", [
            'type' => 'like'
        ]);

        $response->assertStatus(401);
    }
}