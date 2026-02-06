<template>
    <div class="virtual-event-controls">
        <!-- Control Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Event Controls</h3>
                <p class="text-sm text-gray-600">Manage your virtual event settings and participants</p>
            </div>

            <div class="flex items-center space-x-2">
                <div class="flex items-center space-x-2 text-sm text-gray-600">
                    <div class="h-2 w-2 animate-pulse rounded-full bg-green-500"></div>
                    <span>Live</span>
                </div>
                <div class="text-sm text-gray-600">{{ participants.length }} participants</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
            <button
                @click="toggleMeeting"
                :class="[
                    'flex flex-col items-center rounded-lg border-2 p-4 transition-all',
                    meetingActive
                        ? 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'
                        : 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100',
                ]"
            >
                <Icon :name="meetingActive ? 'stop-circle' : 'play-circle'" class="mb-2 h-6 w-6" />
                <span class="text-sm font-medium">{{ meetingActive ? 'End Meeting' : 'Start Meeting' }}</span>
            </button>

            <button
                @click="toggleRecording"
                :class="[
                    'flex flex-col items-center rounded-lg border-2 p-4 transition-all',
                    isRecording
                        ? 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'
                        : 'border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100',
                ]"
                :disabled="!event.recording_enabled"
            >
                <Icon :name="isRecording ? 'stop-circle' : 'video'" class="mb-2 h-6 w-6" />
                <span class="text-sm font-medium">{{ isRecording ? 'Stop Recording' : 'Start Recording' }}</span>
            </button>

            <button
                @click="toggleWaitingRoom"
                :class="[
                    'flex flex-col items-center rounded-lg border-2 p-4 transition-all',
                    waitingRoomEnabled
                        ? 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100'
                        : 'border-gray-200 bg-gray-50 text-gray-700 hover:bg-gray-100',
                ]"
            >
                <Icon name="shield" class="mb-2 h-6 w-6" />
                <span class="text-sm font-medium">Waiting Room</span>
            </button>

            <button
                @click="showBreakoutRooms = true"
                class="flex flex-col items-center rounded-lg border-2 border-gray-200 bg-gray-50 p-4 text-gray-700 transition-all hover:bg-gray-100"
            >
                <Icon name="users" class="mb-2 h-6 w-6" />
                <span class="text-sm font-medium">Breakout Rooms</span>
            </button>
        </div>

        <!-- Participants Management -->
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
            <div class="mb-4 flex items-center justify-between">
                <h4 class="font-medium text-gray-900">Participants ({{ participants.length }})</h4>
                <div class="flex items-center space-x-2">
                    <button @click="muteAll" class="rounded px-3 py-1 text-sm text-red-600 transition-colors hover:bg-red-50 hover:text-red-700">
                        <Icon name="mic-off" class="mr-1 h-4 w-4" />
                        Mute All
                    </button>
                    <button
                        @click="showInviteModal = true"
                        class="rounded px-3 py-1 text-sm text-blue-600 transition-colors hover:bg-blue-50 hover:text-blue-700"
                    >
                        <Icon name="user-plus" class="mr-1 h-4 w-4" />
                        Invite
                    </button>
                </div>
            </div>

            <!-- Waiting Room Queue -->
            <div v-if="waitingParticipants.length > 0" class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 p-3">
                <div class="mb-2 flex items-center justify-between">
                    <h5 class="text-sm font-medium text-yellow-800">Waiting Room ({{ waitingParticipants.length }})</h5>
                    <button @click="admitAll" class="text-xs text-yellow-700 underline hover:text-yellow-800">Admit All</button>
                </div>
                <div class="space-y-2">
                    <div v-for="participant in waitingParticipants" :key="participant.id" class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <img :src="participant.avatar || '/default-avatar.png'" :alt="participant.name" class="h-6 w-6 rounded-full" />
                            <span class="text-sm text-yellow-800">{{ participant.name }}</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <button
                                @click="admitParticipant(participant.id)"
                                class="rounded bg-green-100 px-2 py-1 text-xs text-green-700 transition-colors hover:bg-green-200"
                            >
                                Admit
                            </button>
                            <button
                                @click="denyParticipant(participant.id)"
                                class="rounded bg-red-100 px-2 py-1 text-xs text-red-700 transition-colors hover:bg-red-200"
                            >
                                Deny
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Participants -->
            <div class="max-h-64 overflow-y-auto">
                <div class="space-y-2">
                    <div
                        v-for="participant in activeParticipants"
                        :key="participant.id"
                        class="flex items-center justify-between rounded p-2 hover:bg-gray-50"
                    >
                        <div class="flex items-center space-x-3">
                            <div class="relative">
                                <img :src="participant.avatar || '/default-avatar.png'" :alt="participant.name" class="h-8 w-8 rounded-full" />
                                <div
                                    v-if="participant.is_speaking"
                                    class="absolute -bottom-1 -right-1 h-3 w-3 animate-pulse rounded-full border-2 border-white bg-green-500"
                                ></div>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-900">{{ participant.name }}</span>
                                    <span v-if="participant.is_host" class="rounded bg-blue-100 px-2 py-0.5 text-xs text-blue-700"> Host </span>
                                    <span v-if="participant.is_moderator" class="rounded bg-purple-100 px-2 py-0.5 text-xs text-purple-700">
                                        Moderator
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2 text-xs text-gray-500">
                                    <span>{{ participant.join_time }}</span>
                                    <span v-if="participant.breakout_room">• Room {{ participant.breakout_room }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-1">
                            <!-- Audio Status -->
                            <button
                                @click="toggleParticipantAudio(participant.id)"
                                :class="[
                                    'rounded p-1 transition-colors',
                                    participant.audio_enabled ? 'text-green-600 hover:bg-green-50' : 'text-red-600 hover:bg-red-50',
                                ]"
                                :title="participant.audio_enabled ? 'Mute' : 'Unmute'"
                            >
                                <Icon :name="participant.audio_enabled ? 'mic' : 'mic-off'" class="h-4 w-4" />
                            </button>

                            <!-- Video Status -->
                            <button
                                @click="toggleParticipantVideo(participant.id)"
                                :class="[
                                    'rounded p-1 transition-colors',
                                    participant.video_enabled ? 'text-green-600 hover:bg-green-50' : 'text-red-600 hover:bg-red-50',
                                ]"
                                :title="participant.video_enabled ? 'Stop Video' : 'Start Video'"
                            >
                                <Icon :name="participant.video_enabled ? 'video' : 'video-off'" class="h-4 w-4" />
                            </button>

                            <!-- More Actions -->
                            <div class="relative">
                                <button
                                    @click="toggleParticipantMenu(participant.id)"
                                    class="rounded p-1 text-gray-400 transition-colors hover:bg-gray-50 hover:text-gray-600"
                                >
                                    <Icon name="more-vertical" class="h-4 w-4" />
                                </button>

                                <!-- Participant Menu -->
                                <div
                                    v-if="activeParticipantMenu === participant.id"
                                    class="absolute right-0 top-8 z-10 w-48 rounded-lg border border-gray-200 bg-white shadow-lg"
                                >
                                    <div class="py-1">
                                        <button
                                            @click="makeParticipantModerator(participant.id)"
                                            class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                                        >
                                            Make Moderator
                                        </button>
                                        <button
                                            @click="moveToBreakoutRoom(participant.id)"
                                            class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                                        >
                                            Move to Breakout Room
                                        </button>
                                        <button
                                            @click="removeParticipant(participant.id)"
                                            class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                                        >
                                            Remove from Meeting
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meeting Settings -->
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <h4 class="mb-4 font-medium text-gray-900">Meeting Settings</h4>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Allow Chat</label>
                        <p class="text-xs text-gray-500">Enable text messaging</p>
                    </div>
                    <input
                        v-model="settings.chat_enabled"
                        type="checkbox"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        @change="updateSettings"
                    />
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Screen Sharing</label>
                        <p class="text-xs text-gray-500">Allow participants to share screens</p>
                    </div>
                    <input
                        v-model="settings.screen_sharing_enabled"
                        type="checkbox"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        @change="updateSettings"
                    />
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Recording</label>
                        <p class="text-xs text-gray-500">Enable meeting recording</p>
                    </div>
                    <input
                        v-model="settings.recording_enabled"
                        type="checkbox"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        @change="updateSettings"
                    />
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-sm font-medium text-gray-700">Waiting Room</label>
                        <p class="text-xs text-gray-500">Require host approval</p>
                    </div>
                    <input
                        v-model="settings.waiting_room_enabled"
                        type="checkbox"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        @change="updateSettings"
                    />
                </div>
            </div>
        </div>

        <!-- Invite Modal -->
        <div
            v-if="showInviteModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            @click.self="showInviteModal = false"
        >
            <div class="w-full max-w-md rounded-lg bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Invite Participants</h3>

                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Email Addresses</label>
                        <textarea
                            v-model="inviteEmails"
                            rows="3"
                            placeholder="Enter email addresses, separated by commas"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Personal Message (Optional)</label>
                        <textarea
                            v-model="inviteMessage"
                            rows="2"
                            placeholder="Add a personal message to the invitation"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button @click="showInviteModal = false" class="px-4 py-2 text-gray-600 transition-colors hover:text-gray-800">Cancel</button>
                    <button @click="sendInvitations" class="rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700">
                        Send Invitations
                    </button>
                </div>
            </div>
        </div>

        <!-- Breakout Rooms Modal -->
        <div
            v-if="showBreakoutRooms"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            @click.self="showBreakoutRooms = false"
        >
            <div class="w-full max-w-2xl rounded-lg bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Breakout Rooms</h3>

                <!-- Breakout room management would go here -->
                <div class="py-8 text-center text-gray-500">
                    <Icon name="users" class="mx-auto mb-4 h-12 w-12" />
                    <p>Breakout room management coming soon</p>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        @click="showBreakoutRooms = false"
                        class="rounded-lg bg-gray-100 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-200"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/Components/Icon.vue';
import { useToast } from '@/Composables/useToast';
import { computed, onMounted, ref } from 'vue';

interface Participant {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    is_host: boolean;
    is_moderator: boolean;
    is_speaking: boolean;
    audio_enabled: boolean;
    video_enabled: boolean;
    join_time: string;
    status: 'active' | 'waiting';
    breakout_room?: number;
}

interface Event {
    id: number;
    title: string;
    recording_enabled: boolean;
    chat_enabled: boolean;
    screen_sharing_enabled: boolean;
    waiting_room_enabled: boolean;
}

interface Props {
    event: Event;
    participants: Participant[];
    canManageEvent?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canManageEvent: true,
});

const emit = defineEmits<{
    settingsUpdated: [settings: any];
    participantAction: [action: string, participantId: number, data?: any];
    meetingAction: [action: string, data?: any];
}>();

const { showToast } = useToast();

// Reactive state
const meetingActive = ref(true);
const isRecording = ref(false);
const waitingRoomEnabled = ref(props.event.waiting_room_enabled);
const activeParticipantMenu = ref<number | null>(null);
const showInviteModal = ref(false);
const showBreakoutRooms = ref(false);
const inviteEmails = ref('');
const inviteMessage = ref('');

const settings = ref({
    chat_enabled: props.event.chat_enabled,
    screen_sharing_enabled: props.event.screen_sharing_enabled,
    recording_enabled: props.event.recording_enabled,
    waiting_room_enabled: props.event.waiting_room_enabled,
});

// Computed
const activeParticipants = computed(() => props.participants.filter((p) => p.status === 'active'));

const waitingParticipants = computed(() => props.participants.filter((p) => p.status === 'waiting'));

// Methods
const toggleMeeting = () => {
    meetingActive.value = !meetingActive.value;
    emit('meetingAction', meetingActive.value ? 'start' : 'end');

    showToast({
        type: 'success',
        message: meetingActive.value ? 'Meeting started' : 'Meeting ended',
    });
};

const toggleRecording = () => {
    if (!props.event.recording_enabled) {
        showToast({
            type: 'error',
            message: 'Recording is not enabled for this event',
        });
        return;
    }

    isRecording.value = !isRecording.value;
    emit('meetingAction', 'toggleRecording', { recording: isRecording.value });

    showToast({
        type: 'success',
        message: isRecording.value ? 'Recording started' : 'Recording stopped',
    });
};

const toggleWaitingRoom = () => {
    waitingRoomEnabled.value = !waitingRoomEnabled.value;
    settings.value.waiting_room_enabled = waitingRoomEnabled.value;
    updateSettings();
};

const muteAll = () => {
    emit('meetingAction', 'muteAll');
    showToast({
        type: 'success',
        message: 'All participants muted',
    });
};

const admitAll = () => {
    waitingParticipants.value.forEach((participant) => {
        emit('participantAction', 'admit', participant.id);
    });

    showToast({
        type: 'success',
        message: `Admitted ${waitingParticipants.value.length} participants`,
    });
};

const admitParticipant = (participantId: number) => {
    emit('participantAction', 'admit', participantId);
    showToast({
        type: 'success',
        message: 'Participant admitted',
    });
};

const denyParticipant = (participantId: number) => {
    emit('participantAction', 'deny', participantId);
    showToast({
        type: 'info',
        message: 'Participant denied entry',
    });
};

const toggleParticipantAudio = (participantId: number) => {
    emit('participantAction', 'toggleAudio', participantId);
};

const toggleParticipantVideo = (participantId: number) => {
    emit('participantAction', 'toggleVideo', participantId);
};

const toggleParticipantMenu = (participantId: number) => {
    activeParticipantMenu.value = activeParticipantMenu.value === participantId ? null : participantId;
};

const makeParticipantModerator = (participantId: number) => {
    emit('participantAction', 'makeModerator', participantId);
    activeParticipantMenu.value = null;
    showToast({
        type: 'success',
        message: 'Participant promoted to moderator',
    });
};

const moveToBreakoutRoom = (participantId: number) => {
    // This would open a breakout room selection modal
    activeParticipantMenu.value = null;
    showToast({
        type: 'info',
        message: 'Breakout room feature coming soon',
    });
};

const removeParticipant = (participantId: number) => {
    emit('participantAction', 'remove', participantId);
    activeParticipantMenu.value = null;
    showToast({
        type: 'success',
        message: 'Participant removed from meeting',
    });
};

const updateSettings = () => {
    emit('settingsUpdated', settings.value);
    showToast({
        type: 'success',
        message: 'Meeting settings updated',
    });
};

const sendInvitations = () => {
    if (!inviteEmails.value.trim()) {
        showToast({
            type: 'error',
            message: 'Please enter at least one email address',
        });
        return;
    }

    const emails = inviteEmails.value
        .split(',')
        .map((email) => email.trim())
        .filter(Boolean);

    emit('meetingAction', 'sendInvitations', {
        emails,
        message: inviteMessage.value,
    });

    showInviteModal.value = false;
    inviteEmails.value = '';
    inviteMessage.value = '';

    showToast({
        type: 'success',
        message: `Invitations sent to ${emails.length} recipients`,
    });
};

// Close participant menu when clicking outside
onMounted(() => {
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.relative')) {
            activeParticipantMenu.value = null;
        }
    });
});
</script>

<style scoped>
.virtual-event-controls {
    @apply space-y-6;
}

/* Control buttons */
.control-button {
    @apply flex flex-col items-center rounded-lg border-2 p-4 transition-all;
}

.control-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Participant list */
.participant-item {
    @apply flex items-center justify-between rounded p-2 transition-colors hover:bg-gray-50;
}

/* Speaking indicator animation */
@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .virtual-event-controls {
        @apply space-y-4;
    }

    .control-button {
        @apply p-3;
    }
}
</style>















