<?php
// ABOUTME: EmailAutomationRule model for managing email automation rules with schema-based tenant isolation
// ABOUTME: Uses schema-based tenancy where each tenant has their own database schema for complete data isolation

namespace App\Models;

use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailAutomationRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'trigger_event',
        'trigger_conditions',
        'audience_criteria',
        'template_id',
        'delay_minutes',
        'is_active',
        'sent_count',
        'created_by',
        // 'tenant_id', // Commented out for schema-based tenancy - tenant isolation handled at schema level
    ];

    /**
     * Get current tenant from context service
     */
    public function getCurrentTenant()
    {
        return app(TenantContextService::class)->getCurrentTenant();
    }

    protected function casts(): array
    {
        return [
            'trigger_conditions' => 'array',
            'audience_criteria' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tenant(): BelongsTo
    {
        // Schema-based tenancy: Return current tenant from context instead of database relationship
        $tenant = $this->getCurrentTenant();
        return $this->belongsTo(Tenant::class)->where('id', $tenant->id ?? null);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByEvent($query, string $event)
    {
        return $query->where('trigger_event', $event);
    }

    public function incrementSentCount(): void
    {
        $this->increment('sent_count');
    }
}
