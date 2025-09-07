<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Event Invitation: {{ $event->title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .event-details {
            background-color: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .event-title {
            font-size: 24px;
            font-weight: bold;
            color: #1976d2;
            margin-bottom: 10px;
        }
        .event-time {
            font-size: 18px;
            font-weight: bold;
            color: #1565c0;
        }
        .event-location {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .organizer-info {
            background-color: #fff3e0;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 14px;
            color: #666;
        }
        .button {
            display: inline-block;
            background-color: #1976d2;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px;
        }
        .button.secondary {
            background-color: #757575;
        }
        .ics-info {
            background-color: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>You're Invited!</h1>
        <p>You have been invited to attend an event</p>
    </div>

    <div class="event-details">
        <div class="event-title">{{ $event->title }}</div>

        @if($event->description)
            <p><strong>Description:</strong> {{ $event->description }}</p>
        @endif

        <div class="event-time">
            📅 {{ $formattedStartDate }}
            @if($formattedStartDate !== $formattedEndDate)
                - {{ $formattedEndDate }}
            @endif
        </div>

        @if($event->timezone)
            <p><strong>Timezone:</strong> {{ $timezone }}</p>
        @endif

        @if($event->location)
            <div class="event-location">
                <strong>📍 Location:</strong><br>
                {{ $event->location }}
            </div>
        @endif

        @if($event->meeting_url)
            <div class="event-location">
                <strong>🔗 Virtual Meeting:</strong><br>
                <a href="{{ $event->meeting_url }}" target="_blank">{{ $event->meeting_url }}</a>
            </div>
        @endif
    </div>

    @if($organizer)
        <div class="organizer-info">
            <strong>Organized by:</strong> {{ $organizer->name }}<br>
            <strong>Contact:</strong> {{ $organizer->email }}
        </div>
    @endif

    <div class="ics-info">
        <strong>📎 Calendar Attachment:</strong><br>
        An ICS calendar file has been attached to this email. You can import this into your preferred calendar application (Google Calendar, Outlook, Apple Calendar, etc.) to automatically add this event to your calendar.
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <p><strong>How to add to your calendar:</strong></p>
        <ol style="text-align: left; display: inline-block;">
            <li>Download the attached ICS file</li>
            <li>Open your calendar application</li>
            <li>Import the ICS file</li>
            <li>The event will be added to your calendar</li>
        </ol>
    </div>

    <div style="text-align: center;">
        <a href="mailto:{{ $organizer->email ?? config('app.organization_email', 'support@example.com') }}?subject=RSVP for {{ urlencode($event->title) }}" class="button">Send RSVP</a>
        <a href="{{ route('events.show', $event) }}" class="button secondary">View Event Details</a>
    </div>

    <p>Thank you for your attention to this invitation. We look forward to seeing you at the event!</p>

    <div class="footer">
        <p>This is an automated calendar invitation from {{ config('app.name') }}.</p>
        <p>If you have any questions about this event, please contact the organizer at {{ $organizer->email ?? config('app.organization_email', 'support@example.com') }}.</p>
    </div>
</body>
</html>