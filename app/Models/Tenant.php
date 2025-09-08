<?php
// ABOUTME: Tenant model for schema-based multi-tenancy management
// ABOUTME: Manages tenant information and schema operations for multi-tenant architecture

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\TenantContextService;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'database_name',
        'schema_name',
        'status',
        'settings',
        'subscription_plan',
        'subscription_status',
        'trial_ends_at',
        'created_by'
    ];

    protected $casts = [
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $attributes = [
        'status' => 'active',
        'subscription_status' => 'trial'
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($tenant) {
            // Generate schema name if not provided
            if (!$tenant->schema_name) {
                $tenant->schema_name = 'tenant_' . $tenant->id;
            }
        });

        static::created(function ($tenant) {
            // Create tenant schema after tenant is created
            $tenantService = app(TenantContextService::class);
            $tenantService->createTenantSchema($tenant->id);
        });

        static::deleting(function ($tenant) {
            // Drop tenant schema when tenant is deleted
            $tenantService = app(TenantContextService::class);
            $tenantService->dropTenantSchema($tenant->id);
        });
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Generate schema name for this tenant
     */
    public function getSchemaName(): string
    {
        return $this->schema_name ?: 'tenant_' . $this->id;
    }

    /**
     * Check if tenant schema exists
     */
    public function schemaExists(): bool
    {
        $tenantService = app(TenantContextService::class);
        return $tenantService->tenantSchemaExists($this->id);
    }

    /**
     * Create tenant schema
     */
    public function createSchema(): string
    {
        $tenantService = app(TenantContextService::class);
        $schemaName = $tenantService->createTenantSchema($this->id);
        
        $this->update(['schema_name' => $schemaName]);
        
        return $schemaName;
    }

    /**
     * Drop tenant schema
     */
    public function dropSchema(): void
    {
        $tenantService = app(TenantContextService::class);
        $tenantService->dropTenantSchema($this->id);
    }

    /**
     * Execute callback in this tenant's context
     */
    public function run(callable $callback)
    {
        $tenantService = app(TenantContextService::class);
        return $tenantService->runInTenantContext($this->id, $callback);
    }

    /**
     * Switch to this tenant's context
     */
    public function makeCurrent(): void
    {
        $tenantService = app(TenantContextService::class);
        $tenantService->setTenant($this->id);
    }

    /**
     * Get tenant users (from central database)
     */
    public function users(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    /**
     * Get tenant domains
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * Check if tenant is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if tenant is on trial
     */
    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial' && 
               $this->trial_ends_at && 
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if tenant trial has expired
     */
    public function trialExpired(): bool
    {
        return $this->subscription_status === 'trial' && 
               $this->trial_ends_at && 
               $this->trial_ends_at->isPast();
    }

    /**
     * Get tenant setting
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set tenant setting
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?: [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    /**
     * Scope to active tenants
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to tenants on trial
     */
    public function scopeOnTrial($query)
    {
        return $query->where('subscription_status', 'trial')
                    ->where('trial_ends_at', '>', now());
    }

    /**
     * Scope to tenants with expired trials
     */
    public function scopeTrialExpired($query)
    {
        return $query->where('subscription_status', 'trial')
                    ->where('trial_ends_at', '<=', now());
    }

    /**
     * Scope to tenants by subscription plan
     */
    public function scopeByPlan($query, string $plan)
    {
        return $query->where('subscription_plan', $plan);
    }
}