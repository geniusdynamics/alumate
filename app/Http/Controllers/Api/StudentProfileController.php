<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StudentProfileController extends Controller
{
    /**
     * Get the current user's student profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->studentProfile;

        if (!$profile) {
            return response()->json([
                'message' => 'Student profile not found'
            ], 404);
        }

        return response()->json([
            'profile' => $profile->load(['course', 'user'])
        ]);
    }

    /**
     * Create a new student profile
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user already has a student profile
        if ($user->studentProfile) {
            return response()->json([
                'message' => 'Student profile already exists'
            ], 400);
        }

        $validated = $request->validate([
            'student_id' => 'required|string|unique:student_profiles,student_id',
            'course_id' => 'required|exists:courses,id',
            'current_year' => 'required|integer|min:1|max:6',
            'expected_graduation_year' => 'required|integer|min:' . now()->year,
            'enrollment_date' => 'required|date',
            'current_gpa' => 'nullable|numeric|min:0|max:4',
            'academic_standing' => ['nullable', Rule::in(['excellent', 'good', 'satisfactory', 'probation'])],
            'career_interests' => 'nullable|array',
            'skills' => 'nullable|array',
            'learning_goals' => 'nullable|array',
            'career_goals' => 'nullable|string|max:1000',
            'seeking_mentorship' => 'boolean',
            'mentorship_interests' => 'nullable|array',
            'interested_in_alumni_stories' => 'boolean',
            'interested_in_networking' => 'boolean',
            'interested_in_events' => 'boolean',
            'allow_alumni_contact' => 'boolean',
            'allow_mentor_requests' => 'boolean',
            'allow_event_invitations' => 'boolean',
        ]);

        $validated['user_id'] = $user->id;
        $validated['enrollment_status'] = 'active';

        $profile = StudentProfile::create($validated);
        $profile->updateProfileCompletion();

        // Assign student role if not already assigned
        if (!$user->hasRole('Student')) {
            $user->assignRole('Student');
        }

        return response()->json([
            'message' => 'Student profile created successfully',
            'profile' => $profile->load(['course', 'user'])
        ], 201);
    }

    /**
     * Update the student profile
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->studentProfile;

        if (!$profile) {
            return response()->json([
                'message' => 'Student profile not found'
            ], 404);
        }

        $validated = $request->validate([
            'current_year' => 'sometimes|integer|min:1|max:6',
            'expected_graduation_year' => 'sometimes|integer|min:' . now()->year,
            'current_gpa' => 'nullable|numeric|min:0|max:4',
            'academic_standing' => ['nullable', Rule::in(['excellent', 'good', 'satisfactory', 'probation'])],
            'career_interests' => 'nullable|array',
            'skills' => 'nullable|array',
            'learning_goals' => 'nullable|array',
            'career_goals' => 'nullable|string|max:1000',
            'seeking_mentorship' => 'boolean',
            'mentorship_interests' => 'nullable|array',
            'interested_in_alumni_stories' => 'boolean',
            'interested_in_networking' => 'boolean',
            'interested_in_events' => 'boolean',
            'allow_alumni_contact' => 'boolean',
            'allow_mentor_requests' => 'boolean',
            'allow_event_invitations' => 'boolean',
        ]);

        $profile->update($validated);
        $profile->updateProfileCompletion();

        return response()->json([
            'message' => 'Student profile updated successfully',
            'profile' => $profile->load(['course', 'user'])
        ]);
    }

    /**
     * Get profile completion status
     */
    public function completion(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->studentProfile;

        if (!$profile) {
            return response()->json([
                'message' => 'Student profile not found'
            ], 404);
        }

        return response()->json([
            'completion_percentage' => $profile->profile_completion_percentage,
            'completion_fields' => $profile->profile_completion_fields,
            'suggestions' => $this->getCompletionSuggestions($profile)
        ]);
    }

    /**
     * Get available courses for student registration
     */
    public function courses(): JsonResponse
    {
        $courses = Course::active()
                        ->orderBy('name')
                        ->get(['id', 'name', 'description', 'duration_years']);

        return response()->json([
            'courses' => $courses
        ]);
    }

    /**
     * Get student statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->studentProfile;

        if (!$profile) {
            return response()->json([
                'message' => 'Student profile not found'
            ], 404);
        }

        $stats = [
            'academic_progress' => $profile->getAcademicProgress(),
            'years_until_graduation' => $profile->getYearsUntilGraduation(),
            'is_near_graduation' => $profile->isNearGraduation(),
            'profile_completion' => $profile->profile_completion_percentage,
            'mentorship_requests_sent' => $profile->mentorshipRequests()->count(),
            'active_mentorships' => $profile->mentorshipRequests()->where('status', 'accepted')->count(),
        ];

        return response()->json([
            'statistics' => $stats
        ]);
    }

    /**
     * Get completion suggestions for the profile
     */
    private function getCompletionSuggestions(StudentProfile $profile): array
    {
        $suggestions = [];

        if (empty($profile->career_interests)) {
            $suggestions[] = [
                'field' => 'career_interests',
                'message' => 'Add your career interests to get better alumni connections',
                'priority' => 'high'
            ];
        }

        if (empty($profile->skills)) {
            $suggestions[] = [
                'field' => 'skills',
                'message' => 'List your current skills to find relevant mentors',
                'priority' => 'high'
            ];
        }

        if (empty($profile->learning_goals)) {
            $suggestions[] = [
                'field' => 'learning_goals',
                'message' => 'Set learning goals to track your progress',
                'priority' => 'medium'
            ];
        }

        if (empty($profile->career_goals)) {
            $suggestions[] = [
                'field' => 'career_goals',
                'message' => 'Define your career goals for better guidance',
                'priority' => 'medium'
            ];
        }

        if (is_null($profile->current_gpa)) {
            $suggestions[] = [
                'field' => 'current_gpa',
                'message' => 'Add your GPA to complete your academic profile',
                'priority' => 'low'
            ];
        }

        return $suggestions;
    }
}
