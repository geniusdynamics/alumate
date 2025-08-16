<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'snapshot_date',
        'data',
        'metadata',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'data' => 'array',
        'metadata' => 'array',
    ];

    // Scopes
    public function scopeDaily($query)
    {
        return $query->where('type', 'daily');
    }

    public function scopeWeekly($query)
    {
        return $query->where('type', 'weekly');
    }

    public function scopeMonthly($query)
    {
        return $query->where('type', 'monthly');
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('snapshot_date', [$startDate, $endDate]);
    }

    // Helper methods
    public static function getLatestSnapshot($type)
    {
        return static::where('type', $type)
            ->orderBy('snapshot_date', 'desc')
            ->first();
    }

    public static function getSnapshotForDate($type, $date)
    {
        return static::where('type', $type)
            ->where('snapshot_date', $date)
            ->first();
    }

    public static function getTrendData($type, $days = 30)
    {
        $startDate = Carbon::now()->subDays($days);

        return static::where('type', $type)
            ->where('snapshot_date', '>=', $startDate)
            ->orderBy('snapshot_date')
            ->get();
    }

    public function getMetric($key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    public function hasMetric($key)
    {
        return data_get($this->data, $key) !== null;
    }
}
