<?php

use App\Services\ComponentRenderService;

it('can instantiate ComponentRenderService without models', function () {
    $service = new ComponentRenderService;
    expect($service)->toBeInstanceOf(ComponentRenderService::class);
});

it('can generate sample data for hero category', function () {
    $service = new ComponentRenderService;

    // Use reflection to access protected method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateHeroSampleData');
    $method->setAccessible(true);

    $result = $method->invoke($service, []);

    expect($result)->toBeArray();
    expect($result)->toHaveKey('headline');
    expect($result)->toHaveKey('subheading');
});
