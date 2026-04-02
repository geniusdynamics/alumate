<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAnalyticsStore } from '@/Stores/analytics';

const analyticsStore = useAnalyticsStore();

onMounted(() => {
    analyticsStore.fetchABTests();
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">A/B Tests</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Run experiments to optimize your templates</p>
                </div>
                <button class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                    Create Test
                </button>
            </div>

            <!-- Loading -->
            <div v-if="analyticsStore.loading" class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>

            <!-- Tests List -->
            <div v-else-if="analyticsStore.abTests.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                No A/B tests yet. Create your first experiment to get started.
            </div>

            <div v-else class="space-y-4">
                <div v-for="test in analyticsStore.abTests" :key="test.id" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ test.name }}</h3>
                        <span :class="{
                            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': test.status === 'running',
                            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': test.status === 'draft',
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300': test.status === 'paused',
                            'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': test.status === 'completed',
                        }" class="px-2 py-0.5 text-xs font-medium rounded-full capitalize">
                            {{ test.status }}
                        </span>
                    </div>

                    <p v-if="test.description" class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ test.description }}</p>

                    <!-- Variants -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="variant in test.variants" :key="variant.id" class="p-4 border border-gray-200 dark:border-gray-700 rounded">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ variant.name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ variant.traffic_percentage }}% traffic</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white mt-2">{{ variant.conversion_rate }}%</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ variant.conversion_count }} conversions</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
