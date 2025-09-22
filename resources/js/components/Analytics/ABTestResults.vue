<template>
    <div class="ab-test-results">
        <!-- Header with Date Range Picker -->
        <div class="mb-6 flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">A/B Test Results</h3>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <label for="date-from" class="text-sm text-gray-700">From:</label>
                    <input
                        id="date-from"
                        v-model="dateRange.from"
                        type="date"
                        class="rounded border border-gray-300 px-3 py-1 text-sm focus:border-blue-500 focus:ring-blue-500"
                        aria-label="Start date"
                    />
                </div>
                <div class="flex items-center space-x-2">
                    <label for="date-to" class="text-sm text-gray-700">To:</label>
                    <input
                        id="date-to"
                        v-model="dateRange.to"
                        type="date"
                        class="rounded border border-gray-300 px-3 py-1 text-sm focus:border-blue-500 focus:ring-blue-500"
                        aria-label="End date"
                    />
                </div>
                <button
                    @click="refreshResults"
                    :disabled="isLoading"
                    class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    aria-label="Refresh results"
                >
                    <svg v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                    <svg v-else class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center py-12">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading results...</span>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
            <div class="flex items-center">
                <svg class="mr-2 h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"
                    />
                </svg>
                <span class="text-red-800">{{ error }}</span>
            </div>
        </div>

        <!-- Results Content -->
        <div v-else-if="results" class="space-y-6">
            <!-- Summary Stats -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-lg border bg-white p-4 shadow">
                    <div class="text-sm text-gray-600">Total Participants</div>
                    <div class="text-2xl font-bold text-gray-900">{{ results.total_participants.toLocaleString() }}</div>
                </div>
                <div class="rounded-lg border bg-white p-4 shadow">
                    <div class="text-sm text-gray-600">Total Conversions</div>
                    <div class="text-2xl font-bold text-gray-900">{{ results.total_conversions.toLocaleString() }}</div>
                </div>
                <div class="rounded-lg border bg-white p-4 shadow">
                    <div class="text-sm text-gray-600">Overall Conversion Rate</div>
                    <div class="text-2xl font-bold text-gray-900">
                        {{ ((results.total_conversions / results.total_participants) * 100).toFixed(2) }}%
                    </div>
                </div>
            </div>

            <!-- Significance Indicator -->
            <div class="rounded-lg border bg-white p-4 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-lg font-medium text-gray-900">Statistical Significance</h4>
                        <p class="mt-1 text-sm text-gray-600">p-value: {{ results.significance.p_value.toFixed(4) }}</p>
                    </div>
                    <div class="flex items-center">
                        <span
                            :class="results.significance.is_significant ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                            class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium"
                        >
                            <svg
                                :class="results.significance.is_significant ? 'text-green-400' : 'text-yellow-400'"
                                class="mr-2 h-4 w-4"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            {{ results.significance.is_significant ? 'Significant' : 'Not Significant' }}
                        </span>
                    </div>
                </div>
                <div v-if="results.significance.winner_variant" class="mt-2">
                    <span class="text-sm text-gray-600">Winner: </span>
                    <span class="font-medium text-green-600">{{ results.significance.winner_variant }}</span>
                </div>
            </div>

            <!-- Charts Container -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Conversion Rate Chart -->
                <div class="rounded-lg border bg-white p-4 shadow">
                    <h4 class="mb-4 text-lg font-medium text-gray-900">Conversion Rates by Variant</h4>
                    <div class="h-64">
                        <canvas ref="conversionChartRef" aria-label="Conversion rates chart"></canvas>
                    </div>
                </div>

                <!-- Participants Chart -->
                <div class="rounded-lg border bg-white p-4 shadow">
                    <h4 class="mb-4 text-lg font-medium text-gray-900">Participants by Variant</h4>
                    <div class="h-64">
                        <canvas ref="participantsChartRef" aria-label="Participants chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Detailed Results Table -->
            <div class="overflow-hidden bg-white shadow sm:rounded-md">
                <div class="px-4 py-5 sm:px-6">
                    <h4 class="text-lg font-medium text-gray-900">Detailed Results</h4>
                    <p class="mt-1 text-sm text-gray-600">Performance metrics for each variant</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" role="table" aria-label="Detailed results">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Variant</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Participants
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Conversions
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Conversion Rate
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Confidence Interval
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="variant in results.variants" :key="variant.variant_id" class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ variant.variant_name }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ variant.participants.toLocaleString() }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ variant.conversions.toLocaleString() }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ (variant.conversion_rate * 100).toFixed(2) }}%</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    <div v-if="variant.confidence_interval">
                                        {{ (variant.confidence_interval.lower * 100).toFixed(2) }}% -
                                        {{ (variant.confidence_interval.upper * 100).toFixed(2) }}%
                                    </div>
                                    <div v-else>N/A</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- No Results State -->
        <div v-else class="py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No results available</h3>
            <p class="mt-1 text-sm text-gray-500">Results will appear once the test has collected data.</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import Chart from 'chart.js/auto';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import type { ABTestApiResponse, ABTestResults, ABTestResultsProps } from '../../Types/analytics';

// Props
const props = withDefaults(defineProps<ABTestResultsProps>(), {
    dateRange: () => ({}),
});

// Emits
const emit = defineEmits<{
    close: [];
}>();

// Reactive data
const results = ref<ABTestResults | null>(null);
const isLoading = ref(false);
const error = ref<string | null>(null);
const dateRange = ref({
    from: props.dateRange.from || new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    to: props.dateRange.to || new Date().toISOString().split('T')[0],
});

// Chart refs and instances
const conversionChartRef = ref<HTMLCanvasElement>();
const participantsChartRef = ref<HTMLCanvasElement>();
let conversionChart: Chart | null = null;
let participantsChart: Chart | null = null;

// Methods
const fetchResults = async () => {
    if (!props.testId) return;

    isLoading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();
        if (dateRange.value.from) params.append('date_from', dateRange.value.from);
        if (dateRange.value.to) params.append('date_to', dateRange.value.to);

        const response = await axios.get<ABTestApiResponse>(`/api/ab-tests/${props.testId}/results?${params}`);
        results.value = response.data.data as ABTestResults;

        // Update charts after data is loaded
        nextTick(() => {
            updateCharts();
        });
    } catch (err) {
        error.value = err instanceof Error ? err.message : 'Failed to load test results';
        console.error('Results fetch error:', err);
    } finally {
        isLoading.value = false;
    }
};

const refreshResults = () => {
    fetchResults();
};

const updateCharts = () => {
    if (!results.value) return;

    // Destroy existing charts
    if (conversionChart) {
        conversionChart.destroy();
    }
    if (participantsChart) {
        participantsChart.destroy();
    }

    // Create conversion rate chart
    if (conversionChartRef.value) {
        const conversionCtx = conversionChartRef.value.getContext('2d');
        if (conversionCtx) {
            const variantNames = results.value.variants.map((v) => v.variant_name);
            const conversionRates = results.value.variants.map((v) => v.conversion_rate * 100);

            conversionChart = new Chart(conversionCtx, {
                type: 'bar',
                data: {
                    labels: variantNames,
                    datasets: [
                        {
                            label: 'Conversion Rate (%)',
                            data: conversionRates,
                            backgroundColor: 'rgba(59, 130, 246, 0.5)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => `${value}%`,
                            },
                        },
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                    },
                },
            });
        }
    }

    // Create participants chart
    if (participantsChartRef.value) {
        const participantsCtx = participantsChartRef.value.getContext('2d');
        if (participantsCtx) {
            const variantNames = results.value.variants.map((v) => v.variant_name);
            const participants = results.value.variants.map((v) => v.participants);

            participantsChart = new Chart(participantsCtx, {
                type: 'doughnut',
                data: {
                    labels: variantNames,
                    datasets: [
                        {
                            data: participants,
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(245, 158, 11, 0.8)',
                                'rgba(239, 68, 68, 0.8)',
                                'rgba(139, 92, 246, 0.8)',
                            ],
                            borderWidth: 1,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                    },
                },
            });
        }
    }
};

// Watchers
watch(
    () => props.testId,
    () => {
        if (props.testId) {
            fetchResults();
        }
    },
);

watch(
    dateRange,
    () => {
        fetchResults();
    },
    { deep: true },
);

// Lifecycle
onMounted(() => {
    if (props.testId) {
        fetchResults();
    }
});

onUnmounted(() => {
    if (conversionChart) {
        conversionChart.destroy();
    }
    if (participantsChart) {
        participantsChart.destroy();
    }
});
</script>

<style scoped>
.ab-test-results {
    @apply mx-auto w-full max-w-7xl;
}

/* Focus styles for accessibility */
input:focus,
button:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Chart container styling */
.h-64 {
    height: 16rem;
}
</style>
