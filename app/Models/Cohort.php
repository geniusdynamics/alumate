<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cohort Model
 *
 * Represents a user cohort for analytics and segmentation purposes.
 * Cohorts are used to group users based on specific criteria for analysis.
 */
class Cohort extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'criteria_json',
        'created_by',
        'members_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'criteria_json' => 'array',
        'members_count' => 'integer',
    ];

    /**
     * Get the tenant that owns the cohort.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who created the cohort.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get users that match this cohort's criteria.
     */
    public function users()
    {
        // This is a dynamic relationship based on criteria
        return User::query()->where(function ($query) {
            $criteria = $this->criteria_json;
            if (isset($criteria['grad_year'])) {
                $query->where('graduation_year', $criteria['grad_year']);
            }
            if (isset($criteria['degree'])) {
                $query->where('degree', $criteria['degree']);
            }
            // Add more criteria as needed
        });
    }

    /**
     * Scope a query to only include cohorts for a specific tenant.
     */
    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include cohorts created by a specific user.
     */
    public function scopeByCreator($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope a query to order cohorts by creation date (newest first).
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get the cohort criteria as a formatted string.
     */
    public function getCriteriaSummaryAttribute(): string
    {
        if (! $this->criteria_json) {
            return 'No criteria defined';
        }

        $summary = [];
        foreach ($this->criteria_json as $key => $value) {
            if (is_array($value)) {
                $summary[] = ucfirst($key).': '.json_encode($value);
            } else {
                $summary[] = ucfirst($key).': '.$value;
            }
        }

        return implode(', ', $summary);
    }
}
