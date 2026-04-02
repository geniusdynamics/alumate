<script setup lang="ts">
import { ref } from 'vue';
import { useBrandStore } from '@/Stores/brand';

const brandStore = useBrandStore();
const showAddColor = ref(false);
const newColor = ref({ name: '', value: '#000000', type: 'primary' as const });

function addColor() {
    if (!newColor.value.name || !newColor.value.value) return;
    brandStore.createColor(newColor.value);
    showAddColor.value = false;
    newColor.value = { name: '', value: '#000000', type: 'primary' };
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Brand Colors</h2>
            <button @click="showAddColor = true" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Add Color
            </button>
        </div>

        <!-- Add Color Modal -->
        <div v-if="showAddColor" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Add Color</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <input v-model="newColor.name" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="e.g., Brand Blue" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Color</label>
                        <input v-model="newColor.value" type="color" class="w-full h-10 rounded-md border-gray-300 dark:border-gray-600" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select v-model="newColor.type" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="primary">Primary</option>
                            <option value="secondary">Secondary</option>
                            <option value="accent">Accent</option>
                            <option value="neutral">Neutral</option>
                            <option value="warning">Warning</option>
                            <option value="error">Error</option>
                            <option value="success">Success</option>
                            <option value="info">Info</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button @click="showAddColor = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">Cancel</button>
                    <button @click="addColor" :disabled="!newColor.name" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50">Add</button>
                </div>
            </div>
        </div>

        <!-- Color Grid -->
        <div v-if="brandStore.colors.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
            No colors defined yet. Add your brand colors to get started.
        </div>

        <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <div v-for="color in brandStore.colors" :key="color.id" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="h-16" :style="{ backgroundColor: color.value }"></div>
                <div class="p-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ color.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ color.value }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 capitalize mt-1">{{ color.type }}</p>
                    <button @click="brandStore.deleteColor(color.id)" class="mt-2 text-xs text-red-600 dark:text-red-400 hover:text-red-800">Delete</button>
                </div>
            </div>
        </div>
    </div>
</template>
