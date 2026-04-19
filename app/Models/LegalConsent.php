<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalConsent extends Model
{
    use HasFactory;

    protected $table = 'legal_consents';

    protected $fillable = [
        'user_id',
        'consent_type',
        'granted',
        'ip_address',
        'user_agent',
        'granted_at',
        'withdrawn_at',
        'metadata',
    ];

    protected $casts = [
        'granted' => 'boolean',
        'granted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isGranted(): bool
    {
        return $this->granted && $this->withdrawn_at === null;
    }

    public function isWithdrawn(): bool
    {
        return $this->withdrawn_at !== null;
    }

    public function grant(?string $ipAddress = null, ?string $userAgent = null): void
    {
        $this->update([
            'granted' => true,
            'granted_at' => now(),
            'withdrawn_at' => null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public function withdraw(): void
    {
        $this->update([
            'granted' => false,
            'withdrawn_at' => now(),
        ]);
    }
}
