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
}
