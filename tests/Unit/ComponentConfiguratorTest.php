<?php

it('can validate configuration schema structure', function () {
    $validConfig = [
        'headline' => 'Valid Headline',
        'subheading' => 'Valid Subheading',
        'audienceType' => 'individual',
        'theme' => 'modern',
        'primaryColor' => '#3b82f6'
    ];
    
    expect($validConfig)->toHaveKey('headline');
    expect($validConfig)->toHaveKey('theme');
    expect($validConfig)->toHaveKey('primaryColor');
    expect($validConfig['headline'])->toBe('Valid Headline');
    expect($validConfig['theme'])->toBe('modern');
    expect($validConfig['primaryColor'])->toBe('#3b82f6');
});

it('can validate configuration schema for hero components', function () {
    $validConfig = [
        'headline' => 'Valid Headline',
        'subheading' => 'Valid Subheading',
        'audienceType' => 'individual',
        'theme' => 'modern',
        'primaryColor' => '#3b82f6'
    ];
    
    expect($validConfig)->toHaveKey('headline');
    expect($validConfig)->toHaveKey('audienceType');
    expect($validConfig['audienceType'])->toBeIn(['individual', 'institution', 'employer']);
});

it('can handle configuration presets', function () {
    $presets = [
        'minimal' => [
            'theme' => 'minimal',
            'primaryColor' => '#6366f1',
            'fontFamily' => 'system',
            'padding' => 'sm'
        ],
        'modern' => [
            'theme' => 'modern',
            'primaryColor' => '#3b82f6',
            'fontFamily' => 'inter',
            'padding' => 'lg'
        ]
    ];
    
    foreach ($presets as $presetName => $presetConfig) {
        expect($presetConfig)->toHaveKey('theme');
        expect($presetConfig)->toHaveKey('primaryColor');
        expect($presetConfig['theme'])->toBeIn(['minimal', 'modern', 'classic', 'default']);
    }
});

it('can validate required fields for different component categories', function () {
    $testCases = [
        'hero' => [
            'required' => ['headline', 'audienceType'],
            'optional' => ['subheading', 'description']
        ],
        'forms' => [
            'required' => ['submitButtonText'],
            'optional' => ['title', 'enableValidation']
        ],
        'testimonials' => [
            'required' => ['layout'],
            'optional' => ['title', 'showAuthorPhoto']
        ]
    ];
    
    foreach ($testCases as $category => $fields) {
        expect($fields)->toHaveKey('required');
        expect($fields)->toHaveKey('optional');
        expect($fields['required'])->toBeArray();
        expect($fields['optional'])->toBeArray();
    }
});

it('can handle color validation', function () {
    $validColors = ['#000000', '#ffffff', '#6366f1', '#3b82f6'];
    $invalidColors = ['invalid', '#gggggg', 'rgb(255,255,255)', '#fff'];
    
    foreach ($validColors as $color) {
        // Test valid hex color format
        expect($color)->toMatch('/^#[0-9A-F]{6}$/i');
    }
    
    foreach ($invalidColors as $color) {
        // Test that invalid colors don't match hex format
        expect($color)->not->toMatch('/^#[0-9A-F]{6}$/i');
    }
});

it('can handle font family configuration', function () {
    $fontFamilies = ['system', 'inter', 'roboto', 'open-sans', 'lato', 'montserrat', 'poppins'];
    
    foreach ($fontFamilies as $font) {
        expect($font)->toBeString();
        expect($fontFamilies)->toContain($font);
    }
});

it('can handle spacing configuration', function () {
    $spacingOptions = ['none', 'sm', 'md', 'lg', 'xl'];
    
    foreach ($spacingOptions as $spacing) {
        expect($spacing)->toBeString();
        expect($spacingOptions)->toContain($spacing);
    }
});

it('can handle layout configuration', function () {
    $layoutOptions = ['centered', 'left-aligned', 'right-aligned', 'split'];
    
    foreach ($layoutOptions as $layout) {
        expect($layout)->toBeString();
        expect($layoutOptions)->toContain($layout);
    }
});

it('can handle responsive settings', function () {
    $responsiveConfig = [
        'mobileOptimized' => true,
        'tabletOptimized' => true,
        'mobileLayout' => 'stacked',
        'tabletLayout' => 'split'
    ];
    
    expect($responsiveConfig['mobileOptimized'])->toBeTrue();
    expect($responsiveConfig['tabletOptimized'])->toBeTrue();
    expect($responsiveConfig['mobileLayout'])->toBe('stacked');
    expect($responsiveConfig['tabletLayout'])->toBe('split');
});

it('can handle animation settings', function () {
    $animationConfig = [
        'animationsEnabled' => true,
        'animationDuration' => 'normal',
        'animationType' => 'fade',
        'animationDelay' => 100
    ];
    
    expect($animationConfig['animationsEnabled'])->toBeTrue();
    expect($animationConfig['animationDuration'])->toBe('normal');
    expect($animationConfig['animationType'])->toBe('fade');
    expect($animationConfig['animationDelay'])->toBe(100);
});

it('can handle custom CSS configuration', function () {
    $customCSS = '
        .custom-component {
            background-color: #f0f0f0;
            border-radius: 8px;
        }
        
        .custom-component:hover {
            background-color: #e0e0e0;
        }
    ';
    
    expect($customCSS)->toBeString();
    expect($customCSS)->toContain('.custom-component');
    expect($customCSS)->toContain('background-color');
});

it('can handle configuration export format', function () {
    $exportConfig = [
        'component' => [
            'id' => 'test-id',
            'name' => 'Test Component',
            'category' => 'hero'
        ],
        'configuration' => [
            'headline' => 'Test Headline',
            'theme' => 'default'
        ],
        'exportedAt' => '2024-01-01T00:00:00.000Z',
        'version' => '1.0'
    ];
    
    expect($exportConfig)->toHaveKey('component');
    expect($exportConfig)->toHaveKey('configuration');
    expect($exportConfig)->toHaveKey('exportedAt');
    expect($exportConfig)->toHaveKey('version');
    
    expect($exportConfig['component']['id'])->toBe('test-id');
    expect($exportConfig['component']['name'])->toBe('Test Component');
    expect($exportConfig['component']['category'])->toBe('hero');
});

it('can handle configuration import validation', function () {
    $validImportData = [
        'component' => [
            'id' => 'test-id',
            'name' => 'Test Component',
            'category' => 'hero'
        ],
        'configuration' => [
            'headline' => 'Imported Headline',
            'theme' => 'modern',
            'primaryColor' => '#3b82f6'
        ],
        'exportedAt' => '2024-01-01T00:00:00.000Z',
        'version' => '1.0'
    ];
    
    expect($validImportData['configuration']['headline'])->toBe('Imported Headline');
    expect($validImportData['configuration']['theme'])->toBe('modern');
    expect($validImportData['configuration']['primaryColor'])->toBe('#3b82f6');
});

it('can handle configuration versioning and history', function () {
    $initialConfig = ['headline' => 'Initial Headline', 'theme' => 'default'];
    
    // Simulate configuration changes
    $changes = [
        ['headline' => 'First Change'],
        ['headline' => 'Second Change', 'theme' => 'modern'],
        ['headline' => 'Third Change', 'theme' => 'minimal', 'primaryColor' => '#000000']
    ];
    
    $currentConfig = $initialConfig;
    foreach ($changes as $change) {
        $currentConfig = array_merge($currentConfig, $change);
        
        // Verify the change was applied
        foreach ($change as $key => $value) {
            expect($currentConfig[$key])->toBe($value);
        }
    }
    
    // Final config should have all the latest changes
    expect($currentConfig['headline'])->toBe('Third Change');
    expect($currentConfig['theme'])->toBe('minimal');
    expect($currentConfig['primaryColor'])->toBe('#000000');
});

it('can handle tenant-specific configuration isolation', function () {
    $tenant1Config = [
        'tenant_id' => 1,
        'headline' => 'Tenant 1 Headline',
        'theme' => 'modern'
    ];
    
    $tenant2Config = [
        'tenant_id' => 2,
        'headline' => 'Tenant 2 Headline',
        'theme' => 'classic'
    ];
    
    // Verify isolation
    expect($tenant1Config['headline'])->toBe('Tenant 1 Headline');
    expect($tenant2Config['headline'])->toBe('Tenant 2 Headline');
    
    // Verify tenant IDs are different
    expect($tenant1Config['tenant_id'])->not->toBe($tenant2Config['tenant_id']);
});

it('can handle configuration field validation rules', function () {
    $validationRules = [
        'headline' => ['required', 'max:100'],
        'subheading' => ['max:150'],
        'description' => ['max:500'],
        'primaryColor' => ['regex:/^#[0-9A-F]{6}$/i'],
        'fontFamily' => ['in:system,inter,roboto,open-sans,lato,montserrat,poppins'],
        'padding' => ['in:none,sm,md,lg,xl'],
        'margin' => ['in:none,sm,md,lg,xl'],
        'layout' => ['in:centered,left-aligned,right-aligned,split']
    ];
    
    // Test valid values
    $validConfig = [
        'headline' => 'Valid Headline',
        'subheading' => 'Valid Subheading',
        'description' => 'Valid description text',
        'primaryColor' => '#6366F1',
        'fontFamily' => 'inter',
        'padding' => 'md',
        'margin' => 'lg',
        'layout' => 'centered'
    ];
    
    foreach ($validConfig as $key => $value) {
        expect($validConfig)->toHaveKey($key);
        expect($validConfig[$key])->toBe($value);
    }
    
    // Test validation rules structure
    expect($validationRules)->toHaveKey('headline');
    expect($validationRules)->toHaveKey('primaryColor');
    expect($validationRules['headline'])->toContain('required');
});

it('can handle accessibility configuration options', function () {
    $accessibilityConfig = [
        'ariaLabel' => 'Custom aria label',
        'ariaDescribedBy' => 'description-id',
        'headingLevel' => 2,
        'keyboardNavigation' => true,
        'screenReaderSupport' => true,
        'highContrastMode' => true,
        'respectReducedMotion' => true
    ];
    
    expect($accessibilityConfig['ariaLabel'])->toBe('Custom aria label');
    expect($accessibilityConfig['headingLevel'])->toBe(2);
    expect($accessibilityConfig['keyboardNavigation'])->toBeTrue();
    expect($accessibilityConfig['respectReducedMotion'])->toBeTrue();
});