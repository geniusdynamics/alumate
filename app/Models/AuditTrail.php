<?php
// ABOUTME: Eloquent model for audit_trail table in hybrid tenancy architecture
// ABOUTME: Tracks all changes across the system for compliance, security, and monitoring purposes

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class AuditTrail extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'audit_trail';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'global_user_id',
        'tenant_id',
        'table_name',
        'record_id',
        'operation',
        'old_values',
        'new_values',
        'changed_fields',
        'ip_address',
        'user_agent',
        'session_id',
        'request_id',
        'metadata',
        'severity_level',
        'category',
        'tags',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
    ];

    /**
     * Available operations.
     */
    public const OPERATIONS = [
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'restore' => 'Restore',
        'login' => 'Login',
        'logout' => 'Logout',
        'password_change' => 'Password Change',
        'permission_change' => 'Permission Change',
        'role_change' => 'Role Change',
        'tenant_switch' => 'Tenant Switch',
        'data_export' => 'Data Export',
        'data_import' => 'Data Import',
        'backup_created' => 'Backup Created',
        'backup_restored' => 'Backup Restored',
        'migration_started' => 'Migration Started',
        'migration_completed' => 'Migration Completed',
        'migration_failed' => 'Migration Failed',
        'schema_change' => 'Schema Change',
        'status_change' => 'Status Change',
        'bulk_operation' => 'Bulk Operation',
        'api_access' => 'API Access',
        'file_upload' => 'File Upload',
        'file_download' => 'File Download',
        'security_event' => 'Security Event',
        'system_event' => 'System Event',
    ];

    /**
     * Available severity levels.
     */
    public const SEVERITY_LEVELS = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
    ];

    /**
     * Available categories.
     */
    public const CATEGORIES = [
        'authentication' => 'Authentication',
        'authorization' => 'Authorization',
        'data_access' => 'Data Access',
        'data_modification' => 'Data Modification',
        'system_administration' => 'System Administration',
        'tenant_management' => 'Tenant Management',
        'user_management' => 'User Management',
        'course_management' => 'Course Management',
        'enrollment_management' => 'Enrollment Management',
        'financial' => 'Financial',
        'reporting' => 'Reporting',
        'security' => 'Security',
        'compliance' => 'Compliance',
        'performance' => 'Performance',
        'error' => 'Error',
        'warning' => 'Warning',
        'information' => 'Information',
    ];

    /**
     * Tables that should be audited.
     */
    public const AUDITED_TABLES = [
        'global_users',
        'user_tenant_memberships',
        'global_courses',
        'tenant_course_offerings',
        'super_admin_analytics',
        'data_sync_logs',
        'tenants',
        'users', // tenant-specific users
        'courses', // tenant-specific courses
        'enrollments',
        'payments',
        'grades',
        'transcripts',
    ];

    /**
     * Sensitive fields that should be masked in audit logs.
     */
    public const SENSITIVE_FIELDS = [
        'password',
        'password_hash',
        'remember_token',
        'api_token',
        'access_token',
        'refresh_token',
        'secret_key',
        'private_key',
        'ssn',
        'social_security_number',
        'credit_card_number',
        'bank_account_number',
        'routing_number',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(GlobalUser::class, 'global_user_id', 'global_user_id');
    }

    /**
     * Get the tenant associated with this audit entry.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the operation display name.
     */
    public function getOperationDisplayNameAttribute(): string
    {
        return self::OPERATIONS[$this->operation] ?? ucfirst(str_replace('_', ' ', $this->operation));
    }

    /**
     * Get the severity level display name.
     */
    public function getSeverityLevelDisplayNameAttribute(): string
    {
        return self::SEVERITY_LEVELS[$this->severity_level] ?? ucfirst($this->severity_level);
    }

    /**
     * Get the category display name.
     */
    public function getCategoryDisplayNameAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst(str_replace('_', ' ', $this->category));
    }

    /**
     * Get a human-readable description of the change.
     */
    public function getDescriptionAttribute(): string
    {
        $user = $this->user ? $this->user->name : 'System';
        $operation = $this->operation_display_name;
        $table = str_replace('_', ' ', $this->table_name);
        
        $description = "{$user} performed {$operation} on {$table}";
        
        if ($this->record_id) {
            $description .= " (ID: {$this->record_id})";
        }
        
        if ($this->tenant_id) {
            $description .= " in tenant {$this->tenant_id}";
        }
        
        return $description;
    }

    /**
     * Get the changes summary.
     */
    public function getChangesSummaryAttribute(): array
    {
        if (!$this->changed_fields || empty($this->changed_fields)) {
            return [];
        }
        
        $summary = [];
        
        foreach ($this->changed_fields as $field) {
            $oldValue = $this->old_values[$field] ?? null;
            $newValue = $this->new_values[$field] ?? null;
            
            // Mask sensitive fields
            if (in_array($field, self::SENSITIVE_FIELDS)) {
                $oldValue = $oldValue ? '[MASKED]' : null;
                $newValue = $newValue ? '[MASKED]' : null;
            }
            
            $summary[$field] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }
        
        return $summary;
    }

    /**
     * Check if this audit entry represents a security-sensitive operation.
     */
    public function isSecuritySensitive(): bool
    {
        $securityOperations = [
            'login',
            'logout',
            'password_change',
            'permission_change',
            'role_change',
            'security_event',
        ];
        
        return in_array($this->operation, $securityOperations) ||
               $this->category === 'security' ||
               $this->severity_level === 'critical';
    }

    /**
     * Check if this audit entry represents a compliance-relevant operation.
     */
    public function isComplianceRelevant(): bool
    {
        $complianceOperations = [
            'data_export',
            'data_import',
            'delete',
            'permission_change',
            'role_change',
        ];
        
        return in_array($this->operation, $complianceOperations) ||
               $this->category === 'compliance' ||
               in_array($this->table_name, ['global_users', 'user_tenant_memberships']);
    }

    /**
     * Get the risk score for this audit entry.
     */
    public function getRiskScoreAttribute(): int
    {
        $score = 0;
        
        // Base score by operation
        $operationScores = [
            'delete' => 8,
            'permission_change' => 7,
            'role_change' => 7,
            'security_event' => 9,
            'data_export' => 6,
            'password_change' => 5,
            'update' => 3,
            'create' => 2,
            'login' => 1,
        ];
        
        $score += $operationScores[$this->operation] ?? 1;
        
        // Severity multiplier
        $severityMultipliers = [
            'critical' => 3,
            'high' => 2,
            'medium' => 1.5,
            'low' => 1,
        ];
        
        $score *= $severityMultipliers[$this->severity_level] ?? 1;
        
        // Sensitive table bonus
        $sensitiveTables = ['global_users', 'user_tenant_memberships', 'payments'];
        if (in_array($this->table_name, $sensitiveTables)) {
            $score += 2;
        }
        
        return min(10, round($score));
    }

    /**
     * Scope to filter by operation.
     */
    public function scopeOperation($query, string $operation)
    {
        return $query->where('operation', $operation);
    }

    /**
     * Scope to filter by table.
     */
    public function scopeTable($query, string $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, string $globalUserId)
    {
        return $query->where('global_user_id', $globalUserId);
    }

    /**
     * Scope to filter by tenant.
     */
    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by severity level.
     */
    public function scopeSeverityLevel($query, string $severityLevel)
    {
        return $query->where('severity_level', $severityLevel);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, Carbon $startDate = null, Carbon $endDate = null)
    {
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        return $query;
    }

    /**
     * Scope to filter security-sensitive entries.
     */
    public function scopeSecuritySensitive($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('operation', ['login', 'logout', 'password_change', 'permission_change', 'role_change', 'security_event'])
              ->orWhere('category', 'security')
              ->orWhere('severity_level', 'critical');
        });
    }

    /**
     * Scope to filter compliance-relevant entries.
     */
    public function scopeComplianceRelevant($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('operation', ['data_export', 'data_import', 'delete', 'permission_change', 'role_change'])
              ->orWhere('category', 'compliance')
              ->orWhereIn('table_name', ['global_users', 'user_tenant_memberships']);
        });
    }

    /**
     * Scope to filter high-risk entries.
     */
    public function scopeHighRisk($query, int $minRiskScore = 7)
    {
        return $query->where(function ($q) use ($minRiskScore) {
            // This is a simplified version - in practice, you'd calculate risk score in the database
            $q->whereIn('operation', ['delete', 'permission_change', 'role_change', 'security_event'])
              ->orWhere('severity_level', 'critical')
              ->orWhere('severity_level', 'high');
        });
    }

    /**
     * Scope to search audit entries.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('table_name', 'ILIKE', "%{$search}%")
              ->orWhere('operation', 'ILIKE', "%{$search}%")
              ->orWhere('record_id', 'ILIKE', "%{$search}%")
              ->orWhere('ip_address', 'ILIKE', "%{$search}%")
              ->orWhereHas('user', function ($uq) use ($search) {
                  $uq->where('name', 'ILIKE', "%{$search}%")
                     ->orWhere('email', 'ILIKE', "%{$search}%");
              });
        });
    }

    /**
     * Scope to get recent entries.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours))
                     ->orderBy('created_at', 'desc');
    }

    /**
     * Create an audit entry for a model operation.
     */
    public static function logModelOperation(
        string $operation,
        Model $model,
        string $globalUserId = null,
        string $tenantId = null,
        array $metadata = []
    ): self {
        $oldValues = $operation === 'update' ? $model->getOriginal() : null;
        $newValues = $model->getAttributes();
        $changedFields = $operation === 'update' ? array_keys($model->getDirty()) : null;
        
        // Mask sensitive fields
        if ($oldValues) {
            $oldValues = self::maskSensitiveFields($oldValues);
        }
        $newValues = self::maskSensitiveFields($newValues);
        
        return self::create([
            'global_user_id' => $globalUserId,
            'tenant_id' => $tenantId,
            'table_name' => $model->getTable(),
            'record_id' => $model->getKey(),
            'operation' => $operation,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'request_id' => request()->header('X-Request-ID'),
            'metadata' => $metadata,
            'severity_level' => self::determineSeverityLevel($operation, $model->getTable()),
            'category' => self::determineCategory($operation, $model->getTable()),
        ]);
    }

    /**
     * Create an audit entry for a system event.
     */
    public static function logSystemEvent(
        string $operation,
        string $description,
        string $globalUserId = null,
        string $tenantId = null,
        array $metadata = [],
        string $severityLevel = 'medium'
    ): self {
        return self::create([
            'global_user_id' => $globalUserId,
            'tenant_id' => $tenantId,
            'table_name' => 'system',
            'operation' => $operation,
            'new_values' => ['description' => $description],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'request_id' => request()->header('X-Request-ID'),
            'metadata' => $metadata,
            'severity_level' => $severityLevel,
            'category' => 'system_administration',
        ]);
    }

    /**
     * Mask sensitive fields in data.
     */
    protected static function maskSensitiveFields(array $data): array
    {
        foreach (self::SENSITIVE_FIELDS as $field) {
            if (isset($data[$field]) && $data[$field] !== null) {
                $data[$field] = '[MASKED]';
            }
        }
        
        return $data;
    }

    /**
     * Determine severity level based on operation and table.
     */
    protected static function determineSeverityLevel(string $operation, string $tableName): string
    {
        // Critical operations
        if (in_array($operation, ['delete', 'security_event'])) {
            return 'critical';
        }
        
        // High severity operations
        if (in_array($operation, ['permission_change', 'role_change', 'data_export'])) {
            return 'high';
        }
        
        // Sensitive tables
        if (in_array($tableName, ['global_users', 'user_tenant_memberships', 'payments'])) {
            return $operation === 'update' ? 'medium' : 'high';
        }
        
        // Default
        return 'low';
    }

    /**
     * Determine category based on operation and table.
     */
    protected static function determineCategory(string $operation, string $tableName): string
    {
        // Authentication/Authorization operations
        if (in_array($operation, ['login', 'logout', 'password_change'])) {
            return 'authentication';
        }
        
        if (in_array($operation, ['permission_change', 'role_change'])) {
            return 'authorization';
        }
        
        // Table-based categories
        $tableCategories = [
            'global_users' => 'user_management',
            'user_tenant_memberships' => 'tenant_management',
            'global_courses' => 'course_management',
            'tenant_course_offerings' => 'course_management',
            'enrollments' => 'enrollment_management',
            'payments' => 'financial',
            'grades' => 'data_modification',
            'transcripts' => 'data_modification',
        ];
        
        if (isset($tableCategories[$tableName])) {
            return $tableCategories[$tableName];
        }
        
        // Operation-based categories
        if (in_array($operation, ['create', 'update', 'delete'])) {
            return 'data_modification';
        }
        
        return 'information';
    }

    /**
     * Get audit statistics for a date range.
     */
    public static function getStatistics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $query = self::query();
        
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        
        $total = $query->count();
        $byOperation = $query->groupBy('operation')->selectRaw('operation, count(*) as count')->pluck('count', 'operation');
        $bySeverity = $query->groupBy('severity_level')->selectRaw('severity_level, count(*) as count')->pluck('count', 'severity_level');
        $byCategory = $query->groupBy('category')->selectRaw('category, count(*) as count')->pluck('count', 'category');
        $byTable = $query->groupBy('table_name')->selectRaw('table_name, count(*) as count')->pluck('count', 'table_name');
        
        return [
            'total_entries' => $total,
            'by_operation' => $byOperation->toArray(),
            'by_severity' => $bySeverity->toArray(),
            'by_category' => $byCategory->toArray(),
            'by_table' => $byTable->toArray(),
            'security_sensitive' => $query->securitySensitive()->count(),
            'compliance_relevant' => $query->complianceRelevant()->count(),
            'high_risk' => $query->highRisk()->count(),
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        // Prevent modification of audit records
        static::updating(function ($model) {
            return false; // Audit records should be immutable
        });
        
        static::deleting(function ($model) {
            return false; // Audit records should not be deleted
        });
    }
}