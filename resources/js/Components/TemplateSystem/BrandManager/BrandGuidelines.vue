<script setup lang="ts">
import { useBrandStore } from '@/Stores/brand';

const brandStore = useBrandStore();

function toggleSetting(key: string) {
    if (!brandStore.guidelines) return;
    const value = brandStore.guidelines[key as keyof typeof brandStore.guidelines];
    if (typeof value === 'boolean') {
        brandStore.updateGuidelines({ [key]: !value });
    }
}
</script>

<template>
    <div>
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Brand Guidelines</h2>

        <div v-if="!brandStore.guidelines" class="text-center py-8 text-gray-500 dark:text-gray-400">
            No guidelines configured yet.
        </div>

        <div v-else class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-200 dark:divide-gray-700">
            <!-- Color Palette -->
            <div class="p-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Enforce Color Palette</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Require all designs to use approved brand colors</p>
                </div>
                <button
                    @click="toggleSetting('enforce_color_palette')"
                    :class="[
                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                        brandStore.guidelines.enforce_color_palette ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600',
                    ]"
                >
                    <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform', brandStore.guidelines.enforce_color_palette ? 'translate-x-6' : 'translate-x-1']" />
                </button>
            </div>

            <!-- Contrast Check -->
            <div class="p-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Require Contrast Check</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Ensure text meets WCAG contrast requirements</p>
                </div>
                <button
                    @click="toggleSetting('require_contrast_check')"
                    :class="[
                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                        brandStore.guidelines.require_contrast_check ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600',
                    ]"
                >
                    <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform', brandStore.guidelines.require_contrast_check ? 'translate-x-6' : 'translate-x-1']" />
                </button>
            </div>

            <!-- Font Families -->
            <div class="p-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Enforce Font Families</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Restrict fonts to approved brand typefaces</p>
                </div>
                <button
                    @click="toggleSetting('enforce_font_families')"
                    :class="[
                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                        brandStore.guidelines.enforce_font_families ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600',
                    ]"
                >
                    <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform', brandStore.guidelines.enforce_font_families ? 'translate-x-6' : 'translate-x-1']" />
                </button>
            </div>

            <!-- Logo Placement -->
            <div class="p-4 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Enforce Logo Placement</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Require minimum logo size and clear space</p>
                </div>
                <button
                    @click="toggleSetting('enforce_logo_placement')"
                    :class="[
                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                        brandStore.guidelines.enforce_logo_placement ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600',
                    ]"
                >
                    <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform', brandStore.guidelines.enforce_logo_placement ? 'translate-x-6' : 'translate-x-1']" />
                </button>
            </div>

            <!-- Min Contrast Ratio -->
            <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Minimum Contrast Ratio</h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ brandStore.guidelines.min_contrast_ratio }}:1</span>
                </div>
                <input
                    type="range"
                    min="3"
                    max="7"
                    step="0.1"
                    :value="brandStore.guidelines.min_contrast_ratio"
                    @input="brandStore.updateGuidelines({ min_contrast_ratio: parseFloat(($event.target as HTMLInputElement).value) })"
                    class="w-full"
                />
                <div class="flex justify-between text-xs text-gray-400 mt-1">
                    <span>3:1 (AA Large)</span>
                    <span>4.5:1 (AA)</span>
                    <span>7:1 (AAA)</span>
                </div>
            </div>
        </div>
    </div>
</template>
