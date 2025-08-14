<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostEngagement;
use App\Events\PostEngagement as PostEngagementEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PostEngagementController extends Controller
{
    /**
     * Like or unlike a post
     */
    public function like(Request $request, Post $post): JsonResponse
    {
        $user = Auth::user();
        
        try {
            DB::beginTransaction();
            
            // Check if user already liked the post
            $existingLike = PostEngagement::where([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'type' => 'like'
            ])->first();
            
            if ($existingLike) {
                // Unlike the post
                $existingLike->delete();
                $action = 'unliked';
            } else {
                // Like the post
                PostEngagement::create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                    'type' => 'like',
                    'data' => null,
                ]);
                $action = 'liked';
            }
            
            // Broadcast the engagement event
            broadcast(new PostEngagementEvent($post, $user, 'like', ['action' => $action]));
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'action' => $action,
                'message' => $action === 'liked' ? 'Post liked successfully' : 'Post unliked successfully',
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process like action',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Add a comment to a post
     */
    public function comment(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:post_engagements,id',
        ]);
        
        $user = Auth::user();
        
        try {
            DB::beginTransaction();
            
            $comment = PostEngagement::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'type' => 'comment',
                'data' => [
                    'content' => $request->content,
                    'parent_id' => $request->parent_id,
                ],
            ]);
            
            // Load the comment with user relationship
            $comment->load('user');
            
            // Broadcast the engagement event
            broadcast(new PostEngagementEvent($post, $user, 'comment', [
                'id' => $comment->id,
                'content' => $request->content,
                'parent_id' => $request->parent_id,
                'created_at' => $comment->created_at,
            ]));
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'comment' => [
                    'id' => $comment->id,
                    'content' => $request->content,
                    'parent_id' => $request->parent_id,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'avatar_url' => $user->avatar_url,
                    ],
                    'created_at' => $comment->created_at,
                ],
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Share a post
     */
    public function share(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'message' => 'nullable|string|max:500',
            'visibility' => 'in:public,connections,circles,private',
            'circle_ids' => 'nullable|array',
            'circle_ids.*' => 'exists:circles,id',
        ]);
        
        $user = Auth::user();
        
        try {
            DB::beginTransaction();
            
            // Check if user already shared this post
            $existingShare = PostEngagement::where([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'type' => 'share'
            ])->first();
            
            if ($existingShare) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already shared this post',
                ], 400);
            }
            
            // Create share engagement
            $share = PostEngagement::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'type' => 'share',
                'data' => [
                    'message' => $request->message,
                    'visibility' => $request->visibility ?? 'connections',
                    'circle_ids' => $request->circle_ids ?? [],
                ],
            ]);
            
            // Create a new post for the share (if user added a message)
            if ($request->message) {
                $sharedPost = Post::create([
                    'user_id' => $user->id,
                    'content' => $request->message,
                    'post_type' => 'share',
                    'visibility' => $request->visibility ?? 'connections',
                    'circle_ids' => $request->circle_ids ?? [],
                    'shared_post_id' => $post->id,
                ]);
            }
            
            // Broadcast the engagement event
            broadcast(new PostEngagementEvent($post, $user, 'share', [
                'message' => $request->message,
                'visibility' => $request->visibility ?? 'connections',
                'shared_post_id' => $sharedPost->id ?? null,
            ]));
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Post shared successfully',
                'share' => [
                    'id' => $share->id,
                    'message' => $request->message,
                    'shared_post_id' => $sharedPost->id ?? null,
                    'created_at' => $share->created_at,
                ],
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to share post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Add a reaction to a post
     */
    public function reaction(Request $request, Post $post): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:like,love,celebrate,support,insightful,funny',
        ]);
        
        $user = Auth::user();
        
        try {
            DB::beginTransaction();
            
            // Remove any existing reaction from this user
            PostEngagement::where([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'type' => 'reaction'
            ])->delete();
            
            // Add the new reaction
            $reaction = PostEngagement::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'type' => 'reaction',
                'data' => [
                    'reaction_type' => $request->type,
                ],
            ]);
            
            // Broadcast the engagement event
            broadcast(new PostEngagementEvent($post, $user, 'reaction', [
                'type' => $request->type,
            ]));
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Reaction added successfully',
                'reaction' => [
                    'id' => $reaction->id,
                    'type' => $request->type,
                    'created_at' => $reaction->created_at,
                ],
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add reaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get engagement statistics for a post
     */
    public function stats(Post $post): JsonResponse
    {
        try {
            $stats = [
                'likes' => PostEngagement::where('post_id', $post->id)
                    ->where('type', 'like')
                    ->count(),
                'comments' => PostEngagement::where('post_id', $post->id)
                    ->where('type', 'comment')
                    ->count(),
                'shares' => PostEngagement::where('post_id', $post->id)
                    ->where('type', 'share')
                    ->count(),
                'reactions' => PostEngagement::where('post_id', $post->id)
                    ->where('type', 'reaction')
                    ->count(),
            ];
            
            // Get reaction breakdown
            $reactionBreakdown = PostEngagement::where('post_id', $post->id)
                ->where('type', 'reaction')
                ->select(DB::raw('JSON_EXTRACT(data, "$.reaction_type") as reaction_type'), DB::raw('COUNT(*) as count'))
                ->groupBy('reaction_type')
                ->pluck('count', 'reaction_type')
                ->toArray();
            
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'reaction_breakdown' => $reactionBreakdown,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get engagement stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}