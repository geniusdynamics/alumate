<?php

namespace Database\Factories;

use App\Models\SsoConfiguration;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SsoConfiguration>
 */
class SsoConfigurationFactory extends Factory
{
    protected $model = SsoConfiguration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $provider = $this->faker->randomElement(['google', 'microsoft', 'saml', 'github', 'linkedin']);
        $protocol = $provider === 'saml' ? 'saml2' : $this->faker->randomElement(['oauth2', 'oidc']);

        return [
            'name' => $this->faker->company.' '.ucfirst($provider).' SSO',
            'provider' => $provider,
            'protocol' => $protocol,
            'institution_id' => $this->faker->boolean(70) ? Tenant::factory() : null,
            'configuration' => $this->getProviderConfiguration($provider, $protocol),
            'attribute_mapping' => $this->getAttributeMapping($protocol),
            'role_mapping' => $this->getRoleMapping(),
            'is_active' => $this->faker->boolean(90),
            'auto_provision' => $this->faker->boolean(60),
            'auto_update' => $this->faker->boolean(40),
            'entity_id' => $protocol === 'saml2' ? $this->faker->url : null,
            'certificate' => $protocol === 'saml2' ? $this->generateMockCertificate() : null,
            'private_key' => $protocol === 'saml2' ? $this->generateMockPrivateKey() : null,
            'sso_url' => $protocol === 'saml2' ? $this->faker->url.'/sso' : null,
            'sls_url' => $protocol === 'saml2' ? $this->faker->url.'/sls' : null,
            'client_id' => in_array($protocol, ['oauth2', 'oidc']) ? $this->faker->uuid : null,
            'client_secret' => in_array($protocol, ['oauth2', 'oidc']) ? $this->faker->sha256 : null,
            'discovery_url' => $protocol === 'oidc' ? $this->faker->url.'/.well-known/openid_configuration' : null,
            'scopes' => in_array($protocol, ['oauth2', 'oidc']) ? ['openid', 'profile', 'email'] : null,
            'metadata' => [
                'created_by' => 'factory',
                'test_mode' => true,
            ],
        ];
    }

    /**
     * Create a SAML configuration
     */
    public function saml(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'saml',
            'protocol' => 'saml2',
            'entity_id' => $this->faker->url,
            'certificate' => $this->generateMockCertificate(),
            'private_key' => $this->generateMockPrivateKey(),
            'sso_url' => $this->faker->url.'/sso',
            'sls_url' => $this->faker->url.'/sls',
            'client_id' => null,
            'client_secret' => null,
            'discovery_url' => null,
            'scopes' => null,
        ]);
    }

    /**
     * Create an OAuth2 configuration
     */
    public function oauth2(): static
    {
        return $this->state(fn (array $attributes) => [
            'protocol' => 'oauth2',
            'client_id' => $this->faker->uuid,
            'client_secret' => $this->faker->sha256,
            'scopes' => ['profile', 'email'],
            'entity_id' => null,
            'certificate' => null,
            'private_key' => null,
            'sso_url' => null,
            'sls_url' => null,
            'discovery_url' => null,
        ]);
    }

    /**
     * Create an OIDC configuration
     */
    public function oidc(): static
    {
        return $this->state(fn (array $attributes) => [
            'protocol' => 'oidc',
            'client_id' => $this->faker->uuid,
            'client_secret' => $this->faker->sha256,
            'discovery_url' => $this->faker->url.'/.well-known/openid_configuration',
            'scopes' => ['openid', 'profile', 'email'],
            'entity_id' => null,
            'certificate' => null,
            'private_key' => null,
            'sso_url' => null,
            'sls_url' => null,
        ]);
    }

    /**
     * Create an active configuration
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive configuration
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a global configuration (not tied to institution)
     */
    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'institution_id' => null,
        ]);
    }

    /**
     * Get provider-specific configuration
     */
    protected function getProviderConfiguration(string $provider, string $protocol): array
    {
        $config = [
            'timeout' => 30,
            'verify_ssl' => true,
        ];

        if ($protocol === 'saml2') {
            $config = array_merge($config, [
                'sign_requests' => $this->faker->boolean(),
                'encrypt_assertions' => $this->faker->boolean(),
                'name_id_format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
            ]);
        }

        if (in_array($protocol, ['oauth2', 'oidc'])) {
            $config = array_merge($config, [
                'authorization_url' => $this->getAuthUrl($provider),
                'token_url' => $this->getTokenUrl($provider),
                'userinfo_url' => $this->getUserInfoUrl($provider),
            ]);
        }

        return $config;
    }

    /**
     * Get attribute mapping for protocol
     */
    protected function getAttributeMapping(string $protocol): array
    {
        if ($protocol === 'saml2') {
            return [
                'name' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name',
                'email' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress',
                'first_name' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname',
                'last_name' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname',
            ];
        }

        return [
            'name' => 'name',
            'email' => 'email',
            'first_name' => 'given_name',
            'last_name' => 'family_name',
        ];
    }

    /**
     * Get role mapping
     */
    protected function getRoleMapping(): array
    {
        return [
            'admin' => 'Super Admin',
            'institution_admin' => 'Institution Admin',
            'student' => 'Student',
            'alumni' => 'Graduate',
            'employer' => 'Employer',
            'default_role' => 'Graduate',
        ];
    }

    /**
     * Generate mock certificate for testing
     */
    protected function generateMockCertificate(): string
    {
        return '-----BEGIN CERTIFICATE-----
MIICXjCCAcegAwIBAgIJAKS0yiqVrJejMA0GCSqGSIb3DQEBCwUAMEUxCzAJBgNV
BAYTAkFVMRMwEQYDVQQIDApTb21lLVN0YXRlMSEwHwYDVQQKDBhJbnRlcm5ldCBX
aWRnaXRzIFB0eSBMdGQwHhcNMjMwMTAxMDAwMDAwWhcNMjQwMTAxMDAwMDAwWjBF
MQswCQYDVQQGEwJBVTETMBEGA1UECAwKU29tZS1TdGF0ZTEhMB8GA1UECgwYSW50
ZXJuZXQgV2lkZ2l0cyBQdHkgTHRkMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKB
gQC7vbqajDw4o6gJy8UtmIbkcpnkO3Kwc4qsEnSZp/TR+fQi62F79RHWmwKOtBmY
ckbmgqnSpyuMnIgdnuJhkmQfmCxcjUMcnJS0W9SRtVb9+kVn5VkjmQfmCxcjUMcn
JS0W9SRtVb9+kVn5VkjmQfmCxcjUMcnJS0W9SRtVb9+kVn5VkjmQIDAQABo1MwUTA
dBgNVHQ4EFgQU4qSpyuMnIgdnuJhkmQfmCxcjUMcnJwwHwYDVR0jBBgwFoAU4qSp
yuMnIgdnuJhkmQfmCxcjUMcnJwwDwYDVR0TAQH/BAUwAwEB/zANBgkqhkiG9w0B
AQsFAAOBgQBvzBOFaHIp1AqHQTpHUyFd7qkzjKU4q4q4q4q4q4q4q4q4q4q4q4q4
q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4q4
-----END CERTIFICATE-----';
    }

    /**
     * Generate mock private key for testing
     */
    protected function generateMockPrivateKey(): string
    {
        return '-----BEGIN PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBALu9upqMPDijqAnL
xS2YhuRymeQ7crBziqwSdJmn9NH59CLrYXv1EdabAo60GZhyRuaCqdKnK4yciB2e
4mGSZB+YLFyNQxyclLRb1JG1Vv36RWflWSOZB+YLFyNQxyclLRb1JG1Vv36RWflW
SOZB+YLFyNQxyclLRb1JG1Vv36RWflWSOZAgMBAAECgYEAqKqKqKqKqKqKqKqKqKqK
qKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqK
qKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqK
qKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqK
qKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqKqK
-----END PRIVATE KEY-----';
    }

    /**
     * Get authorization URL for provider
     */
    protected function getAuthUrl(string $provider): string
    {
        return match ($provider) {
            'google' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'microsoft' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
            'github' => 'https://github.com/login/oauth/authorize',
            'linkedin' => 'https://www.linkedin.com/oauth/v2/authorization',
            default => $this->faker->url.'/oauth/authorize',
        };
    }

    /**
     * Get token URL for provider
     */
    protected function getTokenUrl(string $provider): string
    {
        return match ($provider) {
            'google' => 'https://oauth2.googleapis.com/token',
            'microsoft' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'github' => 'https://github.com/login/oauth/access_token',
            'linkedin' => 'https://www.linkedin.com/oauth/v2/accessToken',
            default => $this->faker->url.'/oauth/token',
        };
    }

    /**
     * Get user info URL for provider
     */
    protected function getUserInfoUrl(string $provider): string
    {
        return match ($provider) {
            'google' => 'https://www.googleapis.com/oauth2/v2/userinfo',
            'microsoft' => 'https://graph.microsoft.com/v1.0/me',
            'github' => 'https://api.github.com/user',
            'linkedin' => 'https://api.linkedin.com/v2/people/~',
            default => $this->faker->url.'/oauth/userinfo',
        };
    }
}
