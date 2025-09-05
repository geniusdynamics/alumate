<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CalendarInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public string $recipientEmail
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're invited: {$this->event->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.calendar-invite',
            with: [
                'event' => $this->event,
                'organizer' => $this->event->organizer,
                'recipientEmail' => $this->recipientEmail,
                'formattedStartDate' => $this->event->getLocalStartDate()->format('l, F j, Y \a\t g:i A'),
                'formattedEndDate' => $this->event->getLocalEndDate()->format('l, F j, Y \a\t g:i A'),
                'timezone' => $this->event->timezone ?? config('app.timezone', 'UTC'),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->generateICSFile(),
                'event-invite.ics'
            )
                ->withMime('text/calendar; charset=UTF-8; method=REQUEST'),
        ];
    }

    private function generateICSFile(): string
    {
        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Alumni Platform//Calendar Integration//EN\r\n";
        $ics .= "METHOD:REQUEST\r\n";
        $ics .= "BEGIN:VEVENT\r\n";

        // Event UID
        $ics .= "UID:" . $this->event->id . "@" . config('app.url') . "\r\n";

        // Event details
        $ics .= "SUMMARY:" . $this->escapeICSValue($this->event->title) . "\r\n";

        if ($this->event->description) {
            $ics .= "DESCRIPTION:" . $this->escapeICSValue($this->event->description) . "\r\n";
        }

        if ($this->event->location) {
            $ics .= "LOCATION:" . $this->escapeICSValue($this->event->location) . "\r\n";
        }

        // Date/time in UTC
        $startDate = $this->event->start_date->setTimezone('UTC');
        $endDate = $this->event->end_date->setTimezone('UTC');

        $ics .= "DTSTART:" . $startDate->format('Ymd\THis\Z') . "\r\n";
        $ics .= "DTEND:" . $endDate->format('Ymd\THis\Z') . "\r\n";

        // Organizer
        if ($this->event->organizer) {
            $ics .= "ORGANIZER;CN=" . $this->escapeICSValue($this->event->organizer->name) . ":mailto:" . $this->event->organizer->email . "\r\n";
        }

        // Attendee
        $ics .= "ATTENDEE;ROLE=REQ-PARTICIPANT;RSVP=TRUE:mailto:" . $this->recipientEmail . "\r\n";

        // Status and other properties
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "SEQUENCE:0\r\n";
        $ics .= "CREATED:" . now()->setTimezone('UTC')->format('Ymd\THis\Z') . "\r\n";
        $ics .= "LAST-MODIFIED:" . now()->setTimezone('UTC')->format('Ymd\THis\Z') . "\r\n";

        // Add reminder
        $ics .= "BEGIN:VALARM\r\n";
        $ics .= "TRIGGER:-PT15M\r\n"; // 15 minutes before
        $ics .= "ACTION:DISPLAY\r\n";
        $ics .= "DESCRIPTION:Reminder: " . $this->escapeICSValue($this->event->title) . "\r\n";
        $ics .= "END:VALARM\r\n";

        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    private function escapeICSValue(string $value): string
    {
        // Escape special characters for ICS format
        return str_replace(
            [',', ';', '\\', "\n"],
            ['\\,', '\\;', '\\\\', '\\n'],
            $value
        );
    }
}