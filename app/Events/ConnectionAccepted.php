<?php

namespace App\Events;

use App\Models\Connection;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConnectionAccepted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Connection $connection
    ) {}
}
