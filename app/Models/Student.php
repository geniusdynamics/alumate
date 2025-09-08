<?php
// ABOUTME: Student model for schema-based multi-tenancy without tenant_id column
// ABOUTME: Handles student data within tenant schemas with relationships and validation

namespace App\Models;

use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Exception;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'student_id',
        'date_of_birth',
        'enrollment_date',
        'graduation_date',
        'status',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'profile_photo_url',
        'metadata'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'enrollment_date' => 'date',
        'graduation_date' => 'date',
        'metadata' => 'array',
        'email_verified_at' => 'datetime'
    ];

    protected $dates = [
        'deleted_at',
        'email_verified_at'
    ];

    protected $appends = [
        'full_name',
        'is_graduated',
        'current_tenant'
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
                throw new Exception('Student model requires tenant context. Use TenantContextService::setTenant() first.');
            }
        });

        // Auto-generate student_id if not provided
        static::creating(function ($student) {
            if (empty($student->student_id)) {
                $student->student_id = static::generateStudentId();
            }
        });

        // Log student activities
        static::created(function ($student) {
            $student->logActivity('created', 'Student record created');
        });

        static::updated(function ($student) {
            $student->logActivity('updated', 'Student record updated');
        });

        static::deleted(function ($student) {
            $student->logActivity('deleted', 'Student record deleted');
        });
    }

    /**
     * Get the student's enrollments
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the student's grades
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Get the courses the student is enrolled in
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments')
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
     * Get the student's activity logs
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'student_id');
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get graduation status
     */
    public function getIsGraduatedAttribute(): bool
    {
        return $this->status === 'graduated' && !is_null($this->graduation_date);
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
     * Calculate GPA
     */
    public function calculateGPA(): float
    {
        $grades = $this->grades()->whereNotNull('grade_points')->get();
        
        if ($grades->isEmpty()) {
            return 0.0;
        }

        $totalPoints = $grades->sum(function ($grade) {
            return $grade->grade_points * $grade->credits;
        });
        
        $totalCredits = $grades->sum('credits');
        
        return $totalCredits > 0 ? round($totalPoints / $totalCredits, 2) : 0.0;
    }

    /**
     * Get total credits earned
     */
    public function getTotalCreditsEarned(): int
    {
        return $this->enrollments()
            ->where('status', 'completed')
            ->whereNotNull('credits_earned')
            ->sum('credits_earned');
    }

    /**
     * Check if student can graduate
     */
    public function canGraduate(int $requiredCredits = 120): bool
    {
        return $this->getTotalCreditsEarned() >= $requiredCredits && 
               $this->calculateGPA() >= 2.0;
    }

    /**
     * Graduate the student
     */
    public function graduate(): bool
    {
        if (!$this->canGraduate()) {
            return false;
        }

        $this->update([
            'status' => 'graduated',
            'graduation_date' => now()
        ]);

        // Create graduate record
        Graduate::create([
            'student_id' => $this->id,
            'graduation_date' => $this->graduation_date,
            'gpa' => $this->calculateGPA(),
            'total_credits' => $this->getTotalCreditsEarned(),
            'honors' => $this->determineHonors()
        ]);

        $this->logActivity('graduated', 'Student graduated');

        return true;
    }

    /**
     * Determine graduation honors
     */
    private function determineHonors(): ?string
    {
        $gpa = $this->calculateGPA();
        
        if ($gpa >= 3.9) {
            return 'summa_cum_laude';
        } elseif ($gpa >= 3.7) {
            return 'magna_cum_laude';
        } elseif ($gpa >= 3.5) {
            return 'cum_laude';
        }
        
        return null;
    }

    /**
     * Generate unique student ID
     */
    public static function generateStudentId(): string
    {
        $tenant = TenantContextService::getCurrentTenant();
        $prefix = $tenant ? strtoupper(substr($tenant->slug, 0, 3)) : 'STU';
        $year = date('Y');
        
        do {
            $number = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $studentId = $prefix . $year . $number;
        } while (static::where('student_id', $studentId)->exists());
        
        return $studentId;
    }

    /**
     * Search students
     */
    public static function search(string $query): Builder
    {
        return static::where(function ($q) use ($query) {
            $q->where('first_name', 'ILIKE', "%{$query}%")
              ->orWhere('last_name', 'ILIKE', "%{$query}%")
              ->orWhere('email', 'ILIKE', "%{$query}%")
              ->orWhere('student_id', 'ILIKE', "%{$query}%")
              ->orWhereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", ["%{$query}%"]);
        });
    }

    /**
     * Get students by status
     */
    public static function byStatus(string $status): Builder
    {
        return static::where('status', $status);
    }

    /**
     * Get students enrolled in a specific course
     */
    public static function enrolledInCourse(int $courseId): Builder
    {
        return static::whereHas('enrollments', function ($query) use ($courseId) {
            $query->where('course_id', $courseId)
                  ->where('status', 'active');
        });
    }

    /**
     * Get students by enrollment year
     */
    public static function byEnrollmentYear(int $year): Builder
    {
        return static::whereYear('enrollment_date', $year);
    }

    /**
     * Get graduation candidates
     */
    public static function graduationCandidates(int $requiredCredits = 120): Builder
    {
        return static::where('status', 'active')
            ->whereHas('enrollments', function ($query) use ($requiredCredits) {
                $query->where('status', 'completed')
                      ->havingRaw('SUM(credits_earned) >= ?', [$requiredCredits]);
            });
    }

    /**
     * Log student activity
     */
    public function logActivity(string $action, string $description, array $metadata = []): void
    {
        try {
            ActivityLog::create([
                'student_id' => $this->id,
                'user_id' => auth()->id(),
                'action' => $action,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => array_merge($metadata, [
                    'student_name' => $this->full_name,
                    'student_id' => $this->student_id
                ])
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to log student activity', [
                'student_id' => $this->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get student statistics for current tenant
     */
    public static function getStatistics(): array
    {
        return [
            'total_students' => static::count(),
            'active_students' => static::where('status', 'active')->count(),
            'graduated_students' => static::where('status', 'graduated')->count(),
            'suspended_students' => static::where('status', 'suspended')->count(),
            'average_gpa' => static::selectRaw('AVG((
                SELECT AVG(grade_points * credits) / AVG(credits)
                FROM grades 
                WHERE grades.student_id = students.id
                AND grade_points IS NOT NULL
            )) as avg_gpa')->value('avg_gpa') ?: 0,
            'enrollment_by_year' => static::selectRaw('EXTRACT(YEAR FROM enrollment_date) as year, COUNT(*) as count')
                ->groupBy('year')
                ->orderBy('year', 'desc')
                ->pluck('count', 'year')
                ->toArray()
        ];
    }

    /**
     * Export student data for current tenant
     */
    public static function exportData(array $fields = null): array
    {
        $fields = $fields ?: [
            'student_id', 'first_name', 'last_name', 'email', 
            'status', 'enrollment_date', 'graduation_date'
        ];

        return static::select($fields)
            ->with(['enrollments.course:id,course_code,title'])
            ->get()
            ->map(function ($student) {
                $data = $student->toArray();
                $data['gpa'] = $student->calculateGPA();
                $data['total_credits'] = $student->getTotalCreditsEarned();
                return $data;
            })
            ->toArray();
    }

    /**
     * Validate student data integrity
     */
    public function validateDataIntegrity(): array
    {
        $errors = [];

        // Check for orphaned enrollments
        $orphanedEnrollments = DB::table('enrollments')
            ->where('student_id', $this->id)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('courses')
                      ->whereColumn('courses.id', 'enrollments.course_id');
            })
            ->count();

        if ($orphanedEnrollments > 0) {
            $errors[] = "Student has {$orphanedEnrollments} enrollments with non-existent courses";
        }

        // Check for invalid grades
        $invalidGrades = $this->grades()
            ->where(function ($query) {
                $query->where('grade_points', '<', 0)
                      ->orWhere('grade_points', '>', 4.0);
            })
            ->count();

        if ($invalidGrades > 0) {
            $errors[] = "Student has {$invalidGrades} grades with invalid grade points";
        }

        // Check graduation status consistency
        if ($this->status === 'graduated' && is_null($this->graduation_date)) {
            $errors[] = "Student marked as graduated but has no graduation date";
        }

        if (!is_null($this->graduation_date) && $this->status !== 'graduated') {
            $errors[] = "Student has graduation date but status is not 'graduated'";
        }

        return $errors;
    }
}