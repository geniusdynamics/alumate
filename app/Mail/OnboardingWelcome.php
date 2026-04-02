<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OnboardingWelcome extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The onboarding welcome data.
     */
    public array $data;

    /**
     * Create a new message instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject("Welcome to {$this->data['tenant_name']} Alumni Portal!")
            ->view('emails.onboarding-welcome')
            ->with([
                'userName' => $this->data['user_name'],
                'tenantName' => $this->data['tenant_name'],
                'loginUrl' => $this->data['login_url'],
                'dashboardUrl' => $this->data['dashboard_url'],
            ]);
    }
}
