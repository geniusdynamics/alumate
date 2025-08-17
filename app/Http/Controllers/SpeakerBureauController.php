<?php

namespace App\Http\Controllers;

use App\Models\SpeakerProfile;
use App\Models\SpeakerRequest;
use App\Models\SpeakingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SpeakerBureauController extends Controller
{
    public function index(Request $request)
    {
        // Build query for speakers
        $query = SpeakerProfile::with(['user', 'speakingEvents', 'testimonials'])
            ->where('status', 'active')
            ->where('is_available', true);

        // Apply search filters
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('user', function ($userQuery) use ($searchTerm) {
                    $userQuery->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('current_position', 'like', "%{$searchTerm}%")
                        ->orWhere('current_company', 'like', "%{$searchTerm}%");
                })
                    ->orWhere('bio', 'like', "%{$searchTerm}%")
                    ->orWhere('speaking_topics', 'like', "%{$searchTerm}%");
            });
        }

        // Apply topic filter
        if ($request->filled('topic')) {
            $query->whereJsonContains('speaking_topics', $request->topic);
        }

        // Apply industry filter
        if ($request->filled('industry')) {
            $query->whereHas('user', function ($userQuery) use ($request) {
                $userQuery->where('industry', $request->industry);
            });
        }

        // Apply event type filter
        if ($request->filled('event_type')) {
            $query->whereJsonContains('preferred_event_types', $request->event_type);
        }

        // Apply availability filter
        if ($request->filled('availability')) {
            switch ($request->availability) {
                case 'immediate':
                    $query->where('availability_status', 'available');
                    break;
                case 'this_month':
                    $query->where('next_available_date', '<=', now()->endOfMonth());
                    break;
                case 'next_month':
                    $query->where('next_available_date', '<=', now()->addMonth()->endOfMonth());
                    break;
                case 'flexible':
                    $query->where('travel_preference', '!=', 'local_only');
                    break;
            }
        }

        // Apply sorting
        switch ($request->get('sort', 'relevance')) {
            case 'rating':
                $query->orderBy('average_rating', 'desc');
                break;
            case 'experience':
                $query->withCount('speakingEvents')->orderBy('speaking_events_count', 'desc');
                break;
            case 'recent':
                $query->orderBy('last_active_at', 'desc');
                break;
            case 'alphabetical':
                $query->join('users', 'speaker_profiles.user_id', '=', 'users.id')
                    ->orderBy('users.name', 'asc');
                break;
            default: // relevance
                $query->orderBy('average_rating', 'desc')
                    ->orderBy('speaking_events_count', 'desc');
                break;
        }

        // Get paginated results
        $speakers = $query->paginate(12)->withQueryString();

        // Get featured speakers
        $featuredSpeakers = SpeakerProfile::with(['user', 'testimonials'])
            ->where('is_featured', true)
            ->where('status', 'active')
            ->limit(3)
            ->get();

        // Get upcoming speaking events
        $upcomingEvents = SpeakingEvent::with(['speaker.user', 'organizer'])
            ->where('event_date', '>=', now())
            ->where('status', 'confirmed')
            ->orderBy('event_date', 'asc')
            ->limit(6)
            ->get();

        // Get speaking topics for filter
        $speakingTopics = SpeakerProfile::whereNotNull('speaking_topics')
            ->get()
            ->pluck('speaking_topics')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        // Get industries for filter
        $industries = SpeakerProfile::join('users', 'speaker_profiles.user_id', '=', 'users.id')
            ->whereNotNull('users.industry')
            ->distinct()
            ->pluck('users.industry')
            ->sort()
            ->values();

        // Get completed events count
        $completedEvents = SpeakingEvent::where('status', 'completed')->count();

        return Inertia::render('SpeakerBureau/Index', [
            'speakers' => $speakers,
            'featuredSpeakers' => $featuredSpeakers,
            'upcomingEvents' => $upcomingEvents,
            'speakingTopics' => $speakingTopics,
            'industries' => $industries,
            'completedEvents' => $completedEvents,
            'filters' => $request->only(['search', 'topic', 'industry', 'event_type', 'availability', 'sort']),
        ]);
    }

    public function show(SpeakerProfile $speaker)
    {
        $speaker->load(['user', 'speakingEvents.testimonials', 'testimonials']);

        return Inertia::render('SpeakerBureau/Speaker', [
            'speaker' => $speaker,
            'recentEvents' => $speaker->speakingEvents()
                ->with('testimonials')
                ->where('status', 'completed')
                ->orderBy('event_date', 'desc')
                ->limit(5)
                ->get(),
            'upcomingEvents' => $speaker->speakingEvents()
                ->where('event_date', '>=', now())
                ->where('status', 'confirmed')
                ->orderBy('event_date', 'asc')
                ->limit(3)
                ->get(),
        ]);
    }

    public function request(Request $request, ?SpeakerProfile $speaker = null)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'speaker_id' => 'nullable|exists:speaker_profiles,id',
                'event_title' => 'required|string|max:255',
                'event_description' => 'required|string|max:2000',
                'event_date' => 'required|date|after:today',
                'event_duration' => 'required|integer|min:15|max:480',
                'event_type' => 'required|in:keynote,panel,workshop,classroom,career_fair,networking',
                'audience_size' => 'required|integer|min:1',
                'audience_type' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'is_virtual' => 'boolean',
                'budget_range' => 'nullable|string',
                'special_requirements' => 'nullable|string|max:1000',
                'contact_name' => 'required|string|max:255',
                'contact_email' => 'required|email',
                'contact_phone' => 'nullable|string|max:20',
                'organization' => 'required|string|max:255',
            ]);

            $speakerRequest = SpeakerRequest::create([
                'requester_id' => Auth::id(),
                'speaker_id' => $validated['speaker_id'],
                'event_title' => $validated['event_title'],
                'event_description' => $validated['event_description'],
                'event_date' => $validated['event_date'],
                'event_duration' => $validated['event_duration'],
                'event_type' => $validated['event_type'],
                'audience_size' => $validated['audience_size'],
                'audience_type' => $validated['audience_type'],
                'location' => $validated['location'],
                'is_virtual' => $validated['is_virtual'] ?? false,
                'budget_range' => $validated['budget_range'],
                'special_requirements' => $validated['special_requirements'],
                'contact_name' => $validated['contact_name'],
                'contact_email' => $validated['contact_email'],
                'contact_phone' => $validated['contact_phone'],
                'organization' => $validated['organization'],
                'status' => 'pending',
            ]);

            // TODO: Send notification to speaker or admin

            return redirect()->route('speaker-bureau.index')
                ->with('success', 'Speaker request submitted successfully!');
        }

        return Inertia::render('SpeakerBureau/Request', [
            'speaker' => $speaker,
            'eventTypes' => [
                'keynote' => 'Keynote Speech',
                'panel' => 'Panel Discussion',
                'workshop' => 'Workshop/Training',
                'classroom' => 'Classroom Visit',
                'career_fair' => 'Career Fair',
                'networking' => 'Networking Event',
            ],
        ]);
    }

    public function join(Request $request)
    {
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'bio' => 'required|string|max:2000',
                'speaking_topics' => 'required|array|min:1',
                'speaking_topics.*' => 'string|max:100',
                'preferred_event_types' => 'required|array|min:1',
                'preferred_event_types.*' => 'in:keynote,panel,workshop,classroom,career_fair,networking',
                'travel_preference' => 'required|in:local_only,regional,national,international,virtual_only,hybrid',
                'speaking_experience' => 'required|string|max:1000',
                'notable_events' => 'nullable|array',
                'notable_events.*' => 'string|max:255',
                'sample_videos' => 'nullable|array',
                'sample_videos.*' => 'url',
                'availability_notes' => 'nullable|string|max:500',
                'fee_range' => 'nullable|string',
                'special_requirements' => 'nullable|string|max:1000',
            ]);

            $user = Auth::user();

            $speakerProfile = SpeakerProfile::create([
                'user_id' => $user->id,
                'bio' => $validated['bio'],
                'speaking_topics' => $validated['speaking_topics'],
                'preferred_event_types' => $validated['preferred_event_types'],
                'travel_preference' => $validated['travel_preference'],
                'speaking_experience' => $validated['speaking_experience'],
                'notable_events' => $validated['notable_events'] ?? [],
                'sample_videos' => $validated['sample_videos'] ?? [],
                'availability_notes' => $validated['availability_notes'],
                'fee_range' => $validated['fee_range'],
                'special_requirements' => $validated['special_requirements'],
                'status' => 'pending_review',
                'is_available' => true,
                'availability_status' => 'available',
            ]);

            return redirect()->route('speaker-bureau.index')
                ->with('success', 'Speaker profile submitted for review!');
        }

        return Inertia::render('SpeakerBureau/Join');
    }

    public function events()
    {
        $events = SpeakingEvent::with(['speaker.user', 'organizer'])
            ->where('is_public', true)
            ->where('event_date', '>=', now())
            ->orderBy('event_date', 'asc')
            ->paginate(12);

        return Inertia::render('SpeakerBureau/Events', [
            'events' => $events,
        ]);
    }
}
