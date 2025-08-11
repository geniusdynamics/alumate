<?php

namespace App\Mail;

use App\Models\CampaignDonation;
use App\Models\DonationAcknowledgment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonationThankYouMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CampaignDonation $donation,
        public DonationAcknowledgment $acknowledgment
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Thank you for your generous donation!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.donation-thank-you',
            with: [
                'donation' => $this->donation,
                'acknowledgment' => $this->acknowledgment,
                'campaign' => $this->donation->campaign,
                'donor_name' => $this->acknowledgment->recipient_name,
                'amount' => number_format($this->donation->amount, 2),
                'is_recurring' => $this->donation->is_recurring,
                'personalization' => $this->acknowledgment->personalization_data ?? [],
            ],
        );
    }
}
