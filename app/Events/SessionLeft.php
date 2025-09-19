<?php

namespace App\Events;

use App\Models\CollaborationSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionLeft implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CollaborationSession $session
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('page.' . $this->session->page_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'session' => $this->session->load('user:id,name,email,avatar'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'SessionLeft';
    }
}
