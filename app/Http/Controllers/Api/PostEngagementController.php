<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Comment;
use App\Services\PostEngagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class PostEngagementController extends Controller
{
    protected PostEngagementService $engagementService;

    public function __construct(PostEngagementService $engagementService)
    {
        $this->engagementService = $engagementService;
    }

    /**
     * Add or update a reaction to a post.
     */
    public function react(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'type' => ['required', Rule::in(['like', 'love', 'celebrate', 'support', 'insightful'])]
        ]);

        try {
            $engagement = $this->engagementService->addReaction(
                $post,
                $request->user(),
                $request->type
            );

            return response()->json([
                'success' => true,
                'message' => 'Reaction added successfully',
                'engagement' => $engagement,
                'stats' => $this->engagementService->getEngagementStats($post),
                'user_engagement' => $this->engagementService->getUserEngagement($post, $request->user())
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add reaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a reaction from a post.
     */
    public function unreact(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'type' => ['required', Rule::in(['like', 'love', 'celebrate', 'support', 'insightful'])]
        ]);

        try {
            $removed = $this->engagementService->removeReaction(
                $post,
                $request->user(),
                $request->type
            );

            return response()->json([
                'success' => true,
                'message' => $removed ? 'Reaction removed successfully' : 'Reaction not found',
                'stats' => $this->engagementService->getEngagementStats($post),
                'user_engagement' => $this->engagementService->getUserEngagement($post, $request->user())
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove reaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a comment to a post.
     */
    public function comment(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|integer|exists:comments,id'
        ]);

        try {
            $comment = $this->engagementService->addComment(
                $post,
                $request->user(),
                $request->content,
                $request->parent_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'comment' => $comment->load(['user:id,name,username,avatar_url', 'replies.user:id,name,username,avatar_url']),
                'stats' => $this->engagementService->getEngagementStats($post)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Share a post.
     */
    public function share(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'commentary' => 'nullable|string|max:1000'
        ]);

        try {
            $sharedPost = $this->engagementService->sharePost(
                $post,
                $request->user(),
                $request->commentary
            );

            return response()->json([
                'success' => true,
                'message' => 'Post shared successfully',
                'shared_post' => $sharedPost->load('user:id,name,username,avatar_url'),
                'stats' => $this->engagementService->getEngagementStats($post),
                'user_engagement' => $this->engagementService->getUserEngagement($post, $request->user())
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to share post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bookmark or unbookmark a post.
     */
    public function bookmark(Request $request, Post $post): JsonResponse
    {
        try {
            $user = $request->user();
            $userEngagement = $this->engagementService->getUserEngagement($post, $user);

            if ($userEngagement['bookmarked']) {
                // Remove bookmark
                $this->engagementService->removeBookmark($post, $user);
                $message = 'Bookmark removed successfully';
                $bookmarked = false;
            } else {
                // Add bookmark
                $this->engagementService->bookmarkPost($post, $user);
                $message = 'Post bookmarked successfully';
                $bookmarked = true;
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'bookmarked' => $bookmarked,
                'stats' => $this->engagementService->getEngagementStats($post)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle bookmark',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get engagement statistics for a post.
     */
    public function stats(Request $request, Post $post): JsonResponse
    {
        try {
            $stats = $this->engagementService->getEngagementStats($post);
            $userEngagement = $this->engagementService->getUserEngagement($post, $request->user());

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'user_engagement' => $userEngagement
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get engagement stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users who reacted with a specific reaction type.
     */
    public function reactionUsers(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'type' => ['required', Rule::in(['like', 'love', 'celebrate', 'support', 'insightful', 'share'])],
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        try {
            $users = $this->engagementService->getReactionUsers(
                $post,
                $request->type,
                $request->get('limit', 10)
            );

            return response()->json([
                'success' => true,
                'users' => $users,
                'type' => $request->type
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get reaction users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comments for a post.
     */
    public function getComments(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50'
        ]);

        try {
            $comments = Comment::where('post_id', $post->id)
                ->whereNull('parent_id') // Only top-level comments
                ->with([
                    'user:id,name,username,avatar_url',
                    'allReplies.user:id,name,username,avatar_url'
                ])
                ->latest()
                ->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'comments' => $comments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get comments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search users for mentions.
     */
    public function searchMentions(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1|max:50',
            'limit' => 'nullable|integer|min:1|max:20'
        ]);

        try {
            $users = $this->engagementService->searchUsersForMention(
                $request->query,
                $request->get('limit', 10)
            );

            return response()->json([
                'success' => true,
                'users' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search users',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}