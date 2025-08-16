<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarConnection;
use App\Models\Event;
use App\Services\CalendarIntegrationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CalendarSyncController extends Controller
{
    public function __construct(
        protected CalendarIntegrationService $calendarService
    ) {}

    /**
     * Get user's calendar connections
     */
    public function index(): JsonResponse
    {
        $connections = Auth::user()->calendarConnections()
            ->select(['id', 'provider', 'is_active', 'last_sync_at', 'sync_status'])
            ->get();

        return response()->json([
            'connections' => $connections,
        ]);
    }

    /**
     * Connect a calendar provider
     */
    public function connect(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|in:google,outlook,apple,caldav',
            'credentials' => 'required|array',
            'credentials.access_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $connection = $this->calendarService->connectCalendar(
                Auth::user(),
                $request->provider,
                $request->credentials
            );

            return response()->json([
                'message' => 'Calendar connected successfully',
                'connection' => [
                    'id' => $connection->id,
                    'provider' => $connection->provider,
                    'is_active' => $connection->is_active,
                    'last_sync_at' => $connection->last_sync_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to connect calendar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disconnect a calendar provider
     */
    public function disconnect(CalendarConnection $connection): JsonResponse
    {
        $this->authorize('update', $connection);

        $connection->update(['is_active' => false]);

        return response()->json([
            'message' => 'Calendar disconnected successfully',
        ]);
    }

    /**
     * Sync calendar events
     */
    public function sync(CalendarConnection $connection): JsonResponse
    {
        $this->authorize('update', $connection);

        $success = $this->calendarService->syncCalendar($connection);

        if ($success) {
            return response()->json([
                'message' => 'Calendar synced successfully',
                'last_sync_at' => $connection->fresh()->last_sync_at,
            ]);
        }

        return response()->json([
            'message' => 'Calendar sync failed',
            'error' => $connection->fresh()->sync_error,
        ], 500);
    }

    /**
     * Get user's availability
     */
    public function availability(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'user_id' => 'sometimes|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user_id
            ? \App\Models\User::findOrFail($request->user_id)
            : Auth::user();

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $availability = $this->calendarService->getUserAvailability($user, $startDate, $endDate);

        return response()->json([
            'availability' => $availability,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ]);
    }

    /**
     * Find available time slots for multiple users
     */
    public function findSlots(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'duration_minutes' => 'sometimes|integer|min:15|max:480',
            'working_hours' => 'sometimes|array|size:2',
            'working_hours.*' => 'date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $users = \App\Models\User::whereIn('id', $request->user_ids)->get();
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $durationMinutes = $request->duration_minutes ?? 60;
        $workingHours = $request->working_hours ?? ['09:00', '17:00'];

        $availableSlots = $this->calendarService->findAvailableSlots(
            $users,
            $startDate,
            $endDate,
            $durationMinutes,
            $workingHours
        );

        return response()->json([
            'available_slots' => $availableSlots,
            'parameters' => [
                'users' => $users->pluck('name', 'id'),
                'duration_minutes' => $durationMinutes,
                'working_hours' => $workingHours,
            ],
        ]);
    }

    /**
     * Create a calendar event
     */
    public function createEvent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location' => 'sometimes|string|max:255',
            'is_virtual' => 'sometimes|boolean',
            'meeting_url' => 'sometimes|url',
            'attendees' => 'sometimes|array',
            'attendees.*' => 'email',
            'event_type' => 'sometimes|string|in:general,mentorship,networking,reunion',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $event = $this->calendarService->createEvent(Auth::user(), $request->all());

            return response()->json([
                'message' => 'Event created successfully',
                'event' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'location' => $event->location,
                    'is_virtual' => $event->is_virtual,
                    'meeting_url' => $event->meeting_url,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create event',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Schedule a mentorship session
     */
    public function scheduleMentorship(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mentor_id' => 'required|exists:users,id',
            'mentee_id' => 'required|exists:users,id',
            'start_time' => 'required|date|after:now',
            'duration_minutes' => 'sometimes|integer|min:15|max:240',
            'topic' => 'sometimes|string|max:255',
            'notes' => 'sometimes|string|max:1000',
            'meeting_url' => 'sometimes|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $mentor = \App\Models\User::findOrFail($request->mentor_id);
            $mentee = \App\Models\User::findOrFail($request->mentee_id);
            $startTime = Carbon::parse($request->start_time);
            $durationMinutes = $request->duration_minutes ?? 60;

            // Check if user can schedule this session
            if (Auth::id() !== $mentor->id && Auth::id() !== $mentee->id) {
                return response()->json([
                    'message' => 'Unauthorized to schedule this session',
                ], 403);
            }

            $session = $this->calendarService->scheduleMentorshipSession(
                $mentor,
                $mentee,
                $startTime,
                $durationMinutes,
                $request->only(['topic', 'notes', 'meeting_url'])
            );

            return response()->json([
                'message' => 'Mentorship session scheduled successfully',
                'session' => [
                    'id' => $session->id,
                    'mentor' => $mentor->name,
                    'mentee' => $mentee->name,
                    'scheduled_at' => $session->scheduled_at,
                    'duration_minutes' => $session->duration_minutes,
                    'topic' => $session->topic,
                    'meeting_url' => $session->meeting_url,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to schedule mentorship session',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send calendar invites for an event
     */
    public function sendInvites(Event $event): JsonResponse
    {
        $this->authorize('update', $event);

        try {
            $this->calendarService->sendCalendarInvites($event);

            return response()->json([
                'message' => 'Calendar invites sent successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send calendar invites',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get calendar sync status
     */
    public function syncStatus(): JsonResponse
    {
        $connections = Auth::user()->calendarConnections()
            ->select(['id', 'provider', 'is_active', 'last_sync_at', 'sync_status', 'sync_error'])
            ->get();

        $totalConnections = $connections->count();
        $activeConnections = $connections->where('is_active', true)->count();
        $failedSyncs = $connections->where('sync_status', 'failed')->count();

        return response()->json([
            'summary' => [
                'total_connections' => $totalConnections,
                'active_connections' => $activeConnections,
                'failed_syncs' => $failedSyncs,
                'last_sync' => $connections->max('last_sync_at'),
            ],
            'connections' => $connections,
        ]);
    }
}
