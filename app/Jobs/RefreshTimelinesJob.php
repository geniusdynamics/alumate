<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use App\Services\TimelineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshTimelinesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private ?Post $post = null,
        private ?array $userIds = null,
        private bool $refreshAll = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TimelineService $timelineService): void
    {
        try {
            if ($this->refreshAll) {
                $this->refreshAllActiveUserTimelines($timelineService);
            } elseif ($this->post) {
                $this->refreshTimelinesForPost($timelineService);
            } elseif ($this->userIds) {
                $this->refreshSpecificUserTimelines($timelineService);
            }

            Log::info('Timeline refresh job completed successfully', [
                'post_id' => $this->post?->id,
                'user_ids' => $this->userIds,
                'refresh_all' => $this->refreshAll,
            ]);

        } catch (\Exception $e) {
            Log::error('Timeline refresh job failed', [
                'error' => $e->getMessage(),
                'post_id' => $this->post?->id,
                'user_ids' => $this->userIds,
                'refresh_all' => $this->refreshAll,
            ]);

            throw $e;
        }
    }

    /**
     * Refresh timelines for all active users.
     */
    private function refreshAllActiveUserTimelines(TimelineService $timelineService): void
    {
        $batchSize = 100;
        $processedCount = 0;

        User::active()
            ->recentlyActive(7) // Users active in the last 7 days
            ->chunk($batchSize, function ($users) use ($timelineService, &$processedCount) {
                foreach ($users as $user) {
                    try {
                        $timelineService->invalidateTimelineCache($user);
                        
                        // Pre-generate timeline for very active users
                        if ($this->isVeryActiveUser($user)) {
                            $timelineService->generateTimelineForUser($user, 20);
                        }

                        $processedCount++;

                    } catch (\Exception $e) {
                        Log::warning('Failed to refresh timeline for user', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                Log::info("Processed {$processedCount} user timelines");
            });
    }

    /**
     * Refresh timelines for users affected by a new post.
     */
    private function refreshTimelinesForPost(TimelineService $timelineService): void
    {
        if (!$this->post) {
            return;
        }

        // Invalidate cache for affected users
        $timelineService->invalidateTimelineCacheForPost($this->post);

        Log::info('Invalidated timelines for post', [
            'post_id' => $this->post->id,
            'post_user_id' => $this->post->user_id,
        ]);
    }

    /**
     * Refresh timelines for specific users.
     */
    private function refreshSpecificUserTimelines(TimelineService $timelineService): void
    {
        if (!$this->userIds) {
            return;
        }

        $users = User::whereIn('id', $this->userIds)->get();

        foreach ($users as $user) {
            try {
                $timelineService->invalidateTimelineCache($user);
                
                // Pre-generate timeline for active users
                if ($this->isActiveUser($user)) {
                    $timelineService->generateTimelineForUser($user, 20);
                }

            } catch (\Exception $e) {
                Log::warning('Failed to refresh timeline for specific user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Check if user is very active (logged in within last 2 hours).
     */
    private function isVeryActiveUser(User $user): bool
    {
        return $user->last_activity_at && 
               $user->last_activity_at->diffInHours(now()) < 2;
    }

    /**
     * Check if user is active (logged in within last 24 hours).
     */
    private function isActiveUser(User $user): bool
    {
        return $user->last_activity_at && 
               $user->last_activity_at->diffInHours(now()) < 24;
    }

    /**
     * Create job to refresh timelines when a new post is created.
     */
    public static function forNewPost(Post $post): self
    {
        return new self($post);
    }

    /**
     * Create job to refresh specific user timelines.
     */
    public static function forUsers(array $userIds): self
    {
        return new self(userIds: $userIds);
    }

    /**
     * Create job to refresh all active user timelines.
     */
    public static function forAllActiveUsers(): self
    {
        return new self(refreshAll: true);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('RefreshTimelinesJob failed permanently', [
            'error' => $exception->getMessage(),
            'post_id' => $this->post?->id,
            'user_ids' => $this->userIds,
            'refresh_all' => $this->refreshAll,
        ]);
    }
}