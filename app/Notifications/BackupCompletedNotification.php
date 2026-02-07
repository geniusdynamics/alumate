<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a backup operation completes successfully
 */
class BackupCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $backupData;

    public function __construct(array $backupData)
    {
        $this->backupData = $backupData;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'backup_completed',
            'backup_type' => $this->backupData['type'] ?? 'unknown',
            'status' => $this->backupData['status'] ?? 'unknown',
            'started_at' => $this->backupData['started_at'] ?? null,
            'completed_at' => $this->backupData['completed_at'] ?? null,
            'database_backup' => $this->backupData['database']['path'] ?? null,
            'files_backup' => $this->backupData['files']['path'] ?? null,
            'config_backup' => $this->backupData['config']['path'] ?? null,
            'errors' => $this->backupData['errors'] ?? [],
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
        $backupType = $this->backupData['type'] ?? 'unknown';
        $mail = (new MailMessage)
            ->subject("✅ Backup Completed Successfully - {$backupType} backup")
            ->greeting("Hi {$notifiable->name}!")
            ->line('A backup operation has completed successfully.')
            ->line("**Backup Type:** {$backupType}")
            ->line('**Started:** '.($this->backupData['started_at'] ?? 'N/A'))
            ->line('**Completed:** '.($this->backupData['completed_at'] ?? 'N/A'));

        // Add backup details
        if (! empty($this->backupData['database']['path'])) {
            $mail->line('**Database Backup:** Created');
        }
        if (! empty($this->backupData['files']['path'])) {
            $mail->line('**Files Backup:** Created');
        }
        if (! empty($this->backupData['config']['path'])) {
            $mail->line('**Config Backup:** Created');
        }

        // Add verification status
        if (! empty($this->backupData['verification'])) {
            $verification = $this->backupData['verification'];
            $isValid = empty($verification['issues']);
            $mail->line('')
                ->line('**Verification Status:** '.($isValid ? '✅ Passed' : '❌ Failed'));

            if (! empty($verification['issues'])) {
                $mail->line('**Issues Found:**')
                    ->line(implode("\n", array_map(fn ($i) => "  - {$i}", $verification['issues'])));
            }
        }

        return $mail
            ->line('')
            ->line('The backup files have been stored and are ready for recovery if needed.')
            ->action('View Backup Status', url('/admin/backups'));
    }
}
