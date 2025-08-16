<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScholarshipRecipient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'scholarship_id',
        'application_id',
        'recipient_id',
        'awarded_amount',
        'award_date',
        'status',
        'success_story',
        'academic_progress',
        'impact_metrics',
        'thank_you_message',
        'updates',
    ];

    protected $casts = [
        'awarded_amount' => 'decimal:2',
        'award_date' => 'date',
        'academic_progress' => 'array',
        'impact_metrics' => 'array',
        'updates' => 'array',
    ];

    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ScholarshipApplication::class, 'application_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function hasSuccessStory(): bool
    {
        return ! empty($this->success_story);
    }

    public function getYearsSinceAwardAttribute(): int
    {
        return $this->award_date->diffInYears(now());
    }
}
