<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'url',
        'events',
        'secret',
        'status',
        'name',
        'description',
        'headers',
        'timeout',
        'retry_attempts',
    ];

    protected $casts = [
        'events' => 'array',
        'headers' => 'array',
    ];

    protected $hidden = [
        'secret',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function subscribesToEvent(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isDisabled(): bool
    {
        return $this->status === 'disabled';
    }
}
