<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LandingPageCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $template;
    protected $landingPage;
    protected $user;
    protected $additionalData;

    public function __construct($template, $user = null, $additionalData = [])
    {
        $this->template = $template;
        $this->landingPage = $additionalData['landing_page'] ?? null;
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
            'type' => 'landing_page_created',
            'template_id' => $this->template->id,
            'template_name' => $this->template->name,
            'landing_page_id' => $this->landingPage->id ?? null,
            'landing_page_title' => $this->landingPage->title ?? null,
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
        $pageTitle = $this->landingPage->title ?? 'New Landing Page';
        $campaignType = ucfirst(str_replace('_', ' ', $this->template->campaign_type));

        return (new MailMessage)
            ->subject("🎨 Landing Page Created: '{$pageTitle}'")
            ->greeting("Hi {$notifiable->name}!")
            ->line("Your new landing page has been successfully created!")
            ->line("**Landing Page Details:**")
            ->line("📄 Title: {$pageTitle}")
            ->line("🎯 Campaign: {$campaignType}")
            ->line("🏷️ Template: {$this->template->name}")
            ->line("👥 Audience: " . ucfirst($this->template->audience_type))
            ->action('View Landing Page', url('/landing-pages/' . ($this->landingPage->id ?? '') . '/edit'))
            ->action('Preview Page', url('/landing-pages/' . ($this->landingPage->id ?? '') . '/preview'))
            ->line('Your landing page is ready for customization and publishing!');
    }
}