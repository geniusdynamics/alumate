<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\ForumTag;
use App\Models\ForumTopic;
use App\Models\ForumTopicSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForumTopicController extends Controller
{
    /**
     * Display a listing of topics for a forum.
     */
    public function index(Request $request, Forum $forum): JsonResponse
    {
        $user = Auth::user();

        if (! $forum->canUserAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this forum.',
            ], 403);
        }

        $query = $forum->topics()
            ->with(['user', 'lastPostUser', 'tags'])
            ->active();

        // Filter by tag if specified
        if ($request->has('tag')) {
            $query->withTag($request->tag);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort', 'activity');
        switch ($sortBy) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'popular':
                $query->orderBy('views_count', 'desc');
                break;
            case 'activity':
            default:
                $query->orderBy('is_sticky', 'desc')
                    ->orderBy('is_announcement', 'desc')
                    ->orderBy('last_post_at', 'desc');
                break;
        }

        $topics = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $topics->items(),
            'pagination' => [
                'current_page' => $topics->currentPage(),
                'last_page' => $topics->lastPage(),
                'per_page' => $topics->perPage(),
                'total' => $topics->total(),
            ],
        ]);
    }

    /**
     * Store a newly created topic.
     */
    public function store(Request $request, Forum $forum): JsonResponse
    {
        $user = Auth::user();

        if (! $forum->canUserAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this forum.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        DB::beginTransaction();
        try {
            $topic = $forum->topics()->create([
                'user_id' => $user->id,
                'title' => $validated['title'],
                'content' => $validated['content'],
                'is_approved' => ! $forum->requires_approval,
            ]);

            // Handle tags
            if (! empty($validated['tags'])) {
                $tagIds = [];
                foreach ($validated['tags'] as $tagName) {
                    $tag = ForumTag::firstOrCreate(
                        ['name' => $tagName],
                        ['slug' => \Str::slug($tagName)]
                    );
                    $tag->incrementUsage();
                    $tagIds[] = $tag->id;
                }
                $topic->tags()->sync($tagIds);
            }

            // Auto-subscribe the creator to the topic
            ForumTopicSubscription::create([
                'topic_id' => $topic->id,
                'user_id' => $user->id,
            ]);

            DB::commit();

            $topic->load(['user', 'tags']);

            return response()->json([
                'success' => true,
                'data' => $topic,
                'message' => 'Topic created successfully.',
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create topic.',
            ], 500);
        }
    }

    /**
     * Display the specified topic.
     */
    public function show(Forum $forum, ForumTopic $topic): JsonResponse
    {
        $user = Auth::user();

        if (! $forum->canUserAccess($user) || $topic->forum_id !== $forum->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        // Increment view count
        $topic->incrementViews();

        // Load relationships
        $topic->load([
            'user',
            'tags',
            'posts' => function ($query) {
                $query->approved()
                    ->with(['user', 'replies.user'])
                    ->orderBy('created_at');
            },
        ]);

        // Check if user is subscribed
        $subscription = ForumTopicSubscription::where('topic_id', $topic->id)
            ->where('user_id', $user->id)
            ->first();

        if ($subscription) {
            $subscription->markAsRead();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $topic->id,
                'title' => $topic->title,
                'slug' => $topic->slug,
                'content' => $topic->content,
                'status' => $topic->status,
                'is_sticky' => $topic->is_sticky,
                'is_announcement' => $topic->is_announcement,
                'posts_count' => $topic->posts_count,
                'views_count' => $topic->views_count,
                'likes_count' => $topic->likes_count,
                'created_at' => $topic->created_at,
                'last_post_at' => $topic->last_post_at,
                'user' => [
                    'id' => $topic->user->id,
                    'name' => $topic->user->name,
                    'avatar_url' => $topic->user->avatar_url,
                ],
                'tags' => $topic->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                        'color' => $tag->color,
                    ];
                }),
                'posts' => $topic->posts->map(function ($post) {
                    return [
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
                    ];
                }),
                'is_subscribed' => $subscription !== null,
            ],
        ]);
    }

    /**
     * Update the specified topic.
     */
    public function update(Request $request, Forum $forum, ForumTopic $topic): JsonResponse
    {
        $user = Auth::user();

        if ($topic->user_id !== $user->id && ! $user->hasRole(['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to edit this topic.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'status' => 'sometimes|in:active,locked,archived',
            'is_sticky' => 'boolean',
            'is_announcement' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        DB::beginTransaction();
        try {
            $topic->update($validated);

            // Handle tags if provided
            if (isset($validated['tags'])) {
                // Remove usage count from old tags
                foreach ($topic->tags as $oldTag) {
                    $oldTag->decrementUsage();
                }

                $tagIds = [];
                foreach ($validated['tags'] as $tagName) {
                    $tag = ForumTag::firstOrCreate(
                        ['name' => $tagName],
                        ['slug' => \Str::slug($tagName)]
                    );
                    $tag->incrementUsage();
                    $tagIds[] = $tag->id;
                }
                $topic->tags()->sync($tagIds);
            }

            DB::commit();

            $topic->load(['user', 'tags']);

            return response()->json([
                'success' => true,
                'data' => $topic,
                'message' => 'Topic updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update topic.',
            ], 500);
        }
    }

    /**
     * Remove the specified topic.
     */
    public function destroy(Forum $forum, ForumTopic $topic): JsonResponse
    {
        $user = Auth::user();

        if ($topic->user_id !== $user->id && ! $user->hasRole(['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this topic.',
            ], 403);
        }

        // Decrement tag usage counts
        foreach ($topic->tags as $tag) {
            $tag->decrementUsage();
        }

        $topic->delete();

        return response()->json([
            'success' => true,
            'message' => 'Topic deleted successfully.',
        ]);
    }

    /**
     * Subscribe/unsubscribe to a topic.
     */
    public function toggleSubscription(Forum $forum, ForumTopic $topic): JsonResponse
    {
        $user = Auth::user();

        if (! $forum->canUserAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        $subscription = ForumTopicSubscription::where('topic_id', $topic->id)
            ->where('user_id', $user->id)
            ->first();

        if ($subscription) {
            $subscription->delete();
            $subscribed = false;
            $message = 'Unsubscribed from topic.';
        } else {
            ForumTopicSubscription::create([
                'topic_id' => $topic->id,
                'user_id' => $user->id,
            ]);
            $subscribed = true;
            $message = 'Subscribed to topic.';
        }

        return response()->json([
            'success' => true,
            'data' => ['subscribed' => $subscribed],
            'message' => $message,
        ]);
    }
}
