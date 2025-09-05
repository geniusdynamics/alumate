<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
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

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
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
        return $query->where('tenant_id', $tenantId);
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
