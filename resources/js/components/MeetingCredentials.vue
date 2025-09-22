<template>
    <div class="meeting-credentials">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <Icon :name="getPlatformIcon(credentials.platform)" class="h-6 w-6 text-blue-600" />
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Meeting Details</h3>
                    <p class="text-sm text-gray-600">{{ getPlatformName(credentials.platform) }}</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <button
                    @click="copyAllCredentials"
                    class="flex items-center space-x-2 rounded-lg px-3 py-2 text-sm text-blue-600 transition-colors hover:bg-blue-50 hover:text-blue-700"
                >
                    <Icon name="copy" class="h-4 w-4" />
                    <span>Copy All</span>
                </button>

                <button
                    @click="shareCredentials"
                    class="flex items-center space-x-2 rounded-lg px-3 py-2 text-sm text-green-600 transition-colors hover:bg-green-50 hover:text-green-700"
                >
                    <Icon name="share" class="h-4 w-4" />
                    <span>Share</span>
                </button>
            </div>
        </div>

        <!-- Meeting URL -->
        <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
            <div class="mb-2 flex items-center justify-between">
                <label class="text-sm font-medium text-gray-700">Meeting Link</label>
                <button
                    @click="copyToClipboard(credentials.url, 'Meeting link copied!')"
                    class="flex items-center space-x-1 text-xs text-blue-600 hover:text-blue-700"
                >
                    <Icon name="copy" class="h-3 w-3" />
                    <span>Copy</span>
                </button>
            </div>
            <div class="flex items-center space-x-3">
                <code class="flex-1 break-all rounded bg-gray-50 px-3 py-2 font-mono text-sm text-gray-800">
                    {{ credentials.url }}
                </code>
                <a
                    :href="credentials.url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex-shrink-0 rounded p-2 text-blue-600 transition-colors hover:bg-blue-50 hover:text-blue-700"
                    title="Open in new tab"
                >
                    <Icon name="external-link" class="h-4 w-4" />
                </a>
            </div>
        </div>

        <!-- Meeting ID/Room ID -->
        <div v-if="credentials.room_id || credentials.meeting_id" class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
            <div class="mb-2 flex items-center justify-between">
                <label class="text-sm font-medium text-gray-700">
                    {{ credentials.platform === 'jitsi' ? 'Room ID' : 'Meeting ID' }}
                </label>
                <button
                    @click="copyToClipboard(credentials.room_id || credentials.meeting_id, 'Meeting ID copied!')"
                    class="flex items-center space-x-1 text-xs text-blue-600 hover:text-blue-700"
                >
                    <Icon name="copy" class="h-3 w-3" />
                    <span>Copy</span>
                </button>
            </div>
            <code class="block rounded bg-gray-50 px-3 py-2 font-mono text-sm text-gray-800">
                {{ credentials.room_id || credentials.meeting_id }}
            </code>
        </div>

        <!-- Meeting Password -->
        <div v-if="credentials.password" class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
            <div class="mb-2 flex items-center justify-between">
                <label class="text-sm font-medium text-gray-700">Password</label>
                <div class="flex items-center space-x-2">
                    <button @click="togglePasswordVisibility" class="flex items-center space-x-1 text-xs text-gray-600 hover:text-gray-700">
                        <Icon :name="showPassword ? 'eye-off' : 'eye'" class="h-3 w-3" />
                        <span>{{ showPassword ? 'Hide' : 'Show' }}</span>
                    </button>
                    <button
                        @click="copyToClipboard(credentials.password, 'Password copied!')"
                        class="flex items-center space-x-1 text-xs text-blue-600 hover:text-blue-700"
                    >
                        <Icon name="copy" class="h-3 w-3" />
                        <span>Copy</span>
                    </button>
                </div>
            </div>
            <code class="block rounded bg-gray-50 px-3 py-2 font-mono text-sm text-gray-800">
                {{ showPassword ? credentials.password : '•'.repeat(credentials.password.length) }}
            </code>
        </div>

        <!-- Dial-in Numbers (for platforms that support it) -->
        <div v-if="credentials.dial_in" class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
            <div class="mb-2 flex items-center justify-between">
                <label class="text-sm font-medium text-gray-700">Dial-in Numbers</label>
                <button @click="copyDialInNumbers" class="flex items-center space-x-1 text-xs text-blue-600 hover:text-blue-700">
                    <Icon name="copy" class="h-3 w-3" />
                    <span>Copy</span>
                </button>
            </div>
            <div class="space-y-2">
                <div v-for="(number, country) in credentials.dial_in" :key="country" class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">{{ country }}:</span>
                    <code class="font-mono text-sm text-gray-800">{{ number }}</code>
                </div>
            </div>
        </div>

        <!-- Meeting Features -->
        <div v-if="credentials.features" class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
            <label class="mb-3 block text-sm font-medium text-gray-700">Meeting Features</label>
            <div class="grid grid-cols-2 gap-3">
                <div v-for="(enabled, feature) in credentials.features" :key="feature" class="flex items-center space-x-2">
                    <Icon :name="enabled ? 'check-circle' : 'x-circle'" :class="['h-4 w-4', enabled ? 'text-green-500' : 'text-gray-400']" />
                    <span class="text-sm capitalize text-gray-700">
                        {{ feature.replace('_', ' ') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div v-if="credentials.instructions" class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
            <div class="flex items-start space-x-3">
                <Icon name="info" class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-500" />
                <div>
                    <h4 class="mb-2 font-medium text-blue-900">How to Join</h4>
                    <div class="whitespace-pre-line text-sm text-blue-800">{{ credentials.instructions }}</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-3">
            <a
                :href="credentials.url"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center space-x-2 rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700"
            >
                <Icon name="video" class="h-4 w-4" />
                <span>Join Meeting</span>
            </a>

            <button
                @click="addToCalendar"
                class="flex items-center space-x-2 rounded-lg bg-gray-100 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-200"
            >
                <Icon name="calendar" class="h-4 w-4" />
                <span>Add to Calendar</span>
            </button>

            <button
                v-if="canTest"
                @click="testConnection"
                class="flex items-center space-x-2 rounded-lg bg-green-100 px-4 py-2 text-green-700 transition-colors hover:bg-green-200"
            >
                <Icon name="wifi" class="h-4 w-4" />
                <span>Test Connection</span>
            </button>
        </div>

        <!-- QR Code Modal -->
        <div v-if="showQRCode" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click.self="showQRCode = false">
            <div class="w-full max-w-sm rounded-lg bg-white p-6">
                <div class="text-center">
                    <h3 class="mb-4 text-lg font-semibold">Scan to Join</h3>
                    <div class="mb-4 rounded-lg bg-gray-100 p-4">
                        <!-- QR Code would be generated here -->
                        <div class="mx-auto flex h-48 w-48 items-center justify-center rounded bg-gray-200">
                            <Icon name="qr-code" class="h-16 w-16 text-gray-400" />
                        </div>
                    </div>
                    <p class="mb-4 text-sm text-gray-600">Scan this QR code with your mobile device to join the meeting</p>
                    <button @click="showQRCode = false" class="rounded-lg bg-gray-100 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-200">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { useToast } from '@/composables/useToast';
import { ref } from 'vue';

interface MeetingCredentials {
    platform: string;
    url: string;
    password?: string;
    room_id?: string;
    meeting_id?: string;
    instructions?: string;
    dial_in?: Record<string, string>;
    features?: Record<string, boolean>;
    embed_allowed?: boolean;
}

interface Props {
    credentials: MeetingCredentials;
    eventTitle?: string;
    eventDate?: string;
    canTest?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canTest: true,
});

const emit = defineEmits<{
    testConnection: [];
    addToCalendar: [credentials: MeetingCredentials];
}>();

const { showToast } = useToast();

// Reactive state
const showPassword = ref(false);
const showQRCode = ref(false);

// Methods
const getPlatformIcon = (platform: string): string => {
    const icons = {
        jitsi: 'video',
        zoom: 'video',
        teams: 'users',
        google_meet: 'video',
        webex: 'video',
        other: 'external-link',
    };
    return icons[platform] || 'video';
};

const getPlatformName = (platform: string): string => {
    const names = {
        jitsi: 'Jitsi Meet',
        zoom: 'Zoom',
        teams: 'Microsoft Teams',
        google_meet: 'Google Meet',
        webex: 'WebEx',
        other: 'External Platform',
    };
    return names[platform] || 'Virtual Meeting';
};

const copyToClipboard = async (text: string, successMessage: string) => {
    try {
        await navigator.clipboard.writeText(text);
        showToast({
            type: 'success',
            message: successMessage,
        });
    } catch (err) {
        showToast({
            type: 'error',
            message: 'Failed to copy to clipboard',
        });
    }
};

const copyAllCredentials = async () => {
    let text = `Meeting Details - ${getPlatformName(props.credentials.platform)}\n\n`;
    text += `Meeting Link: ${props.credentials.url}\n`;

    if (props.credentials.room_id || props.credentials.meeting_id) {
        text += `Meeting ID: ${props.credentials.room_id || props.credentials.meeting_id}\n`;
    }

    if (props.credentials.password) {
        text += `Password: ${props.credentials.password}\n`;
    }

    if (props.credentials.dial_in) {
        text += '\nDial-in Numbers:\n';
        Object.entries(props.credentials.dial_in).forEach(([country, number]) => {
            text += `${country}: ${number}\n`;
        });
    }

    if (props.credentials.instructions) {
        text += `\nInstructions:\n${props.credentials.instructions}`;
    }

    await copyToClipboard(text, 'All meeting details copied!');
};

const copyDialInNumbers = async () => {
    if (!props.credentials.dial_in) return;

    let text = 'Dial-in Numbers:\n';
    Object.entries(props.credentials.dial_in).forEach(([country, number]) => {
        text += `${country}: ${number}\n`;
    });

    await copyToClipboard(text, 'Dial-in numbers copied!');
};

const shareCredentials = async () => {
    if (navigator.share) {
        try {
            await navigator.share({
                title: `${props.eventTitle || 'Meeting'} - ${getPlatformName(props.credentials.platform)}`,
                text: `Join the meeting: ${props.credentials.url}`,
                url: props.credentials.url,
            });
        } catch (err) {
            // Fallback to copy
            await copyAllCredentials();
        }
    } else {
        await copyAllCredentials();
    }
};

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const addToCalendar = () => {
    emit('addToCalendar', props.credentials);
};

const testConnection = () => {
    emit('testConnection');
    showToast({
        type: 'info',
        message: 'Testing connection...',
    });
};
</script>

<style scoped>
.meeting-credentials {
    @apply space-y-4;
}

/* Credential cards */
.credential-card {
    @apply rounded-lg border border-gray-200 bg-white p-4;
    transition: all 0.2s ease-in-out;
}

.credential-card:hover {
    @apply border-gray-300 shadow-sm;
}

/* Code blocks */
code {
    font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', monospace;
}

/* Feature grid */
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .meeting-credentials {
        @apply space-y-3;
    }

    .credential-card {
        @apply p-3;
    }

    .features-grid {
        grid-template-columns: 1fr;
    }
}
</style>
