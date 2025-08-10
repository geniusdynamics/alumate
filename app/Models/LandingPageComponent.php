<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPageComponent extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'default_props',
        'schema',
        'icon',
        'category',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'default_props' => 'array',
        'schema' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Scope for active components
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
