<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useBrandStore } from '@/Stores/brand';
import BrandLogoManager from './BrandLogoManager.vue';
import BrandColorManager from './BrandColorManager.vue';
import BrandFontManager from './BrandFontManager.vue';
import BrandGuidelines from './BrandGuidelines.vue';

const brandStore = useBrandStore();
const activeTab = ref('logos');

const tabs = [
    { id: 'logos', label: 'Logos', icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { id: 'colors', label: 'Colors', icon: 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01' },
    { id: 'fonts', label: 'Fonts', icon: 'M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129' },
    { id: 'guidelines', label: 'Guidelines', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
];

onMounted(() => {
    brandStore.fetchBrandAssets();
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Brand Manager</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage your brand assets, colors, fonts, and guidelines
                </p>
            </div>

            <!-- Loading State -->
            <div v-if="brandStore.loading" class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>

            <!-- Error State -->
            <div v-else-if="brandStore.error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
                <p class="text-sm text-red-600 dark:text-red-400">{{ brandStore.error }}</p>
                <button @click="brandStore.clearError(); brandStore.fetchBrandAssets()" class="mt-2 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-500">
                    Try again
                </button>
            </div>

            <template v-else>
                <!-- Tabs -->
                <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                activeTab === tab.id
                                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2',
                            ]"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                            </svg>
                            {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <BrandLogoManager v-if="activeTab === 'logos'" />
                <BrandColorManager v-else-if="activeTab === 'colors'" />
                <BrandFontManager v-else-if="activeTab === 'fonts'" />
                <BrandGuidelines v-else-if="activeTab === 'guidelines'" />
            </template>
        </div>
    </div>
</template>
