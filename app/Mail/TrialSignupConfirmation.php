<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TrialSignupConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public array $trialData;

    public function __construct(array $trialData)
    {
        $this->trialData = $trialData;
    }

    public function build()
    {
        return $this->subject('Welcome to Your 14-Day Free Trial!')
            ->view('emails.trial-signup-confirmation')
            ->with([
                'name' => $this->trialData['name'],
                'trialId' => $this->trialData['trial_id'],
                'planName' => ucfirst($this->trialData['plan_id']),
                'trialEndDate' => $this->trialData['trial_end_date'],
                'loginUrl' => route('login'),
                'supportEmail' => 'support@example.com'
            ]);
    }
}