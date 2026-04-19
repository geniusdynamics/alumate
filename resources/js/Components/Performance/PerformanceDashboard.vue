<template>
    <div class="performance-dashboard">
        <!-- Header -->
        <div class="dashboard-header">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Performance Analytics</h2>
            <div class="flex items-center space-x-4">
                <select
                    v-model="selectedPeriod"
                    @change="loadAnalytics"
                    class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                >
                    <option value="1h">Last Hour</option>
                    <option value="24h">Last 24 Hours</option>
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                </select>
                <button @click="loadAnalytics" :disabled="loading" class="btn btn-primary">
                    <ArrowPathIcon class="mr-2 h-4 w-4" />
                    Refresh
                </button>

                <button @click="toggleMonitoring" :class="['btn ml-2', isMonitoring ? 'btn-danger' : 'btn-success']">
                    {{ isMonitoring ? 'Stop Monitoring' : 'Start Monitoring' }}
                </button>

                <button @click="exportReport" class="btn btn-secondary ml-2">Export Report</button>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
            <LoadingSpinner size="lg" />
        </div>

        <!-- Dashboard Content -->
        <div v-else class="space-y-6">
            <!-- Core Web Vitals -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Core Web Vitals</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-5">
                    <div
                        v-for="vital in coreWebVitals"
                        :key="vital.name"
                        class="rounded-lg p-4 text-center"
                        :class="getVitalStatusClass(vital.score)"
                    >
                        <div class="text-2xl font-bold">{{ formatMetricValue(vital.name, vital.p75_value) }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ vital.name }}</div>
                        <div class="mt-1 text-xs" :class="getScoreTextClass(vital.score)">
                            {{ vital.score.toUpperCase() }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics Chart -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Performance Trends</h3>
                <div class="h-64">
                    <canvas ref="performanceChart"></canvas>
                </div>
            </div>

            <!-- Performance Statistics -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Metrics Summary -->
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold">Metrics Summary</h3>
                    <div class="space-y-3">
                        <div
                            v-for="stat in performanceStats"
                            :key="stat.name"
                            class="flex items-center justify-between border-b border-gray-200 py-2 last:border-b-0 dark:border-gray-700"
                        >
                            <span class="font-medium">{{ stat.name }}</span>
                            <div class="text-right">
                                <div class="font-semibold">{{ formatMetricValue(stat.name, stat.avg_value) }}</div>
                                <div class="text-xs text-gray-500">avg ({{ stat.count }} samples)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Slow Pages -->
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold">Slowest Pages</h3>
                    <div class="space-y-3">
                        <div v-for="(metrics, url) in slowPages" :key="url" class="rounded-lg bg-gray-50 p-3 dark:bg-gray-700">
                            <div class="truncate text-sm font-medium" :title="url">
                                {{ getPageName(url) }}
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span
                                    v-for="metric in metrics"
                                    :key="metric.name"
                                    class="inline-flex items-center rounded-full px-2 py-1 text-xs"
                                    :class="getMetricBadgeClass(metric.name, metric.avg_value)"
                                >
                                    {{ metric.name }}: {{ formatMetricValue(metric.name, metric.avg_value) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Real-time Metrics -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Real-time Metrics</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div v-for="metric in realTimeMetrics" :key="metric.timestamp" class="rounded-lg bg-gray-50 p-3 dark:bg-gray-700">
                        <div class="font-semibold">{{ metric.name }}</div>
                        <div class="text-lg">{{ formatMetricValue(metric.name, metric.value) }}</div>
                        <div class="text-xs text-gray-500">{{ formatTimestamp(metric.timestamp) }}</div>
                    </div>
                </div>
            </div>

            <!-- Performance Recommendations -->
            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h3 class="mb-4 text-lg font-semibold">Performance Recommendations</h3>
                <div class="space-y-3">
                    <div
                        v-for="recommendation in recommendations"
                        :key="recommendation.id"
                        class="flex items-start space-x-3 rounded-lg p-3"
                        :class="getRecommendationClass(recommendation.priority)"
                    >
                        <component :is="getRecommendationIcon(recommendation.priority)" class="mt-0.5 h-5 w-5 flex-shrink-0" />
                        <div>
                            <div class="font-medium">{{ recommendation.title }}</div>
                            <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ recommendation.description }}
                            </div>
                            <div v-if="recommendation.impact" class="mt-1 text-xs text-gray-500">
                                Expected improvement: {{ recommendation.impact }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { usePerformanceMonitoring } from '@/Composables/usePerformanceMonitoring';
import { bundleAnalyzer } from '@/utils/bundle-analyzer';
import { ArrowPathIcon, CheckCircleIcon, ExclamationTriangleIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';
import { Chart, registerables } from 'chart.js';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import LoadingSpinner from '../LoadingSpinner.vue';

Chart.register(...registerables);

interface PerformanceMetric {
    name: string;
    value: number;
    timestamp: number;
    url: string;
}

interface CoreWebVital {
    name: string;
    avg_value: number;
    p75_value: number;
    p95_value: number;
    count: number;
    score: 'good' | 'needs-improvement' | 'poor';
}

interface PerformanceStat {
    name: string;
    count: number;
    avg_value: number;
    min_value: number;
    max_value: number;
    median_value: number;
    p95_value: number;
}

interface Recommendation {
    id: string;
    title: string;
    description: string;
    priority: 'high' | 'medium' | 'low';
    impact?: string;
}

const loading = ref(false);
const selectedPeriod = ref('24h');
const coreWebVitals = ref<CoreWebVital[]>([]);
const performanceStats = ref<PerformanceStat[]>([]);
const slowPages = ref<Record<string, any[]>>({});
const realTimeMetrics = ref<PerformanceMetric[]>([]);
const recommendations = ref<Recommendation[]>([]);
const performanceChart = ref<HTMLCanvasElement>();
const chartInstance = ref<Chart>();

// Enhanced performance monitoring
const {
    isMonitoring,
    currentSession,
    metrics,
    alerts,
    thresholds,
    startMonitoring,
    stopMonitoring,
    getAverageMetrics,
    getPerformanceScore,
    generateReport,
} = usePerformanceMonitoring('PerformanceDashboard');

// Computed properties
const averageMetrics = computed(() => getAverageMetrics());
const performanceScore = computed(() => getPerformanceScore());
const bundleInfo = computed(() => bundleAnalyzer.getAllBundles());
const isDevelopment = computed(() => process.env.NODE_ENV === 'development');

// Load analytics data
const loadAnalytics = async () => {
    loading.value = true;
    try {
        // Load Core Web Vitals
        const vitalsResponse = await fetch(`/api/performance/core-web-vitals?period=${selectedPeriod.value}`);
        const vitalsData = await vitalsResponse.json();

        if (vitalsData.success) {
            coreWebVitals.value = Object.entries(vitalsData.data.vitals).map(([name, data]: [string, any]) => ({
                name,
                ...data,
                score: vitalsData.data.scores[name] || 'good',
            }));
        }

        // Load performance analytics
        const analyticsResponse = await fetch(`/api/performance/analytics?period=${selectedPeriod.value}`);
        const analyticsData = await analyticsResponse.json();

        if (analyticsData.success) {
            performanceStats.value = analyticsData.data.statistics;
            slowPages.value = analyticsData.data.slowPages;
            updateChart(analyticsData.data.timeSeries);
        }

        // Load real-time metrics
        const realTimeResponse = await fetch('/api/performance/real-time');
        const realTimeData = await realTimeResponse.json();

        if (realTimeData.success) {
            realTimeMetrics.value = realTimeData.data.slice(-8); // Show last 8 metrics
        }

        // Generate recommendations
        generateRecommendations();
    } catch (error) {
        console.error('Failed to load performance analytics:', error);
    } finally {
        loading.value = false;
    }
};

// Update performance chart
const updateChart = (timeSeries: Record<string, any[]>) => {
    if (!performanceChart.value) return;

    const ctx = performanceChart.value.getContext('2d');
    if (!ctx) return;

    // Destroy existing chart
    if (chartInstance.value) {
        chartInstance.value.destroy();
    }

    // Prepare chart data
    const datasets = Object.entries(timeSeries).map(([metric, data], index) => ({
        label: metric,
        data: data.map((point: any) => ({
            x: point.hour,
            y: point.avg_value,
        })),
        borderColor: getChartColor(index),
        backgroundColor: getChartColor(index, 0.1),
        tension: 0.4,
    }));

    chartInstance.value = new Chart(ctx, {
        type: 'line',
        data: { datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'hour',
                    },
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Time (ms)',
                    },
                },
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                },
            },
        },
    });
};

// Generate performance recommendations
const generateRecommendations = () => {
    const recs: Recommendation[] = [];

    // Check Core Web Vitals
    coreWebVitals.value.forEach((vital) => {
        if (vital.score === 'poor') {
            recs.push({
                id: `cwv-${vital.name}`,
                title: `Improve ${vital.name}`,
                description: getVitalRecommendation(vital.name),
                priority: 'high',
                impact: 'High user experience improvement',
            });
        } else if (vital.score === 'needs-improvement') {
            recs.push({
                id: `cwv-${vital.name}`,
                title: `Optimize ${vital.name}`,
                description: getVitalRecommendation(vital.name),
                priority: 'medium',
                impact: 'Moderate user experience improvement',
            });
        }
    });

    // Check for slow API responses
    const slowApiMetrics = performanceStats.value.filter((stat) => stat.name === 'ApiRequest' && stat.avg_value > 2000);

    if (slowApiMetrics.length > 0) {
        recs.push({
            id: 'slow-api',
            title: 'Optimize API Response Times',
            description: 'Some API endpoints are responding slowly. Consider caching, database optimization, or CDN usage.',
            priority: 'high',
            impact: 'Faster page loads and better user experience',
        });
    }

    // Check for large resource loads
    const slowResources = performanceStats.value.filter((stat) => stat.name === 'ResourceLoad' && stat.avg_value > 1000);

    if (slowResources.length > 0) {
        recs.push({
            id: 'slow-resources',
            title: 'Optimize Resource Loading',
            description: 'Large resources are slowing down page loads. Consider compression, lazy loading, or CDN usage.',
            priority: 'medium',
            impact: 'Faster initial page loads',
        });
    }

    recommendations.value = recs;
};

// Utility functions
const formatMetricValue = (name: string, value: number): string => {
    if (name === 'CLS') {
        return value.toFixed(3);
    }
    return `${Math.round(value)}ms`;
};

const formatTimestamp = (timestamp: number): string => {
    return new Date(timestamp).toLocaleTimeString();
};

const getPageName = (url: string): string => {
    try {
        const urlObj = new URL(url);
        return urlObj.pathname || '/';
    } catch {
        return url;
    }
};

const getVitalStatusClass = (score: string): string => {
    switch (score) {
        case 'good':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'needs-improvement':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        case 'poor':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
};

const getScoreTextClass = (score: string): string => {
    switch (score) {
        case 'good':
            return 'text-green-600 dark:text-green-400';
        case 'needs-improvement':
            return 'text-yellow-600 dark:text-yellow-400';
        case 'poor':
            return 'text-red-600 dark:text-red-400';
        default:
            return 'text-gray-600 dark:text-gray-400';
    }
};

const getMetricBadgeClass = (name: string, value: number): string => {
    const thresholds = {
        LCP: 2500,
        FID: 100,
        CLS: 0.1,
        FCP: 1800,
        TTFB: 800,
    };

    const threshold = thresholds[name as keyof typeof thresholds];
    if (!threshold) return 'bg-gray-100 text-gray-800';

    if (value <= threshold) {
        return 'bg-green-100 text-green-800';
    } else if (value <= threshold * 1.5) {
        return 'bg-yellow-100 text-yellow-800';
    } else {
        return 'bg-red-100 text-red-800';
    }
};

const getRecommendationClass = (priority: string): string => {
    switch (priority) {
        case 'high':
            return 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800';
        case 'medium':
            return 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800';
        case 'low':
            return 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800';
        default:
            return 'bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600';
    }
};

const getRecommendationIcon = (priority: string) => {
    switch (priority) {
        case 'high':
            return ExclamationTriangleIcon;
        case 'medium':
            return InformationCircleIcon;
        case 'low':
            return CheckCircleIcon;
        default:
            return InformationCircleIcon;
    }
};

const getVitalRecommendation = (vital: string): string => {
    const recommendations = {
        LCP: 'Optimize images, remove unused CSS/JS, use CDN, and improve server response times.',
        FID: 'Reduce JavaScript execution time, remove non-critical third-party scripts, and use web workers.',
        CLS: 'Set size attributes on images and videos, avoid inserting content above existing content.',
        FCP: 'Eliminate render-blocking resources, minify CSS, and optimize web fonts.',
        TTFB: 'Optimize server configuration, use CDN, and implement caching strategies.',
    };

    return recommendations[vital as keyof typeof recommendations] || 'Optimize this metric for better performance.';
};

const getChartColor = (index: number, alpha: number = 1): string => {
    const colors = [
        `rgba(59, 130, 246, ${alpha})`, // blue
        `rgba(16, 185, 129, ${alpha})`, // green
        `rgba(245, 158, 11, ${alpha})`, // yellow
        `rgba(239, 68, 68, ${alpha})`, // red
        `rgba(139, 92, 246, ${alpha})`, // purple
    ];

    return colors[index % colors.length];
};

// Enhanced methods
const toggleMonitoring = () => {
    if (isMonitoring.value) {
        stopMonitoring();
    } else {
        startMonitoring();
    }
};

const exportReport = () => {
    const report = generateReport();
    const blob = new Blob([report], { type: 'text/markdown' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `performance-report-${new Date().toISOString().split('T')[0]}.md`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};

const clearAlerts = () => {
    alerts.value.splice(0, alerts.value.length);
};

const getScoreClass = (score: number): string => {
    if (score >= 90) return 'text-green-600 dark:text-green-400';
    if (score >= 70) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const formatTime = (time?: number): string => {
    if (!time) return '0ms';
    return `${Math.round(time)}ms`;
};

const formatSize = (size?: number): string => {
    if (!size) return '0B';
    const units = ['B', 'KB', 'MB', 'GB'];
    let unitIndex = 0;
    let value = size;

    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex++;
    }

    return `${value.toFixed(1)}${units[unitIndex]}`;
};

const formatDuration = (ms: number): string => {
    const seconds = Math.floor(ms / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);

    if (hours > 0) {
        return `${hours}h ${minutes % 60}m`;
    } else if (minutes > 0) {
        return `${minutes}m ${seconds % 60}s`;
    } else {
        return `${seconds}s`;
    }
};

// Auto-refresh real-time metrics
let refreshInterval: NodeJS.Timeout;

onMounted(() => {
    loadAnalytics();

    // Refresh real-time metrics every 30 seconds
    refreshInterval = setInterval(async () => {
        try {
            const response = await fetch('/api/performance/real-time');
            const data = await response.json();

            if (data.success) {
                realTimeMetrics.value = data.data.slice(-8);
            }
        } catch (error) {
            console.error('Failed to refresh real-time metrics:', error);
        }
    }, 30000);
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }

    if (chartInstance.value) {
        chartInstance.value.destroy();
    }
});
</script>

<style scoped>
.performance-dashboard {
    @apply space-y-6;
}

.dashboard-header {
    @apply mb-6 flex items-center justify-between;
}

.btn {
    @apply inline-flex items-center rounded-md border border-transparent px-4 py-2 text-sm font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.btn-primary {
    @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
}

.btn:disabled {
    @apply cursor-not-allowed opacity-50;
}

.btn-secondary {
    @apply border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-blue-500;
}

.btn-success {
    @apply bg-green-600 text-white hover:bg-green-700 focus:ring-green-500;
}

.btn-danger {
    @apply bg-red-600 text-white hover:bg-red-700 focus:ring-red-500;
}

.form-select {
    @apply block w-full border-gray-300 px-3 py-2 text-base focus:border-blue-500 focus:outline-none focus:ring-blue-500 sm:text-sm;
}

.performance-alerts {
    @apply rounded-lg border border-yellow-200 bg-yellow-50 p-4;
}

.alert {
    @apply rounded-lg p-4;
}

.alert-warning {
    @apply border border-yellow-200 bg-yellow-50 text-yellow-800;
}

.alert-header {
    @apply flex items-center justify-between;
}

.performance-score-card {
    @apply flex items-center space-x-6 rounded-lg bg-white p-6 shadow dark:bg-gray-800;
}

.score-circle {
    @apply text-center;
}

.score-value {
    @apply text-4xl font-bold;
}

.score-label {
    @apply mt-1 text-sm text-gray-600 dark:text-gray-400;
}

.score-breakdown {
    @apply grid flex-1 grid-cols-3 gap-4;
}

.score-item {
    @apply text-center;
}

.score-metric {
    @apply block text-sm font-medium text-gray-600 dark:text-gray-400;
}

.score-threshold {
    @apply block text-xs text-gray-500 dark:text-gray-500;
}

.metrics-grid {
    @apply grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-4;
}

.metric-card {
    @apply rounded-lg bg-white p-6 shadow dark:bg-gray-800;
}

.metric-header {
    @apply mb-4 flex items-center justify-between;
}

.metric-header h3 {
    @apply text-lg font-semibold;
}

.metric-status {
    @apply rounded-full px-2 py-1 text-xs font-medium;
}

.metric-content {
    @apply space-y-3;
}

.vital-metric,
.resource-metric,
.session-metric {
    @apply flex items-center justify-between;
}

.vital-label,
.resource-label,
.session-label {
    @apply text-sm font-medium text-gray-600 dark:text-gray-400;
}

.vital-value,
.resource-value,
.session-value {
    @apply text-sm font-semibold;
}

.vital-threshold {
    @apply text-xs text-gray-500;
}

.bundle-list {
    @apply space-y-2;
}

.bundle-item {
    @apply flex items-center justify-between text-sm;
}

.bundle-name {
    @apply font-medium;
}

.bundle-size,
.bundle-time {
    @apply text-gray-600 dark:text-gray-400;
}

.performance-timeline {
    @apply rounded-lg bg-white p-6 shadow dark:bg-gray-800;
}

.performance-timeline h3 {
    @apply mb-4 text-lg font-semibold;
}

.timeline-chart {
    @apply h-64;
}

.recommendations-section {
    @apply rounded-lg bg-white p-6 shadow dark:bg-gray-800;
}

.recommendations-section h3 {
    @apply mb-4 text-lg font-semibold;
}

.recommendations-list {
    @apply space-y-3;
}

.recommendation-item {
    @apply rounded-lg border p-4;
}

.recommendation-header {
    @apply mb-2 flex items-center justify-between;
}

.recommendation-title {
    @apply font-semibold;
}

.recommendation-impact {
    @apply text-sm text-gray-600 dark:text-gray-400;
}

.recommendation-description {
    @apply text-sm text-gray-700 dark:text-gray-300;
}

.raw-metrics-section {
    @apply rounded-lg bg-white p-6 shadow dark:bg-gray-800;
}

.raw-metrics-section h3 {
    @apply mb-4 text-lg font-semibold;
}

.raw-metrics-table {
    @apply overflow-x-auto;
}

.table {
    @apply min-w-full divide-y divide-gray-200 dark:divide-gray-700;
}

.table th {
    @apply px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400;
}

.table td {
    @apply whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100;
}
</style>















