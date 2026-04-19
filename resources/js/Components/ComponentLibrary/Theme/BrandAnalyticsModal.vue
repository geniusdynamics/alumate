<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

            <!-- Modal panel -->
            <div
                class="my-8 inline-block w-full max-w-4xl transform overflow-hidden rounded-lg bg-white p-6 text-left align-middle shadow-xl transition-all dark:bg-gray-800"
            >
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Brand Usage Analytics</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Insights into brand asset usage and compliance across Components</p>
                    </div>
                    <button @click="$emit('close')" class="btn-icon">
                        <Icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                <!-- Analytics Content -->
                <div class="space-y-6">
                    <!-- Overview Cards -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div class="metric-card">
                            <div class="metric-icon bg-blue-100 text-blue-600">
                                <Icon name="color-swatch" class="h-6 w-6" />
                            </div>
                            <div class="metric-content">
                                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ Object.keys(analyticsData.colorUsage).length }}
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Colors in Use</p>
                            </div>
                        </div>

                        <div class="metric-card">
                            <div class="metric-icon bg-green-100 text-green-600">
                                <Icon name="document-text" class="h-6 w-6" />
                            </div>
                            <div class="metric-content">
                                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ Object.keys(analyticsData.fontUsage).length }}
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Fonts in Use</p>
                            </div>
                        </div>

                        <div class="metric-card">
                            <div class="metric-icon bg-purple-100 text-purple-600">
                                <Icon name="template" class="h-6 w-6" />
                            </div>
                            <div class="metric-content">
                                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ Object.keys(analyticsData.templateUsage).length }}
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Active Templates</p>
                            </div>
                        </div>

                        <div class="metric-card">
                            <div class="metric-icon bg-yellow-100 text-yellow-600">
                                <Icon name="chart-bar" class="h-6 w-6" />
                            </div>
                            <div class="metric-content">
                                <h4 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ Math.round(analyticsData.complianceScore * 100) }}%
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Compliance Score</p>
                            </div>
                        </div>
                    </div>

                    <!-- Usage Charts -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Color Usage Chart -->
                        <div class="chart-container">
                            <h4 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">Color Usage Distribution</h4>
                            <div class="space-y-3">
                                <div v-for="(usage, colorName) in sortedColorUsage" :key="colorName" class="usage-bar-item">
                                    <div class="mb-1 flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ colorName }}
                                        </span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400"> {{ usage }} uses </span>
                                    </div>
                                    <div class="usage-bar">
                                        <div class="usage-bar-fill bg-blue-500" :style="{ width: `${(usage / maxColorUsage) * 100}%` }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Font Usage Chart -->
                        <div class="chart-container">
                            <h4 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">Font Usage Distribution</h4>
                            <div class="space-y-3">
                                <div v-for="(usage, fontName) in sortedFontUsage" :key="fontName" class="usage-bar-item">
                                    <div class="mb-1 flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ fontName }}
                                        </span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400"> {{ usage }} uses </span>
                                    </div>
                                    <div class="usage-bar">
                                        <div class="usage-bar-fill bg-green-500" :style="{ width: `${(usage / maxFontUsage) * 100}%` }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trends Chart -->
                    <div class="chart-container">
                        <h4 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">Compliance Trends (Last 30 Days)</h4>
                        <div class="trends-chart">
                            <svg viewBox="0 0 800 200" class="h-48 w-full">
                                <!-- Grid lines -->
                                <defs>
                                    <pattern id="grid" width="40" height="20" patternUnits="userSpaceOnUse">
                                        <path d="M 40 0 L 0 0 0 20" fill="none" stroke="#e5e7eb" stroke-width="1" />
                                    </pattern>
                                </defs>
                                <rect width="800" height="200" fill="url(#grid)" />

                                <!-- Trend line -->
                                <polyline :points="trendLinePoints" fill="none" stroke="#3b82f6" stroke-width="2" />

                                <!-- Data points -->
                                <circle
                                    v-for="(point, index) in trendPoints"
                                    :key="index"
                                    :cx="point.x"
                                    :cy="point.y"
                                    r="4"
                                    fill="#3b82f6"
                                    class="hover:r-6 transition-all duration-200"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Asset Usage Details -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Most Used Assets -->
                        <div class="asset-list">
                            <h4 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">Most Used Assets</h4>
                            <div class="space-y-3">
                                <div v-for="(usage, assetName) in topAssets" :key="assetName" class="asset-item">
                                    <div class="asset-info">
                                        <h5 class="font-medium text-gray-900 dark:text-white">
                                            {{ assetName }}
                                        </h5>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Used in {{ usage }} Components</p>
                                    </div>
                                    <div class="asset-usage">
                                        <div class="usage-indicator">
                                            <div class="usage-fill" :style="{ width: `${(usage / maxAssetUsage) * 100}%` }"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Unused Assets -->
                        <div class="asset-list">
                            <h4 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">Unused Assets</h4>
                            <div class="space-y-3">
                                <div v-for="asset in unusedAssets" :key="asset" class="asset-item">
                                    <div class="asset-info">
                                        <h5 class="font-medium text-gray-900 dark:text-white">
                                            {{ asset }}
                                        </h5>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Not currently in use</p>
                                    </div>
                                    <div class="asset-actions">
                                        <button @click="removeUnusedAsset(asset)" class="btn-sm btn-secondary">Remove</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-8 flex items-center justify-between border-t border-gray-200 pt-6 dark:border-gray-700">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Last updated: {{ formatDate(new Date()) }}</div>
                    <div class="flex gap-3">
                        <button @click="exportAnalytics" class="btn-secondary">
                            <Icon name="download" class="mr-2 h-4 w-4" />
                            Export Report
                        </button>
                        <button @click="refreshAnalytics" class="btn-primary">
                            <Icon name="refresh" class="mr-2 h-4 w-4" />
                            Refresh Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/Components/Common/Icon.vue';
import type { BrandAnalytics } from '@/types/Components';
import { computed } from 'vue';

interface Props {
    analyticsData: BrandAnalytics;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
    refresh: [];
    export: [];
    removeAsset: [assetName: string];
}>();

// Computed properties for sorted usage data
const sortedColorUsage = computed(() => {
    return Object.fromEntries(
        Object.entries(props.analyticsData.colorUsage)
            .sort(([, a], [, b]) => b - a)
            .slice(0, 10),
    );
});

const sortedFontUsage = computed(() => {
    return Object.fromEntries(
        Object.entries(props.analyticsData.fontUsage)
            .sort(([, a], [, b]) => b - a)
            .slice(0, 10),
    );
});

const maxColorUsage = computed(() => {
    return Math.max(...Object.values(props.analyticsData.colorUsage));
});

const maxFontUsage = computed(() => {
    return Math.max(...Object.values(props.analyticsData.fontUsage));
});

const topAssets = computed(() => {
    return Object.fromEntries(
        Object.entries(props.analyticsData.assetUsage)
            .sort(([, a], [, b]) => b - a)
            .slice(0, 5),
    );
});

const maxAssetUsage = computed(() => {
    return Math.max(...Object.values(props.analyticsData.assetUsage));
});

const unusedAssets = computed(() => {
    return Object.entries(props.analyticsData.assetUsage)
        .filter(([, usage]) => usage === 0)
        .map(([name]) => name)
        .slice(0, 5);
});

// Trend chart data
const trendPoints = computed(() => {
    const data = props.analyticsData.trendsData.filter((d) => d.metric === 'compliance');
    const maxValue = Math.max(...data.map((d) => d.value));

    return data.map((point, index) => ({
        x: (index / (data.length - 1)) * 760 + 20,
        y: 180 - (point.value / maxValue) * 160,
    }));
});

const trendLinePoints = computed(() => {
    return trendPoints.value.map((p) => `${p.x},${p.y}`).join(' ');
});

// Methods
const formatDate = (date: Date): string => {
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const refreshAnalytics = () => {
    emit('refresh');
};

const exportAnalytics = () => {
    emit('export');
};

const removeUnusedAsset = (assetName: string) => {
    if (confirm(`Are you sure you want to remove "${assetName}"?`)) {
        emit('removeAsset', assetName);
    }
};
</script>

<style scoped>
.btn-icon {
    @apply rounded-md p-2 text-gray-600 transition-colors duration-200 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white;
}

.btn-primary {
    @apply flex items-center rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors duration-200 hover:bg-blue-700;
}

.btn-secondary {
    @apply flex items-center rounded-md bg-gray-100 px-4 py-2 font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600;
}

.btn-sm {
    @apply rounded-md px-3 py-1.5 text-sm font-medium transition-colors duration-200;
}

.btn-sm.btn-secondary {
    @apply bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600;
}

.metric-card {
    @apply flex items-center gap-4 rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-600 dark:bg-gray-700;
}

.metric-icon {
    @apply flex h-12 w-12 items-center justify-center rounded-lg;
}

.metric-content {
    @apply flex-1;
}

.chart-container {
    @apply rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-600 dark:bg-gray-700;
}

.usage-bar-item {
    @apply space-y-1;
}

.usage-bar {
    @apply h-2 w-full rounded-full bg-gray-200 dark:bg-gray-600;
}

.usage-bar-fill {
    @apply h-2 rounded-full transition-all duration-300;
}

.trends-chart {
    @apply rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-600 dark:bg-gray-800;
}

.asset-list {
    @apply rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-600 dark:bg-gray-700;
}

.asset-item {
    @apply flex items-center justify-between rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-600 dark:bg-gray-800;
}

.asset-info {
    @apply min-w-0 flex-1;
}

.asset-usage {
    @apply ml-4 flex-shrink-0;
}

.usage-indicator {
    @apply h-2 w-20 rounded-full bg-gray-200 dark:bg-gray-600;
}

.usage-fill {
    @apply h-2 rounded-full bg-blue-500 transition-all duration-300;
}

.asset-actions {
    @apply ml-4 flex-shrink-0;
}
</style>















