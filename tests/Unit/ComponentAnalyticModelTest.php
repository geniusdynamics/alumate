<?php

use App\Models\ComponentAnalytic;
use App\Models\ComponentInstance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('component analytic has correct fillable attributes', function () {
    $analytic = new ComponentAnalytic;

    $expected = [
        'component_instance_id',
        'event_type',
        'user_id',
        'session_id',
        'data',
    ];

    expect($analytic->getFillable())->toBe($expected);
});

test('component analytic casts data to array', function () {
    $analytic = ComponentAnalytic::factory()->create([
        'data' => ['key' => 'value'],
    ]);

    expect($analytic->data)->toBeArray()
        ->and($analytic->data)->toBe(['key' => 'value']);
});

test('component analytic belongs to component instance', function () {
    $componentInstance = ComponentInstance::factory()->create();
    $analytic = ComponentAnalytic::factory()->create([
        'component_instance_id' => $componentInstance->id,
    ]);

    expect($analytic->componentInstance)->toBeInstanceOf(ComponentInstance::class)
        ->and($analytic->componentInstance->id)->toBe($componentInstance->id);
});

test('component analytic belongs to user', function () {
    $user = User::factory()->create();
    $analytic = ComponentAnalytic::factory()->create([
        'user_id' => $user->id,
    ]);

    expect($analytic->user)->toBeInstanceOf(User::class)
        ->and($analytic->user->id)->toBe($user->id);
});

test('component analytic can have null user', function () {
    $analytic = ComponentAnalytic::factory()->create([
        'user_id' => null,
    ]);

    expect($analytic->user)->toBeNull();
});

test('record view creates view event', function () {
    $componentInstance = ComponentInstance::factory()->create();
    $user = User::factory()->create();
    $sessionId = 'test-session-123';
    $data = ['viewport_width' => 1920];

    $analytic = ComponentAnalytic::recordView(
        $componentInstance->id,
        $user->id,
        $sessionId,
        $data
    );

    expect($analytic)->toBeInstanceOf(ComponentAnalytic::class)
        ->and($analytic->component_instance_id)->toBe($componentInstance->id)
        ->and($analytic->event_type)->toBe('view')
        ->and($analytic->user_id)->toBe($user->id)
        ->and($analytic->session_id)->toBe($sessionId)
        ->and($analytic->data)->toBe($data);
});

test('record click creates click event', function () {
    $componentInstance = ComponentInstance::factory()->create();
    $data = ['element_id' => 'cta-button'];

    $analytic = ComponentAnalytic::recordClick(
        $componentInstance->id,
        null,
        null,
        $data
    );

    expect($analytic->event_type)->toBe('click')
        ->and($analytic->data)->toBe($data);
});

test('record conversion creates conversion event', function () {
    $componentInstance = ComponentInstance::factory()->create();
    $data = ['conversion_value' => 100.50];

    $analytic = ComponentAnalytic::recordConversion(
        $componentInstance->id,
        null,
        null,
        $data
    );

    expect($analytic->event_type)->toBe('conversion')
        ->and($analytic->data)->toBe($data);
});

test('record form submit creates form submit event', function () {
    $componentInstance = ComponentInstance::factory()->create();
    $data = ['form_id' => 'contact-form'];

    $analytic = ComponentAnalytic::recordFormSubmit(
        $componentInstance->id,
        null,
        null,
        $data
    );

    expect($analytic->event_type)->toBe('form_submit')
        ->and($analytic->data)->toBe($data);
});

test('get event counts returns correct counts', function () {
    $componentInstance = ComponentInstance::factory()->create();

    // Create test data
    ComponentAnalytic::factory()->count(5)->create([
        'component_instance_id' => $componentInstance->id,
        'event_type' => 'view',
    ]);
    ComponentAnalytic::factory()->count(3)->create([
        'component_instance_id' => $componentInstance->id,
        'event_type' => 'click',
    ]);
    ComponentAnalytic::factory()->count(1)->create([
        'component_instance_id' => $componentInstance->id,
        'event_type' => 'conversion',
    ]);

    $counts = ComponentAnalytic::getEventCounts($componentInstance->id);

    expect($counts)->toHaveCount(3);

    $viewCount = $counts->where('event_type', 'view')->first();
    $clickCount = $counts->where('event_type', 'click')->first();
    $conversionCount = $counts->where('event_type', 'conversion')->first();

    expect($viewCount->count)->toBe(5)
        ->and($clickCount->count)->toBe(3)
        ->and($conversionCount->count)->toBe(1);
});

test('get event counts with specific event type', function () {
    $componentInstance = ComponentInstance::factory()->create();

    ComponentAnalytic::factory()->count(5)->create([
        'component_instance_id' => $componentInstance->id,
        'event_type' => 'view',
    ]);
    ComponentAnalytic::factory()->count(3)->create([
        'component_instance_id' => $componentInstance->id,
        'event_type' => 'click',
    ]);

    $counts = ComponentAnalytic::getEventCounts($componentInstance->id, 'view');

    expect($counts)->toHaveCount(1)
        ->and($counts->first()->event_type)->toBe('view')
        ->and($counts->first()->count)->toBe(5);
});

test('get conversion rate calculates correctly', function () {
    $componentInstance = ComponentInstance::factory()->create();

    // 100 views, 10 conversions = 10% conversion rate
    ComponentAnalytic::factory()->count(100)->create([
        'component_instance_id' => $componentInstance->id,
        'event_type' => 'view',
    ]);
    ComponentAnalytic::factory()->count(10)->create([
        'component_instance_id' => $componentInstance->id,
        'event_type' => 'conversion',
    ]);

    $conversionRate = ComponentAnalytic::getConversionRate($componentInstance->id);

    expect($conversionRate)->toBe(10.0);
});

test('get conversion rate returns zero with no views', function () {
    $componentInstance = ComponentInstance::factory()->create();

    ComponentAnalytic::factory()->count(5)->create([
        'component_instance_id' => $componentInstance->id,
        'event_type' => 'conversion',
    ]);

    $conversionRate = ComponentAnalytic::getConversionRate($componentInstance->id);

    expect($conversionRate)->toBe(0.0);
});

test('get click through rate calculates correctly', function () {
    $componentInstance = ComponentInstance::factory()->create();

    // 100 views, 25 clicks = 25% CTR
    ComponentAnalytic::factory()->count(100)->create([
        'component_instance_id' => $componentInstance->id,
        'event_type' => 'view',
    ]);
    ComponentAnalytic::factory()->count(25)->create([
        'component_instance_id' => $componentInstance->id,
        'event_type' => 'click',
    ]);

    $ctr = ComponentAnalytic::getClickThroughRate($componentInstance->id);

    expect($ctr)->toBe(25.0);
});

test('get unique users counts distinct users', function () {
    $componentInstance = ComponentInstance::factory()->create();
    $users = User::factory()->count(3)->create();

    // Create multiple events for same users
    foreach ($users as $user) {
        ComponentAnalytic::factory()->count(2)->create([
            'component_instance_id' => $componentInstance->id,
            'user_id' => $user->id,
            'event_type' => 'view',
        ]);
    }

    // Create some anonymous events
    ComponentAnalytic::factory()->count(5)->create([
        'component_instance_id' => $componentInstance->id,
        'user_id' => null,
        'event_type' => 'view',
    ]);

    $uniqueUsers = ComponentAnalytic::getUniqueUsers($componentInstance->id);

    expect($uniqueUsers)->toBe(3);
});

test('get unique sessions counts distinct sessions', function () {
    $componentInstance = ComponentInstance::factory()->create();
    $sessionIds = ['session-1', 'session-2', 'session-3'];

    foreach ($sessionIds as $sessionId) {
        ComponentAnalytic::factory()->count(3)->create([
            'component_instance_id' => $componentInstance->id,
            'session_id' => $sessionId,
            'event_type' => 'view',
        ]);
    }

    $uniqueSessions = ComponentAnalytic::getUniqueSessions($componentInstance->id);

    expect($uniqueSessions)->toBe(3);
});

test('record variant event stores variant data', function () {
    $componentInstance = ComponentInstance::factory()->create();
    $user = User::factory()->create();

    $analytic = ComponentAnalytic::recordVariantEvent(
        $componentInstance->id,
        'view',
        'variant-a',
        $user->id,
        'session-123',
        ['extra' => 'data']
    );

    expect($analytic->event_type)->toBe('view')
        ->and($analytic->data['variant'])->toBe('variant-a')
        ->and($analytic->data['extra'])->toBe('data');
});

test('get variant performance calculates metrics', function () {
    $componentInstance = ComponentInstance::factory()->create();

    // Variant A: 100 views, 20 clicks, 5 conversions
    ComponentAnalytic::factory()->count(100)->withVariant('A')->view()->create([
        'component_instance_id' => $componentInstance->id,
    ]);
    ComponentAnalytic::factory()->count(20)->withVariant('A')->click()->create([
        'component_instance_id' => $componentInstance->id,
    ]);
    ComponentAnalytic::factory()->count(5)->withVariant('A')->conversion()->create([
        'component_instance_id' => $componentInstance->id,
    ]);

    // Variant B: 100 views, 15 clicks, 8 conversions
    ComponentAnalytic::factory()->count(100)->withVariant('B')->view()->create([
        'component_instance_id' => $componentInstance->id,
    ]);
    ComponentAnalytic::factory()->count(15)->withVariant('B')->click()->create([
        'component_instance_id' => $componentInstance->id,
    ]);
    ComponentAnalytic::factory()->count(8)->withVariant('B')->conversion()->create([
        'component_instance_id' => $componentInstance->id,
    ]);

    $performance = ComponentAnalytic::getVariantPerformance($componentInstance->id);

    expect($performance)->toHaveCount(2);

    $variantA = $performance->get('A');
    $variantB = $performance->get('B');

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
    $componentInstance = ComponentInstance::factory()->create();

    // Variant A: 5% conversion rate
    ComponentAnalytic::factory()->count(100)->withVariant('A')->view()->create([
        'component_instance_id' => $componentInstance->id,
    ]);
    ComponentAnalytic::factory()->count(5)->withVariant('A')->conversion()->create([
        'component_instance_id' => $componentInstance->id,
    ]);

    // Variant B: 8% conversion rate (better)
    ComponentAnalytic::factory()->count(100)->withVariant('B')->view()->create([
        'component_instance_id' => $componentInstance->id,
    ]);
    ComponentAnalytic::factory()->count(8)->withVariant('B')->conversion()->create([
        'component_instance_id' => $componentInstance->id,
    ]);

    $bestVariant = ComponentAnalytic::getBestPerformingVariant($componentInstance->id);

    expect($bestVariant['variant'])->toBe('B')
        ->and($bestVariant['conversion_rate'])->toBe(8.0);
});

test('get best performing variant returns null when no variants', function () {
    $componentInstance = ComponentInstance::factory()->create();

    ComponentAnalytic::factory()->count(5)->create([
        'component_instance_id' => $componentInstance->id,
        'event_type' => 'view',
    ]);

    $bestVariant = ComponentAnalytic::getBestPerformingVariant($componentInstance->id);

    expect($bestVariant)->toBeNull();
});

test('scope for date range filters correctly', function () {
    $componentInstance = ComponentInstance::factory()->create();
    $startDate = Carbon::parse('2024-01-01');
    $endDate = Carbon::parse('2024-01-31');

    // Create analytics within range
    ComponentAnalytic::factory()->count(3)->create([
        'component_instance_id' => $componentInstance->id,
        'created_at' => Carbon::parse('2024-01-15'),
    ]);

    // Create analytics outside range
    ComponentAnalytic::factory()->count(2)->create([
        'component_instance_id' => $componentInstance->id,
        'created_at' => Carbon::parse('2024-02-15'),
    ]);

    $analytics = ComponentAnalytic::where('component_instance_id', $componentInstance->id)
        ->forDateRange($startDate, $endDate)
        ->get();

    expect($analytics)->toHaveCount(3);
});

test('scope for event type filters correctly', function () {
    $componentInstance = ComponentInstance::factory()->create();

    ComponentAnalytic::factory()->count(5)->create([
        'component_instance_id' => $componentInstance->id,
        'event_type' => 'view',
    ]);
    ComponentAnalytic::factory()->count(3)->create([
        'component_instance_id' => $componentInstance->id,
        'event_type' => 'click',
    ]);

    $viewAnalytics = ComponentAnalytic::where('component_instance_id', $componentInstance->id)
        ->forEventType('view')
        ->get();

    expect($viewAnalytics)->toHaveCount(5);
    expect($viewAnalytics->every(fn ($a) => $a->event_type === 'view'))->toBeTrue();
});

test('scope for event types filters multiple types', function () {
    $componentInstance = ComponentInstance::factory()->create();

    ComponentAnalytic::factory()->count(5)->create([
        'component_instance_id' => $componentInstance->id,
        'event_type' => 'view',
    ]);
    ComponentAnalytic::factory()->count(3)->create([
        'component_instance_id' => $componentInstance->id,
        'event_type' => 'click',
    ]);
    ComponentAnalytic::factory()->count(2)->create([
        'component_instance_id' => $componentInstance->id,
        'event_type' => 'conversion',
    ]);

    $analytics = ComponentAnalytic::where('component_instance_id', $componentInstance->id)
        ->forEventTypes(['view', 'click'])
        ->get();

    expect($analytics)->toHaveCount(8);
});

test('scope for variant filters correctly', function () {
    $componentInstance = ComponentInstance::factory()->create();

    ComponentAnalytic::factory()->count(3)->withVariant('A')->create([
        'component_instance_id' => $componentInstance->id,
    ]);
    ComponentAnalytic::factory()->count(2)->withVariant('B')->create([
        'component_instance_id' => $componentInstance->id,
    ]);

    $variantAAnalytics = ComponentAnalytic::where('component_instance_id', $componentInstance->id)
        ->forVariant('A')
        ->get();

    expect($variantAAnalytics)->toHaveCount(3);
});

test('scope with variant filters only variant events', function () {
    $componentInstance = ComponentInstance::factory()->create();

    ComponentAnalytic::factory()->count(3)->withVariant('A')->create([
        'component_instance_id' => $componentInstance->id,
    ]);
    ComponentAnalytic::factory()->count(2)->create([
        'component_instance_id' => $componentInstance->id,
        'data' => ['no_variant' => true],
    ]);

    $variantAnalytics = ComponentAnalytic::where('component_instance_id', $componentInstance->id)
        ->withVariant()
        ->get();

    expect($variantAnalytics)->toHaveCount(3);
});

test('scope today filters todays events', function () {
    $componentInstance = ComponentInstance::factory()->create();

    ComponentAnalytic::factory()->count(3)->create([
        'component_instance_id' => $componentInstance->id,
        'created_at' => now(),
    ]);
    ComponentAnalytic::factory()->count(2)->create([
        'component_instance_id' => $componentInstance->id,
        'created_at' => now()->subDay(),
    ]);

    $todayAnalytics = ComponentAnalytic::where('component_instance_id', $componentInstance->id)
        ->today()
        ->get();

    expect($todayAnalytics)->toHaveCount(3);
});

test('scope last days filters correctly', function () {
    $componentInstance = ComponentInstance::factory()->create();

    ComponentAnalytic::factory()->count(3)->create([
        'component_instance_id' => $componentInstance->id,
        'created_at' => now()->subDays(2),
    ]);
    ComponentAnalytic::factory()->count(2)->create([
        'component_instance_id' => $componentInstance->id,
        'created_at' => now()->subDays(10),
    ]);

    $recentAnalytics = ComponentAnalytic::where('component_instance_id', $componentInstance->id)
        ->lastDays(7)
        ->get();

    expect($recentAnalytics)->toHaveCount(3);
});
