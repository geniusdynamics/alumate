<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Requests\ComparePerformanceRequest;
use App\Http\Requests\TrackProgressRequest;
use App\Models\LearningProgress;
use App\Services\Analytics\LearningAnalyticsService;
use App\Services\TenantContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Learning Analytics API Controller
 *
 * Provides RESTful endpoints for comprehensive learning analytics including
 * progress tracking, outcome analysis, performance comparison, and predictions.
 * Implements proper tenant isolation and role-based access control.
 */
class LearningAnalyticsController extends Controller
{
    public function __construct(
        private readonly LearningAnalyticsService $learningAnalyticsService,
        private readonly TenantContextService $tenantContextService
    ) {}

    /**
     * Get all learning analytics data for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $tenantId = $this->getCurrentTenantId();

            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);
            $includeMetrics = $request->input('include_metrics', true);

            // Get all learning progress records for user
            $progressQuery = LearningProgress::byTenant($tenantId)
                ->byUser($userId)
                ->with(['course'])
                ->orderBy('updated_at', 'desc');

            $progressRecords = $progressQuery->paginate($perPage, ['*'], 'page', $page);

            $data = [
                'total_courses' => $progressRecords->total(),
                'progress_records' => $progressRecords->items(),
            ];

            if ($includeMetrics) {
                $data['summary'] = [
                    'total_learning_time' => $progressRecords->sum('engagement_duration'),
                    'average_score' => round($progressRecords->avg('total_score') ?? 0, 2),
                    'average_engagement' => round($progressRecords->avg('engagement_score') ?? 0, 2),
                    'completed_courses' => $progressRecords->where('progress_percentage', 100)->count(),
                    'certifications' => $progressRecords->where('certified', true)->count(),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $progressRecords->currentPage(),
                    'per_page' => $progressRecords->perPage(),
                    'total' => $progressRecords->total(),
                    'last_page' => $progressRecords->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve learning analytics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve learning analytics',
            ], 500);
        }
    }

    /**
     * Get learning analytics for a specific user
     */
    public function show(int $userId): JsonResponse
    {
        try {
            // Check authorization - user can view own data or admin
            if ($userId !== auth()->id() && ! Gate::allows('view-learning-analytics')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to access this learning analytics data',
                ], 403);
            }

            $tenantId = $this->getCurrentTenantId();

            // Verify user belongs to tenant
            $progress = LearningProgress::byTenant($tenantId)
                ->where('user_id', $userId)
                ->first();

            if (! $progress && ! auth()->user()->hasRole('super-admin')) {
                return response()->json([
                    'success' => false,
                    'error' => 'User learning data not found',
                ], 404);
            }

            // Get comprehensive outcomes analysis
            $outcomes = $this->learningAnalyticsService->analyzeLearningOutcomes($userId);
            $metrics = $this->learningAnalyticsService->getLearningMetrics($userId, [
                'start' => now()->subDays(30),
                'end' => now(),
            ]);
            $recommendations = $this->learningAnalyticsService->getLearningRecommendations($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'outcomes' => $outcomes,
                    'metrics' => $metrics,
                    'recommendations' => $recommendations,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve learning analytics for user', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve learning analytics',
            ], 500);
        }
    }

    /**
     * Track learning progress for a user
     */
    public function trackProgress(TrackProgressRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $userId = auth()->id();
            $courseId = $validated['course_id'];

            $progressData = [
                'progress_percentage' => $validated['progress_percentage'] ?? 0,
                'modules_completed' => $validated['modules_completed'] ?? 0,
                'engagement_duration' => $validated['engagement_duration'] ?? 0,
                'interactions_count' => $validated['interactions_count'] ?? 0,
                'total_score' => $validated['total_score'] ?? 0,
            ];

            $progress = $this->learningAnalyticsService->trackLearningProgress(
                $userId,
                $courseId,
                $progressData
            );

            Log::info('Learning progress tracked via API', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'progress_percentage' => $progressData['progress_percentage'],
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'progress' => $progress,
                    'engagement_score' => $this->learningAnalyticsService->calculateEngagementScore($userId, $courseId),
                ],
                'message' => 'Learning progress tracked successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to track learning progress', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to track learning progress',
            ], 500);
        }
    }

    /**
     * Get learning progress for a specific user and course
     */
    public function getProgress(int $userId, int $courseId): JsonResponse
    {
        try {
            // Check authorization
            if ($userId !== auth()->id() && ! Gate::allows('view-learning-analytics')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to access this learning progress',
                ], 403);
            }

            $progress = $this->learningAnalyticsService->getLearningProgress($userId, $courseId);

            if (! $progress) {
                return response()->json([
                    'success' => false,
                    'error' => 'Learning progress not found',
                ], 404);
            }

            $engagementScore = $this->learningAnalyticsService->calculateEngagementScore($userId, $courseId);
            $certification = $this->learningAnalyticsService->verifyCertification($userId, [
                'course_id' => $courseId,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'progress' => $progress,
                    'engagement_score' => $engagementScore,
                    'certification' => $certification,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve learning progress', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'course_id' => $courseId,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve learning progress',
            ], 500);
        }
    }

    /**
     * Analyze learning outcomes for a user
     */
    public function analyzeOutcomes(int $userId): JsonResponse
    {
        try {
            // Check authorization
            if ($userId !== auth()->id() && ! Gate::allows('view-learning-analytics')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to access learning outcomes',
                ], 403);
            }

            $outcomes = $this->learningAnalyticsService->analyzeLearningOutcomes($userId);

            return response()->json([
                'success' => true,
                'data' => $outcomes,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to analyze learning outcomes', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to analyze learning outcomes',
            ], 500);
        }
    }

    /**
     * Get learning metrics for a user
     */
    public function getMetrics(Request $request, int $userId): JsonResponse
    {
        try {
            // Check authorization
            if ($userId !== auth()->id() && ! Gate::allows('view-learning-analytics')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to access learning metrics',
                ], 403);
            }

            $dateRange = [
                'start' => $request->input('start_date', now()->subDays(30)),
                'end' => $request->input('end_date', now()),
            ];

            $metrics = $this->learningAnalyticsService->getLearningMetrics($userId, $dateRange);

            return response()->json([
                'success' => true,
                'data' => $metrics,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve learning metrics', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve learning metrics',
            ], 500);
        }
    }

    /**
     * Compare learning performance across multiple users
     */
    public function comparePerformance(ComparePerformanceRequest $request): JsonResponse
    {
        try {
            // Authorization check
            if (! Gate::allows('compare-learning-performance')) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to compare learning performance.',
                ], 403);
            }

            $validated = $request->validated();
            $userIds = $validated['user_ids'];

            if (count($userIds) < 2) {
                return response()->json([
                    'success' => false,
                    'error' => 'At least two user IDs are required for comparison',
                ], 422);
            }

            $comparison = $this->learningAnalyticsService->compareLearningPerformance($userIds);

            return response()->json([
                'success' => true,
                'data' => $comparison,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to compare learning performance', [
                'error' => $e->getMessage(),
                'user_ids' => $request->input('user_ids', []),
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to compare learning performance',
            ], 500);
        }
    }

    /**
     * Predict learning completion for a user in a course
     */
    public function predictCompletion(int $userId, int $courseId): JsonResponse
    {
        try {
            // Check authorization
            if ($userId !== auth()->id() && ! Gate::allows('view-learning-analytics')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to access completion prediction',
                ], 403);
            }

            $prediction = $this->learningAnalyticsService->predictLearningCompletion($userId, $courseId);

            return response()->json([
                'success' => true,
                'data' => $prediction,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to predict learning completion', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'course_id' => $courseId,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to predict learning completion',
            ], 500);
        }
    }

    /**
     * Get learning recommendations for a user
     */
    public function getRecommendations(int $userId): JsonResponse
    {
        try {
            // Check authorization
            if ($userId !== auth()->id() && ! Gate::allows('view-learning-analytics')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to access learning recommendations',
                ], 403);
            }

            $recommendations = $this->learningAnalyticsService->getLearningRecommendations($userId);

            return response()->json([
                'success' => true,
                'data' => $recommendations,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get learning recommendations', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get learning recommendations',
            ], 500);
        }
    }

    /**
     * Track learning activity (general activity tracking)
     */
    public function trackActivity(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'event_name' => 'required|string',
                'course_id' => 'required|integer',
                'activity_type' => 'nullable|string',
                'duration' => 'nullable|integer',
                'module_id' => 'nullable|integer',
                'resource_type' => 'nullable|string',
                'resource_id' => 'nullable|integer',
            ]);

            $activity = array_merge($validated, [
                'properties' => $request->input('properties', []),
                'occurred_at' => now(),
            ]);

            $event = $this->learningAnalyticsService->trackLearningActivity(
                auth()->id(),
                $activity
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'event_id' => $event->id,
                    'event_name' => $event->event_name,
                ],
                'message' => 'Learning activity tracked successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to track learning activity', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to track learning activity',
            ], 500);
        }
    }

    /**
     * Verify certification eligibility
     */
    public function verifyCertification(int $userId, int $courseId): JsonResponse
    {
        try {
            // Check authorization
            if ($userId !== auth()->id() && ! Gate::allows('verify-certifications')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to verify certification',
                ], 403);
            }

            $certification = $this->learningAnalyticsService->verifyCertification($userId, [
                'course_id' => $courseId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $certification,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to verify certification', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'course_id' => $courseId,
                'tenant_id' => $this->getCurrentTenantId(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to verify certification',
            ], 500);
        }
    }

    /**
     * Get current tenant ID with fallback
     */
    private function getCurrentTenantId(): int
    {
        return $this->tenantContextService->getCurrentTenantId() ?? 1;
    }
}
