<template>
    <div class="space-y-8">
        <!-- Overview Stats -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Donors</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(analytics.totalDonors || 0) }}</p>
                    </div>
                    <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900/20">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Donors</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(analytics.activeDonors || 0) }}</p>
                    </div>
                    <div class="rounded-full bg-green-100 p-3 dark:bg-green-900/20">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg Gift Size</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ formatNumber(analytics.averageGiftSize || 0) }}</p>
                    </div>
                    <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-900/20">
                        <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Retention Rate</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ analytics.retentionRate || 0 }}%</p>
                    </div>
                    <div class="rounded-full bg-orange-100 p-3 dark:bg-orange-900/20">
                        <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                    </div>
                </div>
                <div class="mt-2 flex items-center text-sm">
                    <span :class="(analytics.retentionGrowth || 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                        {{ (analytics.retentionGrowth || 0) >= 0 ? '+' : '' }}{{ analytics.retentionGrowth || 0 }}%
                    </span>
                    <span class="ml-1 text-gray-500 dark:text-gray-400">vs last period</span>
                </div>
            </div>
        </div>

        <!-- Donor Segments -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-6 text-lg font-semibold text-gray-900 dark:text-white">Donor Segments</h3>
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- Segment Chart -->
                <div>
                    <canvas ref="segmentChart" class="max-h-80"></canvas>
                </div>

                <!-- Segment Details -->
                <div class="space-y-4">
                    <div
                        v-for="segment in analytics.donorSegments"
                        :key="segment.name"
                        class="flex items-center justify-between rounded-lg bg-gray-50 p-4 dark:bg-gray-700"
                    >
                        <div class="flex items-center space-x-3">
                            <div class="h-4 w-4 rounded-full" :style="{ backgroundColor: segment.color }"></div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ segment.name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ segment.count }} donors</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900 dark:text-white">${{ formatNumber(segment.totalGiving) }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ segment.percentage }}%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Donors -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top Donors</h3>
                <button class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">View All</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Donor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Total Given
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Last Gift
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Gifts Count
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        <tr v-for="donor in analytics.topDonors" :key="donor.id">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-300 dark:bg-gray-600">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{
                                                    donor.name
                                                        .split(' ')
                                                        .map((n: string) => n[0])
                                                        .join('')
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ donor.name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ donor.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                ${{ formatNumber(donor.totalGiven) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ formatDate(donor.lastGiftDate) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ donor.giftCount }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Chart from 'chart.js/auto';
import { nextTick, onMounted, ref } from 'vue';

interface DonorSegment {
    name: string;
    count: number;
    totalGiving: number;
    percentage: number;
    color: string;
}

interface TopDonor {
    id: number;
    name: string;
    email: string;
    totalGiven: number;
    lastGiftDate: string;
    giftCount: number;
}

interface DonorAnalytics {
    totalDonors: number;
    activeDonors: number;
    averageGiftSize: number;
    retentionRate: number;
    donorGrowth: number;
    giftSizeGrowth: number;
    retentionGrowth: number;
    donorSegments: DonorSegment[];
    topDonors: TopDonor[];
}

const props = defineProps<{
    analytics: DonorAnalytics;
}>();

const segmentChart = ref<HTMLCanvasElement>();
let chartInstance: Chart | null = null;

const formatNumber = (value: number): string => {
    return new Intl.NumberFormat('en-US').format(value);
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const initializeChart = async (): Promise<void> => {
    await nextTick();

    if (!segmentChart.value) return;

    const ctx = segmentChart.value.getContext('2d');
    if (!ctx) return;

    if (chartInstance) {
        chartInstance.destroy();
    }

    chartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: props.analytics.donorSegments.map((segment) => segment.name),
            datasets: [
                {
                    data: props.analytics.donorSegments.map((segment) => segment.totalGiving),
                    backgroundColor: props.analytics.donorSegments.map((segment) => segment.color),
                    borderWidth: 2,
                    borderColor: '#ffffff',
                },
            ],
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
                            const segment = props.analytics.donorSegments[context.dataIndex];
                            return `${segment.name}: ${formatNumber(segment.totalGiving)} (${segment.percentage}%)`;
                        },
                    },
                },
            },
        },
    });
};

onMounted(() => {
    initializeChart();
});
</script>
