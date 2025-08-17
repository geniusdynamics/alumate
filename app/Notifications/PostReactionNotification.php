<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PostReactionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Post $post;

    protected User $reactor;

    protected string $reactionType;

    public function __construct(Post $post, User $reactor, string $reactionType)
    {
        $this->post = $post;
        $this->reactor = $reactor;
        $this->reactionType = $reactionType;
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
            'type' => 'post_reaction',
            'post_id' => $this->post->id,
            'reactor_id' => $this->reactor->id,
            'reactor_name' => $this->reactor->name,
            'reactor_username' => $this->reactor->username,
            'reactor_avatar' => $this->reactor->avatar_url,
            'reaction_type' => $this->reactionType,
            'post_content' => substr($this->post->content, 0, 100),
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
        $reactionText = $this->getReactionText();

        return (new MailMessage)
            ->subject("{$this->reactor->name} {$reactionText} your post")
            ->greeting("Hi {$notifiable->name}!")
            ->line("{$this->reactor->name} {$reactionText} your post:")
            ->line('"'.substr($this->post->content, 0, 100).'"')
            ->action('View Post', url("/posts/{$this->post->id}"))
            ->line('Thank you for being part of our alumni community!');
    }

    private function getReactionText(): string
    {
        return match ($this->reactionType) {
            'like' => 'liked',
            'love' => 'loved',
            'celebrate' => 'celebrated',
            'support' => 'supported',
            'insightful' => 'found insightful',
            'share' => 'shared',
            default => 'reacted to'
        };
    }
}
