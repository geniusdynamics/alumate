<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Graduate;
use App\Models\JobApplication;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Cross-Tenant Aggregation Service
 *
 * Provides secure cross-tenant data aggregation for super admin analytics.
 * Maintains tenant data isolation while providing platform-wide visibility.
 */
class CrossTenantAggregationService extends BaseService
{
    /**
     * Default cache TTL for aggregated metrics (in seconds).
     */
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Cache key prefix for cross-tenant aggregations.
     */
    private const CACHE_PREFIX = 'cross_tenant_agg:';

    /**
     * Get graduate count for a specific tenant.
     *
     * @param  string  $tenantId  The tenant ID
     * @return int The graduate count
     */
    public function getTenantGraduateCount(string $tenantId): int
    {
        $cacheKey = $this->getCacheKey("graduates:{$tenantId}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId) {
            $count = 0;

            try {
                $tenant = Tenant::find($tenantId);
                if (! $tenant) {
                    return 0;
                }

                $tenant->run(function () use (&$count) {
                    if (Schema::hasTable('graduates')) {
                        $count = Graduate::count();
                    }
                });
            } catch (\Exception $e) {
                Log::warning('Failed to get graduate count for tenant', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);

                return 0;
            }

            return $count;
        });
    }

    /**
     * Get graduate counts for all tenants.
     *
     * @return array Associative array of tenant_id => graduate_count
     */
    public function getAllTenantGraduateCounts(): array
    {
        $cacheKey = $this->getCacheKey('all_graduates');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $counts = [];
            $tenants = Tenant::all();

            foreach ($tenants as $tenant) {
                $counts[$tenant->id] = $this->getTenantGraduateCount($tenant->id);
            }

            return $counts;
        });
    }

    /**
     * Get employment trends across all tenants.
     *
     * @param  \Carbon\Carbon|null  $startDate  Optional start date for filtering
     * @return Collection Employment status distribution
     */
    public function getEmploymentTrends(?\Carbon\Carbon $startDate = null): Collection
    {
        $cacheKey = $this->getCacheKey('employment_trends:'.($startDate ? $startDate->toDateString() : 'all'));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($startDate) {
            $trends = collect([
                'employed' => 0,
                'unemployed' => 0,
                'self_employed' => 0,
                'studying' => 0,
                'seeking' => 0,
                'other' => 0,
            ]);

            $tenants = Tenant::all();

            foreach ($tenants as $tenant) {
                try {
                    $tenant->run(function () use (&$trends, $startDate) {
                        if (! Schema::hasTable('graduates')) {
                            return;
                        }

                        $query = Graduate::query();

                        if ($startDate) {
                            $query->where('created_at', '>=', $startDate);
                        }

                        $graduates = $query->get(['employment_status']);

                        foreach ($graduates as $graduate) {
                            $status = $graduate->employment_status ?? 'other';
                            if ($trends->has($status)) {
                                $trends[$status]++;
                            } else {
                                $trends['other']++;
                            }
                        }
                    });
                } catch (\Exception $e) {
                    Log::warning('Failed to get employment trends for tenant', [
                        'tenant_id' => $tenant->id,
                        'error' => $e->getMessage(),
                    ]);

                    continue;
                }
            }

            return $trends->map(fn ($count, $status) => [
                'employment_status' => $status,
                'count' => $count,
            ])->values();
        });
    }

    /**
     * Get total graduate count across all tenants.
     *
     * @return int Total graduate count
     */
    public function getTotalGraduateCount(): int
    {
        $cacheKey = $this->getCacheKey('total_graduates');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $total = 0;
            $tenants = Tenant::all();

            foreach ($tenants as $tenant) {
                $total += $this->getTenantGraduateCount($tenant->id);
            }

            return $total;
        });
    }

    /**
     * Get job application counts across all tenants.
     *
     * @param  \Carbon\Carbon|null  $startDate  Optional start date for filtering
     * @return int Total job application count
     */
    public function getTotalJobApplicationCount(?\Carbon\Carbon $startDate = null): int
    {
        $cacheKey = $this->getCacheKey('total_applications:'.($startDate ? $startDate->toDateString() : 'all'));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($startDate) {
            $total = 0;
            $tenants = Tenant::all();

            foreach ($tenants as $tenant) {
                try {
                    $tenant->run(function () use (&$total, $startDate) {
                        if (! Schema::hasTable('job_applications')) {
                            return;
                        }

                        $query = JobApplication::query();

                        if ($startDate) {
                            $query->where('created_at', '>=', $startDate);
                        }

                        $total += $query->count();
                    });
                } catch (\Exception $e) {
                    Log::warning('Failed to get job application count for tenant', [
                        'tenant_id' => $tenant->id,
                        'error' => $e->getMessage(),
                    ]);

                    continue;
                }
            }

            return $total;
        });
    }

    /**
     * Get employment rate across all tenants.
     *
     * @return float Employment rate as percentage
     */
    public function getOverallEmploymentRate(): float
    {
        $cacheKey = $this->getCacheKey('employment_rate');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $totalGraduates = 0;
            $employedGraduates = 0;
            $tenants = Tenant::all();

            foreach ($tenants as $tenant) {
                try {
                    $tenant->run(function () use (&$totalGraduates, &$employedGraduates) {
                        if (! Schema::hasTable('graduates')) {
                            return;
                        }

                        $totalGraduates += Graduate::count();
                        $employedGraduates += Graduate::whereIn('employment_status', ['employed', 'self_employed'])->count();
                    });
                } catch (\Exception $e) {
                    Log::warning('Failed to get employment rate for tenant', [
                        'tenant_id' => $tenant->id,
                        'error' => $e->getMessage(),
                    ]);

                    continue;
                }
            }

            return $totalGraduates > 0 ? round(($employedGraduates / $totalGraduates) * 100, 1) : 0;
        });
    }

    /**
     * Get aggregated metrics for a specific tenant.
     *
     * @param  string  $tenantId  The tenant ID
     * @return array Tenant metrics
     */
    public function getTenantMetrics(string $tenantId): array
    {
        $cacheKey = $this->getCacheKey("metrics:{$tenantId}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenantId) {
            $metrics = [
                'graduate_count' => 0,
                'employment_rate' => 0,
                'active_jobs' => 0,
                'total_applications' => 0,
            ];

            try {
                $tenant = Tenant::find($tenantId);
                if (! $tenant) {
                    return $metrics;
                }

                $tenant->run(function () use (&$metrics) {
                    if (Schema::hasTable('graduates')) {
                        $metrics['graduate_count'] = Graduate::count();
                        $employedCount = Graduate::whereIn('employment_status', ['employed', 'self_employed'])->count();
                        $metrics['employment_rate'] = $metrics['graduate_count'] > 0
                            ? round(($employedCount / $metrics['graduate_count']) * 100, 1)
                            : 0;
                    }

                    if (Schema::hasTable('jobs')) {
                        $metrics['active_jobs'] = DB::table('jobs')->where('status', 'active')->count();
                    }

                    if (Schema::hasTable('job_applications')) {
                        $metrics['total_applications'] = JobApplication::count();
                    }
                });
            } catch (\Exception $e) {
                Log::warning('Failed to get metrics for tenant', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);
            }

            return $metrics;
        });
    }

    /**
     * Get aggregated metrics for all tenants.
     *
     * @return array Array of tenant metrics
     */
    public function getAllTenantMetrics(): array
    {
        $cacheKey = $this->getCacheKey('all_metrics');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $metrics = [];
            $tenants = Tenant::all();

            foreach ($tenants as $tenant) {
                $metrics[$tenant->id] = $this->getTenantMetrics($tenant->id);
            }

            return $metrics;
        });
    }

    /**
     * Clear all cross-tenant aggregation cache.
     */
    public function clearCache(): void
    {
        $pattern = self::CACHE_PREFIX.'*';
        $keys = Cache::getRedis()->keys($pattern);

        if (! empty($keys)) {
            Cache::getRedis()->del($keys);
        }

        Log::info('Cross-tenant aggregation cache cleared', [
            'keys_cleared' => count($keys),
        ]);
    }

    /**
     * Clear cache for a specific tenant.
     *
     * @param  string  $tenantId  The tenant ID
     */
    public function clearTenantCache(string $tenantId): void
    {
        $patterns = [
            self::CACHE_PREFIX."graduates:{$tenantId}",
            self::CACHE_PREFIX."metrics:{$tenantId}",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        // Also clear aggregated caches
        $this->clearCache();

        Log::info('Tenant aggregation cache cleared', [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Generate a cache key for cross-tenant aggregation.
     *
     * @param  string  $key  The cache key suffix
     * @return string The full cache key
     */
    private function getCacheKey(string $key): string
    {
        return self::CACHE_PREFIX.$key;
    }

    /**
     * Validate that the current user has super admin privileges.
     *
     * @return bool True if user is super admin
     */
    private function validateSuperAdminAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->is_super_admin === true;
    }

    /**
     * Execute a cross-tenant aggregation with security validation.
     *
     * @param  callable  $callback  The aggregation callback
     * @return mixed The aggregation result
     *
     * @throws \Exception If user is not a super admin
     */
    private function executeSecureAggregation(callable $callback)
    {
        if (! $this->validateSuperAdminAccess()) {
            throw new \Exception('Unauthorized: Cross-tenant aggregation requires super admin privileges');
        }

        return $callback();
    }
}
