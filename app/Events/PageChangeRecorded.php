<?php

namespace App\Events;

use App\Models\PageChange;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PageChangeRecorded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PageChange $change
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('page.'.$this->change->page_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'change' => $this->change->load('user:id,name,email'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PageChangeRecorded';
    }
}
