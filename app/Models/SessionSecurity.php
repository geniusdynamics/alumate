<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionSecurity extends Model
{
    use HasFactory;

    protected $table = 'session_security';

    protected $fillable = [
        'session_id',
        'user_id',
        'ip_address',
        'user_agent',
        'last_activity',
        'is_suspicious',
        'security_flags',
        'expires_at',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
        'is_suspicious' => 'boolean',
        'security_flags' => 'array',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    // Helper methods
    public static function trackSession($sessionId, $userId, $ip, $userAgent)
    {
        $existing = self::where('session_id', $sessionId)->first();

        if ($existing) {
            // Check for suspicious activity
            $suspicious = false;
            $flags = $existing->security_flags ?? [];

            // Check for IP change
            if ($existing->ip_address !== $ip) {
                $suspicious = true;
                $flags[] = 'ip_change';
            }

            // Check for user agent change
            if ($existing->user_agent !== $userAgent) {
                $suspicious = true;
                $flags[] = 'user_agent_change';
            }

            $existing->update([
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'last_activity' => now(),
                'is_suspicious' => $suspicious,
                'security_flags' => array_unique($flags),
            ]);

            return $existing;
        }

        return self::create([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'last_activity' => now(),
            'expires_at' => now()->addMinutes(config('session.lifetime', 120)),
        ]);
    }

    public function flagAsSuspicious($reason)
    {
        $flags = $this->security_flags ?? [];
        $flags[] = $reason;

        $this->update([
            'is_suspicious' => true,
            'security_flags' => array_unique($flags),
        ]);
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    public function extend($minutes = null)
    {
        $minutes = $minutes ?? config('session.lifetime', 120);

        $this->update([
            'expires_at' => now()->addMinutes($minutes),
            'last_activity' => now(),
        ]);
    }

    public static function cleanupExpired()
    {
        return self::expired()->delete();
    }
}
