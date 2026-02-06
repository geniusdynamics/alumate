<?php
// ABOUTME: NotificationTemplate model for managing notification templates with schema-based tenant isolation
// ABOUTME: Uses schema-based tenancy where each tenant has their own database schema for complete data isolation

namespace App\Models;

use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        // 'tenant_id', // Commented out for schema-based tenancy - tenant isolation handled at schema level
        'name',
        'type',
        'subject',
        'content',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get current tenant from context service
     */
    public function getCurrentTenant()
    {
        return app(TenantContextService::class)->getCurrentTenant();
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Schema-based tenancy: No global scope needed as tenant isolation is handled at schema level
        // Each tenant operates within their own database schema
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        // Schema-based tenancy: Return current tenant from context instead of database relationship
        $tenant = $this->getCurrentTenant();
        return $this->belongsTo(Tenant::class)->where('id', $tenant->id ?? null);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        // Legacy compatibility: In schema-based tenancy, tenant isolation is handled at schema level
        // This scope returns the query unchanged as all data is already tenant-isolated
        return $query;
    }

    // Helper methods
    public function render($variables = [])
    {
        $content = $this->content;
        $subject = $this->subject;

        foreach ($variables as $key => $value) {
            $placeholder = '{{'.$key.'}}';
            $content = str_replace($placeholder, $value, $content);
            if ($subject) {
                $subject = str_replace($placeholder, $value, $subject);
            }
        }

        return [
            'subject' => $subject,
            'content' => $content,
        ];
    }

    public static function getTemplate($name, $type = 'email')
    {
        return self::where('name', $name)
            ->where('type', $type)
            ->active()
            ->first();
    }
}
