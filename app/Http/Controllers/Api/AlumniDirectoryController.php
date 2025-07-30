<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AlumniDirectoryService;
use App\Models\User;
use App\Models\Connection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AlumniDirectoryController extends Controller
{
    private AlumniDirectoryService $alumniDirectoryService;
    
    public function __construct(AlumniDirectoryService $alumniDirectoryService)
    {
        $this->alumniDirectoryService = $alumniDirectoryService;
    }
    
    /**
     * Get paginated list of alumni with filters
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'graduation_year_from' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
            'graduation_year_to' => 'nullable|integer|min:1900|max:' . (date('Y') + 10),
            'location' => 'nullable|string|max:255',
            'industries' => 'nullable|array',
            'industries.*' => 'string|max:100',
            'company' => 'nullable|string|max:255',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:50',
            'current_role' => 'nullable|string|max:255',
            'institutions' => 'nullable|array',
            'institutions.*' => 'integer|exists:institutions,id',
            'circles' => 'nullable|array',
            'circles.*' => 'integer|exists:circles,id',
            'groups' => 'nullable|array',
            'groups.*' => 'integer|exists:groups,id',
            'sort_by' => 'nullable|in:name,graduation_year,location,created_at',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1'
        ]);
        
        $pagination = [
            'per_page' => $request->get('per_page', 20),
            'page' => $request->get('page', 1)
        ];
        
        $alumni = $this->alumniDirectoryService->getFilteredAlumni($filters, $pagination);
        
        return response()->json([
            'data' => $alumni->items(),
            'meta' => [
                'current_page' => $alumni->currentPage(),
                'last_page' => $alumni->lastPage(),
                'per_page' => $alumni->perPage(),
                'total' => $alumni->total(),
                'from' => $alumni->firstItem(),
                'to' => $alumni->lastItem()
            ],
            'links' => [
                'first' => $alumni->url(1),
                'last' => $alumni->url($alumni->lastPage()),
                'prev' => $alumni->previousPageUrl(),
                'next' => $alumni->nextPageUrl()
            ]
        ]);
    }
    
    /**
     * Get detailed alumni profile
     */
    public function show(int $userId): JsonResponse
    {
        $currentUser = Auth::user();
        $alumni = $this->alumniDirectoryService->getAlumniProfile($userId, $currentUser);
        
        if (!$alumni) {
            return response()->json([
                'message' => 'Alumni not found'
            ], 404);
        }
        
        return response()->json([
            'data' => $alumni
        ]);
    }
    
    /**
     * Get available filter options
     */
    public function filters(): JsonResponse
    {
        $filters = $this->alumniDirectoryService->getAvailableFilters();
        
        return response()->json([
            'data' => $filters
        ]);
    }
    
    /**
     * Search alumni with autocomplete suggestions
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:255',
            'type' => 'nullable|in:name,company,location,skill',
            'limit' => 'nullable|integer|min:1|max:20'
        ]);
        
        $query = $request->get('query');
        $type = $request->get('type', 'name');
        $limit = $request->get('limit', 10);
        
        $results = $this->getSearchSuggestions($query, $type, $limit);
        
        return response()->json([
            'data' => $results
        ]);
    }
    
    /**
     * Send connection request to alumni
     */
    public function connect(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'message' => 'nullable|string|max:500'
        ]);
        
        $currentUser = Auth::user();
        $targetUser = User::find($userId);
        
        if (!$targetUser) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }
        
        if ($currentUser->id === $targetUser->id) {
            return response()->json([
                'message' => 'Cannot connect to yourself'
            ], 400);
        }
        
        // Check if connection already exists
        $existingConnection = Connection::where(function ($q) use ($currentUser, $targetUser) {
            $q->where('user_id', $currentUser->id)
              ->where('connected_user_id', $targetUser->id);
        })->orWhere(function ($q) use ($currentUser, $targetUser) {
            $q->where('user_id', $targetUser->id)
              ->where('connected_user_id', $currentUser->id);
        })->first();
        
        if ($existingConnection) {
            return response()->json([
                'message' => 'Connection already exists',
                'status' => $existingConnection->status
            ], 400);
        }
        
        // Create connection request
        $connection = Connection::create([
            'user_id' => $currentUser->id,
            'connected_user_id' => $targetUser->id,
            'status' => 'pending',
            'message' => $request->get('message')
        ]);
        
        // Send notification (would be handled by notification service)
        // NotificationService::sendConnectionRequest($targetUser, $currentUser, $connection);
        
        return response()->json([
            'message' => 'Connection request sent successfully',
            'data' => $connection
        ], 201);
    }
    
    /**
     * Get search suggestions based on query and type
     */
    private function getSearchSuggestions(string $query, string $type, int $limit): array
    {
        switch ($type) {
            case 'company':
                return $this->getCompanySuggestions($query, $limit);
            case 'location':
                return $this->getLocationSuggestions($query, $limit);
            case 'skill':
                return $this->getSkillSuggestions($query, $limit);
            default:
                return $this->getNameSuggestions($query, $limit);
        }
    }
    
    /**
     * Get name suggestions
     */
    private function getNameSuggestions(string $query, int $limit): array
    {
        return User::where('name', 'ILIKE', "%{$query}%")
            ->where('is_active', true)
            ->select('id', 'name', 'avatar_url')
            ->limit($limit)
            ->get()
            ->toArray();
    }
    
    /**
     * Get company suggestions
     */
    private function getCompanySuggestions(string $query, int $limit): array
    {
        return \DB::table('work_experiences')
            ->select('company', \DB::raw('COUNT(DISTINCT user_id) as count'))
            ->where('company', 'ILIKE', "%{$query}%")
            ->whereNotNull('company')
            ->where('company', '!=', '')
            ->groupBy('company')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->company,
                    'count' => $item->count
                ];
            })
            ->toArray();
    }
    
    /**
     * Get location suggestions
     */
    private function getLocationSuggestions(string $query, int $limit): array
    {
        return \DB::table('users')
            ->select('location', \DB::raw('COUNT(*) as count'))
            ->where('location', 'ILIKE', "%{$query}%")
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->groupBy('location')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->location,
                    'count' => $item->count
                ];
            })
            ->toArray();
    }
    
    /**
     * Get skill suggestions
     */
    private function getSkillSuggestions(string $query, int $limit): array
    {
        $skills = \DB::table('users')
            ->whereNotNull('skills')
            ->pluck('skills')
            ->filter()
            ->flatMap(function ($skillsJson) {
                return json_decode($skillsJson, true) ?? [];
            })
            ->filter(function ($skill) use ($query) {
                return stripos($skill, $query) !== false;
            })
            ->countBy()
            ->sortDesc()
            ->take($limit)
            ->map(function ($count, $skill) {
                return [
                    'value' => $skill,
                    'count' => $count
                ];
            })
            ->values()
            ->toArray();
        
        return $skills;
    }
}