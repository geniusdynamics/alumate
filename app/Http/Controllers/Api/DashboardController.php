<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use App\Models\Job;
use App\Models\Event;
use App\Models\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get social activity feed for dashboard
     */
    public function socialActivity(Request $request)
    {
        $user = Auth::user();
        $limit = $request->get('limit', 5);
        
        // Get activities from user's circles and connections
        $activities = collect();
        
        // Get recent posts from circles and connections
        $recentPosts = Post::with(['user'])
            ->where(function ($query) use ($user) {
                // Posts from user's circles
                $query->whereJsonContains('circle_ids', $user->circles->pluck('id')->toArray())
                      // Posts from connections
                      ->orWhereIn('user_id', $user->connections->pluck('id')->toArray());
            })
            ->where('user_id', '!=', $user->id) // Exclude own posts
            ->orderBy('created_at', 'desc')
            ->limit($limit * 2) // Get more to filter and limit later
            ->get();
        
        foreach ($recentPosts as $post) {
            $activities->push([
                'id' => 'post_' . $post->id,
                'type' => 'post_created',
                'user_name' => $post->user->name,
                'content' => $post->content,
                'post_id' => $post->id,
                'created_at' => $post->created_at
            ]);
        }
        
        // Get recent connections
        $recentConnections = Connection::with(['requester', 'recipient'])
            ->where(function ($query) use ($user) {
                $query->where('requester_id', $user->id)
                      ->orWhere('recipient_id', $user->id);
            })
            ->where('status', 'accepted')
            ->orderBy('connected_at', 'desc')
            ->limit(3)
            ->get();
        
        foreach ($recentConnections as $connection) {
            $otherUser = $connection->requester_id === $user->id ? $connection->recipient : $connection->requester;
            $activities->push([
                'id' => 'connection_' . $connection->id,
                'type' => 'connection_made',
                'user_name' => $otherUser->name,
                'content' => null,
                'created_at' => $connection->connected_at
            ]);
        }
        
        // Sort by created_at and limit
        $activities = $activities->sortByDesc('created_at')->take($limit)->values();
        
        return response()->json([
            'activities' => $activities
        ]);
    }
    
    /**
     * Get alumni suggestions for dashboard
     */
    public function alumniSuggestions(Request $request)
    {
        $user = Auth::user();
        $limit = $request->get('limit', 3);
        
        // Get suggestions based on shared circles, mutual connections, and similar backgrounds
        $suggestions = User::select([
                'users.id',
                'users.name',
                'users.avatar_url',
                'users.bio',
                'graduate_profiles.current_job_title as title',
                'graduate_profiles.current_company as company'
            ])
            ->leftJoin('graduate_profiles', 'users.id', '=', 'graduate_profiles.user_id')
            ->where('users.id', '!=', $user->id)
            ->whereNotIn('users.id', function ($query) use ($user) {
                // Exclude already connected users
                $query->select('recipient_id')
                      ->from('alumni_connections')
                      ->where('requester_id', $user->id)
                      ->where('status', '!=', 'blocked');
            })
            ->whereNotIn('users.id', function ($query) use ($user) {
                // Exclude users who sent connection requests
                $query->select('requester_id')
                      ->from('alumni_connections')
                      ->where('recipient_id', $user->id)
                      ->where('status', '!=', 'blocked');
            })
            ->inRandomOrder()
            ->limit($limit * 2) // Get more to add connection reasons
            ->get();
        
        // Add connection reasons
        $suggestions = $suggestions->map(function ($suggestion) use ($user) {
            $reasons = [];
            
            // Check for shared circles (simplified - would need actual circle membership logic)
            $reasons[] = 'Alumni from your network';
            
            // Check for similar companies or industries
            if ($suggestion->company && $user->graduateProfile && 
                str_contains(strtolower($suggestion->company), strtolower($user->graduateProfile->current_company ?? ''))) {
                $reasons[] = 'Works at similar company';
            }
            
            $suggestion->connection_reason = $reasons[0] ?? 'Suggested for you';
            $suggestion->title = $suggestion->title ?? 'Alumni';
            $suggestion->company = $suggestion->company ?? 'Not specified';
            
            return $suggestion;
        })->take($limit);
        
        return response()->json([
            'suggestions' => $suggestions
        ]);
    }
    
    /**
     * Get job recommendations for dashboard
     */
    public function jobRecommendations(Request $request)
    {
        $user = Auth::user();
        $limit = $request->get('limit', 3);
        
        // Get job recommendations based on user profile and connections
        $jobs = Job::with(['company'])
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->limit($limit * 2)
            ->get();
        
        // Calculate match scores and add insights
        $jobs = $jobs->map(function ($job) use ($user) {
            $matchScore = $this->calculateJobMatchScore($user, $job);
            
            return [
                'id' => $job->id,
                'title' => $job->title,
                'company_name' => $job->company->name ?? 'Unknown Company',
                'location' => $job->location,
                'employment_type' => $job->employment_type,
                'salary_range' => $job->salary_min && $job->salary_max 
                    ? '$' . number_format($job->salary_min) . ' - $' . number_format($job->salary_max)
                    : null,
                'match_score' => $matchScore,
                'connection_insights' => $this->getJobConnectionInsights($user, $job),
                'matching_skills' => $this->getMatchingSkills($user, $job),
                'created_at' => $job->created_at,
                'is_saved' => false // Would check saved jobs table
            ];
        })->sortByDesc('match_score')->take($limit)->values();
        
        return response()->json([
            'jobs' => $jobs
        ]);
    }
    
    /**
     * Get upcoming events for dashboard
     */
    public function upcomingEvents(Request $request)
    {
        $user = Auth::user();
        $limit = $request->get('limit', 3);
        
        $events = Event::where('start_date', '>', now())
            ->where(function ($query) use ($user) {
                // Public events or events user is invited to
                $query->where('visibility', 'public')
                      ->orWhereHas('attendees', function ($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            })
            ->orderBy('start_date', 'asc')
            ->limit($limit)
            ->get();
        
        $events = $events->map(function ($event) use ($user) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'start_date' => $event->start_date,
                'location' => $event->location,
                'is_virtual' => $event->is_virtual,
                'attendee_count' => $event->attendees()->count(),
                'rsvp_status' => $event->attendees()
                    ->where('user_id', $user->id)
                    ->first()?->pivot?->status
            ];
        });
        
        return response()->json([
            'events' => $events
        ]);
    }
    
    /**
     * Calculate job match score based on user profile
     */
    private function calculateJobMatchScore($user, $job)
    {
        $score = 50; // Base score
        
        // Add scoring logic based on:
        // - Skills match
        // - Experience level
        // - Industry match
        // - Location preference
        // - Connection insights
        
        // For now, return a random score between 60-95
        return rand(60, 95);
    }
    
    /**
     * Get connection insights for a job
     */
    private function getJobConnectionInsights($user, $job)
    {
        // Check if user has connections at the company
        $connectionCount = $user->connections()
            ->whereHas('graduateProfile', function ($query) use ($job) {
                $query->where('current_company', $job->company->name ?? '');
            })
            ->count();
        
        if ($connectionCount > 0) {
            return $connectionCount === 1 
                ? '1 connection works here'
                : "{$connectionCount} connections work here";
        }
        
        return null;
    }
    
    /**
     * Get matching skills between user and job
     */
    private function getMatchingSkills($user, $job)
    {
        // This would compare user skills with job requirements
        // For now, return some sample skills
        $sampleSkills = ['PHP', 'Laravel', 'Vue.js', 'JavaScript', 'MySQL', 'Git'];
        return array_slice($sampleSkills, 0, rand(2, 4));
    }
}