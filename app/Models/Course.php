<?php
// ABOUTME: Course model for schema-based multi-tenancy supporting both global and tenant-specific courses
// ABOUTME: Handles course data within tenant schemas with relationships, validation, and global course integration

namespace App\Models;

use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Exception;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_code',
        'title',
        'description',
        'credits',
        'level',
        'department',
        'prerequisites',
        'status',
        'max_enrollment',
        'instructor_name',
        'instructor_email',
        'schedule',
        'location',
        'start_date',
        'end_date',
        'global_course_id',
        'is_custom',
        'syllabus_url',
        'metadata'
    ];

    protected $casts = [
        'prerequisites' => 'array',
        'schedule' => 'array',
        'metadata' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_custom' => 'boolean'
    ];

    protected $dates = [
        'deleted_at'
    ];

    protected $appends = [
        'enrollment_count',
        'available_spots',
        'is_full',
        'current_tenant',
        'is_global_course'
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure we're in a tenant context
        static::addGlobalScope('tenant_context', function (Builder $builder) {
            if (!TenantContextService::hasTenant()) {
                throw new Exception('Course model requires tenant context. Use TenantContextService::setTenant() first.');
            }
        });

        // Auto-generate course code if not provided
        static::creating(function ($course) {
            if (empty($course->course_code)) {
                $course->course_code = static::generateCourseCode($course->department, $course->level);
            }
        });

        // Log course activities
        static::created(function ($course) {
            $course->logActivity('created', 'Course created');
        });

        static::updated(function ($course) {
            $course->logActivity('updated', 'Course updated');
        });

        static::deleted(function ($course) {
            $course->logActivity('deleted', 'Course deleted');
        });
    }

    /**
     * Get the course's enrollments
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the course's grades
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Get the students enrolled in this course
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'enrollments')
            ->withPivot(['enrollment_date', 'status', 'grade', 'credits_earned'])
            ->withTimestamps();
    }

    /**
     * Get active enrollments
     */
    public function activeEnrollments(): HasMany
    {
        return $this->enrollments()->where('status', 'active');
    }

    /**
     * Get completed enrollments
     */
    public function completedEnrollments(): HasMany
    {
        return $this->enrollments()->where('status', 'completed');
    }

    /**
     * Get the global course this is based on (if any)
     */
    public function globalCourse()
    {
        if (!$this->global_course_id) {
            return null;
        }

        // Query the global courses table in public schema
        return DB::connection('pgsql')
            ->table('public.global_courses')
            ->where('id', $this->global_course_id)
            ->first();
    }

    /**
     * Get enrollment count attribute
     */
    public function getEnrollmentCountAttribute(): int
    {
        return $this->activeEnrollments()->count();
    }

    /**
     * Get available spots attribute
     */
    public function getAvailableSpotsAttribute(): int
    {
        if (!$this->max_enrollment) {
            return PHP_INT_MAX;
        }
        
        return max(0, $this->max_enrollment - $this->enrollment_count);
    }

    /**
     * Check if course is full
     */
    public function getIsFullAttribute(): bool
    {
        return $this->max_enrollment && $this->enrollment_count >= $this->max_enrollment;
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
     * Check if this is based on a global course
     */
    public function getIsGlobalCourseAttribute(): bool
    {
        return !is_null($this->global_course_id) && !$this->is_custom;
    }

    /**
     * Generate unique course code
     */
    public static function generateCourseCode(string $department = null, string $level = null): string
    {
        $department = $department ?: 'GEN';
        $level = $level ?: '100';
        
        $prefix = strtoupper(substr($department, 0, 3));
        $levelNum = preg_replace('/[^0-9]/', '', $level) ?: '100';
        
        do {
            $suffix = str_pad(random_int(1, 99), 2, '0', STR_PAD_LEFT);
            $courseCode = $prefix . $levelNum . $suffix;
        } while (static::where('course_code', $courseCode)->exists());
        
        return $courseCode;
    }

    /**
     * Create course from global course template
     */
    public static function createFromGlobalCourse(int $globalCourseId, array $overrides = []): self
    {
        // Get global course data
        $globalCourse = DB::connection('pgsql')
            ->table('public.global_courses')
            ->where('id', $globalCourseId)
            ->first();

        if (!$globalCourse) {
            throw new Exception("Global course with ID {$globalCourseId} not found");
        }

        // Convert to array and merge with overrides
        $courseData = array_merge([
            'course_code' => $globalCourse->course_code,
            'title' => $globalCourse->title,
            'description' => $globalCourse->description,
            'credits' => $globalCourse->credits,
            'level' => $globalCourse->level,
            'department' => $globalCourse->department,
            'prerequisites' => json_decode($globalCourse->prerequisites, true),
            'global_course_id' => $globalCourse->id,
            'is_custom' => false,
            'status' => 'active'
        ], $overrides);

        return static::create($courseData);
    }

    /**
     * Sync with global course updates
     */
    public function syncWithGlobalCourse(): bool
    {
        if (!$this->global_course_id || $this->is_custom) {
            return false;
        }

        $globalCourse = $this->globalCourse();
        if (!$globalCourse) {
            return false;
        }

        // Update fields that should sync (preserve tenant-specific customizations)
        $syncFields = [
            'title' => $globalCourse->title,
            'description' => $globalCourse->description,
            'credits' => $globalCourse->credits,
            'level' => $globalCourse->level,
            'prerequisites' => json_decode($globalCourse->prerequisites, true)
        ];

        $this->update($syncFields);
        $this->logActivity('synced', 'Course synced with global course template');

        return true;
    }

    /**
     * Search courses
     */
    public static function search(string $query): Builder
    {
        return static::where(function ($q) use ($query) {
            $q->where('course_code', 'ILIKE', "%{$query}%")
              ->orWhere('title', 'ILIKE', "%{$query}%")
              ->orWhere('description', 'ILIKE', "%{$query}%")
              ->orWhere('department', 'ILIKE', "%{$query}%")
              ->orWhere('instructor_name', 'ILIKE', "%{$query}%");
        });
    }

    /**
     * Get courses by department
     */
    public static function byDepartment(string $department): Builder
    {
        return static::where('department', $department);
    }

    /**
     * Get courses by level
     */
    public static function byLevel(string $level): Builder
    {
        return static::where('level', $level);
    }

    /**
     * Get courses by status
     */
    public static function byStatus(string $status): Builder
    {
        return static::where('status', $status);
    }

    /**
     * Get available courses (not full)
     */
    public static function available(): Builder
    {
        return static::where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('max_enrollment')
                      ->orWhereRaw('(
                          SELECT COUNT(*) 
                          FROM enrollments 
                          WHERE course_id = courses.id 
                          AND status = \'active\'
                      ) < max_enrollment');
            });
    }

    /**
     * Get courses with prerequisites met by student
     */
    public static function availableForStudent(Student $student): Builder
    {
        $completedCourses = $student->completedEnrollments()
            ->with('course')
            ->get()
            ->pluck('course.course_code')
            ->toArray();

        return static::available()
            ->where(function ($query) use ($completedCourses) {
                $query->whereNull('prerequisites')
                      ->orWhere('prerequisites', '[]')
                      ->orWhere(function ($q) use ($completedCourses) {
                          foreach ($completedCourses as $courseCode) {
                              $q->orWhereJsonContains('prerequisites', $courseCode);
                          }
                      });
            });
    }

    /**
     * Check if student meets prerequisites
     */
    public function studentMeetsPrerequisites(Student $student): bool
    {
        if (empty($this->prerequisites)) {
            return true;
        }

        $completedCourses = $student->completedEnrollments()
            ->with('course')
            ->get()
            ->pluck('course.course_code')
            ->toArray();

        foreach ($this->prerequisites as $prerequisite) {
            if (!in_array($prerequisite, $completedCourses)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Enroll a student in this course
     */
    public function enrollStudent(Student $student, array $enrollmentData = []): Enrollment
    {
        // Check if course is available
        if ($this->is_full) {
            throw new Exception('Course is full');
        }

        // Check prerequisites
        if (!$this->studentMeetsPrerequisites($student)) {
            throw new Exception('Student does not meet prerequisites');
        }

        // Check if already enrolled
        $existingEnrollment = $this->enrollments()
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->first();

        if ($existingEnrollment) {
            throw new Exception('Student is already enrolled in this course');
        }

        // Create enrollment
        $enrollment = $this->enrollments()->create(array_merge([
            'student_id' => $student->id,
            'enrollment_date' => now(),
            'status' => 'active'
        ], $enrollmentData));

        $this->logActivity('student_enrolled', "Student {$student->full_name} enrolled", [
            'student_id' => $student->id,
            'enrollment_id' => $enrollment->id
        ]);

        return $enrollment;
    }

    /**
     * Calculate course statistics
     */
    public function getStatistics(): array
    {
        $enrollments = $this->enrollments()->with('student')->get();
        $grades = $this->grades()->whereNotNull('grade_points')->get();

        return [
            'total_enrollments' => $enrollments->count(),
            'active_enrollments' => $enrollments->where('status', 'active')->count(),
            'completed_enrollments' => $enrollments->where('status', 'completed')->count(),
            'dropped_enrollments' => $enrollments->where('status', 'dropped')->count(),
            'average_grade' => $grades->avg('grade_points') ?: 0,
            'pass_rate' => $grades->where('grade_points', '>=', 2.0)->count() / max($grades->count(), 1) * 100,
            'enrollment_capacity' => $this->max_enrollment ? ($this->enrollment_count / $this->max_enrollment * 100) : null
        ];
    }

    /**
     * Get global courses available for import
     */
    public static function getAvailableGlobalCourses(): array
    {
        $existingGlobalIds = static::whereNotNull('global_course_id')
            ->pluck('global_course_id')
            ->toArray();

        return DB::connection('pgsql')
            ->table('public.global_courses')
            ->where('status', 'active')
            ->whereNotIn('id', $existingGlobalIds)
            ->orderBy('department')
            ->orderBy('course_code')
            ->get()
            ->toArray();
    }

    /**
     * Bulk import global courses
     */
    public static function importGlobalCourses(array $globalCourseIds, array $defaultOverrides = []): array
    {
        $results = [];
        
        foreach ($globalCourseIds as $globalCourseId) {
            try {
                $course = static::createFromGlobalCourse($globalCourseId, $defaultOverrides);
                $results['success'][] = [
                    'global_course_id' => $globalCourseId,
                    'course_id' => $course->id,
                    'course_code' => $course->course_code
                ];
            } catch (Exception $e) {
                $results['errors'][] = [
                    'global_course_id' => $globalCourseId,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }

    /**
     * Log course activity
     */
    public function logActivity(string $action, string $description, array $metadata = []): void
    {
        try {
            ActivityLog::create([
                'course_id' => $this->id,
                'user_id' => auth()->id(),
                'action' => $action,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => array_merge($metadata, [
                    'course_code' => $this->course_code,
                    'course_title' => $this->title
                ])
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to log course activity', [
                'course_id' => $this->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get course statistics for current tenant
     */
    public static function getTenantStatistics(): array
    {
        return [
            'total_courses' => static::count(),
            'active_courses' => static::where('status', 'active')->count(),
            'custom_courses' => static::where('is_custom', true)->count(),
            'global_courses' => static::where('is_custom', false)->whereNotNull('global_course_id')->count(),
            'courses_by_department' => static::selectRaw('department, COUNT(*) as count')
                ->groupBy('department')
                ->orderBy('count', 'desc')
                ->pluck('count', 'department')
                ->toArray(),
            'courses_by_level' => static::selectRaw('level, COUNT(*) as count')
                ->groupBy('level')
                ->orderBy('level')
                ->pluck('count', 'level')
                ->toArray(),
            'average_enrollment' => static::withCount('activeEnrollments')
                ->avg('active_enrollments_count') ?: 0
        ];
    }

    /**
     * Validate course data integrity
     */
    public function validateDataIntegrity(): array
    {
        $errors = [];

        // Check for orphaned enrollments
        $orphanedEnrollments = DB::table('enrollments')
            ->where('course_id', $this->id)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('students')
                      ->whereColumn('students.id', 'enrollments.student_id');
            })
            ->count();

        if ($orphanedEnrollments > 0) {
            $errors[] = "Course has {$orphanedEnrollments} enrollments with non-existent students";
        }

        // Check global course reference
        if ($this->global_course_id && !$this->globalCourse()) {
            $errors[] = "Course references non-existent global course ID: {$this->global_course_id}";
        }

        // Check enrollment limits
        if ($this->max_enrollment && $this->enrollment_count > $this->max_enrollment) {
            $errors[] = "Course has more enrollments ({$this->enrollment_count}) than maximum allowed ({$this->max_enrollment})";
        }

        // Check date consistency
        if ($this->start_date && $this->end_date && $this->start_date > $this->end_date) {
            $errors[] = "Course start date is after end date";
        }

        return $errors;
    }
}