<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public $sender;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message, User $sender)
    {
        $this->message = $message;
        $this->sender = $sender;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Broadcast to conversation channel
        $channels[] = new PrivateChannel('conversation.'.$this->message->conversation_id);

        // Broadcast to each participant's personal channel
        $participants = $this->message->conversation->participants;
        foreach ($participants as $participant) {
            if ($participant->id !== $this->sender->id) {
                $channels[] = new PrivateChannel('user.'.$participant->id.'.messages');
            }
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'content' => $this->message->content,
                'type' => $this->message->type,
                'attachments' => $this->message->attachments,
                'reply_to_id' => $this->message->reply_to_id,
                'created_at' => $this->message->created_at->toISOString(),
                'user' => [
                    'id' => $this->sender->id,
                    'name' => $this->sender->name,
                    'avatar_url' => $this->sender->avatar_url,
                ],
            ],
            'conversation' => [
                'id' => $this->message->conversation->id,
                'type' => $this->message->conversation->type,
                'title' => $this->message->conversation->getDisplayName($this->sender),
                'last_message_at' => $this->message->conversation->last_message_at->toISOString(),
            ],
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
