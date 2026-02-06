# Debugging Guide

This guide covers debugging techniques, tools, and best practices for troubleshooting issues in the Alumate Platform.

## Table of Contents

1. [Debugging Environment Setup](#debugging-environment-setup)
2. [PHP/Laravel Debugging](#phplaravel-debugging)
3. [Vue.js/TypeScript Debugging](#vuejstypescript-debugging)
4. [Database Debugging](#database-debugging)
5. [API Debugging](#api-debugging)
6. [Performance Debugging](#performance-debugging)
7. [Production Debugging](#production-debugging)
8. [Common Issues](#common-issues)

## Debugging Environment Setup

### Enable Debug Mode

```bash
# .env
APP_DEBUG=true
APP_ENV=local

# Enable detailed error messages
LOG_LEVEL=debug
```

### VS Code Debug Configuration

```json
// .vscode/launch.json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "${workspaceFolder}": "${workspaceFolder}"
      }
    },
    {
      "name": "Launch Chrome",
      "type": "chrome",
      "request": "launch",
      "url": "http://localhost:8080",
      "webRoot": "${workspaceFolder}/resources/js",
      "sourceMaps": true
    },
    {
      "name": "Debug PHPUnit Test",
      "type": "php",
      "request": "launch",
      "program": "${workspaceFolder}/vendor/bin/phpunit",
      "args": ["--filter", "${selectedText}"],
      "cwd": "${workspaceFolder}",
      "port": 9003
    }
  ]
}
```

### Xdebug Configuration

```ini
; php.ini
[xdebug]
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
xdebug.log=/tmp/xdebug.log
xdebug.idekey=VSCODE
```

## PHP/Laravel Debugging

### Using Laravel Debugbar

```bash
# Install debugbar
composer require barryvdh/laravel-debugbar --dev

# Publish configuration
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

```php
// config/debugbar.php
return [
    'enabled' => env('DEBUGBAR_ENABLED', true),
    'collectors' => [
        'phpinfo' => true,
        'messages' => true,
        'time' => true,
        'memory' => true,
        'exceptions' => true,
        'log' => true,
        'db' => true,
        'views' => true,
        'route' => true,
        'auth' => true,
        'gate' => true,
        'session' => true,
        'request' => true,
        'mail' => true,
        'laravel' => true,
        'events' => true,
        'default_request' => true,
        'logs' => true,
        'files' => true,
        'config' => true,
        'cache' => true,
        'models' => true,
        'livewire' => true,
    ],
];
```

### Debug Helpers

```php
<?php

// Dump and die
dd($variable);

// Dump without dying
dump($variable);

// Log to file
\Log::debug('Debug message', ['context' => $data]);
\Log::info('Info message');
\Log::warning('Warning message');
\Log::error('Error message', ['exception' => $e]);

// Log to specific channel
\Log::channel('analytics')->debug('Analytics debug', $data);

// Ray debugging (if installed)
ray($variable)->blue();
ray()->measure();
ray()->showQueries();
```

### Query Debugging

```php
<?php

// Enable query logging
DB::enableQueryLog();

// Your queries here
$users = User::where('active', true)->get();

// Get logged queries
$queries = DB::getQueryLog();
dd($queries);

// Listen to queries in real-time
DB::listen(function ($query) {
    \Log::debug('Query executed', [
        'sql' => $query->sql,
        'bindings' => $query->bindings,
        'time' => $query->time,
    ]);
});

// Debug specific query
$query = User::where('active', true);
dd($query->toSql(), $query->getBindings());
```

### Exception Debugging

```php
<?php

try {
    // Code that might throw
    $result = $this->riskyOperation();
} catch (\Exception $e) {
    // Log full exception details
    \Log::error('Operation failed', [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    // Re-throw or handle
    throw $e;
}
```

### Artisan Debugging Commands

```bash
# Clear all caches
php artisan optimize:clear

# View routes
php artisan route:list
php artisan route:list --name=api

# View configuration
php artisan config:show database
php artisan config:show app

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()

# Check environment
php artisan env

# View scheduled tasks
php artisan schedule:list
```

## Vue.js/TypeScript Debugging

### Vue DevTools

Install Vue DevTools browser extension for:
- Component inspection
- State management debugging
- Event tracking
- Performance profiling

### Console Debugging

```typescript
// Basic logging
console.log('Debug:', variable)
console.info('Info:', data)
console.warn('Warning:', message)
console.error('Error:', error)

// Grouped logging
console.group('User Data')
console.log('Name:', user.name)
console.log('Email:', user.email)
console.groupEnd()

// Table format
console.table(arrayOfObjects)

// Timing
console.time('operation')
// ... operation
console.timeEnd('operation')

// Stack trace
console.trace('Trace point')

// Conditional logging
console.assert(condition, 'Assertion failed')
```

### Debug Composable

```typescript
// composables/useDebug.ts
import { watch, onMounted, onUnmounted } from 'vue'

export function useDebug(name: string, data: Record<string, any>) {
  if (import.meta.env.DEV) {
    onMounted(() => {
      console.log(`[${name}] Mounted`, data)
    })

    onUnmounted(() => {
      console.log(`[${name}] Unmounted`)
    })

    // Watch all reactive data
    Object.entries(data).forEach(([key, value]) => {
      if (typeof value === 'object' && value !== null) {
        watch(
          () => value,
          (newVal, oldVal) => {
            console.log(`[${name}] ${key} changed:`, { old: oldVal, new: newVal })
          },
          { deep: true }
        )
      }
    })
  }
}

// Usage in component
const { alumni, isLoading } = useAlumni()
useDebug('AlumniList', { alumni, isLoading })
```

### Error Boundary Component

```vue
<!-- Components/ErrorBoundary.vue -->
<script setup lang="ts">
import { ref, onErrorCaptured } from 'vue'

const error = ref<Error | null>(null)
const errorInfo = ref<string>('')

onErrorCaptured((err, instance, info) => {
  error.value = err
  errorInfo.value = info
  
  // Log to error tracking service
  console.error('Error captured:', {
    error: err,
    component: instance?.$options?.name,
    info,
  })
  
  // Return false to propagate error
  return false
})

const reset = () => {
  error.value = null
  errorInfo.value = ''
}
</script>

<template>
  <div v-if="error" class="error-boundary">
    <h2>Something went wrong</h2>
    <pre>{{ error.message }}</pre>
    <pre v-if="import.meta.env.DEV">{{ error.stack }}</pre>
    <button @click="reset">Try Again</button>
  </div>
  <slot v-else />
</template>
```

### Network Request Debugging

```typescript
// services/api.ts
import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
})

// Request interceptor
api.interceptors.request.use(
  (config) => {
    if (import.meta.env.DEV) {
      console.log('API Request:', {
        method: config.method,
        url: config.url,
        data: config.data,
        params: config.params,
      })
    }
    return config
  },
  (error) => {
    console.error('Request Error:', error)
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => {
    if (import.meta.env.DEV) {
      console.log('API Response:', {
        status: response.status,
        url: response.config.url,
        data: response.data,
      })
    }
    return response
  },
  (error) => {
    console.error('Response Error:', {
      status: error.response?.status,
      message: error.message,
      data: error.response?.data,
    })
    return Promise.reject(error)
  }
)

export default api
```

## Database Debugging

### Query Analysis

```sql
-- Explain query execution plan
EXPLAIN ANALYZE SELECT * FROM users WHERE graduation_year = 2023;

-- Check table statistics
SELECT * FROM pg_stat_user_tables WHERE relname = 'users';

-- View active queries
SELECT pid, now() - pg_stat_activity.query_start AS duration, query, state
FROM pg_stat_activity
WHERE (now() - pg_stat_activity.query_start) > interval '5 seconds';

-- Check index usage
SELECT schemaname, tablename, indexname, idx_scan, idx_tup_read, idx_tup_fetch
FROM pg_stat_user_indexes
WHERE schemaname = 'public'
ORDER BY idx_scan DESC;
```

### Laravel Query Debugging

```php
<?php

// Debug query with bindings
$query = User::where('graduation_year', 2023)
    ->where('active', true);

// Get raw SQL
$sql = $query->toSql();
$bindings = $query->getBindings();

// Get full query with bindings replaced
$fullSql = vsprintf(str_replace('?', "'%s'", $sql), $bindings);
dd($fullSql);

// Use query builder macro
// In AppServiceProvider
Builder::macro('toRawSql', function () {
    return vsprintf(
        str_replace('?', "'%s'", $this->toSql()),
        $this->getBindings()
    );
});

// Usage
dd(User::where('active', true)->toRawSql());
```

### Migration Debugging

```bash
# Check migration status
php artisan migrate:status

# Rollback last migration
php artisan migrate:rollback

# Rollback specific steps
php artisan migrate:rollback --step=3

# Fresh migration (drops all tables)
php artisan migrate:fresh

# Debug migration SQL
php artisan migrate --pretend
```

## API Debugging

### Request/Response Logging Middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiDebugMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        // Log request
        Log::channel('api')->debug('API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'ip' => $request->ip(),
        ]);

        $response = $next($request);

        // Log response
        $duration = microtime(true) - $startTime;
        
        Log::channel('api')->debug('API Response', [
            'status' => $response->getStatusCode(),
            'duration' => round($duration * 1000, 2) . 'ms',
            'content' => $this->getResponseContent($response),
        ]);

        return $response;
    }

    private function getResponseContent($response): mixed
    {
        $content = $response->getContent();
        $decoded = json_decode($content, true);
        
        return $decoded ?? $content;
    }
}
```

### API Testing with Postman/Insomnia

```bash
# Export API collection
php artisan route:list --json > api-routes.json

# Generate Postman collection
php artisan export:postman
```

### cURL Debugging

```bash
# Verbose request
curl -v -X GET "http://localhost:8080/api/v1/alumni" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# With timing
curl -w "@curl-format.txt" -o /dev/null -s "http://localhost:8080/api/v1/alumni"

# curl-format.txt
#     time_namelookup:  %{time_namelookup}s\n
#        time_connect:  %{time_connect}s\n
#     time_appconnect:  %{time_appconnect}s\n
#    time_pretransfer:  %{time_pretransfer}s\n
#       time_redirect:  %{time_redirect}s\n
#  time_starttransfer:  %{time_starttransfer}s\n
#                     ----------\n
#          time_total:  %{time_total}s\n
```

## Performance Debugging

### PHP Profiling

```php
<?php

// Simple timing
$start = microtime(true);
// ... code to profile
$duration = microtime(true) - $start;
\Log::debug("Operation took: {$duration}s");

// Memory usage
$memoryBefore = memory_get_usage();
// ... code to profile
$memoryAfter = memory_get_usage();
$memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024;
\Log::debug("Memory used: {$memoryUsed}MB");

// Peak memory
$peakMemory = memory_get_peak_usage(true) / 1024 / 1024;
\Log::debug("Peak memory: {$peakMemory}MB");
```

### Laravel Telescope

```bash
# Install Telescope
composer require laravel/telescope --dev

# Publish assets
php artisan telescope:install
php artisan migrate

# Access at /telescope
```

### Frontend Performance

```typescript
// Performance timing
const measurePerformance = (name: string, fn: () => void) => {
  performance.mark(`${name}-start`)
  fn()
  performance.mark(`${name}-end`)
  performance.measure(name, `${name}-start`, `${name}-end`)
  
  const measure = performance.getEntriesByName(name)[0]
  console.log(`${name}: ${measure.duration.toFixed(2)}ms`)
}

// Usage
measurePerformance('renderList', () => {
  // Render operation
})

// Component render tracking
import { onMounted, onUpdated } from 'vue'

let renderCount = 0
onMounted(() => console.log('Mounted'))
onUpdated(() => console.log(`Updated: ${++renderCount}`))
```

## Production Debugging

### Safe Production Debugging

```php
<?php

// Conditional debugging
if (config('app.debug') && request()->has('_debug')) {
    // Debug code
}

// IP-restricted debugging
$allowedIps = ['192.168.1.1', '10.0.0.1'];
if (in_array(request()->ip(), $allowedIps)) {
    // Debug code
}

// Feature flag debugging
if (Feature::active('debug_mode')) {
    // Debug code
}
```

### Log Analysis

```bash
# View recent logs
tail -f storage/logs/laravel.log

# Search for errors
grep -i "error" storage/logs/laravel.log

# Count error types
grep -o "Exception.*:" storage/logs/laravel.log | sort | uniq -c | sort -rn

# View logs with context
grep -A 5 "ERROR" storage/logs/laravel.log
```

### Error Tracking Integration

```php
<?php

// Sentry integration
\Sentry\captureException($exception);

// Custom context
\Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
    $scope->setUser(['id' => auth()->id()]);
    $scope->setTag('tenant', tenant()->id ?? 'none');
    $scope->setContext('request', [
        'url' => request()->fullUrl(),
        'method' => request()->method(),
    ]);
});
```

## Common Issues

### Issue: 500 Internal Server Error

```bash
# Check Laravel logs
tail -100 storage/logs/laravel.log

# Check PHP error log
tail -100 /var/log/php/error.log

# Check permissions
ls -la storage/
chmod -R 775 storage bootstrap/cache

# Clear caches
php artisan optimize:clear
```

### Issue: CORS Errors

```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### Issue: Session/Authentication Problems

```bash
# Clear session
php artisan session:clear

# Regenerate key
php artisan key:generate

# Check session config
php artisan config:show session
```

### Issue: Memory Exhausted

```php
// Increase memory limit temporarily
ini_set('memory_limit', '512M');

// Use chunking for large datasets
User::chunk(1000, function ($users) {
    foreach ($users as $user) {
        // Process
    }
});

// Use lazy collections
User::lazy()->each(function ($user) {
    // Process
});
```

### Issue: Slow Queries

```php
// Add indexes
Schema::table('users', function (Blueprint $table) {
    $table->index('graduation_year');
    $table->index(['active', 'graduation_year']);
});

// Use eager loading
$users = User::with(['profile', 'connections'])->get();

// Select only needed columns
$users = User::select(['id', 'name', 'email'])->get();
```

## Debug Checklist

### Before Debugging
- [ ] Enable debug mode (`APP_DEBUG=true`)
- [ ] Check log files
- [ ] Verify environment configuration
- [ ] Clear all caches

### During Debugging
- [ ] Reproduce the issue consistently
- [ ] Isolate the problem area
- [ ] Add logging/breakpoints
- [ ] Check related components

### After Debugging
- [ ] Remove debug code
- [ ] Disable debug mode in production
- [ ] Document the fix
- [ ] Add tests to prevent regression

---

**Related Documentation**:
- [Development Setup](./development-setup.md)
- [Testing Guide](./testing-guide.md)
- [Troubleshooting Guide](./troubleshooting-guide.md)

**Last Updated**: February 2026
