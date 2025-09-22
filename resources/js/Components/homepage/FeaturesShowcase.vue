<template>
    <section class="features-showcase" ref="sectionRef">
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

            <!-- Persona Filters -->
            <div v-if="showPersonaFilters && personaFilters.length > 0" class="mb-8 flex flex-wrap justify-center gap-3">
                <button
                    v-for="filter in personaFilters"
                    :key="filter.value"
                    @click="setActivePersona(filter.value)"
                    :class="[
                        'rounded-full px-6 py-3 text-sm font-medium transition-all duration-200',
                        activePersona === filter.value
                            ? 'scale-105 transform bg-blue-600 text-white shadow-lg'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 hover:shadow-md',
                    ]"
                    :aria-pressed="activePersona === filter.value"
                >
                    {{ filter.label }}
                    <span v-if="filter.count" class="ml-2 text-xs opacity-75"> ({{ filter.count }}) </span>
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="py-12 text-center">
                <div class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-b-2 border-blue-600"></div>
                <p class="text-gray-600">Loading features...</p>
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
                <button @click="fetchFeatures" class="rounded bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700">Try Again</button>
            </div>

            <!-- Features Content -->
            <div v-else-if="filteredFeatures.length > 0" class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <!-- Feature Tabs/Navigation -->
                <div class="lg:col-span-4">
                    <div class="sticky top-8">
                        <h3 class="mb-4 text-xl font-semibold text-gray-900">
                            {{ navigationTitle }}
                        </h3>

                        <!-- Desktop: Vertical Tabs -->
                        <div class="hidden space-y-2 lg:block">
                            <button
                                v-for="(feature, index) in filteredFeatures"
                                :key="feature.id"
                                @click="setActiveFeature(index)"
                                :class="[
                                    'w-full rounded-lg p-4 text-left transition-all duration-200',
                                    activeFeatureIndex === index
                                        ? 'border-l-4 border-blue-600 bg-blue-50 shadow-sm'
                                        : 'border-l-4 border-transparent hover:bg-gray-50',
                                ]"
                                :aria-selected="activeFeatureIndex === index"
                                role="tab"
                            >
                                <div class="flex items-start space-x-3">
                                    <div class="mt-1 flex-shrink-0">
                                        <div v-if="feature.icon" class="h-8 w-8 text-blue-600" v-html="getFeatureIcon(feature.icon)"></div>
                                    </div>
                                    <div class="min-w-0 flex-grow">
                                        <h4 class="mb-1 font-medium text-gray-900">
                                            {{ feature.title }}
                                        </h4>
                                        <p class="line-clamp-2 text-sm text-gray-600">
                                            {{ feature.description }}
                                        </p>
                                        <div v-if="feature.usageStats && feature.usageStats.length > 0" class="mt-2">
                                            <span
                                                class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800"
                                            >
                                                {{ formatUsageStat(feature.usageStats[0]) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <!-- Mobile: Horizontal Tabs -->
                        <div class="lg:hidden">
                            <div class="flex space-x-2 overflow-x-auto pb-2">
                                <button
                                    v-for="(feature, index) in filteredFeatures"
                                    :key="feature.id"
                                    @click="setActiveFeature(index)"
                                    :class="[
                                        'flex-shrink-0 rounded-full px-4 py-2 text-sm font-medium transition-colors',
                                        activeFeatureIndex === index ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                    ]"
                                >
                                    {{ feature.title }}
                                </button>
                            </div>
                        </div>

                        <!-- Feature Comparison Matrix Toggle -->
                        <div v-if="showComparisonMatrix" class="mt-6 border-t border-gray-200 pt-6">
                            <button @click="toggleComparisonMatrix" class="flex items-center space-x-2 font-medium text-blue-600 hover:text-blue-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2z"
                                    ></path>
                                </svg>
                                <span>{{ showMatrix ? 'Hide' : 'Show' }} Comparison Matrix</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Feature Content -->
                <div class="lg:col-span-8">
                    <div v-if="activeFeature" class="overflow-hidden rounded-xl bg-white shadow-lg">
                        <!-- Feature Header -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-grow">
                                    <h3 class="mb-2 text-2xl font-bold text-gray-900">
                                        {{ activeFeature.title }}
                                    </h3>
                                    <p class="mb-4 text-gray-700">
                                        {{ activeFeature.description }}
                                    </p>

                                    <!-- Benefits List -->
                                    <div v-if="activeFeature.benefits && activeFeature.benefits.length > 0" class="space-y-2">
                                        <div v-for="benefit in activeFeature.benefits.slice(0, 3)" :key="benefit" class="flex items-center space-x-2">
                                            <svg class="h-5 w-5 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            <span class="text-sm text-gray-700">{{ benefit }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Usage Statistics -->
                                <div v-if="activeFeature.usageStats && activeFeature.usageStats.length > 0" class="ml-6 flex-shrink-0">
                                    <div class="rounded-lg bg-white p-4 shadow-sm">
                                        <div class="text-center">
                                            <div class="text-2xl font-bold text-blue-600">
                                                {{ formatUsageStatValue(activeFeature.usageStats[0]) }}
                                            </div>
                                            <div class="text-xs text-gray-600">
                                                {{ activeFeature.usageStats[0].label }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Interactive Demo -->
                        <div class="p-6">
                            <!-- Demo Iframe -->
                            <div v-if="activeFeature.demoUrl && showInteractiveDemo" class="mb-6">
                                <div class="relative overflow-hidden rounded-lg bg-gray-100" style="aspect-ratio: 16/9">
                                    <iframe
                                        :src="activeFeature.demoUrl"
                                        class="absolute inset-0 h-full w-full"
                                        frameborder="0"
                                        :title="`Interactive demo of ${activeFeature.title}`"
                                        loading="lazy"
                                        sandbox="allow-scripts allow-same-origin allow-forms"
                                        @load="handleDemoLoad"
                                        @error="handleDemoError"
                                    ></iframe>

                                    <!-- Demo Loading Overlay -->
                                    <div v-if="demoLoading" class="absolute inset-0 flex items-center justify-center bg-gray-100">
                                        <div class="text-center">
                                            <div class="mx-auto mb-2 h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
                                            <p class="text-sm text-gray-600">Loading interactive demo...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Screenshot with Hotspots -->
                            <div v-else-if="activeFeature.screenshot" class="mb-6">
                                <div class="relative">
                                    <img
                                        :src="activeFeature.screenshot"
                                        :alt="`Screenshot of ${activeFeature.title}`"
                                        class="w-full rounded-lg shadow-md"
                                        @error="handleImageError"
                                    />

                                    <!-- Interactive Hotspots -->
                                    <div
                                        v-for="(hotspot, index) in activeFeature.hotspots"
                                        :key="index"
                                        class="absolute -translate-x-1/2 -translate-y-1/2 transform cursor-pointer"
                                        :style="{ left: `${hotspot.x}%`, top: `${hotspot.y}%` }"
                                        @click="showHotspotTooltip(hotspot, index)"
                                        @mouseenter="showHotspotTooltip(hotspot, index)"
                                        @mouseleave="hideHotspotTooltip"
                                    >
                                        <div class="h-6 w-6 animate-pulse rounded-full border-2 border-white bg-blue-600 shadow-lg">
                                            <div class="h-full w-full animate-ping rounded-full bg-blue-600"></div>
                                        </div>

                                        <!-- Hotspot Tooltip -->
                                        <div
                                            v-if="activeHotspot === index"
                                            class="absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 transform"
                                        >
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
                                </div>
                            </div>

                            <!-- Demo Video -->
                            <div v-if="activeFeature.demoVideo && !showInteractiveDemo" class="mb-6">
                                <div class="relative overflow-hidden rounded-lg bg-black" style="aspect-ratio: 16/9">
                                    <video
                                        :src="activeFeature.demoVideo"
                                        class="h-full w-full object-cover"
                                        controls
                                        preload="metadata"
                                        :poster="activeFeature.screenshot"
                                    >
                                        <track
                                            v-if="activeFeature.subtitles"
                                            kind="subtitles"
                                            :src="activeFeature.subtitles"
                                            srclang="en"
                                            label="English"
                                            default
                                        />
                                    </video>
                                </div>
                            </div>

                            <!-- Feature Details -->
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Target Personas -->
                                <div v-if="activeFeature.targetPersona && activeFeature.targetPersona.length > 0">
                                    <h4 class="mb-3 font-semibold text-gray-900">Perfect For</h4>
                                    <div class="space-y-2">
                                        <div
                                            v-for="persona in activeFeature.targetPersona"
                                            :key="persona.id"
                                            class="flex items-center space-x-3 rounded-lg bg-gray-50 p-3"
                                        >
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                                                <svg class="h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                        clip-rule="evenodd"
                                                    ></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900">{{ persona.name }}</div>
                                                <div class="text-sm text-gray-600">{{ persona.description }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Benefits -->
                                <div v-if="activeFeature.benefits && activeFeature.benefits.length > 3">
                                    <h4 class="mb-3 font-semibold text-gray-900">Additional Benefits</h4>
                                    <ul class="space-y-2">
                                        <li v-for="benefit in activeFeature.benefits.slice(3)" :key="benefit" class="flex items-start space-x-2">
                                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd"
                                                ></path>
                                            </svg>
                                            <span class="text-sm text-gray-700">{{ benefit }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- CTA Section -->
                            <div class="mt-8 border-t border-gray-200 pt-6">
                                <div class="flex flex-col items-center justify-between space-y-4 sm:flex-row sm:space-y-0">
                                    <div>
                                        <h4 class="font-semibold text-gray-900">Ready to try {{ activeFeature.title }}?</h4>
                                        <p class="text-sm text-gray-600">Join thousands of alumni already using this feature</p>
                                    </div>
                                    <div class="flex space-x-3">
                                        <button
                                            @click="handleFeatureCTA('demo')"
                                            class="rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700"
                                        >
                                            Try Demo
                                        </button>
                                        <button
                                            @click="handleFeatureCTA('learn_more')"
                                            class="rounded-lg border border-gray-300 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-50"
                                        >
                                            Learn More
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Comparison Matrix Modal -->
            <div v-if="showMatrix" class="fixed inset-0 z-50 overflow-y-auto" @click="closeComparisonMatrix">
                <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                    <div
                        class="my-8 inline-block w-full max-w-6xl transform overflow-hidden rounded-lg bg-white p-6 text-left align-middle shadow-xl transition-all"
                        @click.stop
                    >
                        <FeatureComparisonMatrix :features="filteredFeatures" :audience="audience" @close="closeComparisonMatrix" />
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else-if="!loading && filteredFeatures.length === 0" class="py-12 text-center">
                <div class="mb-4 text-gray-400">
                    <svg class="mx-auto h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                        ></path>
                    </svg>
                </div>
                <h3 class="mb-2 text-lg font-medium text-gray-900">No features available</h3>
                <p class="text-gray-600">No features match the selected criteria.</p>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import type { AudienceType, FeatureStatistic, InstitutionalFeature, PlatformFeature } from '@/Types/homepage';
import { computed, onMounted, ref, watch } from 'vue';
import FeatureComparisonMatrix from './FeatureComparisonMatrix.vue';

interface PersonaFilter {
    value: string;
    label: string;
    count?: number;
}

interface Hotspot {
    x: number;
    y: number;
    title: string;
    description: string;
}

interface Props {
    audience: AudienceType;
    title?: string;
    subtitle?: string;
    showPersonaFilters?: boolean;
    showComparisonMatrix?: boolean;
    showInteractiveDemo?: boolean;
    demoMode?: 'carousel' | 'tabs' | 'accordion';
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Powerful Features for Alumni Success',
    subtitle: 'Discover the tools and capabilities that will accelerate your professional growth and networking',
    showPersonaFilters: true,
    showComparisonMatrix: true,
    showInteractiveDemo: true,
    demoMode: 'tabs',
});

// Reactive state
const features = ref<(PlatformFeature | InstitutionalFeature)[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const activePersona = ref<string>('all');
const activeFeatureIndex = ref(0);
const showMatrix = ref(false);
const demoLoading = ref(false);
const activeHotspot = ref<number | null>(null);
const sectionRef = ref<HTMLElement>();

// Computed properties
const title = computed(() => {
    if (props.audience === 'institutional') {
        return 'Enterprise Alumni Engagement Solutions';
    }
    return props.title;
});

const subtitle = computed(() => {
    if (props.audience === 'institutional') {
        return 'Comprehensive tools and analytics to transform your alumni community engagement';
    }
    return props.subtitle;
});

const navigationTitle = computed(() => {
    return props.audience === 'institutional' ? 'Enterprise Features' : 'Platform Features';
});

const personaFilters = computed((): PersonaFilter[] => {
    if (!props.showPersonaFilters) return [];

    const filters: PersonaFilter[] = [{ value: 'all', label: 'All Features', count: features.value.length }];

    if (props.audience === 'individual') {
        const individualFeatures = features.value as PlatformFeature[];
        const personas = new Set<string>();

        individualFeatures.forEach((feature) => {
            feature.targetPersona?.forEach((persona) => {
                personas.add(persona.name);
            });
        });

        personas.forEach((persona) => {
            const count = individualFeatures.filter((feature) => feature.targetPersona?.some((p) => p.name === persona)).length;

            filters.push({
                value: persona.toLowerCase().replace(/\s+/g, '_'),
                label: persona,
                count,
            });
        });
    } else {
        const institutionalFeatures = features.value as InstitutionalFeature[];
        const types = new Set<string>();

        institutionalFeatures.forEach((feature) => {
            types.add(feature.targetInstitution);
        });

        types.forEach((type) => {
            const count = institutionalFeatures.filter((feature) => feature.targetInstitution === type).length;

            filters.push({
                value: type,
                label: formatInstitutionType(type),
                count,
            });
        });
    }

    return filters;
});

const filteredFeatures = computed(() => {
    if (activePersona.value === 'all') {
        return features.value;
    }

    if (props.audience === 'individual') {
        const individualFeatures = features.value as PlatformFeature[];
        const personaName = activePersona.value.replace(/_/g, ' ');

        return individualFeatures.filter((feature) =>
            feature.targetPersona?.some((persona) => persona.name.toLowerCase() === personaName.toLowerCase()),
        );
    } else {
        const institutionalFeatures = features.value as InstitutionalFeature[];
        return institutionalFeatures.filter((feature) => feature.targetInstitution === activePersona.value);
    }
});

const activeFeature = computed(() => {
    return filteredFeatures.value[activeFeatureIndex.value] || null;
});

// Methods
const fetchFeatures = async (): Promise<void> => {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch(`/api/homepage/features?audience=${props.audience}`, {
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
            features.value = data.data;
        } else {
            throw new Error(data.message || 'Failed to fetch features');
        }
    } catch (err) {
        console.error('Error fetching features:', err);
        error.value = err instanceof Error ? err.message : 'Failed to load features';

        // Fallback to mock data for development
        if (process.env.NODE_ENV === 'development') {
            features.value = getMockFeatures();
        }
    } finally {
        loading.value = false;
    }
};

const getMockFeatures = (): (PlatformFeature | InstitutionalFeature)[] => {
    if (props.audience === 'institutional') {
        return [
            {
                id: 'admin_dashboard',
                title: 'Comprehensive Admin Dashboard',
                description: 'Manage your entire alumni community with powerful analytics and engagement tools.',
                benefits: [
                    'Real-time engagement analytics',
                    'Event management tools',
                    'Communication campaign builder',
                    'Alumni directory management',
                    'Custom reporting and insights',
                ],
                targetInstitution: 'university',
                screenshot: '/images/features/admin-dashboard.png',
                demoVideo: '/videos/features/admin-dashboard-demo.mp4',
                demoUrl: '/demo/admin-dashboard',
                pricingTier: 'enterprise',
                customizationLevel: 'advanced',
                icon: 'dashboard',
                usageStats: [
                    {
                        metric: 'institutions_using',
                        value: 150,
                        label: 'Institutions Using',
                        trend: 'up',
                    },
                ],
                hotspots: [
                    {
                        x: 25,
                        y: 30,
                        title: 'Analytics Overview',
                        description: 'Real-time engagement metrics and trends',
                    },
                    {
                        x: 75,
                        y: 45,
                        title: 'Event Management',
                        description: 'Create and manage alumni events with RSVP tracking',
                    },
                ],
            },
            {
                id: 'branded_mobile_app',
                title: 'Custom Branded Mobile Apps',
                description: 'Deploy your own branded alumni app with full customization and white-label solutions.',
                benefits: [
                    'Complete white-label solution',
                    'Custom branding and features',
                    'App store deployment included',
                    'Push notification campaigns',
                    'Offline functionality',
                ],
                targetInstitution: 'university',
                screenshot: '/images/features/branded-app-showcase.png',
                demoVideo: '/videos/features/branded-app-demo.mp4',
                pricingTier: 'enterprise',
                customizationLevel: 'full',
                icon: 'mobile',
                usageStats: [
                    {
                        metric: 'apps_deployed',
                        value: 45,
                        label: 'Apps Deployed',
                        trend: 'up',
                    },
                ],
            },
        ] as InstitutionalFeature[];
    }

    return [
        {
            id: 'networking',
            title: 'Smart Alumni Networking',
            description: 'Connect with alumni based on shared interests, career goals, and industry focus using AI-powered recommendations.',
            benefits: [
                'AI-powered connection recommendations',
                'Industry-specific networking groups',
                'Professional conversation starters',
                'Mutual connection discovery',
                'Privacy-controlled networking',
            ],
            screenshot: '/images/features/networking-dashboard.png',
            demoVideo: '/videos/features/networking-demo.mp4',
            demoUrl: '/demo/networking',
            usageStats: [
                {
                    metric: 'connections_made_monthly',
                    value: 12000,
                    label: 'Monthly Connections',
                    trend: 'up',
                },
            ],
            targetPersona: [
                {
                    id: 'recent_grad',
                    name: 'Recent Graduates',
                    description: 'New alumni looking to build professional networks',
                    careerStage: 'recent_grad',
                    primaryGoals: ['Find mentors', 'Job opportunities'],
                    painPoints: ['Limited network', 'Career uncertainty'],
                },
                {
                    id: 'mid_career',
                    name: 'Mid-Career Professionals',
                    description: 'Experienced professionals seeking career advancement',
                    careerStage: 'mid_career',
                    primaryGoals: ['Career advancement', 'Industry connections'],
                    painPoints: ['Career plateau', 'Limited industry connections'],
                },
            ],
            category: 'networking',
            icon: 'network',
            hotspots: [
                {
                    x: 30,
                    y: 25,
                    title: 'Connection Recommendations',
                    description: 'AI-powered suggestions based on your profile and goals',
                },
                {
                    x: 70,
                    y: 60,
                    title: 'Industry Groups',
                    description: 'Join groups specific to your industry and interests',
                },
            ],
        },
        {
            id: 'mentorship',
            title: 'Career Mentorship Matching',
            description: 'Get paired with experienced alumni mentors in your field through our intelligent matching system.',
            benefits: [
                'Personalized mentor matching',
                'Structured mentorship programs',
                'Goal tracking and progress monitoring',
                'Video call integration',
                'Mentorship resource library',
            ],
            screenshot: '/images/features/mentorship-matching.png',
            demoVideo: '/videos/features/mentorship-demo.mp4',
            demoUrl: '/demo/mentorship',
            usageStats: [
                {
                    metric: 'active_mentorships',
                    value: 1800,
                    label: 'Active Mentorships',
                    trend: 'up',
                },
            ],
            targetPersona: [
                {
                    id: 'recent_grad',
                    name: 'Recent Graduates',
                    description: 'New alumni seeking career guidance',
                    careerStage: 'recent_grad',
                    primaryGoals: ['Career guidance', 'Skill development'],
                    painPoints: ['Lack of experience', 'Career direction'],
                },
            ],
            category: 'mentorship',
            icon: 'users',
        },
        {
            id: 'job_board',
            title: 'Exclusive Alumni Job Board',
            description: 'Access job opportunities shared exclusively within your alumni network, with referral tracking.',
            benefits: [
                'Alumni-exclusive job postings',
                'Referral tracking system',
                'Application status updates',
                'Salary insights and benchmarks',
                'Interview preparation resources',
            ],
            screenshot: '/images/features/job-board.png',
            demoVideo: '/videos/features/job-board-demo.mp4',
            usageStats: [
                {
                    metric: 'job_placements',
                    value: 3200,
                    label: 'Job Placements',
                    trend: 'up',
                },
            ],
            targetPersona: [
                {
                    id: 'job_seeker',
                    name: 'Job Seekers',
                    description: 'Alumni actively looking for new opportunities',
                    careerStage: 'mid_career',
                    primaryGoals: ['Find new job', 'Career change'],
                    painPoints: ['Limited opportunities', 'Competition'],
                },
            ],
            category: 'jobs',
            icon: 'briefcase',
        },
    ] as PlatformFeature[];
};

const setActivePersona = (persona: string): void => {
    activePersona.value = persona;
    activeFeatureIndex.value = 0; // Reset to first feature when filter changes
};

const setActiveFeature = (index: number): void => {
    activeFeatureIndex.value = index;
    activeHotspot.value = null; // Hide any active hotspots
};

const toggleComparisonMatrix = (): void => {
    showMatrix.value = !showMatrix.value;
};

const closeComparisonMatrix = (): void => {
    showMatrix.value = false;
};

const showHotspotTooltip = (hotspot: Hotspot, index: number): void => {
    activeHotspot.value = index;
};

const hideHotspotTooltip = (): void => {
    activeHotspot.value = null;
};

const handleDemoLoad = (): void => {
    demoLoading.value = false;
};

const handleDemoError = (): void => {
    demoLoading.value = false;
    console.error('Failed to load interactive demo');
};

const handleImageError = (event: Event): void => {
    const img = event.target as HTMLImageElement;
    img.src = '/images/placeholder-feature.png'; // Fallback image
};

const handleFeatureCTA = (action: 'demo' | 'learn_more'): void => {
    if (!activeFeature.value) return;

    // Track the CTA click
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'feature_cta_click', {
            feature_id: activeFeature.value.id,
            action: action,
            audience: props.audience,
        });
    }

    if (action === 'demo') {
        // Open demo in new tab or modal
        if (activeFeature.value.demoUrl) {
            window.open(activeFeature.value.demoUrl, '_blank');
        }
    } else {
        // Navigate to feature detail page or show more info
        console.log('Learn more about feature:', activeFeature.value.id);
    }
};

const getFeatureIcon = (iconName: string): string => {
    const icons: Record<string, string> = {
        network: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M15 9H9v6h6V9zm-2 4h-2v-2h2v2zm8-2V9h-2V7c0-1.1-.9-2-2-2h-2V3h-2v2h-2V3H9v2H7c-1.1 0-2 .9-2 2v2H3v2h2v2H3v2h2v2c0 1.1.9 2 2 2h2v2h2v-2h2v2h2v-2h2c1.1 0 2-.9 2-2v-2h2v-2h-2v-2h2z"/></svg>`,
        users: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>`,
        briefcase: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M10 16V8a1 1 0 011-1h2a1 1 0 011 1v8a1 1 0 01-1 1h-2a1 1 0 01-1-1zM6 16V8a1 1 0 011-1h2a1 1 0 011 1v8a1 1 0 01-1 1H7a1 1 0 01-1-1zM14 16V8a1 1 0 011-1h2a1 1 0 011 1v8a1 1 0 01-1 1h-2a1 1 0 01-1-1z"/></svg>`,
        dashboard: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>`,
        mobile: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M17 2H7c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM7 4h10v12H7V4zm5 15c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>`,
    };

    return icons[iconName] || icons.network;
};

const formatInstitutionType = (type: string): string => {
    const typeMap: Record<string, string> = {
        university: 'Universities',
        college: 'Colleges',
        corporate: 'Corporations',
        nonprofit: 'Non-Profits',
    };
    return typeMap[type] || type;
};

const formatUsageStat = (stat: FeatureStatistic): string => {
    return `${stat.value.toLocaleString()} ${stat.label}`;
};

const formatUsageStatValue = (stat: FeatureStatistic): string => {
    return stat.value.toLocaleString();
};

// Watchers
watch(activePersona, () => {
    // Reset to first feature when persona changes
    activeFeatureIndex.value = 0;
});

watch(
    () => props.audience,
    () => {
        // Refetch features when audience changes
        fetchFeatures();
    },
);

// Lifecycle hooks
onMounted(() => {
    fetchFeatures();
});

// Expose methods for parent Components
defineExpose({
    fetchFeatures,
    refresh: fetchFeatures,
    setActiveFeature,
    setActivePersona,
});
</script>

<style scoped>
.features-showcase {
    padding-top: 4rem;
    padding-bottom: 4rem;
    background-color: #f9fafb;
}

/* Smooth transitions */
.features-showcase * {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

/* Line clamp utility */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
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

/* Sticky positioning for navigation */
.sticky {
    position: sticky;
}

/* Focus styles for accessibility */
.features-showcase button:focus,
.features-showcase iframe:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .features-showcase .lg\:col-span-4 {
        @apply col-span-1;
    }

    .features-showcase .lg\:col-span-8 {
        @apply col-span-1;
    }
}

@media (max-width: 768px) {
    .features-showcase {
        @apply py-12;
    }

    .features-showcase .sticky {
        position: static;
    }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .features-showcase *,
    .animate-spin,
    .animate-pulse,
    .animate-ping {
        animation: none;
        transition: none;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .features-showcase .bg-gray-50 {
        background-color: #ffffff;
    }

    .features-showcase .text-gray-600 {
        color: #000000;
    }
}

/* Print styles */
@media print {
    .features-showcase {
        break-inside: avoid;
    }

    .features-showcase .sticky {
        position: static;
    }

    .features-showcase iframe,
    .features-showcase video {
        display: none;
    }
}
</style>














