<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Requests\LearningIndexRequest;
use App\Http\Requests\StoreLearningInteractionRequest;
use App\Http\Requests\UpdateLearningProgressRequest;
use App\Models\LearningProgress;
use App\Services\Analytics\LearningAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Learning Analytics API Controller
 *
 * Provides RESTful endpoints for learning progress tracking, engagement analytics,
 * and certification verification.
 */
class LearningController extends Controller
{
    public function __construct(
        private readonly LearningAnalyticsService $learningService
    ) {}

    /**
     * Retrieve learning progress for authenticated user
     *
     * @param LearningIndexRequest $request
     * @return JsonResponse
     */
    public function index(LearningIndexRequest $request): JsonResponse
    {
        try {
            // Check analytics consent
            $consentService = app(\App\Services\Analytics\ConsentService::class);
            if (!$consentService->hasConsent()) {
                \Illuminate\Support\Facades\Log::info('Learning analytics access denied - no consent', [
                    'user_id' => auth()->id(),
                    'ip' => $request->ip(),
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Analytics consent required',
                ], 403);
            }

            $tenantId = session('tenant_id', 'default');
            $userId = auth()->id();
            $courseId = $request->input('course_id');
            $dateFrom = $request->has('date_from') ? $request->date_from : null;
            $dateTo = $request->has('date_to') ? $request->date_to : null;
            $page = $request->input('page', 1);
            $perPage = 20;

            // Get learning progress from service
            $progressQuery = LearningProgress::byTenant($tenantId)
                ->byUser($userId);

            if ($courseId) {
                $progressQuery->byCourse($courseId);
            }

            $progress = $progressQuery->paginate($perPage, ['*'], 'page', $page);

            // Calculate engagement scores for each progress record
            $progress->getCollection()->transform(function ($item) {
                $engagementScore = $this->learningService->calculateEngagementScore(
                    $item->user_id,
                    $item->course_id
                );

                $item->engagement_score = $engagementScore;
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $progress->items(),
                'pagination' => [
                    'current_page' => $progress->currentPage(),
                    'per_page' => $progress->perPage(),
                    'total' => $progress->total(),
                    'last_page' => $progress->lastPage(),
                ],
                'filters' => [
                    'course_id' => $courseId,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve learning progress', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve learning progress',
            ], 500);
        }
    }

    /**
     * Get specific user-course progress details
     *
     * @param int $userId
     * @param int $courseId
     * @return JsonResponse
     */
    public function show(int $userId, int $courseId): JsonResponse
    {
        try {
            $tenantId = session('tenant_id', 'default');

            // Check if user can access this progress (own progress or super admin)
            if ($userId !== auth()->id() && !auth()->user()->hasRole('super-admin')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to access this learning progress',
                ], 403);
            }

            $progress = LearningProgress::byTenant($tenantId)
                ->byUser($userId)
                ->byCourse($courseId)
                ->first();

            if (!$progress) {
                return response()->json([
                    'success' => false,
                    'error' => 'Learning progress not found',
                ], 404);
            }

            // Get engagement score and certification status
            $engagementScore = $this->learningService->calculateEngagementScore($userId, $courseId);
            $certification = $this->learningService->verifyCertification($userId, [
                'course_id' => $courseId
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'progress' => $progress,
                    'engagement_score' => $engagementScore,
                    'certification' => $certification,
                    'completion_percentage' => $progress->completion_percentage,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve learning progress details', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'course_id' => $courseId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve learning progress details',
            ], 500);
        }
    }

    /**
     * Track learning interaction
     *
     * @param StoreLearningInteractionRequest $request
     * @return JsonResponse
     */
    public function storeInteraction(StoreLearningInteractionRequest $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $courseId = $request->input('course_id');
            $moduleId = $request->input('module_id');
            $duration = $request->input('duration');
            $score = $request->input('score');

            $interactionData = [
                'module_id' => $moduleId,
                'duration' => $duration,
                'score' => $score,
                'interaction_type' => $request->input('interaction_type', 'view'),
            ];

            // Track interaction using service
            $success = $this->learningService->trackCourseInteraction($userId, $courseId, $interactionData);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to track learning interaction',
                ], 500);
            }

            // Get updated progress
            $tenantId = session('tenant_id', 'default');
            $progress = LearningProgress::byTenant($tenantId)
                ->byUser($userId)
                ->byCourse($courseId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Learning interaction tracked successfully',
                'data' => [
                    'progress' => $progress,
                    'engagement_score' => $this->learningService->calculateEngagementScore($userId, $courseId),
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to track learning interaction', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to track learning interaction',
            ], 500);
        }
    }

    /**
     * Update learning progress with manual overrides
     *
     * @param UpdateLearningProgressRequest $request
     * @param int $userId
     * @return JsonResponse
     */
    public function updateProgress(UpdateLearningProgressRequest $request, int $userId): JsonResponse
    {
        try {
            $tenantId = session('tenant_id', 'default');

            // Check permissions (own progress or super admin)
            if ($userId !== auth()->id() && !auth()->user()->hasRole('super-admin')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized to update this learning progress',
                ], 403);
            }

            $courseId = $request->input('course_id');
            $modulesCompleted = $request->input('modules_completed');
            $totalScore = $request->input('total_score');
            $certified = $request->input('certified');

            $progress = LearningProgress::firstOrNew([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'course_id' => $courseId,
            ]);

            $updateData = [];
            if ($modulesCompleted !== null) {
                $updateData['modules_completed'] = $modulesCompleted;
            }
            if ($totalScore !== null) {
                $updateData['total_score'] = $totalScore;
            }
            if ($certified !== null) {
                $updateData['certified'] = $certified;
            }

            $progress->fill($updateData);
            $progress->save();

            // Trigger insights generation
            $this->learningService->generateLearningInsights([
                'course_id' => $courseId,
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $progress->fresh(),
                'message' => 'Learning progress updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update learning progress', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update learning progress',
            ], 500);
        }
    }
}