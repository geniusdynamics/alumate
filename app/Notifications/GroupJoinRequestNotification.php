<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupJoinRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Group $group;
    protected User $requester;

    /**
     * Create a new notification instance.
     */
    public function __construct(Group $group, User $requester)
    {
        $this->group = $group;
        $this->requester = $requester;
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
        return (new MailMessage)
            ->subject("New join request for {$this->group->name}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$this->requester->name} has requested to join the group \"{$this->group->name}\".")
            ->line('As a group administrator, you can approve or reject this request.')
            ->action('Review Request', url("/groups/{$this->group->id}/members/pending"))
            ->line('Thank you for managing our alumni community!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'group_join_request',
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'requester_id' => $this->requester->id,
            'requester_name' => $this->requester->name,
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'group_join_request';
    }
}