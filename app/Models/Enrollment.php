<?php
// ABOUTME: Enrollment model for schema-based multi-tenancy managing student-course relationships
// ABOUTME: Handles enrollment data within tenant schemas with status tracking, grade management, and validation

namespace App\Models;

use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class Enrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'course_id',
        'enrollment_date',
        'status',
        'grade',
        'grade_points',
        'credits_earned',
        'completion_date',
        'dropped_date',
        'drop_reason',
        'semester',
        'academic_year',
        'payment_status',
        'payment_amount',
        'notes',
        'metadata'
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'completion_date' => 'date',
        'dropped_date' => 'date',
        'grade_points' => 'decimal:2',
        'credits_earned' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'metadata' => 'array'
    ];

    protected $dates = [
        'deleted_at'
    ];

    protected $appends = [
        'is_active',
        'is_completed',
        'is_dropped',
        'duration_days',
        'current_tenant'
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DROPPED = 'dropped';
    const STATUS_WITHDRAWN = 'withdrawn';
    const STATUS_FAILED = 'failed';
    const STATUS_PENDING = 'pending';

    // Grade constants
    const GRADE_A_PLUS = 'A+';
    const GRADE_A = 'A';
    const GRADE_A_MINUS = 'A-';
    const GRADE_B_PLUS = 'B+';
    const GRADE_B = 'B';
    const GRADE_B_MINUS = 'B-';
    const GRADE_C_PLUS = 'C+';
    const GRADE_C = 'C';
    const GRADE_C_MINUS = 'C-';
    const GRADE_D_PLUS = 'D+';
    const GRADE_D = 'D';
    const GRADE_F = 'F';
    const GRADE_INCOMPLETE = 'I';
    const GRADE_WITHDRAW = 'W';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure we're in a tenant context
        static::addGlobalScope('tenant_context', function (Builder $builder) {
            if (!TenantContextService::hasTenant()) {
                throw new Exception('Enrollment model requires tenant context. Use TenantContextService::setTenant() first.');
            }
        });

        // Auto-set enrollment date if not provided
        static::creating(function ($enrollment) {
            if (empty($enrollment->enrollment_date)) {
                $enrollment->enrollment_date = now();
            }
            
            // Set default status
            if (empty($enrollment->status)) {
                $enrollment->status = self::STATUS_PENDING;
            }

            // Set academic year and semester if not provided
            if (empty($enrollment->academic_year)) {
                $enrollment->academic_year = static::getCurrentAcademicYear();
            }
            
            if (empty($enrollment->semester)) {
                $enrollment->semester = static::getCurrentSemester();
            }
        });

        // Auto-calculate grade points when grade is set
        static::saving(function ($enrollment) {
            if ($enrollment->isDirty('grade') && $enrollment->grade) {
                $enrollment->grade_points = static::calculateGradePoints($enrollment->grade);
            }

            // Set completion date when status changes to completed
            if ($enrollment->isDirty('status') && $enrollment->status === self::STATUS_COMPLETED) {
                $enrollment->completion_date = now();
                
                // Set credits earned from course if not already set
                if (!$enrollment->credits_earned && $enrollment->course) {
                    $enrollment->credits_earned = $enrollment->course->credits;
                }
            }

            // Set dropped date when status changes to dropped/withdrawn
            if ($enrollment->isDirty('status') && in_array($enrollment->status, [self::STATUS_DROPPED, self::STATUS_WITHDRAWN])) {
                $enrollment->dropped_date = now();
                $enrollment->credits_earned = 0;
            }
        });

        // Log enrollment activities
        static::created(function ($enrollment) {
            $enrollment->logActivity('created', 'Enrollment created');
        });

        static::updated(function ($enrollment) {
            $enrollment->logActivity('updated', 'Enrollment updated');
        });

        static::deleted(function ($enrollment) {
            $enrollment->logActivity('deleted', 'Enrollment deleted');
        });
    }

    /**
     * Get the student that owns the enrollment
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the course that owns the enrollment
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the grades for this enrollment
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Get the attendance records for this enrollment
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * Check if enrollment is active
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if enrollment is completed
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if enrollment is dropped
     */
    public function getIsDroppedAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_DROPPED, self::STATUS_WITHDRAWN]);
    }

    /**
     * Get enrollment duration in days
     */
    public function getDurationDaysAttribute(): ?int
    {
        if (!$this->enrollment_date) {
            return null;
        }

        $endDate = $this->completion_date ?? $this->dropped_date ?? now();
        return $this->enrollment_date->diffInDays($endDate);
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
     * Calculate grade points from letter grade
     */
    public static function calculateGradePoints(string $grade): float
    {
        $gradePoints = [
            self::GRADE_A_PLUS => 4.0,
            self::GRADE_A => 4.0,
            self::GRADE_A_MINUS => 3.7,
            self::GRADE_B_PLUS => 3.3,
            self::GRADE_B => 3.0,
            self::GRADE_B_MINUS => 2.7,
            self::GRADE_C_PLUS => 2.3,
            self::GRADE_C => 2.0,
            self::GRADE_C_MINUS => 1.7,
            self::GRADE_D_PLUS => 1.3,
            self::GRADE_D => 1.0,
            self::GRADE_F => 0.0,
            self::GRADE_INCOMPLETE => 0.0,
            self::GRADE_WITHDRAW => 0.0
        ];

        return $gradePoints[$grade] ?? 0.0;
    }

    /**
     * Get current academic year
     */
    public static function getCurrentAcademicYear(): string
    {
        $now = Carbon::now();
        $year = $now->year;
        
        // Academic year typically starts in August/September
        if ($now->month >= 8) {
            return $year . '-' . ($year + 1);
        } else {
            return ($year - 1) . '-' . $year;
        }
    }

    /**
     * Get current semester
     */
    public static function getCurrentSemester(): string
    {
        $now = Carbon::now();
        $month = $now->month;
        
        if ($month >= 8 && $month <= 12) {
            return 'Fall';
        } elseif ($month >= 1 && $month <= 5) {
            return 'Spring';
        } else {
            return 'Summer';
        }
    }

    /**
     * Get all available statuses
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_DROPPED => 'Dropped',
            self::STATUS_WITHDRAWN => 'Withdrawn',
            self::STATUS_FAILED => 'Failed'
        ];
    }

    /**
     * Get all available grades
     */
    public static function getAvailableGrades(): array
    {
        return [
            self::GRADE_A_PLUS => 'A+ (4.0)',
            self::GRADE_A => 'A (4.0)',
            self::GRADE_A_MINUS => 'A- (3.7)',
            self::GRADE_B_PLUS => 'B+ (3.3)',
            self::GRADE_B => 'B (3.0)',
            self::GRADE_B_MINUS => 'B- (2.7)',
            self::GRADE_C_PLUS => 'C+ (2.3)',
            self::GRADE_C => 'C (2.0)',
            self::GRADE_C_MINUS => 'C- (1.7)',
            self::GRADE_D_PLUS => 'D+ (1.3)',
            self::GRADE_D => 'D (1.0)',
            self::GRADE_F => 'F (0.0)',
            self::GRADE_INCOMPLETE => 'I (Incomplete)',
            self::GRADE_WITHDRAW => 'W (Withdraw)'
        ];
    }

    /**
     * Scope for active enrollments
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for completed enrollments
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for dropped enrollments
     */
    public function scopeDropped(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_DROPPED, self::STATUS_WITHDRAWN]);
    }

    /**
     * Scope for current semester
     */
    public function scopeCurrentSemester(Builder $query): Builder
    {
        return $query->where('semester', static::getCurrentSemester())
                    ->where('academic_year', static::getCurrentAcademicYear());
    }

    /**
     * Scope for specific semester
     */
    public function scopeForSemester(Builder $query, string $semester, string $academicYear): Builder
    {
        return $query->where('semester', $semester)
                    ->where('academic_year', $academicYear);
    }

    /**
     * Scope for enrollments with grades
     */
    public function scopeWithGrades(Builder $query): Builder
    {
        return $query->whereNotNull('grade')
                    ->whereNotNull('grade_points');
    }

    /**
     * Scope for passing grades
     */
    public function scopePassing(Builder $query): Builder
    {
        return $query->where('grade_points', '>=', 2.0)
                    ->whereNotIn('grade', [self::GRADE_F, self::GRADE_INCOMPLETE, self::GRADE_WITHDRAW]);
    }

    /**
     * Complete the enrollment with a grade
     */
    public function complete(string $grade, float $creditsEarned = null): bool
    {
        $this->grade = $grade;
        $this->grade_points = static::calculateGradePoints($grade);
        $this->status = self::STATUS_COMPLETED;
        $this->completion_date = now();
        
        if ($creditsEarned !== null) {
            $this->credits_earned = $creditsEarned;
        } elseif (!$this->credits_earned && $this->course) {
            $this->credits_earned = $this->course->credits;
        }

        $saved = $this->save();
        
        if ($saved) {
            $this->logActivity('completed', "Enrollment completed with grade: {$grade}", [
                'grade' => $grade,
                'grade_points' => $this->grade_points,
                'credits_earned' => $this->credits_earned
            ]);
        }

        return $saved;
    }

    /**
     * Drop the enrollment
     */
    public function drop(string $reason = null): bool
    {
        $this->status = self::STATUS_DROPPED;
        $this->dropped_date = now();
        $this->drop_reason = $reason;
        $this->credits_earned = 0;

        $saved = $this->save();
        
        if ($saved) {
            $this->logActivity('dropped', 'Enrollment dropped', [
                'reason' => $reason,
                'dropped_date' => $this->dropped_date->toDateString()
            ]);
        }

        return $saved;
    }

    /**
     * Withdraw from the enrollment
     */
    public function withdraw(string $reason = null): bool
    {
        $this->status = self::STATUS_WITHDRAWN;
        $this->dropped_date = now();
        $this->drop_reason = $reason;
        $this->grade = self::GRADE_WITHDRAW;
        $this->grade_points = 0.0;
        $this->credits_earned = 0;

        $saved = $this->save();
        
        if ($saved) {
            $this->logActivity('withdrawn', 'Enrollment withdrawn', [
                'reason' => $reason,
                'withdrawn_date' => $this->dropped_date->toDateString()
            ]);
        }

        return $saved;
    }

    /**
     * Activate pending enrollment
     */
    public function activate(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new Exception('Only pending enrollments can be activated');
        }

        $this->status = self::STATUS_ACTIVE;
        $saved = $this->save();
        
        if ($saved) {
            $this->logActivity('activated', 'Enrollment activated');
        }

        return $saved;
    }

    /**
     * Get enrollment statistics for a student
     */
    public static function getStudentStatistics(Student $student): array
    {
        $enrollments = static::where('student_id', $student->id)->get();
        $completedEnrollments = $enrollments->where('status', self::STATUS_COMPLETED);
        $activeEnrollments = $enrollments->where('status', self::STATUS_ACTIVE);
        
        $totalCreditsAttempted = $enrollments->sum('course.credits');
        $totalCreditsEarned = $completedEnrollments->sum('credits_earned');
        $gradePoints = $completedEnrollments->where('grade_points', '>', 0);
        
        $gpa = $gradePoints->count() > 0 
            ? $gradePoints->avg('grade_points') 
            : 0;

        return [
            'total_enrollments' => $enrollments->count(),
            'active_enrollments' => $activeEnrollments->count(),
            'completed_enrollments' => $completedEnrollments->count(),
            'dropped_enrollments' => $enrollments->whereIn('status', [self::STATUS_DROPPED, self::STATUS_WITHDRAWN])->count(),
            'total_credits_attempted' => $totalCreditsAttempted,
            'total_credits_earned' => $totalCreditsEarned,
            'gpa' => round($gpa, 2),
            'completion_rate' => $enrollments->count() > 0 
                ? ($completedEnrollments->count() / $enrollments->count() * 100) 
                : 0
        ];
    }

    /**
     * Get enrollment statistics for a course
     */
    public static function getCourseStatistics(Course $course): array
    {
        $enrollments = static::where('course_id', $course->id)->get();
        $completedEnrollments = $enrollments->where('status', self::STATUS_COMPLETED);
        $grades = $completedEnrollments->whereNotNull('grade_points');
        
        return [
            'total_enrollments' => $enrollments->count(),
            'active_enrollments' => $enrollments->where('status', self::STATUS_ACTIVE)->count(),
            'completed_enrollments' => $completedEnrollments->count(),
            'dropped_enrollments' => $enrollments->whereIn('status', [self::STATUS_DROPPED, self::STATUS_WITHDRAWN])->count(),
            'average_grade' => $grades->avg('grade_points') ?: 0,
            'pass_rate' => $grades->count() > 0 
                ? ($grades->where('grade_points', '>=', 2.0)->count() / $grades->count() * 100) 
                : 0,
            'completion_rate' => $enrollments->count() > 0 
                ? ($completedEnrollments->count() / $enrollments->count() * 100) 
                : 0
        ];
    }

    /**
     * Get semester enrollment statistics
     */
    public static function getSemesterStatistics(string $semester = null, string $academicYear = null): array
    {
        $query = static::query();
        
        if ($semester && $academicYear) {
            $query->forSemester($semester, $academicYear);
        } else {
            $query->currentSemester();
        }
        
        $enrollments = $query->get();
        $completedEnrollments = $enrollments->where('status', self::STATUS_COMPLETED);
        $grades = $completedEnrollments->whereNotNull('grade_points');
        
        return [
            'semester' => $semester ?: static::getCurrentSemester(),
            'academic_year' => $academicYear ?: static::getCurrentAcademicYear(),
            'total_enrollments' => $enrollments->count(),
            'active_enrollments' => $enrollments->where('status', self::STATUS_ACTIVE)->count(),
            'completed_enrollments' => $completedEnrollments->count(),
            'dropped_enrollments' => $enrollments->whereIn('status', [self::STATUS_DROPPED, self::STATUS_WITHDRAWN])->count(),
            'average_gpa' => $grades->avg('grade_points') ?: 0,
            'total_credits_earned' => $completedEnrollments->sum('credits_earned'),
            'completion_rate' => $enrollments->count() > 0 
                ? ($completedEnrollments->count() / $enrollments->count() * 100) 
                : 0
        ];
    }

    /**
     * Bulk update enrollment statuses
     */
    public static function bulkUpdateStatus(array $enrollmentIds, string $status, array $additionalData = []): int
    {
        $updateData = array_merge(['status' => $status], $additionalData);
        
        // Add status-specific fields
        if ($status === self::STATUS_COMPLETED) {
            $updateData['completion_date'] = now();
        } elseif (in_array($status, [self::STATUS_DROPPED, self::STATUS_WITHDRAWN])) {
            $updateData['dropped_date'] = now();
            $updateData['credits_earned'] = 0;
        }
        
        return static::whereIn('id', $enrollmentIds)->update($updateData);
    }

    /**
     * Log enrollment activity
     */
    public function logActivity(string $action, string $description, array $metadata = []): void
    {
        try {
            ActivityLog::create([
                'enrollment_id' => $this->id,
                'student_id' => $this->student_id,
                'course_id' => $this->course_id,
                'user_id' => auth()->id(),
                'action' => $action,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => array_merge($metadata, [
                    'enrollment_status' => $this->status,
                    'enrollment_date' => $this->enrollment_date?->toDateString()
                ])
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to log enrollment activity', [
                'enrollment_id' => $this->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate enrollment data integrity
     */
    public function validateDataIntegrity(): array
    {
        $errors = [];

        // Check if student exists
        if (!$this->student) {
            $errors[] = "Enrollment references non-existent student ID: {$this->student_id}";
        }

        // Check if course exists
        if (!$this->course) {
            $errors[] = "Enrollment references non-existent course ID: {$this->course_id}";
        }

        // Check date consistency
        if ($this->completion_date && $this->enrollment_date && $this->completion_date < $this->enrollment_date) {
            $errors[] = "Completion date is before enrollment date";
        }

        if ($this->dropped_date && $this->enrollment_date && $this->dropped_date < $this->enrollment_date) {
            $errors[] = "Dropped date is before enrollment date";
        }

        // Check status consistency
        if ($this->status === self::STATUS_COMPLETED && !$this->completion_date) {
            $errors[] = "Completed enrollment missing completion date";
        }

        if (in_array($this->status, [self::STATUS_DROPPED, self::STATUS_WITHDRAWN]) && !$this->dropped_date) {
            $errors[] = "Dropped/withdrawn enrollment missing dropped date";
        }

        // Check grade consistency
        if ($this->grade && $this->grade_points !== static::calculateGradePoints($this->grade)) {
            $errors[] = "Grade points do not match letter grade";
        }

        return $errors;
    }
}