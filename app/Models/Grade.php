<?php
// ABOUTME: Grade model for schema-based multi-tenancy managing individual assessment grades
// ABOUTME: Handles grade records within tenant schemas with assessment tracking, calculations, and validation

namespace App\Models;

use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Exception;

class Grade extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'course_id',
        'enrollment_id',
        'assessment_type',
        'assessment_name',
        'points_earned',
        'points_possible',
        'percentage',
        'letter_grade',
        'grade_points',
        'weight',
        'graded_date',
        'due_date',
        'submitted_date',
        'late_penalty',
        'extra_credit',
        'comments',
        'grader_id',
        'is_final',
        'is_published',
        'metadata'
    ];

    protected $casts = [
        'points_earned' => 'decimal:2',
        'points_possible' => 'decimal:2',
        'percentage' => 'decimal:2',
        'grade_points' => 'decimal:2',
        'weight' => 'decimal:2',
        'late_penalty' => 'decimal:2',
        'extra_credit' => 'decimal:2',
        'graded_date' => 'datetime',
        'due_date' => 'datetime',
        'submitted_date' => 'datetime',
        'is_final' => 'boolean',
        'is_published' => 'boolean',
        'metadata' => 'array'
    ];

    protected $dates = [
        'deleted_at'
    ];

    protected $appends = [
        'is_late',
        'is_passing',
        'adjusted_points',
        'current_tenant'
    ];

    // Assessment type constants
    const TYPE_EXAM = 'exam';
    const TYPE_QUIZ = 'quiz';
    const TYPE_ASSIGNMENT = 'assignment';
    const TYPE_PROJECT = 'project';
    const TYPE_PARTICIPATION = 'participation';
    const TYPE_HOMEWORK = 'homework';
    const TYPE_LAB = 'lab';
    const TYPE_PRESENTATION = 'presentation';
    const TYPE_FINAL = 'final';
    const TYPE_MIDTERM = 'midterm';
    const TYPE_OTHER = 'other';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure we're in a tenant context
        static::addGlobalScope('tenant_context', function (Builder $builder) {
            if (!TenantContextService::hasTenant()) {
                throw new Exception('Grade model requires tenant context. Use TenantContextService::setTenant() first.');
            }
        });

        // Auto-calculate derived fields
        static::saving(function ($grade) {
            // Calculate percentage if points are provided
            if ($grade->points_earned !== null && $grade->points_possible > 0) {
                $grade->percentage = ($grade->points_earned / $grade->points_possible) * 100;
            }

            // Calculate letter grade from percentage
            if ($grade->percentage !== null && empty($grade->letter_grade)) {
                $grade->letter_grade = static::calculateLetterGrade($grade->percentage);
            }

            // Calculate grade points from letter grade
            if ($grade->letter_grade && $grade->grade_points === null) {
                $grade->grade_points = static::calculateGradePoints($grade->letter_grade);
            }

            // Set graded date if not provided
            if (empty($grade->graded_date) && $grade->points_earned !== null) {
                $grade->graded_date = now();
            }
        });

        // Log grade activities
        static::created(function ($grade) {
            $grade->logActivity('created', 'Grade record created');
        });

        static::updated(function ($grade) {
            $grade->logActivity('updated', 'Grade record updated');
        });

        static::deleted(function ($grade) {
            $grade->logActivity('deleted', 'Grade record deleted');
        });
    }

    /**
     * Get the student that owns the grade
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the course that owns the grade
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the enrollment that owns the grade
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Get the grader (user who assigned the grade)
     */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'grader_id');
    }

    /**
     * Check if submission was late
     */
    public function getIsLateAttribute(): bool
    {
        return $this->submitted_date && $this->due_date && $this->submitted_date > $this->due_date;
    }

    /**
     * Check if grade is passing
     */
    public function getIsPassingAttribute(): bool
    {
        return $this->percentage >= 60; // Configurable passing threshold
    }

    /**
     * Get adjusted points after penalties and extra credit
     */
    public function getAdjustedPointsAttribute(): float
    {
        $points = $this->points_earned ?? 0;
        $points -= $this->late_penalty ?? 0;
        $points += $this->extra_credit ?? 0;
        
        return max(0, min($points, $this->points_possible ?? $points));
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
     * Calculate letter grade from percentage
     */
    public static function calculateLetterGrade(float $percentage): string
    {
        if ($percentage >= 97) return 'A+';
        if ($percentage >= 93) return 'A';
        if ($percentage >= 90) return 'A-';
        if ($percentage >= 87) return 'B+';
        if ($percentage >= 83) return 'B';
        if ($percentage >= 80) return 'B-';
        if ($percentage >= 77) return 'C+';
        if ($percentage >= 73) return 'C';
        if ($percentage >= 70) return 'C-';
        if ($percentage >= 67) return 'D+';
        if ($percentage >= 60) return 'D';
        
        return 'F';
    }

    /**
     * Calculate grade points from letter grade
     */
    public static function calculateGradePoints(string $letterGrade): float
    {
        $gradePoints = [
            'A+' => 4.0, 'A' => 4.0, 'A-' => 3.7,
            'B+' => 3.3, 'B' => 3.0, 'B-' => 2.7,
            'C+' => 2.3, 'C' => 2.0, 'C-' => 1.7,
            'D+' => 1.3, 'D' => 1.0,
            'F' => 0.0, 'I' => 0.0, 'W' => 0.0
        ];

        return $gradePoints[$letterGrade] ?? 0.0;
    }

    /**
     * Get all available assessment types
     */
    public static function getAssessmentTypes(): array
    {
        return [
            self::TYPE_EXAM => 'Exam',
            self::TYPE_QUIZ => 'Quiz',
            self::TYPE_ASSIGNMENT => 'Assignment',
            self::TYPE_PROJECT => 'Project',
            self::TYPE_PARTICIPATION => 'Participation',
            self::TYPE_HOMEWORK => 'Homework',
            self::TYPE_LAB => 'Lab',
            self::TYPE_PRESENTATION => 'Presentation',
            self::TYPE_FINAL => 'Final Exam',
            self::TYPE_MIDTERM => 'Midterm Exam',
            self::TYPE_OTHER => 'Other'
        ];
    }

    /**
     * Scope for published grades
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope for final grades
     */
    public function scopeFinal(Builder $query): Builder
    {
        return $query->where('is_final', true);
    }

    /**
     * Scope for specific assessment type
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('assessment_type', $type);
    }

    /**
     * Scope for passing grades
     */
    public function scopePassing(Builder $query): Builder
    {
        return $query->where('percentage', '>=', 60);
    }

    /**
     * Scope for failing grades
     */
    public function scopeFailing(Builder $query): Builder
    {
        return $query->where('percentage', '<', 60);
    }

    /**
     * Scope for late submissions
     */
    public function scopeLate(Builder $query): Builder
    {
        return $query->whereNotNull('submitted_date')
                    ->whereNotNull('due_date')
                    ->whereColumn('submitted_date', '>', 'due_date');
    }

    /**
     * Scope for grades with extra credit
     */
    public function scopeWithExtraCredit(Builder $query): Builder
    {
        return $query->where('extra_credit', '>', 0);
    }

    /**
     * Calculate weighted average for a collection of grades
     */
    public static function calculateWeightedAverage($grades): float
    {
        $totalWeightedPoints = 0;
        $totalWeight = 0;

        foreach ($grades as $grade) {
            if ($grade->percentage !== null && $grade->weight > 0) {
                $totalWeightedPoints += $grade->percentage * $grade->weight;
                $totalWeight += $grade->weight;
            }
        }

        return $totalWeight > 0 ? $totalWeightedPoints / $totalWeight : 0;
    }

    /**
     * Calculate course grade for a student
     */
    public static function calculateCourseGrade(Student $student, Course $course): array
    {
        $grades = static::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->published()
            ->get();

        if ($grades->isEmpty()) {
            return [
                'percentage' => null,
                'letter_grade' => null,
                'grade_points' => null,
                'total_points_earned' => 0,
                'total_points_possible' => 0,
                'grade_breakdown' => []
            ];
        }

        // Group grades by assessment type
        $gradesByType = $grades->groupBy('assessment_type');
        $breakdown = [];
        $totalWeightedScore = 0;
        $totalWeight = 0;

        foreach ($gradesByType as $type => $typeGrades) {
            $typeAverage = $typeGrades->avg('percentage');
            $typeWeight = $typeGrades->first()->weight ?? 1;
            
            $breakdown[$type] = [
                'average' => round($typeAverage, 2),
                'weight' => $typeWeight,
                'count' => $typeGrades->count(),
                'total_points_earned' => $typeGrades->sum('points_earned'),
                'total_points_possible' => $typeGrades->sum('points_possible')
            ];
            
            $totalWeightedScore += $typeAverage * $typeWeight;
            $totalWeight += $typeWeight;
        }

        $finalPercentage = $totalWeight > 0 ? $totalWeightedScore / $totalWeight : 0;
        $letterGrade = static::calculateLetterGrade($finalPercentage);
        $gradePoints = static::calculateGradePoints($letterGrade);

        return [
            'percentage' => round($finalPercentage, 2),
            'letter_grade' => $letterGrade,
            'grade_points' => $gradePoints,
            'total_points_earned' => $grades->sum('points_earned'),
            'total_points_possible' => $grades->sum('points_possible'),
            'grade_breakdown' => $breakdown
        ];
    }

    /**
     * Get grade statistics for a course
     */
    public static function getCourseStatistics(Course $course): array
    {
        $grades = static::where('course_id', $course->id)
            ->published()
            ->get();

        if ($grades->isEmpty()) {
            return [
                'total_grades' => 0,
                'average_percentage' => 0,
                'median_percentage' => 0,
                'pass_rate' => 0,
                'grade_distribution' => [],
                'assessment_breakdown' => []
            ];
        }

        $percentages = $grades->pluck('percentage')->filter()->sort()->values();
        $gradeDistribution = $grades->groupBy('letter_grade')
            ->map(function ($group) {
                return $group->count();
            })
            ->toArray();

        $assessmentBreakdown = $grades->groupBy('assessment_type')
            ->map(function ($group, $type) {
                return [
                    'count' => $group->count(),
                    'average' => round($group->avg('percentage'), 2),
                    'total_points_possible' => $group->sum('points_possible'),
                    'total_points_earned' => $group->sum('points_earned')
                ];
            })
            ->toArray();

        return [
            'total_grades' => $grades->count(),
            'average_percentage' => round($percentages->avg(), 2),
            'median_percentage' => $percentages->count() > 0 
                ? $percentages->median() 
                : 0,
            'pass_rate' => $grades->where('percentage', '>=', 60)->count() / $grades->count() * 100,
            'grade_distribution' => $gradeDistribution,
            'assessment_breakdown' => $assessmentBreakdown
        ];
    }

    /**
     * Get student grade statistics
     */
    public static function getStudentStatistics(Student $student): array
    {
        $grades = static::where('student_id', $student->id)
            ->published()
            ->get();

        if ($grades->isEmpty()) {
            return [
                'total_grades' => 0,
                'overall_gpa' => 0,
                'average_percentage' => 0,
                'total_credits_attempted' => 0,
                'total_credits_earned' => 0,
                'course_breakdown' => []
            ];
        }

        $courseBreakdown = $grades->groupBy('course_id')
            ->map(function ($courseGrades, $courseId) {
                $course = Course::find($courseId);
                $courseGrade = static::calculateCourseGrade(
                    $courseGrades->first()->student, 
                    $course
                );
                
                return [
                    'course_code' => $course->course_code,
                    'course_title' => $course->title,
                    'credits' => $course->credits,
                    'final_grade' => $courseGrade
                ];
            })
            ->toArray();

        $completedCourses = collect($courseBreakdown)
            ->where('final_grade.letter_grade', '!=', null);

        $totalCreditsAttempted = $completedCourses->sum('credits');
        $weightedGradePoints = $completedCourses->sum(function ($course) {
            return $course['credits'] * $course['final_grade']['grade_points'];
        });
        
        $overallGpa = $totalCreditsAttempted > 0 
            ? $weightedGradePoints / $totalCreditsAttempted 
            : 0;

        return [
            'total_grades' => $grades->count(),
            'overall_gpa' => round($overallGpa, 2),
            'average_percentage' => round($grades->avg('percentage'), 2),
            'total_credits_attempted' => $totalCreditsAttempted,
            'total_credits_earned' => $completedCourses
                ->where('final_grade.grade_points', '>=', 2.0)
                ->sum('credits'),
            'course_breakdown' => $courseBreakdown
        ];
    }

    /**
     * Bulk update grades
     */
    public static function bulkUpdate(array $gradeData): array
    {
        $results = ['success' => [], 'errors' => []];
        
        foreach ($gradeData as $data) {
            try {
                $grade = static::find($data['id']);
                if ($grade) {
                    $grade->update($data);
                    $results['success'][] = $grade->id;
                } else {
                    $results['errors'][] = "Grade with ID {$data['id']} not found";
                }
            } catch (Exception $e) {
                $results['errors'][] = "Error updating grade {$data['id']}: " . $e->getMessage();
            }
        }
        
        return $results;
    }

    /**
     * Publish grades for a course assessment
     */
    public static function publishAssessmentGrades(Course $course, string $assessmentType, string $assessmentName): int
    {
        return static::where('course_id', $course->id)
            ->where('assessment_type', $assessmentType)
            ->where('assessment_name', $assessmentName)
            ->update(['is_published' => true]);
    }

    /**
     * Apply late penalty to grade
     */
    public function applyLatePenalty(float $penaltyAmount): bool
    {
        if (!$this->is_late) {
            return false;
        }

        $this->late_penalty = $penaltyAmount;
        $saved = $this->save();
        
        if ($saved) {
            $this->logActivity('late_penalty_applied', "Late penalty of {$penaltyAmount} points applied", [
                'penalty_amount' => $penaltyAmount,
                'original_points' => $this->points_earned,
                'adjusted_points' => $this->adjusted_points
            ]);
        }

        return $saved;
    }

    /**
     * Add extra credit to grade
     */
    public function addExtraCredit(float $creditAmount, string $reason = null): bool
    {
        $this->extra_credit = ($this->extra_credit ?? 0) + $creditAmount;
        $saved = $this->save();
        
        if ($saved) {
            $this->logActivity('extra_credit_added', "Extra credit of {$creditAmount} points added", [
                'credit_amount' => $creditAmount,
                'reason' => $reason,
                'total_extra_credit' => $this->extra_credit,
                'adjusted_points' => $this->adjusted_points
            ]);
        }

        return $saved;
    }

    /**
     * Log grade activity
     */
    public function logActivity(string $action, string $description, array $metadata = []): void
    {
        try {
            ActivityLog::create([
                'grade_id' => $this->id,
                'student_id' => $this->student_id,
                'course_id' => $this->course_id,
                'user_id' => auth()->id(),
                'action' => $action,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => array_merge($metadata, [
                    'assessment_type' => $this->assessment_type,
                    'assessment_name' => $this->assessment_name,
                    'points_earned' => $this->points_earned,
                    'points_possible' => $this->points_possible
                ])
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to log grade activity', [
                'grade_id' => $this->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate grade data integrity
     */
    public function validateDataIntegrity(): array
    {
        $errors = [];

        // Check if student exists
        if (!$this->student) {
            $errors[] = "Grade references non-existent student ID: {$this->student_id}";
        }

        // Check if course exists
        if (!$this->course) {
            $errors[] = "Grade references non-existent course ID: {$this->course_id}";
        }

        // Check if enrollment exists
        if ($this->enrollment_id && !$this->enrollment) {
            $errors[] = "Grade references non-existent enrollment ID: {$this->enrollment_id}";
        }

        // Check points consistency
        if ($this->points_earned > $this->points_possible) {
            $errors[] = "Points earned ({$this->points_earned}) exceeds points possible ({$this->points_possible})";
        }

        // Check percentage calculation
        if ($this->points_possible > 0) {
            $calculatedPercentage = ($this->points_earned / $this->points_possible) * 100;
            if (abs($this->percentage - $calculatedPercentage) > 0.01) {
                $errors[] = "Percentage ({$this->percentage}) does not match calculated value ({$calculatedPercentage})";
            }
        }

        // Check letter grade consistency
        if ($this->percentage !== null) {
            $calculatedLetterGrade = static::calculateLetterGrade($this->percentage);
            if ($this->letter_grade !== $calculatedLetterGrade) {
                $errors[] = "Letter grade ({$this->letter_grade}) does not match calculated value ({$calculatedLetterGrade})";
            }
        }

        // Check date consistency
        if ($this->submitted_date && $this->graded_date && $this->submitted_date > $this->graded_date) {
            $errors[] = "Submitted date is after graded date";
        }

        return $errors;
    }
}