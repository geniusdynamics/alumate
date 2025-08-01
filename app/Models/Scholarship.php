<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Scholarship extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'amount',
        'type',
        'status',
        'eligibility_criteria',
        'application_requirements',
        'application_deadline',
        'award_date',
        'max_recipients',
        'total_fund_amount',
        'awarded_amount',
        'creator_id',
        'institution_id',
        'metadata',
    ];

    protected $casts = [
        'eligibility_criteria' => 'array',
        'application_requirements' => 'array',
        'application_deadline' => 'date',
        'award_date' => 'date',
        'amount' => 'decimal:2',
        'total_fund_amount' => 'decimal:2',
        'awarded_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ScholarshipApplication::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(ScholarshipRecipient::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOpenForApplications($query)
    {
        return $query->where('status', 'active')
                    ->where('application_deadline', '>=', now());
    }

    public function isOpenForApplications(): bool
    {
        return $this->status === 'active' && $this->application_deadline >= now();
    }

    public function getRemainingFundsAttribute(): float
    {
        return $this->total_fund_amount - $this->awarded_amount;
    }

    public function getApplicationsCountAttribute(): int
    {
        return $this->applications()->count();
    }

    public function getRecipientsCountAttribute(): int
    {
        return $this->recipients()->count();
    }
}
