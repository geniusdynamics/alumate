<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ConnectionController extends Controller
{
    public function request(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500'
        ]);

        $user = Auth::user();
        $targetUserId = $request->user_id;

        // Check if user is trying to connect to themselves
        if ($user->id === $targetUserId) {
            throw ValidationException::withMessages([
                'user_id' => 'You cannot connect to yourself.'
            ]);
        }

        // Check if connection already exists
        $existingConnection = Connection::where(function ($query) use ($user, $targetUserId) {
            $query->where('user_id', $user->id)
                  ->where('connected_user_id', $targetUserId);
        })->orWhere(function ($query) use ($user, $targetUserId) {
            $query->where('user_id', $targetUserId)
                  ->where('connected_user_id', $user->id);
        })->first();

        if ($existingConnection) {
            throw ValidationException::withMessages([
                'user_id' => 'Connection already exists or is pending.'
            ]);
        }

        // Create connection request
        $connection = Connection::create([
            'user_id' => $user->id,
            'connected_user_id' => $targetUserId,
            'status' => 'pending',
            'message' => $request->message,
        ]);

        // TODO: Send notification to target user

        return response()->json([
            'message' => 'Connection request sent successfully.',
            'connection' => $connection
        ]);
    }

    public function accept(Connection $connection)
    {
        $user = Auth::user();

        // Check if user is the recipient of the connection request
        if ($connection->connected_user_id !== $user->id) {
            abort(403, 'You can only accept connection requests sent to you.');
        }

        // Check if connection is still pending
        if ($connection->status !== 'pending') {
            throw ValidationException::withMessages([
                'connection' => 'This connection request is no longer pending.'
            ]);
        }

        // Accept the connection
        $connection->update([
            'status' => 'accepted',
            'accepted_at' => now()
        ]);

        // TODO: Send notification to requester

        return response()->json([
            'message' => 'Connection request accepted.',
            'connection' => $connection->load(['user', 'connectedUser'])
        ]);
    }

    public function decline(Connection $connection)
    {
        $user = Auth::user();

        // Check if user is the recipient of the connection request
        if ($connection->connected_user_id !== $user->id) {
            abort(403, 'You can only decline connection requests sent to you.');
        }

        // Check if connection is still pending
        if ($connection->status !== 'pending') {
            throw ValidationException::withMessages([
                'connection' => 'This connection request is no longer pending.'
            ]);
        }

        // Decline the connection
        $connection->update([
            'status' => 'declined',
            'declined_at' => now()
        ]);

        return response()->json([
            'message' => 'Connection request declined.'
        ]);
    }

    public function remove(Connection $connection)
    {
        $user = Auth::user();

        // Check if user is part of this connection
        if ($connection->user_id !== $user->id && $connection->connected_user_id !== $user->id) {
            abort(403, 'You can only remove your own connections.');
        }

        // Check if connection is accepted
        if ($connection->status !== 'accepted') {
            throw ValidationException::withMessages([
                'connection' => 'You can only remove accepted connections.'
            ]);
        }

        // Remove the connection
        $connection->delete();

        return response()->json([
            'message' => 'Connection removed successfully.'
        ]);
    }

    public function startConversation(Request $request)
    {
        $request->validate([
            'participant_id' => 'required|exists:users,id',
            'context' => 'nullable|string',
            'context_id' => 'nullable|integer',
            'initial_message' => 'nullable|string|max:1000'
        ]);

        $user = Auth::user();
        $participantId = $request->participant_id;

        // Check if conversation already exists
        $existingConversation = \DB::table('conversations')
            ->where(function ($query) use ($user, $participantId) {
                $query->whereJsonContains('participants', [$user->id, $participantId])
                      ->orWhereJsonContains('participants', [$participantId, $user->id]);
            })
            ->first();

        if ($existingConversation) {
            return response()->json([
                'success' => true,
                'data' => ['conversation_id' => $existingConversation->id],
                'message' => 'Conversation already exists.'
            ]);
        }

        // Create new conversation
        $conversationId = \DB::table('conversations')->insertGetId([
            'participants' => json_encode([$user->id, $participantId]),
            'context' => $request->context,
            'context_id' => $request->context_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Send initial message if provided
        if ($request->initial_message) {
            \DB::table('messages')->insert([
                'conversation_id' => $conversationId,
                'sender_id' => $user->id,
                'content' => $request->initial_message,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => ['conversation_id' => $conversationId],
            'message' => 'Conversation started successfully.'
        ]);
    }
}
