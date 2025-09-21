<!-- ABOUTME: Error rate monitoring chart component using Chart.js for tracking system errors -->
<!-- ABOUTME: Displays error trends over time with different error types and severity levels -->
<template>
    <div class="error-rate-chart-container">
        <div class="chart-header">
            <h3 class="chart-title">Error Rate Trends</h3>
            <div class="chart-controls">
                <div class="error-type-filters">
                    <button
                        v-for="type in errorTypes"
                        :key="type.key"
                        @click="toggleErrorType(type.key)"
                        :class="['filter-button', activeErrorTypes.includes(type.key) ? 'active' : 'inactive']"
                    >
                        <span :class="['indicator', type.color]"></span>
                        {{ type.label }}
                    </button>
                </div>
                <select v-model="selectedTimeRange" @change="updateChart" class="time-range-selector">
                    <option value="1h">Last Hour</option>
                    <option value="24h">Last 24 Hours</option>
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                </select>
            </div>
        </div>

        <div class="chart-wrapper">
            <canvas ref="chartCanvas" :id="chartId" class="error-rate-chart"></canvas>
        </div>

        <div v-if="loading" class="chart-loading">
            <div class="loading-spinner"></div>
            <span>Loading error data...</span>
        </div>

        <div v-if="error" class="chart-error">
            <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                ></path>
            </svg>
            <span>{{ error }}</span>
        </div>

        <!-- Error Summary Stats -->
        <div class="error-summary">
            <div class="summary-item">
                <span class="summary-label">Total Errors</span>
                <span class="summary-value">{{ totalErrors }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Error Rate</span>
                <span class="summary-value">{{ errorRate }}%</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Avg Response Time</span>
                <span class="summary-value">{{ avgResponseTime }}ms</span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { BarElement, CategoryScale, Chart, Filler, Legend, LinearScale, LineElement, PointElement, Title, Tooltip } from 'chart.js';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

// Register Chart.js components
Chart.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler);

interface ErrorDataPoint {
    timestamp: string;
    total_errors: number;
    error_rate: number;
    http_4xx: number;
    http_5xx: number;
    database_errors: number;
    validation_errors: number;
    response_time: number;
}

interface Props {
    data?: ErrorDataPoint[];
    height?: number;
    refreshInterval?: number;
}

const props = withDefaults(defineProps<Props>(), {
    data: () => [],
    height: 350,
    refreshInterval: 30000, // 30 seconds
});

// Refs
const chartCanvas = ref<HTMLCanvasElement>();
const chart = ref<Chart | null>(null);
const loading = ref(false);
const error = ref('');
const selectedTimeRange = ref('24h');
const activeErrorTypes = ref(['total', '4xx', '5xx', 'database']);

// Error types configuration
const errorTypes = [
    { key: 'total', label: 'Total', color: 'bg-gray-500' },
    { key: '4xx', label: '4xx Errors', color: 'bg-yellow-500' },
    { key: '5xx', label: '5xx Errors', color: 'bg-red-500' },
    { key: 'database', label: 'Database', color: 'bg-purple-500' },
    { key: 'validation', label: 'Validation', color: 'bg-blue-500' },
];

// Computed
const chartId = computed(() => `error-rate-chart-${Math.random().toString(36).substr(2, 9)}`);

const isDarkMode = computed(() => {
    return document.documentElement.classList.contains('dark');
});

const totalErrors = computed(() => {
    if (!props.data || props.data.length === 0) return 0;
    return props.data[props.data.length - 1]?.total_errors || 0;
});

const errorRate = computed(() => {
    if (!props.data || props.data.length === 0) return 0;
    const latest = props.data[props.data.length - 1];
    return latest ? Number(latest.error_rate.toFixed(2)) : 0;
});

const avgResponseTime = computed(() => {
    if (!props.data || props.data.length === 0) return 0;
    const sum = props.data.reduce((acc, point) => acc + point.response_time, 0);
    return Math.round(sum / props.data.length);
});

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index' as const,
        intersect: false,
    },
    plugins: {
        title: {
            display: false,
        },
        legend: {
            position: 'top' as const,
            labels: {
                color: isDarkMode.value ? '#e5e7eb' : '#374151',
                usePointStyle: true,
                padding: 15,
                filter: function (legendItem: any) {
                    return (
                        activeErrorTypes.value.includes(legendItem.datasetIndex.toString()) ||
                        activeErrorTypes.value.includes(legendItem.text.toLowerCase().replace(/\s+/g, ''))
                    );
                },
            },
        },
        tooltip: {
            backgroundColor: isDarkMode.value ? '#1f2937' : '#ffffff',
            titleColor: isDarkMode.value ? '#f9fafb' : '#111827',
            bodyColor: isDarkMode.value ? '#e5e7eb' : '#374151',
            borderColor: isDarkMode.value ? '#374151' : '#e5e7eb',
            borderWidth: 1,
            cornerRadius: 8,
            displayColors: true,
            callbacks: {
                label: function (context: any) {
                    const label = context.dataset.label || '';
                    const value = context.parsed.y;

                    if (label.includes('Rate')) {
                        return `${label}: ${value}%`;
                    } else {
                        return `${label}: ${value} errors`;
                    }
                },
            },
        },
    },
    scales: {
        x: {
            display: true,
            title: {
                display: true,
                text: 'Time',
                color: isDarkMode.value ? '#9ca3af' : '#6b7280',
            },
            grid: {
                color: isDarkMode.value ? '#374151' : '#f3f4f6',
            },
            ticks: {
                color: isDarkMode.value ? '#9ca3af' : '#6b7280',
            },
        },
        y: {
            display: true,
            title: {
                display: true,
                text: 'Error Count',
                color: isDarkMode.value ? '#9ca3af' : '#6b7280',
            },
            grid: {
                color: isDarkMode.value ? '#374151' : '#f3f4f6',
            },
            ticks: {
                color: isDarkMode.value ? '#9ca3af' : '#6b7280',
            },
            beginAtZero: true,
        },
    },
    elements: {
        line: {
            tension: 0.4,
        },
        point: {
            radius: 3,
            hoverRadius: 6,
        },
    },
}));

const chartData = computed(() => {
    if (!props.data || props.data.length === 0) {
        return {
            labels: [],
            datasets: [],
        };
    }

    const labels = props.data.map((point) => {
        const date = new Date(point.timestamp);
        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
        });
    });

    const datasets = [];

    if (activeErrorTypes.value.includes('total')) {
        datasets.push({
            label: 'Total Errors',
            data: props.data.map((point) => point.total_errors),
            borderColor: '#6b7280',
            backgroundColor: 'rgba(107, 114, 128, 0.1)',
            fill: false,
        });
    }

    if (activeErrorTypes.value.includes('4xx')) {
        datasets.push({
            label: '4xx Errors',
            data: props.data.map((point) => point.http_4xx),
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            fill: false,
        });
    }

    if (activeErrorTypes.value.includes('5xx')) {
        datasets.push({
            label: '5xx Errors',
            data: props.data.map((point) => point.http_5xx),
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            fill: false,
        });
    }

    if (activeErrorTypes.value.includes('database')) {
        datasets.push({
            label: 'Database Errors',
            data: props.data.map((point) => point.database_errors),
            borderColor: '#8b5cf6',
            backgroundColor: 'rgba(139, 92, 246, 0.1)',
            fill: false,
        });
    }

    if (activeErrorTypes.value.includes('validation')) {
        datasets.push({
            label: 'Validation Errors',
            data: props.data.map((point) => point.validation_errors),
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: false,
        });
    }

    return {
        labels,
        datasets,
    };
});

// Methods
const toggleErrorType = (type: string) => {
    const index = activeErrorTypes.value.indexOf(type);
    if (index > -1) {
        activeErrorTypes.value.splice(index, 1);
    } else {
        activeErrorTypes.value.push(type);
    }
    updateChart();
};

const initChart = async () => {
    if (!chartCanvas.value) return;

    try {
        loading.value = true;
        error.value = '';

        // Destroy existing chart
        if (chart.value) {
            chart.value.destroy();
        }

        // Create new chart
        chart.value = new Chart(chartCanvas.value, {
            type: 'line',
            data: chartData.value,
            options: chartOptions.value,
        });
    } catch (err) {
        console.error('Error initializing chart:', err);
        error.value = 'Failed to initialize error rate chart';
    } finally {
        loading.value = false;
    }
};

const updateChart = async () => {
    if (!chart.value) {
        await initChart();
        return;
    }

    try {
        loading.value = true;
        error.value = '';

        // Update chart data
        chart.value.data = chartData.value;
        chart.value.options = chartOptions.value;
        chart.value.update('active');
    } catch (err) {
        console.error('Error updating chart:', err);
        error.value = 'Failed to update error rate chart';
    } finally {
        loading.value = false;
    }
};

const handleResize = () => {
    if (chart.value) {
        chart.value.resize();
    }
};

// Watchers
watch(
    () => props.data,
    () => {
        updateChart();
    },
    { deep: true },
);

watch(isDarkMode, () => {
    updateChart();
});

// Lifecycle
onMounted(async () => {
    await nextTick();
    await initChart();

    // Add resize listener
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    if (chart.value) {
        chart.value.destroy();
    }
    window.removeEventListener('resize', handleResize);
});
</script>

<style scoped>
.error-rate-chart-container {
    @apply rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800;
}

.chart-header {
    @apply mb-6 flex items-start justify-between;
}

.chart-title {
    @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.chart-controls {
    @apply flex flex-col items-end gap-3;
}

.error-type-filters {
    @apply flex flex-wrap gap-2;
}

.filter-button {
    @apply flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-medium;
    @apply border transition-all duration-200;
}

.filter-button.active {
    @apply border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20;
    @apply text-blue-700 dark:text-blue-300;
}

.filter-button.inactive {
    @apply border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700;
    @apply text-gray-600 dark:text-gray-400;
}

.indicator {
    @apply h-2 w-2 rounded-full;
}

.time-range-selector {
    @apply rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600;
    @apply bg-white text-gray-900 dark:bg-gray-700 dark:text-white;
    @apply focus:border-blue-500 focus:ring-2 focus:ring-blue-500;
    @apply transition-colors duration-200;
}

.chart-wrapper {
    @apply relative;
    height: 350px;
}

.error-rate-chart {
    @apply h-full w-full;
}

.chart-loading {
    @apply absolute inset-0 flex items-center justify-center;
    @apply bg-white bg-opacity-90 dark:bg-gray-800 dark:bg-opacity-90;
    @apply text-gray-600 dark:text-gray-400;
}

.loading-spinner {
    @apply h-6 w-6 rounded-full border-2 border-gray-300 border-t-blue-600;
    animation: spin 1s linear infinite;
}

.chart-error {
    @apply flex items-center justify-center gap-2 p-4;
    @apply bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400;
    @apply rounded-lg border border-red-200 dark:border-red-800;
}

.error-icon {
    @apply h-5 w-5;
}

.error-summary {
    @apply mt-6 flex items-center justify-around border-t border-gray-200 pt-6 dark:border-gray-700;
}

.summary-item {
    @apply text-center;
}

.summary-label {
    @apply block text-sm text-gray-600 dark:text-gray-400;
}

.summary-value {
    @apply mt-1 block text-lg font-semibold text-gray-900 dark:text-white;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .chart-header {
        @apply flex-col items-start gap-3;
    }

    .chart-controls {
        @apply items-start;
    }

    .error-type-filters {
        @apply w-full;
    }

    .chart-wrapper {
        height: 300px;
    }

    .error-summary {
        @apply flex-col gap-4;
    }
}

@media (max-width: 640px) {
    .error-rate-chart-container {
        @apply p-4;
    }

    .chart-wrapper {
        height: 250px;
    }
}
</style>
