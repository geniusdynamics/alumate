<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Navigation -->
        <nav class="border-b border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Hero Component Demo</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <select v-model="selectedAudience" class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="individual">Individual Alumni</option>
                            <option value="institution">Institution</option>
                            <option value="employer">Employer</option>
                        </select>
                        <label class="flex items-center space-x-2">
                            <input v-model="enableABTest" type="checkbox" class="rounded border-gray-300 dark:border-gray-600" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">A/B Testing</span>
                        </label>
                        <button @click="togglePreview" class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                            {{ showPreview ? 'Hide Preview' : 'Show Preview' }}
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Component Preview -->
        <div v-if="showPreview" class="relative">
            <component
                :is="currentHeroComponent"
                ref="heroComponent"
                :config="currentConfig"
                :sample-data="true"
                :enable-a-b-test="enableABTest"
                :user-id="'demo-user-123'"
                @ab-test-conversion="handleABTestConversion"
            />

            <!-- A/B Test Info Panel -->
            <div v-if="enableABTest && currentVariant" class="absolute right-4 top-4 rounded-lg bg-black/80 px-4 py-2 text-sm text-white">
                <div class="font-medium">A/B Test Active</div>
                <div>Variant: {{ currentVariant }}</div>
                <div>Audience: {{ selectedAudience }}</div>
            </div>
        </div>

        <!-- Configuration Panel -->
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <!-- Configuration Form -->
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h2 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">Configuration</h2>

                    <!-- Basic Settings -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"> Headline </label>
                            <input
                                v-model="currentConfig.headline"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"> Subheading </label>
                            <input
                                v-model="currentConfig.subheading"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                    </div>
                </div>

                <!-- Validation Results -->
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h2 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">Validation Results</h2>
                    <ValidationDisplay :validation-result="validationResult" />

                    <!-- A/B Test Information -->
                    <div v-if="enableABTest" class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700">
                        <h3 class="text-md mb-3 font-medium text-gray-900 dark:text-white">A/B Test Configuration</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Test ID:</span>
                                <span class="font-mono">{{ currentConfig.abTest?.testId || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Active Variant:</span>
                                <span class="font-mono">{{ currentVariant || 'Loading...' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Variants:</span>
                                <span class="font-mono">{{ currentConfig.abTest?.variants?.length || 0 }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Color Scheme:</span>
                                <span class="font-mono">{{ currentConfig.variantStyling?.colorScheme || 'default' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { logger } from '@/utils/logger';
import { HeroEmployer, HeroIndividual, HeroInstitution, getHeroConfigForAudience } from '@/Components/ComponentLibrary/Hero';
import ValidationDisplay from '@/Components/ComponentLibrary/ValidationDisplay.vue';
import type { AudienceType, HeroComponentConfig } from '@/types/Components';
import { validateHeroConfig } from '@/utils/heroConfigValidator';
import { computed, ref, watch } from 'vue';

const selectedAudience = ref<AudienceType>('individual');
const showPreview = ref(true);
const enableABTest = ref(true);
const currentConfig = ref<HeroComponentConfig>(getHeroConfigForAudience('individual'));
const heroComponent = ref();
const currentVariant = ref<string>('');

const currentHeroComponent = computed(() => {
    switch (selectedAudience.value) {
        case 'individual':
            return HeroIndividual;
        case 'institution':
            return HeroInstitution;
        case 'employer':
            return HeroEmployer;
        default:
            return HeroIndividual;
    }
});

const validationResult = computed(() => {
    return validateHeroConfig(currentConfig.value);
});

watch(selectedAudience, (newAudience) => {
    currentConfig.value = getHeroConfigForAudience(newAudience);
});

watch(
    heroComponent,
    (component) => {
        if (component && component.variant) {
            currentVariant.value = component.variant;
        }
    },
    { flush: 'post' },
);

const togglePreview = () => {
    showPreview.value = !showPreview.value;
};

const handleABTestConversion = (event: CustomEvent) => {
    logger.log('A/B Test Conversion:', event.detail);
    // In a real app, this would send data to your analytics service
};
</script>
















