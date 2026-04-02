<template>
    <div class="hybrid-event-interface">
        <!-- Event Mode Toggle -->
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Event Mode</h3>
                <div class="flex items-center space-x-2 text-sm text-gray-600">
                    <Icon name="users" class="h-4 w-4" />
                    <span>{{ totalAttendees }} total attendees</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <!-- In-Person Attendees -->
                <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                    <div class="mb-2 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <Icon name="map-pin" class="h-5 w-5 text-green-600" />
                            <span class="font-medium text-green-900">In-Person</span>
                        </div>
                        <span class="text-2xl font-bold text-green-600">{{ inPersonCount }}</span>
                    </div>
                    <p class="text-sm text-green-700">Physically present</p>
                </div>

                <!-- Virtual Attendees -->
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <div class="mb-2 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <Icon name="video" class="h-5 w-5 text-blue-600" />
                            <span class="font-medium text-blue-900">Virtual</span>
                        </div>
                        <span class="text-2xl font-bold text-blue-600">{{ virtualCount }}</span>
                    </div>
                    <p class="text-sm text-blue-700">Joined online</p>
                </div>

                <!-- Hybrid Features -->
                <div class="rounded-lg border border-purple-200 bg-purple-50 p-4">
                    <div class="mb-2 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <Icon name="zap" class="h-5 w-5 text-purple-600" />
                            <span class="font-medium text-purple-900">Interactive</span>
                        </div>
                        <span class="text-2xl font-bold text-purple-600">{{ interactiveFeatures }}</span>
                    </div>
                    <p class="text-sm text-purple-700">Active features</p>
                </div>
            </div>
        </div>

        <!-- Main Interface Layout -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Virtual Meeting Area -->
            <div class="lg:col-span-2">
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-gray-900">Virtual Meeting</h4>
                            <div class="flex items-center space-x-2">
                                <button
                                    @click="toggleFullscreen"
                                    class="rounded p-1 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900"
                                    title="Toggle Fullscreen"
                                >
                                    <Icon name="maximize" class="h-4 w-4" />
                                </button>
                                <button
                                    @click="showVirtualControls = !showVirtualControls"
                                    class="rounded p-1 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900"
                                    title="Toggle Controls"
                                >
                                    <Icon name="settings" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Virtual Event Viewer -->
                    <VirtualEventViewer
                        :event="event"
                        :meeting-credentials="meetingCredentials"
                        :can-manage-event="canManageEvent"
                        :can-record="canRecord"
                        height="400px"
                        @settings-updated="handleVirtualSettingsUpdate"
                        @recording-toggled="handleRecordingToggle"
                    />
                </div>

                <!-- Virtual Controls (Collapsible) -->
                <div v-if="showVirtualControls && canManageEvent" class="mt-4">
                    <VirtualEventControls
                        :event="event"
                        :participants="virtualParticipants"
                        :can-manage-event="canManageEvent"
                        @settings-updated="handleVirtualSettingsUpdate"
                        @participant-action="handleParticipantAction"
                        @meeting-action="handleMeetingAction"
                    />
                </div>
            </div>

            <!-- Hybrid Features Sidebar -->
            <div class="space-y-6">
                <!-- Cross-Platform Chat -->
                <div class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-gray-900">Event Chat</h4>
                            <div class="flex items-center space-x-1">
                                <span class="text-xs text-gray-500">{{ chatMessages.length }} messages</span>
                                <button @click="toggleChat" class="rounded p-1 text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900">
                                    <Icon :name="chatExpanded ? 'chevron-up' : 'chevron-down'" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="chatExpanded" class="p-4">
                        <!-- Chat Messages -->
                        <div class="mb-4 h-64 space-y-3 overflow-y-auto">
                            <div v-for="message in chatMessages" :key="message.id" class="flex items-start space-x-3">
                                <img
                                    :src="message.user.avatar || '/default-avatar.png'"
                                    :alt="message.user.name"
                                    class="h-6 w-6 flex-shrink-0 rounded-full"
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-900">{{ message.user.name }}</span>
                                        <span
                                            :class="[
                                                'rounded-full px-2 py-0.5 text-xs',
                                                message.user.attendance_type === 'virtual'
                                                    ? 'bg-blue-100 text-blue-700'
                                                    : 'bg-green-100 text-green-700',
                                            ]"
                                        >
                                            {{ message.user.attendance_type }}
                                        </span>
                                        <span class="text-xs text-gray-500">{{ message.timestamp }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-700">{{ message.content }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Chat Input -->
                        <div class="flex items-center space-x-2">
                            <input
                                v-model="newMessage"
                                type="text"
                                placeholder="Type a message..."
                                class="flex-1 rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                @keyup.enter="sendMessage"
                            />
                            <button
                                @click="sendMessage"
                                :disabled="!newMessage.trim()"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Icon name="send" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Live Q&A -->
                <div class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-gray-900">Q&A Session</h4>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-gray-500">{{ questions.length }} questions</span>
                                <button
                                    v-if="canManageEvent"
                                    @click="toggleQAModeration"
                                    :class="[
                                        'rounded px-2 py-1 text-xs transition-colors',
                                        qaModerationEnabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700',
                                    ]"
                                >
                                    {{ qaModerationEnabled ? 'Moderated' : 'Open' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <!-- Question Input -->
                        <div class="mb-4">
                            <div class="flex items-center space-x-2">
                                <input
                                    v-model="newQuestion"
                                    type="text"
                                    placeholder="Ask a question..."
                                    class="flex-1 rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    @keyup.enter="submitQuestion"
                                />
                                <button
                                    @click="submitQuestion"
                                    :disabled="!newQuestion.trim()"
                                    class="rounded-lg bg-green-600 px-4 py-2 text-white transition-colors hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <Icon name="help-circle" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Questions List -->
                        <div class="max-h-48 space-y-3 overflow-y-auto">
                            <div v-for="question in sortedQuestions" :key="question.id" class="rounded-lg border border-gray-200 p-3">
                                <div class="mb-2 flex items-start justify-between">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-900">{{ question.user.name }}</span>
                                        <span
                                            :class="[
                                                'rounded-full px-2 py-0.5 text-xs',
                                                question.user.attendance_type === 'virtual'
                                                    ? 'bg-blue-100 text-blue-700'
                                                    : 'bg-green-100 text-green-700',
                                            ]"
                                        >
                                            {{ question.user.attendance_type }}
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button
                                            @click="upvoteQuestion(question.id)"
                                            class="flex items-center space-x-1 text-xs text-gray-600 transition-colors hover:text-blue-600"
                                        >
                                            <Icon name="arrow-up" class="h-3 w-3" />
                                            <span>{{ question.upvotes }}</span>
                                        </button>
                                        <button
                                            v-if="canManageEvent"
                                            @click="answerQuestion(question.id)"
                                            class="text-xs text-green-600 transition-colors hover:text-green-700"
                                        >
                                            Answer
                                        </button>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-700">{{ question.content }}</p>
                                <div v-if="question.answer" class="mt-2 rounded border border-green-200 bg-green-50 p-2">
                                    <p class="text-sm text-green-800">{{ question.answer }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Polls -->
                <div class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium text-gray-900">Live Polls</h4>
                            <button
                                v-if="canManageEvent"
                                @click="showCreatePoll = true"
                                class="rounded bg-purple-100 px-3 py-1 text-sm text-purple-700 transition-colors hover:bg-purple-200"
                            >
                                <Icon name="plus" class="mr-1 h-4 w-4" />
                                Create Poll
                            </button>
                        </div>
                    </div>

                    <div class="p-4">
                        <div v-if="activePoll" class="space-y-4">
                            <div>
                                <h5 class="mb-2 font-medium text-gray-900">{{ activePoll.question }}</h5>
                                <div class="space-y-2">
                                    <div v-for="option in activePoll.options" :key="option.id" class="flex items-center justify-between">
                                        <button
                                            @click="votePoll(option.id)"
                                            :disabled="hasVoted"
                                            class="flex-1 rounded border border-gray-300 px-3 py-2 text-left transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            {{ option.text }}
                                        </button>
                                        <div class="ml-3 text-sm text-gray-600">
                                            {{ option.votes }} ({{ Math.round((option.votes / activePoll.total_votes) * 100) }}%)
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center text-xs text-gray-500">{{ activePoll.total_votes }} total votes</div>
                        </div>

                        <div v-else class="py-8 text-center text-gray-500">
                            <Icon name="bar-chart" class="mx-auto mb-4 h-12 w-12" />
                            <p>No active polls</p>
                        </div>
                    </div>
                </div>

                <!-- Networking Opportunities -->
                <div class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                        <h4 class="font-medium text-gray-900">Networking</h4>
                    </div>

                    <div class="p-4">
                        <div class="space-y-3">
                            <button
                                @click="joinNetworkingRoom"
                                class="flex w-full items-center justify-center space-x-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 transition-colors hover:bg-blue-100"
                            >
                                <Icon name="users" class="h-5 w-5 text-blue-600" />
                                <span class="font-medium text-blue-700">Join Networking Room</span>
                            </button>

                            <button
                                @click="requestBreakoutRoom"
                                class="flex w-full items-center justify-center space-x-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 transition-colors hover:bg-green-100"
                            >
                                <Icon name="message-circle" class="h-5 w-5 text-green-600" />
                                <span class="font-medium text-green-700">Request Breakout Room</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Poll Modal -->
        <div
            v-if="showCreatePoll"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            @click.self="showCreatePoll = false"
        >
            <div class="w-full max-w-md rounded-lg bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold">Create Poll</h3>

                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Question</label>
                        <input
                            v-model="newPoll.question"
                            type="text"
                            placeholder="Enter your poll question"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Options</label>
                        <div class="space-y-2">
                            <input
                                v-for="(option, index) in newPoll.options"
                                :key="index"
                                v-model="newPoll.options[index]"
                                type="text"
                                :placeholder="`Option ${index + 1}`"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <button @click="addPollOption" class="mt-2 text-sm text-blue-600 hover:text-blue-700">+ Add Option</button>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button @click="showCreatePoll = false" class="px-4 py-2 text-gray-600 transition-colors hover:text-gray-800">Cancel</button>
                    <button @click="createPoll" class="rounded-lg bg-purple-600 px-4 py-2 text-white transition-colors hover:bg-purple-700">
                        Create Poll
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/Components/Icon.vue';
import VirtualEventControls from '@/Components/VirtualEventControls.vue';
import VirtualEventViewer from '@/Components/VirtualEventViewer.vue';
import { useToast } from '@/Composables/useToast';
import { computed, ref } from 'vue';

interface Event {
    id: number;
    title: string;
    type: 'hybrid' | 'virtual' | 'in_person';
    meeting_platform: string;
    recording_enabled: boolean;
    chat_enabled: boolean;
    screen_sharing_enabled: boolean;
    waiting_room_enabled: boolean;
}

interface Participant {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    attendance_type: 'virtual' | 'in_person';
    is_host: boolean;
    is_moderator: boolean;
    status: 'active' | 'waiting';
}

interface ChatMessage {
    id: number;
    content: string;
    timestamp: string;
    user: {
        name: string;
        avatar?: string;
        attendance_type: 'virtual' | 'in_person';
    };
}

interface Question {
    id: number;
    content: string;
    upvotes: number;
    answer?: string;
    user: {
        name: string;
        attendance_type: 'virtual' | 'in_person';
    };
}

interface Poll {
    id: number;
    question: string;
    options: Array<{
        id: number;
        text: string;
        votes: number;
    }>;
    total_votes: number;
    active: boolean;
}

interface Props {
    event: Event;
    participants: Participant[];
    meetingCredentials: any;
    canManageEvent?: boolean;
    canRecord?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canManageEvent: false,
    canRecord: false,
});

const emit = defineEmits<{
    settingsUpdated: [settings: any];
    participantAction: [action: string, participantId: number, data?: any];
    meetingAction: [action: string, data?: any];
    chatMessage: [message: string];
    questionSubmitted: [question: string];
    pollCreated: [poll: any];
    pollVoted: [optionId: number];
}>();

const { showToast } = useToast();

// Reactive state
const showVirtualControls = ref(false);
const chatExpanded = ref(true);
const newMessage = ref('');
const newQuestion = ref('');
const qaModerationEnabled = ref(false);
const showCreatePoll = ref(false);
const hasVoted = ref(false);

// Mock data (would come from props/API in real implementation)
const chatMessages = ref<ChatMessage[]>([
    {
        id: 1,
        content: 'Welcome everyone to our hybrid event!',
        timestamp: '2:30 PM',
        user: { name: 'Event Host', attendance_type: 'in_person' },
    },
    {
        id: 2,
        content: 'Great to see both virtual and in-person attendees!',
        timestamp: '2:32 PM',
        user: { name: 'John Doe', attendance_type: 'virtual' },
    },
]);

const questions = ref<Question[]>([
    {
        id: 1,
        content: 'What are the key benefits of hybrid events?',
        upvotes: 5,
        user: { name: 'Alice Smith', attendance_type: 'virtual' },
    },
    {
        id: 2,
        content: 'How do you ensure equal participation for virtual attendees?',
        upvotes: 3,
        user: { name: 'Bob Johnson', attendance_type: 'in_person' },
    },
]);

const activePoll = ref<Poll | null>({
    id: 1,
    question: 'What type of events do you prefer?',
    options: [
        { id: 1, text: 'In-person only', votes: 12 },
        { id: 2, text: 'Virtual only', votes: 8 },
        { id: 3, text: 'Hybrid events', votes: 25 },
    ],
    total_votes: 45,
    active: true,
});

const newPoll = ref({
    question: '',
    options: ['', ''],
});

// Computed
const totalAttendees = computed(() => props.participants.length);

const inPersonCount = computed(() => props.participants.filter((p) => p.attendance_type === 'in_person').length);

const virtualCount = computed(() => props.participants.filter((p) => p.attendance_type === 'virtual').length);

const virtualParticipants = computed(() => props.participants.filter((p) => p.attendance_type === 'virtual'));

const interactiveFeatures = computed(() => {
    let count = 0;
    if (props.event.chat_enabled) count++;
    if (activePoll.value?.active) count++;
    if (questions.value.length > 0) count++;
    return count;
});

const sortedQuestions = computed(() => [...questions.value].sort((a, b) => b.upvotes - a.upvotes));

// Methods
const toggleFullscreen = () => {
    const element = document.querySelector('.hybrid-event-interface');
    if (element) {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            element.requestFullscreen();
        }
    }
};

const toggleChat = () => {
    chatExpanded.value = !chatExpanded.value;
};

const sendMessage = () => {
    if (!newMessage.value.trim()) return;

    emit('chatMessage', newMessage.value);

    // Add to local messages (would be handled by real-time updates in production)
    chatMessages.value.push({
        id: Date.now(),
        content: newMessage.value,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        user: { name: 'You', attendance_type: 'virtual' }, // Would be current user
    });

    newMessage.value = '';
    showToast({
        type: 'success',
        message: 'Message sent',
    });
};

const submitQuestion = () => {
    if (!newQuestion.value.trim()) return;

    emit('questionSubmitted', newQuestion.value);

    // Add to local questions
    questions.value.push({
        id: Date.now(),
        content: newQuestion.value,
        upvotes: 0,
        user: { name: 'You', attendance_type: 'virtual' },
    });

    newQuestion.value = '';
    showToast({
        type: 'success',
        message: 'Question submitted',
    });
};

const upvoteQuestion = (questionId: number) => {
    const question = questions.value.find((q) => q.id === questionId);
    if (question) {
        question.upvotes++;
    }
};

const answerQuestion = (questionId: number) => {
    // This would open a modal or inline editor for the answer
    showToast({
        type: 'info',
        message: 'Answer question feature coming soon',
    });
};

const toggleQAModeration = () => {
    qaModerationEnabled.value = !qaModerationEnabled.value;
    showToast({
        type: 'success',
        message: `Q&A moderation ${qaModerationEnabled.value ? 'enabled' : 'disabled'}`,
    });
};

const votePoll = (optionId: number) => {
    if (hasVoted.value) return;

    emit('pollVoted', optionId);

    if (activePoll.value) {
        const option = activePoll.value.options.find((o) => o.id === optionId);
        if (option) {
            option.votes++;
            activePoll.value.total_votes++;
            hasVoted.value = true;

            showToast({
                type: 'success',
                message: 'Vote recorded',
            });
        }
    }
};

const addPollOption = () => {
    newPoll.value.options.push('');
};

const createPoll = () => {
    if (!newPoll.value.question.trim() || newPoll.value.options.filter((o) => o.trim()).length < 2) {
        showToast({
            type: 'error',
            message: 'Please provide a question and at least 2 options',
        });
        return;
    }

    const poll = {
        question: newPoll.value.question,
        options: newPoll.value.options
            .filter((o) => o.trim())
            .map((text, index) => ({
                id: index + 1,
                text,
                votes: 0,
            })),
    };

    emit('pollCreated', poll);

    // Set as active poll
    activePoll.value = {
        id: Date.now(),
        ...poll,
        total_votes: 0,
        active: true,
    };

    showCreatePoll.value = false;
    newPoll.value = { question: '', options: ['', ''] };
    hasVoted.value = false;

    showToast({
        type: 'success',
        message: 'Poll created and activated',
    });
};

const joinNetworkingRoom = () => {
    showToast({
        type: 'info',
        message: 'Networking room feature coming soon',
    });
};

const requestBreakoutRoom = () => {
    showToast({
        type: 'info',
        message: 'Breakout room request sent',
    });
};

const handleVirtualSettingsUpdate = (settings: any) => {
    emit('settingsUpdated', settings);
};

const handleRecordingToggle = (recording: boolean) => {
    emit('meetingAction', 'toggleRecording', { recording });
};

const handleParticipantAction = (action: string, participantId: number, data?: any) => {
    emit('participantAction', action, participantId, data);
};

const handleMeetingAction = (action: string, data?: any) => {
    emit('meetingAction', action, data);
};
</script>

<style scoped>
.hybrid-event-interface > * + * {
    margin-top: 1.5rem;
}

/* Fullscreen styles */
.hybrid-event-interface:fullscreen {
    background-color: #f3f4f6;
    padding: 1.5rem;
}

/* Chat message animations */
.chat-message {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Poll option hover effects */
.poll-option {
    transition: all 0.2s ease-in-out;
}

.poll-option:hover:not(:disabled) {
    transform: translateX(2px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .hybrid-event-interface > * + * {
        margin-top: 1rem;
    }
}

@media (max-width: 768px) {
    .hybrid-event-interface .grid {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
}
</style>















