<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'type',
        'description',
        'website',
        'email',
        'phone',
        'address',
        'logo_url',
        'banner_url',
        'established_year',
        'student_count',
        'alumni_count',
        'settings',
        'subscription_plan',
        'subscription_status',
        'trial_ends_at',
        'is_active',
        'verified_at',
        'status',
    ];

    protected $casts = [
        'address' => 'array',
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
        'verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the users for the institution.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the events for the institution.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the groups for the institution.
     */
    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}
