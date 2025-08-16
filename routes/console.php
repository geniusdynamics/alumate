<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Homepage monitoring scheduled tasks will be handled by the MonitorHomepage command class

Artisan::command('homepage:cleanup-metrics', function () {
    $this->info('Cleaning up old performance metrics...');

    // Keep metrics for 30 days
    $deleted = DB::table('homepage_performance_metrics')
        ->where('recorded_at', '<', now()->subDays(30))
        ->delete();

    $this->info("Deleted {$deleted} old performance metrics");

    // Keep analytics events for 90 days
    $deleted = DB::table('homepage_analytics_events')
        ->where('event_timestamp', '<', now()->subDays(90))
        ->delete();

    $this->info("Deleted {$deleted} old analytics events");

})->purpose('Clean up old monitoring data')->daily();
