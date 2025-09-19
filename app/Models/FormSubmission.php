<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'submission_data',
        'user_ip',
        'user_agent',
        'referrer_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'crm_sync_status',
        'crm_lead_id',
        'crm_sync_error',
        'validation_errors',
        'status',
        'tenant_id'
    ];

    protected $casts = [
        'submission_data' => 'array',
        'validation_errors' => 'array',
        'crm_sync_error' => 'array'
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(FormBuilder::class, 'form_id');
    }

    public function crmSyncLogs(): HasMany
    {
        return $this->hasMany(CrmSyncLog::class, 'submission_id');
    }

    public function getStatusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'processed' => 'Processed',
            'failed' => 'Failed',
            'synced' => 'Synced to CRM'
        ];
    }

    public function scopePendingCrmSync($query)
    {
        return $query->where('crm_sync_status', 'pending');
    }

    public function scopeFailedCrmSync($query)
    {
        return $query->where('crm_sync_status', 'failed');
    }
}