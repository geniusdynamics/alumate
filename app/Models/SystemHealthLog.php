<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemHealthLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'component',
        'status',
        'metrics',
        'message',
        'checked_at',
    ];

    protected $casts = [
        'metrics' => 'array',
        'checked_at' => 'datetime',
    ];

    // Scopes
    public function scopeByComponent($query, $component)
    {
        return $query->where('component', $component);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeHealthy($query)
    {
        return $query->where('status', 'healthy');
    }

    public function scopeWarning($query)
    {
        return $query->where('status', 'warning');
    }

    public function scopeCritical($query)
    {
        return $query->where('status', 'critical');
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('checked_at', '>=', now()->subHours($hours));
    }

    public function scopeLatestByComponent($query)
    {
        return $query->whereIn('id', function($subQuery) {
            $subQuery->selectRaw('MAX(id)')
                    ->from('system_health_logs')
                    ->groupBy('component');
        });
    }

    // Helper methods
    public function isHealthy()
    {
        return $this->status === 'healthy';
    }

    public function isWarning()
    {
        return $this->status === 'warning';
    }

    public function isCritical()
    {
        return $this->status === 'critical';
    }

    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'healthy':
                return 'green';
            case 'warning':
                return 'yellow';
            case 'critical':
                return 'red';
            default:
                return 'gray';
        }
    }

    public function getStatusIconAttribute()
    {
        switch ($this->status) {
            case 'healthy':
                return '✓';
            case 'warning':
                return '⚠';
            case 'critical':
                return '✗';
            default:
                return '?';
        }
    }

    // Component constants
    public const COMPONENT_DATABASE = 'database';
    public const COMPONENT_CACHE = 'cache';
    public const COMPONENT_STORAGE = 'storage';
    public const COMPONENT_QUEUE = 'queue';
    public const COMPONENT_MEMORY = 'memory';
    public const COMPONENT_DISK_SPACE = 'disk_space';

    // Status constants
    public const STATUS_HEALTHY = 'healthy';
    public const STATUS_WARNING = 'warning';
    public const STATUS_CRITICAL = 'critical';
}