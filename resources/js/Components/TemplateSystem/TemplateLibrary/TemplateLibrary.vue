<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useTemplateStore } from '@/Stores/template';
import TemplateCard from './TemplateCard.vue';
import TemplateFilters from './TemplateFilters.vue';
import TemplateSearch from './TemplateSearch.vue';
import { Link } from '@inertiajs/vue3';
import type { TemplateFilters as TemplateFilterType } from '@/Types';

const store = useTemplateStore();
const searchQuery = ref('');

const filteredTemplates = computed(() => {
    if (!searchQuery.value) return store.templates;
    const query = searchQuery.value.toLowerCase();
    return store.templates.filter(
        t => t.name.toLowerCase().includes(query) || t.description?.toLowerCase().includes(query)
    );
});

onMounted(() => {
    store.fetchTemplates();
});

function handleFilterChange(filters: TemplateFilterType) {
    store.fetchTemplates(filters);
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Template Library</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Browse and select templates for your landing pages
                    </p>
                </div>
                <Link
                    href="/templates/create"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Create Template
                </Link>
            </div>

            <!-- Search and Filters -->
            <div class="mb-6 space-y-4">
                <TemplateSearch v-model="searchQuery" />
                <TemplateFilters @change="handleFilterChange" />
            </div>

            <!-- Loading State -->
            <div v-if="store.loading" class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>

            <!-- Error State -->
            <div v-else-if="store.error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
                <p class="text-sm text-red-600 dark:text-red-400">{{ store.error }}</p>
                <button
                    @click="store.clearError(); store.fetchTemplates()"
                    class="mt-2 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-500"
                >
                    Try again
                </button>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredTemplates.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No templates found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new template.</p>
            </div>

            <!-- Template Grid -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <TemplateCard
                    v-for="template in filteredTemplates"
                    :key="template.id"
                    :template="template"
                />
            </div>

            <!-- Pagination -->
            <div v-if="store.pagination.last_page > 1" class="mt-8 flex items-center justify-between">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Showing {{ store.pagination.from }} to {{ store.pagination.to }} of {{ store.pagination.total }} results
                </p>
                <nav class="flex space-x-2">
                    <button
                        v-for="page in store.pagination.last_page"
                        :key="page"
                        @click="store.fetchTemplates({ ...store.pagination, current_page: page })"
                        :class="[
                            'px-3 py-1 rounded-md text-sm',
                            page === store.pagination.current_page
                                ? 'bg-indigo-600 text-white'
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700',
                        ]"
                    >
                        {{ page }}
                    </button>
                </nav>
            </div>
        </div>
    </div>
</template>
