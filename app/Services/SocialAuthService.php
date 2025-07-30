<?php

namespace App\Services;

use App\Models\SocialProfile;
use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class SocialAuthService
{
    /**
     * Create or update a user from social provider data.
     */
    public function createOrUpdateUser(string $provider, SocialiteUser $socialUser): User
    {
        return DB::transaction(function () use ($provider, $socialUser) {
            // Check if social profile already exists
            $socialProfile = SocialProfile::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if ($socialProfile) {
                // Update existing social profile
                $this->updateSocialProfile($socialProfile, $socialUser);
                return $socialProfile->user;
            }

            // Check if user exists by email
            $user = User::where('email', $socialUser->getEmail())->first();

            if (!$user) {
                // Create new user
                $user = $this->createUserFromSocialData($socialUser);
            }

            // Create social profile
            $this->createSocialProfile($user, $provider, $socialUser);

            return $user;
        });
    }

    /**
     * Link a social profile to an existing user.
     */
    public function linkProfileToUser(User $user, string $provider, SocialiteUser $socialUser): SocialProfile
    {
        // Check if this social profile is already linked to another user
        $existingProfile = SocialProfile::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($existingProfile && $existingProfile->user_id !== $user->id) {
            throw new Exception('This ' . $provider . ' account is already linked to another user.');
        }

        // Check if user already has this provider linked
        $userProfile = $user->socialProfiles()->where('provider', $provider)->first();
        
        if ($userProfile) {
            // Update existing profile
            return $this->updateSocialProfile($userProfile, $socialUser);
        }

        // Create new social profile
        return $this->createSocialProfile($user, $provider, $socialUser);
    }

    /**
     * Unlink a social profile.
     */
    public function unlinkProfile(int $profileId): bool
    {
        $profile = SocialProfile::findOrFail($profileId);
        
        return $profile->delete();
    }

    /**
     * Create a new user from social provider data.
     */
    private function createUserFromSocialData(SocialiteUser $socialUser): User
    {
        $name = $socialUser->getName() ?: 'User';
        $email = $socialUser->getEmail();
        
        // Generate unique username from name or email
        $username = $this->generateUniqueUsername($name, $email);

        return User::create([
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'avatar_url' => $socialUser->getAvatar(),
            'email_verified_at' => now(), // Social accounts are considered verified
        ]);
    }

    /**
     * Create a social profile for a user.
     */
    private function createSocialProfile(User $user, string $provider, SocialiteUser $socialUser): SocialProfile
    {
        $profileData = [
            'id' => $socialUser->getId(),
            'name' => $socialUser->getName(),
            'email' => $socialUser->getEmail(),
            'avatar' => $socialUser->getAvatar(),
        ];

        // Add provider-specific data
        if ($provider === 'linkedin') {
            $profileData = array_merge($profileData, [
                'localizedFirstName' => $socialUser->user['localizedFirstName'] ?? null,
                'localizedLastName' => $socialUser->user['localizedLastName'] ?? null,
                'publicProfileUrl' => $socialUser->user['publicProfileUrl'] ?? null,
            ]);
        } elseif ($provider === 'github') {
            $profileData = array_merge($profileData, [
                'login' => $socialUser->user['login'] ?? null,
                'html_url' => $socialUser->user['html_url'] ?? null,
                'company' => $socialUser->user['company'] ?? null,
                'location' => $socialUser->user['location'] ?? null,
                'bio' => $socialUser->user['bio'] ?? null,
            ]);
        } elseif ($provider === 'twitter') {
            $profileData = array_merge($profileData, [
                'username' => $socialUser->user['username'] ?? null,
                'profile_image_url' => $socialUser->user['profile_image_url'] ?? null,
                'description' => $socialUser->user['description'] ?? null,
                'location' => $socialUser->user['location'] ?? null,
            ]);
        }

        // Set as primary if it's the user's first social profile
        $isPrimary = $user->socialProfiles()->count() === 0;

        return SocialProfile::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'profile_data' => $profileData,
            'access_token' => $socialUser->token,
            'refresh_token' => $socialUser->refreshToken,
            'is_primary' => $isPrimary,
        ]);
    }

    /**
     * Update an existing social profile.
     */
    private function updateSocialProfile(SocialProfile $profile, SocialiteUser $socialUser): SocialProfile
    {
        $profileData = array_merge($profile->profile_data ?? [], [
            'name' => $socialUser->getName(),
            'email' => $socialUser->getEmail(),
            'avatar' => $socialUser->getAvatar(),
        ]);

        $profile->update([
            'profile_data' => $profileData,
            'access_token' => $socialUser->token,
            'refresh_token' => $socialUser->refreshToken,
        ]);

        return $profile;
    }

    /**
     * Generate a unique username from name or email.
     */
    private function generateUniqueUsername(string $name, string $email): string
    {
        // Try to use name first
        $baseUsername = Str::slug(Str::lower($name));
        
        // If name is empty or invalid, use email prefix
        if (empty($baseUsername) || strlen($baseUsername) < 3) {
            $baseUsername = Str::before($email, '@');
            $baseUsername = Str::slug(Str::lower($baseUsername));
        }

        // Ensure minimum length
        if (strlen($baseUsername) < 3) {
            $baseUsername = 'user' . rand(1000, 9999);
        }

        $username = $baseUsername;
        $counter = 1;

        // Make sure username is unique
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }
}