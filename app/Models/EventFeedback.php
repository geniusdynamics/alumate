<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventFeedback extends Model
{
    use HasFactory;

    protected $table = 'event_feedback';

    protected $fillable = [
        'event_id',
        'user_id',
        'overall_rating',
        'content_rating',
        'organization_rating',
        'networking_rating',
        'venue_rating',
        'feedback_text',
        'feedback_categories',
        'would_recommend',
        'would_attend_again',
        'improvement_suggestions',
        'is_anonymous',
    ];

    protected $casts = [
        'feedback_categories' => 'array',
        'improvement_suggestions' => 'array',
        'would_recommend' => 'boolean',
        'would_attend_again' => 'boolean',
        'is_anonymous' => 'boolean',
        'overall_rating' => 'integer',
        'content_rating' => 'integer',
        'organization_rating' => 'integer',
        'networking_rating' => 'integer',
        'venue_rating' => 'integer',
    ];

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByRating($query, int $rating)
    {
        return $query->where('overall_rating', $rating);
    }

    public function scopeHighRated($query)
    {
        return $query->where('overall_rating', '>=', 4);
    }

    public function scopeRecommended($query)
    {
        return $query->where('would_recommend', true);
    }

    // Helper methods
    public function getAverageRating(): float
    {
        $ratings = array_filter([
            $this->overall_rating,
            $this->content_rating,
            $this->organization_rating,
            $this->networking_rating,
            $this->venue_rating,
        ]);

        return count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 2) : 0;
    }

    public function getRatingBreakdown(): array
    {
        return [
            'overall' => $this->overall_rating,
            'content' => $this->content_rating,
            'organization' => $this->organization_rating,
            'networking' => $this->networking_rating,
            'venue' => $this->venue_rating,
        ];
    }

    public function isPositive(): bool
    {
        return $this->overall_rating >= 4;
    }

    public function hasDetailedFeedback(): bool
    {
        return ! empty($this->feedback_text) ||
               ! empty($this->feedback_categories) ||
               ! empty($this->improvement_suggestions);
    }
}
