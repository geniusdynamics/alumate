<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SkillEndorsedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $skillName;
    protected $endorserName;
    protected $endorserId;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $skillName, string $endorserName, int $endorserId)
    {
        $this->skillName = $skillName;
        $this->endorserName = $endorserName;
        $this->endorserId = $endorserId;
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
            ->subject('New Skill Endorsement Received')
            ->greeting('Hello!')
            ->line("{$this->endorserName} has endorsed your skill: {$this->skillName}")
            ->action('View Your Profile', url('/profile'))
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
            'type' => 'skill_endorsed',
            'skill_name' => $this->skillName,
            'endorser_name' => $this->endorserName,
            'endorser_id' => $this->endorserId,
            'message' => "{$this->endorserName} endorsed your skill: {$this->skillName}",
        ];
    }
}