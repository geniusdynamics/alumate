<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TimelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TimelineController extends Controller
{
    public function __construct(
        private TimelineService $timelineService
    ) {}

    /**
     * Get the user's timeline.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'limit' => 'integer|min:1|max:50',
                'cursor' => 'string|nullable',
            ]);

            $user = Auth::user();
            $limit = $request->get('limit', 20);
            $cursor = $request->get('cursor');

            $timeline = $this->timelineService->generateTimelineForUser($user, $limit, $cursor);

            return response()->json([
                'success' => true,
                'data' => $timeline,
                'message' => 'Timeline retrieved successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve timeline',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Refresh the user's timeline (clear cache and regenerate).
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'limit' => 'integer|min:1|max:50',
            ]);

            $user = Auth::user();
            $limit = $request->get('limit', 20);

            // Invalidate cache first
            $this->timelineService->invalidateTimelineCache($user);

            // Generate fresh timeline
            $timeline = $this->timelineService->generateTimelineForUser($user, $limit);

            return response()->json([
                'success' => true,
                'data' => $timeline,
                'message' => 'Timeline refreshed successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh timeline',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Load more posts for infinite scroll.
     */
    public function loadMore(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'cursor' => 'required|string',
                'limit' => 'integer|min:1|max:50',
            ]);

            $user = Auth::user();
            $cursor = $request->get('cursor');
            $limit = $request->get('limit', 20);

            $timeline = $this->timelineService->generateTimelineForUser($user, $limit, $cursor);

            return response()->json([
                'success' => true,
                'data' => $timeline,
                'message' => 'More posts loaded successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load more posts',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get posts from user's circles only.
     */
    public function circles(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'limit' => 'integer|min:1|max:50',
                'cursor' => 'string|nullable',
            ]);

            $user = Auth::user();
            $limit = $request->get('limit', 20);
            $cursor = $request->get('cursor');

            $posts = $this->timelineService->getCirclePosts($user, $limit, $cursor);
            $nextCursor = $posts->isNotEmpty() ? $this->generateCursor($posts->last()) : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'posts' => $posts->values()->toArray(),
                    'next_cursor' => $nextCursor,
                    'has_more' => $posts->count() === $limit,
                ],
                'message' => 'Circle posts retrieved successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve circle posts',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get posts from user's groups only.
     */
    public function groups(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'limit' => 'integer|min:1|max:50',
                'cursor' => 'string|nullable',
            ]);

            $user = Auth::user();
            $limit = $request->get('limit', 20);
            $cursor = $request->get('cursor');

            $posts = $this->timelineService->getGroupPosts($user, $limit, $cursor);
            $nextCursor = $posts->isNotEmpty() ? $this->generateCursor($posts->last()) : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'posts' => $posts->values()->toArray(),
                    'next_cursor' => $nextCursor,
                    'has_more' => $posts->count() === $limit,
                ],
                'message' => 'Group posts retrieved successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve group posts',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Generate cursor for pagination.
     */
    private function generateCursor($post): string
    {
        return base64_encode(json_encode([
            'id' => $post->id,
            'created_at' => $post->created_at->toISOString(),
        ]));
    }
}
