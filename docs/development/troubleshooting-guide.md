# Developer Troubleshooting Guide

This guide provides solutions to common development issues encountered when working with the Alumate Platform.

## Table of Contents

1. [Environment Issues](#environment-issues)
2. [Database Issues](#database-issues)
3. [Authentication Issues](#authentication-issues)
4. [Frontend Issues](#frontend-issues)
5. [API Issues](#api-issues)
6. [Build Issues](#build-issues)
7. [Testing Issues](#testing-issues)
8. [Performance Issues](#performance-issues)
9. [Multi-Tenant Issues](#multi-tenant-issues)
10. [Analytics Issues](#analytics-issues)

## Environment Issues

### PHP Version Mismatch

**Symptoms:**
- Syntax errors on valid code
- Missing function errors
- Composer dependency conflicts

**Solution:**
```bash
# Check PHP version
php -v

# Ensure PHP 8.3+ is installed
# Windows: Update PATH to point to correct PHP
set PATH=D:\DevCenter\xampp\php-8.3.23;%PATH%

# Verify extensions
php -m | findstr /i "pgsql redis mbstring"

# If extensions missing, enable in php.ini
extension=pgsql
extension=redis
extension=mbstring
```

### Composer Memory Exhausted

**Symptoms:**
```
PHP Fatal error: Allowed memory size of X bytes exhausted
```

**Solution:**
```bash
# Increase memory limit for Composer
COMPOSER_MEMORY_LIMIT=-1 composer install

# Or use PHP directly
php -d memory_limit=-1 composer.phar install

# Windows
set COMPOSER_MEMORY_LIMIT=-1
composer install
```

### Node.js/npm Issues

**Symptoms:**
- Module not found errors
- Version conflicts
- Build failures

**Solution:**
```bash
# Clear npm cache
npm cache clean --force

# Remove node_modules and reinstall
rmdir /s /q node_modules
del package-lock.json
npm install

# If peer dependency issues
npm install --legacy-peer-deps

# Check Node version
node -v  # Should be 18+
```

### Environment Variables Not Loading

**Symptoms:**
- `env()` returns null
- Configuration values missing

**Solution:**
```bash
# Clear config cache
php artisan config:clear

# Regenerate cache
php artisan config:cache

# Check .env file exists and is readable
type .env

# Verify key is set
php artisan key:generate

# Debug specific value
php artisan tinker
>>> env('APP_KEY')
>>> config('app.key')
```

## Database Issues

### Connection Refused

**Symptoms:**
```
SQLSTATE[08006] [7] could not connect to server: Connection refused
```

**Solution:**
```bash
# Check PostgreSQL is running
# Windows
net start postgresql-x64-17

# Verify connection settings in .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=alumate_db
DB_USERNAME=alumate_user
DB_PASSWORD=your_password

# Test connection
psql -h 127.0.0.1 -p 5432 -U alumate_user -d alumate_db

# Check pg_hba.conf for authentication settings
```

### Migration Failures

**Symptoms:**
- Migration errors
- Table already exists
- Column not found

**Solution:**
```bash
# Check migration status
php artisan migrate:status

# Rollback failed migration
php artisan migrate:rollback

# Fresh migration (development only!)
php artisan migrate:fresh

# Run specific migration
php artisan migrate --path=database/migrations/2024_01_01_000000_create_users_table.php

# Debug migration SQL
php artisan migrate --pretend
```

### N+1 Query Problems

**Symptoms:**
- Slow page loads
- Many similar queries in debugbar
- High database load

**Solution:**
```php
// Enable query logging to identify N+1
DB::enableQueryLog();
// ... your code
dd(DB::getQueryLog());

// Fix with eager loading
// ❌ Bad
$alumni = Alumni::all();
foreach ($alumni as $a) {
    echo $a->connections->count(); // N+1!
}

// ✅ Good
$alumni = Alumni::with('connections')->get();
foreach ($alumni as $a) {
    echo $a->connections->count(); // No additional queries
}
```

### Deadlocks

**Symptoms:**
```
SQLSTATE[40P01]: Deadlock detected
```

**Solution:**
```php
// Use database transactions with retry
DB::transaction(function () {
    // Your queries
}, 3); // Retry 3 times

// Or handle manually
try {
    DB::beginTransaction();
    // Queries
    DB::commit();
} catch (\Illuminate\Database\QueryException $e) {
    DB::rollBack();
    if ($e->getCode() === '40P01') {
        // Retry logic
    }
    throw $e;
}
```

## Authentication Issues

### Token Invalid/Expired

**Symptoms:**
- 401 Unauthorized responses
- "Unauthenticated" errors

**Solution:**
```bash
# Clear token cache
php artisan cache:clear

# Regenerate tokens
php artisan sanctum:prune-expired

# Check token in request
# Header should be: Authorization: Bearer YOUR_TOKEN

# Debug in controller
public function test(Request $request)
{
    dd($request->user()); // Should show user
}
```

### Session Issues

**Symptoms:**
- Logged out unexpectedly
- Session data lost
- CSRF token mismatch

**Solution:**
```bash
# Clear sessions
php artisan session:clear

# Check session configuration
php artisan config:show session

# Verify Redis connection (if using Redis sessions)
php artisan tinker
>>> Redis::ping()

# Check cookie settings in .env
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=false  # true in production
```

### CSRF Token Mismatch

**Symptoms:**
```
419 Page Expired
CSRF token mismatch
```

**Solution:**
```php
// Ensure CSRF token in forms
<form method="POST">
    @csrf
    <!-- form fields -->
</form>

// For AJAX requests
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

// Exclude routes from CSRF (if needed)
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    'api/*',
    'webhooks/*',
];
```

## Frontend Issues

### Vite Build Errors

**Symptoms:**
- Build fails
- Module not found
- TypeScript errors

**Solution:**
```bash
# Clear Vite cache
rmdir /s /q node_modules\.vite

# Rebuild
npm run build

# Check for TypeScript errors
npx tsc --noEmit

# Run with verbose output
npm run dev -- --debug
```

### Hot Module Replacement Not Working

**Symptoms:**
- Changes not reflecting
- Full page reload instead of HMR

**Solution:**
```bash
# Check Vite server is running
npm run dev

# Verify vite.config.ts
export default defineConfig({
  server: {
    host: '127.0.0.1',
    port: 5173,
    hmr: {
      host: '127.0.0.1',
    },
  },
})

# Check browser console for WebSocket errors
# May need to allow in firewall
```

### Component Not Rendering

**Symptoms:**
- Blank page
- Component missing
- Vue warnings in console

**Solution:**
```vue
<!-- Check component registration -->
<script setup lang="ts">
// Ensure component is imported
import MyComponent from '@/Components/MyComponent.vue'
</script>

<!-- Check for Vue errors in console -->
<!-- Common issues: -->
<!-- - Missing required props -->
<!-- - Invalid prop types -->
<!-- - Template syntax errors -->

<!-- Debug with Vue DevTools -->
<!-- Install browser extension -->
```

### TypeScript Errors

**Symptoms:**
- Type errors in IDE
- Build failures
- Missing type definitions

**Solution:**
```bash
# Regenerate type definitions
npm run build

# Check tsconfig.json paths
{
  "compilerOptions": {
    "paths": {
      "@/*": ["./resources/js/*"]
    }
  }
}

# Install missing types
npm install -D @types/node

# Ignore specific errors (temporary)
// @ts-ignore
// @ts-expect-error
```

## API Issues

### CORS Errors

**Symptoms:**
```
Access to XMLHttpRequest blocked by CORS policy
```

**Solution:**
```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000', 'http://localhost:5173'],
    'allowed_headers' => ['*'],
    'supports_credentials' => true,
];

// Clear config cache
php artisan config:clear
```

### Rate Limiting

**Symptoms:**
```
429 Too Many Requests
```

**Solution:**
```php
// Check rate limit configuration
// app/Providers/RouteServiceProvider.php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Increase limit for development
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(1000)->by($request->ip());
});

// Or disable temporarily
Route::middleware(['api'])->group(function () {
    // Routes without throttle
});
```

### Response Format Issues

**Symptoms:**
- Unexpected response format
- Missing data in response
- Serialization errors

**Solution:**
```php
// Ensure consistent response format
return response()->json([
    'success' => true,
    'data' => $data,
    'message' => 'Success',
]);

// Check API Resource
class AlumniResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // Ensure all fields are included
        ];
    }
}

// Debug response
dd($response->getContent());
```

## Build Issues

### Composer Autoload Issues

**Symptoms:**
- Class not found errors
- Namespace errors

**Solution:**
```bash
# Regenerate autoload
composer dump-autoload

# Clear all caches
php artisan optimize:clear

# If class was renamed/moved
composer dump-autoload -o
```

### Asset Compilation Failures

**Symptoms:**
- CSS/JS not loading
- Build errors
- Missing assets

**Solution:**
```bash
# Clean build
rmdir /s /q public\build
npm run build

# Check for errors
npm run build 2>&1

# Verify manifest
type public\build\manifest.json
```

## Testing Issues

### Tests Not Running

**Symptoms:**
- PHPUnit errors
- Test database issues
- Configuration problems

**Solution:**
```bash
# Check PHPUnit configuration
php artisan test --configuration

# Use testing database
# .env.testing
DB_DATABASE=alumate_testing_db

# Run with specific configuration
php artisan test --env=testing

# Clear test cache
php artisan config:clear --env=testing
```

### Test Database Issues

**Symptoms:**
- Tests affecting production data
- Database state not resetting

**Solution:**
```php
// Use RefreshDatabase trait
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_example()
    {
        // Database is fresh for each test
    }
}

// Or use DatabaseTransactions for speed
use Illuminate\Foundation\Testing\DatabaseTransactions;
```

### Mock/Stub Issues

**Symptoms:**
- Mocks not working
- Real services being called

**Solution:**
```php
// Ensure proper mocking
public function test_with_mock()
{
    $mock = $this->mock(ExternalService::class);
    $mock->shouldReceive('call')
         ->once()
         ->andReturn(['data' => 'mocked']);
    
    // Test code that uses ExternalService
}

// For facades
Mail::fake();
// ... code that sends mail
Mail::assertSent(WelcomeEmail::class);
```

## Performance Issues

### Slow Page Loads

**Symptoms:**
- Long response times
- High server load
- Timeout errors

**Solution:**
```bash
# Enable query logging
DB::enableQueryLog();

# Check for N+1 queries
# Use Laravel Debugbar

# Profile with Telescope
php artisan telescope:install
php artisan migrate

# Check slow queries
tail -f storage/logs/laravel.log | grep "slow"
```

### Memory Issues

**Symptoms:**
```
Allowed memory size exhausted
```

**Solution:**
```php
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

// Increase memory limit (temporary)
ini_set('memory_limit', '512M');
```

## Multi-Tenant Issues

### Tenant Not Identified

**Symptoms:**
- Wrong tenant data
- Tenant context missing
- Cross-tenant data leakage

**Solution:**
```php
// Check tenant middleware
// Ensure TenantMiddleware is applied

// Debug current tenant
dd(tenant());

// Manually set tenant for testing
tenancy()->initialize($tenant);

// Check tenant identification
// Domain-based
$tenant = Tenant::where('domain', request()->getHost())->first();
```

### Tenant Migration Issues

**Symptoms:**
- Tenant tables missing
- Migration errors for tenants

**Solution:**
```bash
# Run tenant migrations
php artisan tenants:migrate

# Run for specific tenant
php artisan tenants:migrate --tenants=tenant-id

# Rollback tenant migrations
php artisan tenants:migrate:rollback

# Fresh tenant migrations
php artisan tenants:migrate:fresh
```

## Analytics Issues

### Events Not Tracking

**Symptoms:**
- Analytics data missing
- Events not recorded
- Dashboard empty

**Solution:**
```php
// Check consent status
$consentService = app(ConsentService::class);
if (!$consentService->hasConsent(auth()->id(), 'analytics')) {
    // User hasn't consented
}

// Debug event tracking
\Log::debug('Tracking event', [
    'event' => $eventName,
    'properties' => $properties,
]);

// Check queue processing
php artisan queue:work --verbose

// Verify analytics configuration
php artisan config:show analytics
```

### Analytics Performance Issues

**Symptoms:**
- Slow dashboard loading
- Query timeouts
- High database load

**Solution:**
```php
// Use caching for analytics queries
$metrics = Cache::remember('analytics:dashboard', 300, function () {
    return $this->calculateMetrics();
});

// Aggregate data in background
// Schedule aggregation job
$schedule->job(new AggregateAnalyticsJob)->hourly();

// Use materialized views for complex queries
```

## Quick Reference

### Common Commands

```bash
# Clear all caches
php artisan optimize:clear

# Regenerate everything
composer dump-autoload
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Check application health
php artisan about

# Debug routes
php artisan route:list --name=api

# Check logs
tail -f storage/logs/laravel.log
```

### Debug Checklist

1. [ ] Check error logs (`storage/logs/laravel.log`)
2. [ ] Verify environment configuration (`.env`)
3. [ ] Clear all caches
4. [ ] Check database connection
5. [ ] Verify file permissions
6. [ ] Check service status (PostgreSQL, Redis)
7. [ ] Review recent code changes
8. [ ] Test in isolation

---

**Related Documentation**:
- [Debugging Guide](./debugging-guide.md)
- [Development Setup](./development-setup.md)
- [Testing Guide](./testing-guide.md)

**Last Updated**: February 2026
