<?php

namespace App\Services\Federation;

use App\Models\Circle;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;

/**
 * Maps internal platform entities to ActivityPub objects
 * Prepares for future ActivityPub federation integration
 */
class ActivityPubMapper
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('app.url');
    }

    /**
     * Convert a post to ActivityPub Note object
     */
    public function postToActivityPubObject(Post $post): array
    {
        $object = [
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1',
                [
                    'alumni' => 'https://alumni-platform.org/ns#',
                ],
            ],
            'type' => $this->getActivityPubType($post),
            'id' => $this->getPostUrl($post),
            'attributedTo' => $this->getUserUrl($post->user),
            'content' => $post->content,
            'published' => $post->created_at->toISOString(),
            'to' => $this->getPostAudience($post),
            'cc' => $this->getPostCcAudience($post),

            // Alumni platform extensions
            'alumni:postId' => $post->id,
            'alumni:postType' => $post->post_type,
            'alumni:visibility' => $post->visibility,
        ];

        // Add media attachments
        if ($post->media_urls && count($post->media_urls) > 0) {
            $object['attachment'] = $this->mapMediaAttachments($post->media_urls);
        }

        // Add circle/group context
        if ($post->circle_ids) {
            $object['alumni:circles'] = array_map(
                fn ($id) => $this->getCircleUrl($id),
                $post->circle_ids
            );
        }

        if ($post->group_ids) {
            $object['alumni:groups'] = array_map(
                fn ($id) => $this->getGroupUrl($id),
                $post->group_ids
            );
        }

        // Add tags and mentions if present
        $object['tag'] = $this->extractTags($post->content);

        return $object;
    }

    /**
     * Convert a user to ActivityPub Person object
     */
    public function userToActivityPubActor(User $user): array
    {
        return [
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1',
                [
                    'alumni' => 'https://alumni-platform.org/ns#',
                ],
            ],
            'type' => 'Person',
            'id' => $this->getUserUrl($user),
            'preferredUsername' => $this->generateUsername($user),
            'name' => $user->name,
            'summary' => $user->profile_data['bio'] ?? null,
            'url' => $this->getUserProfileUrl($user),
            'icon' => $user->avatar_url ? [
                'type' => 'Image',
                'mediaType' => 'image/jpeg',
                'url' => $user->avatar_url,
            ] : null,
            'inbox' => $this->getUserInboxUrl($user),
            'outbox' => $this->getUserOutboxUrl($user),
            'followers' => $this->getUserFollowersUrl($user),
            'following' => $this->getUserFollowingUrl($user),
            'publicKey' => [
                'id' => $this->getUserUrl($user).'#main-key',
                'owner' => $this->getUserUrl($user),
                'publicKeyPem' => $this->getUserPublicKey($user),
            ],

            // Alumni platform extensions
            'alumni:userId' => $user->id,
            'alumni:location' => $user->location,
            'alumni:website' => $user->website,
            'alumni:joinedAt' => $user->created_at->toISOString(),
        ];
    }

    /**
     * Convert a group to ActivityPub Group object
     */
    public function groupToActivityPubGroup(Group $group): array
    {
        return [
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                [
                    'alumni' => 'https://alumni-platform.org/ns#',
                ],
            ],
            'type' => 'Group',
            'id' => $this->getGroupUrl($group->id),
            'name' => $group->name,
            'summary' => $group->description,
            'url' => $this->getGroupProfileUrl($group),
            'icon' => $group->avatar_url ? [
                'type' => 'Image',
                'mediaType' => 'image/jpeg',
                'url' => $group->avatar_url,
            ] : null,
            'inbox' => $this->getGroupInboxUrl($group),
            'outbox' => $this->getGroupOutboxUrl($group),
            'members' => $this->getGroupMembersUrl($group),

            // Alumni platform extensions
            'alumni:groupId' => $group->id,
            'alumni:groupType' => $group->type,
            'alumni:privacy' => $group->privacy,
            'alumni:institutionId' => $group->institution_id,
            'alumni:memberCount' => $group->member_count,
        ];
    }

    /**
     * Convert a circle to ActivityPub Collection
     */
    public function circleToActivityPubCollection(Circle $circle): array
    {
        return [
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                [
                    'alumni' => 'https://alumni-platform.org/ns#',
                ],
            ],
            'type' => 'Collection',
            'id' => $this->getCircleUrl($circle->id),
            'name' => $circle->name,
            'summary' => "Alumni circle: {$circle->name}",
            'totalItems' => $circle->member_count,
            'items' => $this->getCircleMembersUrl($circle),

            // Alumni platform extensions
            'alumni:circleId' => $circle->id,
            'alumni:circleType' => $circle->type,
            'alumni:criteria' => $circle->criteria,
            'alumni:autoGenerated' => $circle->auto_generated,
        ];
    }

    /**
     * Create ActivityPub Create activity for a post
     */
    public function createPostActivity(Post $post): array
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Create',
            'id' => $this->getPostActivityUrl($post, 'create'),
            'actor' => $this->getUserUrl($post->user),
            'object' => $this->postToActivityPubObject($post),
            'published' => $post->created_at->toISOString(),
            'to' => $this->getPostAudience($post),
            'cc' => $this->getPostCcAudience($post),
        ];
    }

    /**
     * Create ActivityPub Like activity
     */
    public function createLikeActivity(User $user, Post $post): array
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Like',
            'id' => $this->getLikeActivityUrl($user, $post),
            'actor' => $this->getUserUrl($user),
            'object' => $this->getPostUrl($post),
            'published' => now()->toISOString(),
        ];
    }

    /**
     * Create ActivityPub Follow activity
     */
    public function createFollowActivity(User $follower, User $following): array
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Follow',
            'id' => $this->getFollowActivityUrl($follower, $following),
            'actor' => $this->getUserUrl($follower),
            'object' => $this->getUserUrl($following),
            'published' => now()->toISOString(),
        ];
    }

    /**
     * Create ActivityPub Join activity for groups
     */
    public function createJoinActivity(User $user, Group $group): array
    {
        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Join',
            'id' => $this->getJoinActivityUrl($user, $group),
            'actor' => $this->getUserUrl($user),
            'object' => $this->getGroupUrl($group->id),
            'published' => now()->toISOString(),
        ];
    }

    /**
     * Generate URLs for various entities
     */
    public function getUserUrl(User $user): string
    {
        $username = $this->generateUsername($user);

        return "{$this->baseUrl}/federation/users/{$username}";
    }

    /**
     * Generate a username from user data
     */
    protected function generateUsername(User $user): string
    {
        // Use email prefix as username, sanitized for federation compatibility
        $emailPrefix = explode('@', $user->email)[0];
        $username = preg_replace('/[^a-zA-Z0-9._-]/', '', $emailPrefix);

        // Fallback to user ID if email prefix is empty
        if (empty($username)) {
            $username = "user_{$user->id}";
        }

        return strtolower($username);
    }

    protected function getUserProfileUrl(User $user): string
    {
        $username = $this->generateUsername($user);

        return "{$this->baseUrl}/alumni/{$username}";
    }

    protected function getUserInboxUrl(User $user): string
    {
        $username = $this->generateUsername($user);

        return "{$this->baseUrl}/federation/users/{$username}/inbox";
    }

    protected function getUserOutboxUrl(User $user): string
    {
        $username = $this->generateUsername($user);

        return "{$this->baseUrl}/federation/users/{$username}/outbox";
    }

    protected function getUserFollowersUrl(User $user): string
    {
        $username = $this->generateUsername($user);

        return "{$this->baseUrl}/federation/users/{$username}/followers";
    }

    protected function getUserFollowingUrl(User $user): string
    {
        $username = $this->generateUsername($user);

        return "{$this->baseUrl}/federation/users/{$username}/following";
    }

    protected function getPostUrl(Post $post): string
    {
        return "{$this->baseUrl}/federation/posts/{$post->id}";
    }

    protected function getPostActivityUrl(Post $post, string $activity): string
    {
        return "{$this->baseUrl}/federation/posts/{$post->id}/activities/{$activity}";
    }

    protected function getGroupUrl(int $groupId): string
    {
        return "{$this->baseUrl}/federation/groups/{$groupId}";
    }

    protected function getGroupProfileUrl(Group $group): string
    {
        return "{$this->baseUrl}/groups/{$group->id}";
    }

    protected function getGroupInboxUrl(Group $group): string
    {
        return "{$this->baseUrl}/federation/groups/{$group->id}/inbox";
    }

    protected function getGroupOutboxUrl(Group $group): string
    {
        return "{$this->baseUrl}/federation/groups/{$group->id}/outbox";
    }

    protected function getGroupMembersUrl(Group $group): string
    {
        return "{$this->baseUrl}/federation/groups/{$group->id}/members";
    }

    protected function getCircleUrl(int $circleId): string
    {
        return "{$this->baseUrl}/federation/circles/{$circleId}";
    }

    protected function getCircleMembersUrl(Circle $circle): string
    {
        return "{$this->baseUrl}/federation/circles/{$circle->id}/members";
    }

    protected function getLikeActivityUrl(User $user, Post $post): string
    {
        $username = $this->generateUsername($user);

        return "{$this->baseUrl}/federation/users/{$username}/activities/like/{$post->id}";
    }

    protected function getFollowActivityUrl(User $follower, User $following): string
    {
        $followerUsername = $this->generateUsername($follower);
        $followingUsername = $this->generateUsername($following);

        return "{$this->baseUrl}/federation/users/{$followerUsername}/activities/follow/{$followingUsername}";
    }

    protected function getJoinActivityUrl(User $user, Group $group): string
    {
        $username = $this->generateUsername($user);

        return "{$this->baseUrl}/federation/users/{$username}/activities/join/{$group->id}";
    }

    /**
     * Determine ActivityPub object type based on post
     */
    protected function getActivityPubType(Post $post): string
    {
        if ($post->media_urls && count($post->media_urls) > 0) {
            return 'Document';
        }

        return 'Note';
    }

    /**
     * Get post audience for ActivityPub
     */
    protected function getPostAudience(Post $post): array
    {
        $audience = [];

        if ($post->visibility === 'public') {
            $audience[] = 'https://www.w3.org/ns/activitystreams#Public';
        }

        // Add circle/group audiences
        if ($post->circle_ids) {
            foreach ($post->circle_ids as $circleId) {
                $audience[] = $this->getCircleUrl($circleId);
            }
        }

        if ($post->group_ids) {
            foreach ($post->group_ids as $groupId) {
                $audience[] = $this->getGroupUrl($groupId);
            }
        }

        return $audience;
    }

    /**
     * Get post CC audience for ActivityPub
     */
    protected function getPostCcAudience(Post $post): array
    {
        // Add followers if public post
        if ($post->visibility === 'public') {
            return [$this->getUserFollowersUrl($post->user)];
        }

        return [];
    }

    /**
     * Map media attachments to ActivityPub format
     */
    protected function mapMediaAttachments(array $mediaUrls): array
    {
        return array_map(function ($url) {
            return [
                'type' => 'Document',
                'mediaType' => $this->guessMediaType($url),
                'url' => $url,
                'name' => basename($url),
            ];
        }, $mediaUrls);
    }

    /**
     * Extract tags and mentions from content
     */
    protected function extractTags(string $content): array
    {
        $tags = [];

        // Extract hashtags
        preg_match_all('/#([a-zA-Z0-9_]+)/', $content, $hashtags);
        foreach ($hashtags[1] as $hashtag) {
            $tags[] = [
                'type' => 'Hashtag',
                'href' => "{$this->baseUrl}/tags/{$hashtag}",
                'name' => "#{$hashtag}",
            ];
        }

        // Extract mentions
        preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $mentions);
        foreach ($mentions[1] as $username) {
            $tags[] = [
                'type' => 'Mention',
                'href' => "{$this->baseUrl}/federation/users/{$username}",
                'name' => "@{$username}",
            ];
        }

        return $tags;
    }

    /**
     * Guess media type from URL
     */
    protected function guessMediaType(string $url): string
    {
        $extension = pathinfo($url, PATHINFO_EXTENSION);

        return match (strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }

    /**
     * Get user's public key for ActivityPub signatures
     * In a real implementation, this would retrieve the actual key
     */
    protected function getUserPublicKey(User $user): string
    {
        // Placeholder - in real implementation, generate/retrieve actual keys
        return "-----BEGIN PUBLIC KEY-----\n[PUBLIC_KEY_PLACEHOLDER]\n-----END PUBLIC KEY-----";
    }
}
