<?php
// ABOUTME: User model for schema-based multi-tenancy handling authentication and user management
// ABOUTME: Manages users across tenants with role-based access control and tenant context awareness

namespace App\Models;

use App\Services\TenantContextService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
// use Laravel\Sanctum\HasApiTokens; // Commented out - Sanctum not installed
use Spatie\Permission\Traits\HasRoles;
use Exception;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'first_name',
        'last_name',
        'phone',
        'avatar',
        'timezone',
        'locale',
        'is_active',
        'is_super_admin',
        'last_login_at',
        'last_login_ip',
        'password_changed_at',
        'two_factor_enabled',
        'two_factor_secret',
        'preferences',
        'metadata'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'is_active' => 'boolean',
        'is_super_admin' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'preferences' => 'array',
        'metadata' => 'array'
    ];

    protected $dates = [
        'deleted_at'
    ];

    protected $appends = [
        'full_name',
        'initials',
        'avatar_url',
        'accessible_tenants',
        'current_tenant_role'
    ];

    // User roles
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_TENANT_ADMIN = 'tenant_admin';
    const ROLE_INSTRUCTOR = 'instructor';
    const ROLE_STAFF = 'staff';
    const ROLE_STUDENT = 'student';
    const ROLE_VIEWER = 'viewer';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Log user activities
        static::created(function ($user) {
            ActivityLog::logSystem('user_created', "User created: {$user->email}", [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_name' => $user->name
            ]);
        });

        static::updated(function ($user) {
            $changes = $user->getChanges();
            unset($changes['updated_at'], $changes['password']); // Don't log password changes in detail
            
            if (!empty($changes)) {
                ActivityLog::logSystem('user_updated', "User updated: {$user->email}", [
                    'user_id' => $user->id,
                    'changes' => array_keys($changes)
                ]);
            }
        });

        static::deleted(function ($user) {
            ActivityLog::logSecurity('user_deleted', "User deleted: {$user->email}", [
                'user_id' => $user->id,
                'user_email' => $user->email
            ], ActivityLog::SEVERITY_CRITICAL);
        });
    }

    /**
     * Get user's full name
     */
    public function getFullNameAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return "{$this->first_name} {$this->last_name}";
        }
        
        return $this->name ?? $this->email;
    }

    /**
     * Get user's initials
     */
    public function getInitialsAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
        }
        
        $name = $this->name ?? $this->email;
        $parts = explode(' ', $name);
        
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }
        
        return strtoupper(substr($name, 0, 2));
    }

    /**
     * Get user's avatar URL
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/avatars/' . $this->avatar);
        }
        
        // Generate Gravatar URL as fallback
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=200";
    }

    /**
     * Get tenants accessible to this user
     */
    public function getAccessibleTenantsAttribute(): array
    {
        if ($this->is_super_admin) {
            return Tenant::all()->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'schema' => $tenant->schema_name,
                    'role' => 'super_admin'
                ];
            })->toArray();
        }

        return $this->tenantUsers()->with('tenant')->get()->map(function ($tenantUser) {
            return [
                'id' => $tenantUser->tenant->id,
                'name' => $tenantUser->tenant->name,
                'schema' => $tenantUser->tenant->schema_name,
                'role' => $tenantUser->role,
                'is_active' => $tenantUser->is_active
            ];
        })->toArray();
    }

    /**
     * Get current tenant role
     */
    public function getCurrentTenantRoleAttribute(): ?string
    {
        if ($this->is_super_admin) {
            return self::ROLE_SUPER_ADMIN;
        }

        $currentTenant = TenantContextService::getCurrentTenant();
        if (!$currentTenant) {
            return null;
        }

        $tenantUser = $this->tenantUsers()
            ->where('tenant_id', $currentTenant->id)
            ->first();

        return $tenantUser?->role;
    }

    /**
     * Get tenant users (pivot relationship)
     */
    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    /**
     * Get tenants this user belongs to
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
            ->withPivot(['role', 'is_active', 'joined_at', 'invited_by'])
            ->withTimestamps();
    }

    /**
     * Get activity logs for this user
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Get grades assigned by this user (as grader)
     */
    public function gradesAssigned(): HasMany
    {
        return $this->hasMany(Grade::class, 'grader_id');
    }

    /**
     * Scope for active users
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for super admins
     */
    public function scopeSuperAdmins(Builder $query): Builder
    {
        return $query->where('is_super_admin', true);
    }

    /**
     * Scope for verified users
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope for users with two-factor enabled
     */
    public function scopeWithTwoFactor(Builder $query): Builder
    {
        return $query->where('two_factor_enabled', true);
    }

    /**
     * Scope for users in specific tenant
     */
    public function scopeInTenant(Builder $query, int $tenantId): Builder
    {
        return $query->whereHas('tenants', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        });
    }

    /**
     * Scope for users with specific role in current tenant
     */
    public function scopeWithRole(Builder $query, string $role): Builder
    {
        $currentTenant = TenantContextService::getCurrentTenant();
        
        if (!$currentTenant) {
            return $query->where('is_super_admin', true)->where('1', '0'); // No results if no tenant context
        }

        return $query->whereHas('tenantUsers', function ($q) use ($role, $currentTenant) {
            $q->where('tenant_id', $currentTenant->id)
              ->where('role', $role)
              ->where('is_active', true);
        });
    }

    /**
     * Check if user has access to tenant
     */
    public function hasAccessToTenant(int $tenantId): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return $this->tenantUsers()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if user has role in current tenant
     */
    public function hasRoleInCurrentTenant(string $role): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $currentTenant = TenantContextService::getCurrentTenant();
        if (!$currentTenant) {
            return false;
        }

        return $this->tenantUsers()
            ->where('tenant_id', $currentTenant->id)
            ->where('role', $role)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if user can manage students
     */
    public function canManageStudents(): bool
    {
        return $this->hasRoleInCurrentTenant(self::ROLE_TENANT_ADMIN) ||
               $this->hasRoleInCurrentTenant(self::ROLE_INSTRUCTOR) ||
               $this->hasRoleInCurrentTenant(self::ROLE_STAFF);
    }

    /**
     * Check if user can manage courses
     */
    public function canManageCourses(): bool
    {
        return $this->hasRoleInCurrentTenant(self::ROLE_TENANT_ADMIN) ||
               $this->hasRoleInCurrentTenant(self::ROLE_INSTRUCTOR);
    }

    /**
     * Check if user can assign grades
     */
    public function canAssignGrades(): bool
    {
        return $this->hasRoleInCurrentTenant(self::ROLE_TENANT_ADMIN) ||
               $this->hasRoleInCurrentTenant(self::ROLE_INSTRUCTOR);
    }

    /**
     * Check if user can view analytics
     */
    public function canViewAnalytics(): bool
    {
        return $this->hasRoleInCurrentTenant(self::ROLE_TENANT_ADMIN) ||
               $this->hasRoleInCurrentTenant(self::ROLE_INSTRUCTOR) ||
               $this->hasRoleInCurrentTenant(self::ROLE_STAFF);
    }

    /**
     * Add user to tenant with role
     */
    public function addToTenant(int $tenantId, string $role, ?int $invitedBy = null): TenantUser
    {
        // Check if user already exists in tenant
        $existingTenantUser = $this->tenantUsers()
            ->where('tenant_id', $tenantId)
            ->first();

        if ($existingTenantUser) {
            // Update existing relationship
            $existingTenantUser->update([
                'role' => $role,
                'is_active' => true,
                'invited_by' => $invitedBy
            ]);
            
            return $existingTenantUser;
        }

        // Create new tenant user relationship
        $tenantUser = $this->tenantUsers()->create([
            'tenant_id' => $tenantId,
            'role' => $role,
            'is_active' => true,
            'joined_at' => now(),
            'invited_by' => $invitedBy
        ]);

        ActivityLog::logSystem('user_added_to_tenant', "User {$this->email} added to tenant {$tenantId} with role {$role}", [
            'user_id' => $this->id,
            'tenant_id' => $tenantId,
            'role' => $role,
            'invited_by' => $invitedBy
        ]);

        return $tenantUser;
    }

    /**
     * Remove user from tenant
     */
    public function removeFromTenant(int $tenantId): bool
    {
        $removed = $this->tenantUsers()
            ->where('tenant_id', $tenantId)
            ->delete();

        if ($removed) {
            ActivityLog::logSystem('user_removed_from_tenant', "User {$this->email} removed from tenant {$tenantId}", [
                'user_id' => $this->id,
                'tenant_id' => $tenantId
            ]);
        }

        return $removed > 0;
    }

    /**
     * Update user role in tenant
     */
    public function updateTenantRole(int $tenantId, string $newRole): bool
    {
        $tenantUser = $this->tenantUsers()
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$tenantUser) {
            return false;
        }

        $oldRole = $tenantUser->role;
        $updated = $tenantUser->update(['role' => $newRole]);

        if ($updated) {
            ActivityLog::logSecurity('user_role_changed', "User {$this->email} role changed from {$oldRole} to {$newRole} in tenant {$tenantId}", [
                'user_id' => $this->id,
                'tenant_id' => $tenantId,
                'old_role' => $oldRole,
                'new_role' => $newRole
            ]);
        }

        return $updated;
    }

    /**
     * Activate/deactivate user in tenant
     */
    public function setTenantStatus(int $tenantId, bool $isActive): bool
    {
        $tenantUser = $this->tenantUsers()
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$tenantUser) {
            return false;
        }

        $updated = $tenantUser->update(['is_active' => $isActive]);

        if ($updated) {
            $status = $isActive ? 'activated' : 'deactivated';
            ActivityLog::logSystem("user_{$status}_in_tenant", "User {$this->email} {$status} in tenant {$tenantId}", [
                'user_id' => $this->id,
                'tenant_id' => $tenantId,
                'is_active' => $isActive
            ]);
        }

        return $updated;
    }

    /**
     * Record login activity
     */
    public function recordLogin(string $ipAddress = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress ?? request()->ip()
        ]);

        ActivityLog::logAuth(ActivityLog::ACTION_LOGIN, $this->id, [
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * Record failed login attempt
     */
    public static function recordFailedLogin(string $email, string $ipAddress = null): void
    {
        ActivityLog::logAuth(ActivityLog::ACTION_FAILED_LOGIN, null, [
            'email' => $email,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * Enable two-factor authentication
     */
    public function enableTwoFactor(string $secret): bool
    {
        $updated = $this->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt($secret)
        ]);

        if ($updated) {
            ActivityLog::logSecurity('two_factor_enabled', "Two-factor authentication enabled for user {$this->email}", [
                'user_id' => $this->id
            ]);
        }

        return $updated;
    }

    /**
     * Disable two-factor authentication
     */
    public function disableTwoFactor(): bool
    {
        $updated = $this->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null
        ]);

        if ($updated) {
            ActivityLog::logSecurity('two_factor_disabled', "Two-factor authentication disabled for user {$this->email}", [
                'user_id' => $this->id
            ]);
        }

        return $updated;
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(array $preferences): bool
    {
        $currentPreferences = $this->preferences ?? [];
        $newPreferences = array_merge($currentPreferences, $preferences);
        
        return $this->update(['preferences' => $newPreferences]);
    }

    /**
     * Get user preference
     */
    public function getPreference(string $key, $default = null)
    {
        return data_get($this->preferences, $key, $default);
    }

    /**
     * Get user statistics
     */
    public function getStatistics(): array
    {
        $stats = [
            'total_logins' => $this->activityLogs()
                ->where('action', ActivityLog::ACTION_LOGIN)
                ->count(),
            'last_login' => $this->last_login_at,
            'account_age_days' => $this->created_at->diffInDays(now()),
            'tenants_count' => $this->tenants()->count(),
            'is_verified' => !is_null($this->email_verified_at),
            'has_two_factor' => $this->two_factor_enabled
        ];

        // Add tenant-specific stats if in tenant context
        $currentTenant = TenantContextService::getCurrentTenant();
        if ($currentTenant && $this->hasAccessToTenant($currentTenant->id)) {
            $stats['current_tenant'] = [
                'name' => $currentTenant->name,
                'role' => $this->current_tenant_role,
                'activities_count' => $this->activityLogs()
                    ->recent(30)
                    ->count()
            ];

            // Add role-specific stats
            if ($this->canAssignGrades()) {
                $stats['grades_assigned'] = $this->gradesAssigned()->count();
            }
        }

        return $stats;
    }

    /**
     * Get all available roles
     */
    public static function getAvailableRoles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Super Administrator',
            self::ROLE_TENANT_ADMIN => 'Tenant Administrator',
            self::ROLE_INSTRUCTOR => 'Instructor',
            self::ROLE_STAFF => 'Staff',
            self::ROLE_STUDENT => 'Student',
            self::ROLE_VIEWER => 'Viewer'
        ];
    }

    /**
     * Search users
     */
    public static function search(string $query): Builder
    {
        return static::where(function ($q) use ($query) {
            $q->where('name', 'ILIKE', "%{$query}%")
              ->orWhere('email', 'ILIKE', "%{$query}%")
              ->orWhere('first_name', 'ILIKE', "%{$query}%")
              ->orWhere('last_name', 'ILIKE', "%{$query}%");
        });
    }

    /**
     * Bulk invite users to tenant
     */
    public static function bulkInviteToTenant(array $emails, int $tenantId, string $role, int $invitedBy): array
    {
        $results = ['success' => [], 'errors' => []];
        
        foreach ($emails as $email) {
            try {
                $user = static::where('email', $email)->first();
                
                if (!$user) {
                    // Create new user
                    $user = static::create([
                        'email' => $email,
                        'name' => explode('@', $email)[0], // Temporary name
                        'password' => bcrypt(str()->random(16)), // Temporary password
                        'is_active' => false // Will be activated when they set password
                    ]);
                }
                
                $user->addToTenant($tenantId, $role, $invitedBy);
                $results['success'][] = $email;
                
            } catch (Exception $e) {
                $results['errors'][] = "Error inviting {$email}: " . $e->getMessage();
            }
        }
        
        return $results;
    }
}