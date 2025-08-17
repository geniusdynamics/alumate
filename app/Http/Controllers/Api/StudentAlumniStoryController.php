<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentAlumniConnection;
use App\Models\SuccessStory;
use App\Services\SuccessStoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAlumniStoryController extends Controller
{
    public function __construct(
        private SuccessStoryService $successStoryService
    ) {}

    /**
     * Get alumni stories tailored for students with enhanced filtering
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required to access alumni stories',
            ], 403);
        }

        $filters = $request->only([
            'industry',
            'achievement_type',
            'graduation_year',
            'tags',
            'search',
            'course_id',
            'career_interest',
            'company',
            'role',
        ]);

        // Add student-specific filtering
        $perPage = $request->get('per_page', 12);
        $stories = $this->getStudentTailoredStories($filters, $studentProfile, $perPage);

        return response()->json([
            'success' => true,
            'data' => $stories,
            'student_context' => [
                'career_interests' => $studentProfile->career_interests,
                'course' => $studentProfile->course->name ?? null,
                'graduation_year' => $studentProfile->expected_graduation_year,
            ],
        ]);
    }

    /**
     * Get recommended alumni stories based on student profile
     */
    public function recommended(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required',
            ], 403);
        }

        $limit = $request->get('limit', 6);
        $stories = $this->getRecommendedStories($studentProfile, $limit);

        return response()->json([
            'success' => true,
            'data' => $stories,
            'recommendation_basis' => [
                'career_interests' => $studentProfile->career_interests,
                'skills' => $studentProfile->skills,
                'course' => $studentProfile->course->name ?? null,
            ],
        ]);
    }

    /**
     * Get alumni stories by career path
     */
    public function byCareerPath(Request $request): JsonResponse
    {
        $careerPath = $request->get('career_path');

        if (! $careerPath) {
            return response()->json([
                'message' => 'Career path parameter required',
            ], 400);
        }

        $stories = SuccessStory::with('user.graduate')
            ->published()
            ->where(function ($query) use ($careerPath) {
                $query->where('industry', 'like', "%{$careerPath}%")
                    ->orWhere('current_role', 'like', "%{$careerPath}%")
                    ->orWhere('achievement_type', 'like', "%{$careerPath}%")
                    ->orWhereJsonContains('tags', $careerPath);
            })
            ->orderBy('is_featured', 'desc')
            ->orderBy('view_count', 'desc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $stories,
            'career_path' => $careerPath,
        ]);
    }

    /**
     * Get alumni from the same course/program
     */
    public function fromSameCourse(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required',
            ], 403);
        }

        $stories = SuccessStory::with('user.graduate')
            ->published()
            ->whereHas('user.graduate', function ($query) use ($studentProfile) {
                $query->where('course_id', $studentProfile->course_id);
            })
            ->orderBy('is_featured', 'desc')
            ->orderBy('graduation_year', 'desc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $stories,
            'course' => $studentProfile->course->name ?? null,
        ]);
    }

    /**
     * Get recent graduates (within last 5 years)
     */
    public function recentGraduates(Request $request): JsonResponse
    {
        $currentYear = now()->year;
        $fiveYearsAgo = $currentYear - 5;

        $stories = SuccessStory::with('user.graduate')
            ->published()
            ->where('graduation_year', '>=', $fiveYearsAgo)
            ->orderBy('graduation_year', 'desc')
            ->orderBy('is_featured', 'desc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $stories,
            'year_range' => "{$fiveYearsAgo} - {$currentYear}",
        ]);
    }

    /**
     * Connect with an alumni (send connection request)
     */
    public function connect(Request $request, SuccessStory $story): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile || ! $studentProfile->canConnectWithAlumni()) {
            return response()->json([
                'message' => 'Not authorized to connect with alumni',
            ], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:500',
            'connection_type' => 'required|in:mentorship,networking,advice,collaboration',
        ]);

        // Check if connection already exists
        $existingConnection = StudentAlumniConnection::where('student_id', $user->id)
            ->where('alumni_id', $story->user_id)
            ->where('connection_type', $validated['connection_type'])
            ->first();

        if ($existingConnection) {
            return response()->json([
                'message' => 'Connection request already exists',
                'status' => $existingConnection->status,
            ], 400);
        }

        // Create connection request
        $connection = StudentAlumniConnection::create([
            'student_id' => $user->id,
            'alumni_id' => $story->user_id,
            'success_story_id' => $story->id,
            'connection_type' => $validated['connection_type'],
            'student_message' => $validated['message'],
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Connection request sent successfully',
            'connection' => $connection,
            'alumni' => [
                'name' => $story->user->name,
                'role' => $story->current_role,
                'company' => $story->current_company,
            ],
        ]);
    }

    /**
     * Get student's connection requests and status
     */
    public function connections(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required',
            ], 403);
        }

        $connections = StudentAlumniConnection::with(['alumni', 'successStory'])
            ->forStudent($user->id)
            ->orderBy('requested_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $connections,
        ]);
    }

    /**
     * Get career insights from alumni stories
     */
    public function careerInsights(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = $user->studentProfile;

        if (! $studentProfile) {
            return response()->json([
                'message' => 'Student profile required',
            ], 403);
        }

        $insights = $this->generateCareerInsights($studentProfile);

        return response()->json([
            'success' => true,
            'data' => $insights,
        ]);
    }

    /**
     * Get student-tailored stories with enhanced filtering
     */
    private function getStudentTailoredStories($filters, $studentProfile, $perPage)
    {
        $query = SuccessStory::with('user.graduate')
            ->published();

        // Apply standard filters
        if (! empty($filters['industry'])) {
            $query->byIndustry($filters['industry']);
        }

        if (! empty($filters['achievement_type'])) {
            $query->byAchievementType($filters['achievement_type']);
        }

        if (! empty($filters['graduation_year'])) {
            $query->byGraduationYear($filters['graduation_year']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('current_role', 'like', "%{$search}%")
                    ->orWhere('current_company', 'like', "%{$search}%");
            });
        }

        // Student-specific filters
        if (! empty($filters['course_id'])) {
            $query->whereHas('user.graduate', function ($q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }

        if (! empty($filters['career_interest']) && $studentProfile->career_interests) {
            $query->where(function ($q) use ($filters) {
                $interest = $filters['career_interest'];
                $q->where('industry', 'like', "%{$interest}%")
                    ->orWhere('current_role', 'like', "%{$interest}%")
                    ->orWhereJsonContains('tags', $interest);
            });
        }

        // Prioritize stories from same course if student has one
        if ($studentProfile->course_id) {
            $query->orderByRaw("CASE WHEN EXISTS (
                SELECT 1 FROM graduates g
                WHERE g.user_id = success_stories.user_id
                AND g.course_id = {$studentProfile->course_id}
            ) THEN 0 ELSE 1 END");
        }

        $query->orderBy('is_featured', 'desc')
            ->orderBy('published_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get recommended stories based on student profile
     */
    private function getRecommendedStories($studentProfile, $limit)
    {
        $query = SuccessStory::with('user.graduate')
            ->published();

        // Recommend based on career interests
        if ($studentProfile->career_interests) {
            $query->where(function ($q) use ($studentProfile) {
                foreach ($studentProfile->career_interests as $interest) {
                    $q->orWhere('industry', 'like', "%{$interest}%")
                        ->orWhere('current_role', 'like', "%{$interest}%")
                        ->orWhereJsonContains('tags', $interest);
                }
            });
        }

        // Prioritize same course
        if ($studentProfile->course_id) {
            $query->whereHas('user.graduate', function ($q) use ($studentProfile) {
                $q->where('course_id', $studentProfile->course_id);
            });
        }

        return $query->orderBy('is_featured', 'desc')
            ->orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Generate career insights for the student
     */
    private function generateCareerInsights($studentProfile)
    {
        $insights = [];

        // Popular career paths from same course
        if ($studentProfile->course_id) {
            $popularRoles = SuccessStory::published()
                ->whereHas('user.graduate', function ($q) use ($studentProfile) {
                    $q->where('course_id', $studentProfile->course_id);
                })
                ->selectRaw('current_role, COUNT(*) as count')
                ->whereNotNull('current_role')
                ->groupBy('current_role')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get();

            $insights['popular_roles_in_course'] = $popularRoles;
        }

        // Trending industries
        $trendingIndustries = SuccessStory::published()
            ->selectRaw('industry, COUNT(*) as count')
            ->whereNotNull('industry')
            ->where('graduation_year', '>=', now()->year - 3)
            ->groupBy('industry')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        $insights['trending_industries'] = $trendingIndustries;

        // Career progression examples
        if ($studentProfile->career_interests) {
            $careerProgression = SuccessStory::published()
                ->where(function ($q) use ($studentProfile) {
                    foreach ($studentProfile->career_interests as $interest) {
                        $q->orWhere('industry', 'like', "%{$interest}%");
                    }
                })
                ->orderBy('graduation_year', 'asc')
                ->limit(3)
                ->get(['title', 'current_role', 'current_company', 'graduation_year', 'achievement_type']);

            $insights['career_progression_examples'] = $careerProgression;
        }

        return $insights;
    }
}
