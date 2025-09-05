<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// User-specific private channels
Broadcast::channel('user.{userId}.timeline', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('user.{userId}.notifications', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('user.{userId}.connections', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Circle-specific private channels
Broadcast::channel('circle.{circleId}', function ($user, $circleId) {
    // Check if user is a member of the circle
    return $user->circles()->where('circles.id', $circleId)->exists();
});

Broadcast::channel('circle.{circleId}.activity', function ($user, $circleId) {
    // Check if user is a member of the circle
    return $user->circles()->where('circles.id', $circleId)->exists();
});

// Group-specific private channels
Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    // Check if user is a member of the group
    return $user->groups()->where('groups.id', $groupId)->exists();
});

Broadcast::channel('group.{groupId}.activity', function ($user, $groupId) {
    // Check if user is a member of the group
    return $user->groups()->where('groups.id', $groupId)->exists();
});

// Connection-specific private channels
Broadcast::channel('connection.{connectionId}', function ($user, $connectionId) {
    // Check if user is part of this connection
    return $user->connections()
        ->where('id', $connectionId)
        ->orWhere('from_user_id', $user->id)
        ->orWhere('to_user_id', $user->id)
        ->exists();
});

// Event-specific channels
Broadcast::channel('event.{eventId}', function ($user, $eventId) {
    // Check if user has access to the event (public events or user is attendee)
    $event = \App\Models\Event::find($eventId);

    if (! $event) {
        return false;
    }

    // Public events are accessible to all authenticated users
    if ($event->visibility === 'public') {
        return true;
    }

    // Private events require attendance or ownership
    return $event->attendees()->where('user_id', $user->id)->exists() ||
           $event->organizer_id === $user->id;
});

// Job-specific channels
Broadcast::channel('job.{jobId}', function ($user, $jobId) {
    // Check if user has access to the job posting
    $job = \App\Models\Job::find($jobId);

    if (! $job) {
        return false;
    }

    // Public jobs are accessible to all authenticated users
    if ($job->visibility === 'public') {
        return true;
    }

    // Private jobs require specific access
    return $job->posted_by === $user->id ||
           $job->applications()->where('user_id', $user->id)->exists();
});

// Presence channels for real-time collaboration
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    // Check if user is part of the chat
    $chat = \App\Models\Chat::find($chatId);

    if (! $chat) {
        return false;
    }

    return $chat->participants()->where('user_id', $user->id)->exists();
});

// Alumni map presence channel
Broadcast::channel('alumni-map', function ($user) {
    // All authenticated users can join the alumni map presence channel
    return [
        'id' => $user->id,
        'name' => $user->name,
        'username' => $user->username,
        'avatar_url' => $user->avatar_url,
        'current_position' => $user->current_position,
        'current_company' => $user->current_company,
        'location' => [
            'city' => $user->city,
            'state' => $user->state,
            'country' => $user->country,
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
        ],
    ];
});

// Messaging System channels
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // Check if user is a participant in the conversation
    $conversation = \App\Models\Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    return $conversation->hasParticipant($user);
});

Broadcast::channel('user.{userId}.messages', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Activity feed presence channel
Broadcast::channel('activity-feed', function ($user) {
    // All authenticated users can join the activity feed presence channel
    return [
        'id' => $user->id,
        'name' => $user->name,
        'username' => $user->username,
        'avatar_url' => $user->avatar_url,
        'last_active' => now(),
    ];
});

// Template preview channels
Broadcast::channel('template.{templateId}.preview', function ($user, $templateId) {
    // Check if user has access to this template
    // For now, allow all authenticated users (can be restricted later based on permissions)
    return [
        'user_id' => $user->id,
        'template_id' => $templateId,
        'joined_at' => now()->toISOString()
    ];
});

Broadcast::channel('template.{templateId}.collaborators', function ($user, $templateId) {
    // Presence channel for template collaborators
    // Can add authorization logic here based on template permissions
    return [
        'user_id' => $user->id,
        'user_name' => $user->name,
        'template_id' => $templateId,
        'joined_at' => now()->toISOString()
    ];
});

// Tenant-specific template preview channels
Broadcast::channel('tenant.{tenantId}.template-previews', function ($user, $tenantId) {
    // Verify user belongs to the tenant (with tenant isolation)
    if (config('database.multi_tenant', false)) {
        try {
            $tenant = tenant();
            return $tenant && $tenant->id === $tenantId;
        } catch (\Exception $e) {
            return false;
        }
    }

    // For single-tenant, allow all authenticated users
    return true;
});

// User-specific template preview channels
Broadcast::channel('user.{userId}.template-previews', function ($user, $userId) {
    // Only allow users to listen to their own template preview updates
    return (int) $user->id === (int) $userId;
});
