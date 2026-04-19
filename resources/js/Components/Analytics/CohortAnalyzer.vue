<template>
    <div class="cohort-analyzer" role="region" aria-label="Cohort analysis dashboard">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center p-8">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading cohort data...</span>
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
        <div v-else class="cohort-analyzer-container">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Cohort Analysis</h1>
                    <p class="text-sm text-gray-600">
                        Analyze user behavior patterns and retention trends across different cohorts
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        @click="showCreateModal = true"
                        class="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        aria-label="Create new cohort"
                    >
                        <svg class="mr-2 h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Create Cohort
                    </button>
                    <button
                        @click="refreshData"
                        class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        :disabled="isLoading"
                        aria-label="Refresh cohort data"
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
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
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
                            <option value="custom">Custom range</option>
                        </select>
                    </div>

                    <!-- Metric Selection -->
                    <div>
                        <label for="metrics" class="block text-sm font-medium text-gray-700">Metrics</label>
                        <select
                            id="metrics"
                            v-model="selectedMetric"
                            @change="handleMetricChange"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            aria-label="Select metric to display"
                        >
                            <option value="retention">Retention</option>
                            <option value="engagement">Engagement</option>
                            <option value="conversion">Conversion</option>
                        </select>
                    </div>

                    <!-- Cohort Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cohorts</label>
                        <div class="mt-1 flex flex-wrap gap-2">
                            <button
                                v-for="cohort in availableCohorts"
                                :key="cohort.id"
                                @click="toggleCohort(cohort.id)"
                                :class="[
                                    'rounded px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                                    selectedCohorts.includes(cohort.id)
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                ]"
                                :aria-label="`Toggle ${cohort.name} cohort selection`"
                                :aria-pressed="selectedCohorts.includes(cohort.id)"
                            >
                                {{ cohort.name }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Comparison Mode Toggle -->
                <div class="mt-4 flex items-center">
                    <input
                        id="comparison-mode"
                        type="checkbox"
                        v-model="comparisonMode"
                        @change="handleComparisonModeChange"
                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        aria-label="Enable cohort comparison mode"
                    />
                    <label for="comparison-mode" class="ml-2 text-sm text-gray-700">
                        Enable comparison mode (select multiple cohorts)
                    </label>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Retention Trend Chart -->
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Retention Trends</h3>
                    <div class="h-64">
                        <canvas ref="retentionChartRef" aria-label="Retention trend chart" role="img"></canvas>
                    </div>
                </div>

                <!-- Engagement Chart -->
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Engagement Metrics</h3>
                    <div class="h-64">
                        <canvas ref="engagementChartRef" aria-label="Engagement metrics chart" role="img"></canvas>
                    </div>
                </div>
            </div>

            <!-- Comparison Table -->
            <div v-if="comparisonMode && selectedCohorts.length > 1" class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">Cohort Comparison</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" role="table" aria-label="Cohort comparison table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer hover:bg-gray-100"
                                    @click="sortTable('name')"
                                    :aria-sort="sortField === 'name' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                                >
                                    Cohort Name
                                    <span v-if="sortField === 'name'" class="ml-1">
                                        {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer hover:bg-gray-100"
                                    @click="sortTable('retention7d')"
                                    :aria-sort="sortField === 'retention7d' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                                >
                                    7-Day Retention
                                    <span v-if="sortField === 'retention7d'" class="ml-1">
                                        {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer hover:bg-gray-100"
                                    @click="sortTable('retention30d')"
                                    :aria-sort="sortField === 'retention30d' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                                >
                                    30-Day Retention
                                    <span v-if="sortField === 'retention30d'" class="ml-1">
                                        {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer hover:bg-gray-100"
                                    @click="sortTable('engagement')"
                                    :aria-sort="sortField === 'engagement' ? (sortDirection === 'asc' ? 'ascending' : 'descending') : 'none'"
                                >
                                    Engagement Score
                                    <span v-if="sortField === 'engagement'" class="ml-1">
                                        {{ sortDirection === 'asc' ? '↑' : '↓' }}
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="cohort in sortedCohorts" :key="cohort.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ cohort.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span :class="getVarianceClass(cohort.metrics.retention?.day7 || 0, 'retention7d')">
                                        {{ cohort.metrics.retention?.day7 || 0 }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span :class="getVarianceClass(cohort.metrics.retention?.day30 || 0, 'retention30d')">
                                        {{ cohort.metrics.retention?.day30 || 0 }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span :class="getVarianceClass(cohort.metrics.engagement?.score || 0, 'engagement')">
                                        {{ cohort.metrics.engagement?.score || 0 }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Insights Panel -->
            <div v-if="insights.length > 0" class="rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">AI Insights & Recommendations</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="insight in insights"
                        :key="insight.type + insight.message"
                        :class="[
                            'rounded-lg border p-4',
                            getInsightClass(insight.type)
                        ]"
                        role="alert"
                        :aria-live="insight.type === 'critical' ? 'assertive' : 'polite'"
                    >
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg :class="['h-5 w-5', getInsightIconClass(insight.type)]" fill="currentColor" viewBox="0 0 20 20">
                                    <path v-if="insight.type === 'positive'" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    <path v-else-if="insight.type === 'warning'" fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    <path v-else fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <h4 class="text-sm font-medium">{{ getInsightTitle(insight.type) }}</h4>
                                <p class="mt-1 text-sm">{{ insight.message }}</p>
                                <p class="mt-2 text-sm font-medium text-blue-600">{{ insight.recommendation }}</p>
                                <div class="mt-2">
                                    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getImpactClass(insight.impact)]">
                                        {{ insight.impact }} impact
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Cohort Modal -->
        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    @click="closeCreateModal"
                    aria-hidden="true"
                ></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Create New Cohort
                                </h3>
                                <div class="mt-4">
                                    <form @submit.prevent="createCohort" class="space-y-4">
                                        <!-- Cohort Name -->
                                        <div>
                                            <label for="cohort-name" class="block text-sm font-medium text-gray-700">
                                                Cohort Name
                                            </label>
                                            <input
                                                id="cohort-name"
                                                v-model="newCohort.name"
                                                type="text"
                                                required
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="e.g., 2023 Computer Science Graduates"
                                            />
                                        </div>

                                        <!-- Criteria: Graduation Year -->
                                        <div>
                                            <label for="grad-year" class="block text-sm font-medium text-gray-700">
                                                Graduation Year
                                            </label>
                                            <input
                                                id="grad-year"
                                                v-model="newCohort.criteria.grad_year"
                                                type="number"
                                                min="2000"
                                                :max="new Date().getFullYear() + 4"
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="e.g., 2023"
                                            />
                                        </div>

                                        <!-- Criteria: Degree -->
                                        <div>
                                            <label for="degree" class="block text-sm font-medium text-gray-700">
                                                Degree
                                            </label>
                                            <input
                                                id="degree"
                                                v-model="newCohort.criteria.degree"
                                                type="text"
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="e.g., Computer Science"
                                            />
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            type="button"
                            @click="createCohort"
                            :disabled="!canCreateCohort"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Create Cohort
                        </button>
                        <button
                            type="button"
                            @click="closeCreateModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';
import { useCohortStore } from '../../Stores/useCohortStore';
import type { CohortInsight } from '../../types/analytics';

// Register Chart.js components
Chart.register(...registerables);

// Props
const props = withDefaults(defineProps<{
    initialCohortIds?: string[];
    defaultMetrics?: string[];
}>(), {
    initialCohortIds: () => [],
    defaultMetrics: () => ['retention', 'engagement'],
});

// Store
const cohortStore = useCohortStore();

// Reactive state
const selectedDateRange = ref('30d');
const selectedMetric = ref('retention');
const comparisonMode = ref(false);
const sortField = ref('name');
const sortDirection = ref<'asc' | 'desc'>('asc');
const showCreateModal = ref(false);
const newCohort = ref({
    name: '',
    criteria: {
        grad_year: '',
        degree: ''
    }
});

// Chart refs
const retentionChartRef = ref<HTMLCanvasElement>();
const engagementChartRef = ref<HTMLCanvasElement>();
let retentionChart: Chart | null = null;
let engagementChart: Chart | null = null;

// Computed properties
const isLoading = computed(() => cohortStore.isLoading);
const error = computed(() => cohortStore.error);
const availableCohorts = computed(() => cohortStore.availableCohorts);
const selectedCohorts = computed(() => cohortStore.filters.cohortIds);
const currentData = computed(() => {
    if (comparisonMode.value && selectedCohorts.value.length > 1) {
        return cohortStore.comparisonData?.cohorts || [];
    }
    return cohortStore.cohortData ? [cohortStore.cohortData] : [];
});
const insights = computed(() => {
    const allInsights: CohortInsight[] = [];
    currentData.value.forEach(cohort => {
        if (cohort.insights) {
            allInsights.push(...cohort.insights);
        }
    });
    return allInsights;
});
const sortedCohorts = computed(() => {
    if (!comparisonMode.value) return currentData.value;

    return [...currentData.value].sort((a, b) => {
        let aValue: any, bValue: any;

        switch (sortField.value) {
            case 'name':
                aValue = a.name.toLowerCase();
                bValue = b.name.toLowerCase();
                break;
            case 'retention7d':
                aValue = a.metrics.retention?.day7 || 0;
                bValue = b.metrics.retention?.day7 || 0;
                break;
            case 'retention30d':
                aValue = a.metrics.retention?.day30 || 0;
                bValue = b.metrics.retention?.day30 || 0;
                break;
            case 'engagement':
                aValue = a.metrics.engagement?.score || 0;
                bValue = b.metrics.engagement?.score || 0;
                break;
            default:
                return 0;
        }

        if (sortDirection.value === 'asc') {
            return aValue > bValue ? 1 : -1;
        } else {
            return aValue < bValue ? 1 : -1;
        }
    });
});

const canCreateCohort = computed(() => {
    return newCohort.value.name.trim() &&
           (newCohort.value.criteria.grad_year || newCohort.value.criteria.degree);
});

// Methods
const handleDateRangeChange = () => {
    // Update date range in store
    const dateRanges = {
        '7d': { from: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], to: new Date().toISOString().split('T')[0] },
        '30d': { from: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], to: new Date().toISOString().split('T')[0] },
        '90d': { from: new Date(Date.now() - 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], to: new Date().toISOString().split('T')[0] },
    };

    if (dateRanges[selectedDateRange.value as keyof typeof dateRanges]) {
        cohortStore.updateFilters({
            dateRange: dateRanges[selectedDateRange.value as keyof typeof dateRanges]
        });
    }
    refreshData();
};

const handleMetricChange = () => {
    updateCharts();
};

const toggleCohort = (cohortId: string) => {
    cohortStore.toggleCohortSelection(cohortId);
    refreshData();
};

const handleComparisonModeChange = () => {
    cohortStore.setComparisonMode(comparisonMode.value);
    if (comparisonMode.value && selectedCohorts.value.length < 2) {
        // Auto-select first two cohorts if available
        const availableIds = availableCohorts.value.slice(0, 2).map(c => c.id);
        cohortStore.updateFilters({ cohortIds: availableIds });
    }
    refreshData();
};

const sortTable = (field: string) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

const getVarianceClass = (value: number, field: string) => {
    if (!comparisonMode.value || currentData.value.length < 2) return '';

    const values = currentData.value.map(cohort => {
        switch (field) {
            case 'retention7d': return cohort.metrics.retention?.day7 || 0;
            case 'retention30d': return cohort.metrics.retention?.day30 || 0;
            case 'engagement': return cohort.metrics.engagement?.score || 0;
            default: return 0;
        }
    });

    const avg = values.reduce((a, b) => a + b, 0) / values.length;
    const diff = ((value - avg) / avg) * 100;

    if (Math.abs(diff) > 10) {
        return diff > 0 ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold';
    }
    return '';
};

const getInsightClass = (type: string) => {
    switch (type) {
        case 'positive': return 'border-green-200 bg-green-50';
        case 'warning': return 'border-yellow-200 bg-yellow-50';
        case 'critical': return 'border-red-200 bg-red-50';
        default: return 'border-gray-200 bg-gray-50';
    }
};

const getInsightIconClass = (type: string) => {
    switch (type) {
        case 'positive': return 'text-green-400';
        case 'warning': return 'text-yellow-400';
        case 'critical': return 'text-red-400';
        default: return 'text-gray-400';
    }
};

const getInsightTitle = (type: string) => {
    switch (type) {
        case 'positive': return 'Positive Insight';
        case 'warning': return 'Warning';
        case 'critical': return 'Critical Issue';
        default: return 'Insight';
    }
};

const getImpactClass = (impact: string) => {
    switch (impact) {
        case 'high': return 'bg-red-100 text-red-800';
        case 'medium': return 'bg-yellow-100 text-yellow-800';
        case 'low': return 'bg-green-100 text-green-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const updateCharts = () => {
    if (!currentData.value.length) return;

    updateRetentionChart();
    updateEngagementChart();
};

const updateRetentionChart = () => {
    if (!retentionChartRef.value) return;

    const ctx = retentionChartRef.value.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (retentionChart) {
        retentionChart.destroy();
    }

    const datasets = currentData.value.map((cohort, index) => ({
        label: cohort.name,
        data: cohort.metrics.retention?.trend || [],
        borderColor: `hsl(${(index * 360) / currentData.value.length}, 70%, 50%)`,
        backgroundColor: `hsl(${(index * 360) / currentData.value.length}, 70%, 50%, 0.1)`,
        tension: 0.4,
    }));

    const labels = Array.from({ length: 30 }, (_, i) => `Day ${i + 1}`);

    retentionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top' as const,
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
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

const updateEngagementChart = () => {
    if (!engagementChartRef.value) return;

    const ctx = engagementChartRef.value.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (engagementChart) {
        engagementChart.destroy();
    }

    const labels = currentData.value.map(cohort => cohort.name);
    const engagementScores = currentData.value.map(cohort => cohort.metrics.engagement?.score || 0);
    const sessionsPerWeek = currentData.value.map(cohort => cohort.metrics.engagement?.sessionsPerWeek || 0);
    const pagesPerSession = currentData.value.map(cohort => cohort.metrics.engagement?.pagesPerSession || 0);

    engagementChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Engagement Score',
                    data: engagementScores,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                },
                {
                    label: 'Sessions per Week',
                    data: sessionsPerWeek,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1,
                },
                {
                    label: 'Pages per Session',
                    data: pagesPerSession,
                    backgroundColor: 'rgba(245, 158, 11, 0.8)',
                    borderColor: 'rgba(245, 158, 11, 1)',
                    borderWidth: 1,
                },
            ],
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

const refreshData = async () => {
    try {
        if (comparisonMode.value && selectedCohorts.value.length > 1) {
            await cohortStore.fetchComparison(selectedCohorts.value);
        } else if (selectedCohorts.value.length === 1) {
            await cohortStore.fetchCohort(selectedCohorts.value[0]);
        }

        await nextTick();
        updateCharts();
    } catch (err) {
        console.error('Failed to refresh data:', err);
    }
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    newCohort.value = {
        name: '',
        criteria: {
            grad_year: '',
            degree: ''
        }
    };
};

const createCohort = async () => {
    try {
        const cohortData = {
            name: newCohort.value.name,
            criteria: {
                ...(newCohort.value.criteria.grad_year && { grad_year: newCohort.value.criteria.grad_year }),
                ...(newCohort.value.criteria.degree && { degree: newCohort.value.criteria.degree }),
            }
        };

        await cohortStore.createCohort(cohortData);
        closeCreateModal();
        refreshData();
    } catch (err) {
        console.error('Failed to create cohort:', err);
    }
};

// Watchers
watch(() => cohortStore.availableCohorts, (newCohorts) => {
    if (newCohorts.length > 0 && selectedCohorts.value.length === 0) {
        // Auto-select first cohort if none selected
        const firstCohortId = newCohorts[0].id;
        cohortStore.toggleCohortSelection(firstCohortId);
        refreshData();
    }
}, { immediate: true });

watch(() => currentData.value, () => {
    updateCharts();
}, { deep: true });

// Lifecycle
onMounted(async () => {
    await cohortStore.initialize();

    // Set initial selections based on props
    if (props.initialCohortIds.length > 0) {
        cohortStore.updateFilters({ cohortIds: props.initialCohortIds });
    }

    if (props.defaultMetrics.length > 0) {
        cohortStore.updateFilters({ metrics: props.defaultMetrics });
    }

    handleDateRangeChange();
});
</script>

<style scoped>
.cohort-analyzer {
    @apply w-full max-w-7xl mx-auto;
}

/* Custom focus styles for accessibility */
.cohort-analyzer button:focus,
.cohort-analyzer select:focus,
.cohort-analyzer input:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Table sorting indicators */
.sort-indicator {
    @apply ml-1 inline-block transition-transform duration-200;
}

.sort-indicator.asc {
    @apply rotate-0;
}

.sort-indicator.desc {
    @apply rotate-180;
}

/* Chart responsive design */
@media (max-width: 768px) {
    .cohort-analyzer-container {
        @apply px-2;
    }

    .grid-cols-1.md\\:grid-cols-3 {
        @apply grid-cols-1;
    }
}
</style>