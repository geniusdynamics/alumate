<template>
    <div class="attribution-visualizer" role="region" aria-label="Attribution analysis dashboard">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center p-8">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading attribution data...</span>
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

        <!-- Main Content -->
        <div v-else class="attribution-visualizer-container">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Attribution Analysis</h1>
                    <p class="text-sm text-gray-600">
                        Track user touchpoints and analyze conversion attribution across marketing channels
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        @click="trackSampleTouch"
                        class="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        aria-label="Track sample touchpoint"
                    >
                        <svg class="mr-2 h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Track Touch
                    </button>
                    <button
                        @click="refreshData"
                        class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        :disabled="isLoading"
                        aria-label="Refresh attribution data"
                    >
                        <svg class="mr-2 h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Filters Panel -->
            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Filters & Controls</h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <!-- User Selection -->
                    <div>
                        <label for="user-id" class="block text-sm font-medium text-gray-700">User ID</label>
                        <input
                            id="user-id"
                            v-model="filters.user_id"
                            type="text"
                            placeholder="Enter user ID"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Filter by user ID"
                        />
                    </div>

                    <!-- Source Filter -->
                    <div>
                        <label for="source" class="block text-sm font-medium text-gray-700">Source</label>
                        <select
                            id="source"
                            v-model="filters.source"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Filter by traffic source"
                        >
                            <option value="">All Sources</option>
                            <option value="google">Google</option>
                            <option value="facebook">Facebook</option>
                            <option value="twitter">Twitter</option>
                            <option value="linkedin">LinkedIn</option>
                            <option value="email">Email</option>
                            <option value="direct">Direct</option>
                        </select>
                    </div>

                    <!-- Attribution Model -->
                    <div>
                        <label for="model" class="block text-sm font-medium text-gray-700">Attribution Model</label>
                        <select
                            id="model"
                            v-model="selectedModel"
                            @change="handleModelChange"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Select attribution model"
                        >
                            <option value="last_touch">Last Touch</option>
                            <option value="first_touch">First Touch</option>
                            <option value="linear">Linear</option>
                            <option value="time_decay">Time Decay</option>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div>
                        <label for="date-range" class="block text-sm font-medium text-gray-700">Date Range</label>
                        <select
                            id="date-range"
                            v-model="selectedDateRange"
                            @change="handleDateRangeChange"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Select date range for analysis"
                        >
                            <option value="7d">Last 7 days</option>
                            <option value="30d">Last 30 days</option>
                            <option value="90d">Last 90 days</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Attribution Report -->
            <div v-if="hasReport" class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Attribution Report</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">${{ totalValue }}</div>
                        <div class="text-sm text-gray-600">Total Value</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ touchCount }}</div>
                        <div class="text-sm text-gray-600">Touchpoints</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ selectedModel.replace('_', ' ').toUpperCase() }}</div>
                        <div class="text-sm text-gray-600">Model</div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Attribution Sources Chart -->
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Attribution by Source</h3>
                    <div class="h-64">
                        <canvas ref="sourcesChartRef" aria-label="Attribution sources chart" role="img"></canvas>
                    </div>
                </div>

                <!-- Touch Timeline -->
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Touch Timeline</h3>
                    <div class="h-64">
                        <canvas ref="timelineChartRef" aria-label="Touch timeline chart" role="img"></canvas>
                    </div>
                </div>
            </div>

            <!-- Touchpoints List -->
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Recent Touchpoints</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" role="table" aria-label="Attribution touchpoints table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Timestamp
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Event Type
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Source
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Value
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Summary
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="touch in sortedTouches.slice(0, 10)" :key="touch.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ new Date(touch.timestamp).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ touch.event_type.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ touch.source || 'direct' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    ${{ touch.value }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ touch.touch_summary || 'N/A' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="sortedTouches.length > 10" class="mt-4 text-center">
                    <span class="text-sm text-gray-500">Showing 10 of {{ sortedTouches.length }} touchpoints</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';
import { useAttributionStore } from '../../Stores/useAttributionStore';
import { useAttribution } from '../../Composables/useAttribution';

// Register Chart.js components
Chart.register(...registerables);

// Props
const props = withDefaults(defineProps<{
    userId?: string;
    defaultModel?: 'last_touch' | 'first_touch' | 'linear' | 'time_decay';
}>(), {
    defaultModel: 'last_touch',
});

// Store and composable
const attributionStore = useAttributionStore();
const { getSourceColor, getModelDescription } = useAttribution();

// Reactive state
const selectedDateRange = ref('30d');
const selectedModel = ref<'last_touch' | 'first_touch' | 'linear' | 'time_decay'>(props.defaultModel);
const filters = ref({
    user_id: props.userId || '',
    source: '',
    start_date: '',
    end_date: '',
});

// Chart refs
const sourcesChartRef = ref<HTMLCanvasElement>();
const timelineChartRef = ref<HTMLCanvasElement>();
let sourcesChart: Chart | null = null;
let timelineChart: Chart | null = null;

// Computed properties
const isLoading = computed(() => attributionStore.isLoading);
const error = computed(() => attributionStore.error);
const hasReport = computed(() => attributionStore.hasReport);
const sortedTouches = computed(() => attributionStore.touches);
const totalValue = computed(() => attributionStore.attributionReport?.total_value || 0);
const touchCount = computed(() => attributionStore.attributionReport?.touch_count || 0);

// Methods
const handleDateRangeChange = () => {
    const dateRanges = {
        '7d': { start: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], end: new Date().toISOString().split('T')[0] },
        '30d': { start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], end: new Date().toISOString().split('T')[0] },
        '90d': { start: new Date(Date.now() - 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], end: new Date().toISOString().split('T')[0] },
    };

    const range = dateRanges[selectedDateRange.value as keyof typeof dateRanges];
    if (range) {
        filters.value.start_date = range.start;
        filters.value.end_date = range.end;
        attributionStore.setDateRange(range.start, range.end);
        refreshData();
    }
};

const handleModelChange = () => {
    attributionStore.setModel(selectedModel.value);
    refreshData();
};

const trackSampleTouch = async () => {
    const sampleTouch = {
        event_type: 'page_view' as const,
        source: 'google',
        medium: 'organic',
        campaign: 'sample_campaign',
        value: Math.floor(Math.random() * 100) + 1,
    };

    try {
        await attributionStore.trackTouch(sampleTouch);
        await refreshData();
    } catch (err) {
        console.error('Failed to track sample touch:', err);
    }
};

const updateCharts = () => {
    updateSourcesChart();
    updateTimelineChart();
};

const updateSourcesChart = () => {
    if (!sourcesChartRef.value) return;

    const ctx = sourcesChartRef.value.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (sourcesChart) {
        sourcesChart.destroy();
    }

    const sources = attributionStore.attributionReport?.sources || [];
    const labels = sources.map(s => s.name);
    const data = sources.map(s => s.percentage);

    sourcesChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: sources.map(s => getSourceColor(s.name)),
                borderWidth: 1,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom' as const,
                },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const source = sources[context.dataIndex];
                            return `${source.name}: ${source.percentage}% ($${source.value})`;
                        },
                    },
                },
            },
        },
    });
};

const updateTimelineChart = () => {
    if (!timelineChartRef.value) return;

    const ctx = timelineChartRef.value.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (timelineChart) {
        timelineChart.destroy();
    }

    const touches = attributionStore.touches.slice(0, 20); // Last 20 touches
    const labels = touches.map(t => new Date(t.timestamp).toLocaleDateString());
    const values = touches.map(t => t.value);

    timelineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Touch Value',
                data: values,
                borderColor: 'rgba(59, 130, 246, 1)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                pointBackgroundColor: touches.map(t => getSourceColor(t.source || 'direct')),
                pointBorderColor: touches.map(t => getSourceColor(t.source || 'direct')),
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const touch = touches[context.dataIndex];
                            return `${touch.event_type}: $${touch.value} from ${touch.source || 'direct'}`;
                        },
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => `$${value}`,
                    },
                },
            },
        },
    });
};

const refreshData = async () => {
    try {
        await attributionStore.refreshData();
        await nextTick();
        updateCharts();
    } catch (err) {
        console.error('Failed to refresh data:', err);
    }
};

// Watchers
watch(() => attributionStore.touches, () => {
    updateCharts();
}, { deep: true });

watch(() => attributionStore.attributionReport, () => {
    updateCharts();
}, { deep: true });

// Lifecycle
onMounted(async () => {
    handleDateRangeChange();
    await refreshData();
});
</script>

<style scoped>
.attribution-visualizer {
    @apply w-full max-w-7xl mx-auto;
}

/* Custom focus styles for accessibility */
.attribution-visualizer button:focus,
.attribution-visualizer select:focus,
.attribution-visualizer input:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Chart responsive design */
@media (max-width: 768px) {
    .attribution-visualizer-container {
        @apply px-2;
    }

    .grid-cols-1.md\\:grid-cols-4 {
        @apply grid-cols-1;
    }
}
</style>