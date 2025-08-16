<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPageAnalytics extends Model
{
    protected $fillable = [
        'landing_page_id',
        'event_type',
        'event_name',
        'event_data',
        'session_id',
        'visitor_id',
        'ip_address',
        'user_agent',
        'referrer',
        'utm_data',
        'device_type',
        'browser',
        'os',
        'country',
        'city',
        'event_time',
    ];

    protected $casts = [
        'event_data' => 'array',
        'utm_data' => 'array',
        'event_time' => 'datetime',
    ];

    /**
     * Get the landing page this analytics event belongs to
     */
    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    /**
     * Scope by event type
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('event_time', [$startDate, $endDate]);
    }
}
