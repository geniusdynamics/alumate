<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobMatchScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'user_id',
        'score',
        'reasons',
        'calculated_at',
        'connection_score',
        'skills_score',
        'education_score',
        'circle_score',
        'mutual_connections_count',
    ];

    protected $casts = [
        'reasons' => 'array',
        'calculated_at' => 'datetime',
        'score' => 'decimal:2',
        'connection_score' => 'decimal:2',
        'skills_score' => 'decimal:2',
        'education_score' => 'decimal:2',
        'circle_score' => 'decimal:2',
        'mutual_connections_count' => 'integer',
    ];

    protected $dates = [
        'calculated_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the job posting for this match score
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_id');
    }

    /**
     * Get the user for this match score
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the overall match percentage
     */
    public function getMatchPercentage(): int
    {
        return (int) round($this->score);
    }

    /**
     * Get the match level (High, Medium, Low)
     */
    public function getMatchLevel(): string
    {
        $score = $this->score;

        if ($score >= 80) {
            return 'High';
        } elseif ($score >= 60) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }

    /**
     * Get the match level color for UI
     */
    public function getMatchLevelColor(): string
    {
        return match ($this->getMatchLevel()) {
            'High' => 'green',
            'Medium' => 'yellow',
            'Low' => 'red',
            default => 'gray',
        };
    }

    /**
     * Check if the score is recent (calculated within last 24 hours)
     */
    public function isRecent(): bool
    {
        return $this->calculated_at && $this->calculated_at->isAfter(now()->subDay());
    }

    /**
     * Get the top reasons for the match
     */
    public function getTopReasons(int $limit = 3): array
    {
        if (! $this->reasons) {
            return [];
        }

        // Sort reasons by importance/score and return top ones
        $reasons = collect($this->reasons);

        return $reasons->sortByDesc('score')
            ->take($limit)
            ->pluck('reason')
            ->toArray();
    }

    /**
     * Scope to high match scores
     */
    public function scopeHighMatch($query)
    {
        return $query->where('score', '>=', 80);
    }

    /**
     * Scope to recent calculations
     */
    public function scopeRecent($query)
    {
        return $query->where('calculated_at', '>=', now()->subDay());
    }

    /**
     * Scope by minimum score
     */
    public function scopeMinimumScore($query, float $minScore)
    {
        return $query->where('score', '>=', $minScore);
    }
}
