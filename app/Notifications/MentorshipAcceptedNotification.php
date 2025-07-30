<?php

namespace App\Notifications;

use App\Models\MentorshipRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentorshipAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private MentorshipRequest $mentorshipRequest
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Mentorship Request Accepted!')
            ->greeting('Congratulations ' . $notifiable->name . '!')
            ->line($this->mentorshipRequest->mentor->name . ' has accepted your mentorship request.')
            ->line('You can now schedule sessions and begin your mentorship journey.')
            ->action('View Mentorship', url('/mentorship/dashboard'))
            ->line('We wish you a successful mentorship experience!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'mentorship_accepted',
            'mentorship_request_id' => $this->mentorshipRequest->id,
            'mentor_id' => $this->mentorshipRequest->mentor_id,
            'mentor_name' => $this->mentorshipRequest->mentor->name,
            'accepted_at' => $this->mentorshipRequest->accepted_at,
        ];
    }
}