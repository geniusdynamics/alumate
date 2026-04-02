<?php

declare(strict_types=1);

namespace App\Jobs\Analytics;

use App\Services\Analytics\ConsentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to generate insights asynchronously
 *
 * This job handles the heavy computation for generating analytics insights,
 * allowing the main application to respond quickly while analysis runs in the background.
 */
class InsightsGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private array $options
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        AutomatedInsightsService $insightsService,
        ConsentService $consentService
    ): void {
        try {
            Log::info('Starting InsightsGenerationJob', [
                'options' => $this->options,
            ]);

            // Check consent for data access
            if (! $consentService->hasConsent()) {
                Log::warning('Insights generation attempted without data processing consent');

                return;
            }

            // Generate insights with the provided options
            $insights = $insightsService->generateInsights($this->options);

            Log::info('InsightsGenerationJob completed', [
                'insight_count' => count($insights),
                'options' => $this->options,
            ]);

        } catch (\Exception $e) {
            Log::error('InsightsGenerationJob failed', [
                'options' => $this->options,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'analytics',
            'insights-generation',
            'tenant:'.($this->options['tenant_id'] ?? 'unknown'),
        ];
    }
}
