<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Has Profile Information Trait
 *
 * Extracts profile-related fields from the monolithic User model.
 * Contains: name, phone, avatar, location, graduation_year, degree, skills, is_mentor
 *
 * Used by: User, Graduate
 */
trait HasProfileInformation
{
    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name ?? '', $this->last_name ?? '']);

        return implode(' ', $parts) ?: $this->name ?? 'Unknown User';
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
     * Scope: Filter by graduation year range
     */
    public function scopeGraduationYearBetween(Builder $query, int $from, int $to): Builder
    {
        return $query->whereBetween('graduation_year', [$from, $to]);
    }

    /**
     * Scope: Filter by location
     */
    public function scopeWhereLocation(Builder $query, string $location): Builder
    {
        return $query->where('location', 'LIKE', "%{$location}%");
    }

    /**
     * Scope: Filter by skills
     */
    public function scopeWhereSkill(Builder $query, string $skill): Builder
    {
        return $query->whereJsonContains('skills', $skill);
    }

    /**
     * Scope: Filter mentors only
     */
    public function scopeMentors(Builder $query): Builder
    {
        return $query->where('is_mentor', true);
    }

    /**
     * Check if user has complete profile
     */
    public function hasCompleteProfile(): bool
    {
        $requiredFields = ['first_name', 'last_name', 'email', 'phone', 'location'];

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
    public function getProfileCompletionPercentage(): int
    {
        $allFields = ['first_name', 'last_name', 'email', 'phone', 'avatar', 'location', 'graduation_year', 'degree', 'is_mentor'];
        $filledFields = 0;

        foreach ($allFields as $field) {
            if (! empty($this->{$field})) {
                $filledFields++;
            }
        }

        return (int) round(($filledFields / count($allFields)) * 100);
    }
}
