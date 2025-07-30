<?php

namespace App\Notifications;

use App\Models\MentorshipSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private MentorshipSession $session
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $otherUser = $notifiable->id === $this->session->mentorship->mentor_id 
            ? $this->session->mentorship->mentee 
            : $this->session->mentorship->mentor;

        return (new MailMessage)
            ->subject('Mentorship Session Scheduled')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A mentorship session has been scheduled with ' . $otherUser->name . '.')
            ->line('Date & Time: ' . $this->session->scheduled_at->format('F j, Y \a\t g:i A'))
            ->line('Duration: ' . $this->session->duration . ' minutes')
            ->action('View Session', url('/mentorship/sessions/' . $this->session->id))
            ->line('Please make sure to attend on time!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'session_scheduled',
            'session_id' => $this->session->id,
            'mentorship_id' => $this->session->mentorship_id,
            'scheduled_at' => $this->session->scheduled_at,
            'duration' => $this->session->duration,
            'other_user_name' => $notifiable->id === $this->session->mentorship->mentor_id 
                ? $this->session->mentorship->mentee->name 
                : $this->session->mentorship->mentor->name,
        ];
    }
}