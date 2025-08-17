<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventHighlightInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'highlight_id',
        'user_id',
        'type',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Relationships
    public function highlight(): BelongsTo
    {
        return $this->belongsTo(EventHighlight::class, 'highlight_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeLikes($query)
    {
        return $query->where('type', 'like');
    }

    public function scopeShares($query)
    {
        return $query->where('type', 'share');
    }

    public function scopeComments($query)
    {
        return $query->where('type', 'comment');
    }

    // Helper methods
    public function isLike(): bool
    {
        return $this->type === 'like';
    }

    public function isShare(): bool
    {
        return $this->type === 'share';
    }

    public function isComment(): bool
    {
        return $this->type === 'comment';
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'like' => 'Like',
            'share' => 'Share',
            'comment' => 'Comment',
            default => 'Interaction'
        };
    }
}
