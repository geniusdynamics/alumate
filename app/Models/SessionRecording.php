<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SessionRecording extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'session_id',
        'user_id',
        'recording_data',
        'duration_seconds',
        'page_views',
        'interactions_count',
        'privacy_masked',
    ];

    protected $casts = [
        'recording_data' => 'array',
        'privacy_masked' => 'boolean',
        'duration_seconds' => 'integer',
        'page_views' => 'integer',
        'interactions_count' => 'integer',
    ];

    /**
     * Get the tenant this session recording belongs to
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who owns this session recording
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope by tenant
     */
    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope by session ID
     */
    public function scopeBySessionId($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for privacy masked recordings
     */
    public function scopePrivacyMasked($query)
    {
        return $query->where('privacy_masked', true);
    }

    /**
     * Scope for recordings within date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Check if recording data is compressed
     */
    public function isCompressed(): bool
    {
        return is_string($this->recording_data) && str_starts_with($this->recording_data, 'compressed:');
    }

    /**
     * Get decompressed recording data
     */
    public function getDecompressedData(): array
    {
        if ($this->isCompressed()) {
            // Implement decompression logic here
            $compressedData = substr($this->recording_data, 11); // Remove 'compressed:' prefix

            return json_decode(gzuncompress(base64_decode($compressedData)), true) ?? [];
        }

        return $this->recording_data ?? [];
    }

    /**
     * Calculate session insights
     */
    public function getSessionInsights(): array
    {
        $data = $this->getDecompressedData();

        $insights = [
            'total_events' => count($data),
            'rage_clicks' => 0,
            'confusion_patterns' => 0,
            'drop_off_points' => [],
            'interaction_clusters' => [],
        ];

        // Analyze events for patterns
        $clickEvents = array_filter($data, fn ($event) => ($event['type'] ?? '') === 'click');
        $rageClickThreshold = 3; // clicks within 1 second
        $confusionThreshold = 5; // rapid interactions

        foreach ($clickEvents as $index => $event) {
            $timestamp = $event['timestamp'] ?? 0;
            $nearbyClicks = array_filter($clickEvents, function ($otherEvent) use ($timestamp) {
                return abs(($otherEvent['timestamp'] ?? 0) - $timestamp) < 1000;
            });

            if (count($nearbyClicks) >= $rageClickThreshold) {
                $insights['rage_clicks']++;
            }
        }

        // Detect confusion patterns (rapid back-and-forth interactions)
        $interactionTimestamps = array_column($data, 'timestamp');
        sort($interactionTimestamps);

        for ($i = 0; $i < count($interactionTimestamps) - $confusionThreshold; $i++) {
            $timeSpan = $interactionTimestamps[$i + $confusionThreshold - 1] - $interactionTimestamps[$i];
            if ($timeSpan < 5000) { // within 5 seconds
                $insights['confusion_patterns']++;
            }
        }

        return $insights;
    }
}
