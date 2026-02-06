# Performance Optimization Guide

## Overview

This guide covers comprehensive performance optimization strategies for the Component Library System, including frontend optimization, backend performance, database tuning, caching strategies, and monitoring best practices.

## Frontend Performance Optimization

### Asset Optimization

#### Image Optimization

```javascript
// resources/js/utils/imageOptimization.js

class ImageOptimizer {
    constructor() {
        this.supportedFormats = this.detectSupportedFormats();
        this.lazyLoadObserver = this.createLazyLoadObserver();
    }
    
    detectSupportedFormats() {
        const formats = {
            webp: false,
            avif: false
        };
        
        // Check WebP support
        const webpCanvas = document.createElement('canvas');
        webpCanvas.width = 1;
        webpCanvas.height = 1;
        formats.webp = webpCanvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
        
        // Check AVIF support
        const avifImage = new Image();
        avifImage.src = 'data:image/avif;base64,AAAAIGZ0eXBhdmlmAAAAAGF2aWZtaWYxbWlhZk1BMUIAAADybWV0YQAAAAAAAAAoaGRscgAAAAAAAAAAcGljdAAAAAAAAAAAAAAAAGxpYmF2aWYAAAAADnBpdG0AAAAAAAEAAAAeaWxvYwAAAABEAAABAAEAAAABAAABGgAAAB0AAAAoaWluZgAAAAAAAQAAABppbmZlAgAAAAABAABhdjAxQ29sb3IAAAAAamlwcnAAAABLaXBjbwAAABRpc3BlAAAAAAAAAAIAAAACAAAAEHBpeGkAAAAAAwgICAAAAAxhdjFDgQ0MAAAAABNjb2xybmNseAACAAIAAYAAAAAXaXBtYQAAAAAAAAABAAEEAQKDBAAAACVtZGF0EgAKCBgABogQEAwgMg8f8D///8WfhwB8+ErK42A=';
        
        return new Promise((resolve) => {
            avifImage.onload = () => {
                formats.avif = true;
                resolve(formats);
            };
            avifImage.onerror = () => {
                resolve(formats);
            };
        }).then(() => formats);
    }
    
    createLazyLoadObserver() {
        if (!('IntersectionObserver' in window)) {
            return null;
        }
        
        return new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadImage(entry.target);
                    this.lazyLoadObserver.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });
    }
    
    optimizeImage(imgElement, options = {}) {
        const {
            sizes = '(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw',
            loading = 'lazy',
            quality = 85
        } = options;
        
        const baseSrc = imgElement.dataset.src || imgElement.src;
        const baseUrl = baseSrc.replace(/\.[^.]+$/, '');
        const extension = baseSrc.split('.').pop();
        
        // Generate srcset for different sizes
        const widths = [320, 640, 768, 1024, 1280, 1920];
        const srcSet = [];
        const webpSrcSet = [];
        const avifSrcSet = [];
        
        widths.forEach(width => {
            srcSet.push(`${baseUrl}_w${width}.${extension} ${width}w`);
            
            if (this.supportedFormats.webp) {
                webpSrcSet.push(`${baseUrl}_w${width}.webp ${width}w`);
            }
            
            if (this.supportedFormats.avif) {
                avifSrcSet.push(`${baseUrl}_w${width}.avif ${width}w`);
            }
        });
        
        // Create picture element for format selection
        const picture = document.createElement('picture');
        
        // AVIF source (best compression)
        if (avifSrcSet.length > 0) {
            const avifSource = document.createElement('source');
            avifSource.srcset = avifSrcSet.join(', ');
            avifSource.sizes = sizes;
            avifSource.type = 'image/avif';
            picture.appendChild(avifSource);
        }
        
        // WebP source (good compression, wide support)
        if (webpSrcSet.length > 0) {
            const webpSource = document.createElement('source');
            webpSource.srcset = webpSrcSet.join(', ');
            webpSource.sizes = sizes;
            webpSource.type = 'image/webp';
            picture.appendChild(webpSource);
        }
        
        // Fallback img element
        imgElement.srcset = srcSet.join(', ');
        imgElement.sizes = sizes;
        imgElement.loading = loading;
        
        picture.appendChild(imgElement);
        
        return picture;
    }
    
    loadImage(imgElement) {
        if (imgElement.dataset.src) {
            imgElement.src = imgElement.dataset.src;
            imgElement.removeAttribute('data-src');
        }
        
        if (imgElement.dataset.srcset) {
            imgElement.srcset = imgElement.dataset.srcset;
            imgElement.removeAttribute('data-srcset');
        }
        
        imgElement.classList.remove('lazy');
        imgElement.classList.add('loaded');
    }
    
    enableLazyLoading() {
        if (!this.lazyLoadObserver) {
            // Fallback for browsers without IntersectionObserver
            this.fallbackLazyLoad();
            return;
        }
        
        const lazyImages = document.querySelectorAll('img[data-src], img.lazy');
        lazyImages.forEach(img => {
            this.lazyLoadObserver.observe(img);
        });
    }
    
    fallbackLazyLoad() {
        const lazyImages = document.querySelectorAll('img[data-src], img.lazy');
        
        const loadImagesInViewport = () => {
            lazyImages.forEach(img => {
                if (this.isInViewport(img)) {
                    this.loadImage(img);
                }
            });
        };
        
        window.addEventListener('scroll', this.throttle(loadImagesInViewport, 100));
        window.addEventListener('resize', this.throttle(loadImagesInViewport, 100));
        loadImagesInViewport();
    }
    
    isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }
    
    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// Initialize image optimizer
const imageOptimizer = new ImageOptimizer();
document.addEventListener('DOMContentLoaded', () => {
    imageOptimizer.enableLazyLoading();
});

export default ImageOptimizer;
```

#### Code Splitting and Bundle Optimization

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { splitVendorChunkPlugin } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        splitVendorChunkPlugin(),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // Vendor chunks
                    'vue-vendor': ['vue', '@inertiajs/vue3'],
                    'ui-vendor': ['@headlessui/vue', '@heroicons/vue'],
                    
                    // Component chunks
                    'hero-components': [
                        './resources/js/Components/Hero/HeroBase.vue',
                        './resources/js/Components/Hero/HeroIndividual.vue',
                        './resources/js/Components/Hero/HeroInstitution.vue',
                        './resources/js/Components/Hero/HeroEmployer.vue'
                    ],
                    'form-components': [
                        './resources/js/Components/Forms/FormBase.vue',
                        './resources/js/Components/Forms/LeadCaptureForm.vue',
                        './resources/js/Components/Forms/DemoRequestForm.vue'
                    ],
                    'testimonial-components': [
                        './resources/js/Components/Testimonials/TestimonialBase.vue',
                        './resources/js/Components/Testimonials/TestimonialCarousel.vue',
                        './resources/js/Components/Testimonials/TestimonialGrid.vue'
                    ]
                }
            }
        },
        chunkSizeWarningLimit: 1000,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
    },
    optimizeDeps: {
        include: [
            'vue',
            '@inertiajs/vue3',
            '@headlessui/vue',
            '@heroicons/vue/24/outline',
            '@heroicons/vue/24/solid'
        ],
    },
});
```

#### Dynamic Component Loading

```javascript
// resources/js/composables/useComponentLoader.js
import { ref, shallowRef } from 'vue';

export function useComponentLoader() {
    const loadedComponents = new Map();
    const loading = ref(false);
    const error = ref(null);
    
    const loadComponent = async (category, type) => {
        const componentKey = `${category}-${type}`;
        
        if (loadedComponents.has(componentKey)) {
            return loadedComponents.get(componentKey);
        }
        
        loading.value = true;
        error.value = null;
        
        try {
            let component;
            
            switch (category) {
                case 'hero':
                    component = await import(`../Components/Hero/Hero${capitalize(type)}.vue`);
                    break;
                case 'forms':
                    component = await import(`../Components/Forms/${capitalize(type)}Form.vue`);
                    break;
                case 'testimonials':
                    component = await import(`../Components/Testimonials/Testimonial${capitalize(type)}.vue`);
                    break;
                case 'statistics':
                    component = await import(`../Components/Statistics/Statistics${capitalize(type)}.vue`);
                    break;
                case 'ctas':
                    component = await import(`../Components/CTAs/CTA${capitalize(type)}.vue`);
                    break;
                case 'media':
                    component = await import(`../Components/Media/Media${capitalize(type)}.vue`);
                    break;
                default:
                    throw new Error(`Unknown component category: ${category}`);
            }
            
            const loadedComponent = shallowRef(component.default);
            loadedComponents.set(componentKey, loadedComponent);
            
            return loadedComponent;
        } catch (err) {
            error.value = err;
            console.error(`Failed to load component ${category}:${type}`, err);
            throw err;
        } finally {
            loading.value = false;
        }
    };
    
    const preloadComponent = async (category, type) => {
        try {
            await loadComponent(category, type);
        } catch (err) {
            // Silently fail for preloading
            console.warn(`Failed to preload component ${category}:${type}`, err);
        }
    };
    
    const preloadCommonComponents = async () => {
        const commonComponents = [
            ['hero', 'individual'],
            ['forms', 'leadCapture'],
            ['testimonials', 'single'],
            ['ctas', 'button']
        ];
        
        await Promise.allSettled(
            commonComponents.map(([category, type]) => preloadComponent(category, type))
        );
    };
    
    const capitalize = (str) => str.charAt(0).toUpperCase() + str.slice(1);
    
    return {
        loadComponent,
        preloadComponent,
        preloadCommonComponents,
        loading,
        error
    };
}
```

### Performance Monitoring

#### Core Web Vitals Tracking

```javascript
// resources/js/utils/performanceMonitoring.js

class PerformanceMonitor {
    constructor() {
        this.metrics = {};
        this.observers = [];
        this.initializeObservers();
    }
    
    initializeObservers() {
        // Largest Contentful Paint (LCP)
        this.observeLCP();
        
        // First Input Delay (FID)
        this.observeFID();
        
        // Cumulative Layout Shift (CLS)
        this.observeCLS();
        
        // Time to First Byte (TTFB)
        this.observeTTFB();
        
        // Custom metrics
        this.observeCustomMetrics();
    }
    
    observeLCP() {
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const lastEntry = entries[entries.length - 1];
                
                this.metrics.lcp = lastEntry.startTime;
                this.reportMetric('LCP', lastEntry.startTime);
            });
            
            observer.observe({ entryTypes: ['largest-contentful-paint'] });
            this.observers.push(observer);
        }
    }
    
    observeFID() {
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                entries.forEach(entry => {
                    this.metrics.fid = entry.processingStart - entry.startTime;
                    this.reportMetric('FID', this.metrics.fid);
                });
            });
            
            observer.observe({ entryTypes: ['first-input'] });
            this.observers.push(observer);
        }
    }
    
    observeCLS() {
        if ('PerformanceObserver' in window) {
            let clsValue = 0;
            let clsEntries = [];
            
            const observer = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                
                entries.forEach(entry => {
                    if (!entry.hadRecentInput) {
                        clsEntries.push(entry);
                        clsValue += entry.value;
                    }
                });
                
                this.metrics.cls = clsValue;
                this.reportMetric('CLS', clsValue);
            });
            
            observer.observe({ entryTypes: ['layout-shift'] });
            this.observers.push(observer);
        }
    }
    
    observeTTFB() {
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                entries.forEach(entry => {
                    if (entry.name === window.location.href) {
                        this.metrics.ttfb = entry.responseStart - entry.requestStart;
                        this.reportMetric('TTFB', this.metrics.ttfb);
                    }
                });
            });
            
            observer.observe({ entryTypes: ['navigation'] });
            this.observers.push(observer);
        }
    }
    
    observeCustomMetrics() {
        // Component render time
        this.measureComponentRenderTime();
        
        // API response times
        this.measureAPIResponseTimes();
        
        // Resource loading times
        this.measureResourceLoadTimes();
    }
    
    measureComponentRenderTime() {
        const originalMount = Vue.prototype.$mount;
        
        Vue.prototype.$mount = function(el) {
            const startTime = performance.now();
            const result = originalMount.call(this, el);
            const endTime = performance.now();
            
            const renderTime = endTime - startTime;
            this.reportMetric('Component Render Time', renderTime, {
                component: this.$options.name || 'Unknown'
            });
            
            return result;
        };
    }
    
    measureAPIResponseTimes() {
        const originalFetch = window.fetch;
        
        window.fetch = async function(...args) {
            const startTime = performance.now();
            
            try {
                const response = await originalFetch.apply(this, args);
                const endTime = performance.now();
                const responseTime = endTime - startTime;
                
                this.reportMetric('API Response Time', responseTime, {
                    url: args[0],
                    status: response.status,
                    method: args[1]?.method || 'GET'
                });
                
                return response;
            } catch (error) {
                const endTime = performance.now();
                const responseTime = endTime - startTime;
                
                this.reportMetric('API Response Time', responseTime, {
                    url: args[0],
                    status: 'error',
                    error: error.message
                });
                
                throw error;
            }
        }.bind(this);
    }
    
    measureResourceLoadTimes() {
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                
                entries.forEach(entry => {
                    if (entry.initiatorType === 'img') {
                        this.reportMetric('Image Load Time', entry.duration, {
                            url: entry.name,
                            size: entry.transferSize
                        });
                    } else if (entry.initiatorType === 'script') {
                        this.reportMetric('Script Load Time', entry.duration, {
                            url: entry.name,
                            size: entry.transferSize
                        });
                    } else if (entry.initiatorType === 'css') {
                        this.reportMetric('CSS Load Time', entry.duration, {
                            url: entry.name,
                            size: entry.transferSize
                        });
                    }
                });
            });
            
            observer.observe({ entryTypes: ['resource'] });
            this.observers.push(observer);
        }
    }
    
    reportMetric(name, value, metadata = {}) {
        // Send to analytics service
        if (typeof gtag !== 'undefined') {
            gtag('event', 'performance_metric', {
                event_category: 'Performance',
                event_label: name,
                value: Math.round(value),
                custom_map: {
                    metric_name: name,
                    metric_value: value,
                    ...metadata
                }
            });
        }
        
        // Send to custom analytics endpoint
        this.sendToAnalytics(name, value, metadata);
        
        // Log to console in development
        if (process.env.NODE_ENV === 'development') {
            console.log(`Performance Metric: ${name}`, {
                value: `${value.toFixed(2)}ms`,
                ...metadata
            });
        }
    }
    
    async sendToAnalytics(name, value, metadata) {
        try {
            await fetch('/api/v1/analytics/performance', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    metric_name: name,
                    metric_value: value,
                    metadata,
                    url: window.location.href,
                    user_agent: navigator.userAgent,
                    timestamp: Date.now()
                })
            });
        } catch (error) {
            console.warn('Failed to send performance metric:', error);
        }
    }
    
    getMetrics() {
        return { ...this.metrics };
    }
    
    disconnect() {
        this.observers.forEach(observer => observer.disconnect());
        this.observers = [];
    }
}

// Initialize performance monitoring
const performanceMonitor = new PerformanceMonitor();

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    performanceMonitor.disconnect();
});

export default PerformanceMonitor;
```

## Backend Performance Optimization

### Database Optimization

#### Query Optimization

```php
<?php
// app/Services/OptimizedComponentService.php

namespace App\Services;

use App\Models\Component;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OptimizedComponentService
{
    public function getComponentsWithAnalytics(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Component::query()
            ->select([
                'components.*',
                DB::raw('COUNT(component_instances.id) as instance_count'),
                DB::raw('AVG(analytics_summary.conversion_rate) as avg_conversion_rate'),
                DB::raw('SUM(analytics_summary.total_views) as total_views')
            ])
            ->leftJoin('component_instances', 'components.id', '=', 'component_instances.component_id')
            ->leftJoin(
                DB::raw('(
                    SELECT 
                        component_instance_id,
                        COUNT(CASE WHEN event_type = "view" THEN 1 END) as total_views,
                        COUNT(CASE WHEN event_type = "conversion" THEN 1 END) / 
                        NULLIF(COUNT(CASE WHEN event_type = "view" THEN 1 END), 0) as conversion_rate
                    FROM component_analytics 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY component_instance_id
                ) as analytics_summary'),
                'component_instances.id',
                '=',
                'analytics_summary.component_instance_id'
            )
            ->when($filters['category'] ?? null, fn($query, $category) => 
                $query->where('components.category', $category)
            )
            ->when($filters['search'] ?? null, fn($query, $search) => 
                $query->where(function ($q) use ($search) {
                    $q->where('components.name', 'like', "%{$search}%")
                      ->orWhere('components.description', 'like', "%{$search}%");
                })
            )
            ->where('components.is_active', true)
            ->groupBy('components.id')
            ->orderByDesc('total_views')
            ->paginate($filters['per_page'] ?? 15);
    }
    
    public function getPopularComponents(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Component::query()
            ->select([
                'components.*',
                DB::raw('COUNT(component_instances.id) as usage_count')
            ])
            ->join('component_instances', 'components.id', '=', 'component_instances.component_id')
            ->where('components.is_active', true)
            ->groupBy('components.id')
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get();
    }
    
    public function getComponentAnalyticsSummary(int $componentId, int $days = 30): array
    {
        $result = DB::select("
            SELECT 
                DATE(created_at) as date,
                event_type,
                COUNT(*) as count
            FROM component_analytics ca
            JOIN component_instances ci ON ca.component_instance_id = ci.id
            WHERE ci.component_id = ? 
            AND ca.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at), event_type
            ORDER BY date DESC
        ", [$componentId, $days]);
        
        return collect($result)->groupBy('date')->map(function ($dayEvents) {
            return $dayEvents->pluck('count', 'event_type')->toArray();
        })->toArray();
    }
    
    public function bulkUpdateComponents(array $componentIds, array $updates): int
    {
        return Component::whereIn('id', $componentIds)
            ->update(array_merge($updates, [
                'updated_at' => now()
            ]));
    }
}
```

#### Database Connection Optimization

```php
<?php
// config/database.php

return [
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode="STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"',
                PDO::ATTR_TIMEOUT => 30,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ]) : [],
            
            // Connection pool settings
            'pool' => [
                'min_connections' => env('DB_POOL_MIN', 5),
                'max_connections' => env('DB_POOL_MAX', 20),
                'connect_timeout' => env('DB_CONNECT_TIMEOUT', 10),
                'wait_timeout' => env('DB_WAIT_TIMEOUT', 3),
                'heartbeat' => env('DB_HEARTBEAT', 30),
                'max_idle_time' => env('DB_MAX_IDLE_TIME', 60),
            ],
        ],
        
        'mysql_read' => [
            'driver' => 'mysql',
            'read' => [
                'host' => [
                    env('DB_READ_HOST_1', env('DB_HOST', '127.0.0.1')),
                    env('DB_READ_HOST_2', env('DB_HOST', '127.0.0.1')),
                ],
            ],
            'write' => [
                'host' => [
                    env('DB_WRITE_HOST', env('DB_HOST', '127.0.0.1')),
                ],
            ],
            'sticky' => true,
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],
    ],
];
```

### Caching Strategies

#### Multi-Level Caching

```php
<?php
// app/Services/CacheManager.php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheManager
{
    private const L1_TTL = 300;    // 5 minutes - Memory cache
    private const L2_TTL = 3600;   // 1 hour - Redis cache
    private const L3_TTL = 86400;  // 24 hours - Database cache
    
    private array $memoryCache = [];
    
    public function get(string $key, callable $callback = null, int $ttl = null)
    {
        // L1: Memory cache (fastest)
        if (isset($this->memoryCache[$key])) {
            return $this->memoryCache[$key];
        }
        
        // L2: Redis cache (fast)
        $redisValue = Redis::get($key);
        if ($redisValue !== null) {
            $value = unserialize($redisValue);
            $this->memoryCache[$key] = $value;
            return $value;
        }
        
        // L3: Database/callback (slowest)
        if ($callback) {
            $value = $callback();
            $this->set($key, $value, $ttl);
            return $value;
        }
        
        return null;
    }
    
    public function set(string $key, $value, int $ttl = null): void
    {
        $ttl = $ttl ?? self::L2_TTL;
        
        // Set in memory cache
        $this->memoryCache[$key] = $value;
        
        // Set in Redis cache
        Redis::setex($key, $ttl, serialize($value));
        
        // Set in Laravel cache (database/file)
        Cache::put($key, $value, $ttl);
    }
    
    public function forget(string $key): void
    {
        unset($this->memoryCache[$key]);
        Redis::del($key);
        Cache::forget($key);
    }
    
    public function flush(string $pattern = null): void
    {
        if ($pattern) {
            $this->flushPattern($pattern);
        } else {
            $this->memoryCache = [];
            Redis::flushdb();
            Cache::flush();
        }
    }
    
    private function flushPattern(string $pattern): void
    {
        // Clear memory cache
        $this->memoryCache = array_filter(
            $this->memoryCache,
            fn($key) => !fnmatch($pattern, $key),
            ARRAY_FILTER_USE_KEY
        );
        
        // Clear Redis cache
        $keys = Redis::keys($pattern);
        if (!empty($keys)) {
            Redis::del($keys);
        }
        
        // Clear Laravel cache (if using Redis store)
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $cacheKeys = Cache::getStore()->getRedis()->keys(Cache::getPrefix() . $pattern);
            if (!empty($cacheKeys)) {
                Cache::getStore()->getRedis()->del($cacheKeys);
            }
        }
    }
    
    public function remember(string $key, int $ttl, callable $callback)
    {
        return $this->get($key, $callback, $ttl);
    }
    
    public function tags(array $tags): TaggedCacheManager
    {
        return new TaggedCacheManager($this, $tags);
    }
}

class TaggedCacheManager
{
    public function __construct(
        private CacheManager $cacheManager,
        private array $tags
    ) {}
    
    public function get(string $key, callable $callback = null, int $ttl = null)
    {
        $taggedKey = $this->getTaggedKey($key);
        return $this->cacheManager->get($taggedKey, $callback, $ttl);
    }
    
    public function set(string $key, $value, int $ttl = null): void
    {
        $taggedKey = $this->getTaggedKey($key);
        $this->cacheManager->set($taggedKey, $value, $ttl);
        
        // Store tag references
        foreach ($this->tags as $tag) {
            $this->addKeyToTag($tag, $taggedKey);
        }
    }
    
    public function flush(): void
    {
        foreach ($this->tags as $tag) {
            $keys = $this->getKeysForTag($tag);
            foreach ($keys as $key) {
                $this->cacheManager->forget($key);
            }
            $this->clearTag($tag);
        }
    }
    
    private function getTaggedKey(string $key): string
    {
        $tagString = implode(':', $this->tags);
        return "tagged:{$tagString}:{$key}";
    }
    
    private function addKeyToTag(string $tag, string $key): void
    {
        $tagKey = "tag:{$tag}";
        Redis::sadd($tagKey, $key);
        Redis::expire($tagKey, 86400); // Expire tag references after 24 hours
    }
    
    private function getKeysForTag(string $tag): array
    {
        $tagKey = "tag:{$tag}";
        return Redis::smembers($tagKey);
    }
    
    private function clearTag(string $tag): void
    {
        $tagKey = "tag:{$tag}";
        Redis::del($tagKey);
    }
}
```

#### Cache Warming

```php
<?php
// app/Console/Commands/WarmCache.php

namespace App\Console\Commands;

use App\Models\Component;
use App\Services\CacheManager;
use App\Services\ComponentService;
use Illuminate\Console\Command;

class WarmCache extends Command
{
    protected $signature = 'cache:warm {--type=all : Type of cache to warm (all, components, analytics)}';
    protected $description = 'Warm application caches';
    
    public function __construct(
        private CacheManager $cacheManager,
        private ComponentService $componentService
    ) {
        parent::__construct();
    }
    
    public function handle(): void
    {
        $type = $this->option('type');
        
        $this->info('Starting cache warming...');
        
        match ($type) {
            'components' => $this->warmComponentCache(),
            'analytics' => $this->warmAnalyticsCache(),
            'all' => $this->warmAllCaches(),
            default => $this->error('Invalid cache type')
        };
        
        $this->info('Cache warming completed!');
    }
    
    private function warmAllCaches(): void
    {
        $this->warmComponentCache();
        $this->warmAnalyticsCache();
        $this->warmConfigCache();
    }
    
    private function warmComponentCache(): void
    {
        $this->info('Warming component cache...');
        
        $bar = $this->output->createProgressBar();
        
        // Warm popular components
        $popularComponents = Component::select(['id', 'category', 'type'])
            ->join('component_instances', 'components.id', '=', 'component_instances.component_id')
            ->selectRaw('COUNT(component_instances.id) as usage_count')
            ->groupBy('components.id')
            ->orderByDesc('usage_count')
            ->limit(50)
            ->get();
            
        $bar->setMaxSteps($popularComponents->count());
        
        foreach ($popularComponents as $component) {
            // Cache component data
            $this->cacheManager->remember(
                "component_{$component->id}",
                3600,
                fn() => Component::with(['theme', 'instances'])->find($component->id)
            );
            
            // Cache rendered component
            $this->cacheManager->remember(
                "component_render_{$component->id}_default",
                86400,
                fn() => $this->componentService->render($component, $component->config)
            );
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        // Warm component lists by category
        $categories = ['hero', 'forms', 'testimonials', 'statistics', 'ctas', 'media'];
        
        foreach ($categories as $category) {
            $this->cacheManager->remember(
                "components_category_{$category}",
                1800,
                fn() => Component::where('category', $category)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get()
            );
        }
    }
    
    private function warmAnalyticsCache(): void
    {
        $this->info('Warming analytics cache...');
        
        // Warm popular component analytics
        $componentIds = Component::join('component_instances', 'components.id', '=', 'component_instances.component_id')
            ->join('component_analytics', 'component_instances.id', '=', 'component_analytics.component_instance_id')
            ->selectRaw('components.id, COUNT(component_analytics.id) as analytics_count')
            ->groupBy('components.id')
            ->orderByDesc('analytics_count')
            ->limit(20)
            ->pluck('id');
            
        foreach ($componentIds as $componentId) {
            // Cache 30-day analytics
            $this->cacheManager->remember(
                "analytics_component_{$componentId}_30d",
                1800,
                fn() => $this->getComponentAnalytics($componentId, 30)
            );
            
            // Cache 7-day analytics
            $this->cacheManager->remember(
                "analytics_component_{$componentId}_7d",
                900,
                fn() => $this->getComponentAnalytics($componentId, 7)
            );
        }
    }
    
    private function warmConfigCache(): void
    {
        $this->info('Warming configuration cache...');
        
        // Warm tenant configurations
        $tenantIds = \DB::table('tenants')->pluck('id');
        
        foreach ($tenantIds as $tenantId) {
            $this->cacheManager->remember(
                "tenant_config_{$tenantId}",
                3600,
                fn() => \DB::table('tenant_configurations')
                    ->where('tenant_id', $tenantId)
                    ->pluck('value', 'key')
                    ->toArray()
            );
        }
    }
    
    private function getComponentAnalytics(int $componentId, int $days): array
    {
        return \DB::select("
            SELECT 
                DATE(ca.created_at) as date,
                ca.event_type,
                COUNT(*) as count
            FROM component_analytics ca
            JOIN component_instances ci ON ca.component_instance_id = ci.id
            WHERE ci.component_id = ? 
            AND ca.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(ca.created_at), ca.event_type
            ORDER BY date DESC
        ", [$componentId, $days]);
    }
}
```

### Queue Optimization

#### Optimized Queue Processing

```php
<?php
// app/Jobs/OptimizedComponentRenderJob.php

namespace App\Jobs;

use App\Models\Component;
use App\Services\ComponentRenderService;
use App\Services\CacheManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OptimizedComponentRenderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $tries = 3;
    public int $maxExceptions = 2;
    public int $timeout = 120;
    public int $backoff = 60;
    
    public function __construct(
        private int $componentId,
        private array $config,
        private array $variants = ['default', 'mobile', 'preview']
    ) {
        $this->onQueue('render');
    }
    
    public function handle(
        ComponentRenderService $renderService,
        CacheManager $cacheManager
    ): void {
        $component = Component::find($this->componentId);
        
        if (!$component) {
            Log::warning("Component {$this->componentId} not found for rendering");
            return;
        }
        
        $startTime = microtime(true);
        
        try {
            foreach ($this->variants as $variant) {
                $cacheKey = "component_render_{$component->id}_{$variant}_" . md5(serialize($this->config));
                
                $rendered = $renderService->render($component, $this->config, $variant);
                
                $cacheManager->set($cacheKey, $rendered, 86400); // Cache for 24 hours
                
                Log::info("Rendered component {$component->id} variant {$variant}");
            }
            
            $duration = microtime(true) - $startTime;
            
            Log::info("Component rendering completed", [
                'component_id' => $this->componentId,
                'variants' => count($this->variants),
                'duration' => round($duration * 1000, 2) . 'ms'
            ]);
            
        } catch (\Exception $e) {
            Log::error("Component rendering failed", [
                'component_id' => $this->componentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    public function failed(\Throwable $exception): void
    {
        Log::error("Component render job failed permanently", [
            'component_id' => $this->componentId,
            'error' => $exception->getMessage()
        ]);
    }
}
```

#### Queue Monitoring and Scaling

```php
<?php
// app/Console/Commands/MonitorQueues.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Artisan;

class MonitorQueues extends Command
{
    protected $signature = 'queue:monitor';
    protected $description = 'Monitor queue sizes and auto-scale workers';
    
    private array $queueThresholds = [
        'default' => ['min' => 2, 'max' => 10, 'threshold' => 100],
        'render' => ['min' => 1, 'max' => 5, 'threshold' => 50],
        'analytics' => ['min' => 1, 'max' => 3, 'threshold' => 200],
        'notifications' => ['min' => 1, 'max' => 4, 'threshold' => 75],
    ];
    
    public function handle(): void
    {
        foreach ($this->queueThresholds as $queue => $config) {
            $queueSize = Redis::llen("queues:{$queue}");
            $currentWorkers = $this->getCurrentWorkerCount($queue);
            
            $targetWorkers = $this->calculateTargetWorkers($queueSize, $config);
            
            if ($targetWorkers > $currentWorkers) {
                $this->scaleUp($queue, $targetWorkers - $currentWorkers);
            } elseif ($targetWorkers < $currentWorkers) {
                $this->scaleDown($queue, $currentWorkers - $targetWorkers);
            }
            
            $this->info("Queue {$queue}: Size={$queueSize}, Workers={$currentWorkers}->{$targetWorkers}");
        }
    }
    
    private function calculateTargetWorkers(int $queueSize, array $config): int
    {
        if ($queueSize > $config['threshold']) {
            return $config['max'];
        }
        
        if ($queueSize > $config['threshold'] / 2) {
            return min($config['max'], $config['min'] + 2);
        }
        
        if ($queueSize > 10) {
            return $config['min'] + 1;
        }
        
        return $config['min'];
    }
    
    private function getCurrentWorkerCount(string $queue): int
    {
        $output = shell_exec("supervisorctl status | grep 'queue-{$queue}' | grep RUNNING | wc -l");
        return (int) trim($output);
    }
    
    private function scaleUp(string $queue, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            shell_exec("supervisorctl start queue-{$queue}:*");
        }
        
        $this->info("Scaled up {$queue} queue by {$count} workers");
    }
    
    private function scaleDown(string $queue, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            shell_exec("supervisorctl stop queue-{$queue}:*");
        }
        
        $this->info("Scaled down {$queue} queue by {$count} workers");
    }
}
```

## Performance Testing

### Load Testing

```php
<?php
// tests/Performance/ComponentLoadTest.php

namespace Tests\Performance;

use App\Models\Component;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentLoadTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_component_list_performance(): void
    {
        // Create test data
        $user = User::factory()->create();
        Component::factory()->count(100)->create(['tenant_id' => $user->tenant_id]);
        
        $startTime = microtime(true);
        
        // Perform 100 requests
        for ($i = 0; $i < 100; $i++) {
            $response = $this->actingAs($user)
                ->getJson('/api/v1/components?per_page=15');
                
            $response->assertOk();
        }
        
        $duration = microtime(true) - $startTime;
        $avgResponseTime = ($duration / 100) * 1000; // Convert to milliseconds
        
        $this->assertLessThan(200, $avgResponseTime, 'Average response time should be under 200ms');
        
        $this->info("Average response time: {$avgResponseTime}ms");
    }
    
    public function test_component_render_performance(): void
    {
        $user = User::factory()->create();
        $component = Component::factory()->create(['tenant_id' => $user->tenant_id]);
        
        $startTime = microtime(true);
        
        // Render component 50 times
        for ($i = 0; $i < 50; $i++) {
            $response = $this->actingAs($user)
                ->postJson("/api/v1/components/{$component->id}/render", [
                    'config' => $component->config,
                    'variant' => 'default'
                ]);
                
            $response->assertOk();
        }
        
        $duration = microtime(true) - $startTime;
        $avgRenderTime = ($duration / 50) * 1000;
        
        $this->assertLessThan(500, $avgRenderTime, 'Average render time should be under 500ms');
        
        $this->info("Average render time: {$avgRenderTime}ms");
    }
    
    public function test_concurrent_component_access(): void
    {
        $user = User::factory()->create();
        $component = Component::factory()->create(['tenant_id' => $user->tenant_id]);
        
        $promises = [];
        $startTime = microtime(true);
        
        // Simulate 20 concurrent requests
        for ($i = 0; $i < 20; $i++) {
            $promises[] = $this->actingAs($user)
                ->getJson("/api/v1/components/{$component->id}");
        }
        
        // Wait for all requests to complete
        foreach ($promises as $promise) {
            $promise->assertOk();
        }
        
        $duration = microtime(true) - $startTime;
        
        $this->assertLessThan(2000, $duration * 1000, 'Concurrent requests should complete within 2 seconds');
        
        $this->info("Concurrent access time: " . ($duration * 1000) . "ms");
    }
}
```

### Benchmarking

```bash
#!/bin/bash
# scripts/benchmark.sh

echo "🚀 Starting Component Library Performance Benchmark"

# API Endpoint Benchmarks
echo "📊 Benchmarking API endpoints..."

# Component list endpoint
echo "Testing /api/v1/components"
ab -n 1000 -c 10 -H "Authorization: Bearer $API_TOKEN" \
   -H "Accept: application/json" \
   http://localhost:8000/api/v1/components

# Component detail endpoint
echo "Testing /api/v1/components/1"
ab -n 500 -c 5 -H "Authorization: Bearer $API_TOKEN" \
   -H "Accept: application/json" \
   http://localhost:8000/api/v1/components/1

# Component render endpoint
echo "Testing component render"
ab -n 200 -c 5 -H "Authorization: Bearer $API_TOKEN" \
   -H "Content-Type: application/json" \
   -p render_payload.json \
   http://localhost:8000/api/v1/components/1/render

# Database Performance
echo "📈 Database performance metrics..."
mysql -u $DB_USERNAME -p$DB_PASSWORD -e "
    SELECT 
        table_name,
        table_rows,
        data_length,
        index_length,
        (data_length + index_length) as total_size
    FROM information_schema.tables 
    WHERE table_schema = '$DB_DATABASE'
    ORDER BY total_size DESC;
"

# Cache Performance
echo "💾 Cache performance metrics..."
redis-cli info memory
redis-cli info stats

# Queue Performance
echo "⚡ Queue performance metrics..."
php artisan queue:monitor --once

echo "✅ Benchmark completed"
```

This comprehensive performance guide covers all aspects of optimizing the Component Library System for maximum speed, efficiency, and scalability.