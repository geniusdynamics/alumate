<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConnectionRequest implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $fromUser;
    public $toUser;
    public $connectionId;
    public $status;
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(User $fromUser, User $toUser, int $connectionId, string $status, ?string $message = null)
    {
        $this->fromUser = $fromUser;
        $this->toUser = $toUser;
        $this->connectionId = $connectionId;
        $this->status = $status;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        // Broadcast to the recipient user
        $channels[] = new PrivateChannel('user.' . $this->toUser->id . '.notifications');
        
        // Broadcast to the sender for status updates
        $channels[] = new PrivateChannel('user.' . $this->fromUser->id . '.connections');
        
        // Broadcast connection status updates
        $channels[] = new PrivateChannel('connection.' . $this->connectionId);
        
        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'connection_id' => $this->connectionId,
            'status' => $this->status,
            'message' => $this->message,
            'from_user' => [
                'id' => $this->fromUser->id,
                'name' => $this->fromUser->name,
                'username' => $this->fromUser->username,
                'avatar_url' => $this->fromUser->avatar_url,
                'current_position' => $this->fromUser->current_position,
                'current_company' => $this->fromUser->current_company,
            ],
            'to_user' => [
                'id' => $this->toUser->id,
                'name' => $this->toUser->name,
                'username' => $this->toUser->username,
                'avatar_url' => $this->toUser->avatar_url,
            ],
            'timestamp' => now(),
            'notification_type' => $this->getNotificationType(),
        ];
    }

    /**
     * Get notification type based on status.
     */
    private function getNotificationType(): string
    {
        return match ($this->status) {
            'pending' => 'connection_request_received',
            'accepted' => 'connection_request_accepted',
            'rejected' => 'connection_request_rejected',
            'blocked' => 'connection_blocked',
            default => 'connection_update',
        };
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'connection.request';
    }
}