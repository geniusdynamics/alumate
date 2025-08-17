<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPosting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'requirements',
        'location',
        'salary_range',
        'posted_by',
        'expires_at',
        'is_active',
        'remote_allowed',
        'employment_type',
        'experience_level',
        'skills_required',
    ];

    protected $casts = [
        'requirements' => 'array',
        'skills_required' => 'array',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'remote_allowed' => 'boolean',
    ];

    protected $dates = [
        'expires_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the company that posted this job
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who posted this job
     */
    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Get all applications for this job
     */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_id');
    }

    /**
     * Get all match scores for this job
     */
    public function matchScores(): HasMany
    {
        return $this->hasMany(JobMatchScore::class, 'job_id');
    }

    /**
     * Check if the job is still active and not expired
     */
    public function isActive(): bool
    {
        return $this->is_active &&
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Get the match score for a specific user
     */
    public function getMatchScoreForUser(User $user): ?JobMatchScore
    {
        return $this->matchScores()
            ->where('user_id', $user->id)
            ->latest('calculated_at')
            ->first();
    }

    /**
     * Scope to only active jobs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to jobs by location
     */
    public function scopeByLocation($query, string $location)
    {
        return $query->where('location', 'ILIKE', "%{$location}%");
    }

    /**
     * Scope to remote jobs
     */
    public function scopeRemote($query)
    {
        return $query->where('remote_allowed', true);
    }
}
