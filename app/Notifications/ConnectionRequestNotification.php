<?php

namespace App\Notifications;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConnectionRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected User $requester;

    protected Connection $connectionModel;

    protected ?string $message;

    public function __construct(User $requester, Connection $connection, ?string $message = null)
    {
        $this->requester = $requester;
        $this->connectionModel = $connection;
        $this->message = $message;
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
            'type' => 'connection_request',
            'connection_id' => $this->connectionModel->id,
            'requester_id' => $this->requester->id,
            'requester_name' => $this->requester->name,
            'requester_username' => $this->requester->username,
            'requester_avatar' => $this->requester->avatar_url,
            'requester_title' => $this->requester->current_job_title,
            'requester_company' => $this->requester->current_company,
            'message' => $this->message,
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
            ->subject("{$this->requester->name} wants to connect with you")
            ->greeting("Hi {$notifiable->name}!")
            ->line("{$this->requester->name} would like to connect with you on the alumni platform.");

        if ($this->requester->current_job_title || $this->requester->current_company) {
            $jobInfo = [];
            if ($this->requester->current_job_title) {
                $jobInfo[] = $this->requester->current_job_title;
            }
            if ($this->requester->current_company) {
                $jobInfo[] = $this->requester->current_company;
            }
            $mailMessage->line('Currently: '.implode(' at ', $jobInfo));
        }

        if ($this->message) {
            $mailMessage->line('Message: "'.$this->message.'"');
        }

        return $mailMessage
            ->action('View Request', url("/connections/requests/{$this->connectionModel->id}"))
            ->line('You can accept or decline this connection request from your dashboard.');
    }
}
