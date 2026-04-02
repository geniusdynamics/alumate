<?php

declare(strict_types=1);

use App\Models\Connection;
use App\Models\Event;
use App\Models\Graduate;
use App\Models\Job;
use App\Services\Homepage\HomepageStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(HomepageStatisticsService::class);
});

it('returns base statistics for general audience', function () {
    // Arrange
    Graduate::factory()->count(100)->create();
    Event::factory()->count(5)->create();

    // Act
    $stats = $this->service->getPlatformStatistics('general');

    // Assert
    expect($stats)->toBeArray()
        ->and($stats['total_alumni'])->toBe(100)
        ->and($stats['events_hosted'])->toBe(5)
        ->and($stats)->toHaveKey('last_updated')
        ->and($stats)->toHaveKey('active_users')
        ->and($stats)->toHaveKey('successful_connections')
        ->and($stats)->toHaveKey('job_placements')
        ->and($stats)->toHaveKey('mentorship_matches')
        ->and($stats)->toHaveKey('companies_represented');
});

it('includes institutional metrics for institutional audience', function () {
    $stats = $this->service->getPlatformStatistics('institutional');

    expect($stats)->toHaveKeys([
        'institutions_served',
        'branded_apps_deployed',
        'average_engagement_increase',
        'admin_satisfaction_rate',
    ]);
});

it('includes employer metrics for employer audience', function () {
    $stats = $this->service->getPlatformStatistics('employer');

    expect($stats)->toHaveKeys([
        'active_job_postings',
        'qualified_candidates',
        'average_time_to_hire',
        'employer_satisfaction_rate',
    ]);
});

it('caches statistics for performance', function () {
    // First call (cache miss)
    $start = microtime(true);
    $this->service->getPlatformStatistics('general');
    $duration1 = microtime(true) - $start;

    // Second call (cache hit)
    $start = microtime(true);
    $this->service->getPlatformStatistics('general');
    $duration2 = microtime(true) - $start;

    // Cache hit should be significantly faster
    expect($duration2)->toBeLessThan($duration1);
});

it('counts successful connections correctly', function () {
    // Arrange
    Connection::factory()->count(10)->create(['status' => 'accepted']);
    Connection::factory()->count(5)->create(['status' => 'pending']);

    // Act
    $stats = $this->service->getPlatformStatistics('general');

    // Assert
    expect($stats['successful_connections'])->toBe(10);
});

it('counts active jobs correctly', function () {
    // Arrange
    Job::factory()->count(8)->create(['status' => 'active']);
    Job::factory()->count(3)->create(['status' => 'filled']);

    // Act
    $stats = $this->service->getPlatformStatistics('employer');

    // Assert
    expect($stats['active_job_postings'])->toBe(8);
});
