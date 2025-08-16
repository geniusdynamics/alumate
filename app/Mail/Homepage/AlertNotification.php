<?php

namespace App\Mail\Homepage;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $alertData
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $priority = match ($this->alertData['severity']) {
            'critical' => 'high',
            'error' => 'high',
            'warning' => 'normal',
            default => 'low',
        };

        return new Envelope(
            subject: "[{$this->alertData['severity']}] {$this->alertData['title']}",
            tags: $this->alertData['tags'] ?? [],
            metadata: [
                'alert_type' => $this->alertData['type'],
                'severity' => $this->alertData['severity'],
                'environment' => app()->environment(),
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.homepage.alert-notification',
            with: [
                'alert' => $this->alertData,
                'severityColor' => $this->getSeverityColor(),
                'severityIcon' => $this->getSeverityIcon(),
            ]
        );
    }

    /**
     * Get color for severity level.
     */
    private function getSeverityColor(): string
    {
        return match ($this->alertData['severity']) {
            'critical' => '#dc3545',
            'error' => '#dc3545',
            'warning' => '#ffc107',
            'info' => '#17a2b8',
            default => '#6c757d',
        };
    }

    /**
     * Get icon for severity level.
     */
    private function getSeverityIcon(): string
    {
        return match ($this->alertData['severity']) {
            'critical' => '🚨',
            'error' => '❌',
            'warning' => '⚠️',
            'info' => 'ℹ️',
            default => '📊',
        };
    }
}
