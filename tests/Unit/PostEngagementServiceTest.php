<?php

namespace Tests\Unit;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostEngagement;
use App\Models\User;
use App\Services\PostEngagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostEngagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PostEngagementService $service;

    protected User $user;

    protected User $otherUser;

    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PostEngagementService;
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->post = Post::factory()->create(['user_id' => $this->otherUser->id]);
    }

    /** @test */
    public function can_add_reaction_to_post()
    {
        $engagement = $this->service->addReaction($this->post, $this->user, 'like');

        $this->assertInstanceOf(PostEngagement::class, $engagement);
        $this->assertEquals($this->post->id, $engagement->post_id);
        $this->assertEquals($this->user->id, $engagement->user_id);
        $this->assertEquals('like', $engagement->type);
    }

    /** @test */
    public function adding_different_reaction_replaces_existing_one()
    {
        // Add initial reaction
        $this->service->addReaction($this->post, $this->user, 'like');

        // Add different reaction
        $this->service->addReaction($this->post, $this->user, 'love');

        // Should only have the love reaction
        $this->assertDatabaseMissing('post_engagements', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'type' => 'like',
        ]);

        $this->assertDatabaseHas('post_engagements', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'type' => 'love',
        ]);
    }

    /** @test */
    public function can_remove_reaction_from_post()
    {
        // Add reaction first
        $this->service->addReaction($this->post, $this->user, 'like');

        // Remove reaction
        $result = $this->service->removeReaction($this->post, $this->user, 'like');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('post_engagements', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'type' => 'like',
        ]);
    }

    /** @test */
    public function can_add_comment_to_post()
    {
        $content = 'This is a test comment';
        $comment = $this->service->addComment($this->post, $this->user, $content);

        $this->assertInstanceOf(Comment::class, $comment);
        $this->assertEquals($this->post->id, $comment->post_id);
        $this->assertEquals($this->user->id, $comment->user_id);
        $this->assertEquals($content, $comment->content);
        $this->assertNull($comment->parent_id);
    }

    /** @test */
    public function can_add_reply_to_comment()
    {
        // Create parent comment
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->otherUser->id,
        ]);

        $replyContent = 'This is a reply';
        $reply = $this->service->addComment($this->post, $this->user, $replyContent, $parentComment->id);

        $this->assertEquals($parentComment->id, $reply->parent_id);
        $this->assertEquals($replyContent, $reply->content);
    }

    /** @test */
    public function can_share_post()
    {
        $commentary = 'Great post!';
        $sharedPost = $this->service->sharePost($this->post, $this->user, $commentary);

        $this->assertInstanceOf(Post::class, $sharedPost);
        $this->assertEquals($this->user->id, $sharedPost->user_id);
        $this->assertEquals($commentary, $sharedPost->content);
        $this->assertEquals('share', $sharedPost->post_type);

        // Check that share engagement was created
        $this->assertDatabaseHas('post_engagements', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'type' => 'share',
        ]);
    }

    /** @test */
    public function can_bookmark_post()
    {
        $engagement = $this->service->bookmarkPost($this->post, $this->user);

        $this->assertInstanceOf(PostEngagement::class, $engagement);
        $this->assertEquals('bookmark', $engagement->type);
    }

    /** @test */
    public function can_remove_bookmark()
    {
        // Add bookmark first
        $this->service->bookmarkPost($this->post, $this->user);

        // Remove bookmark
        $result = $this->service->removeBookmark($this->post, $this->user);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('post_engagements', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'type' => 'bookmark',
        ]);
    }

    /** @test */
    public function can_get_engagement_stats()
    {
        // Add various engagements
        $this->service->addReaction($this->post, $this->user, 'like');
        $this->service->addReaction($this->post, $this->otherUser, 'love');
        $this->service->addComment($this->post, $this->user, 'Test comment');
        $this->service->bookmarkPost($this->post, $this->user);

        $stats = $this->service->getEngagementStats($this->post);

        $this->assertEquals(1, $stats['like']);
        $this->assertEquals(1, $stats['love']);
        $this->assertEquals(1, $stats['comment']);
        $this->assertEquals(1, $stats['bookmark']);
        $this->assertEquals(0, $stats['celebrate']); // Should be 0 for unused reactions
    }

    /** @test */
    public function can_get_user_engagement()
    {
        // Add engagements
        $this->service->addReaction($this->post, $this->user, 'like');
        $this->service->bookmarkPost($this->post, $this->user);
        $this->service->addComment($this->post, $this->user, 'Test comment');

        $userEngagement = $this->service->getUserEngagement($this->post, $this->user);

        $this->assertContains('like', $userEngagement['reactions']);
        $this->assertTrue($userEngagement['bookmarked']);
        $this->assertTrue($userEngagement['commented']);
        $this->assertFalse($userEngagement['shared']);
    }

    /** @test */
    public function can_get_reaction_users()
    {
        // Add reactions from multiple users
        $this->service->addReaction($this->post, $this->user, 'like');
        $this->service->addReaction($this->post, $this->otherUser, 'like');

        $users = $this->service->getReactionUsers($this->post, 'like');

        $this->assertCount(2, $users);
        $this->assertEquals($this->user->id, $users[1]['id']); // Latest first
        $this->assertEquals($this->otherUser->id, $users[0]['id']);
    }

    /** @test */
    public function mentions_are_parsed_correctly()
    {
        $mentionedUser = User::factory()->create(['username' => 'testuser']);
        $content = 'Hello @testuser and @nonexistent, how are you?';

        $comment = $this->service->addComment($this->post, $this->user, $content);

        // Should only include existing users
        $this->assertEquals(['testuser'], $comment->mentions);
    }

    /** @test */
    public function can_search_users_for_mention()
    {
        $user1 = User::factory()->create(['username' => 'johndoe', 'name' => 'John Doe']);
        $user2 = User::factory()->create(['username' => 'janedoe', 'name' => 'Jane Doe']);
        $user3 = User::factory()->create(['username' => 'bobsmith', 'name' => 'Bob Smith']);

        $results = $this->service->searchUsersForMention('doe');

        $this->assertCount(2, $results);

        $usernames = array_column($results, 'username');
        $this->assertContains('johndoe', $usernames);
        $this->assertContains('janedoe', $usernames);
        $this->assertNotContains('bobsmith', $usernames);
    }

    /** @test */
    public function comment_depth_level_is_calculated_correctly()
    {
        // Create a nested comment thread
        $level1 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => null,
        ]);

        $level2 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => $level1->id,
        ]);

        $level3 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_id' => $level2->id,
        ]);

        $this->assertEquals(0, $level1->getDepthLevel());
        $this->assertEquals(1, $level2->getDepthLevel());
        $this->assertEquals(2, $level3->getDepthLevel());
    }

    /** @test */
    public function comment_relationships_work_correctly()
    {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $childComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->otherUser->id,
            'parent_id' => $parentComment->id,
        ]);

        // Test parent relationship
        $this->assertEquals($parentComment->id, $childComment->parent->id);

        // Test child relationship
        $this->assertTrue($parentComment->replies->contains($childComment));

        // Test level checks
        $this->assertTrue($parentComment->isTopLevel());
        $this->assertFalse($parentComment->isReply());
        $this->assertFalse($childComment->isTopLevel());
        $this->assertTrue($childComment->isReply());
    }
}
