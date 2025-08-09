<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CareerCalculatorReport extends Mailable
{
    use Queueable, SerializesModels;

    public array $formData;
    public array $result;

    /**
     * Create a new message instance.
     */
    public function __construct(array $formData, array $result)
    {
        $this->formData = $formData;
        $this->result = $result;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Personalized Career Value Report',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.career-calculator-report',
            with: [
                'formData' => $this->formData,
                'result' => $this->result,
                'projectedIncrease' => number_format($this->result['projectedSalaryIncrease']),
                'successRate' => $this->result['successProbability'],
                'timeline' => $this->result['careerAdvancementTimeline'],
                'roi' => $this->result['roiEstimate'],
                'recommendations' => $this->result['personalizedRecommendations']
            ]
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