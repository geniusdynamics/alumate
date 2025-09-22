<template>
    <section class="platform-preview" ref="sectionRef">
        <div class="container mx-auto px-4">
            <!-- Section Header -->
            <div class="mb-12 text-center">
                <h2 class="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">
                    {{ title }}
                </h2>
                <p class="mx-auto max-w-3xl text-lg text-gray-600">
                    {{ subtitle }}
                </p>
            </div>

            <!-- Preview Mode Toggle -->
            <div class="mb-8 flex justify-center">
                <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1">
                    <button
                        @click="setPreviewMode('screenshots')"
                        :class="[
                            'rounded-md px-4 py-2 text-sm font-medium transition-all duration-200',
                            previewMode === 'screenshots' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900',
                        ]"
                    >
                        Screenshots
                    </button>
                    <button
                        @click="setPreviewMode('tour')"
                        :class="[
                            'rounded-md px-4 py-2 text-sm font-medium transition-all duration-200',
                            previewMode === 'tour' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900',
                        ]"
                    >
                        Guided Tour
                    </button>
                    <button
                        v-if="showLiveDemo"
                        @click="setPreviewMode('live')"
                        :class="[
                            'rounded-md px-4 py-2 text-sm font-medium transition-all duration-200',
                            previewMode === 'live' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900',
                        ]"
                    >
                        Live Demo
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="py-12 text-center">
                <div class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-b-2 border-blue-600"></div>
                <p class="text-gray-600">Loading platform preview...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="py-12 text-center">
                <div class="mb-4 text-red-500">
                    <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                </div>
                <p class="mb-4 text-gray-600">{{ error }}</p>
                <button @click="fetchPreviewData" class="rounded bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700">
                    Try Again
                </button>
            </div>

            <!-- Screenshots Mode -->
            <div v-else-if="previewMode === 'screenshots'" class="space-y-12">
                <!-- Device Selection -->
                <div class="mb-8 flex justify-center space-x-4">
                    <button
                        v-for="device in deviceTypes"
                        :key="device.type"
                        @click="setActiveDevice(device.type)"
                        :class="[
                            'flex items-center space-x-2 rounded-lg border px-4 py-2 transition-all duration-200',
                            activeDevice === device.type
                                ? 'border-blue-600 bg-blue-50 text-blue-700'
                                : 'border-gray-300 text-gray-700 hover:border-gray-400 hover:bg-gray-50',
                        ]"
                    >
                        <div class="h-5 w-5" v-html="device.icon"></div>
                        <span class="font-medium">{{ device.label }}</span>
                    </button>
                </div>

                <!-- Device Mockup -->
                <div class="flex justify-center">
                    <div class="relative">
                        <!-- Device Frame -->
                        <div :class="getDeviceFrameClass()">
                            <!-- Screenshot Container -->
                            <div class="relative overflow-hidden" :class="getScreenClass()">
                                <!-- Screenshot Image -->
                                <img
                                    v-if="activeScreenshot"
                                    :src="activeScreenshot.image"
                                    :alt="activeScreenshot.description"
                                    class="h-full w-full object-cover transition-opacity duration-300"
                                    @load="handleImageLoad"
                                    @error="handleImageError"
                                />

                                <!-- Interactive Hotspots -->
                                <div
                                    v-for="(hotspot, index) in activeScreenshot?.hotspots || []"
                                    :key="index"
                                    class="absolute z-10 -translate-x-1/2 -translate-y-1/2 transform cursor-pointer"
                                    :style="{ left: `${hotspot.x}%`, top: `${hotspot.y}%` }"
                                    @click="showHotspotDetail(hotspot, index)"
                                    @mouseenter="showHotspotTooltip(hotspot, index)"
                                    @mouseleave="hideHotspotTooltip"
                                >
                                    <!-- Hotspot Indicator -->
                                    <div class="relative">
                                        <div
                                            class="flex h-8 w-8 animate-pulse items-center justify-center rounded-full border-2 border-white bg-blue-600 shadow-lg"
                                        >
                                            <div class="h-3 w-3 rounded-full bg-white"></div>
                                        </div>

                                        <!-- Pulse Animation -->
                                        <div class="absolute inset-0 h-8 w-8 animate-ping rounded-full bg-blue-600 opacity-30"></div>
                                    </div>

                                    <!-- Hotspot Tooltip -->
                                    <div v-if="activeHotspot === index" class="absolute bottom-full left-1/2 z-20 mb-3 -translate-x-1/2 transform">
                                        <div class="max-w-xs rounded-lg bg-gray-900 px-3 py-2 text-sm text-white shadow-lg">
                                            <div class="mb-1 font-medium">{{ hotspot.title }}</div>
                                            <div class="text-xs text-gray-300">{{ hotspot.description }}</div>

                                            <!-- Tooltip Arrow -->
                                            <div class="absolute left-1/2 top-full -translate-x-1/2 transform">
                                                <div class="border-4 border-transparent border-t-gray-900"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Zoom Controls -->
                                <div v-if="showZoomControls" class="absolute right-4 top-4 flex flex-col space-y-2">
                                    <button
                                        @click="zoomIn"
                                        :disabled="zoomLevel >= maxZoom"
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-white shadow-md transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                                        aria-label="Zoom in"
                                    >
                                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                            ></path>
                                        </svg>
                                    </button>
                                    <button
                                        @click="zoomOut"
                                        :disabled="zoomLevel <= minZoom"
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-white shadow-md transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                                        aria-label="Zoom out"
                                    >
                                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"></path>
                                        </svg>
                                    </button>
                                    <button
                                        @click="resetZoom"
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-white shadow-md transition-colors hover:bg-gray-50"
                                        aria-label="Reset zoom"
                                    >
                                        <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                            ></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Device Labels -->
                        <div class="absolute -bottom-8 left-1/2 -translate-x-1/2 transform">
                            <div class="text-center text-sm text-gray-600">
                                {{ getDeviceLabel() }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Screenshot Navigation -->
                <div class="mt-8 flex justify-center space-x-2">
                    <button
                        v-for="(screenshot, index) in currentDeviceScreenshots"
                        :key="screenshot.id"
                        @click="setActiveScreenshot(index)"
                        :class="[
                            'h-12 w-16 overflow-hidden rounded-lg border-2 transition-all duration-200',
                            activeScreenshotIndex === index ? 'border-blue-600 ring-2 ring-blue-200' : 'border-gray-300 hover:border-gray-400',
                        ]"
                    >
                        <img :src="screenshot.thumbnail || screenshot.image" :alt="screenshot.title" class="h-full w-full object-cover" />
                    </button>
                </div>

                <!-- Screenshot Details -->
                <div v-if="activeScreenshot" class="mx-auto max-w-4xl">
                    <div class="rounded-lg bg-white p-6 shadow-md">
                        <h3 class="mb-2 text-xl font-semibold text-gray-900">
                            {{ activeScreenshot.title }}
                        </h3>
                        <p class="mb-4 text-gray-600">
                            {{ activeScreenshot.description }}
                        </p>

                        <!-- Feature Highlights -->
                        <div v-if="activeScreenshot.features && activeScreenshot.features.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div
                                v-for="feature in activeScreenshot.features"
                                :key="feature.id"
                                class="flex items-start space-x-3 rounded-lg bg-gray-50 p-3"
                            >
                                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100">
                                    <svg class="h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ feature.title }}</h4>
                                    <p class="text-sm text-gray-600">{{ feature.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guided Tour Mode -->
            <div v-else-if="previewMode === 'tour'" class="mx-auto max-w-6xl">
                <GuidedTour
                    :steps="tourSteps"
                    :current-step="currentTourStep"
                    :audience="audience"
                    @step-change="handleTourStepChange"
                    @tour-complete="handleTourComplete"
                    @tour-exit="handleTourExit"
                />
            </div>

            <!-- Live Demo Mode -->
            <div v-else-if="previewMode === 'live'" class="mx-auto max-w-6xl">
                <div class="overflow-hidden rounded-lg bg-white shadow-lg">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Live Platform Demo</h3>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                                    <div class="mr-1 h-2 w-2 animate-pulse rounded-full bg-green-400"></div>
                                    Live
                                </span>
                                <button @click="refreshDemo" class="rounded p-1 text-gray-400 hover:text-gray-600" aria-label="Refresh demo">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                        ></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="relative" style="aspect-ratio: 16/10">
                        <iframe
                            :src="liveDemoUrl"
                            class="h-full w-full"
                            frameborder="0"
                            title="Live platform demo"
                            sandbox="allow-scripts allow-same-origin allow-forms"
                            loading="lazy"
                            @load="handleDemoLoad"
                            @error="handleDemoError"
                        ></iframe>

                        <!-- Demo Loading Overlay -->
                        <div v-if="demoLoading" class="absolute inset-0 flex items-center justify-center bg-gray-100">
                            <div class="text-center">
                                <div class="mx-auto mb-2 h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
                                <p class="text-sm text-gray-600">Loading live demo...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="mt-12 text-center">
                <div class="mx-auto max-w-2xl">
                    <h3 class="mb-4 text-2xl font-bold text-gray-900">Ready to Experience the Full Platform?</h3>
                    <p class="mb-6 text-gray-600">
                        {{ ctaDescription }}
                    </p>
                    <div class="flex flex-col items-center justify-center space-y-3 sm:flex-row sm:space-x-4 sm:space-y-0">
                        <button
                            @click="handlePrimaryCTA"
                            class="w-full rounded-lg bg-blue-600 px-8 py-3 font-semibold text-white transition-colors hover:bg-blue-700 sm:w-auto"
                        >
                            {{ primaryCTAText }}
                        </button>
                        <button
                            @click="handleSecondaryCTA"
                            class="w-full rounded-lg border border-gray-300 px-8 py-3 font-semibold text-gray-700 transition-colors hover:bg-gray-50 sm:w-auto"
                        >
                            {{ secondaryCTAText }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hotspot Detail Modal -->
        <div v-if="selectedHotspot" class="fixed inset-0 z-50 overflow-y-auto" @click="closeHotspotDetail">
            <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                <div
                    class="my-8 inline-block w-full max-w-md transform overflow-hidden rounded-lg bg-white p-6 text-left align-middle shadow-xl transition-all"
                    @click.stop
                >
                    <HotspotDetail :hotspot="selectedHotspot" @close="closeHotspotDetail" />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import type { AudienceType } from '@/Types/homepage';
import { computed, onMounted, ref, watch } from 'vue';
import GuidedTour from './GuidedTour.vue';
import HotspotDetail from './HotspotDetail.vue';

interface Screenshot {
    id: string;
    title: string;
    description: string;
    image: string;
    thumbnail?: string;
    device: 'desktop' | 'tablet' | 'mobile';
    hotspots: Hotspot[];
    features: ScreenshotFeature[];
}

interface Hotspot {
    x: number;
    y: number;
    title: string;
    description: string;
    feature?: string;
    action?: string;
}

interface ScreenshotFeature {
    id: string;
    title: string;
    description: string;
    icon?: string;
}

interface TourStep {
    id: string;
    title: string;
    description: string;
    screenshot: string;
    callouts: Callout[];
    duration?: number;
}

interface Callout {
    x: number;
    y: number;
    title: string;
    description: string;
    type: 'info' | 'highlight' | 'warning';
}

interface DeviceType {
    type: 'desktop' | 'tablet' | 'mobile';
    label: string;
    icon: string;
}

interface Props {
    audience: AudienceType;
    title?: string;
    subtitle?: string;
    showZoomControls?: boolean;
    showLiveDemo?: boolean;
    autoStartTour?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'See the Platform in Action',
    subtitle: 'Explore our intuitive interface and powerful features through interactive screenshots and guided tours',
    showZoomControls: true,
    showLiveDemo: true,
    autoStartTour: false,
});

// Reactive state
const screenshots = ref<Screenshot[]>([]);
const tourSteps = ref<TourStep[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const previewMode = ref<'screenshots' | 'tour' | 'live'>('screenshots');
const activeDevice = ref<'desktop' | 'tablet' | 'mobile'>('desktop');
const activeScreenshotIndex = ref(0);
const activeHotspot = ref<number | null>(null);
const selectedHotspot = ref<Hotspot | null>(null);
const currentTourStep = ref(0);
const zoomLevel = ref(1);
const minZoom = ref(0.5);
const maxZoom = ref(2);
const demoLoading = ref(false);
const sectionRef = ref<HTMLElement>();

// Computed properties
const title = computed(() => {
    if (props.audience === 'institutional') {
        return 'Experience Your Branded Alumni Platform';
    }
    return props.title;
});

const subtitle = computed(() => {
    if (props.audience === 'institutional') {
        return 'See how your institution can transform alumni engagement with our comprehensive platform and branded mobile apps';
    }
    return props.subtitle;
});

const ctaDescription = computed(() => {
    if (props.audience === 'institutional') {
        return 'Schedule a personalized demo to see how our platform can transform your alumni community engagement.';
    }
    return 'Join thousands of alumni who are already advancing their careers through meaningful connections.';
});

const primaryCTAText = computed(() => {
    return props.audience === 'institutional' ? 'Request Demo' : 'Start Free Trial';
});

const secondaryCTAText = computed(() => {
    return props.audience === 'institutional' ? 'Download Case Studies' : 'Learn More';
});

const liveDemoUrl = computed(() => {
    return props.audience === 'institutional' ? '/demo/institutional-platform' : '/demo/alumni-platform';
});

const deviceTypes = computed((): DeviceType[] => [
    {
        type: 'desktop',
        label: 'Desktop',
        icon: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M21 2H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h7l-2 3v1h8v-1l-2-3h7c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 12H3V4h18v10z"/></svg>`,
    },
    {
        type: 'tablet',
        label: 'Tablet',
        icon: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M19 2H5c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 16H5V4h14v14zm-7 1c-.55 0-1 .45-1 1s.45 1 1 1 1-.45 1-1-.45-1-1-1z"/></svg>`,
    },
    {
        type: 'mobile',
        label: 'Mobile',
        icon: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M17 2H7c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM7 4h10v12H7V4zm5 15c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>`,
    },
]);

const currentDeviceScreenshots = computed(() => {
    return screenshots.value.filter((screenshot) => screenshot.device === activeDevice.value);
});

const activeScreenshot = computed(() => {
    return currentDeviceScreenshots.value[activeScreenshotIndex.value] || null;
});

// Methods
const fetchPreviewData = async (): Promise<void> => {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch(`/api/homepage/platform-preview?audience=${props.audience}`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            screenshots.value = data.data.screenshots || [];
            tourSteps.value = data.data.tour_steps || [];
        } else {
            throw new Error(data.message || 'Failed to fetch preview data');
        }
    } catch (err) {
        console.error('Error fetching platform preview data:', err);
        error.value = err instanceof Error ? err.message : 'Failed to load platform preview';

        // Fallback to mock data for development
        if (process.env.NODE_ENV === 'development') {
            const mockData = getMockPreviewData();
            screenshots.value = mockData.screenshots;
            tourSteps.value = mockData.tourSteps;
        }
    } finally {
        loading.value = false;
    }
};

const getMockPreviewData = (): { screenshots: Screenshot[]; tourSteps: TourStep[] } => {
    const screenshots: Screenshot[] = [
        {
            id: 'dashboard-desktop',
            title: 'Alumni Dashboard',
            description: 'Your personalized dashboard showing connections, opportunities, and recent activity.',
            image: '/images/screenshots/dashboard-desktop.png',
            thumbnail: '/images/screenshots/dashboard-desktop-thumb.png',
            device: 'desktop',
            hotspots: [
                {
                    x: 25,
                    y: 30,
                    title: 'Connection Recommendations',
                    description: 'AI-powered suggestions for new alumni connections based on your profile and interests.',
                    feature: 'networking',
                    action: 'view_connections',
                },
                {
                    x: 75,
                    y: 45,
                    title: 'Job Opportunities',
                    description: 'Exclusive job postings shared within your alumni network.',
                    feature: 'jobs',
                    action: 'view_jobs',
                },
                {
                    x: 50,
                    y: 70,
                    title: 'Upcoming Events',
                    description: 'Alumni events and networking opportunities in your area.',
                    feature: 'events',
                    action: 'view_events',
                },
            ],
            features: [
                {
                    id: 'personalized_feed',
                    title: 'Personalized Activity Feed',
                    description: 'Stay updated with relevant alumni news and opportunities',
                },
                {
                    id: 'quick_actions',
                    title: 'Quick Actions',
                    description: 'Access key features with one-click shortcuts',
                },
            ],
        },
        {
            id: 'networking-desktop',
            title: 'Alumni Network',
            description: 'Browse and connect with alumni based on shared interests, location, and career goals.',
            image: '/images/screenshots/networking-desktop.png',
            device: 'desktop',
            hotspots: [
                {
                    x: 20,
                    y: 25,
                    title: 'Search Filters',
                    description: 'Filter alumni by industry, location, graduation year, and more.',
                    feature: 'search',
                },
                {
                    x: 60,
                    y: 40,
                    title: 'Alumni Profiles',
                    description: 'View detailed profiles with career history and interests.',
                    feature: 'profiles',
                },
            ],
            features: [
                {
                    id: 'advanced_search',
                    title: 'Advanced Search',
                    description: 'Find alumni using multiple criteria and filters',
                },
                {
                    id: 'connection_requests',
                    title: 'Smart Connection Requests',
                    description: 'Send personalized connection requests with context',
                },
            ],
        },
        {
            id: 'dashboard-mobile',
            title: 'Mobile Dashboard',
            description: 'Access your alumni network on the go with our mobile-optimized interface.',
            image: '/images/screenshots/dashboard-mobile.png',
            device: 'mobile',
            hotspots: [
                {
                    x: 50,
                    y: 30,
                    title: 'Mobile Navigation',
                    description: 'Easy access to all platform features from your mobile device.',
                    feature: 'navigation',
                },
            ],
            features: [
                {
                    id: 'mobile_optimized',
                    title: 'Mobile Optimized',
                    description: 'Full functionality optimized for mobile devices',
                },
            ],
        },
    ];

    const tourSteps: TourStep[] = [
        {
            id: 'welcome',
            title: 'Welcome to Your Alumni Platform',
            description: "Let's take a quick tour of the key features that will help you advance your career.",
            screenshot: '/images/tour/step-1-welcome.png',
            callouts: [
                {
                    x: 50,
                    y: 20,
                    title: 'Your Dashboard',
                    description: 'This is your personalized homepage',
                    type: 'info',
                },
            ],
            duration: 5000,
        },
        {
            id: 'networking',
            title: 'Connect with Alumni',
            description: 'Discover and connect with alumni who share your interests and career goals.',
            screenshot: '/images/tour/step-2-networking.png',
            callouts: [
                {
                    x: 30,
                    y: 40,
                    title: 'Smart Recommendations',
                    description: 'AI-powered connection suggestions',
                    type: 'highlight',
                },
            ],
            duration: 7000,
        },
        {
            id: 'opportunities',
            title: 'Discover Opportunities',
            description: 'Access exclusive job postings and career opportunities shared by your network.',
            screenshot: '/images/tour/step-3-opportunities.png',
            callouts: [
                {
                    x: 70,
                    y: 50,
                    title: 'Exclusive Jobs',
                    description: 'Jobs shared only within your alumni network',
                    type: 'highlight',
                },
            ],
            duration: 6000,
        },
    ];

    return { screenshots, tourSteps };
};

const setPreviewMode = (mode: 'screenshots' | 'tour' | 'live'): void => {
    previewMode.value = mode;

    if (mode === 'tour' && props.autoStartTour) {
        currentTourStep.value = 0;
    }
};

const setActiveDevice = (device: 'desktop' | 'tablet' | 'mobile'): void => {
    activeDevice.value = device;
    activeScreenshotIndex.value = 0; // Reset to first screenshot for new device
    resetZoom();
};

const setActiveScreenshot = (index: number): void => {
    activeScreenshotIndex.value = index;
    activeHotspot.value = null;
    resetZoom();
};

const showHotspotTooltip = (hotspot: Hotspot, index: number): void => {
    activeHotspot.value = index;
};

const hideHotspotTooltip = (): void => {
    activeHotspot.value = null;
};

const showHotspotDetail = (hotspot: Hotspot, index: number): void => {
    selectedHotspot.value = hotspot;
};

const closeHotspotDetail = (): void => {
    selectedHotspot.value = null;
};

const zoomIn = (): void => {
    if (zoomLevel.value < maxZoom.value) {
        zoomLevel.value = Math.min(zoomLevel.value + 0.25, maxZoom.value);
    }
};

const zoomOut = (): void => {
    if (zoomLevel.value > minZoom.value) {
        zoomLevel.value = Math.max(zoomLevel.value - 0.25, minZoom.value);
    }
};

const resetZoom = (): void => {
    zoomLevel.value = 1;
};

const handleTourStepChange = (step: number): void => {
    currentTourStep.value = step;
};

const handleTourComplete = (): void => {
    // Track tour completion
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'tour_complete', {
            audience: props.audience,
        });
    }
};

const handleTourExit = (): void => {
    previewMode.value = 'screenshots';
};

const handleImageLoad = (): void => {
    // Image loaded successfully
};

const handleImageError = (event: Event): void => {
    const img = event.target as HTMLImageElement;
    img.src = '/images/placeholder-screenshot.png'; // Fallback image
};

const handleDemoLoad = (): void => {
    demoLoading.value = false;
};

const handleDemoError = (): void => {
    demoLoading.value = false;
    console.error('Failed to load live demo');
};

const refreshDemo = (): void => {
    demoLoading.value = true;
    // Force iframe reload
    const iframe = document.querySelector('iframe');
    if (iframe) {
        iframe.src = iframe.src;
    }
};

const handlePrimaryCTA = (): void => {
    // Track CTA click
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'preview_cta_click', {
            cta_type: 'primary',
            audience: props.audience,
        });
    }

    if (props.audience === 'institutional') {
        // Navigate to demo request page
        window.location.href = '/demo-request';
    } else {
        // Navigate to trial signup
        window.location.href = '/register';
    }
};

const handleSecondaryCTA = (): void => {
    // Track CTA click
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'preview_cta_click', {
            cta_type: 'secondary',
            audience: props.audience,
        });
    }

    if (props.audience === 'institutional') {
        // Navigate to case studies
        window.location.href = '/case-studies';
    } else {
        // Navigate to features page
        window.location.href = '/features';
    }
};

const getDeviceFrameClass = (): string => {
    const baseClasses = 'relative bg-gray-900 rounded-lg shadow-2xl';

    switch (activeDevice.value) {
        case 'desktop':
            return `${baseClasses} p-2 max-w-4xl`;
        case 'tablet':
            return `${baseClasses} p-3 max-w-2xl rounded-2xl`;
        case 'mobile':
            return `${baseClasses} p-1 max-w-sm rounded-3xl`;
        default:
            return baseClasses;
    }
};

const getScreenClass = (): string => {
    const baseClasses = 'bg-white rounded-lg';
    const zoomStyle = `transform: scale(${zoomLevel.value}); transform-origin: center;`;

    switch (activeDevice.value) {
        case 'desktop':
            return `${baseClasses} aspect-video`;
        case 'tablet':
            return `${baseClasses} aspect-[4/3] rounded-xl`;
        case 'mobile':
            return `${baseClasses} aspect-[9/16] rounded-2xl`;
        default:
            return baseClasses;
    }
};

const getDeviceLabel = (): string => {
    const device = deviceTypes.value.find((d) => d.type === activeDevice.value);
    return device?.label || 'Device';
};

// Watchers
watch(
    () => props.audience,
    () => {
        // Refetch data when audience changes
        fetchPreviewData();
    },
);

watch(activeDevice, () => {
    // Reset screenshot index when device changes
    activeScreenshotIndex.value = 0;
});

// Lifecycle hooks
onMounted(() => {
    fetchPreviewData();
});

// Expose methods for parent components
defineExpose({
    fetchPreviewData,
    refresh: fetchPreviewData,
    setPreviewMode,
    setActiveDevice,
    startTour: () => setPreviewMode('tour'),
});
</script>

<style scoped>
.platform-preview {
    @apply bg-white py-16;
}

/* Smooth transitions */
.platform-preview * {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
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

/* Hotspot animations */
@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

@keyframes ping {
    75%,
    100% {
        transform: scale(2);
        opacity: 0;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.animate-ping {
    animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
}

/* Device frame shadows */
.platform-preview .shadow-2xl {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

/* Zoom controls */
.platform-preview .zoom-controls button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Focus styles for accessibility */
.platform-preview button:focus,
.platform-preview iframe:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .platform-preview {
        @apply py-12;
    }

    .platform-preview .max-w-4xl {
        @apply max-w-full;
    }

    .platform-preview .max-w-2xl {
        @apply max-w-full;
    }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .platform-preview *,
    .animate-spin,
    .animate-pulse,
    .animate-ping {
        animation: none;
        transition: none;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .platform-preview .bg-gray-50 {
        background-color: #ffffff;
        border: 1px solid #000000;
    }

    .platform-preview .text-gray-600 {
        color: #000000;
    }
}

/* Print styles */
@media print {
    .platform-preview {
        break-inside: avoid;
    }

    .platform-preview iframe,
    .platform-preview video {
        display: none;
    }

    .platform-preview .shadow-2xl {
        box-shadow: none;
        border: 1px solid #e5e7eb;
    }
}
</style>














