<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * LearningProgress Model
 *
 * Tracks individual user progress through courses, including completion status,
 * scores, and engagement metrics for learning analytics.
 */
class LearningProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'course_id',
        'module_id',
        'progress_percentage',
        'engagement_duration',
        'completion_timestamp',
        'certifications',
        'interactions_count',
        'modules_completed',
        'total_score',
        'engagement_score',
        'certified',
    ];

    protected $casts = [
        'module_id' => 'integer',
        'progress_percentage' => 'decimal:2',
        'engagement_duration' => 'integer',
        'completion_timestamp' => 'datetime',
        'certifications' => 'json',
        'interactions_count' => 'integer',
        'modules_completed' => 'integer',
        'total_score' => 'decimal:2',
        'engagement_score' => 'decimal:2',
        'certified' => 'boolean',
    ];

    /**
     * Get the user this progress belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course this progress belongs to
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get learning events for this progress
     */
    public function learningEvents(): HasMany
    {
        return $this->hasMany(LearningEvent::class);
    }

    /**
     * Scope by tenant
     */
    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope by course
     */
    public function scopeByCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope for users eligible for certification
     */
    public function scopeEligibleForCertification($query, array $criteria = [])
    {
        $minScore = $criteria['min_score'] ?? 80;
        $minModules = $criteria['modules_completed'] ?? 5;

        return $query->where('total_score', '>=', $minScore)
            ->where('modules_completed', '>=', $minModules)
            ->where('certified', false);
    }

    /**
     * Scope for certified users
     */
    public function scopeCertified($query)
    {
        return $query->where('certified', true);
    }

    /**
     * Scope by module
     */
    public function scopeByModule($query, int $moduleId)
    {
        return $query->where('module_id', $moduleId);
    }

    /**
     * Scope for incomplete progress (< 100%)
     */
    public function scopeIncomplete($query)
    {
        return $query->where('progress_percentage', '<', 100);
    }

    /**
     * Scope for completed progress (= 100%)
     */
    public function scopeCompleted($query)
    {
        return $query->where('progress_percentage', '=', 100);
    }

    /**
     * Scope by progress range
     */
    public function scopeByProgressRange($query, float $min, float $max)
    {
        return $query->whereBetween('progress_percentage', [$min, $max]);
    }

    /**
     * Scope by engagement duration range
     */
    public function scopeByEngagementRange($query, int $minMinutes, int $maxMinutes)
    {
        return $query->whereBetween('engagement_duration', [$minMinutes, $maxMinutes]);
    }

    /**
     * Check if user meets certification criteria
     */
    public function meetsCertificationCriteria(array $criteria = []): bool
    {
        $minScore = $criteria['min_score'] ?? 80;
        $minModules = $criteria['modules_completed'] ?? 5;

        return $this->total_score >= $minScore &&
               $this->modules_completed >= $minModules;
    }

    /**
     * Mark as certified
     */
    public function markAsCertified(): bool
    {
        return $this->update(['certified' => true]);
    }

    /**
     * Calculate completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        // Assuming course has modules_count, otherwise use a default
        $totalModules = $this->course->modules_count ?? 10;

        return $totalModules > 0 ? round(($this->modules_completed / $totalModules) * 100, 2) : 0;
    }
}
