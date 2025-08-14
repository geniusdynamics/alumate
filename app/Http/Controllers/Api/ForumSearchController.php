<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumTag;
use App\Services\ForumService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ForumSearchController extends Controller
{
    public function __construct(
        private ForumService $forumService
    ) {}

    /**
     * Search topics across forums.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2',
            'forum_id' => 'nullable|exists:forums,id',
            'tag' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'sort' => 'nullable|in:relevance,newest,oldest,popular,activity',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $user = Auth::user();
        $topics = $this->forumService->searchTopics(
            $validated['query'],
            $user,
            $validated
        );

        return response()->json([
            'success' => true,
            'data' => $topics->map(function ($topic) {
                return [
                    'id' => $topic->id,
                    'title' => $topic->title,
                    'slug' => $topic->slug,
                    'content' => substr($topic->content, 0, 200) . '...',
                    'posts_count' => $topic->posts_count,
                    'views_count' => $topic->views_count,
                    'created_at' => $topic->created_at,
                    'last_post_at' => $topic->last_post_at,
                    'forum' => [
                        'id' => $topic->forum->id,
                        'name' => $topic->forum->name,
                        'slug' => $topic->forum->slug,
                    ],
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
                ];
            }),
            'query' => $validated['query'],
            'total' => $topics->count(),
        ]);
    }

    /**
     * Get popular tags.
     */
    public function getTags(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 20);
        $featured = $request->boolean('featured');

        $query = ForumTag::query();

        if ($featured) {
            $query->featured();
        } else {
            $query->popular($limit);
        }

        $tags = $query->get()->map(function ($tag) {
            return [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'color' => $tag->color,
                'usage_count' => $tag->usage_count,
                'is_featured' => $tag->is_featured,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $tags,
        ]);
    }

    /**
     * Get topics by tag.
     */
    public function getTopicsByTag(Request $request, ForumTag $tag): JsonResponse
    {
        $user = Auth::user();
        
        $topics = $tag->topics()
            ->with(['forum', 'user', 'lastPostUser'])
            ->whereHas('forum', function ($query) use ($user) {
                $query->active()->where(function ($subQ) use ($user) {
                    $subQ->where('visibility', 'public')
                         ->orWhere(function ($groupQ) use ($user) {
                             $groupQ->where('visibility', 'group_only')
                                    ->whereHas('group.members', function ($memberQ) use ($user) {
                                        $memberQ->where('user_id', $user->id);
                                    });
                         });
                });
            })
            ->active()
            ->orderBy('is_sticky', 'desc')
            ->orderBy('last_post_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $topics->items(),
            'tag' => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'color' => $tag->color,
                'description' => $tag->description,
                'usage_count' => $tag->usage_count,
            ],
            'pagination' => [
                'current_page' => $topics->currentPage(),
                'last_page' => $topics->lastPage(),
                'per_page' => $topics->perPage(),
                'total' => $topics->total(),
            ],
        ]);
    }
}
