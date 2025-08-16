<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\EventsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventsController extends Controller
{
    public function __construct(
        private EventsService $eventsService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'type', 'format', 'date_range', 'location',
            'radius', 'tags', 'search',
        ]);

        $events = $this->eventsService->getEventsForUser(
            $request->user(),
            $filters,
            $request->get('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'data' => $events->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    public function show(Event $event, Request $request): JsonResponse
    {
        if (! $event->canUserView($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this event.',
            ], 403);
        }

        $event->load(['organizer', 'institution']);

        // Add user-specific data
        $userData = [
            'is_registered' => $event->isUserRegistered($request->user()),
            'registration' => $event->getUserRegistration($request->user()),
            'is_checked_in' => $event->isUserCheckedIn($request->user()),
            'can_edit' => $event->canUserEdit($request->user()),
        ];

        return response()->json([
            'success' => true,
            'data' => array_merge($event->toArray(), ['user_data' => $userData]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'type' => 'required|in:networking,reunion,webinar,workshop,social,professional,fundraising,other',
            'format' => 'required|in:in_person,virtual,hybrid',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'timezone' => 'required|string|max:50',
            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string',
            'virtual_link' => 'nullable|url',
            'virtual_instructions' => 'nullable|string',
            'max_capacity' => 'nullable|integer|min:1',
            'requires_approval' => 'boolean',
            'ticket_price' => 'nullable|numeric|min:0',
            'registration_deadline' => 'nullable|date|before:start_date',
            'institution_id' => 'nullable|exists:institutions,id',
            'visibility' => 'required|in:public,alumni_only,institution_only,private',
            'allow_guests' => 'boolean',
            'max_guests_per_attendee' => 'nullable|integer|min:0',
            'enable_networking' => 'boolean',
            'enable_checkin' => 'boolean',
            'tags' => 'nullable|array',
            'media_urls' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $event = $this->eventsService->createEvent(
                $validator->validated(),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Event created successfully',
                'data' => $event,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create event: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(Event $event, Request $request): JsonResponse
    {
        if (! $event->canUserEdit($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit this event.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'short_description' => 'nullable|string|max:500',
            'type' => 'sometimes|required|in:networking,reunion,webinar,workshop,social,professional,fundraising,other',
            'format' => 'sometimes|required|in:in_person,virtual,hybrid',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'timezone' => 'sometimes|required|string|max:50',
            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string',
            'virtual_link' => 'nullable|url',
            'virtual_instructions' => 'nullable|string',
            'max_capacity' => 'nullable|integer|min:1',
            'requires_approval' => 'boolean',
            'ticket_price' => 'nullable|numeric|min:0',
            'registration_deadline' => 'nullable|date',
            'visibility' => 'sometimes|required|in:public,alumni_only,institution_only,private',
            'allow_guests' => 'boolean',
            'max_guests_per_attendee' => 'nullable|integer|min:0',
            'enable_networking' => 'boolean',
            'enable_checkin' => 'boolean',
            'tags' => 'nullable|array',
            'media_urls' => 'nullable|array',
            'status' => 'sometimes|in:draft,published,cancelled,completed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updatedEvent = $this->eventsService->updateEvent(
                $event,
                $validator->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Event updated successfully',
                'data' => $updatedEvent,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update event: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Event $event, Request $request): JsonResponse
    {
        if (! $event->canUserEdit($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this event.',
            ], 403);
        }

        try {
            $event->delete();

            return response()->json([
                'success' => true,
                'message' => 'Event deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete event: '.$e->getMessage(),
            ], 500);
        }
    }

    public function register(Event $event, Request $request): JsonResponse
    {
        if (! $event->canRegister()) {
            return response()->json([
                'success' => false,
                'message' => 'Registration is not available for this event.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'guests_count' => 'nullable|integer|min:0|max:'.$event->max_guests_per_attendee,
            'guest_details' => 'nullable|array',
            'special_requirements' => 'nullable|string',
            'additional_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $registration = $this->eventsService->registerUserForEvent(
                $event,
                $request->user(),
                $validator->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Successfully registered for event',
                'data' => $registration,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function cancelRegistration(Event $event, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->eventsService->cancelRegistration(
                $event,
                $request->user(),
                $request->get('reason')
            );

            return response()->json([
                'success' => true,
                'message' => 'Registration cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function checkIn(Event $event, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'method' => 'nullable|string|in:manual,qr_code,nfc,geofence',
            'location' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $checkIn = $this->eventsService->checkInUser(
                $event,
                $request->user(),
                $validator->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Successfully checked in',
                'data' => $checkIn,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function attendees(Event $event, Request $request): JsonResponse
    {
        if (! $event->canUserView($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view event attendees.',
            ], 403);
        }

        $status = $request->get('status', 'all');
        $attendees = $this->eventsService->getEventAttendees($event, $status);

        return response()->json([
            'success' => true,
            'data' => $attendees,
        ]);
    }

    public function analytics(Event $event, Request $request): JsonResponse
    {
        if (! $event->canUserEdit($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view event analytics.',
            ], 403);
        }

        $analytics = $this->eventsService->getEventAnalytics($event);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    public function upcoming(Request $request): JsonResponse
    {
        $events = $this->eventsService->getUpcomingEventsForUser(
            $request->user(),
            $request->get('limit', 5)
        );

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    public function recommended(Request $request): JsonResponse
    {
        $events = $this->eventsService->getRecommendedEvents(
            $request->user(),
            $request->get('limit', 10)
        );

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }
}
