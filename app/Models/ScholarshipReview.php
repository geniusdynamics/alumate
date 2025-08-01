<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScholarshipReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'application_id',
        'reviewer_id',
        'score',
        'comments',
        'criteria_scores',
        'recommendation',
        'feedback_for_applicant',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'criteria_scores' => 'array',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(ScholarshipApplication::class, 'application_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('recommendation', 'approve');
    }

    public function scopeRejected($query)
    {
        return $query->where('recommendation', 'reject');
    }

    public function isPositive(): bool
    {
        return $this->recommendation === 'approve';
    }
}
