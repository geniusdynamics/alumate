<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class PostCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Post $post;
    protected Comment $comment;
    protected User $commenter;

    public function __construct(Post $post, Comment $comment, User $commenter)
    {
        $this->post = $post;
        $this->comment = $comment;
        $this->commenter = $commenter;
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
            'type' => 'post_comment',
            'post_id' => $this->post->id,
            'comment_id' => $this->comment->id,
            'commenter_id' => $this->commenter->id,
            'commenter_name' => $this->commenter->name,
            'commenter_username' => $this->commenter->username,
            'commenter_avatar' => $this->commenter->avatar_url,
            'comment_content' => substr($this->comment->content, 0, 100),
            'post_content' => substr($this->post->content, 0, 100),
            'is_reply' => !is_null($this->comment->parent_id),
            'created_at' => now()->toISOString()
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
        $actionText = $this->comment->parent_id ? 'replied to' : 'commented on';
        
        return (new MailMessage)
            ->subject("{$this->commenter->name} {$actionText} your post")
            ->greeting("Hi {$notifiable->name}!")
            ->line("{$this->commenter->name} {$actionText} your post:")
            ->line('"' . substr($this->post->content, 0, 100) . '"')
            ->line('Comment: "' . substr($this->comment->content, 0, 100) . '"')
            ->action('View Post', url("/posts/{$this->post->id}"))
            ->line('Thank you for being part of our alumni community!');
    }
}