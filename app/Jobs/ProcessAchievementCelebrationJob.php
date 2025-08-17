<?php

namespace App\Jobs;

use App\Models\AchievementCelebration;
use App\Services\AchievementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAchievementCelebrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private AchievementCelebration $celebration
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AchievementService $achievementService): void
    {
        try {
            // Create social post for the achievement
            $post = $achievementService->createAchievementPost($this->celebration);

            Log::info('Achievement celebration processed', [
                'celebration_id' => $this->celebration->id,
                'post_id' => $post->id,
                'user_id' => $this->celebration->recipient->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process achievement celebration', [
                'celebration_id' => $this->celebration->id,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger job retry
            throw $e;
        }
    }
}
