<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimelineService
{
    private const CACHE_PREFIX = 'timeline:user:';
    private const ACTIVE_USER_TTL = 900; // 15 minutes
    private const INACTIVE_USER_TTL = 3600; // 1 hour
    private const ACTIVE_THRESHOLD_HOURS = 24; // Consider user active if last activity within 24 hours

    /**
     * Generate personalized timeline for a user with cursor-based pagination.
     */
    public function generateTimelineForUser(User $user, int $limit = 20, ?string $cursor = null): array
    {
        $cacheKey = $this->getTimelineCacheKey($user->id, $cursor);
        
        // Try to get from cache first
        $cachedTimeline = Cache::get($cacheKey);
        if ($cachedTimeline) {
            return $cachedTimeline;
        }

        // Generate fresh timeline
        $timeline = $this->buildTimelineQuery($user, $limit, $cursor);
        
        // Cache the timeline
        $this->cacheTimeline($user, $timeline);
        
        return $timeline;
    }

    /**
     * Get posts from user's circles with cursor-based pagination.
     */
    public function getCirclePosts(User $user, int $limit = 20, ?string $cursor = null): Collection
    {
        $circleIds = $user->circles()->pluck('circles.id')->toArray();
        
        if (empty($circleIds)) {
            return collect();
        }

        $query = Post::with(['user', 'engagements'])
            ->where('visibility', 'circles')
            ->where(function ($q) use ($circleIds) {
                foreach ($circleIds as $circleId) {
                    $q->orWhereJsonContains('circle_ids', $circleId);
                }
            });

        return $this->applyCursorPagination($query, $limit, $cursor);
    }

    /**
     * Get posts from user's groups with cursor-based pagination.
     */
    public function getGroupPosts(User $user, int $limit = 20, ?string $cursor = null): Collection
    {
        $groupIds = $user->groups()->pluck('groups.id')->toArray();
        
        if (empty($groupIds)) {
            return collect();
        }

        $query = Post::with(['user', 'engagements'])
            ->where('visibility', 'groups')
            ->where(function ($q) use ($groupIds) {
                foreach ($groupIds as $groupId) {
                    $q->orWhereJsonContains('group_ids', $groupId);
                }
            });

        return $this->applyCursorPagination($query, $limit, $cursor);
    }

    /**
     * Calculate relevance score for post ranking.
     */
    public function scorePost(Post $post, User $user): float
    {
        $score = 0.0;

        // Base score from post age (newer posts get higher score)
        $ageInHours = $post->created_at->diffInHours(now());
        $ageScore = max(0, 100 - ($ageInHours * 2)); // Decrease by 2 points per hour
        $score += $ageScore * 0.3;

        // Connection score (posts from connections get higher score)
        if ($this->isFromConnection($post, $user)) {
            $score += 50;
        }

        // Engagement score (posts with more engagement get higher score)
        $engagementCount = $post->engagements()->count();
        $engagementScore = min(50, $engagementCount * 2); // Max 50 points, 2 points per engagement
        $score += $engagementScore * 0.4;

        // Circle/Group relevance score
        $relevanceScore = $this->calculateRelevanceScore($post, $user);
        $score += $relevanceScore * 0.3;

        // User interaction history (if user frequently interacts with this author)
        $interactionScore = $this->calculateInteractionScore($post, $user);
        $score += $interactionScore * 0.2;

        return round($score, 2);
    }

    /**
     * Cache timeline for a user with appropriate TTL.
     */
    public function cacheTimeline(User $user, array $timeline): void
    {
        $ttl = $this->getTtlForUser($user);
        $cacheKey = $this->getTimelineCacheKey($user->id);
        
        Cache::put($cacheKey, $timeline, $ttl);
    }

    /**
     * Invalidate timeline cache for a user.
     */
    public function invalidateTimelineCache(User $user): void
    {
        $pattern = self::CACHE_PREFIX . $user->id . ':*';
        
        // Get all cache keys for this user
        $keys = Cache::getRedis()->keys($pattern);
        
        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }
    }

    /**
     * Invalidate timeline cache for users who should see a new post.
     */
    public function invalidateTimelineCacheForPost(Post $post): void
    {
        $affectedUserIds = $this->getAffectedUserIds($post);
        
        foreach ($affectedUserIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $this->invalidateTimelineCache($user);
            }
        }
    }

    /**
     * Build the main timeline query with scoring and pagination.
     */
    private function buildTimelineQuery(User $user, int $limit, ?string $cursor): array
    {
        // Get posts from different sources
        $circlePosts = $this->getCirclePosts($user, $limit * 2, $cursor);
        $groupPosts = $this->getGroupPosts($user, $limit * 2, $cursor);
        $publicPosts = $this->getPublicPosts($user, $limit, $cursor);
        $connectionPosts = $this->getConnectionPosts($user, $limit, $cursor);

        // Combine all posts
        $allPosts = collect()
            ->merge($circlePosts)
            ->merge($groupPosts)
            ->merge($publicPosts)
            ->merge($connectionPosts)
            ->unique('id');

        // Score and sort posts
        $scoredPosts = $allPosts->map(function ($post) use ($user) {
            return [
                'post' => $post,
                'score' => $this->scorePost($post, $user),
            ];
        })->sortByDesc('score');

        // Take the top posts
        $topPosts = $scoredPosts->take($limit);

        // Prepare pagination info
        $posts = $topPosts->pluck('post');
        $nextCursor = $posts->isNotEmpty() ? $this->generateCursor($posts->last()) : null;

        return [
            'posts' => $posts->values()->toArray(),
            'next_cursor' => $nextCursor,
            'has_more' => $scoredPosts->count() > $limit,
        ];
    }

    /**
     * Get public posts for timeline.
     */
    private function getPublicPosts(User $user, int $limit, ?string $cursor): Collection
    {
        $query = Post::with(['user', 'engagements'])
            ->where('visibility', 'public');

        return $this->applyCursorPagination($query, $limit, $cursor);
    }

    /**
     * Get posts from user's connections.
     */
    private function getConnectionPosts(User $user, int $limit, ?string $cursor): Collection
    {
        $connectionIds = $user->getAcceptedConnections()->pluck('users.id')->toArray();
        
        if (empty($connectionIds)) {
            return collect();
        }

        $query = Post::with(['user', 'engagements'])
            ->whereIn('user_id', $connectionIds)
            ->where(function ($q) {
                $q->where('visibility', 'public')
                  ->orWhere('visibility', 'circles')
                  ->orWhere('visibility', 'groups');
            });

        return $this->applyCursorPagination($query, $limit, $cursor);
    }

    /**
     * Apply cursor-based pagination to a query.
     */
    private function applyCursorPagination($query, int $limit, ?string $cursor): Collection
    {
        if ($cursor) {
            $decodedCursor = $this->decodeCursor($cursor);
            $query->where('created_at', '<', $decodedCursor['created_at'])
                  ->orWhere(function ($q) use ($decodedCursor) {
                      $q->where('created_at', '=', $decodedCursor['created_at'])
                        ->where('id', '<', $decodedCursor['id']);
                  });
        }

        return $query->orderBy('created_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Generate cursor for pagination.
     */
    private function generateCursor(Post $post): string
    {
        return base64_encode(json_encode([
            'id' => $post->id,
            'created_at' => $post->created_at->toISOString(),
        ]));
    }

    /**
     * Decode cursor for pagination.
     */
    private function decodeCursor(string $cursor): array
    {
        return json_decode(base64_decode($cursor), true);
    }

    /**
     * Check if post is from a user's connection.
     */
    private function isFromConnection(Post $post, User $user): bool
    {
        return $user->getAcceptedConnections()
                   ->where('users.id', $post->user_id)
                   ->exists();
    }

    /**
     * Calculate relevance score based on circles and groups.
     */
    private function calculateRelevanceScore(Post $post, User $user): float
    {
        $score = 0.0;

        // Check circle relevance
        if ($post->circle_ids) {
            $userCircleIds = $user->circles()->pluck('circles.id')->toArray();
            $sharedCircles = array_intersect($post->circle_ids, $userCircleIds);
            $score += count($sharedCircles) * 10; // 10 points per shared circle
        }

        // Check group relevance
        if ($post->group_ids) {
            $userGroupIds = $user->groups()->pluck('groups.id')->toArray();
            $sharedGroups = array_intersect($post->group_ids, $userGroupIds);
            $score += count($sharedGroups) * 15; // 15 points per shared group
        }

        return $score;
    }

    /**
     * Calculate interaction score based on user's history with post author.
     */
    private function calculateInteractionScore(Post $post, User $user): float
    {
        // Count recent interactions with this author
        $recentInteractions = DB::table('post_engagements')
            ->join('posts', 'post_engagements.post_id', '=', 'posts.id')
            ->where('post_engagements.user_id', $user->id)
            ->where('posts.user_id', $post->user_id)
            ->where('post_engagements.created_at', '>=', now()->subDays(30))
            ->count();

        return min(20, $recentInteractions * 2); // Max 20 points, 2 points per interaction
    }

    /**
     * Get cache key for timeline.
     */
    private function getTimelineCacheKey(int $userId, ?string $cursor = null): string
    {
        $key = self::CACHE_PREFIX . $userId;
        if ($cursor) {
            $key .= ':' . md5($cursor);
        }
        return $key;
    }

    /**
     * Get TTL for user based on activity.
     */
    private function getTtlForUser(User $user): int
    {
        $isActive = $user->last_activity_at && 
                   $user->last_activity_at->diffInHours(now()) < self::ACTIVE_THRESHOLD_HOURS;

        return $isActive ? self::ACTIVE_USER_TTL : self::INACTIVE_USER_TTL;
    }

    /**
     * Get user IDs that should see a post in their timeline.
     */
    private function getAffectedUserIds(Post $post): array
    {
        $userIds = [];

        // Add users from circles
        if ($post->circle_ids) {
            $circleUserIds = DB::table('circle_memberships')
                ->whereIn('circle_id', $post->circle_ids)
                ->where('status', 'active')
                ->pluck('user_id')
                ->toArray();
            $userIds = array_merge($userIds, $circleUserIds);
        }

        // Add users from groups
        if ($post->group_ids) {
            $groupUserIds = DB::table('group_memberships')
                ->whereIn('group_id', $post->group_ids)
                ->where('status', 'active')
                ->pluck('user_id')
                ->toArray();
            $userIds = array_merge($userIds, $groupUserIds);
        }

        // Add connections for public posts
        if ($post->visibility === 'public') {
            $connectionIds = DB::table('connections')
                ->where(function ($q) use ($post) {
                    $q->where('user_id', $post->user_id)
                      ->orWhere('connected_user_id', $post->user_id);
                })
                ->where('status', 'accepted')
                ->get()
                ->map(function ($connection) use ($post) {
                    return $connection->user_id === $post->user_id 
                        ? $connection->connected_user_id 
                        : $connection->user_id;
                })
                ->toArray();
            $userIds = array_merge($userIds, $connectionIds);
        }

        return array_unique($userIds);
    }
}