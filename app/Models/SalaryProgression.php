<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryProgression extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'salary',
        'currency',
        'salary_type',
        'position_title',
        'company',
        'industry',
        'effective_date',
        'years_since_graduation',
        'metadata',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'effective_date' => 'date',
        'metadata' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByIndustry($query, string $industry)
    {
        return $query->where('industry', $industry);
    }

    public function scopeByYearsSinceGraduation($query, int $years)
    {
        return $query->where('years_since_graduation', $years);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('effective_date', 'desc');
    }

    // Accessors
    public function getFormattedSalaryAttribute()
    {
        return number_format($this->salary, 0) . ' ' . $this->currency;
    }

    public function getAnnualizedSalaryAttribute()
    {
        return match ($this->salary_type) {
            'hourly' => $this->salary * 40 * 52, // 40 hours/week, 52 weeks/year
            'monthly' => $this->salary * 12,
            'annual' => $this->salary,
            default => $this->salary
        };
    }
}