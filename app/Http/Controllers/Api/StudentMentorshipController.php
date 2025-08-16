<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MentorProfile;
use App\Models\MentorshipRequest;
use App\Models\User;
use App\Services\MentorshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentMentorshipController extends Controller
{
    public function __construct(
        private MentorshipService $mentorshipService
    ) {}

    /**
     * Get available alumni mentors for students
     */
    public function getAlumniMentors(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required to access mentors',
            ], 403);
        }

        $filters = $request->only([
            'expertise_areas',
            'availability',
            'industry',
            'course_id',
            'graduation_year_range',
        ]);

        $mentors = $this->getStudentTailoredMentors($studentProfile, $filters);

        return response()->json([
            'success' => true,
            'data' => $mentors,
            'student_context' => [
                'career_interests' => $studentProfile->career_interests,
                'course' => $studentProfile->course->name ?? null,
                'seeking_mentorship' => $studentProfile->seeking_mentorship,
            ],
        ]);
    }

    /**
     * Get recommended mentors based on student profile
     */
    public function getRecommendedMentors(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required',
            ], 403);
        }

        $criteria = [
            'expertise_areas' => $studentProfile->career_interests ?? [],
            'availability' => 'medium',
        ];

        $mentors = $this->mentorshipService->matchMentorToMentee($user, $criteria);

        // Filter to only include alumni (users with Graduate role)
        $alumniMentors = $mentors->filter(function ($mentorProfile) {
            return $mentorProfile->user->hasRole('Graduate');
        });

        return response()->json([
            'success' => true,
            'data' => $alumniMentors->values(),
            'recommendation_basis' => $criteria,
        ]);
    }

    /**
     * Request mentorship from an alumni mentor
     */
    public function requestMentorship(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile || ! $studentProfile->canRequestMentorship()) {
            return response()->json([
                'message' => 'Not authorized to request mentorship',
            ], 403);
        }

        $validated = $request->validate([
            'mentor_id' => 'required|exists:users,id',
            'message' => 'required|string|min:20|max:500',
            'goals' => 'required|string|max:1000',
            'duration_months' => 'required|integer|min:1|max:24',
            'preferred_meeting_frequency' => 'required|in:weekly,biweekly,monthly',
            'specific_areas' => 'nullable|array',
            'specific_areas.*' => 'string|max:100',
        ]);

        // Verify the mentor is an alumni
        $mentor = User::findOrFail($validated['mentor_id']);
        if (! $mentor->hasRole('Graduate')) {
            return response()->json([
                'message' => 'Selected mentor must be an alumni',
            ], 400);
        }

        try {
            // Add student-specific data to the request
            $mentorshipData = array_merge($validated, [
                'student_profile_data' => [
                    'course' => $studentProfile->course->name ?? null,
                    'current_year' => $studentProfile->current_year,
                    'expected_graduation' => $studentProfile->expected_graduation_year,
                    'career_interests' => $studentProfile->career_interests,
                    'preferred_meeting_frequency' => $validated['preferred_meeting_frequency'],
                    'specific_areas' => $validated['specific_areas'] ?? [],
                ],
            ]);

            $mentorshipRequest = $this->mentorshipService->createMentorshipRequest(
                $validated['mentor_id'],
                $user->id,
                $mentorshipData
            );

            return response()->json([
                'success' => true,
                'message' => 'Mentorship request sent successfully',
                'request' => $mentorshipRequest->load(['mentor.graduate', 'mentee.studentProfile']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send mentorship request',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get student's mentorship requests and active mentorships
     */
    public function getStudentMentorships(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required',
            ], 403);
        }

        $mentorships = MentorshipRequest::where('mentee_id', $user->id)
            ->with(['mentor.graduate', 'mentor.mentorProfile', 'sessions'])
            ->orderBy('created_at', 'desc')
            ->get();

        $grouped = $mentorships->groupBy('status');

        return response()->json([
            'success' => true,
            'data' => [
                'pending' => $grouped->get('pending', collect()),
                'accepted' => $grouped->get('accepted', collect()),
                'declined' => $grouped->get('declined', collect()),
                'completed' => $grouped->get('completed', collect()),
            ],
            'statistics' => [
                'total_requests' => $mentorships->count(),
                'active_mentorships' => $grouped->get('accepted', collect())->count(),
                'completed_mentorships' => $grouped->get('completed', collect())->count(),
            ],
        ]);
    }

    /**
     * Get mentors from the same course/program
     */
    public function getMentorsFromSameCourse(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required',
            ], 403);
        }

        $mentors = MentorProfile::with(['user.graduate'])
            ->available()
            ->whereHas('user.graduate', function ($query) use ($studentProfile) {
                $query->where('course_id', $studentProfile->course_id);
            })
            ->whereHas('user', function ($query) {
                $query->role('Graduate');
            })
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $mentors,
            'course' => $studentProfile->course->name ?? null,
        ]);
    }

    /**
     * Get career-specific mentors
     */
    public function getCareerSpecificMentors(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required',
            ], 403);
        }

        $careerInterest = $request->get('career_interest');

        if (! $careerInterest) {
            return response()->json([
                'message' => 'Career interest parameter required',
            ], 400);
        }

        $mentors = MentorProfile::with(['user.graduate'])
            ->available()
            ->where(function ($query) use ($careerInterest) {
                $query->whereJsonContains('expertise_areas', $careerInterest)
                    ->orWhereHas('user.graduate', function ($q) use ($careerInterest) {
                        $q->where('current_position', 'like', "%{$careerInterest}%")
                            ->orWhere('industry', 'like', "%{$careerInterest}%");
                    });
            })
            ->whereHas('user', function ($query) {
                $query->role('Graduate');
            })
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $mentors,
            'career_interest' => $careerInterest,
        ]);
    }

    /**
     * Get student-tailored mentors with enhanced filtering
     */
    private function getStudentTailoredMentors($studentProfile, $filters)
    {
        $query = MentorProfile::with(['user.graduate'])
            ->available()
            ->whereHas('user', function ($q) {
                $q->role('Graduate');
            });

        // Apply filters
        if (! empty($filters['expertise_areas'])) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters['expertise_areas'] as $area) {
                    $q->orWhereJsonContains('expertise_areas', $area);
                }
            });
        }

        if (! empty($filters['availability'])) {
            $query->where('availability', $filters['availability']);
        }

        if (! empty($filters['course_id'])) {
            $query->whereHas('user.graduate', function ($q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }

        if (! empty($filters['industry'])) {
            $query->whereHas('user.graduate', function ($q) use ($filters) {
                $q->where('industry', 'like', "%{$filters['industry']}%");
            });
        }

        // Prioritize mentors from same course
        if ($studentProfile->course_id) {
            $query->orderByRaw("CASE WHEN EXISTS (
                SELECT 1 FROM graduates g
                WHERE g.user_id = mentor_profiles.user_id
                AND g.course_id = {$studentProfile->course_id}
            ) THEN 0 ELSE 1 END");
        }

        $query->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc');

        return $query->paginate(12);
    }
}
