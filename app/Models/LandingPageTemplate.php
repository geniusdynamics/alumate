<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingPageTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'content',
        'default_settings',
        'preview_image',
        'is_active',
        'is_premium',
        'usage_count',
        'tags',
        'created_by',
    ];

    protected $casts = [
        'content' => 'array',
        'default_settings' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_premium' => 'boolean',
    ];

    /**
     * Get the user who created this template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get landing pages using this template
     */
    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class, 'template_id');
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
