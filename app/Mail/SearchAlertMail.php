<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SearchAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $alertData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $alertData)
    {
        $this->alertData = $alertData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $searchName = $this->alertData['search']->name;
        $newResultsCount = $this->alertData['new_results_count'];
        
        return new Envelope(
            subject: "New results for your saved search: {$searchName} ({$newResultsCount} new)",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.search-alert',
            with: $this->alertData
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}