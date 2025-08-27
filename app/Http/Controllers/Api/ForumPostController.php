<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumPost;
use App\Models\ForumPostLike;
use App\Models\ForumTopic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForumPostController extends Controller
{
    /**
     * Store a newly created post.
     */
    public function store(Request $request, ForumTopic $topic): JsonResponse
    {
        $user = Auth::user();

        if (! $topic->forum->canUserAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        if ($topic->status === 'locked') {
            return response()->json([
                'success' => false,
                'message' => 'This topic is locked.',
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:forum_posts,id',
        ]);

        // Validate parent post belongs to the same topic
        if ($validated['parent_id']) {
            $parentPost = ForumPost::find($validated['parent_id']);
            if ($parentPost->topic_id !== $topic->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parent post.',
                ], 400);
            }
        }

        $post = $topic->posts()->create([
            'user_id' => $user->id,
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'],
            'is_approved' => ! $topic->forum->requires_approval,
        ]);

        $post->load('user');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $post->id,
                'content' => $post->content,
                'depth' => $post->depth,
                'parent_id' => $post->parent_id,
                'likes_count' => $post->likes_count,
                'is_solution' => $post->is_solution,
                'created_at' => $post->created_at,
                'user' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'avatar_url' => $post->user->avatar_url,
                ],
            ],
            'message' => 'Post created successfully.',
        ], 201);
    }

    /**
     * Display the specified post.
     */
    public function show(ForumPost $post): JsonResponse
    {
        $user = Auth::user();

        if (! $post->topic->forum->canUserAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        $post->load(['user', 'replies.user']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $post->id,
                'content' => $post->content,
                'content_html' => $post->content_html,
                'depth' => $post->depth,
                'parent_id' => $post->parent_id,
                'likes_count' => $post->likes_count,
                'is_solution' => $post->is_solution,
                'created_at' => $post->created_at,
                'edited_at' => $post->edited_at,
                'user' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'avatar_url' => $post->user->avatar_url,
                ],
                'replies' => $post->replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'content' => $reply->content,
                        'created_at' => $reply->created_at,
                        'user' => [
                            'id' => $reply->user->id,
                            'name' => $reply->user->name,
                            'avatar_url' => $reply->user->avatar_url,
                        ],
                    ];
                }),
                'has_liked' => $post->hasUserLiked($user),
            ],
        ]);
    }

    /**
     * Update the specified post.
     */
    public function update(Request $request, ForumPost $post): JsonResponse
    {
        $user = Auth::user();

        if ($post->user_id !== $user->id && ! $user->hasRole(['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to edit this post.',
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'edit_reason' => 'nullable|string|max:255',
        ]);

        $post->update([
            'content' => $validated['content'],
            'edited_at' => now(),
            'edited_by' => $user->id,
            'edit_reason' => $validated['edit_reason'] ?? null,
        ]);

        $post->load('user');

        return response()->json([
            'success' => true,
            'data' => $post,
            'message' => 'Post updated successfully.',
        ]);
    }

    /**
     * Remove the specified post.
     */
    public function destroy(ForumPost $post): JsonResponse
    {
        $user = Auth::user();

        if ($post->user_id !== $user->id && ! $user->hasRole(['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this post.',
            ], 403);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully.',
        ]);
    }

    /**
     * Like/unlike a post.
     */
    public function toggleLike(ForumPost $post): JsonResponse
    {
        $user = Auth::user();

        if (! $post->topic->forum->canUserAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        $like = ForumPostLike::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if ($like) {
            $like->delete();
            $liked = false;
            $message = 'Post unliked.';
        } else {
            ForumPostLike::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'type' => 'like',
            ]);
            $liked = true;
            $message = 'Post liked.';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'liked' => $liked,
                'likes_count' => $post->fresh()->likes_count,
            ],
            'message' => $message,
        ]);
    }

    /**
     * Mark post as solution.
     */
    public function markAsSolution(ForumPost $post): JsonResponse
    {
        $user = Auth::user();
        $topic = $post->topic;

        // Only topic creator or moderators can mark solutions
        if ($topic->user_id !== $user->id && ! $user->hasRole(['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to mark solution.',
            ], 403);
        }

        $post->markAsSolution();

        return response()->json([
            'success' => true,
            'message' => 'Post marked as solution.',
        ]);
    }
}
