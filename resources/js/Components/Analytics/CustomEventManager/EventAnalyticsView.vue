<template>
    <div class="event-analytics-view" role="region" aria-label="Custom event analytics dashboard">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Event Analytics</h2>
                <p class="text-sm text-gray-600">
                    {{ selectedEvent ? `Analytics for ${selectedEvent}` : 'Select an event to view analytics' }}
                </p>
            </div>
        </div>

        <div v-if="!selectedEvent" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No event selected</h3>
            <p class="mt-1 text-sm text-gray-500">Select an event from the list to view its analytics.</p>
        </div>

        <div v-else-if="isLoading" class="flex items-center justify-center py-12">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading analytics...</span>
        </div>

        <div v-else-if="analyticsData" class="space-y-6">
            <!-- Key Metrics -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Total Events</p>
                            <p class="text-2xl font-bold text-gray-900">{{ analyticsData.total_events.toLocaleString() }}</p>
                        </div>
                        <div class="rounded-full bg-blue-100 p-3">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Unique Users</p>
                            <p class="text-2xl font-bold text-gray-900">{{ analyticsData.unique_users.toLocaleString() }}</p>
                        </div>
                        <div class="rounded-full bg-green-100 p-3">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Avg per Session</p>
                            <p class="text-2xl font-bold text-gray-900">{{ analyticsData.avg_events_per_user }}</p>
                        </div>
                        <div class="rounded-full bg-yellow-100 p-3">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-600">Top Events</p>
                            <p class="text-2xl font-bold text-gray-900">{{ analyticsData.top_events.length }}</p>
                        </div>
                        <div class="rounded-full bg-purple-100 p-3">
                            <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Funnel Chart -->
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Conversion Funnel</h3>
                    <div class="h-64">
                        <canvas ref="funnelChartRef" aria-label="Conversion funnel chart" role="img"></canvas>
                    </div>
                </div>

                <!-- Time Distribution -->
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Event Distribution by Hour</h3>
                    <div class="h-64">
                        <canvas ref="timeChartRef" aria-label="Time distribution chart" role="img"></canvas>
                    </div>
                </div>
            </div>

            <!-- Correlations Table -->
            <div class="rounded-lg border border-gray-200 bg-white p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Event Correlations</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Event Pair
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Correlation
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Strength
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="correlation in analyticsData.correlations" :key="`${correlation.event1}-${correlation.event2}`">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ correlation.event1 }} → {{ correlation.event2 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ correlation.correlation.toFixed(3) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getCorrelationBadgeClass(correlation.correlation)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                        {{ getCorrelationStrength(correlation.correlation) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { Chart, registerables } from 'chart.js';
import type { CustomEventAnalytics } from '../../../Types/analytics';

// Register Chart.js components
Chart.register(...registerables);

// Props
const props = defineProps<{
    analyticsData: CustomEventAnalytics | null;
    selectedEvent: string | null;
}>();

// Reactive state
const isLoading = ref(false);
const funnelChartRef = ref<HTMLCanvasElement>();
const timeChartRef = ref<HTMLCanvasElement>();
let funnelChart: Chart | null = null;
let timeChart: Chart | null = null;

// Methods
const getCorrelationBadgeClass = (correlation: number) => {
    const abs = Math.abs(correlation);
    if (abs >= 0.7) return 'bg-red-100 text-red-800';
    if (abs >= 0.4) return 'bg-yellow-100 text-yellow-800';
    return 'bg-green-100 text-green-800';
};

const getCorrelationStrength = (correlation: number) => {
    const abs = Math.abs(correlation);
    if (abs >= 0.7) return 'Strong';
    if (abs >= 0.4) return 'Moderate';
    return 'Weak';
};

const updateCharts = () => {
    if (!props.analyticsData) return;

    updateFunnelChart();
    updateTimeChart();
};

const updateFunnelChart = () => {
    if (!funnelChartRef.value || !props.analyticsData) return;

    const ctx = funnelChartRef.value.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (funnelChart) {
        funnelChart.destroy();
    }

    const labels = props.analyticsData.funnel_data.map(step => step.event_name);
    const data = props.analyticsData.funnel_data.map(step => step.conversion_rate);

    funnelChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Conversion Rate (%)',
                data,
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top' as const,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: (value) => `${value}%`,
                    },
                },
            },
        },
    });
};

const updateTimeChart = () => {
    if (!timeChartRef.value || !props.analyticsData) return;

    const ctx = timeChartRef.value.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (timeChart) {
        timeChart.destroy();
    }

    const labels = props.analyticsData.time_distribution.map((_, index) => `${index}:00`);
    const data = props.analyticsData.time_distribution.map(item => item.count);

    timeChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Events',
                data,
                borderColor: 'rgba(16, 185, 129, 1)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top' as const,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                },
            },
        },
    });
};

// Watchers
watch(() => props.analyticsData, () => {
    updateCharts();
}, { deep: true });

// Lifecycle
onMounted(() => {
    updateCharts();
});
</script>

<style scoped>
.event-analytics-view {
    @apply w-full;
}
</style>