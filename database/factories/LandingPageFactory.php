<?php

namespace Database\Factories;

use App\Models\LandingPage;
use App\Models\Template;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LandingPage>
 */
class LandingPageFactory extends Factory
{
    protected $model = LandingPage::class;

    public function definition(): array
    {
        $template = Template::factory()->create();
        $campaignType = $template->campaign_type ?? fake()->randomElement([
            'onboarding', 'event_promotion', 'donation', 'networking',
            'career_services', 'recruiting', 'leadership', 'marketing'
        ]);

        $audienceType = $template->audience_type ?? fake()->randomElement([
            'individual', 'institution', 'employer'
        ]);

        $category = $template->category ?? 'individual';

        $name = $this->generateLandingPageName($campaignType, $audienceType);
        $status = fake()->randomElement(['draft', 'reviewing', 'published', 'archived']);

        $createdAt = fake()->dateTimeBetween('-6 months', 'now');
        $publishedAt = $status === 'published' ? fake()->dateTimeBetween($createdAt, 'now') : null;

        return [
            'template_id' => $template->id,
            'tenant_id' => $template->tenant_id,
            'name' => $name,
            'slug' => Str::slug($name . '-' . fake()->unique()->randomNumber()),
            'description' => $this->generateDescription($campaignType, $audienceType, $category),
            'config' => $this->generateConfig($template, $campaignType, $audienceType),
            'brand_config' => $this->generateBrandConfig(),
            'audience_type' => $audienceType,
            'campaign_type' => $campaignType,
            'category' => $category,
            'status' => $status,
            'published_at' => $publishedAt,
            'draft_hash' => Str::random(32),
            'version' => fake()->numberBetween(1, 5),
            'usage_count' => fake()->numberBetween(0, 10000),
            'conversion_count' => fake()->numberBetween(0, 1000),
            'preview_url' => 'https://preview.example.com/pages/' . Str::slug($name),
            'public_url' => $publishedAt ? 'https://example.com/p/' . Str::slug($name) : null,
            'seo_title' => substr($name, 0, 60),
            'seo_description' => substr($this->generateDescription($campaignType, $audienceType, $category), 0, 160),
            'seo_keywords' => fake()->randomElements([
                'alumni', 'networking', 'career', 'education', 'professional',
                'community', 'events', 'recruiting', 'mentorship', 'career services',
                'job opportunities', 'professional development', 'skills', 'leadership',
                'industry connections', 'work experience', 'graduates', 'success stories'
            ], rand(3, 8)),
            'social_image' => 'https://via.placeholder.com/1200x630/3B82F6/FFFFFF?text=' . urlencode($name),
            'tracking_id' => 'GA-' . fake()->numberBetween(100000000, 999999999),
            'favicon_url' => 'https://via.placeholder.com/32x32/3B82F6/FFFFFF?text=' . strtoupper(substr($name, 0, 3)),
            'custom_css' => $this->generateCustomCss($campaignType),
            'custom_js' => $this->generateCustomJs($campaignType),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    private function generateLandingPageName(string $campaignType, string $audienceType): string
    {
        $campaignNames = [
            'onboarding' => ['Welcome Aboard', 'Join Our Community', 'Getting Started', 'Your Journey Begins', 'Welcome to Success'],
            'event_promotion' => ['Annual Networking Event', 'Career Fair 2024', 'Leadership Summit', 'Professional Development Day', 'Industry Conference'],
            'donation' => ['Support Our Mission', 'Make a Difference', 'Give Back Today', 'Impact Our Community', 'Your Contribution Matters'],
            'networking' => ['Connect & Grow', 'Professional Networking', 'Industry Connections', 'Expand Your Network', 'Meet Industry Leaders'],
            'career_services' => ['Career Center', 'Job Opportunities', 'Professional Development', 'Skills Enhancement', 'Career Advancement'],
            'recruiting' => ['Join Our Team', 'Career Opportunities', 'We\'re Hiring', 'Build Your Future', 'Great Jobs Await'],
            'leadership' => ['Leadership Program', 'Executive Development', 'Mentorship Program', 'Leadership Journey', 'Executive Training'],
            'marketing' => ['Discover Our Services', 'Learn About Us', 'Why Choose Us', 'Our Story', 'What We Offer'],
        ];

        $audiencePrefixes = [
            'individual' => ['Alumni ', 'Graduate ', 'Professional ', 'Member '],
            'institution' => ['Institution ', 'University ', 'College ', 'Academic '],
            'employer' => ['Employer ', 'Corporate ', 'Business ', 'Company '],
        ];

        $prefix = fake()->randomElement($audiencePrefixes[$audienceType] ?? ['']);
        $suffix = fake()->randomElement($campaignNames[$campaignType]);

        return trim("{$prefix}{$suffix}");
    }

    private function generateDescription(string $campaignType, string $audienceType, string $category): string
    {
        $base = "A comprehensive landing page designed for {$audienceType} audiences focusing on {$campaignType}.";

        $descriptions = [
            'onboarding' => 'This page helps new users get started and understand all available resources and opportunities.',
            'event_promotion' => 'Promote your upcoming events with detailed information and easy registration options.',
            'donation' => 'Encourage contributions and donations to support important causes and community initiatives.',
            'networking' => 'Connect professionals and create meaningful relationships through our networking platform.',
            'career_services' => 'Access comprehensive career development resources and professional growth opportunities.',
            'recruiting' => 'Attract top talent with our professional recruiting pages and career opportunity listings.',
        ];

        return $base . ' ' . ($descriptions[$campaignType] ?? 'Discover what we have to offer and take the next step.');
    }

    private function generateConfig(Template $template, string $campaignType, string $audienceType): array
    {
        $baseConfig = $template->default_config ?? [];

        $campaignConfig = [
            'onboarding' => [
                'show_welcome_video' => true,
                'enable_social_proof' => true,
                'collect_demographics' => true,
                'offer_mentorship' => false,
                'highlight_resources' => true,
                'personalization_enabled' => true,
            ],
            'event_promotion' => [
                'show_event_date' => true,
                'enable_registration' => true,
                'display_speakers' => true,
                'show_venue_details' => true,
                'include_agenda' => true,
                'rsvp_required' => true,
            ],
            'donation' => [
                'show_impact_stories' => true,
                'enable_donation_tiers' => true,
                'display_donor_list' => false,
                'include_tax_info' => true,
                'show_progress_bar' => true,
                'offer_monthly_option' => true,
            ],
            'networking' => [
                'show_member_count' => true,
                'enable_direct_messaging' => true,
                'display_active_groups' => true,
                'include_search_filters' => true,
                'show_connection_suggestions' => true,
                'highlight_featured_profiles' => true,
            ],
            'career_services' => [
                'show_job_board' => true,
                'enable_resume_builder' => true,
                'include_skill_assessment' => true,
                'display_salary_data' => true,
                'offer_career_coaching' => true,
                'show_alumni_success' => true,
            ],
            'recruiting' => [
                'show_job_listings' => true,
                'enable_application_tracking' => true,
                'display_company_culture' => true,
                'include_benefits_info' => true,
                'show_team_photos' => true,
                'offer_referral_program' => false,
            ],
            'leadership' => [
                'show_program_details' => true,
                'enable_waitlist' => true,
                'display_faculty_bios' => true,
                'include_curriculum' => true,
                'show_alumni_outcomes' => true,
                'offer_info_sessions' => true,
            ],
            'marketing' => [
                'show_service_overview' => true,
                'enable_contact_form' => true,
                'display_testimonials' => true,
                'include_case_studies' => false,
                'show_team_members' => true,
                'offer_free_consultation' => true,
            ],
        ];

        $audienceConfig = [
            'individual' => [
                'personalization_level' => 'high',
                'show_peer_connections' => true,
                'enable_user_dashboard' => true,
                'track_personal_progress' => true,
                'custom_content_delivery' => true,
            ],
            'institution' => [
                'show_admin_features' => true,
                'enable_bulk_operations' => true,
                'include_institutional_data' => true,
                'track_group_progress' => true,
                'display_institution_branding' => true,
            ],
            'employer' => [
                'show_recruitment_tools' => true,
                'enable_candidate_tracking' => true,
                'include_analytics_dashboard' => true,
                'track_hiring_metrics' => true,
                'display_employer_branding' => true,
            ],
        ];

        return array_merge(
            $baseConfig,
            $campaignConfig[$campaignType] ?? [],
            $audienceConfig[$audienceType] ?? []
        );
    }

    private function generateBrandConfig(): array
    {
        return [
            'primary_color' => '#3B82F6',
            'secondary_color' => '#64748B',
            'accent_color' => '#EF4444',
            'font_family' => 'Inter, sans-serif',
            'heading_font_family' => 'Inter, sans-serif',
            'body_font_family' => 'Inter, sans-serif',
            'border_radius' => '8px',
            'button_style' => 'rounded',
            'logo_position' => 'top-left',
            'favicon_url' => 'https://via.placeholder.com/32x32/3B82F6/FFFFFF?text=F',
            'custom_variables' => [
                '--brand-primary' => '#3B82F6',
                '--brand-secondary' => '#64748B',
                '--brand-accent' => '#EF4444',
                '--brand-text' => '#1E293B',
                '--brand-background' => '#FFFFFF',
            ],
            'responsive_breakpoints' => [
                'mobile' => '576px',
                'tablet' => '768px',
                'desktop' => '1024px',
                'wide' => '1200px',
            ],
            'typography_scale' => [
                'xs' => '0.75rem',
                'sm' => '0.875rem',
                'base' => '1rem',
                'lg' => '1.125rem',
                'xl' => '1.25rem',
                '2xl' => '1.5rem',
                '3xl' => '1.875rem',
                '4xl' => '2.25rem',
                '5xl' => '3rem',
            ],
            'spacing_scale' => [
                '1' => '0.25rem',
                '2' => '0.5rem',
                '3' => '0.75rem',
                '4' => '1rem',
                '5' => '1.25rem',
                '6' => '1.5rem',
                '8' => '2rem',
                '10' => '2.5rem',
                '12' => '3rem',
                '16' => '4rem',
                '20' => '5rem',
                '24' => '6rem',
            ],
        ];
    }

    private function generateCustomCss(string $campaignType): string
    {
        $baseCss = "/* Custom styles for {$campaignType} landing page */\n";

        $campaignSpecificCss = [
            'onboarding' => "
.hero-section { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.welcome-message { animation: fadeInUp 0.8s ease; }
",

            'event_promotion' => "
.event-card { border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
.countdown-timer { font-weight: 700; color: #EF4444; }
",

            'recruiting' => "
.job-card:hover { transform: translateY(-2px); transition: all 0.3s ease; }
.highlight-badge { background: #10B981; color: white; padding: 4px 8px; border-radius: 20px; }
",

            'donation' => "
.progress-bar { height: 8px; background: #E5E7EB; border-radius: 4px; }
.progress-fill { background: linear-gradient(90deg, #10B981 0%, #059669 100%); }
",
        ];

        return $baseCss . ($campaignSpecificCss[$campaignType] ?? "
/* General custom styles */
.accent-button {
    background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-accent) 100%);
    border: none;
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.accent-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
}
");
    }

    private function generateCustomJs(string $campaignType): string
    {
        $baseJs = "// Custom JavaScript for {$campaignType} landing page\n";

        $campaignSpecificJs = [
            'onboarding' => "
function initializeWelcomeTour() {
    // Initialize user onboarding tour
    console.log('Welcome tour initialized');
}

function trackUserEngagement() {
    // Track user engagement metrics
    document.addEventListener('click', function(e) {
        if (e.target.matches('.engagement-element')) {
            // Track engagement event
            console.log('User engagement tracked');
        }
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeWelcomeTour();
    trackUserEngagement();
});
",

            'event_promotion' => "
function initializeEventCountdown() {
    const eventDate = new Date('2024-06-15T09:00:00');
    const countdownElement = document.getElementById('countdown');

    if (countdownElement) {
        setInterval(() => {
            const now = new Date();
            const difference = eventDate - now;

            if (difference > 0) {
                const days = Math.floor(difference / (1000 * 60 * 60 * 24));
                const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));

                countdownElement.textContent = `${days}d ${hours}h ${minutes}m`;
            }
        }, 60000);
    }
}

// Initialize countdown
document.addEventListener('DOMContentLoaded', initializeEventCountdown);
",

            'recruiting' => "
function initializeJobFilters() {
    const filterButtons = document.querySelectorAll('.job-filter');
    const jobCards = document.querySelectorAll('.job-card');

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.dataset.filter;

            jobCards.forEach(card => {
                if (filter === 'all' || card.dataset.category === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
}

// Initialize job filters
document.addEventListener('DOMContentLoaded', initializeJobFilters);
",

            'donation' => "
function initializeDonationTracker() {
    const donationForms = document.querySelectorAll('.donation-form');
    const progressBar = document.getElementById('donation-progress');

    donationForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const amount = parseFloat(this.querySelector('[name=\"amount\"]').value);

            if (amount && amount > 0) {
                // Update progress bar
                const currentProgress = parseFloat(progressBar.dataset.progress || 0);
                const newProgress = Math.min(currentProgress + (amount / 1000) * 100, 100);
                progressBar.style.width = newProgress + '%';
                progressBar.dataset.progress = newProgress;

                // Show thank you message
                showThankYouMessage(amount);
            }
        });
    });
}

function showThankYouMessage(amount) {
    const message = document.createElement('div');
    message.className = 'thank-you-message';
    message.innerHTML = `<strong>Thank you!</strong> Your $${amount} donation makes a real difference.`;
    message.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #10B981;
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        z-index: 1000;
        animation: slideInRight 0.3s ease;
    `;
    document.body.appendChild(message);

    setTimeout(() => message.remove(), 5000);
}

// Initialize donation tracker
document.addEventListener('DOMContentLoaded', initializeDonationTracker);
",
        ];

        return $baseJs . ($campaignSpecificJs[$campaignType] ?? "
// General custom JavaScript
console.log('Landing page loaded successfully');

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^=\"#\"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});

// Add loading states to forms
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type=\"submit\"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
        }
    });
});
");
    }

    // State methods
    public function published(): static
    {
        return $this->state([
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'public_url' => 'https://example.com/p/' . fake()->slug(),
        ]);
    }

    public function draft(): static
    {
        return $this->state([
            'status' => 'draft',
            'published_at' => null,
            'public_url' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state([
            'status' => 'archived',
            'published_at' => fake()->dateTimeBetween('-6 months', '-1 month'),
        ]);
    }

    public function onboarding(): static
    {
        return $this->state(['campaign_type' => 'onboarding']);
    }

    public function recruiting(): static
    {
        return $this->state(['campaign_type' => 'recruiting']);
    }

    public function eventPromotion(): static
    {
        return $this->state(['campaign_type' => 'event_promotion']);
    }

    public function donation(): static
    {
        return $this->state(['campaign_type' => 'donation']);
    }

    public function networking(): static
    {
        return $this->state(['campaign_type' => 'networking']);
    }

    public function forAudience(string $audienceType): static
    {
        return $this->state(['audience_type' => $audienceType]);
    }

    public function popular(): static
    {
        return $this->state([
            'usage_count' => fake()->numberBetween(1000, 10000),
            'conversion_count' => fake()->numberBetween(100, 1000),
        ]);
    }

    public function highConversion(): static
    {
        return $this->state([
            'conversion_count' => fake()->numberBetween(500, 2000),
            'usage_count' => fake()->numberBetween(2000, 5000),
        ]);
    }

    public function forTenant($tenantId): static
    {
        return $this->state([
            'tenant_id' => $tenantId,
        ]);
    }

    public function withCustomConfig(array $config): static
    {
        return $this->state(function (array $attributes) use ($config) {
            $existingConfig = $attributes['config'] ?? [];
            return [
                'config' => array_merge($existingConfig, $config),
            ];
        });
    }

    public function withBrandConfig(array $brandConfig): static
    {
        return $this->state(function (array $attributes) use ($brandConfig) {
            $existingBrandConfig = $attributes['brand_config'] ?? [];
            return [
                'brand_config' => array_merge($existingBrandConfig, $brandConfig),
            ];
        });
    }

    public function withSeo(array $seoData): static
    {
        return $this->state($seoData);
    }

    public function withTemplate($template): static
    {
        return $this->state([
            'template_id' => $template instanceof Template ? $template->id : $template,
            'campaign_type' => $template->campaign_type ?? $this->campaign_type,
            'audience_type' => $template->audience_type ?? $this->audience_type,
        ]);
    }

    public function reviewed(): static
    {
        return $this->state([
            'status' => 'reviewing',
            'published_at' => null,
        ]);
    }

    public function readyToPublish(): static
    {
        return $this->state([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}