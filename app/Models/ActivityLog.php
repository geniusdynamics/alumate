<?php
// ABOUTME: ActivityLog model for schema-based multi-tenancy tracking all system activities and changes
// ABOUTME: Provides comprehensive audit trail functionality with tenant isolation and activity monitoring

namespace App\Models;

use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Exception;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_id',
        'course_id',
        'enrollment_id',
        'grade_id',
        'loggable_type',
        'loggable_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'session_id',
        'request_id',
        'severity',
        'category',
        'metadata',
        'old_values',
        'new_values',
        'performed_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'performed_at' => 'datetime'
    ];

    protected $appends = [
        'current_tenant',
        'changes_summary',
        'is_sensitive'
    ];

    // Severity levels
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    // Activity categories
    const CATEGORY_AUTH = 'authentication';
    const CATEGORY_STUDENT = 'student';
    const CATEGORY_COURSE = 'course';
    const CATEGORY_ENROLLMENT = 'enrollment';
    const CATEGORY_GRADE = 'grade';
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_ADMIN = 'admin';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_DATA = 'data';
    const CATEGORY_API = 'api';

    // Common actions
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_DELETED = 'deleted';
    const ACTION_VIEWED = 'viewed';
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_FAILED_LOGIN = 'failed_login';
    const ACTION_ENROLLED = 'enrolled';
    const ACTION_UNENROLLED = 'unenrolled';
    const ACTION_GRADED = 'graded';
    const ACTION_EXPORTED = 'exported';
    const ACTION_IMPORTED = 'imported';
    const ACTION_BACKUP = 'backup';
    const ACTION_RESTORE = 'restore';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure we're in a tenant context for non-system logs
        static::addGlobalScope('tenant_context', function (Builder $builder) {
            if (!TenantContextService::hasTenant() && !static::isSystemLog()) {
                throw new Exception('ActivityLog model requires tenant context for non-system logs. Use TenantContextService::setTenant() first.');
            }
        });

        // Auto-set performed_at timestamp
        static::creating(function ($log) {
            if (empty($log->performed_at)) {
                $log->performed_at = now();
            }

            // Auto-set session and request IDs
            if (empty($log->session_id)) {
                $log->session_id = session()->getId();
            }

            if (empty($log->request_id)) {
                $log->request_id = request()->header('X-Request-ID') ?? uniqid('req_', true);
            }

            // Auto-detect severity if not set
            if (empty($log->severity)) {
                $log->severity = static::detectSeverity($log->action, $log->category);
            }
        });
    }

    /**
     * Get the user that performed the activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the student related to the activity
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the course related to the activity
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the enrollment related to the activity
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Get the grade related to the activity
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the loggable model (polymorphic relationship)
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
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
     * Get summary of changes
     */
    public function getChangesSummaryAttribute(): ?string
    {
        if (empty($this->old_values) || empty($this->new_values)) {
            return null;
        }

        $changes = [];
        foreach ($this->new_values as $field => $newValue) {
            $oldValue = $this->old_values[$field] ?? null;
            if ($oldValue !== $newValue) {
                $changes[] = "{$field}: '{$oldValue}' → '{$newValue}'";
            }
        }

        return implode(', ', $changes);
    }

    /**
     * Check if this is a sensitive activity
     */
    public function getIsSensitiveAttribute(): bool
    {
        $sensitiveActions = [
            self::ACTION_LOGIN,
            self::ACTION_FAILED_LOGIN,
            self::ACTION_DELETED,
            'password_changed',
            'permission_changed',
            'role_changed'
        ];

        $sensitiveCategories = [
            self::CATEGORY_SECURITY,
            self::CATEGORY_ADMIN
        ];

        return in_array($this->action, $sensitiveActions) || 
               in_array($this->category, $sensitiveCategories) ||
               $this->severity === self::SEVERITY_CRITICAL;
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific student
     */
    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope for specific course
     */
    public function scopeForCourse(Builder $query, int $courseId): Builder
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope for specific action
     */
    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for specific category
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for specific severity
     */
    public function scopeBySeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope for sensitive activities
     */
    public function scopeSensitive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereIn('action', [
                self::ACTION_LOGIN,
                self::ACTION_FAILED_LOGIN,
                self::ACTION_DELETED,
                'password_changed',
                'permission_changed',
                'role_changed'
            ])
            ->orWhereIn('category', [
                self::CATEGORY_SECURITY,
                self::CATEGORY_ADMIN
            ])
            ->orWhere('severity', self::SEVERITY_CRITICAL);
        });
    }

    /**
     * Scope for recent activities
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('performed_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('performed_at', [$startDate, $endDate]);
    }

    /**
     * Scope for IP address
     */
    public function scopeByIp(Builder $query, string $ipAddress): Builder
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Static method to log activity
     */
    public static function logActivity(array $data): self
    {
        // Merge with default values
        $data = array_merge([
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'performed_at' => now()
        ], $data);

        return static::create($data);
    }

    /**
     * Log authentication activity
     */
    public static function logAuth(string $action, ?int $userId = null, array $metadata = []): self
    {
        return static::logActivity([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'category' => self::CATEGORY_AUTH,
            'description' => ucfirst($action) . ' activity',
            'metadata' => $metadata,
            'severity' => $action === self::ACTION_FAILED_LOGIN ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM
        ]);
    }

    /**
     * Log student activity
     */
    public static function logStudent(string $action, Student $student, string $description = null, array $metadata = []): self
    {
        return static::logActivity([
            'student_id' => $student->id,
            'action' => $action,
            'category' => self::CATEGORY_STUDENT,
            'description' => $description ?? "{$action} student: {$student->full_name}",
            'metadata' => array_merge($metadata, [
                'student_name' => $student->full_name,
                'student_email' => $student->email
            ])
        ]);
    }

    /**
     * Log course activity
     */
    public static function logCourse(string $action, Course $course, string $description = null, array $metadata = []): self
    {
        return static::logActivity([
            'course_id' => $course->id,
            'action' => $action,
            'category' => self::CATEGORY_COURSE,
            'description' => $description ?? "{$action} course: {$course->course_code}",
            'metadata' => array_merge($metadata, [
                'course_code' => $course->course_code,
                'course_title' => $course->title
            ])
        ]);
    }

    /**
     * Log enrollment activity
     */
    public static function logEnrollment(string $action, Enrollment $enrollment, string $description = null, array $metadata = []): self
    {
        return static::logActivity([
            'student_id' => $enrollment->student_id,
            'course_id' => $enrollment->course_id,
            'enrollment_id' => $enrollment->id,
            'action' => $action,
            'category' => self::CATEGORY_ENROLLMENT,
            'description' => $description ?? "{$action} enrollment",
            'metadata' => array_merge($metadata, [
                'enrollment_status' => $enrollment->status,
                'enrollment_date' => $enrollment->enrolled_date
            ])
        ]);
    }

    /**
     * Log grade activity
     */
    public static function logGrade(string $action, Grade $grade, string $description = null, array $metadata = []): self
    {
        return static::logActivity([
            'student_id' => $grade->student_id,
            'course_id' => $grade->course_id,
            'grade_id' => $grade->id,
            'action' => $action,
            'category' => self::CATEGORY_GRADE,
            'description' => $description ?? "{$action} grade",
            'metadata' => array_merge($metadata, [
                'assessment_type' => $grade->assessment_type,
                'assessment_name' => $grade->assessment_name,
                'points_earned' => $grade->points_earned,
                'points_possible' => $grade->points_possible
            ])
        ]);
    }

    /**
     * Log system activity
     */
    public static function logSystem(string $action, string $description, array $metadata = [], string $severity = self::SEVERITY_MEDIUM): self
    {
        return static::logActivity([
            'action' => $action,
            'category' => self::CATEGORY_SYSTEM,
            'description' => $description,
            'metadata' => $metadata,
            'severity' => $severity
        ]);
    }

    /**
     * Log security activity
     */
    public static function logSecurity(string $action, string $description, array $metadata = [], string $severity = self::SEVERITY_HIGH): self
    {
        return static::logActivity([
            'action' => $action,
            'category' => self::CATEGORY_SECURITY,
            'description' => $description,
            'metadata' => $metadata,
            'severity' => $severity
        ]);
    }

    /**
     * Log model changes
     */
    public static function logModelChanges(Model $model, string $action, array $oldValues = [], array $newValues = []): self
    {
        $modelName = class_basename($model);
        
        return static::logActivity([
            'loggable_type' => get_class($model),
            'loggable_id' => $model->id,
            'action' => $action,
            'category' => self::CATEGORY_DATA,
            'description' => "{$action} {$modelName} (ID: {$model->id})",
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => [
                'model_type' => $modelName,
                'model_id' => $model->id
            ]
        ]);
    }

    /**
     * Get activity statistics
     */
    public static function getStatistics(array $filters = []): array
    {
        $query = static::query();

        // Apply filters
        if (isset($filters['start_date'])) {
            $query->where('performed_at', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('performed_at', '<=', $filters['end_date']);
        }
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        $total = $query->count();
        
        return [
            'total_activities' => $total,
            'by_category' => $query->groupBy('category')
                ->selectRaw('category, count(*) as count')
                ->pluck('count', 'category')
                ->toArray(),
            'by_action' => $query->groupBy('action')
                ->selectRaw('action, count(*) as count')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'action')
                ->toArray(),
            'by_severity' => $query->groupBy('severity')
                ->selectRaw('severity, count(*) as count')
                ->pluck('count', 'severity')
                ->toArray(),
            'by_user' => $query->whereNotNull('user_id')
                ->groupBy('user_id')
                ->selectRaw('user_id, count(*) as count')
                ->orderByDesc('count')
                ->limit(10)
                ->with('user:id,name,email')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->user->name ?? "User {$item->user_id}" => $item->count];
                })
                ->toArray(),
            'recent_critical' => $query->where('severity', self::SEVERITY_CRITICAL)
                ->orderByDesc('performed_at')
                ->limit(5)
                ->get(['action', 'description', 'performed_at'])
                ->toArray()
        ];
    }

    /**
     * Get user activity summary
     */
    public static function getUserActivitySummary(int $userId, int $days = 30): array
    {
        $activities = static::where('user_id', $userId)
            ->where('performed_at', '>=', now()->subDays($days))
            ->get();

        return [
            'total_activities' => $activities->count(),
            'daily_average' => round($activities->count() / $days, 2),
            'most_common_action' => $activities->groupBy('action')
                ->map->count()
                ->sortDesc()
                ->keys()
                ->first(),
            'categories_used' => $activities->pluck('category')->unique()->values()->toArray(),
            'last_activity' => $activities->sortByDesc('performed_at')->first()?->performed_at,
            'sensitive_activities' => $activities->filter->is_sensitive->count()
        ];
    }

    /**
     * Clean old logs
     */
    public static function cleanOldLogs(int $daysToKeep = 365): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return static::where('performed_at', '<', $cutoffDate)
            ->where('severity', '!=', self::SEVERITY_CRITICAL) // Keep critical logs longer
            ->delete();
    }

    /**
     * Export activities to CSV
     */
    public static function exportToCsv(array $filters = []): string
    {
        $query = static::query();

        // Apply filters
        foreach ($filters as $field => $value) {
            if (!empty($value)) {
                $query->where($field, $value);
            }
        }

        $activities = $query->with(['user:id,name,email', 'student:id,first_name,last_name', 'course:id,course_code,title'])
            ->orderByDesc('performed_at')
            ->get();

        $csv = "Date,User,Student,Course,Action,Category,Severity,Description,IP Address\n";
        
        foreach ($activities as $activity) {
            $csv .= implode(',', [
                $activity->performed_at->format('Y-m-d H:i:s'),
                $activity->user?->name ?? 'System',
                $activity->student ? "{$activity->student->first_name} {$activity->student->last_name}" : '',
                $activity->course?->course_code ?? '',
                $activity->action,
                $activity->category,
                $activity->severity,
                '"' . str_replace('"', '""', $activity->description) . '"',
                $activity->ip_address ?? ''
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * Detect severity based on action and category
     */
    protected static function detectSeverity(string $action, string $category): string
    {
        // Critical actions
        if (in_array($action, [self::ACTION_DELETED, 'password_changed', 'permission_changed'])) {
            return self::SEVERITY_CRITICAL;
        }

        // High severity actions
        if (in_array($action, [self::ACTION_FAILED_LOGIN, 'role_changed', 'security_breach'])) {
            return self::SEVERITY_HIGH;
        }

        // Medium severity categories
        if (in_array($category, [self::CATEGORY_SECURITY, self::CATEGORY_ADMIN, self::CATEGORY_GRADE])) {
            return self::SEVERITY_MEDIUM;
        }

        // Default to low
        return self::SEVERITY_LOW;
    }

    /**
     * Check if this is a system log (no tenant context required)
     */
    protected static function isSystemLog(): bool
    {
        // Check if we're logging system-level activities
        return request()->has('system_log') || 
               in_array(request()->route()?->getName(), ['system.logs', 'admin.system']);
    }
}