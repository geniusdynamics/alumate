<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAnalyticsStore } from '@/Stores/analytics';

const analyticsStore = useAnalyticsStore();
const selectedTemplateId = ref<number | null>(null);
const selectedPageId = ref<number | null>(null);

onMounted(() => {
    // Fetch overview analytics if IDs are available
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Analytics Dashboard</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Monitor template and landing page performance</p>
            </div>

            <!-- Loading -->
            <div v-if="analyticsStore.loading" class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>

            <template v-else>
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Template Usage</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ analyticsStore.templateMetrics?.usage_count ?? '—' }}
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Conversion Rate</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ analyticsStore.templateMetrics?.conversion_rate ?? '—' }}%
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Page Views</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ analyticsStore.landingPageMetrics?.traffic.page_views ?? '—' }}
                        </p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Brand Consistency</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ analyticsStore.brandMetrics?.consistency_score ?? '—' }}%
                        </p>
                    </div>
                </div>

                <!-- Brand Issues -->
                <div v-if="analyticsStore.brandMetrics?.issues?.length" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Brand Issues</h3>
                    <div class="space-y-2">
                        <div v-for="issue in analyticsStore.brandMetrics.issues" :key="issue.id" class="flex items-start gap-3 p-3 bg-red-50 dark:bg-red-900/10 rounded">
                            <span :class="{
                                'text-red-600': issue.severity === 'critical',
                                'text-orange-600': issue.severity === 'high',
                                'text-yellow-600': issue.severity === 'medium',
                                'text-blue-600': issue.severity === 'low',
                            }" class="text-sm font-medium capitalize">{{ issue.severity }}</span>
                            <div>
                                <p class="text-sm text-gray-900 dark:text-white">{{ issue.title }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ issue.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- A/B Tests -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Active A/B Tests</h3>
                    <div v-if="analyticsStore.abTests.length === 0" class="text-sm text-gray-500 dark:text-gray-400">
                        No active A/B tests.
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="test in analyticsStore.abTests" :key="test.id" class="p-3 border border-gray-200 dark:border-gray-700 rounded">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ test.name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ test.variants.length }} variants · {{ test.conversion_goal }}
                            </p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>
