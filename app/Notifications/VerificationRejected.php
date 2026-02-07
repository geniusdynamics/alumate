<?php

namespace App\Notifications;

use App\Models\AlumniVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationRejected extends Notification
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
        $rejectionReason = $this->verification->rejection_reason ?? 'No specific reason provided.';

        $message = (new MailMessage)
            ->subject('Alumni Verification Update')
            ->greeting('Hello!')
            ->line("We regret to inform you that your alumni verification for {$institutionName} could not be approved at this time.")
            ->line('Reason: '.$rejectionReason)
            ->line('You may resubmit your verification with additional or corrected information.')
            ->action('Resubmit Verification', url('/verification'));

        // Add helpful tips based on rejection reason
        if (str_contains(strtolower($rejectionReason), 'document')) {
            $message->line('Tip: Please ensure your supporting documents are clear, legible, and show your full name and graduation information.');
        } elseif (str_contains(strtolower($rejectionReason), 'information') || str_contains(strtolower($rejectionReason), 'details')) {
            $message->line('Tip: Please verify that all information provided matches your official records.');
        } elseif (str_contains(strtolower($rejectionReason), 'year')) {
            $message->line('Tip: Please double-check your graduation year. It should match your official records.');
        }

        return $message->line('If you have any questions, please contact our support team.');
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
            'status' => 'rejected',
            'rejection_reason' => $this->verification->rejection_reason,
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
