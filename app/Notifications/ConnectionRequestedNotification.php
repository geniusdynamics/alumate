<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConnectionRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $connectorName;
    protected $connectorId;
    protected $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $connectorName, int $connectorId, string $message)
    {
        $this->connectorName = $connectorName;
        $this->connectorId = $connectorId;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Connection Request')
            ->greeting('Hello!')
            ->line("{$this->connectorName} wants to connect with you!")
            ->line("Message: {$this->message}")
            ->action('View Connection Requests', url('/connections'))
            ->line('Thank you for using our platform!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'connection_requested',
            'connector_name' => $this->connectorName,
            'connector_id' => $this->connectorId,
            'message' => $this->message,
            'notification_message' => "{$this->connectorName} wants to connect with you: {$this->message}",
        ];
    }
}