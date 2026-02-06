<?php
// ABOUTME: Eloquent model for global_courses table in hybrid tenancy architecture
// ABOUTME: Manages the global course catalog that can be shared across multiple tenants

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class GlobalCourse extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'global_courses';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'global_course_code',
        'title',
        'description',
        'credit_hours',
        'level',
        'subject_area',
        'prerequisites',
        'learning_outcomes',
        'competencies',
        'delivery_method',
        'typical_duration_weeks',
        'typical_workload_hours_per_week',
        'difficulty_level',
        'tags',
        'metadata',
        'is_active',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'prerequisites' => 'array',
        'learning_outcomes' => 'array',
        'competencies' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'typical_workload_hours_per_week' => 'decimal:1',
    ];

    /**
     * Available course levels.
     */
    public const LEVELS = [
        'undergraduate' => 'Undergraduate',
        'graduate' => 'Graduate',
        'certificate' => 'Certificate',
        'continuing_education' => 'Continuing Education',
    ];

    /**
     * Available delivery methods.
     */
    public const DELIVERY_METHODS = [
        'in_person' => 'In Person',
        'online' => 'Online',
        'hybrid' => 'Hybrid',
        'self_paced' => 'Self Paced',
    ];

    /**
     * Available difficulty levels.
     */
    public const DIFFICULTY_LEVELS = [
        'beginner' => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced' => 'Advanced',
        'expert' => 'Expert',
    ];

    /**
     * Common subject areas.
     */
    public const SUBJECT_AREAS = [
        'Mathematics',
        'English',
        'Computer Science',
        'Biology',
        'Chemistry',
        'Physics',
        'History',
        'Psychology',
        'Business',
        'Economics',
        'Art',
        'Music',
        'Philosophy',
        'Political Science',
        'Sociology',
        'Engineering',
        'Medicine',
        'Law',
        'Education',
        'Communications',
    ];

    /**
     * Get the user who created this course.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(GlobalUser::class, 'created_by', 'global_user_id');
    }

    /**
     * Get the tenant course offerings for this global course.
     */
    public function tenantOfferings(): HasMany
    {
        return $this->hasMany(TenantCourseOffering::class, 'global_course_id');
    }

    /**
     * Get active tenant course offerings.
     */
    public function activeOfferings(): HasMany
    {
        return $this->tenantOfferings()->whereIn('status', ['published', 'enrollment_open', 'in_progress']);
    }

    /**
     * Get the level display name.
     */
    public function getLevelDisplayNameAttribute(): string
    {
        return self::LEVELS[$this->level] ?? ucfirst($this->level);
    }

    /**
     * Get the delivery method display name.
     */
    public function getDeliveryMethodDisplayNameAttribute(): string
    {
        return self::DELIVERY_METHODS[$this->delivery_method] ?? ucfirst(str_replace('_', ' ', $this->delivery_method));
    }

    /**
     * Get the difficulty level display name.
     */
    public function getDifficultyLevelDisplayNameAttribute(): string
    {
        return self::DIFFICULTY_LEVELS[$this->difficulty_level] ?? ucfirst($this->difficulty_level);
    }

    /**
     * Get the estimated total workload hours.
     */
    public function getTotalWorkloadHoursAttribute(): ?float
    {
        if ($this->typical_duration_weeks && $this->typical_workload_hours_per_week) {
            return $this->typical_duration_weeks * $this->typical_workload_hours_per_week;
        }
        return null;
    }

    /**
     * Check if this course has prerequisites.
     */
    public function hasPrerequisites(): bool
    {
        return !empty($this->prerequisites);
    }

    /**
     * Get prerequisite courses.
     */
    public function getPrerequisiteCourses()
    {
        if (!$this->hasPrerequisites()) {
            return collect();
        }

        return self::whereIn('global_course_code', $this->prerequisites)
                   ->where('is_active', true)
                   ->get();
    }

    /**
     * Get courses that have this course as a prerequisite.
     */
    public function getDependentCourses()
    {
        return self::where('is_active', true)
                   ->whereJsonContains('prerequisites', $this->global_course_code)
                   ->get();
    }

    /**
     * Check if a user meets the prerequisites for this course.
     */
    public function userMeetsPrerequisites(string $globalUserId, string $tenantId): bool
    {
        if (!$this->hasPrerequisites()) {
            return true;
        }

        // This would need to be implemented based on your enrollment/completion tracking
        // For now, we'll return true as a placeholder
        return true;
    }

    /**
     * Get the number of tenants offering this course.
     */
    public function getTenantOfferingsCountAttribute(): int
    {
        return $this->activeOfferings()->distinct('tenant_id')->count('tenant_id');
    }

    /**
     * Get the total enrollment across all tenant offerings.
     */
    public function getTotalEnrollmentAttribute(): int
    {
        return $this->activeOfferings()->sum('current_enrollment');
    }

    /**
     * Scope to filter active courses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by level.
     */
    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope to filter by subject area.
     */
    public function scopeSubjectArea($query, string $subjectArea)
    {
        return $query->where('subject_area', $subjectArea);
    }

    /**
     * Scope to filter by delivery method.
     */
    public function scopeDeliveryMethod($query, string $deliveryMethod)
    {
        return $query->where('delivery_method', $deliveryMethod);
    }

    /**
     * Scope to filter by difficulty level.
     */
    public function scopeDifficultyLevel($query, string $difficultyLevel)
    {
        return $query->where('difficulty_level', $difficultyLevel);
    }

    /**
     * Scope to filter by credit hours range.
     */
    public function scopeCreditHours($query, int $min = null, int $max = null)
    {
        if ($min !== null) {
            $query->where('credit_hours', '>=', $min);
        }
        if ($max !== null) {
            $query->where('credit_hours', '<=', $max);
        }
        return $query;
    }

    /**
     * Scope to search courses by title, description, or tags.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'ILIKE', "%{$search}%")
              ->orWhere('description', 'ILIKE', "%{$search}%")
              ->orWhere('global_course_code', 'ILIKE', "%{$search}%")
              ->orWhere('subject_area', 'ILIKE', "%{$search}%")
              ->orWhereJsonContains('tags', $search);
        });
    }

    /**
     * Scope to filter courses with specific tags.
     */
    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Scope to filter courses available in a specific tenant.
     */
    public function scopeAvailableInTenant($query, string $tenantId)
    {
        return $query->whereHas('activeOfferings', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        });
    }

    /**
     * Scope to get popular courses (most offered).
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->withCount('activeOfferings')
                     ->orderBy('active_offerings_count', 'desc')
                     ->limit($limit);
    }

    /**
     * Scope to get recently created courses.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days))
                     ->orderBy('created_at', 'desc');
    }

    /**
     * Add a tag to the course.
     */
    public function addTag(string $tag): bool
    {
        $tags = $this->tags ?? [];
        
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            return $this->update(['tags' => $tags]);
        }
        
        return true;
    }

    /**
     * Remove a tag from the course.
     */
    public function removeTag(string $tag): bool
    {
        $tags = $this->tags ?? [];
        $tags = array_filter($tags, fn($t) => $t !== $tag);
        
        return $this->update(['tags' => array_values($tags)]);
    }

    /**
     * Get course statistics.
     */
    public function getStatistics(): array
    {
        $offerings = $this->activeOfferings;
        
        return [
            'total_offerings' => $offerings->count(),
            'unique_tenants' => $offerings->unique('tenant_id')->count(),
            'total_enrollment' => $offerings->sum('current_enrollment'),
            'average_enrollment' => $offerings->avg('current_enrollment'),
            'max_enrollment' => $offerings->max('current_enrollment'),
            'total_capacity' => $offerings->sum('max_enrollment'),
            'utilization_rate' => $offerings->sum('max_enrollment') > 0 
                ? ($offerings->sum('current_enrollment') / $offerings->sum('max_enrollment')) * 100 
                : 0,
            'average_tuition' => $offerings->whereNotNull('tuition_cost')->avg('tuition_cost'),
            'delivery_methods' => $offerings->groupBy('delivery_method')->map->count()->toArray(),
        ];
    }

    /**
     * Clone this course for a specific tenant with customizations.
     */
    public function createTenantOffering(string $tenantId, array $customizations = []): TenantCourseOffering
    {
        $defaultData = [
            'tenant_id' => $tenantId,
            'global_course_id' => $this->id,
            'local_course_code' => $customizations['local_course_code'] ?? $this->global_course_code,
            'local_title' => $customizations['local_title'] ?? null,
            'local_description' => $customizations['local_description'] ?? null,
            'local_credit_hours' => $customizations['local_credit_hours'] ?? null,
            'semester' => $customizations['semester'] ?? 'Fall',
            'year' => $customizations['year'] ?? date('Y'),
            'status' => 'draft',
        ];

        return TenantCourseOffering::create(array_merge($defaultData, $customizations));
    }

    /**
     * Get recommended courses based on this course.
     */
    public function getRecommendedCourses(int $limit = 5)
    {
        return self::active()
                   ->where('id', '!=', $this->id)
                   ->where(function ($query) {
                       $query->where('subject_area', $this->subject_area)
                             ->orWhere('level', $this->level)
                             ->orWhere('difficulty_level', $this->difficulty_level);
                   })
                   ->when($this->tags, function ($query) {
                       foreach ($this->tags as $tag) {
                           $query->orWhereJsonContains('tags', $tag);
                       }
                   })
                   ->limit($limit)
                   ->get();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Create audit trail entries
        static::created(function ($model) {
            AuditTrail::create([
                'global_user_id' => $model->created_by,
                'table_name' => 'global_courses',
                'record_id' => $model->id,
                'operation' => 'create',
                'new_values' => $model->getAttributes(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::updated(function ($model) {
            AuditTrail::create([
                'global_user_id' => $model->created_by,
                'table_name' => 'global_courses',
                'record_id' => $model->id,
                'operation' => 'update',
                'old_values' => $model->getOriginal(),
                'new_values' => $model->getAttributes(),
                'changed_fields' => array_keys($model->getDirty()),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::deleted(function ($model) {
            AuditTrail::create([
                'global_user_id' => $model->created_by,
                'table_name' => 'global_courses',
                'record_id' => $model->id,
                'operation' => 'delete',
                'old_values' => $model->getOriginal(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }
}