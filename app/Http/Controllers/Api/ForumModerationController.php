<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ForumService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ForumModerationController extends Controller
{
    public function __construct(
        private ForumService $forumService
    ) {}

    /**
     * Moderate content (approve/reject/delete).
     */
    public function moderate(Request $request, string $type, int $id): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user has moderation permissions
        if (!$user->hasRole(['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions for moderation.',
            ], 403);
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,reject,delete',
            'reason' => 'nullable|string|max:500',
        ]);

        $success = $this->forumService->moderateContent(
            $type,
            $id,
            $validated['action'],
            $user
        );

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found or action failed.',
            ], 404);
        }

        $actionMessages = [
            'approve' => 'Content approved successfully.',
            'reject' => 'Content rejected successfully.',
            'delete' => 'Content deleted successfully.',
        ];

        return response()->json([
            'success' => true,
            'message' => $actionMessages[$validated['action']],
        ]);
    }

    /**
     * Get content pending moderation.
     */
    public function getPending(): JsonResponse
    {
        $user = Auth::user();
        
        // Check if user has moderation permissions
        if (!$user->hasRole(['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions for moderation.',
            ], 403);
        }

        $pendingContent = $this->forumService->getPendingModeration($user);

        return response()->json([
            'success' => true,
            'data' => [
                'topics' => $pendingContent['topics']->map(function ($topic) {
                    return [
                        'id' => $topic->id,
                        'title' => $topic->title,
                        'content' => substr($topic->content, 0, 200) . '...',
                        'created_at' => $topic->created_at,
                        'user' => [
                            'id' => $topic->user->id,
                            'name' => $topic->user->name,
                            'avatar_url' => $topic->user->avatar_url,
                        ],
                        'forum' => [
                            'id' => $topic->forum->id,
                            'name' => $topic->forum->name,
                        ],
                    ];
                }),
                'posts' => $pendingContent['posts']->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'content' => substr($post->content, 0, 200) . '...',
                        'created_at' => $post->created_at,
                        'user' => [
                            'id' => $post->user->id,
                            'name' => $post->user->name,
                            'avatar_url' => $post->user->avatar_url,
                        ],
                        'topic' => [
                            'id' => $post->topic->id,
                            'title' => $post->topic->title,
                            'forum' => [
                                'id' => $post->topic->forum->id,
                                'name' => $post->topic->forum->name,
                            ],
                        ],
                    ];
                }),
            ],
            'counts' => [
                'pending_topics' => $pendingContent['topics']->count(),
                'pending_posts' => $pendingContent['posts']->count(),
            ],
        ]);
    }
}
