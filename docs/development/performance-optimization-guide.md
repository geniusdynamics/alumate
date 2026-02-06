# Performance Optimization Guide

This guide covers performance optimization techniques and best practices for the Alumate Platform, including backend, frontend, and database optimizations.

## Table of Contents

1. [Performance Principles](#performance-principles)
2. [Backend Optimization](#backend-optimization)
3. [Database Optimization](#database-optimization)
4. [Frontend Optimization](#frontend-optimization)
5. [Caching Strategies](#caching-strategies)
6. [Asset Optimization](#asset-optimization)
7. [API Performance](#api-performance)
8. [Monitoring & Profiling](#monitoring--profiling)

## Performance Principles

### Key Metrics

| Metric | Target | Description |
|--------|--------|-------------|
| TTFB | < 200ms | Time to First Byte |
| FCP | < 1.8s | First Contentful Paint |
| LCP | < 2.5s | Largest Contentful Paint |
| TTI | < 3.8s | Time to Interactive |
| CLS | < 0.1 | Cumulative Layout Shift |
| API Response | < 100ms | Average API response time |

### Performance Budget

```json
{
  "budgets": [
    {
      "resourceType": "script",
      "budget": 300
    },
    {
      "resourceType": "stylesheet",
      "budget": 100
    },
    {
      "resourceType": "image",
      "budget": 500
    },
    {
      "resourceType": "total",
      "budget": 1000
    }
  ]
}
```

## Backend Optimization

### Eager Loading

```php
<?php

// ❌ Bad: N+1 query problem
$alumni = Alumni::all();
foreach ($alumni as $alumnus) {
    echo $alumnus->connections->count(); // N additional queries
}

// ✅ Good: Eager loading
$alumni = Alumni::with(['connections', 'experiences', 'educations'])->get();
foreach ($alumni as $alumnus) {
    echo $alumnus->connections->count(); // No additional queries
}

// ✅ Better: Conditional eager loading
$alumni = Alumni::with([
    'connections' => function ($query) {
        $query->where('status', 'accepted')->limit(10);
    },
    'experiences' => function ($query) {
        $query->orderBy('start_date', 'desc')->limit(5);
    },
])->get();

// ✅ Best: Load counts without full relations
$alumni = Alumni::withCount(['connections', 'experiences'])->get();
```

### Query Optimization

```php
<?php

// ❌ Bad: Loading all columns
$users = User::all();

// ✅ Good: Select only needed columns
$users = User::select(['id', 'name', 'email', 'graduation_year'])->get();

// ❌ Bad: Multiple queries
$activeCount = User::where('active', true)->count();
$inactiveCount = User::where('active', false)->count();

// ✅ Good: Single query with grouping
$counts = User::selectRaw('active, COUNT(*) as count')
    ->groupBy('active')
    ->pluck('count', 'active');

// ✅ Good: Use chunk for large datasets
User::chunk(1000, function ($users) {
    foreach ($users as $user) {
        // Process user
    }
});

// ✅ Better: Use lazy collections for memory efficiency
User::lazy()->each(function ($user) {
    // Process user
});

// ✅ Best: Use cursor for streaming
foreach (User::cursor() as $user) {
    // Process user - minimal memory usage
}
```

### Service Layer Optimization

```php
<?php

namespace App\Services;

use App\Models\Alumni;
use Illuminate\Support\Facades\Cache;

class AlumniService
{
    /**
     * Get alumni with caching
     */
    public function getAlumni(array $filters = [], int $perPage = 15)
    {
        $cacheKey = $this->buildCacheKey('alumni_list', $filters, $perPage);
        
        return Cache::tags(['alumni'])->remember($cacheKey, 300, function () use ($filters, $perPage) {
            return Alumni::query()
                ->select(['id', 'name', 'email', 'graduation_year', 'major', 'avatar_url'])
                ->withCount('connections')
                ->when($filters['graduation_year'] ?? null, function ($query, $year) {
                    $query->where('graduation_year', $year);
                })
                ->when($filters['major'] ?? null, function ($query, $major) {
                    $query->where('major', 'ILIKE', "%{$major}%");
                })
                ->orderBy('name')
                ->paginate($perPage);
        });
    }

    /**
     * Invalidate alumni cache
     */
    public function invalidateCache(): void
    {
        Cache::tags(['alumni'])->flush();
    }

    private function buildCacheKey(string $prefix, array $filters, int $perPage): string
    {
        return $prefix . ':' . md5(serialize($filters) . $perPage);
    }
}
```

### Queue Heavy Operations

```php
<?php

// ❌ Bad: Synchronous heavy operation
public function generateReport(Request $request)
{
    $report = $this->reportService->generate($request->all()); // Takes 30+ seconds
    return response()->json($report);
}

// ✅ Good: Queue the operation
public function generateReport(Request $request)
{
    $job = GenerateReportJob::dispatch($request->user(), $request->all());
    
    return response()->json([
        'message' => 'Report generation started',
        'job_id' => $job->id,
    ], 202);
}

// Job class
class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public array $options
    ) {}

    public function handle(ReportService $reportService): void
    {
        $report = $reportService->generate($this->options);
        
        // Notify user when complete
        $this->user->notify(new ReportReadyNotification($report));
    }
}
```

## Database Optimization

### Indexing Strategy

```php
<?php

// Migration for optimized indexes
public function up(): void
{
    Schema::table('alumni', function (Blueprint $table) {
        // Single column indexes for common filters
        $table->index('graduation_year');
        $table->index('major');
        $table->index('active');
        
        // Composite index for common query patterns
        $table->index(['graduation_year', 'major']);
        $table->index(['active', 'graduation_year']);
        
        // Full-text search index
        $table->fullText(['name', 'bio']);
        
        // Partial index for active users only
        $table->index('last_login_at')->where('active', true);
    });
}
```

### Query Analysis

```sql
-- Analyze query performance
EXPLAIN ANALYZE SELECT * FROM alumni 
WHERE graduation_year = 2023 
AND active = true 
ORDER BY name;

-- Check index usage
SELECT 
    schemaname,
    tablename,
    indexname,
    idx_scan,
    idx_tup_read,
    idx_tup_fetch
FROM pg_stat_user_indexes
WHERE schemaname = 'public'
ORDER BY idx_scan DESC;

-- Find missing indexes
SELECT 
    relname,
    seq_scan,
    seq_tup_read,
    idx_scan,
    idx_tup_fetch
FROM pg_stat_user_tables
WHERE seq_scan > idx_scan
ORDER BY seq_tup_read DESC;
```

### Connection Pooling

```php
// config/database.php
'pgsql' => [
    'driver' => 'pgsql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => 'prefer',
    
    // Connection pooling settings
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
],
```

## Frontend Optimization

### Code Splitting

```typescript
// vite.config.ts
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          'vendor': ['vue', 'vue-router', 'pinia'],
          'ui': ['@/Components/ui'],
          'charts': ['chart.js', 'vue-chartjs'],
        },
      },
    },
    chunkSizeWarningLimit: 500,
  },
})
```

### Lazy Loading Components

```vue
<script setup lang="ts">
import { defineAsyncComponent, Suspense } from 'vue'

// Lazy load heavy components
const AnalyticsDashboard = defineAsyncComponent(() => 
  import('@/Components/analytics/AnalyticsDashboard.vue')
)

const ChartWidget = defineAsyncComponent({
  loader: () => import('@/Components/analytics/ChartWidget.vue'),
  loadingComponent: () => import('@/Components/common/ChartSkeleton.vue'),
  delay: 200,
  timeout: 10000,
})
</script>

<template>
  <Suspense>
    <AnalyticsDashboard />
    <template #fallback>
      <LoadingSkeleton />
    </template>
  </Suspense>
</template>
```

### Virtual Scrolling

```vue
<script setup lang="ts">
import { useVirtualList } from '@vueuse/core'

interface Props {
  items: any[]
}

const props = defineProps<Props>()

const { list, containerProps, wrapperProps } = useVirtualList(
  () => props.items,
  {
    itemHeight: 80,
    overscan: 10,
  }
)
</script>

<template>
  <div v-bind="containerProps" class="h-[600px] overflow-auto">
    <div v-bind="wrapperProps">
      <div
        v-for="{ data, index } in list"
        :key="index"
        class="h-20 border-b flex items-center px-4"
      >
        <AlumniListItem :alumni="data" />
      </div>
    </div>
  </div>
</template>
```

### Computed Property Optimization

```vue
<script setup lang="ts">
import { computed, shallowRef, triggerRef } from 'vue'

// Use shallowRef for large objects
const largeDataset = shallowRef<any[]>([])

// Memoized expensive computation
const expensiveResult = computed(() => {
  console.log('Computing expensive result...')
  return largeDataset.value.reduce((acc, item) => {
    return acc + complexCalculation(item)
  }, 0)
})

// Manual trigger when needed
const updateDataset = (newData: any[]) => {
  largeDataset.value = newData
  triggerRef(largeDataset)
}

// Avoid unnecessary reactivity
const staticConfig = Object.freeze({
  maxItems: 100,
  pageSize: 20,
})
</script>
```

### Debouncing and Throttling

```typescript
// composables/useDebounce.ts
import { ref, watch } from 'vue'

export function useDebouncedRef<T>(value: T, delay = 300) {
  const debouncedValue = ref(value) as Ref<T>
  let timeout: ReturnType<typeof setTimeout>

  watch(
    () => value,
    (newValue) => {
      clearTimeout(timeout)
      timeout = setTimeout(() => {
        debouncedValue.value = newValue
      }, delay)
    }
  )

  return debouncedValue
}

// Usage in component
const searchQuery = ref('')
const debouncedQuery = useDebouncedRef(searchQuery, 300)

watch(debouncedQuery, (query) => {
  // Only triggers after 300ms of no input
  fetchResults(query)
})
```

## Caching Strategies

### Multi-Layer Caching

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    /**
     * Get with multi-layer cache
     */
    public function get(string $key, callable $callback, int $ttl = 3600)
    {
        // L1: In-memory cache (request scope)
        static $memoryCache = [];
        if (isset($memoryCache[$key])) {
            return $memoryCache[$key];
        }

        // L2: Redis cache
        $value = Cache::remember($key, $ttl, $callback);
        
        // Store in memory for subsequent calls
        $memoryCache[$key] = $value;
        
        return $value;
    }

    /**
     * Cache with tags for easy invalidation
     */
    public function getTagged(array $tags, string $key, callable $callback, int $ttl = 3600)
    {
        return Cache::tags($tags)->remember($key, $ttl, $callback);
    }

    /**
     * Invalidate by tags
     */
    public function invalidateTags(array $tags): void
    {
        Cache::tags($tags)->flush();
    }
}
```

### Response Caching

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CacheApiResponse
{
    public function handle(Request $request, Closure $next, int $ttl = 60)
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        $cacheKey = 'api:' . md5($request->fullUrl() . $request->user()?->id);

        return Cache::remember($cacheKey, $ttl, function () use ($next, $request) {
            return $next($request);
        });
    }
}
```

### Frontend Caching

```typescript
// services/cache.ts
class CacheService {
  private cache = new Map<string, { data: any; expiry: number }>()

  get<T>(key: string): T | null {
    const item = this.cache.get(key)
    if (!item) return null
    
    if (Date.now() > item.expiry) {
      this.cache.delete(key)
      return null
    }
    
    return item.data as T
  }

  set<T>(key: string, data: T, ttlSeconds = 300): void {
    this.cache.set(key, {
      data,
      expiry: Date.now() + ttlSeconds * 1000,
    })
  }

  invalidate(pattern: string): void {
    const regex = new RegExp(pattern)
    for (const key of this.cache.keys()) {
      if (regex.test(key)) {
        this.cache.delete(key)
      }
    }
  }
}

export const cache = new CacheService()

// Usage
const alumni = cache.get<Alumni[]>('alumni:list')
if (!alumni) {
  const data = await fetchAlumni()
  cache.set('alumni:list', data, 300)
}
```

## Asset Optimization

### Image Optimization

```vue
<template>
  <!-- Lazy loading with placeholder -->
  <img
    :src="alumni.avatar"
    :alt="alumni.name"
    loading="lazy"
    decoding="async"
    class="w-16 h-16 rounded-full"
    @error="handleImageError"
  />
</template>

<script setup lang="ts">
const handleImageError = (e: Event) => {
  const img = e.target as HTMLImageElement
  img.src = '/images/default-avatar.webp'
}
</script>
```

### Vite Asset Configuration

```typescript
// vite.config.ts
import { defineConfig } from 'vite'
import imagemin from 'vite-plugin-imagemin'

export default defineConfig({
  plugins: [
    imagemin({
      gifsicle: { optimizationLevel: 7 },
      optipng: { optimizationLevel: 7 },
      mozjpeg: { quality: 80 },
      pngquant: { quality: [0.8, 0.9] },
      svgo: {
        plugins: [
          { name: 'removeViewBox', active: false },
          { name: 'removeEmptyAttrs', active: false },
        ],
      },
      webp: { quality: 80 },
    }),
  ],
  build: {
    cssCodeSplit: true,
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true,
      },
    },
  },
})
```

## API Performance

### Pagination Best Practices

```php
<?php

// ❌ Bad: Offset pagination for large datasets
$alumni = Alumni::skip(($page - 1) * $perPage)->take($perPage)->get();

// ✅ Good: Cursor pagination for large datasets
$alumni = Alumni::orderBy('id')->cursorPaginate($perPage);

// Response format
return response()->json([
    'data' => AlumniResource::collection($alumni),
    'meta' => [
        'next_cursor' => $alumni->nextCursor()?->encode(),
        'prev_cursor' => $alumni->previousCursor()?->encode(),
        'per_page' => $alumni->perPage(),
    ],
]);
```

### Response Compression

```php
// app/Http/Middleware/CompressResponse.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CompressResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($this->shouldCompress($request, $response)) {
            $content = gzencode($response->getContent(), 9);
            $response->setContent($content);
            $response->headers->set('Content-Encoding', 'gzip');
            $response->headers->set('Content-Length', strlen($content));
        }

        return $response;
    }

    private function shouldCompress($request, $response): bool
    {
        return str_contains($request->header('Accept-Encoding', ''), 'gzip')
            && $response->headers->get('Content-Type') === 'application/json'
            && strlen($response->getContent()) > 1024;
    }
}
```

### Field Selection

```php
<?php

// Allow clients to select fields
public function index(Request $request)
{
    $fields = $request->input('fields', ['id', 'name', 'email']);
    $allowedFields = ['id', 'name', 'email', 'graduation_year', 'major', 'avatar_url'];
    
    $selectedFields = array_intersect($fields, $allowedFields);
    
    $alumni = Alumni::select($selectedFields)->paginate();
    
    return AlumniResource::collection($alumni);
}

// Usage: GET /api/alumni?fields[]=id&fields[]=name&fields[]=email
```

## Monitoring & Profiling

### Performance Monitoring

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PerformanceMonitor
{
    private array $timers = [];

    public function start(string $name): void
    {
        $this->timers[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(),
        ];
    }

    public function end(string $name): array
    {
        if (!isset($this->timers[$name])) {
            return [];
        }

        $timer = $this->timers[$name];
        $duration = (microtime(true) - $timer['start']) * 1000;
        $memoryUsed = (memory_get_usage() - $timer['memory_start']) / 1024 / 1024;

        $metrics = [
            'name' => $name,
            'duration_ms' => round($duration, 2),
            'memory_mb' => round($memoryUsed, 2),
        ];

        // Log slow operations
        if ($duration > 1000) {
            Log::warning('Slow operation detected', $metrics);
        }

        unset($this->timers[$name]);
        
        return $metrics;
    }
}
```

### Query Performance Logging

```php
<?php

// In AppServiceProvider
public function boot(): void
{
    if (config('app.debug')) {
        DB::listen(function ($query) {
            if ($query->time > 100) { // Log queries over 100ms
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ]);
            }
        });
    }
}
```

### Frontend Performance Tracking

```typescript
// utils/performance.ts
export const trackPerformance = () => {
  if (typeof window === 'undefined') return

  // Track Core Web Vitals
  const observer = new PerformanceObserver((list) => {
    for (const entry of list.getEntries()) {
      console.log(`${entry.name}: ${entry.startTime}ms`)
      
      // Send to analytics
      trackMetric(entry.name, entry.startTime)
    }
  })

  observer.observe({ entryTypes: ['largest-contentful-paint', 'first-input', 'layout-shift'] })

  // Track page load
  window.addEventListener('load', () => {
    const timing = performance.timing
    const metrics = {
      dns: timing.domainLookupEnd - timing.domainLookupStart,
      tcp: timing.connectEnd - timing.connectStart,
      ttfb: timing.responseStart - timing.requestStart,
      domLoad: timing.domContentLoadedEventEnd - timing.navigationStart,
      windowLoad: timing.loadEventEnd - timing.navigationStart,
    }
    
    console.table(metrics)
  })
}
```

## Performance Checklist

### Backend
- [ ] Use eager loading for relationships
- [ ] Select only needed columns
- [ ] Add appropriate database indexes
- [ ] Implement caching for expensive queries
- [ ] Queue heavy operations
- [ ] Use cursor pagination for large datasets

### Frontend
- [ ] Implement code splitting
- [ ] Lazy load components
- [ ] Use virtual scrolling for long lists
- [ ] Debounce user input
- [ ] Optimize images
- [ ] Minimize bundle size

### Database
- [ ] Analyze slow queries
- [ ] Add missing indexes
- [ ] Use connection pooling
- [ ] Implement query caching
- [ ] Regular maintenance (VACUUM, ANALYZE)

### API
- [ ] Implement response caching
- [ ] Use compression
- [ ] Support field selection
- [ ] Implement rate limiting
- [ ] Monitor response times

---

**Related Documentation**:
- [Debugging Guide](./debugging-guide.md)
- [API Development Guide](./api-development-guide.md)
- [Analytics Services](./analytics-services.md)

**Last Updated**: February 2026
