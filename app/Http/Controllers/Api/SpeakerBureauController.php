<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SpeakerBookingRequest;
use App\Models\SpeakerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpeakerBureauController extends Controller
{
    /**
     * Get available speakers with filtering
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'topic',
            'format',
            'audience',
            'industry',
            'event_type',
            'budget',
            'location',
            'search',
        ]);

        $speakers = $this->getFilteredSpeakers($filters);

        return response()->json([
            'success' => true,
            'data' => $speakers,
        ]);
    }

    /**
     * Get featured speakers
     */
    public function featured(Request $request): JsonResponse
    {
        $speakers = SpeakerProfile::with(['user.graduate'])
            ->featured()
            ->orderBy('rating', 'desc')
            ->orderBy('total_engagements', 'desc')
            ->limit(6)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $speakers,
        ]);
    }

    /**
     * Get speaker details
     */
    public function show(SpeakerProfile $speaker): JsonResponse
    {
        $speaker->load(['user.graduate', 'completedBookings']);

        return response()->json([
            'success' => true,
            'data' => $speaker,
        ]);
    }

    /**
     * Create or update speaker profile
     */
    public function createProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user is an alumni
        if (! $user->hasRole('Graduate')) {
            return response()->json([
                'message' => 'Only alumni can create speaker profiles',
            ], 403);
        }

        $validated = $request->validate([
            'speaker_title' => 'nullable|string|max:255',
            'bio' => 'required|string|max:2000',
            'speaking_experience' => 'nullable|string|max:1000',
            'expertise_topics' => 'required|array|min:1',
            'expertise_topics.*' => 'string|max:100',
            'speaking_formats' => 'required|array|min:1',
            'speaking_formats.*' => 'in:keynote,workshop,panel,webinar,seminar,other',
            'target_audiences' => 'required|array|min:1',
            'target_audiences.*' => 'string|max:100',
            'industries' => 'nullable|array',
            'industries.*' => 'string|max:100',
            'speaking_fee' => 'nullable|numeric|min:0',
            'travel_willing' => 'boolean',
            'max_travel_distance' => 'nullable|integer|min:0',
            'virtual_speaking' => 'boolean',
            'availability_preferences' => 'nullable|array',
            'preferred_contact_method' => 'required|in:email,phone,platform',
            'special_requirements' => 'nullable|string|max:500',
            'demo_video_url' => 'nullable|url',
        ]);

        $profile = SpeakerProfile::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Speaker profile saved successfully',
            'data' => $profile->load('user'),
        ]);
    }

    /**
     * Request speaker booking
     */
    public function requestBooking(Request $request, SpeakerProfile $speaker): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'event_title' => 'required|string|max:255',
            'event_description' => 'required|string|max:1000',
            'event_date' => 'required|date|after:today',
            'event_start_time' => 'required|date_format:H:i',
            'event_end_time' => 'required|date_format:H:i|after:event_start_time',
            'event_location' => 'nullable|string|max:255',
            'event_format' => 'required|in:keynote,workshop,panel,webinar,seminar,other',
            'topic_requested' => 'required|string|max:255',
            'expected_audience_size' => 'nullable|integer|min:1',
            'audience_demographics' => 'nullable|array',
            'event_type' => 'required|in:virtual,in_person,hybrid',
            'budget_offered' => 'nullable|numeric|min:0',
            'special_requirements' => 'nullable|string|max:500',
            'additional_notes' => 'nullable|string|max:1000',
        ]);

        // Check if speaker is available for the requested format and type
        if ($validated['event_type'] === 'virtual' && ! $speaker->virtual_speaking) {
            return response()->json([
                'message' => 'Speaker is not available for virtual events',
            ], 400);
        }

        if (! in_array($validated['event_format'], $speaker->speaking_formats)) {
            return response()->json([
                'message' => 'Speaker does not offer the requested format',
            ], 400);
        }

        $booking = SpeakerBookingRequest::create(array_merge($validated, [
            'speaker_id' => $speaker->user_id,
            'requester_id' => $user->id,
            'status' => 'pending',
            'requested_at' => now(),
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Booking request sent successfully',
            'data' => $booking->load(['speaker', 'requester']),
        ], 201);
    }

    /**
     * Get speaker's booking requests (for speakers)
     */
    public function getSpeakerBookings(Request $request): JsonResponse
    {
        $user = $request->user();
        $speakerProfile = $user->speakerProfile;

        if (! $speakerProfile) {
            return response()->json([
                'message' => 'Speaker profile not found',
            ], 404);
        }

        $bookings = SpeakerBookingRequest::forSpeaker($user->id)
            ->with(['requester'])
            ->orderBy('requested_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    /**
     * Get user's booking requests (for requesters)
     */
    public function getUserBookings(Request $request): JsonResponse
    {
        $user = $request->user();

        $bookings = SpeakerBookingRequest::forRequester($user->id)
            ->with(['speaker'])
            ->orderBy('requested_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    /**
     * Respond to booking request (for speakers)
     */
    public function respondToBooking(Request $request, SpeakerBookingRequest $booking): JsonResponse
    {
        $user = $request->user();

        if ($booking->speaker_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'action' => 'required|in:accept,decline',
            'response' => 'nullable|string|max:500',
            'final_fee' => 'nullable|numeric|min:0',
            'booking_details' => 'nullable|array',
        ]);

        if ($validated['action'] === 'accept') {
            $booking->accept(
                $validated['response'] ?? null,
                $validated['booking_details'] ?? null
            );

            if (isset($validated['final_fee'])) {
                $booking->final_fee = $validated['final_fee'];
                $booking->save();
            }

            $message = 'Booking request accepted successfully';
        } else {
            $booking->decline($validated['response'] ?? null);
            $message = 'Booking request declined';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $booking->fresh(['speaker', 'requester']),
        ]);
    }

    /**
     * Complete booking and add feedback (for requesters)
     */
    public function completeBooking(Request $request, SpeakerBookingRequest $booking): JsonResponse
    {
        $user = $request->user();

        if ($booking->requester_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'feedback' => 'nullable|array',
            'feedback.overall' => 'nullable|string|max:500',
            'feedback.presentation_quality' => 'nullable|string|max:500',
            'feedback.audience_engagement' => 'nullable|string|max:500',
            'feedback.would_recommend' => 'boolean',
        ]);

        $booking->complete($validated['feedback'] ?? null, $validated['rating']);

        // Update speaker's overall rating
        $booking->speaker->speakerProfile->updateRating();

        return response()->json([
            'success' => true,
            'message' => 'Booking completed and feedback submitted',
            'data' => $booking->fresh(['speaker', 'requester']),
        ]);
    }

    /**
     * Get speakers by topic
     */
    public function getByTopic(Request $request): JsonResponse
    {
        $topic = $request->get('topic');

        if (! $topic) {
            return response()->json([
                'message' => 'Topic parameter required',
            ], 400);
        }

        $speakers = SpeakerProfile::with(['user.graduate'])
            ->active()
            ->byTopic($topic)
            ->orderBy('rating', 'desc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $speakers,
            'topic' => $topic,
        ]);
    }

    /**
     * Get filtered speakers
     */
    private function getFilteredSpeakers($filters)
    {
        $query = SpeakerProfile::with(['user.graduate'])
            ->active();

        if (! empty($filters['topic'])) {
            $query->byTopic($filters['topic']);
        }

        if (! empty($filters['format'])) {
            $query->byFormat($filters['format']);
        }

        if (! empty($filters['audience'])) {
            $query->byAudience($filters['audience']);
        }

        if (! empty($filters['industry'])) {
            $query->byIndustry($filters['industry']);
        }

        if (! empty($filters['event_type'])) {
            if ($filters['event_type'] === 'virtual') {
                $query->virtualAvailable();
            } elseif ($filters['event_type'] === 'in_person') {
                $query->travelWilling();
            }
        }

        if (! empty($filters['budget'])) {
            $query->withinBudget($filters['budget']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('bio', 'like', "%{$search}%")
                    ->orWhere('speaker_title', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $query->orderBy('is_featured', 'desc')
            ->orderBy('rating', 'desc')
            ->orderBy('total_engagements', 'desc');

        return $query->paginate(12);
    }
}
