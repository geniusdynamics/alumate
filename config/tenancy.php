<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Domain;
use App\Models\Tenant;
use Stancl\Tenancy\Jobs\CreateDatabase;
use Stancl\Tenancy\Jobs\DeleteDatabase;
use Stancl\Tenancy\Jobs\MigrateDatabase;
use Stancl\Tenancy\Jobs\SeedDatabase;

return [
    'tenant_model' => Tenant::class,
    'domain_model' => Domain::class,
    'central_domains' => [
        'localhost',
    ],
    'bootstrappers' => [
        Stancl\Tenancy\TenancyBootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\TenancyBootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\TenancyBootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\TenancyBootstrappers\QueueTenancyBootstrapper::class,
        Stancl\Tenancy\TenancyBootstrappers\RedisTenancyBootstrapper::class, // Note: phpredis is required
    ],
    'database' => [
        'central_connection' => env('DB_CONNECTION', 'central'),
        'template_tenant_connection' => null,
        'prefix' => 'tenant',
        'suffix' => '',
        'managers' => [
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLSchemaManager::class,
        ],
    ],
    'cache' => [
        'tag_base' => 'tenant',
    ],
    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
            // 's3',
        ],
        'root_override' => [
            // Disks whose roots should be overriden after storage_path() is suffixed.
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
    ],
    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [
            // 'default',
        ],
    ],
    'features' => [
        // Stancl\Tenancy\Features\UserImpersonation::class,
        // Stancl\Tenancy\Features\TelescopeTags::class,
        // Stancl\Tenancy\Features\TenantConfig::class,
        // Stancl\Tenancy\Features\CrossDomainRedirect::class,
        // Stancl\Tenancy\Features\ViteBundler::class,
    ],
    'migration_parameters' => [
        '--path' => [
            database_path('migrations/tenant'),
        ],
        '--realpath' => true,
    ],
    'seeder_parameters' => [
        '--class' => 'DatabaseSeeder', // Tenant seeder class
        '--force' => true,
    ],
    'jobs' => [
        'create_database' => CreateDatabase::class,
        'delete_database' => DeleteDatabase::class,
        'migrate_database' => MigrateDatabase::class,
        'seed_database' => SeedDatabase::class,
    ],
];
