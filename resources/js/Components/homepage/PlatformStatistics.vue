<template>
    <div class="platform-statistics">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 h-80 w-80 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 opacity-30 animate-float"></div>
            <div class="absolute -bottom-40 -left-40 h-96 w-96 rounded-full bg-gradient-to-tr from-indigo-100 to-cyan-100 opacity-25 animate-float-delayed"></div>
            <div class="absolute top-1/2 left-1/2 h-64 w-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-gradient-to-r from-indigo-100 to-cyan-100 opacity-20 animate-pulse-slow"></div>
        </div>
        
        <div class="container relative mx-auto px-4">
            <div class="mb-16 text-center">
                <div class="mb-6">
                    <span class="inline-block rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-lg animate-shimmer">
                        Platform Impact
                    </span>
                </div>
                <h2 class="mb-6 bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-600 bg-clip-text text-4xl font-black text-transparent md:text-5xl lg:text-6xl animate-glow">
                    {{ audienceSpecificTitle }}
                </h2>
                <p class="mx-auto max-w-3xl text-xl font-medium text-gray-600 leading-relaxed">
                    {{ audienceSpecificSubtitle }}
                </p>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="grid grid-cols-2 gap-6 md:grid-cols-4 md:gap-8">
                <div v-for="i in 4" :key="i" class="animate-pulse text-center">
                    <div class="mb-2 h-16 rounded bg-gray-200"></div>
                    <div class="mx-auto h-4 w-3/4 rounded bg-gray-200"></div>
                </div>
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
                <button @click="fetchStatistics" class="rounded bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700">
                    Try Again
                </button>
            </div>

            <!-- Statistics Grid -->
            <div v-else class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div 
                    v-for="(stat, index) in statistics" 
                    :key="stat.key" 
                    class="group relative overflow-hidden rounded-3xl bg-white/20 backdrop-blur-xl border border-white/30 p-8 text-center shadow-xl transition-all duration-700 hover:shadow-2xl hover:scale-105 hover:-translate-y-2" 
                    :class="{ 'animate-fade-in-up': isVisible }"
                    :style="{ animationDelay: `${index * 150}ms` }"
                >
                    <!-- Card Background Gradient -->
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-indigo-500/5 rounded-3xl z-0"></div>
                    
                    <!-- Hover Glow Effect -->
                    <div class="absolute inset-0 rounded-3xl bg-gradient-to-r from-blue-500/10 via-indigo-500/10 to-cyan-500/10 opacity-0 blur-xl transition-opacity duration-700 group-hover:opacity-100"></div>
                    
                    <!-- Content -->
                    <div class="relative z-10">
                        <!-- Icon Container -->
                        <div class="mb-6">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-lg transition-all duration-700 group-hover:scale-110 group-hover:rotate-3 group-hover:shadow-2xl relative overflow-hidden">
                                <div
                                    v-if="stat.icon"
                                    class="h-8 w-8 text-white transition-transform duration-700 group-hover:scale-110 z-10"
                                    v-html="getIconSvg(stat.icon)"
                                ></div>
                                <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent"></div>
                            </div>
                        </div>

                        <!-- Counter -->
                        <div class="mb-4">
                            <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-cyan-600 bg-clip-text text-4xl font-black text-transparent md:text-5xl transition-all duration-700 group-hover:scale-110">
                                <AnimatedCounter
                                    :target-value="stat.value"
                                    :format="stat.format"
                                    :suffix="stat.suffix"
                                    :animate="isVisible && stat.animateOnScroll"
                                    :duration="2000"
                                    :aria-label="`${stat.label}: ${stat.value}${stat.suffix || ''}`"
                                />
                            </div>
                        </div>

                        <!-- Label -->
                        <p class="text-base font-semibold text-gray-700 transition-colors duration-500 group-hover:text-gray-900 group-hover:font-bold">
                            {{ stat.label }}
                        </p>
                        
                        <!-- Progress Bar -->
                        <div class="mt-4 h-1 w-full overflow-hidden rounded-full bg-white/20 backdrop-blur-sm">
                            <div 
                                class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 transition-all duration-1000 ease-out rounded-full"
                                :style="{ width: isVisible ? '100%' : '0%', transitionDelay: `${index * 200 + 500}ms` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Last Updated -->
            <div v-if="lastUpdated && !loading && !error" class="mt-12 text-center">
                <div class="inline-flex items-center rounded-full bg-gray-100/80 backdrop-blur-sm px-4 py-2 text-sm text-gray-600 shadow-sm">
                    <div class="mr-2 h-2 w-2 rounded-full bg-green-500 animate-pulse"></div>
                    Last updated: {{ formatDate(lastUpdated) }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import AnimatedCounter from '@/Components/ui/AnimatedCounter.vue';
import type { AudienceType, PlatformStatistic } from '@/types/homepage';
import { useIntersectionObserver } from '@vueuse/core';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Props {
    audience: AudienceType;
    title?: string;
    subtitle?: string;
    autoFetch?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Trusted by Alumni Worldwide',
    subtitle: 'Join thousands of professionals advancing their careers through meaningful connections',
    autoFetch: true,
});

// Reactive state
const statistics = ref<PlatformStatistic[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const lastUpdated = ref<Date | null>(null);
const statisticsContainer = ref<HTMLElement>();
const isVisible = ref(false);

// Computed properties
const audienceSpecificTitle = computed(() => {
    if (props.audience === 'institutional') {
        return 'Trusted by Leading Institutions';
    }
    return props.title;
});

const audienceSpecificSubtitle = computed(() => {
    if (props.audience === 'institutional') {
        return 'See how universities and organizations are transforming alumni engagement';
    }
    return props.subtitle;
});

// Use intersection observer to trigger animations when visible
const { stop } = useIntersectionObserver(
    statisticsContainer,
    ([{ isIntersecting }]) => {
        if (isIntersecting && !isVisible.value) {
            isVisible.value = true;
            stop(); // Stop observing once visible
        }
    },
    {
        threshold: 0.3,
        rootMargin: '50px',
    },
);

// API functions
const fetchStatistics = async (): Promise<void> => {
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch(`/api/homepage/statistics?audience=${props.audience}`, {
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
            statistics.value = data.data.statistics;
            lastUpdated.value = data.data.last_updated ? new Date(data.data.last_updated) : null;
        } else {
            throw new Error(data.message || 'Failed to fetch statistics');
        }
    } catch (err) {
        console.error('Error fetching platform statistics:', err);
        error.value = err instanceof Error ? err.message : 'Failed to load statistics';

        // Fallback to mock data for development
        if (process.env.NODE_ENV === 'development') {
            statistics.value = getMockStatistics();
            lastUpdated.value = new Date();
        }
    } finally {
        loading.value = false;
    }
};

// Mock data for development/fallback
const getMockStatistics = (): PlatformStatistic[] => {
    const baseStats: PlatformStatistic[] = [
        {
            key: 'total_alumni',
            value: 25000,
            label: 'Alumni Connected',
            icon: 'users',
            animateOnScroll: true,
            format: 'number',
            suffix: '+',
        },
        {
            key: 'successful_connections',
            value: 45000,
            label: 'Successful Connections',
            icon: 'network',
            animateOnScroll: true,
            format: 'number',
            suffix: '+',
        },
        {
            key: 'job_placements',
            value: 3200,
            label: 'Job Placements',
            icon: 'briefcase',
            animateOnScroll: true,
            format: 'number',
            suffix: '+',
        },
        {
            key: 'average_salary_increase',
            value: 42,
            label: 'Average Salary Increase',
            icon: 'trending-up',
            animateOnScroll: true,
            format: 'percentage',
        },
    ];

    if (props.audience === 'institutional') {
        return [
            {
                key: 'institutions_served',
                value: 150,
                label: 'Institutions Served',
                icon: 'building',
                animateOnScroll: true,
                format: 'number',
                suffix: '+',
            },
            {
                key: 'branded_apps_deployed',
                value: 45,
                label: 'Branded Apps Deployed',
                icon: 'mobile',
                animateOnScroll: true,
                format: 'number',
            },
            {
                key: 'average_engagement_increase',
                value: 300,
                label: 'Average Engagement Increase',
                icon: 'trending-up',
                animateOnScroll: true,
                format: 'percentage',
            },
            {
                key: 'admin_satisfaction_rate',
                value: 96,
                label: 'Admin Satisfaction Rate',
                icon: 'star',
                animateOnScroll: true,
                format: 'percentage',
            },
        ];
    }

    return baseStats;
};

// Utility functions
const getIconSvg = (iconName: string): string => {
    const icons: Record<string, string> = {
        users: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>`,
        network: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M15 9H9v6h6V9zm-2 4h-2v-2h2v2zm8-2V9h-2V7c0-1.1-.9-2-2-2h-2V3h-2v2h-2V3H9v2H7c-1.1 0-2 .9-2 2v2H3v2h2v2H3v2h2v2c0 1.1.9 2 2 2h2v2h2v-2h2v2h2v-2h2c1.1 0 2-.9 2-2v-2h2v-2h-2v-2h2z"/></svg>`,
        briefcase: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M10 16V8a1 1 0 011-1h2a1 1 0 011 1v8a1 1 0 01-1 1h-2a1 1 0 01-1-1zM6 16V8a1 1 0 011-1h2a1 1 0 011 1v8a1 1 0 01-1 1H7a1 1 0 01-1-1zM14 16V8a1 1 0 011-1h2a1 1 0 011 1v8a1 1 0 01-1 1h-2a1 1 0 01-1-1z"/></svg>`,
        'trending-up': `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6h-6z"/></svg>`,
        building: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>`,
        mobile: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M17 2H7c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM7 4h10v12H7V4zm5 15c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>`,
        star: `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>`,
    };

    return icons[iconName] || icons.users;
};

const formatDate = (date: Date): string => {
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

// Lifecycle hooks
onMounted(() => {
    if (props.autoFetch) {
        fetchStatistics();
    }
});

onUnmounted(() => {
    stop();
});

// Expose methods for parent Components
defineExpose({
    fetchStatistics,
    refresh: fetchStatistics,
});
</script>

<style scoped>
.platform-statistics {
    @apply relative bg-gradient-to-br from-slate-50/80 via-blue-50/40 to-indigo-50/30 py-20 overflow-hidden;
    backdrop-filter: blur(8px);
}

/* Enhanced Animations */
.animate-fade-in-up {
    animation: fadeInUp 0.8s ease-out forwards;
    opacity: 0;
    transform: translateY(40px);
}

.animate-shimmer {
    background-size: 200% 200%;
    animation: shimmer 3s ease-in-out infinite;
}

.animate-glow {
    animation: glow 2s ease-in-out infinite alternate;
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-float-delayed {
    animation: float 8s ease-in-out infinite reverse;
}

.animate-pulse-slow {
    animation: pulseSlow 4s ease-in-out infinite;
}

/* Keyframe Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(40px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

@keyframes glow {
    from {
        text-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
    }
    to {
        text-shadow: 0 0 30px rgba(147, 51, 234, 0.4), 0 0 40px rgba(59, 130, 246, 0.2);
    }
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(-20px) rotate(5deg);
    }
}

@keyframes pulseSlow {
    0%, 100% {
        opacity: 0.2;
        transform: scale(1);
    }
    50% {
        opacity: 0.3;
        transform: scale(1.05);
    }
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Card Hover Effects */
.group:hover {
    transform: translateY(-8px) scale(1.02);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .platform-statistics {
        @apply py-16;
    }
    
    .animate-fade-in-up {
        transform: translateY(20px);
    }
}

@media (max-width: 640px) {
    .platform-statistics {
        @apply py-12;
    }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .animate-fade-in-up,
    .animate-shimmer,
    .animate-glow,
    .animate-float,
    .animate-float-delayed,
    .animate-pulse-slow,
    .animate-pulse {
        animation: none;
    }

    .group:hover {
        transform: none;
        transition: none;
    }
    
    .animate-fade-in-up {
        opacity: 1;
        transform: none;
    }
}

/* Enhanced backdrop blur support */
@supports (backdrop-filter: blur(10px)) {
    .backdrop-blur-sm {
        backdrop-filter: blur(4px);
    }
}

/* Improved focus states for accessibility */
.group:focus-within {
    @apply ring-4 ring-blue-500/20 ring-offset-2;
}

/* Custom scrollbar for better aesthetics */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    @apply bg-gray-100 rounded-full;
}

::-webkit-scrollbar-thumb {
    @apply bg-gradient-to-b from-blue-400 to-indigo-500 rounded-full;
}

::-webkit-scrollbar-thumb:hover {
    @apply from-blue-500 to-indigo-600;
}
</style>















