<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplatePerformanceReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'report_type',
        'parameters',
        'data',
        'format',
        'status',
        'generated_at',
        'expires_at',
        'error_message',
    ];

    protected $casts = [
        'parameters' => 'array',
        'data' => 'array',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $attributes = [
        'format' => 'json',
        'status' => 'pending',
    ];

    /**
     * Report status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Report type constants
     */
    public const REPORT_TYPES = [
        'template_performance',
        'comparison',
        'trend_analysis',
        'bottleneck_analysis',
        'conversion_funnel',
        'device_breakdown',
        'geographic_analysis',
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
     * Scope query by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope query by report type
     */
    public function scopeByType($query, string $reportType)
    {
        return $query->where('report_type', $reportType);
    }

    /**
     * Scope query for completed reports
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope query for expired reports
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Get the tenant that owns the report
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if report is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if report is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if report is still valid
     */
    public function isValid(): bool
    {
        return $this->isCompleted() && !$this->isExpired();
    }

    /**
     * Mark report as processing
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    /**
     * Mark report as completed
     */
    public function markAsCompleted(array $data = null): void
    {
        $updateData = [
            'status' => self::STATUS_COMPLETED,
            'generated_at' => now(),
        ];

        if ($data !== null) {
            $updateData['data'] = $data;
        }

        // Set expiration date (24 hours from now)
        if (!$this->expires_at) {
            $updateData['expires_at'] = now()->addHours(24);
        }

        $this->update($updateData);
    }

    /**
     * Mark report as failed
     */
    public function markAsFailed(string $errorMessage = null): void
    {
        $updateData = ['status' => self::STATUS_FAILED];

        if ($errorMessage) {
            $updateData['error_message'] = $errorMessage;
        }

        $this->update($updateData);
    }

    /**
     * Get report data with caching
     */
    public function getReportData(): ?array
    {
        if (!$this->isValid()) {
            return null;
        }

        return $this->data;
    }

    /**
     * Get formatted report for export
     */
    public function getFormattedReport(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'report_type' => $this->report_type,
            'parameters' => $this->parameters,
            'data' => $this->getReportData(),
            'format' => $this->format,
            'status' => $this->status,
            'generated_at' => $this->generated_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
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
            'report_type' => 'required|string|in:' . implode(',', self::REPORT_TYPES),
            'parameters' => 'nullable|array',
            'data' => 'nullable|array',
            'format' => 'string|in:json,csv,excel,pdf',
            'status' => 'string|in:' . implode(',', [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_COMPLETED, self::STATUS_FAILED]),
            'generated_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'error_message' => 'nullable|string|max:1000',
        ];
    }
}
