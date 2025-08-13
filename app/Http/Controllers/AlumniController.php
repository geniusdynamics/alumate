<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Course;
use App\Models\Graduate;
use App\Models\Institution;
use App\Models\User;
use App\Services\AlumniMapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AlumniController extends Controller
{
    public function directory(Request $request)
    {
        $user = Auth::user();

        // Build query for alumni directory
        $query = Graduate::with(['user', 'course', 'institution', 'currentJob'])
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            });

        // Apply filters
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('institution_id')) {
            $query->where('institution_id', $request->institution_id);
        }

        if ($request->filled('graduation_year')) {
            $query->where('graduation_year', $request->graduation_year);
        }

        if ($request->filled('location')) {
            $query->where('current_location', 'like', '%'.$request->location.'%');
        }

        if ($request->filled('industry')) {
            $query->whereHas('currentJob', function ($q) use ($request) {
                $q->where('industry', 'like', '%'.$request->industry.'%');
            });
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('email', 'like', '%'.$searchTerm.'%');
            });
        }

        $alumni = $query->paginate(20);

        // Get filter options
        $courses = Course::all();
        $institutions = Institution::all();
        $graduationYears = Graduate::distinct()->pluck('graduation_year')->sort()->values();

        return Inertia::render('Alumni/Directory', [
            'alumni' => $alumni,
            'courses' => $courses,
            'institutions' => $institutions,
            'graduationYears' => $graduationYears,
            'filters' => $request->only(['course_id', 'institution_id', 'graduation_year', 'location', 'industry', 'search']),
        ]);
    }

    public function recommendations(Request $request)
    {
        $user = Auth::user();
        $graduate = $user->graduate;

        if (! $graduate) {
            return Inertia::render('Alumni/Recommendations', [
                'recommendations' => collect(),
                'connectionInsights' => collect(),
                'networkStats' => [
                    'total_connections' => 0,
                    'mutual_connections' => 0,
                    'same_institution' => 0,
                ],
                'institutions' => Institution::all(),
                'industries' => collect(),
                'currentFilters' => $request->only(['institution_id', 'industry', 'location']),
                'message' => 'Complete your graduate profile to get personalized recommendations.',
            ]);
        }

        // Get recommendations based on various criteria
        $recommendations = collect();

        // Same course and graduation year
        $coursemates = $this->getCoursemates($graduate);
        $recommendations = $recommendations->merge($coursemates->map(function ($alumni) {
            return [
                'user' => $alumni,
                'reason' => 'Same course and graduation year',
                'match_score' => 95,
                'mutual_connections' => 0, // Calculate actual mutual connections
            ];
        }));

        // Same institution, different course
        $institutionAlumni = $this->getInstitutionAlumni($graduate);
        $recommendations = $recommendations->merge($institutionAlumni->map(function ($alumni) {
            return [
                'user' => $alumni,
                'reason' => 'Same institution',
                'match_score' => 80,
                'mutual_connections' => 0,
            ];
        }));

        // Same industry/field
        $industryPeers = $this->getIndustryPeers($graduate);
        $recommendations = $recommendations->merge($industryPeers->map(function ($alumni) {
            return [
                'user' => $alumni,
                'reason' => 'Same industry',
                'match_score' => 70,
                'mutual_connections' => 0,
            ];
        }));

        // Apply filters
        if ($request->filled('institution_id')) {
            $recommendations = $recommendations->filter(function ($rec) use ($request) {
                return $rec['user']->institution_id == $request->institution_id;
            });
        }

        if ($request->filled('industry')) {
            $recommendations = $recommendations->filter(function ($rec) use ($request) {
                return $rec['user']->current_industry == $request->industry;
            });
        }

        if ($request->filled('location')) {
            $recommendations = $recommendations->filter(function ($rec) use ($request) {
                return stripos($rec['user']->current_location, $request->location) !== false;
            });
        }

        // Remove duplicates and already connected users
        $recommendations = $recommendations->unique('user.id')
            ->filter(function ($rec) use ($user) {
                return ! $this->isAlreadyConnected($user, $rec['user']->user);
            })
            ->take(20);

        // Get network stats
        $networkStats = $this->getNetworkStats($user);

        // Get connection insights
        $connectionInsights = $this->getConnectionInsights($user, $graduate);

        // Get filter options
        $institutions = Institution::all();
        $industries = Graduate::distinct()->pluck('current_industry')->filter()->sort()->values();

        return Inertia::render('Alumni/Recommendations', [
            'recommendations' => $recommendations->values(),
            'connectionInsights' => $connectionInsights,
            'networkStats' => $networkStats,
            'institutions' => $institutions,
            'industries' => $industries,
            'currentFilters' => $request->only(['institution_id', 'industry', 'location']),
        ]);
    }

    public function connections()
    {
        $user = Auth::user();

        // Get user's connections
        $connections = Connection::where('user_id', $user->id)
            ->orWhere('connected_user_id', $user->id)
            ->where('status', 'accepted')
            ->with(['user.graduate', 'connectedUser.graduate'])
            ->get()
            ->map(function ($connection) use ($user) {
                return $connection->user_id === $user->id
                    ? $connection->connectedUser
                    : $connection->user;
            });

        // Get pending connection requests
        $pendingRequests = Connection::where('connected_user_id', $user->id)
            ->where('status', 'pending')
            ->with(['user.graduate'])
            ->get();

        // Get sent requests
        $sentRequests = Connection::where('user_id', $user->id)
            ->where('status', 'pending')
            ->with(['connectedUser.graduate'])
            ->get();

        return Inertia::render('Alumni/Connections', [
            'connections' => $connections,
            'pendingRequests' => $pendingRequests,
            'sentRequests' => $sentRequests,
        ]);
    }

    public function map()
    {
        $user = Auth::user();

        // Get alumni with location data (respecting privacy settings)
        $alumni = User::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('location_privacy', '!=', 'private')
            ->with(['educations.school', 'currentEmployment.company'])
            ->get()
            ->map(function ($alumnus) {
                return [
                    'id' => $alumnus->id,
                    'name' => $alumnus->name,
                    'avatar_url' => $alumnus->avatar_url,
                    'current_title' => $alumnus->current_title,
                    'current_company' => $alumnus->current_company,
                    'location' => $alumnus->location,
                    'latitude' => (float) $alumnus->latitude,
                    'longitude' => (float) $alumnus->longitude,
                    'location_privacy' => $alumnus->location_privacy ?? 'alumni_only'
                ];
            });

        // Get regional statistics
        $stats = [
            'total_alumni' => $alumni->count(),
            'by_country' => $alumni->groupBy('country')->map->count()->sortDesc()->take(10),
            'by_region' => $alumni->groupBy('region')->map->count()->sortDesc()->take(10),
            'by_industry' => $alumni->whereNotNull('current_industry')->groupBy('current_industry')->map->count()->sortDesc()->take(10)
        ];

        // Get filter options
        $schools = Institution::all(['id', 'name']);
        $industries = User::whereNotNull('current_industry')->distinct()->pluck('current_industry')->filter()->sort()->values();
        $countries = User::whereNotNull('country')->distinct()->pluck('country')->filter()->sort()->values();
        $graduationYears = range(date('Y') - 50, date('Y') + 5);

        // Get user's location if available
        $userLocation = null;
        if ($user->latitude && $user->longitude) {
            $userLocation = [
                'latitude' => (float) $user->latitude,
                'longitude' => (float) $user->longitude,
                'privacy' => $user->location_privacy ?? 'alumni_only'
            ];
        }

        return Inertia::render('Alumni/Map', [
            'alumni' => $alumni,
            'stats' => $stats,
            'schools' => $schools,
            'industries' => $industries,
            'countries' => $countries,
            'graduationYears' => $graduationYears,
            'userLocation' => $userLocation
        ]);
    }

    private function getCoursemates($graduate)
    {
        return Graduate::with(['user', 'course', 'institution'])
            ->where('course_id', $graduate->course_id)
            ->where('graduation_year', $graduate->graduation_year)
            ->where('id', '!=', $graduate->id)
            ->limit(5)
            ->get();
    }

    private function getInstitutionAlumni($graduate)
    {
        return Graduate::with(['user', 'course', 'institution'])
            ->where('institution_id', $graduate->institution_id)
            ->where('course_id', '!=', $graduate->course_id)
            ->where('id', '!=', $graduate->id)
            ->limit(5)
            ->get();
    }

    private function getIndustryPeers($graduate)
    {
        if (! $graduate->current_industry) {
            return collect();
        }

        return Graduate::with(['user', 'course', 'institution'])
            ->where('current_industry', $graduate->current_industry)
            ->where('id', '!=', $graduate->id)
            ->limit(5)
            ->get();
    }

    private function isAlreadyConnected($user, $targetUser)
    {
        return Connection::where(function ($query) use ($user, $targetUser) {
            $query->where('user_id', $user->id)
                ->where('connected_user_id', $targetUser->id);
        })->orWhere(function ($query) use ($user, $targetUser) {
            $query->where('user_id', $targetUser->id)
                ->where('connected_user_id', $user->id);
        })->exists();
    }

    private function getNetworkStats($user)
    {
        $totalConnections = Connection::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhere('connected_user_id', $user->id);
        })->where('status', 'accepted')->count();

        $graduate = $user->graduate;
        $sameInstitution = 0;
        $mutualConnections = 0;

        if ($graduate) {
            $sameInstitution = Connection::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('connected_user_id', $user->id);
            })
                ->where('status', 'accepted')
                ->whereHas('user.graduate', function ($q) use ($graduate) {
                    $q->where('institution_id', $graduate->institution_id);
                })
                ->orWhereHas('connectedUser.graduate', function ($q) use ($graduate) {
                    $q->where('institution_id', $graduate->institution_id);
                })
                ->count();
        }

        return [
            'total_connections' => $totalConnections,
            'mutual_connections' => $mutualConnections,
            'same_institution' => $sameInstitution,
        ];
    }

    private function getConnectionInsights($user, $graduate)
    {
        $insights = collect();

        if ($graduate) {
            // Industry insights
            $industryConnections = Connection::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('connected_user_id', $user->id);
            })
                ->where('status', 'accepted')
                ->count();

            if ($industryConnections < 5) {
                $insights->push([
                    'type' => 'industry_growth',
                    'title' => 'Expand Your Industry Network',
                    'message' => 'Connect with more professionals in your industry to unlock new opportunities.',
                    'action' => 'Find Industry Peers',
                ]);
            }

            // Institution insights
            $institutionConnections = Connection::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('connected_user_id', $user->id);
            })
                ->where('status', 'accepted')
                ->whereHas('user.graduate', function ($q) use ($graduate) {
                    $q->where('institution_id', $graduate->institution_id);
                })
                ->count();

            if ($institutionConnections < 3) {
                $insights->push([
                    'type' => 'institution_network',
                    'title' => 'Connect with Alumni',
                    'message' => 'Build stronger ties with fellow alumni from your institution.',
                    'action' => 'Browse Alumni Directory',
                ]);
            }
        }

        return $insights;
    }
}
