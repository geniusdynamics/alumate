<?php

namespace App\Models;

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
        'tenant_id',
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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
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
