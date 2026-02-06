<?php
// ABOUTME: EmailCampaign model for managing email campaigns with schema-based tenant isolation
// ABOUTME: Uses schema-based tenancy where each tenant has their own database schema for complete data isolation

namespace App\Models;

use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailCampaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'subject',
        'content',
        'template_data',
        'type',
        'status',
        'provider',
        'provider_campaign_id',
        'provider_data',
        'audience_criteria',
        'personalization_rules',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'unsubscribed_count',
        'bounced_count',
        'open_rate',
        'click_rate',
        'unsubscribe_rate',
        'bounce_rate',
        'is_ab_test',
        'ab_test_variant',
        'ab_test_parent_id',
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
            'template_data' => 'array',
            'provider_data' => 'array',
            'audience_criteria' => 'array',
            'personalization_rules' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'open_rate' => 'decimal:2',
            'click_rate' => 'decimal:2',
            'unsubscribe_rate' => 'decimal:2',
            'bounce_rate' => 'decimal:2',
            'is_ab_test' => 'boolean',
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

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailCampaignRecipient::class, 'campaign_id');
    }

    public function parentCampaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'ab_test_parent_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(EmailCampaign::class, 'ab_test_parent_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['draft', 'scheduled', 'sending', 'sent']);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function getEngagementRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return round(($this->clicked_count / $this->total_recipients) * 100, 2);
    }

    public function getDeliveryRateAttribute(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return round(($this->delivered_count / $this->total_recipients) * 100, 2);
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_at && $this->scheduled_at->isFuture();
    }

    public function canBeSent(): bool
    {
        return in_array($this->status, ['draft', 'scheduled']);
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft']);
    }
}
