<?php

use App\Models\ComponentAnalytic;
use App\Models\ComponentInstance;
use App\Models\User;
use App\Services\ComponentAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new ComponentAnalyticsService();
    $this->componentInstance = ComponentInstance::factory()->create();
    $this->user = User::factory()->create();
});

test('service can be instantiated', function () {
    expect($this->service)->toBeInstanceOf(ComponentAnalyticsService::class);
});

test('record view creates analytics record', function () {
    $analytic = $this->service->recordView(
        $this->componentInstance->id,
        $this->user->id,
        'session-123'
    );

    expect($analytic)->toBeInstanceOf(ComponentAnalytic::class)
        ->and($analytic->component_instance_id)->toBe($this->componentInstance->id)
        ->and($analytic->event_type)->toBe('view')
        ->and($analytic->user_id)->toBe($this->user->id)
        ->and($analytic->session_id)->toBe('session-123');
});

test('record click creates analytics record', function () {
    $analytic = $this->service->recordClick(
        $this->componentInstance->id,
        null,
        'session-456'
    );

    expect($analytic->event_type)->toBe('click')
        ->and($analytic->user_id)->toBeNull()
        ->and($analytic->session_id)->toBe('session-456');
});

test('record conversion creates analytics record', function () {
    $analytic = $this->service->recordConversion(
        $this->componentInstance->id,
        $this->user->id,
        'session-789'
    );

    expect($analytic->event_type)->toBe('conversion')
        ->and($analytic->user_id)->toBe($this->user->id);
});

test('record form submit creates analytics record', function () {
    $analytic = $this->service->recordFormSubmit(
        $this->componentInstance->id,
        $this->user->id,
        'session-abc'
    );

    expect($analytic->event_type)->toBe('form_submit')
        ->and($analytic->user_id)->toBe($this->user->id);
});

test('assign variant returns consistent variant for same user', function () {
    $variants = ['A', 'B', 'C'];

    $variant1 = $this->service->assignVariant($this->componentInstance->id, $this->user->id, null, $variants);
    $variant2 = $this->service->assignVariant($this->componentInstance->id, $this->user->id, null, $variants);

    expect($variant1)->toBe($variant2)
        ->and(in_array($variant1, $variants))->toBeTrue();
});

test('assign variant returns consistent variant for same session', function () {
    $sessionId = 'session-test-123';
    $variants = ['A', 'B'];

    $variant1 = $this->service->assignVariant($this->componentInstance->id, null, $sessionId, $variants);
    $variant2 = $this->service->assignVariant($this->componentInstance->id, null, $sessionId, $variants);

    expect($variant1)->toBe($variant2)
        ->and(in_array($variant1, $variants))->toBeTrue();
});

test('record variant event stores variant data', function () {
    $analytic = $this->service->recordVariantEvent(
        $this->componentInstance->id,
        'view',
        'variant-a',
        $this->user->id,
        'session-123'
    );

    expect($analytic->event_type)->toBe('view')
        ->and($analytic->data['variant'])->toBe('variant-a');
});

test('get component analytics returns data structure', function () {
    // Create some test data
    ComponentAnalytic::factory()->count(5)->create([
        'component_instance_id' => $this->componentInstance->id,
        'event_type' => 'view',
    ]);

    $analytics = $this->service->getComponentAnalytics($this->componentInstance->id);

    expect($analytics)->toHaveKeys([
        'summary',
        'event_counts',
        'conversion_metrics',
        'engagement_metrics',
        'variant_performance',
        'time_series',
        'user_behavior',
    ]);
});

test('get best performing variant returns null when no variants', function () {
    $bestVariant = $this->service->getBestPerformingVariant($this->componentInstance->id);

    expect($bestVariant)->toBeNull();
});

test('cleanup old data removes expired records', function () {
    // Create old data that should be deleted
    ComponentAnalytic::factory()->count(3)->create([
        'component_instance_id' => $this->componentInstance->id,
        'created_at' => now()->subDays(400), // Older than retention period
    ]);

    // Create recent data that should be kept
    ComponentAnalytic::factory()->count(2)->create([
        'component_instance_id' => $this->componentInstance->id,
        'created_at' => now()->subDays(30),
    ]);

    $initialCount = ComponentAnalytic::count();
    expect($initialCount)->toBe(5);

    $deletedCount = $this->service->cleanupOldData();

    $finalCount = ComponentAnalytic::count();
    expect($finalCount)->toBe(2)
        ->and($deletedCount)->toBe(3);
});

test('clear cache returns true', function () {
    $result = $this->service->clearCache($this->componentInstance->id);

    expect($result)->toBeTrue();
});

test('service handles empty analytics data gracefully', function () {
    $analytics = $this->service->getComponentAnalytics($this->componentInstance->id);

    expect($analytics['summary']['total_events'])->toBe(0)
        ->and($analytics['summary']['views'])->toBe(0)
        ->and($analytics['summary']['clicks'])->toBe(0)
        ->and($analytics['summary']['conversions'])->toBe(0);
});