<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScholarshipApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'scholarship_id',
        'applicant_id',
        'status',
        'application_data',
        'documents',
        'personal_statement',
        'gpa',
        'financial_need_statement',
        'references',
        'submitted_at',
        'admin_notes',
        'score',
    ];

    protected $casts = [
        'application_data' => 'array',
        'documents' => 'array',
        'references' => 'array',
        'submitted_at' => 'datetime',
        'gpa' => 'decimal:2',
        'score' => 'decimal:2',
    ];

    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ScholarshipReview::class, 'application_id');
    }

    public function recipient(): HasOne
    {
        return $this->hasOne(ScholarshipRecipient::class, 'application_id');
    }

    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function isSubmitted(): bool
    {
        return ! is_null($this->submitted_at);
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'submitted']);
    }

    public function getAverageScoreAttribute(): ?float
    {
        $reviews = $this->reviews;
        if ($reviews->isEmpty()) {
            return null;
        }

        return $reviews->avg('score');
    }
}
