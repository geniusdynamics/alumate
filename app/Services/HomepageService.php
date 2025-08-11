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
            'last_updated' => now(),
        ];

        if ($audience === 'institutional') {
            return array_merge($baseStats, [
                'institutions_served' => 150,
                'branded_apps_deployed' => 45,
                'average_engagement_increase' => 300,
                'admin_satisfaction_rate' => 96,
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
                    'profile_image' => '/images/testimonials/sarah-chen.jpg',
                ],
                'metrics' => [
                    'salary_increase' => 65,
                    'time_to_placement' => 45,
                ],
            ],
            [
                'id' => 2,
                'quote' => 'The mentorship program connected me with industry leaders who guided my career transition.',
                'author' => [
                    'name' => 'Michael Rodriguez',
                    'graduation_year' => 2016,
                    'current_role' => 'Product Manager',
                    'current_company' => 'Microsoft',
                    'profile_image' => '/images/testimonials/michael-rodriguez.jpg',
                ],
                'metrics' => [
                    'salary_increase' => 45,
                    'career_advancement' => 'Senior to Director',
                ],
            ],
        ]);

        $institutionalTestimonials = collect([
            [
                'id' => 3,
                'quote' => 'Our alumni engagement increased by 400% after implementing the branded mobile app.',
                'institution' => [
                    'name' => 'Stanford University',
                    'type' => 'university',
                    'logo' => '/images/institutions/stanford-logo.png',
                ],
                'administrator' => [
                    'name' => 'Dr. Jennifer Walsh',
                    'title' => 'Director of Alumni Relations',
                    'profile_image' => '/images/testimonials/jennifer-walsh.jpg',
                ],
                'results' => [
                    'engagement_increase' => 400,
                    'app_downloads' => 15000,
                    'event_attendance_increase' => 250,
                ],
            ],
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
                    'career_stage' => 'mid_career',
                ],
                'career_progression' => [
                    'before' => [
                        'role' => 'Recent Graduate',
                        'salary' => 65000,
                    ],
                    'after' => [
                        'role' => 'Tech Lead',
                        'salary' => 180000,
                    ],
                    'timeframe' => '3 years',
                ],
                'platform_impact' => [
                    'connections_made' => 45,
                    'mentors_worked_with' => 3,
                    'referrals_received' => 8,
                ],
            ],
        ]);

        // Apply filters
        if (! empty($filters['industry'])) {
            $stories = $stories->where('alumni_profile.industry', $filters['industry']);
        }

        if (! empty($filters['graduation_year'])) {
            $stories = $stories->where('alumni_profile.graduation_year', $filters['graduation_year']);
        }

        if (! empty($filters['career_stage'])) {
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
                'description' => 'Connect with alumni based on shared interests, career goals, and industry focus using AI-powered recommendations.',
                'benefits' => [
                    'AI-powered connection recommendations',
                    'Industry-specific networking groups',
                    'Professional conversation starters',
                    'Mutual connection discovery',
                    'Privacy-controlled networking',
                ],
                'screenshot' => '/images/features/networking-dashboard.png',
                'demo_video' => '/videos/features/networking-demo.mp4',
                'demo_url' => '/demo/networking',
                'icon' => 'network',
                'category' => 'networking',
                'usage_stats' => [
                    [
                        'metric' => 'connections_made_monthly',
                        'value' => 12000,
                        'label' => 'Monthly Connections',
                        'trend' => 'up',
                    ],
                ],
                'target_persona' => [
                    [
                        'id' => 'recent_grad',
                        'name' => 'Recent Graduates',
                        'description' => 'New alumni looking to build professional networks',
                        'career_stage' => 'recent_grad',
                        'primary_goals' => ['Find mentors', 'Job opportunities'],
                        'pain_points' => ['Limited network', 'Career uncertainty'],
                    ],
                    [
                        'id' => 'mid_career',
                        'name' => 'Mid-Career Professionals',
                        'description' => 'Experienced professionals seeking career advancement',
                        'career_stage' => 'mid_career',
                        'primary_goals' => ['Career advancement', 'Industry connections'],
                        'pain_points' => ['Career plateau', 'Limited industry connections'],
                    ],
                ],
                'hotspots' => [
                    [
                        'x' => 30,
                        'y' => 25,
                        'title' => 'Connection Recommendations',
                        'description' => 'AI-powered suggestions based on your profile and goals',
                    ],
                    [
                        'x' => 70,
                        'y' => 60,
                        'title' => 'Industry Groups',
                        'description' => 'Join groups specific to your industry and interests',
                    ],
                ],
            ],
            [
                'id' => 'mentorship',
                'title' => 'Career Mentorship Matching',
                'description' => 'Get paired with experienced alumni mentors in your field through our intelligent matching system.',
                'benefits' => [
                    'Personalized mentor matching',
                    'Structured mentorship programs',
                    'Goal tracking and progress monitoring',
                    'Video call integration',
                    'Mentorship resource library',
                ],
                'screenshot' => '/images/features/mentorship-matching.png',
                'demo_video' => '/videos/features/mentorship-demo.mp4',
                'demo_url' => '/demo/mentorship',
                'icon' => 'users',
                'category' => 'mentorship',
                'usage_stats' => [
                    [
                        'metric' => 'active_mentorships',
                        'value' => 1800,
                        'label' => 'Active Mentorships',
                        'trend' => 'up',
                    ],
                ],
                'target_persona' => [
                    [
                        'id' => 'recent_grad',
                        'name' => 'Recent Graduates',
                        'description' => 'New alumni seeking career guidance',
                        'career_stage' => 'recent_grad',
                        'primary_goals' => ['Career guidance', 'Skill development'],
                        'pain_points' => ['Lack of experience', 'Career direction'],
                    ],
                ],
            ],
            [
                'id' => 'job_board',
                'title' => 'Exclusive Alumni Job Board',
                'description' => 'Access job opportunities shared exclusively within your alumni network, with referral tracking.',
                'benefits' => [
                    'Alumni-exclusive job postings',
                    'Referral tracking system',
                    'Application status updates',
                    'Salary insights and benchmarks',
                    'Interview preparation resources',
                ],
                'screenshot' => '/images/features/job-board.png',
                'demo_video' => '/videos/features/job-board-demo.mp4',
                'icon' => 'briefcase',
                'category' => 'jobs',
                'usage_stats' => [
                    [
                        'metric' => 'job_placements',
                        'value' => 3200,
                        'label' => 'Job Placements',
                        'trend' => 'up',
                    ],
                ],
                'target_persona' => [
                    [
                        'id' => 'job_seeker',
                        'name' => 'Job Seekers',
                        'description' => 'Alumni actively looking for new opportunities',
                        'career_stage' => 'mid_career',
                        'primary_goals' => ['Find new job', 'Career change'],
                        'pain_points' => ['Limited opportunities', 'Competition'],
                    ],
                ],
            ],
            [
                'id' => 'events',
                'title' => 'Alumni Events & Networking',
                'description' => 'Discover and attend alumni events, both virtual and in-person, tailored to your interests.',
                'benefits' => [
                    'Personalized event recommendations',
                    'Virtual and in-person events',
                    'RSVP and calendar integration',
                    'Event networking tools',
                    'Post-event connection facilitation',
                ],
                'screenshot' => '/images/features/events-calendar.png',
                'demo_video' => '/videos/features/events-demo.mp4',
                'icon' => 'calendar',
                'category' => 'events',
                'usage_stats' => [
                    [
                        'metric' => 'events_monthly',
                        'value' => 850,
                        'label' => 'Monthly Events',
                        'trend' => 'up',
                    ],
                ],
                'target_persona' => [
                    [
                        'id' => 'networker',
                        'name' => 'Active Networkers',
                        'description' => 'Alumni who actively participate in networking events',
                        'career_stage' => 'mid_career',
                        'primary_goals' => ['Expand network', 'Industry insights'],
                        'pain_points' => ['Finding relevant events', 'Time constraints'],
                    ],
                ],
            ],
        ]);

        $institutionalFeatures = collect([
            [
                'id' => 'admin_dashboard',
                'title' => 'Comprehensive Admin Dashboard',
                'description' => 'Manage your entire alumni community with powerful analytics and engagement tools.',
                'benefits' => [
                    'Real-time engagement analytics',
                    'Event management tools',
                    'Communication campaign builder',
                    'Alumni directory management',
                    'Custom reporting and insights',
                ],
                'target_institution' => 'university',
                'screenshot' => '/images/features/admin-dashboard.png',
                'demo_video' => '/videos/features/admin-dashboard-demo.mp4',
                'demo_url' => '/demo/admin-dashboard',
                'pricing_tier' => 'enterprise',
                'customization_level' => 'advanced',
                'icon' => 'dashboard',
                'usage_stats' => [
                    [
                        'metric' => 'institutions_using',
                        'value' => 150,
                        'label' => 'Institutions Using',
                        'trend' => 'up',
                    ],
                ],
                'hotspots' => [
                    [
                        'x' => 25,
                        'y' => 30,
                        'title' => 'Analytics Overview',
                        'description' => 'Real-time engagement metrics and trends',
                    ],
                    [
                        'x' => 75,
                        'y' => 45,
                        'title' => 'Event Management',
                        'description' => 'Create and manage alumni events with RSVP tracking',
                    ],
                ],
            ],
            [
                'id' => 'branded_mobile_app',
                'title' => 'Custom Branded Mobile Apps',
                'description' => 'Deploy your own branded alumni app with full customization and white-label solutions.',
                'benefits' => [
                    'Complete white-label solution',
                    'Custom branding and features',
                    'App store deployment included',
                    'Push notification campaigns',
                    'Offline functionality',
                ],
                'target_institution' => 'university',
                'screenshot' => '/images/features/branded-app-showcase.png',
                'demo_video' => '/videos/features/branded-app-demo.mp4',
                'pricing_tier' => 'enterprise',
                'customization_level' => 'full',
                'icon' => 'mobile',
                'usage_stats' => [
                    [
                        'metric' => 'apps_deployed',
                        'value' => 45,
                        'label' => 'Apps Deployed',
                        'trend' => 'up',
                    ],
                ],
            ],
            [
                'id' => 'analytics_reporting',
                'title' => 'Advanced Analytics & Reporting',
                'description' => 'Comprehensive analytics suite with custom reporting and data visualization tools.',
                'benefits' => [
                    'Custom dashboard creation',
                    'Alumni engagement metrics',
                    'Event performance analytics',
                    'ROI tracking and reporting',
                    'Data export capabilities',
                ],
                'target_institution' => 'university',
                'screenshot' => '/images/features/analytics-dashboard.png',
                'demo_video' => '/videos/features/analytics-demo.mp4',
                'pricing_tier' => 'professional',
                'customization_level' => 'basic',
                'icon' => 'chart',
                'usage_stats' => [
                    [
                        'metric' => 'reports_generated',
                        'value' => 25000,
                        'label' => 'Reports Generated',
                        'trend' => 'up',
                    ],
                ],
            ],
            [
                'id' => 'integration_suite',
                'title' => 'Enterprise Integration Suite',
                'description' => 'Seamlessly integrate with your existing CRM, email, and event management systems.',
                'benefits' => [
                    'CRM system integration',
                    'Email platform connectivity',
                    'Single sign-on (SSO) support',
                    'API access and webhooks',
                    'Data synchronization tools',
                ],
                'target_institution' => 'corporate',
                'screenshot' => '/images/features/integrations.png',
                'demo_video' => '/videos/features/integrations-demo.mp4',
                'pricing_tier' => 'enterprise',
                'customization_level' => 'advanced',
                'icon' => 'integration',
                'usage_stats' => [
                    [
                        'metric' => 'active_integrations',
                        'value' => 120,
                        'label' => 'Active Integrations',
                        'trend' => 'up',
                    ],
                ],
            ],
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
                'Seek mentorship from senior professionals',
            ],
            'success_probability' => 87,
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
                'Proposal development',
            ],
            'estimated_response_time' => '24 hours',
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
                'Event browsing',
            ],
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
            'lead_id' => 'LEAD_'.uniqid(),
            'follow_up_scheduled' => true,
            'message' => 'Thank you for your interest. We\'ll be in touch soon.',
        ];
    }

    /**
     * Get personalized content based on audience type
     */
    public function getPersonalizedContent(string $audience, array $context = []): array
    {
        $cacheKey = "homepage_content_{$audience}_".md5(serialize($context));

        return Cache::remember($cacheKey, 3600, function () use ($audience, $context) {
            return [
                'hero' => $this->getHeroContent($audience, $context),
                'features' => $this->getFeaturesContent($audience, $context),
                'testimonials' => $this->getTestimonialsContent($audience, $context),
                'pricing' => $this->getPricingContent($audience, $context),
                'cta' => $this->getCTAContent($audience, $context),
                'meta' => $this->getMetaContent($audience, $context),
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
                'background_video' => '/videos/hero/alumni-success-stories.mp4',
            ],
            'institutional' => [
                'headline' => 'Transform Alumni Engagement with Your Branded Platform',
                'subtitle' => 'Increase alumni participation by 300% with custom mobile apps and comprehensive analytics',
                'description' => 'Empower your institution with white-label alumni solutions, branded mobile apps, and powerful engagement tools.',
                'background_image' => '/images/hero/institutional-dashboard.jpg',
                'background_video' => '/videos/hero/institutional-success.mp4',
            ],
        ];

        $content = $baseContent[$audience] ?? $baseContent['individual'];

        // Personalize based on context
        if (! empty($context['referrer']) && str_contains($context['referrer'], '.edu')) {
            $content['headline'] = 'Welcome, '.$this->extractInstitutionName($context['referrer']).' Alumni!';
        }

        if (! empty($context['utm_campaign'])) {
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
            'items' => $features->toArray(),
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
            'items' => $testimonials->toArray(),
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
                            'Standard integrations',
                        ],
                        'popular' => false,
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
                            'Dedicated success manager',
                        ],
                        'popular' => true,
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
                            'On-premise deployment option',
                        ],
                        'popular' => false,
                        'custom_quote' => true,
                    ],
                ],
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
                        'Basic profile',
                    ],
                    'popular' => false,
                    'trial_available' => false,
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
                        'Career resources',
                    ],
                    'popular' => true,
                    'trial_available' => true,
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
                        'Personal brand building tools',
                    ],
                    'popular' => false,
                    'trial_available' => true,
                ],
            ],
        ];
    }

    /**
     * Get branded apps showcase data for institutional audience
     */
    public function getBrandedAppsData(): array
    {
        return [
            'featured_apps' => [
                [
                    'id' => 'stanford-alumni',
                    'institution_name' => 'Stanford University',
                    'institution_type' => 'university',
                    'logo' => '/images/institutions/stanford-logo.png',
                    'app_icon' => '/images/apps/stanford-app-icon.png',
                    'app_store_url' => 'https://apps.apple.com/us/app/stanford-alumni/id123456789',
                    'play_store_url' => 'https://play.google.com/store/apps/details?id=edu.stanford.alumni',
                    'screenshots' => [
                        [
                            'id' => 'stanford-home',
                            'url' => '/images/apps/stanford/home-screen.png',
                            'title' => 'Home Dashboard',
                            'description' => 'Personalized alumni dashboard with news and updates',
                            'device' => 'iphone',
                            'category' => 'home',
                        ],
                        [
                            'id' => 'stanford-network',
                            'url' => '/images/apps/stanford/networking-screen.png',
                            'title' => 'Alumni Network',
                            'description' => 'Connect with fellow Stanford alumni worldwide',
                            'device' => 'iphone',
                            'category' => 'networking',
                        ],
                        [
                            'id' => 'stanford-events',
                            'url' => '/images/apps/stanford/events-screen.png',
                            'title' => 'Events & Reunions',
                            'description' => 'Discover and register for alumni events',
                            'device' => 'iphone',
                            'category' => 'events',
                        ],
                    ],
                    'customizations' => [
                        [
                            'category' => 'branding',
                            'name' => 'Custom Color Scheme',
                            'description' => 'Stanford Cardinal red throughout the app',
                            'implemented' => true,
                            'complexity' => 'basic',
                        ],
                        [
                            'category' => 'features',
                            'name' => 'Class Year Groups',
                            'description' => 'Automatic grouping by graduation year',
                            'implemented' => true,
                            'complexity' => 'advanced',
                        ],
                        [
                            'category' => 'integrations',
                            'name' => 'Stanford Directory Integration',
                            'description' => 'Sync with official alumni directory',
                            'implemented' => true,
                            'complexity' => 'custom',
                        ],
                    ],
                    'user_count' => 15000,
                    'engagement_stats' => [
                        [
                            'metric' => 'daily_active_users',
                            'value' => 2500,
                            'unit' => 'count',
                            'trend' => 'up',
                            'period' => 'last_30_days',
                        ],
                        [
                            'metric' => 'session_duration',
                            'value' => 12,
                            'unit' => 'minutes',
                            'trend' => 'up',
                            'period' => 'average',
                        ],
                    ],
                    'launch_date' => '2023-09-01',
                    'featured' => true,
                ],
                [
                    'id' => 'mit-connect',
                    'institution_name' => 'MIT',
                    'institution_type' => 'university',
                    'logo' => '/images/institutions/mit-logo.png',
                    'app_icon' => '/images/apps/mit-app-icon.png',
                    'app_store_url' => 'https://apps.apple.com/us/app/mit-alumni/id987654321',
                    'play_store_url' => 'https://play.google.com/store/apps/details?id=edu.mit.alumni',
                    'screenshots' => [
                        [
                            'id' => 'mit-home',
                            'url' => '/images/apps/mit/home-screen.png',
                            'title' => 'MIT Connect Home',
                            'description' => 'Tech-focused alumni dashboard',
                            'device' => 'iphone',
                            'category' => 'home',
                        ],
                        [
                            'id' => 'mit-innovation',
                            'url' => '/images/apps/mit/innovation-screen.png',
                            'title' => 'Innovation Hub',
                            'description' => 'Startup and innovation networking',
                            'device' => 'iphone',
                            'category' => 'networking',
                        ],
                    ],
                    'customizations' => [
                        [
                            'category' => 'branding',
                            'name' => 'MIT Brand Colors',
                            'description' => 'Cardinal red and steel gray theme',
                            'implemented' => true,
                            'complexity' => 'basic',
                        ],
                        [
                            'category' => 'features',
                            'name' => 'Startup Showcase',
                            'description' => 'Dedicated section for alumni startups',
                            'implemented' => true,
                            'complexity' => 'advanced',
                        ],
                    ],
                    'user_count' => 12000,
                    'engagement_stats' => [
                        [
                            'metric' => 'daily_active_users',
                            'value' => 1800,
                            'unit' => 'count',
                            'trend' => 'up',
                            'period' => 'last_30_days',
                        ],
                        [
                            'metric' => 'retention_rate',
                            'value' => 78,
                            'unit' => 'percentage',
                            'trend' => 'stable',
                            'period' => '30_day',
                        ],
                    ],
                    'launch_date' => '2023-11-15',
                    'featured' => true,
                ],
                [
                    'id' => 'google-alumni',
                    'institution_name' => 'Google',
                    'institution_type' => 'corporate',
                    'logo' => '/images/institutions/google-logo.png',
                    'app_icon' => '/images/apps/google-app-icon.png',
                    'app_store_url' => 'https://apps.apple.com/us/app/google-alumni/id456789123',
                    'play_store_url' => 'https://play.google.com/store/apps/details?id=com.google.alumni',
                    'screenshots' => [
                        [
                            'id' => 'google-home',
                            'url' => '/images/apps/google/home-screen.png',
                            'title' => 'Google Alumni Hub',
                            'description' => 'Corporate alumni networking platform',
                            'device' => 'android',
                            'category' => 'home',
                        ],
                    ],
                    'customizations' => [
                        [
                            'category' => 'branding',
                            'name' => 'Google Material Design',
                            'description' => 'Full Google brand integration',
                            'implemented' => true,
                            'complexity' => 'advanced',
                        ],
                    ],
                    'user_count' => 8500,
                    'engagement_stats' => [
                        [
                            'metric' => 'daily_active_users',
                            'value' => 1200,
                            'unit' => 'count',
                            'trend' => 'up',
                            'period' => 'last_30_days',
                        ],
                    ],
                    'launch_date' => '2024-01-20',
                    'featured' => false,
                ],
            ],
            'customization_options' => [
                [
                    'id' => 'branding-options',
                    'category' => 'branding',
                    'name' => 'Visual Branding',
                    'description' => 'Complete visual customization with your institution\'s brand identity',
                    'options' => [
                        [
                            'id' => 'logo-placement',
                            'name' => 'Custom Logo Placement',
                            'description' => 'Your logo prominently displayed throughout the app',
                            'type' => 'logo',
                            'required' => true,
                        ],
                        [
                            'id' => 'color-scheme',
                            'name' => 'Brand Color Scheme',
                            'description' => 'Primary and secondary colors matching your brand',
                            'type' => 'color',
                            'required' => true,
                        ],
                        [
                            'id' => 'typography',
                            'name' => 'Custom Typography',
                            'description' => 'Brand-consistent fonts and text styling',
                            'type' => 'text',
                            'required' => false,
                        ],
                    ],
                    'examples' => [
                        [
                            'id' => 'stanford-branding',
                            'name' => 'Stanford University',
                            'description' => 'Cardinal red theme with Stanford tree logo',
                            'before_image' => '/images/examples/generic-app.png',
                            'after_image' => '/images/examples/stanford-branded.png',
                            'institution_type' => 'university',
                        ],
                    ],
                    'level' => 'basic',
                ],
                [
                    'id' => 'feature-customization',
                    'category' => 'features',
                    'name' => 'Feature Customization',
                    'description' => 'Tailor app features to your institution\'s specific needs',
                    'options' => [
                        [
                            'id' => 'custom-sections',
                            'name' => 'Custom Content Sections',
                            'description' => 'Add institution-specific content areas',
                            'type' => 'feature',
                            'required' => false,
                        ],
                        [
                            'id' => 'event-integration',
                            'name' => 'Event System Integration',
                            'description' => 'Connect with your existing event management',
                            'type' => 'integration',
                            'required' => false,
                        ],
                    ],
                    'examples' => [
                        [
                            'id' => 'mit-features',
                            'name' => 'MIT Innovation Hub',
                            'description' => 'Custom startup showcase and innovation tracking',
                            'before_image' => '/images/examples/standard-features.png',
                            'after_image' => '/images/examples/mit-innovation.png',
                            'institution_type' => 'university',
                        ],
                    ],
                    'level' => 'advanced',
                ],
                [
                    'id' => 'integration-options',
                    'category' => 'integrations',
                    'name' => 'System Integrations',
                    'description' => 'Connect with your existing institutional systems',
                    'options' => [
                        [
                            'id' => 'crm-integration',
                            'name' => 'CRM System Integration',
                            'description' => 'Sync with Salesforce, HubSpot, or custom CRM',
                            'type' => 'integration',
                            'required' => false,
                        ],
                        [
                            'id' => 'sso-integration',
                            'name' => 'Single Sign-On (SSO)',
                            'description' => 'Integrate with institutional authentication',
                            'type' => 'integration',
                            'required' => false,
                        ],
                    ],
                    'examples' => [],
                    'level' => 'enterprise',
                ],
                [
                    'id' => 'analytics-customization',
                    'category' => 'analytics',
                    'name' => 'Analytics & Reporting',
                    'description' => 'Custom analytics dashboards and reporting tools',
                    'options' => [
                        [
                            'id' => 'custom-dashboards',
                            'name' => 'Custom Analytics Dashboards',
                            'description' => 'Tailored metrics and KPI tracking',
                            'type' => 'feature',
                            'required' => false,
                        ],
                        [
                            'id' => 'automated-reports',
                            'name' => 'Automated Reporting',
                            'description' => 'Scheduled reports for administrators',
                            'type' => 'feature',
                            'required' => false,
                        ],
                    ],
                    'examples' => [],
                    'level' => 'advanced',
                ],
            ],
            'app_store_integration' => [
                'apple_app_store' => true,
                'google_play_store' => true,
                'custom_domain' => true,
                'white_label' => true,
                'institution_branding' => true,
                'review_management' => true,
                'analytics_integration' => true,
            ],
            'development_timeline' => [
                'phases' => [
                    [
                        'id' => 'discovery-planning',
                        'name' => 'Discovery & Planning',
                        'description' => 'Requirements gathering, brand analysis, and technical planning',
                        'duration' => '2-3 weeks',
                        'deliverables' => [
                            'Technical requirements document',
                            'Brand integration guidelines',
                            'Feature specification document',
                            'Project timeline and milestones',
                        ],
                        'dependencies' => [],
                        'milestones' => [
                            [
                                'id' => 'requirements-complete',
                                'name' => 'Requirements Finalized',
                                'description' => 'All technical and brand requirements documented',
                                'due_date' => 'Week 2',
                                'status' => 'pending',
                            ],
                            [
                                'id' => 'design-approval',
                                'name' => 'Design Mockups Approved',
                                'description' => 'Brand-integrated design mockups approved',
                                'due_date' => 'Week 3',
                                'status' => 'pending',
                            ],
                        ],
                    ],
                    [
                        'id' => 'design-branding',
                        'name' => 'Design & Branding',
                        'description' => 'UI/UX design with full brand integration and customization',
                        'duration' => '3-4 weeks',
                        'deliverables' => [
                            'Branded UI/UX designs',
                            'Interactive prototypes',
                            'Brand style guide implementation',
                            'App store assets (icons, screenshots)',
                        ],
                        'dependencies' => ['discovery-planning'],
                        'milestones' => [
                            [
                                'id' => 'ui-designs-complete',
                                'name' => 'UI Designs Complete',
                                'description' => 'All screen designs with branding complete',
                                'due_date' => 'Week 6',
                                'status' => 'pending',
                            ],
                            [
                                'id' => 'prototype-testing',
                                'name' => 'Prototype User Testing',
                                'description' => 'Interactive prototype tested with stakeholders',
                                'due_date' => 'Week 7',
                                'status' => 'pending',
                            ],
                        ],
                    ],
                    [
                        'id' => 'development',
                        'name' => 'App Development',
                        'description' => 'Native iOS and Android app development with custom features',
                        'duration' => '6-8 weeks',
                        'deliverables' => [
                            'Native iOS application',
                            'Native Android application',
                            'Backend API integration',
                            'Push notification system',
                            'Offline functionality',
                        ],
                        'dependencies' => ['design-branding'],
                        'milestones' => [
                            [
                                'id' => 'alpha-build',
                                'name' => 'Alpha Build Complete',
                                'description' => 'Core functionality implemented',
                                'due_date' => 'Week 11',
                                'status' => 'pending',
                            ],
                            [
                                'id' => 'beta-testing',
                                'name' => 'Beta Testing Phase',
                                'description' => 'Internal testing with stakeholders',
                                'due_date' => 'Week 14',
                                'status' => 'pending',
                            ],
                        ],
                    ],
                    [
                        'id' => 'testing-qa',
                        'name' => 'Testing & Quality Assurance',
                        'description' => 'Comprehensive testing, bug fixes, and performance optimization',
                        'duration' => '2-3 weeks',
                        'deliverables' => [
                            'Comprehensive test suite',
                            'Performance optimization',
                            'Security audit completion',
                            'Bug fixes and refinements',
                        ],
                        'dependencies' => ['development'],
                        'milestones' => [
                            [
                                'id' => 'qa-complete',
                                'name' => 'QA Testing Complete',
                                'description' => 'All testing phases completed successfully',
                                'due_date' => 'Week 16',
                                'status' => 'pending',
                            ],
                        ],
                    ],
                    [
                        'id' => 'deployment-launch',
                        'name' => 'Deployment & Launch',
                        'description' => 'App store submission, approval, and official launch',
                        'duration' => '1-2 weeks',
                        'deliverables' => [
                            'App Store submission and approval',
                            'Google Play submission and approval',
                            'Launch marketing materials',
                            'User onboarding documentation',
                            'Admin training materials',
                        ],
                        'dependencies' => ['testing-qa'],
                        'milestones' => [
                            [
                                'id' => 'store-approval',
                                'name' => 'App Store Approval',
                                'description' => 'Apps approved on both iOS and Android stores',
                                'due_date' => 'Week 17',
                                'status' => 'pending',
                            ],
                            [
                                'id' => 'official-launch',
                                'name' => 'Official Launch',
                                'description' => 'Apps publicly available and launched',
                                'due_date' => 'Week 18',
                                'status' => 'pending',
                            ],
                        ],
                    ],
                ],
                'total_duration' => '14-20 weeks',
                'estimated_cost' => 'Starting at $75,000',
                'maintenance_cost' => '$2,000-5,000/month',
            ],
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
                    'href' => '/demo-request',
                ],
                'secondary' => [
                    'text' => 'Download Case Studies',
                    'action' => 'download',
                    'variant' => 'secondary',
                    'tracking_event' => 'institutional_case_study_download',
                    'href' => '/case-studies',
                ],
                'tertiary' => [
                    'text' => 'Contact Sales',
                    'action' => 'contact',
                    'variant' => 'outline',
                    'tracking_event' => 'institutional_contact_sales',
                    'href' => '/contact-sales',
                ],
            ];
        }

        return [
            'primary' => [
                'text' => 'Start Free Trial',
                'action' => 'trial',
                'variant' => 'primary',
                'tracking_event' => 'individual_trial_start',
                'href' => '/register',
            ],
            'secondary' => [
                'text' => 'Join Waitlist',
                'action' => 'waitlist',
                'variant' => 'secondary',
                'tracking_event' => 'individual_waitlist_join',
                'href' => '/waitlist',
            ],
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
                'keywords' => 'alumni engagement, university alumni platform, branded alumni app, institutional alumni solutions, alumni analytics',
            ];
        }

        return [
            'title' => 'Professional Alumni Networking Platform | AlumniConnect',
            'description' => 'Connect with alumni, find mentors, discover job opportunities, and advance your career through meaningful professional networking.',
            'keywords' => 'alumni networking, career advancement, professional mentorship, job opportunities, alumni connections',
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
                        'subtitle' => 'Join thousands of alumni advancing their careers',
                    ],
                    'variant_a' => [
                        'headline' => 'Unlock Your Career Potential with Alumni Network',
                        'subtitle' => 'Connect with successful alumni and fast-track your career growth',
                    ],
                    'variant_b' => [
                        'headline' => 'Your Next Career Move Starts Here',
                        'subtitle' => 'Leverage the power of alumni connections for career success',
                    ],
                ],
                'institutional' => [
                    'control' => [
                        'headline' => 'Transform Alumni Engagement with Your Branded Platform',
                        'subtitle' => 'Increase alumni participation by 300% with custom solutions',
                    ],
                    'variant_a' => [
                        'headline' => 'Build a Thriving Alumni Community',
                        'subtitle' => 'Engage alumni with branded apps and powerful analytics',
                    ],
                    'variant_b' => [
                        'headline' => 'The Complete Alumni Engagement Solution',
                        'subtitle' => 'From mobile apps to analytics - everything you need in one platform',
                    ],
                ],
            ],
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
                'ip' => request()->ip(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track personalization event', [
                'error' => $e->getMessage(),
                'audience' => $audience,
                'event' => $event,
            ]);
        }
    }

    /**
     * Get default content when personalized content fails or returns null
     */
    public function getDefaultContent(string $audience = 'individual'): array
    {
        if ($audience === 'institutional') {
            return [
                'hero' => [
                    'headline' => 'Transform Alumni Engagement with Your Institution',
                    'subtitle' => 'Increase alumni participation with comprehensive engagement tools',
                    'description' => 'Empower your institution with white-label solutions and powerful analytics',
                    'background_image' => '/images/hero/institutional-default.jpg',
                    'background_video' => null,
                ],
                'features' => [
                    'title' => 'Complete Alumni Engagement Solutions',
                    'subtitle' => 'Everything you need to build a thriving alumni community',
                    'items' => [
                        [
                            'id' => 'branded_apps',
                            'title' => 'Branded Mobile Apps',
                            'description' => 'Custom mobile applications with your institution\'s branding',
                            'icon' => 'mobile-app',
                        ],
                        [
                            'id' => 'analytics',
                            'title' => 'Advanced Analytics',
                            'description' => 'Comprehensive reporting and engagement insights',
                            'icon' => 'analytics',
                        ],
                    ],
                ],
                'testimonials' => [
                    'title' => 'Trusted by Leading Institutions',
                    'subtitle' => 'See how organizations are transforming alumni engagement',
                    'items' => [],
                ],
                'pricing' => [
                    'title' => 'Enterprise Solutions',
                    'subtitle' => 'Flexible pricing that scales with your community',
                    'tiers' => [],
                ],
                'cta' => [
                    'primary' => [
                        'text' => 'Request Demo',
                        'action' => 'demo',
                        'href' => '/demo-request',
                    ],
                    'secondary' => [
                        'text' => 'Learn More',
                        'action' => 'learn',
                        'href' => '/learn-more',
                    ],
                ],
                'meta' => [
                    'title' => 'Alumni Engagement Platform for Institutions',
                    'description' => 'Transform your alumni community with branded solutions and analytics',
                    'keywords' => 'alumni engagement, institutional solutions',
                ],
            ];
        }

        // Default individual audience content
        return [
            'hero' => [
                'headline' => 'Connect. Engage. Thrive.',
                'subtitle' => 'Join the alumni community that transforms careers',
                'description' => 'Build meaningful connections and advance your career through alumni networking',
                'background_image' => '/images/hero/individual-default.jpg',
                'background_video' => null,
            ],
            'features' => [
                'title' => 'Powerful Career Development Tools',
                'subtitle' => 'Connect, learn, and grow with your alumni network',
                'items' => [
                    [
                        'id' => 'networking',
                        'title' => 'Alumni Networking',
                        'description' => 'Connect with alumni in your field and industry',
                        'icon' => 'network',
                    ],
                    [
                        'id' => 'mentorship',
                        'title' => 'Mentorship Matching',
                        'description' => 'Find experienced mentors to guide your career',
                        'icon' => 'mentor',
                    ],
                ],
            ],
            'testimonials' => [
                'title' => 'Success Stories from Alumni',
                'subtitle' => 'Discover how alumni are advancing their careers',
                'items' => [],
            ],
            'pricing' => [
                'title' => 'Choose Your Plan',
                'subtitle' => 'Start free, upgrade when ready',
                'tiers' => [],
            ],
            'cta' => [
                'primary' => [
                    'text' => 'Get Started',
                    'action' => 'signup',
                    'href' => '/register',
                ],
                'secondary' => [
                    'text' => 'Learn More',
                    'action' => 'learn',
                    'href' => '/learn-more',
                ],
            ],
            'meta' => [
                'title' => 'Alumni Networking Platform',
                'description' => 'Connect with alumni and advance your career through meaningful networking',
                'keywords' => 'alumni networking, career advancement',
            ],
        ];
    }

    /**
     * Apply A/B test variants to content only if variants exist and are valid
     */
    public function applyABTestVariants(array $content, array $abTests, string $audience): array
    {
        // If content is not array, default it
        if (! is_array($content)) {
            $content = [];
        }

        if (empty($abTests) || ! is_array($abTests)) {
            return $content;
        }

        foreach ($abTests as $testId => $testData) {
            // Iterate only when each $testData is array
            if (! is_array($testData)) {
                continue;
            }

            // $variant = $testData['variant'] if array; else continue
            if (! isset($testData['variant']) || ! is_array($testData['variant'])) {
                continue;
            }
            $variant = $testData['variant'];

            // $overrides = $variant['component_overrides'] if array; else []
            $overrides = [];
            if (isset($variant['component_overrides']) && is_array($variant['component_overrides'])) {
                $overrides = $variant['component_overrides'];
            }

            // If overrides contain key matching $audience and is array, call mergeContentOverrides
            if (isset($overrides[$audience]) && is_array($overrides[$audience])) {
                $content = $this->mergeContentOverrides($content, $overrides[$audience]);
            } else {
                // Otherwise apply general keys (headline/subtitle) safely
                $generalKeys = ['headline', 'subtitle'];
                foreach ($generalKeys as $key) {
                    if (isset($overrides[$key])) {
                        $content[$key] = $overrides[$key];
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Format A/B tests for frontend consumption with strict null safety
     */
    public function formatABTestsForFrontend($abTests): array
    {
        // If $abTests is not array, return []
        if (! is_array($abTests)) {
            return [];
        }

        $formatted = [];
        foreach ($abTests as $testId => $testData) {
            // Skip non-array entries
            if (! is_array($testData)) {
                continue;
            }

            // Safely extract test name and variant fields with ?? fallbacks
            $test = $testData['test'] ?? [];
            $variant = $testData['variant'] ?? [];

            // Extract test name with fallback
            $testName = null;
            if (is_array($test)) {
                $testName = $test['name'] ?? null;
            }
            $testName = $testName ?? 'unknown';

            // Extract variant ID and name with fallbacks
            $variantId = null;
            $variantName = null;
            if (is_array($variant)) {
                $variantId = $variant['id'] ?? null;
                $variantName = $variant['name'] ?? null;
            }

            // Validate id and variant_id; if missing, synthesize readable placeholders
            $finalTestId = is_string($testId) && ! empty(trim($testId)) ? $testId : 'unknown';
            $finalVariantId = is_string($variantId) && ! empty(trim($variantId)) ? $variantId : 'unknown';
            $finalVariantName = is_string($variantName) && ! empty(trim($variantName)) ? $variantName : 'unknown';

            // Always add entry with synthesized placeholders if needed
            $formatted[$finalTestId] = [
                'id' => $finalTestId,
                'name' => $testName,
                'variant_id' => $finalVariantId,
                'variant_name' => $finalVariantName,
            ];
        }

        // Always return an array; no exceptions from malformed entries
        return $formatted;
    }

    /**
     * Merge content overrides with deep null-safe merging
     */
    private function mergeContentOverrides(array $content, array $overrides): array
    {
        // Ensure content has hero and cta structure; if missing, initialize subsections
        if (! isset($content['hero']) || ! is_array($content['hero'])) {
            $content['hero'] = [];
        }
        if (! isset($content['cta']) || ! is_array($content['cta'])) {
            $content['cta'] = [];
        }

        foreach ($overrides as $key => $value) {
            // For known keys (headline, subtitle, primary_cta_text, secondary_cta_text), update safely
            $knownKeys = ['headline', 'subtitle', 'primary_cta_text', 'secondary_cta_text'];
            if (in_array($key, $knownKeys)) {
                if ($key === 'headline' || $key === 'subtitle') {
                    $content['hero'][$key] = $value;
                } elseif ($key === 'primary_cta_text') {
                    if (! isset($content['cta']['primary']) || ! is_array($content['cta']['primary'])) {
                        $content['cta']['primary'] = [];
                    }
                    $content['cta']['primary']['text'] = $value;
                } elseif ($key === 'secondary_cta_text') {
                    if (! isset($content['cta']['secondary']) || ! is_array($content['cta']['secondary'])) {
                        $content['cta']['secondary'] = [];
                    }
                    $content['cta']['secondary']['text'] = $value;
                }
            } else {
                // For other keys, call setNestedValue only when the key is a non-empty string; ignore invalid
                if (is_string($key) && ! empty(trim($key))) {
                    if (strpos($key, '.') !== false) {
                        // Handle nested keys like 'hero.headline'
                        $this->setNestedValue($content, $key, $value);
                    } else {
                        // Direct key assignment with null safety
                        if (! isset($content[$key])) {
                            $content[$key] = [];
                        }
                        if (is_array($value) && is_array($content[$key])) {
                            $content[$key] = array_merge($content[$key], $value);
                        } else {
                            $content[$key] = $value;
                        }
                    }
                }
                // Ignore invalid keys (non-string or empty)
            }
        }

        return $content;
    }

    /**
     * Set nested array value using dot notation with null safety
     */
    private function setNestedValue(array &$array, string $key, $value): void
    {
        // Split by '.'; walk the path creating arrays when missing or when existing value is not array
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $k) {
            // Guard against empty path segments; skip on invalid
            if (empty(trim($k))) {
                return;
            }

            // Ensure strict array type before descent
            if (! isset($current[$k]) || ! is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    /**
     * Deep merge minimal defaults into content to ensure required keys exist
     */
    public function deepMergeDefaults(array $content, string $audience): array
    {
        $defaults = $this->getDefaultContent($audience);

        return $this->deepMergeArrays($defaults, $content);
    }

    /**
     * Deep merge two arrays, preserving non-empty values from source array
     */
    private function deepMergeArrays(array $defaults, array $content): array
    {
        foreach ($defaults as $key => $defaultValue) {
            if (! array_key_exists($key, $content)) {
                // Key missing in content, use default
                $content[$key] = $defaultValue;
            } elseif (is_array($defaultValue) && is_array($content[$key])) {
                // Both are arrays, recurse
                $content[$key] = $this->deepMergeArrays($defaultValue, $content[$key]);
            } elseif (empty($content[$key]) && ! empty($defaultValue)) {
                // Content is empty but default has value, use default
                $content[$key] = $defaultValue;
            }
            // Otherwise keep existing content value
        }

        return $content;
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
                    'max_length' => ['headline' => 100, 'subtitle' => 200, 'description' => 500],
                ],
                'features' => [
                    'fields' => ['title', 'subtitle', 'items'],
                    'required' => ['title', 'items'],
                    'max_items' => 6,
                ],
                'testimonials' => [
                    'fields' => ['title', 'subtitle', 'items'],
                    'required' => ['title', 'items'],
                    'max_items' => 10,
                ],
                'pricing' => [
                    'fields' => ['title', 'subtitle', 'tiers'],
                    'required' => ['title', 'tiers'],
                    'max_tiers' => 4,
                ],
            ],
            'cache_duration' => 3600,
            'version_control' => true,
            'approval_workflow' => true,
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
                'subtitle' => 'Connect with alumni who can help you land your dream job',
            ],
            'reunion' => [
                'headline' => 'Reconnect and Advance Your Career',
                'subtitle' => 'Turn reunion connections into career opportunities',
            ],
            'graduation' => [
                'headline' => 'Welcome to Your Alumni Network!',
                'subtitle' => 'Start building professional connections that will shape your career',
            ],
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
                'verification_url' => 'https://example.com/soc2-verification',
            ],
            [
                'id' => '2',
                'name' => 'GDPR Compliant',
                'image' => '/images/badges/gdpr-compliant.png',
                'description' => 'Full compliance with the General Data Protection Regulation for EU data protection.',
                'verification_url' => 'https://example.com/gdpr-verification',
            ],
            [
                'id' => '3',
                'name' => 'ISO 27001',
                'image' => '/images/badges/iso-27001.png',
                'description' => 'ISO 27001 certified information security management system.',
                'verification_url' => 'https://example.com/iso-verification',
            ],
            [
                'id' => '4',
                'name' => 'Privacy Shield',
                'image' => '/images/badges/privacy-shield.png',
                'description' => 'EU-US Privacy Shield framework compliance for international data transfers.',
            ],
            [
                'id' => '5',
                'name' => 'SSL Secured',
                'image' => '/images/badges/ssl-secured.png',
                'description' => '256-bit SSL encryption protects all data in transit.',
            ],
            [
                'id' => '6',
                'name' => 'CCPA Compliant',
                'image' => '/images/badges/ccpa-compliant.png',
                'description' => 'California Consumer Privacy Act compliance for enhanced privacy rights.',
            ],
        ];

        $companyLogos = [
            [
                'id' => '1',
                'name' => 'Google',
                'logo' => '/images/companies/google-logo.png',
                'website' => 'https://google.com',
                'category' => 'Technology',
            ],
            [
                'id' => '2',
                'name' => 'Microsoft',
                'logo' => '/images/companies/microsoft-logo.png',
                'website' => 'https://microsoft.com',
                'category' => 'Technology',
            ],
            [
                'id' => '3',
                'name' => 'Apple',
                'logo' => '/images/companies/apple-logo.png',
                'website' => 'https://apple.com',
                'category' => 'Technology',
            ],
            [
                'id' => '4',
                'name' => 'Amazon',
                'logo' => '/images/companies/amazon-logo.png',
                'website' => 'https://amazon.com',
                'category' => 'Technology',
            ],
            [
                'id' => '5',
                'name' => 'Meta',
                'logo' => '/images/companies/meta-logo.png',
                'website' => 'https://meta.com',
                'category' => 'Technology',
            ],
            [
                'id' => '6',
                'name' => 'Netflix',
                'logo' => '/images/companies/netflix-logo.png',
                'website' => 'https://netflix.com',
                'category' => 'Entertainment',
            ],
            [
                'id' => '7',
                'name' => 'Tesla',
                'logo' => '/images/companies/tesla-logo.png',
                'website' => 'https://tesla.com',
                'category' => 'Automotive',
            ],
            [
                'id' => '8',
                'name' => 'Goldman Sachs',
                'logo' => '/images/companies/goldman-sachs-logo.png',
                'website' => 'https://goldmansachs.com',
                'category' => 'Finance',
            ],
        ];

        // Add audience-specific badges for institutional clients
        if ($audience === 'institutional') {
            $trustBadges[] = [
                'id' => '7',
                'name' => 'FERPA Compliant',
                'image' => '/images/badges/ferpa-compliant.png',
                'description' => 'Family Educational Rights and Privacy Act compliance for educational institutions.',
                'verification_url' => 'https://example.com/ferpa-verification',
            ];
        }

        return [
            'trust_badges' => $trustBadges,
            'company_logos' => $companyLogos,
        ];
    }

    /**
     * Get platform preview data including screenshots and tour steps
     */
    public function getPlatformPreviewData(string $audience): array
    {
        // Mock data for now - will be replaced with real database queries
        $screenshots = [
            [
                'id' => 'dashboard-desktop',
                'title' => 'Alumni Dashboard',
                'description' => 'Your personalized dashboard showing connections, opportunities, and recent activity.',
                'image' => '/images/screenshots/dashboard-desktop.png',
                'thumbnail' => '/images/screenshots/dashboard-desktop-thumb.png',
                'device' => 'desktop',
                'hotspots' => [
                    [
                        'x' => 25,
                        'y' => 30,
                        'title' => 'Connection Recommendations',
                        'description' => 'AI-powered suggestions for new alumni connections based on your profile and interests.',
                        'feature' => 'networking',
                        'action' => 'view_connections',
                    ],
                    [
                        'x' => 75,
                        'y' => 45,
                        'title' => 'Job Opportunities',
                        'description' => 'Exclusive job postings shared within your alumni network.',
                        'feature' => 'jobs',
                        'action' => 'view_jobs',
                    ],
                    [
                        'x' => 50,
                        'y' => 70,
                        'title' => 'Upcoming Events',
                        'description' => 'Alumni events and networking opportunities in your area.',
                        'feature' => 'events',
                        'action' => 'view_events',
                    ],
                ],
                'features' => [
                    [
                        'id' => 'personalized_feed',
                        'title' => 'Personalized Activity Feed',
                        'description' => 'Stay updated with relevant alumni news and opportunities',
                    ],
                    [
                        'id' => 'quick_actions',
                        'title' => 'Quick Actions',
                        'description' => 'Access key features with one-click shortcuts',
                    ],
                ],
            ],
            [
                'id' => 'networking-desktop',
                'title' => 'Alumni Network',
                'description' => 'Browse and connect with alumni based on shared interests, location, and career goals.',
                'image' => '/images/screenshots/networking-desktop.png',
                'device' => 'desktop',
                'hotspots' => [
                    [
                        'x' => 20,
                        'y' => 25,
                        'title' => 'Search Filters',
                        'description' => 'Filter alumni by industry, location, graduation year, and more.',
                        'feature' => 'search',
                    ],
                    [
                        'x' => 60,
                        'y' => 40,
                        'title' => 'Alumni Profiles',
                        'description' => 'View detailed profiles with career history and interests.',
                        'feature' => 'profiles',
                    ],
                ],
                'features' => [
                    [
                        'id' => 'advanced_search',
                        'title' => 'Advanced Search',
                        'description' => 'Find alumni using multiple criteria and filters',
                    ],
                    [
                        'id' => 'connection_requests',
                        'title' => 'Smart Connection Requests',
                        'description' => 'Send personalized connection requests with context',
                    ],
                ],
            ],
            [
                'id' => 'dashboard-mobile',
                'title' => 'Mobile Dashboard',
                'description' => 'Access your alumni network on the go with our mobile-optimized interface.',
                'image' => '/images/screenshots/dashboard-mobile.png',
                'device' => 'mobile',
                'hotspots' => [
                    [
                        'x' => 50,
                        'y' => 30,
                        'title' => 'Mobile Navigation',
                        'description' => 'Easy access to all platform features from your mobile device.',
                        'feature' => 'navigation',
                    ],
                ],
                'features' => [
                    [
                        'id' => 'mobile_optimized',
                        'title' => 'Mobile Optimized',
                        'description' => 'Full functionality optimized for mobile devices',
                    ],
                ],
            ],
        ];

        $tourSteps = [
            [
                'id' => 'welcome',
                'title' => 'Welcome to Your Alumni Platform',
                'description' => 'Let\'s take a quick tour of the key features that will help you advance your career.',
                'screenshot' => '/images/tour/step-1-welcome.png',
                'callouts' => [
                    [
                        'x' => 50,
                        'y' => 20,
                        'title' => 'Your Dashboard',
                        'description' => 'This is your personalized homepage',
                        'type' => 'info',
                    ],
                ],
                'duration' => 5000,
            ],
            [
                'id' => 'networking',
                'title' => 'Connect with Alumni',
                'description' => 'Discover and connect with alumni who share your interests and career goals.',
                'screenshot' => '/images/tour/step-2-networking.png',
                'callouts' => [
                    [
                        'x' => 30,
                        'y' => 40,
                        'title' => 'Smart Recommendations',
                        'description' => 'AI-powered connection suggestions',
                        'type' => 'highlight',
                    ],
                ],
                'duration' => 7000,
            ],
            [
                'id' => 'opportunities',
                'title' => 'Discover Opportunities',
                'description' => 'Access exclusive job postings and career opportunities shared by your network.',
                'screenshot' => '/images/tour/step-3-opportunities.png',
                'callouts' => [
                    [
                        'x' => 70,
                        'y' => 50,
                        'title' => 'Exclusive Jobs',
                        'description' => 'Jobs shared only within your alumni network',
                        'type' => 'highlight',
                    ],
                ],
                'duration' => 6000,
            ],
        ];

        // Customize for institutional audience
        if ($audience === 'institutional') {
            $screenshots = [
                [
                    'id' => 'admin-dashboard-desktop',
                    'title' => 'Admin Dashboard',
                    'description' => 'Comprehensive analytics and management tools for your alumni community.',
                    'image' => '/images/screenshots/admin-dashboard-desktop.png',
                    'device' => 'desktop',
                    'hotspots' => [
                        [
                            'x' => 25,
                            'y' => 30,
                            'title' => 'Engagement Analytics',
                            'description' => 'Real-time metrics showing alumni engagement and activity.',
                            'feature' => 'analytics',
                        ],
                        [
                            'x' => 75,
                            'y' => 45,
                            'title' => 'Event Management',
                            'description' => 'Create and manage alumni events with RSVP tracking.',
                            'feature' => 'events',
                        ],
                    ],
                    'features' => [
                        [
                            'id' => 'real_time_analytics',
                            'title' => 'Real-time Analytics',
                            'description' => 'Monitor engagement metrics and community growth',
                        ],
                        [
                            'id' => 'event_management',
                            'title' => 'Event Management',
                            'description' => 'Comprehensive event planning and tracking tools',
                        ],
                    ],
                ],
                [
                    'id' => 'branded-app-desktop',
                    'title' => 'Branded Mobile App',
                    'description' => 'Deploy your own custom-branded mobile app for alumni engagement.',
                    'image' => '/images/screenshots/branded-app-desktop.png',
                    'device' => 'desktop',
                    'hotspots' => [
                        [
                            'x' => 40,
                            'y' => 35,
                            'title' => 'App Customization',
                            'description' => 'Customize colors, branding, and features for your institution.',
                            'feature' => 'branding',
                        ],
                    ],
                    'features' => [
                        [
                            'id' => 'white_label',
                            'title' => 'White Label Solution',
                            'description' => 'Complete branding customization for your institution',
                        ],
                    ],
                ],
            ];

            $tourSteps = [
                [
                    'id' => 'admin_welcome',
                    'title' => 'Welcome to Your Alumni Management Platform',
                    'description' => 'Let\'s explore the tools that will help you engage your alumni community.',
                    'screenshot' => '/images/tour/admin-step-1-welcome.png',
                    'callouts' => [
                        [
                            'x' => 50,
                            'y' => 20,
                            'title' => 'Admin Dashboard',
                            'description' => 'Your central hub for alumni management',
                            'type' => 'info',
                        ],
                    ],
                    'duration' => 5000,
                ],
                [
                    'id' => 'analytics',
                    'title' => 'Powerful Analytics',
                    'description' => 'Monitor engagement, track events, and measure the success of your programs.',
                    'screenshot' => '/images/tour/admin-step-2-analytics.png',
                    'callouts' => [
                        [
                            'x' => 30,
                            'y' => 40,
                            'title' => 'Real-time Metrics',
                            'description' => 'Live engagement and participation data',
                            'type' => 'highlight',
                        ],
                    ],
                    'duration' => 7000,
                ],
            ];
        }

        return [
            'screenshots' => $screenshots,
            'tour_steps' => $tourSteps,
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
            'Media' => 0.16,
        ];

        return $multipliers[$industry] ?? 0.15;
    }

    /**
     * Get enterprise metrics and ROI data for institutional clients
     */
    public function getEnterpriseMetrics(array $params = []): array
    {
        $timeframe = $params['timeframe'] ?? '12_months';
        $requestedMetrics = $params['metrics'] ?? ['engagement', 'financial', 'operational', 'growth'];

        // Mock enterprise metrics data - will be replaced with real database queries
        $allMetrics = [
            [
                'id' => 'engagement_rate',
                'name' => 'Alumni Engagement Rate',
                'category' => 'engagement',
                'metric' => 'engagement',
                'beforeValue' => 25,
                'afterValue' => 75,
                'improvementPercentage' => 200,
                'timeframe' => $timeframe,
                'verified' => true,
                'unit' => 'percentage',
            ],
            [
                'id' => 'event_attendance',
                'name' => 'Event Attendance',
                'category' => 'operational',
                'metric' => 'event_attendance',
                'beforeValue' => 200,
                'afterValue' => 800,
                'improvementPercentage' => 300,
                'timeframe' => $timeframe,
                'verified' => true,
                'unit' => 'count',
            ],
            [
                'id' => 'donation_revenue',
                'name' => 'Annual Donation Revenue',
                'category' => 'financial',
                'metric' => 'donations',
                'beforeValue' => 500000,
                'afterValue' => 1250000,
                'improvementPercentage' => 150,
                'timeframe' => $timeframe,
                'verified' => true,
                'unit' => 'currency',
            ],
            [
                'id' => 'app_downloads',
                'name' => 'Mobile App Downloads',
                'category' => 'growth',
                'metric' => 'app_downloads',
                'beforeValue' => 0,
                'afterValue' => 15000,
                'improvementPercentage' => 100,
                'timeframe' => $timeframe,
                'verified' => true,
                'unit' => 'count',
            ],
            [
                'id' => 'response_time',
                'name' => 'Admin Response Time',
                'category' => 'operational',
                'metric' => 'response_time',
                'beforeValue' => 72,
                'afterValue' => 24,
                'improvementPercentage' => 67,
                'timeframe' => $timeframe,
                'verified' => false,
                'unit' => 'days',
            ],
            [
                'id' => 'cost_per_engagement',
                'name' => 'Cost Per Alumni Engagement',
                'category' => 'financial',
                'metric' => 'cost_efficiency',
                'beforeValue' => 45,
                'afterValue' => 18,
                'improvementPercentage' => 60,
                'timeframe' => $timeframe,
                'verified' => true,
                'unit' => 'currency',
            ],
        ];

        // Filter metrics based on requested categories
        $filteredMetrics = array_filter($allMetrics, function ($metric) use ($requestedMetrics) {
            return in_array($metric['category'], $requestedMetrics);
        });

        // Calculate ROI data
        $roiData = [
            'percentage' => 350,
            'investment' => 75000,
            'return' => 262500,
            'timeframe' => str_replace('_', ' ', $timeframe),
        ];

        return [
            'metrics' => array_values($filteredMetrics),
            'roi_data' => $roiData,
            'summary' => [
                'total_metrics' => count($filteredMetrics),
                'verified_metrics' => count(array_filter($filteredMetrics, fn ($m) => $m['verified'])),
                'average_improvement' => round(array_sum(array_column($filteredMetrics, 'improvementPercentage')) / count($filteredMetrics)),
            ],
        ];
    }

    /**
     * Get institutional before/after comparison data
     */
    public function getInstitutionalComparison(array $params = []): array
    {
        $institutionId = $params['institution_id'] ?? 'stanford_university';
        $caseStudyId = $params['case_study_id'] ?? 'stanford_digital_transformation';

        // Mock institutional comparison data
        return [
            'title' => 'Digital Transformation Success',
            'subtitle' => 'How Stanford University revolutionized alumni engagement',
            'institution_name' => 'Stanford University',
            'institution_type' => 'university',
            'institution_logo' => '/images/institutions/stanford-logo.png',
            'alumni_count' => 250000,
            'before_metrics' => [
                [
                    'key' => 'engagement',
                    'label' => 'Alumni Engagement Rate',
                    'value' => 25,
                    'unit' => 'percentage',
                ],
                [
                    'key' => 'events',
                    'label' => 'Monthly Events',
                    'value' => 5,
                    'unit' => 'count',
                ],
                [
                    'key' => 'donations',
                    'label' => 'Annual Donations',
                    'value' => 500000,
                    'unit' => 'currency',
                ],
                [
                    'key' => 'app_usage',
                    'label' => 'Digital Platform Usage',
                    'value' => 0,
                    'unit' => 'percentage',
                ],
            ],
            'after_metrics' => [
                [
                    'key' => 'engagement',
                    'label' => 'Alumni Engagement Rate',
                    'value' => 75,
                    'unit' => 'percentage',
                ],
                [
                    'key' => 'events',
                    'label' => 'Monthly Events',
                    'value' => 20,
                    'unit' => 'count',
                ],
                [
                    'key' => 'donations',
                    'label' => 'Annual Donations',
                    'value' => 1250000,
                    'unit' => 'currency',
                ],
                [
                    'key' => 'app_usage',
                    'label' => 'Digital Platform Usage',
                    'value' => 85,
                    'unit' => 'percentage',
                ],
            ],
            'before_challenges' => [
                'Low alumni participation in events',
                'Limited digital engagement channels',
                'Difficulty tracking alumni career progress',
                'Inefficient communication methods',
                'Lack of mobile accessibility',
            ],
            'after_benefits' => [
                'Increased alumni participation by 200%',
                'Streamlined digital communication platform',
                'Real-time alumni career tracking',
                'Automated engagement workflows',
                'Mobile-first alumni experience',
            ],
            'timeframe' => '18 months',
            'impact_summary' => 'Stanford University achieved a 200% increase in alumni engagement through strategic digital transformation, resulting in higher event attendance, increased donations, and improved alumni satisfaction.',
        ];
    }

    /**
     * Get implementation timeline data for institutional projects
     */
    public function getImplementationTimeline(array $params = []): array
    {
        $institutionType = $params['institution_type'] ?? 'university';
        $alumniCount = $params['alumni_count'] ?? 50000;
        $complexity = $params['complexity'] ?? 'standard';

        // Adjust timeline based on complexity and size
        $baseWeeks = 12;
        $complexityMultiplier = [
            'basic' => 0.8,
            'standard' => 1.0,
            'advanced' => 1.3,
            'enterprise' => 1.6,
        ];

        $sizeMultiplier = $alumniCount > 100000 ? 1.2 : ($alumniCount > 25000 ? 1.1 : 1.0);
        $totalWeeks = round($baseWeeks * $complexityMultiplier[$complexity] * $sizeMultiplier);

        return [
            'title' => 'Implementation Timeline',
            'subtitle' => 'Step-by-step project execution plan',
            'total_duration' => "{$totalWeeks} weeks",
            'phases' => [
                [
                    'id' => '1',
                    'name' => 'Discovery & Planning',
                    'description' => 'Initial assessment and project planning phase',
                    'duration' => '3-4 weeks',
                    'deliverables' => [
                        'Project plan and timeline',
                        'Technical specifications',
                        'Resource allocation plan',
                        'Risk assessment',
                        'Stakeholder alignment',
                    ],
                    'dependencies' => [],
                    'milestones' => [
                        [
                            'id' => '1-1',
                            'name' => 'Kickoff meeting',
                            'description' => 'Project initiation and team introductions',
                            'dueDate' => 'Week 1',
                            'status' => 'completed',
                        ],
                        [
                            'id' => '1-2',
                            'name' => 'Requirements gathering',
                            'description' => 'Detailed requirements collection and analysis',
                            'dueDate' => 'Week 2',
                            'status' => 'completed',
                        ],
                        [
                            'id' => '1-3',
                            'name' => 'Technical architecture review',
                            'description' => 'System architecture and integration planning',
                            'dueDate' => 'Week 3',
                            'status' => 'completed',
                        ],
                    ],
                    'status' => 'completed',
                ],
                [
                    'id' => '2',
                    'name' => 'Platform Configuration',
                    'description' => 'Core platform setup and customization',
                    'duration' => '4-6 weeks',
                    'deliverables' => [
                        'Configured platform environment',
                        'Custom branding implementation',
                        'Initial data migration',
                        'Basic integrations setup',
                        'Security configuration',
                    ],
                    'dependencies' => ['Discovery & Planning'],
                    'milestones' => [
                        [
                            'id' => '2-1',
                            'name' => 'Environment setup',
                            'description' => 'Production and staging environments configured',
                            'dueDate' => 'Week 5',
                            'status' => 'in_progress',
                        ],
                        [
                            'id' => '2-2',
                            'name' => 'Branding implementation',
                            'description' => 'Custom branding and theming applied',
                            'dueDate' => 'Week 7',
                            'status' => 'pending',
                        ],
                        [
                            'id' => '2-3',
                            'name' => 'Data migration',
                            'description' => 'Alumni data imported and validated',
                            'dueDate' => 'Week 8',
                            'status' => 'pending',
                        ],
                    ],
                    'status' => 'in_progress',
                ],
                [
                    'id' => '3',
                    'name' => 'Mobile App Development',
                    'description' => 'Custom branded mobile application development',
                    'duration' => '6-8 weeks',
                    'deliverables' => [
                        'iOS and Android applications',
                        'App store submissions',
                        'Push notification setup',
                        'Offline functionality',
                        'App analytics integration',
                    ],
                    'dependencies' => ['Platform Configuration'],
                    'milestones' => [
                        [
                            'id' => '3-1',
                            'name' => 'App development kickoff',
                            'description' => 'Mobile development team onboarded',
                            'dueDate' => 'Week 9',
                            'status' => 'pending',
                        ],
                        [
                            'id' => '3-2',
                            'name' => 'Beta app release',
                            'description' => 'Internal testing version available',
                            'dueDate' => 'Week 12',
                            'status' => 'pending',
                        ],
                        [
                            'id' => '3-3',
                            'name' => 'App store approval',
                            'description' => 'Apps approved and published',
                            'dueDate' => 'Week 15',
                            'status' => 'pending',
                        ],
                    ],
                    'status' => 'pending',
                ],
                [
                    'id' => '4',
                    'name' => 'Testing & Quality Assurance',
                    'description' => 'Comprehensive testing and bug fixes',
                    'duration' => '2-3 weeks',
                    'deliverables' => [
                        'Test plan execution',
                        'Bug fixes and optimizations',
                        'Performance testing',
                        'Security audit',
                        'User acceptance testing',
                    ],
                    'dependencies' => ['Mobile App Development'],
                    'milestones' => [
                        [
                            'id' => '4-1',
                            'name' => 'System testing complete',
                            'description' => 'All functional testing completed',
                            'dueDate' => 'Week 16',
                            'status' => 'pending',
                        ],
                        [
                            'id' => '4-2',
                            'name' => 'User acceptance testing',
                            'description' => 'Stakeholder testing and approval',
                            'dueDate' => 'Week 17',
                            'status' => 'pending',
                        ],
                    ],
                    'status' => 'pending',
                ],
                [
                    'id' => '5',
                    'name' => 'Launch & Training',
                    'description' => 'Go-live preparation and user training',
                    'duration' => '2-3 weeks',
                    'deliverables' => [
                        'Staff training completion',
                        'Launch communication plan',
                        'Support documentation',
                        'Monitoring setup',
                        'Success metrics baseline',
                    ],
                    'dependencies' => ['Testing & Quality Assurance'],
                    'milestones' => [
                        [
                            'id' => '5-1',
                            'name' => 'Staff training complete',
                            'description' => 'All administrators trained on platform',
                            'dueDate' => 'Week 18',
                            'status' => 'pending',
                        ],
                        [
                            'id' => '5-2',
                            'name' => 'Soft launch',
                            'description' => 'Limited user group launch',
                            'dueDate' => 'Week 19',
                            'status' => 'pending',
                        ],
                        [
                            'id' => '5-3',
                            'name' => 'Full launch',
                            'description' => 'Platform available to all alumni',
                            'dueDate' => 'Week 20',
                            'status' => 'pending',
                        ],
                    ],
                    'status' => 'pending',
                ],
            ],
        ];
    }

    /**
     * Get success metrics tracking data for institutions
     */
    public function getSuccessMetricsTracking(array $params = []): array
    {
        $institutionId = $params['institution_id'] ?? null;
        $dateFrom = $params['date_from'] ?? now()->subMonths(6);
        $dateTo = $params['date_to'] ?? now();
        $requestedMetrics = $params['metrics'] ?? null;

        // Mock success metrics tracking data
        $allMetrics = [
            [
                'id' => 'alumni_engagement',
                'name' => 'Alumni Engagement Rate',
                'category' => 'engagement',
                'current_value' => 75,
                'target_value' => 80,
                'unit' => 'percentage',
                'trend' => 'up',
                'trending' => true,
                'change_from_previous' => 12,
                'verified' => true,
            ],
            [
                'id' => 'event_attendance',
                'name' => 'Monthly Event Attendance',
                'category' => 'operational',
                'current_value' => 850,
                'target_value' => 1000,
                'unit' => 'count',
                'trend' => 'up',
                'trending' => true,
                'change_from_previous' => 25,
                'verified' => true,
            ],
            [
                'id' => 'app_downloads',
                'name' => 'Mobile App Downloads',
                'category' => 'growth',
                'current_value' => 15000,
                'target_value' => 20000,
                'unit' => 'count',
                'trend' => 'up',
                'trending' => true,
                'change_from_previous' => 35,
                'verified' => true,
            ],
            [
                'id' => 'donation_revenue',
                'name' => 'Quarterly Donation Revenue',
                'category' => 'financial',
                'current_value' => 312500,
                'target_value' => 350000,
                'unit' => 'currency',
                'trend' => 'up',
                'trending' => true,
                'change_from_previous' => 18,
                'verified' => false,
            ],
            [
                'id' => 'response_time',
                'name' => 'Admin Response Time',
                'category' => 'operational',
                'current_value' => 24,
                'target_value' => 12,
                'unit' => 'days',
                'trend' => 'down',
                'trending' => true,
                'change_from_previous' => -33,
                'verified' => true,
            ],
            [
                'id' => 'user_satisfaction',
                'name' => 'User Satisfaction Score',
                'category' => 'engagement',
                'current_value' => 87,
                'target_value' => 90,
                'unit' => 'percentage',
                'trend' => 'stable',
                'trending' => false,
                'change_from_previous' => 2,
                'verified' => true,
            ],
        ];

        // Filter metrics if specific ones are requested
        if ($requestedMetrics) {
            $allMetrics = array_filter($allMetrics, function ($metric) use ($requestedMetrics) {
                return in_array($metric['id'], $requestedMetrics);
            });
        }

        // Mock insights
        $insights = [
            [
                'id' => 'engagement_trend',
                'title' => 'Strong Engagement Growth',
                'description' => 'Alumni engagement has increased by 12% this quarter, driven by mobile app adoption and improved event programming.',
                'type' => 'positive',
            ],
            [
                'id' => 'app_adoption',
                'title' => 'Mobile App Success',
                'description' => 'Mobile app downloads exceeded expectations by 35%, indicating strong alumni interest in mobile-first experiences.',
                'type' => 'positive',
            ],
            [
                'id' => 'response_improvement',
                'title' => 'Response Time Optimization',
                'description' => 'Admin response times have improved significantly but still need work to reach the 12-day target.',
                'type' => 'warning',
            ],
        ];

        return [
            'title' => 'Success Metrics Tracking',
            'subtitle' => 'Real-time performance monitoring and insights',
            'metrics' => array_values($allMetrics),
            'insights' => $insights,
            'last_updated' => now(),
            'summary' => [
                'metrics_on_track' => count(array_filter($allMetrics, function ($m) {
                    $progress = ($m['current_value'] / $m['target_value']) * 100;

                    return $progress >= 80 && $progress < 100;
                })),
                'metrics_exceeding' => count(array_filter($allMetrics, function ($m) {
                    return ($m['current_value'] / $m['target_value']) * 100 >= 100;
                })),
                'metrics_behind' => count(array_filter($allMetrics, function ($m) {
                    return ($m['current_value'] / $m['target_value']) * 100 < 80;
                })),
                'average_progress' => round(array_sum(array_map(function ($m) {
                    return min(100, ($m['current_value'] / $m['target_value']) * 100);
                }, $allMetrics)) / count($allMetrics)),
            ],
        ];
    }
}
