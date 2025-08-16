<?php

namespace App\Events;

use App\Models\CareerMilestone;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CareerMilestoneCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CareerMilestone $careerMilestone
    ) {}
}
