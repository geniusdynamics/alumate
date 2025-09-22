<template>
    <div class="video-call-interface">
        <!-- Call Header -->
        <div class="call-header flex items-center justify-between bg-gray-900 p-4 text-white">
            <div class="call-info">
                <h2 class="text-lg font-semibold">{{ call.title }}</h2>
                <p class="text-sm text-gray-300">{{ formatDuration(callDuration) }} • {{ activeParticipants.length }} participants</p>
            </div>

            <div class="call-actions flex space-x-2">
                <button
                    v-if="canModerate"
                    @click="toggleRecording"
                    :class="[
                        'rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                        isRecording ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-gray-700 text-gray-300 hover:bg-gray-600',
                    ]"
                >
                    <i :class="isRecording ? 'fas fa-stop' : 'fas fa-record-vinyl'" class="mr-1"></i>
                    {{ isRecording ? 'Stop Recording' : 'Record' }}
                </button>

                <button @click="leaveCall" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700">
                    <i class="fas fa-phone-slash mr-1"></i>
                    Leave Call
                </button>
            </div>
        </div>

        <!-- Video Grid -->
        <div class="video-grid relative flex-1 bg-black">
            <!-- Main Video Area -->
            <div class="main-video-container relative h-full">
                <!-- Jitsi Meet Embed -->
                <div v-if="call.provider === 'jitsi'" ref="jitsiContainer" class="jitsi-meet-container h-full w-full"></div>

                <!-- Fallback for other providers -->
                <div v-else class="flex h-full items-center justify-center text-white">
                    <div class="text-center">
                        <i class="fas fa-video mb-4 text-6xl text-gray-400"></i>
                        <p class="text-lg">Video calling with {{ call.provider }} coming soon</p>
                    </div>
                </div>

                <!-- Screen Sharing Indicator -->
                <div v-if="isScreenSharing" class="absolute left-4 top-4 rounded-lg bg-blue-600 px-3 py-1 text-sm text-white">
                    <i class="fas fa-desktop mr-1"></i>
                    Screen Sharing Active
                </div>

                <!-- Connection Quality Indicator -->
                <div class="absolute right-4 top-4 flex items-center space-x-2">
                    <div
                        :class="[
                            'h-3 w-3 rounded-full',
                            connectionQuality === 'good' ? 'bg-green-500' : connectionQuality === 'fair' ? 'bg-yellow-500' : 'bg-red-500',
                        ]"
                    ></div>
                    <span class="text-sm capitalize text-white">{{ connectionQuality }}</span>
                </div>
            </div>

            <!-- Participants Sidebar -->
            <div v-if="showParticipants" class="participants-sidebar w-80 overflow-y-auto bg-gray-800 p-4 text-white">
                <h3 class="mb-4 text-lg font-semibold">Participants ({{ call.participants.length }})</h3>

                <div class="space-y-3">
                    <div
                        v-for="participant in call.participants"
                        :key="participant.id"
                        class="participant-item flex items-center space-x-3 rounded-lg p-2 hover:bg-gray-700"
                    >
                        <img :src="participant.user.avatar_url || '/default-avatar.png'" :alt="participant.user.name" class="h-8 w-8 rounded-full" />
                        <div class="flex-1">
                            <p class="font-medium">{{ participant.user.name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ participant.role }}
                                <span v-if="participant.is_active" class="ml-1 text-green-400">• Active</span>
                            </p>
                        </div>

                        <div class="participant-actions">
                            <button
                                v-if="canModerate && participant.user.id !== $page.props.auth.user.id"
                                @click="toggleParticipantMute(participant)"
                                class="text-sm text-gray-400 hover:text-white"
                            >
                                <i :class="participant.is_muted ? 'fas fa-microphone-slash' : 'fas fa-microphone'"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call Controls -->
        <div class="call-controls flex items-center justify-center space-x-4 bg-gray-900 p-4">
            <button @click="toggleMicrophone" :class="['control-button', isMuted ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-700 hover:bg-gray-600']">
                <i :class="isMuted ? 'fas fa-microphone-slash' : 'fas fa-microphone'"></i>
            </button>

            <button @click="toggleCamera" :class="['control-button', isCameraOff ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-700 hover:bg-gray-600']">
                <i :class="isCameraOff ? 'fas fa-video-slash' : 'fas fa-video'"></i>
            </button>

            <button
                @click="toggleScreenShare"
                :class="['control-button', isScreenSharing ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-700 hover:bg-gray-600']"
            >
                <i class="fas fa-desktop"></i>
            </button>

            <button @click="toggleParticipants" class="control-button bg-gray-700 hover:bg-gray-600">
                <i class="fas fa-users"></i>
            </button>

            <button @click="toggleChat" class="control-button relative bg-gray-700 hover:bg-gray-600">
                <i class="fas fa-comment"></i>
                <span
                    v-if="unreadMessages > 0"
                    class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white"
                >
                    {{ unreadMessages }}
                </span>
            </button>

            <button @click="openSettings" class="control-button bg-gray-700 hover:bg-gray-600">
                <i class="fas fa-cog"></i>
            </button>
        </div>

        <!-- Chat Sidebar -->
        <div v-if="showChat" class="chat-sidebar fixed right-0 top-0 z-50 flex h-full w-80 flex-col bg-white shadow-lg">
            <div class="chat-header flex items-center justify-between border-b bg-gray-100 p-4">
                <h3 class="font-semibold">Chat</h3>
                <button @click="toggleChat" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="chat-messages flex-1 space-y-3 overflow-y-auto p-4">
                <div v-for="message in chatMessages" :key="message.id" class="message">
                    <div class="flex items-start space-x-2">
                        <img :src="message.user.avatar_url || '/default-avatar.png'" :alt="message.user.name" class="h-6 w-6 rounded-full" />
                        <div class="flex-1">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-medium">{{ message.user.name }}</span>
                                <span class="text-xs text-gray-500">{{ formatTime(message.created_at) }}</span>
                            </div>
                            <p class="mt-1 text-sm">{{ message.content }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chat-input border-t p-4">
                <div class="flex space-x-2">
                    <input
                        v-model="newMessage"
                        @keyup.enter="sendMessage"
                        type="text"
                        placeholder="Type a message..."
                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <button
                        @click="sendMessage"
                        :disabled="!newMessage.trim()"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Settings Modal -->
        <div v-if="showSettings" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="w-96 max-w-full rounded-lg bg-white p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Call Settings</h3>
                    <button @click="closeSettings" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700"> Microphone </label>
                        <select
                            v-model="selectedMicrophone"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option v-for="device in audioDevices" :key="device.deviceId" :value="device.deviceId">
                                {{ device.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700"> Camera </label>
                        <select
                            v-model="selectedCamera"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option v-for="device in videoDevices" :key="device.deviceId" :value="device.deviceId">
                                {{ device.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700"> Video Quality </label>
                        <select
                            v-model="videoQuality"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="low">Low (360p)</option>
                            <option value="medium">Medium (720p)</option>
                            <option value="high">High (1080p)</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button @click="closeSettings" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                    <button @click="saveSettings" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Save Settings</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    call: {
        type: Object,
        required: true,
    },
    jitsiUrl: {
        type: String,
        default: null,
    },
    canModerate: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['call-ended', 'participant-joined', 'participant-left']);

const page = usePage();

// Refs
const jitsiContainer = ref(null);
const jitsiApi = ref(null);

// State
const callDuration = ref(0);
const isRecording = ref(false);
const isScreenSharing = ref(false);
const isMuted = ref(false);
const isCameraOff = ref(false);
const showParticipants = ref(true);
const showChat = ref(false);
const showSettings = ref(false);
const connectionQuality = ref('good');
const unreadMessages = ref(0);
const newMessage = ref('');
const chatMessages = ref([]);

// Device settings
const audioDevices = ref([]);
const videoDevices = ref([]);
const selectedMicrophone = ref('');
const selectedCamera = ref('');
const videoQuality = ref('medium');

// Computed
const activeParticipants = computed(() => {
    return props.call.participants?.filter((p) => p.is_active) || [];
});

// Methods
const initializeJitsi = () => {
    if (props.call.provider !== 'jitsi' || !props.jitsiUrl || !jitsiContainer.value) {
        return;
    }

    // Load Jitsi Meet API
    const script = document.createElement('script');
    script.src = 'https://meet.jit.si/external_api.js';
    script.onload = () => {
        const domain = 'meet.jit.si';
        const options = {
            roomName: props.call.jitsi_room_name,
            width: '100%',
            height: '100%',
            parentNode: jitsiContainer.value,
            userInfo: {
                displayName: page.props.auth.user.name,
                email: page.props.auth.user.email,
            },
            configOverwrite: {
                startWithAudioMuted: true,
                startWithVideoMuted: false,
                enableWelcomePage: false,
                prejoinPageEnabled: false,
            },
            interfaceConfigOverwrite: {
                TOOLBAR_BUTTONS: [
                    'microphone',
                    'camera',
                    'closedcaptions',
                    'desktop',
                    'fullscreen',
                    'fodeviceselection',
                    'hangup',
                    'profile',
                    'chat',
                    'recording',
                    'livestreaming',
                    'etherpad',
                    'sharedvideo',
                    'settings',
                    'raisehand',
                    'videoquality',
                    'filmstrip',
                    'invite',
                    'feedback',
                    'stats',
                    'shortcuts',
                    'tileview',
                    'videobackgroundblur',
                    'download',
                    'help',
                    'mute-everyone',
                ],
            },
        };

        jitsiApi.value = new window.JitsiMeetExternalAPI(domain, options);

        // Event listeners
        jitsiApi.value.addEventListener('participantJoined', handleParticipantJoined);
        jitsiApi.value.addEventListener('participantLeft', handleParticipantLeft);
        jitsiApi.value.addEventListener('audioMuteStatusChanged', handleAudioMuteChanged);
        jitsiApi.value.addEventListener('videoMuteStatusChanged', handleVideoMuteChanged);
        jitsiApi.value.addEventListener('screenSharingStatusChanged', handleScreenSharingChanged);
        jitsiApi.value.addEventListener('recordingStatusChanged', handleRecordingChanged);
    };

    document.head.appendChild(script);
};

const handleParticipantJoined = (participant) => {
    emit('participant-joined', participant);
};

const handleParticipantLeft = (participant) => {
    emit('participant-left', participant);
};

const handleAudioMuteChanged = (event) => {
    isMuted.value = event.muted;
};

const handleVideoMuteChanged = (event) => {
    isCameraOff.value = event.muted;
};

const handleScreenSharingChanged = (event) => {
    isScreenSharing.value = event.on;
};

const handleRecordingChanged = (event) => {
    isRecording.value = event.on;
};

const toggleMicrophone = () => {
    if (jitsiApi.value) {
        jitsiApi.value.executeCommand('toggleAudio');
    }
};

const toggleCamera = () => {
    if (jitsiApi.value) {
        jitsiApi.value.executeCommand('toggleVideo');
    }
};

const toggleScreenShare = () => {
    if (jitsiApi.value) {
        jitsiApi.value.executeCommand('toggleShareScreen');
    }
};

const toggleRecording = () => {
    if (jitsiApi.value) {
        jitsiApi.value.executeCommand('toggleRecording');
    }
};

const toggleParticipants = () => {
    showParticipants.value = !showParticipants.value;
};

const toggleChat = () => {
    showChat.value = !showChat.value;
    if (showChat.value) {
        unreadMessages.value = 0;
    }
};

const openSettings = () => {
    showSettings.value = true;
    loadDevices();
};

const closeSettings = () => {
    showSettings.value = false;
};

const saveSettings = () => {
    // Apply device settings
    if (jitsiApi.value) {
        if (selectedMicrophone.value) {
            jitsiApi.value.setAudioInputDevice(selectedMicrophone.value);
        }
        if (selectedCamera.value) {
            jitsiApi.value.setVideoInputDevice(selectedCamera.value);
        }
    }

    closeSettings();
};

const loadDevices = async () => {
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        audioDevices.value = devices.filter((device) => device.kind === 'audioinput');
        videoDevices.value = devices.filter((device) => device.kind === 'videoinput');
    } catch (error) {
        console.error('Error loading devices:', error);
    }
};

const sendMessage = () => {
    if (!newMessage.value.trim()) return;

    // In a real implementation, this would send via WebSocket or API
    const message = {
        id: Date.now(),
        content: newMessage.value,
        user: page.props.auth.user,
        created_at: new Date().toISOString(),
    };

    chatMessages.value.push(message);
    newMessage.value = '';
};

const leaveCall = async () => {
    try {
        await axios.post(`/api/video-calls/${props.call.id}/leave`);

        if (jitsiApi.value) {
            jitsiApi.value.dispose();
        }

        emit('call-ended');
    } catch (error) {
        console.error('Error leaving call:', error);
    }
};

const formatDuration = (seconds) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    return `${minutes}:${secs.toString().padStart(2, '0')}`;
};

const formatTime = (timestamp) => {
    return new Date(timestamp).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Lifecycle
onMounted(() => {
    initializeJitsi();

    // Start call duration timer
    const timer = setInterval(() => {
        if (props.call.started_at) {
            const startTime = new Date(props.call.started_at);
            const now = new Date();
            callDuration.value = Math.floor((now - startTime) / 1000);
        }
    }, 1000);

    // Cleanup timer on unmount
    onUnmounted(() => {
        clearInterval(timer);
    });
});

onUnmounted(() => {
    if (jitsiApi.value) {
        jitsiApi.value.dispose();
    }
});
</script>

<style scoped>
.video-call-interface {
    @apply flex h-screen flex-col;
}

.control-button {
    @apply flex h-12 w-12 items-center justify-center rounded-full text-white transition-colors;
}

.jitsi-meet-container {
    @apply h-full w-full;
}

.chat-sidebar {
    box-shadow: -4px 0 6px -1px rgba(0, 0, 0, 0.1);
}

.participant-item:hover {
    @apply bg-gray-700;
}

.message {
    @apply break-words;
}
</style>
