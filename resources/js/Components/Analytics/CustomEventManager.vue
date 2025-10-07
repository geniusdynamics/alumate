<template>
    <div class="custom-event-manager" role="region" aria-label="Custom event management dashboard">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center p-8">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading custom events...</span>
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
        <div v-else class="custom-event-manager-container">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Custom Event Manager</h1>
                    <p class="text-sm text-gray-600">
                        Define, track, and analyze custom events for advanced analytics
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        @click="trackSampleEvent"
                        class="rounded bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                        aria-label="Track sample custom event"
                    >
                        <svg class="mr-2 h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Track Sample Event
                    </button>
                    <button
                        @click="refreshData"
                        class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        :disabled="isLoading"
                        aria-label="Refresh custom event data"
                    >
                        <svg class="mr-2 h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-6">
                <nav class="flex space-x-1" aria-label="Tabs">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === tab.id
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                        :aria-current="activeTab === tab.id ? 'page' : undefined"
                    >
                        {{ tab.name }}
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- List View -->
                <div v-if="activeTab === 'list'" class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-900">Event Definitions</h2>
                        <button
                            @click="activeTab = 'create'"
                            class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
                        >
                            Create New Event
                        </button>
                    </div>

                    <div class="grid gap-4">
                        <div v-for="definition in definitions" :key="definition.id"
                             class="rounded-lg border border-gray-200 bg-white p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">{{ definition.name }}</h3>
                                    <p class="text-sm text-gray-600">{{ definition.description }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span v-for="param in definition.parameters_json"
                                              :key="param.name"
                                              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ param.name }}: {{ param.type }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click="viewAnalytics(definition.id)"
                                        class="text-blue-600 hover:text-blue-800 text-sm"
                                    >
                                        View Analytics
                                    </button>
                                    <button
                                        @click="editDefinition(definition)"
                                        class="text-gray-600 hover:text-gray-800 text-sm"
                                    >
                                        Edit
                                    </button>
                                </div>
                            </div>

                            <!-- Aggregates -->
                            <div v-if="definition.aggregates" class="mt-4 grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <div class="text-2xl font-bold text-blue-600">{{ definition.aggregates.total_events || 0 }}</div>
                                    <div class="text-sm text-gray-600">Total Events</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-green-600">{{ definition.aggregates.unique_users || 0 }}</div>
                                    <div class="text-sm text-gray-600">Unique Users</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-purple-600">{{ definition.aggregates.time_series?.length || 0 }}</div>
                                    <div class="text-sm text-gray-600">Data Points</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Create View -->
                <div v-if="activeTab === 'create'" class="max-w-2xl">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Create Event Definition</h2>
                    <form @submit.prevent="createDefinition" class="space-y-4">
                        <div>
                            <label for="event-name" class="block text-sm font-medium text-gray-700">Event Name</label>
                            <input
                                id="event-name"
                                v-model="newDefinition.name"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="e.g., purchase_completed"
                            />
                        </div>

                        <div>
                            <label for="event-description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea
                                id="event-description"
                                v-model="newDefinition.description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="Describe what this event tracks..."
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Parameters</label>
                            <div v-for="(param, index) in newDefinition.parameters_json" :key="index"
                                 class="flex items-center space-x-2 mb-2">
                                <input
                                    v-model="param.name"
                                    type="text"
                                    placeholder="Parameter name"
                                    required
                                    class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                />
                                <select
                                    v-model="param.type"
                                    required
                                    class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="string">String</option>
                                    <option value="number">Number</option>
                                    <option value="boolean">Boolean</option>
                                </select>
                                <button
                                    @click="removeParameter(index)"
                                    type="button"
                                    class="text-red-600 hover:text-red-800"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <button
                                @click="addParameter"
                                type="button"
                                class="mt-2 text-blue-600 hover:text-blue-800 text-sm"
                            >
                                + Add Parameter
                            </button>
                        </div>

                        <div class="flex justify-end space-x-2">
                            <button
                                @click="activeTab = 'list'"
                                type="button"
                                class="rounded bg-gray-600 px-4 py-2 text-sm text-white hover:bg-gray-700"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
                                :disabled="isSubmitting"
                            >
                                {{ isSubmitting ? 'Creating...' : 'Create Event' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Analytics View -->
                <div v-if="activeTab === 'analytics'" class="space-y-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-900">Event Analytics</h2>
                        <select
                            v-model="selectedDefinitionId"
                            @change="loadAnalytics"
                            class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        >
                            <option value="">Select Event Definition</option>
                            <option v-for="definition in definitions" :key="definition.id" :value="definition.id">
                                {{ definition.name }}
                            </option>
                        </select>
                    </div>

                    <div v-if="analyticsData" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Summary Cards -->
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Summary</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Events:</span>
                                    <span class="font-semibold">{{ analyticsData.total_events }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Unique Users:</span>
                                    <span class="font-semibold">{{ analyticsData.unique_users }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Aggregates Chart -->
                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Aggregates</h3>
                            <div class="h-64">
                                <canvas ref="aggregatesChartRef" aria-label="Event aggregates chart" role="img"></canvas>
                            </div>
                        </div>

                        <!-- Time Series -->
                        <div class="rounded-lg border border-gray-200 bg-white p-4 lg:col-span-2">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Event Timeline</h3>
                            <div class="h-64">
                                <canvas ref="timelineChartRef" aria-label="Event timeline chart" role="img"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Event Flow View -->
                <div v-if="activeTab === 'flow'" class="space-y-6">
                    <h2 class="text-xl font-semibold text-gray-900">Event Flow Visualization</h2>
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <p class="text-gray-600">Event flow visualization will be implemented here.</p>
                        <!-- Placeholder for Sankey diagram or flow chart -->
                        <div class="h-96 flex items-center justify-center bg-gray-50 rounded">
                            <span class="text-gray-500">Flow visualization coming soon</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';
import { useCustomEventStore } from '../../Stores/useCustomEventStore';
import { useCustomEvent } from '../../Composables/useCustomEvent';

// Register Chart.js components
Chart.register(...registerables);

// Props
const props = withDefaults(defineProps<{
    initialTab?: 'list' | 'create' | 'analytics' | 'flow';
}>(), {
    initialTab: 'list',
});

// Store and composable
const customEventStore = useCustomEventStore();
const { validateEventData } = useCustomEvent();

// Reactive state
const activeTab = ref<'list' | 'create' | 'analytics' | 'flow'>(props.initialTab);
const selectedDefinitionId = ref('');
const newDefinition = ref({
    name: '',
    description: '',
    parameters_json: [{ name: '', type: 'string' as 'string' | 'number' | 'boolean' }],
});
const isSubmitting = ref(false);

// Chart refs
const aggregatesChartRef = ref<HTMLCanvasElement>();
const timelineChartRef = ref<HTMLCanvasElement>();
let aggregatesChart: Chart | null = null;
let timelineChart: Chart | null = null;

// Tabs configuration
const tabs = [
    { id: 'list', name: 'Event Definitions' },
    { id: 'create', name: 'Create Event' },
    { id: 'analytics', name: 'Analytics' },
    { id: 'flow', name: 'Event Flow' },
];

// Computed properties
const isLoading = computed(() => customEventStore.isLoading);
const error = computed(() => customEventStore.error);
const definitions = computed(() => customEventStore.definitions);
const analyticsData = computed(() => customEventStore.analyticsData);

// Methods
const addParameter = () => {
    newDefinition.value.parameters_json.push({ name: '', type: 'string' });
};

const removeParameter = (index: number) => {
    if (newDefinition.value.parameters_json.length > 1) {
        newDefinition.value.parameters_json.splice(index, 1);
    }
};

const createDefinition = async () => {
    try {
        isSubmitting.value = true;

        // Validate parameters
        const validParams = newDefinition.value.parameters_json.filter(p => p.name.trim());
        if (validParams.length === 0) {
            throw new Error('At least one parameter is required');
        }

        await customEventStore.defineEvent({
            ...newDefinition.value,
            parameters_json: validParams,
        });

        // Reset form
        newDefinition.value = {
            name: '',
            description: '',
            parameters_json: [{ name: '', type: 'string' }],
        };

        activeTab.value = 'list';
        await refreshData();
    } catch (err) {
        console.error('Failed to create definition:', err);
    } finally {
        isSubmitting.value = false;
    }
};

const viewAnalytics = (definitionId: number) => {
    selectedDefinitionId.value = definitionId.toString();
    activeTab.value = 'analytics';
    loadAnalytics();
};

const editDefinition = (definition: any) => {
    newDefinition.value = {
        name: definition.name,
        description: definition.description,
        parameters_json: [...definition.parameters_json],
    };
    activeTab.value = 'create';
};

const loadAnalytics = async () => {
    if (selectedDefinitionId.value) {
        await customEventStore.loadAnalytics(parseInt(selectedDefinitionId.value));
        await nextTick();
        updateCharts();
    }
};

const trackSampleEvent = async () => {
    const sampleEvent = {
        definition_id: definitions.value[0]?.id || 1,
        user_id: 1,
        data_json: {
            sample_param: 'test_value',
            count: Math.floor(Math.random() * 100),
        },
    };

    try {
        await customEventStore.trackEvent(sampleEvent);
        await refreshData();
    } catch (err) {
        console.error('Failed to track sample event:', err);
    }
};

const updateCharts = () => {
    updateAggregatesChart();
    updateTimelineChart();
};

const updateAggregatesChart = () => {
    if (!aggregatesChartRef.value || !analyticsData.value?.aggregates) return;

    const ctx = aggregatesChartRef.value.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (aggregatesChart) {
        aggregatesChart.destroy();
    }

    const aggregates = analyticsData.value.aggregates;
    const labels = Object.keys(aggregates);
    const data = Object.values(aggregates).map((agg: any) => agg?.count || 0);

    aggregatesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Event Count',
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
                    display: false,
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

const updateTimelineChart = () => {
    if (!timelineChartRef.value || !analyticsData.value?.time_series) return;

    const ctx = timelineChartRef.value.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (timelineChart) {
        timelineChart.destroy();
    }

    const timeSeries = analyticsData.value.time_series;
    const labels = timeSeries.map((point: any) => point.date);
    const data = timeSeries.map((point: any) => point.count);

    timelineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Events per Day',
                data,
                borderColor: 'rgba(59, 130, 246, 1)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
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
        await customEventStore.loadDefinitions();
    } catch (err) {
        console.error('Failed to refresh data:', err);
    }
};

// Watchers
watch(() => customEventStore.definitions, () => {
    updateCharts();
}, { deep: true });

// Lifecycle
onMounted(async () => {
    await refreshData();
});
</script>

<style scoped>
.custom-event-manager {
    @apply w-full max-w-7xl mx-auto;
}

/* Custom focus styles for accessibility */
.custom-event-manager button:focus,
.custom-event-manager select:focus,
.custom-event-manager input:focus,
.custom-event-manager textarea:focus {
    @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Tab styles */
.tab-content {
    @apply min-h-96;
}

/* Chart responsive design */
@media (max-width: 768px) {
    .custom-event-manager-container {
        @apply px-2;
    }

    .grid-cols-1.lg\\:grid-cols-2 {
        @apply grid-cols-1;
    }
}
</style>