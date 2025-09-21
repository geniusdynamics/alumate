<template>
    <div class="template-performance-dashboard">
        <!-- Header -->
        <div class="dashboard-header mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Template Performance Dashboard</h1>
                    <p class="mt-1 text-gray-600">Monitor and analyze your template performance metrics</p>
                </div>
                <div class="flex gap-3">
                    <button @click="refreshData" :disabled="loading" class="btn-secondary flex items-center gap-2">
                        <RefreshCwIcon :class="{ 'animate-spin': loading }" class="h-4 w-4" />
                        Refresh
                    </button>
                    <button @click="exportData" class="btn-primary flex items-center gap-2">
                        <DownloadIcon class="h-4 w-4" />
                        Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="dashboard-filters mb-6">
            <div class="rounded-lg bg-white p-4 shadow">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Date Range</label>
                        <select v-model="filters.dateRange" @change="updateFilters" class="form-select">
                            <option value="last_7_days">Last 7 days</option>
                            <option value="last_30_days">Last 30 days</option>
                            <option value="last_90_days">Last 90 days</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div v-if="filters.dateRange === 'custom'">
                        <label class="mb-1 block text-sm font-medium text-gray-700">From</label>
                        <input v-model="filters.dateFrom" type="date" @change="updateFilters" class="form-input" />
                    </div>
                    <div v-if="filters.dateRange === 'custom'">
                        <label class="mb-1 block text-sm font-medium text-gray-700">To</label>
                        <input v-model="filters.dateTo" type="date" @change="updateFilters" class="form-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Template</label>
                        <select v-model="filters.templateId" @change="updateFilters" class="form-select">
                            <option value="">All Templates</option>
                            <option v-for="template in templates" :key="template.id" :value="template.id">
                                {{ template.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
            <div class="flex items-center justify-center py-12">
                <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
                <span class="ml-2 text-gray-600">Loading dashboard data...</span>
            </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
            <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                <div class="flex">
                    <AlertCircleIcon class="h-5 w-5 text-red-400" />
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Error loading dashboard</h3>
                        <p class="mt-1 text-sm text-red-700">{{ error }}</p>
                        <button @click="refreshData" class="mt-2 text-sm text-red-600 hover:text-red-500">Try again</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div v-else class="dashboard-content">
            <!-- Summary Cards -->
            <div class="summary-cards mb-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <MetricCard
                        v-for="metric in summaryMetrics"
                        :key="metric.key"
                        :title="metric.title"
                        :value="metric.value"
                        :change="metric.change"
                        :change-type="metric.changeType"
                        :icon="metric.icon"
                    />
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-section mb-6">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Conversion Rate Trend -->
                    <div class="rounded-lg bg-white p-6 shadow">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">Conversion Rate Trend</h3>
                        <LineChart :data="trendData" :options="chartOptions" class="h-64" />
                    </div>

                    <!-- Template Performance -->
                    <div class="rounded-lg bg-white p-6 shadow">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">Template Performance</h3>
                        <BarChart :data="performanceData" :options="chartOptions" class="h-64" />
                    </div>
                </div>
            </div>

            <!-- Insights Section -->
            <div class="insights-section mb-6">
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Performance Insights</h3>
                    <div class="space-y-4">
                        <InsightCard v-for="insight in insights" :key="insight.id" :insight="insight" />
                    </div>
                </div>
            </div>

            <!-- Template Comparison -->
            <div class="comparison-section">
                <div class="rounded-lg bg-white p-6 shadow">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Template Comparison</h3>
                        <button @click="showComparisonModal = true" class="btn-secondary text-sm">Compare Templates</button>
                    </div>
                    <div v-if="comparisonData" class="comparison-table">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Template</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Usage Count</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Conversion Rate
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Performance Score
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="template in comparisonData.templates" :key="template.template.id">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                            {{ template.template.name }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {{ template.metrics.usage_count }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ template.metrics.conversion_rate }}%</td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                :class="getScoreBadgeClass(template.performance_score)"
                                            >
                                                {{ template.performance_score }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-gray-500">
                        <BarChart3Icon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No comparison data</h3>
                        <p class="mt-1 text-sm text-gray-500">Select templates to compare their performance.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comparison Modal -->
        <Modal v-if="showComparisonModal" @close="showComparisonModal = false" title="Compare Templates">
            <TemplateComparison @compare="handleTemplateComparison" @close="showComparisonModal = false" />
        </Modal>
    </div>
</template>

<script setup lang="ts">
import { AlertCircleIcon, BarChart3Icon, DownloadIcon, RefreshCwIcon } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

// Components
import Modal from '../ui/Modal.vue';
import BarChart from './Charts/BarChart.vue';
import LineChart from './Charts/LineChart.vue';
import InsightCard from './InsightCard.vue';
import MetricCard from './MetricCard.vue';
import TemplateComparison from './TemplateComparison.vue';

// Composables
import { useDashboardStore } from '../../stores/dashboard';

// Props
interface Props {
    tenantId?: number;
}

const props = withDefaults(defineProps<Props>(), {
    tenantId: 1,
});

// Reactive data
const loading = ref(false);
const error = ref('');
const showComparisonModal = ref(false);
const dashboardStore = useDashboardStore();

// Filters
const filters = ref({
    dateRange: 'last_30_days',
    dateFrom: '',
    dateTo: '',
    templateId: '',
});

// Computed properties
const summaryMetrics = computed(() => {
    const data = dashboardStore.overviewData?.summary;
    if (!data) return [];

    return [
        {
            key: 'total_templates',
            title: 'Total Templates',
            value: data.total_templates || 0,
            change: 0,
            changeType: 'neutral',
            icon: 'template',
        },
        {
            key: 'total_conversions',
            title: 'Total Conversions',
            value: data.total_conversions || 0,
            change: 0,
            changeType: 'neutral',
            icon: 'conversion',
        },
        {
            key: 'conversion_rate',
            title: 'Conversion Rate',
            value: `${data.conversion_rate || 0}%`,
            change: 0,
            changeType: 'neutral',
            icon: 'percentage',
        },
        {
            key: 'unique_users',
            title: 'Unique Users',
            value: data.unique_users || 0,
            change: 0,
            changeType: 'neutral',
            icon: 'users',
        },
    ];
});

const trendData = computed(() => {
    const trends = dashboardStore.overviewData?.trends || [];
    return {
        labels: trends.map((t) => t.date),
        datasets: [
            {
                label: 'Conversion Rate',
                data: trends.map((t) => t.conversions),
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
            },
        ],
    };
});

const performanceData = computed(() => {
    const performance = dashboardStore.overviewData?.performance || {};
    const labels = Object.keys(performance);
    const data = Object.values(performance).map((p: any) => p.performance_score || 0);

    return {
        labels,
        datasets: [
            {
                label: 'Performance Score',
                data,
                backgroundColor: '#10B981',
                borderColor: '#059669',
                borderWidth: 1,
            },
        ],
    };
});

const insights = computed(() => {
    return dashboardStore.overviewData?.insights || [];
});

const comparisonData = computed(() => {
    return dashboardStore.comparisonData;
});

const templates = computed(() => {
    return dashboardStore.templates;
});

const chartOptions = computed(() => ({
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
}));

// Methods
const refreshData = async () => {
    loading.value = true;
    error.value = '';

    try {
        await dashboardStore.fetchOverview(props.tenantId, filters.value);
    } catch (err: any) {
        error.value = err.message || 'Failed to load dashboard data';
    } finally {
        loading.value = false;
    }
};

const updateFilters = () => {
    refreshData();
};

const exportData = async () => {
    try {
        await dashboardStore.exportData(props.tenantId, 'json', filters.value);
    } catch (err: any) {
        error.value = err.message || 'Failed to export data';
    }
};

const handleTemplateComparison = async (templateIds: number[]) => {
    try {
        await dashboardStore.fetchComparison(templateIds, filters.value);
        showComparisonModal.value = false;
    } catch (err: any) {
        error.value = err.message || 'Failed to compare templates';
    }
};

const getScoreBadgeClass = (score: number) => {
    if (score >= 80) return 'bg-green-100 text-green-800';
    if (score >= 60) return 'bg-yellow-100 text-yellow-800';
    return 'bg-red-100 text-red-800';
};

// Lifecycle
onMounted(() => {
    refreshData();
});
</script>

<style scoped>
.template-performance-dashboard {
    @apply p-6;
}

.dashboard-header {
    @apply rounded-lg bg-white p-6 shadow;
}

.dashboard-filters {
    @apply rounded-lg bg-gray-50 p-4;
}

.btn-primary {
    @apply rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
}

.btn-secondary {
    @apply rounded-md bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2;
}

.form-select {
    @apply block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500;
}

.form-input {
    @apply block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500;
}

.loading-state {
    @apply rounded-lg bg-white p-6 shadow;
}

.error-state {
    @apply rounded-lg bg-white p-6 shadow;
}

.summary-cards {
    @apply grid gap-6;
}

.charts-section {
    @apply grid gap-6;
}

.insights-section {
    @apply rounded-lg bg-white shadow;
}

.comparison-section {
    @apply rounded-lg bg-white shadow;
}

.comparison-table {
    @apply overflow-x-auto;
}
</style>
