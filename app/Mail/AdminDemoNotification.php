<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminDemoNotification extends Mailable
{
    use Queueable, SerializesModels;

    public array $demoData;

    public function __construct(array $demoData)
    {
        $this->demoData = $demoData;
    }

    public function build()
    {
        return $this->subject('New Demo Request - '.$this->demoData['institution_name'])
            ->view('emails.admin-demo-notification')
            ->with([
                'demoData' => $this->demoData,
            ]);
    }
}
