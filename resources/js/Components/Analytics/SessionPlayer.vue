<template>
    <div class="session-player" role="region" aria-label="Session recording player">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center p-8">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading session data...</span>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4">
            <div class="flex items-center">
                <svg class="mr-2 h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span class="text-red-800">{{ error }}</span>
            </div>
        </div>

        <!-- Session Player -->
        <div v-else class="session-player-container">
            <!-- Header -->
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Session Playback</h2>
                    <p class="text-sm text-gray-600">
                        Duration: {{ formatDuration(duration) }} |
                        Events: {{ sessionData?.events.length || 0 }}
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        @click="reset"
                        class="rounded bg-gray-100 px-3 py-1 text-sm hover:bg-gray-200"
                        aria-label="Reset playback"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- Playback Controls -->
            <div class="mb-4 flex items-center space-x-4 rounded-lg bg-gray-50 p-4">
                <!-- Play/Pause -->
                <button
                    @click="togglePlayPause"
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    :aria-label="isPlaying ? 'Pause playback' : 'Start playback'"
                >
                    <svg v-if="isPlaying" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 4h4v12H6V4zM10 4h4v12h-4V4z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8 5.14v9.72a1 1 0 001.555.832l6-4.5a1 1 0 000-1.664l-6-4.5A1 1 0 008 5.14z" clip-rule="evenodd" />
                    </svg>
                </button>

                <!-- Time Display -->
                <div class="text-sm font-mono">
                    {{ formatTime(currentTime) }} / {{ formatDuration(duration) }}
                </div>

                <!-- Progress Bar -->
                <div class="relative flex-1">
                    <div class="h-2 rounded-full bg-gray-200">
                        <div
                            class="h-2 rounded-full bg-blue-600 transition-all duration-100"
                            :style="{ width: progress + '%' }"
                        ></div>
                    </div>
                    <input
                        v-model="progressInput"
                        type="range"
                        min="0"
                        max="100"
                        step="0.1"
                        class="absolute inset-0 h-2 w-full cursor-pointer opacity-0"
                        @input="handleProgressChange"
                        aria-label="Seek through session timeline"
                    />
                </div>

                <!-- Speed Control -->
                <div class="flex items-center space-x-2">
                    <label for="speed-control" class="text-sm">Speed:</label>
                    <select
                        id="speed-control"
                        v-model="speed"
                        @change="setSpeed(Number(($event.target as HTMLSelectElement).value))"
                        class="rounded border border-gray-300 px-2 py-1 text-sm"
                        aria-label="Playback speed"
                    >
                        <option :value="0.25">0.25x</option>
                        <option :value="0.5">0.5x</option>
                        <option :value="0.75">0.75x</option>
                        <option :value="1">1x</option>
                        <option :value="1.25">1.25x</option>
                        <option :value="1.5">1.5x</option>
                        <option :value="2">2x</option>
                    </select>
                </div>
            </div>

            <!-- Timeline with Event Markers -->
            <div class="mb-4">
                <div class="relative h-16 rounded-lg border border-gray-200 bg-white p-2">
                    <!-- Timeline background -->
                    <div class="absolute inset-0 rounded bg-gray-50"></div>

                    <!-- Event markers -->
                    <div
                        v-for="event in sessionData?.events || []"
                        :key="event.timestamp"
                        class="absolute top-1/2 h-2 w-1 -translate-y-1/2 transform"
                        :class="getEventMarkerClass(event)"
                        :style="{ left: getEventPosition(event) + '%' }"
                        :title="getEventTooltip(event)"
                        @click="seek(event.timestamp)"
                        role="button"
                        tabindex="0"
                        :aria-label="`Jump to ${getEventTypeLabel(event.type)} event at ${formatTime(event.timestamp)}`"
                        @keydown.enter="seek(event.timestamp)"
                        @keydown.space.prevent="seek(event.timestamp)"
                    ></div>

                    <!-- Current time indicator -->
                    <div
                        class="absolute top-0 bottom-0 w-0.5 bg-red-500"
                        :style="{ left: progress + '%' }"
                    ></div>

                    <!-- Annotation markers -->
                    <div
                        v-for="annotation in annotations"
                        :key="annotation.id"
                        class="absolute -top-1 h-3 w-3 rounded-full bg-yellow-400 border-2 border-white shadow"
                        :style="{ left: getAnnotationPosition(annotation) + '%' }"
                        :title="annotation.content"
                        @click="seek(annotation.timestamp)"
                        role="button"
                        tabindex="0"
                        :aria-label="`Annotation: ${annotation.content}`"
                    ></div>
                </div>

                <!-- Timeline labels -->
                <div class="mt-1 flex justify-between text-xs text-gray-500">
                    <span>0:00</span>
                    <span>{{ formatDuration(duration) }}</span>
                </div>
            </div>

            <!-- Event Details Panel -->
            <div v-if="currentEvent" class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="mb-2 font-medium text-gray-900">Current Event</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium">Type:</span> {{ getEventTypeLabel(currentEvent.type) }}
                    </div>
                    <div>
                        <span class="font-medium">Time:</span> {{ formatTime(currentEvent.timestamp) }}
                    </div>
                    <div v-if="currentEvent.element">
                        <span class="font-medium">Element:</span> {{ currentEvent.element }}
                    </div>
                    <div v-if="currentEvent.x !== undefined && currentEvent.y !== undefined">
                        <span class="font-medium">Position:</span> ({{ currentEvent.x }}, {{ currentEvent.y }})
                    </div>
                </div>
            </div>

            <!-- Annotations Panel -->
            <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="font-medium text-gray-900">Annotations</h3>
                    <button
                        @click="showAnnotationModal = true"
                        class="rounded bg-blue-600 px-3 py-1 text-sm text-white hover:bg-blue-700"
                        aria-label="Add new annotation"
                    >
                        Add Note
                    </button>
                </div>

                <div v-if="annotations.length === 0" class="text-center text-gray-500 py-4">
                    No annotations yet. Click "Add Note" to create one.
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="annotation in annotations"
                        :key="annotation.id"
                        class="flex items-center justify-between rounded border border-gray-200 p-2"
                    >
                        <div class="flex-1">
                            <div class="text-sm">{{ annotation.content }}</div>
                            <div class="text-xs text-gray-500">{{ formatTime(annotation.timestamp) }}</div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button
                                @click="seek(annotation.timestamp)"
                                class="text-blue-600 hover:text-blue-800"
                                :aria-label="`Jump to annotation at ${formatTime(annotation.timestamp)}`"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                            <button
                                @click="removeAnnotation(annotation.id)"
                                class="text-red-600 hover:text-red-800"
                                :aria-label="`Remove annotation: ${annotation.content}`"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Privacy Masking Info -->
            <div v-if="sessionData?.metadata.privacyMasked" class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                <div class="flex items-center">
                    <svg class="mr-2 h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-yellow-800">This session contains privacy masking for sensitive data.</span>
                </div>
            </div>
        </div>

        <!-- Annotation Modal -->
        <div v-if="showAnnotationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" role="dialog" aria-modal="true" aria-labelledby="annotation-modal-title">
            <div class="w-full max-w-md rounded-lg bg-white p-6">
                <h3 id="annotation-modal-title" class="mb-4 text-lg font-medium">Add Annotation</h3>
                <textarea
                    v-model="newAnnotationContent"
                    class="w-full rounded border border-gray-300 p-2 focus:border-blue-500 focus:outline-none"
                    rows="3"
                    placeholder="Enter your annotation..."
                    @keydown.enter.exact.prevent="saveAnnotation"
                ></textarea>
                <div class="mt-4 flex justify-end space-x-2">
                    <button
                        @click="cancelAnnotation"
                        class="rounded bg-gray-100 px-4 py-2 text-sm hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveAnnotation"
                        class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
                        :disabled="!newAnnotationContent.trim()"
                    >
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { useSessionPlayback } from '../../Composables/useSessionPlayback';
import type { SessionPlayerProps, SessionEvent, SessionAnnotation } from '../../Types/analytics';

// Props
const props = withDefaults(defineProps<SessionPlayerProps>(), {
    autoPlay: false,
    initialSpeed: 1,
    showAnnotations: true,
});

// Composables
const {
    state,
    progress,
    currentEvent,
    togglePlayPause,
    seek,
    seekByProgress,
    setSpeed,
    reset,
    addAnnotation,
    removeAnnotation,
    fetchSessionData,
} = useSessionPlayback(props.sessionId, {
    autoPlay: props.autoPlay,
    initialSpeed: props.initialSpeed,
    showAnnotations: props.showAnnotations,
});

// Reactive state
const progressInput = ref(0);
const speed = ref(props.initialSpeed);
const showAnnotationModal = ref(false);
const newAnnotationContent = ref('');

// Computed properties
const isLoading = computed(() => state.value.isLoading);
const error = computed(() => state.value.error);
const isPlaying = computed(() => state.value.isPlaying);
const currentTime = computed(() => state.value.currentTime);
const duration = computed(() => state.value.duration);
const sessionData = computed(() => state.value.sessionData);
const annotations = computed(() => state.value.annotations);

// Methods
const formatTime = (milliseconds: number): string => {
    const totalSeconds = Math.floor(milliseconds / 1000);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
};

const formatDuration = (milliseconds: number): string => {
    const totalSeconds = Math.floor(milliseconds / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
};

const handleProgressChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const newProgress = Number(target.value);
    seekByProgress(newProgress);
};

const getEventMarkerClass = (event: SessionEvent): string => {
    const baseClasses = 'cursor-pointer hover:h-3 focus:h-3 transition-all';

    switch (event.type) {
        case 'click':
            return `${baseClasses} bg-blue-500`;
        case 'scroll':
            return `${baseClasses} bg-green-500`;
        case 'form_submit':
            return `${baseClasses} bg-red-500`;
        case 'page_view':
            return `${baseClasses} bg-purple-500`;
        case 'input':
            return `${baseClasses} bg-yellow-500`;
        default:
            return `${baseClasses} bg-gray-500`;
    }
};

const getEventPosition = (event: SessionEvent): number => {
    return (event.timestamp / duration.value) * 100;
};

const getAnnotationPosition = (annotation: SessionAnnotation): number => {
    return (annotation.timestamp / duration.value) * 100;
};

const getEventTooltip = (event: SessionEvent): string => {
    const time = formatTime(event.timestamp);
    const type = getEventTypeLabel(event.type);
    let details = '';

    if (event.element) {
        details += ` on ${event.element}`;
    }
    if (event.x !== undefined && event.y !== undefined) {
        details += ` at (${event.x}, ${event.y})`;
    }

    return `${type} at ${time}${details}`;
};

const getEventTypeLabel = (type: SessionEvent['type']): string => {
    const labels: Record<SessionEvent['type'], string> = {
        click: 'Click',
        scroll: 'Scroll',
        form_submit: 'Form Submit',
        page_view: 'Page View',
        input: 'Input',
        focus: 'Focus',
        blur: 'Blur',
    };
    return labels[type] || type;
};

const saveAnnotation = () => {
    if (newAnnotationContent.value.trim()) {
        addAnnotation(newAnnotationContent.value.trim());
        newAnnotationContent.value = '';
        showAnnotationModal.value = false;
    }
};

const cancelAnnotation = () => {
    newAnnotationContent.value = '';
    showAnnotationModal.value = false;
};

// Watchers
watch(progress, (newProgress) => {
    progressInput.value = newProgress;
});

watch(() => props.sessionId, (newId) => {
    if (newId) {
        reset();
        fetchSessionData();
    }
});

// Lifecycle
onMounted(() => {
    progressInput.value = progress.value;
});
</script>

<style scoped>
.session-player {
    @apply w-full max-w-4xl mx-auto;
}

/* Custom focus styles for accessibility */
.session-player button:focus,
.session-player [role="button"]:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Timeline marker animations */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.timeline-marker {
    animation: pulse 2s infinite;
}

/* Responsive design */
@media (max-width: 768px) {
    .session-player-container {
        @apply px-2;
    }

    .playback-controls {
        @apply flex-col space-y-2 space-x-0;
    }
}
</style>