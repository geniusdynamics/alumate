<script setup lang="ts">
import { computed } from 'vue';
import type { Template } from '@/Types';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    template: Template;
}>();

const statusColor = computed(() => {
    if (!props.template.is_active) return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    if (props.template.is_premium) return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
    return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
});

const statusLabel = computed(() => {
    if (!props.template.is_active) return 'Inactive';
    if (props.template.is_premium) return 'Premium';
    return 'Active';
});

const categoryLabel = computed(() => {
    return props.template.category.charAt(0).toUpperCase() + props.template.category.slice(1);
});
</script>

<template>
    <Link
        :href="`/templates/${template.id}/edit`"
        class="group block bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden border border-gray-200 dark:border-gray-700"
    >
        <!-- Preview Image -->
        <div class="aspect-video bg-gray-100 dark:bg-gray-700 relative overflow-hidden">
            <img
                v-if="template.preview_image"
                :src="template.preview_image"
                :alt="template.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
            />
            <div v-else class="w-full h-full flex items-center justify-center">
                <svg class="h-12 w-12 text-gray-300 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                </svg>
            </div>
            <!-- Status Badge -->
            <span :class="['absolute top-2 right-2 px-2 py-0.5 text-xs font-medium rounded-full', statusColor]">
                {{ statusLabel }}
            </span>
        </div>

        <!-- Content -->
        <div class="p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                {{ template.name }}
            </h3>
            <p v-if="template.description" class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                {{ template.description }}
            </p>

            <!-- Meta Info -->
            <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">
                    {{ categoryLabel }}
                </span>
                <span>{{ template.usage_count }} uses</span>
            </div>

            <!-- Tags -->
            <div v-if="template.tags?.length" class="mt-3 flex flex-wrap gap-1">
                <span
                    v-for="tag in template.tags.slice(0, 3)"
                    :key="tag"
                    class="px-1.5 py-0.5 text-xs bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 rounded"
                >
                    {{ tag }}
                </span>
                <span v-if="template.tags.length > 3" class="text-xs text-gray-400">+{{ template.tags.length - 3 }}</span>
            </div>
        </div>
    </Link>
</template>
