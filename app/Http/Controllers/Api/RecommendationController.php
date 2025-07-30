<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AlumniRecommendationService;
use App\Jobs\GenerateRecommendationsJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RecommendationController extends Controller
{
    private AlumniRecommendationService $recommendationService;

    public function __construct(AlumniRecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /**
     * Get recommendations for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $limit = $request->input('limit', 10);
            
            $recommendations = $this->recommendationService->getRecommendationsForUser($user, $limit);
            
            return response()->json([
                'data' => $recommendations->map(function ($recommendation) {
                    return [
                        'user' => [
                            'id' => $recommendation['user']->id,
                            'name' => $recommendation['user']->name,
                            'avatar_url' => $recommendation['user']->avatar_url,
                            'current_title' => $recommendation['user']->current_title,
                            'current_company' => $recommendation['user']->current_company,
                            'location' => $recommendation['user']->location,
                            'bio' => $recommendation['user']->bio,
                        ],
                        'score' => $recommendation['score'],
                        'reasons' => $recommendation['reasons'],
                        'shared_circles' => $recommendation['shared_circles']->map(function ($circle) {
                            return [
                                'id' => $circle->id,
                                'name' => $circle->name,
                                'type' => $circle->type,
                            ];
                        }),
                        'mutual_connections' => $recommendation['mutual_connections']->map(function ($connection) {
                            return [
                                'id' => $connection->id,
                                'name' => $connection->name,
                                'avatar_url' => $connection->avatar_url,
                            ];
                        }),
                    ];
                }),
                'meta' => [
                    'total' => $recommendations->count(),
                    'generated_at' => now()->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get recommendations', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to load recommendations',
                'message' => 'Please try again later'
            ], 500);
        }
    }

    /**
     * Dismiss a recommendation
     */
    public function dismiss(Request $request, int $userId): JsonResponse
    {
        try {
            $user = $request->user();
            
            $this->recommendationService->dismissRecommendation($user, $userId);
            
            Log::info('Recommendation dismissed', [
                'user_id' => $user->id,
                'dismissed_user_id' => $userId
            ]);
            
            return response()->json([
                'message' => 'Recommendation dismissed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dismiss recommendation', [
                'user_id' => $request->user()->id,
                'dismissed_user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to dismiss recommendation'
            ], 500);
        }
    }

    /**
     * Provide feedback on a recommendation
     */
    public function feedback(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|in:not_relevant,already_know,not_interested,other',
            'comment' => 'nullable|string|max:500'
        ]);

        try {
            $user = $request->user();
            $reason = $request->input('reason');
            $comment = $request->input('comment');
            
            // Store feedback for analytics
            $feedbackKey = "recommendation_feedback:user:{$user->id}";
            $feedback = Cache::get($feedbackKey, []);
            
            $feedback[] = [
                'recommended_user_id' => $userId,
                'reason' => $reason,
                'comment' => $comment,
                'timestamp' => now()->toISOString()
            ];
            
            Cache::put($feedbackKey, $feedback, now()->addDays(90));
            
            // Also dismiss the recommendation
            $this->recommendationService->dismissRecommendation($user, $userId);
            
            Log::info('Recommendation feedback received', [
                'user_id' => $user->id,
                'recommended_user_id' => $userId,
                'reason' => $reason,
                'has_comment' => !empty($comment)
            ]);
            
            return response()->json([
                'message' => 'Feedback submitted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to submit recommendation feedback', [
                'user_id' => $request->user()->id,
                'recommended_user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to submit feedback'
            ], 500);
        }
    }

    /**
     * Refresh recommendations for the user
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Clear existing cache
            $this->recommendationService->clearRecommendationCache($user);
            
            // Dispatch job to generate fresh recommendations
            GenerateRecommendationsJob::dispatch($user->id);
            
            // Get fresh recommendations
            $recommendations = $this->recommendationService->getRecommendationsForUser($user, 10);
            
            Log::info('Recommendations refreshed', [
                'user_id' => $user->id,
                'new_count' => $recommendations->count()
            ]);
            
            return response()->json([
                'data' => $recommendations->map(function ($recommendation) {
                    return [
                        'user' => [
                            'id' => $recommendation['user']->id,
                            'name' => $recommendation['user']->name,
                            'avatar_url' => $recommendation['user']->avatar_url,
                            'current_title' => $recommendation['user']->current_title,
                            'current_company' => $recommendation['user']->current_company,
                            'location' => $recommendation['user']->location,
                            'bio' => $recommendation['user']->bio,
                        ],
                        'score' => $recommendation['score'],
                        'reasons' => $recommendation['reasons'],
                        'shared_circles' => $recommendation['shared_circles']->map(function ($circle) {
                            return [
                                'id' => $circle->id,
                                'name' => $circle->name,
                                'type' => $circle->type,
                            ];
                        }),
                        'mutual_connections' => $recommendation['mutual_connections']->map(function ($connection) {
                            return [
                                'id' => $connection->id,
                                'name' => $connection->name,
                                'avatar_url' => $connection->avatar_url,
                            ];
                        }),
                    ];
                }),
                'meta' => [
                    'total' => $recommendations->count(),
                    'generated_at' => now()->toISOString(),
                    'refreshed' => true
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to refresh recommendations', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Failed to refresh recommendations'
            ], 500);
        }
    }
}