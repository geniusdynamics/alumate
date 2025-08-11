<?php

namespace App\Notifications;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConnectionAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected User $accepter;

    protected Connection $connection;

    public function __construct(User $accepter, Connection $connection)
    {
        $this->accepter = $accepter;
        $this->connection = $connection;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'connection_accepted',
            'connection_id' => $this->connection->id,
            'accepter_id' => $this->accepter->id,
            'accepter_name' => $this->accepter->name,
            'accepter_username' => $this->accepter->username,
            'accepter_avatar' => $this->accepter->avatar_url,
            'accepter_title' => $this->accepter->current_job_title,
            'accepter_company' => $this->accepter->current_company,
            'connected_at' => $this->connection->connected_at?->toISOString(),
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return $this->toArray($notifiable);
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast($notifiable): DatabaseMessage
    {
        return new DatabaseMessage($this->toArray($notifiable));
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject("{$this->accepter->name} accepted your connection request")
            ->greeting("Hi {$notifiable->name}!")
            ->line("{$this->accepter->name} has accepted your connection request. You are now connected!");

        if ($this->accepter->current_job_title || $this->accepter->current_company) {
            $jobInfo = [];
            if ($this->accepter->current_job_title) {
                $jobInfo[] = $this->accepter->current_job_title;
            }
            if ($this->accepter->current_company) {
                $jobInfo[] = $this->accepter->current_company;
            }
            $mailMessage->line('Currently: '.implode(' at ', $jobInfo));
        }

        return $mailMessage
            ->action('View Profile', url("/alumni/{$this->accepter->id}"))
            ->line('You can now see their full profile and send them messages.')
            ->line('Start building your professional network today!');
    }
}
