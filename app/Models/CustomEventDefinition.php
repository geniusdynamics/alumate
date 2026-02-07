<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CustomEventDefinition Model
 *
 * Defines custom event schemas and validation rules for flexible event tracking.
 * Enables business-specific event definitions with JSON schema validation.
 */
class CustomEventDefinition extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'parameters_json',
        'created_by',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'parameters_json' => 'array',
    ];

    /**
     * Get the tenant that owns this custom event definition.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who created this custom event definition.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the custom events for this definition.
     */
    public function customEvents()
    {
        return $this->hasMany(CustomEvent::class, 'definition_id');
    }

    /**
     * Scope a query to only include custom event definitions for a specific tenant.
     */
    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include active custom event definitions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
