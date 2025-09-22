<template>
    <div class="space-y-4">
        <!-- Validation Status -->
        <div class="flex items-center space-x-2">
            <div :class="['h-3 w-3 rounded-full', validationResult.isValid ? 'bg-green-500' : 'bg-red-500']" />
            <span
                :class="['text-sm font-medium', validationResult.isValid ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400']"
            >
                {{ validationResult.isValid ? 'Valid Configuration' : 'Invalid Configuration' }}
            </span>
        </div>

        <!-- Errors -->
        <div v-if="validationResult.errors.length > 0" class="space-y-2">
            <h3 class="text-sm font-medium text-red-700 dark:text-red-400">Errors ({{ validationResult.errors.length }})</h3>
            <div class="space-y-1">
                <div
                    v-for="error in validationResult.errors"
                    :key="`error-${error.field}-${error.code}`"
                    class="flex items-start space-x-2 rounded-md bg-red-50 p-2 dark:bg-red-900/20"
                >
                    <Icon name="x-circle" class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-500" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-red-700 dark:text-red-400">
                            <span class="font-medium">{{ error.field }}:</span>
                            {{ error.message }}
                        </p>
                        <p class="mt-1 text-xs text-red-600 dark:text-red-500">Code: {{ error.code }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warnings -->
        <div v-if="validationResult.warnings.length > 0" class="space-y-2">
            <h3 class="text-sm font-medium text-yellow-700 dark:text-yellow-400">Warnings ({{ validationResult.warnings.length }})</h3>
            <div class="space-y-1">
                <div
                    v-for="warning in validationResult.warnings"
                    :key="`warning-${warning.field}-${warning.code}`"
                    class="flex items-start space-x-2 rounded-md bg-yellow-50 p-2 dark:bg-yellow-900/20"
                >
                    <Icon name="exclamation-triangle" class="mt-0.5 h-4 w-4 flex-shrink-0 text-yellow-500" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-yellow-700 dark:text-yellow-400">
                            <span class="font-medium">{{ warning.field }}:</span>
                            {{ warning.message }}
                        </p>
                        <p class="mt-1 text-xs text-yellow-600 dark:text-yellow-500">Code: {{ warning.code }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success State -->
        <div
            v-if="validationResult.isValid && validationResult.warnings.length === 0"
            class="flex items-center space-x-2 rounded-md bg-green-50 p-3 dark:bg-green-900/20"
        >
            <Icon name="check-circle" class="h-5 w-5 text-green-500" />
            <p class="text-sm text-green-700 dark:text-green-400">Configuration is valid and ready to use!</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from '@/Components/Icon.vue';
import type { ValidationResult } from '@/utils/heroConfigValidator';

interface Props {
    validationResult: ValidationResult;
}

defineProps<Props>();
</script>
