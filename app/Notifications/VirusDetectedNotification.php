<?php

// ABOUTME: Notification sent when a virus is detected in an uploaded file
// ABOUTME: Notifies users and admins about infected files

namespace App\Notifications;

use App\Models\StoredFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VirusDetectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected StoredFile $storedFile;

    protected bool $isAdminNotification;

    protected ?string $infectionDetails;

    public function __construct(
        StoredFile $storedFile,
        bool $isAdminNotification = false,
        ?string $infectionDetails = null
    ) {
        $this->storedFile = $storedFile;
        $this->isAdminNotification = $isAdminNotification;
        $this->infectionDetails = $infectionDetails;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        // Always notify via database, email for admins or critical cases
        $channels = ['database'];

        if ($this->isAdminNotification || config('filesystems.virus_scanning.notify_by_email', true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'virus_detected',
            'file_id' => $this->storedFile->id,
            'filename' => $this->storedFile->filename,
            'file_size' => $this->storedFile->formatted_size,
            'mime_type' => $this->storedFile->mime_type,
            'collection' => $this->storedFile->collection,
            'uploaded_at' => $this->storedFile->created_at?->toISOString(),
            'infection_details' => $this->infectionDetails,
            'is_admin_notification' => $this->isAdminNotification,
            'action_taken' => config('filesystems.virus_scanning.auto_delete_infected', false)
                ? 'file_auto_deleted'
                : 'file_quarantined',
            'created_at' => now()->toISOString(),
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
        if ($this->isAdminNotification) {
            return $this->toAdminMail($notifiable);
        }

        return $this->toUserMail($notifiable);
    }

    /**
     * Get the mail for user notification
     */
    protected function toUserMail($notifiable): MailMessage
    {
        $subject = '⚠️ Security Alert: Infected File Detected';
        $actionTaken = config('filesystems.virus_scanning.auto_delete_infected', false)
            ? 'has been automatically deleted'
            : 'has been quarantined and is no longer accessible';

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name},")
            ->line('We detected a security issue with a file you recently uploaded.')
            ->line('')
            ->line('**File Details:**')
            ->line("- Name: {$this->storedFile->filename}")
            ->line("- Size: {$this->storedFile->formatted_size}")
            ->line("- Uploaded: {$this->storedFile->created_at?->format('Y-m-d H:i:s')}")
            ->line('')
            ->line("The file {$actionTaken} to protect your account and our systems.");

        if ($this->infectionDetails) {
            $mail->line('')
                ->line('**Detection Details:**')
                ->line($this->infectionDetails);
        }

        $mail->line('')
            ->line('If you believe this is a false positive, please contact our support team.')
            ->line('')
            ->line('For your security, please ensure your local system is protected with up-to-date antivirus software.');

        return $mail;
    }

    /**
     * Get the mail for admin notification
     */
    protected function toAdminMail($notifiable): MailMessage
    {
        $subject = '🚨 Admin Alert: Virus Detected in User Upload';
        $actionTaken = config('filesystems.virus_scanning.auto_delete_infected', false)
            ? 'AUTO-DELETED'
            : 'QUARANTINED';

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello Admin,')
            ->line('A virus has been detected in a file uploaded by a user.')
            ->line('')
            ->line('**File Details:**')
            ->line("- File ID: {$this->storedFile->id}")
            ->line("- Filename: {$this->storedFile->filename}")
            ->line("- User ID: {$this->storedFile->user_id}")
            ->line("- User Email: {$this->storedFile->user?->email}")
            ->line("- Size: {$this->storedFile->formatted_size}")
            ->line("- MIME Type: {$this->storedFile->mime_type}")
            ->line("- Collection: {$this->storedFile->collection}")
            ->line("- Uploaded At: {$this->storedFile->created_at?->format('Y-m-d H:i:s')}")
            ->line("- Tenant ID: {$this->storedFile->tenant_id}")
            ->line('')
            ->line("**Action Taken:** File has been {$actionTaken}");

        if ($this->infectionDetails) {
            $mail->line('')
                ->line('**Infection Details:**')
                ->line($this->infectionDetails);
        }

        return $mail
            ->line('')
            ->line('Please review this incident and take any additional necessary actions.')
            ->action('View File Details', url("/admin/files/{$this->storedFile->id}"));
    }

    /**
     * Get the notification's priority level.
     */
    public function priority(): string
    {
        return $this->isAdminNotification ? 'high' : 'normal';
    }
}
