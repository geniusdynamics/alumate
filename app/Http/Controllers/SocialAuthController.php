<?php

namespace App\Http\Controllers;

use App\Models\SocialProfile;
use App\Services\SocialAuthService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function __construct(
        private SocialAuthService $socialAuthService
    ) {}

    /**
     * Redirect to the social provider for authentication.
     */
    public function redirectToProvider(string $provider): RedirectResponse
    {
        if (! $this->isValidProvider($provider)) {
            return redirect()->route('login')->with('error', 'Invalid social provider.');
        }

        try {
            return Socialite::driver($provider)->redirect();
        } catch (Exception $e) {
            return redirect()->route('login')->with('error', 'Unable to connect to '.ucfirst($provider).'. Please try again.');
        }
    }

    /**
     * Handle the callback from the social provider.
     */
    public function handleProviderCallback(string $provider): RedirectResponse
    {
        if (! $this->isValidProvider($provider)) {
            return redirect()->route('login')->with('error', 'Invalid social provider.');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();

            $user = $this->socialAuthService->createOrUpdateUser($provider, $socialUser);

            Auth::login($user, true);

            return redirect()->intended(route('dashboard'))->with('success', 'Successfully logged in with '.ucfirst($provider).'!');

        } catch (Exception $e) {
            return redirect()->route('login')->with('error', 'Authentication failed. Please try again.');
        }
    }

    /**
     * Link a social profile to the current user.
     */
    public function linkProfile(Request $request, string $provider): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'You must be logged in to link social profiles.');
        }

        if (! $this->isValidProvider($provider)) {
            return redirect()->back()->with('error', 'Invalid social provider.');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();

            $this->socialAuthService->linkProfileToUser(Auth::user(), $provider, $socialUser);

            return redirect()->back()->with('success', ucfirst($provider).' profile linked successfully!');

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to link '.ucfirst($provider).' profile. It may already be linked to another account.');
        }
    }

    /**
     * Unlink a social profile from the current user.
     */
    public function unlinkProfile(Request $request, int $profileId): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        try {
            $profile = SocialProfile::where('id', $profileId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            // Prevent unlinking if it's the only authentication method
            if (Auth::user()->socialProfiles()->count() === 1 && ! Auth::user()->password) {
                return redirect()->back()->with('error', 'Cannot unlink your only authentication method. Please set a password first.');
            }

            $this->socialAuthService->unlinkProfile($profileId);

            return redirect()->back()->with('success', ucfirst($profile->provider).' profile unlinked successfully!');

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to unlink social profile.');
        }
    }

    /**
     * Show the social profile linking page.
     */
    public function showLinkingPage()
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $linkedProfiles = Auth::user()->socialProfiles;
        $availableProviders = ['linkedin', 'github', 'twitter', 'facebook', 'google'];
        $unlinkedProviders = array_diff($availableProviders, $linkedProfiles->pluck('provider')->toArray());

        return inertia('Profile/SocialProfiles', [
            'linkedProfiles' => $linkedProfiles,
            'unlinkedProviders' => $unlinkedProviders,
        ]);
    }

    /**
     * Check if the provider is valid and supported.
     */
    private function isValidProvider(string $provider): bool
    {
        return in_array($provider, ['linkedin', 'github', 'twitter', 'facebook', 'google']);
    }
}
