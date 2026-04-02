<template>
    <div class="institutional-testimonial-card rounded-lg bg-white p-6 shadow-lg transition-shadow duration-300 hover:shadow-xl">
        <!-- Institution Header -->
        <div class="mb-4 flex items-center">
            <div class="flex-shrink-0">
                <img
                    :src="testimonial.institution.logo"
                    :alt="`${testimonial.institution.name} logo`"
                    class="h-16 w-16 rounded-lg border border-gray-200 object-contain"
                    loading="lazy"
                />
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-900">{{ testimonial.institution.name }}</h3>
                <p class="text-sm capitalize text-gray-600">{{ testimonial.institution.type }}</p>
                <p class="text-xs text-gray-500">{{ formatAlumniCount(testimonial.institution.alumniCount) }} Alumni</p>
            </div>
        </div>

        <!-- Testimonial Quote -->
        <blockquote class="mb-6 italic leading-relaxed text-gray-700">"{{ testimonial.quote }}"</blockquote>

        <!-- Administrator Profile -->
        <div class="mb-4 flex items-center">
            <img
                :src="testimonial.administrator.profileImage"
                :alt="`${testimonial.administrator.name} profile`"
                class="h-12 w-12 rounded-full border-2 border-gray-200 object-cover"
                loading="lazy"
            />
            <div class="ml-3">
                <p class="font-medium text-gray-900">{{ testimonial.administrator.name }}</p>
                <p class="text-sm text-gray-600">{{ testimonial.administrator.title }}</p>
                <p class="text-xs text-gray-500">{{ testimonial.administrator.experience }} years experience</p>
            </div>
        </div>

        <!-- Results Metrics -->
        <div class="mb-4 grid grid-cols-2 gap-4" v-if="testimonial.results.length > 0">
            <div v-for="result in testimonial.results.slice(0, 4)" :key="result.metric" class="rounded-lg bg-gray-50 p-3 text-center">
                <div class="text-2xl font-bold text-blue-600">+{{ result.improvementPercentage }}%</div>
                <div class="text-xs capitalize text-gray-600">
                    {{ formatMetricLabel(result.metric) }}
                </div>
            </div>
        </div>

        <!-- Video Testimonial Button -->
        <div class="flex items-center justify-between">
            <button
                v-if="testimonial.videoTestimonial"
                @click="$emit('play-video', testimonial.videoTestimonial)"
                class="flex items-center text-blue-600 transition-colors duration-200 hover:text-blue-700"
                :aria-label="`Play video testimonial from ${testimonial.administrator.name}`"
            >
                <svg class="mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M8 5v10l8-5-8-5z" />
                </svg>
                Watch Video
            </button>

            <!-- Institution Type Badge -->
            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium capitalize text-blue-800">
                {{ testimonial.institution.type }}
            </span>
        </div>

        <!-- Verification Badge -->
        <div v-if="testimonial.results.some((r) => r.verified)" class="mt-3 flex items-center text-xs text-green-600">
            <svg class="mr-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"
                />
            </svg>
            Verified Results
        </div>
    </div>
</template>

<script setup lang="ts">
import type { InstitutionTestimonial } from '@/Types/homepage';

interface Props {
    testimonial: InstitutionTestimonial;
}

defineProps<Props>();

defineEmits<{
    'play-video': [videoUrl: string];
}>();

const formatAlumniCount = (count: number): string => {
    if (count >= 1000000) {
        return `${(count / 1000000).toFixed(1)}M`;
    } else if (count >= 1000) {
        return `${(count / 1000).toFixed(1)}K`;
    }
    return count.toString();
};

const formatMetricLabel = (metric: string): string => {
    return metric.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};
</script>

<style scoped>
.institutional-testimonial-card {
    @apply border border-gray-200;
}

.institutional-testimonial-card:hover {
    @apply border-blue-200;
}
</style>














