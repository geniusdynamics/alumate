/**
 * OnboardingService - Manages user onboarding flow and feature discovery
 */
class OnboardingService {
    constructor() {
        this.storageKey = 'alumni_onboarding_state';
        this.tourSteps = this.initializeTourSteps();
        this.apiBaseUrl = '/api/onboarding';
    }

    /**
     * Initialize tour steps configuration
     */
    initializeTourSteps() {
        return [
            {
                id: 'welcome',
                title: 'Welcome to the Alumni Platform!',
                description: 'Let\'s take a quick tour to help you discover all the amazing features available.',
                target: null,
                position: 'center',
                icon: 'rocket',
                tips: [
                    'This tour will take about 2 minutes',
                    'You can skip any step or restart the tour later',
                    'Click anywhere outside to pause the tour'
                ]
            },
            {
                id: 'navigation',
                title: 'Main Navigation',
                description: 'Access all platform features from this sidebar. Find alumni, browse jobs, join events, and more.',
                target: '[data-tour="main-navigation"]',
                position: 'right',
                icon: 'users',
                tips: [
                    'Use the search bar to quickly find features',
                    'Your most-used features will appear at the top',
                    'Click the hamburger menu on mobile'
                ]
            },
            {
                id: 'dashboard',
                title: 'Your Dashboard',
                description: 'Your personalized dashboard shows recent activity, recommendations, and quick actions.',
                target: '[data-tour="dashboard"]',
                position: 'bottom',
                icon: 'chart',
                tips: [
                    'Widgets are customizable - drag to reorder',
                    'Click the + button to add more widgets',
                    'Your dashboard adapts to your usage patterns'
                ]
            },
            {
                id: 'social-timeline',
                title: 'Social Timeline',
                description: 'Connect with fellow alumni, share updates, and engage with your community.',
                target: '[data-tour="social-timeline"]',
                position: 'bottom',
                icon: 'chat',
                tips: [
                    'Share career updates, achievements, and insights',
                    'Engage with posts through likes, comments, and shares',
                    'Use hashtags to increase visibility'
                ]
            },
            {
                id: 'alumni-directory',
                title: 'Alumni Directory',
                description: 'Discover and connect with fellow alumni from your school and beyond.',
                target: '[data-tour="alumni-directory"]',
                position: 'bottom',
                icon: 'users',
                tips: [
                    'Use advanced filters to find specific alumni',
                    'Send personalized connection requests',
                    'View mutual connections and shared experiences'
                ]
            },
            {
                id: 'career-center',
                title: 'Career Center',
                description: 'Explore job opportunities, find mentors, and advance your career.',
                target: '[data-tour="career-center"]',
                position: 'bottom',
                icon: 'briefcase',
                tips: [
                    'Jobs are ranked by your network connections',
                    'Request introductions through mutual contacts',
                    'Track your applications and follow up'
                ]
            },
            {
                id: 'events',
                title: 'Events & Networking',
                description: 'Join alumni events, reunions, and networking opportunities.',
                target: '[data-tour="events"]',
                position: 'bottom',
                icon: 'calendar',
                tips: [
                    'RSVP to events and add them to your calendar',
                    'Connect with other attendees before events',
                    'Share event highlights and photos'
                ]
            },
            {
                id: 'profile-completion',
                title: 'Complete Your Profile',
                description: 'A complete profile helps you get better recommendations and connections.',
                target: '[data-tour="profile"]',
                position: 'bottom',
                icon: 'academic',
                interactive: {
                    type: 'action',
                    buttonText: 'Complete Profile',
                    action: 'navigate-to-profile'
                },
                tips: [
                    'Add your work experience and education',
                    'Upload a professional photo',
                    'Include your interests and skills'
                ]
            }
        ];
    }

    /**
     * Get role-specific tour steps
     */
    getRoleSpecificTour(userRole) {
        const baseTour = [...this.tourSteps];
        
        switch (userRole) {
            case 'student':
                return baseTour.concat([
                    {
                        id: 'mentorship',
                        title: 'Find Mentors',
                        description: 'Connect with experienced alumni who can guide your career.',
                        target: '[data-tour="mentorship"]',
                        position: 'bottom',
                        icon: 'heart',
                        tips: [
                            'Browse mentors by industry and expertise',
                            'Send thoughtful mentorship requests',
                            'Be specific about what you want to learn'
                        ]
                    },
                    {
                        id: 'success-stories',
                        title: 'Success Stories',
                        description: 'Get inspired by alumni achievements and career journeys.',
                        target: '[data-tour="success-stories"]',
                        position: 'bottom',
                        icon: 'trophy',
                        tips: [
                            'Read stories from your field of interest',
                            'Connect with featured alumni',
                            'Share your own achievements'
                        ]
                    }
                ]);
                
            case 'employer':
                return baseTour.concat([
                    {
                        id: 'job-posting',
                        title: 'Post Jobs',
                        description: 'Reach qualified alumni candidates through targeted job postings.',
                        target: '[data-tour="job-posting"]',
                        position: 'bottom',
                        icon: 'briefcase',
                        tips: [
                            'Target specific schools and graduation years',
                            'Highlight alumni connections at your company',
                            'Use the referral system for better matches'
                        ]
                    }
                ]);
                
            case 'admin':
                return baseTour.concat([
                    {
                        id: 'analytics',
                        title: 'Analytics Dashboard',
                        description: 'Track engagement, measure success, and understand your alumni community.',
                        target: '[data-tour="analytics"]',
                        position: 'bottom',
                        icon: 'chart',
                        tips: [
                            'Monitor platform usage and engagement',
                            'Track fundraising and event success',
                            'Export data for external reporting'
                        ]
                    },
                    {
                        id: 'fundraising',
                        title: 'Fundraising Tools',
                        description: 'Create campaigns, track donations, and engage donors.',
                        target: '[data-tour="fundraising"]',
                        position: 'bottom',
                        icon: 'currency',
                        tips: [
                            'Create compelling campaign stories',
                            'Segment donors for targeted outreach',
                            'Automate thank you messages'
                        ]
                    }
                ]);
                
            default:
                return baseTour;
        }
    }

    /**
     * Get user's onboarding state from storage
     */
    getOnboardingState() {
        const stored = localStorage.getItem(this.storageKey);
        return stored ? JSON.parse(stored) : {
            hasCompletedOnboarding: false,
            hasSkippedOnboarding: false,
            completedSteps: [],
            lastActiveStep: 0,
            profileCompletionDismissed: false,
            featureDiscoveryViewed: false,
            exploredFeatures: [],
            whatsNewViewed: [],
            preferences: {
                showTips: true,
                autoShowUpdates: true,
                tourSpeed: 'normal'
            }
        };
    }

    /**
     * Save onboarding state to storage
     */
    saveOnboardingState(state) {
        localStorage.setItem(this.storageKey, JSON.stringify(state));
    }

    /**
     * Mark onboarding as completed
     */
    markOnboardingCompleted() {
        const state = this.getOnboardingState();
        state.hasCompletedOnboarding = true;
        state.completedSteps = this.tourSteps.map(step => step.id);
        this.saveOnboardingState(state);
        
        // Send completion event to backend
        this.sendOnboardingEvent('completed');
    }

    /**
     * Mark onboarding as skipped
     */
    markOnboardingSkipped() {
        const state = this.getOnboardingState();
        state.hasSkippedOnboarding = true;
        this.saveOnboardingState(state);
        
        // Send skip event to backend
        this.sendOnboardingEvent('skipped');
    }

    /**
     * Mark a specific step as completed
     */
    markStepCompleted(stepId) {
        const state = this.getOnboardingState();
        if (!state.completedSteps.includes(stepId)) {
            state.completedSteps.push(stepId);
            this.saveOnboardingState(state);
        }
    }

    /**
     * Mark profile completion prompt as dismissed
     */
    markProfileCompletionDismissed() {
        const state = this.getOnboardingState();
        state.profileCompletionDismissed = true;
        this.saveOnboardingState(state);
    }

    /**
     * Mark feature discovery as viewed
     */
    markFeatureDiscoveryViewed() {
        const state = this.getOnboardingState();
        state.featureDiscoveryViewed = true;
        this.saveOnboardingState(state);
    }

    /**
     * Mark a feature as explored
     */
    markFeatureExplored(featureId) {
        const state = this.getOnboardingState();
        if (!state.exploredFeatures.includes(featureId)) {
            state.exploredFeatures.push(featureId);
            this.saveOnboardingState(state);
        }
    }

    /**
     * Mark what's new updates as viewed
     */
    markWhatsNewViewed(updateIds = []) {
        const state = this.getOnboardingState();
        updateIds.forEach(id => {
            if (!state.whatsNewViewed.includes(id)) {
                state.whatsNewViewed.push(id);
            }
        });
        this.saveOnboardingState(state);
    }

    /**
     * Check if user should see onboarding
     */
    shouldShowOnboarding() {
        const state = this.getOnboardingState();
        return !state.hasCompletedOnboarding && !state.hasSkippedOnboarding;
    }

    /**
     * Check if user should see profile completion prompt
     */
    shouldShowProfileCompletion(completionPercentage) {
        const state = this.getOnboardingState();
        return !state.profileCompletionDismissed && completionPercentage < 70;
    }

    /**
     * Get contextual help for a specific element
     */
    getContextualHelp(elementId) {
        const helpContent = {
            'post-creator': {
                title: 'Create a Post',
                description: 'Share updates, achievements, or insights with your alumni network.',
                steps: [
                    'Click in the text area to start writing',
                    'Add images, videos, or links to enrich your post',
                    'Choose your audience (circles, groups, or public)',
                    'Click "Post" to share with your network'
                ],
                tips: [
                    'Posts with images get 3x more engagement',
                    'Use @mentions to notify specific alumni',
                    'Add relevant hashtags to increase discoverability'
                ],
                actions: [
                    {
                        label: 'Try It Now',
                        type: 'event',
                        event: 'focus-post-creator'
                    }
                ]
            },
            'alumni-search': {
                title: 'Search Alumni',
                description: 'Find and connect with alumni using our powerful search tools.',
                steps: [
                    'Enter a name, company, or skill in the search box',
                    'Use filters to narrow down results',
                    'Click on profiles to learn more',
                    'Send connection requests with personal messages'
                ],
                tips: [
                    'Search by graduation year to find classmates',
                    'Filter by location to find local alumni',
                    'Look for mutual connections for warm introductions'
                ],
                learnMoreUrl: '/help/alumni-search'
            },
            'job-matching': {
                title: 'Job Recommendations',
                description: 'Discover job opportunities through your alumni network.',
                steps: [
                    'Browse jobs ranked by your network connections',
                    'Click "View Details" to see full job descriptions',
                    'Look for alumni connections at companies',
                    'Request introductions through mutual contacts'
                ],
                tips: [
                    'Jobs with alumni connections are highlighted',
                    'Update your profile to get better matches',
                    'Set up job alerts for specific criteria'
                ],
                actions: [
                    {
                        label: 'Update Profile',
                        type: 'navigate',
                        url: '/profile/edit'
                    }
                ]
            }
        };

        return helpContent[elementId] || null;
    }

    /**
     * Send onboarding event to backend for analytics
     */
    async sendOnboardingEvent(eventType, data = {}) {
        try {
            await fetch(`${this.apiBaseUrl}/events`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    event_type: eventType,
                    data: data,
                    timestamp: new Date().toISOString()
                })
            });
        } catch (error) {
            console.error('Failed to send onboarding event:', error);
        }
    }

    /**
     * Show contextual help for an element
     */
    showContextualHelp(elementId, targetElement) {
        const helpContent = this.getContextualHelp(elementId);
        if (!helpContent) return;

        const rect = targetElement.getBoundingClientRect();
        const position = {
            x: rect.left + (rect.width / 2),
            y: rect.bottom,
            placement: 'bottom'
        };

        // Adjust placement if tooltip would go off screen
        if (position.y + 200 > window.innerHeight) {
            position.y = rect.top;
            position.placement = 'top';
        }

        window.dispatchEvent(new CustomEvent('show-contextual-help', {
            detail: { content: helpContent, position }
        }));
    }

    /**
     * Show feature spotlight
     */
    showFeatureSpotlight(feature) {
        window.dispatchEvent(new CustomEvent('show-feature-spotlight', {
            detail: { feature }
        }));
    }

    /**
     * Restart onboarding tour
     */
    restartTour() {
        const state = this.getOnboardingState();
        state.hasCompletedOnboarding = false;
        state.hasSkippedOnboarding = false;
        state.lastActiveStep = 0;
        this.saveOnboardingState(state);

        window.dispatchEvent(new CustomEvent('restart-onboarding-tour'));
    }

    /**
     * Update user preferences
     */
    updatePreferences(preferences) {
        const state = this.getOnboardingState();
        state.preferences = { ...state.preferences, ...preferences };
        this.saveOnboardingState(state);
    }
}

// Export singleton instance
export default new OnboardingService();