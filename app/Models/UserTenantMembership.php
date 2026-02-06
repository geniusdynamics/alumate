<?php
// ABOUTME: Eloquent model for user_tenant_memberships table in schema-based tenancy architecture
// ABOUTME: Manages the many-to-many relationship between global users and tenants with roles and permissions

namespace App\Models;

use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class UserTenantMembership extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'user_tenant_memberships';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'global_user_id',
        // 'tenant_id', // Removed for schema-based tenancy
        'role',
        'status',
        'joined_at',
        'last_active_at',
        'tenant_specific_data',
        'permissions',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'joined_at' => 'datetime',
        'last_active_at' => 'datetime',
        'tenant_specific_data' => 'array',
        'permissions' => 'array',
    ];

    /**
     * Available roles in the system.
     */
    public const ROLES = [
        'super_admin' => 'Super Administrator',
        'admin' => 'Administrator',
        'instructor' => 'Instructor',
        'student' => 'Student',
        'guest' => 'Guest',
    ];

    /**
     * Available statuses for memberships.
     */
    public const STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'suspended' => 'Suspended',
        'pending' => 'Pending',
    ];

    /**
     * Role hierarchy for permission checking.
     */
    public const ROLE_HIERARCHY = [
        'super_admin' => 100,
        'admin' => 80,
        'instructor' => 60,
        'student' => 40,
        'guest' => 20,
    ];

    /**
     * Default permissions for each role.
     */
    public const DEFAULT_PERMISSIONS = [
        'super_admin' => [
            'manage_all_tenants',
            'view_analytics',
            'manage_global_courses',
            'manage_users',
            'manage_system_settings',
        ],
        'admin' => [
            'manage_tenant',
            'manage_courses',
            'manage_enrollments',
            'view_reports',
            'manage_instructors',
            'manage_students',
        ],
        'instructor' => [
            'manage_assigned_courses',
            'view_student_progress',
            'grade_assignments',
            'communicate_with_students',
            'create_course_content',
        ],
        'student' => [
            'view_courses',
            'enroll_in_courses',
            'submit_assignments',
            'view_grades',
            'communicate_with_instructors',
        ],
        'guest' => [
            'view_public_courses',
            'view_basic_information',
        ],
    ];

    /**
     * Get the global user that owns the membership.
     */
    public function globalUser(): BelongsTo
    {
        return $this->belongsTo(GlobalUser::class, 'global_user_id', 'global_user_id');
    }

    /**
     * Get the tenant that owns the membership.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * Check if the membership is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the membership is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the membership is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Activate the membership.
     */
    public function activate(): bool
    {
        return $this->update([
            'status' => 'active',
            'last_active_at' => now(),
        ]);
    }

    /**
     * Suspend the membership.
     */
    public function suspend(): bool
    {
        return $this->update(['status' => 'suspended']);
    }

    /**
     * Deactivate the membership.
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => 'inactive']);
    }

    /**
     * Get the role display name.
     */
    public function getRoleDisplayNameAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Get the role hierarchy level.
     */
    public function getRoleLevel(): int
    {
        return self::ROLE_HIERARCHY[$this->role] ?? 0;
    }

    /**
     * Check if this role has higher or equal authority than another role.
     */
    public function hasAuthorityOver(string $otherRole): bool
    {
        return $this->getRoleLevel() >= (self::ROLE_HIERARCHY[$otherRole] ?? 0);
    }

    /**
     * Get all permissions for this membership (default + custom).
     */
    public function getAllPermissions(): array
    {
        $defaultPermissions = self::DEFAULT_PERMISSIONS[$this->role] ?? [];
        $customPermissions = $this->permissions ?? [];
        
        return array_unique(array_merge($defaultPermissions, $customPermissions));
    }

    /**
     * Check if the membership has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getAllPermissions());
    }

    /**
     * Add a custom permission to this membership.
     */
    public function addPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            return $this->update(['permissions' => $permissions]);
        }
        
        return true;
    }

    /**
     * Remove a custom permission from this membership.
     */
    public function removePermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        $permissions = array_filter($permissions, fn($p) => $p !== $permission);
        
        return $this->update(['permissions' => array_values($permissions)]);
    }

    /**
     * Update the last active timestamp.
     */
    public function updateLastActive(): bool
    {
        return $this->update(['last_active_at' => now()]);
    }

    /**
     * Get days since last activity.
     */
    public function getDaysSinceLastActiveAttribute(): ?int
    {
        if (!$this->last_active_at) {
            return null;
        }
        
        return $this->last_active_at->diffInDays(now());
    }

    /**
     * Get days since joining.
     */
    public function getDaysSinceJoinedAttribute(): int
    {
        return $this->joined_at->diffInDays(now());
    }

    /**
     * Check if the user has been inactive for a specified number of days.
     */
    public function isInactiveFor(int $days): bool
    {
        if (!$this->last_active_at) {
            return $this->joined_at->diffInDays(now()) >= $days;
        }
        
        return $this->last_active_at->diffInDays(now()) >= $days;
    }

    /**
     * Scope to filter by role.
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter active memberships.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the current tenant from context.
     */
    public function getCurrentTenant()
    {
        return app(TenantContextService::class)->getCurrentTenant();
    }

    /**
     * Scope to filter by tenant (legacy compatibility - returns query unchanged in schema-based tenancy).
     */
    public function scopeForTenant($query, string $tenantId)
    {
        // In schema-based tenancy, tenant filtering is handled by database schema
        // This scope is maintained for legacy compatibility but returns query unchanged
        return $query;
    }

    /**
     * Scope to filter inactive users.
     */
    public function scopeInactiveFor($query, int $days)
    {
        $cutoffDate = Carbon::now()->subDays($days);
        
        return $query->where(function ($q) use ($cutoffDate) {
            $q->where('last_active_at', '<', $cutoffDate)
              ->orWhere(function ($subQ) use ($cutoffDate) {
                  $subQ->whereNull('last_active_at')
                       ->where('joined_at', '<', $cutoffDate);
              });
        });
    }

    /**
     * Scope to get memberships with specific permissions.
     */
    public function scopeWithPermission($query, string $permission)
    {
        return $query->where(function ($q) use ($permission) {
            // Check if permission is in default permissions for the role
            $rolesWithPermission = [];
            foreach (self::DEFAULT_PERMISSIONS as $role => $permissions) {
                if (in_array($permission, $permissions)) {
                    $rolesWithPermission[] = $role;
                }
            }
            
            if (!empty($rolesWithPermission)) {
                $q->whereIn('role', $rolesWithPermission);
            }
            
            // Also check custom permissions
            $q->orWhereJsonContains('permissions', $permission);
        });
    }

    /**
     * Get membership statistics for a tenant (schema-based tenancy - tenantId parameter ignored).
     */
    public static function getStatsForTenant(string $tenantId = null): array
    {
        // In schema-based tenancy, we get all memberships from current schema
        $memberships = self::all();
        
        return [
            'total' => $memberships->count(),
            'active' => $memberships->where('status', 'active')->count(),
            'pending' => $memberships->where('status', 'pending')->count(),
            'suspended' => $memberships->where('status', 'suspended')->count(),
            'inactive' => $memberships->where('status', 'inactive')->count(),
            'by_role' => $memberships->groupBy('role')->map->count()->toArray(),
            'recent_joins' => $memberships->where('joined_at', '>=', now()->subDays(30))->count(),
            'inactive_30_days' => $memberships->filter(fn($m) => $m->isInactiveFor(30))->count(),
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set default joined_at timestamp
        static::creating(function ($model) {
            if (!$model->joined_at) {
                $model->joined_at = now();
            }
        });

        // Create audit trail entries
        static::created(function ($model) {
            $tenant = $model->getCurrentTenant();
            AuditTrail::create([
                'global_user_id' => $model->global_user_id,
                'tenant_id' => $tenant->id,
                'table_name' => 'user_tenant_memberships',
                'record_id' => $model->id,
                'operation' => 'create',
                'new_values' => $model->getAttributes(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::updated(function ($model) {
            $tenant = $model->getCurrentTenant();
            AuditTrail::create([
                'global_user_id' => $model->global_user_id,
                'tenant_id' => $tenant->id,
                'table_name' => 'user_tenant_memberships',
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
            $tenant = $model->getCurrentTenant();
            AuditTrail::create([
                'global_user_id' => $model->global_user_id,
                'tenant_id' => $tenant->id,
                'table_name' => 'user_tenant_memberships',
                'record_id' => $model->id,
                'operation' => 'delete',
                'old_values' => $model->getOriginal(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }
}