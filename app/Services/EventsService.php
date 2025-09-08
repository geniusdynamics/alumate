<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventCheckIn;
use App\Models\EventRegistration;
use App\Models\User;
use App\Models\Institution;
use App\Models\Circle;
use App\Models\Group;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

class EventsService extends BaseService
{
    protected JitsiMeetService $jitsiMeetService;

    public function __construct(JitsiMeetService $jitsiMeetService)
    {
        $this->jitsiMeetService = $jitsiMeetService;
    }

    public function getEventsForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Event::published()
            ->with(['organizer', 'institution'])
            ->where(function ($q) use ($user) {
                $q->where('visibility', 'public')
                    ->orWhere(function ($subQ) use ($user) {
                        $subQ->where('visibility', 'alumni_only')
                            ->where(function ($roleQ) use ($user) {
                                $roleQ->whereHas('organizer', function ($orgQ) use ($user) {
                                    // User can see events from their circles/groups
                                    $orgQ->whereIn('id', $this->getUserNetworkIds($user));
                                });
                            });
                    })
                    ->orWhere(function ($subQ) use ($user) {
                        $subQ->where('visibility', 'institution_only')
                            ->where('institution_id', $user->institution_id);
                    })
                    ->orWhere('organizer_id', $user->id);
            });

        // Apply filters
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['format'])) {
            $query->where('format', $filters['format']);
        }

        if (! empty($filters['date_range'])) {
            $this->applyDateFilter($query, $filters['date_range']);
        }

        if (! empty($filters['location']) && ! empty($filters['radius'])) {
            $location = $filters['location'];
            $radius = $filters['radius'];
            $query->nearLocation($location['lat'], $location['lng'], $radius);
        }

        if (! empty($filters['tags'])) {
            $query->whereJsonContains('tags', $filters['tags']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%")
                    ->orWhere('venue_name', 'ILIKE', "%{$search}%");
            });
        }

        // Default ordering
        $query->orderBy('start_date', 'asc');

        return $query->paginate($perPage);
    }

    public function createEvent(array $data, User $organizer): Event
    {
        return DB::transaction(function () use ($data, $organizer) {
            $event = Event::create([
                ...$data,
                'organizer_id' => $organizer->id,
                'current_attendees' => 0,
            ]);

            // Set up virtual meeting if needed
            if (in_array($event->format, ['virtual', 'hybrid'])) {
                $this->setupVirtualMeeting($event, $data);
            }

            // Auto-register organizer if specified
            if ($data['auto_register_organizer'] ?? false) {
                $this->registerUserForEvent($event, $organizer);
            }

            return $event->load(['organizer', 'institution']);
        });
    }

    public function updateEvent(Event $event, array $data): Event
    {
        $event->update($data);

        return $event->load(['organizer', 'institution']);
    }

    public function registerUserForEvent(Event $event, User $user, array $registrationData = []): EventRegistration
    {
        // Check if user is already registered
        if ($event->isUserRegistered($user)) {
            throw new \Exception('User is already registered for this event.');
        }

        // Check capacity
        $totalAttendees = 1 + ($registrationData['guests_count'] ?? 0);
        if (! $event->hasCapacity() || $event->getAvailableSpots() < $totalAttendees) {
            // Add to waitlist if capacity is full
            $status = 'waitlisted';
        } else {
            $status = $event->requires_approval ? 'pending' : 'registered';
        }

        return DB::transaction(function () use ($event, $user, $registrationData, $status) {
            $registration = EventRegistration::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'status' => $status,
                'registered_at' => now(),
                'guests_count' => $registrationData['guests_count'] ?? 0,
                'guest_details' => $registrationData['guest_details'] ?? null,
                'special_requirements' => $registrationData['special_requirements'] ?? null,
                'registration_data' => $registrationData['additional_data'] ?? null,
            ]);

            // Update event attendee count
            $event->updateAttendeeCount();

            return $registration;
        });
    }

    public function cancelRegistration(Event $event, User $user, ?string $reason = null): void
    {
        $registration = $event->getUserRegistration($user);

        if (! $registration || ! $registration->canCancel()) {
            throw new \Exception('Cannot cancel this registration.');
        }

        DB::transaction(function () use ($registration, $reason, $event) {
            $registration->cancel($reason);

            // If there's a waitlist, promote the next person
            $this->promoteFromWaitlist($event);
        });
    }

    public function checkInUser(Event $event, User $user, array $checkInData = []): EventCheckIn
    {
        $registration = $event->getUserRegistration($user);

        if (! $registration || ! $registration->canCheckIn()) {
            throw new \Exception('User cannot check in for this event.');
        }

        return DB::transaction(function () use ($event, $user, $registration, $checkInData) {
            // Create check-in record
            $checkIn = EventCheckIn::create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'checked_in_at' => now(),
                'check_in_method' => $checkInData['method'] ?? 'manual',
                'location_data' => $checkInData['location'] ?? null,
                'notes' => $checkInData['notes'] ?? null,
            ]);

            // Update registration status
            $registration->checkIn();

            return $checkIn;
        });
    }

    public function getEventAttendees(Event $event, string $status = 'all'): Collection
    {
        $query = $event->registrations()->with('user');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function getEventAnalytics(Event $event): array
    {
        $registrations = $event->registrations;
        $checkIns = $event->checkIns;

        return [
            'total_registered' => $registrations->where('status', 'registered')->count(),
            'total_waitlisted' => $registrations->where('status', 'waitlisted')->count(),
            'total_cancelled' => $registrations->where('status', 'cancelled')->count(),
            'total_attended' => $registrations->where('status', 'attended')->count(),
            'total_no_show' => $registrations->where('status', 'no_show')->count(),
            'total_guests' => $registrations->sum('guests_count'),
            'check_in_rate' => $registrations->where('status', 'registered')->count() > 0
                ? ($checkIns->count() / $registrations->where('status', 'registered')->count()) * 100
                : 0,
            'capacity_utilization' => $event->max_capacity
                ? ($event->current_attendees / $event->max_capacity) * 100
                : null,
            'registration_timeline' => $this->getRegistrationTimeline($event),
        ];
    }

    public function getUpcomingEventsForUser(User $user, int $limit = 5): Collection
    {
        return Event::published()
            ->upcoming()
            ->where(function ($q) use ($user) {
                $q->whereHas('registrations', function ($regQ) use ($user) {
                    $regQ->where('user_id', $user->id)
                        ->whereIn('status', ['registered', 'waitlisted']);
                });
            })
            ->with(['organizer', 'institution'])
            ->orderBy('start_date')
            ->limit($limit)
            ->get();
    }

    public function getRecommendedEvents(User $user, int $limit = 10): Collection
    {
        // Get events based on user's interests, location, and network
        return Event::published()
            ->upcoming()
            ->where(function ($q) use ($user) {
                // Events from user's institution
                $q->where('institution_id', $user->institution_id)
                  // Events in user's location (if available)
                    ->orWhere(function ($locQ) use ($user) {
                        if ($user->location) {
                            // This would need geocoding to work properly
                            $locQ->where('venue_address', 'ILIKE', "%{$user->location}%");
                        }
                    })
                  // Events from user's network
                    ->orWhereIn('organizer_id', $this->getUserNetworkIds($user));
            })
            ->whereDoesntHave('registrations', function ($regQ) use ($user) {
                $regQ->where('user_id', $user->id);
            })
            ->with(['organizer', 'institution'])
            ->orderBy('start_date')
            ->limit($limit)
            ->get();
    }

    private function applyDateFilter($query, string $dateRange): void
    {
        $now = now();

        switch ($dateRange) {
            case 'today':
                $query->whereDate('start_date', $now->toDateString());
                break;
            case 'tomorrow':
                $query->whereDate('start_date', $now->addDay()->toDateString());
                break;
            case 'this_week':
                $query->whereBetween('start_date', [
                    $now->startOfWeek(),
                    $now->endOfWeek(),
                ]);
                break;
            case 'next_week':
                $query->whereBetween('start_date', [
                    $now->addWeek()->startOfWeek(),
                    $now->endOfWeek(),
                ]);
                break;
            case 'this_month':
                $query->whereBetween('start_date', [
                    $now->startOfMonth(),
                    $now->endOfMonth(),
                ]);
                break;
            case 'next_month':
                $query->whereBetween('start_date', [
                    $now->addMonth()->startOfMonth(),
                    $now->endOfMonth(),
                ]);
                break;
        }
    }

    private function promoteFromWaitlist(Event $event): void
    {
        if (! $event->hasCapacity()) {
            return;
        }

        $waitlistedRegistration = $event->registrations()
            ->where('status', 'waitlisted')
            ->orderBy('registered_at')
            ->first();

        if ($waitlistedRegistration) {
            $waitlistedRegistration->update(['status' => 'registered']);
            $event->updateAttendeeCount();
        }
    }

    private function getRegistrationTimeline(Event $event): array
    {
        return $event->registrations()
            ->selectRaw('DATE(registered_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getUserNetworkIds(User $user): array
    {
        // This would get IDs of users in the same circles/groups
        // For now, return empty array - would need to implement based on circles/groups
        return [];
    }

    /**
     * Set up virtual meeting for an event
     */
    private function setupVirtualMeeting(Event $event, array $data): void
    {
        $meetingPlatform = $data['meeting_platform'] ?? 'jitsi';

        if ($meetingPlatform === 'jitsi') {
            // Auto-create Jitsi Meet room
            $meetingData = $this->jitsiMeetService->createMeeting($event->id, $event->title);

            $event->update([
                'jitsi_room_id' => $meetingData['room_id'],
                'meeting_url' => $meetingData['meeting_url'],
                'meeting_platform' => 'jitsi',
                'jitsi_config' => $meetingData['config'],
                'meeting_embed_allowed' => true,
            ]);
        } else {
            // Manual meeting URL setup
            $event->update([
                'meeting_platform' => $meetingPlatform,
                'meeting_url' => $data['meeting_url'] ?? null,
                'meeting_password' => $data['meeting_password'] ?? null,
                'meeting_instructions' => $data['meeting_instructions'] ?? null,
                'meeting_embed_allowed' => false,
            ]);
        }
    }

    /**
     * Update virtual meeting settings for an event
     */
    public function updateVirtualMeetingSettings(Event $event, array $settings): Event
    {
        if ($event->meeting_platform === 'jitsi') {
            // Update Jitsi configuration
            $currentConfig = $event->jitsi_config ?? [];
            $newConfig = array_merge($currentConfig, $settings);

            $event->update([
                'jitsi_config' => $newConfig,
                'waiting_room_enabled' => $settings['waiting_room_enabled'] ?? $event->waiting_room_enabled,
                'chat_enabled' => $settings['chat_enabled'] ?? $event->chat_enabled,
                'screen_sharing_enabled' => $settings['screen_sharing_enabled'] ?? $event->screen_sharing_enabled,
                'recording_enabled' => $settings['recording_enabled'] ?? $event->recording_enabled,
            ]);
        } else {
            // Update manual meeting settings
            $event->update(array_intersect_key($settings, array_flip([
                'meeting_url',
                'meeting_password',
                'meeting_instructions',
                'waiting_room_enabled',
                'chat_enabled',
                'screen_sharing_enabled',
                'recording_enabled',
            ])));
        }

        return $event->fresh();
    }

    /**
     * Get meeting credentials for an event
     */
    public function getMeetingCredentials(Event $event): array
    {
        if (! $event->isVirtual()) {
            return [];
        }

        return $this->jitsiMeetService->generateMeetingCredentials($event);
    }

    /**
     * Validate a meeting URL
     */
    public function validateMeetingUrl(string $url): array
    {
        return $this->jitsiMeetService->validateMeetingUrl($url);
    }

    /**
     * Extract meeting details from a URL
     */
    public function extractMeetingDetails(string $url): array
    {
        return $this->jitsiMeetService->extractMeetingDetails($url);
    }
}
