<script setup lang="ts">
import { ref } from 'vue';
import { useBrandStore } from '@/Stores/brand';

const brandStore = useBrandStore();
const showAddFont = ref(false);
const newFont = ref({ name: '', family: '', type: 'google' as const, weights: ['400', '700'] });

function addFont() {
    if (!newFont.value.name || !newFont.value.family) return;
    brandStore.createFont(newFont.value);
    showAddFont.value = false;
    newFont.value = { name: '', family: '', type: 'google', weights: ['400', '700'] };
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Brand Fonts</h2>
            <button @click="showAddFont = true" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Add Font
            </button>
        </div>

        <!-- Add Font Modal -->
        <div v-if="showAddFont" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Add Font</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Display Name</label>
                        <input v-model="newFont.name" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="e.g., Brand Heading" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Font Family</label>
                        <input v-model="newFont.family" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" placeholder="e.g., Inter, Roboto" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Source</label>
                        <select v-model="newFont.type" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="google">Google Fonts</option>
                            <option value="system">System Font</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button @click="showAddFont = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">Cancel</button>
                    <button @click="addFont" :disabled="!newFont.name" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50">Add</button>
                </div>
            </div>
        </div>

        <!-- Font List -->
        <div v-if="brandStore.fonts.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
            No fonts defined yet. Add your brand fonts to get started.
        </div>

        <div v-else class="space-y-3">
            <div v-for="font in brandStore.fonts" :key="font.id" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-medium text-gray-900 dark:text-white" :style="{ fontFamily: font.family }">{{ font.name }}</p>
                        <span v-if="font.is_primary" class="px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">Primary</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ font.family }} · {{ font.weights.join(', ') }} · {{ font.type }}</p>
                </div>
                <button @click="brandStore.deleteFont(font.id)" class="text-xs text-red-600 dark:text-red-400 hover:text-red-800">Delete</button>
            </div>
        </div>
    </div>
</template>
