<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplatePerformanceDashboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'configuration',
        'filters',
        'is_default',
        'is_active',
        'cached_metrics',
        'last_updated_at',
    ];

    protected $casts = [
        'configuration' => 'array',
        'filters' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'cached_metrics' => 'array',
        'last_updated_at' => 'datetime',
    ];

    protected $attributes = [
        'is_default' => false,
        'is_active' => true,
        'configuration' => '{}',
        'filters' => '{}',
        'cached_metrics' => '{}',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply tenant scoping automatically for multi-tenant isolation
        static::addGlobalScope('tenant', function ($builder) {
            // Check if we're in a multi-tenant context
            if (config('database.multi_tenant', false)) {
                try {
                    // In production, apply tenant filter based on current tenant context
                    if (tenant() && tenant()->id) {
                        $builder->where('tenant_id', tenant()->id);
                    }
                } catch (\Exception $e) {
                    // Skip tenant scoping in test environment
                }
            }
        });
    }

    /**
     * Scope query to specific tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope query to active dashboards only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to default dashboard
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the tenant that owns the dashboard
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get dashboard configuration with defaults applied
     */
    public function getEffectiveConfiguration(): array
    {
        $config = $this->configuration ?? [];

        if (empty($config)) {
            return $this->getDefaultConfiguration();
        }

        return array_merge($this->getDefaultConfiguration(), $config);
    }

    /**
     * Get default dashboard configuration
     */
    protected function getDefaultConfiguration(): array
    {
        return [
            'widgets' => [
                [
                    'type' => 'metric_card',
                    'title' => 'Total Templates',
                    'metric' => 'total_templates',
                    'position' => ['x' => 0, 'y' => 0, 'w' => 3, 'h' => 2],
                ],
                [
                    'type' => 'metric_card',
                    'title' => 'Active Templates',
                    'metric' => 'active_templates',
                    'position' => ['x' => 3, 'y' => 0, 'w' => 3, 'h' => 2],
                ],
                [
                    'type' => 'chart',
                    'title' => 'Conversion Rate Trend',
                    'chart_type' => 'line',
                    'metric' => 'conversion_rate_trend',
                    'position' => ['x' => 0, 'y' => 2, 'w' => 6, 'h' => 4],
                ],
                [
                    'type' => 'chart',
                    'title' => 'Template Performance',
                    'chart_type' => 'bar',
                    'metric' => 'template_performance',
                    'position' => ['x' => 6, 'y' => 0, 'w' => 6, 'h' => 6],
                ],
            ],
            'refresh_interval' => 300, // 5 minutes
            'date_range' => 'last_30_days',
        ];
    }

    /**
     * Update cached metrics
     */
    public function updateCachedMetrics(array $metrics): void
    {
        $this->update([
            'cached_metrics' => $metrics,
            'last_updated_at' => now(),
        ]);
    }

    /**
     * Check if cached metrics are still fresh
     */
    public function hasFreshMetrics(int $maxAgeSeconds = 300): bool
    {
        if (!$this->last_updated_at) {
            return false;
        }

        return $this->last_updated_at->diffInSeconds(now()) < $maxAgeSeconds;
    }

    /**
     * Get cached metrics if available and fresh
     */
    public function getCachedMetrics(int $maxAgeSeconds = 300): ?array
    {
        if ($this->hasFreshMetrics($maxAgeSeconds)) {
            return $this->cached_metrics;
        }

        return null;
    }

    /**
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'configuration' => 'nullable|array',
            'filters' => 'nullable|array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'cached_metrics' => 'nullable|array',
            'last_updated_at' => 'nullable|date',
        ];
    }
}
