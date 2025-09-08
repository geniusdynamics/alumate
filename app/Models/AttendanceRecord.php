<?php
// ABOUTME: AttendanceRecord model for schema-based multi-tenancy managing student attendance
// ABOUTME: Handles attendance tracking within tenant schemas with status management and validation

namespace App\Models;

use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Exception;

class AttendanceRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'course_id',
        'enrollment_id',
        'attendance_date',
        'status',
        'check_in_time',
        'check_out_time',
        'notes',
        'recorded_by',
        'metadata'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'metadata' => 'array'
    ];

    protected $dates = [
        'deleted_at'
    ];

    protected $appends = [
        'is_present',
        'duration_minutes',
        'current_tenant'
    ];

    // Status constants
    const STATUS_PRESENT = 'present';
    const STATUS_ABSENT = 'absent';
    const STATUS_LATE = 'late';
    const STATUS_EXCUSED = 'excused';
    const STATUS_TARDY = 'tardy';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure we're in a tenant context
        static::addGlobalScope('tenant_context', function (Builder $builder) {
            if (!TenantContextService::hasTenant()) {
                throw new Exception('AttendanceRecord model requires tenant context. Use TenantContextService::setTenant() first.');
            }
        });

        // Auto-set attendance date if not provided
        static::creating(function ($record) {
            if (empty($record->attendance_date)) {
                $record->attendance_date = now()->toDateString();
            }
            
            // Set default status
            if (empty($record->status)) {
                $record->status = self::STATUS_PRESENT;
            }
        });

        // Log attendance activities
        static::created(function ($record) {
            $record->logActivity('created', 'Attendance record created');
        });

        static::updated(function ($record) {
            $record->logActivity('updated', 'Attendance record updated');
        });

        static::deleted(function ($record) {
            $record->logActivity('deleted', 'Attendance record deleted');
        });
    }

    /**
     * Get the student that owns the attendance record
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the course that owns the attendance record
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the enrollment that owns the attendance record
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Get the user who recorded the attendance
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Check if student is present
     */
    public function getIsPresentAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_PRESENT, self::STATUS_LATE, self::STATUS_TARDY]);
    }

    /**
     * Get duration in minutes
     */
    public function getDurationMinutesAttribute(): ?int
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return null;
        }

        return $this->check_in_time->diffInMinutes($this->check_out_time);
    }

    /**
     * Get current tenant information
     */
    public function getCurrentTenantAttribute(): ?array
    {
        $tenant = TenantContextService::getCurrentTenant();
        return $tenant ? [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'schema' => $tenant->schema_name
        ] : null;
    }

    /**
     * Log activity for this attendance record
     */
    protected function logActivity(string $action, string $description, array $properties = []): void
    {
        try {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'student_id' => $this->student_id,
                'course_id' => $this->course_id,
                'action' => $action,
                'description' => $description,
                'model_type' => self::class,
                'model_id' => $this->id,
                'properties' => array_merge($properties, [
                    'attendance_date' => $this->attendance_date,
                    'status' => $this->status
                ])
            ]);
        } catch (Exception $e) {
            // Log the error but don't fail the main operation
            \Log::error('Failed to log attendance activity: ' . $e->getMessage());
        }
    }

    /**
     * Scope to filter by status
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter present records
     */
    public function scopePresent(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PRESENT, self::STATUS_LATE, self::STATUS_TARDY]);
    }

    /**
     * Scope to filter absent records
     */
    public function scopeAbsent(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ABSENT);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeInDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('attendance_date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by student
     */
    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope to filter by course
     */
    public function scopeForCourse(Builder $query, int $courseId): Builder
    {
        return $query->where('course_id', $courseId);
    }
}