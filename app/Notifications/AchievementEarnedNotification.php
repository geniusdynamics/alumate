<?php

namespace App\Notifications;

use App\Models\UserAchievement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AchievementEarnedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private UserAchievement $userAchievement
    ) {}

    /**
     * Get the notification's delivery channels.
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
        $achievement = $this->userAchievement->achievement;

        return (new MailMessage)
            ->subject('🏆 You earned a new achievement!')
            ->greeting('Congratulations!')
            ->line("You've just earned the '{$achievement->name}' achievement!")
            ->line($achievement->description)
            ->line("Points earned: {$achievement->points}")
            ->action('View Your Achievements', url('/achievements'))
            ->line('Keep up the great work!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $achievement = $this->userAchievement->achievement;

        return [
            'type' => 'achievement_earned',
            'title' => 'New Achievement Unlocked!',
            'message' => "You've earned the '{$achievement->name}' achievement!",
            'achievement_id' => $achievement->id,
            'achievement_name' => $achievement->name,
            'achievement_description' => $achievement->description,
            'achievement_icon' => $achievement->icon,
            'achievement_rarity' => $achievement->rarity,
            'points_earned' => $achievement->points,
            'earned_at' => $this->userAchievement->earned_at,
            'action_url' => url('/achievements'),
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
