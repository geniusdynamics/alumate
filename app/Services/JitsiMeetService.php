<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JitsiMeetService
{
    private string $jitsiDomain;
    private array $defaultConfig;

    public function __construct()
    {
        $this->jitsiDomain = config('services.jitsi.domain', 'meet.jit.si');
        $this->defaultConfig = config('services.jitsi.default_config', []);
    }

    /**
     * Create a Jitsi Meet room for an event
     */
    public function createMeeting(int $eventId, string $eventTitle): array
    {
        $event = Event::findOrFail($eventId);
        
        // Generate unique room ID
        $roomId = $this->generateRoomId($event, $eventTitle);
        
        // Set up default Jitsi configuration
        $config = array_merge($this->defaultConfig, [
            'roomName' => $roomId,
            'subject' => $eventTitle,
            'startWithAudioMuted' => true,
            'startWithVideoMuted' => false,
            'enableWelcomePage' => false,
            'prejoinPageEnabled' => $event->waiting_room_enabled ?? false,
            'disableDeepLinking' => true,
            'enableClosePage' => false,
            'disableInviteFunctions' => true,
            'toolbarButtons' => $this->getToolbarButtons($event),
        ]);

        // Update event with Jitsi details
        $event->update([
            'jitsi_room_id' => $roomId,
            'meeting_platform' => 'jitsi',
            'meeting_url' => $this->getMeetingUrl($roomId),
            'jitsi_config' => $config,
        ]);

        return [
            'room_id' => $roomId,
            'meeting_url' => $this->getMeetingUrl($roomId),
            'embed_url' => $this->getEmbedUrl($roomId, $config),
            'config' => $config,
        ];
    }

    /**
     * Generate meeting credentials for an event
     */
    public function generateMeetingCredentials(Event $event): array
    {
        if ($event->meeting_platform === 'jitsi') {
            return [
                'platform' => 'jitsi',
                'room_id' => $event->jitsi_room_id,
                'meeting_url' => $this->getMeetingUrl($event->jitsi_room_id),
                'embed_url' => $event->canEmbedMeeting() ? $this->getEmbedUrl($event->jitsi_room_id, $event->jitsi_config ?? []) : null,
                'password' => $event->meeting_password,
                'instructions' => $this->generateJitsiInstructions($event),
                'features' => [
                    'chat' => $event->chat_enabled ?? true,
                    'screen_sharing' => $event->screen_sharing_enabled ?? true,
                    'recording' => $event->recording_enabled ?? false,
                    'waiting_room' => $event->waiting_room_enabled ?? false,
                ],
            ];
        }

        return [
            'platform' => $event->meeting_platform,
            'meeting_url' => $event->meeting_url,
            'password' => $event->meeting_password,
            'instructions' => $event->meeting_instructions,
            'embed_allowed' => false,
        ];
    }

    /**
     * Generate iframe embed code for Jitsi Meet
     */
    public function getMeetingEmbedCode(string $meetingUrl, array $options = []): string
    {
        $width = $options['width'] ?? '100%';
        $height = $options['height'] ?? '600px';
        $allowFullscreen = $options['allowFullscreen'] ?? true;
        
        $allowAttributes = [
            'camera',
            'microphone',
            'display-capture',
            'fullscreen',
            'web-share',
        ];

        $allow = implode('; ', $allowAttributes);
        $fullscreenAttr = $allowFullscreen ? 'allowfullscreen' : '';

        return "<iframe 
            src=\"{$meetingUrl}\" 
            width=\"{$width}\" 
            height=\"{$height}\" 
            frameborder=\"0\" 
            allow=\"{$allow}\" 
            {$fullscreenAttr}>
        </iframe>";
    }

    /**
     * Validate meeting URL from various platforms
     */
    public function validateMeetingUrl(string $url): array
    {
        $url = trim($url);
        
        // Zoom validation
        if (preg_match('/zoom\.us\/j\/(\d+)/', $url, $matches)) {
            return [
                'valid' => true,
                'platform' => 'zoom',
                'meeting_id' => $matches[1],
                'url' => $url,
            ];
        }

        // Microsoft Teams validation
        if (strpos($url, 'teams.microsoft.com') !== false || strpos($url, 'teams.live.com') !== false) {
            return [
                'valid' => true,
                'platform' => 'teams',
                'url' => $url,
            ];
        }

        // Google Meet validation
        if (preg_match('/meet\.google\.com\/([a-z-]+)/', $url, $matches)) {
            return [
                'valid' => true,
                'platform' => 'google_meet',
                'meeting_code' => $matches[1],
                'url' => $url,
            ];
        }

        // WebEx validation
        if (strpos($url, 'webex.com') !== false) {
            return [
                'valid' => true,
                'platform' => 'webex',
                'url' => $url,
            ];
        }

        // Jitsi validation
        if (preg_match('/meet\.jit\.si\/(.+)/', $url, $matches)) {
            return [
                'valid' => true,
                'platform' => 'jitsi',
                'room_id' => $matches[1],
                'url' => $url,
            ];
        }

        // Generic URL validation
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'valid' => true,
                'platform' => 'other',
                'url' => $url,
            ];
        }

        return [
            'valid' => false,
            'error' => 'Invalid meeting URL format',
        ];
    }

    /**
     * Extract meeting details from various platform URLs
     */
    public function extractMeetingDetails(string $url): array
    {
        $validation = $this->validateMeetingUrl($url);
        
        if (!$validation['valid']) {
            return $validation;
        }

        $details = [
            'platform' => $validation['platform'],
            'url' => $validation['url'],
            'instructions' => $this->generatePlatformInstructions($validation['platform']),
        ];

        switch ($validation['platform']) {
            case 'zoom':
                $details['meeting_id'] = $validation['meeting_id'];
                $details['dial_in'] = $this->getZoomDialInNumbers();
                break;
                
            case 'google_meet':
                $details['meeting_code'] = $validation['meeting_code'];
                break;
                
            case 'jitsi':
                $details['room_id'] = $validation['room_id'];
                $details['embed_allowed'] = true;
                break;
        }

        return $details;
    }

    /**
     * Generate unique room ID for Jitsi
     */
    private function generateRoomId(Event $event, string $eventTitle): string
    {
        $slug = Str::slug($eventTitle, '-');
        $timestamp = now()->format('Ymd');
        return "alumni-{$event->id}-{$slug}-{$timestamp}";
    }

    /**
     * Get Jitsi meeting URL
     */
    private function getMeetingUrl(string $roomId): string
    {
        return "https://{$this->jitsiDomain}/{$roomId}";
    }

    /**
     * Get Jitsi embed URL with configuration
     */
    private function getEmbedUrl(string $roomId, array $config = []): string
    {
        $params = [];
        
        foreach ($config as $key => $value) {
            if (is_bool($value)) {
                $params["config.{$key}"] = $value ? 'true' : 'false';
            } else {
                $params["config.{$key}"] = $value;
            }
        }

        $queryString = http_build_query($params);
        return "https://{$this->jitsiDomain}/{$roomId}" . ($queryString ? "?{$queryString}" : '');
    }

    /**
     * Get toolbar buttons based on event settings
     */
    private function getToolbarButtons(Event $event): array
    {
        $buttons = ['microphone', 'camera', 'desktop', 'fullscreen', 'fodeviceselection', 'hangup', 'profile', 'settings'];
        
        if ($event->chat_enabled ?? true) {
            $buttons[] = 'chat';
        }
        
        if ($event->recording_enabled ?? false) {
            $buttons[] = 'recording';
        }
        
        if ($event->screen_sharing_enabled ?? true) {
            $buttons[] = 'desktop';
        }

        return $buttons;
    }

    /**
     * Generate instructions for Jitsi meetings
     */
    private function generateJitsiInstructions(Event $event): string
    {
        $instructions = "Join the virtual event using Jitsi Meet:\n\n";
        $instructions .= "1. Click the meeting link or join button\n";
        $instructions .= "2. Allow camera and microphone access when prompted\n";
        $instructions .= "3. Enter your name when joining\n";
        
        if ($event->waiting_room_enabled) {
            $instructions .= "4. Wait for the host to admit you to the meeting\n";
        }
        
        $instructions .= "\nTechnical requirements:\n";
        $instructions .= "- Modern web browser (Chrome, Firefox, Safari, Edge)\n";
        $instructions .= "- Stable internet connection\n";
        $instructions .= "- Camera and microphone (optional for viewing only)\n";

        return $instructions;
    }

    /**
     * Generate platform-specific instructions
     */
    private function generatePlatformInstructions(string $platform): string
    {
        switch ($platform) {
            case 'zoom':
                return "1. Click the meeting link\n2. Download Zoom client if prompted\n3. Enter meeting ID and password if required\n4. Join with audio and video";
                
            case 'teams':
                return "1. Click the meeting link\n2. Choose to join via web browser or Teams app\n3. Enter your name\n4. Join the meeting";
                
            case 'google_meet':
                return "1. Click the meeting link\n2. Sign in with Google account if required\n3. Allow camera and microphone access\n4. Join the meeting";
                
            case 'webex':
                return "1. Click the meeting link\n2. Enter your name and email\n3. Join via browser or download WebEx app\n4. Connect audio and video";
                
            default:
                return "Click the meeting link to join the virtual event. Ensure you have a stable internet connection and allow camera/microphone access if participating.";
        }
    }

    /**
     * Get Zoom dial-in numbers (placeholder - would integrate with Zoom API)
     */
    private function getZoomDialInNumbers(): array
    {
        return [
            'US' => '+1 669 900 6833',
            'UK' => '+44 203 481 5237',
            'International' => '+1 346 248 7799',
        ];
    }
}