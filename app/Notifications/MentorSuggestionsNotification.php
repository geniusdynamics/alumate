<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentorSuggestionsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Collection $suggestedMentors
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mentorNames = $this->suggestedMentors->take(3)
            ->pluck('user.name')
            ->join(', ', ' and ');

        return (new MailMessage)
            ->subject('Mentor Suggestions for You')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('We found some great mentors who might be perfect for your career journey.')
            ->line('Top suggestions include: '.$mentorNames)
            ->action('Browse Mentors', url('/mentorship/directory'))
            ->line('Don\'t miss out on this opportunity to accelerate your career growth!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'mentor_suggestions',
            'suggested_mentors' => $this->suggestedMentors->map(function ($mentorProfile) {
                return [
                    'id' => $mentorProfile->user->id,
                    'name' => $mentorProfile->user->name,
                    'expertise_areas' => $mentorProfile->expertise_areas,
                    'match_score' => $mentorProfile->match_score ?? 0,
                ];
            })->toArray(),
            'count' => $this->suggestedMentors->count(),
        ];
    }
}
