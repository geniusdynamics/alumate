<?php
// ABOUTME: Brand guidelines model for managing brand standards and approval workflows
// ABOUTME: Updated for schema-based multi-tenancy without tenant_id column

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Auth as AuthFacade;
use Illuminate\Support\Str;
use App\Services\TenantContextService;

class BrandGuidelines extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_config_id',
        'name',
        'slug',
        'description',
        'usage_rules',
        'color_guidelines',
        'typography_guidelines',
        'logo_guidelines',
        'dos_and_donts',
        'brand_voice_tone',
        'brand_personality',
        'target_audience',
        'brand_values',
        'legal_restrictions',
        'contact_information',
        'review_process',
        'version',
        'effective_date',
        'is_active',
        'requires_approval',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'usage_rules' => 'array',
        'color_guidelines' => 'array',
        'typography_guidelines' => 'array',
        'logo_guidelines' => 'array',
        'dos_and_donts' => 'array',
        'brand_voice_tone' => 'array',
        'brand_personality' => 'array',
        'target_audience' => 'array',
        'brand_values' => 'array',
        'legal_restrictions' => 'array',
        'contact_information' => 'array',
        'review_process' => 'array',
        'version' => 'integer',
        'effective_date' => 'date',
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
    ];

    protected $attributes = [
        'version' => 1,
        'is_active' => true,
        'requires_approval' => true,
        'usage_rules' => '[]',
        'color_guidelines' => '[]',
        'typography_guidelines' => '[]',
        'logo_guidelines' => '[]',
        'dos_and_donts' => '[]',
        'brand_voice_tone' => '{}',
        'brand_personality' => '{}',
        'target_audience' => '[]',
        'brand_values' => '[]',
        'legal_restrictions' => '[]',
        'contact_information' => '{}',
        'review_process' => '{}',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply tenant context for schema-based multi-tenancy
        static::addGlobalScope('tenant_context', function ($builder) {
            TenantContextService::applyTenantContext($builder);
        });

        // Auto-generate slug if not provided
        static::creating(function ($brandGuidelines) {
            if (empty($brandGuidelines->slug)) {
                $brandGuidelines->slug = $brandGuidelines->generateUniqueSlug($brandGuidelines->name);
            }
        });
    }

    /**
     * Scope query to specific tenant (legacy compatibility)
     */
    public function scopeForTenant($query, int $tenantId = null)
    {
        // For schema-based tenancy, this is handled by global scope
        return $query;
    }

    /**
     * Scope query to active brand guidelines only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to approved guidelines only
     */
    public function scopeApproved($query)
    {
        return $query->where('approved_by', '!=', null);
    }

    /**
     * Scope query to guidelines requiring approval
     */
    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    /**
     * Get the current tenant context
     */
    public function getCurrentTenant()
    {
        return TenantContextService::getCurrentTenant();
    }

    /**
     * Get the brand config this guidelines belong to
     */
    public function brandConfig(): BelongsTo
    {
        return $this->belongsTo(BrandConfig::class, 'brand_config_id');
    }

    /**
     * Get the user who created these brand guidelines
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated these brand guidelines
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who approved these brand guidelines
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get guideline review history
     */
    // TODO: Create BrandGuidelineReview model for tracking approval history
    // public function reviewHistory(): HasMany
    // {
    //     return $this->hasMany(BrandGuidelineReview::class);
    // }

    /**
     * Get complete guidelines data
     */
    public function getCompleteGuidelines(): array
    {
        return [
            'basic_info' => [
                'name' => $this->name,
                'description' => $this->description,
                'version' => $this->version,
                'effective_date' => $this->effective_date,
                'is_active' => $this->is_active,
            ],
            'usage_rules' => $this->usage_rules ?? [],
            'color_guidelines' => $this->color_guidelines ?? [],
            'typography_guidelines' => $this->typography_guidelines ?? [],
            'logo_guidelines' => $this->logo_guidelines ?? [],
            'dos_and_donts' => $this->dos_and_donts ?? [],
            'brand_voice_tone' => $this->brand_voice_tone ?? [],
            'brand_personality' => $this->brand_personality ?? [],
            'target_audience' => $this->target_audience ?? [],
            'brand_values' => $this->brand_values ?? [],
            'legal_restrictions' => $this->legal_restrictions ?? [],
            'contact_information' => $this->contact_information ?? [],
            'review_process' => $this->review_process ?? [],
            'approval' => [
                'requires_approval' => $this->requires_approval,
                'approved_by' => $this->approved_by,
                'approved_at' => $this->approved_at,
            ],
        ];
    }

    /**
     * Check if guidelines are approved
     */
    public function isApproved(): bool
    {
        return $this->approved_by !== null && $this->approved_at !== null;
    }

    /**
     * Check if guidelines are effective (active and approved if required)
     */
    public function isEffective(): bool
    {
        $isAfterEffectiveDate = $this->effective_date
            ? $this->effective_date->isPast() || $this->effective_date->isToday()
            : true;

        return $this->is_active && $isAfterEffectiveDate && (!$this->requires_approval || $this->isApproved());
    }

    /**
     * Approve the brand guidelines
     */
    public function approve(?int $approvedBy = null): void
    {
        $approvedBy = $approvedBy ?? Auth::id();

        $this->update([
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        // Log approval
        // TODO: Implement logging when BrandGuidelineReview model is created
        // $this->reviewHistory()->create([
        //     'action' => 'approved',
        //     'user_id' => $approvedBy,
        //     'comments' => 'Guidelines approved',
        // ]);
    }

    /**
     * Revoke approval from brand guidelines
     */
    public function revokeApproval(?string $reason = null): void
    {
        $comments = $reason ?? 'Approval revoked';

        $this->update([
            'approved_by' => null,
            'approved_at' => null,
        ]);

        // Log revocation
        $this->reviewHistory()->create([
            'action' => 'approval_revoked',
            'user_id' => auth()->id(),
            'comments' => $comments,
        ]);
    }

    /**
     * Request review for guidelines
     */
    // TODO: Implement when BrandGuidelineReview model exists
    // public function requestReview(string $comments = ''): void
    // {
    //     $this->reviewHistory()->create([
    //         'action' => 'review_requested',
    //         'user_id' => Auth::id(),
    //         'comments' => $comments ?: 'Review requested',
    //     ]);
    // }

    /**
     * Create a new version of the guidelines
     */
    public function createNewVersion(): self
    {
        return static::create([
            'brand_config_id' => $this->brand_config_id,
            'name' => $this->name . ' (Version ' . ($this->version + 1) . ')',
            'description' => $this->description,
            'usage_rules' => $this->usage_rules,
            'color_guidelines' => $this->color_guidelines,
            'typography_guidelines' => $this->typography_guidelines,
            'logo_guidelines' => $this->logo_guidelines,
            'dos_and_donts' => $this->dos_and_donts,
            'brand_voice_tone' => $this->brand_voice_tone,
            'brand_personality' => $this->brand_personality,
            'target_audience' => $this->target_audience,
            'brand_values' => $this->brand_values,
            'legal_restrictions' => $this->legal_restrictions,
            'contact_information' => $this->contact_information,
            'review_process' => $this->review_process,
            'version' => $this->version + 1,
            'effective_date' => now()->addDays(1), // Default to next day
            'is_active' => false, // New version starts inactive
            'requires_approval' => $this->requires_approval,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    /**
     * Generate a unique slug for the brand guidelines
     */
    protected function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug exists within current tenant context
     */
    protected function slugExists(string $slug): bool
    {
        $query = static::where('slug', $slug);

        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        return $query->exists();
    }

    /**
     * Get guidelines usage statistics
     */
    public function getUsageStats(): array
    {
        return [
            'version' => $this->version,
            'is_active' => $this->is_active,
            'is_approved' => $this->isApproved(),
            'is_effective' => $this->isEffective(),
            'requires_approval' => $this->requires_approval,
            'review_history_count' => $this->reviewHistory()->count(),
        ];
    }

    /**
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            // tenant_id removed for schema-based tenancy
            'brand_config_id' => 'nullable|exists:brand_configs,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/',
            'description' => 'nullable|string|max:2000',
            'usage_rules' => 'nullable|array',
            'color_guidelines' => 'nullable|array',
            'typography_guidelines' => 'nullable|array',
            'logo_guidelines' => 'nullable|array',
            'dos_and_donts' => 'nullable|array',
            'brand_voice_tone' => 'nullable|array',
            'brand_personality' => 'nullable|array',
            'target_audience' => 'nullable|array',
            'brand_values' => 'nullable|array',
            'legal_restrictions' => 'nullable|array',
            'contact_information' => 'nullable|array',
            'review_process' => 'nullable|array',
            'version' => 'nullable|integer|min:1',
            'effective_date' => 'nullable|date',
            'is_active' => 'boolean',
            'requires_approval' => 'boolean',
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id',
            'approved_by' => 'nullable|exists:users,id',
            'approved_at' => 'nullable|datetime',
        ];
    }

    /**
     * Get unique validation rules (for updates)
     */
    public static function getUniqueValidationRules(?int $ignoreId = null): array
    {
        $rules = self::getValidationRules();

        if ($ignoreId) {
            $rules['slug'] = 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:brand_guidelines,slug,' . $ignoreId;
        } else {
            $rules['slug'] = 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:brand_guidelines,slug';
        }

        return $rules;
    }
}
