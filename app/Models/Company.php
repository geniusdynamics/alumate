<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'logo_url',
        'website',
        'size',
        'industry',
        'location',
        'founded_year',
        'is_verified',
    ];

    protected $casts = [
        'founded_year' => 'integer',
        'is_verified' => 'boolean',
    ];

    /**
     * Get all job postings for this company
     */
    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    /**
     * Get active job postings for this company
     */
    public function activeJobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class)->where('is_active', true);
    }

    /**
     * Get the company size label
     */
    public function getSizeLabel(): string
    {
        return match ($this->size) {
            'startup' => '1-10 employees',
            'small' => '11-50 employees',
            'medium' => '51-200 employees',
            'large' => '201-1000 employees',
            'enterprise' => '1000+ employees',
            default => 'Unknown size',
        };
    }

    /**
     * Scope to verified companies
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope by industry
     */
    public function scopeByIndustry($query, string $industry)
    {
        return $query->where('industry', $industry);
    }
}