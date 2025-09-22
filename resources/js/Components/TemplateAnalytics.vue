<template>
    <div class="template-analytics">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Template Performance Analytics</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Real-time insights into template performance and conversion metrics</p>
            </div>

            <!-- Refresh Controls -->
            <div class="flex items-center gap-3">
                <button
                    @click="refreshData"
                    :disabled="isLoading"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    :aria-label="isLoading ? 'Refreshing data...' : 'Refresh analytics data'"
                >
                    <svg v-if="isLoading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A11.02 11.02 0 0115.346 4M4 20h.582M20.917 15.417A11.02 11.02 0 0119.17 18M11 9v6l4-2-4-2z"
                        ></path>
                    </svg>
                    {{ isLoading ? 'Refreshing...' : 'Refresh' }}
                </button>

                <div class="text-sm text-gray-600 dark:text-gray-400">Last updated: {{ lastUpdateTime }}</div>
            </div>
        </div>

        <!-- Key Metrics Cards -->
        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <MetricCard
                v-for="metric in keyMetrics"
                :key="metric.id"
                :title="metric.title"
                :value="metric.value"
                :change="metric.change"
                :trend="metric.trend"
                :icon="metric.icon"
                :color="metric.color"
                :format="metric.format"
            />
        </div>

        <!-- Main Analytics Grid -->
        <div class="mb-8 grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Conversion Trends Chart -->
            <div class="lg:col-span-2">
                <!-- ConversionChart placeholder until component is created -->
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Conversion Rate Trends</h3>
                    <div class="flex h-80 items-center justify-center text-gray-500 dark:text-gray-400">
                        <div class="text-center">
                            <svg class="mx-auto mb-4 h-16 w-16 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                ></path>
                            </svg>
                            <p class="text-sm">Chart will be displayed here</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Template Performance Rankings -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Template Performance Rankings</h3>

                <div v-if="templateRankings.length > 0" class="space-y-3">
                    <div
                        v-for="(template, index) in templateRankings.slice(0, 5)"
                        :key="template.id"
                        class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-700"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-medium text-white"
                            >
                                {{ index + 1 }}
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ template.name }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">{{ template.conversions }} conversions</div>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ template.conversionRate }}%</div>
                            <div
                                :class="template.change > 0 ? 'text-green-600' : template.change < 0 ? 'text-red-600' : 'text-gray-600'"
                                class="flex items-center gap-1 text-xs"
                            >
                                <svg v-if="template.change !== 0" class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        :d="template.change > 0 ? 'M7 17l9.2-9.2M17 17V7H7' : 'M17 7l-9.2 9.2M7 7v10h10'"
                                    ></path>
                                </svg>
                                {{ template.change > 0 ? '+' : '' }}{{ template.change }}%
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="py-8 text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto mb-4 h-12 w-12 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        ></path>
                    </svg>
                    No template performance data available
                </div>
            </div>
        </div>

        <!-- Template Recommendations -->
        <div class="mb-8">
            <!-- TemplateRecommendations placeholder until component is created -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Template Recommendations</h3>
                <div class="text-gray-500 dark:text-gray-400">
                    <p>Smart recommendations will be displayed here based on performance data.</p>
                </div>
            </div>
        </div>

        <!-- Detailed Performance Table -->
        <div class="overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detailed Performance Metrics</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Comprehensive breakdown of template performance by category and time period
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                Template
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                Category
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Views</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                Conversions
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                                7-Day Trend
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        <tr v-for="template in detailedPerformance" :key="template.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-600">
                                        <svg class="h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            ></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ template.name }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ template.audienceType }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                {{ template.category }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                {{ formatNumber(template.views) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                {{ formatNumber(template.conversions) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">{{ template.conversionRate }}%</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                <span
                                    :class="template.trend.value > 0 ? 'text-green-600' : template.trend.value < 0 ? 'text-red-600' : 'text-gray-600'"
                                    class="flex items-center gap-1"
                                >
                                    <svg v-if="template.trend.value !== 0" class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            :d="template.trend.value > 0 ? 'M7 17l9.2-9.2M17 17V7H7' : 'M17 7l-9.2 9.2M7 7v10h10'"
                                        ></path>
                                    </svg>
                                    {{ template.trend.value > 0 ? '+' : '' }}{{ template.trend.value }}%
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span :class="getStatusBadge(template.status)" class="inline-flex rounded-full px-2 py-1 text-xs font-medium">
                                    {{ template.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Real-time Update Status -->
        <div class="mt-6 text-center">
            <div class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-4 py-2 text-sm text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    ></path>
                </svg>
                Real-time updates connected • Next refresh in { { refreshCountdown }}s
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import MetricCard from '@/Components/Analytics/MetricCard.vue';
import { onMounted, onUnmounted, ref, watch } from 'vue';
// import ConversionChart from './ConversionChart.vue'
// import TemplateRecommendations from './TemplateRecommendations.vue'

// Types
interface TemplatePerformance {
    id: number;
    name: string;
    category: string;
    audienceType: string;
    views: number;
    conversions: number;
    conversionRate: number;
    change: number;
    trend: { value: number; direction: 'up' | 'down' | 'neutral' };
    status: 'Excellent' | 'Good' | 'Average' | 'Poor';
}

interface KeyMetric {
    id: string;
    title: string;
    value: string | number;
    change: string;
    trend: 'up' | 'down' | 'neutral';
    icon: string;
    color: 'blue' | 'green' | 'yellow' | 'red' | 'purple';
    format?: 'number' | 'currency' | 'percentage';
}

// Props
interface Props {
    tenantId?: number;
    refreshInterval?: number; // in seconds
}

const props = withDefaults(defineProps<Props>(), {
    tenantId: undefined,
    refreshInterval: 60,
});

const isLoading = ref(false);
const lastUpdateTime = ref('');
const refreshCountdown = ref(60);

const keyMetrics = ref<KeyMetric[]>([
    {
        id: 'total-templates',
        title: 'Total Templates',
        value: 145,
        change: '+12.5%',
        trend: 'up',
        icon: '🎯',
        color: 'blue' as const,
        format: 'number',
    },
    {
        id: 'total-views',
        title: 'Total Views',
        value: '2.3M',
        change: '+8.2%',
        trend: 'up',
        icon: '👁️',
        color: 'green' as const,
        format: 'number',
    },
    {
        id: 'conversion-rate',
        title: 'Avg Conversion Rate',
        value: '4.7%',
        change: '+1.3%',
        trend: 'up',
        icon: '📈',
        color: 'yellow' as const,
        format: 'percentage',
    },
    {
        id: 'active-campaigns',
        title: 'Active Campaigns',
        value: 23,
        change: '+2',
        trend: 'up',
        icon: '🚀',
        color: 'purple' as const,
        format: 'number',
    },
]);

const templateRankings = ref<TemplatePerformance[]>([
    {
        id: 1,
        name: 'Modern Business Hero',
        category: 'landing',
        audienceType: 'employer',
        views: 15000,
        conversions: 1200,
        conversionRate: 8.0,
        change: 2.3,
        trend: { value: 2.3, direction: 'up' },
        status: 'Excellent',
    },
    {
        id: 2,
        name: 'Student Welcome CTA',
        category: 'landing',
        audienceType: 'individual',
        views: 12000,
        conversions: 840,
        conversionRate: 7.0,
        change: -1.2,
        trend: { value: -1.2, direction: 'down' },
        status: 'Good',
    },
    {
        id: 3,
        name: 'Institution Overview',
        category: 'homepage',
        audienceType: 'institution',
        views: 18000,
        conversions: 1080,
        conversionRate: 6.0,
        change: 1.8,
        trend: { value: 1.8, direction: 'up' },
        status: 'Good',
    },
    {
        id: 4,
        name: 'Career Services Form',
        category: 'form',
        audienceType: 'individual',
        views: 8000,
        conversions: 320,
        conversionRate: 4.0,
        change: 0.5,
        trend: { value: 0.5, direction: 'up' },
        status: 'Average',
    },
    {
        id: 5,
        name: 'Network Directory',
        category: 'social',
        audienceType: 'general',
        views: 25000,
        conversions: 750,
        conversionRate: 3.0,
        change: -0.8,
        trend: { value: -0.8, direction: 'down' },
        status: 'Poor',
    },
]);

// Computed property for template details with conversion rates
const detailedPerformance = ref(
    templateRankings.value.map((template) => ({
        ...template,
        conversionRate: ((template.conversions / template.views) * 100).toFixed(1),
    })),
);

// Methods
const refreshData = async () => {
    isLoading.value = true;

    try {
        // Simulate API call for real-time data refresh
        await new Promise((resolve) => setTimeout(resolve, 1500));

        // Update last update time
        lastUpdateTime.value = new Date().toLocaleTimeString();

        // In a real app, this would make API calls to refresh data
        console.log('Refreshing template analytics data...');
    } catch (error) {
        console.error('Failed to refresh analytics data:', error);
    } finally {
        isLoading.value = false;
    }
};

const getStatusBadge = (status: string): string => {
    switch (status) {
        case 'Excellent':
            return 'bg-green-100 text-green-800';
        case 'Good':
            return 'bg-blue-100 text-blue-800';
        case 'Average':
            return 'bg-yellow-100 text-yellow-800';
        case 'Poor':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const formatNumber = (num: number): string => {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
};

// handleRealTimeUpdate function placeholder for future real-time functionality

let refreshTimer: number | null = null;
let countdownTimer: number | null = null;

const startRefreshTimer = () => {
    refreshTimer = window.setInterval(() => {
        if (!isLoading.value) {
            refreshData();
        }
    }, props.refreshInterval * 1000);
};

const startCountdownTimer = () => {
    countdownTimer = window.setInterval(() => {
        if (refreshCountdown.value > 0) {
            refreshCountdown.value--;
        } else {
            refreshCountdown.value = props.refreshInterval;
        }
    }, 1000);
};

// Lifecycle
onMounted(() => {
    lastUpdateTime.value = new Date().toLocaleTimeString();
    startRefreshTimer();
    startCountdownTimer();

    // Setup real-time updates (WebSocket or SSE)
    // This would be implemented based on your real-time architecture
});

onUnmounted(() => {
    if (refreshTimer) {
        clearInterval(refreshTimer);
    }
    if (countdownTimer) {
        clearInterval(countdownTimer);
    }
});

// Watch for tenant changes
watch(
    () => props.tenantId,
    (newTenantId) => {
        if (newTenantId) {
            refreshData();
        }
    },
);
</script>

<style scoped>
.template-analytics {
    @apply mx-auto max-w-7xl p-6;
}

/* Custom animations */
@keyframes pulse-subtle {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

.animate-pulse-subtle {
    animation: pulse-subtle 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Enhanced hover effects */
.table-row {
    @apply transition-colors duration-150;
}

.table-row:hover {
    @apply bg-gray-50 dark:bg-gray-700;
}

/* Responsive design improvements */
@media (max-width: 768px) {
    .template-analytics {
        @apply p-4;
    }

    .grid {
        @apply gap-4;
    }
}

/* Dark mode improvements */
.dark .badge-excellent {
    @apply bg-green-900 text-green-200;
}

.dark .badge-good {
    @apply bg-blue-900 text-blue-200;
}

.dark .badge-average {
    @apply bg-yellow-900 text-yellow-200;
}

.dark .badge-poor {
    @apply bg-red-900 text-red-200;
}
</style>
