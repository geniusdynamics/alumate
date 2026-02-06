<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $referrerName;
    protected $referrerId;
    protected $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $referrerName, int $referrerId, string $message)
    {
        $this->referrerName = $referrerName;
        $this->referrerId = $referrerId;
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
            ->subject('New Referral Received')
            ->greeting('Hello!')
            ->line("{$this->referrerName} has referred you!")
            ->line("Message: {$this->message}")
            ->action('View Referral', url('/referrals'))
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
            'type' => 'referral_received',
            'referrer_name' => $this->referrerName,
            'referrer_id' => $this->referrerId,
            'message' => $this->message,
            'notification_message' => "{$this->referrerName} referred you: {$this->message}",
        ];
    }
}