<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
              Hero Component Demo
            </h1>
          </div>
          <div class="flex items-center space-x-4">
            <select
              v-model="selectedAudience"
              class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
              <option value="individual">Individual Alumni</option>
              <option value="institution">Institution</option>
              <option value="employer">Employer</option>
            </select>
            <label class="flex items-center space-x-2">
              <input
                v-model="enableABTest"
                type="checkbox"
                class="rounded border-gray-300 dark:border-gray-600"
              >
              <span class="text-sm text-gray-700 dark:text-gray-300">A/B Testing</span>
            </label>
            <button
              @click="togglePreview"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
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
      <div
        v-if="enableABTest && currentVariant"
        class="absolute top-4 right-4 bg-black/80 text-white px-4 py-2 rounded-lg text-sm"
      >
        <div class="font-medium">A/B Test Active</div>
        <div>Variant: {{ currentVariant }}</div>
        <div>Audience: {{ selectedAudience }}</div>
      </div>
    </div>

    <!-- Configuration Panel -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Configuration Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Configuration
          </h2>
          
          <!-- Basic Settings -->
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Headline
              </label>
              <input
                v-model="currentConfig.headline"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Subheading
              </label>
              <input
                v-model="currentConfig.subheading"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
              >
            </div>
          </div>
        </div>

        <!-- Validation Results -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Validation Results
          </h2>
          <ValidationDisplay :validation-result="validationResult" />
          
          <!-- A/B Test Information -->
          <div v-if="enableABTest" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <h3 class="text-md font-medium text-gray-900 dark:text-white mb-3">
              A/B Test Configuration
            </h3>
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
</template><
script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { HeroIndividual, HeroInstitution, HeroEmployer, getHeroConfigForAudience } from '@/components/ComponentLibrary/Hero'
import { validateHeroConfig } from '@/utils/heroConfigValidator'
import ValidationDisplay from '@/components/ComponentLibrary/ValidationDisplay.vue'
import type { AudienceType, HeroComponentConfig } from '@/types/components'

const selectedAudience = ref<AudienceType>('individual')
const showPreview = ref(true)
const enableABTest = ref(true)
const currentConfig = ref<HeroComponentConfig>(getHeroConfigForAudience('individual'))
const heroComponent = ref()
const currentVariant = ref<string>('')

const currentHeroComponent = computed(() => {
  switch (selectedAudience.value) {
    case 'individual':
      return HeroIndividual
    case 'institution':
      return HeroInstitution
    case 'employer':
      return HeroEmployer
    default:
      return HeroIndividual
  }
})

const validationResult = computed(() => {
  return validateHeroConfig(currentConfig.value)
})

watch(selectedAudience, (newAudience) => {
  currentConfig.value = getHeroConfigForAudience(newAudience)
})

watch(heroComponent, (component) => {
  if (component && component.variant) {
    currentVariant.value = component.variant
  }
}, { flush: 'post' })

const togglePreview = () => {
  showPreview.value = !showPreview.value
}

const handleABTestConversion = (event: CustomEvent) => {
  console.log('A/B Test Conversion:', event.detail)
  // In a real app, this would send data to your analytics service
}
</script>