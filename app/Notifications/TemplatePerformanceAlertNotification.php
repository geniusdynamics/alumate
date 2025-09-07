<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemplatePerformanceAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $template;
    protected $metrics;
    protected $user;
    protected $additionalData;

    public function __construct($template, $user = null, $additionalData = [])
    {
        $this->template = $template;
        $this->metrics = $additionalData['metrics'] ?? [];
        $this->user = $user;
        $this->additionalData = $additionalData;
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
            'type' => 'template_performance_alert',
            'template_id' => $this->template->id,
            'template_name' => $this->template->name,
            'metrics' => $this->metrics,
            'alert_type' => $this->getAlertType(),
            'tenant_id' => $this->template->tenant_id,
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
        $alertType = $this->getAlertType();
        $subject = $this->getSubjectForAlertType($alertType);

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hi {$notifiable->name}!")
            ->line("Performance update for your template '{$this->template->name}':")
            ->when($alertType === 'congratulations', function ($mail) {
                return $mail->line('🎉 Excellent performance! Your template is performing better than expected.');
            })
            ->when($alertType === 'warning', function ($mail) {
                return $mail->line('⚠️ Your template needs attention. Performance metrics are below optimal levels.');
            })
            ->when($alertType === 'critical', function ($mail) {
                return $mail->line('🚨 Critical Alert: Your template requires immediate attention!');
            })
            ->line($this->formatMetrics())
            ->action('View Template Analytics', url("/templates/{$this->template->id}/analytics"))
            ->line($this->getRecommendationText($alertType));
    }

    private function getAlertType(): string
    {
        $conversionRate = $this->metrics['conversion_rate'] ?? 0;
        $avgLoadTime = $this->metrics['avg_load_time'] ?? 0;
        $usageCount = $this->template->usage_count ?? 0;

        // High-performing template
        if ($conversionRate > 5.0 || $usageCount > 50) {
            return 'congratulations';
        }

        // Warning level
        if ($conversionRate < 1.0 || $avgLoadTime > 3.0) {
            return 'warning';
        }

        // Critical level
        if ($conversionRate < 0.5 || $avgLoadTime > 5.0) {
            return 'critical';
        }

        return 'info';
    }

    private function getSubjectForAlertType(string $alertType): string
    {
        return match ($alertType) {
            'congratulations' => "🎉 Great News: '{$this->template->name}' Performance Update",
            'warning' => "⚠️ Attention Needed: '{$this->template->name}' Performance",
            'critical' => "🚨 Critical Alert: '{$this->template->name}' Performance",
            default => "Performance Update: '{$this->template->name}'",
        };
    }

    private function formatMetrics(): string
    {
        $conversionRate = $this->metrics['conversion_rate'] ?? 0;
        $avgLoadTime = $this->metrics['avg_load_time'] ?? 0;
        $usageCount = $this->template->usage_count ?? 0;

        return "• **Conversion Rate:** {$conversionRate}%\n" .
               "• **Average Load Time:** {$avgLoadTime}s\n" .
               "• **Total Usage:** {$usageCount} times\n";
    }

    private function getRecommendationText(string $alertType): string
    {
        return match ($alertType) {
            'congratulations' => 'Keep up the excellent work! Consider sharing this template with the community.',
            'warning' => 'Consider optimizing your template structure or reviewing its audience targeting.',
            'critical' => 'Please review and optimize your template to improve performance.',
            default => 'Monitor performance metrics regularly for optimal results.',
        };
    }
}