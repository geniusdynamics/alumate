<?php

namespace App\Models;

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
        'tenant_id',
    ];

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
        return $this->belongsTo(Tenant::class);
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
