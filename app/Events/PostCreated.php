<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $post;

    /**
     * Create a new event instance.
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Broadcast to user's timeline
        $channels[] = new PrivateChannel('user.'.$this->post->user_id.'.timeline');

        // Broadcast to circles if post is shared with circles
        if ($this->post->circle_ids) {
            foreach ($this->post->circle_ids as $circleId) {
                $channels[] = new PrivateChannel('circle.'.$circleId);
            }
        }

        // Broadcast to groups if post is shared with groups
        if ($this->post->group_ids) {
            foreach ($this->post->group_ids as $groupId) {
                $channels[] = new PrivateChannel('group.'.$groupId);
            }
        }

        // Broadcast to general timeline for public posts
        if ($this->post->visibility === 'public') {
            $channels[] = new Channel('timeline.public');
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'post' => [
                'id' => $this->post->id,
                'user_id' => $this->post->user_id,
                'content' => $this->post->content,
                'media_urls' => $this->post->media_urls,
                'post_type' => $this->post->post_type,
                'visibility' => $this->post->visibility,
                'circle_ids' => $this->post->circle_ids,
                'group_ids' => $this->post->group_ids,
                'created_at' => $this->post->created_at,
                'user' => [
                    'id' => $this->post->user->id,
                    'name' => $this->post->user->name,
                    'username' => $this->post->user->username,
                    'avatar_url' => $this->post->user->avatar_url,
                ],
                'engagement_counts' => [
                    'likes' => 0,
                    'comments' => 0,
                    'shares' => 0,
                ],
            ],
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'post.created';
    }
}
