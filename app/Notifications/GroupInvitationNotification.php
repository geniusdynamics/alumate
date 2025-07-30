<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Group $group;
    protected User $inviter;
    protected ?string $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Group $group, User $inviter, ?string $message = null)
    {
        $this->group = $group;
        $this->inviter = $inviter;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject("You've been invited to join {$this->group->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$this->inviter->name} has invited you to join the group \"{$this->group->name}\".");

        if ($this->message) {
            $mailMessage->line("Personal message: \"{$this->message}\"");
        }

        $mailMessage->line($this->group->description ?: 'Join this group to connect with fellow alumni and participate in discussions.')
            ->action('View Group', url("/groups/{$this->group->id}"))
            ->line('Thank you for being part of our alumni community!');

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'group_invitation',
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'inviter_id' => $this->inviter->id,
            'inviter_name' => $this->inviter->name,
            'message' => $this->message,
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'group_invitation';
    }
}