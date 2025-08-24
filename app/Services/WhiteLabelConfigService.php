<?php

namespace App\Services;

use App\Models\Institution;

class WhiteLabelConfigService
{
    /**
     * Generate a complete white-label configuration for an institution
     */
    public function generateConfig(Institution $institution): array
    {
        return [
            'deployment' => $this->getDeploymentConfig($institution),
            'branding' => $this->getBrandingConfig($institution),
            'features' => $this->getFeatureConfig($institution),
            'customization' => $this->getCustomizationConfig($institution),
            'integrations' => $this->getIntegrationConfig($institution),
            'environment' => $this->getEnvironmentConfig($institution),
        ];
    }

    /**
     * Get deployment configuration
     */
    private function getDeploymentConfig(Institution $institution): array
    {
        return [
            'subdomain' => $institution->slug,
            'custom_domain' => $institution->domain,
            'ssl_enabled' => true,
            'cdn_enabled' => true,
            'app_name' => $institution->name,
            'app_url' => $institution->domain ? "https://{$institution->domain}" : "https://{$institution->slug}.alumate.com",
            'database_prefix' => $institution->slug.'_',
            'cache_prefix' => $institution->slug.':',
        ];
    }

    /**
     * Get branding configuration
     */
    private function getBrandingConfig(Institution $institution): array
    {
        $settings = $institution->settings ?? [];
        $branding = $settings['branding'] ?? [];

        return [
            'name' => $institution->name,
            'logo_url' => $institution->logo_url,
            'logo_path' => $institution->logo_path,
            'banner_url' => $institution->banner_url,
            'primary_color' => $institution->primary_color ?? '#007bff',
            'secondary_color' => $institution->secondary_color ?? '#6c757d',
            'font_family' => $branding['font_family'] ?? 'inter',
            'theme_style' => $branding['theme_style'] ?? 'modern',
            'custom_css' => $branding['custom_css'] ?? '',
            'favicon_url' => $branding['favicon_url'] ?? null,
            'meta_description' => $institution->description,
            'social_image' => $institution->banner_url,
        ];
    }

    /**
     * Get feature configuration
     */
    private function getFeatureConfig(Institution $institution): array
    {
        $features = $institution->feature_flags ?? [];

        return [
            'enabled_features' => array_keys(array_filter($features)),
            'disabled_features' => array_keys(array_filter($features, fn ($enabled) => ! $enabled)),
            'feature_flags' => $features,
            'subscription_plan' => $institution->subscription_plan,
            'feature_limits' => $this->getFeatureLimits($institution),
        ];
    }

    /**
     * Get customization configuration
     */
    private function getCustomizationConfig(Institution $institution): array
    {
        $settings = $institution->settings ?? [];

        return [
            'custom_fields' => $settings['custom_fields'] ?? [],
            'workflows' => $settings['workflows'] ?? [],
            'reporting' => $settings['reporting'] ?? [],
            'email_templates' => $settings['email_templates'] ?? [],
            'notification_settings' => $settings['notifications'] ?? [],
        ];
    }

    /**
     * Get integration configuration
     */
    private function getIntegrationConfig(Institution $institution): array
    {
        $integrations = $institution->integration_settings ?? [];

        // Remove sensitive data like API keys for security
        $sanitizedIntegrations = [];
        foreach ($integrations as $integration) {
            $sanitizedIntegrations[] = [
                'name' => $integration['name'],
                'enabled' => $integration['enabled'],
                'provider' => $integration['config']['provider'] ?? null,
                // Don't include sensitive config data
            ];
        }

        return [
            'integrations' => $sanitizedIntegrations,
            'webhook_endpoints' => $this->getWebhookEndpoints($institution),
            'api_endpoints' => $this->getApiEndpoints($institution),
        ];
    }

    /**
     * Get environment configuration
     */
    private function getEnvironmentConfig(Institution $institution): array
    {
        return [
            'timezone' => $institution->settings['timezone'] ?? 'UTC',
            'locale' => $institution->settings['locale'] ?? 'en',
            'currency' => $institution->settings['currency'] ?? 'USD',
            'date_format' => $institution->settings['date_format'] ?? 'Y-m-d',
            'time_format' => $institution->settings['time_format'] ?? 'H:i',
        ];
    }

    /**
     * Get feature limits based on subscription plan
     */
    private function getFeatureLimits(Institution $institution): array
    {
        $plan = $institution->subscription_plan ?? 'basic';

        $limits = [
            'basic' => [
                'max_users' => 500,
                'max_events_per_month' => 10,
                'max_custom_fields' => 5,
                'max_workflows' => 3,
                'storage_limit_gb' => 5,
            ],
            'professional' => [
                'max_users' => 2000,
                'max_events_per_month' => 50,
                'max_custom_fields' => 20,
                'max_workflows' => 10,
                'storage_limit_gb' => 25,
            ],
            'enterprise' => [
                'max_users' => -1, // unlimited
                'max_events_per_month' => -1,
                'max_custom_fields' => -1,
                'max_workflows' => -1,
                'storage_limit_gb' => 100,
            ],
        ];

        return $limits[$plan] ?? $limits['basic'];
    }

    /**
     * Get webhook endpoints for the institution
     */
    private function getWebhookEndpoints(Institution $institution): array
    {
        $baseUrl = $institution->domain ? "https://{$institution->domain}" : "https://{$institution->slug}.alumate.com";

        return [
            'user_registered' => "{$baseUrl}/webhooks/user-registered",
            'profile_updated' => "{$baseUrl}/webhooks/profile-updated",
            'event_created' => "{$baseUrl}/webhooks/event-created",
            'donation_received' => "{$baseUrl}/webhooks/donation-received",
            'job_application' => "{$baseUrl}/webhooks/job-application",
        ];
    }

    /**
     * Get API endpoints for the institution
     */
    private function getApiEndpoints(Institution $institution): array
    {
        $baseUrl = $institution->domain ? "https://{$institution->domain}" : "https://{$institution->slug}.alumate.com";

        return [
            'base_url' => "{$baseUrl}/api",
            'auth_endpoint' => "{$baseUrl}/api/auth",
            'users_endpoint' => "{$baseUrl}/api/users",
            'events_endpoint' => "{$baseUrl}/api/events",
            'analytics_endpoint' => "{$baseUrl}/api/analytics",
        ];
    }
}
