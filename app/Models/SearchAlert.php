<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'saved_search_id',
        'results_count',
        'sent_at',
        'results_data',
        'opened_at',
        'clicked_results',
    ];

    protected $casts = [
        'results_count' => 'integer',
        'sent_at' => 'datetime',
        'results_data' => 'array',
        'opened_at' => 'datetime',
        'clicked_results' => 'array',
    ];

    // Relationships
    public function savedSearch()
    {
        return $this->belongsTo(SavedSearch::class);
    }

    // Scopes
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('sent_at', '>=', now()->subDays($days));
    }

    public function scopeOpened($query)
    {
        return $query->whereNotNull('opened_at');
    }

    public function scopeUnopened($query)
    {
        return $query->whereNull('opened_at');
    }

    // Helper Methods
    public function markAsOpened()
    {
        if (!$this->opened_at) {
            $this->update(['opened_at' => now()]);
        }
    }

    public function trackResultClick($resultId, $resultType)
    {
        $clicks = $this->clicked_results ?? [];
        $clicks[] = [
            'result_id' => $resultId,
            'result_type' => $resultType,
            'clicked_at' => now()->toISOString(),
        ];
        
        $this->update(['clicked_results' => $clicks]);
    }

    public function getClickThroughRate()
    {
        if ($this->results_count === 0) {
            return 0;
        }

        $uniqueClicks = collect($this->clicked_results ?? [])->unique('result_id')->count();
        return round(($uniqueClicks / $this->results_count) * 100, 2);
    }
}