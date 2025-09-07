<?php

use App\Models\Component;
use App\Models\ComponentTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('it has correct fillable fields', function () {
    $theme = new ComponentTheme;

    $expectedFillable = [
        'tenant_id',
        'name',
        'slug',
        'config',
        'is_default',
    ];

    expect($theme->getFillable())->toEqual($expectedFillable);
});

test('it has correct casts', function () {
    $theme = new ComponentTheme;

    $casts = $theme->getCasts();

    expect($casts)->toHaveKey('config');
    expect($casts)->toHaveKey('is_default');
    expect($casts['config'])->toBe('array');
    expect($casts['is_default'])->toBe('boolean');
});

test('it can generate css variables', function () {
    $theme = new ComponentTheme;
    $theme->config = [
        'colors' => ['primary' => '#007bff'],
        'typography' => ['font_family' => 'Arial, sans-serif'],
        'spacing' => ['base' => '1rem'],
    ];
    $theme->is_default = true; // Make it default to avoid database queries

    $css = $theme->generateCssVariables();

    expect($css)->toContain('--color-primary: #007bff;');
    expect($css)->toContain('--font-family: Arial, sans-serif;');
    expect($css)->toContain('--spacing-base: 1rem;');
    expect($css)->toStartWith(':root {');
    expect($css)->toEndWith("}\n");
});

test('it can calculate color contrast', function () {
    $theme = new ComponentTheme;

    // High contrast (black on white)
    $highContrast = $theme->getColorContrast('#000000', '#ffffff');
    expect($highContrast)->toBeGreaterThan(15);

    // Low contrast (light gray on white)
    $lowContrast = $theme->getColorContrast('#f0f0f0', '#ffffff');
    expect($lowContrast)->toBeLessThan(2);

    // Medium contrast
    $mediumContrast = $theme->getColorContrast('#666666', '#ffffff');
    expect($mediumContrast)->toBeGreaterThan(4);
    expect($mediumContrast)->toBeLessThan(8);
});

test('it casts config to array', function () {
    $config = [
        'colors' => ['primary' => '#007bff'],
        'typography' => ['font_family' => 'Arial'],
    ];

    $theme = ComponentTheme::factory()->create([
        'config' => $config,
    ]);

    expect($theme->config)->toBeArray();
    expect($theme->config)->toBe($config);
});

test('it casts is_default to boolean', function () {
    $theme = ComponentTheme::factory()->create([
        'is_default' => 1,
    ]);

    expect($theme->is_default)->toBeBool();
    expect($theme->is_default)->toBeTrue();
});

test('it can validate valid config', function () {
    $theme = ComponentTheme::factory()->create();

    $validConfig = [
        'colors' => [
            'primary' => '#007bff',
            'secondary' => '#6c757d',
            'background' => '#ffffff',
        ],
        'typography' => [
            'font_family' => 'Arial, sans-serif',
            'font_sizes' => [
                'base' => '16px',
            ],
            'line_height' => 1.6,
        ],
        'spacing' => [
            'base' => '1rem',
            'small' => '0.5rem',
        ],
    ];

    expect($theme->validateConfig($validConfig))->toBeTrue();
});

test('it validates hex colors', function () {
    $theme = ComponentTheme::factory()->create();

    // Valid hex colors
    $validConfig = [
        'colors' => ['primary' => '#007bff'],
        'typography' => ['font_family' => 'Arial'],
        'spacing' => ['base' => '1rem'],
    ];
    expect($theme->validateConfig($validConfig))->toBeTrue();

    // Valid short hex colors
    $validConfig['colors']['primary'] = '#fff';
    expect($theme->validateConfig($validConfig))->toBeTrue();

    // Invalid hex color
    $invalidConfig = $validConfig;
    $invalidConfig['colors']['primary'] = 'blue';
    expect(fn () => $theme->validateConfig($invalidConfig))
        ->toThrow(ValidationException::class);
});

test('it validates font sizes', function () {
    $theme = ComponentTheme::factory()->create();

    $baseConfig = [
        'colors' => ['primary' => '#007bff'],
        'typography' => ['font_family' => 'Arial'],
        'spacing' => ['base' => '1rem'],
    ];

    // Valid font sizes
    $validSizes = ['16px', '1.2rem', '1.5em'];
    foreach ($validSizes as $size) {
        $config = $baseConfig;
        $config['typography']['font_sizes'] = ['base' => $size];
        expect($theme->validateConfig($config))->toBeTrue();
    }

    // Invalid font size
    $invalidConfig = $baseConfig;
    $invalidConfig['typography']['font_sizes'] = ['base' => 'large'];
    expect(fn () => $theme->validateConfig($invalidConfig))
        ->toThrow(ValidationException::class);
});

test('it validates spacing values', function () {
    $theme = ComponentTheme::factory()->create();

    $baseConfig = [
        'colors' => ['primary' => '#007bff'],
        'typography' => ['font_family' => 'Arial'],
        'spacing' => ['base' => '1rem'],
    ];

    // Valid spacing values
    $validSpacing = ['1rem', '16px', '1.5em'];
    foreach ($validSpacing as $spacing) {
        $config = $baseConfig;
        $config['spacing']['base'] = $spacing;
        expect($theme->validateConfig($config))->toBeTrue();
    }

    // Invalid spacing
    $invalidConfig = $baseConfig;
    $invalidConfig['spacing']['base'] = 'medium';
    expect(fn () => $theme->validateConfig($invalidConfig))
        ->toThrow(ValidationException::class);
});

test('it can apply theme to components', function () {
    $theme = ComponentTheme::factory()->create(['tenant_id' => 1]);
    $components = Component::factory()->count(3)->create(['tenant_id' => 1]);

    $updatedCount = $theme->applyToComponents($components->pluck('id')->toArray());

    expect($updatedCount)->toBe(3);

    foreach ($components as $component) {
        $component->refresh();
        expect($component->theme_id)->toBe($theme->id);
    }
});

test('it can apply theme to all tenant components', function () {
    $theme = ComponentTheme::factory()->create(['tenant_id' => 1]);
    Component::factory()->count(5)->create(['tenant_id' => 1]);
    Component::factory()->count(2)->create(['tenant_id' => 2]); // Different tenant

    $updatedCount = $theme->applyToComponents();

    expect($updatedCount)->toBe(5);

    $tenantComponents = Component::where('tenant_id', 1)->get();
    foreach ($tenantComponents as $component) {
        expect($component->theme_id)->toBe($theme->id);
    }

    // Other tenant components should not be affected
    $otherTenantComponents = Component::where('tenant_id', 2)->get();
    foreach ($otherTenantComponents as $component) {
        expect($component->theme_id)->toBeNull();
    }
});

test('it gets inheritance chain for default theme', function () {
    $defaultTheme = ComponentTheme::factory()->default()->create(['tenant_id' => 1]);

    $chain = $defaultTheme->getInheritanceChain();

    expect($chain)->toHaveCount(1);
    expect($chain[0])->toBe($defaultTheme->config);
});

test('it gets inheritance chain for custom theme', function () {
    $defaultTheme = ComponentTheme::factory()->default()->create(['tenant_id' => 1]);
    $customTheme = ComponentTheme::factory()->create(['tenant_id' => 1]);

    $chain = $customTheme->getInheritanceChain();

    expect($chain)->toHaveCount(2);
    expect($chain[0])->toBe($customTheme->config);
    expect($chain[1])->toBe($defaultTheme->config);
});

test('it merges config with inheritance', function () {
    $defaultTheme = ComponentTheme::factory()->create([
        'tenant_id' => 1,
        'is_default' => true,
        'config' => [
            'colors' => ['primary' => '#007bff', 'secondary' => '#6c757d'],
            'typography' => ['font_family' => 'Arial'],
        ],
    ]);

    $customTheme = ComponentTheme::factory()->create([
        'tenant_id' => 1,
        'config' => [
            'colors' => ['primary' => '#ff0000'], // Override primary color
            'spacing' => ['base' => '1rem'], // Add new property
        ],
    ]);

    $mergedConfig = $customTheme->getMergedConfig();

    expect($mergedConfig['colors']['primary'])->toBe('#ff0000'); // Overridden
    expect($mergedConfig['colors']['secondary'])->toBe('#6c757d'); // Inherited
    expect($mergedConfig['typography']['font_family'])->toBe('Arial'); // Inherited
    expect($mergedConfig['spacing']['base'])->toBe('1rem'); // Added
});

test('it generates css variables', function () {
    $theme = ComponentTheme::factory()->create([
        'config' => [
            'colors' => ['primary' => '#007bff'],
            'typography' => ['font_family' => 'Arial, sans-serif'],
            'spacing' => ['base' => '1rem'],
        ],
    ]);

    $css = $theme->generateCssVariables();

    expect($css)->toContain('--color-primary: #007bff;');
    expect($css)->toContain('--font-family: Arial, sans-serif;');
    expect($css)->toContain('--spacing-base: 1rem;');
    expect($css)->toStartWith(':root {');
    expect($css)->toEndWith("}\n");
});

test('it generates preview html', function () {
    $theme = ComponentTheme::factory()->create([
        'name' => 'Test Theme',
        'config' => [
            'colors' => ['primary' => '#007bff'],
            'typography' => ['font_family' => 'Arial'],
            'spacing' => ['base' => '1rem'],
        ],
    ]);

    $html = $theme->generatePreviewHtml();

    expect($html)->toContain('<!DOCTYPE html>');
    expect($html)->toContain('Theme Preview: Test Theme');
    expect($html)->toContain('--color-primary: #007bff;');
    expect($html)->toContain('Sample Button');
});

test('it compiles to css', function () {
    $theme = ComponentTheme::factory()->create([
        'config' => [
            'colors' => ['primary' => '#007bff'],
            'spacing' => ['base' => '1rem'],
        ],
    ]);

    $css = $theme->compileToCss();

    expect($css)->toContain('--color-primary: #007bff;');
    expect($css)->toContain('.component-button');
    expect($css)->toContain('background-color: var(--color-primary);');
});

test('it creates default theme', function () {
    $theme = ComponentTheme::createDefaultTheme(1, 'Custom Default');

    expect($theme->tenant_id)->toBe(1);
    expect($theme->name)->toBe('Custom Default');
    expect($theme->slug)->toBe('custom-default');
    expect($theme->is_default)->toBeTrue();
    expect($theme->config)->toHaveKey('colors');
    expect($theme->config)->toHaveKey('typography');
    expect($theme->config)->toHaveKey('spacing');
});

test('it calculates color contrast', function () {
    $theme = ComponentTheme::factory()->create();

    // High contrast (black on white)
    $highContrast = $theme->getColorContrast('#000000', '#ffffff');
    expect($highContrast)->toBeGreaterThan(15);

    // Low contrast (light gray on white)
    $lowContrast = $theme->getColorContrast('#f0f0f0', '#ffffff');
    expect($lowContrast)->toBeLessThan(2);

    // Medium contrast
    $mediumContrast = $theme->getColorContrast('#666666', '#ffffff');
    expect($mediumContrast)->toBeGreaterThan(4);
    expect($mediumContrast)->toBeLessThan(8);
});

test('it checks accessibility', function () {
    // High contrast theme (should pass)
    $accessibleTheme = ComponentTheme::factory()->create([
        'config' => [
            'colors' => [
                'primary' => '#000000',
                'text' => '#000000',
                'background' => '#ffffff',
            ],
            'typography' => ['font_family' => 'Arial'],
            'spacing' => ['base' => '1rem'],
        ],
    ]);

    $issues = $accessibleTheme->checkAccessibility();
    expect($issues)->toBeEmpty();

    // Low contrast theme (should fail)
    $inaccessibleTheme = ComponentTheme::factory()->create([
        'config' => [
            'colors' => [
                'primary' => '#f0f0f0',
                'text' => '#f0f0f0',
                'background' => '#ffffff',
            ],
            'typography' => ['font_family' => 'Arial'],
            'spacing' => ['base' => '1rem'],
        ],
    ]);

    $issues = $inaccessibleTheme->checkAccessibility();
    expect($issues)->not->toBeEmpty();
    expect($issues)->toHaveCount(2); // Both primary and text colors should fail
});

test('it scopes to tenant', function () {
    ComponentTheme::factory()->create(['tenant_id' => 1]);
    ComponentTheme::factory()->create(['tenant_id' => 2]);

    $tenant1Themes = ComponentTheme::forTenant(1)->get();
    $tenant2Themes = ComponentTheme::forTenant(2)->get();

    expect($tenant1Themes)->toHaveCount(1);
    expect($tenant2Themes)->toHaveCount(1);
    expect($tenant1Themes->first()->tenant_id)->toBe(1);
    expect($tenant2Themes->first()->tenant_id)->toBe(2);
});
test('it can generate preview html', function () {
    $theme = new ComponentTheme;
    $theme->name = 'Test Theme';
    $theme->config = [
        'colors' => ['primary' => '#007bff'],
        'typography' => ['font_family' => 'Arial'],
        'spacing' => ['base' => '1rem'],
    ];
    $theme->is_default = true; // Make it default to avoid database queries

    $html = $theme->generatePreviewHtml();

    expect($html)->toContain('<!DOCTYPE html>');
    expect($html)->toContain('Theme Preview: Test Theme');
    expect($html)->toContain('--color-primary: #007bff;');
    expect($html)->toContain('Sample Button');
});

test('it can compile to css', function () {
    $theme = new ComponentTheme;
    $theme->config = [
        'colors' => ['primary' => '#007bff'],
        'spacing' => ['base' => '1rem'],
    ];
    $theme->is_default = true; // Make it default to avoid database queries

    $css = $theme->compileToCss();

    expect($css)->toContain('--color-primary: #007bff;');
    expect($css)->toContain('.component-button');
    expect($css)->toContain('background-color: var(--color-primary);');
});

test('it can check accessibility for high contrast', function () {
    $theme = new ComponentTheme;
    $theme->config = [
        'colors' => [
            'primary' => '#000000',
            'text' => '#000000',
            'background' => '#ffffff',
        ],
        'typography' => ['font_family' => 'Arial'],
        'spacing' => ['base' => '1rem'],
    ];
    $theme->is_default = true; // Make it default to avoid database queries

    $issues = $theme->checkAccessibility();
    expect($issues)->toBeEmpty();
});

test('it can check accessibility for low contrast', function () {
    $theme = new ComponentTheme;
    $theme->config = [
        'colors' => [
            'primary' => '#f0f0f0',
            'text' => '#f0f0f0',
            'background' => '#ffffff',
        ],
        'typography' => ['font_family' => 'Arial'],
        'spacing' => ['base' => '1rem'],
    ];
    $theme->is_default = true; // Make it default to avoid database queries

    $issues = $theme->checkAccessibility();
    expect($issues)->not->toBeEmpty();
    expect($issues)->toHaveCount(2); // Both primary and text colors should fail
});
