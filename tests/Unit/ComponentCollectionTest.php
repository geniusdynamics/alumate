<?php

use App\Models\Component;
use App\Models\ComponentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('collection filters by category', function () {
    $heroComponent = Component::factory()->hero()->create();
    $formComponent = Component::factory()->form()->create();

    $collection = new ComponentCollection([$heroComponent, $formComponent]);
    $heroComponents = $collection->byCategory('hero');

    expect($heroComponents)->toHaveCount(1);
    expect($heroComponents->first()->category)->toBe('hero');
});

test('collection filters by type', function () {
    $component1 = Component::factory()->create(['type' => 'individual']);
    $component2 = Component::factory()->create(['type' => 'institution']);

    $collection = new ComponentCollection([$component1, $component2]);
    $individualComponents = $collection->byType('individual');

    expect($individualComponents)->toHaveCount(1);
    expect($individualComponents->first()->type)->toBe('individual');
});

test('collection filters active components', function () {
    $activeComponent = Component::factory()->active()->create();
    $inactiveComponent = Component::factory()->inactive()->create();

    $collection = new ComponentCollection([$activeComponent, $inactiveComponent]);
    $activeComponents = $collection->active();

    expect($activeComponents)->toHaveCount(1);
    expect($activeComponents->first()->is_active)->toBeTrue();
});

test('collection filters inactive components', function () {
    $activeComponent = Component::factory()->active()->create();
    $inactiveComponent = Component::factory()->inactive()->create();

    $collection = new ComponentCollection([$activeComponent, $inactiveComponent]);
    $inactiveComponents = $collection->inactive();

    expect($inactiveComponents)->toHaveCount(1);
    expect($inactiveComponents->first()->is_active)->toBeFalse();
});

test('collection groups by category', function () {
    $heroComponent = Component::factory()->hero()->create();
    $formComponent = Component::factory()->form()->create();

    $collection = new ComponentCollection([$heroComponent, $formComponent]);
    $grouped = $collection->groupByCategory();

    expect($grouped)->toHaveKey('hero');
    expect($grouped)->toHaveKey('forms');
    expect($grouped['hero'])->toHaveCount(1);
    expect($grouped['forms'])->toHaveCount(1);
});

test('collection groups by type', function () {
    $component1 = Component::factory()->create(['type' => 'individual']);
    $component2 = Component::factory()->create(['type' => 'institution']);

    $collection = new ComponentCollection([$component1, $component2]);
    $grouped = $collection->groupByType();

    expect($grouped)->toHaveKey('individual');
    expect($grouped)->toHaveKey('institution');
    expect($grouped['individual'])->toHaveCount(1);
    expect($grouped['institution'])->toHaveCount(1);
});

test('collection filters by config key', function () {
    $component1 = Component::factory()->create(['config' => ['headline' => 'Test']]);
    $component2 = Component::factory()->create(['config' => ['other' => 'value']]);

    $collection = new ComponentCollection([$component1, $component2]);
    $withHeadline = $collection->withConfigKey('headline');

    expect($withHeadline)->toHaveCount(1);
    expect($withHeadline->first()->hasConfigKey('headline'))->toBeTrue();
});

test('collection filters by config value', function () {
    $component1 = Component::factory()->create(['config' => ['style' => 'primary']]);
    $component2 = Component::factory()->create(['config' => ['style' => 'secondary']]);

    $collection = new ComponentCollection([$component1, $component2]);
    $primaryComponents = $collection->withConfigValue('style', 'primary');

    expect($primaryComponents)->toHaveCount(1);
    expect($primaryComponents->first()->getConfigValue('style'))->toBe('primary');
});

test('collection sorts by name', function () {
    $component1 = Component::factory()->create(['name' => 'Z Component']);
    $component2 = Component::factory()->create(['name' => 'A Component']);

    $collection = new ComponentCollection([$component1, $component2]);
    $sorted = $collection->sortByName();

    expect($sorted->first()->name)->toBe('A Component');
    expect($sorted->last()->name)->toBe('Z Component');

    $sortedDesc = $collection->sortByName(true);
    expect($sortedDesc->first()->name)->toBe('Z Component');
    expect($sortedDesc->last()->name)->toBe('A Component');
});

test('collection searches components', function () {
    $component1 = Component::factory()->create([
        'name' => 'Hero Banner',
        'description' => 'A great hero component',
    ]);
    $component2 = Component::factory()->create([
        'name' => 'Contact Form',
        'description' => 'Simple contact form',
    ]);

    $collection = new ComponentCollection([$component1, $component2]);
    $searchResults = $collection->search('hero');

    expect($searchResults)->toHaveCount(1);
    expect($searchResults->first()->name)->toBe('Hero Banner');

    $searchResults2 = $collection->search('form');
    expect($searchResults2)->toHaveCount(1);
    expect($searchResults2->first()->name)->toBe('Contact Form');
});

test('collection gets usage stats', function () {
    $heroComponent = Component::factory()->hero()->active()->create();
    $formComponent = Component::factory()->form()->inactive()->create();
    $ctaComponent = Component::factory()->cta()->active()->create();

    $collection = new ComponentCollection([$heroComponent, $formComponent, $ctaComponent]);
    $stats = $collection->getUsageStats();

    expect($stats['total'])->toBe(3);
    expect($stats['active'])->toBe(2);
    expect($stats['inactive'])->toBe(1);
    expect($stats['by_category']['hero'])->toBe(1);
    expect($stats['by_category']['forms'])->toBe(1);
    expect($stats['by_category']['ctas'])->toBe(1);
});

test('collection gets recent components', function () {
    $old = Component::factory()->create(['updated_at' => now()->subDays(5)]);
    $recent = Component::factory()->create(['updated_at' => now()->subHour()]);
    $newest = Component::factory()->create(['updated_at' => now()]);

    $collection = new ComponentCollection([$old, $recent, $newest]);
    $recentComponents = $collection->recent(2);

    expect($recentComponents)->toHaveCount(2);
    expect($recentComponents->first()->id)->toBe($newest->id);
    expect($recentComponents->last()->id)->toBe($recent->id);
});

test('collection filters by version', function () {
    $v1 = Component::factory()->create(['version' => '1.0.0']);
    $v2 = Component::factory()->create(['version' => '2.0.0']);

    $collection = new ComponentCollection([$v1, $v2]);
    $v1Components = $collection->byVersion('1.0.0');

    expect($v1Components)->toHaveCount(1);
    expect($v1Components->first()->version)->toBe('1.0.0');
});

test('collection gets latest versions', function () {
    $oldHero = Component::factory()->create(['type' => 'hero', 'version' => '1.0.0']);
    $newHero = Component::factory()->create(['type' => 'hero', 'version' => '2.0.0']);
    $form = Component::factory()->create(['type' => 'form', 'version' => '1.5.0']);

    $collection = new ComponentCollection([$oldHero, $newHero, $form]);
    $latest = $collection->latestVersions();

    expect($latest)->toHaveCount(2);
    expect($latest->where('type', 'hero')->first()->version)->toBe('2.0.0');
    expect($latest->where('type', 'form')->first()->version)->toBe('1.5.0');
});

test('collection validates all components', function () {
    $validComponent = Component::factory()->hero()->create([
        'config' => [
            'headline' => 'Valid Headline',
            'cta_url' => 'https://example.com',
            'background_type' => 'image',
        ],
    ]);

    $invalidComponent = Component::factory()->hero()->create([
        'config' => [
            'headline' => str_repeat('a', 300), // Too long
            'background_type' => 'invalid_type',
        ],
    ]);

    $collection = new ComponentCollection([$validComponent, $invalidComponent]);
    $results = $collection->validateAll();

    expect($results)->toHaveCount(2);
    expect($results[$validComponent->id]['valid'])->toBeTrue();
    expect($results[$invalidComponent->id]['valid'])->toBeFalse();
});

test('collection filters valid components', function () {
    $validComponent = Component::factory()->hero()->create([
        'config' => [
            'headline' => 'Valid Headline',
            'cta_url' => 'https://example.com',
            'background_type' => 'image',
        ],
    ]);

    $invalidComponent = Component::factory()->hero()->create([
        'config' => [
            'headline' => str_repeat('a', 300), // Too long
            'background_type' => 'invalid_type',
        ],
    ]);

    $collection = new ComponentCollection([$validComponent, $invalidComponent]);
    $validComponents = $collection->valid();
    $invalidComponents = $collection->invalid();

    expect($validComponents)->toHaveCount(1);
    expect($invalidComponents)->toHaveCount(1);
    expect($validComponents->first()->id)->toBe($validComponent->id);
    expect($invalidComponents->first()->id)->toBe($invalidComponent->id);
});
