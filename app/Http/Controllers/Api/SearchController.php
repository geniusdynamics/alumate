<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ElasticsearchService;
use App\Models\SavedSearch;
use App\Models\SearchAlert;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{
    protected ElasticsearchService $elasticsearchService;

    public function __construct(ElasticsearchService $elasticsearchService)
    {
        $this->elasticsearchService = $elasticsearchService;
    }

    /**
     * Perform alumni search
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'nullable|string|max:255',
            'filters' => 'nullable|array',
            'filters.graduation_year' => 'nullable|array',
            'filters.graduation_year.min' => 'nullable|integer|min:1900|max:' . date('Y'),
            'filters.graduation_year.max' => 'nullable|integer|min:1900|max:' . date('Y'),
            'filters.location' => 'nullable|string|max:255',
            'filters.industry' => 'nullable|array',
            'filters.industry.*' => 'string|max:255',
            'filters.company' => 'nullable|string|max:255',
            'filters.school' => 'nullable|string|max:255',
            'filters.skills' => 'nullable|array',
            'filters.skills.*' => 'string|max:255',
            'filters.location_radius' => 'nullable|string',
            'filters.location_center' => 'nullable|array',
            'filters.location_center.lat' => 'nullable|numeric',
            'filters.location_center.lon' => 'nullable|numeric',
            'page' => 'nullable|integer|min:1',
            'size' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $request->input('query', '');
        $filters = $request->input('filters', []);
        $page = $request->input('page', 1);
        $size = $request->input('size', 20);

        $pagination = [
            'size' => $size,
            'from' => ($page - 1) * $size
        ];

        try {
            $results = $this->elasticsearchService->searchUsers($query, $filters, $pagination);

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $results['users'],
                    'total' => $results['total'],
                    'aggregations' => $results['aggregations'],
                    'page' => $page,
                    'size' => $size,
                    'total_pages' => ceil($results['total'] / $size)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get search suggestions
     */
    public function suggestions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $request->input('query');

        try {
            $suggestions = $this->elasticsearchService->suggestUsers($query);

            return response()->json([
                'success' => true,
                'data' => $suggestions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Suggestions failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save a search
     */
    public function saveSearch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'query' => 'required|string|max:255',
            'filters' => 'nullable|array',
            'create_alert' => 'nullable|boolean',
            'alert_frequency' => 'nullable|in:daily,weekly,monthly'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $query = $request->input('query');
            $filters = $request->input('filters', []);
            $name = $request->input('name');

            // Generate name if not provided
            if (!$name) {
                $name = $this->generateSearchName($query, $filters);
            }

            // Check if user already has a saved search with this name
            $existingSearch = SavedSearch::where('user_id', $user->id)
                ->where('name', $name)
                ->first();

            if ($existingSearch) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have a saved search with this name'
                ], 409);
            }

            $savedSearch = $this->elasticsearchService->saveSearch($user, $query, $filters);
            
            // Update the name if provided
            if ($request->has('name')) {
                $savedSearch->update(['name' => $name]);
            }

            // Create alert if requested
            if ($request->input('create_alert', false)) {
                $alert = $this->elasticsearchService->createSearchAlert($user, $savedSearch->id);
                
                if ($request->has('alert_frequency')) {
                    $alert->update(['frequency' => $request->input('alert_frequency')]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Search saved successfully',
                'data' => $savedSearch->load('alerts')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save search',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's saved searches
     */
    public function getSavedSearches(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $savedSearches = SavedSearch::where('user_id', $user->id)
                ->with('alerts')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $savedSearches
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve saved searches',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a saved search
     */
    public function deleteSavedSearch(Request $request, int $searchId): JsonResponse
    {
        try {
            $user = $request->user();
            
            $savedSearch = SavedSearch::where('user_id', $user->id)
                ->where('id', $searchId)
                ->first();

            if (!$savedSearch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saved search not found'
                ], 404);
            }

            $savedSearch->delete();

            return response()->json([
                'success' => true,
                'message' => 'Saved search deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete saved search',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update search alert
     */
    public function updateSearchAlert(Request $request, int $alertId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'frequency' => 'nullable|in:daily,weekly,monthly',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            $alert = SearchAlert::where('user_id', $user->id)
                ->where('id', $alertId)
                ->first();

            if (!$alert) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search alert not found'
                ], 404);
            }

            $alert->update($request->only(['frequency', 'is_active']));

            // Recalculate next send time if frequency changed
            if ($request->has('frequency')) {
                $alert->calculateNextSendTime();
            }

            return response()->json([
                'success' => true,
                'message' => 'Search alert updated successfully',
                'data' => $alert
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update search alert',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a descriptive name for saved search
     */
    protected function generateSearchName(string $query, array $filters): string
    {
        $parts = [];
        
        if (!empty($query)) {
            $parts[] = "\"$query\"";
        }
        
        if (!empty($filters['location'])) {
            $parts[] = "in {$filters['location']}";
        }
        
        if (!empty($filters['industry'])) {
            $industry = is_array($filters['industry']) ? implode(', ', $filters['industry']) : $filters['industry'];
            $parts[] = "in $industry";
        }
        
        if (!empty($filters['graduation_year'])) {
            if (is_array($filters['graduation_year'])) {
                $parts[] = "graduated {$filters['graduation_year']['min']}-{$filters['graduation_year']['max']}";
            } else {
                $parts[] = "graduated {$filters['graduation_year']}";
            }
        }
        
        return implode(' ', $parts) ?: 'All Alumni';
    }
}