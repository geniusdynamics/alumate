<?php

namespace App\Services;

use App\Models\SsoConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OAuthService
{
    /**
     * Handle OAuth callback and extract user data
     */
    public function handleCallback(SsoConfiguration $config, array $callbackData): array
    {
        Log::info('Processing OAuth callback', [
            'config_id' => $config->id,
            'provider' => $config->provider,
        ]);

        // Extract authorization code
        $code = $callbackData['code'] ?? null;
        if (! $code) {
            throw new \Exception('Authorization code not provided in callback');
        }

        // Exchange code for access token
        $tokenData = $this->exchangeCodeForToken($config, $code);

        // Get user information
        $userData = $this->getUserInfo($config, $tokenData['access_token']);

        // Add token information to user data
        $userData['access_token'] = $tokenData['access_token'];
        $userData['refresh_token'] = $tokenData['refresh_token'] ?? null;
        $userData['token_expires_at'] = isset($tokenData['expires_in'])
            ? now()->addSeconds($tokenData['expires_in'])
            : null;

        return $userData;
    }

    /**
     * Exchange authorization code for access token
     */
    protected function exchangeCodeForToken(SsoConfiguration $config, string $code): array
    {
        $tokenUrl = $this->getTokenUrl($config);
        $redirectUri = $config->getRedirectUrl();

        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => $config->client_id,
            'client_secret' => $config->client_secret,
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ];

        // Add PKCE verifier if used
        if (config('sso.oauth.pkce') && session('oauth_code_verifier')) {
            $params['code_verifier'] = session('oauth_code_verifier');
        }

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($tokenUrl, $params);

            if (! $response->successful()) {
                throw new \Exception('Token exchange failed: '.$response->body());
            }

            $tokenData = $response->json();

            if (! isset($tokenData['access_token'])) {
                throw new \Exception('Access token not received from provider');
            }

            return $tokenData;

        } catch (\Exception $e) {
            Log::error('OAuth token exchange failed', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get user information using access token
     */
    protected function getUserInfo(SsoConfiguration $config, string $accessToken): array
    {
        $userInfoUrl = $this->getUserInfoUrl($config);

        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get($userInfoUrl);

            if (! $response->successful()) {
                throw new \Exception('Failed to get user info: '.$response->body());
            }

            $userData = $response->json();

            // For OIDC, we might also have ID token with additional claims
            if ($config->protocol === 'oidc') {
                $userData = $this->mergeIdTokenClaims($userData, $accessToken);
            }

            return $userData;

        } catch (\Exception $e) {
            Log::error('OAuth user info request failed', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Merge ID token claims for OIDC
     */
    protected function mergeIdTokenClaims(array $userData, string $accessToken): array
    {
        // In a real implementation, you would decode and validate the ID token
        // For now, we'll just return the user data as-is
        return $userData;
    }

    /**
     * Initiate OAuth authentication
     */
    public function initiateAuthentication(SsoConfiguration $config, ?string $returnUrl = null): string
    {
        $authUrl = $this->getAuthorizationUrl($config);
        $redirectUri = $config->getRedirectUrl();
        $scopes = implode(' ', $config->getScopes());
        $state = Str::random(40);

        // Store state and return URL in session
        session([
            'oauth_state' => $state,
            'oauth_return_url' => $returnUrl,
        ]);

        $params = [
            'response_type' => config('sso.oauth.response_type', 'code'),
            'client_id' => $config->client_id,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'state' => $state,
        ];

        // Add PKCE parameters if enabled
        if (config('sso.oauth.pkce')) {
            $codeVerifier = Str::random(128);
            $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

            session(['oauth_code_verifier' => $codeVerifier]);

            $params['code_challenge'] = $codeChallenge;
            $params['code_challenge_method'] = 'S256';
        }

        // Add response mode if specified
        if (config('sso.oauth.response_mode')) {
            $params['response_mode'] = config('sso.oauth.response_mode');
        }

        return $authUrl.'?'.http_build_query($params);
    }

    /**
     * Get authorization URL for provider
     */
    protected function getAuthorizationUrl(SsoConfiguration $config): string
    {
        // For OIDC, try to get from discovery document
        if ($config->protocol === 'oidc' && $config->discovery_url) {
            $discoveryData = $this->getDiscoveryDocument($config);
            if (isset($discoveryData['authorization_endpoint'])) {
                return $discoveryData['authorization_endpoint'];
            }
        }

        // Fallback to configured URL or provider-specific defaults
        return $config->getProviderConfig('authorization_url') ?? $this->getDefaultAuthUrl($config);
    }

    /**
     * Get token URL for provider
     */
    protected function getTokenUrl(SsoConfiguration $config): string
    {
        // For OIDC, try to get from discovery document
        if ($config->protocol === 'oidc' && $config->discovery_url) {
            $discoveryData = $this->getDiscoveryDocument($config);
            if (isset($discoveryData['token_endpoint'])) {
                return $discoveryData['token_endpoint'];
            }
        }

        // Fallback to configured URL or provider-specific defaults
        return $config->getProviderConfig('token_url') ?? $this->getDefaultTokenUrl($config);
    }

    /**
     * Get user info URL for provider
     */
    protected function getUserInfoUrl(SsoConfiguration $config): string
    {
        // For OIDC, try to get from discovery document
        if ($config->protocol === 'oidc' && $config->discovery_url) {
            $discoveryData = $this->getDiscoveryDocument($config);
            if (isset($discoveryData['userinfo_endpoint'])) {
                return $discoveryData['userinfo_endpoint'];
            }
        }

        // Fallback to configured URL or provider-specific defaults
        return $config->getProviderConfig('userinfo_url') ?? $this->getDefaultUserInfoUrl($config);
    }

    /**
     * Get OIDC discovery document
     */
    protected function getDiscoveryDocument(SsoConfiguration $config): array
    {
        $cacheKey = "oidc_discovery_{$config->id}";

        return cache()->remember($cacheKey, 3600, function () use ($config) {
            try {
                $response = Http::timeout(30)->get($config->discovery_url);

                if (! $response->successful()) {
                    throw new \Exception('Failed to fetch discovery document');
                }

                return $response->json();

            } catch (\Exception $e) {
                Log::error('OIDC discovery failed', [
                    'config_id' => $config->id,
                    'discovery_url' => $config->discovery_url,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Get default authorization URL based on provider
     */
    protected function getDefaultAuthUrl(SsoConfiguration $config): string
    {
        return match (strtolower($config->provider)) {
            'google' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'microsoft' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
            'github' => 'https://github.com/login/oauth/authorize',
            'linkedin' => 'https://www.linkedin.com/oauth/v2/authorization',
            'facebook' => 'https://www.facebook.com/v18.0/dialog/oauth',
            default => throw new \Exception("Unknown OAuth provider: {$config->provider}"),
        };
    }

    /**
     * Get default token URL based on provider
     */
    protected function getDefaultTokenUrl(SsoConfiguration $config): string
    {
        return match (strtolower($config->provider)) {
            'google' => 'https://oauth2.googleapis.com/token',
            'microsoft' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'github' => 'https://github.com/login/oauth/access_token',
            'linkedin' => 'https://www.linkedin.com/oauth/v2/accessToken',
            'facebook' => 'https://graph.facebook.com/v18.0/oauth/access_token',
            default => throw new \Exception("Unknown OAuth provider: {$config->provider}"),
        };
    }

    /**
     * Get default user info URL based on provider
     */
    protected function getDefaultUserInfoUrl(SsoConfiguration $config): string
    {
        return match (strtolower($config->provider)) {
            'google' => 'https://www.googleapis.com/oauth2/v2/userinfo',
            'microsoft' => 'https://graph.microsoft.com/v1.0/me',
            'github' => 'https://api.github.com/user',
            'linkedin' => 'https://api.linkedin.com/v2/people/~',
            'facebook' => 'https://graph.facebook.com/v18.0/me?fields=id,name,email,first_name,last_name',
            default => throw new \Exception("Unknown OAuth provider: {$config->provider}"),
        };
    }

    /**
     * Test OAuth configuration
     */
    public function testConfiguration(SsoConfiguration $config): array
    {
        $results = [
            'discovery' => false,
            'endpoints' => false,
            'credentials' => false,
            'errors' => [],
        ];

        try {
            // Test OIDC discovery if applicable
            if ($config->protocol === 'oidc' && $config->discovery_url) {
                $discoveryData = $this->getDiscoveryDocument($config);
                $results['discovery'] = ! empty($discoveryData);
                if (empty($discoveryData)) {
                    $results['errors'][] = 'Failed to retrieve OIDC discovery document';
                }
            } else {
                $results['discovery'] = true; // Not applicable
            }

            // Test endpoint connectivity
            $authUrl = $this->getAuthorizationUrl($config);
            $tokenUrl = $this->getTokenUrl($config);

            $authConnectivity = $this->testEndpointConnectivity($authUrl);
            $tokenConnectivity = $this->testEndpointConnectivity($tokenUrl);

            $results['endpoints'] = $authConnectivity && $tokenConnectivity;
            if (! $authConnectivity) {
                $results['errors'][] = 'Cannot connect to authorization endpoint';
            }
            if (! $tokenConnectivity) {
                $results['errors'][] = 'Cannot connect to token endpoint';
            }

            // Test credentials (basic validation)
            if (empty($config->client_id) || empty($config->client_secret)) {
                $results['credentials'] = false;
                $results['errors'][] = 'Client ID and Client Secret are required';
            } else {
                $results['credentials'] = true;
            }

        } catch (\Exception $e) {
            $results['errors'][] = 'Configuration test failed: '.$e->getMessage();
        }

        return $results;
    }

    /**
     * Test endpoint connectivity
     */
    protected function testEndpointConnectivity(string $url): bool
    {
        try {
            $response = Http::timeout(10)->get($url);

            // For OAuth endpoints, we expect either success or a specific OAuth error
            return $response->successful() || $response->status() === 400;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate OAuth state parameter
     */
    public function validateState(string $receivedState): bool
    {
        $sessionState = session('oauth_state');

        if (! $sessionState || $sessionState !== $receivedState) {
            Log::warning('OAuth state validation failed', [
                'received_state' => $receivedState,
                'session_state' => $sessionState,
            ]);

            return false;
        }

        // Clear state from session
        session()->forget('oauth_state');

        return true;
    }

    /**
     * Refresh access token
     */
    public function refreshToken(SsoConfiguration $config, string $refreshToken): array
    {
        $tokenUrl = $this->getTokenUrl($config);

        $params = [
            'grant_type' => 'refresh_token',
            'client_id' => $config->client_id,
            'client_secret' => $config->client_secret,
            'refresh_token' => $refreshToken,
        ];

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($tokenUrl, $params);

            if (! $response->successful()) {
                throw new \Exception('Token refresh failed: '.$response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('OAuth token refresh failed', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
