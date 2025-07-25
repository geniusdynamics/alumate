<?php

namespace App\Console\Commands;

use App\Services\MatchingService;
use Illuminate\Console\Command;

class RefreshJobMatches extends Command
{
    protected $signature = 'matching:refresh-matches {--cleanup : Clean up old matches}';
    protected $description = 'Refresh job-graduate matches and optionally clean up old ones';

    protected $matchingService;

    public function __construct(MatchingService $matchingService)
    {
        parent::__construct();
        $this->matchingService = $matchingService;
    }

    public function handle()
    {
        $this->info('Refreshing job-graduate matches...');

        if ($this->option('cleanup')) {
            $this->info('Cleaning up old matches...');
            $deleted = $this->matchingService->cleanupOldMatches();
            $this->info("Deleted {$deleted} old matches.");
        }

        $totalMatches = $this->matchingService->refreshAllMatches();

        $this->info("Calculated {$totalMatches} job-graduate matches.");

        // Display statistics
        $stats = $this->matchingService->getMatchingStatistics();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Matches', $stats['total_matches']],
                ['High Quality Matches (80%+)', $stats['high_quality_matches']],
                ['Average Match Score', round($stats['avg_match_score'], 2) . '%'],
                ['Match Success Rate', $stats['match_success_rate'] . '%'],
            ]
        );

        return 0;
    }
}