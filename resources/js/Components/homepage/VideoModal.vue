<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="video-modal fixed inset-0 z-50 flex items-center justify-center p-4"
            @click="handleBackdropClick"
            @keydown.esc="closeModal"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="titleId"
        >
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black bg-opacity-75 transition-opacity"></div>

            <!-- Modal Content -->
            <div class="relative max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-lg bg-white shadow-xl" @click.stop ref="modalContent">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-gray-200 p-4">
                    <h2 :id="titleId" class="truncate text-lg font-semibold text-gray-900">
                        {{ title }}
                    </h2>

                    <button
                        @click="closeModal"
                        class="flex-shrink-0 rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                        aria-label="Close video modal"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Video Container -->
                <div class="relative bg-black" style="aspect-ratio: 16/9">
                    <!-- Loading State -->
                    <div v-if="loading" class="absolute inset-0 flex items-center justify-center">
                        <div class="text-white">
                            <svg class="h-8 w-8 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                ></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="error" class="absolute inset-0 flex items-center justify-center p-8 text-center text-white">
                        <div>
                            <svg class="mx-auto mb-4 h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                            <p class="mb-2 text-lg font-medium">Video Unavailable</p>
                            <p class="text-sm text-gray-300">{{ error }}</p>
                            <button @click="retryLoad" class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700">
                                Try Again
                            </button>
                        </div>
                    </div>

                    <!-- Video Element -->
                    <video
                        v-else
                        ref="videoElement"
                        class="h-full w-full"
                        :src="videoUrl"
                        controls
                        preload="metadata"
                        @loadstart="handleLoadStart"
                        @loadeddata="handleLoadedData"
                        @error="handleVideoError"
                        @ended="handleVideoEnded"
                        :aria-label="`Video testimonial: ${title}`"
                    >
                        <track v-if="subtitlesUrl" kind="subtitles" :src="subtitlesUrl" srclang="en" label="English" default />

                        <!-- Fallback for browsers that don't support video -->
                        <p class="p-8 text-center text-white">
                            Your browser doesn't support video playback.
                            <a :href="videoUrl" class="text-blue-400 underline hover:text-blue-300" target="_blank" rel="noopener noreferrer">
                                Download the video
                            </a>
                        </p>
                    </video>

                    <!-- Play Button Overlay (for custom styling) -->
                    <div
                        v-if="showPlayButton && !loading && !error"
                        class="absolute inset-0 flex cursor-pointer items-center justify-center bg-black bg-opacity-30"
                        @click="playVideo"
                    >
                        <div class="transform rounded-full bg-white bg-opacity-90 p-4 transition-all hover:scale-110 hover:bg-opacity-100">
                            <svg class="h-12 w-12 text-gray-800" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Footer (optional) -->
                <div v-if="showFooter" class="border-t border-gray-200 bg-gray-50 p-4">
                    <div class="flex items-center justify-between text-sm text-gray-600">
                        <div class="flex items-center space-x-4">
                            <span>Duration: {{ formatDuration(videoDuration) }}</span>
                            <span v-if="videoSize">Size: {{ formatFileSize(videoSize) }}</span>
                        </div>

                        <div class="flex items-center space-x-2">
                            <button
                                @click="toggleFullscreen"
                                class="rounded p-2 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-600"
                                aria-label="Toggle fullscreen"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"
                                    ></path>
                                </svg>
                            </button>

                            <a
                                :href="videoUrl"
                                download
                                class="rounded p-2 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-600"
                                aria-label="Download video"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                    ></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

interface Props {
    videoUrl: string;
    title: string;
    subtitlesUrl?: string;
    showFooter?: boolean;
    autoPlay?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showFooter: true,
    autoPlay: false,
});

// Emits
const emit = defineEmits<{
    close: [];
    videoEnded: [];
    videoStarted: [];
}>();

// Reactive state
const show = ref(true);
const loading = ref(true);
const error = ref<string | null>(null);
const showPlayButton = ref(false);
const videoDuration = ref(0);
const videoSize = ref<number | null>(null);
const modalContent = ref<HTMLElement>();
const videoElement = ref<HTMLVideoElement>();

// Computed properties
const titleId = computed(() => `video-modal-title-${Math.random().toString(36).substr(2, 9)}`);

// Methods
const closeModal = (): void => {
    show.value = false;
    emit('close');
};

const handleBackdropClick = (event: MouseEvent): void => {
    if (event.target === event.currentTarget) {
        closeModal();
    }
};

const handleLoadStart = (): void => {
    loading.value = true;
    error.value = null;
};

const handleLoadedData = (): void => {
    loading.value = false;
    showPlayButton.value = !props.autoPlay;

    if (videoElement.value) {
        videoDuration.value = videoElement.value.duration;

        if (props.autoPlay) {
            playVideo();
        }
    }
};

const handleVideoError = (event: Event): void => {
    loading.value = false;
    const video = event.target as HTMLVideoElement;
    const errorCode = video.error?.code;

    switch (errorCode) {
        case MediaError.MEDIA_ERR_ABORTED:
            error.value = 'Video playback was aborted';
            break;
        case MediaError.MEDIA_ERR_NETWORK:
            error.value = 'Network error occurred while loading video';
            break;
        case MediaError.MEDIA_ERR_DECODE:
            error.value = 'Video format is not supported';
            break;
        case MediaError.MEDIA_ERR_SRC_NOT_SUPPORTED:
            error.value = 'Video source is not supported';
            break;
        default:
            error.value = 'An unknown error occurred while loading the video';
    }
};

const handleVideoEnded = (): void => {
    showPlayButton.value = true;
    emit('videoEnded');
};

const playVideo = (): void => {
    if (videoElement.value && !loading.value && !error.value) {
        videoElement.value.play();
        showPlayButton.value = false;
        emit('videoStarted');
    }
};

const retryLoad = (): void => {
    error.value = null;
    loading.value = true;

    if (videoElement.value) {
        videoElement.value.load();
    }
};

const toggleFullscreen = (): void => {
    if (videoElement.value) {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            videoElement.value.requestFullscreen();
        }
    }
};

const formatDuration = (seconds: number): string => {
    if (!seconds || isNaN(seconds)) return '0:00';

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = Math.floor(seconds % 60);

    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
};

const formatFileSize = (bytes: number): string => {
    if (!bytes) return 'Unknown';

    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));

    return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${sizes[i]}`;
};

// Keyboard event handler
const handleKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Escape') {
        closeModal();
    } else if (event.key === ' ' || event.key === 'Enter') {
        if (showPlayButton.value) {
            event.preventDefault();
            playVideo();
        }
    }
};

// Focus management
const focusModal = async (): Promise<void> => {
    await nextTick();
    if (modalContent.value) {
        modalContent.value.focus();
    }
};

const trapFocus = (event: KeyboardEvent): void => {
    if (event.key !== 'Tab') return;

    const focusableElements = modalContent.value?.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');

    if (!focusableElements || focusableElements.length === 0) return;

    const firstElement = focusableElements[0] as HTMLElement;
    const lastElement = focusableElements[focusableElements.length - 1] as HTMLElement;

    if (event.shiftKey) {
        if (document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
        }
    } else {
        if (document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
        }
    }
};

// Watchers
watch(
    () => props.videoUrl,
    () => {
        if (videoElement.value) {
            loading.value = true;
            error.value = null;
            showPlayButton.value = false;
            videoElement.value.load();
        }
    },
);

// Lifecycle hooks
onMounted(() => {
    // Add event listeners
    document.addEventListener('keydown', handleKeydown);
    document.addEventListener('keydown', trapFocus);

    // Prevent body scroll
    document.body.style.overflow = 'hidden';

    // Focus the modal
    focusModal();

    // Fetch video metadata if possible
    if (props.videoUrl) {
        fetch(props.videoUrl, { method: 'HEAD' })
            .then((response) => {
                const contentLength = response.headers.get('content-length');
                if (contentLength) {
                    videoSize.value = parseInt(contentLength, 10);
                }
            })
            .catch(() => {
                // Ignore errors for metadata fetching
            });
    }
});

onUnmounted(() => {
    // Remove event listeners
    document.removeEventListener('keydown', handleKeydown);
    document.removeEventListener('keydown', trapFocus);

    // Restore body scroll
    document.body.style.overflow = '';

    // Pause video if playing
    if (videoElement.value && !videoElement.value.paused) {
        videoElement.value.pause();
    }
});
</script>

<style scoped>
.video-modal {
    backdrop-filter: blur(4px);
}

/* Smooth transitions */
.video-modal * {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

/* Video controls styling */
.video-modal video::-webkit-media-controls-panel {
    background-color: rgba(0, 0, 0, 0.8);
}

.video-modal video::-webkit-media-controls-play-button,
.video-modal video::-webkit-media-controls-volume-slider,
.video-modal video::-webkit-media-controls-timeline {
    filter: invert(1);
}

/* Loading animation */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Focus styles for accessibility */
.video-modal button:focus,
.video-modal a:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

.video-modal video:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .video-modal .max-w-4xl {
        max-width: calc(100vw - 2rem);
    }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .video-modal * {
        transition: none;
    }

    .animate-spin {
        animation: none;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .video-modal .bg-black {
        background-color: #000000;
    }

    .video-modal .text-white {
        color: #ffffff;
    }
}
</style>
