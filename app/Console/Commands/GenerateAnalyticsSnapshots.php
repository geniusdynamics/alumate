<?php

namespace App\Console\Commands;

use App\Services\AnalyticsService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerateAnalyticsSnapshots extends Command
{
    protected $signature = 'analytics:generate-snapshots 
                            {--type=daily : Type of snapshot to generate (daily, weekly, monthly)}
                            {--date= : Specific date to generate snapshot for (YYYY-MM-DD)}
                            {--force : Force regeneration even if snapshot exists}';

    protected $description = 'Generate analytics snapshots for historical data tracking';

    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    public function handle()
    {
        $type = $this->option('type');
        $date = $this->option('date');
        $force = $this->option('force');

        $this->info("Generating {$type} analytics snapshots...");

        try {
            switch ($type) {
                case 'daily':
                    $this->generateDailySnapshots($date, $force);
                    break;
                case 'weekly':
                    $this->generateWeeklySnapshots($date, $force);
                    break;
                case 'monthly':
                    $this->generateMonthlySnapshots($date, $force);
                    break;
                default:
                    $this->error("Invalid snapshot type: {$type}");
                    return 1;
            }

            $this->info("Analytics snapshots generated successfully!");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to generate snapshots: " . $e->getMessage());
            return 1;
        }
    }

    private function generateDailySnapshots($date = null, $force = false)
    {
        if ($date) {
            $dates = [Carbon::parse($date)];
        } else {
            // Generate for the last 7 days if no date specified
            $dates = collect(range(0, 6))->map(fn($i) => now()->subDays($i));
        }

        $bar = $this->output->createProgressBar($dates->count());
        $bar->start();

        foreach ($dates as $snapshotDate) {
            $dateString = $snapshotDate->toDateString();
            
            // Check if snapshot already exists
            if (!$force && \App\Models\AnalyticsSnapshot::getSnapshotForDate('daily', $dateString)) {
                $bar->advance();
                continue;
            }

            $this->analyticsService->generateDailySnapshot($dateString);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function generateWeeklySnapshots($date = null, $force = false)
    {
        if ($date) {
            $dates = [Carbon::parse($date)->startOfWeek()];
        } else {
            // Generate for the last 4 weeks if no date specified
            $dates = collect(range(0, 3))->map(fn($i) => now()->subWeeks($i)->startOfWeek());
        }

        $bar = $this->output->createProgressBar($dates->count());
        $bar->start();

        foreach ($dates as $snapshotDate) {
            $dateString = $snapshotDate->toDateString();
            
            // Check if snapshot already exists
            if (!$force && \App\Models\AnalyticsSnapshot::getSnapshotForDate('weekly', $dateString)) {
                $bar->advance();
                continue;
            }

            $this->analyticsService->generateWeeklySnapshot($dateString);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function generateMonthlySnapshots($date = null, $force = false)
    {
        if ($date) {
            $dates = [Carbon::parse($date)->startOfMonth()];
        } else {
            // Generate for the last 6 months if no date specified
            $dates = collect(range(0, 5))->map(fn($i) => now()->subMonths($i)->startOfMonth());
        }

        $bar = $this->output->createProgressBar($dates->count());
        $bar->start();

        foreach ($dates as $snapshotDate) {
            $dateString = $snapshotDate->toDateString();
            
            // Check if snapshot already exists
            if (!$force && \App\Models\AnalyticsSnapshot::getSnapshotForDate('monthly', $dateString)) {
                $bar->advance();
                continue;
            }

            $this->analyticsService->generateMonthlySnapshot($dateString);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}