<?php

namespace App\Console\Commands;

use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateAnalyticsSnapshots extends Command
{
    protected $signature = 'analytics:generate-snapshots 
                            {--type=daily : Type of snapshot to generate (daily, weekly, monthly, graduate_outcomes, course_roi, employer_engagement, community_health, platform_benchmarks, market_trends, system_growth)}
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
                case 'graduate_outcomes':
                    $this->generateGraduateOutcomesSnapshots($date, $force);
                    break;
                case 'course_roi':
                    $this->generateCourseRoiSnapshots($date, $force);
                    break;
                case 'employer_engagement':
                    $this->generateEmployerEngagementSnapshots($date, $force);
                    break;
                case 'community_health':
                    $this->generateCommunityHealthSnapshots($date, $force);
                    break;
                case 'platform_benchmarks':
                    $this->generatePlatformBenchmarksSnapshots($date, $force);
                    break;
                case 'market_trends':
                    $this->generateMarketTrendsSnapshots($date, $force);
                    break;
                case 'system_growth':
                    $this->generateSystemGrowthSnapshots($date, $force);
                    break;
                default:
                    $this->error("Invalid snapshot type: {$type}");

                    return 1;
            }

            $this->info('Analytics snapshots generated successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to generate snapshots: '.$e->getMessage());

            return 1;
        }
    }

    private function generateDailySnapshots($date = null, $force = false)
    {
        if ($date) {
            $dates = [Carbon::parse($date)];
        } else {
            // Generate for the last 7 days if no date specified
            $dates = collect(range(0, 6))->map(fn ($i) => now()->subDays($i));
        }

        $bar = $this->output->createProgressBar($dates->count());
        $bar->start();

        foreach ($dates as $snapshotDate) {
            $dateString = $snapshotDate->toDateString();

            // Check if snapshot already exists
            if (! $force && \App\Models\AnalyticsSnapshot::getSnapshotForDate('daily', $dateString)) {
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
            $dates = collect(range(0, 3))->map(fn ($i) => now()->subWeeks($i)->startOfWeek());
        }

        $bar = $this->output->createProgressBar($dates->count());
        $bar->start();

        foreach ($dates as $snapshotDate) {
            $dateString = $snapshotDate->toDateString();

            // Check if snapshot already exists
            if (! $force && \App\Models\AnalyticsSnapshot::getSnapshotForDate('weekly', $dateString)) {
                $bar->advance();

                continue;
            }

            $this->analyticsService->generateWeeklySnapshot($dateString);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function generateGraduateOutcomesSnapshots($date = null, $force = false)
    {
        $this->info('Generating graduate outcome snapshots...');
        $snapshotDate = $date ? Carbon::parse($date) : now();
        $dateString = $snapshotDate->toDateString();

        if (! $force && \App\Models\AnalyticsSnapshot::getSnapshotForDate('graduate_outcomes', $dateString)) {
            $this->info('Snapshot for today already exists. Use --force to regenerate.');
            return;
        }

        $this->analyticsService->generateGraduateOutcomeSnapshot($dateString);
        $this->info('Graduate outcome snapshot generated for '.$dateString);
    }

    private function generateCourseRoiSnapshots($date = null, $force = false)
    {
        $this->info('Generating course ROI snapshots...');
        $snapshotDate = $date ? Carbon::parse($date) : now();
        $dateString = $snapshotDate->toDateString();

        if (! $force && \App\Models\AnalyticsSnapshot::getSnapshotForDate('course_roi', $dateString)) {
            $this->info('Snapshot for today already exists. Use --force to regenerate.');
            return;
        }

        $metrics = $this->analyticsService->getCourseRoiMetrics();
        \App\Models\AnalyticsSnapshot::updateOrCreate(
            ['type' => 'course_roi', 'date' => $dateString],
            ['data' => $metrics]
        );

        $this->info('Course ROI snapshot generated for '.$dateString);
    }

    private function generateEmployerEngagementSnapshots($date = null, $force = false)
    {
        $this->info('Generating employer engagement snapshots...');
        $snapshotDate = $date ? Carbon::parse($date) : now();
        $dateString = $snapshotDate->toDateString();

        if (! $force && \App\Models\AnalyticsSnapshot::getSnapshotForDate('employer_engagement', $dateString)) {
            $this->info('Snapshot for today already exists. Use --force to regenerate.');
            return;
        }

        $metrics = $this->analyticsService->getEmployerEngagementMetrics();
        \App\Models\AnalyticsSnapshot::updateOrCreate(
            ['type' => 'employer_engagement', 'date' => $dateString],
            ['data' => $metrics]
        );

        $this->info('Employer engagement snapshot generated for '.$dateString);
    }

    private function generateCommunityHealthSnapshots($date = null, $force = false)
    {
        $this->info('Generating community health snapshots...');
        $snapshotDate = $date ? Carbon::parse($date) : now();
        $dateString = $snapshotDate->toDateString();

        if (! $force && \App\Models\AnalyticsSnapshot::getSnapshotForDate('community_health', $dateString)) {
            $this->info('Snapshot for today already exists. Use --force to regenerate.');
            return;
        }

        $metrics = $this->analyticsService->getCommunityHealthMetrics();
        \App\Models\AnalyticsSnapshot::updateOrCreate(
            ['type' => 'community_health', 'date' => $dateString],
            ['data' => $metrics]
        );

        $this->info('Community health snapshot generated for '.$dateString);
    }

    private function generatePlatformBenchmarksSnapshots($date = null, $force = false)
    {
        $this->info('Generating platform benchmarks snapshots...');
        $snapshotDate = $date ? Carbon::parse($date) : now();
        $dateString = $snapshotDate->toDateString();

        if (! $force && \App\Models\AnalyticsSnapshot::getSnapshotForDate('platform_benchmarks', $dateString)) {
            $this->info('Snapshot for today already exists. Use --force to regenerate.');
            return;
        }

        $metrics = $this->analyticsService->getPlatformBenchmarks();
        \App\Models\AnalyticsSnapshot::updateOrCreate(
            ['type' => 'platform_benchmarks', 'date' => $dateString],
            ['data' => $metrics]
        );

        $this->info('Platform benchmarks snapshot generated for '.$dateString);
    }

    private function generateMarketTrendsSnapshots($date = null, $force = false)
    {
        $this->info('Generating market trends snapshots...');
        $snapshotDate = $date ? Carbon::parse($date) : now();
        $dateString = $snapshotDate->toDateString();

        if (! $force && \App\Models\AnalyticsSnapshot::getSnapshotForDate('market_trends', $dateString)) {
            $this->info('Snapshot for today already exists. Use --force to regenerate.');
            return;
        }

        $metrics = $this->analyticsService->getMarketTrends();
        \App\Models\AnalyticsSnapshot::updateOrCreate(
            ['type' => 'market_trends', 'date' => $dateString],
            ['data' => $metrics]
        );

        $this->info('Market trends snapshot generated for '.$dateString);
    }

    private function generateSystemGrowthSnapshots($date = null, $force = false)
    {
        $this->info('Generating system growth snapshots...');
        $snapshotDate = $date ? Carbon::parse($date) : now();
        $dateString = $snapshotDate->toDateString();

        if (! $force && \App\Models\AnalyticsSnapshot::getSnapshotForDate('system_growth', $dateString)) {
            $this->info('Snapshot for today already exists. Use --force to regenerate.');
            return;
        }

        $metrics = $this->analyticsService->getSystemGrowthMetrics();
        \App\Models\AnalyticsSnapshot::updateOrCreate(
            ['type' => 'system_growth', 'date' => $dateString],
            ['data' => $metrics]
        );

        $this->info('System growth snapshot generated for '.$dateString);
    }

    private function generateMonthlySnapshots($date = null, $force = false)
    {
        if ($date) {
            $dates = [Carbon::parse($date)->startOfMonth()];
        } else {
            // Generate for the last 6 months if no date specified
            $dates = collect(range(0, 5))->map(fn ($i) => now()->subMonths($i)->startOfMonth());
        }

        $bar = $this->output->createProgressBar($dates->count());
        $bar->start();

        foreach ($dates as $snapshotDate) {
            $dateString = $snapshotDate->toDateString();

            // Check if snapshot already exists
            if (! $force && \App\Models\AnalyticsSnapshot::getSnapshotForDate('monthly', $dateString)) {
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
