<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    /**
     * RSVP to an event
     */
    public function rsvp(Request $request, Event $event)
    {
        $request->validate([
            'status' => ['required', Rule::in(['attending', 'maybe', 'not_attending'])]
        ]);

        $user = Auth::user();

        $attendee = EventAttendee::updateOrCreate(
            [
                'event_id' => $event->id,
                'user_id' => $user->id
            ],
            [
                'status' => $request->status,
                'rsvp_date' => now()
            ]
        );

        return response()->json([
            'message' => 'RSVP updated successfully',
            'status' => $request->status,
            'attendee' => $attendee
        ]);
    }

    /**
     * Cancel RSVP to an event
     */
    public function cancelRsvp(Event $event)
    {
        $user = Auth::user();

        EventAttendee::where('event_id', $event->id)
                    ->where('user_id', $user->id)
                    ->delete();

        return response()->json([
            'message' => 'RSVP cancelled successfully'
        ]);
    }
}