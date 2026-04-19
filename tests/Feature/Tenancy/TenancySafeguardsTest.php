<?php

declare(strict_types=1);

use App\Services\TenantContextService;
use Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper;
use Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper;

it('defines tenancy safeguards configuration defaults', function () {
    expect(config('tenancy.safeguards'))->toBeArray();
    expect(config('tenancy.safeguards.require_existing_schema'))->toBeBool();
    expect(config('tenancy.safeguards.log_context_failures'))->toBeBool();
});

it('rejects unsafe schema names derived from tenant id', function () {
    $service = app(TenantContextService::class);

    $action = fn () => $service->setCurrentTenant('bad;schema');

    expect($action)->toThrow(Exception::class, 'Invalid schema name');
});

it('keeps queue and cache tenancy isolation bootstrappers enabled', function () {
    $bootstrappers = config('tenancy.bootstrappers');

    expect($bootstrappers)->toContain(CacheTenancyBootstrapper::class);
    expect($bootstrappers)->toContain(QueueTenancyBootstrapper::class);
    expect(config('tenancy.cache.tag_base'))->toBe('tenant');
});
