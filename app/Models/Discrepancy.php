<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Discrepancy Model
 * 
 * Tracks data discrepancies between different analytics sources
 */
class Discrepancy extends Model
{
    protected $fillable = [
        'discrepancy_id',
        'metric',
        'source1',
        'source2',
        'value1',
        'value2',
        'difference_percentage',
        'severity',
        'date_range_start',
        'date_range_end',
        'tenant_id',
        'resolved',
        'resolved_at',
        'resolution',
        'resolved_value',
    ];

    protected $casts = [
        'value1' => 'float',
        'value2' => 'float',
        'difference_percentage' => 'float',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'resolved_value' => 'float',
    ];

    /**
     * Get the tenant that owns this discrepancy
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to unresolved discrepancies
     */
    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    /**
     * Scope to resolved discrepancies
     */
    public function scopeResolved($query)
    {
        return $query->where('resolved', true);
    }

    /**
     * Scope to specific severity
     */
    public function scopeSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to specific tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to high severity discrepancies
     */
    public function scopeHighSeverity($query)
    {
        return $query->where('severity', 'high');
    }

    /**
     * Scope to medium severity discrepancies
     */
    public function scopeMediumSeverity($query)
    {
        return $query->where('severity', 'medium');
    }

    /**
     * Scope to low severity discrepancies
     */
    public function scopeLowSeverity($query)
    {
        return $query->where('severity', 'low');
    }

    /**
     * Get the average value
     */
    public function getAverageValueAttribute(): float
    {
        return ($this->value1 + $this->value2) / 2;
    }

    /**
     * Get the absolute difference
     */
    public function getAbsoluteDifferenceAttribute(): float
    {
        return abs($this->value1 - $this->value2);
    }

    /**
     * Check if discrepancy is high severity
     */
    public function isHighSeverity(): bool
    {
        return $this->severity === 'high';
    }

    /**
     * Check if discrepancy is medium severity
     */
    public function isMediumSeverity(): bool
    {
        return $this->severity === 'medium';
    }

    /**
     * Check if discrepancy is low severity
     */
    public function isLowSeverity(): bool
    {
        return $this->severity === 'low';
    }
}
