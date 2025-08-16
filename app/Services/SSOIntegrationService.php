<?php

namespace App\Services;

use App\Models\SsoConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SSOIntegrationService
{
    public function __construct(
        protected SamlService $samlService,
        protected OAuthService $oauthService
    ) {}

    /**
     * Authenticate user via SSO provider
     */
    public function authenticate(SsoConfiguration $config, array $userData): User
    {
        Log::info('SSO Authentication attempt', [
            'provider' => $config->provider,
            'protocol' => $config->protocol,
            'user_email' => $userData['email'] ?? 'unknown',
        ]);

        // Validate required attributes
        $this->validateUserData($userData);

        // Find or create user
        $user = $this->findOrCreateUser($config, $userData);

        // Update user data if configured
        if ($config->auto_update) {
            $this->updateUserData($user, $config, $userData);
        }

        // Sync roles if configured
        if (config('sso.provisioning.sync_roles')) {
            $this->syncUserRoles($user, $config, $userData);
        }

        // Update last login
        $user->updateLastLogin();

        // Log successful authentication
        Log::info('SSO Authentication successful', [
            'user_id' => $user->id,
            'provider' => $config->provider,
            'email' => $user->email,
        ]);

        return $user;
    }

    /**
     * Find existing user or create new one
     */
    protected function findOrCreateUser(SsoConfiguration $config, array $userData): User
    {
        $email = $userData['email'];

        // Try to find existing user by email
        $user = User::where('email', $email)->first();

        if ($user) {
            return $user;
        }

        // Check if auto-provisioning is enabled
        if (! $config->auto_provision && ! config('sso.provisioning.create_missing_users')) {
            throw new \Exception("User with email {$email} not found and auto-provisioning is disabled");
        }

        // Create new user
        return $this->createUser($config, $userData);
    }

    /**
     * Create new user from SSO data
     */
    protected function createUser(SsoConfiguration $config, array $userData): User
    {
        $mappedData = $this->mapUserAttributes($config, $userData);

        $user = User::create([
            'name' => $mappedData['name'],
            'email' => $mappedData['email'],
            'password' => Hash::make(Str::random(32)), // Random password for SSO users
            'phone' => $mappedData['phone'] ?? null,
            'bio' => $mappedData['bio'] ?? null,
            'location' => $mappedData['location'] ?? null,
            'website' => $mappedData['website'] ?? null,
            'institution_id' => $config->institution_id,
            'status' => config('sso.provisioning.default_status', 'active'),
            'email_verified_at' => config('sso.provisioning.email_verification') ? null : now(),
            'profile_data' => [
                'sso_provider' => $config->provider,
                'sso_config_id' => $config->id,
                'external_id' => $userData['id'] ?? $userData['sub'] ?? null,
                'first_login' => true,
            ],
        ]);

        // Assign default role
        $defaultRole = $this->getDefaultRole($config, $userData);
        if ($defaultRole) {
            $user->assignRole($defaultRole);
        }

        Log::info('New SSO user created', [
            'user_id' => $user->id,
            'email' => $user->email,
            'provider' => $config->provider,
        ]);

        return $user;
    }

    /**
     * Update existing user data from SSO
     */
    protected function updateUserData(User $user, SsoConfiguration $config, array $userData): void
    {
        if (! config('sso.provisioning.update_existing_users')) {
            return;
        }

        $mappedData = $this->mapUserAttributes($config, $userData);
        $updateData = [];

        // Only update if sync_attributes is enabled
        if (config('sso.provisioning.sync_attributes')) {
            foreach (['name', 'phone', 'bio', 'location', 'website'] as $field) {
                if (isset($mappedData[$field]) && $mappedData[$field] !== $user->$field) {
                    $updateData[$field] = $mappedData[$field];
                }
            }
        }

        // Update profile data
        $profileData = $user->profile_data ?? [];
        $profileData['last_sso_login'] = now()->toISOString();
        $profileData['sso_provider'] = $config->provider;
        $updateData['profile_data'] = $profileData;

        if (! empty($updateData)) {
            $user->update($updateData);

            Log::info('SSO user data updated', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($updateData),
            ]);
        }
    }

    /**
     * Map external attributes to user model fields
     */
    protected function mapUserAttributes(SsoConfiguration $config, array $userData): array
    {
        $mapping = $config->attribute_mapping ?? [];
        $defaultMapping = config("sso.attribute_mapping.{$config->protocol}", []);

        // Merge config-specific mapping with defaults
        $mapping = array_merge($defaultMapping, $mapping);

        $mappedData = [];

        foreach ($mapping as $internalField => $externalField) {
            if (isset($userData[$externalField])) {
                $mappedData[$internalField] = $userData[$externalField];
            }
        }

        // Ensure required fields are present
        if (empty($mappedData['email'])) {
            throw new \Exception('Email is required but not provided by SSO provider');
        }

        if (empty($mappedData['name'])) {
            // Try to construct name from first_name and last_name
            $firstName = $mappedData['first_name'] ?? '';
            $lastName = $mappedData['last_name'] ?? '';
            $mappedData['name'] = trim($firstName.' '.$lastName) ?: $mappedData['email'];
        }

        return $mappedData;
    }

    /**
     * Sync user roles based on SSO data
     */
    protected function syncUserRoles(User $user, SsoConfiguration $config, array $userData): void
    {
        $roleMapping = $config->role_mapping ?? config('sso.role_mapping', []);
        $externalRoles = $this->extractRoles($userData);

        $rolesToAssign = [];

        // Map external roles to internal roles
        foreach ($externalRoles as $externalRole) {
            $internalRole = $this->mapRole($externalRole, $roleMapping);
            if ($internalRole) {
                $rolesToAssign[] = $internalRole;
            }
        }

        // If no roles mapped, assign default role
        if (empty($rolesToAssign)) {
            $defaultRole = $this->getDefaultRole($config, $userData);
            if ($defaultRole) {
                $rolesToAssign[] = $defaultRole;
            }
        }

        // Sync roles
        if (! empty($rolesToAssign)) {
            $user->syncRoles($rolesToAssign);

            Log::info('SSO user roles synced', [
                'user_id' => $user->id,
                'roles' => $rolesToAssign,
                'external_roles' => $externalRoles,
            ]);
        }
    }

    /**
     * Extract roles from user data
     */
    protected function extractRoles(array $userData): array
    {
        $roles = [];

        // Check various possible role fields
        $roleFields = ['roles', 'groups', 'memberOf', 'authorities'];

        foreach ($roleFields as $field) {
            if (isset($userData[$field])) {
                $fieldRoles = is_array($userData[$field]) ? $userData[$field] : [$userData[$field]];
                $roles = array_merge($roles, $fieldRoles);
            }
        }

        return array_unique($roles);
    }

    /**
     * Map external role to internal role
     */
    protected function mapRole(string $externalRole, array $roleMapping): ?string
    {
        $externalRole = strtolower(trim($externalRole));

        // Check direct mapping
        if (isset($roleMapping[$externalRole])) {
            return $roleMapping[$externalRole];
        }

        // Check predefined role categories
        $roleCategories = [
            'admin_roles' => 'Super Admin',
            'institution_admin_roles' => 'Institution Admin',
            'student_roles' => 'Student',
            'alumni_roles' => 'Graduate',
            'employer_roles' => 'Employer',
        ];

        foreach ($roleCategories as $category => $internalRole) {
            $categoryRoles = config("sso.role_mapping.{$category}", []);
            if (in_array($externalRole, array_map('strtolower', $categoryRoles))) {
                return $internalRole;
            }
        }

        return null;
    }

    /**
     * Get default role for user
     */
    protected function getDefaultRole(SsoConfiguration $config, array $userData): ?string
    {
        // Check if role is specified in config
        if (isset($config->role_mapping['default_role'])) {
            return $config->role_mapping['default_role'];
        }

        // Use system default
        return config('sso.role_mapping.default_role', 'Graduate');
    }

    /**
     * Validate user data from SSO provider
     */
    protected function validateUserData(array $userData): void
    {
        $requiredAttributes = config('sso.provisioning.required_attributes', ['email', 'name']);

        foreach ($requiredAttributes as $attribute) {
            if (empty($userData[$attribute])) {
                throw new \Exception("Required attribute '{$attribute}' is missing from SSO response");
            }
        }

        // Validate email format
        if (isset($userData['email']) && ! filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid email format provided by SSO provider');
        }
    }

    /**
     * Handle SSO logout
     */
    public function logout(SsoConfiguration $config, ?string $sessionId = null): void
    {
        Log::info('SSO Logout initiated', [
            'provider' => $config->provider,
            'session_id' => $sessionId,
        ]);

        // Logout from Laravel
        Auth::logout();

        // Invalidate session
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        // Handle provider-specific logout if needed
        if ($config->isSaml() && config('sso.session.single_logout')) {
            $this->samlService->initiateSingleLogout($config, $sessionId);
        }
    }

    /**
     * Get available SSO configurations for institution
     */
    public function getAvailableConfigurations(?int $institutionId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = SsoConfiguration::active();

        if ($institutionId) {
            $query->where(function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId)
                    ->orWhereNull('institution_id'); // Global configurations
            });
        } else {
            $query->whereNull('institution_id'); // Only global configurations
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Test SSO configuration
     */
    public function testConfiguration(SsoConfiguration $config): array
    {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
        ];

        // Validate configuration
        $configErrors = $config->validateConfiguration();
        if (! empty($configErrors)) {
            $results['valid'] = false;
            $results['errors'] = array_merge($results['errors'], $configErrors);
        }

        // Test connectivity based on protocol
        try {
            if ($config->isSaml()) {
                $this->samlService->testConfiguration($config);
            } elseif ($config->isOAuth()) {
                $this->oauthService->testConfiguration($config);
            }
        } catch (\Exception $e) {
            $results['valid'] = false;
            $results['errors'][] = 'Connectivity test failed: '.$e->getMessage();
        }

        // Check role mappings
        if (empty($config->role_mapping)) {
            $results['warnings'][] = 'No role mappings configured - users will get default role';
        }

        // Check attribute mappings
        if (empty($config->attribute_mapping)) {
            $results['warnings'][] = 'No attribute mappings configured - using defaults';
        }

        return $results;
    }

    /**
     * Get SSO login URL for configuration
     */
    public function getLoginUrl(SsoConfiguration $config, ?string $returnUrl = null): string
    {
        $params = ['config' => $config->id];

        if ($returnUrl) {
            $params['return_url'] = $returnUrl;
        }

        return route('auth.sso.redirect', $params);
    }

    /**
     * Handle SSO callback
     */
    public function handleCallback(SsoConfiguration $config, array $callbackData): User
    {
        try {
            // Extract user data based on protocol
            if ($config->isSaml()) {
                $userData = $this->samlService->handleCallback($config, $callbackData);
            } elseif ($config->isOAuth()) {
                $userData = $this->oauthService->handleCallback($config, $callbackData);
            } else {
                throw new \Exception('Unsupported SSO protocol: '.$config->protocol);
            }

            // Authenticate user
            return $this->authenticate($config, $userData);

        } catch (\Exception $e) {
            Log::error('SSO Callback failed', [
                'provider' => $config->provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
