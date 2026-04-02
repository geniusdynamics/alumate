<?php

namespace App\Notifications;

use App\Models\AlumniVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationApproved extends Notification
{
    use Queueable;

    protected AlumniVerification $verification;

    /**
     * Create a new notification instance.
     */
    public function __construct(AlumniVerification $verification)
    {
        $this->verification = $verification;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $institutionName = $this->verification->institution?->name ?? 'your institution';
        $graduationYear = $this->verification->graduation_year;

        return (new MailMessage)
            ->subject('Your Alumni Verification Has Been Approved')
            ->greeting('Congratulations!')
            ->line("Your alumni verification for {$institutionName} has been approved.")
            ->line("Graduation Year: {$graduation_year}")
            ->when($this->verification->degree, function ($message) {
                return $message->line("Degree: {$this->verification->degree}");
            })
            ->line('You now have full access to alumni features and benefits.')
            ->action('View Your Profile', url('/profile'))
            ->line('Thank you for being part of our alumni community!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'verification_id' => $this->verification->id,
            'institution_id' => $this->verification->institution_id,
            'institution_name' => $this->verification->institution?->name,
            'graduation_year' => $this->verification->graduation_year,
            'degree' => $this->verification->degree,
            'status' => 'approved',
            'reviewed_at' => $this->verification->reviewed_at,
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
