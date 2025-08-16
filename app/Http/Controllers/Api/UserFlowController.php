<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\Event;
use App\Models\Job;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserFlowController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get network-based job recommendations
     */
    public function getNetworkJobRecommendations()
    {
        try {
            $user = Auth::user();

            // Get user's connections
            $connectionIds = Connection::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('connected_user_id', $user->id);
            })
                ->where('status', 'accepted')
                ->get()
                ->map(function ($connection) use ($user) {
                    return $connection->user_id === $user->id
                        ? $connection->connected_user_id
                        : $connection->user_id;
                });

            // Get jobs from companies where connections work
            $jobs = Job::whereHas('employer.employees', function ($query) use ($connectionIds) {
                $query->whereIn('user_id', $connectionIds);
            })
                ->where('status', 'active')
                ->where('application_deadline', '>', now())
                ->with(['employer', 'employer.employees' => function ($query) use ($connectionIds) {
                    $query->whereIn('user_id', $connectionIds);
                }])
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'jobs' => $jobs,
                    'message' => 'Network-based job recommendations retrieved successfully',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get network job recommendations: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get event attendees for networking
     */
    public function getEventAttendees(Event $event)
    {
        try {
            $user = Auth::user();

            // Get other attendees of the event
            $attendees = User::whereHas('eventRegistrations', function ($query) use ($event) {
                $query->where('event_id', $event->id)
                    ->where('status', 'confirmed');
            })
                ->where('id', '!=', $user->id)
                ->with(['profile', 'currentEmployment'])
                ->limit(20)
                ->get();

            // Add connection status for each attendee
            $connectionIds = Connection::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('connected_user_id', $user->id);
            })
                ->pluck('user_id', 'connected_user_id')
                ->merge(
                    Connection::where(function ($query) use ($user) {
                        $query->where('user_id', $user->id)
                            ->orWhere('connected_user_id', $user->id);
                    })
                        ->pluck('connected_user_id', 'user_id')
                );

            $attendees->each(function ($attendee) use ($connectionIds) {
                $attendee->connection_status = $connectionIds->has($attendee->id) ? 'connected' : 'none';
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'attendees' => $attendees,
                    'message' => 'Event attendees retrieved successfully',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get event attendees: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get posts with engagement opportunities
     */
    public function getEngagementOpportunities()
    {
        try {
            $user = Auth::user();

            // Get posts from user's network that have low engagement
            $connectionIds = Connection::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('connected_user_id', $user->id);
            })
                ->where('status', 'accepted')
                ->get()
                ->map(function ($connection) use ($user) {
                    return $connection->user_id === $user->id
                        ? $connection->connected_user_id
                        : $connection->user_id;
                });

            $posts = Post::whereIn('user_id', $connectionIds)
                ->where('created_at', '>', now()->subDays(7))
                ->whereDoesntHave('engagements', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->withCount('engagements')
                ->having('engagements_count', '<', 5)
                ->with(['user', 'engagements', 'comments'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'posts' => $posts,
                    'message' => 'Engagement opportunities retrieved successfully',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get engagement opportunities: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get connection recommendations based on mutual connections and interests
     */
    public function getConnectionRecommendations()
    {
        try {
            $user = Auth::user();

            // Get user's current connections
            $currentConnectionIds = Connection::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('connected_user_id', $user->id);
            })
                ->get()
                ->map(function ($connection) use ($user) {
                    return $connection->user_id === $user->id
                        ? $connection->connected_user_id
                        : $connection->user_id;
                })
                ->push($user->id); // Exclude self

            // Find users with mutual connections
            $recommendations = User::whereHas('connections', function ($query) use ($currentConnectionIds) {
                $query->whereIn('user_id', $currentConnectionIds)
                    ->orWhereIn('connected_user_id', $currentConnectionIds);
            })
                ->whereNotIn('id', $currentConnectionIds)
                ->with(['profile', 'currentEmployment'])
                ->limit(10)
                ->get();

            // Add mutual connection count
            $recommendations->each(function ($recommendation) use ($currentConnectionIds) {
                $mutualCount = Connection::where(function ($query) use ($recommendation) {
                    $query->where('user_id', $recommendation->id)
                        ->orWhere('connected_user_id', $recommendation->id);
                })
                    ->where(function ($query) use ($currentConnectionIds) {
                        $query->whereIn('user_id', $currentConnectionIds)
                            ->orWhereIn('connected_user_id', $currentConnectionIds);
                    })
                    ->count();

                $recommendation->mutual_connections_count = $mutualCount;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'recommendations' => $recommendations,
                    'message' => 'Connection recommendations retrieved successfully',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get connection recommendations: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get career insights based on user's profile and network
     */
    public function getCareerInsights()
    {
        try {
            $user = Auth::user();
            $insights = [];

            // Skill gap analysis
            $userSkills = $user->skills()->pluck('name')->toArray();
            $trendingSkills = DB::table('job_skills')
                ->select('skill_name', DB::raw('COUNT(*) as demand'))
                ->whereNotIn('skill_name', $userSkills)
                ->groupBy('skill_name')
                ->orderBy('demand', 'desc')
                ->limit(5)
                ->get();

            if ($trendingSkills->isNotEmpty()) {
                $insights[] = [
                    'type' => 'skill_gap',
                    'title' => 'Skill Development Opportunity',
                    'message' => 'Consider learning '.$trendingSkills->first()->skill_name.' - it\'s in high demand',
                    'action' => 'View Skill Recommendations',
                    'data' => $trendingSkills,
                ];
            }

            // Network growth suggestions
            $connectionCount = Connection::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('connected_user_id', $user->id);
            })
                ->where('status', 'accepted')
                ->count();

            if ($connectionCount < 10) {
                $insights[] = [
                    'type' => 'network_growth',
                    'title' => 'Expand Your Network',
                    'message' => 'Connect with more alumni to unlock better opportunities',
                    'action' => 'Find Alumni',
                    'data' => ['current_connections' => $connectionCount],
                ];
            }

            // Career progression insights
            $similarProfiles = User::where('id', '!=', $user->id)
                ->whereHas('profile', function ($query) use ($user) {
                    $query->where('course_id', $user->profile->course_id ?? null)
                        ->where('graduation_year', '<=', ($user->profile->graduation_year ?? 0) - 2);
                })
                ->with('currentEmployment')
                ->limit(5)
                ->get();

            if ($similarProfiles->isNotEmpty()) {
                $insights[] = [
                    'type' => 'career_progression',
                    'title' => 'Career Path Insights',
                    'message' => 'See how alumni from your course have progressed in their careers',
                    'action' => 'View Career Paths',
                    'data' => $similarProfiles,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'insights' => $insights,
                    'message' => 'Career insights retrieved successfully',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get career insights: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Request job referral from connection
     */
    public function requestJobReferral(Request $request)
    {
        $request->validate([
            'referrer_id' => 'required|exists:users,id',
            'job_id' => 'required|exists:jobs,id',
            'message' => 'nullable|string|max:1000',
        ]);

        try {
            $user = Auth::user();

            // Check if connection exists
            $connection = Connection::where(function ($query) use ($user, $request) {
                $query->where('user_id', $user->id)
                    ->where('connected_user_id', $request->referrer_id);
            })
                ->orWhere(function ($query) use ($user, $request) {
                    $query->where('user_id', $request->referrer_id)
                        ->where('connected_user_id', $user->id);
                })
                ->where('status', 'accepted')
                ->first();

            if (! $connection) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be connected to request a referral',
                ], 403);
            }

            // Create referral request
            $referralRequest = DB::table('job_referral_requests')->insert([
                'requester_id' => $user->id,
                'referrer_id' => $request->referrer_id,
                'job_id' => $request->job_id,
                'message' => $request->message,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // TODO: Send notification to referrer

            return response()->json([
                'success' => true,
                'message' => 'Referral request sent successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send referral request: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Request introduction through mutual connection
     */
    public function requestIntroduction(Request $request)
    {
        $request->validate([
            'connection_id' => 'required|exists:users,id',
            'target_user_id' => 'nullable|exists:users,id',
            'job_id' => 'nullable|exists:jobs,id',
            'message' => 'required|string|max:1000',
        ]);

        try {
            $user = Auth::user();

            // Verify connection exists
            $connection = Connection::where(function ($query) use ($user, $request) {
                $query->where('user_id', $user->id)
                    ->where('connected_user_id', $request->connection_id);
            })
                ->orWhere(function ($query) use ($user, $request) {
                    $query->where('user_id', $request->connection_id)
                        ->where('connected_user_id', $user->id);
                })
                ->where('status', 'accepted')
                ->first();

            if (! $connection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Connection not found',
                ], 404);
            }

            // Create introduction request
            $introductionRequest = DB::table('introduction_requests')->insert([
                'requester_id' => $user->id,
                'connector_id' => $request->connection_id,
                'target_user_id' => $request->target_user_id,
                'job_id' => $request->job_id,
                'message' => $request->message,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // TODO: Send notification to connector

            return response()->json([
                'success' => true,
                'message' => 'Introduction request sent successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send introduction request: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit event feedback and get follow-up recommendations
     */
    public function submitEventFeedback(Request $request, Event $event)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:2000',
            'would_recommend' => 'required|boolean',
            'topics_of_interest' => 'nullable|array',
        ]);

        try {
            $user = Auth::user();

            // Check if user attended the event
            $registration = DB::table('event_registrations')
                ->where('event_id', $event->id)
                ->where('user_id', $user->id)
                ->where('status', 'confirmed')
                ->first();

            if (! $registration) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must have attended the event to provide feedback',
                ], 403);
            }

            // Save feedback
            DB::table('event_feedback')->insert([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'rating' => $request->rating,
                'feedback' => $request->feedback,
                'would_recommend' => $request->would_recommend,
                'topics_of_interest' => json_encode($request->topics_of_interest ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Get follow-up recommendations based on feedback
            $recommendations = [];

            if ($request->topics_of_interest) {
                $relatedEvents = Event::where('status', 'published')
                    ->where('id', '!=', $event->id)
                    ->where('start_date', '>', now())
                    ->where(function ($query) use ($request) {
                        foreach ($request->topics_of_interest as $topic) {
                            $query->orWhere('description', 'like', "%{$topic}%")
                                ->orWhere('title', 'like', "%{$topic}%");
                        }
                    })
                    ->limit(3)
                    ->get();

                if ($relatedEvents->isNotEmpty()) {
                    $recommendations['events'] = $relatedEvents;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => 'Thank you for your feedback!',
                    'recommendations' => $recommendations,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit feedback: '.$e->getMessage(),
            ], 500);
        }
    }
}
