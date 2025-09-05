<?php

namespace Database\Seeders;

use App\Models\Template;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder for creating sample templates for all campaign types and audience types
 */
class TemplateSeeder extends Seeder
{
    /**
     * Define template configurations by campaign and audience type
     */
    private array $templateConfigurations = [
        'onboarding' => [
            'individual' => [
                'count' => 3,
                'categories' => ['landing', 'homepage', 'form'],
            ],
            'institution' => [
                'count' => 2,
                'categories' => ['landing', 'homepage'],
            ],
            'employer' => [
                'count' => 2,
                'categories' => ['landing', 'email'],
            ],
            'general' => [
                'count' => 1,
                'categories' => ['social'],
            ],
        ],
        'event_promotion' => [
            'individual' => [
                'count' => 2,
                'categories' => ['landing', 'email', 'social'],
            ],
            'institution' => [
                'count' => 2,
                'categories' => ['landing', 'homepage'],
            ],
            'employer' => [
                'count' => 1,
                'categories' => ['email'],
            ],
        ],
        'donation' => [
            'individual' => [
                'count' => 1,
                'categories' => ['landing'],
            ],
            'employer' => [
                'count' => 1,
                'categories' => ['landing'],
            ],
        ],
        'networking' => [
            'individual' => [
                'count' => 2,
                'categories' => ['homepage', 'form'],
            ],
            'general' => [
                'count' => 1,
                'categories' => ['landing'],
            ],
        ],
        'career_services' => [
            'individual' => [
                'count' => 3,
                'categories' => ['landing', 'homepage', 'form'],
            ],
            'employer' => [
                'count' => 2,
                'categories' => ['landing', 'email'],
            ],
        ],
        'recruiting' => [
            'individual' => [
                'count' => 1,
                'categories' => ['landing'],
            ],
            'employer' => [
                'count' => 3,
                'categories' => ['landing', 'homepage', 'email'],
            ],
            'institution' => [
                'count' => 1,
                'categories' => ['email'],
            ],
        ],
        'leadership' => [
            'individual' => [
                'count' => 2,
                'categories' => ['landing', 'email'],
            ],
            'institution' => [
                'count' => 1,
                'categories' => ['landing'],
            ],
        ],
        'marketing' => [
            'individual' => [
                'count' => 1,
                'categories' => ['homepage'],
            ],
            'institution' => [
                'count' => 2,
                'categories' => ['landing', 'social'],
            ],
            'employer' => [
                'count' => 1,
                'categories' => ['landing'],
            ],
            'general' => [
                'count' => 2,
                'categories' => ['social', 'email'],
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating sample templates...');

        // Get existing tenants or create sample ones
        $tenants = $this->getOrCreateTenants();

        $totalCreated = 0;

        foreach ($this->templateConfigurations as $campaignType => $audienceConfigs) {
            foreach ($audienceConfigs as $audienceType => $config) {
                foreach ($config['categories'] as $category) {
                    // Create multiple templates for each configuration
                    for ($i = 0; $i < $config['count']; $i++) {
                        $template = $this->createTemplateForConfig(
                            $campaignType,
                            $audienceType,
                            $category,
                            $tenants->random(),
                            $i + 1
                        );

                        $totalCreated++;
                        $this->command->info("Created: {$template->name} ({$campaignType} - {$audienceType} - {$category})");
                    }
                }
            }
        }

        $this->command->info("TemplateSeeder completed. Total templates created: {$totalCreated}");
    }

    /**
     * Get existing tenants or create sample ones
     */
    private function getOrCreateTenants()
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->info('No tenants found. Creating sample tenants...');

            // Create sample tenants
            $tenantConfigs = [
                'Alumni Network Pro',
                'TalentHub Solutions',
                'CareerBridge Platform',
                'SuccessPath Alumni',
                'Elite Alumni Alliance',
            ];

            foreach ($tenantConfigs as $config) {
                $tenants->push(\App\Models\Tenant::factory()->create([
                    'name' => $config,
                ]));
            }
        }

        return $tenants;
    }

    /**
     * Create a template for specific campaign, audience, and category
     */
    private function createTemplateForConfig(
        string $campaignType,
        string $audienceType,
        string $category,
        $tenant,
        int $sequence = 1
    ): Template {
        // Create template with specific state
        $factory = Template::factory()
            ->forTenant($tenant)
            ->forAudience($audienceType);

        // Apply campaign specific configuration
        switch ($campaignType) {
            case 'onboarding':
                $factory->onboarding();
                break;
            case 'recruiting':
                $factory->recruiting();
                break;
            case 'event_promotion':
                // Use standard factory for event promotion
                break;
            case 'donation':
                // Use standard factory for donation
                break;
            case 'networking':
                $factory->networking();
                break;
            case 'career_services':
                $factory->careerServices();
                break;
            case 'leadership':
                $factory->leadership();
                break;
            case 'marketing':
                $factory->marketing();
                break;
        }

        // Apply category specific configuration
        switch ($category) {
            case 'landing':
                // Use default landing configuration
                break;
            case 'homepage':
                $factory->homepage();
                break;
            case 'form':
                // Use default form configuration
                break;
            case 'email':
                $factory->email();
                break;
            case 'social':
                $factory->social();
                break;
        }

        // Add some variety for multiple templates of same type
        if ($sequence > 1) {
            if ($sequence % 2 === 0) {
                $factory->premium();
            }
            // Add usage stats for some templates
            if ($sequence % 3 === 0) {
                $factory->popular();
            }
        }

        // Perform additional customizations
        $template = $factory->create();

        // Add custom performance metrics based on campaign type
        $this->addCustomPerformanceMetrics($template, $campaignType, $audienceType, $category);

        return $template;
    }

    /**
     * Add custom performance metrics based on campaign type
     */
    private function addCustomPerformanceMetrics(
        Template $template,
        string $campaignType,
        string $audienceType,
        string $category
    ): void {
        $baseMetrics = [
            'conversion_rate' => 8.5,
            'avg_load_time' => 1.8,
            'bounce_rate' => 35,
            'completion_rate' => 68,
            'avg_session_duration' => 180,
            'mobile_conversion_rate' => 12.5,
            'desktop_conversion_rate' => 8.8,
        ];

        // Adjust metrics based on campaign type
        $campaignAdjustments = [
            'onboarding' => [
                'conversion_rate' => +2.5,
                'completion_rate' => +8,
                'bounce_rate' => -5,
            ],
            'recruiting' => [
                'conversion_rate' => +4.2,
                'completion_rate' => -5,
                'bounce_rate' => -8,
            ],
            'event_promotion' => [
                'conversion_rate' => -0.5,
                'completion_rate' => -10,
                'bounce_rate' => +5,
            ],
            'donation' => [
                'conversion_rate' => +12.5,
                'completion_rate' => +5,
                'bounce_rate' => -12,
            ],
            'networking' => [
                'conversion_rate' => +1.8,
                'completion_rate' => +3,
                'bounce_rate' => +2,
            ],
            'career_services' => [
                'conversion_rate' => +2.8,
                'completion_rate' => +12,
                'bounce_rate' => -8,
            ],
            'leadership' => [
                'conversion_rate' => +3.2,
                'completion_rate' => +15,
                'bounce_rate' => -10,
            ],
            'marketing' => [
                'conversion_rate' => +1.2,
                'completion_rate' => -2,
                'bounce_rate' => +3,
            ],
        ];

        // Adjust metrics based on audience type
        $audienceAdjustments = [
            'individual' => [
                'conversion_rate' => +1.5,
                'completion_rate' => +3,
                'bounce_rate' => -2,
            ],
            'employer' => [
                'conversion_rate' => -1.2,
                'completion_rate' => -5,
                'bounce_rate' => +4,
            ],
            'institution' => [
                'conversion_rate' => -2.1,
                'completion_rate' => -3,
                'bounce_rate' => +2,
            ],
            'general' => [
                'conversion_rate' => 0,
                'completion_rate' => -1,
                'bounce_rate' => +1,
            ],
        ];

        // Calculate final metrics
        $finalMetrics = $baseMetrics;

        // Apply campaign adjustments
        if (isset($campaignAdjustments[$campaignType])) {
            foreach ($campaignAdjustments[$campaignType] as $key => $adjustment) {
                $finalMetrics[$key] += $adjustment;
            }
        }

        // Apply audience adjustments
        if (isset($audienceAdjustments[$audienceType])) {
            foreach ($audienceAdjustments[$audienceType] as $key => $adjustment) {
                $finalMetrics[$key] += $adjustment;
            }
        }

        // Round metrics appropriately
        $roundedMetrics = [];
        foreach ($finalMetrics as $key => $value) {
            if (str_contains($key, 'rate') || str_contains($key, 'time')) {
                $roundedMetrics[$key] = round($value, 1);
            } else {
                $roundedMetrics[$key] = intval($value);
            }
        }

        // Add some timestamp when metrics were last updated
        $roundedMetrics['last_updated'] = now()->toISOString();

        // Update the template with custom metrics
        $template->update(['performance_metrics' => $roundedMetrics]);
    }

    /**
     * Create templates for a specific tenant
     */
    public function runForTenant(int $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->command->info("Creating templates for tenant: {$tenant->name}");

        $totalCreated = 0;

        // Create a smaller set for a specific tenant
        foreach (['onboarding', 'recruiting', 'networking'] as $campaignType) {
            foreach (['individual', 'employer'] as $audienceType) {
                foreach (['landing', 'homepage'] as $category) {
                    $template = $this->createTemplateForConfig(
                        $campaignType,
                        $audienceType,
                        $category,
                        $tenant,
                        1
                    );

                    $totalCreated++;
                }
            }
        }

        $this->command->info("Created {$totalCreated} templates for tenant: {$tenant->name}");
    }

    /**
     * Create premium templates only
     */
    public function runPremium(): void
    {
        $this->command->info('Creating premium templates...');

        $tenants = $this->getOrCreateTenants();
        $totalCreated = 0;

        foreach (['leadership', 'career_services', 'recruiting'] as $campaignType) {
            foreach (['individual', 'employer'] as $audienceType) {
                foreach (['landing', 'homepage'] as $category) {
                    $template = Template::factory()
                        ->forTenant($tenants->random())
                        ->forAudience($audienceType)
                        ->premium()
                        ->popular()
                        ->create();

                    $totalCreated++;
                }
            }
        }

        $this->command->info("Premium TemplateSeeder completed. Total premium templates created: {$totalCreated}");
    }

    /**
     * Create templates with high performance metrics
     */
    public function runHighPerforming(): void
    {
        $this->command->info('Creating high-performing templates...');

        $tenants = $this->getOrCreateTenants();
        $totalCreated = 0;

        $highPerformers = [
            ['campaign' => 'recruiting', 'audience' => 'employer', 'category' => 'landing'],
            ['campaign' => 'onboarding', 'audience' => 'individual', 'category' => 'landing'],
            ['campaign' => 'donation', 'audience' => 'individual', 'category' => 'landing'],
            ['campaign' => 'career_services', 'audience' => 'individual', 'category' => 'homepage'],
        ];

        foreach ($highPerformers as $config) {
            // Create multiple variants of high performers
            for ($i = 0; $i < 2; $i++) {
                $template = Template::factory()
                    ->forTenant($tenants->random())
                    ->forAudience($config['audience'])
                    ->popular()
                    ->create([
                        'campaign_type' => $config['campaign'],
                        'category' => $config['category'],
                        'performance_metrics' => [
                            'conversion_rate' => fake()->randomFloat(2, 15, 35),
                            'avg_load_time' => fake()->randomFloat(2, 0.8, 2.5),
                            'bounce_rate' => fake()->randomFloat(2, 10, 25),
                            'completion_rate' => fake()->randomFloat(2, 75, 95),
                            'avg_session_duration' => fake()->numberBetween(240, 420),
                            'mobile_conversion_rate' => fake()->randomFloat(2, 18, 45),
                            'desktop_conversion_rate' => fake()->randomFloat(2, 12, 30),
                            'last_updated' => now()->toISOString(),
                        ],
                    ]);

                $totalCreated++;
            }
        }

        $this->command->info("High-performing templates created: {$totalCreated}");
    }
}