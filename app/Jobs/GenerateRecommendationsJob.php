<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AlumniRecommendationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateRecommendationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    private ?int $userId;
    private bool $forAllUsers;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $userId = null, bool $forAllUsers = false)
    {
        $this->userId = $userId;
        $this->forAllUsers = $forAllUsers;
    }

    /**
     * Execute the job.
     */
    public function handle(AlumniRecommendationService $recommendationService): void
    {
        try {
            if ($this->forAllUsers) {
                $this->generateForAllUsers($recommendationService);
            } elseif ($this->userId) {
                $this->generateForUser($this->userId, $recommendationService);
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate recommendations', [
                'user_id' => $this->userId,
                'for_all_users' => $this->forAllUsers,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Generate recommendations for a specific user
     */
    private function generateForUser(int $userId, AlumniRecommendationService $recommendationService): void
    {
        $user = User::find($userId);
        
        if (!$user) {
            Log::warning('User not found for recommendation generation', ['user_id' => $userId]);
            return;
        }

        Log::info('Generating recommendations for user', ['user_id' => $userId]);

        // Clear existing cache and generate fresh recommendations
        $recommendationService->clearRecommendationCache($user);
        $recommendations = $recommendationService->getRecommendationsForUser($user, 20);

        Log::info('Generated recommendations for user', [
            'user_id' => $userId,
            'recommendation_count' => $recommendations->count()
        ]);
    }

    /**
     * Generate recommendations for all active users
     */
    private function generateForAllUsers(AlumniRecommendationService $recommendationService): void
    {
        Log::info('Starting bulk recommendation generation for all users');

        $batchSize = 50;
        $processedCount = 0;
        $errorCount = 0;

        User::whereNotNull('last_login_at')
            ->where('last_login_at', '>=', now()->subDays(30))
            ->chunk($batchSize, function ($users) use ($recommendationService, &$processedCount, &$errorCount) {
                foreach ($users as $user) {
                    try {
                        $recommendationService->clearRecommendationCache($user);
                        $recommendations = $recommendationService->getRecommendationsForUser($user, 20);
                        $processedCount++;

                        if ($processedCount % 100 === 0) {
                            Log::info('Bulk recommendation generation progress', [
                                'processed' => $processedCount,
                                'errors' => $errorCount
                            ]);
                        }
                    } catch (\Exception $e) {
                        $errorCount++;
                        Log::error('Failed to generate recommendations for user', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

        Log::info('Completed bulk recommendation generation', [
            'total_processed' => $processedCount,
            'total_errors' => $errorCount
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateRecommendationsJob failed', [
            'user_id' => $this->userId,
            'for_all_users' => $this->forAllUsers,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        $tags = ['recommendations'];
        
        if ($this->userId) {
            $tags[] = "user:{$this->userId}";
        }
        
        if ($this->forAllUsers) {
            $tags[] = 'bulk-generation';
        }
        
        return $tags;
    }
}