<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ABTestCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $template;
    protected $results;
    protected $user;
    protected $additionalData;

    public function __construct($template, $user = null, $additionalData = [])
    {
        $this->template = $template;
        $this->results = $additionalData['results'] ?? [];
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
            'type' => 'ab_test_completed',
            'template_id' => $this->template->id,
            'template_name' => $this->template->name,
            'results' => $this->results,
            'winner_variant' => $this->results['winner']['variant'] ?? null,
            'winner_conversion' => $this->results['winner']['conversion_rate'] ?? 0,
            'significance' => $this->results['statistical_significance'] ?? false,
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
        $winnerVariant = $this->results['winner']['variant'] ?? 'No clear winner';
        $significance = $this->results['statistical_significance'] ?? false;

        return (new MailMessage)
            ->subject("🧪 A/B Test Results: '{$this->template->name}'")
            ->greeting("Hi {$notifiable->name}!")
            ->line("Your A/B test for the template '{$this->template->name}' has completed!")
            ->line("**Test Results:**")
            ->line("🏆 **Winner:** {$winnerVariant}")
            ->line("📊 {:.2f}", $this->results['winner']['conversion_rate'] ?? 0 . '% conversion rate')
            ->when($significance, function ($mail) {
                return $mail->line('✅ Results are statistically significant');
            })
            ->when(!$significance, function ($mail) {
                return $mail->line('⚠️ Results may not be statistically significant');
            })
            ->line($this->formatVariantsComparison())
            ->action('View Full Results', url("/templates/{$this->template->id}/ab-test/results"))
            ->line('Use the winning variant to optimize your template performance!');
    }

    private function formatVariantsComparison(): string
    {
        if (empty($this->results['variants'])) {
            return '';
        }

        $variants = array_slice($this->results['variants'], 0, 3); // Show top 3 variants
        $text = "**Top Variants:**\n";

        foreach ($variants as $variant) {
            $diff = ($variant['conversion_rate'] ?? 0) - ($this->results['control_conversion'] ?? 0);
            $diffText = $diff > 0 ? '+' . $diff . '%' : $diff . '%';
            $text .= "• {$variant['variant']}: {$variant['conversion_rate']}% ({$diffText})\n";
        }

        return $text;
    }
}