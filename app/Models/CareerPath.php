<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerPath extends Model
{
    use HasFactory;

    const PATH_LINEAR = 'linear';

    const PATH_PIVOT = 'pivot';

    const PATH_ENTREPRENEURIAL = 'entrepreneurial';

    const PATH_PORTFOLIO = 'portfolio';

    const PATH_ACADEMIC = 'academic';

    protected $fillable = [
        'user_id',
        'path_type',
        'progression_stages',
        'total_job_changes',
        'promotions_count',
        'industry_changes',
        'salary_growth_rate',
        'years_to_leadership',
        'skills_evolution',
    ];

    protected $casts = [
        'progression_stages' => 'array',
        'salary_growth_rate' => 'decimal:2',
        'skills_evolution' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByPathType($query, string $pathType)
    {
        return $query->where('path_type', $pathType);
    }

    public function scopeWithLeadership($query)
    {
        return $query->whereNotNull('years_to_leadership');
    }

    // Accessors
    public function getPathTypeDisplayAttribute()
    {
        return match ($this->path_type) {
            self::PATH_LINEAR => 'Linear Career Progression',
            self::PATH_PIVOT => 'Career Pivot',
            self::PATH_ENTREPRENEURIAL => 'Entrepreneurial Path',
            self::PATH_PORTFOLIO => 'Portfolio Career',
            self::PATH_ACADEMIC => 'Academic Career',
            default => ucfirst($this->path_type)
        };
    }

    public function getJobStabilityScoreAttribute()
    {
        if ($this->total_job_changes === 0) {
            return 100;
        }

        // Calculate based on years since graduation and job changes
        $user = $this->user;
        $yearsSinceGraduation = $user->graduationYear ? now()->year - $user->graduationYear : 5;

        if ($yearsSinceGraduation <= 0) {
            return 100;
        }

        $changesPerYear = $this->total_job_changes / $yearsSinceGraduation;

        // Score decreases with more frequent job changes
        return max(0, 100 - ($changesPerYear * 25));
    }

    public function getCareerVelocityAttribute()
    {
        $user = $this->user;
        $yearsSinceGraduation = $user->graduationYear ? now()->year - $user->graduationYear : 1;

        if ($yearsSinceGraduation <= 0) {
            return 0;
        }

        return round($this->promotions_count / $yearsSinceGraduation, 2);
    }

    // Static methods
    public static function getPathTypes(): array
    {
        return [
            self::PATH_LINEAR => 'Linear Career Progression',
            self::PATH_PIVOT => 'Career Pivot',
            self::PATH_ENTREPRENEURIAL => 'Entrepreneurial Path',
            self::PATH_PORTFOLIO => 'Portfolio Career',
            self::PATH_ACADEMIC => 'Academic Career',
        ];
    }
}
