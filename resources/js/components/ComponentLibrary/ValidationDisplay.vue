<template>
  <div class="space-y-4">
    <!-- Validation Status -->
    <div class="flex items-center space-x-2">
      <div
        :class="[
          'w-3 h-3 rounded-full',
          validationResult.isValid ? 'bg-green-500' : 'bg-red-500'
        ]"
      />
      <span
        :class="[
          'text-sm font-medium',
          validationResult.isValid ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'
        ]"
      >
        {{ validationResult.isValid ? 'Valid Configuration' : 'Invalid Configuration' }}
      </span>
    </div>

    <!-- Errors -->
    <div v-if="validationResult.errors.length > 0" class="space-y-2">
      <h3 class="text-sm font-medium text-red-700 dark:text-red-400">
        Errors ({{ validationResult.errors.length }})
      </h3>
      <div class="space-y-1">
        <div
          v-for="error in validationResult.errors"
          :key="`error-${error.field}-${error.code}`"
          class="flex items-start space-x-2 p-2 bg-red-50 dark:bg-red-900/20 rounded-md"
        >
          <Icon name="x-circle" class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" />
          <div class="flex-1 min-w-0">
            <p class="text-sm text-red-700 dark:text-red-400">
              <span class="font-medium">{{ error.field }}:</span>
              {{ error.message }}
            </p>
            <p class="text-xs text-red-600 dark:text-red-500 mt-1">
              Code: {{ error.code }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Warnings -->
    <div v-if="validationResult.warnings.length > 0" class="space-y-2">
      <h3 class="text-sm font-medium text-yellow-700 dark:text-yellow-400">
        Warnings ({{ validationResult.warnings.length }})
      </h3>
      <div class="space-y-1">
        <div
          v-for="warning in validationResult.warnings"
          :key="`warning-${warning.field}-${warning.code}`"
          class="flex items-start space-x-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded-md"
        >
          <Icon name="exclamation-triangle" class="w-4 h-4 text-yellow-500 mt-0.5 flex-shrink-0" />
          <div class="flex-1 min-w-0">
            <p class="text-sm text-yellow-700 dark:text-yellow-400">
              <span class="font-medium">{{ warning.field }}:</span>
              {{ warning.message }}
            </p>
            <p class="text-xs text-yellow-600 dark:text-yellow-500 mt-1">
              Code: {{ warning.code }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Success State -->
    <div
      v-if="validationResult.isValid && validationResult.warnings.length === 0"
      class="flex items-center space-x-2 p-3 bg-green-50 dark:bg-green-900/20 rounded-md"
    >
      <Icon name="check-circle" class="w-5 h-5 text-green-500" />
      <p class="text-sm text-green-700 dark:text-green-400">
        Configuration is valid and ready to use!
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ValidationResult } from '@/utils/heroConfigValidator'
import Icon from '@/components/Icon.vue'

interface Props {
  validationResult: ValidationResult
}

defineProps<Props>()
</script>