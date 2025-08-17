<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ElasticsearchService;
use App\Models\User;
use App\Models\Post;
use App\Models\Job;
use App\Models\Event;
use App\Models\SavedSearch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    private ElasticsearchService $elasticsearchService;

    public function __construct(ElasticsearchService $elasticsearchService)
    {
        $this->elasticsearchService = $elasticsearchService;
    }

    /**
     * Perform advanced search across multiple content types
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:255',
            'filters' => 'array',
            'filters.types' => 'array',
            'filters.types.*' => 'in:user,post,job,event',
            'filters.location' => 'string|max:100',
            'filters.graduation_year' => 'integer|min:1900|max:' . (date('Y') + 10),
            'filters.industry' => 'array',
            'filters.skills' => 'array',
            'filters.date_range' => 'array',
            'filters.date_range.from' => 'date',
            'filters.date_range.to' => 'date|after_or_equal:filters.date_range.from',
            'filters.sort' => 'in:relevance,date,name,engagement',
            'size' => 'integer|min:1|max:100',
            'from' => 'integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid search parameters',
                'details' => $validator->errors()
            ], 400);
        }

        $query = $request->input('query');
        $filters = $request->input('filters', []);
        $size = $request->input('size', 20);
        $from = $request->input('from', 0);

        try {
            // Log search query for analytics
            $this->logSearchQuery($query, $filters);

            // Perform Elasticsearch search
            $results = $this->elasticsearchService->search($query, $filters, $size, $from);

            // Track search analytics
            $this->trackSearchAnalytics($query, $filters, $results['total']);

            return response()->json([
                'success' => true,
                'query' => $query,
                'filters' => $filters,
                'hits' => $results['hits'],
                'total' => $results['total'],
                'aggregations' => $results['aggregations'],
                'took' => $results['took'],
                'pagination' => [
                    'current_page' => floor($from / $size) + 1,
                    'per_page' => $size,
                    'total' => $results['total'],
                    'total_pages' => ceil($results['total'] / $size)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Search failed', [
                'query' => $query,
                'filters' => $filters,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'error' => 'Search temporarily unavailable. Please try again.',
                'fallback' => $this->getFallbackResults($query, $filters, $size, $from)
            ], 500);
        }
    }

    /**
     * Get search suggestions based on partial query
     */
    public function suggestions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:100',
            'size' => 'integer|min:1|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid suggestion parameters',
                'details' => $validator->errors()
            ], 400);
        }

        $query = $request->input('q');
        $size = $request->input('size', 5);

        try {
            $suggestions = $this->elasticsearchService->getSuggestions($query, $size);

            return response()->json([
                'success' => true,
                'query' => $query,
                'suggestions' => $suggestions
            ]);
        } catch (\Exception $e) {
            Log::error('Suggestions failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            // Fallback to database suggestions
            $fallbackSuggestions = $this->getFallbackSuggestions($query, $size);

            return response()->json([
                'success' => true,
                'query' => $query,
                'suggestions' => $fallbackSuggestions,
                'fallback' => true
            ]);
        }
    }

    /**
     * Save a search for later use and alerts
     */
    public function saveSearch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'query' => 'required|string|max:255',
            'filters' => 'array',
            'email_alerts' => 'boolean',
            'alert_frequency' => 'in:immediate,daily,weekly'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid search data',
                'details' => $validator->errors()
            ], 400);
        }

        try {
            $savedSearch = SavedSearch::create([
                'user_id' => Auth::id(),
                'name' => $request->input('name'),
                'query' => $request->input('query'),
                'filters' => $request->input('filters', []),
                'email_alerts' => $request->input('email_alerts', false),
                'alert_frequency' => $request->input('alert_frequency', 'daily')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Search saved successfully',
                'search' => $savedSearch
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save search', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'error' => 'Failed to save search. Please try again.'
            ], 500);
        }
    }

    /**
     * Get user's saved searches
     */
    public function getSavedSearches(): JsonResponse
    {
        try {
            $savedSearches = SavedSearch::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'searches' => $savedSearches
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get saved searches', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'error' => 'Failed to load saved searches'
            ], 500);
        }
    }

    /**
     * Update a saved search
     */
    public function updateSavedSearch(Request $request, SavedSearch $savedSearch): JsonResponse
    {
        // Ensure user owns the search
        if ($savedSearch->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'query' => 'string|max:255',
            'filters' => 'array',
            'email_alerts' => 'boolean',
            'alert_frequency' => 'in:immediate,daily,weekly'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid search data',
                'details' => $validator->errors()
            ], 400);
        }

        try {
            $savedSearch->update($request->only([
                'name', 'query', 'filters', 'email_alerts', 'alert_frequency'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Search updated successfully',
                'search' => $savedSearch->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update saved search', [
                'search_id' => $savedSearch->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'error' => 'Failed to update search. Please try again.'
            ], 500);
        }
    }

    /**
     * Delete a saved search
     */
    public function deleteSavedSearch(SavedSearch $savedSearch): JsonResponse
    {
        // Ensure user owns the search
        if ($savedSearch->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $savedSearch->delete();

            return response()->json([
                'success' => true,
                'message' => 'Search deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete saved search', [
                'search_id' => $savedSearch->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'error' => 'Failed to delete search. Please try again.'
            ], 500);
        }
    }

    /**
     * Run a saved search and update its last run time
     */
    public function runSavedSearch(SavedSearch $savedSearch): JsonResponse
    {
        // Ensure user owns the search
        if ($savedSearch->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            // Perform the search
            $results = $this->elasticsearchService->search(
                $savedSearch->query,
                $savedSearch->filters,
                20,
                0
            );

            // Update last run time and result count
            $savedSearch->update([
                'last_run_at' => now(),
                'last_result_count' => $results['total']
            ]);

            return response()->json([
                'success' => true,
                'search' => $savedSearch->fresh(),
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to run saved search', [
                'search_id' => $savedSearch->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'error' => 'Failed to run search. Please try again.'
            ], 500);
        }
    }

    /**
     * Get search analytics for the current user
     */
    public function getSearchAnalytics(): JsonResponse
    {
        try {
            // This would typically query a search analytics table
            // For now, return mock data
            $analytics = [
                'total_searches' => 150,
                'popular_queries' => [
                    'software engineer',
                    'product manager',
                    'data scientist',
                    'marketing',
                    'startup'
                ],
                'search_trends' => [
                    ['date' => '2024-01-01', 'count' => 12],
                    ['date' => '2024-01-02', 'count' => 18],
                    ['date' => '2024-01-03', 'count' => 15],
                ],
                'popular_filters' => [
                    'location' => ['San Francisco', 'New York', 'Remote'],
                    'industry' => ['Technology', 'Finance', 'Healthcare'],
                    'graduation_year' => ['2020', '2019', '2021']
                ]
            ];

            return response()->json([
                'success' => true,
                'analytics' => $analytics
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get search analytics', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'error' => 'Failed to load analytics'
            ], 500);
        }
    }

    /**
     * Log search query for analytics
     */
    private function logSearchQuery(string $query, array $filters): void
    {
        try {
            // This would typically insert into a search_logs table
            Log::info('Search performed', [
                'query' => $query,
                'filters' => $filters,
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);
        } catch (\Exception $e) {
            // Don't fail the search if logging fails
            Log::error('Failed to log search query', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Track search analytics
     */
    private function trackSearchAnalytics(string $query, array $filters, int $resultCount): void
    {
        try {
            // This would typically update search analytics tables
            // For now, just log the data
            Log::info('Search analytics', [
                'query' => $query,
                'filters' => $filters,
                'result_count' => $resultCount,
                'user_id' => Auth::id()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track search analytics', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get fallback results when Elasticsearch is unavailable
     */
    private function getFallbackResults(string $query, array $filters, int $size, int $from): array
    {
        $results = [];

        // Fallback user search
        if (empty($filters['types']) || in_array('user', $filters['types'])) {
            $users = User::where('name', 'LIKE', "%{$query}%")
                ->orWhere('bio', 'LIKE', "%{$query}%")
                ->limit($size)
                ->offset($from)
                ->get();

            foreach ($users as $user) {
                $results[] = [
                    'id' => $user->id,
                    'type' => 'user',
                    'score' => 1.0,
                    'source' => $user->toArray(),
                    'highlight' => []
                ];
            }
        }

        return [
            'hits' => $results,
            'total' => count($results),
            'aggregations' => [],
            'took' => 0
        ];
    }

    /**
     * Get fallback suggestions when Elasticsearch is unavailable
     */
    private function getFallbackSuggestions(string $query, int $size): array
    {
        $suggestions = [];

        // Get user name suggestions
        $users = User::where('name', 'LIKE', "%{$query}%")
            ->limit($size)
            ->pluck('name');

        foreach ($users as $name) {
            $suggestions[] = [
                'text' => $name,
                'score' => 1.0,
                'type' => 'name'
            ];
        }

        return $suggestions;
    }
}