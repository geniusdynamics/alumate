<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use App\Models\Consent;
use App\Models\Insight;
use App\Services\Analytics\GoogleAnalyticsService;
use App\Services\Analytics\MatomoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to purge user data after consent revocation
 */
class ConsentPurgeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;
    public string $consentType;
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, string $consentType = 'analytics')
    {
        $this->userId = $userId;
        $this->consentType = $consentType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting consent purge for user {$this->userId}, type: {$this->consentType}");

        try {
            // Check if consent is still revoked (30-day retention period)
            $consent = Consent::byUser($this->userId)
                              ->byType($this->consentType)
                              ->expired()
                              ->first();

            if (!$consent) {
                Log::info("Consent not expired yet for user {$this->userId}, skipping purge");
                return;
            }

            // Purge analytics events in chunks
            AnalyticsEvent::byUser($this->userId)->chunk(1000, function ($events) {
                foreach ($events as $event) {
                    $event->delete(); // Soft delete or hard delete based on requirements
                }
            });

            // Purge insights
            Insight::where('user_id', $this->userId)->delete();

            // Opt-out from external platforms
            $this->optOutFromExternalPlatforms();

            Log::info("Completed consent purge for user {$this->userId}");

        } catch (\Exception $e) {
            Log::error("Failed to purge consent data for user {$this->userId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Opt-out from external analytics platforms
     */
    private function optOutFromExternalPlatforms(): void
    {
        try {
            // TODO: Implement opt-out methods in GoogleAnalyticsService and MatomoService
            // For now, just log the intent
            Log::info("User {$this->userId} opted out - external platform opt-out pending implementation");

        } catch (\Exception $e) {
            Log::warning("Failed to opt-out user from external platforms: " . $e->getMessage());
        }
    }
}