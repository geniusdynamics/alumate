<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'severity',
        'ip_address',
        'user_id',
        'description',
        'metadata',
        'resolved',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
    ];

    protected $casts = [
        'metadata' => 'array',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Helper methods
    public function resolve($userId, $notes = null)
    {
        $this->update([
            'resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $userId,
            'resolution_notes' => $notes,
        ]);
    }

    public function isCritical()
    {
        return $this->severity === 'critical';
    }

    public function isHigh()
    {
        return $this->severity === 'high';
    }

    // Event types constants
    const TYPE_FAILED_LOGIN = 'failed_login';

    const TYPE_SUSPICIOUS_ACTIVITY = 'suspicious_activity';

    const TYPE_RATE_LIMIT_EXCEEDED = 'rate_limit_exceeded';

    const TYPE_UNAUTHORIZED_ACCESS = 'unauthorized_access';

    const TYPE_DATA_BREACH_ATTEMPT = 'data_breach_attempt';

    const TYPE_MALICIOUS_REQUEST = 'malicious_request';

    const TYPE_ACCOUNT_LOCKOUT = 'account_lockout';

    const TYPE_TWO_FACTOR_ENABLED = 'two_factor_enabled';

    const TYPE_TWO_FACTOR_DISABLED = 'two_factor_disabled';

    const TYPE_SESSION_CLEANUP = 'session_cleanup';

    // Severity levels
    const SEVERITY_LOW = 'low';

    const SEVERITY_MEDIUM = 'medium';

    const SEVERITY_HIGH = 'high';

    const SEVERITY_CRITICAL = 'critical';
}
