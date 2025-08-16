<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SsoConfiguration;
use App\Services\SSOIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SSOController extends Controller
{
    public function __construct(
        protected SSOIntegrationService $ssoService
    ) {}

    /**
     * Show available SSO providers for login
     */
    public function index(Request $request)
    {
        $institutionId = $request->get('institution_id');
        $configurations = $this->ssoService->getAvailableConfigurations($institutionId);

        return Inertia::render('Auth/SSO/Index', [
            'configurations' => $configurations->map(function ($config) {
                return [
                    'id' => $config->id,
                    'name' => $config->name,
                    'provider' => $config->provider,
                    'protocol' => $config->protocol,
                    'login_url' => $this->ssoService->getLoginUrl($config),
                ];
            }),
            'institution_id' => $institutionId,
        ]);
    }

    /**
     * Redirect to SSO provider for authentication
     */
    public function redirect(Request $request, ?string $provider = null, ?int $configId = null)
    {
        try {
            // Get configuration
            $config = $this->getConfiguration($provider, $configId);

            if (! $config || ! $config->is_active) {
                return redirect()->route('login')
                    ->withErrors(['sso' => 'SSO provider not available or inactive']);
            }

            // Get return URL
            $returnUrl = $request->get('return_url', $request->get('intended', '/dashboard'));

            // Store return URL in session
            session(['sso_return_url' => $returnUrl]);

            // Generate authentication URL based on protocol
            if ($config->isSaml()) {
                $authUrl = app(\App\Services\SamlService::class)->initiateAuthentication($config, $returnUrl);
            } elseif ($config->isOAuth()) {
                $authUrl = app(\App\Services\OAuthService::class)->initiateAuthentication($config, $returnUrl);
            } else {
                throw new \Exception('Unsupported SSO protocol: '.$config->protocol);
            }

            Log::info('SSO authentication initiated', [
                'provider' => $config->provider,
                'config_id' => $config->id,
                'user_ip' => $request->ip(),
            ]);

            return redirect($authUrl);

        } catch (\Exception $e) {
            Log::error('SSO redirect failed', [
                'provider' => $provider,
                'config_id' => $configId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->withErrors(['sso' => 'Failed to initiate SSO authentication: '.$e->getMessage()]);
        }
    }

    /**
     * Handle SSO callback from provider
     */
    public function callback(Request $request, ?string $provider = null, ?int $configId = null)
    {
        try {
            // Get configuration
            $config = $this->getConfiguration($provider, $configId);

            if (! $config || ! $config->is_active) {
                throw new \Exception('SSO configuration not found or inactive');
            }

            // Check for errors in callback
            if ($request->has('error')) {
                $error = $request->get('error');
                $errorDescription = $request->get('error_description', 'Unknown error');

                Log::warning('SSO callback error', [
                    'provider' => $config->provider,
                    'error' => $error,
                    'description' => $errorDescription,
                ]);

                return redirect()->route('login')
                    ->withErrors(['sso' => "SSO authentication failed: {$errorDescription}"]);
            }

            // Validate OAuth state if applicable
            if ($config->isOAuth() && $request->has('state')) {
                $oauthService = app(\App\Services\OAuthService::class);
                if (! $oauthService->validateState($request->get('state'))) {
                    throw new \Exception('Invalid OAuth state parameter');
                }
            }

            // Handle the callback
            $user = $this->ssoService->handleCallback($config, $request->all());

            // Log the user in
            Auth::login($user, config('sso.defaults.remember_me', true));

            // Get return URL
            $returnUrl = session('sso_return_url', $user->getDashboardRoute());
            session()->forget('sso_return_url');

            Log::info('SSO authentication successful', [
                'user_id' => $user->id,
                'provider' => $config->provider,
                'email' => $user->email,
            ]);

            return redirect()->intended($returnUrl);

        } catch (\Exception $e) {
            Log::error('SSO callback failed', [
                'provider' => $provider,
                'config_id' => $configId,
                'error' => $e->getMessage(),
                'request_data' => $request->except(['password', 'token']),
            ]);

            $errorMessage = config('sso.error_handling.show_detailed_errors')
                ? $e->getMessage()
                : 'SSO authentication failed. Please try again or contact support.';

            return redirect()->route('login')
                ->withErrors(['sso' => $errorMessage]);
        }
    }

    /**
     * Handle SSO logout
     */
    public function logout(Request $request)
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return redirect()->route('login');
            }

            // Get SSO configuration from user profile
            $ssoConfigId = $user->profile_data['sso_config_id'] ?? null;
            $sessionId = session(config('sso.session.sso_session_key'));

            if ($ssoConfigId) {
                $config = SsoConfiguration::find($ssoConfigId);
                if ($config) {
                    $this->ssoService->logout($config, $sessionId);
                }
            } else {
                // Regular logout
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            $redirectUrl = config('sso.session.logout_redirect', '/');

            return redirect($redirectUrl);

        } catch (\Exception $e) {
            Log::error('SSO logout failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            // Fallback to regular logout
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/');
        }
    }

    /**
     * Handle SAML metadata request
     */
    public function metadata(Request $request, int $configId)
    {
        try {
            $config = SsoConfiguration::findOrFail($configId);

            if (! $config->isSaml()) {
                abort(404, 'Not a SAML configuration');
            }

            // Generate SAML metadata
            $metadata = $this->generateSamlMetadata($config);

            return response($metadata)
                ->header('Content-Type', 'application/samlmetadata+xml');

        } catch (\Exception $e) {
            Log::error('SAML metadata generation failed', [
                'config_id' => $configId,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Failed to generate metadata');
        }
    }

    /**
     * Test SSO configuration
     */
    public function test(Request $request, int $configId)
    {
        try {
            $config = SsoConfiguration::findOrFail($configId);

            // Check permissions
            if (! $request->user()->can('manage-sso') && $config->institution_id !== $request->user()->institution_id) {
                abort(403, 'Unauthorized');
            }

            $results = $this->ssoService->testConfiguration($config);

            return response()->json([
                'success' => true,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('SSO configuration test failed', [
                'config_id' => $configId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get SSO configuration
     */
    protected function getConfiguration(?string $provider, ?int $configId): ?SsoConfiguration
    {
        if ($configId) {
            return SsoConfiguration::find($configId);
        }

        if ($provider) {
            return SsoConfiguration::active()
                ->where('provider', $provider)
                ->first();
        }

        return null;
    }

    /**
     * Generate SAML metadata for service provider
     */
    protected function generateSamlMetadata(SsoConfiguration $config): string
    {
        $entityId = $config->entity_id;
        $acsUrl = route('auth.sso.callback', ['provider' => $config->provider, 'config' => $config->id]);
        $sloUrl = route('auth.sso.logout');

        $metadata = '<?xml version="1.0" encoding="UTF-8"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="'.htmlspecialchars($entityId).'">
    <md:SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="'.htmlspecialchars($acsUrl).'" index="0"/>
        <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="'.htmlspecialchars($sloUrl).'"/>
    </md:SPSSODescriptor>
</md:EntityDescriptor>';

        return $metadata;
    }
}
