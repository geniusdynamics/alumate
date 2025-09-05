<?php

namespace Database\Seeders;

use App\Models\LandingPage;
use App\Models\BrandConfig;
use App\Models\Template;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Master seeder that creates comprehensive template and landing page data
 * for testing multi-tenant scenarios with diverse campaign types
 */
class MasterTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('=== Master Template Seeder Starting ===');

        // 1. First, seed brand configurations
        $this->command->info('Step 1: Creating brand configurations...');
        $this->call([
            BrandSeeder::class,
        ]);

        // 2. Create brand guidelines for each tenant with brand configs
        $this->command->info('Step 2: Creating brand guidelines...');
        $this->createBrandGuidelines();

        // 3. Seed template data across all campaign types
        $this->command->info('Step 3: Creating templates...');
        $this->call([
            TemplateSeeder::class,
        ]);

        // 4. Create comprehensive landing pages with various configurations
        $this->command->info('Step 4: Creating landing pages...');
        $this->createLandingPages();

        // 5. Create demo content showing different campaign types
        $this->command->info('Step 5: Creating demo campaign examples...');
        $this->createDemoCampaigns();

        $this->command->info('=== Master Template Seeder Completed ===');

        // Summary
        $this->printSummary();
    }

    /**
     * Create brand guidelines for tenants that have brand configs
     */
    private function createBrandGuidelines(): void
    {
        $tenants = Tenant::has('brandConfigs')->get();

        foreach ($tenants as $tenant) {
            $brandConfig = $tenant->brandConfigs()->first();

            // Create guidelines with different approaches for different tenant types
            \App\Models\BrandGuidelines::factory()->create([
                'tenant_id' => $tenant->id,
                'brand_config_id' => $brandConfig->id,
                'approved_by' => 1, // System admin
                'approved_at' => now()->subDays(rand(1, 30)),
            ]);

            $this->command->info("  ✓ Created brand guidelines for {$tenant->name}");
        }
    }

    /**
     * Create comprehensive landing pages for different scenarios
     */
    private function createLandingPages(): void
    {
        $tenants = Tenant::all();
        $totalCreated = 0;

        foreach ($tenants as $tenant) {
            // Create several landing pages per tenant
            $pages = $this->createLandingPagesForTenant($tenant);
            $totalCreated += count($pages);

            $this->command->info("  ✓ Created " . count($pages) . " landing pages for {$tenant->name}");
        }

        $this->command->info("Total landing pages created: {$totalCreated}");
    }

    /**
     * Create landing pages for a specific tenant
     */
    private function createLandingPagesForTenant(Tenant $tenant): array
    {
        $pages = [];

        // Get tenant's templates and brand configs
        $templates = Template::forTenant($tenant->id)->active()->get();
        $brandConfigs = BrandConfig::forTenant($tenant->id)->active()->get();

        // Define page configurations
        $pageConfigs = [
            // Onboarding campaigns
            [
                'title' => 'Welcome to Our Community',
                'campaign_type' => 'onboarding',
                'audience_type' => 'individual',
                'content_type' => 'landing',
                'status' => 'published',
            ],
            [
                'title' => 'Get Started Today',
                'campaign_type' => 'onboarding',
                'audience_type' => 'employer',
                'content_type' => 'landing',
                'status' => 'published',
            ],

            // Event promotions
            [
                'title' => 'Networking Evening Event',
                'campaign_type' => 'event_promotion',
                'audience_type' => 'individual',
                'content_type' => 'landing',
                'status' => 'published',
            ],
            [
                'title' => 'Career Fair 2024',
                'campaign_type' => 'event_promotion',
                'audience_type' => 'individual',
                'content_type' => 'landing',
                'status' => 'published',
            ],

            // Career services
            [
                'title' => 'Resume Writing Workshop',
                'campaign_type' => 'career_services',
                'audience_type' => 'individual',
                'content_type' => 'landing',
                'status' => 'published',
            ],
            [
                'title' => 'Executive Mentoring Program',
                'campaign_type' => 'leadership',
                'audience_type' => 'individual',
                'content_type' => 'landing',
                'status' => 'published',
            ],

            // Recruiting campaigns
            [
                'title' => 'Join Our Innovative Team',
                'campaign_type' => 'recruiting',
                'audience_type' => 'individual',
                'content_type' => 'landing',
                'status' => 'published',
            ],
            [
                'title' => 'Top Talent Wanted',
                'campaign_type' => 'recruiting',
                'audience_type' => 'employer',
                'content_type' => 'landing',
                'status' => 'published',
            ],

            // Donors and networking
            [
                'title' => 'Make Your Impact Today',
                'campaign_type' => 'donation',
                'audience_type' => 'individual',
                'content_type' => 'landing',
                'status' => 'published',
            ],
            [
                'title' => 'Connect & Grow',
                'campaign_type' => 'networking',
                'audience_type' => 'individual',
                'content_type' => 'landing',
                'status' => 'published',
            ],

            // Draft pages for testing
            [
                'title' => 'Draft: New Marketing Campaign',
                'campaign_type' => 'marketing',
                'audience_type' => 'general',
                'content_type' => 'landing',
                'status' => 'draft',
            ],
            [
                'title' => 'Draft: Alumni Newsletter',
                'campaign_type' => 'marketing',
                'audience_type' => 'individual',
                'content_type' => 'email',
                'status' => 'draft',
            ],
        ];

        foreach ($pageConfigs as $config) {
            // Find appropriate template and brand config
            $template = $this->findBestTemplate($templates, $config);
            $brandConfig = $this->findBestBrandConfig($brandConfigs, $config);

            $pages[] = LandingPage::factory()->create([
                'tenant_id' => $tenant->id,
                'template_id' => $template?->id,
                'name' => $config['title'],
                'campaign_type' => $config['campaign_type'],
                'audience_type' => $config['audience_type'],
                'category' => $tenant->isEmployer() ? 'employer' : 'individual',
                'status' => $config['status'],
                'published_at' => $config['status'] === 'published' ? now()->subDays(rand(1, 365)) : null,
                'brand_config' => $brandConfig?->getEffectiveConfig(),
                'usage_count' => fake()->numberBetween(0, 1000),
                'conversion_count' => fake()->numberBetween(0, 100),
                'created_by' => rand(1, 5),
                'updated_by' => rand(1, 5),
            ]);
        }

        return $pages;
    }

    /**
     * Find the best template match for a page configuration
     */
    private function findBestTemplate($templates, array $config)
    {
        // First try exact match
        $template = $templates
            ->where('campaign_type', $config['campaign_type'])
            ->where('audience_type', $config['audience_type'])
            ->where('category', $config['content_type'])
            ->first();

        if ($template) {
            return $template;
        }

        // Then try campaign type and category match
        $template = $templates
            ->where('campaign_type', $config['campaign_type'])
            ->where('category', $config['content_type'])
            ->first();

        if ($template) {
            return $template;
        }

        // Finally, any template of the right category
        return $templates->where('category', $config['content_type'])->first();
    }

    /**
     * Find the best brand config for a page configuration
     */
    private function findBestBrandConfig($brandConfigs, array $config)
    {
        // For employer-targeted pages, try to get employer-specific config
        if ($config['audience_type'] === 'employer') {
            $config = $brandConfigs->first();
        }

        // Return first available or default
        return $brandConfigs->first();
    }

    /**
     * Create demo campaign examples
     */
    private function createDemoCampaigns(): void
    {
        $tenants = Tenant::has('brandConfigs')->get();

        foreach ($tenants as $tenant) {
            $this->createTenantCampaignDemo($tenant);
        }
    }

    /**
     * Create comprehensive campaign demo for a tenant
     */
    private function createTenantCampaignDemo(Tenant $tenant): void
    {
        $demoPage = LandingPage::factory()->create([
            'tenant_id' => $tenant->id,
            'template_id' => Template::forTenant($tenant->id)
                                ->where('campaign_type', 'onboarding')
                                ->where('audience_type', 'individual')
                                ->first()?->id,
            'name' => "Demo: {$tenant->name} - Complete Campaign Suite",
            'slug' => 'demo-campaign-suite-' . Str::slug($tenant->name),
            'description' => "Comprehensive demonstration of our landing page system with multiple campaign types and brand configurations.",
            'campaign_type' => 'onboarding',
            'audience_type' => 'individual',
            'category' => 'individual',
            'status' => 'published',
            'published_at' => now(),
            'brand_config' => $tenant->brandConfigs()->first()?->getEffectiveConfig(),
            'usage_count' => 250,
            'conversion_count' => 25,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $this->command->info("  ✓ Created demo campaign page for {$tenant->name}: {$demoPage->name}");
    }

    /**
     * Print comprehensive summary
     */
    private function printSummary(): void
    {
        $this->command->info('=== Final Summary ===');

        $stats = [
            'Tenants' => Tenant::count(),
            'Templates' => Template::count(),
            'LandingPages' => LandingPage::count(),
            'BrandConfigs' => BrandConfig::count(),
            'PublishedPages' => LandingPage::where('status', 'published')->count(),
            'DraftPages' => LandingPage::where('status', 'draft')->count(),
        ];

        foreach ($stats as $label => $count) {
            $this->command->info("  {$label}: {$count}");
        }

        $this->command->info('');
        $this->command->info('Campaign Types:');
        $campaignStats = Template::select('campaign_type', \DB::raw('count(*) as count'))
            ->groupBy('campaign_type')
            ->get();

        foreach ($campaignStats as $stat) {
            $this->command->info("  {$stat->campaign_type}: {$stat->count} templates");
        }

        $this->command->info('');
        $this->command->info('Audience Types:');
        $audienceStats = Template::select('audience_type', \DB::raw('count(*) as count'))
            ->groupBy('audience_type')
            ->get();

        foreach ($audienceStats as $stat) {
            $this->command->info("  {$stat->audience_type}: {$stat->count} templates");
        }
    }
}