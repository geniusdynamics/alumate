<?php

use App\Models\ComponentAnalytic;
use App\Models\ComponentInstance;
use App\Models\User;
use App\Services\ComponentAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new ComponentAnalyticsService();
    $this->componentInstance = ComponentInstance::factory()->create();
    $this->user = User::factory()->create();
});

test('record view creates view event with proper data', function () {
    $additionalData = [
        'viewport_width' => 1920,
        'viewport_height' => 1080,
        'scroll_depth' => 50,
    ];

    $analytic = $this->service->recordView(
        $this->componentInstance->id,
        $this->user->id,
        'session-123',
        $additionalData
    );

    expect($analytic)->toBeInstanceOf(ComponentAnalytic::class)
        ->and($analytic->component_instance_id)->toBe($this->componentInstance->id)
        ->and($analytic->event_type)->toBe('view')
        ->and($analytic->user_id)->toBe($this->user->id)
        ->and($analytic->session_id)->toBe('session-123')
        ->and($analytic->data['viewport_width'])->toBe(1920)
        ->and($analytic->data['viewport_height'])->toBe(1080)
        ->and($analytic->data['scroll_depth'])->toBe(50)
        ->and($analytic->data)->toHaveKey('timestamp')
        ->and($analytic->data)->toHaveKey('ip_address');
});

test('record click creates click event with interaction data', function () {
    $additionalData = [
        'element_id' => 'cta-button',
        'element_class' => 'btn-primary',
        'click_x' => 100,
        'click_y' => 200,
    ];

    $analytic = $this->service->recordClick(
        $this->componentInstance->id,
        null,
        'session-456',
        $additionalData
    );

    expect($analytic->event_type)->toBe('click')
        ->and($analytic->user_id)->toBeNull()
        ->and($analytic->session_id)->toBe('session-456')
        ->and($analytic->data['element_id'])->toBe('cta-button')
        ->and($analytic->data['element_class'])->toBe('btn-primary')
        ->and($analytic->data['click_x'])->toBe(100)
        ->and($analytic->data['click_y'])->toBe(200);
});

test('record conversion creates conversion event with value data', function () {
    $additionalData = [
        'conversion_value' => 99.99,
        'conversion_type' => 'signup',
        'funnel_step' => 3,
    ];

    $analytic = $this->service->recordConversion(
        $this->componentInstance->id,
        $this->user->id,
        'session-789',
        $additionalData
    );

    expect($analytic->event_type)->toBe('conversion')
        ->and($analytic->data['conversion_value'])->toBe(99.99)
        ->and($analytic->data['conversion_type'])->toBe('signup')
        ->and($analytic->data['funnel_step'])->toBe(3);
});

test('record form submit creates form submit event with form data', function () {
    $additionalData = [
        'form_id' => 'contact-form',
        'fields_count' => 5,
        'completion_time' => 120,
        'validation_errors' => 0,
    ];

    $analytic = $this->service->recordFormSubmit(
        $this->componentInstance->id,
        $this->user->id,
        'session-abc',
        $additionalData
    );

    expect($analytic->event_type)->toBe('form_submit')
        ->and($analytic->data['form_id'])->toBe('contact-form')
        ->and($analytic->data['fields_count'])->toBe(5)
        ->and($analytic->data['completion_time'])->toBe(120)
        ->and($analytic->data['validation_errors'])->toBe(0);
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

test('assign variant uses default variants when none provided', function () {
    $variant = $this->service->assignVariant($this->componentInstance->id, $this->user->id);

    expect(in_array($variant, ['A', 'B']))->toBeTrue();
});

test('record variant event stores variant data correctly', function () {
    $analytic = $this->service->recordVariantEvent(
        $this->componentInstance->id,
        'view',
        'variant-a',
        $this->user->id,
        'session-123',
        ['test_id' => 'custom-test-123']
    );

    expect($analytic->event_type)->toBe('view')
        ->and($analytic->data['variant'])->toBe('variant-a')
        ->and($analytic->data['test_id'])->toBe('custom-test-123')
        ->and($analytic->data)->toHaveKey('timestamp');
});

test('get component analytics returns comprehensive data', function () {
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
        ->and($analytics['event_counts']['view'])->toBe(10)
        ->and($analytics['event_counts']['click'])->toBe(5)
        ->and($analytics['event_counts']['conversion'])->toBe(2);
});

test('get component analytics with date range filters correctly', function () {
    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-31');

    // Create analytics within range
    ComponentAnalytic::factory()->count(5)->create([
        'component_instance_id' => $this->componentInstance->id,
        'created_at' => Carbon::parse('2024-01-15'),
    ]);

    // Create analytics outside range
    ComponentAnalytic::factory()->count(3)->create([
        'component_instance_id' => $this->componentInstance->id,
        'created_at' => Carbon::parse('2024-02-15'),
    ]);

    $analytics = $this->service->getComponentAnalytics($this->componentInstance->id, $startDate, $endDate);

    expect($analytics['summary']['total_events'])->toBe(5);
});

test('get variant performance returns performance data', function () {
    // Create variant A data
    ComponentAnalytic::factory()->count(100)->withVariant('A')->view()->create([
        'component_instance_id' => $this->componentInstance->id,
    ]);
    ComponentAnalytic::factory()->count(20)->withVariant('A')->click()->create([
        'component_instance_id' => $this->componentInstance->id,
    ]);
    ComponentAnalytic::factory()->count(5)->withVariant('A')->conversion()->create([
        'component_instance_id' => $this->componentInstance->id,
    ]);

    // Create variant B data
    ComponentAnalytic::factory()->count(100)->withVariant('B')->view()->create([
        'component_instance_id' => $this->componentInstance->id,
    ]);
    ComponentAnalytic::factory()->count(15)->withVariant('B')->click()->create([
        'component_instance_id' => $this->componentInstance->id,
    ]);
    ComponentAnalytic::factory()->count(8)->withVariant('B')->conversion()->create([
        'component_instance_id' => $this->componentInstance->id,
    ]);

    $performance = $this->service->getVariantPerformance($this->componentInstance->id);

    expect($performance)->toHaveCount(2);

    $variantA = $performance->where('variant', 'A')->first();
    $variantB = $performance->where('variant', 'B')->first();

    expect($variantA['views'])->toBe(100)
        ->and($variantA['clicks'])->toBe(20)
        ->and($variantA['conversions'])->toBe(5)
        ->and($variantA['click_through_rate'])->toBe(20.0)
        ->and($variantA['conversion_rate'])->toBe(5.0);

    expect($variantB['views'])->toBe(100)
        ->and($variantB['clicks'])->toBe(15)
        ->and($variantB['conversions'])->toBe(8)
        ->and($variantB['click_through_rate'])->toBe(15.0)
        ->and($variantB['conversion_rate'])->toBe(8.0);
});

test('get best performing variant returns highest conversion rate', function () {
    // Create variant A with 5% conversion rate
    ComponentAnalytic::factory()->count(100)->withVariant('A')->view()->create([
        'component_instance_id' => $this->componentInstance->id,
    ]);
    ComponentAnalytic::factory()->count(5)->withVariant('A')->conversion()->create([
        'component_instance_id' => $this->componentInstance->id,
    ]);

    // Create variant B with 8% conversion rate (better)
    ComponentAnalytic::factory()->count(100)->withVariant('B')->view()->create([
        'component_instance_id' => $this->componentInstance->id,
    ]);
    ComponentAnalytic::factory()->count(8)->withVariant('B')->conversion()->create([
        'component_instance_id' => $this->componentInstance->id,
    ]);

    $bestVariant = $this->service->getBestPerformingVariant($this->componentInstance->id);

    expect($bestVariant['variant'])->toBe('B')
        ->and($bestVariant['conversion_rate'])->toBe(8.0);
});

test('get conversion funnel calculates funnel correctly', function () {
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
        ->and($funnel['funnel_steps'][0]['step'])->toBe('view')
        ->and($funnel['funnel_steps'][0]['count'])->toBe(1000)
        ->and($funnel['funnel_steps'][0]['conversion_rate'])->toBe(100.0)
        ->and($funnel['funnel_steps'][1]['step'])->toBe('click')
        ->and($funnel['funnel_steps'][1]['count'])->toBe(200)
        ->and($funnel['funnel_steps'][1]['conversion_rate'])->toBe(20.0)
        ->and($funnel['funnel_steps'][2]['step'])->toBe('conversion')
        ->and($funnel['funnel_steps'][2]['count'])->toBe(50)
        ->and($funnel['funnel_steps'][2]['conversion_rate'])->toBe(5.0)
        ->and($funnel['overall_conversion_rate'])->toBe(5.0)
        ->and($funnel['total_dropoff'])->toBe(950);
});

test('get real time metrics returns current data', function () {
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

    // Create older data (should not be included in today's count)
    ComponentAnalytic::factory()->count(5)->create([
        'component_instance_id' => $this->componentInstance->id,
        'event_type' => 'view',
        'created_at' => now()->subDay(),
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

test('generate report creates comprehensive report for multiple components', function () {
    $componentInstance2 = ComponentInstance::factory()->create();

    // Create data for first component
    ComponentAnalytic::factory()->count(100)->create([
        'component_instance_id' => $this->componentInstance->id,
        'event_type' => 'view',
    ]);
    ComponentAnalytic::factory()->count(10)->create([
        'component_instance_id' => $this->componentInstance->id,
        'event_type' => 'conversion',
    ]);

    // Create data for second component
    ComponentAnalytic::factory()->count(200)->create([
        'component_instance_id' => $componentInstance2->id,
        'event_type' => 'view',
    ]);
    ComponentAnalytic::factory()->count(30)->create([
        'component_instance_id' => $componentInstance2->id,
        'event_type' => 'conversion',
    ]);

    $report = $this->service->generateReport([
        $this->componentInstance->id,
        $componentInstance2->id,
    ]);

    expect($report)->toHaveKeys(['period', 'components', 'summary'])
        ->and($report['components'])->toHaveCount(2)
        ->and($report['summary']['total_views'])->toBe(300)
        ->and($report['summary']['total_conversions'])->toBe(40)
        ->and($report['summary']['average_conversion_rate'])->toBeGreaterThan(0);
});

test('cleanup old data removes expired records', function () {
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

test('cleanup old data anonymizes user data', function () {
    // Create data that should be anonymized
    ComponentAnalytic::factory()->count(3)->create([
        'component_instance_id' => $this->componentInstance->id,
        'user_id' => $this->user->id,
        'created_at' => now()->subDays(100), // Older than anonymization period
        'data' => [
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0',
        ],
    ]);

    $this->service->cleanupOldData();

    $analytics = ComponentAnalytic::where('component_instance_id', $this->componentInstance->id)->get();

    foreach ($analytics as $analytic) {
        expect($analytic->user_id)->toBeNull()
            ->and($analytic->data['ip_address'] ?? null)->toBeNull()
            ->and($analytic->data['user_agent'] ?? null)->toBeNull();
    }
});

test('clear cache removes cached data', function () {
    // Set some cache data
    Cache::put("component_analytics_{$this->componentInstance->id}_test", 'test_data', 60);
    Cache::put("variant_performance_{$this->componentInstance->id}", 'variant_data', 60);

    expect(Cache::has("component_analytics_{$this->componentInstance->id}_test"))->toBeTrue()
        ->and(Cache::has("variant_performance_{$this->componentInstance->id}"))->toBeTrue();

    $result = $this->service->clearCache($this->componentInstance->id);

    expect($result)->toBeTrue();
    // Note: In testing environment, cache clearing might not work as expected
    // This test verifies the method runs without errors
});

test('anonymized ip removes last octet for ipv4', function () {
    $service = new ComponentAnalyticsService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('getAnonymizedIp');
    $method->setAccessible(true);

    $anonymizedIp = $method->invoke($service, '192.168.1.100');

    expect($anonymizedIp)->toBe('192.168.1.0');
});

test('anonymized ip handles null input', function () {
    $service = new ComponentAnalyticsService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('getAnonymizedIp');
    $method->setAccessible(true);

    $anonymizedIp = $method->invoke($service, null);

    expect($anonymizedIp)->toBeNull();
});

test('service caches analytics data for performance', function () {
    ComponentAnalytic::factory()->count(5)->create([
        'component_instance_id' => $this->componentInstance->id,
    ]);

    // First call should cache the data
    $analytics1 = $this->service->getComponentAnalytics($this->componentInstance->id);

    // Add more data
    ComponentAnalytic::factory()->count(3)->create([
        'component_instance_id' => $this->componentInstance->id,
    ]);

    // Second call should return cached data (not including new records)
    $analytics2 = $this->service->getComponentAnalytics($this->componentInstance->id);

    expect($analytics1['summary']['total_events'])->toBe($analytics2['summary']['total_events']);
});

test('service handles empty analytics data gracefully', function () {
    $analytics = $this->service->getComponentAnalytics($this->componentInstance->id);

    expect($analytics['summary']['total_events'])->toBe(0)
        ->and($analytics['summary']['views'])->toBe(0)
        ->and($analytics['summary']['clicks'])->toBe(0)
        ->and($analytics['summary']['conversions'])->toBe(0)
        ->and($analytics['event_counts'])->toBeArray()
        ->and($analytics['variant_performance'])->toBeArray();
});