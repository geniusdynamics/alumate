<?php

use App\Models\LandingPage;
use App\Models\Template;
use App\Services\PublishingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->publishingService = new PublishingService();
});

test('publishing service can be instantiated', function () {
    expect($this->publishingService)->toBeInstanceOf(PublishingService::class);
});

test('publishing service validates configuration', function () {
    $validConfig = [
        'landing_page_id' => 1,
        'tenant_id' => 1,
    ];

    $result = $this->publishingService->validatePublishingConfig($validConfig);
    expect($result)->toBeTrue();
});

test('publishing service throws exception for invalid configuration', function () {
    $invalidConfig = [
        'tenant_id' => 1,
        // missing landing_page_id
    ];

    expect(fn() => $this->publishingService->validatePublishingConfig($invalidConfig))
        ->toThrow(Exception::class);
});

test('publishing service supports correct output formats', function () {
    expect(PublishingService::OUTPUT_FORMATS)->toContain('html', 'static', 'spa');
});

test('publishing service generates static site successfully', function () {
    // Create a mock landing page
    $landingPage = new LandingPage([
        'id' => 1,
        'name' => 'Test Page',
        'config' => ['title' => 'Test Title'],
        'brand_config' => ['colors' => ['primary' => '#007bff']],
    ]);

    $template = new Template([
        'structure' => [
            'sections' => [
                ['type' => 'hero', 'config' => ['title' => 'Welcome']]
            ]
        ]
    ]);

    $landingPage->template = $template;

    $result = $this->publishingService->generateStaticSite($landingPage);

    expect($result)->toHaveKeys(['html', 'assets', 'manifest', 'build_hash', 'generated_at']);
    expect($result['html'])->toContain('<html>');
    expect($result['html'])->toContain('Welcome');
});

test('publishing service minifies HTML when requested', function () {
    $landingPage = new LandingPage([
        'id' => 1,
        'name' => 'Test Page',
        'config' => ['title' => 'Test Title'],
    ]);

    $result = $this->publishingService->generateStaticSite($landingPage, ['minify' => true]);

    expect($result)->toHaveKey('html');
    expect(strlen($result['html']))->toBeGreaterThan(0);
});

test('publishing service handles empty landing page config', function () {
    $landingPage = new LandingPage([
        'id' => 1,
        'name' => 'Test Page',
        'config' => [],
    ]);

    $result = $this->publishingService->generateStaticSite($landingPage);

    expect($result)->toHaveKey('html');
    expect($result['html'])->toContain('<html>');
});

test('publishing service generates build manifest', function () {
    $landingPage = new LandingPage([
        'id' => 1,
        'name' => 'Test Page',
        'version' => 1,
        'config' => [],
    ]);

    $result = $this->publishingService->generateStaticSite($landingPage);

    expect($result['manifest'])->toHaveKeys([
        'landing_page_id',
        'template_id',
        'version',
        'build_time',
        'html_size',
        'assets_count',
        'assets'
    ]);
});
