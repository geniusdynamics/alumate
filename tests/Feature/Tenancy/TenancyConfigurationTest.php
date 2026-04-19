<?php

declare(strict_types=1);

use Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLSchemaManager;

it('uses schema manager as the authoritative pgsql tenancy manager', function () {
    $managers = config('tenancy.database.managers');

    expect($managers)->toBeArray();
    expect($managers)->toHaveKey('pgsql');
    expect($managers['pgsql'])->toBe(PostgreSQLSchemaManager::class);
});
