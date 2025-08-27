<?php

namespace App\Services;

use App\Models\User;
use App\Models\VideoCall;
use Illuminate\Support\Str;

class VideoCallService
{
    public function createCall(array $data): VideoCall
    {
        $roomId = $this->generateRoomId();
        $jitsiRoomName = $this->generateJitsiRoomName($data['title']);

        return VideoCall::create([
            'host_user_id' => $data['host_user_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'provider' => $data['provider'] ?? 'jitsi',
            'scheduled_at' => $data['scheduled_at'],
            'max_participants' => $data['max_participants'] ?? 10,
            'room_id' => $roomId,
            'jitsi_room_name' => $jitsiRoomName,
            'settings' => $data['settings'] ?? [],
        ]);
    }

    public function generateJitsiUrl(VideoCall $call, User $user): string
    {
        $baseUrl = config('services.jitsi.domain', 'meet.jit.si');
        $roomName = $call->jitsi_room_name;

        $params = [
            'userInfo.displayName' => $user->name,
            'userInfo.email' => $user->email,
            'config.startWithAudioMuted' => 'true',
            'config.startWithVideoMuted' => 'false',
        ];

        return "https://{$baseUrl}/{$roomName}?".http_build_query($params);
    }

    public function joinCall(VideoCall $call, User $user, string $role = 'participant'): void
    {
        $call->participants()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'role' => $call->isHost($user) ? 'host' : $role,
            'joined_at' => now(),
        ]);

        // Update call status to active if first participant joins
        if ($call->status === 'scheduled') {
            $call->update(['status' => 'active', 'started_at' => now()]);
        }
    }

    public function leaveCall(VideoCall $call, User $user): void
    {
        $participant = $call->participants()->where('user_id', $user->id)->first();
        if ($participant) {
            $participant->leave();
        }

        // End call if host leaves or no participants remain
        if ($call->isHost($user) || $call->participants()->active()->count() === 0) {
            $this->endCall($call);
        }
    }

    public function endCall(VideoCall $call): void
    {
        $call->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        // Mark all active participants as left
        $call->participants()->active()->update(['left_at' => now()]);
    }

    private function generateRoomId(): string
    {
        return 'room_'.Str::uuid();
    }

    private function generateJitsiRoomName(string $title): string
    {
        return 'alumni_'.Str::slug($title).'_'.time();
    }

    public function getCallAnalytics(VideoCall $call): array
    {
        return [
            'duration' => $call->duration,
            'participants_count' => $call->participants()->count(),
            'max_concurrent_participants' => $this->getMaxConcurrentParticipants($call),
            'recordings_count' => $call->recordings()->completed()->count(),
            'screen_sharing_sessions' => $call->screenSharingSessions()->count(),
        ];
    }

    private function getMaxConcurrentParticipants(VideoCall $call): int
    {
        // This would require more complex logic to track concurrent participants
        // For now, return the total participant count
        return $call->participants()->count();
    }
}
