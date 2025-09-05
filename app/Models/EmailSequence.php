<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmailSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'audience_type',
        'trigger_type',
        'trigger_conditions',
        'is_active',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
        'trigger_conditions' => '{}',
    ];

    /**
     * Audience types for email sequences
     */
    public const AUDIENCE_TYPES = [
        'individual',
        'institutional',
        'employer',
    ];

    /**
     * Trigger types for email sequences
     */
    public const TRIGGER_TYPES = [
        'form_submission',
        'page_visit',
        'behavior',
        'manual',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply tenant scoping automatically for multi-tenant isolation
        static::addGlobalScope('tenant', function ($builder) {
            // Check if we're in a multi-tenant context
            if (config('database.multi_tenant', false)) {
                try {
                    // In production, apply tenant filter based on current tenant context
                    if (tenant() && tenant()->id) {
                        $builder->where('tenant_id', tenant()->id);
                    }
                } catch (\Exception $e) {
                    // Skip tenant scoping in test environment
                }
            }
        });
    }

    /**
     * Scope query to specific tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope query to active sequences only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query by audience type
     */
    public function scopeByAudience($query, string $audienceType)
    {
        return $query->where('audience_type', $audienceType);
    }

    /**
     * Scope query by trigger type
     */
    public function scopeByTrigger($query, string $triggerType)
    {
        return $query->where('trigger_type', $triggerType);
    }

    /**
     * Get the tenant that owns the sequence
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the emails in this sequence
     */
    public function sequenceEmails(): HasMany
    {
        return $this->hasMany(SequenceEmail::class, 'sequence_id');
    }

    /**
     * Get the enrollments for this sequence
     */
    public function sequenceEnrollments(): HasMany
    {
        return $this->hasMany(SequenceEnrollment::class, 'sequence_id');
    }

    /**
     * Get sequence statistics
     */
    public function getSequenceStats(): array
    {
        return [
            'total_emails' => $this->sequenceEmails()->count(),
            'total_enrollments' => $this->sequenceEnrollments()->count(),
            'active_enrollments' => $this->sequenceEnrollments()->where('status', 'active')->count(),
            'completed_enrollments' => $this->sequenceEnrollments()->where('status', 'completed')->count(),
        ];
    }

    /**
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'audience_type' => ['required', 'string', Rule::in(self::AUDIENCE_TYPES)],
            'trigger_type' => ['required', 'string', Rule::in(self::TRIGGER_TYPES)],
            'trigger_conditions' => 'nullable|array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get unique validation rules (for updates)
     */
    public static function getUniqueValidationRules(?int $ignoreId = null): array
    {
        $rules = self::getValidationRules();

        if ($ignoreId) {
            $rules['name'] = 'required|string|max:255|unique:email_sequences,name,' . $ignoreId . ',id,tenant_id,' . tenant()->id;
        } else {
            $rules['name'] = 'required|string|max:255|unique:email_sequences,name,NULL,id,tenant_id,' . tenant()->id;
        }

        return $rules;
    }
}