<?php

namespace App\Jobs;

use App\Models\JobPosting;
use App\Models\User;
use App\Services\JobMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateJobMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private ?int $jobId = null,
        private ?int $userId = null,
        private bool $recalculateAll = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(JobMatchingService $jobMatchingService): void
    {
        try {
            if ($this->recalculateAll) {
                $this->recalculateAllMatches($jobMatchingService);
            } elseif ($this->jobId && $this->userId) {
                $this->calculateSingleMatch($jobMatchingService);
            } elseif ($this->jobId) {
                $this->calculateJobMatches($jobMatchingService);
            } elseif ($this->userId) {
                $this->calculateUserMatches($jobMatchingService);
            }
        } catch (\Exception $e) {
            Log::error('Job matching calculation failed', [
                'job_id' => $this->jobId,
                'user_id' => $this->userId,
                'recalculate_all' => $this->recalculateAll,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate match score for a specific job and user
     */
    private function calculateSingleMatch(JobMatchingService $jobMatchingService): void
    {
        $job = JobPosting::find($this->jobId);
        $user = User::find($this->userId);

        if (! $job || ! $user) {
            Log::warning('Job or user not found for match calculation', [
                'job_id' => $this->jobId,
                'user_id' => $this->userId,
            ]);

            return;
        }

        if (! $job->isActive()) {
            Log::info('Skipping inactive job for match calculation', [
                'job_id' => $this->jobId,
            ]);

            return;
        }

        $jobMatchingService->storeMatchScore($job, $user);

        Log::info('Calculated single job match', [
            'job_id' => $this->jobId,
            'user_id' => $this->userId,
        ]);
    }

    /**
     * Calculate matches for all users for a specific job
     */
    private function calculateJobMatches(JobMatchingService $jobMatchingService): void
    {
        $job = JobPosting::find($this->jobId);

        if (! $job || ! $job->isActive()) {
            Log::warning('Job not found or inactive for match calculation', [
                'job_id' => $this->jobId,
            ]);

            return;
        }

        $processedCount = 0;
        $batchSize = 50;

        // Process users in batches to avoid memory issues
        User::whereHas('educations') // Only users with education data
            ->chunk($batchSize, function ($users) use ($jobMatchingService, $job, &$processedCount) {
                foreach ($users as $user) {
                    try {
                        $jobMatchingService->storeMatchScore($job, $user);
                        $processedCount++;
                    } catch (\Exception $e) {
                        Log::error('Failed to calculate match for user', [
                            'job_id' => $job->id,
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('Calculated job matches for all users', [
            'job_id' => $this->jobId,
            'processed_users' => $processedCount,
        ]);
    }

    /**
     * Calculate matches for all active jobs for a specific user
     */
    private function calculateUserMatches(JobMatchingService $jobMatchingService): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            Log::warning('User not found for match calculation', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        $processedCount = 0;
        $batchSize = 20;

        // Process jobs in batches
        JobPosting::active()
            ->chunk($batchSize, function ($jobs) use ($jobMatchingService, $user, &$processedCount) {
                foreach ($jobs as $job) {
                    try {
                        $jobMatchingService->storeMatchScore($job, $user);
                        $processedCount++;
                    } catch (\Exception $e) {
                        Log::error('Failed to calculate match for job', [
                            'job_id' => $job->id,
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('Calculated user matches for all jobs', [
            'user_id' => $this->userId,
            'processed_jobs' => $processedCount,
        ]);
    }

    /**
     * Recalculate all job matches (expensive operation)
     */
    private function recalculateAllMatches(JobMatchingService $jobMatchingService): void
    {
        $processedCount = 0;
        $jobBatchSize = 10;
        $userBatchSize = 25;

        Log::info('Starting full job match recalculation');

        JobPosting::active()
            ->chunk($jobBatchSize, function ($jobs) use ($jobMatchingService, $userBatchSize, &$processedCount) {
                foreach ($jobs as $job) {
                    User::whereHas('educations')
                        ->chunk($userBatchSize, function ($users) use ($jobMatchingService, $job, &$processedCount) {
                            foreach ($users as $user) {
                                try {
                                    $jobMatchingService->storeMatchScore($job, $user);
                                    $processedCount++;
                                } catch (\Exception $e) {
                                    Log::error('Failed to calculate match in full recalculation', [
                                        'job_id' => $job->id,
                                        'user_id' => $user->id,
                                        'error' => $e->getMessage(),
                                    ]);
                                }
                            }
                        });
                }
            });

        Log::info('Completed full job match recalculation', [
            'total_matches_processed' => $processedCount,
        ]);
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CalculateJobMatchesJob failed', [
            'job_id' => $this->jobId,
            'user_id' => $this->userId,
            'recalculate_all' => $this->recalculateAll,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Create job to calculate matches for a new job posting
     */
    public static function forNewJob(JobPosting $job): self
    {
        return new self($job->id);
    }

    /**
     * Create job to calculate matches for a user (when profile updated)
     */
    public static function forUser(User $user): self
    {
        return new self(null, $user->id);
    }

    /**
     * Create job to calculate a specific job-user match
     */
    public static function forJobAndUser(JobPosting $job, User $user): self
    {
        return new self($job->id, $user->id);
    }

    /**
     * Create job to recalculate all matches (admin operation)
     */
    public static function recalculateAll(): self
    {
        return new self(null, null, true);
    }
}
