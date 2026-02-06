<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\LearningProgress;
use App\Services\Analytics\LearningAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Learning Score Job
 *
 * Processes batch learning score calculations for engagement analytics.
 * Handles large datasets by chunking and provides error recovery.
 */
class LearningScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes
    public int $backoff = 60; // 1 minute delay between retries

    /**
     * Create a new job instance.
     */
    public function __construct(
        private array $userCoursePairs,
        private string $tenantId,
        private bool $dispatchInsightsJob = true
    ) {}

    /**
     * Execute the job.
     */
    public function handle(LearningAnalyticsService $learningService): void
    {
        Log::info('Starting LearningScoreJob', [
            'pairs_count' => count($this->userCoursePairs),
            'tenant_id' => $this->tenantId
        ]);

        $processed = 0;
        $errors = 0;
        $significantChanges = [];

        // Set tenant context
        session(['tenant_id' => $this->tenantId]);

        // Process in chunks to avoid memory issues
        $chunks = array_chunk($this->userCoursePairs, 50);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $pair) {
                try {
                    $userId = $pair['user_id'];
                    $courseId = $pair['course_id'];

                    // Calculate new engagement score
                    $newScore = $learningService->calculateEngagementScore($userId, $courseId);

                    // Get current progress record
                    $progress = LearningProgress::byTenant($this->tenantId)
                        ->byUser($userId)
                        ->byCourse($courseId)
                        ->first();

                    if ($progress) {
                        $oldScore = $progress->engagement_score ?? 0;

                        // Update the progress record
                        $progress->update([
                            'engagement_score' => $newScore,
                            'updated_at' => now()
                        ]);

                        // Track significant changes (>10% difference)
                        if (abs($newScore - $oldScore) > 10) {
                            $significantChanges[] = [
                                'user_id' => $userId,
                                'course_id' => $courseId,
                                'old_score' => $oldScore,
                                'new_score' => $newScore,
                                'change' => $newScore - $oldScore
                            ];
                        }
                    }

                    $processed++;

                } catch (\Exception $e) {
                    Log::error('Failed to process learning score in job', [
                        'user_id' => $pair['user_id'] ?? null,
                        'course_id' => $pair['course_id'] ?? null,
                        'error' => $e->getMessage(),
                        'tenant_id' => $this->tenantId
                    ]);

                    $errors++;
                }
            }

            // Small delay between chunks to prevent overwhelming the system
            sleep(1);
        }

        Log::info('Completed LearningScoreJob', [
            'processed' => $processed,
            'errors' => $errors,
            'significant_changes' => count($significantChanges),
            'tenant_id' => $this->tenantId
        ]);

        // Dispatch insights generation job if there were significant changes
        if ($this->dispatchInsightsJob && !empty($significantChanges)) {
            try {
                // Group changes by user for insights generation
                $userChanges = collect($significantChanges)->groupBy('user_id');

                foreach ($userChanges as $userId => $changes) {
                    // Dispatch insights job for users with significant learning changes
                    \App\Jobs\InsightsGenerationJob::dispatch([
                        'user_id' => $userId,
                        'type' => 'learning_engagement',
                        'data' => [
                            'changes' => $changes->toArray(),
                            'total_impact' => $changes->sum('change')
                        ]
                    ], $this->tenantId);
                }

                Log::info('Dispatched insights jobs for significant learning changes', [
                    'users_affected' => $userChanges->count(),
                    'tenant_id' => $this->tenantId
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to dispatch insights generation job', [
                    'error' => $e->getMessage(),
                    'tenant_id' => $this->tenantId
                ]);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('LearningScoreJob failed completely', [
            'error' => $exception->getMessage(),
            'pairs_count' => count($this->userCoursePairs),
            'tenant_id' => $this->tenantId,
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'learning-analytics',
            'engagement-scoring',
            'tenant:' . $this->tenantId,
            'batch-size:' . count($this->userCoursePairs)
        ];
    }
}