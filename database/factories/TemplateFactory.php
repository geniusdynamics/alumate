<?php

namespace Database\Factories;

use App\Models\Template;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Template>
 */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition(): array
    {
        $campaignType = fake()->randomElement([
            'onboarding', 'event_promotion', 'donation', 'networking',
            'career_services', 'recruiting', 'leadership', 'marketing'
        ]);

        $audienceType = fake()->randomElement(['individual', 'institution', 'employer', 'general']);
        $category = fake()->randomElement(['landing', 'homepage', 'form', 'email', 'social']);

        $name = $this->generateTemplateName($campaignType, $audienceType, $category);
        $structure = $this->generateStructure($category, $campaignType, $audienceType);
        $config = $this->generateDefaultConfig($campaignType, $audienceType);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => Str::slug($name . '-' . fake()->unique()->randomNumber()),
            'description' => $this->generateDescription($campaignType, $audienceType, $category),
            'category' => $category,
            'audience_type' => $audienceType,
            'campaign_type' => $campaignType,
            'structure' => $structure,
            'default_config' => $config,
            'performance_metrics' => [
                'conversion_rate' => fake()->randomFloat(2, 0.5, 25.0),
                'avg_load_time' => fake()->randomFloat(2, 0.5, 3.0),
                'bounce_rate' => fake()->randomFloat(2, 10, 80),
                'completion_rate' => fake()->randomFloat(2, 15, 95),
                'avg_session_duration' => fake()->randomFloat(2, 60, 300),
                'mobile_conversion_rate' => fake()->randomFloat(2, 0.8, 35.0),
                'desktop_conversion_rate' => fake()->randomFloat(2, 1.0, 30.0),
                'ab_test_results' => null,
                'last_updated' => now()->toISOString(),
            ],
            'preview_image' => 'https://via.placeholder.com/800x600/3B82F6/FFFFFF?text=' . urlencode($name),
            'preview_url' => 'https://preview.example.com/templates/' . Str::slug($name),
            'version' => 1,
            'is_active' => true,
            'is_premium' => fake()->boolean(20), // 20% chance of being premium
            'usage_count' => fake()->numberBetween(0, 1000),
            'last_used_at' => fake()->optional(0.3)->dateTimeBetween('-6 months', 'now'),
            'tags' => $this->generateTags($campaignType, $audienceType, $category),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    private function generateTemplateName(string $campaignType, string $audienceType, string $category): string
    {
        $campaignNames = [
            'onboarding' => ['Welcome', 'Getting Started', 'First Steps', 'Introduction'],
            'event_promotion' => ['Event Announcement', 'Registration', 'Upcoming Event', 'Join Us'],
            'donation' => ['Support Our Cause', 'Make a Difference', 'Give Back', 'Impact'],
            'networking' => ['Connect & Network', 'Expand Your Circle', 'Professional Connections', 'Meet Others'],
            'career_services' => ['Career Center', 'Job Search Hub', 'Professional Development', 'Next Step'],
            'recruiting' => ['Join Our Team', 'Career Opportunities', 'We\'re Hiring', 'Talent Center'],
            'leadership' => ['Leadership Program', 'Executive Development', 'Leadership Journey', 'Executive Center'],
            'marketing' => ['Discover More', 'Learn About Us', 'Explore Our Services', 'About Our Organization'],
        ];

        $audienceNames = [
            'individual' => 'Alumni',
            'institution' => 'Institution',
            'employer' => 'Employer',
            'general' => 'Community',
        ];

        $categoryNames = [
            'landing' => 'Landing Page',
            'homepage' => 'Homepage',
            'form' => 'Registration Form',
            'email' => 'Email Template',
            'social' => 'Social Post',
        ];

        $campaign = fake()->randomElement($campaignNames[$campaignType]);
        $audience = $audienceNames[$audienceType];
        $categoryName = $categoryNames[$category];

        return "{$campaign} - {$audience} {$categoryName}";
    }

    private function generateStructure(string $category, string $campaignType, string $audienceType): array
    {
        return match ($category) {
            'landing' => $this->getLandingStructure($campaignType, $audienceType),
            'homepage' => $this->getHomepageStructure($campaignType, $audienceType),
            'form' => $this->getFormStructure($campaignType, $audienceType),
            'email' => $this->getEmailStructure($campaignType, $audienceType),
            'social' => $this->getSocialStructure($campaignType, $audienceType),
        };
    }

    private function getLandingStructure(string $campaignType, string $audienceType): array
    {
        $structures = [
            [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => $this->getHeroTitle($campaignType, $audienceType),
                            'subtitle' => $this->getHeroSubtitle($campaignType, $audienceType),
                            'cta_text' => $this->getCtaText($campaignType),
                            'background_type' => 'gradient',
                            'show_logo' => true,
                        ]
                    ],
                    [
                        'type' => 'features',
                        'config' => [
                            'title' => 'Why Choose Us',
                            'items' => $this->getLandingFeatures($campaignType, $audienceType),
                        ]
                    ],
                    [
                        'type' => 'form',
                        'config' => [
                            'title' => 'Get Started Today',
                            'fields' => $this->getFormFields($campaignType),
                            'submit_text' => 'Submit',
                        ]
                    ]
                ]
            ]
        ];

        return fake()->randomElement($structures);
    }

    private function getHomepageStructure(string $campaignType, string $audienceType): array
    {
        return [
            'sections' => [
                [
                    'type' => 'header',
                    'config' => [
                        'logo' => '{{brandLogo}}',
                        'navigation' => ['Home', 'About', 'Services', 'Contact'],
                        'cta_button' => ['text' => 'Sign Up', 'link' => '#signup'],
                    ]
                ],
                [
                    'type' => 'hero',
                    'config' => [
                        'title' => $this->getHeroTitle($campaignType, $audienceType),
                        'subtitle' => $this->getHeroSubtitle($campaignType, $audienceType),
                        'cta_text' => $this->getCtaText($campaignType),
                    ]
                ],
                [
                    'type' => 'statistics',
                    'config' => [
                        'items' => $this->getStatsItems($campaignType, $audienceType),
                    ]
                ],
                [
                    'type' => 'testimonials',
                    'config' => [
                        'title' => 'What People Say',
                        'items' => $this->getTestimonials($campaignType),
                    ]
                ],
                [
                    'type' => 'footer',
                    'config' => [
                        'copyright' => '© {{year}} All rights reserved.',
                        'links' => ['Privacy', 'Terms', 'Contact'],
                    ]
                ]
            ]
        ];
    }

    private function getFormStructure(string $campaignType, string $audienceType): array
    {
        return [
            'sections' => [
                [
                    'type' => 'header',
                    'config' => [
                        'title' => $this->getFormTitle($campaignType, $audienceType),
                        'description' => $this->getFormDescription($campaignType, $audienceType),
                    ]
                ],
                [
                    'type' => 'form',
                    'config' => [
                        'fields' => $this->getFormFields($campaignType),
                        'submit_text' => $this->getSubmitText($campaignType),
                        'show_progress' => true,
                        'steps' => $this->getFormSteps($campaignType),
                    ]
                ]
            ]
        ];
    }

    private function getEmailStructure(string $campaignType, string $audienceType): array
    {
        return [
            'sections' => [
                [
                    'type' => 'header',
                    'config' => [
                        'logo' => '{{brandLogo}}',
                        'title' => 'Important Update',
                        'date' => '{{currentDate}}',
                    ]
                ],
                [
                    'type' => 'content',
                    'config' => [
                        'title' => $this->getEmailTitle($campaignType, $audienceType),
                        'body' => $this->getEmailContent($campaignType, $audienceType),
                        'cta_text' => $this->getCtaText($campaignType),
                        'cta_url' => '#action',
                        'featured_image' => '{{emailImage}}',
                    ]
                ],
                [
                    'type' => 'social_links',
                    'config' => [
                        'platforms' => ['linkedin', 'twitter', 'facebook'],
                    ]
                ],
                [
                    'type' => 'footer',
                    'config' => [
                        'unsubscribe_text' => 'Unsubscribe',
                        'unsubscribe_url' => '#unsubscribe',
                        'copyright' => '© {{year}} All rights reserved.',
                    ]
                ]
            ]
        ];
    }

    private function getSocialStructure(string $campaignType, string $audienceType): array
    {
        return [
            'sections' => [
                [
                    'type' => 'image',
                    'config' => [
                        'url' => '{{socialImage}}',
                        'alt' => $this->getSocialAltText($campaignType),
                        'dimensions' => '1200x630',
                    ]
                ],
                [
                    'type' => 'text',
                    'config' => [
                        'headline' => $this->getSocialHeadline($campaignType, $audienceType),
                        'body' => $this->getSocialBody($campaignType, $audienceType),
                        'hashtag' => $this->getHashTags($campaignType),
                        'cta_text' => $this->getSocialCta($campaignType),
                    ]
                ]
            ]
        ];
    }

    // Helper methods for generating content
    private function getHeroTitle(string $campaignType, string $audienceType): string
    {
        $titles = [
            'onboarding' => [
                'individual' => 'Welcome to Your Alumni Journey',
                'institution' => 'Start Your Academic Adventure',
                'employer' => 'Connect with Top Talent',
                'general' => 'Join Our Community',
            ],
            'recruiting' => [
                'individual' => 'Shape Your Future',
                'institution' => 'Build Your Career',
                'employer' => 'Find Your Next Hire',
                'general' => 'Career Opportunities Await',
            ],
        ];

        return $titles[$campaignType][$audienceType] ?? 'Discover Your Potential';
    }

    private function getHeroSubtitle(string $campaignType, string $audienceType): string
    {
        $subtitles = [
            'onboarding' => 'Connect, learn, and grow with professionals just like you.',
            'recruiting' => 'Unlock new possibilities and advance your career journey.',
            'event_promotion' => 'Don\'t miss this incredible opportunity to network and learn.',
        ];

        return $subtitles[$campaignType] ?? 'Join thousands who trust our platform.';
    }

    private function getCtaText(string $campaignType): string
    {
        $ctas = [
            'onboarding' => 'Get Started',
            'recruiting' => 'Apply Now',
            'donation' => 'Donate Today',
            'event_promotion' => 'Register Now',
        ];

        return $ctas[$campaignType] ?? 'Learn More';
    }

    private function getLandingFeatures(string $campaignType, string $audienceType): array
    {
        $features = [
            'onboarding' => [
                ['title' => 'Easy Setup', 'description' => 'Get started in minutes'],
                ['title' => 'Community Support', 'description' => 'Connect with peers'],
                ['title' => 'Resources', 'description' => 'Access valuable content'],
            ],
            'recruiting' => [
                ['title' => 'Job Matching', 'description' => 'Perfect career matches'],
                ['title' => 'Skill Development', 'description' => 'Continuous learning'],
                ['title' => 'Networking', 'description' => 'Professional connections'],
            ],
        ];

        return $features[$campaignType] ?? [
            ['title' => 'Quality Service', 'description' => 'Professional support'],
            ['title' => 'Expert Team', 'description' => 'Experienced professionals'],
            ['title' => 'Proven Results', 'description' => 'Track record of success'],
        ];
    }

    private function getFormFields(string $campaignType): array
    {
        $fields = [
            'onboarding' => [
                ['type' => 'text', 'name' => 'first_name', 'label' => 'First Name', 'required' => true],
                ['type' => 'text', 'name' => 'last_name', 'label' => 'Last Name', 'required' => true],
                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
                ['type' => 'select', 'name' => 'graduation_year', 'label' => 'Graduation Year'],
            ],
            'recruiting' => [
                ['type' => 'text', 'name' => 'full_name', 'label' => 'Full Name', 'required' => true],
                ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
                ['type' => 'file', 'name' => 'resume', 'label' => 'Resume/CV'],
                ['type' => 'textarea', 'name' => 'experience', 'label' => 'Relevant Experience'],
            ],
        ];

        return $fields[$campaignType] ?? [
            ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true],
            ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
            ['type' => 'textarea', 'name' => 'message', 'label' => 'Message'],
        ];
    }

    private function getStatsItems(string $campaignType, string $audienceType): array
    {
        $stats = [
            'onboarding' => [
                ['value' => '10,000+', 'label' => 'Active Members'],
                ['value' => '500+', 'label' => 'Events Per Year'],
                ['value' => '95%', 'label' => 'Satisfaction Rate'],
            ],
            'recruiting' => [
                ['value' => '2,000+', 'label' => 'Open Positions'],
                ['value' => '500+', 'label' => 'Partner Companies'],
                ['value' => '85%', 'label' => 'Hiring Success'],
            ],
        ];

        return $stats[$campaignType] ?? [
            ['value' => '1,000+', 'label' => 'Happy Users'],
            ['value' => '24/7', 'label' => 'Support'],
            ['value' => '99%', 'label' => 'Uptime'],
        ];
    }

    private function getTestimonials(string $campaignType): array
    {
        return [
            [
                'name' => fake()->name(),
                'title' => 'Alumni 2020',
                'content' => 'This platform helped me reconnect with my network and find amazing opportunities.',
                'avatar' => 'https://via.placeholder.com/60x60?text=A',
            ],
            [
                'name' => fake()->name(),
                'title' => 'Alumni 2018',
                'content' => 'The career services provided were instrumental in my professional growth.',
                'avatar' => 'https://via.placeholder.com/60x60?text=B',
            ],
        ];
    }

    private function getFormTitle(string $campaignType, string $audienceType): string
    {
        $titles = [
            'onboarding' => 'Complete Your Profile',
            'recruiting' => 'Join Our Talent Pool',
            'donation' => 'Make a Difference Today',
            'event_promotion' => 'Register for the Event',
        ];

        return $titles[$campaignType] ?? 'Get Started';
    }

    private function getFormDescription(string $campaignType, string $audienceType): string
    {
        $descriptions = [
            'onboarding' => 'Tell us a bit about yourself to get the most out of your alumni experience.',
            'recruiting' => 'Share your qualifications and interests to connect with relevant opportunities.',
            'donation' => 'Your contribution helps us continue our mission and support our community.',
        ];

        return $descriptions[$campaignType] ?? 'Please fill out the form below.';
    }

    private function getSubmitText(string $campaignType): string
    {
        $texts = [
            'onboarding' => 'Create My Profile',
            'recruiting' => 'Submit Application',
            'donation' => 'Make Donation',
            'event_promotion' => 'Register Now',
        ];

        return $texts[$campaignType] ?? 'Submit';
    }

    private function getFormSteps(string $campaignType): array
    {
        $steps = [
            'onboarding' => [
                ['title' => 'Personal Details', 'description' => 'Basic information'],
                ['title' => 'Professional Info', 'description' => 'Career details'],
                ['title' => 'Preferences', 'description' => 'What interests you'],
            ],
            'recruiting' => [
                ['title' => 'Contact Info', 'description' => 'How to reach you'],
                ['title' => 'Experience', 'description' => 'Professional background'],
                ['title' => 'Documents', 'description' => 'Supporting files'],
            ],
        ];

        return $steps[$campaignType] ?? [['title' => 'Information', 'description' => 'Complete the form']];
    }

    private function getEmailTitle(string $campaignType, string $audienceType): string
    {
        $titles = [
            'onboarding' => 'Welcome to Our Community',
            'recruiting' => 'Exciting Career Opportunity',
            'event_promotion' => 'Don\'t Miss This Event',
            'donation' => 'Your Support Matters',
        ];

        return $titles[$campaignType] ?? 'Important Update';
    }

    private function getEmailContent(string $campaignType, string $audienceType): string
    {
        $content = [
            'onboarding' => 'We\'re thrilled to have you join our alumni community. Here\'s how to make the most of your experience...',
            'recruiting' => 'We have the perfect opportunity that matches your skills and interests. Learn more about this exciting role...',
            'event_promotion' => 'Join us for an unforgettable networking and learning experience. Early registration is recommended...',
        ];

        return $content[$campaignType] ?? 'Thank you for being part of our community. Here\'s what\'s new...';
    }

    private function getSocialAltText(string $campaignType): string
    {
        $texts = [
            'onboarding' => 'Welcome to our community - new members joining',
            'recruiting' => 'Career opportunities available',
            'event_promotion' => 'Upcoming event announcement',
            'donation' => 'Support our cause today',
        ];

        return $texts[$campaignType] ?? 'Community update';
    }

    private function getSocialHeadline(string $campaignType, string $audienceType): string
    {
        $headlines = [
            'onboarding' => 'Welcome to the Alumni Network! 🎉',
            'recruiting' => 'Great Career Opportunity Available',
            'event_promotion' => 'Join Us This Week for an Amazing Event!',
            'donation' => 'Your Support Makes a Difference',
        ];

        return $headlines[$campaignType] ?? 'Important Update';
    }

    private function getSocialBody(string $campaignType, string $audienceType): string
    {
        $bodies = [
            'onboarding' => 'We\'re excited to welcome new members to our growing community. Connect, learn, and grow together. #Welcome #Community #Network',
            'recruiting' => 'Looking for your next career move? We have amazing opportunities waiting for talented professionals. #Career #Jobs #Hiring',
            'event_promotion' => 'Don\'t miss this incredible networking event. Register now and connect with industry leaders. Limited spots available!',
        ];

        return $bodies[$campaignType] ?? 'Stay connected and be part of something special. #Community #Networking';
    }

    private function getHashTags(string $campaignType): string
    {
        $hashtags = [
            'onboarding' => '#Alumni #Welcome #Community #Network',
            'recruiting' => '#Career #Jobs #Hiring #Opportunity',
            'event_promotion' => '#Event #Networking #Community #JoinUs',
            'donation' => '#Support #Community #GiveBack #Impact',
        ];

        return $hashtags[$campaignType] ?? '#Community #Alumni';
    }

    private function getSocialCta(string $campaignType): string
    {
        $ctas = [
            'onboarding' => 'Join Now',
            'recruiting' => 'Apply Today',
            'event_promotion' => 'Register Here',
            'donation' => 'Learn More',
        ];

        return $ctas[$campaignType] ?? 'Learn More';
    }

    private function generateDefaultConfig(string $campaignType, string $audienceType): array
    {
        return [
            'colors' => [
                'primary' => '#3B82F6',
                'secondary' => '#64748B',
                'accent' => '#EF4444',
            ],
            'typography' => [
                'font_family' => 'Inter, sans-serif',
                'heading_size' => '2rem',
                'body_size' => '1rem',
            ],
            'spacing' => [
                'section_padding' => '2rem',
                'container_max_width' => '1200px',
            ],
            'campaign_settings' => [
                'type' => $campaignType,
                'target_audience' => $audienceType,
                'conversion_goal' => $this->getConversionGoal($campaignType),
            ],
        ];
    }

    private function generateDescription(string $campaignType, string $audienceType, string $category): string
    {
        $baseDescription = "A professional {$category} template designed for {$audienceType} audiences focusing on {$campaignType} campaigns.";

        return $baseDescription . ' This template features modern design elements, responsive layout, and optimized conversion elements.';
    }

    private function generateTags(string $campaignType, string $audienceType, string $category): array
    {
        $tags = [$campaignType, $audienceType, $category];

        $additional = [
            'responsive', 'mobile-friendly', 'professional',
            'conversion-focused', 'modern-design', 'accessible',
        ];

        return array_merge($tags, fake()->randomElements($additional, 3));
    }

    private function getConversionGoal(string $campaignType): string
    {
        $goals = [
            'onboarding' => 'profile_completion',
            'recruiting' => 'job_application',
            'event_promotion' => 'event_registration',
            'donation' => 'donation_amount',
            'networking' => 'connection_request',
            'career_services' => 'service_signup',
            'leadership' => 'program_application',
            'marketing' => 'lead_generation',
        ];

        return $goals[$campaignType] ?? 'general_engagement';
    }

    // State methods for different template types
    public function onboarding(): static
    {
        return $this->state(['campaign_type' => 'onboarding']);
    }

    public function recruiting(): static
    {
        return $this->state(['campaign_type' => 'recruiting']);
    }

    public function landing(): static
    {
        return $this->state(['category' => 'landing']);
    }

    public function homepage(): static
    {
        return $this->state(['category' => 'homepage']);
    }

    public function email(): static
    {
        return $this->state(['category' => 'email']);
    }

    public function social(): static
    {
        return $this->state(['category' => 'social']);
    }

    public function forAudience(string $audienceType): static
    {
        return $this->state(['audience_type' => $audienceType]);
    }

    public function premium(): static
    {
        return $this->state(['is_premium' => true]);
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function popular(): static
    {
        return $this->state([
            'usage_count' => fake()->numberBetween(500, 2000),
            'last_used_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function forTenant($tenantId): static
    {
        return $this->state(['tenant_id' => $tenantId]);
    }

    public function withPerformance(array $metrics): static
    {
        return $this->state(['performance_metrics' => $metrics]);
    }

    public function network(): static
    {
        return $this->state(['campaign_type' => 'networking']);
    }

    public function eventPromotion(): static
    {
        return $this->state(['campaign_type' => 'event_promotion']);
    }

    public function leaderboard(): static
    {
        return $this->state(['campaign_type' => 'leadership']);
    }

    public function careerServices(): static
    {
        return $this->state(['campaign_type' => 'career_services']);
    }

    public function donationCampaign(): static
    {
        return $this->state(['campaign_type' => 'donation']);
    }

    public function marketingCampaign(): static
    {
        return $this->state(['campaign_type' => 'marketing']);
    }
}