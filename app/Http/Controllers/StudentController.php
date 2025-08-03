<?php

namespace App\Http\Controllers;

use App\Models\SuccessStory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function storiesDiscovery(Request $request)
    {
        $user = Auth::user();
        
        // Build query for stories
        $query = SuccessStory::with(['author.profile', 'author.careerTimeline'])
            ->where('status', 'published')
            ->where('visibility', 'public');

        // Apply search filters
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('content', 'like', "%{$searchTerm}%")
                  ->orWhere('key_insights', 'like', "%{$searchTerm}%")
                  ->orWhereHas('author', function ($authorQuery) use ($searchTerm) {
                      $authorQuery->where('name', 'like', "%{$searchTerm}%")
                                  ->orWhere('current_position', 'like', "%{$searchTerm}%")
                                  ->orWhere('current_company', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Apply career field filter
        if ($request->filled('career_field')) {
            $query->where('career_field', $request->career_field);
        }

        // Apply graduation year filter
        if ($request->filled('graduation_year')) {
            $yearFilter = $request->graduation_year;
            $currentYear = date('Y');
            
            $query->whereHas('author', function ($authorQuery) use ($yearFilter, $currentYear) {
                switch ($yearFilter) {
                    case 'recent':
                        $authorQuery->where('graduation_year', '>=', $currentYear - 5);
                        break;
                    case 'mid':
                        $authorQuery->whereBetween('graduation_year', [$currentYear - 10, $currentYear - 5]);
                        break;
                    case 'experienced':
                        $authorQuery->where('graduation_year', '<', $currentYear - 10);
                        break;
                }
            });
        }

        // Apply company type filter
        if ($request->filled('company_type')) {
            $query->where('company_type', $request->company_type);
        }

        // Apply story type filter
        if ($request->filled('story_type')) {
            $query->where('category', $request->story_type);
        }

        // Apply quick filters
        if ($request->filled('quick_filter')) {
            switch ($request->quick_filter) {
                case 'my_field':
                    if ($user->major) {
                        $query->where('career_field', 'like', "%{$user->major}%");
                    }
                    break;
                case 'recent_grads':
                    $query->whereHas('author', function ($authorQuery) {
                        $authorQuery->where('graduation_year', '>=', date('Y') - 3);
                    });
                    break;
                case 'top_rated':
                    $query->where('rating', '>=', 4.5);
                    break;
                case 'entrepreneurs':
                    $query->where('category', 'entrepreneurship');
                    break;
                case 'award_winners':
                    $query->whereNotNull('awards');
                    break;
                case 'social_impact':
                    $query->where('category', 'social_impact');
                    break;
            }
        }

        // Apply sorting
        switch ($request->get('sort', 'relevance')) {
            case 'recent':
                $query->orderBy('published_at', 'desc');
                break;
            case 'popular':
                $query->orderBy('views_count', 'desc');
                break;
            case 'graduation_year':
                $query->join('users', 'success_stories.user_id', '=', 'users.id')
                      ->orderBy('users.graduation_year', 'desc');
                break;
            default: // relevance
                // Calculate relevance based on user's profile
                if ($user->major) {
                    $query->orderByRaw("CASE WHEN career_field LIKE '%{$user->major}%' THEN 1 ELSE 2 END");
                }
                $query->orderBy('views_count', 'desc');
                break;
        }

        // Get paginated results
        $stories = $query->paginate(12)->withQueryString();

        // Add relevance scores for students
        $stories->getCollection()->transform(function ($story) use ($user) {
            $story->relevance_score = $this->calculateRelevanceScore($story, $user);
            $story->relevance_reason = $this->getRelevanceReason($story, $user);
            return $story;
        });

        // Get suggested alumni connections
        $suggestedConnections = $this->getSuggestedConnections($user);

        // Get career fields for filter
        $careerFields = SuccessStory::distinct()
            ->whereNotNull('career_field')
            ->pluck('career_field')
            ->sort()
            ->values();

        return Inertia::render('Students/StoriesDiscovery', [
            'stories' => $stories,
            'suggestedConnections' => $suggestedConnections,
            'careerFields' => $careerFields,
            'filters' => $request->only(['search', 'career_field', 'graduation_year', 'company_type', 'story_type', 'sort', 'quick_filter'])
        ]);
    }

    private function calculateRelevanceScore($story, $user)
    {
        $score = 0;

        // Major/field match
        if ($user->major && stripos($story->career_field, $user->major) !== false) {
            $score += 2;
        }

        // Interest match
        if ($user->interests) {
            $userInterests = is_array($user->interests) ? $user->interests : json_decode($user->interests, true);
            $storyKeywords = array_merge(
                explode(' ', strtolower($story->title)),
                $story->key_insights ? $story->key_insights : [],
                $story->skills ? $story->skills : []
            );
            
            foreach ($userInterests as $interest) {
                if (in_array(strtolower($interest), $storyKeywords)) {
                    $score += 1;
                }
            }
        }

        // Career goals match
        if ($user->career_goals && stripos($story->category, $user->career_goals) !== false) {
            $score += 1;
        }

        // Graduation year proximity (recent grads might be more relatable)
        $yearDiff = abs(($user->expected_graduation_year ?? date('Y')) - $story->author->graduation_year);
        if ($yearDiff <= 5) {
            $score += 1;
        }

        return min(5, max(1, $score)); // Ensure score is between 1-5
    }

    private function getRelevanceReason($story, $user)
    {
        $reasons = [];

        if ($user->major && stripos($story->career_field, $user->major) !== false) {
            $reasons[] = "Matches your major ({$user->major})";
        }

        $yearDiff = abs(($user->expected_graduation_year ?? date('Y')) - $story->author->graduation_year);
        if ($yearDiff <= 3) {
            $reasons[] = "Recent graduate with similar timeline";
        }

        if ($user->career_goals && stripos($story->category, $user->career_goals) !== false) {
            $reasons[] = "Aligns with your career interests";
        }

        return implode(' • ', array_slice($reasons, 0, 2));
    }

    private function getSuggestedConnections($user)
    {
        return User::where('user_type', 'alumni')
            ->where('id', '!=', $user->id)
            ->whereHas('profile', function ($query) {
                $query->where('is_public', true);
            })
            ->with(['profile', 'successStories'])
            ->limit(8)
            ->get()
            ->map(function ($alumni) use ($user) {
                return [
                    'id' => $alumni->id,
                    'name' => $alumni->name,
                    'current_position' => $alumni->current_position,
                    'current_company' => $alumni->current_company,
                    'graduation_year' => $alumni->graduation_year,
                    'location' => $alumni->location,
                    'industry' => $alumni->industry,
                    'expertise' => $alumni->skills ? (is_array($alumni->skills) ? $alumni->skills : json_decode($alumni->skills, true)) : [],
                    'stories_count' => $alumni->successStories->count(),
                    'mutual_connections_count' => 0, // TODO: Calculate actual mutual connections
                    'response_rate' => rand(70, 95), // TODO: Calculate actual response rate
                    'connection_reason' => $this->getConnectionReason($alumni, $user),
                    'mentorship_available' => $alumni->profile->mentorship_available ?? false,
                    'connection_sent' => false // TODO: Check if connection already sent
                ];
            });
    }

    private function getConnectionReason($alumni, $user)
    {
        $reasons = [];

        if ($user->major && $alumni->major === $user->major) {
            $reasons[] = "Same major: {$user->major}";
        }

        if ($alumni->current_company && $user->target_companies) {
            $targetCompanies = is_array($user->target_companies) ? $user->target_companies : json_decode($user->target_companies, true);
            if (in_array($alumni->current_company, $targetCompanies ?? [])) {
                $reasons[] = "Works at your target company";
            }
        }

        if ($alumni->successStories->count() > 0) {
            $reasons[] = "Has shared {$alumni->successStories->count()} inspiring stories";
        }

        return $reasons ? $reasons[0] : "Fellow alumni with valuable experience";
    }

    public function careerGuidance()
    {
        $user = Auth::user();

        // Get career assessment if exists
        $careerAssessment = $user->careerAssessment;

        // Get recommended careers based on user profile
        $recommendedCareers = [
            ['title' => 'Software Engineer', 'match' => 92],
            ['title' => 'Product Manager', 'match' => 87],
            ['title' => 'Data Scientist', 'match' => 83],
            ['title' => 'UX Designer', 'match' => 78],
        ];

        // Get skills to improve
        $skillsToImprove = [
            ['name' => 'Python Programming', 'priority' => 'High'],
            ['name' => 'Data Analysis', 'priority' => 'Medium'],
            ['name' => 'Public Speaking', 'priority' => 'Medium'],
            ['name' => 'Project Management', 'priority' => 'Low'],
        ];

        // Get career tools
        $careerTools = [
            [
                'id' => 1,
                'title' => 'Resume Builder',
                'description' => 'Create a professional resume with our guided builder',
                'icon' => 'document',
                'category' => 'application'
            ],
            [
                'id' => 2,
                'title' => 'Interview Simulator',
                'description' => 'Practice interviews with AI-powered feedback',
                'icon' => 'chat',
                'category' => 'preparation'
            ],
            [
                'id' => 3,
                'title' => 'Salary Calculator',
                'description' => 'Research salary ranges for your target roles',
                'icon' => 'currency',
                'category' => 'research'
            ],
            [
                'id' => 4,
                'title' => 'Networking Tracker',
                'description' => 'Manage your professional connections',
                'icon' => 'users',
                'category' => 'networking'
            ],
        ];

        // Get industry insights
        $industryInsights = [
            [
                'id' => 1,
                'title' => 'Tech Industry Trends 2024',
                'summary' => 'AI and machine learning continue to drive growth',
                'growth_rate' => '+15%',
                'avg_salary' => '$95,000'
            ],
            [
                'id' => 2,
                'title' => 'Healthcare Innovation',
                'summary' => 'Digital health solutions creating new opportunities',
                'growth_rate' => '+12%',
                'avg_salary' => '$78,000'
            ],
        ];

        // Get career stories
        $careerStories = SuccessStory::with(['author'])
            ->where('status', 'published')
            ->where('category', 'career_change')
            ->limit(6)
            ->get();

        // Get upcoming career events
        $upcomingEvents = [
            [
                'id' => 1,
                'title' => 'Tech Career Fair',
                'date' => now()->addDays(7),
                'type' => 'career_fair'
            ],
            [
                'id' => 2,
                'title' => 'Resume Workshop',
                'date' => now()->addDays(14),
                'type' => 'workshop'
            ],
        ];

        // Calculate progress metrics
        $careerReadiness = $this->calculateCareerReadiness($user);
        $skillsProgress = $this->calculateSkillsProgress($user);
        $networkProgress = $this->calculateNetworkProgress($user);

        return Inertia::render('Students/CareerGuidance', [
            'careerAssessment' => $careerAssessment,
            'recommendedCareers' => $recommendedCareers,
            'skillsToImprove' => $skillsToImprove,
            'careerTools' => $careerTools,
            'industryInsights' => $industryInsights,
            'careerStories' => $careerStories,
            'upcomingEvents' => $upcomingEvents,
            'careerReadiness' => $careerReadiness,
            'skillsProgress' => $skillsProgress,
            'networkProgress' => $networkProgress,
        ]);
    }

    private function calculateCareerReadiness($user)
    {
        $score = 0;

        // Profile completion
        if ($user->profile_completion >= 80) $score += 25;
        elseif ($user->profile_completion >= 60) $score += 15;
        elseif ($user->profile_completion >= 40) $score += 10;

        // Resume uploaded
        if ($user->resume_url) $score += 20;

        // Career goals set
        if ($user->career_goals) $score += 15;

        // Skills listed
        if ($user->skills && count($user->skills) >= 5) $score += 20;
        elseif ($user->skills && count($user->skills) >= 3) $score += 10;

        // Network connections
        $connectionCount = $user->connections()->count();
        if ($connectionCount >= 10) $score += 20;
        elseif ($connectionCount >= 5) $score += 10;

        return min(100, $score);
    }

    private function calculateSkillsProgress($user)
    {
        // This would be based on completed courses, certifications, etc.
        return rand(40, 80);
    }

    private function calculateNetworkProgress($user)
    {
        $connectionCount = $user->connections()->count();
        $mentorshipCount = $user->mentorshipRequests()->where('status', 'accepted')->count();

        $score = 0;
        $score += min(50, $connectionCount * 5); // 5 points per connection, max 50
        $score += $mentorshipCount * 25; // 25 points per active mentorship

        return min(100, $score);
    }
}
