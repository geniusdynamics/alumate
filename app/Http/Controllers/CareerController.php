<?php

namespace App\Http\Controllers;

use App\Models\CareerTimeline;
use App\Models\CareerMilestone;
use App\Models\MentorshipRequest;
use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class CareerController extends Controller
{
    public function timeline()
    {
        $user = Auth::user();
        
        // Get user's career timeline entries
        $careerEntries = CareerTimeline::where('user_id', $user->id)
            ->with(['milestones'])
            ->orderBy('start_date', 'desc')
            ->get();

        // Get career milestones
        $milestones = CareerMilestone::where('user_id', $user->id)
            ->orderBy('achieved_at', 'desc')
            ->get();

        // Get career goals (upcoming milestones)
        $goals = CareerMilestone::where('user_id', $user->id)
            ->whereNull('achieved_at')
            ->orderBy('target_date', 'asc')
            ->get();

        // Get career insights based on user's profile
        $insights = $this->getCareerInsights($user);

        return Inertia::render('Career/Timeline', [
            'careerEntries' => $careerEntries,
            'milestones' => $milestones,
            'goals' => $goals,
            'insights' => $insights,
        ]);
    }

    public function goals()
    {
        $user = Auth::user();
        
        // Get user's career goals
        $activeGoals = CareerMilestone::where('user_id', $user->id)
            ->whereNull('achieved_at')
            ->orderBy('target_date', 'asc')
            ->get();

        $achievedGoals = CareerMilestone::where('user_id', $user->id)
            ->whereNotNull('achieved_at')
            ->orderBy('achieved_at', 'desc')
            ->get();

        // Get goal templates/suggestions
        $goalSuggestions = $this->getGoalSuggestions($user);

        return Inertia::render('Career/Goals', [
            'activeGoals' => $activeGoals,
            'achievedGoals' => $achievedGoals,
            'goalSuggestions' => $goalSuggestions,
        ]);
    }

    public function mentorship()
    {
        $user = Auth::user();
        
        // Get user's mentorship requests
        $mentorshipRequests = MentorshipRequest::where('mentee_id', $user->id)
            ->with(['mentor.user', 'mentor.skills'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get available mentors
        $availableMentors = MentorProfile::where('is_available', true)
            ->with(['user.graduate', 'skills', 'sessions'])
            ->whereDoesntHave('mentorshipRequests', function ($query) use ($user) {
                $query->where('mentee_id', $user->id)
                      ->whereIn('status', ['pending', 'accepted']);
            })
            ->get();

        // Get mentorship sessions if user is a mentee
        $mentorshipSessions = [];
        if ($user->mentorshipRequests()->where('status', 'accepted')->exists()) {
            $mentorshipSessions = $user->mentorshipSessions()
                ->with(['mentor.user'])
                ->orderBy('scheduled_at', 'desc')
                ->get();
        }

        // Check if user is also a mentor
        $mentorProfile = MentorProfile::where('user_id', $user->id)->first();

        return Inertia::render('Career/Mentorship', [
            'mentorshipRequests' => $mentorshipRequests,
            'availableMentors' => $availableMentors,
            'mentorshipSessions' => $mentorshipSessions,
            'mentorProfile' => $mentorProfile,
        ]);
    }

    public function mentorshipHub(Request $request)
    {
        $user = Auth::user();
        
        // Get active mentorships (both as mentor and mentee)
        $activeMentorships = MentorshipRequest::where(function ($query) use ($user) {
            $query->where('mentee_id', $user->id)
                  ->orWhereHas('mentor', function ($q) use ($user) {
                      $q->where('user_id', $user->id);
                  });
        })
        ->where('status', 'accepted')
        ->with(['mentor.user', 'mentee'])
        ->get();

        // Get pending requests (received as mentor)
        $pendingRequests = MentorshipRequest::whereHas('mentor', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->where('status', 'pending')
        ->with(['mentee'])
        ->get();

        // Get upcoming sessions
        $upcomingSessions = collect(); // Placeholder for mentorship sessions

        // Get available mentors with filtering
        $mentorsQuery = MentorProfile::where('is_available', true)
            ->with(['user.graduate', 'skills'])
            ->whereDoesntHave('mentorshipRequests', function ($query) use ($user) {
                $query->where('mentee_id', $user->id)
                      ->whereIn('status', ['pending', 'accepted']);
            });

        // Apply filters
        if ($request->filled('expertise')) {
            $mentorsQuery->whereHas('skills', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->expertise . '%');
            });
        }

        if ($request->filled('location')) {
            $mentorsQuery->whereHas('user.graduate', function ($query) use ($request) {
                $query->where('current_location', 'like', '%' . $request->location . '%');
            });
        }

        if ($request->filled('availability')) {
            $mentorsQuery->where('availability', $request->availability);
        }

        $mentors = $mentorsQuery->limit(20)->get();

        // Get mentorship goals
        $mentorshipGoals = CareerMilestone::where('user_id', $user->id)
            ->where('category', 'mentorship')
            ->whereNull('achieved_at')
            ->orderBy('target_date', 'asc')
            ->get();

        // Get learning resources
        $learningResources = $this->getLearningResources();

        // Get expertise areas for filtering
        $expertiseAreas = MentorProfile::whereHas('skills')
            ->with('skills')
            ->get()
            ->pluck('skills')
            ->flatten()
            ->pluck('name')
            ->unique()
            ->sort()
            ->values();

        // Get mentorship stats
        $mentorshipStats = [
            'active_mentorships' => $activeMentorships->count(),
            'completed_sessions' => 0, // Placeholder
            'goals_achieved' => CareerMilestone::where('user_id', $user->id)
                ->where('category', 'mentorship')
                ->whereNotNull('achieved_at')
                ->count(),
        ];

        return Inertia::render('Career/MentorshipHub', [
            'mentors' => $mentors,
            'activeMentorships' => $activeMentorships,
            'pendingRequests' => $pendingRequests,
            'upcomingSessions' => $upcomingSessions,
            'mentorshipGoals' => $mentorshipGoals,
            'learningResources' => $learningResources,
            'expertiseAreas' => $expertiseAreas,
            'mentorshipStats' => $mentorshipStats,
            'currentFilters' => $request->only(['expertise', 'location', 'availability']),
        ]);
    }

    private function getCareerInsights($user)
    {
        $graduate = $user->graduate;
        if (!$graduate) {
            return [];
        }

        $insights = [];

        // Career progression insight
        $careerEntries = CareerTimeline::where('user_id', $user->id)->count();
        if ($careerEntries === 0) {
            $insights[] = [
                'type' => 'suggestion',
                'title' => 'Start Your Career Timeline',
                'message' => 'Add your first career entry to track your professional journey.',
                'action' => 'Add Career Entry',
            ];
        }

        // Mentorship insight
        $hasMentor = MentorshipRequest::where('mentee_id', $user->id)
            ->where('status', 'accepted')
            ->exists();
        
        if (!$hasMentor) {
            $insights[] = [
                'type' => 'opportunity',
                'title' => 'Find a Mentor',
                'message' => 'Connect with experienced alumni to accelerate your career growth.',
                'action' => 'Browse Mentors',
            ];
        }

        // Skill development insight
        $userSkills = $user->skills()->count();
        if ($userSkills < 5) {
            $insights[] = [
                'type' => 'improvement',
                'title' => 'Expand Your Skills',
                'message' => 'Add more skills to your profile to increase job matching opportunities.',
                'action' => 'Add Skills',
            ];
        }

        return $insights;
    }

    private function getGoalSuggestions($user)
    {
        $graduate = $user->graduate;
        if (!$graduate) {
            return [];
        }

        $suggestions = [
            [
                'title' => 'Complete Professional Certification',
                'description' => 'Earn a certification relevant to your field',
                'category' => 'skill_development',
                'timeline' => '6 months',
            ],
            [
                'title' => 'Secure Leadership Role',
                'description' => 'Advance to a management or leadership position',
                'category' => 'career_advancement',
                'timeline' => '2 years',
            ],
            [
                'title' => 'Expand Professional Network',
                'description' => 'Connect with 50 new professionals in your industry',
                'category' => 'networking',
                'timeline' => '1 year',
            ],
            [
                'title' => 'Increase Salary by 20%',
                'description' => 'Negotiate a raise or find a higher-paying position',
                'category' => 'compensation',
                'timeline' => '1 year',
            ],
            [
                'title' => 'Mentor Junior Professionals',
                'description' => 'Give back by mentoring recent graduates',
                'category' => 'giving_back',
                'timeline' => '6 months',
            ],
        ];

        return $suggestions;
    }

    private function getLearningResources()
    {
        return [
            [
                'title' => 'Career Development Fundamentals',
                'type' => 'course',
                'provider' => 'LinkedIn Learning',
                'duration' => '2 hours',
                'url' => '#',
                'description' => 'Learn the basics of career planning and development.',
            ],
            [
                'title' => 'Effective Networking Strategies',
                'type' => 'article',
                'provider' => 'Harvard Business Review',
                'duration' => '10 min read',
                'url' => '#',
                'description' => 'Master the art of professional networking.',
            ],
            [
                'title' => 'Leadership Skills Workshop',
                'type' => 'workshop',
                'provider' => 'Alumni Association',
                'duration' => '4 hours',
                'url' => '#',
                'description' => 'Develop essential leadership capabilities.',
            ],
            [
                'title' => 'Industry Trends Report 2024',
                'type' => 'report',
                'provider' => 'McKinsey & Company',
                'duration' => '30 min read',
                'url' => '#',
                'description' => 'Stay updated with the latest industry insights.',
            ],
        ];
    }
}
