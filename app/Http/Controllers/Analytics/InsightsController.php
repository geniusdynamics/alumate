<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\InsightsService;
use App\Http\Requests\GenerateInsightsRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller for handling insights and recommendations
 * 
 * This controller manages the API endpoints for automated insights and recommendations,
 * including generating insights, viewing existing insights, and tracking recommendation effectiveness.
 */
class InsightsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private InsightsService $insightsService
    ) {
        // Dependencies injected via constructor
    }

    /**
     * Display a paginated list of the latest insights
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);
            $filters = $request->only(['type', 'metric', 'start_date', 'end_date', 'severity']);
            
            // Generate options for insights service
            $options = [
                'page' => $page,
                'limit' => $limit,
                'filters' => $filters,
            ];
            
            // For now, we'll just generate insights with default options
            // In a real implementation, you might want to retrieve stored insights from a database
            $insights = $this->insightsService->generateInsights([
                'period' => $request->get('period', 'last_30_days'),
                'metrics_filter' => $request->get('metrics_filter', []),
            ]);
            
            // Paginate the results manually for now
            $offset = ($page - 1) * $limit;
            $paginatedInsights = array_slice($insights, $offset, $limit);
            
            return response()->json([
                'data' => $paginatedInsights,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => count($insights),
                    'last_page' => ceil(count($insights) / $limit),
                ],
                'filters' => $filters,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve insights', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'error' => 'Failed to retrieve insights',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger the generation of new insights for a specific period
     *
     * @param GenerateInsightsRequest $request
     * @return JsonResponse
     */
    public function generate(GenerateInsightsRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Determine if the operation should be queued
            $queue = $request->get('queue', false);
            
            // Generate options for insights service
            $options = [
                'period' => $validated['period'] ?? 'last_30_days',
                'metrics_filter' => $validated['metrics_filter'] ?? [],
                'start_date' => $validated['start_date'] ?? now()->subDays(30),
                'end_date' => $validated['end_date'] ?? now(),
                'queue' => $queue,
            ];
            
            $result = $this->insightsService->generateInsights($options);
            
            $status = $queue ? 202 : 200; // 202 Accepted if queued, 200 OK if completed
            
            return response()->json([
                'message' => $queue ? 'Insights generation queued' : 'Insights generated successfully',
                'result' => $result,
                'options' => $options,
            ], $status);
        } catch (\Exception $e) {
            Log::error('Failed to generate insights', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'error' => 'Failed to generate insights',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Track the effectiveness of a specific insight/recommendation
     *
     * @param Request $request
     * @param string $insightId
     * @return JsonResponse
     */
    public function trackFeedback(Request $request, string $insightId): JsonResponse
    {
        try {
            // Validate request data
            $validated = $request->validate([
                'effectiveness_score' => 'required|integer|min:1|max:10',
                'implementation_status' => 'nullable|string|in:pending,started,completed,abandoned',
                'notes' => 'nullable|string|max:1000',
                'metadata' => 'nullable|array',
            ]);
            
            $effectivenessScore = $validated['effectiveness_score'];
            $metadata = $validated['metadata'] ?? [];
            
            // Add implementation status and notes to metadata
            if (isset($validated['implementation_status'])) {
                $metadata['implementation_status'] = $validated['implementation_status'];
            }
            if (isset($validated['notes'])) {
                $metadata['notes'] = $validated['notes'];
            }
            
            // Track the effectiveness
            $success = $this->insightsService->trackEffectiveness(
                $insightId,
                $effectivenessScore,
                $metadata
            );
            
            if ($success) {
                return response()->json([
                    'message' => 'Effectiveness feedback recorded successfully',
                    'insight_id' => $insightId,
                    'effectiveness_score' => $effectivenessScore,
                ]);
            } else {
                return response()->json([
                    'error' => 'Failed to record effectiveness feedback',
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Failed to track insight effectiveness', [
                'insight_id' => $insightId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'error' => 'Failed to track insight effectiveness',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}