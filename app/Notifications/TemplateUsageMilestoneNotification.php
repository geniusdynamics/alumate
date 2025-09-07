<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemplateUsageMilestoneNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $template;
    protected $milestone;
    protected $user;
    protected $additionalData;

    public function __construct($template, $user = null, $additionalData = [])
    {
        $this->template = $template;
        $this->milestone = $additionalData['milestone'] ?? $template->usage_count;
        $this->user = $user;
        $this->additionalData = $additionalData;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'template_usage_milestone',
            'template_id' => $this->template->id,
            'template_name' => $this->template->name,
            'milestone' => $this->milestone,
            'category' => $this->template->category,
            'audience_type' => $this->template->audience_type,
            'tenant_id' => $this->template->tenant_id,
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
        $milestoneText = $this->getMilestoneText();

        return (new MailMessage)
            ->subject("🎉 Congratulations: '{$this->template->name}' reached {$this->milestone} uses!")
            ->greeting("Hi {$notifiable->name}!")
            ->line("Fantastic news! Your template '{$this->template->name}' has reached a major milestone.")
            ->line("**Template Achievement:**")
            ->line("{$milestoneText} uses across your tenant")
            ->line("Category: " . ucfirst($this->template->category))
            ->line("Audience: " . ucfirst($this->template->audience_type))
            ->when($this->milestone >= 100, function ($mail) {
                return $mail->line('🏆 Congratulations on reaching the 100+ usage mark!');
            })
            ->when($this->milestone >= 500, function ($mail) {
                return $mail->line('⭐ Your template is now part of our most popular collection!');
            })
            ->action('View Template Stats', url("/templates/{$this->template->id}/analytics"))
            ->line('Thank you for creating outstanding templates!');
    }

    private function getMilestoneText(): string
    {
        return $this->milestone;
    }
}