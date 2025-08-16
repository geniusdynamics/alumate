<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostEngagement;
use App\Models\User;
use App\Notifications\PostCommentNotification;
use App\Notifications\PostMentionNotification;
use App\Notifications\PostReactionNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PostEngagementService
{
    /**
     * Add or update a user's reaction to a post.
     */
    public function addReaction(Post $post, User $user, string $type): PostEngagement
    {
        // Remove existing reaction of different type
        PostEngagement::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->where('type', '!=', $type)
            ->whereIn('type', ['like', 'love', 'celebrate', 'support', 'insightful'])
            ->delete();

        // Create or update reaction
        $engagement = PostEngagement::updateOrCreate(
            [
                'post_id' => $post->id,
                'user_id' => $user->id,
                'type' => $type,
            ],
            [
                'metadata' => ['created_at' => now()],
            ]
        );

        // Clear engagement stats cache
        $this->clearEngagementCache($post);

        // Send notification to post author (if not self-reaction)
        if ($post->user_id !== $user->id) {
            $post->user->notify(new PostReactionNotification($post, $user, $type));
        }

        // Broadcast real-time update
        broadcast(new \App\Events\PostEngagementUpdated($post, $this->getEngagementStats($post)));

        return $engagement;
    }

    /**
     * Remove a user's reaction from a post.
     */
    public function removeReaction(Post $post, User $user, string $type): bool
    {
        $deleted = PostEngagement::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->delete();

        if ($deleted) {
            // Clear engagement stats cache
            $this->clearEngagementCache($post);

            // Broadcast real-time update
            broadcast(new \App\Events\PostEngagementUpdated($post, $this->getEngagementStats($post)));
        }

        return $deleted > 0;
    }

    /**
     * Add a comment to a post.
     */
    public function addComment(Post $post, User $user, string $content, ?int $parentId = null): Comment
    {
        // Validate parent comment exists and belongs to the same post
        if ($parentId) {
            $parentComment = Comment::where('id', $parentId)
                ->where('post_id', $post->id)
                ->firstOrFail();
        }

        // Parse mentions from content
        $mentions = $this->parseMentions($content);

        $comment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'content' => $content,
            'mentions' => $mentions,
            'metadata' => ['created_at' => now()],
        ]);

        // Clear engagement stats cache
        $this->clearEngagementCache($post);

        // Send notifications
        $this->sendCommentNotifications($comment, $post, $user, $mentions);

        // Broadcast real-time update
        broadcast(new \App\Events\PostCommentAdded($post, $comment->load('user')));

        return $comment;
    }

    /**
     * Share a post with optional commentary.
     */
    public function sharePost(Post $post, User $user, ?string $commentary = null): Post
    {
        // Create a new post that references the original
        $sharedPost = Post::create([
            'user_id' => $user->id,
            'content' => $commentary,
            'post_type' => 'share',
            'visibility' => 'public', // Default visibility for shares
            'metadata' => [
                'shared_post_id' => $post->id,
                'shared_at' => now(),
            ],
        ]);

        // Record the share engagement
        PostEngagement::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'type' => 'share',
            'metadata' => [
                'shared_post_id' => $sharedPost->id,
                'commentary' => $commentary,
            ],
        ]);

        // Clear engagement stats cache
        $this->clearEngagementCache($post);

        // Send notification to original post author
        if ($post->user_id !== $user->id) {
            $post->user->notify(new PostReactionNotification($post, $user, 'share'));
        }

        // Broadcast real-time update
        broadcast(new \App\Events\PostEngagementUpdated($post, $this->getEngagementStats($post)));

        return $sharedPost;
    }

    /**
     * Bookmark a post for a user.
     */
    public function bookmarkPost(Post $post, User $user): PostEngagement
    {
        $engagement = PostEngagement::updateOrCreate(
            [
                'post_id' => $post->id,
                'user_id' => $user->id,
                'type' => 'bookmark',
            ],
            [
                'metadata' => ['bookmarked_at' => now()],
            ]
        );

        return $engagement;
    }

    /**
     * Remove bookmark from a post.
     */
    public function removeBookmark(Post $post, User $user): bool
    {
        return PostEngagement::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->where('type', 'bookmark')
            ->delete() > 0;
    }

    /**
     * Get engagement statistics for a post.
     */
    public function getEngagementStats(Post $post): array
    {
        $cacheKey = "post_engagement_stats_{$post->id}";

        return Cache::remember($cacheKey, 300, function () use ($post) {
            $stats = PostEngagement::where('post_id', $post->id)
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();

            // Get comment count separately
            $commentCount = Comment::where('post_id', $post->id)->count();
            $stats['comment'] = $commentCount;

            // Ensure all reaction types are present
            $reactionTypes = ['like', 'love', 'celebrate', 'support', 'insightful', 'share', 'bookmark'];
            foreach ($reactionTypes as $type) {
                if (! isset($stats[$type])) {
                    $stats[$type] = 0;
                }
            }

            return $stats;
        });
    }

    /**
     * Get user's engagement with a post.
     */
    public function getUserEngagement(Post $post, User $user): array
    {
        $engagements = PostEngagement::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->pluck('type')
            ->toArray();

        return [
            'reactions' => array_intersect($engagements, ['like', 'love', 'celebrate', 'support', 'insightful']),
            'shared' => in_array('share', $engagements),
            'bookmarked' => in_array('bookmark', $engagements),
            'commented' => Comment::where('post_id', $post->id)->where('user_id', $user->id)->exists(),
        ];
    }

    /**
     * Get users who reacted to a post with a specific reaction type.
     */
    public function getReactionUsers(Post $post, string $type, int $limit = 10): array
    {
        return PostEngagement::where('post_id', $post->id)
            ->where('type', $type)
            ->with('user:id,name,username,avatar_url')
            ->latest()
            ->limit($limit)
            ->get()
            ->pluck('user')
            ->toArray();
    }

    /**
     * Parse mentions from content.
     */
    private function parseMentions(string $content): array
    {
        preg_match_all('/@(\w+)/', $content, $matches);
        $usernames = $matches[1] ?? [];

        // Validate that mentioned users exist
        $validUsernames = User::whereIn('username', $usernames)
            ->pluck('username')
            ->toArray();

        return $validUsernames;
    }

    /**
     * Send notifications for comments.
     */
    private function sendCommentNotifications(Comment $comment, Post $post, User $commenter, array $mentions): void
    {
        // Notify post author (if not the commenter)
        if ($post->user_id !== $commenter->id) {
            $post->user->notify(new PostCommentNotification($post, $comment, $commenter));
        }

        // Notify mentioned users
        if (! empty($mentions)) {
            $mentionedUsers = User::whereIn('username', $mentions)
                ->where('id', '!=', $commenter->id)
                ->get();

            foreach ($mentionedUsers as $mentionedUser) {
                $mentionedUser->notify(new PostMentionNotification($post, $comment, $commenter));
            }
        }

        // Notify parent comment author (if replying and not the commenter or post author)
        if ($comment->parent_id) {
            $parentComment = Comment::find($comment->parent_id);
            if ($parentComment &&
                $parentComment->user_id !== $commenter->id &&
                $parentComment->user_id !== $post->user_id) {
                $parentComment->user->notify(new PostCommentNotification($post, $comment, $commenter));
            }
        }
    }

    /**
     * Clear engagement cache for a post.
     */
    private function clearEngagementCache(Post $post): void
    {
        Cache::forget("post_engagement_stats_{$post->id}");
    }

    /**
     * Search users for mentions.
     */
    public function searchUsersForMention(string $query, int $limit = 10): array
    {
        return User::where('username', 'LIKE', "%{$query}%")
            ->orWhere('name', 'LIKE', "%{$query}%")
            ->select('id', 'username', 'name', 'avatar_url')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
