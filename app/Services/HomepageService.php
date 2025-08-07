<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HomepageService
{
    /**
     * Get platform statistics based on audience type
     */
    public function getPlatformStatistics(string $audience): array
    {
        // Mock data for now - will be replaced with real database queries
        $baseStats = [
            'total_alumni' => 25000,
            'active_users' => 18500,
            'successful_connections' => 45000,
            'job_placements' => 3200,
            'average_salary_increase' => 42,
            'mentorship_matches' => 1800,
            'events_hosted' => 850,
            'companies_represented' => 2400,
            'last_updated' => now()
        ];

        if ($audience === 'institutional') {
            return array_merge($baseStats, [
                'institutions_served' => 150,
                'branded_apps_deployed' => 45,
                'average_engagement_increase' => 300,
                'admin_satisfaction_rate' => 96
            ]);
        }

        return $baseStats;
    }

    /**
     * Get testimonials filtered by audience
     */
    public function getTestimonials(string $audience): Collection
    {
        // Mock testimonials - will be replaced with database queries
        $individualTestimonials = collect([
            [
                'id' => 1,
                'quote' => 'This platform helped me land my dream job at Google. The alumni connections were invaluable.',
                'author' => [
                    'name' => 'Sarah Chen',
                    'graduation_year' => 2019,
                    'current_role' => 'Software Engineer',
                    'current_company' => 'Google',
                    'profile_image' => '/images/testimonials/sarah-chen.jpg'
                ],
                'metrics' => [
                    'salary_increase' => 65,
                    'time_to_placement' => 45
                ]
            ],
            [
                'id' => 2,
                'quote' => 'The mentorship program connected me with industry leaders who guided my career transition.',
                'author' => [
                    'name' => 'Michael Rodriguez',
                    'graduation_year' => 2016,
                    'current_role' => 'Product Manager',
                    'current_company' => 'Microsoft',
                    'profile_image' => '/images/testimonials/michael-rodriguez.jpg'
                ],
                'metrics' => [
                    'salary_increase' => 45,
                    'career_advancement' => 'Senior to Director'
                ]
            ]
        ]);

        $institutionalTestimonials = collect([
            [
                'id' => 3,
                'quote' => 'Our alumni engagement increased by 400% after implementing the branded mobile app.',
                'institution' => [
                    'name' => 'Stanford University',
                    'type' => 'university',
                    'logo' => '/images/institutions/stanford-logo.png'
                ],
                'administrator' => [
                    'name' => 'Dr. Jennifer Walsh',
                    'title' => 'Director of Alumni Relations',
                    'profile_image' => '/images/testimonials/jennifer-walsh.jpg'
                ],
                'results' => [
                    'engagement_increase' => 400,
                    'app_downloads' => 15000,
                    'event_attendance_increase' => 250
                ]
            ]
        ]);

        return $audience === 'institutional' ? $institutionalTestimonials : $individualTestimonials;
    }

    /**
     * Get success stories with filtering
     */
    public function getSuccessStories(string $audience, array $filters): Collection
    {
        // Mock success stories - will be replaced with database queries
        $stories = collect([
            [
                'id' => 1,
                'title' => 'From Recent Grad to Tech Lead in 3 Years',
                'alumni_profile' => [
                    'name' => 'Alex Thompson',
                    'graduation_year' => 2020,
                    'degree' => 'Computer Science',
                    'current_role' => 'Tech Lead',
                    'current_company' => 'Stripe',
                    'industry' => 'Technology',
                    'career_stage' => 'mid_career'
                ],
                'career_progression' => [
                    'before' => [
                        'role' => 'Recent Graduate',
                        'salary' => 65000
                    ],
                    'after' => [
                        'role' => 'Tech Lead',
                        'salary' => 180000
                    ],
                    'timeframe' => '3 years'
                ],
                'platform_impact' => [
                    'connections_made' => 45,
                    'mentors_worked_with' => 3,
                    'referrals_received' => 8
                ]
            ]
        ]);

        // Apply filters
        if (!empty($filters['industry'])) {
            $stories = $stories->where('alumni_profile.industry', $filters['industry']);
        }

        if (!empty($filters['graduation_year'])) {
            $stories = $stories->where('alumni_profile.graduation_year', $filters['graduation_year']);
        }

        if (!empty($filters['career_stage'])) {
            $stories = $stories->where('alumni_profile.career_stage', $filters['career_stage']);
        }

        return $stories;
    }

    /**
     * Get platform features based on audience
     */
    public function getFeatures(string $audience): Collection
    {
        $individualFeatures = collect([
            [
                'id' => 'networking',
                'title' => 'Smart Alumni Networking',
                'description' => 'Connect with alumni based on shared interests, career goals, and industry focus.',
                'benefits' => [
                    'AI-powered connection recommendations',
                    'Industry-specific networking groups',
                    'Professional conversation starters'
                ],
                'screenshot' => '/images/features/networking-dashboard.png',
                'usage_stats' => [
                    'connections_made_monthly' => 12000,
                    'satisfaction_rate' => 94
                ]
            ],
            [
                'id' => 'mentorship',
                'title' => 'Career Mentorship Matching',
                'description' => 'Get paired with experienced alumni mentors in your field.',
                'benefits' => [
                    'Personalized mentor matching',
                    'Structured mentorship programs',
                    'Goal tracking and progress monitoring'
                ],
                'screenshot' => '/images/features/mentorship-matching.png',
                'usage_stats' => [
                    'active_mentorships' => 1800,
                    'success_rate' => 89
                ]
            ]
        ]);

        $institutionalFeatures = collect([
            [
                'id' => 'branded_apps',
                'title' => 'Custom Branded Mobile Apps',
                'description' => 'Deploy your own branded alumni app with full customization.',
                'benefits' => [
                    'Complete white-label solution',
                    'Custom branding and features',
                    'App store deployment included'
                ],
                'screenshot' => '/images/features/branded-app-showcase.png',
                'usage_stats' => [
                    'apps_deployed' => 45,
                    'average_downloads' => 8500
                ]
            ],
            [
                'id' => 'admin_dashboard',
                'title' => 'Comprehensive Admin Dashboard',
                'description' => 'Manage your entire alumni community with powerful analytics.',
                'benefits' => [
                    'Real-time engagement analytics',
                    'Event management tools',
                    'Communication campaign builder'
                ],
                'screenshot' => '/images/features/admin-dashboard.png',
                'usage_stats' => [
                    'institutions_using' => 150,
                    'time_saved_monthly' => '40 hours'
                ]
            ]
        ]);

        return $audience === 'institutional' ? $institutionalFeatures : $individualFeatures;
    }

    /**
     * Calculate career value based on user inputs
     */
    public function calculateCareerValue(array $data): array
    {
        // Mock calculation logic - will be replaced with real algorithms
        $baseIncrease = 35; // Base salary increase percentage
        $experienceMultiplier = min($data['experience_years'] * 0.05, 0.3);
        $industryMultiplier = $this->getIndustryMultiplier($data['industry']);
        
        $projectedIncrease = $baseIncrease + ($baseIncrease * $experienceMultiplier) + ($baseIncrease * $industryMultiplier);
        
        return [
            'projected_salary_increase' => round($projectedIncrease),
            'networking_value' => 'High',
            'career_advancement_timeline' => '12-18 months',
            'personalized_recommendations' => [
                'Connect with 5-10 alumni in your industry',
                'Join relevant professional groups',
                'Attend 2-3 networking events monthly',
                'Seek mentorship from senior professionals'
            ],
            'success_probability' => 87
        ];
    }

    /**
     * Process demo requests for institutional clients
     */
    public function processDemoRequest(array $data): array
    {
        // Mock processing - will be replaced with real lead management
        return [
            'success' => true,
            'message' => 'Demo request submitted successfully. Our team will contact you within 24 hours.',
            'next_steps' => [
                'Discovery call scheduled',
                'Custom demo preparation',
                'Proposal development'
            ],
            'estimated_response_time' => '24 hours'
        ];
    }

    /**
     * Process trial signups for individual users
     */
    public function processTrialSignup(array $data): array
    {
        // Mock processing - will be replaced with real user registration
        return [
            'success' => true,
            'message' => 'Trial account created successfully. Check your email for login instructions.',
            'trial_duration' => '14 days',
            'features_included' => [
                'Basic networking features',
                'Limited mentorship access',
                'Event browsing'
            ]
        ];
    }

    /**
     * Capture leads from various homepage interactions
     */
    public function captureLeads(array $data): array
    {
        // Mock lead capture - will be replaced with CRM integration
        return [
            'success' => true,
            'lead_id' => 'LEAD_' . uniqid(),
            'follow_up_scheduled' => true,
            'message' => 'Thank you for your interest. We\'ll be in touch soon.'
        ];
    }

    /**
     * Get personalized content based on audience type
     */
    public function getPersonalizedContent(string $audience, array $context = []): array
    {
        $cacheKey = "homepage_content_{$audience}_" . md5(serialize($context));
        
        return Cache::remember($cacheKey, 3600, function () use ($audience, $context) {
            return [
                'hero' => $this->getHeroContent($audience, $context),
                'features' => $this->getFeaturesContent($audience, $context),
                'testimonials' => $this->getTestimonialsContent($audience, $context),
                'pricing' => $this->getPricingContent($audience, $context),
                'cta' => $this->getCTAContent($audience, $context),
                'meta' => $this->getMetaContent($audience, $context)
            ];
        });
    }

    /**
     * Get audience-specific hero content
     */
    public function getHeroContent(string $audience, array $context = []): array
    {
        $baseContent = [
            'individual' => [
                'headline' => 'Accelerate Your Career Through Alumni Connections',
                'subtitle' => 'Join thousands of alumni advancing their careers through meaningful professional networking',
                'description' => 'Connect with alumni in your field, find mentors, discover job opportunities, and build lasting professional relationships.',
                'background_image' => '/images/hero/individual-networking.jpg',
                'background_video' => '/videos/hero/alumni-success-stories.mp4'
            ],
            'institutional' => [
                'headline' => 'Transform Alumni Engagement with Your Branded Platform',
                'subtitle' => 'Increase alumni participation by 300% with custom mobile apps and comprehensive analytics',
                'description' => 'Empower your institution with white-label alumni solutions, branded mobile apps, and powerful engagement tools.',
                'background_image' => '/images/hero/institutional-dashboard.jpg',
                'background_video' => '/videos/hero/institutional-success.mp4'
            ]
        ];

        $content = $baseContent[$audience] ?? $baseContent['individual'];

        // Personalize based on context
        if (!empty($context['referrer']) && str_contains($context['referrer'], '.edu')) {
            $content['headline'] = 'Welcome, ' . $this->extractInstitutionName($context['referrer']) . ' Alumni!';
        }

        if (!empty($context['utm_campaign'])) {
            $content = $this->personalizeForCampaign($content, $context['utm_campaign']);
        }

        return $content;
    }

    /**
     * Get audience-specific features content
     */
    public function getFeaturesContent(string $audience, array $context = []): array
    {
        $features = $this->getFeatures($audience);
        
        // Add audience-specific metadata
        return [
            'title' => $audience === 'institutional' 
                ? 'Comprehensive Alumni Engagement Solutions' 
                : 'Powerful Career Development Tools',
            'subtitle' => $audience === 'institutional'
                ? 'Everything you need to build a thriving alumni community'
                : 'Connect, learn, and grow with your alumni network',
            'items' => $features->toArray()
        ];
    }

    /**
     * Get audience-specific testimonials content
     */
    public function getTestimonialsContent(string $audience, array $context = []): array
    {
        $testimonials = $this->getTestimonials($audience);
        
        return [
            'title' => $audience === 'institutional' 
                ? 'Trusted by Leading Institutions' 
                : 'Success Stories from Alumni Like You',
            'subtitle' => $audience === 'institutional'
                ? 'See how universities and organizations are transforming alumni engagement'
                : 'Discover how alumni are advancing their careers through our platform',
            'items' => $testimonials->toArray()
        ];
    }

    /**
     * Get audience-specific pricing content
     */
    public function getPricingContent(string $audience, array $context = []): array
    {
        if ($audience === 'institutional') {
            return [
                'title' => 'Enterprise Solutions for Every Institution',
                'subtitle' => 'Flexible pricing that scales with your alumni community',
                'tiers' => [
                    [
                        'name' => 'Professional',
                        'price' => 2500,
                        'billing_period' => 'monthly',
                        'features' => [
                            'Up to 5,000 alumni',
                            'Basic analytics dashboard',
                            'Email support',
                            'Standard integrations'
                        ],
                        'popular' => false
                    ],
                    [
                        'name' => 'Enterprise',
                        'price' => 5000,
                        'billing_period' => 'monthly',
                        'features' => [
                            'Up to 25,000 alumni',
                            'Advanced analytics & reporting',
                            'Branded mobile app',
                            'Priority support',
                            'Custom integrations',
                            'Dedicated success manager'
                        ],
                        'popular' => true
                    ],
                    [
                        'name' => 'Custom',
                        'price' => null,
                        'billing_period' => 'custom',
                        'features' => [
                            'Unlimited alumni',
                            'Full white-label solution',
                            'Custom development',
                            '24/7 dedicated support',
                            'On-premise deployment option'
                        ],
                        'popular' => false,
                        'custom_quote' => true
                    ]
                ]
            ];
        }

        return [
            'title' => 'Choose Your Alumni Networking Plan',
            'subtitle' => 'Start free, upgrade when you\'re ready to accelerate your career',
            'tiers' => [
                [
                    'name' => 'Free',
                    'price' => 0,
                    'billing_period' => 'monthly',
                    'features' => [
                        'Basic alumni directory access',
                        'Limited messaging',
                        'Event browsing',
                        'Basic profile'
                    ],
                    'popular' => false,
                    'trial_available' => false
                ],
                [
                    'name' => 'Professional',
                    'price' => 29,
                    'billing_period' => 'monthly',
                    'features' => [
                        'Unlimited messaging',
                        'Advanced search filters',
                        'Mentorship matching',
                        'Job board access',
                        'Event registration',
                        'Career resources'
                    ],
                    'popular' => true,
                    'trial_available' => true
                ],
                [
                    'name' => 'Premium',
                    'price' => 59,
                    'billing_period' => 'monthly',
                    'features' => [
                        'Everything in Professional',
                        'Priority mentorship matching',
                        'Exclusive networking events',
                        'Career coaching sessions',
                        'Industry insights reports',
                        'Personal brand building tools'
                    ],
                    'popular' => false,
                    'trial_available' => true
                ]
            ]
        ];
    }

    /**
     * Get audience-specific CTA content
     */
    public function getCTAContent(string $audience, array $context = []): array
    {
        if ($audience === 'institutional') {
            return [
                'primary' => [
                    'text' => 'Request Demo',
                    'action' => 'demo',
                    'variant' => 'primary',
                    'tracking_event' => 'institutional_demo_request',
                    'href' => '/demo-request'
                ],
                'secondary' => [
                    'text' => 'Download Case Studies',
                    'action' => 'download',
                    'variant' => 'secondary',
                    'tracking_event' => 'institutional_case_study_download',
                    'href' => '/case-studies'
                ],
                'tertiary' => [
                    'text' => 'Contact Sales',
                    'action' => 'contact',
                    'variant' => 'outline',
                    'tracking_event' => 'institutional_contact_sales',
                    'href' => '/contact-sales'
                ]
            ];
        }

        return [
            'primary' => [
                'text' => 'Start Free Trial',
                'action' => 'trial',
                'variant' => 'primary',
                'tracking_event' => 'individual_trial_start',
                'href' => '/register'
            ],
            'secondary' => [
                'text' => 'Join Waitlist',
                'action' => 'waitlist',
                'variant' => 'secondary',
                'tracking_event' => 'individual_waitlist_join',
                'href' => '/waitlist'
            ]
        ];
    }

    /**
     * Get audience-specific meta content for SEO
     */
    public function getMetaContent(string $audience, array $context = []): array
    {
        if ($audience === 'institutional') {
            return [
                'title' => 'Alumni Engagement Platform for Universities & Organizations | AlumniConnect',
                'description' => 'Transform your alumni community with branded mobile apps, comprehensive analytics, and powerful engagement tools. Trusted by 150+ institutions worldwide.',
                'keywords' => 'alumni engagement, university alumni platform, branded alumni app, institutional alumni solutions, alumni analytics'
            ];
        }

        return [
            'title' => 'Professional Alumni Networking Platform | AlumniConnect',
            'description' => 'Connect with alumni, find mentors, discover job opportunities, and advance your career through meaningful professional networking.',
            'keywords' => 'alumni networking, career advancement, professional mentorship, job opportunities, alumni connections'
        ];
    }

    /**
     * Get content variations for A/B testing
     */
    public function getContentVariations(string $audience, string $testId): array
    {
        $variations = [
            'hero_message_test' => [
                'individual' => [
                    'control' => [
                        'headline' => 'Accelerate Your Career Through Alumni Connections',
                        'subtitle' => 'Join thousands of alumni advancing their careers'
                    ],
                    'variant_a' => [
                        'headline' => 'Unlock Your Career Potential with Alumni Network',
                        'subtitle' => 'Connect with successful alumni and fast-track your career growth'
                    ],
                    'variant_b' => [
                        'headline' => 'Your Next Career Move Starts Here',
                        'subtitle' => 'Leverage the power of alumni connections for career success'
                    ]
                ],
                'institutional' => [
                    'control' => [
                        'headline' => 'Transform Alumni Engagement with Your Branded Platform',
                        'subtitle' => 'Increase alumni participation by 300% with custom solutions'
                    ],
                    'variant_a' => [
                        'headline' => 'Build a Thriving Alumni Community',
                        'subtitle' => 'Engage alumni with branded apps and powerful analytics'
                    ],
                    'variant_b' => [
                        'headline' => 'The Complete Alumni Engagement Solution',
                        'subtitle' => 'From mobile apps to analytics - everything you need in one platform'
                    ]
                ]
            ]
        ];

        return $variations[$testId][$audience] ?? [];
    }

    /**
     * Track content personalization events
     */
    public function trackPersonalizationEvent(string $audience, string $event, array $data = []): void
    {
        try {
            Log::info('Homepage personalization event', [
                'audience' => $audience,
                'event' => $event,
                'data' => $data,
                'timestamp' => now(),
                'user_agent' => request()->userAgent(),
                'ip' => request()->ip()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track personalization event', [
                'error' => $e->getMessage(),
                'audience' => $audience,
                'event' => $event
            ]);
        }
    }

    /**
     * Get content management configuration
     */
    public function getContentManagementConfig(): array
    {
        return [
            'audiences' => ['individual', 'institutional'],
            'content_types' => [
                'hero' => [
                    'fields' => ['headline', 'subtitle', 'description', 'background_image', 'background_video'],
                    'required' => ['headline', 'subtitle'],
                    'max_length' => ['headline' => 100, 'subtitle' => 200, 'description' => 500]
                ],
                'features' => [
                    'fields' => ['title', 'subtitle', 'items'],
                    'required' => ['title', 'items'],
                    'max_items' => 6
                ],
                'testimonials' => [
                    'fields' => ['title', 'subtitle', 'items'],
                    'required' => ['title', 'items'],
                    'max_items' => 10
                ],
                'pricing' => [
                    'fields' => ['title', 'subtitle', 'tiers'],
                    'required' => ['title', 'tiers'],
                    'max_tiers' => 4
                ]
            ],
            'cache_duration' => 3600,
            'version_control' => true,
            'approval_workflow' => true
        ];
    }

    /**
     * Extract institution name from referrer URL
     */
    private function extractInstitutionName(string $referrer): string
    {
        $url = parse_url($referrer);
        $domain = $url['host'] ?? '';
        
        // Simple extraction logic - can be enhanced
        $parts = explode('.', $domain);
        if (count($parts) >= 2) {
            return ucfirst($parts[0]);
        }
        
        return 'University';
    }

    /**
     * Personalize content for specific campaigns
     */
    private function personalizeForCampaign(array $content, string $campaign): array
    {
        $campaignPersonalizations = [
            'career_fair' => [
                'headline' => 'Ready to Take the Next Step in Your Career?',
                'subtitle' => 'Connect with alumni who can help you land your dream job'
            ],
            'reunion' => [
                'headline' => 'Reconnect and Advance Your Career',
                'subtitle' => 'Turn reunion connections into career opportunities'
            ],
            'graduation' => [
                'headline' => 'Welcome to Your Alumni Network!',
                'subtitle' => 'Start building professional connections that will shape your career'
            ]
        ];

        if (isset($campaignPersonalizations[$campaign])) {
            $content = array_merge($content, $campaignPersonalizations[$campaign]);
        }

        return $content;
    }

    /**
     * Get trust badges and company logos
     */
    public function getTrustBadgesAndLogos(string $audience): array
    {
        // Mock data for now - will be replaced with real database queries
        $trustBadges = [
            [
                'id' => '1',
                'name' => 'SOC 2 Type II',
                'image' => '/images/badges/soc2-type2.png',
                'description' => 'SOC 2 Type II compliance ensures the highest standards of security, availability, and confidentiality.',
                'verification_url' => 'https://example.com/soc2-verification'
            ],
            [
                'id' => '2',
                'name' => 'GDPR Compliant',
                'image' => '/images/badges/gdpr-compliant.png',
                'description' => 'Full compliance with the General Data Protection Regulation for EU data protection.',
                'verification_url' => 'https://example.com/gdpr-verification'
            ],
            [
                'id' => '3',
                'name' => 'ISO 27001',
                'image' => '/images/badges/iso-27001.png',
                'description' => 'ISO 27001 certified information security management system.',
                'verification_url' => 'https://example.com/iso-verification'
            ],
            [
                'id' => '4',
                'name' => 'Privacy Shield',
                'image' => '/images/badges/privacy-shield.png',
                'description' => 'EU-US Privacy Shield framework compliance for international data transfers.'
            ],
            [
                'id' => '5',
                'name' => 'SSL Secured',
                'image' => '/images/badges/ssl-secured.png',
                'description' => '256-bit SSL encryption protects all data in transit.'
            ],
            [
                'id' => '6',
                'name' => 'CCPA Compliant',
                'image' => '/images/badges/ccpa-compliant.png',
                'description' => 'California Consumer Privacy Act compliance for enhanced privacy rights.'
            ]
        ];

        $companyLogos = [
            [
                'id' => '1',
                'name' => 'Google',
                'logo' => '/images/companies/google-logo.png',
                'website' => 'https://google.com',
                'category' => 'Technology'
            ],
            [
                'id' => '2',
                'name' => 'Microsoft',
                'logo' => '/images/companies/microsoft-logo.png',
                'website' => 'https://microsoft.com',
                'category' => 'Technology'
            ],
            [
                'id' => '3',
                'name' => 'Apple',
                'logo' => '/images/companies/apple-logo.png',
                'website' => 'https://apple.com',
                'category' => 'Technology'
            ],
            [
                'id' => '4',
                'name' => 'Amazon',
                'logo' => '/images/companies/amazon-logo.png',
                'website' => 'https://amazon.com',
                'category' => 'Technology'
            ],
            [
                'id' => '5',
                'name' => 'Meta',
                'logo' => '/images/companies/meta-logo.png',
                'website' => 'https://meta.com',
                'category' => 'Technology'
            ],
            [
                'id' => '6',
                'name' => 'Netflix',
                'logo' => '/images/companies/netflix-logo.png',
                'website' => 'https://netflix.com',
                'category' => 'Entertainment'
            ],
            [
                'id' => '7',
                'name' => 'Tesla',
                'logo' => '/images/companies/tesla-logo.png',
                'website' => 'https://tesla.com',
                'category' => 'Automotive'
            ],
            [
                'id' => '8',
                'name' => 'Goldman Sachs',
                'logo' => '/images/companies/goldman-sachs-logo.png',
                'website' => 'https://goldmansachs.com',
                'category' => 'Finance'
            ]
        ];

        // Add audience-specific badges for institutional clients
        if ($audience === 'institutional') {
            $trustBadges[] = [
                'id' => '7',
                'name' => 'FERPA Compliant',
                'image' => '/images/badges/ferpa-compliant.png',
                'description' => 'Family Educational Rights and Privacy Act compliance for educational institutions.',
                'verification_url' => 'https://example.com/ferpa-verification'
            ];
        }

        return [
            'trust_badges' => $trustBadges,
            'company_logos' => $companyLogos
        ];
    }

    /**
     * Get industry-specific multiplier for salary calculations
     */
    private function getIndustryMultiplier(string $industry): float
    {
        $multipliers = [
            'Technology' => 0.25,
            'Finance' => 0.20,
            'Healthcare' => 0.15,
            'Consulting' => 0.22,
            'Education' => 0.10,
            'Non-profit' => 0.08,
            'Government' => 0.12,
            'Manufacturing' => 0.14,
            'Retail' => 0.11,
            'Media' => 0.16
        ];

        return $multipliers[$industry] ?? 0.15;
    }
}