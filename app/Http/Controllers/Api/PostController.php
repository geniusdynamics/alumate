<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    protected PostService $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
        $this->middleware('auth:sanctum');
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            
            // Handle file uploads
            if ($request->hasFile('media_files')) {
                $data['media_files'] = $request->file('media_files');
            }
            
            $result = $this->postService->createPost($data, Auth::user());
            
            // Handle scheduled posts
            if (is_array($result)) {
                return response()->json([
                    'success' => true,
                    'data' => $result
                ], 201);
            }
            
            // Regular post creation
            $post = $result->load(['user', 'engagements']);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'post' => $post,
                    'message' => 'Post created successfully'
                ]
            ], 201);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Post $post): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check if user can view this post
            if (!$this->canUserViewPost($post, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this post'
                ], 403);
            }
            
            $post->load(['user', 'engagements.user', 'comments.user']);
            
            return response()->json([
                'success' => true,
                'data' => ['post' => $post]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        try {
            $data = $request->all();
            
            // Handle file uploads
            if ($request->hasFile('media_files')) {
                $data['media_files'] = $request->file('media_files');
            }
            
            $updatedPost = $this->postService->updatePost($post, $data, Auth::user());
            $updatedPost->load(['user', 'engagements']);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'post' => $updatedPost,
                    'message' => 'Post updated successfully'
                ]
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\UnauthorizedHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Post $post): JsonResponse
    {
        try {
            $this->postService->deletePost($post, Auth::user());
            
            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully'
            ]);
            
        } catch (\UnauthorizedHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete post: ' . $e->getMessage()
            ], 500);
        }
    }

    public function uploadMedia(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'files' => 'required|array|max:10',
                'files.*' => 'file|max:102400' // 100MB max per file
            ]);
            
            $uploadedMedia = $this->postService->uploadMedia($request->file('files'), Auth::user());
            
            return response()->json([
                'success' => true,
                'data' => [
                    'media' => $uploadedMedia,
                    'message' => 'Media uploaded successfully'
                ]
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload media: ' . $e->getMessage()
            ], 500);
        }
    }

    public function saveDraft(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $result = $this->postService->saveDraft($data, Auth::user());
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save draft: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDrafts(): JsonResponse
    {
        try {
            $drafts = \DB::table('post_drafts')
                ->where('user_id', Auth::id())
                ->orderBy('updated_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => ['drafts' => $drafts]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve drafts: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getScheduledPosts(): JsonResponse
    {
        try {
            $scheduledPosts = \DB::table('scheduled_posts')
                ->where('user_id', Auth::id())
                ->where('status', 'pending')
                ->orderBy('scheduled_for', 'asc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => ['scheduled_posts' => $scheduledPosts]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve scheduled posts: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function canUserViewPost(Post $post, $user): bool
    {
        // Public posts are visible to everyone
        if ($post->visibility === 'public') {
            return true;
        }
        
        // User can always see their own posts
        if ($post->user_id === $user->id) {
            return true;
        }
        
        // Check circle visibility
        if ($post->visibility === 'circles' && $post->circle_ids) {
            $userCircleIds = $user->circles()->pluck('circles.id')->toArray();
            return !empty(array_intersect($post->circle_ids, $userCircleIds));
        }
        
        // Check group visibility
        if ($post->visibility === 'groups' && $post->group_ids) {
            $userGroupIds = $user->groups()->pluck('groups.id')->toArray();
            return !empty(array_intersect($post->group_ids, $userGroupIds));
        }
        
        return false;
    }
}