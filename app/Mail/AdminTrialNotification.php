<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminTrialNotification extends Mailable
{
    use Queueable, SerializesModels;

    public array $trialData;

    public function __construct(array $trialData)
    {
        $this->trialData = $trialData;
    }

    public function build()
    {
        return $this->subject('New Trial Signup - ' . $this->trialData['name'])
            ->view('emails.admin-trial-notification')
            ->with([
                'trialData' => $this->trialData
            ]);
    }
}