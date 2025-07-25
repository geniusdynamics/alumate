<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_definition_id',
        'measurement_date',
        'value',
        'breakdown',
        'metadata',
    ];

    protected $casts = [
        'measurement_date' => 'date',
        'value' => 'decimal:4',
        'breakdown' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function kpiDefinition(): BelongsTo
    {
        return $this->belongsTo(KpiDefinition::class);
    }

    // Scopes
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('measurement_date', [$startDate, $endDate]);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('measurement_date', 'desc');
    }

    // Helper methods
    public function getFormattedValue()
    {
        $kpi = $this->kpiDefinition;
        
        return match($kpi->calculation_method) {
            'percentage' => number_format($this->value, 1) . '%',
            'count' => number_format($this->value, 0),
            'average' => number_format($this->value, 2),
            'ratio' => number_format($this->value, 3) . ':1',
            default => number_format($this->value, 2),
        };
    }

    public function getBreakdownValue($key, $default = null)
    {
        return data_get($this->breakdown, $key, $default);
    }

    public function hasBreakdown()
    {
        return !empty($this->breakdown);
    }

    public function getMetadataValue($key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }

    public function getTrendDirection($previousValue = null)
    {
        if ($previousValue === null) {
            $previous = static::where('kpi_definition_id', $this->kpi_definition_id)
                ->where('measurement_date', '<', $this->measurement_date)
                ->orderBy('measurement_date', 'desc')
                ->first();
            
            $previousValue = $previous?->value;
        }

        if ($previousValue === null) {
            return 'neutral';
        }

        if ($this->value > $previousValue) {
            return 'up';
        } elseif ($this->value < $previousValue) {
            return 'down';
        }

        return 'neutral';
    }

    public function getTrendPercentage($previousValue = null)
    {
        if ($previousValue === null) {
            $previous = static::where('kpi_definition_id', $this->kpi_definition_id)
                ->where('measurement_date', '<', $this->measurement_date)
                ->orderBy('measurement_date', 'desc')
                ->first();
            
            $previousValue = $previous?->value;
        }

        if ($previousValue === null || $previousValue == 0) {
            return 0;
        }

        return (($this->value - $previousValue) / $previousValue) * 100;
    }

    public function isImprovement()
    {
        $kpi = $this->kpiDefinition;
        $direction = $this->getTrendDirection();

        // For minimum targets, up is good
        // For maximum targets, down is good
        return match($kpi->target_type) {
            'minimum' => $direction === 'up',
            'maximum' => $direction === 'down',
            default => $direction === 'up',
        };
    }
}