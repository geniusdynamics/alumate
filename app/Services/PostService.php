<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Models\Circle;
use App\Models\Group;
use App\Jobs\PublishScheduledPostJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PostService
{
    protected MediaUploadService $mediaUploadService;

    public function __construct(MediaUploadService $mediaUploadService)
    {
        $this->mediaUploadService = $mediaUploadService;
    }

    public function createPost(array $data, User $user): Post
    {
        $this->validatePostData($data);
        
        return DB::transaction(function () use ($data, $user) {
            // Handle media uploads if present
            $mediaUrls = [];
            if (isset($data['media_files']) && !empty($data['media_files'])) {
                $mediaUrls = $this->mediaUploadService->uploadMedia($data['media_files'], $user);
            }
            
            // Determine visibility and target audiences
            $visibility = $this->determineVisibility($data);
            $circleIds = $this->getCircleIds($data, $user);
            $groupIds = $this->getGroupIds($data, $user);
            
            // Check if this is a scheduled post
            if (isset($data['scheduled_for']) && $data['scheduled_for']) {
                return $this->createScheduledPost($data, $user, $mediaUrls, $visibility, $circleIds, $groupIds);
            }
            
            // Create the post
            $post = Post::create([
                'user_id' => $user->id,
                'content' => $data['content'] ?? '',
                'media_urls' => $mediaUrls,
                'post_type' => $data['post_type'] ?? 'text',
                'visibility' => $visibility,
                'circle_ids' => $circleIds,
                'group_ids' => $groupIds,
                'metadata' => $data['metadata'] ?? []
            ]);
            
            // Clear any existing draft
            if (isset($data['draft_id'])) {
                DB::table('post_drafts')->where('id', $data['draft_id'])->where('user_id', $user->id)->delete();
            }
            
            return $post;
        });
    }

    public function updatePost(Post $post, array $data, User $user): Post
    {
        if ($post->user_id !== $user->id) {
            throw new \UnauthorizedHttpException('You can only edit your own posts.');
        }
        
        $this->validatePostData($data, true);
        
        return DB::transaction(function () use ($post, $data, $user) {
            // Handle new media uploads
            $mediaUrls = $post->media_urls ?? [];
            if (isset($data['media_files']) && !empty($data['media_files'])) {
                $newMedia = $this->mediaUploadService->uploadMedia($data['media_files'], $user);
                $mediaUrls = array_merge($mediaUrls, $newMedia);
            }
            
            // Handle media deletions
            if (isset($data['delete_media']) && !empty($data['delete_media'])) {
                $mediaUrls = $this->removeMediaFromPost($mediaUrls, $data['delete_media']);
            }
            
            // Update visibility and audiences
            $visibility = $this->determineVisibility($data);
            $circleIds = $this->getCircleIds($data, $user);
            $groupIds = $this->getGroupIds($data, $user);
            
            $post->update([
                'content' => $data['content'] ?? $post->content,
                'media_urls' => $mediaUrls,
                'post_type' => $data['post_type'] ?? $post->post_type,
                'visibility' => $visibility,
                'circle_ids' => $circleIds,
                'group_ids' => $groupIds,
                'metadata' => array_merge($post->metadata ?? [], $data['metadata'] ?? [])
            ]);
            
            return $post->fresh();
        });
    }

    public function deletePost(Post $post, User $user): bool
    {
        if ($post->user_id !== $user->id) {
            throw new \UnauthorizedHttpException('You can only delete your own posts.');
        }
        
        return DB::transaction(function () use ($post) {
            // Delete associated media files
            if ($post->media_urls) {
                $this->mediaUploadService->deleteMedia($post->media_urls);
            }
            
            // Soft delete the post
            return $post->delete();
        });
    }

    public function uploadMedia(array $files, User $user): array
    {
        return $this->mediaUploadService->uploadMedia($files, $user);
    }

    public function saveDraft(array $data, User $user): array
    {
        $draftData = [
            'user_id' => $user->id,
            'content' => $data['content'] ?? '',
            'media_urls' => $data['media_urls'] ?? [],
            'post_type' => $data['post_type'] ?? 'text',
            'visibility' => $data['visibility'] ?? 'public',
            'circle_ids' => $data['circle_ids'] ?? [],
            'group_ids' => $data['group_ids'] ?? [],
            'metadata' => $data['metadata'] ?? [],
            'scheduled_for' => isset($data['scheduled_for']) ? Carbon::parse($data['scheduled_for']) : null
        ];
        
        if (isset($data['draft_id'])) {
            DB::table('post_drafts')
                ->where('id', $data['draft_id'])
                ->where('user_id', $user->id)
                ->update($draftData);
            $draftId = $data['draft_id'];
        } else {
            $draftId = DB::table('post_drafts')->insertGetId($draftData);
        }
        
        return ['draft_id' => $draftId, 'saved_at' => now()];
    }

    protected function createScheduledPost(array $data, User $user, array $mediaUrls, string $visibility, array $circleIds, array $groupIds): array
    {
        $scheduledFor = Carbon::parse($data['scheduled_for']);
        
        if ($scheduledFor->isPast()) {
            throw new ValidationException('Scheduled time must be in the future.');
        }
        
        $scheduledPostId = DB::table('scheduled_posts')->insertGetId([
            'user_id' => $user->id,
            'content' => $data['content'] ?? '',
            'media_urls' => json_encode($mediaUrls),
            'post_type' => $data['post_type'] ?? 'text',
            'visibility' => $visibility,
            'circle_ids' => json_encode($circleIds),
            'group_ids' => json_encode($groupIds),
            'metadata' => json_encode($data['metadata'] ?? []),
            'scheduled_for' => $scheduledFor,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Schedule the job
        PublishScheduledPostJob::dispatch($scheduledPostId)->delay($scheduledFor);
        
        return [
            'scheduled_post_id' => $scheduledPostId,
            'scheduled_for' => $scheduledFor,
            'message' => 'Post scheduled successfully'
        ];
    }

    protected function validatePostData(array $data, bool $isUpdate = false): void
    {
        $rules = [
            'content' => $isUpdate ? 'sometimes|string|max:5000' : 'required|string|max:5000',
            'post_type' => 'sometimes|in:text,media,career_update,achievement,event',
            'visibility' => 'sometimes|in:public,circles,groups,specific',
            'circle_ids' => 'sometimes|array',
            'circle_ids.*' => 'integer|exists:circles,id',
            'group_ids' => 'sometimes|array',
            'group_ids.*' => 'integer|exists:groups,id',
            'scheduled_for' => 'sometimes|date|after:now',
            'metadata' => 'sometimes|array'
        ];
        
        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    protected function determineVisibility(array $data): string
    {
        if (isset($data['visibility'])) {
            return $data['visibility'];
        }
        
        // Auto-determine based on circles/groups
        if (!empty($data['group_ids'])) {
            return 'groups';
        } elseif (!empty($data['circle_ids'])) {
            return 'circles';
        }
        
        return 'public';
    }

    protected function getCircleIds(array $data, User $user): array
    {
        if (isset($data['circle_ids']) && is_array($data['circle_ids'])) {
            // Verify user belongs to these circles
            $userCircleIds = $user->circles()->pluck('circles.id')->toArray();
            return array_intersect($data['circle_ids'], $userCircleIds);
        }
        
        // Default to all user's circles if visibility is 'circles'
        if (($data['visibility'] ?? 'public') === 'circles') {
            return $user->circles()->pluck('circles.id')->toArray();
        }
        
        return [];
    }

    protected function getGroupIds(array $data, User $user): array
    {
        if (isset($data['group_ids']) && is_array($data['group_ids'])) {
            // Verify user belongs to these groups
            $userGroupIds = $user->groups()->pluck('groups.id')->toArray();
            return array_intersect($data['group_ids'], $userGroupIds);
        }
        
        return [];
    }

    protected function removeMediaFromPost(array $mediaUrls, array $mediaToDelete): array
    {
        foreach ($mediaToDelete as $mediaIndex) {
            if (isset($mediaUrls[$mediaIndex])) {
                // Delete the actual files
                $this->mediaUploadService->deleteMedia([$mediaUrls[$mediaIndex]]);
                unset($mediaUrls[$mediaIndex]);
            }
        }
        
        return array_values($mediaUrls); // Re-index array
    }
}