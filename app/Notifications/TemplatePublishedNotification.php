<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemplatePublishedNotification extends Notification implements ShouldQueue
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
            'type' => 'template_published',
            'template_id' => $this->template->id,
            'template_name' => $this->template->name,
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
        return (new MailMessage)
            ->subject("Your Template '{$this->template->name}' Has Been Published!")
            ->greeting("Hi {$notifiable->name}!")
            ->line("Great news! Your template '{$this->template->name}' has been published successfully.")
            ->line("**Template Details:**")
            ->line("- Category: " . ucfirst($this->template->category))
            ->line("- Audience: " . ucfirst($this->template->audience_type))
            ->line("- Campaign Type: " . ucfirst(str_replace('_', ' ', $this->template->campaign_type)))
            ->action('View Template', url("/templates/{$this->template->id}"))
            ->line('Your template is now live and ready to use!')
            ->line('Thank you for contributing to our template library!');
    }
}