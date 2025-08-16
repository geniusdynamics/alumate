<?php

namespace App\Services;

use App\Models\CalendarConnection;
use App\Models\Event;
use App\Models\MentorshipSession;
use App\Models\User;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Microsoft\Graph\Graph;

class CalendarIntegrationService
{
    protected array $supportedProviders = [
        'google',
        'outlook',
        'apple',
        'caldav',
    ];

    public function __construct(
        protected GoogleClient $googleClient,
        protected Graph $microsoftGraph
    ) {}

    /**
     * Connect user's calendar to the platform
     */
    public function connectCalendar(User $user, string $provider, array $credentials): CalendarConnection
    {
        $this->validateProvider($provider);

        $connection = CalendarConnection::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
            ],
            [
                'credentials' => encrypt($credentials),
                'is_active' => true,
                'last_sync_at' => null,
            ]
        );

        // Test the connection
        if ($this->testConnection($connection)) {
            $this->syncCalendar($connection);
        }

        return $connection;
    }

    /**
     * Sync events from external calendar
     */
    public function syncCalendar(CalendarConnection $connection): bool
    {
        try {
            $events = $this->fetchExternalEvents($connection);

            foreach ($events as $externalEvent) {
                $this->createOrUpdateEvent($connection, $externalEvent);
            }

            $connection->update([
                'last_sync_at' => now(),
                'sync_status' => 'success',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Calendar sync failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            $connection->update([
                'sync_status' => 'failed',
                'sync_error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create calendar event and sync to external calendars
     */
    public function createEvent(User $user, array $eventData): Event
    {
        $event = Event::create([
            'user_id' => $user->id,
            'title' => $eventData['title'],
            'description' => $eventData['description'] ?? null,
            'start_time' => Carbon::parse($eventData['start_time']),
            'end_time' => Carbon::parse($eventData['end_time']),
            'location' => $eventData['location'] ?? null,
            'is_virtual' => $eventData['is_virtual'] ?? false,
            'meeting_url' => $eventData['meeting_url'] ?? null,
            'attendees' => $eventData['attendees'] ?? [],
            'event_type' => $eventData['event_type'] ?? 'general',
        ]);

        // Sync to connected calendars
        $this->syncEventToExternalCalendars($event);

        return $event;
    }

    /**
     * Send calendar invites to attendees
     */
    public function sendCalendarInvites(Event $event): void
    {
        $attendees = collect($event->attendees);

        foreach ($attendees as $attendeeEmail) {
            $user = User::where('email', $attendeeEmail)->first();

            if ($user && $user->calendarConnections()->active()->exists()) {
                $this->createEventInUserCalendar($user, $event);
            }

            // Send email invite as fallback
            $this->sendEmailInvite($event, $attendeeEmail);
        }
    }

    /**
     * Get user's availability for scheduling
     */
    public function getUserAvailability(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        $availability = collect();

        // Get user's calendar connections
        $connections = $user->calendarConnections()->active()->get();

        foreach ($connections as $connection) {
            $busyTimes = $this->fetchBusyTimes($connection, $startDate, $endDate);
            $availability = $availability->merge($busyTimes);
        }

        // Get platform events
        $platformEvents = $user->events()
            ->whereBetween('start_time', [$startDate, $endDate])
            ->get(['start_time', 'end_time']);

        foreach ($platformEvents as $event) {
            $availability->push([
                'start' => $event->start_time,
                'end' => $event->end_time,
                'source' => 'platform',
            ]);
        }

        return $availability->sortBy('start');
    }

    /**
     * Find available time slots for scheduling
     */
    public function findAvailableSlots(
        Collection $users,
        Carbon $startDate,
        Carbon $endDate,
        int $durationMinutes = 60,
        array $workingHours = ['09:00', '17:00']
    ): Collection {
        $availableSlots = collect();

        // Get all users' busy times
        $allBusyTimes = collect();
        foreach ($users as $user) {
            $userBusyTimes = $this->getUserAvailability($user, $startDate, $endDate);
            $allBusyTimes = $allBusyTimes->merge($userBusyTimes);
        }

        // Sort busy times
        $allBusyTimes = $allBusyTimes->sortBy('start');

        // Find gaps in busy times
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dayStart = $currentDate->copy()->setTimeFromTimeString($workingHours[0]);
            $dayEnd = $currentDate->copy()->setTimeFromTimeString($workingHours[1]);

            $dayBusyTimes = $allBusyTimes->filter(function ($busyTime) use ($dayStart, $dayEnd) {
                return Carbon::parse($busyTime['start'])->between($dayStart, $dayEnd) ||
                    Carbon::parse($busyTime['end'])->between($dayStart, $dayEnd);
            });

            $slots = $this->findSlotsInDay($dayStart, $dayEnd, $dayBusyTimes, $durationMinutes);
            $availableSlots = $availableSlots->merge($slots);

            $currentDate->addDay();
        }

        return $availableSlots;
    }

    /**
     * Schedule mentorship session with calendar integration
     */
    public function scheduleMentorshipSession(
        User $mentor,
        User $mentee,
        Carbon $startTime,
        int $durationMinutes = 60,
        array $sessionData = []
    ): MentorshipSession {
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        $session = MentorshipSession::create([
            'mentor_id' => $mentor->id,
            'mentee_id' => $mentee->id,
            'scheduled_at' => $startTime,
            'duration_minutes' => $durationMinutes,
            'status' => 'scheduled',
            'topic' => $sessionData['topic'] ?? null,
            'notes' => $sessionData['notes'] ?? null,
            'meeting_url' => $sessionData['meeting_url'] ?? null,
        ]);

        // Create calendar event
        $event = $this->createEvent($mentor, [
            'title' => "Mentorship Session with {$mentee->name}",
            'description' => $sessionData['topic'] ?? 'Mentorship session',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'attendees' => [$mentor->email, $mentee->email],
            'is_virtual' => ! empty($sessionData['meeting_url']),
            'meeting_url' => $sessionData['meeting_url'] ?? null,
            'event_type' => 'mentorship',
        ]);

        $session->update(['event_id' => $event->id]);

        return $session;
    }

    /**
     * Fetch events from external calendar
     */
    protected function fetchExternalEvents(CalendarConnection $connection): Collection
    {
        return match ($connection->provider) {
            'google' => $this->fetchGoogleEvents($connection),
            'outlook' => $this->fetchOutlookEvents($connection),
            'apple' => $this->fetchAppleEvents($connection),
            'caldav' => $this->fetchCalDAVEvents($connection),
            default => collect(),
        };
    }

    /**
     * Fetch Google Calendar events
     */
    protected function fetchGoogleEvents(CalendarConnection $connection): Collection
    {
        $credentials = decrypt($connection->credentials);
        $this->googleClient->setAccessToken($credentials);

        if ($this->googleClient->isAccessTokenExpired()) {
            $this->refreshGoogleToken($connection);
        }

        $service = new GoogleCalendar($this->googleClient);

        $timeMin = now()->subDays(7)->toRfc3339String();
        $timeMax = now()->addDays(30)->toRfc3339String();

        $events = $service->events->listEvents('primary', [
            'timeMin' => $timeMin,
            'timeMax' => $timeMax,
            'singleEvents' => true,
            'orderBy' => 'startTime',
        ]);

        return collect($events->getItems())->map(function ($event) {
            return [
                'external_id' => $event->getId(),
                'title' => $event->getSummary(),
                'description' => $event->getDescription(),
                'start_time' => $event->getStart()->getDateTime() ?? $event->getStart()->getDate(),
                'end_time' => $event->getEnd()->getDateTime() ?? $event->getEnd()->getDate(),
                'location' => $event->getLocation(),
            ];
        });
    }

    /**
     * Fetch Microsoft Outlook events
     */
    protected function fetchOutlookEvents(CalendarConnection $connection): Collection
    {
        $credentials = decrypt($connection->credentials);
        $this->microsoftGraph->setAccessToken($credentials['access_token']);

        $startTime = now()->subDays(7)->toISOString();
        $endTime = now()->addDays(30)->toISOString();

        try {
            $events = $this->microsoftGraph
                ->createRequest('GET', "/me/calendar/events?\$filter=start/dateTime ge '{$startTime}' and end/dateTime le '{$endTime}'")
                ->execute();

            return collect($events->getBody()['value'])->map(function ($event) {
                return [
                    'external_id' => $event['id'],
                    'title' => $event['subject'],
                    'description' => $event['body']['content'] ?? null,
                    'start_time' => $event['start']['dateTime'],
                    'end_time' => $event['end']['dateTime'],
                    'location' => $event['location']['displayName'] ?? null,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to fetch Outlook events', ['error' => $e->getMessage()]);

            return collect();
        }
    }

    /**
     * Test calendar connection
     */
    protected function testConnection(CalendarConnection $connection): bool
    {
        try {
            return match ($connection->provider) {
                'google' => $this->testGoogleConnection($connection),
                'outlook' => $this->testOutlookConnection($connection),
                'apple' => $this->testAppleConnection($connection),
                'caldav' => $this->testCalDAVConnection($connection),
                default => false,
            };
        } catch (\Exception $e) {
            Log::error('Calendar connection test failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Validate calendar provider
     */
    protected function validateProvider(string $provider): void
    {
        if (! in_array($provider, $this->supportedProviders)) {
            throw new \InvalidArgumentException("Unsupported calendar provider: {$provider}");
        }
    }

    /**
     * Additional helper methods would be implemented here for:
     * - fetchAppleEvents()
     * - fetchCalDAVEvents()
     * - testGoogleConnection()
     * - testOutlookConnection()
     * - testAppleConnection()
     * - testCalDAVConnection()
     * - refreshGoogleToken()
     * - createOrUpdateEvent()
     * - syncEventToExternalCalendars()
     * - createEventInUserCalendar()
     * - sendEmailInvite()
     * - fetchBusyTimes()
     * - findSlotsInDay()
     */
}
