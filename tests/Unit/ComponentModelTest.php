<?php

use App\Models\Component;
use App\Models\ComponentCollection;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('component has correct fillable fields', function () {
    $component = new Component;

    $expectedFillable = [
        'tenant_id',
        'theme_id',
        'name',
        'slug',
        'category',
        'type',
        'description',
        'config',
        'metadata',
        'version',
        'is_active',
    ];

    expect($component->getFillable())->toEqual($expectedFillable);
});

test('component has correct casts', function () {
    $component = new Component;

    $casts = $component->getCasts();

    expect($casts)->toHaveKey('config');
    expect($casts)->toHaveKey('metadata');
    expect($casts)->toHaveKey('is_active');
    expect($casts)->toHaveKey('version');
    expect($casts)->toHaveKey('deleted_at');

    expect($casts['config'])->toBe('array');
    expect($casts['metadata'])->toBe('array');
    expect($casts['is_active'])->toBe('boolean');
    expect($casts['version'])->toBe('string');
    expect($casts['deleted_at'])->toBe('datetime');
});

test('component has default attributes', function () {
    $component = new Component;

    expect($component->is_active)->toBeTrue();
    expect($component->version)->toBe('1.0.0');
    expect($component->config)->toBeArray();
    expect($component->metadata)->toBeArray();
});

test('component categories constant', function () {
    $expectedCategories = [
        'hero',
        'forms',
        'testimonials',
        'statistics',
        'ctas',
        'media',
    ];

    expect(Component::CATEGORIES)->toEqual($expectedCategories);
});

test('component belongs to tenant', function () {
    $tenant = Tenant::factory()->create();
    $component = Component::factory()->create(['tenant_id' => $tenant->id]);

    expect($component->tenant)->toBeInstanceOf(Tenant::class);
    expect($component->tenant->id)->toBe($tenant->id);
});

test('component scope for tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    Component::factory()->create(['tenant_id' => $tenant1->id]);
    Component::factory()->create(['tenant_id' => $tenant2->id]);

    $tenant1Components = Component::forTenant($tenant1->id)->get();
    $tenant2Components = Component::forTenant($tenant2->id)->get();

    expect($tenant1Components)->toHaveCount(1);
    expect($tenant2Components)->toHaveCount(1);
    expect($tenant1Components->first()->tenant_id)->toBe($tenant1->id);
    expect($tenant2Components->first()->tenant_id)->toBe($tenant2->id);
});

test('component scope active', function () {
    Component::factory()->active()->create();
    Component::factory()->inactive()->create();

    $activeComponents = Component::active()->get();

    expect($activeComponents)->toHaveCount(1);
    expect($activeComponents->first()->is_active)->toBeTrue();
});

test('component scope by category', function () {
    Component::factory()->hero()->create();
    Component::factory()->form()->create();

    $heroComponents = Component::byCategory('hero')->get();
    $formComponents = Component::byCategory('forms')->get();

    expect($heroComponents)->toHaveCount(1);
    expect($formComponents)->toHaveCount(1);
    expect($heroComponents->first()->category)->toBe('hero');
    expect($formComponents->first()->category)->toBe('forms');
});

test('component scope by type', function () {
    Component::factory()->create(['type' => 'individual']);
    Component::factory()->create(['type' => 'institution']);

    $individualComponents = Component::byType('individual')->get();
    $institutionComponents = Component::byType('institution')->get();

    expect($individualComponents)->toHaveCount(1);
    expect($institutionComponents)->toHaveCount(1);
    expect($individualComponents->first()->type)->toBe('individual');
    expect($institutionComponents->first()->type)->toBe('institution');
});

test('component formatted config attribute', function () {
    $component = Component::factory()->hero()->create([
        'config' => ['headline' => 'Custom Headline'],
    ]);

    $formattedConfig = $component->formatted_config;

    expect($formattedConfig)->toBeArray();
    expect($formattedConfig['headline'])->toBe('Custom Headline');
    expect($formattedConfig)->toHaveKey('subheading');
    expect($formattedConfig)->toHaveKey('cta_text');
});

test('component display name attribute', function () {
    $component = Component::factory()->create(['name' => 'Test Component']);

    expect($component->display_name)->toBe('Test Component');

    $componentWithoutName = Component::factory()->create(['name' => null, 'type' => 'hero']);

    expect($componentWithoutName->display_name)->toBe('Hero');
});

test('component is category method', function () {
    $component = Component::factory()->hero()->create();

    expect($component->isCategory('hero'))->toBeTrue();
    expect($component->isCategory('forms'))->toBeFalse();
});

test('component has config key method', function () {
    $component = Component::factory()->create([
        'config' => ['headline' => 'Test', 'nested' => ['key' => 'value']],
    ]);

    expect($component->hasConfigKey('headline'))->toBeTrue();
    expect($component->hasConfigKey('nonexistent'))->toBeFalse();
});

test('component get config value method', function () {
    $component = Component::factory()->create([
        'config' => ['headline' => 'Test', 'nested' => ['key' => 'value']],
    ]);

    expect($component->getConfigValue('headline'))->toBe('Test');
    expect($component->getConfigValue('nested.key'))->toBe('value');
    expect($component->getConfigValue('nonexistent', 'default'))->toBe('default');
});

test('component set config value method', function () {
    $component = Component::factory()->create(['config' => []]);

    $component->setConfigValue('headline', 'New Headline');
    $component->setConfigValue('nested.key', 'nested value');

    expect($component->config['headline'])->toBe('New Headline');
    expect($component->config['nested']['key'])->toBe('nested value');
});

test('component validate config method', function () {
    $validComponent = Component::factory()->hero()->create([
        'config' => [
            'headline' => 'Valid Headline',
            'subheading' => 'Valid Subheading',
            'cta_text' => 'Click Me',
            'cta_url' => 'https://example.com',
            'background_type' => 'image',
            'show_statistics' => true,
        ],
    ]);

    expect($validComponent->validateConfig())->toBeTrue();

    $invalidComponent = Component::factory()->hero()->create([
        'config' => [
            'headline' => str_repeat('a', 300), // Too long
            'background_type' => 'invalid_type',
        ],
    ]);

    expect($invalidComponent->validateConfig())->toBeFalse();
});

test('component validation rules', function () {
    $rules = Component::getValidationRules();

    expect($rules)->toBeArray();
    expect($rules)->toHaveKey('tenant_id');
    expect($rules)->toHaveKey('name');
    expect($rules)->toHaveKey('category');
    expect($rules)->toHaveKey('type');
});

test('component unique validation rules', function () {
    $rules = Component::getUniqueValidationRules();

    expect($rules)->toBeArray();
    expect($rules['slug'])->toContain('unique:components,slug');

    $rulesWithIgnore = Component::getUniqueValidationRules(1);

    expect($rulesWithIgnore['slug'])->toContain('unique:components,slug,1');
});

test('component uses custom collection', function () {
    $components = Component::factory()->count(3)->create();

    expect($components)->toBeInstanceOf(ComponentCollection::class);
})->skip('ComponentInstance model not yet created');

test('component factory creates valid components', function () {
    $component = Component::factory()->create();

    expect($component->tenant_id)->not->toBeNull();
    expect($component->name)->not->toBeNull();
    expect($component->slug)->not->toBeNull();
    expect($component->category)->toBeIn(Component::CATEGORIES);
    expect($component->type)->not->toBeNull();
    expect($component->config)->toBeArray();
    expect($component->metadata)->toBeArray();
    expect($component->version)->not->toBeNull();
    expect($component->is_active)->toBeBoolean();
});

test('component factory states', function () {
    $heroComponent = Component::factory()->hero()->create();
    expect($heroComponent->category)->toBe('hero');

    $formComponent = Component::factory()->form()->create();
    expect($formComponent->category)->toBe('forms');

    $activeComponent = Component::factory()->active()->create();
    expect($activeComponent->is_active)->toBeTrue();

    $inactiveComponent = Component::factory()->inactive()->create();
    expect($inactiveComponent->is_active)->toBeFalse();
});
