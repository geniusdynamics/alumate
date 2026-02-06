<?php
// ABOUTME: Eloquent model for tenant_course_offerings table in hybrid tenancy architecture
// ABOUTME: Manages tenant-specific course offerings that reference global courses with local customizations

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class TenantCourseOffering extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'tenant_course_offerings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'global_course_id',
        'local_course_code',
        'local_title',
        'local_description',
        'local_credit_hours',
        'instructor_id',
        'semester',
        'year',
        'start_date',
        'end_date',
        'enrollment_start_date',
        'enrollment_end_date',
        'max_enrollment',
        'current_enrollment',
        'waitlist_capacity',
        'current_waitlist',
        'tuition_cost',
        'fees',
        'delivery_method',
        'location',
        'meeting_times',
        'syllabus_url',
        'materials_list',
        'grading_policy',
        'attendance_policy',
        'prerequisites_override',
        'local_learning_outcomes',
        'assessment_methods',
        'status',
        'notes',
        'metadata',
        'created_by',
        'last_modified_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'enrollment_start_date' => 'datetime',
        'enrollment_end_date' => 'datetime',
        'tuition_cost' => 'decimal:2',
        'fees' => 'decimal:2',
        'meeting_times' => 'array',
        'materials_list' => 'array',
        'prerequisites_override' => 'array',
        'local_learning_outcomes' => 'array',
        'assessment_methods' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Available course statuses.
     */
    public const STATUSES = [
        'draft' => 'Draft',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'published' => 'Published',
        'enrollment_open' => 'Enrollment Open',
        'enrollment_closed' => 'Enrollment Closed',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'suspended' => 'Suspended',
    ];

    /**
     * Available semesters.
     */
    public const SEMESTERS = [
        'spring' => 'Spring',
        'summer' => 'Summer',
        'fall' => 'Fall',
        'winter' => 'Winter',
        'year_round' => 'Year Round',
    ];

    /**
     * Available delivery methods (can override global course).
     */
    public const DELIVERY_METHODS = [
        'in_person' => 'In Person',
        'online' => 'Online',
        'hybrid' => 'Hybrid',
        'self_paced' => 'Self Paced',
        'synchronous_online' => 'Synchronous Online',
        'asynchronous_online' => 'Asynchronous Online',
    ];

    /**
     * Get the tenant this offering belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the global course this offering is based on.
     */
    public function globalCourse(): BelongsTo
    {
        return $this->belongsTo(GlobalCourse::class, 'global_course_id');
    }

    /**
     * Get the instructor for this offering.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(GlobalUser::class, 'instructor_id', 'global_user_id');
    }

    /**
     * Get the user who created this offering.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(GlobalUser::class, 'created_by', 'global_user_id');
    }

    /**
     * Get the user who last modified this offering.
     */
    public function lastModifier(): BelongsTo
    {
        return $this->belongsTo(GlobalUser::class, 'last_modified_by', 'global_user_id');
    }

    /**
     * Get the effective title (local or global).
     */
    public function getEffectiveTitleAttribute(): string
    {
        return $this->local_title ?? $this->globalCourse?->title ?? 'Unknown Course';
    }

    /**
     * Get the effective description (local or global).
     */
    public function getEffectiveDescriptionAttribute(): ?string
    {
        return $this->local_description ?? $this->globalCourse?->description;
    }

    /**
     * Get the effective credit hours (local or global).
     */
    public function getEffectiveCreditHoursAttribute(): ?int
    {
        return $this->local_credit_hours ?? $this->globalCourse?->credit_hours;
    }

    /**
     * Get the effective delivery method (local or global).
     */
    public function getEffectiveDeliveryMethodAttribute(): ?string
    {
        return $this->delivery_method ?? $this->globalCourse?->delivery_method;
    }

    /**
     * Get the effective prerequisites (override or global).
     */
    public function getEffectivePrerequisitesAttribute(): array
    {
        return $this->prerequisites_override ?? $this->globalCourse?->prerequisites ?? [];
    }

    /**
     * Get the effective learning outcomes (local or global).
     */
    public function getEffectiveLearningOutcomesAttribute(): array
    {
        return $this->local_learning_outcomes ?? $this->globalCourse?->learning_outcomes ?? [];
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * Get the semester display name.
     */
    public function getSemesterDisplayNameAttribute(): string
    {
        return self::SEMESTERS[$this->semester] ?? ucfirst($this->semester);
    }

    /**
     * Get the delivery method display name.
     */
    public function getDeliveryMethodDisplayNameAttribute(): string
    {
        $method = $this->effective_delivery_method;
        return self::DELIVERY_METHODS[$method] ?? ucfirst(str_replace('_', ' ', $method));
    }

    /**
     * Get the full course identifier.
     */
    public function getFullCourseCodeAttribute(): string
    {
        return $this->local_course_code . ' - ' . $this->semester_display_name . ' ' . $this->year;
    }

    /**
     * Get the enrollment capacity utilization percentage.
     */
    public function getEnrollmentUtilizationAttribute(): float
    {
        if ($this->max_enrollment <= 0) {
            return 0;
        }
        return ($this->current_enrollment / $this->max_enrollment) * 100;
    }

    /**
     * Get the waitlist utilization percentage.
     */
    public function getWaitlistUtilizationAttribute(): float
    {
        if ($this->waitlist_capacity <= 0) {
            return 0;
        }
        return ($this->current_waitlist / $this->waitlist_capacity) * 100;
    }

    /**
     * Get the total cost (tuition + fees).
     */
    public function getTotalCostAttribute(): float
    {
        return ($this->tuition_cost ?? 0) + ($this->fees ?? 0);
    }

    /**
     * Get the course duration in weeks.
     */
    public function getDurationWeeksAttribute(): ?int
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->diffInWeeks($this->end_date);
        }
        return null;
    }

    /**
     * Check if enrollment is currently open.
     */
    public function isEnrollmentOpen(): bool
    {
        $now = now();
        
        return $this->status === 'enrollment_open' &&
               ($this->enrollment_start_date === null || $now >= $this->enrollment_start_date) &&
               ($this->enrollment_end_date === null || $now <= $this->enrollment_end_date) &&
               $this->current_enrollment < $this->max_enrollment;
    }

    /**
     * Check if waitlist is available.
     */
    public function isWaitlistAvailable(): bool
    {
        return $this->waitlist_capacity > 0 && 
               $this->current_waitlist < $this->waitlist_capacity &&
               $this->current_enrollment >= $this->max_enrollment;
    }

    /**
     * Check if the course is currently in session.
     */
    public function isInSession(): bool
    {
        $now = now()->toDateString();
        
        return $this->status === 'in_progress' &&
               ($this->start_date === null || $now >= $this->start_date->toDateString()) &&
               ($this->end_date === null || $now <= $this->end_date->toDateString());
    }

    /**
     * Check if the course has been completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed' || 
               ($this->end_date && now() > $this->end_date);
    }

    /**
     * Check if the course can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'under_review', 'approved', 'published', 'enrollment_open']);
    }

    /**
     * Get available spots for enrollment.
     */
    public function getAvailableSpotsAttribute(): int
    {
        return max(0, $this->max_enrollment - $this->current_enrollment);
    }

    /**
     * Get available waitlist spots.
     */
    public function getAvailableWaitlistSpotsAttribute(): int
    {
        return max(0, $this->waitlist_capacity - $this->current_waitlist);
    }

    /**
     * Scope to filter by tenant.
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by semester and year.
     */
    public function scopeSemesterYear($query, string $semester, int $year)
    {
        return $query->where('semester', $semester)->where('year', $year);
    }

    /**
     * Scope to filter by year.
     */
    public function scopeYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope to filter active offerings.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['published', 'enrollment_open', 'enrollment_closed', 'in_progress']);
    }

    /**
     * Scope to filter offerings with open enrollment.
     */
    public function scopeEnrollmentOpen($query)
    {
        $now = now();
        
        return $query->where('status', 'enrollment_open')
                     ->where(function ($q) use ($now) {
                         $q->whereNull('enrollment_start_date')
                           ->orWhere('enrollment_start_date', '<=', $now);
                     })
                     ->where(function ($q) use ($now) {
                         $q->whereNull('enrollment_end_date')
                           ->orWhere('enrollment_end_date', '>=', $now);
                     })
                     ->whereRaw('current_enrollment < max_enrollment');
    }

    /**
     * Scope to filter offerings with available waitlist.
     */
    public function scopeWaitlistAvailable($query)
    {
        return $query->where('waitlist_capacity', '>', 0)
                     ->whereRaw('current_waitlist < waitlist_capacity')
                     ->whereRaw('current_enrollment >= max_enrollment');
    }

    /**
     * Scope to filter by instructor.
     */
    public function scopeByInstructor($query, string $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    /**
     * Scope to filter by delivery method.
     */
    public function scopeDeliveryMethod($query, string $deliveryMethod)
    {
        return $query->where('delivery_method', $deliveryMethod);
    }

    /**
     * Scope to search offerings.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('local_course_code', 'ILIKE', "%{$search}%")
              ->orWhere('local_title', 'ILIKE', "%{$search}%")
              ->orWhere('local_description', 'ILIKE', "%{$search}%")
              ->orWhereHas('globalCourse', function ($gq) use ($search) {
                  $gq->where('title', 'ILIKE', "%{$search}%")
                     ->orWhere('global_course_code', 'ILIKE', "%{$search}%")
                     ->orWhere('description', 'ILIKE', "%{$search}%");
              });
        });
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, Carbon $startDate = null, Carbon $endDate = null)
    {
        if ($startDate) {
            $query->where('start_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('end_date', '<=', $endDate);
        }
        return $query;
    }

    /**
     * Scope to get upcoming offerings.
     */
    public function scopeUpcoming($query, int $days = 30)
    {
        return $query->where('start_date', '>=', now())
                     ->where('start_date', '<=', now()->addDays($days))
                     ->orderBy('start_date');
    }

    /**
     * Scope to get current offerings.
     */
    public function scopeCurrent($query)
    {
        $now = now()->toDateString();
        
        return $query->where('start_date', '<=', $now)
                     ->where('end_date', '>=', $now)
                     ->where('status', 'in_progress');
    }

    /**
     * Increment enrollment count.
     */
    public function incrementEnrollment(): bool
    {
        if ($this->current_enrollment < $this->max_enrollment) {
            return $this->increment('current_enrollment');
        }
        return false;
    }

    /**
     * Decrement enrollment count.
     */
    public function decrementEnrollment(): bool
    {
        if ($this->current_enrollment > 0) {
            return $this->decrement('current_enrollment');
        }
        return false;
    }

    /**
     * Increment waitlist count.
     */
    public function incrementWaitlist(): bool
    {
        if ($this->current_waitlist < $this->waitlist_capacity) {
            return $this->increment('current_waitlist');
        }
        return false;
    }

    /**
     * Decrement waitlist count.
     */
    public function decrementWaitlist(): bool
    {
        if ($this->current_waitlist > 0) {
            return $this->decrement('current_waitlist');
        }
        return false;
    }

    /**
     * Update the status with validation.
     */
    public function updateStatus(string $newStatus, string $userId = null): bool
    {
        if (!array_key_exists($newStatus, self::STATUSES)) {
            return false;
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;
        
        if ($userId) {
            $this->last_modified_by = $userId;
        }
        
        $result = $this->save();
        
        if ($result) {
            // Log status change
            AuditTrail::create([
                'global_user_id' => $userId,
                'table_name' => 'tenant_course_offerings',
                'record_id' => $this->id,
                'operation' => 'status_change',
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => $newStatus],
                'changed_fields' => ['status'],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'tenant_id' => $this->tenant_id,
                    'course_code' => $this->local_course_code,
                    'semester' => $this->semester,
                    'year' => $this->year,
                ],
            ]);
        }
        
        return $result;
    }

    /**
     * Get offering statistics.
     */
    public function getStatistics(): array
    {
        return [
            'enrollment_rate' => $this->enrollment_utilization,
            'waitlist_rate' => $this->waitlist_utilization,
            'available_spots' => $this->available_spots,
            'available_waitlist_spots' => $this->available_waitlist_spots,
            'total_cost' => $this->total_cost,
            'duration_weeks' => $this->duration_weeks,
            'is_enrollment_open' => $this->isEnrollmentOpen(),
            'is_waitlist_available' => $this->isWaitlistAvailable(),
            'is_in_session' => $this->isInSession(),
            'is_completed' => $this->isCompleted(),
            'days_until_start' => $this->start_date ? now()->diffInDays($this->start_date, false) : null,
            'days_until_end' => $this->end_date ? now()->diffInDays($this->end_date, false) : null,
        ];
    }

    /**
     * Clone this offering to another semester/year.
     */
    public function cloneToSemester(string $semester, int $year, array $overrides = []): self
    {
        $attributes = $this->getAttributes();
        
        // Remove unique identifiers and timestamps
        unset($attributes['id'], $attributes['created_at'], $attributes['updated_at'], $attributes['deleted_at']);
        
        // Update semester and year
        $attributes['semester'] = $semester;
        $attributes['year'] = $year;
        
        // Reset enrollment data
        $attributes['current_enrollment'] = 0;
        $attributes['current_waitlist'] = 0;
        $attributes['status'] = 'draft';
        
        // Clear dates that need to be reset
        $attributes['start_date'] = null;
        $attributes['end_date'] = null;
        $attributes['enrollment_start_date'] = null;
        $attributes['enrollment_end_date'] = null;
        
        // Apply any overrides
        $attributes = array_merge($attributes, $overrides);
        
        return self::create($attributes);
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
                'table_name' => 'tenant_course_offerings',
                'record_id' => $model->id,
                'operation' => 'create',
                'new_values' => $model->getAttributes(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'tenant_id' => $model->tenant_id,
                    'global_course_id' => $model->global_course_id,
                ],
            ]);
        });

        static::updated(function ($model) {
            AuditTrail::create([
                'global_user_id' => $model->last_modified_by ?? $model->created_by,
                'table_name' => 'tenant_course_offerings',
                'record_id' => $model->id,
                'operation' => 'update',
                'old_values' => $model->getOriginal(),
                'new_values' => $model->getAttributes(),
                'changed_fields' => array_keys($model->getDirty()),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'tenant_id' => $model->tenant_id,
                    'global_course_id' => $model->global_course_id,
                ],
            ]);
        });

        static::deleted(function ($model) {
            AuditTrail::create([
                'global_user_id' => $model->last_modified_by ?? $model->created_by,
                'table_name' => 'tenant_course_offerings',
                'record_id' => $model->id,
                'operation' => 'delete',
                'old_values' => $model->getOriginal(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'tenant_id' => $model->tenant_id,
                    'global_course_id' => $model->global_course_id,
                ],
            ]);
        });
    }
}