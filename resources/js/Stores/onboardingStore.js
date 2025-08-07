import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'

export const useOnboardingStore = defineStore('onboarding', () => {
    // State
    const isNewUser = ref(false)
    const hasCompletedOnboarding = ref(false)
    const onboardingProgress = reactive({
        currentStep: 0,
        completedSteps: [],
        skippedSteps: []
    })
    const userPreferences = reactive({
        showFeatureSpotlights: true,
        showProfileCompletion: true,
        showWhatsNew: true,
        tourSpeed: 'normal'
    })
    const exploredFeatures = ref([])
    const dismissedPrompts = ref([])

    // Role-specific tour configurations
    const tourConfigurations = {
        graduate: [
            {
                id: 'welcome',
                title: 'Welcome to Your Alumni Network!',
                description: 'Let\'s get you started with the key features that will help you connect, grow, and succeed.',
                target: '[data-tour="dashboard"]',
                icon: 'rocket',
                tips: [
                    'Your dashboard is your home base for all alumni activities',
                    'Check back regularly for new opportunities and connections'
                ]
            },
            {
                id: 'social-timeline',
                title: 'Share Your Journey',
                description: 'Connect with fellow alumni by sharing updates, achievements, and insights on your social timeline.',
                target: '[data-tour="social-timeline"]',
                icon: 'chat',
                interactive: {
                    type: 'action',
                    buttonText: 'Create Your First Post',
                    action: 'create-sample-post'
                },
                tips: [
                    'Share career updates, achievements, and insights',
                    'Engage with posts from your alumni network',
                    'Use hashtags to increase visibility'
                ]
            },
            {
                id: 'alumni-directory',
                title: 'Discover Your Network',
                description: 'Find and connect with alumni from your school, industry, or location.',
                target: '[data-tour="alumni-directory"]',
                icon: 'users',
                tips: [
                    'Use filters to find alumni in your field',
                    'Send personalized connection requests',
                    'Look for mutual connections for warm introductions'
                ]
            },
            {
                id: 'job-dashboard',
                title: 'Advance Your Career',
                description: 'Discover job opportunities through your alumni network and get personalized recommendations.',
                target: '[data-tour="job-dashboard"]',
                icon: 'briefcase',
                tips: [
                    'Jobs from alumni companies get priority',
                    'Request referrals through your connections',
                    'Set up job alerts for your preferences'
                ]
            },
            {
                id: 'career-timeline',
                title: 'Track Your Progress',
                description: 'Document your career journey and set goals for future growth.',
                target: '[data-tour="career-timeline"]',
                icon: 'chart',
                interactive: {
                    type: 'form',
                    fields: [
                        {
                            name: 'current_position',
                            label: 'Current Position',
                            type: 'text',
                            placeholder: 'e.g., Software Engineer',
                            required: true
                        },
                        {
                            name: 'company',
                            label: 'Company',
                            type: 'text',
                            placeholder: 'e.g., Tech Corp',
                            required: true
                        }
                    ]
                },
                tips: [
                    'Keep your career timeline updated',
                    'Set SMART goals for career advancement',
                    'Celebrate your achievements'
                ]
            },
            {
                id: 'events',
                title: 'Stay Engaged',
                description: 'Join alumni events, reunions, and networking opportunities.',
                target: '[data-tour="events"]',
                icon: 'calendar',
                tips: [
                    'RSVP early for popular events',
                    'Network actively during events',
                    'Follow up with new connections after events'
                ]
            },
            {
                id: 'profile-completion',
                title: 'Complete Your Profile',
                description: 'A complete profile helps you get better recommendations and connect with relevant alumni.',
                target: '[data-tour="profile"]',
                icon: 'academic',
                tips: [
                    'Add a professional photo',
                    'Write a compelling bio',
                    'Keep your information current'
                ]
            }
        ],
        employer: [
            {
                id: 'welcome',
                title: 'Welcome to Alumni Talent Network!',
                description: 'Connect with top alumni talent and build your employer brand.',
                target: '[data-tour="dashboard"]',
                icon: 'rocket'
            },
            {
                id: 'job-posting',
                title: 'Post Your Opportunities',
                description: 'Create compelling job postings that reach qualified alumni candidates.',
                target: '[data-tour="job-posting"]',
                icon: 'briefcase',
                tips: [
                    'Highlight your company culture',
                    'Mention alumni referral bonuses',
                    'Use clear, inclusive language'
                ]
            },
            {
                id: 'candidate-search',
                title: 'Find Top Talent',
                description: 'Search and filter alumni by skills, experience, and location.',
                target: '[data-tour="candidate-search"]',
                icon: 'users',
                tips: [
                    'Use advanced filters for precise targeting',
                    'Reach out through mutual connections',
                    'Personalize your outreach messages'
                ]
            },
            {
                id: 'company-profile',
                title: 'Build Your Brand',
                description: 'Create an attractive company profile to attract top alumni talent.',
                target: '[data-tour="company-profile"]',
                icon: 'heart',
                tips: [
                    'Showcase your company culture',
                    'Highlight alumni success stories',
                    'Keep your information updated'
                ]
            }
        ],
        'institution-admin': [
            {
                id: 'welcome',
                title: 'Welcome to Alumni Management!',
                description: 'Manage your institution\'s alumni network and engagement programs.',
                target: '[data-tour="dashboard"]',
                icon: 'rocket'
            },
            {
                id: 'alumni-management',
                title: 'Manage Alumni Data',
                description: 'Import, export, and manage your alumni database.',
                target: '[data-tour="alumni-management"]',
                icon: 'users',
                tips: [
                    'Keep alumni data current',
                    'Use bulk import for efficiency',
                    'Respect privacy preferences'
                ]
            },
            {
                id: 'engagement-tools',
                title: 'Drive Engagement',
                description: 'Create events, campaigns, and content to keep alumni engaged.',
                target: '[data-tour="engagement-tools"]',
                icon: 'heart',
                tips: [
                    'Create regular touchpoints',
                    'Segment your communications',
                    'Track engagement metrics'
                ]
            },
            {
                id: 'analytics',
                title: 'Track Success',
                description: 'Monitor alumni engagement and program effectiveness.',
                target: '[data-tour="analytics"]',
                icon: 'chart',
                tips: [
                    'Set clear KPIs',
                    'Regular reporting cycles',
                    'Use data to improve programs'
                ]
            }
        ],
        student: [
            {
                id: 'welcome',
                title: 'Connect with Alumni!',
                description: 'Learn from alumni experiences and get guidance for your career.',
                target: '[data-tour="dashboard"]',
                icon: 'rocket'
            },
            {
                id: 'success-stories',
                title: 'Get Inspired',
                description: 'Read success stories from alumni in your field of interest.',
                target: '[data-tour="success-stories"]',
                icon: 'trophy',
                tips: [
                    'Filter stories by industry',
                    'Take notes on career paths',
                    'Reach out to alumni for advice'
                ]
            },
            {
                id: 'mentorship',
                title: 'Find a Mentor',
                description: 'Connect with alumni mentors who can guide your career journey.',
                target: '[data-tour="mentorship"]',
                icon: 'academic',
                tips: [
                    'Be specific about your goals',
                    'Come prepared to meetings',
                    'Show appreciation for their time'
                ]
            },
            {
                id: 'career-guidance',
                title: 'Plan Your Future',
                description: 'Access career resources and guidance from alumni professionals.',
                target: '[data-tour="career-guidance"]',
                icon: 'chart',
                tips: [
                    'Explore different career paths',
                    'Build relevant skills early',
                    'Network before you need it'
                ]
            }
        ]
    }

    // Actions
    const loadUserOnboardingState = async () => {
        try {
            const response = await fetch('/api/onboarding/state', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
            
            const data = await response.json()
            
            if (data.success) {
                isNewUser.value = data.state.is_new_user
                hasCompletedOnboarding.value = data.state.has_completed_onboarding
                Object.assign(onboardingProgress, data.state.progress)
                Object.assign(userPreferences, data.state.preferences)
                exploredFeatures.value = data.state.explored_features || []
                dismissedPrompts.value = data.state.dismissed_prompts || []
            }
        } catch (error) {
            console.error('Failed to load onboarding state:', error)
        }
    }

    const getRoleSpecificTour = (role) => {
        return tourConfigurations[role] || tourConfigurations.graduate
    }

    const markOnboardingCompleted = async () => {
        hasCompletedOnboarding.value = true
        
        try {
            await fetch('/api/onboarding/complete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    completed_steps: onboardingProgress.completedSteps
                })
            })
        } catch (error) {
            console.error('Failed to mark onboarding as completed:', error)
        }
    }

    const markOnboardingSkipped = async () => {
        hasCompletedOnboarding.value = true
        
        try {
            await fetch('/api/onboarding/skip', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
        } catch (error) {
            console.error('Failed to mark onboarding as skipped:', error)
        }
    }

    const markFeatureExplored = async (featureId) => {
        if (!exploredFeatures.value.includes(featureId)) {
            exploredFeatures.value.push(featureId)
            
            try {
                await fetch('/api/onboarding/feature-explored', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ feature_id: featureId })
                })
            } catch (error) {
                console.error('Failed to mark feature as explored:', error)
            }
        }
    }

    const markFeatureDiscoveryViewed = async () => {
        try {
            await fetch('/api/onboarding/feature-discovery-viewed', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
        } catch (error) {
            console.error('Failed to mark feature discovery as viewed:', error)
        }
    }

    const markProfileCompletionPromptDismissed = async () => {
        if (!dismissedPrompts.value.includes('profile-completion')) {
            dismissedPrompts.value.push('profile-completion')
            
            try {
                await fetch('/api/onboarding/dismiss-prompt', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ prompt: 'profile-completion' })
                })
            } catch (error) {
                console.error('Failed to dismiss profile completion prompt:', error)
            }
        }
    }

    const markWhatsNewViewed = async () => {
        try {
            await fetch('/api/onboarding/whats-new-viewed', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                }
            })
        } catch (error) {
            console.error('Failed to mark what\'s new as viewed:', error)
        }
    }

    const updateUserPreferences = async (preferences) => {
        Object.assign(userPreferences, preferences)
        
        try {
            await fetch('/api/onboarding/preferences', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(preferences)
            })
        } catch (error) {
            console.error('Failed to update user preferences:', error)
        }
    }

    const recordStepCompletion = (stepId) => {
        if (!onboardingProgress.completedSteps.includes(stepId)) {
            onboardingProgress.completedSteps.push(stepId)
        }
    }

    const recordStepSkip = (stepId) => {
        if (!onboardingProgress.skippedSteps.includes(stepId)) {
            onboardingProgress.skippedSteps.push(stepId)
        }
    }

    return {
        // State
        isNewUser,
        hasCompletedOnboarding,
        onboardingProgress,
        userPreferences,
        exploredFeatures,
        dismissedPrompts,
        
        // Actions
        loadUserOnboardingState,
        getRoleSpecificTour,
        markOnboardingCompleted,
        markOnboardingSkipped,
        markFeatureExplored,
        markFeatureDiscoveryViewed,
        markProfileCompletionPromptDismissed,
        markWhatsNewViewed,
        updateUserPreferences,
        recordStepCompletion,
        recordStepSkip
    }
})