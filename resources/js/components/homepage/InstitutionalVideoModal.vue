<template>
    <Teleport to="body">
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 overflow-y-auto"
            @click="closeModal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="video-modal-title"
        >
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity"></div>

            <!-- Modal Content -->
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-lg bg-white shadow-xl" @click.stop>
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-200 p-4">
                        <div class="flex items-center">
                            <img
                                v-if="testimonial?.institution.logo"
                                :src="testimonial.institution.logo"
                                :alt="`${testimonial.institution.name} logo`"
                                class="mr-3 h-8 w-8 rounded object-contain"
                            />
                            <div>
                                <h3 id="video-modal-title" class="text-lg font-semibold text-gray-900">
                                    {{ testimonial?.administrator.name }}
                                </h3>
                                <p class="text-sm text-gray-600">{{ testimonial?.administrator.title }} at {{ testimonial?.institution.name }}</p>
                            </div>
                        </div>
                        <button
                            @click="closeModal"
                            class="text-gray-400 transition-colors duration-200 hover:text-gray-600"
                            aria-label="Close video modal"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Video Container -->
                    <div class="relative bg-black">
                        <div class="aspect-video">
                            <video
                                ref="videoElement"
                                :src="videoUrl"
                                class="h-full w-full object-contain"
                                controls
                                autoplay
                                @loadstart="onVideoLoadStart"
                                @canplay="onVideoCanPlay"
                                @error="onVideoError"
                            >
                                Your browser does not support the video tag.
                            </video>

                            <!-- Loading Spinner -->
                            <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50">
                                <div class="h-12 w-12 animate-spin rounded-full border-b-2 border-white"></div>
                            </div>

                            <!-- Error State -->
                            <div v-if="hasError" class="absolute inset-0 flex items-center justify-center bg-black p-8 text-center text-white">
                                <div>
                                    <svg class="mx-auto mb-4 h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    <h4 class="mb-2 text-lg font-semibold">Video Unavailable</h4>
                                    <p class="text-gray-300">Sorry, this video testimonial is currently unavailable.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer with Testimonial Info -->
                    <div v-if="testimonial && !hasError" class="bg-gray-50 p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <blockquote class="mb-3 italic text-gray-700">"{{ testimonial.quote }}"</blockquote>

                                <!-- Key Results -->
                                <div v-if="testimonial.results.length > 0" class="flex flex-wrap gap-4">
                                    <div v-for="result in testimonial.results.slice(0, 3)" :key="result.metric" class="flex items-center text-sm">
                                        <div class="mr-2 h-2 w-2 rounded-full bg-green-500"></div>
                                        <span class="font-medium text-green-600">+{{ result.improvementPercentage }}%</span>
                                        <span class="ml-1 capitalize text-gray-600">{{ formatMetricLabel(result.metric) }}</span>
                                    </div>
                                </div>
                            </div>

                            <button
                                @click="$emit('request-demo', testimonial)"
                                class="ml-4 inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                Request Demo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import type { InstitutionTestimonial } from '@/types/homepage';
import { nextTick, ref, watch } from 'vue';

interface Props {
    isOpen: boolean;
    videoUrl?: string;
    testimonial?: InstitutionTestimonial;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
    'request-demo': [testimonial: InstitutionTestimonial];
}>();

const videoElement = ref<HTMLVideoElement>();
const isLoading = ref(false);
const hasError = ref(false);

const closeModal = () => {
    if (videoElement.value) {
        videoElement.value.pause();
        videoElement.value.currentTime = 0;
    }
    emit('close');
};

const onVideoLoadStart = () => {
    isLoading.value = true;
    hasError.value = false;
};

const onVideoCanPlay = () => {
    isLoading.value = false;
    hasError.value = false;
};

const onVideoError = () => {
    isLoading.value = false;
    hasError.value = true;
};

const formatMetricLabel = (metric: string): string => {
    return metric.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};

// Handle escape key
watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            const handleEscape = (e: KeyboardEvent) => {
                if (e.key === 'Escape') {
                    closeModal();
                }
            };
            document.addEventListener('keydown', handleEscape);

            // Focus management
            nextTick(() => {
                const modal = document.querySelector('[role="dialog"]') as HTMLElement;
                if (modal) {
                    modal.focus();
                }
            });

            return () => {
                document.removeEventListener('keydown', handleEscape);
            };
        }
    },
);
</script>

<style scoped>
/* Ensure video controls are visible */
video::-webkit-media-controls {
    display: flex !important;
}

video::-webkit-media-controls-panel {
    display: flex !important;
}
</style>

