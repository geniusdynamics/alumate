<?php
// ABOUTME: Eloquent model for global_users table in hybrid tenancy architecture
// ABOUTME: Manages cross-tenant user data and relationships with tenant memberships

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class GlobalUser extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'global_users';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'global_user_id';

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'email',
        'first_name',
        'last_name',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'preferences',
        'metadata',
        'email_verified_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'preferences' => 'array',
        'metadata' => 'array',
        'password' => 'hashed',
    ];

    /**
     * Get the tenant memberships for the user.
     */
    public function tenantMemberships(): HasMany
    {
        return $this->hasMany(UserTenantMembership::class, 'global_user_id', 'global_user_id');
    }

    /**
     * Get active tenant memberships for the user.
     */
    public function activeTenantMemberships(): HasMany
    {
        return $this->tenantMemberships()->where('status', 'active');
    }

    /**
     * Get the tenants this user belongs to.
     */
    public function tenants()
    {
        return $this->belongsToManyThrough(
            Tenant::class,
            UserTenantMembership::class,
            'global_user_id',
            'id',
            'global_user_id',
            'tenant_id'
        );
    }

    /**
     * Get audit trail entries for this user.
     */
    public function auditTrail(): HasMany
    {
        return $this->hasMany(AuditTrail::class, 'global_user_id', 'global_user_id');
    }

    /**
     * Check if user has access to a specific tenant.
     */
    public function hasAccessToTenant(string $tenantId): bool
    {
        return $this->activeTenantMemberships()
            ->where('tenant_id', $tenantId)
            ->exists();
    }

    /**
     * Get user's role in a specific tenant.
     */
    public function getRoleInTenant(string $tenantId): ?string
    {
        $membership = $this->activeTenantMemberships()
            ->where('tenant_id', $tenantId)
            ->first();

        return $membership?->role;
    }

    /**
     * Get user's permissions in a specific tenant.
     */
    public function getPermissionsInTenant(string $tenantId): array
    {
        $membership = $this->activeTenantMemberships()
            ->where('tenant_id', $tenantId)
            ->first();

        return $membership?->permissions ?? [];
    }

    /**
     * Check if user has a specific role in any tenant.
     */
    public function hasRole(string $role): bool
    {
        return $this->activeTenantMemberships()
            ->where('role', $role)
            ->exists();
    }

    /**
     * Check if user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Get all tenants where user has a specific role.
     */
    public function getTenantsWithRole(string $role)
    {
        return $this->tenantMemberships()
            ->where('role', $role)
            ->where('status', 'active')
            ->with('tenant')
            ->get()
            ->pluck('tenant');
    }

    /**
     * Get user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get user's initials.
     */
    public function getInitialsAttribute(): string
    {
        return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
    }

    /**
     * Scope to search users by name or email.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'ILIKE', "%{$search}%")
              ->orWhere('last_name', 'ILIKE', "%{$search}%")
              ->orWhere('email', 'ILIKE', "%{$search}%")
              ->orWhereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", ["%{$search}%"]);
        });
    }

    /**
     * Scope to filter users by tenant.
     */
    public function scopeInTenant($query, string $tenantId)
    {
        return $query->whereHas('activeTenantMemberships', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        });
    }

    /**
     * Scope to filter users by role.
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->whereHas('activeTenantMemberships', function ($q) use ($role) {
            $q->where('role', $role);
        });
    }

    /**
     * Create or update tenant membership for this user.
     */
    public function addToTenant(string $tenantId, string $role = 'student', array $tenantSpecificData = []): UserTenantMembership
    {
        return $this->tenantMemberships()->updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'role' => $role,
                'status' => 'active',
                'joined_at' => now(),
                'tenant_specific_data' => $tenantSpecificData,
                'last_active_at' => now(),
            ]
        );
    }

    /**
     * Remove user from tenant.
     */
    public function removeFromTenant(string $tenantId): bool
    {
        return $this->tenantMemberships()
            ->where('tenant_id', $tenantId)
            ->delete() > 0;
    }

    /**
     * Update last active timestamp for a tenant.
     */
    public function updateLastActiveInTenant(string $tenantId): void
    {
        $this->tenantMemberships()
            ->where('tenant_id', $tenantId)
            ->update(['last_active_at' => now()]);
    }

    /**
     * Get user's activity summary across all tenants.
     */
    public function getActivitySummary(): array
    {
        $memberships = $this->activeTenantMemberships()->with('tenant')->get();
        
        return [
            'total_tenants' => $memberships->count(),
            'roles' => $memberships->pluck('role')->unique()->values()->toArray(),
            'most_recent_activity' => $memberships->max('last_active_at'),
            'tenants' => $memberships->map(function ($membership) {
                return [
                    'tenant_id' => $membership->tenant_id,
                    'tenant_name' => $membership->tenant->name ?? 'Unknown',
                    'role' => $membership->role,
                    'joined_at' => $membership->joined_at,
                    'last_active_at' => $membership->last_active_at,
                ];
            })->toArray(),
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically create audit trail entries
        static::created(function ($model) {
            AuditTrail::create([
                'global_user_id' => $model->global_user_id,
                'table_name' => 'global_users',
                'record_id' => $model->global_user_id,
                'operation' => 'create',
                'new_values' => $model->getAttributes(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::updated(function ($model) {
            AuditTrail::create([
                'global_user_id' => $model->global_user_id,
                'table_name' => 'global_users',
                'record_id' => $model->global_user_id,
                'operation' => 'update',
                'old_values' => $model->getOriginal(),
                'new_values' => $model->getAttributes(),
                'changed_fields' => array_keys($model->getDirty()),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        static::deleted(function ($model) {
            AuditTrail::create([
                'global_user_id' => $model->global_user_id,
                'table_name' => 'global_users',
                'record_id' => $model->global_user_id,
                'operation' => 'delete',
                'old_values' => $model->getOriginal(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }
}