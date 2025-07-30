<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MentorProfile;
use App\Models\MentorshipRequest;
use App\Models\MentorshipSession;
use App\Services\MentorshipService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class MentorshipController extends Controller
{
    public function __construct(
        private MentorshipService $mentorshipService
    ) {}

    public function becomeMentor(Request $request): JsonResponse
    {
        $request->validate([
            'bio' => 'required|string|min:50|max:1000',
            'expertise_areas' => 'required|array|min:1|max:10',
            'expertise_areas.*' => 'string|max:100',
            'availability' => ['required', Rule::in(['high', 'medium', 'low'])],
            'max_mentees' => 'required|integer|min:1|max:10',
        ]);

        try {
            // Check if user already has a mentor profile
            $existingProfile = MentorProfile::where('user_id', $request->user()->id)->first();
            
            if ($existingProfile) {
                $profile = $this->mentorshipService->updateMentorProfile(
                    $existingProfile,
                    $request->validated()
                );
            } else {
                $profile = $this->mentorshipService->createMentorProfile(
                    $request->user(),
                    $request->validated()
                );
            }

            return response()->json([
                'message' => 'Mentor profile created successfully',
                'profile' => $profile->load('user')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create mentor profile',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function findMentors(Request $request): JsonResponse
    {
        $request->validate([
            'expertise_areas' => 'sometimes|array',
            'expertise_areas.*' => 'string',
            'availability' => ['sometimes', Rule::in(['high', 'medium', 'low'])],
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        $criteria = $request->only(['expertise_areas', 'availability']);
        $limit = $request->get('limit', 20);

        $mentors = $this->mentorshipService->matchMentorToMentee(
            $request->user(),
            $criteria
        )->take($limit);

        return response()->json([
            'mentors' => $mentors->load(['user.educations', 'user.careerTimelines'])
        ]);
    }

    public function requestMentorship(Request $request): JsonResponse
    {
        $request->validate([
            'mentor_id' => 'required|exists:users,id',
            'message' => 'required|string|min:20|max:500',
            'goals' => 'sometimes|string|max:1000',
            'duration_months' => 'sometimes|integer|min:1|max:24',
        ]);

        try {
            $mentorshipRequest = $this->mentorshipService->createMentorshipRequest(
                $request->mentor_id,
                $request->user()->id,
                $request->validated()
            );

            return response()->json([
                'message' => 'Mentorship request sent successfully',
                'request' => $mentorshipRequest->load(['mentor', 'mentee'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send mentorship request',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function acceptRequest(Request $request, int $requestId): JsonResponse
    {
        $mentorshipRequest = MentorshipRequest::findOrFail($requestId);

        // Ensure the current user is the mentor
        if ($mentorshipRequest->mentor_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized to accept this request'
            ], 403);
        }

        if ($mentorshipRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Request has already been processed'
            ], 400);
        }

        try {
            $acceptedRequest = $this->mentorshipService->acceptMentorshipRequest($requestId);

            return response()->json([
                'message' => 'Mentorship request accepted successfully',
                'request' => $acceptedRequest->load(['mentor', 'mentee'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to accept mentorship request',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function declineRequest(Request $request, int $requestId): JsonResponse
    {
        $mentorshipRequest = MentorshipRequest::findOrFail($requestId);

        // Ensure the current user is the mentor
        if ($mentorshipRequest->mentor_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized to decline this request'
            ], 403);
        }

        if ($mentorshipRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Request has already been processed'
            ], 400);
        }

        $mentorshipRequest->decline();

        return response()->json([
            'message' => 'Mentorship request declined'
        ]);
    }

    public function scheduleSession(Request $request): JsonResponse
    {
        $request->validate([
            'mentorship_id' => 'required|exists:mentorship_requests,id',
            'scheduled_at' => 'required|date|after:now',
            'duration' => 'sometimes|integer|min:15|max:180',
            'notes' => 'sometimes|string|max:500',
        ]);

        $mentorship = MentorshipRequest::findOrFail($request->mentorship_id);

        // Ensure the current user is either mentor or mentee
        if (!in_array($request->user()->id, [$mentorship->mentor_id, $mentorship->mentee_id])) {
            return response()->json([
                'message' => 'Unauthorized to schedule session for this mentorship'
            ], 403);
        }

        try {
            $session = $this->mentorshipService->scheduleMentorshipSession(
                $request->mentorship_id,
                $request->validated()
            );

            return response()->json([
                'message' => 'Session scheduled successfully',
                'session' => $session->load('mentorship.mentor', 'mentorship.mentee')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to schedule session',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function getMentorships(Request $request): JsonResponse
    {
        $user = $request->user();

        $asMentor = MentorshipRequest::where('mentor_id', $user->id)
            ->with(['mentee', 'sessions'])
            ->orderBy('created_at', 'desc')
            ->get();

        $asMentee = MentorshipRequest::where('mentee_id', $user->id)
            ->with(['mentor', 'sessions'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'as_mentor' => $asMentor,
            'as_mentee' => $asMentee
        ]);
    }

    public function getUpcomingSessions(Request $request): JsonResponse
    {
        $sessions = $this->mentorshipService->getUpcomingSessions($request->user());

        return response()->json([
            'sessions' => $sessions
        ]);
    }

    public function completeSession(Request $request, int $sessionId): JsonResponse
    {
        $request->validate([
            'notes' => 'sometimes|string|max:1000',
            'rating' => 'sometimes|integer|min:1|max:5',
            'feedback' => 'sometimes|string|max:500',
        ]);

        $session = MentorshipSession::findOrFail($sessionId);
        $mentorship = $session->mentorship;

        // Ensure the current user is either mentor or mentee
        if (!in_array($request->user()->id, [$mentorship->mentor_id, $mentorship->mentee_id])) {
            return response()->json([
                'message' => 'Unauthorized to complete this session'
            ], 403);
        }

        if (!$session->canBeCompleted()) {
            return response()->json([
                'message' => 'Session cannot be completed at this time'
            ], 400);
        }

        $feedback = [];
        if ($request->has('rating') || $request->has('feedback')) {
            $userType = $request->user()->id === $mentorship->mentor_id ? 'mentor' : 'mentee';
            $feedback[$userType] = [
                'rating' => $request->rating,
                'feedback' => $request->feedback,
                'submitted_at' => now()->toISOString()
            ];
        }

        $session->complete($feedback);

        if ($request->has('notes')) {
            $session->update(['notes' => $request->notes]);
        }

        return response()->json([
            'message' => 'Session completed successfully',
            'session' => $session->fresh()
        ]);
    }

    public function getMentorAnalytics(Request $request): JsonResponse
    {
        $analytics = $this->mentorshipService->getMentorshipAnalytics($request->user()->id);

        return response()->json([
            'analytics' => $analytics
        ]);
    }

    public function getMentorProfile(Request $request): JsonResponse
    {
        $profile = MentorProfile::where('user_id', $request->user()->id)
            ->with('user')
            ->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Mentor profile not found'
            ], 404);
        }

        return response()->json([
            'profile' => $profile
        ]);
    }

    public function updateMentorProfile(Request $request): JsonResponse
    {
        $request->validate([
            'bio' => 'sometimes|string|min:50|max:1000',
            'expertise_areas' => 'sometimes|array|min:1|max:10',
            'expertise_areas.*' => 'string|max:100',
            'availability' => ['sometimes', Rule::in(['high', 'medium', 'low'])],
            'max_mentees' => 'sometimes|integer|min:1|max:10',
            'is_active' => 'sometimes|boolean',
        ]);

        $profile = MentorProfile::where('user_id', $request->user()->id)->first();

        if (!$profile) {
            return response()->json([
                'message' => 'Mentor profile not found'
            ], 404);
        }

        try {
            $updatedProfile = $this->mentorshipService->updateMentorProfile(
                $profile,
                $request->validated()
            );

            return response()->json([
                'message' => 'Mentor profile updated successfully',
                'profile' => $updatedProfile->load('user')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update mentor profile',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}