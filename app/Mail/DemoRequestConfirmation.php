<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DemoRequestConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public array $demoData;

    public function __construct(array $demoData)
    {
        $this->demoData = $demoData;
    }

    public function build()
    {
        return $this->subject('Your Demo Request Has Been Received')
            ->view('emails.demo-request-confirmation')
            ->with([
                'contactName' => $this->demoData['contact_name'],
                'institutionName' => $this->demoData['institution_name'],
                'requestId' => $this->demoData['request_id'],
                'interests' => $this->demoData['interests'],
                'preferredTime' => $this->demoData['preferred_time'],
                'salesEmail' => 'sales@example.com',
                'salesPhone' => '+1 (555) 123-4567',
            ]);
    }
}
