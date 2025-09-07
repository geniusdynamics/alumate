<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComponentVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_id',
        'version_number',
        'config',
        'metadata',
        'changes',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'metadata' => 'array',
            'changes' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the component that owns this version
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * Get the user who created this version
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Scope to get versions for a specific component
     */
    public function scopeForComponent($query, int $componentId)
    {
        return $query->where('component_id', $componentId);
    }

    /**
     * Scope to get latest versions first
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('version_number', 'desc');
    }

    /**
     * Get the version display name
     */
    public function getDisplayNameAttribute(): string
    {
        return "v{$this->version_number}" . ($this->description ? " - {$this->description}" : '');
    }

    /**
     * Check if this is the latest version
     */
    public function getIsLatestAttribute(): bool
    {
        $latestVersion = static::forComponent($this->component_id)
            ->max('version_number');
        
        return $this->version_number === $latestVersion;
    }
}
