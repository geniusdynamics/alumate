<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HeatMapData extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'page_url',
        'coordinate_data',
        'timestamp',
        'session_id',
    ];

    protected $casts = [
        'coordinate_data' => 'array',
        'timestamp' => 'datetime',
    ];

    /**
     * Get the tenant this heat map data belongs to
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope by tenant
     */
    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope by page URL
     */
    public function scopeByPageUrl($query, string $pageUrl)
    {
        return $query->where('page_url', $pageUrl);
    }

    /**
     * Scope by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    /**
     * Scope by session
     */
    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}