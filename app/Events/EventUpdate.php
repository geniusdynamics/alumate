<?php

namespace App\Events;

use App\Models\Event;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $event;
    public $updateType;
    public $updateData;
    public $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Event $event, string $updateType, array $updateData = [], ?User $user = null)
    {
        $this->event = $event;
        $this->updateType = $updateType;
        $this->updateData = $updateData;
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        // Broadcast to event-specific channel
        $channels[] = new Channel('event.' . $this->event->id);
        
        // Broadcast to attendees
        foreach ($this->event->attendees as $attendee) {
            $channels[] = new PrivateChannel('user.' . $attendee->id . '.notifications');
        }
        
        // Broadcast to organizer
        $channels[] = new PrivateChannel('user.' . $this->event->organizer_id . '.notifications');
        
        // Broadcast to public events channel if event is public
        if ($this->event->visibility === 'public') {
            $channels[] = new Channel('events.public');
        }
        
        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $data = [
            'event' => [
                'id' => $this->event->id,
                'title' => $this->event->title,
                'description' => $this->event->description,
                'start_date' => $this->event->start_date,
                'end_date' => $this->event->end_date,
                'location' => $this->event->location,
                'event_type' => $this->event->event_type,
                'visibility' => $this->event->visibility,
                'max_attendees' => $this->event->max_attendees,
                'registration_deadline' => $this->event->registration_deadline,
                'organizer' => [
                    'id' => $this->event->organizer->id,
                    'name' => $this->event->organizer->name,
                    'username' => $this->event->organizer->username,
                    'avatar_url' => $this->event->organizer->avatar_url,
                ],
                'attendee_count' => $this->event->attendees()->count(),
                'updated_at' => $this->event->updated_at,
            ],
            'update_type' => $this->updateType,
            'update_data' => $this->updateData,
            'timestamp' => now(),
        ];

        // Add user information if available
        if ($this->user) {
            $data['user'] = [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'username' => $this->user->username,
                'avatar_url' => $this->user->avatar_url,
            ];
        }

        return $data;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'event.update';
    }
}