<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <div class="container mx-auto px-4 py-8">
            <div class="mx-auto max-w-4xl">
                <div class="mb-8 text-center">
                    <h1 class="mb-4 text-3xl font-bold text-gray-900 dark:text-white">Enhanced Background Media Demo</h1>
                    <p class="text-lg text-gray-600 dark:text-gray-300">
                        Demonstrating responsive images, video backgrounds, and mobile optimization
                    </p>
                </div>

                <!-- Media Type Selector -->
                <div class="mb-8">
                    <div class="flex flex-wrap justify-center gap-4">
                        <button
                            v-for="type in mediaTypes"
                            :key="type.id"
                            @click="selectedMediaType = type.id"
                            :class="[
                                'rounded-lg px-6 py-3 font-medium transition-colors duration-200',
                                selectedMediaType === type.id
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
                            ]"
                        >
                            {{ type.name }}
                        </button>
                    </div>
                </div>

                <!-- Hero Component Demo -->
                <div class="mb-8 overflow-hidden rounded-xl bg-white shadow-lg dark:bg-gray-800">
                    <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ currentMediaType.name }} Background</h2>
                        <p class="mt-1 text-gray-600 dark:text-gray-300">
                            {{ currentMediaType.description }}
                        </p>
                    </div>

                    <div class="relative h-96">
                        <HeroBase :config="currentHeroConfig" />
                    </div>
                </div>

                <!-- Configuration Display -->
                <div class="overflow-hidden rounded-xl bg-white shadow-lg dark:bg-gray-800">
                    <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Configuration</h3>
                    </div>

                    <div class="p-6">
                        <pre class="overflow-x-auto rounded-lg bg-gray-100 p-4 text-sm dark:bg-gray-900">
              <code class="text-gray-800 dark:text-gray-200">{{ formattedConfig }}</code>
            </pre>
                    </div>
                </div>

                <!-- Features List -->
                <div class="mt-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div v-for="feature in features" :key="feature.title" class="rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
                        <div class="mb-3 flex items-center">
                            <div class="mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ feature.title }}</h4>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import HeroBase from '@/Components/ComponentLibrary/Hero/HeroBase.vue';
import { heroMediaSamples } from '@/Data/heroSampleData';
import type { HeroComponentConfig } from '@/types/Components';
import { computed, ref } from 'vue';

// Media type selection
const selectedMediaType = ref('video');

const mediaTypes = [
    {
        id: 'video',
        name: 'Video Background',
        description: 'Responsive video with mobile optimization and bandwidth considerations',
    },
    {
        id: 'image',
        name: 'Responsive Image',
        description: 'WebP/AVIF support with responsive breakpoints and CDN integration',
    },
    {
        id: 'gradient',
        name: 'Gradient Background',
        description: 'CSS gradients with fallback support and mobile optimization',
    },
];

const currentMediaType = computed(() => mediaTypes.find((type) => type.id === selectedMediaType.value) || mediaTypes[0]);

// Hero configurations for each media type
const heroConfigs = {
    video: {
        headline: 'Video Background Hero',
        subheading: 'Adaptive video with mobile optimization',
        description:
            'This hero component features responsive video backgrounds with automatic quality adjustment based on connection speed and device capabilities.',
        audienceType: 'individual' as const,
        backgroundMedia: heroMediaSamples.videoBackground,
        ctaButtons: [
            {
                id: 'video-cta',
                text: 'Experience Video',
                url: '#video-demo',
                style: 'primary' as const,
                size: 'lg' as const,
            },
        ],
        layout: 'centered' as const,
        textAlignment: 'center' as const,
        contentPosition: 'center' as const,
        headingLevel: 1 as const,
        animations: {
            enabled: true,
            entrance: 'fade' as const,
            duration: 800,
        },
        lazyLoad: true,
    },

    image: {
        headline: 'Responsive Image Hero',
        subheading: 'WebP/AVIF with CDN optimization',
        description: 'Modern image formats with responsive breakpoints ensure optimal loading performance across all devices and connection speeds.',
        audienceType: 'institution' as const,
        backgroundMedia: heroMediaSamples.imageBackground,
        ctaButtons: [
            {
                id: 'image-cta',
                text: 'View Gallery',
                url: '#image-demo',
                style: 'primary' as const,
                size: 'lg' as const,
            },
        ],
        layout: 'centered' as const,
        textAlignment: 'center' as const,
        contentPosition: 'center' as const,
        headingLevel: 1 as const,
        animations: {
            enabled: true,
            entrance: 'slide' as const,
            duration: 800,
        },
        lazyLoad: true,
    },

    gradient: {
        headline: 'Gradient Background Hero',
        subheading: 'CSS gradients with fallback support',
        description: 'Beautiful gradient backgrounds with comprehensive fallback support and mobile-optimized performance.',
        audienceType: 'employer' as const,
        backgroundMedia: {
            type: 'gradient' as const,
            gradient: {
                type: 'linear' as const,
                direction: '135deg',
                colors: [
                    { color: '#667eea', stop: 0 },
                    { color: '#764ba2', stop: 100 },
                ],
            },
            overlay: {
                color: 'rgba(0, 0, 0, 0.3)',
                opacity: 0.3,
            },
            lazyLoad: true,
            mobileOptimized: true,
            fallback: {
                type: 'gradient' as const,
                gradient: {
                    type: 'linear' as const,
                    direction: '135deg',
                    colors: [
                        { color: '#3b82f6', stop: 0 },
                        { color: '#1d4ed8', stop: 100 },
                    ],
                },
            },
        },
        ctaButtons: [
            {
                id: 'gradient-cta',
                text: 'Explore Gradients',
                url: '#gradient-demo',
                style: 'primary' as const,
                size: 'lg' as const,
            },
        ],
        layout: 'centered' as const,
        textAlignment: 'center' as const,
        contentPosition: 'center' as const,
        headingLevel: 1 as const,
        animations: {
            enabled: true,
            entrance: 'zoom' as const,
            duration: 800,
        },
        lazyLoad: true,
    },
};

const currentHeroConfig = computed((): HeroComponentConfig => heroConfigs[selectedMediaType.value as keyof typeof heroConfigs]);

const formattedConfig = computed(() => JSON.stringify(currentHeroConfig.value.backgroundMedia, null, 2));

// Features list
const features = [
    {
        title: 'Responsive Images',
        description: 'Automatic generation of responsive image sources with WebP and AVIF support for optimal performance.',
    },
    {
        title: 'Video Optimization',
        description: 'Adaptive video quality based on connection speed with mobile-specific handling and bandwidth considerations.',
    },
    {
        title: 'CDN Integration',
        description: 'Seamless integration with CDN Services for global content delivery and automatic image optimization.',
    },
    {
        title: 'Mobile First',
        description: 'Mobile-optimized media handling with touch-friendly interactions and reduced data usage.',
    },
    {
        title: 'Accessibility',
        description: 'Built-in accessibility features including proper alt text, reduced motion support, and screen reader compatibility.',
    },
    {
        title: 'Fallback Support',
        description: 'Comprehensive fallback system ensures content displays properly even when primary media fails to load.',
    },
];
</script>

<style scoped>
/* Ensure proper code formatting */
pre code {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    line-height: 1.5;
}

/* Smooth transitions for media type switching */
.transition-colors {
    transition-property: color, background-color, border-color;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}
</style>

















