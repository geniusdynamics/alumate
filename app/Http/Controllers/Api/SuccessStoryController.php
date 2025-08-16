<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SuccessStory;
use App\Services\SuccessStoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SuccessStoryController extends Controller
{
    public function __construct(
        private SuccessStoryService $successStoryService
    ) {}

    /**
     * Display a listing of success stories with filters
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'industry',
            'achievement_type',
            'graduation_year',
            'tags',
            'search',
        ]);

        $perPage = $request->get('per_page', 12);
        $stories = $this->successStoryService->getStoriesWithFilters($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $stories,
        ]);
    }

    /**
     * Store a newly created success story
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'required|string|max:500',
            'content' => 'required|string',
            'featured_image_file' => 'nullable|image|max:2048',
            'media_files.*' => 'nullable|file|max:10240',
            'industry' => 'nullable|string|max:100',
            'achievement_type' => 'required|string|max:100',
            'current_role' => 'nullable|string|max:255',
            'current_company' => 'nullable|string|max:255',
            'graduation_year' => 'nullable|string|max:4',
            'degree_program' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'demographics' => 'nullable|array',
            'status' => ['nullable', Rule::in(['draft', 'published'])],
            'allow_social_sharing' => 'boolean',
        ]);

        $story = $this->successStoryService->createStory($validated, Auth::user());

        return response()->json([
            'success' => true,
            'message' => 'Success story created successfully',
            'data' => $story->load('user'),
        ], 201);
    }

    /**
     * Display the specified success story
     */
    public function show(SuccessStory $successStory): JsonResponse
    {
        // Increment view count
        $successStory->incrementViewCount();

        return response()->json([
            'success' => true,
            'data' => $successStory->load('user'),
        ]);
    }

    /**
     * Update the specified success story
     */
    public function update(Request $request, SuccessStory $successStory): JsonResponse
    {
        // Check if user can update this story
        if ($successStory->user_id !== Auth::id() && ! Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this story',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'summary' => 'sometimes|required|string|max:500',
            'content' => 'sometimes|required|string',
            'featured_image_file' => 'nullable|image|max:2048',
            'media_files.*' => 'nullable|file|max:10240',
            'industry' => 'nullable|string|max:100',
            'achievement_type' => 'sometimes|required|string|max:100',
            'current_role' => 'nullable|string|max:255',
            'current_company' => 'nullable|string|max:255',
            'graduation_year' => 'nullable|string|max:4',
            'degree_program' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'demographics' => 'nullable|array',
            'status' => ['nullable', Rule::in(['draft', 'published'])],
            'allow_social_sharing' => 'boolean',
        ]);

        $story = $this->successStoryService->updateStory($successStory, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Success story updated successfully',
            'data' => $story->load('user'),
        ]);
    }

    /**
     * Remove the specified success story
     */
    public function destroy(SuccessStory $successStory): JsonResponse
    {
        // Check if user can delete this story
        if ($successStory->user_id !== Auth::id() && ! Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this story',
            ], 403);
        }

        $this->successStoryService->deleteStory($successStory);

        return response()->json([
            'success' => true,
            'message' => 'Success story deleted successfully',
        ]);
    }

    /**
     * Get featured success stories
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 6);
        $stories = $this->successStoryService->getFeaturedStories($limit);

        return response()->json([
            'success' => true,
            'data' => $stories,
        ]);
    }

    /**
     * Get recommended success stories for the authenticated user
     */
    public function recommended(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 6);
        $stories = $this->successStoryService->getRecommendedStories(Auth::user(), $limit);

        return response()->json([
            'success' => true,
            'data' => $stories,
        ]);
    }

    /**
     * Get success stories by demographics for diversity showcase
     */
    public function byDemographics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'demographics' => 'required|array',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $limit = $validated['limit'] ?? 12;
        $stories = $this->successStoryService->getStoriesByDemographics(
            $validated['demographics'],
            $limit
        );

        return response()->json([
            'success' => true,
            'data' => $stories,
        ]);
    }

    /**
     * Share a success story (increment share count)
     */
    public function share(SuccessStory $successStory): JsonResponse
    {
        if (! $successStory->allow_social_sharing) {
            return response()->json([
                'success' => false,
                'message' => 'This story does not allow social sharing',
            ], 403);
        }

        $successStory->incrementShareCount();

        return response()->json([
            'success' => true,
            'message' => 'Share count updated',
            'data' => [
                'share_data' => $successStory->getSocialShareData(),
                'share_count' => $successStory->share_count,
            ],
        ]);
    }

    /**
     * Like a success story
     */
    public function like(SuccessStory $successStory): JsonResponse
    {
        $successStory->incrementLikeCount();

        return response()->json([
            'success' => true,
            'message' => 'Story liked successfully',
            'data' => [
                'like_count' => $successStory->like_count,
            ],
        ]);
    }

    /**
     * Get analytics for success stories (admin only)
     */
    public function analytics(): JsonResponse
    {
        if (! Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $analytics = $this->successStoryService->getAnalytics();

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Feature/unfeature a success story (admin only)
     */
    public function toggleFeature(SuccessStory $successStory): JsonResponse
    {
        if (! Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($successStory->is_featured) {
            $successStory->unfeature();
            $message = 'Story unfeatured successfully';
        } else {
            $successStory->feature();
            $message = 'Story featured successfully';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $successStory->fresh(),
        ]);
    }
}
