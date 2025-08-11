<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PostMentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Post $post;

    protected Comment $comment;

    protected User $mentioner;

    public function __construct(Post $post, Comment $comment, User $mentioner)
    {
        $this->post = $post;
        $this->comment = $comment;
        $this->mentioner = $mentioner;
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
            'type' => 'post_mention',
            'post_id' => $this->post->id,
            'comment_id' => $this->comment->id,
            'mentioner_id' => $this->mentioner->id,
            'mentioner_name' => $this->mentioner->name,
            'mentioner_username' => $this->mentioner->username,
            'mentioner_avatar' => $this->mentioner->avatar_url,
            'comment_content' => substr($this->comment->content, 0, 100),
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
        return (new MailMessage)
            ->subject("{$this->mentioner->name} mentioned you in a comment")
            ->greeting("Hi {$notifiable->name}!")
            ->line("{$this->mentioner->name} mentioned you in a comment on this post:")
            ->line('"'.substr($this->post->content, 0, 100).'"')
            ->line('Comment: "'.substr($this->comment->content, 0, 100).'"')
            ->action('View Post', url("/posts/{$this->post->id}"))
            ->line('Thank you for being part of our alumni community!');
    }
}
