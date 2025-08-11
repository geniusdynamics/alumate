<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'search_type',
        'search_criteria',
        'results_count',
        'user_agent',
        'ip_address',
        'searched_at',
    ];

    protected $casts = [
        'search_criteria' => 'array',
        'results_count' => 'integer',
        'searched_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('search_type', $type);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('searched_at', '>=', now()->subDays($days));
    }

    public function scopeWithResults($query)
    {
        return $query->where('results_count', '>', 0);
    }

    public function scopeWithoutResults($query)
    {
        return $query->where('results_count', 0);
    }

    // Helper Methods
    public function getKeywords()
    {
        $criteria = $this->search_criteria ?? [];

        return $criteria['keywords'] ?? null;
    }

    public function hasResults()
    {
        return $this->results_count > 0;
    }

    public function getSearchSummary()
    {
        $criteria = $this->search_criteria ?? [];
        $summary = [];

        if (! empty($criteria['keywords'])) {
            $summary[] = "Keywords: {$criteria['keywords']}";
        }

        if (! empty($criteria['location'])) {
            $summary[] = "Location: {$criteria['location']}";
        }

        if (! empty($criteria['course_id'])) {
            $summary[] = "Course ID: {$criteria['course_id']}";
        }

        if (! empty($criteria['skills'])) {
            $summary[] = 'Skills: '.implode(', ', $criteria['skills']);
        }

        return implode(' | ', $summary);
    }
}
