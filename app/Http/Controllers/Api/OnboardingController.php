<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\UserOnboarding;
use App\Models\FeatureUpdate;
use App\Models\Graduate;
use Carbon\Carbon;

class OnboardingController extends Controller
{
    /**
     * Get user's onboarding state
     */
    public function getOnboardingState()
    {
        $user = Auth::user();
        
        $onboarding = UserOnboarding::firstOrCreate(
            ['user_id' => $user->id],
            [
                'is_new_user' => $user->created_at->diffInDays(now()) <= 7,
                'has_completed_onboarding' => false,
                'progress' => [
                    'current_step' => 0,
                    'completed_steps' => [],
                    'skipped_steps' => []
                ],
                'preferences' => [
                    'show_feature_spotlights' => true,
                    'show_profile_completion' => true,
                    'show_whats_new' => true,
                    'tour_speed' => 'normal'
                ],
                'explored_features' => [],
                'dismissed_prompts' => []
            ]
        );

        return response()->json([
            'success' => true,
            'state' => [
                'is_new_user' => $onboarding->is_new_user,
                'has_completed_onboarding' => $onboarding->has_completed_onboarding,
                'progress' => $onboarding->progress,
                'preferences' => $onboarding->preferences,
                'explored_features' => $onboarding->explored_features,
                'dismissed_prompts' => $onboarding->dismissed_prompts
            ]
        ]);
    }

    /**
     * Get new features for discovery
     */
    public function getNewFeatures()
    {
        $user = Auth::user();
        $onboarding = UserOnboarding::where('user_id', $user->id)->first();
        
        $lastViewedAt = $onboarding?->feature_discovery_viewed_at ?? $user->created_at;
        
        $newFeatures = [
            [
                'id' => 'alumni-map',
                'title' => 'Alumni Map Visualization',
                'subtitle' => 'Discover alumni around the world',
                'description' => 'Explore where your fellow alumni are located with our interactive world map. Find networking opportunities in your area or when traveling.',
                'icon' => 'map',
                'status' => 'new',
                'tags' => ['Networking', 'Discovery', 'Geographic'],
                'benefits' => [
                    'Find alumni in your city or travel destinations',
                    'Discover regional alumni groups and events',
                    'Visualize the global reach of your network'
                ],
                'howToUse' => [
                    'Navigate to Alumni Directory',
                    'Click on the Map View tab',
                    'Use filters to find specific alumni',
                    'Click on markers to view profiles'
                ],
                'route' => '/alumni/directory?view=map',
                'actionText' => 'Explore Map'
            ],
            [
                'id' => 'achievement-celebrations',
                'title' => 'Achievement Celebrations',
                'subtitle' => 'Celebrate your milestones',
                'description' => 'Automatically detect and celebrate career milestones, achievements, and special moments with your alumni network.',
                'icon' => 'trophy',
                'status' => 'new',
                'tags' => ['Recognition', 'Social', 'Milestones'],
                'benefits' => [
                    'Get recognized for your achievements',
                    'Celebrate fellow alumni successes',
                    'Build stronger community connections'
                ],
                'howToUse' => [
                    'Update your career timeline',
                    'Share achievements in posts',
                    'Engage with others\' celebrations',
                    'Set achievement goals'
                ],
                'route' => '/career/timeline',
                'actionText' => 'View Achievements'
            ],
            [
                'id' => 'smart-job-matching',
                'title' => 'Smart Job Matching',
                'subtitle' => 'AI-powered career opportunities',
                'description' => 'Get personalized job recommendations based on your network connections, skills, and career goals using advanced AI matching.',
                'icon' => 'briefcase',
                'status' => 'updated',
                'tags' => ['AI', 'Jobs', 'Networking'],
                'benefits' => [
                    'Discover hidden job opportunities',
                    'Leverage your alumni network for referrals',
                    'Get matched with roles that fit your goals'
                ],
                'howToUse' => [
                    'Complete your profile and skills',
                    'Set your job preferences',
                    'Review daily recommendations',
                    'Request introductions through connections'
                ],
                'route' => '/jobs/dashboard',
                'actionText' => 'See Recommendations'
            ],
            [
                'id' => 'mentorship-hub',
                'title' => 'Enhanced Mentorship Hub',
                'subtitle' => 'Connect with mentors and mentees',
                'description' => 'Find mentors in your field or become a mentor yourself with our improved matching system and session management tools.',
                'icon' => 'academic',
                'status' => 'updated',
                'tags' => ['Mentorship', 'Learning', 'Growth'],
                'benefits' => [
                    'Get guidance from experienced alumni',
                    'Give back by mentoring others',
                    'Track your mentorship progress'
                ],
                'howToUse' => [
                    'Browse available mentors',
                    'Send mentorship requests',
                    'Schedule regular sessions',
                    'Set learning goals together'
                ],
                'route' => '/career/mentorship-hub',
                'actionText' => 'Find Mentors'
            ]
        ];

        // Filter features based on user's role and preferences
        $userRole = $user->roles->first()?->name ?? 'graduate';
        $filteredFeatures = collect($newFeatures)->filter(function ($feature) use ($userRole) {
            // Show all features to graduates, filter for other roles
            if ($userRole === 'graduate') return true;
            if ($userRole === 'employer' && in_array($feature['id'], ['smart-job-matching'])) return true;
            if ($userRole === 'student' && in_array($feature['id'], ['mentorship-hub', 'achievement-celebrations'])) return true;
            return false;
        });

        return response()->json([
            'success' => true,
            'features' => $filteredFeatures->values()
        ]);
    }

    /**
     * Get profile completion data
     */
    public function getProfileCompletion()
    {
        $user = Auth::user();
        $graduate = Graduate::where('user_id', $user->id)->first();
        
        $completionData = $this->calculateProfileCompletion($user, $graduate);
        
        return response()->json([
            'success' => true,
            'completion_data' => $completionData
        ]);
    }

    /**
     * Get what's new updates
     */
    public function getWhatsNewUpdates()
    {
        $user = Auth::user();
        $onboarding = UserOnboarding::where('user_id', $user->id)->first();
        
        $lastViewedAt = $onboarding?->whats_new_viewed_at ?? $user->created_at;
        
        $updates = [
            [
                'id' => 'platform-update-2025-01',
                'title' => 'Enhanced Social Features',
                'description' => 'We\'ve improved the social timeline with better engagement tools and real-time updates.',
                'type' => 'feature',
                'created_at' => '2025-01-15T00:00:00Z',
                'read' => $lastViewedAt > '2025-01-15',
                'features' => [
                    'Real-time post updates and notifications',
                    'Improved reaction system with more options',
                    'Better comment threading and mentions',
                    'Enhanced post sharing capabilities'
                ],
                'actions' => [
                    [
                        'label' => 'Try It Now',
                        'type' => 'navigate',
                        'url' => '/social/timeline'
                    ]
                ]
            ],
            [
                'id' => 'platform-update-2025-02',
                'title' => 'Career Development Tools',
                'description' => 'New tools to help you track and advance your career with alumni support.',
                'type' => 'feature',
                'created_at' => '2025-01-20T00:00:00Z',
                'read' => $lastViewedAt > '2025-01-20',
                'features' => [
                    'Visual career timeline with milestones',
                    'Goal setting and progress tracking',
                    'Skills assessment and development',
                    'Mentorship matching improvements'
                ],
                'actions' => [
                    [
                        'label' => 'Update Career',
                        'type' => 'navigate',
                        'url' => '/career/timeline'
                    ]
                ]
            ],
            [
                'id' => 'security-update-2025-01',
                'title' => 'Security Enhancements',
                'description' => 'We\'ve implemented additional security measures to protect your data.',
                'type' => 'security',
                'created_at' => '2025-01-10T00:00:00Z',
                'read' => $lastViewedAt > '2025-01-10',
                'features' => [
                    'Enhanced two-factor authentication',
                    'Improved password security requirements',
                    'Better privacy controls',
                    'Audit logging for account activities'
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'updates' => $updates
        ]);
    }

    /**
     * Mark onboarding as completed
     */
    public function completeOnboarding(Request $request)
    {
        $user = Auth::user();
        
        UserOnboarding::updateOrCreate(
            ['user_id' => $user->id],
            [
                'has_completed_onboarding' => true,
                'completed_at' => now(),
                'progress' => array_merge(
                    UserOnboarding::where('user_id', $user->id)->value('progress') ?? [],
                    ['completed_steps' => $request->input('completed_steps', [])]
                )
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Mark onboarding as skipped
     */
    public function skipOnboarding()
    {
        $user = Auth::user();
        
        UserOnboarding::updateOrCreate(
            ['user_id' => $user->id],
            [
                'has_completed_onboarding' => true,
                'skipped_at' => now()
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Mark feature as explored
     */
    public function markFeatureExplored(Request $request)
    {
        $user = Auth::user();
        $featureId = $request->input('feature_id');
        
        $onboarding = UserOnboarding::where('user_id', $user->id)->first();
        if ($onboarding) {
            $exploredFeatures = $onboarding->explored_features ?? [];
            if (!in_array($featureId, $exploredFeatures)) {
                $exploredFeatures[] = $featureId;
                $onboarding->update(['explored_features' => $exploredFeatures]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark feature discovery as viewed
     */
    public function markFeatureDiscoveryViewed()
    {
        $user = Auth::user();
        
        UserOnboarding::updateOrCreate(
            ['user_id' => $user->id],
            ['feature_discovery_viewed_at' => now()]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Dismiss a prompt
     */
    public function dismissPrompt(Request $request)
    {
        $user = Auth::user();
        $prompt = $request->input('prompt');
        
        $onboarding = UserOnboarding::where('user_id', $user->id)->first();
        if ($onboarding) {
            $dismissedPrompts = $onboarding->dismissed_prompts ?? [];
            if (!in_array($prompt, $dismissedPrompts)) {
                $dismissedPrompts[] = $prompt;
                $onboarding->update(['dismissed_prompts' => $dismissedPrompts]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark what's new as viewed
     */
    public function markWhatsNewViewed()
    {
        $user = Auth::user();
        
        UserOnboarding::updateOrCreate(
            ['user_id' => $user->id],
            ['whats_new_viewed_at' => now()]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();
        
        $onboarding = UserOnboarding::updateOrCreate(
            ['user_id' => $user->id],
            ['preferences' => $request->all()]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Calculate profile completion percentage and missing sections
     */
    private function calculateProfileCompletion($user, $graduate)
    {
        $sections = [
            'basic_info' => [
                'title' => 'Basic Information',
                'description' => 'Name, email, and contact details',
                'icon' => 'contact',
                'weight' => 15,
                'completed' => !empty($user->name) && !empty($user->email)
            ],
            'photo' => [
                'title' => 'Profile Photo',
                'description' => 'Add a professional photo',
                'icon' => 'photo',
                'weight' => 10,
                'completed' => !empty($user->avatar_url)
            ],
            'bio' => [
                'title' => 'Professional Bio',
                'description' => 'Tell others about yourself',
                'icon' => 'bio',
                'weight' => 15,
                'completed' => !empty($graduate?->bio) && strlen($graduate?->bio) > 50
            ],
            'education' => [
                'title' => 'Education History',
                'description' => 'Add your educational background',
                'icon' => 'education',
                'weight' => 20,
                'completed' => $graduate && $graduate->educations()->count() > 0
            ],
            'work_experience' => [
                'title' => 'Work Experience',
                'description' => 'Add your career history',
                'icon' => 'work',
                'weight' => 25,
                'completed' => $graduate && $graduate->careerTimeline()->count() > 0
            ],
            'skills' => [
                'title' => 'Skills & Expertise',
                'description' => 'Showcase your abilities',
                'icon' => 'skills',
                'weight' => 10,
                'completed' => $graduate && $graduate->skills()->count() >= 3
            ],
            'location' => [
                'title' => 'Location',
                'description' => 'Where are you based?',
                'icon' => 'location',
                'weight' => 5,
                'completed' => !empty($graduate?->location)
            ]
        ];

        $totalWeight = array_sum(array_column($sections, 'weight'));
        $completedWeight = array_sum(array_map(function ($section) {
            return $section['completed'] ? $section['weight'] : 0;
        }, $sections));

        $completionPercentage = round(($completedWeight / $totalWeight) * 100);
        
        $missingSections = array_filter($sections, function ($section) {
            return !$section['completed'];
        });

        return [
            'completion_percentage' => $completionPercentage,
            'completed_sections' => array_filter($sections, function ($section) {
                return $section['completed'];
            }),
            'missing_sections' => array_map(function ($key, $section) {
                return array_merge($section, ['key' => $key]);
            }, array_keys($missingSections), $missingSections)
        ];
    }
}