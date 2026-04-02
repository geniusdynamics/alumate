<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HomepageContent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'section',
        'key',
        'content',
        'audience',
        'status',
        'scheduled_at',
        'published_at',
        'version',
        'metadata',
    ];

    protected $casts = [
        'content' => 'array',
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'version' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeForSection($query, string $section)
    {
        return $query->where('section', $section);
    }

    public function scopeForAudience($query, string $audience)
    {
        return $query->where('audience', $audience);
    }
}
