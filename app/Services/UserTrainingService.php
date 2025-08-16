<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class UserTrainingService
{
    /**
     * Get role-specific user guides
     */
    public function getUserGuides(string $role): Collection
    {
        return Cache::remember("user_guides_{$role}", 3600, function () use ($role) {
            $guides = collect();

            switch ($role) {
                case 'graduate':
                case 'alumni':
                    $guides = $this->getAlumniGuides();
                    break;
                case 'institution_admin':
                    $guides = $this->getInstitutionAdminGuides();
                    break;
                case 'super_admin':
                    $guides = $this->getSuperAdminGuides();
                    break;
                case 'employer':
                    $guides = $this->getEmployerGuides();
                    break;
                default:
                    $guides = $this->getGeneralGuides();
            }

            return $guides;
        });
    }

    /**
     * Get video tutorials for a specific role
     */
    public function getVideoTutorials(string $role): Collection
    {
        return Cache::remember("video_tutorials_{$role}", 3600, function () use ($role) {
            $tutorials = collect();

            switch ($role) {
                case 'graduate':
                case 'alumni':
                    $tutorials = $this->getAlumniVideoTutorials();
                    break;
                case 'institution_admin':
                    $tutorials = $this->getInstitutionAdminVideoTutorials();
                    break;
                case 'super_admin':
                    $tutorials = $this->getSuperAdminVideoTutorials();
                    break;
                case 'employer':
                    $tutorials = $this->getEmployerVideoTutorials();
                    break;
            }

            return $tutorials;
        });
    }

    /**
     * Get onboarding sequence for a user role
     */
    public function getOnboardingSequence(string $role): array
    {
        return Cache::remember("onboarding_sequence_{$role}", 3600, function () use ($role) {
            switch ($role) {
                case 'graduate':
                case 'alumni':
                    return $this->getAlumniOnboardingSequence();
                case 'institution_admin':
                    return $this->getInstitutionAdminOnboardingSequence();
                case 'super_admin':
                    return $this->getSuperAdminOnboardingSequence();
                case 'employer':
                    return $this->getEmployerOnboardingSequence();
                default:
                    return $this->getGeneralOnboardingSequence();
            }
        });
    }

    /**
     * Get FAQ items for a specific role
     */
    public function getFAQs(string $role): Collection
    {
        return Cache::remember("faqs_{$role}", 3600, function () use ($role) {
            $faqs = collect();

            switch ($role) {
                case 'graduate':
                case 'alumni':
                    $faqs = $this->getAlumniFAQs();
                    break;
                case 'institution_admin':
                    $faqs = $this->getInstitutionAdminFAQs();
                    break;
                case 'super_admin':
                    $faqs = $this->getSuperAdminFAQs();
                    break;
                case 'employer':
                    $faqs = $this->getEmployerFAQs();
                    break;
            }

            return $faqs->merge($this->getGeneralFAQs());
        });
    }

    /**
     * Get training progress for a user
     */
    public function getTrainingProgress(User $user): array
    {
        $role = $user->roles->first()?->name ?? 'alumni';
        $onboardingSequence = $this->getOnboardingSequence($role);
        $completedSteps = $user->onboarding_progress ?? [];

        $totalSteps = count($onboardingSequence);
        $completedCount = count(array_filter($completedSteps, fn ($step) => $step['completed'] ?? false));

        return [
            'total_steps' => $totalSteps,
            'completed_steps' => $completedCount,
            'completion_percentage' => $totalSteps > 0 ? round(($completedCount / $totalSteps) * 100) : 0,
            'current_step' => $this->getCurrentStep($onboardingSequence, $completedSteps),
            'next_recommended_action' => $this->getNextRecommendedAction($role, $completedSteps),
        ];
    }

    /**
     * Mark training step as completed
     */
    public function markStepCompleted(User $user, string $stepId): bool
    {
        $progress = $user->onboarding_progress ?? [];
        $progress[$stepId] = [
            'completed' => true,
            'completed_at' => now()->toISOString(),
        ];

        $user->update(['onboarding_progress' => $progress]);

        return true;
    }

    /**
     * Get alumni-specific user guides
     */
    private function getAlumniGuides(): Collection
    {
        return collect([
            [
                'id' => 'getting-started',
                'title' => 'Getting Started Guide',
                'description' => 'Complete your profile and connect with your alumni network',
                'category' => 'basics',
                'estimated_time' => '10 minutes',
                'sections' => [
                    'Complete Your Profile',
                    'Upload Professional Photo',
                    'Add Career Timeline',
                    'Connect with Classmates',
                    'Join Relevant Groups',
                ],
                'icon' => 'rocket',
            ],
            [
                'id' => 'networking-guide',
                'title' => 'Networking & Connections',
                'description' => 'Build meaningful professional relationships through the platform',
                'category' => 'networking',
                'estimated_time' => '15 minutes',
                'sections' => [
                    'Finding Alumni in Your Industry',
                    'Sending Connection Requests',
                    'Engaging with Posts',
                    'Participating in Discussions',
                    'Attending Virtual Events',
                ],
                'icon' => 'users',
            ],
            [
                'id' => 'career-development',
                'title' => 'Career Development Tools',
                'description' => 'Leverage the platform for career growth and opportunities',
                'category' => 'career',
                'estimated_time' => '20 minutes',
                'sections' => [
                    'Using the Job Matching System',
                    'Finding Mentors',
                    'Requesting Referrals',
                    'Tracking Career Progress',
                    'Sharing Achievements',
                ],
                'icon' => 'briefcase',
            ],
            [
                'id' => 'social-features',
                'title' => 'Social Timeline & Sharing',
                'description' => 'Share updates and engage with your alumni community',
                'category' => 'social',
                'estimated_time' => '12 minutes',
                'sections' => [
                    'Creating Engaging Posts',
                    'Using Hashtags and Mentions',
                    'Sharing Career Updates',
                    'Celebrating Achievements',
                    'Privacy Settings',
                ],
                'icon' => 'chat',
            ],
        ]);
    }

    /**
     * Get institution admin user guides
     */
    private function getInstitutionAdminGuides(): Collection
    {
        return collect([
            [
                'id' => 'admin-dashboard',
                'title' => 'Administrator Dashboard',
                'description' => 'Navigate and utilize your admin dashboard effectively',
                'category' => 'basics',
                'estimated_time' => '15 minutes',
                'sections' => [
                    'Dashboard Overview',
                    'Key Metrics Understanding',
                    'Quick Actions',
                    'Navigation Tips',
                    'Customizing Views',
                ],
                'icon' => 'chart',
            ],
            [
                'id' => 'alumni-management',
                'title' => 'Alumni Management',
                'description' => 'Manage your institution\'s alumni database and engagement',
                'category' => 'management',
                'estimated_time' => '25 minutes',
                'sections' => [
                    'Importing Alumni Data',
                    'Managing Alumni Profiles',
                    'Bulk Operations',
                    'Data Verification',
                    'Export Functions',
                ],
                'icon' => 'users',
            ],
            [
                'id' => 'events-fundraising',
                'title' => 'Events & Fundraising',
                'description' => 'Create and manage events and fundraising campaigns',
                'category' => 'engagement',
                'estimated_time' => '30 minutes',
                'sections' => [
                    'Creating Alumni Events',
                    'Managing RSVPs',
                    'Setting Up Fundraising Campaigns',
                    'Tracking Donations',
                    'Donor Recognition',
                ],
                'icon' => 'calendar',
            ],
            [
                'id' => 'analytics-reporting',
                'title' => 'Analytics & Reporting',
                'description' => 'Generate insights and reports on alumni engagement',
                'category' => 'analytics',
                'estimated_time' => '20 minutes',
                'sections' => [
                    'Understanding Analytics Dashboard',
                    'Generating Custom Reports',
                    'Tracking Engagement Metrics',
                    'Career Outcome Analysis',
                    'Exporting Data',
                ],
                'icon' => 'chart',
            ],
        ]);
    }

    /**
     * Get super admin user guides
     */
    private function getSuperAdminGuides(): Collection
    {
        return collect([
            [
                'id' => 'system-administration',
                'title' => 'System Administration',
                'description' => 'Manage the entire platform and all institutions',
                'category' => 'administration',
                'estimated_time' => '45 minutes',
                'sections' => [
                    'Platform Overview',
                    'Institution Management',
                    'User Role Management',
                    'System Configuration',
                    'Security Settings',
                ],
                'icon' => 'shield',
            ],
            [
                'id' => 'performance-monitoring',
                'title' => 'Performance Monitoring',
                'description' => 'Monitor system performance and health',
                'category' => 'monitoring',
                'estimated_time' => '30 minutes',
                'sections' => [
                    'System Health Dashboard',
                    'Performance Metrics',
                    'Error Monitoring',
                    'Resource Usage',
                    'Alerting Setup',
                ],
                'icon' => 'monitor',
            ],
        ]);
    }

    /**
     * Get employer user guides
     */
    private function getEmployerGuides(): Collection
    {
        return collect([
            [
                'id' => 'employer-setup',
                'title' => 'Employer Account Setup',
                'description' => 'Set up your company profile and start recruiting',
                'category' => 'setup',
                'estimated_time' => '20 minutes',
                'sections' => [
                    'Company Profile Creation',
                    'Verification Process',
                    'Setting Up Job Posting Templates',
                    'Understanding Alumni Networks',
                    'Compliance Guidelines',
                ],
                'icon' => 'building',
            ],
            [
                'id' => 'talent-acquisition',
                'title' => 'Talent Acquisition',
                'description' => 'Find and recruit top alumni talent',
                'category' => 'recruiting',
                'estimated_time' => '25 minutes',
                'sections' => [
                    'Searching Alumni Database',
                    'Creating Job Postings',
                    'Managing Applications',
                    'Leveraging Alumni Networks',
                    'Interview Scheduling',
                ],
                'icon' => 'briefcase',
            ],
        ]);
    }

    /**
     * Get general user guides
     */
    private function getGeneralGuides(): Collection
    {
        return collect([
            [
                'id' => 'platform-basics',
                'title' => 'Platform Basics',
                'description' => 'Learn the fundamentals of using the alumni platform',
                'category' => 'basics',
                'estimated_time' => '10 minutes',
                'sections' => [
                    'Navigation Overview',
                    'Account Settings',
                    'Privacy Controls',
                    'Notification Preferences',
                    'Getting Help',
                ],
                'icon' => 'info',
            ],
        ]);
    }

    /**
     * Get alumni video tutorials
     */
    private function getAlumniVideoTutorials(): Collection
    {
        return collect([
            [
                'id' => 'profile-setup-video',
                'title' => 'Setting Up Your Alumni Profile',
                'description' => 'Step-by-step guide to creating an impressive alumni profile',
                'duration' => '8:30',
                'thumbnail' => '/images/tutorials/profile-setup-thumb.jpg',
                'video_url' => '/videos/tutorials/profile-setup.mp4',
                'category' => 'getting-started',
                'topics' => ['Profile Creation', 'Photo Upload', 'Career Timeline', 'Skills'],
            ],
            [
                'id' => 'networking-video',
                'title' => 'Networking Like a Pro',
                'description' => 'Master the art of professional networking on the platform',
                'duration' => '12:15',
                'thumbnail' => '/images/tutorials/networking-thumb.jpg',
                'video_url' => '/videos/tutorials/networking.mp4',
                'category' => 'networking',
                'topics' => ['Finding Alumni', 'Connection Requests', 'Messaging', 'Events'],
            ],
            [
                'id' => 'job-search-video',
                'title' => 'Smart Job Search Strategies',
                'description' => 'Use AI-powered job matching and network referrals effectively',
                'duration' => '15:45',
                'thumbnail' => '/images/tutorials/job-search-thumb.jpg',
                'video_url' => '/videos/tutorials/job-search.mp4',
                'category' => 'career',
                'topics' => ['Job Matching', 'Referrals', 'Applications', 'Interview Prep'],
            ],
        ]);
    }

    /**
     * Get institution admin video tutorials
     */
    private function getInstitutionAdminVideoTutorials(): Collection
    {
        return collect([
            [
                'id' => 'admin-overview-video',
                'title' => 'Administrator Dashboard Overview',
                'description' => 'Complete walkthrough of the admin dashboard and key features',
                'duration' => '18:20',
                'thumbnail' => '/images/tutorials/admin-overview-thumb.jpg',
                'video_url' => '/videos/tutorials/admin-overview.mp4',
                'category' => 'administration',
                'topics' => ['Dashboard Navigation', 'Analytics', 'User Management', 'Settings'],
            ],
            [
                'id' => 'data-import-video',
                'title' => 'Alumni Data Import & Management',
                'description' => 'Learn to import, clean, and manage alumni data effectively',
                'duration' => '22:10',
                'thumbnail' => '/images/tutorials/data-import-thumb.jpg',
                'video_url' => '/videos/tutorials/data-import.mp4',
                'category' => 'data-management',
                'topics' => ['CSV Import', 'Data Validation', 'Bulk Updates', 'Export Functions'],
            ],
        ]);
    }

    /**
     * Get super admin video tutorials
     */
    private function getSuperAdminVideoTutorials(): Collection
    {
        return collect([
            [
                'id' => 'system-admin-video',
                'title' => 'System Administration Masterclass',
                'description' => 'Complete guide to managing the entire platform',
                'duration' => '35:00',
                'thumbnail' => '/images/tutorials/system-admin-thumb.jpg',
                'video_url' => '/videos/tutorials/system-admin.mp4',
                'category' => 'system-administration',
                'topics' => ['Institution Management', 'User Roles', 'Security', 'Performance'],
            ],
        ]);
    }

    /**
     * Get employer video tutorials
     */
    private function getEmployerVideoTutorials(): Collection
    {
        return collect([
            [
                'id' => 'employer-setup-video',
                'title' => 'Employer Account Setup',
                'description' => 'Get your company verified and start recruiting alumni',
                'duration' => '14:30',
                'thumbnail' => '/images/tutorials/employer-setup-thumb.jpg',
                'video_url' => '/videos/tutorials/employer-setup.mp4',
                'category' => 'setup',
                'topics' => ['Account Verification', 'Company Profile', 'Job Posting', 'Compliance'],
            ],
        ]);
    }

    /**
     * Get alumni onboarding sequence
     */
    private function getAlumniOnboardingSequence(): array
    {
        return [
            [
                'id' => 'welcome',
                'title' => 'Welcome to Your Alumni Network',
                'description' => 'Discover how this platform will help you connect, grow, and succeed',
                'type' => 'introduction',
                'target' => null,
                'icon' => 'heart',
                'tips' => [
                    'This platform connects you with thousands of alumni worldwide',
                    'Use it to find mentors, job opportunities, and lifelong connections',
                    'Your privacy and data security are our top priorities',
                ],
            ],
            [
                'id' => 'profile-completion',
                'title' => 'Complete Your Profile',
                'description' => 'A complete profile helps you get better recommendations and connections',
                'type' => 'interactive',
                'target' => '[data-tour="profile-section"]',
                'icon' => 'user',
                'interactive' => [
                    'type' => 'form',
                    'fields' => [
                        ['name' => 'current_title', 'label' => 'Current Job Title', 'type' => 'text', 'required' => true],
                        ['name' => 'industry', 'label' => 'Industry', 'type' => 'select', 'required' => true, 'options' => [
                            ['value' => 'technology', 'label' => 'Technology'],
                            ['value' => 'finance', 'label' => 'Finance'],
                            ['value' => 'healthcare', 'label' => 'Healthcare'],
                            ['value' => 'education', 'label' => 'Education'],
                            ['value' => 'other', 'label' => 'Other'],
                        ]],
                    ],
                ],
                'tips' => [
                    'Add a professional photo to increase connection acceptance rates',
                    'Include your current role and company for better job matching',
                    'List your skills and interests to find relevant opportunities',
                ],
            ],
            [
                'id' => 'explore-directory',
                'title' => 'Explore Alumni Directory',
                'description' => 'Find and connect with alumni from your school and beyond',
                'type' => 'guided',
                'target' => '[data-tour="alumni-directory"]',
                'icon' => 'users',
                'tips' => [
                    'Use filters to find alumni in your industry or location',
                    'Send personalized connection requests for better response rates',
                    'Look for alumni at companies where you\'d like to work',
                ],
            ],
            [
                'id' => 'job-matching',
                'title' => 'Discover Job Opportunities',
                'description' => 'See how our AI matches you with relevant job opportunities',
                'type' => 'guided',
                'target' => '[data-tour="job-dashboard"]',
                'icon' => 'briefcase',
                'tips' => [
                    'Jobs are ranked by your network connections and profile match',
                    'Request referrals from alumni at companies you\'re interested in',
                    'Keep your profile updated for better job recommendations',
                ],
            ],
            [
                'id' => 'social-timeline',
                'title' => 'Join the Conversation',
                'description' => 'Share updates and engage with your alumni community',
                'type' => 'guided',
                'target' => '[data-tour="social-timeline"]',
                'icon' => 'chat',
                'tips' => [
                    'Share career updates, achievements, and insights',
                    'Engage with others\' posts to build relationships',
                    'Use relevant hashtags to increase visibility',
                ],
            ],
            [
                'id' => 'mentorship',
                'title' => 'Find Mentors & Give Back',
                'description' => 'Connect with experienced alumni and mentor others',
                'type' => 'guided',
                'target' => '[data-tour="mentorship-hub"]',
                'icon' => 'academic',
                'tips' => [
                    'Look for mentors who have the career path you want',
                    'Be specific about what you want to learn or achieve',
                    'Consider mentoring newer alumni in your field',
                ],
            ],
        ];
    }

    /**
     * Get institution admin onboarding sequence
     */
    private function getInstitutionAdminOnboardingSequence(): array
    {
        return [
            [
                'id' => 'admin-welcome',
                'title' => 'Welcome, Administrator',
                'description' => 'Learn how to manage and engage your alumni community effectively',
                'type' => 'introduction',
                'target' => null,
                'icon' => 'shield',
                'tips' => [
                    'You have powerful tools to engage and support your alumni',
                    'Use analytics to understand alumni behavior and preferences',
                    'Regular engagement leads to stronger alumni relationships',
                ],
            ],
            [
                'id' => 'dashboard-overview',
                'title' => 'Your Admin Dashboard',
                'description' => 'Navigate your dashboard and understand key metrics',
                'type' => 'guided',
                'target' => '[data-tour="admin-dashboard"]',
                'icon' => 'chart',
                'tips' => [
                    'Monitor engagement metrics to track community health',
                    'Use quick actions for common administrative tasks',
                    'Customize your dashboard to show the most relevant data',
                ],
            ],
            [
                'id' => 'alumni-management',
                'title' => 'Managing Alumni Data',
                'description' => 'Import, update, and maintain your alumni database',
                'type' => 'guided',
                'target' => '[data-tour="alumni-management"]',
                'icon' => 'users',
                'tips' => [
                    'Regular data updates improve platform effectiveness',
                    'Use bulk operations for efficient data management',
                    'Verify alumni information to maintain data quality',
                ],
            ],
            [
                'id' => 'events-campaigns',
                'title' => 'Events & Campaigns',
                'description' => 'Create engaging events and fundraising campaigns',
                'type' => 'guided',
                'target' => '[data-tour="events-campaigns"]',
                'icon' => 'calendar',
                'tips' => [
                    'Regular events keep alumni engaged with your institution',
                    'Use targeted campaigns for better fundraising results',
                    'Track RSVP and donation metrics to measure success',
                ],
            ],
        ];
    }

    /**
     * Get super admin onboarding sequence
     */
    private function getSuperAdminOnboardingSequence(): array
    {
        return [
            [
                'id' => 'system-overview',
                'title' => 'Platform System Overview',
                'description' => 'Understand the platform architecture and your responsibilities',
                'type' => 'introduction',
                'target' => null,
                'icon' => 'rocket',
                'tips' => [
                    'You manage the entire platform and all institutions',
                    'Monitor system health and performance regularly',
                    'Ensure security and compliance across all tenants',
                ],
            ],
            [
                'id' => 'institution-management',
                'title' => 'Institution Management',
                'description' => 'Learn to onboard and manage educational institutions',
                'type' => 'guided',
                'target' => '[data-tour="institution-management"]',
                'icon' => 'building',
                'tips' => [
                    'Proper institution setup ensures smooth operations',
                    'Monitor institution health and usage patterns',
                    'Provide support and training to institution admins',
                ],
            ],
        ];
    }

    /**
     * Get employer onboarding sequence
     */
    private function getEmployerOnboardingSequence(): array
    {
        return [
            [
                'id' => 'employer-welcome',
                'title' => 'Welcome to Alumni Talent Network',
                'description' => 'Connect with top alumni talent from leading institutions',
                'type' => 'introduction',
                'target' => null,
                'icon' => 'briefcase',
                'tips' => [
                    'Access pre-screened, high-quality alumni candidates',
                    'Leverage alumni networks for better hiring outcomes',
                    'Build long-term relationships with educational institutions',
                ],
            ],
            [
                'id' => 'company-verification',
                'title' => 'Company Verification',
                'description' => 'Complete your company verification to start recruiting',
                'type' => 'interactive',
                'target' => '[data-tour="company-verification"]',
                'icon' => 'shield',
                'interactive' => [
                    'type' => 'action',
                    'action' => 'start-verification',
                    'buttonText' => 'Start Verification Process',
                ],
                'tips' => [
                    'Verification builds trust with alumni candidates',
                    'Provide accurate company information for better matches',
                    'Verification typically takes 1-2 business days',
                ],
            ],
        ];
    }

    /**
     * Get general onboarding sequence
     */
    private function getGeneralOnboardingSequence(): array
    {
        return [
            [
                'id' => 'platform-intro',
                'title' => 'Welcome to the Alumni Platform',
                'description' => 'Your gateway to lifelong connections and opportunities',
                'type' => 'introduction',
                'target' => null,
                'icon' => 'sparkles',
                'tips' => [
                    'Connect with alumni from your institution and beyond',
                    'Discover career opportunities through your network',
                    'Share your journey and inspire others',
                ],
            ],
        ];
    }

    /**
     * Get alumni FAQs
     */
    private function getAlumniFAQs(): Collection
    {
        return collect([
            [
                'id' => 'profile-visibility',
                'question' => 'Who can see my profile information?',
                'answer' => 'You control your profile visibility through privacy settings. By default, other verified alumni can see your basic information, but you can adjust what\'s visible to different groups.',
                'category' => 'privacy',
                'helpful_count' => 0,
            ],
            [
                'id' => 'job-matching-algorithm',
                'question' => 'How does the job matching system work?',
                'answer' => 'Our AI analyzes your profile, skills, career goals, and network connections to recommend relevant opportunities. Jobs from companies where you have alumni connections are prioritized.',
                'category' => 'career',
                'helpful_count' => 0,
            ],
            [
                'id' => 'connection-requests',
                'question' => 'How do I send effective connection requests?',
                'answer' => 'Personalize your connection requests by mentioning shared experiences, mutual connections, or specific reasons for connecting. Generic requests have lower acceptance rates.',
                'category' => 'networking',
                'helpful_count' => 0,
            ],
            [
                'id' => 'mentorship-matching',
                'question' => 'How are mentors and mentees matched?',
                'answer' => 'Matching is based on industry, career goals, experience level, and availability. You can browse available mentors or be matched automatically based on your preferences.',
                'category' => 'mentorship',
                'helpful_count' => 0,
            ],
        ]);
    }

    /**
     * Get institution admin FAQs
     */
    private function getInstitutionAdminFAQs(): Collection
    {
        return collect([
            [
                'id' => 'data-import-formats',
                'question' => 'What file formats are supported for alumni data import?',
                'answer' => 'We support CSV, Excel (.xlsx), and JSON formats. CSV is recommended for large datasets. Ensure your data includes required fields like name, email, and graduation year.',
                'category' => 'data-management',
                'helpful_count' => 0,
            ],
            [
                'id' => 'engagement-metrics',
                'question' => 'How do I interpret engagement metrics?',
                'answer' => 'Key metrics include active users, post engagement rates, event attendance, and donation participation. Higher engagement indicates a healthier alumni community.',
                'category' => 'analytics',
                'helpful_count' => 0,
            ],
            [
                'id' => 'fundraising-campaigns',
                'question' => 'What makes a successful fundraising campaign?',
                'answer' => 'Successful campaigns have clear goals, compelling stories, regular updates, and multiple giving levels. Use alumni success stories and show impact of previous donations.',
                'category' => 'fundraising',
                'helpful_count' => 0,
            ],
        ]);
    }

    /**
     * Get super admin FAQs
     */
    private function getSuperAdminFAQs(): Collection
    {
        return collect([
            [
                'id' => 'system-performance',
                'question' => 'How do I monitor system performance?',
                'answer' => 'Use the performance monitoring dashboard to track response times, error rates, and resource usage. Set up alerts for critical metrics to ensure system reliability.',
                'category' => 'monitoring',
                'helpful_count' => 0,
            ],
            [
                'id' => 'institution-onboarding',
                'question' => 'What\'s the process for onboarding new institutions?',
                'answer' => 'New institutions go through verification, data setup, admin training, and gradual rollout. The process typically takes 2-4 weeks depending on data complexity.',
                'category' => 'administration',
                'helpful_count' => 0,
            ],
        ]);
    }

    /**
     * Get employer FAQs
     */
    private function getEmployerFAQs(): Collection
    {
        return collect([
            [
                'id' => 'verification-process',
                'question' => 'How long does company verification take?',
                'answer' => 'Company verification typically takes 1-2 business days. We verify company information, domain ownership, and compliance with our terms of service.',
                'category' => 'verification',
                'helpful_count' => 0,
            ],
            [
                'id' => 'candidate-quality',
                'question' => 'How do you ensure candidate quality?',
                'answer' => 'All alumni are verified through their educational institutions. We also use AI to match candidates based on skills, experience, and career trajectory.',
                'category' => 'recruiting',
                'helpful_count' => 0,
            ],
        ]);
    }

    /**
     * Get general FAQs
     */
    private function getGeneralFAQs(): Collection
    {
        return collect([
            [
                'id' => 'account-security',
                'question' => 'How do I keep my account secure?',
                'answer' => 'Use a strong, unique password, enable two-factor authentication, and regularly review your account activity. Never share your login credentials.',
                'category' => 'security',
                'helpful_count' => 0,
            ],
            [
                'id' => 'data-privacy',
                'question' => 'How is my personal data protected?',
                'answer' => 'We use industry-standard encryption, comply with GDPR and other privacy regulations, and never sell your personal information to third parties.',
                'category' => 'privacy',
                'helpful_count' => 0,
            ],
            [
                'id' => 'technical-support',
                'question' => 'How do I get technical support?',
                'answer' => 'Use the help button in the top navigation, email support@alumni.com, or use the in-app chat feature. We typically respond within 24 hours.',
                'category' => 'support',
                'helpful_count' => 0,
            ],
        ]);
    }

    /**
     * Get current step in onboarding sequence
     */
    private function getCurrentStep(array $sequence, array $completedSteps): ?array
    {
        foreach ($sequence as $step) {
            if (! isset($completedSteps[$step['id']]) || ! $completedSteps[$step['id']]['completed']) {
                return $step;
            }
        }

        return null; // All steps completed
    }

    /**
     * Get next recommended action for user
     */
    private function getNextRecommendedAction(string $role, array $completedSteps): ?string
    {
        $sequence = $this->getOnboardingSequence($role);
        $currentStep = $this->getCurrentStep($sequence, $completedSteps);

        if ($currentStep) {
            return "Complete: {$currentStep['title']}";
        }

        // All onboarding completed, suggest ongoing actions
        switch ($role) {
            case 'graduate':
            case 'alumni':
                return 'Update your career timeline or connect with more alumni';
            case 'institution_admin':
                return 'Review engagement analytics or create a new event';
            case 'super_admin':
                return 'Check system health or review institution performance';
            case 'employer':
                return 'Post a new job or review candidate applications';
            default:
                return 'Explore the platform features';
        }
    }
}
