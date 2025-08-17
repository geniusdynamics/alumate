# Video Calling Integration Implementation Guide

## Overview

This document outlines the implementation of video calling capabilities for the Alumni Platform using a multi-tier approach:

1. **Primary Solution**: Jitsi Meet for basic video calling
2. **Advanced Scalability**: Jitsi Videobridge for enterprise-scale conferences  
3. **Backup/Advanced Features**: LiveKit for AI integration and advanced real-time features

## Architecture Overview

### Technology Stack
- **Jitsi Meet**: Open-source video conferencing solution
- **Jitsi Videobridge**: WebRTC-compatible SFU for scalable video routing
- **LiveKit**: End-to-end realtime stack for advanced features and AI integration
- **Laravel Backend**: API management and scheduling
- **Vue.js Frontend**: User interface and integration components

### Implementation Phases

#### Phase 1: Basic Jitsi Integration
- Embed Jitsi Meet for 1-on-1 and small group calls
- Coffee chat scheduling system
- Basic call management and history

#### Phase 2: Jitsi Videobridge Integration- Scale 
to hundreds of concurrent conferences
- Advanced load balancing and clustering
- Enterprise-grade reliability and performance

#### Phase 3: LiveKit Integration
- AI-powered features (transcription, noise cancellation)
- Advanced screen sharing and collaboration
- Real-time data streaming and analytics
- Human-AI interaction capabilities

## Database Schema

### Core Tables

```sql
-- Video calls and meetings
CREATE TABLE video_calls (
    id BIGINT PRIMARY KEY,
    host_user_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('coffee_chat', 'group_meeting', 'alumni_gathering', 'mentorship') NOT NULL,
    provider ENUM('jitsi', 'jitsi_videobridge', 'livekit') DEFAULT 'jitsi',
    status ENUM('scheduled', 'active', 'ended', 'cancelled') DEFAULT 'scheduled',
    scheduled_at TIMESTAMP,
    started_at TIMESTAMP,
    ended_at TIMESTAMP,
    max_participants INT DEFAULT 10,
    room_id VARCHAR(255) UNIQUE NOT NULL,
    jitsi_room_name VARCHAR(255),
    livekit_room_token TEXT,
    settings JSON, -- recording, screen_share, etc.
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (host_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_status (status),
    INDEX idx_provider (provider)
);
```-- Call pa
rticipants
CREATE TABLE video_call_participants (
    id BIGINT PRIMARY KEY,
    call_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    role ENUM('host', 'moderator', 'participant') DEFAULT 'participant',
    joined_at TIMESTAMP,
    left_at TIMESTAMP,
    connection_quality JSON, -- bandwidth, latency metrics
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (call_id) REFERENCES video_calls(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_call_user (call_id, user_id),
    INDEX idx_call_id (call_id),
    INDEX idx_user_id (user_id)
);

-- Coffee chat scheduling and matching
CREATE TABLE coffee_chat_requests (
    id BIGINT PRIMARY KEY,
    requester_id BIGINT NOT NULL,
    recipient_id BIGINT,
    call_id BIGINT,
    type ENUM('direct_request', 'ai_matched', 'open_invitation') DEFAULT 'direct_request',
    proposed_times JSON, -- Array of proposed datetime slots
    selected_time TIMESTAMP,
    status ENUM('pending', 'accepted', 'declined', 'completed', 'expired') DEFAULT 'pending',
    message TEXT,
    matching_criteria JSON, -- Industry, location, interests for AI matching
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (call_id) REFERENCES video_calls(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_requester (requester_id),
    INDEX idx_recipient (recipient_id)
);
```-- S
creen sharing sessions
CREATE TABLE screen_sharing_sessions (
    id BIGINT PRIMARY KEY,
    call_id BIGINT NOT NULL,
    presenter_user_id BIGINT NOT NULL,
    started_at TIMESTAMP NOT NULL,
    ended_at TIMESTAMP,
    session_data JSON, -- Screen dimensions, quality settings
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (call_id) REFERENCES video_calls(id) ON DELETE CASCADE,
    FOREIGN KEY (presenter_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_call_id (call_id),
    INDEX idx_presenter (presenter_user_id)
);

-- Call recordings (for LiveKit integration)
CREATE TABLE call_recordings (
    id BIGINT PRIMARY KEY,
    call_id BIGINT NOT NULL,
    file_path VARCHAR(500),
    file_size BIGINT,
    duration_seconds INT,
    format VARCHAR(50),
    status ENUM('processing', 'completed', 'failed') DEFAULT 'processing',
    transcription TEXT,
    ai_summary TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (call_id) REFERENCES video_calls(id) ON DELETE CASCADE,
    INDEX idx_call_id (call_id),
    INDEX idx_status (status)
);
```## Phase
 1: Jitsi Meet Integration

### Backend Implementation

#### Laravel Services

```php
<?php
// app/Services/VideoCallService.php
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
            'provider' => 'jitsi',
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
        
        return "https://{$baseUrl}/{$roomName}?" . http_build_query($params);
    }
    
    private function generateRoomId(): string
    {
        return 'room_' . Str::uuid();
    }
    
    private function generateJitsiRoomName(string $title): string
    {
        return 'alumni_' . Str::slug($title) . '_' . time();
    }
}
```/
/ app/Services/CoffeeChatService.php
class CoffeeChatService
{
    public function suggestMatches(User $user, array $criteria = []): Collection
    {
        $query = User::where('id', '!=', $user->id)
            ->whereHas('profile', function($q) use ($user, $criteria) {
                // Match by industry
                if (!empty($criteria['industry'])) {
                    $q->where('industry', $criteria['industry']);
                } else {
                    $q->where('industry', $user->profile->industry ?? '');
                }
                
                // Geographic proximity (50km radius)
                if ($user->profile && $user->profile->location) {
                    $q->whereRaw('ST_DWithin(location, ?, 50000)', [$user->profile->location]);
                }
            });
            
        // Prioritize users with fewer completed coffee chats
        return $query->withCount('coffeeChatsCompleted')
            ->orderBy('coffee_chats_completed_count', 'asc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }
    
    public function createRequest(array $data): CoffeeChatRequest
    {
        return CoffeeChatRequest::create([
            'requester_id' => $data['requester_id'],
            'recipient_id' => $data['recipient_id'] ?? null,
            'type' => $data['type'] ?? 'direct_request',
            'proposed_times' => $data['proposed_times'],
            'message' => $data['message'] ?? null,
            'matching_criteria' => $data['matching_criteria'] ?? [],
        ]);
    }
    
    public function acceptRequest(CoffeeChatRequest $request, string $selectedTime): VideoCall
    {
        $request->update([
            'status' => 'accepted',
            'selected_time' => $selectedTime,
        ]);
        
        $videoCallService = app(VideoCallService::class);
        $call = $videoCallService->createCall([
            'host_user_id' => $request->requester_id,
            'title' => 'Coffee Chat: ' . $request->requester->name . ' & ' . $request->recipient->name,
            'type' => 'coffee_chat',
            'scheduled_at' => $selectedTime,
            'max_participants' => 2,
        ]);
        
        $request->update(['call_id' => $call->id]);
        
        return $call;
    }
}
```##
## API Controllers

```php
<?php
// app/Http/Controllers/Api/VideoCallController.php
class VideoCallController extends Controller
{
    public function __construct(
        private VideoCallService $videoCallService,
        private CoffeeChatService $coffeeChatService
    ) {}
    
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:coffee_chat,group_meeting,alumni_gathering,mentorship',
            'scheduled_at' => 'required|date|after:now',
            'max_participants' => 'nullable|integer|min:2|max:50',
            'settings' => 'nullable|array',
        ]);
        
        $validated['host_user_id'] = $request->user()->id;
        
        $call = $this->videoCallService->createCall($validated);
        
        return response()->json([
            'success' => true,
            'data' => $call->load('host'),
            'message' => 'Video call scheduled successfully.',
        ], 201);
    }
    
    public function show(VideoCall $call): JsonResponse
    {
        $user = request()->user();
        
        // Check if user has access to this call
        if (!$this->canUserAccessCall($call, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this call.',
            ], 403);
        }
        
        $jitsiUrl = $this->videoCallService->generateJitsiUrl($call, $user);
        
        return response()->json([
            'success' => true,
            'data' => [
                'call' => $call->load(['host', 'participants.user']),
                'jitsi_url' => $jitsiUrl,
                'can_moderate' => $this->canUserModerate($call, $user),
            ],
        ]);
    }
    
    public function join(VideoCall $call): JsonResponse
    {
        $user = request()->user();
        
        if (!$this->canUserAccessCall($call, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this call.',
            ], 403);
        }
        
        // Add user as participant if not already
        $participant = $call->participants()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'role' => $call->host_user_id === $user->id ? 'host' : 'participant',
            'joined_at' => now(),
        ]);
        
        // Update call status to active if first participant joins
        if ($call->status === 'scheduled') {
            $call->update(['status' => 'active', 'started_at' => now()]);
        }
        
        $jitsiUrl = $this->videoCallService->generateJitsiUrl($call, $user);
        
        return response()->json([
            'success' => true,
            'data' => [
                'jitsi_url' => $jitsiUrl,
                'participant' => $participant,
            ],
            'message' => 'Joined call successfully.',
        ]);
    }
    
    private function canUserAccessCall(VideoCall $call, User $user): bool
    {
        // Host always has access
        if ($call->host_user_id === $user->id) {
            return true;
        }
        
        // Check if user is invited participant
        return $call->participants()->where('user_id', $user->id)->exists();
    }
    
    private function canUserModerate(VideoCall $call, User $user): bool
    {
        if ($call->host_user_id === $user->id) {
            return true;
        }
        
        $participant = $call->participants()->where('user_id', $user->id)->first();
        return $participant && $participant->role === 'moderator';
    }
}
```### Front
end Implementation

#### Vue.js Components

```vue
<!-- resources/js/Components/VideoCall/JitsiMeetComponent.vue -->
<template>
  <div class="jitsi-meet-container">
    <div v-if="loading" class="loading-state">
      <div class="flex items-center justify-center h-64">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <span class="ml-3 text-lg">Preparing video call...</span>
      </div>
    </div>
    
    <div v-else-if="error" class="error-state">
      <div class="bg-red-50 border border-red-200 rounded-md p-4">
        <div class="flex">
          <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Unable to join call</h3>
            <p class="mt-2 text-sm text-red-700">{{ error }}</p>
            <button @click="retryConnection" class="mt-3 text-sm bg-red-100 text-red-800 px-3 py-1 rounded">
              Try Again
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else class="jitsi-iframe-container">
      <div ref="jitsiContainer" class="w-full h-full min-h-[500px]"></div>
      
      <!-- Custom Controls Overlay -->
      <div v-if="showCustomControls" class="custom-controls-overlay">
        <div class="flex items-center space-x-4 bg-black bg-opacity-50 rounded-lg p-3">
          <button @click="toggleMute" :class="{ 'text-red-500': isMuted }" class="text-white hover:text-gray-300">
            <MicrophoneIcon v-if="!isMuted" class="h-6 w-6" />
            <MicrophoneSlashIcon v-else class="h-6 w-6" />
          </button>
          
          <button @click="toggleVideo" :class="{ 'text-red-500': !videoEnabled }" class="text-white hover:text-gray-300">
            <VideoCameraIcon v-if="videoEnabled" class="h-6 w-6" />
            <VideoCameraSlashIcon v-else class="h-6 w-6" />
          </button>
          
          <button @click="toggleScreenShare" :class="{ 'text-blue-500': screenSharing }" class="text-white hover:text-gray-300">
            <ComputerDesktopIcon class="h-6 w-6" />
          </button>
          
          <button @click="leaveCall" class="text-white hover:text-red-300">
            <PhoneXMarkIcon class="h-6 w-6" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import {
  MicrophoneIcon,
  MicrophoneSlashIcon,
  VideoCameraIcon,
  VideoCameraSlashIcon,
  ComputerDesktopIcon,
  PhoneXMarkIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  callId: {
    type: [String, Number],
    required: true
  },
  jitsiDomain: {
    type: String,
    default: 'meet.jit.si'
  },
  showCustomControls: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['callEnded', 'participantJoined', 'participantLeft', 'error'])

// Reactive state
const loading = ref(true)
const error = ref(null)
const jitsiContainer = ref(null)
const jitsiApi = ref(null)
const isMuted = ref(false)
const videoEnabled = ref(true)
const screenSharing = ref(false)

// Methods
const initializeJitsi = async () => {
  try {
    loading.value = true
    error.value = null
    
    // Fetch call details and Jitsi URL
    const response = await fetch(`/api/video-calls/${props.callId}`)
    const data = await response.json()
    
    if (!data.success) {
      throw new Error(data.message || 'Failed to load call details')
    }
    
    const { call, jitsi_url } = data.data
    
    // Load Jitsi Meet API if not already loaded
    if (!window.JitsiMeetExternalAPI) {
      await loadJitsiScript()
    }
    
    // Extract room name from URL
    const url = new URL(jitsi_url)
    const roomName = url.pathname.substring(1)
    const urlParams = new URLSearchParams(url.search)
    
    // Initialize Jitsi Meet
    const options = {
      roomName: roomName,
      width: '100%',
      height: '100%',
      parentNode: jitsiContainer.value,
      configOverwrite: {
        startWithAudioMuted: urlParams.get('config.startWithAudioMuted') === 'true',
        startWithVideoMuted: urlParams.get('config.startWithVideoMuted') === 'true',
        enableWelcomePage: false,
        enableClosePage: false,
        prejoinPageEnabled: false,
        disableInviteFunctions: true,
      },
      interfaceConfigOverwrite: {
        TOOLBAR_BUTTONS: [
          'microphone', 'camera', 'closedcaptions', 'desktop', 'fullscreen',
          'fodeviceselection', 'hangup', 'profile', 'chat', 'recording',
          'livestreaming', 'etherpad', 'sharedvideo', 'settings', 'raisehand',
          'videoquality', 'filmstrip', 'invite', 'feedback', 'stats', 'shortcuts',
          'tileview', 'videobackgroundblur', 'download', 'help', 'mute-everyone'
        ],
        SETTINGS_SECTIONS: ['devices', 'language', 'moderator', 'profile', 'calendar'],
        SHOW_JITSI_WATERMARK: false,
        SHOW_WATERMARK_FOR_GUESTS: false,
      },
      userInfo: {
        displayName: urlParams.get('userInfo.displayName'),
        email: urlParams.get('userInfo.email')
      }
    }
    
    jitsiApi.value = new window.JitsiMeetExternalAPI(props.jitsiDomain, options)
    
    // Set up event listeners
    setupJitsiEventListeners()
    
    loading.value = false
    
  } catch (err) {
    console.error('Failed to initialize Jitsi:', err)
    error.value = err.message
    loading.value = false
    emit('error', err.message)
  }
}

const loadJitsiScript = () => {
  return new Promise((resolve, reject) => {
    if (window.JitsiMeetExternalAPI) {
      resolve()
      return
    }
    
    const script = document.createElement('script')
    script.src = `https://${props.jitsiDomain}/external_api.js`
    script.onload = resolve
    script.onerror = reject
    document.head.appendChild(script)
  })
}

const setupJitsiEventListeners = () => {
  if (!jitsiApi.value) return
  
  jitsiApi.value.addEventListener('participantJoined', (participant) => {
    emit('participantJoined', participant)
  })
  
  jitsiApi.value.addEventListener('participantLeft', (participant) => {
    emit('participantLeft', participant)
  })
  
  jitsiApi.value.addEventListener('videoConferenceLeft', () => {
    emit('callEnded')
  })
  
  jitsiApi.value.addEventListener('audioMuteStatusChanged', (event) => {
    isMuted.value = event.muted
  })
  
  jitsiApi.value.addEventListener('videoMuteStatusChanged', (event) => {
    videoEnabled.value = !event.muted
  })
  
  jitsiApi.value.addEventListener('screenSharingStatusChanged', (event) => {
    screenSharing.value = event.on
  })
}

const toggleMute = () => {
  if (jitsiApi.value) {
    jitsiApi.value.executeCommand('toggleAudio')
  }
}

const toggleVideo = () => {
  if (jitsiApi.value) {
    jitsiApi.value.executeCommand('toggleVideo')
  }
}

const toggleScreenShare = () => {
  if (jitsiApi.value) {
    jitsiApi.value.executeCommand('toggleShareScreen')
  }
}

const leaveCall = () => {
  if (jitsiApi.value) {
    jitsiApi.value.executeCommand('hangup')
  }
}

const retryConnection = () => {
  initializeJitsi()
}

// Lifecycle
onMounted(() => {
  initializeJitsi()
})

onUnmounted(() => {
  if (jitsiApi.value) {
    jitsiApi.value.dispose()
  }
})
</script>

<style scoped>
.jitsi-meet-container {
  position: relative;
  width: 100%;
  height: 100%;
}

.jitsi-iframe-container {
  position: relative;
  width: 100%;
  height: 100%;
}

.custom-controls-overlay {
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 1000;
}

.loading-state,
.error-state {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 400px;
}
</style>
```## P
hase 2: Jitsi Videobridge Integration

### Advanced Scalability Setup

#### Infrastructure Requirements

```yaml
# docker-compose.yml for Jitsi Videobridge cluster
version: '3.8'

services:
  # Jitsi Meet Web Interface
  jitsi-web:
    image: jitsi/web:latest
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    environment:
      - ENABLE_LETSENCRYPT=1
      - LETSENCRYPT_DOMAIN=${JITSI_DOMAIN}
      - LETSENCRYPT_EMAIL=${LETSENCRYPT_EMAIL}
      - PUBLIC_URL=https://${JITSI_DOMAIN}
      - XMPP_DOMAIN=meet.jitsi
      - XMPP_AUTH_DOMAIN=auth.meet.jitsi
      - XMPP_BOSH_URL_BASE=http://prosody:5280
      - XMPP_MUC_DOMAIN=muc.meet.jitsi
      - JVB_WS_DOMAIN=jvb.meet.jitsi
      - JVB_WS_SERVER_ID=jvb1
    networks:
      - jitsi-network
    depends_on:
      - prosody
      - jicofo
      - jvb

  # XMPP Server
  prosody:
    image: jitsi/prosody:latest
    restart: unless-stopped
    environment:
      - AUTH_TYPE=internal
      - ENABLE_GUESTS=1
      - XMPP_DOMAIN=meet.jitsi
      - XMPP_AUTH_DOMAIN=auth.meet.jitsi
      - XMPP_MUC_DOMAIN=muc.meet.jitsi
      - XMPP_INTERNAL_MUC_DOMAIN=internal-muc.meet.jitsi
      - JICOFO_COMPONENT_SECRET=${JICOFO_COMPONENT_SECRET}
      - JVB_AUTH_USER=jvb
      - JVB_AUTH_PASSWORD=${JVB_AUTH_PASSWORD}
      - JICOFO_AUTH_USER=jicofo
      - JICOFO_AUTH_PASSWORD=${JICOFO_AUTH_PASSWORD}
    networks:
      - jitsi-network
    volumes:
      - prosody-config:/config
      - prosody-plugins:/prosody-plugins-custom

  # Conference Focus
  jicofo:
    image: jitsi/jicofo:latest
    restart: unless-stopped
    environment:
      - XMPP_DOMAIN=meet.jitsi
      - XMPP_AUTH_DOMAIN=auth.meet.jitsi
      - XMPP_INTERNAL_MUC_DOMAIN=internal-muc.meet.jitsi
      - XMPP_MUC_DOMAIN=muc.meet.jitsi
      - XMPP_SERVER=prosody
      - JICOFO_COMPONENT_SECRET=${JICOFO_COMPONENT_SECRET}
      - JICOFO_AUTH_USER=jicofo
      - JICOFO_AUTH_PASSWORD=${JICOFO_AUTH_PASSWORD}
      - JVB_BREWERY_MUC=jvbbrewery
      - JIGASI_BREWERY_MUC=jigasibrewery
      - JIGASI_SIP_URI=${JIGASI_SIP_URI}
    networks:
      - jitsi-network
    depends_on:
      - prosody

  # Jitsi Videobridge - Primary
  jvb:
    image: jitsi/jvb:latest
    restart: unless-stopped
    ports:
      - "10000:10000/udp"
      - "4443:4443"
    environment:
      - DOCKER_HOST_ADDRESS=${DOCKER_HOST_ADDRESS}
      - XMPP_AUTH_DOMAIN=auth.meet.jitsi
      - XMPP_INTERNAL_MUC_DOMAIN=internal-muc.meet.jitsi
      - XMPP_SERVER=prosody
      - JVB_AUTH_USER=jvb
      - JVB_AUTH_PASSWORD=${JVB_AUTH_PASSWORD}
      - JVB_BREWERY_MUC=jvbbrewery
      - JVB_PORT=10000
      - JVB_TCP_HARVESTER_DISABLED=true
      - JVB_WS_DOMAIN=jvb.meet.jitsi
      - JVB_WS_SERVER_ID=jvb1
      - PUBLIC_URL=https://${JITSI_DOMAIN}
    networks:
      - jitsi-network
    depends_on:
      - prosody

  # Additional Videobridge instances for scaling
  jvb2:
    image: jitsi/jvb:latest
    restart: unless-stopped
    ports:
      - "10001:10000/udp"
      - "4444:4443"
    environment:
      - DOCKER_HOST_ADDRESS=${DOCKER_HOST_ADDRESS}
      - XMPP_AUTH_DOMAIN=auth.meet.jitsi
      - XMPP_INTERNAL_MUC_DOMAIN=internal-muc.meet.jitsi
      - XMPP_SERVER=prosody
      - JVB_AUTH_USER=jvb
      - JVB_AUTH_PASSWORD=${JVB_AUTH_PASSWORD}
      - JVB_BREWERY_MUC=jvbbrewery
      - JVB_PORT=10000
      - JVB_TCP_HARVESTER_DISABLED=true
      - JVB_WS_DOMAIN=jvb.meet.jitsi
      - JVB_WS_SERVER_ID=jvb2
      - PUBLIC_URL=https://${JITSI_DOMAIN}
    networks:
      - jitsi-network
    depends_on:
      - prosody

networks:
  jitsi-network:
    driver: bridge

volumes:
  prosody-config:
  prosody-plugins:
```#### Load Ba
lancing and Monitoring

```php
<?php
// app/Services/JitsiVideobridgeService.php
class JitsiVideobridgeService extends VideoCallService
{
    private array $videobridgeNodes;
    
    public function __construct()
    {
        $this->videobridgeNodes = config('services.jitsi.videobridge_nodes', []);
    }
    
    public function createScalableCall(array $data): VideoCall
    {
        // Select optimal videobridge node based on load
        $selectedNode = $this->selectOptimalNode($data['expected_participants'] ?? 10);
        
        $call = parent::createCall(array_merge($data, [
            'provider' => 'jitsi_videobridge',
            'settings' => array_merge($data['settings'] ?? [], [
                'videobridge_node' => $selectedNode,
                'load_balancing' => true,
                'max_bitrate' => $this->calculateOptimalBitrate($data['expected_participants'] ?? 10),
            ]),
        ]));
        
        // Pre-allocate resources on the selected node
        $this->preAllocateResources($call, $selectedNode);
        
        return $call;
    }
    
    private function selectOptimalNode(int $expectedParticipants): array
    {
        $nodeStats = $this->getNodeStatistics();
        
        // Sort nodes by available capacity
        usort($nodeStats, function($a, $b) {
            $loadA = $a['current_conferences'] / $a['max_conferences'];
            $loadB = $b['current_conferences'] / $b['max_conferences'];
            return $loadA <=> $loadB;
        });
        
        // Select node with lowest load that can handle the expected participants
        foreach ($nodeStats as $node) {
            if ($node['available_capacity'] >= $expectedParticipants) {
                return $node;
            }
        }
        
        // Fallback to least loaded node
        return $nodeStats[0];
    }
    
    private function getNodeStatistics(): array
    {
        $stats = [];
        
        foreach ($this->videobridgeNodes as $node) {
            try {
                $response = Http::timeout(5)->get("{$node['api_url']}/stats");
                $nodeStats = $response->json();
                
                $stats[] = [
                    'id' => $node['id'],
                    'url' => $node['url'],
                    'api_url' => $node['api_url'],
                    'current_conferences' => $nodeStats['conferences'] ?? 0,
                    'current_participants' => $nodeStats['participants'] ?? 0,
                    'max_conferences' => $node['max_conferences'] ?? 100,
                    'max_participants' => $node['max_participants'] ?? 1000,
                    'available_capacity' => ($node['max_participants'] ?? 1000) - ($nodeStats['participants'] ?? 0),
                    'cpu_usage' => $nodeStats['cpu_usage'] ?? 0,
                    'memory_usage' => $nodeStats['memory_usage'] ?? 0,
                    'bandwidth_usage' => $nodeStats['bandwidth'] ?? 0,
                ];
            } catch (Exception $e) {
                Log::warning("Failed to get stats from videobridge node {$node['id']}: " . $e->getMessage());
                
                // Mark node as unavailable
                $stats[] = [
                    'id' => $node['id'],
                    'url' => $node['url'],
                    'available_capacity' => 0,
                    'status' => 'unavailable',
                ];
            }
        }
        
        return $stats;
    }
    
    private function calculateOptimalBitrate(int $participants): int
    {
        // Dynamic bitrate calculation based on participant count
        if ($participants <= 4) {
            return 2500; // High quality for small groups
        } elseif ($participants <= 10) {
            return 1500; // Medium quality for medium groups
        } elseif ($participants <= 25) {
            return 800;  // Lower quality for larger groups
        } else {
            return 500;  // Minimal quality for very large groups
        }
    }
    
    private function preAllocateResources(VideoCall $call, array $node): void
    {
        // Send pre-allocation request to the selected videobridge node
        try {
            Http::post("{$node['api_url']}/conferences", [
                'room_id' => $call->room_id,
                'expected_participants' => $call->max_participants,
                'bitrate_limit' => $call->settings['max_bitrate'] ?? 1500,
                'recording_enabled' => $call->settings['recording_enabled'] ?? false,
            ]);
        } catch (Exception $e) {
            Log::warning("Failed to pre-allocate resources for call {$call->id}: " . $e->getMessage());
        }
    }
    
    public function getCallMetrics(VideoCall $call): array
    {
        if ($call->provider !== 'jitsi_videobridge') {
            return [];
        }
        
        $node = $call->settings['videobridge_node'] ?? null;
        if (!$node) {
            return [];
        }
        
        try {
            $response = Http::get("{$node['api_url']}/conferences/{$call->room_id}/stats");
            return $response->json();
        } catch (Exception $e) {
            Log::warning("Failed to get metrics for call {$call->id}: " . $e->getMessage());
            return [];
        }
    }
}
```## 
Phase 3: LiveKit Integration

### Advanced Features and AI Integration

#### LiveKit Service Implementation

```php
<?php
// app/Services/LiveKitService.php
use Livekit\AccessToken;
use Livekit\VideoGrant;
use Livekit\RoomServiceClient;

class LiveKitService extends VideoCallService
{
    private RoomServiceClient $roomService;
    private string $apiKey;
    private string $apiSecret;
    private string $wsUrl;
    
    public function __construct()
    {
        $this->apiKey = config('services.livekit.api_key');
        $this->apiSecret = config('services.livekit.api_secret');
        $this->wsUrl = config('services.livekit.ws_url');
        
        $this->roomService = new RoomServiceClient(
            config('services.livekit.host'),
            $this->apiKey,
            $this->apiSecret
        );
    }
    
    public function createAdvancedCall(array $data): VideoCall
    {
        // Create room in LiveKit
        $roomName = $this->generateRoomName($data['title']);
        
        $room = $this->roomService->createRoom([
            'name' => $roomName,
            'empty_timeout' => 300, // 5 minutes
            'max_participants' => $data['max_participants'] ?? 50,
            'metadata' => json_encode([
                'call_type' => $data['type'],
                'host_id' => $data['host_user_id'],
                'features' => $data['features'] ?? [],
            ]),
        ]);
        
        $call = VideoCall::create([
            'host_user_id' => $data['host_user_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'provider' => 'livekit',
            'scheduled_at' => $data['scheduled_at'],
            'max_participants' => $data['max_participants'] ?? 50,
            'room_id' => $roomName,
            'livekit_room_token' => $room->name,
            'settings' => array_merge($data['settings'] ?? [], [
                'ai_features' => $data['ai_features'] ?? [],
                'recording' => $data['recording'] ?? false,
                'transcription' => $data['transcription'] ?? false,
                'noise_cancellation' => $data['noise_cancellation'] ?? true,
                'virtual_background' => $data['virtual_background'] ?? false,
            ]),
        ]);
        
        // Enable AI features if requested
        if (!empty($data['ai_features'])) {
            $this->enableAIFeatures($call, $data['ai_features']);
        }
        
        return $call;
    }
    
    public function generateAccessToken(VideoCall $call, User $user): string
    {
        $token = new AccessToken($this->apiKey, $this->apiSecret);
        $token->setIdentity($user->id);
        $token->setName($user->name);
        $token->setMetadata(json_encode([
            'user_id' => $user->id,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
            'role' => $call->host_user_id === $user->id ? 'host' : 'participant',
        ]));
        
        $videoGrant = new VideoGrant();
        $videoGrant->setRoomJoin(true);
        $videoGrant->setRoom($call->room_id);
        
        // Set permissions based on user role
        if ($call->host_user_id === $user->id) {
            $videoGrant->setRoomAdmin(true);
            $videoGrant->setCanPublish(true);
            $videoGrant->setCanSubscribe(true);
            $videoGrant->setCanPublishData(true);
        } else {
            $videoGrant->setCanPublish(true);
            $videoGrant->setCanSubscribe(true);
            $videoGrant->setCanPublishData(true);
        }
        
        $token->setVideoGrant($videoGrant);
        
        return $token->toJwt();
    }
    
    private function enableAIFeatures(VideoCall $call, array $features): void
    {
        foreach ($features as $feature) {
            switch ($feature) {
                case 'transcription':
                    $this->enableTranscription($call);
                    break;
                case 'noise_cancellation':
                    $this->enableNoiseCancellation($call);
                    break;
                case 'sentiment_analysis':
                    $this->enableSentimentAnalysis($call);
                    break;
                case 'auto_summary':
                    $this->enableAutoSummary($call);
                    break;
                case 'language_translation':
                    $this->enableLanguageTranslation($call);
                    break;
            }
        }
    }
    
    private function enableTranscription(VideoCall $call): void
    {
        // Start real-time transcription using LiveKit's AI features
        $this->roomService->updateRoomMetadata($call->room_id,     
       json_encode([
                'transcription_enabled' => true,
                'language' => 'en-US',
                'real_time' => true,
            ])
        );
    }
    
    private function enableNoiseCancellation(VideoCall $call): void
    {
        // Configure AI-powered noise cancellation
        $this->roomService->updateRoomMetadata($call->room_id,
            json_encode([
                'noise_cancellation' => true,
                'audio_enhancement' => true,
            ])
        );
    }
    
    private function enableSentimentAnalysis(VideoCall $call): void
    {
        // Enable real-time sentiment analysis of conversation
        $this->roomService->updateRoomMetadata($call->room_id,
            json_encode([
                'sentiment_analysis' => true,
                'emotion_detection' => true,
                'engagement_tracking' => true,
            ])
        );
    }
    
    public function getCallAnalytics(VideoCall $call): array
    {
        if ($call->provider !== 'livekit') {
            return [];
        }
        
        try {
            $participants = $this->roomService->listParticipants($call->room_id);
            $room = $this->roomService->getRoom($call->room_id);
            
            return [
                'room_info' => [
                    'name' => $room->name,
                    'creation_time' => $room->creation_time,
                    'num_participants' => $room->num_participants,
                    'max_participants' => $room->max_participants,
                ],
                'participants' => array_map(function($participant) {
                    return [
                        'identity' => $participant->identity,
                        'name' => $participant->name,
                        'joined_at' => $participant->joined_at,
                        'tracks' => $participant->tracks,
                        'metadata' => json_decode($participant->metadata, true),
                    ];
                }, $participants),
                'ai_insights' => $this->getAIInsights($call),
            ];
        } catch (Exception $e) {
            Log::warning("Failed to get LiveKit analytics for call {$call->id}: " . $e->getMessage());
            return [];
        }
    }
    
    private function getAIInsights(VideoCall $call): array
    {
        // Retrieve AI-generated insights from the call
        return [
            'transcription' => $this->getTranscription($call),
            'sentiment_analysis' => $this->getSentimentAnalysis($call),
            'engagement_metrics' => $this->getEngagementMetrics($call),
            'key_topics' => $this->extractKeyTopics($call),
            'action_items' => $this->extractActionItems($call),
        ];
    }
}
```#### Live
Kit Frontend Component

```vue
<!-- resources/js/Components/VideoCall/LiveKitComponent.vue -->
<template>
  <div class="livekit-container">
    <div v-if="loading" class="loading-state">
      <div class="flex items-center justify-center h-64">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <span class="ml-3 text-lg">Connecting to advanced video call...</span>
      </div>
    </div>
    
    <div v-else-if="error" class="error-state">
      <div class="bg-red-50 border border-red-200 rounded-md p-4">
        <div class="flex">
          <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Connection Failed</h3>
            <p class="mt-2 text-sm text-red-700">{{ error }}</p>
            <button @click="retryConnection" class="mt-3 text-sm bg-red-100 text-red-800 px-3 py-1 rounded">
              Retry Connection
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else class="livekit-room">
      <!-- Main Video Grid -->
      <div class="video-grid" :class="gridClass">
        <!-- Local Video -->
        <div class="video-tile local-video" v-if="localParticipant">
          <video ref="localVideo" autoplay muted playsinline></video>
          <div class="participant-info">
            <span class="name">{{ localParticipant.name }} (You)</span>
            <div class="controls">
              <button @click="toggleMute" :class="{ 'muted': isMuted }">
                <MicrophoneIcon v-if="!isMuted" class="h-4 w-4" />
                <MicrophoneSlashIcon v-else class="h-4 w-4" />
              </button>
              <button @click="toggleVideo" :class="{ 'disabled': !videoEnabled }">
                <VideoCameraIcon v-if="videoEnabled" class="h-4 w-4" />
                <VideoCameraSlashIcon v-else class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
        
        <!-- Remote Participants -->
        <div 
          v-for="participant in remoteParticipants" 
          :key="participant.identity"
          class="video-tile remote-video"
        >
          <video 
            :ref="`video-${participant.identity}`" 
            autoplay 
            playsinline
            :muted="false"
          ></video>
          <div class="participant-info">
            <span class="name">{{ participant.name }}</span>
            <div class="status-indicators">
              <MicrophoneSlashIcon v-if="participant.isMuted" class="h-3 w-3 text-red-500" />
              <VideoCameraSlashIcon v-if="!participant.videoEnabled" class="h-3 w-3 text-red-500" />
              <SignalIcon v-if="participant.connectionQuality" 
                         :class="getConnectionQualityClass(participant.connectionQuality)" 
                         class="h-3 w-3" />
            </div>
          </div>
        </div>
        
        <!-- Screen Share Display -->
        <div v-if="screenShareTrack" class="screen-share-container">
          <video ref="screenShareVideo" autoplay playsinline></video>
          <div class="screen-share-info">
            <ComputerDesktopIcon class="h-5 w-5" />
            <span>{{ screenShareParticipant?.name }} is sharing their screen</span>
          </div>
        </div>
      </div>
      
      <!-- AI Features Panel -->
      <div v-if="aiFeatures.enabled" class="ai-features-panel">
        <!-- Real-time Transcription -->
        <div v-if="aiFeatures.transcription" class="transcription-panel">
          <h3 class="text-sm font-medium mb-2">Live Transcription</h3>
          <div class="transcription-content">
            <div 
              v-for="(transcript, index) in transcripts" 
              :key="index"
              class="transcript-item"
            >
              <span class="speaker">{{ transcript.speaker }}:</span>
              <span class="text">{{ transcript.text }}</span>
              <span class="timestamp">{{ formatTime(transcript.timestamp) }}</span>
            </div>
          </div>
        </div>
        
        <!-- Sentiment Analysis -->
        <div v-if="aiFeatures.sentiment" class="sentiment-panel">
          <h3 class="text-sm font-medium mb-2">Meeting Sentiment</h3>
          <div class="sentiment-indicators">
            <div class="sentiment-meter">
              <div class="meter-bar" :style="{ width: sentimentScore + '%' }"></div>
            </div>
            <span class="sentiment-label">{{ sentimentLabel }}</span>
          </div>
        </div>
        
        <!-- Action Items -->
        <div v-if="aiFeatures.actionItems" class="action-items-panel">
          <h3 class="text-sm font-medium mb-2">Action Items</h3>
          <ul class="action-items-list">
            <li v-for="(item, index) in actionItems" :key="index" class="action-item">
              <CheckCircleIcon class="h-4 w-4 text-green-500" />
              <span>{{ item.text }}</span>
              <span class="assignee">{{ item.assignee }}</span>
            </li>
          </ul>
        </div>
      </div>
      
      <!-- Control Bar -->
      <div class="control-bar">
        <div class="control-group">
          <button @click="toggleMute" :class="{ 'active': isMuted }" class="control-button">
            <MicrophoneIcon v-if="!isMuted" class="h-5 w-5" />
            <MicrophoneSlashIcon v-else class="h-5 w-5" />
          </button>
          
          <button @click="toggleVideo" :class="{ 'active': !videoEnabled }" class="control-button">
            <VideoCameraIcon v-if="videoEnabled" class="h-5 w-5" />
            <VideoCameraSlashIcon v-else class="h-5 w-5" />
          </button>
          
          <button @click="toggleScreenShare" :class="{ 'active': isScreenSharing }" class="control-button">
            <ComputerDesktopIcon class="h-5 w-5" />
          </button>
          
          <button @click="toggleRecording" :class="{ 'active': isRecording }" class="control-button">
            <VideoCameraIcon class="h-5 w-5" />
            <span class="recording-indicator" v-if="isRecording"></span>
          </button>
        </div>
        
        <div class="control-group">
          <button @click="toggleChat" class="control-button">
            <ChatBubbleLeftRightIcon class="h-5 w-5" />
            <span v-if="unreadMessages > 0" class="badge">{{ unreadMessages }}</span>
          </button>
          
          <button @click="toggleParticipants" class="control-button">
            <UserGroupIcon class="h-5 w-5" />
            <span class="badge">{{ totalParticipants }}</span>
          </button>
          
          <button @click="toggleSettings" class="control-button">
            <CogIcon class="h-5 w-5" />
          </button>
        </div>
        
        <div class="control-group">
          <button @click="leaveCall" class="control-button leave-button">
            <PhoneXMarkIcon class="h-5 w-5" />
            <span>Leave</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { 
  Room, 
  connect, 
  RoomEvent, 
  Track, 
  RemoteTrack, 
  RemoteParticipant,
  LocalParticipant,
  ConnectionQuality 
} from 'livekit-client'
import {
  MicrophoneIcon,
  MicrophoneSlashIcon,
  VideoCameraIcon,
  VideoCameraSlashIcon,
  ComputerDesktopIcon,
  PhoneXMarkIcon,
  ChatBubbleLeftRightIcon,
  UserGroupIcon,
  CogIcon,
  CheckCircleIcon,
  SignalIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  callId: {
    type: [String, Number],
    required: true
  },
  wsUrl: {
    type: String,
    required: true
  },
  token: {
    type: String,
    required: true
  },
  aiFeatures: {
    type: Object,
    default: () => ({
      enabled: false,
      transcription: false,
      sentiment: false,
      actionItems: false
    })
  }
})

const emit = defineEmits(['callEnded', 'participantJoined', 'participantLeft', 'error'])

// Reactive state
const loading = ref(true)
const error = ref(null)
const room = ref(null)
const localParticipant = ref(null)
const remoteParticipants = ref([])
const isMuted = ref(false)
const videoEnabled = ref(true)
const isScreenSharing = ref(false)
const isRecording = ref(false)
const screenShareTrack = ref(null)
const screenShareParticipant = ref(null)
const transcripts = ref([])
const sentimentScore = ref(50)
const actionItems = ref([])
const unreadMessages = ref(0)

// Computed properties
const totalParticipants = computed(() => {
  return 1 + remoteParticipants.value.length
})

const gridClass = computed(() => {
  const count = totalParticipants.value
  if (count <= 2) return 'grid-1x2'
  if (count <= 4) return 'grid-2x2'
  if (count <= 6) return 'grid-2x3'
  if (count <= 9) return 'grid-3x3'
  return 'grid-auto'
})

const sentimentLabel = computed(() => {
  if (sentimentScore.value >= 70) return 'Positive'
  if (sentimentScore.value >= 40) return 'Neutral'
  return 'Negative'
})

// Methods
const connectToRoom = async () => {
  try {
    loading.value = true
    error.value = null
    
    room.value = new Room({
      adaptiveStream: true,
      dynacast: true,
      videoCaptureDefaults: {
        resolution: {
          width: 1280,
          height: 720,
          frameRate: 30,
        },
      },
    })
    
    setupRoomEventListeners()
    
    await room.value.connect(props.wsUrl, props.token)
    
    // Enable local tracks
    await room.value.localParticipant.enableCameraAndMicrophone()
    
    localParticipant.value = room.value.localParticipant
    updateRemoteParticipants()
    
    loading.value = false
    
  } catch (err) {
    console.error('Failed to connect to LiveKit room:', err)
    error.value = err.message
    loading.value = false
    emit('error', err.message)
  }
}

const setupRoomEventListeners = () => {
  if (!room.value) return
  
  room.value.on(RoomEvent.ParticipantConnected, (participant) => {
    console.log('Participant connected:', participant.identity)
    updateRemoteParticipants()
    emit('participantJoined', participant)
  })
  
  room.value.on(RoomEvent.ParticipantDisconnected, (participant) => {
    console.log('Participant disconnected:', participant.identity)
    updateRemoteParticipants()
    emit('participantLeft', participant)
  })
  
  room.value.on(RoomEvent.TrackSubscribed, (track, publication, participant) => {
    if (track.kind === Track.Kind.Video) {
      attachVideoTrack(track, participant)
    } else if (track.kind === Track.Kind.Audio) {
      attachAudioTrack(track, participant)
    }
  })
  
  room.value.on(RoomEvent.TrackUnsubscribed, (track, publication, participant) => {
    detachTrack(track, participant)
  })
  
  room.value.on(RoomEvent.DataReceived, (payload, participant) => {
    handleDataMessage(payload, participant)
  })
  
  room.value.on(RoomEvent.ConnectionQualityChanged, (quality, participant) => {
    updateConnectionQuality(quality, participant)
  })
}

const attachVideoTrack = (track, participant) => {
  if (participant === room.value.localParticipant) {
    const videoElement = this.$refs.localVideo
    if (videoElement) {
      track.attach(videoElement)
    }
  } else {
    const videoElement = this.$refs[`video-${participant.identity}`]
    if (videoElement && videoElement[0]) {
      track.attach(videoElement[0])
    }
  }
}

const attachAudioTrack = (track, participant) => {
  // Audio tracks are automatically played
  track.attach()
}

const updateRemoteParticipants = () => {
  if (!room.value) return
  
  remoteParticipants.value = Array.from(room.value.participants.values()).map(participant => ({
    identity: participant.identity,
    name: participant.name || participant.identity,
    isMuted: participant.isMicrophoneEnabled === false,
    videoEnabled: participant.isCameraEnabled !== false,
    connectionQuality: participant.connectionQuality,
  }))
}

const toggleMute = async () => {
  if (!room.value) return
  
  try {
    await room.value.localParticipant.setMicrophoneEnabled(!isMuted.value)
    isMuted.value = !isMuted.value
  } catch (err) {
    console.error('Failed to toggle microphone:', err)
  }
}

const toggleVideo = async () => {
  if (!room.value) return
  
  try {
    await room.value.localParticipant.setCameraEnabled(!videoEnabled.value)
    videoEnabled.value = !videoEnabled.value
  } catch (err) {
    console.error('Failed to toggle camera:', err)
  }
}

const toggleScreenShare = async () => {
  if (!room.value) return
  
  try {
    if (isScreenSharing.value) {
      await room.value.localParticipant.setScreenShareEnabled(false)
      isScreenSharing.value = false
    } else {
      await room.value.localParticipant.setScreenShareEnabled(true)
      isScreenSharing.value = true
    }
  } catch (err) {
    console.error('Failed to toggle screen share:', err)
  }
}

const toggleRecording = async () => {
  try {
    const response = await fetch(`/api/video-calls/${props.callId}/recording`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        action: isRecording.value ? 'stop' : 'start'
      })
    })
    
    const data = await response.json()
    if (data.success) {
      isRecording.value = !isRecording.value
    }
  } catch (err) {
    console.error('Failed to toggle recording:', err)
  }
}

const handleDataMessage = (payload, participant) => {
  try {
    const message = JSON.parse(new TextDecoder().decode(payload))
    
    switch (message.type) {
      case 'transcription':
        transcripts.value.push({
          speaker: participant?.name || 'Unknown',
          text: message.text,
          timestamp: Date.now()
        })
        // Keep only last 50 transcripts
        if (transcripts.value.length > 50) {
          transcripts.value = transcripts.value.slice(-50)
        }
        break
        
      case 'sentiment':
        sentimentScore.value = message.score
        break
        
      case 'action_item':
        actionItems.value.push({
          text: message.text,
          assignee: message.assignee,
          timestamp: Date.now()
        })
        break
        
      case 'chat':
        unreadMessages.value++
        break
    }
  } catch (err) {
    console.error('Failed to parse data message:', err)
  }
}

const getConnectionQualityClass = (quality) => {
  switch (quality) {
    case ConnectionQuality.Excellent:
      return 'text-green-500'
    case ConnectionQuality.Good:
      return 'text-yellow-500'
    case ConnectionQuality.Poor:
      return 'text-red-500'
    default:
      return 'text-gray-500'
  }
}

const formatTime = (timestamp) => {
  return new Date(timestamp).toLocaleTimeString()
}

const leaveCall = async () => {
  if (room.value) {
    await room.value.disconnect()
  }
  emit('callEnded')
}

const retryConnection = () => {
  connectToRoom()
}

// Lifecycle
onMounted(() => {
  connectToRoom()
})

onUnmounted(() => {
  if (room.value) {
    room.value.disconnect()
  }
})
</script>

<style scoped>
.livekit-container {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: #1a1a1a;
  color: white;
}

.video-grid {
  flex: 1;
  display: grid;
  gap: 8px;
  padding: 16px;
}

.grid-1x2 { grid-template-columns: 1fr 1fr; }
.grid-2x2 { grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; }
.grid-2x3 { grid-template-columns: repeat(3, 1fr); grid-template-rows: 1fr 1fr; }
.grid-3x3 { grid-template-columns: repeat(3, 1fr); grid-template-rows: repeat(3, 1fr); }
.grid-auto { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }

.video-tile {
  position: relative;
  background: #2a2a2a;
  border-radius: 8px;
  overflow: hidden;
}

.video-tile video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.participant-info {
  position: absolute;
  bottom: 8px;
  left: 8px;
  right: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(0, 0, 0, 0.7);
  padding: 4px 8px;
  border-radius: 4px;
}

.control-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: rgba(0, 0, 0, 0.8);
}

.control-group {
  display: flex;
  gap: 8px;
}

.control-button {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 6px;
  color: white;
  cursor: pointer;
  transition: background-color 0.2s;
}

.control-button:hover {
  background: rgba(255, 255, 255, 0.2);
}

.control-button.active {
  background: #ef4444;
}

.leave-button {
  background: #ef4444;
}

.ai-features-panel {
  position: fixed;
  right: 16px;
  top: 16px;
  width: 300px;
  background: rgba(0, 0, 0, 0.9);
  border-radius: 8px;
  padding: 16px;
  max-height: 60vh;
  overflow-y: auto;
}

.transcription-content {
  max-height: 200px;
  overflow-y: auto;
  font-size: 12px;
}

.transcript-item {
  margin-bottom: 8px;
  padding: 4px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 4px;
}

.sentiment-meter {
  width: 100%;
  height: 8px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;
  overflow: hidden;
}

.meter-bar {
  height: 100%;
  background: linear-gradient(90deg, #ef4444, #f59e0b, #10b981);
  transition: width 0.3s ease;
}

.action-items-list {
  list-style: none;
  padding: 0;
}

.action-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 0;
  font-size: 12px;
}

.badge {
  background: #ef4444;
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: bold;
}

.recording-indicator {
  width: 8px;
  height: 8px;
  background: #ef4444;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
</style>
```## API 
Routes and Integration

### Complete API Routes

```php
<?php
// routes/api.php - Video Calling Routes

Route::middleware('auth:sanctum')->group(function () {
    // Video Calls Management
    Route::apiResource('video-calls', VideoCallController::class);
    Route::post('video-calls/{call}/join', [VideoCallController::class, 'join']);
    Route::post('video-calls/{call}/leave', [VideoCallController::class, 'leave']);
    Route::post('video-calls/{call}/end', [VideoCallController::class, 'end']);
    Route::get('video-calls/{call}/analytics', [VideoCallController::class, 'analytics']);
    
    // Coffee Chat System
    Route::get('coffee-chats/suggestions', [CoffeeChatController::class, 'suggestions']);
    Route::post('coffee-chats/request', [CoffeeChatController::class, 'request']);
    Route::post('coffee-chats/{request}/respond', [CoffeeChatController::class, 'respond']);
    Route::get('coffee-chats/my-requests', [CoffeeChatController::class, 'myRequests']);
    Route::get('coffee-chats/received-requests', [CoffeeChatController::class, 'receivedRequests']);
    
    // Screen Sharing
    Route::post('video-calls/{call}/screen-share/start', [ScreenShareController::class, 'start']);
    Route::post('video-calls/{call}/screen-share/stop', [ScreenShareController::class, 'stop']);
    Route::get('video-calls/{call}/screen-share/status', [ScreenShareController::class, 'status']);
    
    // Recording (LiveKit)
    Route::post('video-calls/{call}/recording', [RecordingController::class, 'toggle']);
    Route::get('video-calls/{call}/recordings', [RecordingController::class, 'list']);
    Route::get('recordings/{recording}/download', [RecordingController::class, 'download']);
    
    // AI Features (LiveKit)
    Route::post('video-calls/{call}/transcription', [AIFeaturesController::class, 'toggleTranscription']);
    Route::get('video-calls/{call}/transcription', [AIFeaturesController::class, 'getTranscription']);
    Route::get('video-calls/{call}/summary', [AIFeaturesController::class, 'generateSummary']);
    Route::get('video-calls/{call}/action-items', [AIFeaturesController::class, 'getActionItems']);
    
    // Provider-specific endpoints
    Route::get('video-calls/{call}/jitsi-url', [JitsiController::class, 'getUrl']);
    Route::get('video-calls/{call}/livekit-token', [LiveKitController::class, 'getToken']);
    Route::get('jitsi/health', [JitsiController::class, 'healthCheck']);
    Route::get('livekit/health', [LiveKitController::class, 'healthCheck']);
});
```

### Configuration Files

```php
<?php
// config/services.php additions

return [
    // ... existing services
    
    'jitsi' => [
        'domain' => env('JITSI_DOMAIN', 'meet.jit.si'),
        'app_id' => env('JITSI_APP_ID'),
        'app_secret' => env('JITSI_APP_SECRET'),
        'videobridge_nodes' => [
            [
                'id' => 'jvb1',
                'url' => env('JITSI_JVB1_URL', 'https://jvb1.yourdomain.com'),
                'api_url' => env('JITSI_JVB1_API_URL', 'https://jvb1.yourdomain.com/colibri'),
                'max_conferences' => 100,
                'max_participants' => 1000,
            ],
            [
                'id' => 'jvb2',
                'url' => env('JITSI_JVB2_URL', 'https://jvb2.yourdomain.com'),
                'api_url' => env('JITSI_JVB2_API_URL', 'https://jvb2.yourdomain.com/colibri'),
                'max_conferences' => 100,
                'max_participants' => 1000,
            ],
        ],
    ],
    
    'livekit' => [
        'host' => env('LIVEKIT_HOST', 'https://your-livekit-server.com'),
        'api_key' => env('LIVEKIT_API_KEY'),
        'api_secret' => env('LIVEKIT_API_SECRET'),
        'ws_url' => env('LIVEKIT_WS_URL', 'wss://your-livekit-server.com'),
        'features' => [
            'transcription' => env('LIVEKIT_TRANSCRIPTION_ENABLED', false),
            'recording' => env('LIVEKIT_RECORDING_ENABLED', false),
            'ai_analysis' => env('LIVEKIT_AI_ANALYSIS_ENABLED', false),
        ],
    ],
];
```

### Environment Variables

```bash
# .env additions

# Jitsi Configuration
JITSI_DOMAIN=meet.yourdomain.com
JITSI_APP_ID=your_jitsi_app_id
JITSI_APP_SECRET=your_jitsi_app_secret

# Jitsi Videobridge Cluster
JITSI_JVB1_URL=https://jvb1.yourdomain.com
JITSI_JVB1_API_URL=https://jvb1.yourdomain.com/colibri
JITSI_JVB2_URL=https://jvb2.yourdomain.com
JITSI_JVB2_API_URL=https://jvb2.yourdomain.com/colibri

# LiveKit Configuration
LIVEKIT_HOST=https://your-livekit-server.com
LIVEKIT_API_KEY=your_livekit_api_key
LIVEKIT_API_SECRET=your_livekit_api_secret
LIVEKIT_WS_URL=wss://your-livekit-server.com

# LiveKit Features
LIVEKIT_TRANSCRIPTION_ENABLED=true
LIVEKIT_RECORDING_ENABLED=true
LIVEKIT_AI_ANALYSIS_ENABLED=true

# Docker Host (for Jitsi Videobridge)
DOCKER_HOST_ADDRESS=your.server.ip.address

# SSL Certificates (for Jitsi)
LETSENCRYPT_DOMAIN=meet.yourdomain.com
LETSENCRYPT_EMAIL=admin@yourdomain.com

# Jitsi Authentication
JICOFO_COMPONENT_SECRET=your_jicofo_secret
JVB_AUTH_PASSWORD=your_jvb_password
JICOFO_AUTH_PASSWORD=your_jicofo_password
```## Deploy
ment and Scaling

### Infrastructure Requirements

#### Minimum Requirements (Phase 1 - Jitsi)
- **Server**: 2 CPU cores, 4GB RAM, 50GB storage
- **Bandwidth**: 100 Mbps up/down
- **Concurrent Users**: Up to 50 participants across multiple rooms

#### Recommended Requirements (Phase 2 - Jitsi Videobridge)
- **Load Balancer**: 2 CPU cores, 2GB RAM
- **Jitsi Web**: 2 CPU cores, 4GB RAM
- **Prosody XMPP**: 1 CPU core, 2GB RAM  
- **Jicofo**: 1 CPU core, 2GB RAM
- **Videobridge Nodes**: 4 CPU cores, 8GB RAM each (multiple nodes)
- **Bandwidth**: 1 Gbps per videobridge node
- **Concurrent Users**: Up to 500 participants per videobridge node

#### Enterprise Requirements (Phase 3 - LiveKit)
- **LiveKit Server**: 8 CPU cores, 16GB RAM
- **Redis**: 2 CPU cores, 4GB RAM
- **Database**: 4 CPU cores, 8GB RAM
- **AI Processing**: GPU-enabled instances for transcription/analysis
- **Storage**: High-performance SSD for recordings
- **Bandwidth**: 10 Gbps
- **Concurrent Users**: 1000+ participants with AI features

### Deployment Scripts

#### Docker Compose for Development

```yaml
# docker-compose.dev.yml
version: '3.8'

services:
  # Laravel Application
  app:
    build: .
    ports:
      - "8000:8000"
    environment:
      - APP_ENV=local
      - JITSI_DOMAIN=localhost:8443
      - LIVEKIT_HOST=http://livekit:7880
    depends_on:
      - database
      - redis
      - jitsi-web
      - livekit
    networks:
      - alumni-network

  # Database
  database:
    image: postgres:15
    environment:
      POSTGRES_DB: alumni_platform
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: password
    volumes:
      - postgres_data:/var/lib/postgresql/data
    networks:
      - alumni-network

  # Redis
  redis:
    image: redis:7-alpine
    networks:
      - alumni-network

  # Jitsi Meet (Development)
  jitsi-web:
    image: jitsi/web:latest
    ports:
      - "8443:443"
    environment:
      - ENABLE_LETSENCRYPT=0
      - PUBLIC_URL=https://localhost:8443
      - XMPP_DOMAIN=meet.jitsi
      - XMPP_AUTH_DOMAIN=auth.meet.jitsi
      - XMPP_BOSH_URL_BASE=http://prosody:5280
    depends_on:
      - prosody
      - jicofo
      - jvb
    networks:
      - alumni-network

  prosody:
    image: jitsi/prosody:latest
    environment:
      - XMPP_DOMAIN=meet.jitsi
      - XMPP_AUTH_DOMAIN=auth.meet.jitsi
      - JICOFO_COMPONENT_SECRET=jicofo_secret
      - JVB_AUTH_PASSWORD=jvb_password
      - JICOFO_AUTH_PASSWORD=jicofo_password
    networks:
      - alumni-network

  jicofo:
    image: jitsi/jicofo:latest
    environment:
      - XMPP_DOMAIN=meet.jitsi
      - XMPP_AUTH_DOMAIN=auth.meet.jitsi
      - JICOFO_COMPONENT_SECRET=jicofo_secret
      - JICOFO_AUTH_PASSWORD=jicofo_password
    depends_on:
      - prosody
    networks:
      - alumni-network

  jvb:
    image: jitsi/jvb:latest
    ports:
      - "10000:10000/udp"
    environment:
      - XMPP_AUTH_DOMAIN=auth.meet.jitsi
      - JVB_AUTH_PASSWORD=jvb_password
      - JVB_PORT=10000
    depends_on:
      - prosody
    networks:
      - alumni-network

  # LiveKit (Development)
  livekit:
    image: livekit/livekit-server:latest
    ports:
      - "7880:7880"
      - "7881:7881"
    command: --config /etc/livekit.yaml
    volumes:
      - ./livekit.yaml:/etc/livekit.yaml
    networks:
      - alumni-network

networks:
  alumni-network:
    driver: bridge

volumes:
  postgres_data:
```

#### LiveKit Configuration

```yaml
# livekit.yaml
port: 7880
bind_addresses:
  - ""

rtc:
  tcp_port: 7881
  port_range_start: 50000
  port_range_end: 60000
  use_external_ip: true

redis:
  address: redis:6379

keys:
  your_api_key: your_api_secret

room:
  auto_create: true
  empty_timeout: 300s
  departure_timeout: 20s

audio:
  # Audio codecs
  mime_types:
    - audio/opus

video:
  # Video codecs  
  mime_types:
    - video/h264
    - video/vp8
    - video/vp9

# AI Features
ingress:
  rtmp_base_url: "rtmp://localhost/live"

egress:
  insecure: true
  
webhook:
  api_key: your_webhook_key
  urls:
    - http://app:8000/api/livekit/webhook
```

### Monitoring and Analytics

#### Health Check Endpoints

```php
<?php
// app/Http/Controllers/Api/HealthController.php

class HealthController extends Controller
{
    public function videoCallingSystems(): JsonResponse
    {
        $jitsiHealth = $this->checkJitsiHealth();
        $livekitHealth = $this->checkLivekitHealth();
        $videobridgeHealth = $this->checkVideobridgeHealth();
        
        return response()->json([
            'status' => 'ok',
            'systems' => [
                'jitsi' => $jitsiHealth,
                'livekit' => $livekitHealth,
                'videobridge_cluster' => $videobridgeHealth,
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }
    
    private function checkJitsiHealth(): array
    {
        try {
            $domain = config('services.jitsi.domain');
            $response = Http::timeout(5)->get("https://{$domain}/config.js");
            
            return [
                'status' => $response->successful() ? 'healthy' : 'unhealthy',
                'response_time' => $response->transferStats->getTransferTime(),
                'domain' => $domain,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    private function checkLivekitHealth(): array
    {
        try {
            $host = config('services.livekit.host');
            $response = Http::timeout(5)->get("{$host}/");
            
            return [
                'status' => $response->successful() ? 'healthy' : 'unhealthy',
                'response_time' => $response->transferStats->getTransferTime(),
                'host' => $host,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    private function checkVideobridgeHealth(): array
    {
        $nodes = config('services.jitsi.videobridge_nodes', []);
        $healthyNodes = 0;
        $nodeStatuses = [];
        
        foreach ($nodes as $node) {
            try {
                $response = Http::timeout(5)->get("{$node['api_url']}/stats");
                $healthy = $response->successful();
                
                $nodeStatuses[] = [
                    'id' => $node['id'],
                    'status' => $healthy ? 'healthy' : 'unhealthy',
                    'stats' => $healthy ? $response->json() : null,
                ];
                
                if ($healthy) {
                    $healthyNodes++;
                }
            } catch (Exception $e) {
                $nodeStatuses[] = [
                    'id' => $node['id'],
                    'status' => 'unhealthy',
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return [
            'status' => $healthyNodes > 0 ? 'healthy' : 'unhealthy',
            'healthy_nodes' => $healthyNodes,
            'total_nodes' => count($nodes),
            'nodes' => $nodeStatuses,
        ];
    }
}
```

### Performance Optimization

#### Caching Strategy

```php
<?php
// app/Services/VideoCallCacheService.php

class VideoCallCacheService
{
    public function cacheCallMetrics(VideoCall $call, array $metrics): void
    {
        Cache::put(
            "call_metrics:{$call->id}",
            $metrics,
            now()->addMinutes(5)
        );
    }
    
    public function getCachedCallMetrics(VideoCall $call): ?array
    {
        return Cache::get("call_metrics:{$call->id}");
    }
    
    public function cacheNodeStatistics(array $stats): void
    {
        Cache::put(
            'videobridge_node_stats',
            $stats,
            now()->addMinutes(1)
        );
    }
    
    public function getCachedNodeStatistics(): ?array
    {
        return Cache::get('videobridge_node_stats');
    }
}
```

## Testing Strategy

### Unit Tests

```php
<?php
// tests/Unit/VideoCallServiceTest.php

class VideoCallServiceTest extends TestCase
{
    public function test_can_create_jitsi_call()
    {
        $service = new VideoCallService();
        $user = User::factory()->create();
        
        $call = $service->createCall([
            'host_user_id' => $user->id,
            'title' => 'Test Call',
            'type' => 'coffee_chat',
            'scheduled_at' => now()->addHour(),
        ]);
        
        $this->assertInstanceOf(VideoCall::class, $call);
        $this->assertEquals('jitsi', $call->provider);
        $this->assertNotNull($call->jitsi_room_name);
    }
    
    public function test_can_generate_jitsi_url()
    {
        $service = new VideoCallService();
        $user = User::factory()->create();
        $call = VideoCall::factory()->create([
            'provider' => 'jitsi',
            'jitsi_room_name' => 'test_room_123',
        ]);
        
        $url = $service->generateJitsiUrl($call, $user);
        
        $this->assertStringContains('meet.jit.si', $url);
        $this->assertStringContains('test_room_123', $url);
        $this->assertStringContains($user->name, $url);
    }
}
```

### Integration Tests

```php
<?php
// tests/Feature/VideoCallIntegrationTest.php

class VideoCallIntegrationTest extends TestCase
{
    public function test_complete_coffee_chat_flow()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();
        
        // Create coffee chat request
        $response = $this->actingAs($requester)
            ->postJson('/api/coffee-chats/request', [
                'recipient_id' => $recipient->id,
                'proposed_times' => [
                    now()->addDay()->toISOString(),
                    now()->addDays(2)->toISOString(),
                ],
                'message' => 'Would love to chat about your career journey!',
            ]);
        
        $response->assertStatus(201);
        $requestId = $response->json('data.id');
        
        // Accept the request
        $response = $this->actingAs($recipient)
            ->postJson("/api/coffee-chats/{$requestId}/respond", [
                'action' => 'accept',
                'selected_time' => now()->addDay()->toISOString(),
            ]);
        
        $response->assertStatus(200);
        $callId = $response->json('data.call.id');
        
        // Join the call
        $response = $this->actingAs($requester)
            ->postJson("/api/video-calls/{$callId}/join");
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'jitsi_url',
                         'participant',
                     ],
                 ]);
    }
}
```

This comprehensive documentation provides a complete implementation guide for the Video Calling Integration task using Jitsi, Jitsi Videobridge, and LiveKit as specified. The implementation covers all requirements including basic video calling, scheduling, scalability, screen sharing, and advanced AI features.