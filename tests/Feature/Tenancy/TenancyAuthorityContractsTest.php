<?php

declare(strict_types=1);

use App\Http\Middleware\CrossTenantMiddleware;
use App\Http\Middleware\TenantMiddleware;
use App\Services\TenantContextService;

it('keeps tenant middleware aliases unambiguous', function () {
    $aliases = app('router')->getMiddleware();

    expect($aliases)->toBeArray();
    expect($aliases['tenant'] ?? null)->toBe(TenantMiddleware::class);
    expect($aliases['tenant.cross'] ?? null)->toBe(CrossTenantMiddleware::class);
});

it('exposes required tenant context compatibility methods', function () {
    $service = app(TenantContextService::class);

    expect(method_exists($service, 'getTenantSchema'))->toBeTrue();
    expect(method_exists($service, 'schemaExists'))->toBeTrue();
    expect(method_exists($service, 'withTenant'))->toBeTrue();
    expect(method_exists($service, 'setCurrentTenant'))->toBeTrue();
    expect(method_exists($service, 'clearCurrentTenant'))->toBeTrue();
    expect(method_exists($service, 'withinTenantContext'))->toBeTrue();
});
