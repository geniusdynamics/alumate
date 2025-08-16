<?php

namespace App\Jobs;

use App\Models\SavedSearch;
use App\Services\ElasticsearchService;
use App\Mail\SearchAlertMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ProcessSearchAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(ElasticsearchService $elasticsearchService): void
    {
        Log::info('Processing search alerts');

        $searchesToProcess = SavedSearch::needsAlertProcessing()->get();

        Log::info('Found searches to process', ['count' => $searchesToProcess->count()]);

        foreach ($searchesToProcess as $savedSearch) {
            try {
                $this->processSearchAlert($savedSearch, $elasticsearchService);
            } catch (\Exception $e) {
                Log::error('Failed to process search alert', [
                    'search_id' => $savedSearch->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Completed processing search alerts');
    }

    /**
     * Process a single search alert
     */
    private function processSearchAlert(SavedSearch $savedSearch, ElasticsearchService $elasticsearchService): void
    {
        Log::info('Processing search alert', ['search_id' => $savedSearch->id]);

        // Perform the search
        $results = $elasticsearchService->search(
            $savedSearch->query,
            $savedSearch->filters,
            50, // Get more results for alerts
            0
        );

        $currentResultCount = $results['total'];

        // Check if there are new results
        if (!$savedSearch->hasNewResults($currentResultCount)) {
            Log::info('No new results for search alert', [
                'search_id' => $savedSearch->id,
                'current_count' => $currentResultCount,
                'previous_count' => $savedSearch->last_result_count
            ]);
            
            // Update last run time even if no new results
            $savedSearch->markAsRun($currentResultCount);
            return;
        }

        // Prepare alert data
        $newResultsCount = $currentResultCount - ($savedSearch->last_result_count ?? 0);
        $alertData = [
            'search' => $savedSearch,
            'new_results_count' => $newResultsCount,
            'total_results_count' => $currentResultCount,
            'results' => array_slice($results['hits'], 0, 10), // Show top 10 results
            'search_url' => $this->generateSearchUrl($savedSearch)
        ];

        // Send email alert
        try {
            Mail::to($savedSearch->user->email)->send(new SearchAlertMail($alertData));
            
            Log::info('Search alert email sent', [
                'search_id' => $savedSearch->id,
                'user_email' => $savedSearch->user->email,
                'new_results' => $newResultsCount
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send search alert email', [
                'search_id' => $savedSearch->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        // Update the search record
        $savedSearch->markAsRun($currentResultCount);

        Log::info('Search alert processed successfully', [
            'search_id' => $savedSearch->id,
            'new_results' => $newResultsCount
        ]);
    }

    /**
     * Generate URL for the search
     */
    private function generateSearchUrl(SavedSearch $savedSearch): string
    {
        $baseUrl = config('app.url');
        $searchParams = http_build_query([
            'q' => $savedSearch->query,
            'filters' => json_encode($savedSearch->filters)
        ]);

        return "{$baseUrl}/search?{$searchParams}";
    }
}