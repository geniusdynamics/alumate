<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Build query for events
        $query = Event::with(['creator', 'institution', 'registrations'])
            ->where('status', 'published')
            ->where('start_date', '>=', now());

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('institution_id')) {
            $query->where('institution_id', $request->institution_id);
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%'.$request->location.'%');
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%'.$searchTerm.'%')
                    ->orWhere('description', 'like', '%'.$searchTerm.'%');
            });
        }

        $events = $query->orderBy('start_date', 'asc')->paginate(20);

        // Get filter options
        $institutions = Institution::all();
        $eventTypes = ['networking', 'workshop', 'seminar', 'reunion', 'career_fair', 'social', 'fundraising'];

        // Get user's registered events
        $userRegistrations = [];
        if ($user) {
            $userRegistrations = EventRegistration::where('user_id', $user->id)
                ->pluck('event_id')
                ->toArray();
        }

        return Inertia::render('Events/Index', [
            'events' => $events,
            'institutions' => $institutions,
            'eventTypes' => $eventTypes,
            'userRegistrations' => $userRegistrations,
            'filters' => $request->only(['type', 'institution_id', 'location', 'search']),
        ]);
    }

    public function create()
    {
        $user = Auth::user();

        // Check if user can create events
        if (! $user->can('create events')) {
            abort(403, 'You do not have permission to create events.');
        }

        $institutions = Institution::all();
        $eventTypes = ['networking', 'workshop', 'seminar', 'reunion', 'career_fair', 'social', 'fundraising'];

        return Inertia::render('Events/Create', [
            'institutions' => $institutions,
            'eventTypes' => $eventTypes,
        ]);
    }

    public function discovery(Request $request)
    {
        $user = Auth::user();

        // Build query for events
        $query = Event::with(['creator', 'institution', 'registrations'])
            ->where('status', 'published')
            ->where('start_date', '>=', now());

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%'.$request->location.'%');
        }

        if ($request->filled('date_range')) {
            $dateRange = $request->date_range;
            switch ($dateRange) {
                case 'this_week':
                    $query->whereBetween('start_date', [now(), now()->addWeek()]);
                    break;
                case 'this_month':
                    $query->whereBetween('start_date', [now(), now()->addMonth()]);
                    break;
                case 'next_month':
                    $query->whereBetween('start_date', [now()->addMonth(), now()->addMonths(2)]);
                    break;
            }
        }

        if ($request->filled('virtual_only') && $request->virtual_only) {
            $query->where('is_virtual', true);
        }

        // Apply sorting
        $sortBy = $request->get('sort', 'date');
        switch ($sortBy) {
            case 'popularity':
                $query->withCount('registrations')->orderBy('registrations_count', 'desc');
                break;
            case 'relevance':
                // Sort by relevance based on user's profile
                $query->orderBy('start_date', 'asc');
                break;
            default:
                $query->orderBy('start_date', 'asc');
        }

        $events = $query->paginate(20);

        // Get featured events
        $featuredEvents = Event::where('is_featured', true)
            ->where('status', 'published')
            ->where('start_date', '>=', now())
            ->with(['creator', 'institution', 'registrations'])
            ->limit(4)
            ->get();

        // Get user's events
        $myEvents = [];
        if ($user) {
            $myEvents = Event::whereHas('registrations', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
                ->where('start_date', '>=', now())
                ->limit(5)
                ->get();
        }

        // Get upcoming reunions
        $upcomingReunions = Event::where('type', 'reunion')
            ->where('status', 'published')
            ->where('start_date', '>=', now())
            ->limit(3)
            ->get();

        // Get event categories with counts
        $eventCategories = Event::where('status', 'published')
            ->where('start_date', '>=', now())
            ->selectRaw('type as name, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->map(function ($category) {
                return [
                    'name' => ucfirst(str_replace('_', ' ', $category->name)),
                    'count' => $category->count,
                ];
            });

        // Get filter options
        $eventTypes = ['networking', 'workshop', 'seminar', 'reunion', 'career_fair', 'social', 'fundraising'];
        $locations = Event::where('status', 'published')
            ->distinct()
            ->pluck('location')
            ->filter()
            ->sort()
            ->values();

        return Inertia::render('Events/Discovery', [
            'events' => $events,
            'featuredEvents' => $featuredEvents,
            'myEvents' => $myEvents,
            'upcomingReunions' => $upcomingReunions,
            'eventCategories' => $eventCategories,
            'eventTypes' => $eventTypes,
            'locations' => $locations,
            'filters' => $request->only(['type', 'location', 'date_range', 'virtual_only']),
        ]);
    }

    public function myEvents()
    {
        $user = Auth::user();

        // Get events user has registered for
        $registeredEvents = Event::whereHas('registrations', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->with(['creator', 'institution'])
            ->orderBy('start_date', 'asc')
            ->get();

        // Get events user has created (if applicable)
        $createdEvents = Event::where('creator_id', $user->id)
            ->with(['institution', 'registrations'])
            ->orderBy('start_date', 'asc')
            ->get();

        // Get upcoming events user might be interested in
        $suggestedEvents = $this->getSuggestedEvents($user);

        return Inertia::render('Events/MyEvents', [
            'registeredEvents' => $registeredEvents,
            'createdEvents' => $createdEvents,
            'suggestedEvents' => $suggestedEvents,
        ]);
    }

    public function show(Event $event)
    {
        $user = Auth::user();

        $event->load(['creator', 'institution', 'registrations.user']);

        // Check if user is registered
        $isRegistered = false;
        if ($user) {
            $isRegistered = EventRegistration::where('event_id', $event->id)
                ->where('user_id', $user->id)
                ->exists();
        }

        // Get similar events
        $similarEvents = Event::where('id', '!=', $event->id)
            ->where('type', $event->type)
            ->where('status', 'published')
            ->where('start_date', '>=', now())
            ->limit(3)
            ->get();

        return Inertia::render('Events/Show', [
            'event' => $event,
            'isRegistered' => $isRegistered,
            'similarEvents' => $similarEvents,
        ]);
    }

    private function getSuggestedEvents($user)
    {
        $graduate = $user->graduate;
        if (! $graduate) {
            return collect();
        }

        // Get events from user's institution
        $institutionEvents = Event::where('institution_id', $graduate->institution_id)
            ->where('status', 'published')
            ->where('start_date', '>=', now())
            ->whereDoesntHave('registrations', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->limit(5)
            ->get();

        // Get networking events
        $networkingEvents = Event::where('type', 'networking')
            ->where('status', 'published')
            ->where('start_date', '>=', now())
            ->whereDoesntHave('registrations', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->limit(3)
            ->get();

        return $institutionEvents->merge($networkingEvents)->unique('id');
    }
}
