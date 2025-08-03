<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventFavorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function register(Event $event)
    {
        $user = Auth::user();

        // Check if event is still accepting registrations
        if ($event->status !== 'published') {
            throw ValidationException::withMessages([
                'event' => 'This event is not available for registration.'
            ]);
        }

        if ($event->registration_deadline && $event->registration_deadline < now()) {
            throw ValidationException::withMessages([
                'event' => 'Registration deadline has passed.'
            ]);
        }

        if ($event->max_attendees && $event->registrations()->count() >= $event->max_attendees) {
            throw ValidationException::withMessages([
                'event' => 'This event is fully booked.'
            ]);
        }

        // Check if user is already registered
        $existingRegistration = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingRegistration) {
            throw ValidationException::withMessages([
                'event' => 'You are already registered for this event.'
            ]);
        }

        // Create registration
        $registration = EventRegistration::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'confirmed',
            'registered_at' => now()
        ]);

        // TODO: Send confirmation email/notification

        return response()->json([
            'message' => 'Successfully registered for the event.',
            'registration' => $registration
        ]);
    }

    public function unregister(Event $event)
    {
        $user = Auth::user();

        // Find the registration
        $registration = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$registration) {
            throw ValidationException::withMessages([
                'event' => 'You are not registered for this event.'
            ]);
        }

        // Check if cancellation is allowed
        if ($event->cancellation_deadline && $event->cancellation_deadline < now()) {
            throw ValidationException::withMessages([
                'event' => 'Cancellation deadline has passed.'
            ]);
        }

        // Remove registration
        $registration->delete();

        return response()->json([
            'message' => 'Successfully unregistered from the event.'
        ]);
    }

    public function favorite(Event $event)
    {
        $user = Auth::user();

        // Check if already favorited
        $existingFavorite = EventFavorite::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingFavorite) {
            throw ValidationException::withMessages([
                'event' => 'Event is already in your favorites.'
            ]);
        }

        // Add to favorites
        EventFavorite::create([
            'event_id' => $event->id,
            'user_id' => $user->id
        ]);

        return response()->json([
            'message' => 'Event added to favorites.'
        ]);
    }

    public function unfavorite(Event $event)
    {
        $user = Auth::user();

        // Find the favorite
        $favorite = EventFavorite::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$favorite) {
            throw ValidationException::withMessages([
                'event' => 'Event is not in your favorites.'
            ]);
        }

        // Remove from favorites
        $favorite->delete();

        return response()->json([
            'message' => 'Event removed from favorites.'
        ]);
    }
}
