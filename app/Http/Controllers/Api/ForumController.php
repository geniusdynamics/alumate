<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ForumController extends Controller
{
    /**
     * Display a listing of forums.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Forum::with(['group'])
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name');

        // Filter by group if specified
        if ($request->has('group_id')) {
            $query->forGroup($request->group_id);
        } else {
            // Only show public forums or group forums user has access to
            $user = Auth::user();
            $query->where(function ($q) use ($user) {
                $q->where('visibility', 'public')
                  ->orWhere(function ($subQ) use ($user) {
                      $subQ->where('visibility', 'group_only')
                           ->whereHas('group.members', function ($memberQ) use ($user) {
                               $memberQ->where('user_id', $user->id);
                           });
                  });
            });
        }

        $forums = $query->get()->map(function ($forum) {
            return [
                'id' => $forum->id,
                'name' => $forum->name,
                'description' => $forum->description,
                'slug' => $forum->slug,
                'color' => $forum->color,
                'icon' => $forum->icon,
                'visibility' => $forum->visibility,
                'topics_count' => $forum->topics_count,
                'posts_count' => $forum->posts_count,
                'last_activity_at' => $forum->last_activity_at,
                'group' => $forum->group ? [
                    'id' => $forum->group->id,
                    'name' => $forum->group->name,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $forums,
        ]);
    }

    /**
     * Store a newly created forum.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Forum::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50',
            'visibility' => 'required|in:public,private,group_only',
            'group_id' => 'nullable|exists:groups,id',
            'requires_approval' => 'boolean',
            'allow_anonymous' => 'boolean',
        ]);

        $forum = Forum::create($validated);

        return response()->json([
            'success' => true,
            'data' => $forum,
            'message' => 'Forum created successfully.',
        ], 201);
    }

    /**
     * Display the specified forum.
     */
    public function show(Forum $forum): JsonResponse
    {
        $user = Auth::user();
        
        if (!$forum->canUserAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this forum.',
            ], 403);
        }

        $forum->load(['group', 'latestTopics.user', 'latestTopics.lastPostUser']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $forum->id,
                'name' => $forum->name,
                'description' => $forum->description,
                'slug' => $forum->slug,
                'color' => $forum->color,
                'icon' => $forum->icon,
                'visibility' => $forum->visibility,
                'topics_count' => $forum->topics_count,
                'posts_count' => $forum->posts_count,
                'last_activity_at' => $forum->last_activity_at,
                'group' => $forum->group ? [
                    'id' => $forum->group->id,
                    'name' => $forum->group->name,
                ] : null,
                'latest_topics' => $forum->latestTopics->take(10)->map(function ($topic) {
                    return [
                        'id' => $topic->id,
                        'title' => $topic->title,
                        'slug' => $topic->slug,
                        'is_sticky' => $topic->is_sticky,
                        'is_announcement' => $topic->is_announcement,
                        'posts_count' => $topic->posts_count,
                        'views_count' => $topic->views_count,
                        'created_at' => $topic->created_at,
                        'last_post_at' => $topic->last_post_at,
                        'user' => [
                            'id' => $topic->user->id,
                            'name' => $topic->user->name,
                            'avatar_url' => $topic->user->avatar_url,
                        ],
                        'last_post_user' => $topic->lastPostUser ? [
                            'id' => $topic->lastPostUser->id,
                            'name' => $topic->lastPostUser->name,
                            'avatar_url' => $topic->lastPostUser->avatar_url,
                        ] : null,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Update the specified forum.
     */
    public function update(Request $request, Forum $forum): JsonResponse
    {
        $this->authorize('update', $forum);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50',
            'visibility' => 'sometimes|in:public,private,group_only',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'requires_approval' => 'boolean',
            'allow_anonymous' => 'boolean',
        ]);

        $forum->update($validated);

        return response()->json([
            'success' => true,
            'data' => $forum,
            'message' => 'Forum updated successfully.',
        ]);
    }

    /**
     * Remove the specified forum.
     */
    public function destroy(Forum $forum): JsonResponse
    {
        $this->authorize('delete', $forum);

        $forum->delete();

        return response()->json([
            'success' => true,
            'message' => 'Forum deleted successfully.',
        ]);
    }
}
