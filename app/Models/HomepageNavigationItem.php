<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomepageNavigationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'url',
        'target',
        'parent_id',
        'order',
        'type',
    ];

    /**
     * Get the parent navigation item.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(HomepageNavigationItem::class, 'parent_id');
    }

    /**
     * Get the children navigation items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(HomepageNavigationItem::class, 'parent_id')->orderBy('order');
    }
}
