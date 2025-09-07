<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Services\ComponentAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComponentAnalyticsController extends Controller
{
    public function __construct(
        private ComponentAnalyticsService $analyticsService
    ) {}

    /**
     * Get component usage statistics
     */
    public function usageStats(Request $request): JsonResponse
    {
        $request->validate([
            'component_id' => 'nullable|exists:components,id',
            'category' => 'nullable|in:hero,forms,testimonials,statistics,ctas,media',
            'period' => 'nullable|in:day,week,month,year,all',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        try {
            $tenantId = Auth::user()->tenant_id;
            $componentId = $request->component_id;
            $category = $request->category;
            $period = $request->period ?? 'month';
            $limit = $request->limit ?? 20;
            
            if ($componentId) {
                // Get stats for specific component
                $component = Component::forTenant($tenantId)->findOrFail($componentId);
                $stats = $this->analyticsService->getComponentStats($componentId);
            } else {
                // Get stats for all components or by category
                $stats = $this->analyticsService->getComponentsStats($tenantId, $category, $period, $limit);
            }
            
            return response()->json([
                'stats' => $stats,
                'period' => $period
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve usage statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Track component usage
     */
    public function trackUsage(Request $request): JsonResponse
    {
        $request->validate([
            'component_id' => 'required|exists:components,id',
            'context' => 'nullable|string|in:grapejs,preview,page_builder,frontend',
            'page_id' => 'nullable|exists:pages,id',
            'user_id' => 'nullable|exists:users,id'
        ]);

        try {
            $componentId = $request->component_id;
            $context = $request->context ?? 'frontend';
            $pageId = $request->page_id;
            $userId = $request->user_id ?? Auth::id();
            
            // Track usage in analytics service
            $this->analyticsService->trackComponentUsage($componentId, $context, $pageId, $userId);
            
            // Update component usage count
            $component = Component::find($componentId);
            if ($component) {
                $component->increment('usage_count');
                $component->update(['last_used_at' => now()]);
            }
            
            return response()->json([
                'message' => 'Usage tracked successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to track usage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get component performance metrics
     */
    public function performanceMetrics(Request $request): JsonResponse
    {
        $request->validate([
            'component_id' => 'nullable|exists:components,id',
            'category' => 'nullable|in:hero,forms,testimonials,statistics,ctas,media',
            'metric' => 'nullable|in:load_time,render_time,memory_usage,dom_nodes',
            'period' => 'nullable|in:day,week,month,year',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        try {
            $tenantId = Auth::user()->tenant_id;
            $componentId = $request->component_id;
            $category = $request->category;
            $metric = $request->metric ?? 'load_time';
            $period = $request->period ?? 'month';
            $limit = $request->limit ?? 10;
            
            if ($componentId) {
                // Get performance metrics for specific component
                $metrics = $this->analyticsService->getComponentPerformanceMetrics($componentId, $metric, $period);
            } else {
                // Get performance metrics for components by category
                $metrics = $this->analyticsService->getComponentsPerformanceMetrics($tenantId, $category, $metric, $period, $limit);
            }
            
            return response()->json([
                'metrics' => $metrics,
                'metric_type' => $metric,
                'period' => $period
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve performance metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get component rating statistics
     */
    public function ratings(Request $request): JsonResponse
    {
        $request->validate([
            'component_id' => 'nullable|exists:components,id',
            'category' => 'nullable|in:hero,forms,testimonials,statistics,ctas,media',
            'period' => 'nullable|in:day,week,month,year,all'
        ]);

        try {
            $tenantId = Auth::user()->tenant_id;
            $componentId = $request->component_id;
            $category = $request->category;
            $period = $request->period ?? 'all';
            
            if ($componentId) {
                // Get ratings for specific component
                $ratings = $this->analyticsService->getComponentRatings($componentId, $period);
            } else {
                // Get ratings for components by category
                $ratings = $this->analyticsService->getComponentsRatings($tenantId, $category, $period);
            }
            
            return response()->json([
                'ratings' => $ratings,
                'period' => $period
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve ratings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Track component rating
     */
    public function trackRating(Request $request): JsonResponse
    {
        $request->validate([
            'component_id' => 'required|exists:components,id',
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:500'
        ]);

        try {
            $componentId = $request->component_id;
            $rating = $request->rating;
            $comment = $request->comment;
            $userId = Auth::id();
            
            // Track rating in analytics service
            $this->analyticsService->trackComponentRating($componentId, $rating, $comment, $userId);
            
            return response()->json([
                'message' => 'Rating tracked successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to track rating',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get component engagement metrics
     */
    public function engagement(Request $request): JsonResponse
    {
        $request->validate([
            'component_id' => 'nullable|exists:components,id',
            'category' => 'nullable|in:hero,forms,testimonials,statistics,ctas,media',
            'metric' => 'nullable|in:clicks,submissions,views,interactions',
            'period' => 'nullable|in:day,week,month,year',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        try {
            $tenantId = Auth::user()->tenant_id;
            $componentId = $request->component_id;
            $category = $request->category;
            $metric = $request->metric ?? 'views';
            $period = $request->period ?? 'month';
            $limit = $request->limit ?? 10;
            
            if ($componentId) {
                // Get engagement metrics for specific component
                $engagement = $this->analyticsService->getComponentEngagement($componentId, $metric, $period);
            } else {
                // Get engagement metrics for components by category
                $engagement = $this->analyticsService->getComponentsEngagement($tenantId, $category, $metric, $period, $limit);
            }
            
            return response()->json([
                'engagement' => $engagement,
                'metric_type' => $metric,
                'period' => $period
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve engagement metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trending components
     */
    public function trending(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'nullable|in:hero,forms,testimonials,statistics,ctas,media',
            'period' => 'nullable|in:day,week,month',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        try {
            $tenantId = Auth::user()->tenant_id;
            $category = $request->category;
            $period = $request->period ?? 'week';
            $limit = $request->limit ?? 10;
            
            $trending = $this->analyticsService->getTrendingComponents($tenantId, $category, $period, $limit);
            
            return response()->json([
                'trending' => $trending,
                'period' => $period,
                'limit' => $limit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve trending components',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get component comparison data
     */
    public function compare(Request $request): JsonResponse
    {
        $request->validate([
            'component_ids' => 'required|array|min:2|max:10',
            'component_ids.*' => 'exists:components,id',
            'metrics' => 'nullable|array',
            'metrics.*' => 'in:usage,rating,performance,engagement',
            'period' => 'nullable|in:day,week,month,year'
        ]);

        try {
            $componentIds = $request->component_ids;
            $metrics = $request->metrics ?? ['usage', 'rating'];
            $period = $request->period ?? 'month';
            
            $comparison = $this->analyticsService->compareComponents($componentIds, $metrics, $period);
            
            return response()->json([
                'comparison' => $comparison,
                'metrics' => $metrics,
                'period' => $period
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to compare components',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get component analytics summary
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'category' => 'nullable|in:hero,forms,testimonials,statistics,ctas,media',
            'period' => 'nullable|in:day,week,month,year,all'
        ]);

        try {
            $tenantId = Auth::user()->tenant_id;
            $category = $request->category;
            $period = $request->period ?? 'month';
            
            $summary = $this->analyticsService->getAnalyticsSummary($tenantId, $category, $period);
            
            return response()->json([
                'summary' => $summary,
                'period' => $period
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve analytics summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export analytics data
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:json,csv,excel',
            'type' => 'required|in:usage,performance,ratings,engagement,summary',
            'period' => 'nullable|in:day,week,month,year,all',
            'component_id' => 'nullable|exists:components,id'
        ]);

        try {
            $tenantId = Auth::user()->tenant_id;
            $format = $request->get('format', 'json');
            $type = $request->get('type', 'usage');
            $period = $request->get('period', 'month');
            $componentId = $request->get('component_id');
            
            $exportData = $this->analyticsService->exportAnalyticsData($tenantId, $type, $period, $componentId);
            
            // In a real implementation, this would generate and return an actual file
            // For now, we'll return the data in the requested format
            
            return response()->json([
                'data' => $exportData,
                'format' => $format,
                'type' => $type,
                'exported_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export analytics data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}