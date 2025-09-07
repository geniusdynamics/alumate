<?php

namespace Database\Seeders;

use App\Models\LandingPage;
use App\Models\Tenant;
use App\Models\Template;
use Illuminate\Database\Seeder;

/**
 * Seeder for creating comprehensive landing pages across all templates and tenants
 */
class LandingPageSeeder extends Seeder
{
    /**
     * Landing page configurations for different scenarios
     */
    private array $landingPageConfigurations = [
        // Published and active pages
        'published_active' => [
            'status' => 'published',
            'count_per_tenant' => 5,
            'published_chance' => 100,
            'templates_to_use' => 'random', // Use random templates for published pages
        ],
        // Draft pages
        'drafts' => [
            'status' => 'draft',
            'count_per_tenant' => 3,
            'published_chance' => 0,
            'templates_to_use' => 'random',
        ],
        // Review pages
        'review' => [
            'status' => 'reviewing',
            'count_per_tenant' => 2,
            'published_chance' => 0,
            'templates_to_use' => 'random',
        ],
        // High-performing pages for each template
        'high_performant' => [
            'status' => 'published',
            'count_per_tenant' => 1,
            'published_chance' => 100,
            'templates_to_use' => 'popular_templates', // Use most popular templates
            'high_performance' => true,
        ],
        // A/B testing pages
        'ab_testing' => [
            'status' => 'published',
            'count_per_tenant' => 1,
            'published_chance' => 100,
            'templates_to_use' => 'random',
            'ab_testing' => true,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating comprehensive landing pages across all tenants...');

        // Get existing tenants or create sample ones
        $tenants = $this->getOrCreateTenants();
        $templates = $this->getAvailableTemplates();

        if ($templates->isEmpty()) {
            $this->command->error('No templates found. Please run TemplateSeeder first.');
            return;
        }

        $totalCreated = [
            'pages' => 0,
            'published' => 0,
            'drafts' => 0,
            'reviews' => 0,
            'high_performers' => 0,
        ];

        foreach ($this->landingPageConfigurations as $configKey => $config) {
            foreach ($tenants as $tenant) {
                for ($i = 0; $i < $config['count_per_tenant']; $i++) {
                    $template = $this->selectTemplate($templates, $config, $tenant);

                    if (!$template) {
                        continue;
                    }

                    $page = $this->createLandingPageForConfig(
                        $configKey,
                        $config,
                        $template,
                        $tenant,
                        $i + 1
                    );

                    $totalCreated['pages']++;

                    switch ($config['status']) {
                        case 'published':
                            $totalCreated['published']++;
                            break;
                        case 'draft':
                            $totalCreated['drafts']++;
                            break;
                        case 'reviewing':
                            $totalCreated['reviews']++;
                            break;
                    }

                    if (isset($config['high_performance']) && $config['high_performance']) {
                        $totalCreated['high_performers']++;
                    }

                    $this->command->info("Created: {$page->name} ({$configKey})");
                }
            }
        }

        $this->command->info('LandingPageSeeder completed:');
        $this->command->info("  - Total pages: {$totalCreated['pages']}");
        $this->command->info("  - Published: {$totalCreated['published']}");
        $this->command->info("  - Drafts: {$totalCreated['drafts']}");
        $this->command->info("  - In review: {$totalCreated['reviews']}");
        $this->command->info("  - High performers: {$totalCreated['high_performers']}");
    }

    /**
     * Get existing tenants or create sample ones
     */
    private function getOrCreateTenants()
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->info('No tenants found. Creating sample tenants...');

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
     * Get all available templates
     */
    private function getAvailableTemplates()
    {
        return Template::with('tenant')->get();
    }

    /**
     * Select appropriate template based on configuration
     */
    private function selectTemplate($templates, array $config, $tenant)
    {
        if ($config['templates_to_use'] === 'popular_templates') {
            // Select templates with high usage counts
            $popularTemplates = $templates->filter(function ($template) use ($tenant) {
                return $template->tenant_id === $tenant->id &&
                       $template->usage_count > 100;
            });

            if ($popularTemplates->isNotEmpty()) {
                return $popularTemplates->random();
            }
        }

        // Default: select random template from the same tenant
        return $templates->filter(function ($template) use ($tenant) {
            return $template->tenant_id === $tenant->id;
        })->random() ?? $templates->random();
    }

    /**
     * Create a landing page for specific configuration
     */
    private function createLandingPageForConfig(
        string $configKey,
        array $config,
        $template,
        $tenant,
        int $sequence = 1
    ): LandingPage {
        // Basic page configuration
        $factory = LandingPage::factory()
            ->forTenant($template->tenant)
            ->withTemplate($template);

        // Apply configuration-specific state
        switch ($configKey) {
            case 'published_active':
                $factory->published();
                break;
            case 'drafts':
                $factory->draft();
                break;
            case 'review':
                $factory->reviewed();
                break;
            case 'high_performant':
                $factory->published()->highConversion();
                break;
        }

        // Generate a unique name based on config and sequence
        $pageName = $this->generatePageName($configKey, $template, $sequence);

        // Create with custom configuration
        $page = $factory->create([
            'name' => $pageName,
            'audience_type' => $template->audience_type,
            'campaign_type' => $template->campaign_type,
            'category' => $template->audience_type,
            'seo_title' => substr($pageName, 0, 60),
            'seo_description' => $this->generateSEODescription($configKey, $template),
            'seo_keywords' => $this->generateSEOKeywords($configKey, $template),
        ]);

        // Add some variety and performance metrics
        $this->enhanceLandingPage($page, $configKey, $sequence);

        return $page;
    }

    /**
     * Generate appropriate page name based on configuration and template
     */
    private function generatePageName(string $configKey, $template, int $sequence): string
    {
        $baseName = match ($configKey) {
            'published_active' => $this->getPublishedPageName($template, $sequence),
            'drafts' => $this->getDraftPageName($template, $sequence),
            'review' => $this->getReviewPageName($template, $sequence),
            'high_performant' => $this->getHighPerformerPageName($template),
            'ab_testing' => $this->getABBTestingPageName($template, $sequence),
            default => $template->name . ' Page ' . $sequence,
        };

        return $baseName;
    }

    /**
     * Generate names for published pages
     */
    private function getPublishedPageName($template, int $sequence): string
    {
        $campaignNames = [
            'onboarding' => [
                'individual' => ['Welcome to Our Alumni Community', 'Start Your Journey Today', 'Join Our Network'],
                'institution' => ['Discover Your Path', 'Begin Your Academic Journey', 'University Community Hub'],
                'employer' => ['Connect with Talent', 'Find Qualified Candidates', 'Your Recruitment Partner'],
            ],
            'recruiting' => [
                'individual' => ['Career Opportunities Await', 'Your Next Career Move', 'Professional Growth Hub'],
                'employer' => ['Top Talent Recruitment', 'Executive Search & Placement', 'Recruitment Solutions'],
            ],
            'event_promotion' => ['Join Our Upcoming Event', 'Network & Learn Together', 'Professional Development Event'],
            'donation' => ['Support Our Mission', 'Make a Difference Today', 'Your Contribution Matters'],
            'networking' => ['Expand Your Professional Network', 'Connect with Industry Leaders', 'Community Hub'],
        ];

        $campaign = $template->campaign_type;
        $audience = $template->audience_type;

        if (isset($campaignNames[$campaign][$audience])) {
            return collect($campaignNames[$campaign][$audience])->random();
        }

        return $template->name . ' - Active Page ' . $sequence;
    }

    /**
     * Generate names for draft pages
     */
    private function getDraftPageName($template, int $sequence): string
    {
        $draftNames = [
            'New Alumni Welcome Page',
            'Updated Career Services Hub',
            'Revised Event Registration',
            'Improved Donation Campaign',
            'Enhanced Networking Platform',
            'Updated Job Board',
            'New Leadership Program',
            'Revised Marketing Page',
        ];

        return collect($draftNames)->random() . ' (Draft ' . $sequence . ')';
    }

    /**
     * Generate names for review pages
     */
    private function getReviewPageName($template, int $sequence): string
    {
        return $template->name . ' (Under Review - Version ' . $sequence . ')';
    }

    /**
     * Generate names for high-performing pages
     */
    private function getHighPerformerPageName($template): string
    {
        $highPerformerNames = [
            'Proven Alumni Success Hub',
            'Top-Rated Job Board',
            'Award-Winning Recruitment Platform',
            'Best-Selling Career Services',
            'Highest-Converting Donation Page',
            'Most Popular Networking Site',
            'Leading Event Registration Platform',
            'Premium Leadership Program Hub',
            'Elite Marketing Performance',
        ];

        return collect($highPerformerNames)->random();
    }

    /**
     * Generate names for A/B testing pages
     */
    private function getABBTestingPageName($template, int $sequence): string
    {
        $variants = ['A', 'B'];
        $variant = $variants[($sequence - 1) % 2];

        return $template->name . ' (Variant ' . $variant . ')';
    }

    /**
     * Generate SEO description
     */
    private function generateSEODescription(string $configKey, $template): string
    {
        $baseDescriptions = [
            'onboarding' => 'Join our comprehensive alumni community and access exclusive resources, networking opportunities, and professional development tools designed for your success.',
            'recruiting' => 'Discover top career opportunities and connect with leading employers. Access our extensive job board and career development resources.',
            'event_promotion' => 'Register for our upcoming events and network with industry professionals. Join thousands of alumni for unique learning and connection opportunities.',
            'donation' => 'Your contribution makes a real difference. Support our mission to connect and develop alumni professionals across the globe.',
            'networking' => 'Build meaningful professional connections in our exclusive alumni network. Access mentorship, job opportunities, and industry insights.',
            'career_services' => 'Accelerate your career with our comprehensive suite of professional development services, resume building, and job search tools.',
            'leadership' => 'Develop your leadership skills with our exclusive programs designed for emerging leaders and executive professionals.',
            'marketing' => 'Discover our comprehensive platform and learn how we can support your professional and academic development journey.',
        ];

        $audienceTypes = [
            'individual' => 'current and aspiring professionals',
            'institution' => 'academic institutions and partners',
            'employer' => 'organizations and hiring managers',
        ];

        $description = $baseDescriptions[$template->campaign_type] ?? 'Join our community and discover all the opportunities available to advance your career.';

        if (isset($audienceTypes[$template->audience_type])) {
            $description .= ' Tailored specifically for ' . $audienceTypes[$template->audience_type] . '.';
        }

        return substr($description, 0, 160);
    }

    /**
     * Generate SEO keywords
     */
    private function generateSEOKeywords(string $configKey, $template): array
    {
        $baseKeywords = [
            $template->campaign_type,
            $template->audience_type,
            'alumni',
            'professional',
            'career',
            'networking',
            'development',
        ];

        $additionalKeywords = [
            'onboarding' => ['welcome', 'introduction', 'getting started', 'resources'],
            'recruiting' => ['jobs', 'hiring', 'employment', 'opportunities', 'careers'],
            'event_promotion' => ['events', 'registration', 'conference', 'networking events'],
            'donation' => ['support', 'contribution', 'campaign', 'giving', 'philanthropy'],
            'networking' => ['connections', 'mentorship', 'community', 'professional network'],
            'career_services' => ['job search', 'resume', 'interview', 'career coaching'],
            'leadership' => ['leadership development', 'management', 'executive', 'skills'],
            'marketing' => ['platform', 'services', 'solutions', 'professional tools'],
        ];

        if (isset($additionalKeywords[$template->campaign_type])) {
            $baseKeywords = array_merge($baseKeywords, $additionalKeywords[$template->campaign_type]);
        }

        return array_slice($baseKeywords, 0, 10); // Limit to 10 keywords
    }

    /**
     * Enhance landing page with additional configuration
     */
    private function enhanceLandingPage(LandingPage $page, string $configKey, int $sequence): void
    {
        // Add performance data for high performers
        if ($configKey === 'high_performant') {
            $page->update([
                'usage_count' => rand(5000, 15000),
                'conversion_count' => rand(500, 2000),
            ]);
        }

        // Add variety to other pages
        else {
            $page->update([
                'usage_count' => rand(50, 1000),
                'conversion_count' => rand(5, 200),
            ]);
        }

        // Update draft hash to reflect changes
        $page->updateDraftHash();
    }

    /**
     * Create landing pages for a specific tenant
     */
    public function runForTenant(int $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->command->info("Creating landing pages for tenant: {$tenant->name}");

        $templates = Template::where('tenant_id', $tenantId)->get();

        if ($templates->isEmpty()) {
            $this->command->error("No templates found for tenant ID: {$tenantId}");
            return;
        }

        $totalCreated = 0;

        // Create a smaller set for a specific tenant
        foreach (['published_active', 'drafts'] as $configKey) {
            $config = $this->landingPageConfigurations[$configKey];

            for ($i = 0; $i < 3; $i++) { // Only 3 pages per type per tenant
                $template = $templates->random();

                $page = $this->createLandingPageForConfig(
                    $configKey,
                    $config,
                    $template,
                    $tenant,
                    $i + 1
                );

                $totalCreated++;
            }
        }

        $this->command->info("Created {$totalCreated} landing pages for tenant: {$tenant->name}");
    }

    /**
     * Create high-performing landing pages only
     */
    public function runHighPerforming(): void
    {
        $this->command->info('Creating high-performing landing pages...');

        $tenants = $this->getOrCreateTenants();
        $templates = $this->getAvailableTemplates();
        $totalCreated = 0;

        foreach ($tenants as $tenant) {
            $tenantTemplates = $templates->filter(fn($t) => $t->tenant_id === $tenant->id);

            if ($tenantTemplates->isEmpty()) {
                continue;
            }

            $config = $this->landingPageConfigurations['high_performant'];

            $page = $this->createLandingPageForConfig(
                'high_performant',
                $config,
                $tenantTemplates->random(),
                $tenant,
                1
            );

            $totalCreated++;
        }

        $this->command->info("High-performing landing pages created: {$totalCreated}");
    }

    /**
     * Create A/B testing variants
     */
    public function runABBTesting(): void
    {
        $this->command->info('Creating A/B testing landing page variants...');

        $tenants = $this->getOrCreateTenants();
        $templates = $this->getAvailableTemplates();
        $totalCreated = 0;

        foreach ($tenants as $tenant) {
            $tenantTemplates = $templates->filter(fn($t) => $t->tenant_id === $tenant->id);

            if ($tenantTemplates->isEmpty()) {
                continue;
            }

            $config = $this->landingPageConfigurations['ab_testing'];

            // Create 2 variants (A and B) for each template type
            foreach (['onboarding', 'recruiting'] as $campaignType) {
                $campaignTemplates = $tenantTemplates->filter(fn($t) => $t->campaign_type === $campaignType);

                if ($campaignTemplates->isEmpty()) {
                    continue;
                }

                for ($i = 0; $i < 2; $i++) {
                    $page = $this->createLandingPageForConfig(
                        'ab_testing',
                        $config,
                        $campaignTemplates->random(),
                        $tenant,
                        $i + 1
                    );

                    $totalCreated++;
                }
            }
        }

        $this->command->info("A/B testing variants created: {$totalCreated}");
    }
}