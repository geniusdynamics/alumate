<?php

use App\Models\ComponentAnalytic;
use App\Models\ComponentInstance;
use App\Models\User;
use App\Services\ComponentAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(ComponentAnalyticsService::class);
    $this->componentInstance = ComponentInstance::factory()->create();
    $this->user = User::factory()->create();
});

test('service can record view events', function () {
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

    $this->assertDatabaseHas('component_analytics', [
        'component_instance_id' => $this->componentInstance->id,
        'event_type' => 'view',
        'user_id' => $this->user->id,
        'session_id' => 'session-123',
    ]);
});

test('service can record click events', function () {
    $analytic = $this->service->recordClick(
        $this->componentInstance->id,
        null,
        'session-456',
        ['element_id' => 'cta-button']
    );

    expect($analytic->event_type)->toBe('click')
        ->and($analytic->user_id)->toBeNull()
        ->and($analytic->session_id)->toBe('session-456')
        ->and($analytic->data['element_id'])->toBe('cta-button');
});

test('service can record conversion events', function () {
    $analytic = $this->service->recordConversion(
        $this->componentInstance->id,
        $this->user->id,
        'session-789',
        ['conversion_value' => 99.99, 'conversion_type' => 'signup']
    );

    expect($analytic->event_type)->toBe('conversion')
        ->and($analytic->data['conversion_value'])->toBe(99.99)
        ->and($analytic->data['conversion_type'])->toBe('signup');
});

test('service can assign consistent variants', function () {
    $variants = ['A', 'B', 'C'];

    $variant1 = $this->service->assignVariant($this->componentInstance->id, $this->user->id, null, $variants);
    $variant2 = $this->service->assignVariant($this->componentInstance->id, $this->user->id, null, $variants);

    expect($variant1)->toBe($variant2)
        ->and(in_array($variant1, $variants))->toBeTrue();
});

test('service can get component analytics', function () {
    // Create test data
    ComponentAnalytic::factory()->count(10)->create([
        'component_instance_id' => $this->componentInstance->id,
        'event_type' => 'view',
    ]);
    ComponentAnalytic::factory()->count(5)->create([
        'component_instance_id' => $this->componentInstance->id,
        'event_type' => 'click',
    ]);
    ComponentAnalytic::factory()->count(2)->create([
        'component_instance_id' => $this->componentInstance->id,
        'event_type' => 'conversion',
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
    ])
        ->and($analytics['summary']['views'])->toBe(10)
        ->and($analytics['summary']['clicks'])->toBe(5)
        ->and($analytics['summary']['conversions'])->toBe(2)
        ->and($analytics['summary']['conversion_rate'])->toBe(20.0); // 2/10 * 100
});

test('service can get variant performance', function () {
    // Create variant A data
    ComponentAnalytic::factory()->count(100)->withVariant('A')->view()->create([
        'component_instance_id' => $this->componentInstance->id,
    ]);
    ComponentAnalytic::factory()->count(5)->withVariant('A')->conversion()->create([
        'component_instance_id' => $this->componentInstance->id,
    ]);

    // Create variant B data
    ComponentAnalytic::factory()->count(100)->withVariant('B')->view()->create([
        'component_instance_id' => $this->componentInstance->id,
    ]);
    ComponentAnalytic::factory()->count(8)->withVariant('B')->conversion()->create([
        'component_instance_id' => $this->componentInstance->id,
    ]);

    $performance = $this->service->getVariantPerformance($this->componentInstance->id);

    expect($performance)->toHaveCount(2);

    $variantA = $performance->where('variant', 'A')->first();
    $variantB = $performance->where('variant', 'B')->first();

    expect($variantA['conversion_rate'])->toBe(5.0)
        ->and($variantB['conversion_rate'])->toBe(8.0);
});

test('service can get conversion funnel', function () {
    // Create funnel data: 1000 views -> 200 clicks -> 50 conversions
    ComponentAnalytic::factory()->count(1000)->create([
        'component_instance_id' => $this->componentInstance->id,
        'event_type' => 'view',
    ]);
    ComponentAnalytic::factory()->count(200)->create([
        'component_instance_id' => $this->componentInstance->id,
        'event_type' => 'click',
    ]);
    ComponentAnalytic::factory()->count(50)->create([
        'component_instance_id' => $this->componentInstance->id,
        'event_type' => 'conversion',
    ]);

    $funnel = $this->service->getConversionFunnel($this->componentInstance->id);

    expect($funnel['funnel_steps'])->toHaveCount(3)
        ->and($funnel['funnel_steps'][0]['count'])->toBe(1000)
        ->and($funnel['funnel_steps'][1]['count'])->toBe(200)
        ->and($funnel['funnel_steps'][2]['count'])->toBe(50)
        ->and($funnel['overall_conversion_rate'])->toBe(5.0);
});

test('service can get real time metrics', function () {
    // Create today's data
    ComponentAnalytic::factory()->count(10)->create([
        'component_instance_id' => $this->componentInstance->id,
        'event_type' => 'view',
        'created_at' => now(),
    ]);
    ComponentAnalytic::factory()->count(3)->create([
        'component_instance_id' => $this->componentInstance->id,
        'event_type' => 'conversion',
        'created_at' => now(),
    ]);

    $metrics = $this->service->getRealTimeMetrics($this->componentInstance->id);

    expect($metrics)->toHaveKeys([
        'views_today',
        'views_this_hour',
        'conversions_today',
        'active_sessions',
        'last_updated',
    ])
        ->and($metrics['views_today'])->toBe(10)
        ->and($metrics['conversions_today'])->toBe(3);
});

test('service can cleanup old data', function () {
    // Create old data that should be deleted
    ComponentAnalytic::factory()->count(5)->create([
        'component_instance_id' => $this->componentInstance->id,
        'created_at' => now()->subDays(400), // Older than retention period
    ]);

    // Create recent data that should be kept
    ComponentAnalytic::factory()->count(3)->create([
        'component_instance_id' => $this->componentInstance->id,
        'created_at' => now()->subDays(30),
    ]);

    $initialCount = ComponentAnalytic::count();
    expect($initialCount)->toBe(8);

    $deletedCount = $this->service->cleanupOldData();

    $finalCount = ComponentAnalytic::count();
    expect($finalCount)->toBe(3)
        ->and($deletedCount)->toBe(5);
});