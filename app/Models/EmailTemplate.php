<?php
// ABOUTME: Email template model for schema-based multi-tenancy without tenant_id column
// ABOUTME: Manages email templates with automatic tenant context resolution

namespace App\Models;

use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'category',
        'html_content',
        'text_content',
        'variables',
        'design_data',
        'is_default',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'design_data' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply tenant context for schema-based tenancy
        static::addGlobalScope('tenant_context', function ($builder) {
            app(TenantContextService::class)->applyTenantContext($builder);
        });
    }

    /**
     * Get the current tenant context
     * Note: In schema-based tenancy, tenant relationship is contextual
     */
    public function getCurrentTenant()
    {
        return app(TenantContextService::class)->getCurrentTenant();
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(EmailCampaign::class, 'template_id');
    }

    public function automationRules(): HasMany
    {
        return $this->hasMany(EmailAutomationRule::class, 'template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function renderContent(array $variables = []): string
    {
        $content = $this->html_content;

        foreach ($variables as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
        }

        return $content;
    }

    public function getAvailableVariables(): array
    {
        return $this->variables ?? [];
    }
}
