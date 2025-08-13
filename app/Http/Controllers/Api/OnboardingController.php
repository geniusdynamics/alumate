<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\UserOnboardingState;
use App\Models\OnboardingEvent;

class OnboardingController extends Controller
{
    /**
     * Get user's onboarding state
     */
    public function getOnboardingState(Request $request)
    {
        $user = Auth::user();
        
        $state = UserOnboardingState::firstOrCreate(
            ['user_id' => $user->id],
            [
                'has_completed_onboarding' => false,
                'has_skipped_onboarding' => false,
                'completed_steps' => [],
                'last_active_step' => 0,
                'profile_completion_dismissed' => false,
                'feature_discovery_viewed' => false,
                'explored_features' => [],
                'whats_new_viewed' => [],
                'preferences' => [
                    'show_tips' => true,
                    'auto_show_updates' => true,
                    'tour_speed' => 'normal'
                ]
            ]
        );

        return response()->json([
            'success' => true,
            'state' => $state
        ]);
    }

    /**
     * Update user's onboarding state
     */
    public function updateOnboardingState(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'has_completed_onboarding' => 'sometimes|boolean',
            'has_skipped_onboarding' => 'sometimes|boolean',
            'completed_steps' => 'sometimes|array',
            'last_active_step' => 'sometimes|integer',
            'profile_completion_dismissed' => 'sometimes|boolean',
            'feature_discovery_viewed' => 'sometimes|boolean',
            'explored_features' => 'sometimes|array',
            'whats_new_viewed' => 'sometimes|array',
            'preferences' => 'sometimes|array'
        ]);

        $state = UserOnboardingState::updateOrCreate(
            ['user_id' => $user->id],
            $request->only([
                'has_completed_onboarding',
                'has_skipped_onboarding', 
                'completed_steps',
                'last_active_step',
                'profile_completion_dismissed',
                'feature_discovery_viewed',
                'explored_features',
                'whats_new_viewed',
                'preferences'
            ])
        );

        return response()->json([
            'success' => true,
            'state' => $state
        ]);
    }

    /**
     * Get new features for feature discovery
     */
    public function getNewFeatures(Request $request)
    {
        $user = Auth::user();
        
        // Get features released since user's last login or registration
        $lastLoginDate = $user->last_login_at ?? $user->created_at;
        
        $newFeatures = [
            [
                'id' => 'alumni-map',
                'title' => 'Alumni Map',
                'description' => 'Discover alumni around the world with our interactive map visualization.',
                'category' => 'networking',
                'icon' => 'map',
                'status' => 'new',
                'tags' => ['networking', 'visualization', 'global'],
                'benefits' => [
                    'Find alumni in your city or travel destinations',
                    'Discover regional alumni clusters and communities',
                    'Plan networking events based on geographic data'
                ],
                'howItWorks' => [
                    'View the interactive world map',
                    'Filter by graduation year, industry, or interests',
                    'Click on markers to see alumni profiles',
                    'Connect with nearby alumni'
                ],
                'tips' => [
                    'Enable location sharing for better recommendations',
                    'Use the cluster view for dense metropolitan areas',
                    'Set up location alerts for travel networking'
                ],
                'route' => '/alumni/map',
                'actionText' => 'Explore Map'
            ],
            [
                'id' => 'job-matching-graph',
                'title' => 'Smart Job Matching',
                'description' => 'Get job recommendations based on your alumni network connections.',
                'category' => 'career',
                'icon' => 'briefcase',
                'status' => 'updated',
                'tags' => ['career', 'networking', 'ai'],
                'benefits' => [
                    'Jobs ranked by your network strength',
                    'See mutual connections at companies',
                    'Request warm introductions'
                ],
                'howItWorks' => [
                    'Browse personalized job recommendations',
                    'View connection insights for each role',
                    'Request introductions through mutual contacts',
                    'Track application progress'
                ],
                'tips' => [
                    'Complete your profile for better matches',
                    'Connect with more alumni to improve rankings',
                    'Set up job alerts for specific criteria'
                ],
                'route' => '/career/jobs',
                'actionText' => 'Find Jobs'
            ],
            [
                'id' => 'real-time-updates',
                'title' => 'Real-time Updates',
                'description' => 'Get instant notifications and live updates across the platform.',
                'category' => 'social',
                'icon' => 'sparkles',
                'status' => 'new',
                'tags' => ['real-time', 'notifications', 'engagement'],
                'benefits' => [
                    'Instant notifications for likes and comments',
                    'Live updates on posts and events',
                    'Real-time connection status'
                ],
                'howItWorks' => [
                    'Enable push notifications in your browser',
                    'Customize notification preferences',
                    'See live activity indicators',
                    'Get instant updates on mobile'
                ],
                'tips' => [
                    'Customize notification frequency to avoid overwhelm',
                    'Use quiet hours for focused work time',
                    'Enable location-based event notifications'
                ],
                'route' => '/settings/notifications',
                'actionText' => 'Configure Notifications'
            ]
        ];

        // Filter features based on user's role and interests
        $filteredFeatures = $this->filterFeaturesByUserProfile($newFeatures, $user);

        return response()->json([
            'success' => true,
            'features' => $filteredFeatures
        ]);
    }

    /**
     * Get profile completion data
     */
    public function getProfileCompletion(Request $request)
    {
        $user = Auth::user();
        
        $completionData = $this->calculateProfileCompletion($user);

        return response()->json([
            'success' => true,
            'completion_data' => $completionData
        ]);
    }

    /**
     * Get what's new updates
     */
    public function getWhatsNew(Request $request)
    {
        $user = Auth::user();
        
        $updates = [
            [
                'id' => 'platform-update-2024-08',
                'title' => 'Enhanced Alumni Directory',
                'description' => 'New search filters and connection insights make it easier to find and connect with alumni.',
                'type' => 'feature',
                'created_at' => '2024-08-01T00:00:00Z',
                'features' => [
                    'Advanced search with natural language queries',
                    'Connection strength indicators',
                    'Mutual connection insights',
                    'Industry and location clustering'
                ],
                'actions' => [
                    [
                        'label' => 'Try New Search',
                        'type' => 'navigate',
                        'url' => '/alumni/directory'
                    ]
                ],
                'read' => false
            ],
            [
                'id' => 'mobile-improvements-2024-08',
                'title' => 'Mobile Experience Improvements',
                'description' => 'Better mobile navigation, touch interactions, and offline support.',
                'type' => 'improvement',
                'created_at' => '2024-08-05T00:00:00Z',
                'features' => [
                    'Improved mobile navigation',
                    'Better touch target sizes',
                    'Offline content caching',
                    'Pull-to-refresh on key pages'
                ],
                'actions' => [
                    [
                        'label' => 'Install App',
                        'type' => 'feature-spotlight',
                        'feature' => [
                            'id' => 'pwa-install',
                            'title' => 'Install Alumni App',
                            'description' => 'Add the alumni platform to your home screen for a native app experience.'
                        ]
                    ]
                ],
                'read' => false
            ]
        ];

        return response()->json([
            'success' => true,
            'updates' => $updates
        ]);
    }

    /**
     * Record onboarding events for analytics
     */
    public function recordEvent(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'event_type' => 'required|string',
            'data' => 'sometimes|array',
            'timestamp' => 'sometimes|date'
        ]);

        OnboardingEvent::create([
            'user_id' => $user->id,
            'event_type' => $request->event_type,
            'data' => $request->data ?? [],
            'timestamp' => $request->timestamp ?? now()
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Save user interests from onboarding
     */
    public function saveUserInterests(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'interests' => 'required|array',
            'interests.*' => 'string'
        ]);

        // Update user's interests
        $user->update([
            'interests' => $request->interests
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Complete onboarding
     */
    public function completeOnboarding(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'completed_steps' => 'sometimes|array'
        ]);

        UserOnboardingState::updateOrCreate(
            ['user_id' => $user->id],
            [
                'has_completed_onboarding' => true,
                'completed_steps' => $request->completed_steps ?? [],
                'last_active_step' => 0
            ]
        );

        // Record completion event
        OnboardingEvent::create([
            'user_id' => $user->id,
            'event_type' => 'onboarding_completed',
            'data' => ['completed_steps' => $request->completed_steps ?? []],
            'timestamp' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding completed successfully'
        ]);
    }

    /**
     * Skip onboarding
     */
    public function skipOnboarding(Request $request)
    {
        $user = Auth::user();

        UserOnboardingState::updateOrCreate(
            ['user_id' => $user->id],
            [
                'has_skipped_onboarding' => true,
                'has_completed_onboarding' => true
            ]
        );

        // Record skip event
        OnboardingEvent::create([
            'user_id' => $user->id,
            'event_type' => 'onboarding_skipped',
            'data' => [],
            'timestamp' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding skipped'
        ]);
    }

    /**
     * Mark feature as explored
     */
    public function markFeatureExplored(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'feature_id' => 'required|string'
        ]);

        $state = UserOnboardingState::firstOrCreate(['user_id' => $user->id]);
        $exploredFeatures = $state->explored_features ?? [];
        
        if (!in_array($request->feature_id, $exploredFeatures)) {
            $exploredFeatures[] = $request->feature_id;
            $state->update(['explored_features' => $exploredFeatures]);
        }

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Mark feature discovery as viewed
     */
    public function markFeatureDiscoveryViewed(Request $request)
    {
        $user = Auth::user();

        UserOnboardingState::updateOrCreate(
            ['user_id' => $user->id],
            ['feature_discovery_viewed' => true]
        );

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Dismiss a prompt
     */
    public function dismissPrompt(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'prompt' => 'required|string'
        ]);

        if ($request->prompt === 'profile-completion') {
            UserOnboardingState::updateOrCreate(
                ['user_id' => $user->id],
                ['profile_completion_dismissed' => true]
            );
        }

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Mark what's new as viewed
     */
    public function markWhatsNewViewed(Request $request)
    {
        $user = Auth::user();

        $state = UserOnboardingState::firstOrCreate(['user_id' => $user->id]);
        $whatsNewViewed = $state->whats_new_viewed ?? [];
        
        // Mark all current updates as viewed
        $currentUpdates = ['platform-update-2024-08', 'mobile-improvements-2024-08'];
        $whatsNewViewed = array_unique(array_merge($whatsNewViewed, $currentUpdates));
        
        $state->update(['whats_new_viewed' => $whatsNewViewed]);

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'show_tips' => 'sometimes|boolean',
            'auto_show_updates' => 'sometimes|boolean',
            'tour_speed' => 'sometimes|string|in:slow,normal,fast'
        ]);

        $state = UserOnboardingState::firstOrCreate(['user_id' => $user->id]);
        $preferences = $state->preferences ?? [];
        
        $preferences = array_merge($preferences, $request->only([
            'show_tips', 'auto_show_updates', 'tour_speed'
        ]));
        
        $state->update(['preferences' => $preferences]);

        return response()->json([
            'success' => true,
            'preferences' => $preferences
        ]);
    }

    /**
     * Get contextual help content
     */
    public function getContextualHelp(Request $request, $elementId)
    {
        $helpContent = $this->getHelpContentForElement($elementId);
        
        if (!$helpContent) {
            return response()->json([
                'success' => false,
                'message' => 'Help content not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'content' => $helpContent
        ]);
    }

    /**
     * Calculate profile completion percentage and missing sections
     */
    private function calculateProfileCompletion(User $user)
    {
        $sections = [
            'basic_info' => [
                'title' => 'Basic Information',
                'description' => 'Name, email, and profile photo',
                'icon' => 'photo',
                'weight' => 15,
                'completed' => !empty($user->name) && !empty($user->email) && !empty($user->avatar_url)
            ],
            'bio' => [
                'title' => 'Professional Bio',
                'description' => 'Tell your story and highlight your expertise',
                'icon' => 'bio',
                'weight' => 10,
                'completed' => !empty($user->bio) && strlen($user->bio) > 50
            ],
            'education' => [
                'title' => 'Education History',
                'description' => 'Add your degrees and certifications',
                'icon' => 'education',
                'weight' => 20,
                'completed' => $user->educations()->count() > 0
            ],
            'work_experience' => [
                'title' => 'Work Experience',
                'description' => 'Share your career journey',
                'icon' => 'work',
                'weight' => 25,
                'completed' => $user->careerEntries()->count() > 0
            ],
            'location' => [
                'title' => 'Location',
                'description' => 'Help alumni find you locally',
                'icon' => 'location',
                'weight' => 10,
                'completed' => !empty($user->location)
            ],
            'skills' => [
                'title' => 'Skills & Expertise',
                'description' => 'Showcase your professional skills',
                'icon' => 'academic',
                'weight' => 15,
                'completed' => $user->skills()->count() >= 3
            ],
            'social_profiles' => [
                'title' => 'Social Profiles',
                'description' => 'Connect your LinkedIn, GitHub, etc.',
                'icon' => 'social',
                'weight' => 5,
                'completed' => $user->socialProfiles()->count() > 0
            ]
        ];

        $totalWeight = array_sum(array_column($sections, 'weight'));
        $completedWeight = 0;
        $missingSections = [];

        foreach ($sections as $key => $section) {
            if ($section['completed']) {
                $completedWeight += $section['weight'];
            } else {
                $missingSections[] = [
                    'key' => $key,
                    'title' => $section['title'],
                    'description' => $section['description'],
                    'icon' => $section['icon']
                ];
            }
        }

        $completionPercentage = round(($completedWeight / $totalWeight) * 100);

        return [
            'completion_percentage' => $completionPercentage,
            'missing_sections' => $missingSections,
            'total_sections' => count($sections),
            'completed_sections' => count($sections) - count($missingSections)
        ];
    }

    /**
     * Filter features based on user profile and role
     */
    private function filterFeaturesByUserProfile(array $features, User $user)
    {
        // For now, return all features
        // In the future, filter based on user role, interests, etc.
        return $features;
    }

    /**
     * Get help content for specific UI elements
     */
    private function getHelpContentForElement($elementId)
    {
        $helpContent = [
            'post-creator' => [
                'title' => 'Create a Post',
                'description' => 'Share updates, achievements, or insights with your alumni network.',
                'steps' => [
                    'Click in the text area to start writing',
                    'Add images, videos, or links to enrich your post',
                    'Choose your audience (circles, groups, or public)',
                    'Click "Post" to share with your network'
                ],
                'tips' => [
                    'Posts with images get 3x more engagement',
                    'Use @mentions to notify specific alumni',
                    'Add relevant hashtags to increase discoverability'
                ],
                'actions' => [
                    [
                        'label' => 'Try It Now',
                        'type' => 'event',
                        'event' => 'focus-post-creator'
                    ]
                ]
            ],
            'alumni-search' => [
                'title' => 'Search Alumni',
                'description' => 'Find and connect with alumni using our powerful search tools.',
                'steps' => [
                    'Enter a name, company, or skill in the search box',
                    'Use filters to narrow down results',
                    'Click on profiles to learn more',
                    'Send connection requests with personal messages'
                ],
                'tips' => [
                    'Search by graduation year to find classmates',
                    'Filter by location to find local alumni',
                    'Look for mutual connections for warm introductions'
                ],
                'learnMoreUrl' => '/help/alumni-search'
            ]
        ];

        return $helpContent[$elementId] ?? null;
    }
}