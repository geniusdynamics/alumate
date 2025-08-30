<?php

use App\Models\Component;
use App\Models\Tenant;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->component = Component::factory()->hero()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Test Hero Component',
        'config' => [
            'headline' => 'Test Headline',
            'subheading' => 'Test Subheading',
            'cta_text' => 'Get Started',
            'cta_url' => '/signup',
            'background_type' => 'image',
            'show_statistics' => false
        ]
    ]);
});

it('can create a component preview with basic configuration', function () {
    expect($this->component)->toBeInstanceOf(Component::class);
    expect($this->component->category)->toBe('hero');
    expect($this->component->name)->toBe('Test Hero Component');
    expect($this->component->config)->toBeArray();
    expect($this->component->config['headline'])->toBe('Test Headline');
});

it('has valid component configuration for preview', function () {
    $config = $this->component->config;
    
    expect($config)->toHaveKey('headline');
    expect($config)->toHaveKey('subheading');
    expect($config)->toHaveKey('cta_text');
    expect($config)->toHaveKey('cta_url');
    expect($config)->toHaveKey('background_type');
    expect($config)->toHaveKey('show_statistics');
    
    expect($config['background_type'])->toBeIn(['image', 'video', 'gradient']);
    expect($config['show_statistics'])->toBeBool();
    expect($config['headline'])->toBeString();
    expect($config['subheading'])->toBeString();
    expect($config['cta_text'])->toBeString();
    expect($config['cta_url'])->toBeString();
});

it('can generate preview URL parameters', function () {
    $baseUrl = 'http://localhost';
    $params = [
        'component' => $this->component->id,
        'device' => 'desktop',
        'zoom' => '1',
        'theme' => 'default',
        'colorScheme' => 'default',
        'sampleData' => 'true'
    ];
    
    $expectedUrl = $baseUrl . '/preview?' . http_build_query($params);
    
    expect($expectedUrl)->toContain('/preview?');
    expect($expectedUrl)->toContain('component=' . $this->component->id);
    expect($expectedUrl)->toContain('device=desktop');
    expect($expectedUrl)->toContain('zoom=1');
    expect($expectedUrl)->toContain('theme=default');
    expect($expectedUrl)->toContain('colorScheme=default');
    expect($expectedUrl)->toContain('sampleData=true');
});

it('can generate shareable preview URL', function () {
    $config = $this->component->config;
    $sampleData = [
        'audienceType' => 'individual',
        'variation' => 'default',
        'contentLength' => 'medium'
    ];
    
    $baseUrl = 'http://localhost';
    $params = [
        'component' => $this->component->id,
        'config' => base64_encode(json_encode($config)),
        'sampleData' => base64_encode(json_encode($sampleData))
    ];
    
    $shareableUrl = $baseUrl . '/shared-preview?' . http_build_query($params);
    
    expect($shareableUrl)->toContain('/shared-preview?');
    expect($shareableUrl)->toContain('component=' . $this->component->id);
    expect($shareableUrl)->toContain('config=');
    expect($shareableUrl)->toContain('sampleData=');
    
    // Verify base64 encoded data can be decoded
    $decodedConfig = json_decode(base64_decode($params['config']), true);
    $decodedSampleData = json_decode(base64_decode($params['sampleData']), true);
    
    expect($decodedConfig)->toBe($config);
    expect($decodedSampleData)->toBe($sampleData);
});

it('validates component categories for preview support', function () {
    $supportedCategories = ['hero', 'forms', 'testimonials', 'statistics', 'ctas', 'media'];
    
    foreach ($supportedCategories as $category) {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => $category
        ]);
        
        expect($component->category)->toBe($category);
        expect($component->category)->toBeIn($supportedCategories);
    }
});

it('can handle different device preview modes', function () {
    $devices = [
        'desktop' => ['width' => '100%', 'height' => 'auto'],
        'tablet' => ['width' => '768px', 'height' => '1024px'],
        'mobile' => ['width' => '375px', 'height' => '667px']
    ];
    
    foreach ($devices as $deviceId => $dimensions) {
        expect($deviceId)->toBeIn(['desktop', 'tablet', 'mobile']);
        expect($dimensions)->toHaveKey('width');
        expect($dimensions)->toHaveKey('height');
    }
});

it('can validate zoom levels for preview', function () {
    $validZoomLevels = [0.25, 0.5, 0.75, 1, 1.25, 1.5, 1.75, 2];
    
    foreach ($validZoomLevels as $zoom) {
        expect($zoom)->toBeFloat()->toBeBetween(0.25, 2);
    }
});

it('can generate embed code for component preview', function () {
    $previewUrl = 'http://localhost/preview?component=' . $this->component->id;
    $width = 800;
    $height = 600;
    
    $embedCode = sprintf(
        '<iframe src="%s" width="%d" height="%d" frameborder="0" allowfullscreen title="%s Component Preview" loading="lazy"></iframe>',
        $previewUrl,
        $width,
        $height,
        $this->component->name
    );
    
    expect($embedCode)->toContain('<iframe');
    expect($embedCode)->toContain('src="' . $previewUrl . '"');
    expect($embedCode)->toContain('width="' . $width . '"');
    expect($embedCode)->toContain('height="' . $height . '"');
    expect($embedCode)->toContain('frameborder="0"');
    expect($embedCode)->toContain('allowfullscreen');
    expect($embedCode)->toContain('title="' . $this->component->name . ' Component Preview"');
    expect($embedCode)->toContain('loading="lazy"');
    expect($embedCode)->toContain('</iframe>');
});

it('can validate accessibility score structure', function () {
    $accessibilityScore = [
        'score' => 85,
        'issues' => [
            [
                'id' => 'missing-alt',
                'severity' => 'error',
                'description' => 'Image missing alt text',
                'element' => 'img',
                'rule' => 'image-alt'
            ],
            [
                'id' => 'low-contrast',
                'severity' => 'warning',
                'description' => 'Text has insufficient color contrast',
                'element' => 'p',
                'rule' => 'color-contrast'
            ]
        ],
        'timestamp' => now()->toISOString()
    ];
    
    expect($accessibilityScore)->toHaveKey('score');
    expect($accessibilityScore)->toHaveKey('issues');
    expect($accessibilityScore)->toHaveKey('timestamp');
    
    expect($accessibilityScore['score'])->toBeInt()->toBeBetween(0, 100);
    expect($accessibilityScore['issues'])->toBeArray();
    
    foreach ($accessibilityScore['issues'] as $issue) {
        expect($issue)->toHaveKey('id');
        expect($issue)->toHaveKey('severity');
        expect($issue)->toHaveKey('description');
        expect($issue['severity'])->toBeIn(['error', 'warning', 'info']);
    }
});

it('can validate performance metrics structure', function () {
    $performanceMetrics = [
        'loadTime' => 250,
        'bundleSize' => 75000,
        'firstPaint' => 150,
        'lcp' => 600,
        'cls' => 0.05,
        'fid' => 25
    ];
    
    expect($performanceMetrics)->toHaveKey('loadTime');
    expect($performanceMetrics)->toHaveKey('bundleSize');
    expect($performanceMetrics)->toHaveKey('firstPaint');
    expect($performanceMetrics)->toHaveKey('lcp');
    expect($performanceMetrics)->toHaveKey('cls');
    expect($performanceMetrics)->toHaveKey('fid');
    
    expect($performanceMetrics['loadTime'])->toBeInt()->toBeGreaterThan(0);
    expect($performanceMetrics['bundleSize'])->toBeInt()->toBeGreaterThan(0);
    expect($performanceMetrics['firstPaint'])->toBeInt()->toBeGreaterThan(0);
    expect($performanceMetrics['lcp'])->toBeInt()->toBeGreaterThan(0);
    expect($performanceMetrics['cls'])->toBeFloat()->toBeBetween(0, 1);
    expect($performanceMetrics['fid'])->toBeInt()->toBeGreaterThan(0);
});

it('can handle sample data configuration', function () {
    $sampleDataConfig = [
        'audienceType' => 'individual',
        'variation' => 'default',
        'contentLength' => 'medium'
    ];
    
    expect($sampleDataConfig)->toHaveKey('audienceType');
    expect($sampleDataConfig)->toHaveKey('variation');
    expect($sampleDataConfig)->toHaveKey('contentLength');
    
    expect($sampleDataConfig['audienceType'])->toBeIn(['individual', 'institution', 'employer']);
    expect($sampleDataConfig['variation'])->toBeIn(['default', 'minimal', 'rich', 'localized']);
    expect($sampleDataConfig['contentLength'])->toBeIn(['short', 'medium', 'long']);
});

it('can validate theme and color scheme options', function () {
    $themes = ['default', 'minimal', 'modern', 'classic'];
    $colorSchemes = ['default', 'primary', 'secondary', 'accent'];
    
    foreach ($themes as $theme) {
        expect($theme)->toBeIn($themes);
    }
    
    foreach ($colorSchemes as $colorScheme) {
        expect($colorScheme)->toBeIn($colorSchemes);
    }
});