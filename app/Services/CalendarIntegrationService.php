<?php
// ABOUTME: Service for integrating with external calendar providers (Google, Outlook, Apple, CalDAV)
// ABOUTME: Updated for schema-based tenancy - handles calendar sync and event management within tenant context

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

class CalendarIntegrationService extends BaseService
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
     * Send email invitation for calendar event
     */
    public function sendEmailInvite(Event $event, string $email): void
    {
        try {
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Log::warning('Invalid email address provided for calendar invite', [
                    'event_id' => $event->id,
                    'email' => $email,
                ]);
                return;
            }

            // Send the invitation email with ICS attachment
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\CalendarInviteMail($event, $email));

            Log::info('Calendar invitation email sent successfully', [
                'event_id' => $event->id,
                'recipient_email' => $email,
                'event_title' => $event->title,
            ]);

        } catch (\Illuminate\Mail\MailException $e) {
            Log::error('Failed to send calendar invitation email', [
                'event_id' => $event->id,
                'recipient_email' => $email,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ]);

            // Could implement retry logic here if needed
            // For now, we'll just log the error and continue

        } catch (\Exception $e) {
            Log::error('Unexpected error sending calendar invitation', [
                'event_id' => $event->id,
                'recipient_email' => $email,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ]);

            // Re-throw unexpected exceptions to maintain error handling chain
            throw $e;
        }
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
     * Fetch busy times from external calendar
     */
    public function fetchBusyTimes(CalendarConnection $connection, Carbon $startDate, Carbon $endDate): Collection
    {
        try {
            $cacheKey = "calendar_busy_times_{$connection->id}_{$startDate->toDateString()}_{$endDate->toDateString()}";

            // Check cache first for performance
            $cachedBusyTimes = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if ($cachedBusyTimes) {
                return collect($cachedBusyTimes);
            }

            $busyTimes = match ($connection->provider) {
                'google' => $this->fetchGoogleBusyTimes($connection, $startDate, $endDate),
                'outlook' => $this->fetchOutlookBusyTimes($connection, $startDate, $endDate),
                'apple' => $this->fetchAppleBusyTimes($connection, $startDate, $endDate),
                'caldav' => $this->fetchCalDAVBusyTimes($connection, $startDate, $endDate),
                default => collect(),
            };

            // Cache the results for 15 minutes
            \Illuminate\Support\Facades\Cache::put($cacheKey, $busyTimes->toArray(), now()->addMinutes(15));

            Log::info('Fetched busy times from external calendar', [
                'connection_id' => $connection->id,
                'provider' => $connection->provider,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'busy_times_count' => $busyTimes->count(),
            ]);

            return $busyTimes;
        } catch (\Exception $e) {
            Log::error('Failed to fetch busy times', [
                'connection_id' => $connection->id,
                'provider' => $connection->provider,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Find available time slots within a day
     */
    public function findSlotsInDay(Carbon $dayStart, Carbon $dayEnd, Collection $busyTimes, int $durationMinutes): Collection
    {
        try {
            $availableSlots = collect();
            $workingHoursStart = $dayStart->copy()->setTime(9, 0, 0); // 9 AM
            $workingHoursEnd = $dayStart->copy()->setTime(17, 0, 0);   // 5 PM

            // Adjust for working hours
            $effectiveStart = $dayStart->max($workingHoursStart);
            $effectiveEnd = $dayEnd->min($workingHoursEnd);

            // If the effective time range is invalid, return empty
            if ($effectiveStart->gte($effectiveEnd)) {
                return $availableSlots;
            }

            // Sort and merge overlapping busy times
            $mergedBusyTimes = $this->mergeOverlappingBusyTimes($busyTimes);

            // Find gaps between busy periods
            $currentTime = $effectiveStart->copy();

            foreach ($mergedBusyTimes as $busyTime) {
                $busyStart = Carbon::parse($busyTime['start']);
                $busyEnd = Carbon::parse($busyTime['end']);

                // Skip busy times outside our working hours
                if ($busyEnd->lte($effectiveStart) || $busyStart->gte($effectiveEnd)) {
                    continue;
                }

                // Adjust busy time to working hours
                $adjustedBusyStart = $busyStart->max($effectiveStart);
                $adjustedBusyEnd = $busyEnd->min($effectiveEnd);

                // If there's a gap before this busy period
                if ($currentTime->lt($adjustedBusyStart)) {
                    $slots = $this->calculateSlotsInGap($currentTime, $adjustedBusyStart, $durationMinutes);
                    $availableSlots = $availableSlots->merge($slots);
                }

                // Move current time to end of this busy period
                $currentTime = $adjustedBusyEnd->max($currentTime);
            }

            // Check for slots after the last busy period
            if ($currentTime->lt($effectiveEnd)) {
                $slots = $this->calculateSlotsInGap($currentTime, $effectiveEnd, $durationMinutes);
                $availableSlots = $availableSlots->merge($slots);
            }

            Log::info('Calculated available slots for day', [
                'day' => $dayStart->toDateString(),
                'working_hours_start' => $workingHoursStart->toTimeString(),
                'working_hours_end' => $workingHoursEnd->toTimeString(),
                'busy_times_count' => $busyTimes->count(),
                'available_slots_count' => $availableSlots->count(),
                'duration_minutes' => $durationMinutes,
            ]);

            return $availableSlots;
        } catch (\Exception $e) {
            Log::error('Failed to find slots in day', [
                'day' => $dayStart->toDateString(),
                'busy_times_count' => $busyTimes->count(),
                'duration_minutes' => $durationMinutes,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
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
     * Fetch Google Calendar busy times
     */
    protected function fetchGoogleBusyTimes(CalendarConnection $connection, Carbon $startDate, Carbon $endDate): Collection
    {
        $credentials = decrypt($connection->credentials);
        $this->googleClient->setAccessToken($credentials);

        if ($this->googleClient->isAccessTokenExpired()) {
            $this->refreshGoogleToken($connection);
        }

        $service = new GoogleCalendar($this->googleClient);

        // Get free/busy information
        $freeBusyRequest = new \Google\Service\Calendar\FreeBusyRequest([
            'timeMin' => $startDate->toRfc3339String(),
            'timeMax' => $endDate->toRfc3339String(),
            'items' => [
                ['id' => 'primary']
            ]
        ]);

        try {
            $freeBusy = $service->freebusy->query($freeBusyRequest);
            $busyTimes = collect();

            if (isset($freeBusy->getCalendars()['primary'])) {
                $calendar = $freeBusy->getCalendars()['primary'];
                foreach ($calendar->getBusy() as $busyPeriod) {
                    $busyTimes->push([
                        'start' => Carbon::parse($busyPeriod->getStart()),
                        'end' => Carbon::parse($busyPeriod->getEnd()),
                        'source' => 'google',
                    ]);
                }
            }

            return $busyTimes;
        } catch (\Exception $e) {
            Log::error('Failed to fetch Google busy times', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Fetch Microsoft Outlook busy times
     */
    protected function fetchOutlookBusyTimes(CalendarConnection $connection, Carbon $startDate, Carbon $endDate): Collection
    {
        $credentials = decrypt($connection->credentials);
        $this->microsoftGraph->setAccessToken($credentials['access_token']);

        try {
            // Get calendar events and filter for busy times
            $events = $this->microsoftGraph
                ->createRequest('GET', "/me/calendar/events?\$filter=start/dateTime ge '{$startDate->toISOString()}' and end/dateTime le '{$endDate->toISOString()}'&\$select=showAs,start,end")
                ->execute();

            $busyTimes = collect();

            foreach ($events->getBody()['value'] as $event) {
                // Only include busy/occupied events
                if (isset($event['showAs']) && in_array($event['showAs'], ['busy', 'oof'])) {
                    $busyTimes->push([
                        'start' => Carbon::parse($event['start']['dateTime']),
                        'end' => Carbon::parse($event['end']['dateTime']),
                        'source' => 'outlook',
                    ]);
                }
            }

            return $busyTimes;
        } catch (\Exception $e) {
            Log::error('Failed to fetch Outlook busy times', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Fetch Apple Calendar busy times using CalDAV
     */
    protected function fetchAppleBusyTimes(CalendarConnection $connection, Carbon $startDate, Carbon $endDate): Collection
    {
        // For Apple Calendar, we can reuse the existing event fetching logic
        // and filter for busy events
        $events = $this->fetchAppleEvents($connection);

        return $events->map(function ($event) {
            return [
                'start' => Carbon::parse($event['start_time']),
                'end' => Carbon::parse($event['end_time']),
                'source' => 'apple',
            ];
        });
    }

    /**
     * Fetch generic CalDAV busy times
     */
    protected function fetchCalDAVBusyTimes(CalendarConnection $connection, Carbon $startDate, Carbon $endDate): Collection
    {
        // For generic CalDAV, we can reuse the existing event fetching logic
        // and filter for busy events
        $events = $this->fetchCalDAVEvents($connection);

        return $events->map(function ($event) {
            return [
                'start' => Carbon::parse($event['start_time']),
                'end' => Carbon::parse($event['end_time']),
                'source' => 'caldav',
            ];
        });
    }

    /**
     * Merge overlapping busy time periods
     */
    protected function mergeOverlappingBusyTimes(Collection $busyTimes): Collection
    {
        if ($busyTimes->isEmpty()) {
            return $busyTimes;
        }

        // Sort busy times by start time
        $sortedBusyTimes = $busyTimes->sortBy('start')->values();

        $merged = collect();
        $current = $sortedBusyTimes->first();

        for ($i = 1; $i < $sortedBusyTimes->count(); $i++) {
            $next = $sortedBusyTimes[$i];

            // Check if current and next overlap or are adjacent
            $currentEnd = Carbon::parse($current['end']);
            $nextStart = Carbon::parse($next['start']);

            if ($currentEnd->gte($nextStart)) {
                // Merge overlapping periods
                $current['end'] = Carbon::parse($current['end'])->max(Carbon::parse($next['end']));
            } else {
                // No overlap, add current to merged and start new current
                $merged->push($current);
                $current = $next;
            }
        }

        // Add the last period
        $merged->push($current);

        return $merged;
    }

    /**
     * Calculate available slots within a time gap
     */
    protected function calculateSlotsInGap(Carbon $gapStart, Carbon $gapEnd, int $durationMinutes): Collection
    {
        $slots = collect();
        $slotDuration = $durationMinutes * 60; // Convert to seconds
        $currentSlotStart = $gapStart->copy();

        while ($currentSlotStart->copy()->addSeconds($slotDuration)->lte($gapEnd)) {
            $slotEnd = $currentSlotStart->copy()->addMinutes($durationMinutes);

            $slots->push([
                'start' => $currentSlotStart->copy(),
                'end' => $slotEnd,
                'duration_minutes' => $durationMinutes,
            ]);

            $currentSlotStart = $slotEnd;
        }

        return $slots;
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
     * Fetch Apple Calendar events using CalDAV protocol
     */
    protected function fetchAppleEvents(CalendarConnection $connection): Collection
    {
        $credentials = decrypt($connection->credentials);

        try {
            // Build CalDAV REPORT request for events
            $startTime = now()->subDays(7)->toISOString();
            $endTime = now()->addDays(30)->toISOString();

            $caldavQuery = $this->buildCalDAVQuery($startTime, $endTime);
            $calendarUrl = $this->getAppleCalendarUrl($credentials);

            $response = \Illuminate\Support\Facades\Http::withBasicAuth(
                $credentials['username'],
                $credentials['password']
            )->withHeaders([
                'Content-Type' => 'application/xml',
                'Depth' => '1',
            ])->send('REPORT', $calendarUrl, [
                'body' => $caldavQuery
            ]);

            if (!$response->successful()) {
                Log::error('Apple CalDAV request failed', [
                    'connection_id' => $connection->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return collect();
            }

            return $this->parseCalDAVResponse($response->body());
        } catch (\Exception $e) {
            Log::error('Failed to fetch Apple Calendar events', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage()
            ]);

            return collect();
        }
    }

    /**
     * Test Apple Calendar connection using CalDAV
     */
    protected function testAppleConnection(CalendarConnection $connection): bool
    {
        $credentials = decrypt($connection->credentials);

        try {
            // Test basic connectivity with PROPFIND request
            $calendarUrl = $this->getAppleCalendarUrl($credentials);

            $response = \Illuminate\Support\Facades\Http::withBasicAuth(
                $credentials['username'],
                $credentials['password']
            )->withHeaders([
                'Content-Type' => 'application/xml',
                'Depth' => '0',
            ])->send('PROPFIND', $calendarUrl, [
                'body' => '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:"><D:prop><D:displayname/></D:prop></D:propfind>'
            ]);

            $success = $response->successful();

            Log::info('Apple Calendar connection test', [
                'connection_id' => $connection->id,
                'success' => $success,
                'status' => $response->status()
            ]);

            return $success;
        } catch (\Exception $e) {
            Log::error('Apple Calendar connection test failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Fetch events from generic CalDAV server
     */
    protected function fetchCalDAVEvents(CalendarConnection $connection): Collection
    {
        $credentials = decrypt($connection->credentials);

        try {
            // Build CalDAV REPORT request for events
            $startTime = now()->subDays(7)->toISOString();
            $endTime = now()->addDays(30)->toISOString();

            $caldavQuery = $this->buildCalDAVQuery($startTime, $endTime);
            $calendarUrl = $this->getCalDAVCalendarUrl($credentials);

            $request = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/xml',
                'Depth' => '1',
            ]);

            // Apply authentication based on credentials
            $request = $this->applyCalDAVAuth($request, $credentials);

            $response = $request->send('REPORT', $calendarUrl, [
                'body' => $caldavQuery
            ]);

            if (!$response->successful()) {
                Log::error('CalDAV request failed', [
                    'connection_id' => $connection->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return collect();
            }

            return $this->parseCalDAVResponse($response->body());
        } catch (\Exception $e) {
            Log::error('Failed to fetch CalDAV events', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage()
            ]);

            return collect();
        }
    }

    /**
     * Test generic CalDAV server connection
     */
    protected function testCalDAVConnection(CalendarConnection $connection): bool
    {
        $credentials = decrypt($connection->credentials);

        try {
            // Test basic connectivity with PROPFIND request
            $calendarUrl = $this->getCalDAVCalendarUrl($credentials);

            $request = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/xml',
                'Depth' => '0',
            ]);

            // Apply authentication based on credentials
            $request = $this->applyCalDAVAuth($request, $credentials);

            $response = $request->send('PROPFIND', $calendarUrl, [
                'body' => '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:"><D:prop><D:displayname/></D:prop></D:propfind>'
            ]);

            $success = $response->successful();

            Log::info('CalDAV connection test', [
                'connection_id' => $connection->id,
                'success' => $success,
                'status' => $response->status()
            ]);

            return $success;
        } catch (\Exception $e) {
            Log::error('CalDAV connection test failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Build CalDAV REPORT query for fetching events
     */
    private function buildCalDAVQuery(string $startTime, string $endTime): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' .
            '<C:calendar-query xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">' .
            '<D:prop><D:getetag/><C:calendar-data/></D:prop>' .
            '<C:filter><C:comp-filter name="VCALENDAR"><C:comp-filter name="VEVENT">' .
            '<C:time-range start="' . $startTime . '" end="' . $endTime . '"/>' .
            '</C:comp-filter></C:comp-filter></C:filter>' .
            '</C:calendar-query>';
    }

    /**
     * Get Apple Calendar URL from credentials
     */
    private function getAppleCalendarUrl(array $credentials): string
    {
        $baseUrl = $credentials['server_url'] ?? 'https://caldav.icloud.com';
        $principalUrl = $credentials['principal_url'] ?? '';

        // If principal URL is provided, construct full calendar URL
        if ($principalUrl) {
            return $baseUrl . $principalUrl . '/calendars/';
        }

        // Default Apple Calendar URL
        return $baseUrl . '/calendars/';
    }

    /**
     * Get generic CalDAV Calendar URL from credentials
     */
    private function getCalDAVCalendarUrl(array $credentials): string
    {
        $baseUrl = $credentials['server_url'] ?? '';
        $calendarUrl = $credentials['calendar_url'] ?? '';
        $principalUrl = $credentials['principal_url'] ?? '';

        // If specific calendar URL is provided, use it
        if ($calendarUrl) {
            return $calendarUrl;
        }

        // If principal URL is provided, construct full calendar URL
        if ($principalUrl) {
            return $baseUrl . $principalUrl . '/calendars/';
        }

        // Default CalDAV calendar URL
        return $baseUrl . '/calendars/';
    }

    /**
     * Apply appropriate authentication to CalDAV request
     */
    private function applyCalDAVAuth($request, array $credentials)
    {
        $authType = $credentials['auth_type'] ?? 'basic';

        switch ($authType) {
            case 'basic':
                return $request->withBasicAuth(
                    $credentials['username'],
                    $credentials['password']
                );

            case 'digest':
                // For digest auth, we'll use basic auth as fallback since Laravel HTTP doesn't support digest directly
                // In production, you might want to use a dedicated CalDAV library
                return $request->withBasicAuth(
                    $credentials['username'],
                    $credentials['password']
                );

            case 'bearer':
                return $request->withToken($credentials['token']);

            default:
                // Default to basic auth
                return $request->withBasicAuth(
                    $credentials['username'],
                    $credentials['password']
                );
        }
    }

    /**
     * Parse CalDAV XML response and extract events
     */
    private function parseCalDAVResponse(string $xmlResponse): Collection
    {
        try {
            $xml = simplexml_load_string($xmlResponse);
            $xml->registerXPathNamespace('D', 'DAV:');
            $xml->registerXPathNamespace('C', 'urn:ietf:params:xml:ns:caldav');

            $events = collect();

            foreach ($xml->xpath('//D:response') as $response) {
                $href = (string) $response->xpath('D:href')[0];
                $calendarData = $response->xpath('D:propstat/D:prop/C:calendar-data');

                if (!empty($calendarData)) {
                    $icalData = (string) $calendarData[0];
                    $parsedEvents = $this->parseICalData($icalData, $href);

                    $events = $events->merge($parsedEvents);
                }
            }

            return $events;
        } catch (\Exception $e) {
            Log::error('Failed to parse CalDAV response', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Parse iCal data from CalDAV response
     */
    private function parseICalData(string $icalData, string $href): Collection
    {
        try {
            // Simple iCal parsing - extract VEVENT components
            $events = collect();

            // Split by BEGIN:VEVENT and END:VEVENT
            $eventBlocks = explode('BEGIN:VEVENT', $icalData);

            foreach ($eventBlocks as $block) {
                if (strpos($block, 'END:VEVENT') === false) {
                    continue;
                }

                $eventData = $this->parseVEvent($block);
                if ($eventData) {
                    $events->push($eventData);
                }
            }

            return $events;
        } catch (\Exception $e) {
            Log::error('Failed to parse iCal data', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Parse individual VEVENT from iCal data
     */
    private function parseVEvent(string $vEventBlock): ?array
    {
        try {
            $lines = explode("\n", trim($vEventBlock));
            $event = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, 'END:VEVENT') === 0) {
                    continue;
                }

                if (strpos($line, 'UID:') === 0) {
                    $event['external_id'] = substr($line, 4);
                } elseif (strpos($line, 'SUMMARY:') === 0) {
                    $event['title'] = substr($line, 8);
                } elseif (strpos($line, 'DESCRIPTION:') === 0) {
                    $event['description'] = substr($line, 12);
                } elseif (strpos($line, 'DTSTART:') === 0) {
                    $event['start_time'] = $this->parseICalDateTime(substr($line, 8));
                } elseif (strpos($line, 'DTEND:') === 0) {
                    $event['end_time'] = $this->parseICalDateTime(substr($line, 8));
                } elseif (strpos($line, 'LOCATION:') === 0) {
                    $event['location'] = substr($line, 9);
                }
            }

            // Only return if we have required fields
            return isset($event['external_id'], $event['title'], $event['start_time'], $event['end_time'])
                ? $event
                : null;
        } catch (\Exception $e) {
            Log::error('Failed to parse VEVENT', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Parse iCal date/time format
     */
    private function parseICalDateTime(string $dateTime): string
    {
        try {
            // Handle different iCal date formats
            $dateTime = trim($dateTime);

            // If it's a date-only format (YYYYMMDD)
            if (strlen($dateTime) === 8 && ctype_digit($dateTime)) {
                $date = \DateTime::createFromFormat('Ymd', $dateTime);
                return $date ? $date->format('Y-m-d H:i:s') : $dateTime;
            }

            // If it's a date-time format (YYYYMMDDTHHMMSSZ or YYYYMMDDTHHMMSS)
            if (strpos($dateTime, 'T') !== false) {
                if (substr($dateTime, -1) === 'Z') {
                    $date = \DateTime::createFromFormat('Ymd\THis\Z', $dateTime);
                } else {
                    $date = \DateTime::createFromFormat('Ymd\THis', $dateTime);
                }
                return $date ? $date->format('Y-m-d H:i:s') : $dateTime;
            }

            return $dateTime;
        } catch (\Exception $e) {
            Log::error('Failed to parse iCal date/time', ['datetime' => $dateTime, 'error' => $e->getMessage()]);
            return $dateTime;
        }
    }

    /**
     * Test Microsoft Outlook connection
     */
    protected function testOutlookConnection(CalendarConnection $connection): bool
    {
        try {
            $credentials = decrypt($connection->credentials);
            $this->microsoftGraph->setAccessToken($credentials['access_token']);

            // Test connectivity with a lightweight Microsoft Graph API call
            // Get user profile to validate token and permissions
            $user = $this->microsoftGraph
                ->createRequest('GET', '/me?$select=id,displayName,userPrincipalName')
                ->execute();

            $userData = $user->getBody();

            // Verify we got the expected user data
            $success = isset($userData['id']) && isset($userData['displayName']);

            Log::info('Outlook connection test', [
                'connection_id' => $connection->id,
                'success' => $success,
                'user_id' => $userData['id'] ?? null,
                'user_email' => $userData['userPrincipalName'] ?? null
            ]);

            return $success;
        } catch (\Exception $e) {
            Log::error('Outlook connection test failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Test Google Calendar connection
     */
    protected function testGoogleConnection(CalendarConnection $connection): bool
    {
        try {
            $credentials = decrypt($connection->credentials);
            $this->googleClient->setAccessToken($credentials);

            // Check if token is expired and refresh if needed
            if ($this->googleClient->isAccessTokenExpired()) {
                $this->refreshGoogleToken($connection);
                // Re-decrypt credentials after refresh
                $credentials = decrypt($connection->credentials);
                $this->googleClient->setAccessToken($credentials);
            }

            // Test connectivity by attempting to get calendar list
            $service = new GoogleCalendar($this->googleClient);
            $calendarList = $service->calendarList->listCalendarList([
                'maxResults' => 1,
                'fields' => 'items(id,summary,primary)'
            ]);

            $success = !empty($calendarList->getItems());

            Log::info('Google Calendar connection test', [
                'connection_id' => $connection->id,
                'success' => $success,
                'calendars_found' => count($calendarList->getItems())
            ]);

            return $success;
        } catch (\Exception $e) {
            Log::error('Google Calendar connection test failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Refresh Google OAuth token
     */
    protected function refreshGoogleToken(CalendarConnection $connection): void
    {
        try {
            $credentials = decrypt($connection->credentials);

            // Check if we have a refresh token
            if (!isset($credentials['refresh_token'])) {
                Log::error('No refresh token available for Google Calendar connection', [
                    'connection_id' => $connection->id,
                ]);
                throw new \Exception('Refresh token not available');
            }

            $this->googleClient->setAccessToken($credentials);

            // Attempt to refresh the token
            if (!$this->googleClient->fetchAccessTokenWithRefreshToken($credentials['refresh_token'])) {
                Log::error('Failed to refresh Google Calendar token', [
                    'connection_id' => $connection->id,
                ]);
                throw new \Exception('Token refresh failed');
            }

            // Get the new access token and update credentials
            $newToken = $this->googleClient->getAccessToken();

            // Merge new token data with existing credentials
            $updatedCredentials = array_merge($credentials, [
                'access_token' => $newToken['access_token'],
                'expires_in' => $newToken['expires_in'] ?? null,
                'created' => $newToken['created'] ?? time(),
            ]);

            // Update the connection with new credentials
            $connection->update([
                'credentials' => encrypt($updatedCredentials),
            ]);

            Log::info('Google Calendar token refreshed successfully', [
                'connection_id' => $connection->id,
                'expires_at' => isset($newToken['created']) && isset($newToken['expires_in'])
                    ? date('Y-m-d H:i:s', $newToken['created'] + $newToken['expires_in'])
                    : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Google Calendar token refresh failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            // Mark connection as inactive if refresh fails
            $connection->update([
                'is_active' => false,
                'sync_error' => 'Token refresh failed: ' . $e->getMessage(),
            ]);

            throw $e;
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
     * Create or update event from external calendar data
     */
    protected function createOrUpdateEvent(CalendarConnection $connection, array $externalEvent): Event
    {
        try {
            // Check if event already exists based on external ID mapping
            $existingEvent = Event::where('user_id', $connection->user_id)
                ->whereJsonContains('external_calendar_ids', [
                    $connection->provider => $externalEvent['external_id']
                ])
                ->first();

            $eventData = [
                'user_id' => $connection->user_id,
                'title' => $externalEvent['title'],
                'description' => $externalEvent['description'] ?? null,
                'start_date' => Carbon::parse($externalEvent['start_time']),
                'end_date' => Carbon::parse($externalEvent['end_time']),
                'location' => $externalEvent['location'] ?? null,
                'is_virtual' => false,
                'attendees' => [],
                'event_type' => 'calendar_sync',
            ];

            if ($existingEvent) {
                // Update existing event
                $existingEvent->update($eventData);
                $event = $existingEvent->fresh();
                Log::info('Updated existing event from external calendar', [
                    'event_id' => $event->id,
                    'external_id' => $externalEvent['external_id'],
                    'provider' => $connection->provider,
                ]);
            } else {
                // Create new event
                $event = Event::create($eventData);

                // Initialize external calendar IDs mapping
                $externalIds = $event->external_calendar_ids ?? [];
                $externalIds[$connection->provider] = $externalEvent['external_id'];
                $event->update(['external_calendar_ids' => $externalIds]);

                Log::info('Created new event from external calendar', [
                    'event_id' => $event->id,
                    'external_id' => $externalEvent['external_id'],
                    'provider' => $connection->provider,
                ]);
            }

            return $event;
        } catch (\Exception $e) {
            Log::error('Failed to create or update event from external calendar', [
                'connection_id' => $connection->id,
                'external_id' => $externalEvent['external_id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync platform event to all connected external calendars
     */
    protected function syncEventToExternalCalendars(Event $event): void
    {
        try {
            $connections = $event->user->calendarConnections()->active()->get();

            foreach ($connections as $connection) {
                try {
                    $this->createEventInExternalCalendar($connection, $event);
                    Log::info('Synced event to external calendar', [
                        'event_id' => $event->id,
                        'connection_id' => $connection->id,
                        'provider' => $connection->provider,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to sync event to external calendar', [
                        'event_id' => $event->id,
                        'connection_id' => $connection->id,
                        'provider' => $connection->provider,
                        'error' => $e->getMessage(),
                    ]);

                    // Continue with other connections even if one fails
                    continue;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync event to external calendars', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create event in specific user's connected calendars
     */
    protected function createEventInUserCalendar(User $user, Event $event): void
    {
        try {
            $connections = $user->calendarConnections()->active()->get();

            foreach ($connections as $connection) {
                try {
                    $externalId = $this->createEventInExternalCalendar($connection, $event);

                    // Update event's external calendar IDs mapping
                    $externalIds = $event->external_calendar_ids ?? [];
                    if ($externalId) {
                        $externalIds[$connection->provider] = $externalId;
                        $event->update(['external_calendar_ids' => $externalIds]);
                    }

                    Log::info('Created event in user calendar', [
                        'event_id' => $event->id,
                        'user_id' => $user->id,
                        'connection_id' => $connection->id,
                        'provider' => $connection->provider,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to create event in user calendar', [
                        'event_id' => $event->id,
                        'user_id' => $user->id,
                        'connection_id' => $connection->id,
                        'provider' => $connection->provider,
                        'error' => $e->getMessage(),
                    ]);

                    // Continue with other connections even if one fails
                    continue;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to create event in user calendars', [
                'event_id' => $event->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create event in external calendar provider
     */
    private function createEventInExternalCalendar(CalendarConnection $connection, Event $event): string
    {
        $eventData = [
            'title' => $event->title,
            'description' => $event->description,
            'start_time' => $event->start_date->toISOString(),
            'end_time' => $event->end_date->toISOString(),
            'location' => $event->location,
        ];

        return match ($connection->provider) {
            'google' => $this->createGoogleEvent($connection, $eventData),
            'outlook' => $this->createOutlookEvent($connection, $eventData),
            'apple' => $this->createAppleEvent($connection, $eventData),
            'caldav' => $this->createCalDAVEvent($connection, $eventData),
            default => throw new \InvalidArgumentException("Unsupported provider: {$connection->provider}"),
        };
    }

    /**
     * Get external event ID after creation
     */
    private function getExternalEventId($response, string $provider): string
    {
        try {
            switch ($provider) {
                case 'google':
                    // Google Calendar API returns the event ID in the response
                    if (is_array($response) && isset($response['id'])) {
                        return $response['id'];
                    }
                    if (is_object($response) && method_exists($response, 'getId')) {
                        return $response->getId();
                    }
                    break;

                case 'outlook':
                    // Microsoft Graph API returns the event ID in the response
                    if (is_array($response) && isset($response['id'])) {
                        return $response['id'];
                    }
                    if (is_object($response) && isset($response->id)) {
                        return $response->id;
                    }
                    break;

                case 'apple':
                case 'caldav':
                    // For CalDAV, we generate and return the UID we created
                    if (is_string($response)) {
                        return $response;
                    }
                    break;

                default:
                    Log::warning('Unknown provider for external event ID extraction', [
                        'provider' => $provider,
                        'response_type' => gettype($response),
                    ]);
                    break;
            }

            Log::warning('Could not extract external event ID from response', [
                'provider' => $provider,
                'response_type' => gettype($response),
            ]);

            return '';
        } catch (\Exception $e) {
            Log::error('Failed to extract external event ID', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Create Google Calendar event
     */
    private function createGoogleEvent(CalendarConnection $connection, array $eventData): string
    {
        $credentials = decrypt($connection->credentials);
        $this->googleClient->setAccessToken($credentials);

        if ($this->googleClient->isAccessTokenExpired()) {
            $this->refreshGoogleToken($connection);
        }

        $service = new GoogleCalendar($this->googleClient);

        // Create Google Calendar event object
        $googleEvent = new \Google\Service\Calendar\Event([
            'summary' => $eventData['title'],
            'description' => $eventData['description'],
            'start' => [
                'dateTime' => $eventData['start_time'],
                'timeZone' => config('app.timezone', 'UTC'),
            ],
            'end' => [
                'dateTime' => $eventData['end_time'],
                'timeZone' => config('app.timezone', 'UTC'),
            ],
            'location' => $eventData['location'],
        ]);

        $createdEvent = $service->events->insert('primary', $googleEvent);

        return $createdEvent->getId();
    }

    /**
     * Create Microsoft Outlook event
     */
    private function createOutlookEvent(CalendarConnection $connection, array $eventData): string
    {
        $credentials = decrypt($connection->credentials);
        $this->microsoftGraph->setAccessToken($credentials['access_token']);

        $eventPayload = [
            'subject' => $eventData['title'],
            'body' => [
                'contentType' => 'text',
                'content' => $eventData['description'] ?? '',
            ],
            'start' => [
                'dateTime' => Carbon::parse($eventData['start_time'])->format('Y-m-d\TH:i:s'),
                'timeZone' => config('app.timezone', 'UTC'),
            ],
            'end' => [
                'dateTime' => Carbon::parse($eventData['end_time'])->format('Y-m-d\TH:i:s'),
                'timeZone' => config('app.timezone', 'UTC'),
            ],
            'location' => [
                'displayName' => $eventData['location'] ?? '',
            ],
        ];

        $response = $this->microsoftGraph->createRequest('POST', '/me/events')
            ->attachBody($eventPayload)
            ->execute();

        return $response->getBody()['id'];
    }

    /**
     * Create Apple Calendar event using CalDAV
     */
    private function createAppleEvent(CalendarConnection $connection, array $eventData): string
    {
        $credentials = decrypt($connection->credentials);

        try {
            // Generate unique event UID
            $eventUid = uniqid('alumate-', true) . '@' . parse_url($credentials['server_url'] ?? 'https://caldav.icloud.com', PHP_URL_HOST);

            // Build iCal event data
            $icalData = $this->buildICalEvent($eventData, $eventUid);

            // Get calendar URL for Apple
            $calendarUrl = $this->getAppleCalendarUrl($credentials);

            // Create the event using CalDAV PUT request
            $response = \Illuminate\Support\Facades\Http::withBasicAuth(
                $credentials['username'],
                $credentials['password']
            )->withHeaders([
                'Content-Type' => 'text/calendar; charset=utf-8',
                'If-None-Match' => '*',
            ])->put($calendarUrl . $eventUid . '.ics', $icalData);

            if (!$response->successful()) {
                Log::error('Apple CalDAV event creation failed', [
                    'connection_id' => $connection->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'event_title' => $eventData['title'],
                ]);
                throw new \Exception('Failed to create Apple Calendar event: ' . $response->status());
            }

            Log::info('Apple Calendar event created successfully', [
                'connection_id' => $connection->id,
                'event_uid' => $eventUid,
                'event_title' => $eventData['title'],
            ]);

            return $eventUid;
        } catch (\Exception $e) {
            Log::error('Failed to create Apple Calendar event', [
                'connection_id' => $connection->id,
                'event_title' => $eventData['title'],
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create generic CalDAV event
     */
    private function createCalDAVEvent(CalendarConnection $connection, array $eventData): string
    {
        $credentials = decrypt($connection->credentials);

        try {
            // Generate unique event UID
            $eventUid = uniqid('alumate-', true) . '@' . parse_url($credentials['server_url'] ?? '', PHP_URL_HOST);

            // Build iCal event data
            $icalData = $this->buildICalEvent($eventData, $eventUid);

            // Get calendar URL for generic CalDAV
            $calendarUrl = $this->getCalDAVCalendarUrl($credentials);

            // Create HTTP request with appropriate authentication
            $request = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'text/calendar; charset=utf-8',
                'If-None-Match' => '*',
            ]);

            // Apply authentication based on credentials
            $request = $this->applyCalDAVAuth($request, $credentials);

            // Create the event using CalDAV PUT request
            $response = $request->put($calendarUrl . $eventUid . '.ics', $icalData);

            if (!$response->successful()) {
                Log::error('CalDAV event creation failed', [
                    'connection_id' => $connection->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'event_title' => $eventData['title'],
                ]);
                throw new \Exception('Failed to create CalDAV event: ' . $response->status());
            }

            Log::info('CalDAV event created successfully', [
                'connection_id' => $connection->id,
                'event_uid' => $eventUid,
                'event_title' => $eventData['title'],
            ]);

            return $eventUid;
        } catch (\Exception $e) {
            Log::error('Failed to create CalDAV event', [
                'connection_id' => $connection->id,
                'event_title' => $eventData['title'],
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build iCal event data for CalDAV requests
     */
    private function buildICalEvent(array $eventData, string $eventUid): string
    {
        $startTime = Carbon::parse($eventData['start_time']);
        $endTime = Carbon::parse($eventData['end_time']);

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Alumate//Calendar Integration//EN\r\n";
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:{$eventUid}\r\n";
        $ical .= "DTSTAMP:" . now()->format('Ymd\THis\Z') . "\r\n";
        $ical .= "DTSTART:" . $startTime->format('Ymd\THis\Z') . "\r\n";
        $ical .= "DTEND:" . $endTime->format('Ymd\THis\Z') . "\r\n";

        if (!empty($eventData['title'])) {
            $ical .= "SUMMARY:" . $this->escapeICalText($eventData['title']) . "\r\n";
        }

        if (!empty($eventData['description'])) {
            $ical .= "DESCRIPTION:" . $this->escapeICalText($eventData['description']) . "\r\n";
        }

        if (!empty($eventData['location'])) {
            $ical .= "LOCATION:" . $this->escapeICalText($eventData['location']) . "\r\n";
        }

        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";

        return $ical;
    }

    /**
     * Escape special characters for iCal format
     */
    private function escapeICalText(string $text): string
    {
        // Escape commas, semicolons, and backslashes
        return str_replace(
            [',', ';', '\\'],
            ['\\,', '\\;', '\\\\'],
            $text
        );
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
     * - sendEmailInvite()
     * - fetchBusyTimes()
     * - findSlotsInDay()
     */
}
