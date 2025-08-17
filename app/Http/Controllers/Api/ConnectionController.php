<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ConnectionController extends Controller
{
    /**
     * Send a connection request
     */
    public function sendRequest(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500'
        ]);

        $user = Auth::user();
        $targetUserId = $request->user_id;

        // Check if connection already exists
        $existingConnection = Connection::where(function ($query) use ($user, $targetUserId) {
            $query->where('requester_id', $user->id)
                  ->where('recipient_id', $targetUserId);
        })->orWhere(function ($query) use ($user, $targetUserId) {
            $query->where('requester_id', $targetUserId)
                  ->where('recipient_id', $user->id);
        })->first();

        if ($existingConnection) {
            return response()->json([
                'message' => 'Connection already exists or request already sent'
            ], 422);
        }

        $connection = Connection::create([
            'requester_id' => $user->id,
            'recipient_id' => $targetUserId,
            'status' => 'pending',
            'message' => $request->message
        ]);

        return response()->json([
            'message' => 'Connection request sent successfully',
            'connection' => $connection
        ]);
    }

    /**
     * Accept a connection request
     */
    public function acceptRequest(Connection $connection)
    {
        $user = Auth::user();

        if ($connection->recipient_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $connection->update([
            'status' => 'accepted',
            'connected_at' => now()
        ]);

        return response()->json([
            'message' => 'Connection request accepted',
            'connection' => $connection
        ]);
    }

    /**
     * Decline a connection request
     */
    public function declineRequest(Connection $connection)
    {
        $user = Auth::user();

        if ($connection->recipient_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $connection->update(['status' => 'declined']);

        return response()->json([
            'message' => 'Connection request declined'
        ]);
    }

    /**
     * Get user's connections
     */
    public function index()
    {
        $user = Auth::user();

        $connections = Connection::with(['requester', 'recipient'])
            ->where(function ($query) use ($user) {
                $query->where('requester_id', $user->id)
                      ->orWhere('recipient_id', $user->id);
            })
            ->where('status', 'accepted')
            ->get()
            ->map(function ($connection) use ($user) {
                $connectedUser = $connection->requester_id === $user->id 
                    ? $connection->recipient 
                    : $connection->requester;
                
                return [
                    'id' => $connection->id,
                    'user' => $connectedUser,
                    'connected_at' => $connection->connected_at
                ];
            });

        return response()->json(['connections' => $connections]);
    }

    /**
     * Get pending connection requests
     */
    public function requests()
    {
        $user = Auth::user();

        $requests = Connection::with(['requester'])
            ->where('recipient_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['requests' => $requests]);
    }
}