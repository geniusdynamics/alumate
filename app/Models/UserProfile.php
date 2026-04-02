<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Profile Model
 *
 * Stores personal profile information separate from authentication data.
 * Extracted from the monolithic User model (967 lines).
 */
class UserProfile extends Model
{
    use HasFactory;

    protected $table = 'user_profiles';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'avatar',
        'avatar_url',
        'location',
        'country',
        'region',
        'latitude',
        'longitude',
        'current_title',
        'current_company',
        'current_industry',
        'bio',
        'website',
        'linkedin_url',
        'twitter_url',
        'github_url',
        'profile_visibility',
        'location_privacy',
        'is_open_to_opportunities',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the user that owns this profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name ?? '', $this->last_name ?? '']);

        return implode(' ', $parts) ?: 'Unknown';
    }

    /**
     * Get initials for avatar placeholder
     */
    public function getInitialsAttribute(): string
    {
        $name = $this->full_name;
        $words = explode(' ', $name);
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return $initials ?: '?';
    }

    /**
     * Scope: Filter by location
     */
    public function scopeWhereLocation($query, string $location)
    {
        return $query->where(function ($q) use ($location) {
            $q->where('location', 'LIKE', "%{$location}%")
                ->orWhere('country', 'LIKE', "%{$location}%")
                ->orWhere('region', 'LIKE', "%{$location}%");
        });
    }

    /**
     * Scope: Filter by current company
     */
    public function scopeWhereCompany($query, string $company)
    {
        return $query->where('current_company', 'LIKE', "%{$company}%");
    }

    /**
     * Scope: Filter by current title
     */
    public function scopeWhereTitle($query, string $title)
    {
        return $query->where('current_title', 'LIKE', "%{$title}%");
    }

    /**
     * Scope: Filter by country
     */
    public function scopeWhereCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope: Filter open to opportunities
     */
    public function scopeOpenToOpportunities($query)
    {
        return $query->where('is_open_to_opportunities', true);
    }

    /**
     * Check if profile is complete
     */
    public function isComplete(): bool
    {
        $requiredFields = ['first_name', 'last_name', 'phone', 'location'];
        foreach ($requiredFields as $field) {
            if (empty($this->{$field})) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get profile completion percentage
     */
    public function getCompletionPercentage(): int
    {
        $allFields = ['first_name', 'last_name', 'phone', 'avatar', 'location', 'country', 'current_title', 'current_company', 'bio'];
        $filledFields = 0;
        foreach ($allFields as $field) {
            if (! empty($this->{$field})) {
                $filledFields++;
            }
        }

        return (int) round(($filledFields / count($allFields)) * 100);
    }
}
