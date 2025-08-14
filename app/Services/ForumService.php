<?php

namespace App\Services;

use App\Models\Forum;
use App\Models\ForumTopic;
use App\Models\ForumPost;
use App\Models\ForumTag;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ForumService
{
    /**
     * Get forums accessible by user with statistics.
     */
    public function getAccessibleForums(User $user, ?int $groupId = null): Collection
    {
        $query = Forum::with(['group'])
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($groupId) {
            $query->forGroup($groupId);
        } else {
            // Filter by user access permissions
            $query->where(function ($q) use ($user) {
                $q->where('visibility', 'public')
                  ->orWhere(function ($subQ) use ($user) {
                      $subQ->where('visibility', 'group_only')
                           ->whereHas('group.members', function ($memberQ) use ($user) {
                               $memberQ->where('user_id', $user->id);
                           });
                  });
            });
        }

        return $query->get();
    }

    /**
     * Search topics across forums.
     */
    public function searchTopics(string $query, User $user, array $filters = []): Collection
    {
        $topicsQuery = ForumTopic::with(['forum', 'user', 'tags'])
            ->whereHas('forum', function ($q) use ($user) {
                $q->active()->where(function ($subQ) use ($user) {
                    $subQ->where('visibility', 'public')
                         ->orWhere(function ($groupQ) use ($user) {
                             $groupQ->where('visibility', 'group_only')
                                    ->whereHas('group.members', function ($memberQ) use ($user) {
                                        $memberQ->where('user_id', $user->id);
                                    });
                         });
                });
            })
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%");
            });

        // Apply filters
        if (!empty($filters['forum_id'])) {
            $topicsQuery->where('forum_id', $filters['forum_id']);
        }

        if (!empty($filters['tag'])) {
            $topicsQuery->withTag($filters['tag']);
        }

        if (!empty($filters['user_id'])) {
            $topicsQuery->where('user_id', $filters['user_id']);
        }

        // Sorting
        $sortBy = $filters['sort'] ?? 'relevance';
        switch ($sortBy) {
            case 'newest':
                $topicsQuery->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $topicsQuery->orderBy('created_at', 'asc');
                break;
            case 'popular':
                $topicsQuery->orderBy('views_count', 'desc');
                break;
            case 'activity':
                $topicsQuery->orderBy('last_post_at', 'desc');
                break;
            case 'relevance':
            default:
                // Simple relevance scoring based on title vs content matches
                $topicsQuery->orderByRaw("
                    CASE 
                        WHEN title LIKE ? THEN 2
                        WHEN content LIKE ? THEN 1
                        ELSE 0
                    END DESC
                ", ["%{$query}%", "%{$query}%"])
                ->orderBy('views_count', 'desc');
                break;
        }

        return $topicsQuery->limit($filters['limit'] ?? 50)->get();
    }

    /**
     * Get popular tags with usage statistics.
     */
    public function getPopularTags(int $limit = 20): Collection
    {
        return ForumTag::popular($limit)
            ->get()
            ->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'color' => $tag->color,
                    'usage_count' => $tag->usage_count,
                    'is_featured' => $tag->is_featured,
                ];
            });
    }

    /**
     * Get forum statistics for admin dashboard.
     */
    public function getForumStatistics(): array
    {
        return [
            'total_forums' => Forum::active()->count(),
            'total_topics' => ForumTopic::active()->count(),
            'total_posts' => ForumPost::approved()->count(),
            'total_tags' => ForumTag::count(),
            'active_users_today' => $this->getActiveUsersCount(1),
            'active_users_week' => $this->getActiveUsersCount(7),
            'popular_forums' => $this->getPopularForums(5),
            'recent_activity' => $this->getRecentActivity(10),
        ];
    }

    /**
     * Get users who posted in the last N days.
     */
    private function getActiveUsersCount(int $days): int
    {
        return User::whereHas('forumPosts', function ($query) use ($days) {
            $query->where('created_at', '>=', now()->subDays($days));
        })->count();
    }

    /**
     * Get most active forums by post count.
     */
    private function getPopularForums(int $limit): Collection
    {
        return Forum::active()
            ->orderBy('posts_count', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'posts_count', 'topics_count']);
    }

    /**
     * Get recent forum activity.
     */
    private function getRecentActivity(int $limit): Collection
    {
        return ForumPost::with(['user', 'topic.forum'])
            ->approved()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($post) {
                return [
                    'type' => 'post',
                    'user' => [
                        'id' => $post->user->id,
                        'name' => $post->user->name,
                        'avatar_url' => $post->user->avatar_url,
                    ],
                    'topic' => [
                        'id' => $post->topic->id,
                        'title' => $post->topic->title,
                        'slug' => $post->topic->slug,
                    ],
                    'forum' => [
                        'id' => $post->topic->forum->id,
                        'name' => $post->topic->forum->name,
                        'slug' => $post->topic->forum->slug,
                    ],
                    'created_at' => $post->created_at,
                ];
            });
    }

    /**
     * Moderate content (approve/reject posts and topics).
     */
    public function moderateContent(string $type, int $id, string $action, User $moderator): bool
    {
        $model = match ($type) {
            'topic' => ForumTopic::find($id),
            'post' => ForumPost::find($id),
            default => null,
        };

        if (!$model) {
            return false;
        }

        switch ($action) {
            case 'approve':
                $model->update([
                    'is_approved' => true,
                    'approved_by' => $moderator->id,
                    'approved_at' => now(),
                ]);
                return true;

            case 'reject':
                $model->update([
                    'is_approved' => false,
                    'approved_by' => null,
                    'approved_at' => null,
                ]);
                return true;

            case 'delete':
                $model->delete();
                return true;

            default:
                return false;
        }
    }

    /**
     * Get content pending moderation.
     */
    public function getPendingModeration(User $moderator): array
    {
        // Only get content from forums the moderator can access
        $accessibleForumIds = $this->getAccessibleForums($moderator)->pluck('id');

        $pendingTopics = ForumTopic::with(['user', 'forum'])
            ->whereIn('forum_id', $accessibleForumIds)
            ->where('is_approved', false)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $pendingPosts = ForumPost::with(['user', 'topic.forum'])
            ->whereHas('topic', function ($query) use ($accessibleForumIds) {
                $query->whereIn('forum_id', $accessibleForumIds);
            })
            ->where('is_approved', false)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return [
            'topics' => $pendingTopics,
            'posts' => $pendingPosts,
        ];
    }
}