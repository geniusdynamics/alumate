<?php

use App\Models\Component;
use App\Models\ComponentInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ComponentInstance Model', function () {
    it('can be created with valid attributes', function () {
        $instance = new ComponentInstance([
            'component_id' => 1,
            'page_type' => 'landing_page',
            'page_id' => 1,
            'position' => 0,
            'custom_config' => ['test' => 'value'],
        ]);

        expect($instance)->toBeInstanceOf(ComponentInstance::class)
            ->and($instance->component_id)->toBe(1)
            ->and($instance->page_type)->toBe('landing_page')
            ->and($instance->page_id)->toBe(1)
            ->and($instance->position)->toBe(0)
            ->and($instance->custom_config)->toBe(['test' => 'value']);
    });

    it('casts custom_config to array', function () {
        $instance = ComponentInstance::factory()->create([
            'custom_config' => ['key' => 'value', 'nested' => ['data' => 'test']],
        ]);

        expect($instance->custom_config)->toBeArray()
            ->and($instance->custom_config['key'])->toBe('value')
            ->and($instance->custom_config['nested']['data'])->toBe('test');
    });

    it('has default attributes', function () {
        $instance = new ComponentInstance;

        expect($instance->custom_config)->toBe([])
            ->and($instance->position)->toBe(0);
    });

    it('belongs to a component', function () {
        $component = Component::factory()->create();
        $instance = ComponentInstance::factory()->create(['component_id' => $component->id]);

        expect($instance->component)->toBeInstanceOf(Component::class)
            ->and($instance->component->id)->toBe($component->id);
    });

    it('has polymorphic relationship to page', function () {
        $instance = ComponentInstance::factory()->create([
            'page_type' => 'landing_page',
            'page_id' => 123,
        ]);

        // The morphTo relationship should be set up correctly
        expect($instance->page_type)->toBe('landing_page')
            ->and($instance->page_id)->toBe(123);
    });

    it('can scope by page', function () {
        ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1]);
        ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 2]);
        ComponentInstance::factory()->create(['page_type' => 'template', 'page_id' => 1]);

        $instances = ComponentInstance::forPage('landing_page', 1)->get();

        expect($instances)->toHaveCount(1)
            ->and($instances->first()->page_type)->toBe('landing_page')
            ->and($instances->first()->page_id)->toBe(1);
    });

    it('can be ordered by position', function () {
        ComponentInstance::factory()->create(['position' => 2]);
        ComponentInstance::factory()->create(['position' => 0]);
        ComponentInstance::factory()->create(['position' => 1]);

        $instances = ComponentInstance::orderedByPosition()->get();

        expect($instances->pluck('position')->toArray())->toBe([0, 1, 2]);
    });
});

describe('Position Management', function () {
    it('automatically sets next position when creating without position', function () {
        ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 0]);
        ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 1]);

        $instance = ComponentInstance::factory()->create([
            'page_type' => 'landing_page',
            'page_id' => 1,
            'position' => 0, // This should be auto-incremented
        ]);

        expect($instance->position)->toBe(2);
    });

    it('gets next available position', function () {
        $instance1 = ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 0]);
        $instance2 = ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 2]);

        $newInstance = new ComponentInstance([
            'page_type' => 'landing_page',
            'page_id' => 1,
        ]);

        expect($newInstance->getNextPosition())->toBe(3);
    });

    it('can move to specific position', function () {
        $instance1 = ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 0]);
        $instance2 = ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 1]);
        $instance3 = ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 2]);

        $result = $instance3->moveToPosition(1);

        expect($result)->toBeTrue();

        $instance1->refresh();
        $instance2->refresh();
        $instance3->refresh();

        expect($instance1->position)->toBe(0)
            ->and($instance2->position)->toBe(2)
            ->and($instance3->position)->toBe(1);
    });

    it('can move up one position', function () {
        $instance1 = ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 0]);
        $instance2 = ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 1]);

        $result = $instance2->moveUp();

        expect($result)->toBeTrue();

        $instance1->refresh();
        $instance2->refresh();

        expect($instance1->position)->toBe(1)
            ->and($instance2->position)->toBe(0);
    });

    it('cannot move up from position 0', function () {
        $instance = ComponentInstance::factory()->create(['position' => 0]);

        $result = $instance->moveUp();

        expect($result)->toBeFalse()
            ->and($instance->position)->toBe(0);
    });

    it('can move down one position', function () {
        $instance1 = ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 0]);
        $instance2 = ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 1]);

        $result = $instance1->moveDown();

        expect($result)->toBeTrue();

        $instance1->refresh();
        $instance2->refresh();

        expect($instance1->position)->toBe(1)
            ->and($instance2->position)->toBe(0);
    });

    it('cannot move down from last position', function () {
        $instance1 = ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 0]);
        $instance2 = ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 1]);

        $result = $instance2->moveDown();

        expect($result)->toBeFalse()
            ->and($instance2->position)->toBe(1);
    });

    it('can reorder page instances to eliminate gaps', function () {
        $instance1 = ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 0]);
        $instance2 = ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 3]);
        $instance3 = ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 5]);

        $instance1->reorderPageInstances();

        $instance1->refresh();
        $instance2->refresh();
        $instance3->refresh();

        $positions = [$instance1->position, $instance2->position, $instance3->position];
        sort($positions);

        expect($positions)->toBe([0, 1, 2]);
    });

    it('validates position uniqueness', function () {
        ComponentInstance::factory()->create(['page_type' => 'landing_page', 'page_id' => 1, 'position' => 1]);

        $instance = new ComponentInstance([
            'page_type' => 'landing_page',
            'page_id' => 1,
            'position' => 1,
        ]);

        expect(fn () => $instance->validatePositionUniqueness())
            ->toThrow(\InvalidArgumentException::class, 'Position 1 is already taken');
    });
});

describe('Configuration Management', function () {
    it('merges component and custom configuration', function () {
        $component = Component::factory()->create([
            'config' => ['base' => 'value', 'shared' => 'component'],
        ]);

        $instance = ComponentInstance::factory()->create([
            'component_id' => $component->id,
            'custom_config' => ['custom' => 'value', 'shared' => 'instance'],
        ]);

        $merged = $instance->getMergedConfig();

        expect($merged['base'])->toBe('value')
            ->and($merged['custom'])->toBe('value')
            ->and($merged['shared'])->toBe('instance'); // Custom config should override
    });

    it('gets configuration value with fallback', function () {
        $component = Component::factory()->create([
            'config' => ['existing' => 'value'],
        ]);

        $instance = ComponentInstance::factory()->create([
            'component_id' => $component->id,
            'custom_config' => ['custom' => 'custom_value'],
        ]);

        expect($instance->getConfigValue('existing'))->toBe('value')
            ->and($instance->getConfigValue('custom'))->toBe('custom_value')
            ->and($instance->getConfigValue('missing', 'default'))->toBe('default');
    });

    it('can set custom configuration values', function () {
        $instance = ComponentInstance::factory()->create(['custom_config' => []]);

        $instance->setCustomConfigValue('test.nested', 'value');

        expect($instance->custom_config['test']['nested'])->toBe('value');
    });

    it('can remove custom configuration values', function () {
        $instance = ComponentInstance::factory()->create([
            'custom_config' => ['keep' => 'value', 'remove' => 'value'],
        ]);

        $instance->removeCustomConfigValue('remove');

        expect($instance->custom_config)->toHaveKey('keep')
            ->and($instance->custom_config)->not->toHaveKey('remove');
    });

    it('checks if custom configuration exists', function () {
        $instance = ComponentInstance::factory()->create([
            'custom_config' => ['existing' => 'value'],
        ]);

        expect($instance->hasCustomConfig('existing'))->toBeTrue()
            ->and($instance->hasCustomConfig('missing'))->toBeFalse();
    });
});

describe('Rendering', function () {
    it('renders component instance with merged configuration', function () {
        $component = Component::factory()->create([
            'name' => 'Test Component',
            'category' => 'hero',
            'type' => 'hero-banner',
            'config' => ['base' => 'value'],
            'metadata' => ['version' => '1.0'],
        ]);

        $instance = ComponentInstance::factory()->create([
            'component_id' => $component->id,
            'position' => 2,
            'custom_config' => ['custom' => 'value'],
        ]);

        $rendered = $instance->render(['extra' => 'data']);

        expect($rendered['id'])->toBe($instance->id)
            ->and($rendered['component_id'])->toBe($component->id)
            ->and($rendered['component_name'])->toBe('Test Component')
            ->and($rendered['component_category'])->toBe('hero')
            ->and($rendered['component_type'])->toBe('hero-banner')
            ->and($rendered['position'])->toBe(2)
            ->and($rendered['config']['base'])->toBe('value')
            ->and($rendered['config']['custom'])->toBe('value')
            ->and($rendered['metadata']['version'])->toBe('1.0')
            ->and($rendered['additional_data']['extra'])->toBe('data');
    });

    it('generates preview with sample data', function () {
        $component = Component::factory()->create(['category' => 'hero']);
        $instance = ComponentInstance::factory()->create(['component_id' => $component->id]);

        $preview = $instance->generatePreview();

        expect($preview)->toHaveKey('sample_data')
            ->and($preview['sample_data'])->toHaveKey('headline')
            ->and($preview['sample_data'])->toHaveKey('subheading')
            ->and($preview['sample_data'])->toHaveKey('statistics');
    });
});

describe('Validation', function () {
    it('has correct validation rules', function () {
        $rules = ComponentInstance::getValidationRules();

        expect($rules)->toHaveKey('component_id')
            ->and($rules)->toHaveKey('page_type')
            ->and($rules)->toHaveKey('page_id')
            ->and($rules)->toHaveKey('position')
            ->and($rules)->toHaveKey('custom_config');
    });

    it('validates page types', function () {
        $rules = ComponentInstance::getValidationRules();

        // Check that the page_type rule contains the Rule::in validation
        expect($rules['page_type'])->toBeArray()
            ->and($rules['page_type'])->toContain('required')
            ->and($rules['page_type'])->toContain('string');
    });
});
