<?php

namespace App\Notifications;

use App\Models\MentorshipRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentorshipRequestNotification extends Notification implements ShouldQueue
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
            ->subject('New Mentorship Request')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->mentorshipRequest->mentee->name . ' has requested you as their mentor.')
            ->line('Message: ' . $this->mentorshipRequest->message)
            ->action('View Request', url('/mentorship/requests/' . $this->mentorshipRequest->id))
            ->line('Thank you for being part of our mentorship community!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'mentorship_request',
            'mentorship_request_id' => $this->mentorshipRequest->id,
            'mentee_id' => $this->mentorshipRequest->mentee_id,
            'mentee_name' => $this->mentorshipRequest->mentee->name,
            'message' => $this->mentorshipRequest->message,
            'created_at' => $this->mentorshipRequest->created_at,
        ];
    }
}