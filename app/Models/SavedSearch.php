<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavedSearch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'query',
        'filters',
        'result_count',
        'last_executed_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'last_executed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the saved search
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the search alerts for this saved search
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(SearchAlert::class);
    }

    /**
     * Get active alerts for this saved search
     */
    public function activeAlerts(): HasMany
    {
        return $this->hasMany(SearchAlert::class)->where('is_active', true);
    }

    /**
     * Update the result count for this search
     */
    public function updateResultCount(int $count): void
    {
        $this->update([
            'result_count' => $count,
            'last_executed_at' => now(),
        ]);
    }

    /**
     * Get a human-readable description of the search filters
     */
    public function getFilterDescriptionAttribute(): string
    {
        $descriptions = [];

        if (! empty($this->filters['location'])) {
            $descriptions[] = "Location: {$this->filters['location']}";
        }

        if (! empty($this->filters['industry'])) {
            $industry = is_array($this->filters['industry'])
                ? implode(', ', $this->filters['industry'])
                : $this->filters['industry'];
            $descriptions[] = "Industry: $industry";
        }

        if (! empty($this->filters['graduation_year'])) {
            if (is_array($this->filters['graduation_year'])) {
                $descriptions[] = "Graduated: {$this->filters['graduation_year']['min']}-{$this->filters['graduation_year']['max']}";
            } else {
                $descriptions[] = "Graduated: {$this->filters['graduation_year']}";
            }
        }

        if (! empty($this->filters['company'])) {
            $descriptions[] = "Company: {$this->filters['company']}";
        }

        if (! empty($this->filters['skills'])) {
            $skills = is_array($this->filters['skills'])
                ? implode(', ', $this->filters['skills'])
                : $this->filters['skills'];
            $descriptions[] = "Skills: $skills";
        }

        return implode(' • ', $descriptions);
    }
}
