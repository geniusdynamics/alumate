<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublishScheduledPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $scheduledPostId;

    public function __construct(int $scheduledPostId)
    {
        $this->scheduledPostId = $scheduledPostId;
    }

    public function handle(): void
    {
        try {
            $scheduledPost = DB::table('scheduled_posts')
                ->where('id', $this->scheduledPostId)
                ->where('status', 'pending')
                ->first();

            if (! $scheduledPost) {
                Log::warning("Scheduled post {$this->scheduledPostId} not found or already processed");

                return;
            }

            // Check if it's time to publish
            if (now()->lt($scheduledPost->scheduled_for)) {
                Log::info("Scheduled post {$this->scheduledPostId} not ready for publishing yet");

                return;
            }

            $user = User::find($scheduledPost->user_id);
            if (! $user) {
                $this->markAsFailed($scheduledPost, 'User not found');

                return;
            }

            DB::transaction(function () use ($scheduledPost) {
                // Create the actual post
                $post = Post::create([
                    'user_id' => $scheduledPost->user_id,
                    'content' => $scheduledPost->content,
                    'media_urls' => json_decode($scheduledPost->media_urls, true),
                    'post_type' => $scheduledPost->post_type,
                    'visibility' => $scheduledPost->visibility,
                    'circle_ids' => json_decode($scheduledPost->circle_ids, true),
                    'group_ids' => json_decode($scheduledPost->group_ids, true),
                    'metadata' => json_decode($scheduledPost->metadata, true),
                    'created_at' => $scheduledPost->scheduled_for,
                    'updated_at' => now(),
                ]);

                // Mark scheduled post as published
                DB::table('scheduled_posts')
                    ->where('id', $this->scheduledPostId)
                    ->update([
                        'status' => 'published',
                        'published_at' => now(),
                        'updated_at' => now(),
                    ]);

                Log::info("Successfully published scheduled post {$this->scheduledPostId} as post {$post->id}");
            });

        } catch (\Exception $e) {
            Log::error("Failed to publish scheduled post {$this->scheduledPostId}: ".$e->getMessage());

            $scheduledPost = DB::table('scheduled_posts')->where('id', $this->scheduledPostId)->first();
            if ($scheduledPost) {
                $this->markAsFailed($scheduledPost, $e->getMessage());
            }

            throw $e;
        }
    }

    protected function markAsFailed($scheduledPost, string $reason): void
    {
        DB::table('scheduled_posts')
            ->where('id', $this->scheduledPostId)
            ->update([
                'status' => 'failed',
                'metadata' => json_encode(array_merge(
                    json_decode($scheduledPost->metadata ?? '{}', true),
                    ['failure_reason' => $reason, 'failed_at' => now()]
                )),
                'updated_at' => now(),
            ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("PublishScheduledPostJob failed for post {$this->scheduledPostId}: ".$exception->getMessage());

        $scheduledPost = DB::table('scheduled_posts')->where('id', $this->scheduledPostId)->first();
        if ($scheduledPost) {
            $this->markAsFailed($scheduledPost, $exception->getMessage());
        }
    }
}
