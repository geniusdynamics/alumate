<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a backup operation fails
 */
class BackupFailedNotification extends Notification implements ShouldQueue
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
            'type' => 'backup_failed',
            'backup_type' => $this->backupData['type'] ?? 'unknown',
            'status' => $this->backupData['status'] ?? 'failed',
            'started_at' => $this->backupData['started_at'] ?? null,
            'completed_at' => $this->backupData['completed_at'] ?? null,
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
        $errors = $this->backupData['errors'] ?? [];
        $errorMessage = ! empty($errors) ? implode("\n", array_map(fn ($e) => "  - {$e}", $errors)) : 'Unknown error occurred';

        $mail = (new MailMessage)
            ->subject("🚨 Backup Failed - {$backupType} backup")
            ->greeting("Hi {$notifiable->name}!")
            ->line('A backup operation has **failed** to complete successfully.')
            ->line("**Backup Type:** {$backupType}")
            ->line('**Started:** '.($this->backupData['started_at'] ?? 'N/A'))
            ->line('**Failed At:** '.($this->backupData['completed_at'] ?? now()->toISOString()))
            ->line('')
            ->line('**Error Details:**')
            ->line($errorMessage)
            ->line('')
            ->line('⚠️ Please investigate the issue immediately. The backup may need to be retried.');

        // Add troubleshooting steps
        return $mail
            ->line('')
            ->line('**Troubleshooting Steps:**')
            ->line('1. Check server disk space')
            ->line('2. Verify database connectivity')
            ->line('3. Review application logs for errors')
            ->line('4. Ensure backup storage is accessible')
            ->line('')
            ->action('View Backup Logs', url('/admin/logs?filter=backup'));
    }
}
