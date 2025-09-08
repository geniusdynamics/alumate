<?php
// ABOUTME: Service provider for registering hybrid tenancy system services and middleware
// ABOUTME: Handles dependency injection, configuration loading, and service bootstrapping for multi-tenant architecture

namespace App\Providers;

use App\Http\Middleware\CrossTenantMiddleware;
use App\Services\CrossTenantSyncService;
use App\Services\TenantContextService;
use App\Services\TenantSchemaService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;

class TenancyServiceProvider extends ServiceProvider
{
    /**
     * All of the container singletons that should be registered.
     *
     * @var array
     */
    public $singletons = [
        TenantContextService::class => TenantContextService::class,
        TenantSchemaService::class => TenantSchemaService::class,
        CrossTenantSyncService::class => CrossTenantSyncService::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge tenancy configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/tenancy.php',
            'tenancy'
        );

        // Register core tenancy services
        $this->registerTenancyServices();

        // Register tenant-aware database connections
        $this->registerTenantConnections();

        // Register console commands
        $this->registerConsoleCommands();

        // Register event listeners
        $this->registerEventListeners();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/tenancy.php' => config_path('tenancy.php'),
        ], 'tenancy-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../../database/migrations/' => database_path('migrations'),
        ], 'tenancy-migrations');

        // Register tenant-aware cache stores (moved to boot to ensure cache service is available)
        $this->registerTenantCacheStores();

        // Register middleware
        $this->registerMiddleware();

        // Register route macros
        $this->registerRouteMacros();

        // Register scheduled tasks
        $this->registerScheduledTasks();

        // Setup query logging if enabled
        $this->setupQueryLogging();

        // Setup tenant context resolution
        $this->setupTenantContextResolution();

        // Register blade directives
        $this->registerBladeDirectives();

        // Setup error handling
        $this->setupErrorHandling();
    }

    /**
     * Register core tenancy services.
     *
     * @return void
     */
    protected function registerTenancyServices(): void
    {
        // Register tenant context service
        $this->app->singleton(TenantContextService::class, function ($app) {
            return new TenantContextService();
        });

        // Register tenant schema service
        $this->app->singleton(TenantSchemaService::class, function ($app) {
            return new TenantSchemaService();
        });

        // Register cross-tenant sync service
        $this->app->singleton(CrossTenantSyncService::class, function ($app) {
            return new CrossTenantSyncService();
        });

        // Register tenant manager facade
        $this->app->alias(TenantContextService::class, 'tenant.context');
        $this->app->alias(TenantSchemaService::class, 'tenant.schema');
        $this->app->alias(CrossTenantSyncService::class, 'tenant.sync');
    }

    /**
     * Register tenant-aware database connections.
     *
     * @return void
     */
    protected function registerTenantConnections(): void
    {
        // Extend database manager to support tenant connections
        $this->app->extend('db', function ($db, $app) {
            $db->extend('tenant', function ($config, $name) use ($app) {
                $tenantContext = $app[TenantContextService::class];
                $currentTenant = $tenantContext->getCurrentTenant();
                
                if ($currentTenant) {
                    $config['search_path'] = $tenantContext->getTenantSchema($currentTenant->id);
                }
                
                return $db->connection('pgsql', $config);
            });
            
            return $db;
        });
    }

    /**
     * Register tenant-aware cache stores.
     *
     * @return void
     */
    protected function registerTenantCacheStores(): void
    {
        $this->app['cache']->extend('tenant', function ($app, $config) {
            try {
                $tenantContext = $app->make(TenantContextService::class);
                $currentTenant = $tenantContext->getCurrentTenant();
                
                $prefix = $config['prefix'] ?? 'laravel_cache';
                if ($currentTenant) {
                    $prefix .= ':tenant_' . $currentTenant->id;
                }
                
                $config['prefix'] = $prefix;
            } catch (\Exception $e) {
                // Fallback if tenant context is not available
                $config['prefix'] = $config['prefix'] ?? 'laravel_cache';
            }
            
            return $app['cache']->repository(
                $app['cache']->store($config['store'] ?? 'redis')->getStore()
            );
        });
    }

    /**
     * Register console commands.
     *
     * @return void
     */
    protected function registerConsoleCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\MigrateTenantToSchema::class,
                \App\Console\Commands\RollbackSchemaToHybrid::class,
            ]);
        }
    }

    /**
     * Register event listeners.
     *
     * @return void
     */
    protected function registerEventListeners(): void
    {
        // Listen for tenant context changes
        $this->app['events']->listen(
            'tenant.context.changed',
            function ($tenant) {
                // Clear tenant-specific caches
                app('cache')->tags(['tenant:' . $tenant->id])->flush();
                
                // Log tenant context change
                Log::info('Tenant context changed', [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'timestamp' => now(),
                ]);
            }
        );

        // Listen for schema operations
        $this->app['events']->listen(
            'tenant.schema.created',
            function ($tenant, $schema) {
                Log::info('Tenant schema created', [
                    'tenant_id' => $tenant->id,
                    'schema_name' => $schema,
                    'timestamp' => now(),
                ]);
            }
        );

        // Listen for sync operations
        $this->app['events']->listen(
            'tenant.sync.completed',
            function ($operation, $results) {
                Log::info('Cross-tenant sync completed', [
                    'operation' => $operation,
                    'results' => $results,
                    'timestamp' => now(),
                ]);
            }
        );
    }

    /**
     * Register middleware.
     *
     * @return void
     */
    protected function registerMiddleware(): void
    {
        // Register cross-tenant middleware
        $this->app['router']->aliasMiddleware('tenant', CrossTenantMiddleware::class);
        
        // Add to global middleware if configured
        if (config('tenancy.middleware.global', false)) {
            $this->app['router']->pushMiddlewareToGroup('web', CrossTenantMiddleware::class);
            $this->app['router']->pushMiddlewareToGroup('api', CrossTenantMiddleware::class);
        }
    }

    /**
     * Register route macros.
     *
     * @return void
     */
    protected function registerRouteMacros(): void
    {
        // Tenant-aware route macro
        Route::macro('tenant', function ($callback) {
            Route::group([
                'middleware' => ['tenant'],
                'prefix' => '{tenant?}',
                'where' => ['tenant' => '[a-zA-Z0-9-_]+'],
            ], $callback);
        });

        // Global route macro (bypasses tenant context)
        Route::macro('global', function ($callback) {
            Route::group([
                'middleware' => ['tenant:global'],
            ], $callback);
        });

        // Super admin route macro
        Route::macro('superAdmin', function ($callback) {
            Route::group([
                'middleware' => ['tenant:super_admin', 'auth:super_admin'],
                'prefix' => 'super-admin',
            ], $callback);
        });
    }

    /**
     * Register scheduled tasks.
     *
     * @return void
     */
    protected function registerScheduledTasks(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            
            // Sync global data every hour
            if (config('tenancy.global.sync.enabled')) {
                $schedule->call(function () {
                    $syncService = app(CrossTenantSyncService::class);
                    $syncService->syncGlobalData();
                })->hourly()->name('sync-global-data');
            }
            
            // Clean up old audit logs daily
            if (config('tenancy.audit.enabled')) {
                $schedule->call(function () {
                    $retentionDays = config('tenancy.audit.retention.days', 365);
                    DB::table('audit_trail')
                        ->where('created_at', '<', now()->subDays($retentionDays))
                        ->delete();
                })->daily()->name('cleanup-audit-logs');
            }
            
            // Clean up old sync logs weekly
            $schedule->call(function () {
                DB::table('data_sync_logs')
                    ->where('created_at', '<', now()->subDays(30))
                    ->delete();
            })->weekly()->name('cleanup-sync-logs');
            
            // Generate analytics data daily
            if (config('tenancy.features.optional.tenant_analytics')) {
                $schedule->call(function () {
                    $syncService = app(CrossTenantSyncService::class);
                    $syncService->generateAnalytics();
                })->daily()->name('generate-analytics');
            }
        });
    }

    /**
     * Setup query logging if enabled.
     *
     * @return void
     */
    protected function setupQueryLogging(): void
    {
        if (config('tenancy.development.log_all_queries')) {
            DB::listen(function (QueryExecuted $query) {
                $tenantContext = app(TenantContextService::class);
                $currentTenant = $tenantContext->getCurrentTenant();
                
                Log::debug('Database Query', [
                    'tenant_id' => $currentTenant?->id,
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'connection' => $query->connectionName,
                ]);
            });
        }
        
        // Log slow queries
        if (config('tenancy.performance.monitoring.log_slow_operations')) {
            DB::listen(function (QueryExecuted $query) {
                $threshold = config('tenancy.performance.monitoring.slow_operation_threshold', 1000);
                
                if ($query->time > $threshold) {
                    $tenantContext = app(TenantContextService::class);
                    $currentTenant = $tenantContext->getCurrentTenant();
                    
                    Log::warning('Slow Database Query', [
                        'tenant_id' => $currentTenant?->id,
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                        'connection' => $query->connectionName,
                        'threshold' => $threshold,
                    ]);
                }
            });
        }
    }

    /**
     * Setup tenant context resolution.
     *
     * @return void
     */
    protected function setupTenantContextResolution(): void
    {
        // Resolve tenant context early in the request lifecycle
        $this->app->resolving(Request::class, function (Request $request) {
            if (!$this->app->runningInConsole()) {
                $tenantContext = app(TenantContextService::class);
                $tenantContext->resolveFromRequest($request);
            }
        });
    }

    /**
     * Register blade directives.
     *
     * @return void
     */
    protected function registerBladeDirectives(): void
    {
        // @tenant directive
        \Blade::directive('tenant', function ($expression) {
            return "<?php if(app('tenant.context')->getCurrentTenant()): ?>";
        });
        
        \Blade::directive('endtenant', function () {
            return "<?php endif; ?>";
        });
        
        // @tenantId directive
        \Blade::directive('tenantId', function () {
            return "<?php echo app('tenant.context')->getCurrentTenant()?->id; ?>";
        });
        
        // @tenantName directive
        \Blade::directive('tenantName', function () {
            return "<?php echo app('tenant.context')->getCurrentTenant()?->name; ?>";
        });
        
        // @global directive (for global context)
        \Blade::directive('global', function () {
            return "<?php if(!app('tenant.context')->getCurrentTenant()): ?>";
        });
        
        \Blade::directive('endglobal', function () {
            return "<?php endif; ?>";
        });
        
        // @superAdmin directive
        \Blade::directive('superAdmin', function () {
            return "<?php if(auth()->check() && auth()->user()->hasRole('super_admin')): ?>";
        });
        
        \Blade::directive('endSuperAdmin', function () {
            return "<?php endif; ?>";
        });
    }

    /**
     * Setup error handling.
     *
     * @return void
     */
    protected function setupErrorHandling(): void
    {
        // Register custom exception handler for tenant-related errors
        $this->app->bind(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            function ($app) {
                $handler = $app->make(\App\Exceptions\Handler::class);
                
                // Extend handler to include tenant context in error reports
                $originalReport = $handler->report(...);
                $handler->reportable(function (\Throwable $e) use ($originalReport) {
                    $tenantContext = app(TenantContextService::class);
                    $currentTenant = $tenantContext->getCurrentTenant();
                    
                    if ($currentTenant) {
                        Log::error('Tenant Error', [
                            'tenant_id' => $currentTenant->id,
                            'tenant_name' => $currentTenant->name,
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                    
                    return $originalReport($e);
                });
                
                return $handler;
            }
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            TenantContextService::class,
            TenantSchemaService::class,
            CrossTenantSyncService::class,
            'tenant.context',
            'tenant.schema',
            'tenant.sync',
        ];
    }
}