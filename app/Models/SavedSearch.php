<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SavedSearch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'query',
        'filters',
        'email_alerts',
        'alert_frequency',
        'last_run_at',
        'last_result_count'
    ];

    protected $casts = [
        'filters' => 'array',
        'email_alerts' => 'boolean',
        'last_run_at' => 'datetime',
        'last_result_count' => 'integer'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'last_run_at'
    ];

    /**
     * Get the user that owns the saved search
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get searches that need alert processing
     */
    public function scopeNeedsAlertProcessing($query)
    {
        return $query->where('email_alerts', true)
            ->where(function ($q) {
                $q->whereNull('last_run_at')
                  ->orWhere(function ($subQuery) {
                      $subQuery->where('alert_frequency', 'immediate')
                               ->where('last_run_at', '<', now()->subMinutes(5));
                  })
                  ->orWhere(function ($subQuery) {
                      $subQuery->where('alert_frequency', 'daily')
                               ->where('last_run_at', '<', now()->subDay());
                  })
                  ->orWhere(function ($subQuery) {
                      $subQuery->where('alert_frequency', 'weekly')
                               ->where('last_run_at', '<', now()->subWeek());
                  });
            });
    }

    /**
     * Get the formatted alert frequency
     */
    public function getFormattedAlertFrequencyAttribute(): string
    {
        $frequencies = [
            'immediate' => 'Immediate',
            'daily' => 'Daily',
            'weekly' => 'Weekly'
        ];

        return $frequencies[$this->alert_frequency] ?? $this->alert_frequency;
    }

    /**
     * Get a summary of active filters
     */
    public function getFiltersSummaryAttribute(): string
    {
        if (empty($this->filters)) {
            return 'No filters';
        }

        $summary = [];

        if (!empty($this->filters['location'])) {
            $summary[] = "Location: {$this->filters['location']}";
        }

        if (!empty($this->filters['graduation_year'])) {
            $summary[] = "Year: {$this->filters['graduation_year']}";
        }

        if (!empty($this->filters['industry']) && is_array($this->filters['industry'])) {
            $count = count($this->filters['industry']);
            $summary[] = "Industries: {$count}";
        }

        if (!empty($this->filters['skills']) && is_array($this->filters['skills'])) {
            $count = count($this->filters['skills']);
            $summary[] = "Skills: {$count}";
        }

        if (!empty($this->filters['types']) && is_array($this->filters['types'])) {
            $types = $this->filters['types'];
            if (count($types) < 4) { // Less than all types
                $summary[] = "Types: " . implode(', ', $types);
            }
        }

        return empty($summary) ? 'No filters' : implode(', ', $summary);
    }

    /**
     * Check if the search should be run for alerts
     */
    public function shouldRunForAlerts(): bool
    {
        if (!$this->email_alerts) {
            return false;
        }

        if (!$this->last_run_at) {
            return true;
        }

        switch ($this->alert_frequency) {
            case 'immediate':
                return $this->last_run_at->lt(now()->subMinutes(5));
            case 'daily':
                return $this->last_run_at->lt(now()->subDay());
            case 'weekly':
                return $this->last_run_at->lt(now()->subWeek());
            default:
                return false;
        }
    }

    /**
     * Mark the search as run with result count
     */
    public function markAsRun(int $resultCount): void
    {
        $this->update([
            'last_run_at' => now(),
            'last_result_count' => $resultCount
        ]);
    }

    /**
     * Get the next scheduled run time for alerts
     */
    public function getNextRunTimeAttribute(): ?Carbon
    {
        if (!$this->email_alerts || !$this->last_run_at) {
            return null;
        }

        switch ($this->alert_frequency) {
            case 'immediate':
                return $this->last_run_at->addMinutes(5);
            case 'daily':
                return $this->last_run_at->addDay();
            case 'weekly':
                return $this->last_run_at->addWeek();
            default:
                return null;
        }
    }

    /**
     * Check if there are new results since last run
     */
    public function hasNewResults(int $currentResultCount): bool
    {
        return $this->last_result_count === null || $currentResultCount > $this->last_result_count;
    }
}