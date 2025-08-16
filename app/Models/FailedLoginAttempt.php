<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailedLoginAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'attempts',
        'last_attempt_at',
        'blocked_until',
    ];

    protected $casts = [
        'last_attempt_at' => 'datetime',
        'blocked_until' => 'datetime',
    ];

    // Scopes
    public function scopeByEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    public function scopeByIp($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeBlocked($query)
    {
        return $query->whereNotNull('blocked_until')
            ->where('blocked_until', '>', now());
    }

    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('last_attempt_at', '>=', now()->subMinutes($minutes));
    }

    // Helper methods
    public static function recordAttempt($email, $ip, $userAgent = null)
    {
        $attempt = self::where('email', $email)
            ->where('ip_address', $ip)
            ->first();

        if ($attempt) {
            $attempt->increment('attempts');
            $attempt->update([
                'last_attempt_at' => now(),
                'user_agent' => $userAgent,
            ]);
        } else {
            $attempt = self::create([
                'email' => $email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'attempts' => 1,
                'last_attempt_at' => now(),
            ]);
        }

        // Block if too many attempts
        if ($attempt->attempts >= config('security.max_login_attempts', 5)) {
            $attempt->update([
                'blocked_until' => now()->addMinutes(config('security.lockout_duration', 30)),
            ]);
        }

        return $attempt;
    }

    public static function clearAttempts($email, $ip)
    {
        return self::where('email', $email)
            ->where('ip_address', $ip)
            ->delete();
    }

    public static function isBlocked($email, $ip)
    {
        return self::where('email', $email)
            ->where('ip_address', $ip)
            ->blocked()
            ->exists();
    }

    public function isCurrentlyBlocked()
    {
        return $this->blocked_until && $this->blocked_until->isFuture();
    }

    public function getTimeUntilUnblocked()
    {
        if (! $this->isCurrentlyBlocked()) {
            return null;
        }

        return $this->blocked_until->diffInMinutes(now());
    }
}
