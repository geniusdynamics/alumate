<?php

namespace App\Events;

use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostEngagement implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $post;

    public $user;

    public $engagementType;

    public $engagementData;

    /**
     * Create a new event instance.
     */
    public function __construct(Post $post, User $user, string $engagementType, array $engagementData = [])
    {
        $this->post = $post;
        $this->user = $user;
        $this->engagementType = $engagementType;
        $this->engagementData = $engagementData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Broadcast to post owner
        $channels[] = new PrivateChannel('user.'.$this->post->user_id.'.notifications');

        // Broadcast to post channel for live engagement counters
        $channels[] = new Channel('post.'.$this->post->id.'.engagement');

        // Broadcast to circles if post is shared with circles
        if ($this->post->circle_ids) {
            foreach ($this->post->circle_ids as $circleId) {
                $channels[] = new PrivateChannel('circle.'.$circleId.'.activity');
            }
        }

        // Broadcast to groups if post is shared with groups
        if ($this->post->group_ids) {
            foreach ($this->post->group_ids as $groupId) {
                $channels[] = new PrivateChannel('group.'.$groupId.'.activity');
            }
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $data = [
            'post_id' => $this->post->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'avatar_url' => $this->user->avatar_url,
            ],
            'engagement_type' => $this->engagementType,
            'timestamp' => now(),
        ];

        // Add engagement-specific data
        if ($this->engagementType === 'comment') {
            $data['comment'] = $this->engagementData;
        } elseif ($this->engagementType === 'reaction') {
            $data['reaction_type'] = $this->engagementData['type'] ?? 'like';
        }

        // Add updated engagement counts
        $data['engagement_counts'] = [
            'likes' => $this->post->engagements()->where('type', 'like')->count(),
            'comments' => $this->post->engagements()->where('type', 'comment')->count(),
            'shares' => $this->post->engagements()->where('type', 'share')->count(),
            'reactions' => $this->post->engagements()->whereIn('type', ['like', 'love', 'celebrate', 'support', 'insightful'])->count(),
        ];

        return $data;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'post.engagement';
    }
}
