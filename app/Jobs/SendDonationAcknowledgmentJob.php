<?php

namespace App\Jobs;

use App\Models\DonationAcknowledgment;
use App\Mail\DonationThankYouMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendDonationAcknowledgmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private DonationAcknowledgment $acknowledgment
    ) {}

    public function handle(): void
    {
        try {
            switch ($this->acknowledgment->type) {
                case 'email':
                    $this->sendEmailAcknowledgment();
                    break;
                case 'letter':
                    $this->sendLetterAcknowledgment();
                    break;
                case 'phone':
                    $this->schedulePhoneCall();
                    break;
                case 'public_recognition':
                    $this->processPublicRecognition();
                    break;
                default:
                    throw new \Exception('Unknown acknowledgment type: ' . $this->acknowledgment->type);
            }

            $this->acknowledgment->markAsSent();

            Log::info('Donation acknowledgment sent successfully', [
                'acknowledgment_id' => $this->acknowledgment->id,
                'type' => $this->acknowledgment->type,
                'donation_id' => $this->acknowledgment->donation_id,
            ]);
        } catch (\Exception $e) {
            $this->acknowledgment->markAsFailed($e->getMessage());

            Log::error('Failed to send donation acknowledgment', [
                'acknowledgment_id' => $this->acknowledgment->id,
                'error' => $e->getMessage(),
            ]);

            // Schedule retry if possible
            if ($this->acknowledgment->canRetry()) {
                $this->acknowledgment->scheduleRetry();
            }

            throw $e;
        }
    }

    private function sendEmailAcknowledgment(): void
    {
        $donation = $this->acknowledgment->donation;
        $recipientEmail = $this->acknowledgment->recipient_email;

        if (empty($recipientEmail)) {
            throw new \Exception('No recipient email address available');
        }

        Mail::to($recipientEmail)->send(
            new DonationThankYouMail($donation, $this->acknowledgment)
        );
    }

    private function sendLetterAcknowledgment(): void
    {
        // This would integrate with a letter printing/mailing service
        // For now, we'll just log it as a placeholder
        Log::info('Letter acknowledgment would be sent', [
            'acknowledgment_id' => $this->acknowledgment->id,
            'recipient' => $this->acknowledgment->recipient_name,
        ]);

        // In a real implementation, you might:
        // 1. Generate a PDF letter
        // 2. Send to a printing service API
        // 3. Track delivery status
    }

    private function schedulePhoneCall(): void
    {
        // This would integrate with a CRM or calling system
        // For now, we'll just create a task/reminder
        Log::info('Phone call acknowledgment scheduled', [
            'acknowledgment_id' => $this->acknowledgment->id,
            'recipient' => $this->acknowledgment->recipient_name,
        ]);

        // In a real implementation, you might:
        // 1. Create a task in a CRM system
        // 2. Add to a calling queue
        // 3. Send notification to staff
    }

    private function processPublicRecognition(): void
    {
        $donation = $this->acknowledgment->donation;
        
        // Only process if donor hasn't opted for anonymity
        if ($donation->is_anonymous) {
            Log::info('Skipping public recognition for anonymous donation', [
                'donation_id' => $donation->id,
            ]);
            return;
        }

        // This would add the donor to public recognition displays
        Log::info('Public recognition processed', [
            'acknowledgment_id' => $this->acknowledgment->id,
            'donor' => $this->acknowledgment->recipient_name,
            'amount' => $donation->amount,
        ]);

        // In a real implementation, you might:
        // 1. Add to donor wall/website
        // 2. Include in newsletter
        // 3. Add to social media posts
        // 4. Update recognition displays
    }
}