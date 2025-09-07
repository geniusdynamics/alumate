<?php

namespace Tests\Browser;

use App\Models\Institution;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TemplateWorkflowBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $testUser;
    protected Institution $institution;
    protected Template $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create([
            'name' => 'Test University',
            'domain' => 'test-university.com',
        ]);

        $this->testUser = User::factory()->create([
            'name' => 'John Admin',
            'email' => 'admin@test-university.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'tenant_id' => $this->institution->id,
        ]);

        $this->template = Template::factory()->create([
            'name' => 'Admissions Landing Page',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'onboarding',
            'tenant_id' => $this->institution->id,
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Welcome to Test University',
                            'subtitle' => 'Your Journey Starts Here',
                            'cta_text' => 'Apply Now',
                        ]
                    ],
                    [
                        'type' => 'form',
                        'config' => [
                            'fields' => [
                                ['type' => 'text', 'name' => 'first_name', 'label' => 'First Name', 'required' => true],
                                ['type' => 'text', 'name' => 'last_name', 'label' => 'Last Name', 'required' => true],
                                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
                            ]
                        ]
                    ]
                ]
            ],
            'default_config' => [
                'theme' => [
                    'primary_color' => '#004080',
                ]
            ],
            'created_by' => $this->testUser->id,
        ]);
    }

    public function test_complete_template_management_workflow()
    {
        $this->browse(function (Browser $browser) {
            // Step 1: User login
            $browser->visit(config('app.url') . '/login')
                    ->waitFor('.login-container')
                    ->type('#email', 'admin@test-university.com')
                    ->type('#password', 'password123')
                    ->click('.login-submit-button')
                    ->assertPathIs('/dashboard')
                    ->assertSee('Welcome to your dashboard')
                    ->assertSee('John Admin');

            // Step 2: Navigate to template management
            $browser->clickLink('Templates')
                    ->waitFor('.templates-dashboard')
                    ->assertPathContains('/templates')
                    ->assertSee('Template Management')
                    ->assertSee('Create New Template');

            // Step 3: Create new template from scratch
            $browser->click('.create-template-button')
                    ->waitFor('.template-creation-modal')
                    ->assertSee('Create New Template');

            // Fill basic template information
            $browser->select('.template-category-select', 'landing')
                    ->select('.template-audience-select', 'individual')
                    ->select('.template-campaign-select', 'onboarding')
                    ->type('#template-name', 'Graduate Program Template')
                    ->type('#template-description', 'Professional template for graduate program admissions')
                    ->click('.next-step-button');

            // Step 4: Design template structure
            $browser->waitFor('.template-designer')
                    ->assertSee('Template Designer')
                    ->assertSee('Hero Section')
                    ->assertSee('Form Section');

            // Add hero section
            $browser->click('.add-hero-section')
                    ->type('.hero-title-input', 'Advance Your Future')
                    ->type('.hero-subtitle-input', 'Join our world-class graduate programs')
                    ->type('.hero-cta-input', 'Start Your Application')
                    ->type('.hero-bg-color', '#004080');

            // Add statistics section
            $browser->click('.add-statistics-section')
                    ->type('.stat-1-label', 'Scholarships Available')
                    ->type('.stat-1-value', '$2.5M')
                    ->type('.stat-2-label', 'Graduate Students')
                    ->type('.stat-2-value', '1,200+')
                    ->type('.stat-3-label', 'Research Centers')
                    ->type('.stat-3-value', '15')
                    ->type('.stat-4-label', 'Employment Rate')
                    ->type('.stat-4-value', '95%');

            // Add testimonials section
            $browser->click('.add-testimonials-section')
                    ->type('.testimonial-1-quote', 'The graduate program transformed my research capabilities and career trajectory.')
                    ->type('.testimonial-1-author', 'Dr. Sarah Chen')
                    ->type('.testimonial-1-title', 'PhD Graduate 2022, Research Scientist at Google');

            // Step 5: Configure theme and branding
            $browser->click('.configure-theme-tab')
                    ->waitFor('.theme-configurator')
                    ->type('.primary-color-input', '#004080')
                    ->type('.secondary-color-input', '#0066cc')
                    ->type('.accent-color-input', '#00aaff')
                    ->select('.heading-font-select', 'Arial, sans-serif')
                    ->select('.body-font-select', 'Georgia, serif')
                    ->check('.responsive-design-checkbox');

            // Step 6: Preview template
            $browser->click('.preview-template-button')
                    ->waitFor('.template-preview-modal')
                    ->assertSee('Graduate Program Template')
                    ->assertSee('Advance Your Future')
                    ->assertSee('Dr. Sarah Chen')
                    ->within('.template-preview-modal', function ($modal) {
                        $this->assertSee('Scholarships Available');
                        $this->assertSee('$2.5M');
                    })
                    ->click('.close-preview-modal');

            // Step 7: Save template
            $browser->click('.save-template-button')
                    ->waitFor('.template-saved-confirmation')
                    ->assertSee('Template saved successfully!')
                    ->click('.view-templates-list');

            // Step 8: Verify template appears in list
            $browser->waitFor('.templates-list')
                    ->assertSee('Graduate Program Template')
                    ->assertSee('landing')
                    ->assertSee('individual')
                    ->assertSee('onboarding');

            // Step 9: Edit existing template (the one created in setUp)
            $browser->click('.template-edit-button:first-child')
                    ->waitFor('.template-editor')
                    ->assertSee('Admissions Landing Page')
                    ->type('#template-name', 'Admissions Landing Page - Updated')
                    ->type('#template-description', 'Updated template for undergraduate admissions with enhanced features');

            // Update hero configuration
            $browser->click('.edit-hero-section')
                    ->type('.hero-title-input', 'Welcome to Test University - Updated')
                    ->type('.hero-subtitle-input', 'Begin your academic journey with us')
                    ->click('.save-changes-button')
                    ->assertSee('Template updated successfully');

            // Step 10: Clone template
            $browser->click('.template-clone-button:first-child')
                    ->waitFor('.clone-template-modal')
                    ->type('#cloned-template-name', 'Graduate Program Template - Clone')
                    ->click('.confirm-clone-button')
                    ->assertSee('Template cloned successfully');

            // Step 11: Manage template versions
            $browser->click('.template-versions-button:first-child')
                    ->waitFor('.template-versions-panel')
                    ->click('.create-new-version-button')
                    ->type('#version-name', 'Version 2.0 - Enhanced Features')
                    ->type('#version-changes', 'Added testimonials section, enhanced mobile responsiveness')
                    ->click('.save-version-button')
                    ->assertSee('Version created successfully');

            // Step 12: Set template as default
            $browser->click('.template-make-default-button:first-child')
                    ->waitFor('.default-template-confirmation')
                    ->click('.confirm-default-button')
                    ->assertSee('Template set as default');

            // Step 13: Test template search and filtering
            $browser->type('.template-search-input', 'Graduate')
                    ->click('.search-button')
                    ->waitFor('.search-results')
                    ->assertSee('Graduate Program Template')
                    ->assertSee('Graduate Program Template - Clone')
                    ->assertDontSee('Admissions Landing Page');

            // Filter by category
            $browser->select('.category-filter', 'landing')
                    ->assertSee('Graduate Program Template')
                    ->assertSee('Admissions Landing Page');

            // Step 14: Create landing page from template
            $browser->click('.create-landing-page-button')
                    ->waitFor('.new-landing-page-modal')
                    ->select('.template-selector', 'Admissions Landing Page')
                    ->type('#landing-page-name', '2024 Spring Admissions')
                    ->type('#landing-page-description', 'Spring semester undergraduate admissions campaign')
                    ->type('#campaign-url', 'spring-2024-admissions')
                    ->click('.create-page-button')
                    ->assertSee('Landing page created successfully');

            // Step 15: Customize landing page
            $browser->waitFor('.landing-page-editor')
                    ->assertSee('2024 Spring Admissions')
                    ->type('.page-title-input', 'Spring 2024 Undergraduate Admissions - Apply Today!')
                    ->click('.customize-branding-tab')
                    ->type('.primary-brand-color', '#007acc')
                    ->upload('.logo-upload', resource_path('testing/test-logo.png'))
                    ->type('.brand-message', 'Join our vibrant campus community')
                    ->click('.save-customization-button')
                    ->assertSee('Landing page customized successfully');

            // Step 16: Add custom sections to landing page
            $browser->click('.add-section-button')
                    ->select('.section-type-selector', 'contact')
                    ->type('.contact-title', 'Get in Touch')
                    ->type('.contact-email', 'admissions@test-university.com')
                    ->type('.contact-phone', '(555) 123-4567')
                    ->click('.save-section-button');

            // Add call-to-action section
            $browser->click('.add-section-button')
                    ->select('.section-type-selector', 'cta')
                    ->type('.cta-title', 'Ready to Apply?')
                    ->type('.cta-description', 'Take the next step in your academic journey')
                    ->type('.cta-button-text', 'Start Application')
                    ->type('.cta-button-url', '/apply')
                    ->click('.save-section-button');

            // Step 17: Preview landing page
            $browser->click('.preview-landing-page-button')
                    ->waitFor('.landing-page-preview')
                    ->assertSee('Spring 2024 Undergraduate Admissions - Apply Today!')
                    ->assertSee('Get in Touch')
                    ->assertSee('Ready to Apply?')
                    ->assertSee('admissions@test-university.com')
                    ->click('.close-preview-button');

            // Step 18: Publish landing page
            $browser->click('.publish-page-button')
                    ->waitFor('.publish-confirmation-modal')
                    ->check('.confirm-publish-checkbox')
                    ->click('.confirm-publish-button')
                    ->assertSee('Landing page published successfully!')
                    ->assertSee('🚀 Your landing page is now live');

            // Step 19: Verify SEO settings
            $browser->click('.seo-settings-tab')
                    ->type('.meta-title-input', 'Spring 2024 Undergraduate Admissions | Test University')
                    ->type('.meta-description-input', 'Apply for Spring 2024 undergraduate admissions at Test University. Begin your academic journey today.')
                    ->click('.save-seo-button')
                    ->assertSee('SEO settings updated');

            // Step 20: Set up tracking and analytics
            $browser->click('.tracking-tab')
                    ->type('.google-analytics-id', 'UA-12345678-1')
                    ->type('.facebook-pixel-id', '123456789012345')
                    ->check('.track-conversions-checkbox')
                    ->check('.track-visitor-data-checkbox')
                    ->click('.save-tracking-button')
                    ->assertSee('Tracking settings saved');

            // Step 21: Test live landing page
            $browser->click('.view-live-page-button')
                    ->assertUrlIs('/spring-2024-admissions')
                    ->assertSee('Spring 2024 Undergraduate Admissions')
                    ->assertSee('Join our vibrant campus community');

            // Step 22: Access analytics dashboard
            $browser->visit('/templates/analytics')
                    ->waitFor('.template-analytics')
                    ->assertSee('Template Performance')
                    ->assertSee('Admissions Landing Page')
                    ->assertSee('2024 Spring Admissions');

            // Step 23: Archive old template version
            $browser->visit('/templates')
                    ->click('.template-actions-dropdown:first-child')
                    ->click('.archive-template-button')
                    ->waitFor('.archive-confirmation')
                    ->click('.confirm-archive-button')
                    ->assertSee('Template archived successfully');

            // Step 24: Export template
            $browser->click('.export-template-button:first-child')
                    ->waitFor('.export-options-modal')
                    ->check('.export-structure-checkbox')
                    ->check('.export-config-checkbox')
                    ->check('.export-branding-checkbox')
                    ->click('.export-button')
                    ->assertSee('Template exported successfully');

            // Step 25: Final verification
            $browser->visit('/dashboard')
                    ->assertSee('Template Management')
                    ->assertSee('Active Templates: 2')
                    ->assertSee('Published Pages: 1')
                    ->assertSee('Total Views: 1');

            // User journey complete: from template creation to published landing page
        });
    }

    public function test_template_workflow_edge_cases()
    {
        $this->browse(function (Browser $browser) {
            // Login as admin
            $browser->visit(config('app.url') . '/login')
                    ->type('#email', 'admin@test-university.com')
                    ->type('#password', 'password123')
                    ->click('.login-submit-button')
                    ->assertPathIs('/dashboard');

            // Test duplicate template name handling
            $browser->visit('/templates/create')
                    ->waitFor('.template-creation-modal')
                    ->type('#template-name', 'Admissions Landing Page') // Duplicate name
                    ->select('.template-category-select', 'landing')
                    ->click('.next-step-button')
                    ->assertSee('Template name already exists');

            // Test validation for required fields
            $browser->visit('/templates/create')
                    ->waitFor('.template-creation-modal')
                    ->click('.next-step-button')
                    ->assertSee('Template name is required')
                    ->assertSee('Category is required');

            // Test large template creation
            $browser->visit('/templates/create')
                    ->waitFor('.template-creation-modal')
                    ->type('#template-name', 'Comprehensive University Template')
                    ->select('.template-category-select', 'landing');

            // Add multiple sections
            for ($i = 1; $i <= 10; $i++) {
                $browser->click('.add-hero-section')
                        ->type(".hero-title-input:last-child", "Hero Section {$i}")
                        ->type(".hero-subtitle-input:last-child", "Subtitle for section {$i}");
            }

            $browser->click('.next-step-button')
                    ->waitFor('.save-template-button')
                    ->click('.save-template-button')
                    ->assertSee('Template saved successfully');

            // Test mobile responsiveness
            $browser->resize(375, 667) // iPhone size
                    ->visit('/templates')
                    ->assertVisible('.mobile-templates-menu')
                    ->assertVisible('.mobile-template-cards');

            // Test template preview on mobile
            $browser->click('.mobile-template-preview:first-child')
                    ->assertVisible('.mobile-template-preview-modal');

            $browser->resize(1920, 1080); // Back to desktop size

            // Test template deletion
            $browser->click('.template-delete-button:last-child')
                    ->waitFor('.delete-confirmation-modal')
                    ->click('.confirm-delete-button')
                    ->assertSee('Template deleted successfully');
        });
    }

    public function test_cross_browser_compatibility()
    {
        // Note: This is a placeholder for actual cross-browser testing
        // In a real environment, you would use services like BrowserStack,
        // Sauce Labs, or multiple Dusk drivers for different browsers

        $this->browse(function (Browser $browser) {
            // Simulate Chrome testing
            $browser->driver->executeScript("
                Object.defineProperty(navigator, 'userAgent', {
                    get: function () {
                        return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36';
                    }
                });
            ");

            // Login and test template functionality
            $browser->visit(config('app.url') . '/login')
                    ->type('#email', 'admin@test-university.com')
                    ->type('#password', 'password123')
                    ->click('.login-submit-button')
                    ->assertPathIs('/dashboard');

            // Test template creation
            $browser->clickLink('Templates')
                    ->click('.create-template-button')
                    ->waitFor('.template-creation-modal')
                    ->type('#template-name', 'Chrome Compatibility Test')
                    ->select('.template-category-select', 'landing')
                    ->select('.template-audience-select', 'individual')
                    ->click('.next-step-button')
                    ->waitFor('.template-designer')
                    ->click('.add-hero-section')
                    ->type('.hero-title-input', 'Cross-Browser Test')
                    ->click('.save-template-button')
                    ->assertSee('Template saved successfully');

            // Simulate Firefox testing
            $browser->driver->executeScript("
                Object.defineProperty(navigator, 'userAgent', {
                    get: function () {
                        return 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:97.0) Gecko/20100101 Firefox/97.0';
                    }
                });
            ");

            // Test in Firefox simulation
            $browser->visit('/templates')
                    ->assertSee('Chrome Compatibility Test')
                    ->click('.template-preview-button')
                    ->assertVisible('.template-preview-modal');
        });
    }
}