<?php

namespace App\Services\Federation;

use App\Models\Circle;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;

/**
 * Maps internal platform entities to Matrix protocol events
 * Prepares for future Matrix federation integration
 */
class MatrixEventMapper
{
    /**
     * Convert a post to Matrix room message event
     */
    public function postToMatrixEvent(Post $post): array
    {
        $content = [
            'msgtype' => $this->getMessageType($post),
            'body' => $this->extractPlainText($post->content),
            'format' => 'org.matrix.custom.html',
            'formatted_body' => $this->formatContentForMatrix($post->content),

            // Alumni platform extensions
            'alumni.post_id' => $post->id,
            'alumni.post_type' => $post->post_type,
            'alumni.visibility' => $post->visibility,
        ];

        // Add media attachments if present
        if ($post->media_urls && count($post->media_urls) > 0) {
            $content['alumni.media'] = $post->media_urls;
        }

        // Add circle/group context
        if ($post->circle_ids) {
            $content['alumni.circles'] = $post->circle_ids;
        }

        if ($post->group_ids) {
            $content['alumni.groups'] = $post->group_ids;
        }

        return [
            'type' => 'm.room.message',
            'content' => $content,
            'sender' => $this->getUserMatrixId($post->user),
            'origin_server_ts' => $post->created_at->timestamp * 1000,
            'event_id' => $this->generateMatrixEventId($post),
        ];
    }

    /**
     * Convert a user to Matrix user profile
     */
    public function userToMatrixProfile(User $user): array
    {
        return [
            'user_id' => $this->getUserMatrixId($user),
            'displayname' => $user->name,
            'avatar_url' => $user->avatar_url ? $this->convertToMatrixMxc($user->avatar_url) : null,

            // Alumni platform extensions
            'alumni.user_id' => $user->id,
            'alumni.username' => $this->generateUsername($user),
            'alumni.bio' => $user->profile_data['bio'] ?? null,
            'alumni.location' => $user->location,
            'alumni.website' => $user->website,
        ];
    }

    /**
     * Convert a group to Matrix room
     */
    public function groupToMatrixRoom(Group $group): array
    {
        return [
            'room_id' => $this->getGroupMatrixRoomId($group),
            'name' => $group->name,
            'topic' => $group->description,
            'avatar' => $group->avatar_url ? $this->convertToMatrixMxc($group->avatar_url) : null,
            'join_rule' => $this->getMatrixJoinRule($group->privacy),
            'history_visibility' => $this->getMatrixHistoryVisibility($group->privacy),

            // Alumni platform extensions
            'alumni.group_id' => $group->id,
            'alumni.group_type' => $group->type,
            'alumni.institution_id' => $group->institution_id,
        ];
    }

    /**
     * Convert a circle to Matrix space
     */
    public function circleToMatrixSpace(Circle $circle): array
    {
        return [
            'room_id' => $this->getCircleMatrixSpaceId($circle),
            'name' => $circle->name,
            'topic' => "Alumni circle: {$circle->name}",
            'type' => 'm.space',

            // Alumni platform extensions
            'alumni.circle_id' => $circle->id,
            'alumni.circle_type' => $circle->type,
            'alumni.criteria' => $circle->criteria,
            'alumni.auto_generated' => $circle->auto_generated,
        ];
    }

    /**
     * Generate Matrix-compatible user ID
     */
    public function getUserMatrixId(User $user): string
    {
        $domain = $user->institution ? $user->institution->domain : config('app.domain', 'localhost');
        $username = $this->generateUsername($user);

        return "@{$username}:{$domain}";
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

    /**
     * Generate Matrix event ID for a post
     */
    protected function generateMatrixEventId(Post $post): string
    {
        $domain = $post->user->tenant ? $post->user->tenant->domain : config('app.domain', 'localhost');

        return "\$post_{$post->id}_".$post->created_at->timestamp.":{$domain}";
    }

    /**
     * Generate Matrix room ID for a group
     */
    protected function getGroupMatrixRoomId(Group $group): string
    {
        $domain = config('app.domain', 'localhost');

        return "!group_{$group->id}:{$domain}";
    }

    /**
     * Generate Matrix space ID for a circle
     */
    protected function getCircleMatrixSpaceId(Circle $circle): string
    {
        $domain = config('app.domain', 'localhost');

        return "!circle_{$circle->id}:{$domain}";
    }

    /**
     * Determine Matrix message type based on post content
     */
    protected function getMessageType(Post $post): string
    {
        if ($post->media_urls && count($post->media_urls) > 0) {
            // Check if it's an image, video, or file
            $firstMedia = $post->media_urls[0];
            if (str_contains($firstMedia, 'image')) {
                return 'm.image';
            }
            if (str_contains($firstMedia, 'video')) {
                return 'm.video';
            }

            return 'm.file';
        }

        return 'm.text';
    }

    /**
     * Extract plain text from rich content
     */
    protected function extractPlainText(string $content): string
    {
        // Remove HTML tags and decode entities
        return html_entity_decode(strip_tags($content), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Format content for Matrix HTML display
     */
    protected function formatContentForMatrix(string $content): string
    {
        // Convert platform-specific formatting to Matrix-compatible HTML
        // This is a simplified version - real implementation would handle
        // mentions, hashtags, links, etc.
        return $content;
    }

    /**
     * Convert platform URL to Matrix MXC URI
     */
    protected function convertToMatrixMxc(string $url): ?string
    {
        // In a real implementation, this would upload the media to Matrix
        // and return the mxc:// URI. For now, we'll return a placeholder
        $domain = config('app.domain', 'localhost');
        $hash = md5($url);

        return "mxc://{$domain}/{$hash}";
    }

    /**
     * Convert group privacy to Matrix join rule
     */
    protected function getMatrixJoinRule(string $privacy): string
    {
        return match ($privacy) {
            'public' => 'public',
            'private' => 'invite',
            'school' => 'restricted',
            default => 'invite',
        };
    }

    /**
     * Convert group privacy to Matrix history visibility
     */
    protected function getMatrixHistoryVisibility(string $privacy): string
    {
        return match ($privacy) {
            'public' => 'world_readable',
            'private' => 'invited',
            'school' => 'shared',
            default => 'invited',
        };
    }

    /**
     * Create Matrix state event for room membership
     */
    public function createMembershipEvent(User $user, string $roomId, string $membership = 'join'): array
    {
        return [
            'type' => 'm.room.member',
            'state_key' => $this->getUserMatrixId($user),
            'content' => [
                'membership' => $membership,
                'displayname' => $user->name,
                'avatar_url' => $user->avatar_url ? $this->convertToMatrixMxc($user->avatar_url) : null,

                // Alumni platform context
                'alumni.user_id' => $user->id,
            ],
            'room_id' => $roomId,
            'sender' => $this->getUserMatrixId($user),
            'origin_server_ts' => now()->timestamp * 1000,
        ];
    }

    /**
     * Create Matrix reaction event
     */
    public function createReactionEvent(User $user, string $eventId, string $reaction): array
    {
        return [
            'type' => 'm.reaction',
            'content' => [
                'm.relates_to' => [
                    'rel_type' => 'm.annotation',
                    'event_id' => $eventId,
                    'key' => $reaction,
                ],
            ],
            'sender' => $this->getUserMatrixId($user),
            'origin_server_ts' => now()->timestamp * 1000,
        ];
    }
}
