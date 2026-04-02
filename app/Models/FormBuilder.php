<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormBuilder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'page_id',
        'configuration',
        'validation_rules',
        'conditional_logic',
        'crm_integration_config',
        'success_message',
        'error_message',
        'redirect_url',
        'is_active',
        'tenant_id',
    ];

    protected $casts = [
        'configuration' => 'array',
        'validation_rules' => 'array',
        'conditional_logic' => 'array',
        'crm_integration_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class, 'page_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'form_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'form_id');
    }
}
