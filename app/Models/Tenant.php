<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $fillable = [
        'id',
        'name',
        'address',
        'contact_information',
        'plan',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Get users associated with this tenant
     */
    public function users()
    {
        return $this->hasMany(User::class, 'institution_id', 'id');
    }

    /**
     * Get courses for this tenant (tenant-specific)
     */
    public function courses()
    {
        return $this->hasMany(Course::class, 'institution_id', 'id');
    }

    /**
     * Get graduates for this tenant (requires tenant context)
     */
    public function graduates()
    {
        // This will only work when called within tenant context
        return $this->hasMany(Graduate::class);
    }

    /**
     * Get employers associated with this tenant
     */
    public function employers()
    {
        return $this->hasMany(Employer::class, 'tenant_id', 'id');
    }

    /**
     * Get jobs for this tenant
     */
    public function jobs()
    {
        return $this->hasMany(Job::class, 'tenant_id', 'id');
    }
}