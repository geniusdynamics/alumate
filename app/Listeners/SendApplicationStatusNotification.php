<?php

namespace App\Listeners;

use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendApplicationStatusNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle($event)
    {
        $application = $event->application;
        $oldStatus = $event->oldStatus;
        $newStatus = $event->newStatus;

        // Load necessary relationships
        $application->load(['graduate.user', 'job.employer']);

        if ($application->graduate && $application->graduate->user) {
            $this->notificationService->sendApplicationStatusNotification(
                $application->graduate->user,
                $application,
                $oldStatus,
                $newStatus
            );
        }
    }
}
