<?php
// ABOUTME: TenantUser pivot model for managing user-tenant relationships in schema-based multi-tenancy
// ABOUTME: Handles role assignments, permissions, and access control for users within specific tenants

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use App\Services\TenantContextService;

class TenantUser extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tenant_users';

    protected $fillable = [
        // 'tenant_id', // Removed for schema-based tenancy - tenant context handled by schema
        'user_id',
        'role',
        'is_active',
        'joined_at',
        'invited_by',
        'invitation_token',
        'invitation_expires_at',
        'last_accessed_at',
        'permissions',
        'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
        'invitation_expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'permissions' => 'array',
        'metadata' => 'array'
    ];

    protected $dates = [
        'deleted_at'
    ];

    // Available roles
    const ROLE_TENANT_ADMIN = 'tenant_admin';
    const ROLE_INSTRUCTOR = 'instructor';
    const ROLE_STAFF = 'staff';
    const ROLE_STUDENT = 'student';
    const ROLE_VIEWER = 'viewer';

    // Permission constants
    const PERMISSION_MANAGE_USERS = 'manage_users';
    const PERMISSION_MANAGE_COURSES = 'manage_courses';
    const PERMISSION_MANAGE_STUDENTS = 'manage_students';
    const PERMISSION_ASSIGN_GRADES = 'assign_grades';
    const PERMISSION_VIEW_ANALYTICS = 'view_analytics';
    const PERMISSION_MANAGE_SETTINGS = 'manage_settings';
    const PERMISSION_EXPORT_DATA = 'export_data';
    const PERMISSION_IMPORT_DATA = 'import_data';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set default permissions based on role when creating
        static::creating(function ($tenantUser) {
            if (empty($tenantUser->permissions)) {
                $tenantUser->permissions = static::getDefaultPermissions($tenantUser->role);
            }

            if (empty($tenantUser->joined_at)) {
                $tenantUser->joined_at = now();
            }
        });

        // Log tenant user activities
        static::created(function ($tenantUser) {
            $tenant = $tenantUser->getCurrentTenant();
            ActivityLog::logSystem('tenant_user_created', "User {$tenantUser->user->email} added to tenant {$tenant->name} with role {$tenantUser->role}", [
                'tenant_id' => $tenant->id,
                'user_id' => $tenantUser->user_id,
                'role' => $tenantUser->role
            ]);
        });

        static::updated(function ($tenantUser) {
            $changes = $tenantUser->getChanges();
            unset($changes['updated_at'], $changes['last_accessed_at']);
            
            if (!empty($changes)) {
                $tenant = $tenantUser->getCurrentTenant();
                ActivityLog::logSystem('tenant_user_updated', "Tenant user relationship updated for {$tenantUser->user->email}", [
                    'tenant_id' => $tenant->id,
                    'user_id' => $tenantUser->user_id,
                    'changes' => array_keys($changes)
                ]);
            }
        });

        static::deleted(function ($tenantUser) {
            $tenant = $tenantUser->getCurrentTenant();
            ActivityLog::logSystem('tenant_user_deleted', "User {$tenantUser->user->email} removed from tenant {$tenant->name}", [
                'tenant_id' => $tenant->id,
                'user_id' => $tenantUser->user_id,
                'role' => $tenantUser->role
            ]);
        });
    }

    /**
     * Get the current tenant context (schema-based tenancy)
     */
    public function getCurrentTenant()
    {
        return TenantContextService::getCurrentTenant();
    }

    /**
     * Legacy tenant relationship (for backward compatibility)
     */
    public function tenant(): BelongsTo
    {
        // In schema-based tenancy, this returns the current tenant context
        return $this->belongsTo(Tenant::class, 'id', 'id')
            ->where('id', TenantContextService::getCurrentTenant()?->id);
    }

    /**
     * Get the user that owns the tenant user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who invited this tenant user
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Scope for active tenant users
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for inactive tenant users
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope for specific role
     */
    public function scopeWithRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    /**
     * Scope for pending invitations
     */
    public function scopePendingInvitations(Builder $query): Builder
    {
        return $query->whereNotNull('invitation_token')
            ->where('invitation_expires_at', '>', now())
            ->where('is_active', false);
    }

    /**
     * Scope for expired invitations
     */
    public function scopeExpiredInvitations(Builder $query): Builder
    {
        return $query->whereNotNull('invitation_token')
            ->where('invitation_expires_at', '<=', now());
    }

    /**
     * Scope for recently accessed
     */
    public function scopeRecentlyAccessed(Builder $query, int $days = 30): Builder
    {
        return $query->where('last_accessed_at', '>=', now()->subDays($days));
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    /**
     * Grant permission to user
     */
    public function grantPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        
        if (!in_array($permission, $permissions)) {
            $permissions[] = $permission;
            return $this->update(['permissions' => $permissions]);
        }
        
        return true;
    }

    /**
     * Revoke permission from user
     */
    public function revokePermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];
        $permissions = array_filter($permissions, fn($p) => $p !== $permission);
        
        return $this->update(['permissions' => array_values($permissions)]);
    }

    /**
     * Set multiple permissions
     */
    public function setPermissions(array $permissions): bool
    {
        return $this->update(['permissions' => $permissions]);
    }

    /**
     * Get all permissions for this role
     */
    public function getAllPermissions(): array
    {
        return $this->permissions ?? static::getDefaultPermissions($this->role);
    }

    /**
     * Check if invitation is valid
     */
    public function isInvitationValid(): bool
    {
        return !empty($this->invitation_token) && 
               $this->invitation_expires_at && 
               $this->invitation_expires_at->isFuture();
    }

    /**
     * Accept invitation
     */
    public function acceptInvitation(): bool
    {
        if (!$this->isInvitationValid()) {
            return false;
        }

        $updated = $this->update([
            'is_active' => true,
            'invitation_token' => null,
            'invitation_expires_at' => null,
            'joined_at' => now()
        ]);

        if ($updated) {
            $tenant = $this->getCurrentTenant();
            ActivityLog::logSystem('invitation_accepted', "User {$this->user->email} accepted invitation to tenant {$tenant->name}", [
                'tenant_id' => $tenant->id,
                'user_id' => $this->user_id,
                'role' => $this->role
            ]);
        }

        return $updated;
    }

    /**
     * Decline invitation
     */
    public function declineInvitation(): bool
    {
        $tenant = $this->getCurrentTenant();
        ActivityLog::logSystem('invitation_declined', "User {$this->user->email} declined invitation to tenant {$tenant->name}", [
            'tenant_id' => $tenant->id,
            'user_id' => $this->user_id,
            'role' => $this->role
        ]);

        return $this->delete();
    }

    /**
     * Generate invitation token
     */
    public function generateInvitationToken(int $expiresInHours = 72): string
    {
        $token = bin2hex(random_bytes(32));
        
        $this->update([
            'invitation_token' => $token,
            'invitation_expires_at' => now()->addHours($expiresInHours)
        ]);

        return $token;
    }

    /**
     * Update last accessed timestamp
     */
    public function updateLastAccessed(): bool
    {
        return $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Activate user in tenant
     */
    public function activate(): bool
    {
        $updated = $this->update(['is_active' => true]);

        if ($updated) {
            $tenant = $this->getCurrentTenant();
            ActivityLog::logSystem('tenant_user_activated', "User {$this->user->email} activated in tenant {$tenant->name}", [
                'tenant_id' => $tenant->id,
                'user_id' => $this->user_id
            ]);
        }

        return $updated;
    }

    /**
     * Deactivate user in tenant
     */
    public function deactivate(): bool
    {
        $updated = $this->update(['is_active' => false]);

        if ($updated) {
            $tenant = $this->getCurrentTenant();
            ActivityLog::logSystem('tenant_user_deactivated', "User {$this->user->email} deactivated in tenant {$tenant->name}", [
                'tenant_id' => $tenant->id,
                'user_id' => $this->user_id
            ]);
        }

        return $updated;
    }

    /**
     * Change user role
     */
    public function changeRole(string $newRole): bool
    {
        $oldRole = $this->role;
        $newPermissions = static::getDefaultPermissions($newRole);
        
        $updated = $this->update([
            'role' => $newRole,
            'permissions' => $newPermissions
        ]);

        if ($updated) {
            $tenant = $this->getCurrentTenant();
            ActivityLog::logSecurity('tenant_user_role_changed', "User {$this->user->email} role changed from {$oldRole} to {$newRole} in tenant {$tenant->name}", [
                'tenant_id' => $tenant->id,
                'user_id' => $this->user_id,
                'old_role' => $oldRole,
                'new_role' => $newRole
            ]);
        }

        return $updated;
    }

    /**
     * Get default permissions for a role
     */
    public static function getDefaultPermissions(string $role): array
    {
        return match ($role) {
            self::ROLE_TENANT_ADMIN => [
                self::PERMISSION_MANAGE_USERS,
                self::PERMISSION_MANAGE_COURSES,
                self::PERMISSION_MANAGE_STUDENTS,
                self::PERMISSION_ASSIGN_GRADES,
                self::PERMISSION_VIEW_ANALYTICS,
                self::PERMISSION_MANAGE_SETTINGS,
                self::PERMISSION_EXPORT_DATA,
                self::PERMISSION_IMPORT_DATA
            ],
            self::ROLE_INSTRUCTOR => [
                self::PERMISSION_MANAGE_COURSES,
                self::PERMISSION_MANAGE_STUDENTS,
                self::PERMISSION_ASSIGN_GRADES,
                self::PERMISSION_VIEW_ANALYTICS,
                self::PERMISSION_EXPORT_DATA
            ],
            self::ROLE_STAFF => [
                self::PERMISSION_MANAGE_STUDENTS,
                self::PERMISSION_VIEW_ANALYTICS,
                self::PERMISSION_EXPORT_DATA
            ],
            self::ROLE_STUDENT => [],
            self::ROLE_VIEWER => [
                self::PERMISSION_VIEW_ANALYTICS
            ],
            default => []
        };
    }

    /**
     * Get all available roles
     */
    public static function getAvailableRoles(): array
    {
        return [
            self::ROLE_TENANT_ADMIN => 'Tenant Administrator',
            self::ROLE_INSTRUCTOR => 'Instructor',
            self::ROLE_STAFF => 'Staff',
            self::ROLE_STUDENT => 'Student',
            self::ROLE_VIEWER => 'Viewer'
        ];
    }

    /**
     * Get all available permissions
     */
    public static function getAvailablePermissions(): array
    {
        return [
            self::PERMISSION_MANAGE_USERS => 'Manage Users',
            self::PERMISSION_MANAGE_COURSES => 'Manage Courses',
            self::PERMISSION_MANAGE_STUDENTS => 'Manage Students',
            self::PERMISSION_ASSIGN_GRADES => 'Assign Grades',
            self::PERMISSION_VIEW_ANALYTICS => 'View Analytics',
            self::PERMISSION_MANAGE_SETTINGS => 'Manage Settings',
            self::PERMISSION_EXPORT_DATA => 'Export Data',
            self::PERMISSION_IMPORT_DATA => 'Import Data'
        ];
    }

    /**
     * Get role hierarchy (higher number = more permissions)
     */
    public static function getRoleHierarchy(): array
    {
        return [
            self::ROLE_VIEWER => 1,
            self::ROLE_STUDENT => 2,
            self::ROLE_STAFF => 3,
            self::ROLE_INSTRUCTOR => 4,
            self::ROLE_TENANT_ADMIN => 5
        ];
    }

    /**
     * Check if role has higher or equal permissions than another role
     */
    public function hasRoleLevel(string $requiredRole): bool
    {
        $hierarchy = static::getRoleHierarchy();
        $currentLevel = $hierarchy[$this->role] ?? 0;
        $requiredLevel = $hierarchy[$requiredRole] ?? 0;
        
        return $currentLevel >= $requiredLevel;
    }

    /**
     * Get tenant user statistics
     */
    public function getStatistics(): array
    {
        return [
            'days_in_tenant' => $this->joined_at ? $this->joined_at->diffInDays(now()) : 0,
            'last_accessed' => $this->last_accessed_at,
            'days_since_last_access' => $this->last_accessed_at ? $this->last_accessed_at->diffInDays(now()) : null,
            'is_active' => $this->is_active,
            'role' => $this->role,
            'permissions_count' => count($this->permissions ?? []),
            'has_pending_invitation' => $this->isInvitationValid(),
            'invited_by' => $this->invitedBy?->name
        ];
    }

    /**
     * Bulk update tenant users
     */
    public static function bulkUpdateRole(array $tenantUserIds, string $newRole): int
    {
        $newPermissions = static::getDefaultPermissions($newRole);
        
        $updated = static::whereIn('id', $tenantUserIds)
            ->update([
                'role' => $newRole,
                'permissions' => $newPermissions
            ]);

        // Log bulk update
        ActivityLog::logSystem('bulk_role_update', "Bulk role update: {$updated} users updated to role {$newRole}", [
            'updated_count' => $updated,
            'new_role' => $newRole,
            'tenant_user_ids' => $tenantUserIds
        ]);

        return $updated;
    }

    /**
     * Bulk activate/deactivate tenant users
     */
    public static function bulkUpdateStatus(array $tenantUserIds, bool $isActive): int
    {
        $updated = static::whereIn('id', $tenantUserIds)
            ->update(['is_active' => $isActive]);

        $status = $isActive ? 'activated' : 'deactivated';
        ActivityLog::logSystem("bulk_user_{$status}", "Bulk status update: {$updated} users {$status}", [
            'updated_count' => $updated,
            'is_active' => $isActive,
            'tenant_user_ids' => $tenantUserIds
        ]);

        return $updated;
    }

    /**
     * Clean up expired invitations
     */
    public static function cleanupExpiredInvitations(): int
    {
        $deleted = static::expiredInvitations()->delete();
        
        if ($deleted > 0) {
            ActivityLog::logSystem('expired_invitations_cleanup', "Cleaned up {$deleted} expired invitations", [
                'deleted_count' => $deleted
            ]);
        }
        
        return $deleted;
    }

    /**
     * Get tenant users summary for current tenant (schema-based tenancy)
     */
    public static function getTenantSummary(): array
    {
        // In schema-based tenancy, all records in current schema belong to current tenant
        $query = static::query();
        
        return [
            'total_users' => $query->count(),
            'active_users' => $query->active()->count(),
            'inactive_users' => $query->inactive()->count(),
            'pending_invitations' => $query->pendingInvitations()->count(),
            'expired_invitations' => $query->expiredInvitations()->count(),
            'roles_breakdown' => $query->selectRaw('role, count(*) as count')
                ->groupBy('role')
                ->pluck('count', 'role')
                ->toArray(),
            'recently_active' => $query->recentlyAccessed(7)->count()
        ];
    }
}