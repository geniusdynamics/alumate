<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOnboardingState extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'has_completed_onboarding',
        'has_skipped_onboarding',
        'completed_steps',
        'last_active_step',
        'profile_completion_dismissed',
        'feature_discovery_viewed',
        'explored_features',
        'whats_new_viewed',
        'preferences'
    ];

    protected $casts = [
        'has_completed_onboarding' => 'boolean',
        'has_skipped_onboarding' => 'boolean',
        'completed_steps' => 'array',
        'last_active_step' => 'integer',
        'profile_completion_dismissed' => 'boolean',
        'feature_discovery_viewed' => 'boolean',
        'explored_features' => 'array',
        'whats_new_viewed' => 'array',
        'preferences' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}