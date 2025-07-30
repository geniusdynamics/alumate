<?php

namespace App\Jobs;

use App\Models\SearchAlert;
use App\Services\ElasticsearchService;
use App\Notifications\SearchAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSearchAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ElasticsearchService $elasticsearchService): void
    {
        Log::info('Processing search alerts');

        $dueAlerts = SearchAlert::due()
            ->with(['user', 'savedSearch'])
            ->get();

        Log::info('Found due alerts', ['count' => $dueAlerts->count()]);

        foreach ($dueAlerts as $alert) {
            try {
                $this->processAlert($alert, $elasticsearchService);
            } catch (\Exception $e) {
                Log::error('Failed to process search alert', [
                    'alert_id' => $alert->id,
                    'user_id' => $alert->user_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Finished processing search alerts');
    }

    /**
     * Process individual search alert
     */
    protected function processAlert(SearchAlert $alert, ElasticsearchService $elasticsearchService): void
    {
        $savedSearch = $alert->savedSearch;
        $user = $alert->user;

        // Get current search results
        $results = $elasticsearchService->searchUsers(
            $savedSearch->query,
            $savedSearch->filters,
            ['size' => 20]
        );

        $currentCount = $results['total'];
        $previousCount = $savedSearch->result_count;

        // Only send notification if there are new results
        if ($currentCount > $previousCount) {
            $newResultsCount = $currentCount - $previousCount;
            
            // Send notification
            $user->notify(new SearchAlertNotification(
                $savedSearch,
                $newResultsCount,
                $results['users']->take(5)->toArray() // Show first 5 new results
            ));

            Log::info('Search alert notification sent', [
                'alert_id' => $alert->id,
                'user_id' => $user->id,
                'new_results' => $newResultsCount
            ]);
        }

        // Update saved search with current count
        $savedSearch->updateResultCount($currentCount);

        // Mark alert as sent
        $alert->markAsSent();
    }
}