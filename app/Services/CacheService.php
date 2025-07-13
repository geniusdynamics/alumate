<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class CacheService
{
    protected $defaultTtl = 3600; // 1 hour

    /**
     * Get data from cache or execute callback and cache result
     */
    public function remember(string $key, $callback, int $ttl = null)
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::error("Cache error for key {$key}: " . $e->getMessage());
            return is_callable($callback) ? $callback() : $callback;
        }
    }

    /**
     * Store data in cache
     */
    public function put(string $key, $value, int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        try {
            return Cache::put($key, $value, $ttl);
        } catch (\Exception $e) {
            Log::error("Cache put error for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get data from cache
     */
    public function get(string $key, $default = null)
    {
        try {
            return Cache::get($key, $default);
        } catch (\Exception $e) {
            Log::error("Cache get error for key {$key}: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Remove data from cache
     */
    public function forget(string $key): bool
    {
        try {
            return Cache::forget($key);
        } catch (\Exception $e) {
            Log::error("Cache forget error for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear all cache
     */
    public function flush(): bool
    {
        try {
            return Cache::flush();
        } catch (\Exception $e) {
            Log::error("Cache flush error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cache with tags (Redis only)
     */
    public function tags(array $tags)
    {
        try {
            return Cache::tags($tags);
        } catch (\Exception $e) {
            Log::error("Cache tags error: " . $e->getMessage());
            return Cache::store('array'); // Fallback to array store
        }
    }

    /**
     * Increment cache value
     */
    public function increment(string $key, int $value = 1): int
    {
        try {
            return Cache::increment($key, $value);
        } catch (\Exception $e) {
            Log::error("Cache increment error for key {$key}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Decrement cache value
     */
    public function decrement(string $key, int $value = 1): int
    {
        try {
            return Cache::decrement($key, $value);
        } catch (\Exception $e) {
            Log::error("Cache decrement error for key {$key}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool
    {
        try {
            return Cache::has($key);
        } catch (\Exception $e) {
            Log::error("Cache has error for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get multiple keys from cache
     */
    public function many(array $keys): array
    {
        try {
            return Cache::many($keys);
        } catch (\Exception $e) {
            Log::error("Cache many error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Put multiple values in cache
     */
    public function putMany(array $values, int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        try {
            return Cache::putMany($values, $ttl);
        } catch (\Exception $e) {
            Log::error("Cache putMany error: " . $e->getMessage());
            return false;
        }
    }
}
