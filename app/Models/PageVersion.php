<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PageVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'version_number',
        'grapejs_data',
        'metadata',
        'change_summary',
        'created_by',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'grapejs_data' => 'array',
            'metadata' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class, 'page_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeForPage($query, int $pageId)
    {
        return $query->where('page_id', $pageId);
    }

    public function scopeLatestVersion($query, int $pageId)
    {
        return $query->where('page_id', $pageId)
                    ->orderBy('version_number', 'desc')
                    ->first();
    }
}
