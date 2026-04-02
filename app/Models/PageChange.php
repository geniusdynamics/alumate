<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'user_id',
        'operation_type',
        'component_id',
        'operation_data',
        'previous_state',
        'new_state',
        'sequence_number',
        'session_id',
        'is_applied',
    ];

    protected function casts(): array
    {
        return [
            'operation_data' => 'array',
            'previous_state' => 'array',
            'new_state' => 'array',
            'is_applied' => 'boolean',
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

    public function scopeForPage($query, int $pageId)
    {
        return $query->where('page_id', $pageId);
    }

    public function scopeInSequence($query)
    {
        return $query->orderBy('sequence_number');
    }

    public function scopeUnapplied($query)
    {
        return $query->where('is_applied', false);
    }

    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function markAsApplied(): void
    {
        $this->update(['is_applied' => true]);
    }
}
