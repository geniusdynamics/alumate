<?php

namespace App\Http\Controllers;

use App\Models\Graduate;
use App\Models\User;
use App\Models\Connection;
use App\Models\Course;
use App\Models\Institution;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

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
            $query->where('current_location', 'like', '%' . $request->location . '%');
        }

        if ($request->filled('industry')) {
            $query->whereHas('currentJob', function ($q) use ($request) {
                $q->where('industry', 'like', '%' . $request->industry . '%');
            });
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('email', 'like', '%' . $searchTerm . '%');
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

    public function recommendations()
    {
        $user = Auth::user();
        $graduate = $user->graduate;

        if (!$graduate) {
            return Inertia::render('Alumni/Recommendations', [
                'recommendations' => collect(),
                'message' => 'Complete your graduate profile to get personalized recommendations.',
            ]);
        }

        // Get recommendations based on various criteria
        $recommendations = collect();

        // Same course and graduation year
        $coursemates = $this->getCoursemates($graduate);
        $recommendations = $recommendations->merge($coursemates);

        // Same institution, different course
        $institutionAlumni = $this->getInstitutionAlumni($graduate);
        $recommendations = $recommendations->merge($institutionAlumni);

        // Same industry/field
        $industryPeers = $this->getIndustryPeers($graduate);
        $recommendations = $recommendations->merge($industryPeers);

        // Remove duplicates and already connected users
        $recommendations = $recommendations->unique('id')
            ->filter(function ($alumni) use ($user) {
                return !$this->isAlreadyConnected($user, $alumni->user);
            })
            ->take(20);

        return Inertia::render('Alumni/Recommendations', [
            'recommendations' => $recommendations,
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
        if (!$graduate->current_industry) {
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
}
