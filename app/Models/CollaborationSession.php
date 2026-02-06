<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CollaborationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'user_id',
        'session_id',
        'cursor_position',
        'selected_component',
        'status',
        'last_activity',
    ];

    protected function casts(): array
    {
        return [
            'cursor_position' => 'array',
            'selected_component' => 'array',
            'last_activity' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class, 'page_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForPage($query, int $pageId)
    {
        return $query->where('page_id', $pageId);
    }

    public function scopeRecentActivity($query, int $minutes = 5)
    {
        return $query->where('last_activity', '>=', now()->subMinutes($minutes));
    }

    public function updateActivity(): void
    {
        $this->update([
            'last_activity' => now(),
            'status' => 'active'
        ]);
    }

    public function disconnect(): void
    {
        $this->update(['status' => 'disconnected']);
    }
}
