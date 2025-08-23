<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'key',
        'permissions',
        'last_used_at',
        'expires_at'
    ];

    protected $casts = [
        'permissions' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    protected $hidden = [
        'key'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}