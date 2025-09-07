<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemplateCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $template;
    protected $user;
    protected $additionalData;

    public function __construct($template, $user = null, $additionalData = [])
    {
        $this->template = $template;
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
            'type' => 'template_created',
            'template_id' => $this->template->id,
            'template_name' => $this->template->name,
            'category' => $this->template->category,
            'audience_type' => $this->template->audience_type,
            'campaign_type' => $this->template->campaign_type,
            'is_premium' => $this->template->is_premium,
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
        $categoryText = ucfirst($this->template->category);
        $audienceText = ucfirst($this->template->audience_type);
        $campaignText = ucfirst(str_replace('_', ' ', $this->template->campaign_type));

        return (new MailMessage)
            ->subject("🎨 Template Created: '{$this->template->name}'")
            ->greeting("Hi {$notifiable->name}!")
            ->line("Great job! Your new template has been successfully created.")
            ->line("**Template Details:**")
            ->line("📄 Name: {$this->template->name}")
            ->line("🎯 Campaign: {$campaignText}")
            ->line("🏷️ Category: {$categoryText}")
            ->line("👥 Audience: {$audienceText}")
            ->when($this->template->is_premium, function ($mail) {
                return $mail->line("⭐ This is a premium template");
            })
            ->when(!$this->template->is_premium, function ($mail) {
                return $mail->line("🔓 This is a standard template");
            })
            ->action('View Template', url("/templates/{$this->template->id}"))
            ->action('Edit Template', url("/templates/{$this->template->id}/edit"))
            ->line('Your template is ready for customization and use!');
    }
}