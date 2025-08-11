<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'profile_data',
        'access_token',
        'refresh_token',
        'is_primary',
    ];

    protected $casts = [
        'profile_data' => 'array',
        'is_primary' => 'boolean',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the user that owns the social profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the profile avatar URL from the social provider.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->profile_data) {
            return null;
        }

        return match ($this->provider) {
            'linkedin' => $this->profile_data['picture'] ?? null,
            'github' => $this->profile_data['avatar_url'] ?? null,
            'twitter' => $this->profile_data['profile_image_url'] ?? null,
            'facebook' => $this->profile_data['picture']['data']['url'] ?? null,
            'google' => $this->profile_data['picture'] ?? null,
            default => null,
        };
    }

    /**
     * Get the profile URL on the social platform.
     */
    public function getProfileUrlAttribute(): ?string
    {
        if (! $this->profile_data) {
            return null;
        }

        return match ($this->provider) {
            'linkedin' => $this->profile_data['publicProfileUrl'] ?? null,
            'github' => $this->profile_data['html_url'] ?? null,
            'twitter' => "https://twitter.com/{$this->profile_data['username']}" ?? null,
            'facebook' => $this->profile_data['link'] ?? null,
            'google' => null, // Google doesn't provide public profile URLs
            default => null,
        };
    }

    /**
     * Get the display name from the social profile.
     */
    public function getDisplayNameAttribute(): ?string
    {
        if (! $this->profile_data) {
            return null;
        }

        return match ($this->provider) {
            'linkedin' => $this->profile_data['localizedFirstName'].' '.$this->profile_data['localizedLastName'],
            'github' => $this->profile_data['name'] ?? $this->profile_data['login'],
            'twitter' => $this->profile_data['name'] ?? $this->profile_data['username'],
            'facebook' => $this->profile_data['name'] ?? null,
            'google' => $this->profile_data['name'] ?? null,
            default => null,
        };
    }

    /**
     * Check if the access token is expired.
     */
    public function isTokenExpired(): bool
    {
        if (! $this->profile_data || ! isset($this->profile_data['expires_at'])) {
            return false;
        }

        return now()->timestamp > $this->profile_data['expires_at'];
    }

    /**
     * Scope to get profiles by provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to get primary profiles.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
