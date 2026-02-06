<template>
    <div class="institutional-before-after-comparison overflow-hidden rounded-lg bg-white shadow-lg">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white">
            <h3 class="mb-2 text-xl font-bold">{{ title }}</h3>
            <p class="text-blue-100">{{ subtitle }}</p>
        </div>

        <!-- Comparison Content -->
        <div class="p-6">
            <!-- Institution Info -->
            <div class="mb-6 flex items-center">
                <img
                    v-if="institutionLogo"
                    :src="institutionLogo"
                    :alt="`${institutionName} logo`"
                    class="mr-4 h-12 w-12 rounded-lg border border-gray-200 object-contain"
                />
                <div>
                    <h4 class="font-semibold text-gray-900">{{ institutionName }}</h4>
                    <p class="text-sm capitalize text-gray-600">{{ institutionType }}</p>
                    <p class="text-xs text-gray-500">{{ formatAlumniCount(alumniCount) }} Alumni</p>
                </div>
            </div>

            <!-- Before/After Grid -->
            <div class="mb-6 grid gap-8 md:grid-cols-2">
                <!-- Before Section -->
                <div class="before-section">
                    <div class="mb-4 flex items-center">
                        <div class="mr-3 flex h-8 w-8 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-4 w-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900">Before Implementation</h4>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="metric in beforeMetrics"
                            :key="metric.key"
                            class="flex items-center justify-between rounded-lg border border-red-200 bg-red-50 p-3"
                        >
                            <span class="text-sm font-medium text-gray-700">{{ metric.label }}</span>
                            <span class="text-lg font-bold text-red-600">
                                {{ formatMetricValue(metric.value, metric.unit) }}
                            </span>
                        </div>
                    </div>

                    <!-- Before Challenges -->
                    <div class="mt-4">
                        <h5 class="mb-2 font-medium text-gray-900">Key Challenges:</h5>
                        <ul class="space-y-1">
                            <li v-for="challenge in beforeChallenges" :key="challenge" class="flex items-start text-sm text-gray-600">
                                <svg class="mr-2 mt-0.5 h-4 w-4 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                {{ challenge }}
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- After Section -->
                <div class="after-section">
                    <div class="mb-4 flex items-center">
                        <div class="mr-3 flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
                            <svg class="h-4 w-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900">After Implementation</h4>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="metric in afterMetrics"
                            :key="metric.key"
                            class="flex items-center justify-between rounded-lg border border-green-200 bg-green-50 p-3"
                        >
                            <span class="text-sm font-medium text-gray-700">{{ metric.label }}</span>
                            <div class="text-right">
                                <span class="text-lg font-bold text-green-600">
                                    {{ formatMetricValue(metric.value, metric.unit) }}
                                </span>
                                <div class="text-xs font-medium text-green-600">+{{ getImprovementPercentage(metric.key) }}%</div>
                            </div>
                        </div>
                    </div>

                    <!-- After Benefits -->
                    <div class="mt-4">
                        <h5 class="mb-2 font-medium text-gray-900">Key Benefits:</h5>
                        <ul class="space-y-1">
                            <li v-for="benefit in afterBenefits" :key="benefit" class="flex items-start text-sm text-gray-600">
                                <svg class="mr-2 mt-0.5 h-4 w-4 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                {{ benefit }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Transformation Arrow -->
            <div class="mb-6 flex justify-center">
                <div class="flex items-center rounded-full border border-blue-200 bg-blue-50 px-4 py-2">
                    <span class="mr-2 text-sm font-medium text-blue-700">{{ timeframe }} Transformation</span>
                    <svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            fill-rule="evenodd"
                            d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
            </div>

            <!-- Overall Impact Summary -->
            <div class="rounded-lg border border-green-200 bg-gradient-to-r from-green-50 to-blue-50 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h4 class="font-semibold text-gray-900">Overall Impact</h4>
                    <div class="flex items-center text-green-600">
                        <svg class="mr-1 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <span class="font-bold">{{ overallImprovement }}% Average Improvement</span>
                    </div>
                </div>
                <p class="text-sm text-gray-700">{{ impactSummary }}</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Metric {
    key: string;
    label: string;
    value: number;
    unit: 'percentage' | 'count' | 'currency' | 'days';
}

interface Props {
    title: string;
    subtitle: string;
    institutionName: string;
    institutionType: string;
    institutionLogo?: string;
    alumniCount: number;
    beforeMetrics: Metric[];
    afterMetrics: Metric[];
    beforeChallenges: string[];
    afterBenefits: string[];
    timeframe: string;
    impactSummary: string;
}

const props = defineProps<Props>();

const overallImprovement = computed(() => {
    const improvements = props.afterMetrics.map((afterMetric) => {
        const beforeMetric = props.beforeMetrics.find((m) => m.key === afterMetric.key);
        if (!beforeMetric || beforeMetric.value === 0) return 0;
        return ((afterMetric.value - beforeMetric.value) / beforeMetric.value) * 100;
    });

    if (improvements.length === 0) return 0;
    const total = improvements.reduce((sum, improvement) => sum + improvement, 0);
    return Math.round(total / improvements.length);
});

const getImprovementPercentage = (metricKey: string): number => {
    const beforeMetric = props.beforeMetrics.find((m) => m.key === metricKey);
    const afterMetric = props.afterMetrics.find((m) => m.key === metricKey);

    if (!beforeMetric || !afterMetric || beforeMetric.value === 0) return 0;

    return Math.round(((afterMetric.value - beforeMetric.value) / beforeMetric.value) * 100);
};

const formatMetricValue = (value: number, unit: string): string => {
    switch (unit) {
        case 'percentage':
            return `${value}%`;
        case 'currency':
            return formatCurrency(value);
        case 'count':
            return formatNumber(value);
        case 'days':
            return `${value} days`;
        default:
            return value.toString();
    }
};

const formatCurrency = (value: number): string => {
    if (value >= 1000000) {
        return `$${(value / 1000000).toFixed(1)}M`;
    } else if (value >= 1000) {
        return `$${(value / 1000).toFixed(1)}K`;
    }
    return `$${value.toLocaleString()}`;
};

const formatNumber = (value: number): string => {
    if (value >= 1000000) {
        return `${(value / 1000000).toFixed(1)}M`;
    } else if (value >= 1000) {
        return `${(value / 1000).toFixed(1)}K`;
    }
    return value.toLocaleString();
};

const formatAlumniCount = (count: number): string => {
    if (count >= 1000000) {
        return `${(count / 1000000).toFixed(1)}M`;
    } else if (count >= 1000) {
        return `${(count / 1000).toFixed(1)}K`;
    }
    return count.toString();
};
</script>

<style scoped>
.before-section,
.after-section {
    @apply relative;
}

.before-section::after {
    content: '';
    @apply absolute right-0 top-0 hidden h-full w-px bg-gray-200 md:block;
}

/* Animation for metric cards */
.before-section > div > div,
.after-section > div > div {
    @apply transition-all duration-300 ease-in-out;
}

.before-section > div > div:hover,
.after-section > div > div:hover {
    @apply scale-105 transform;
}
</style>
