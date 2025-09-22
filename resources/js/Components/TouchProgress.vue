<!-- ABOUTME: Touch-optimized progress indicator component for multi-step forms -->
<!-- ABOUTME: Provides visual feedback for form completion progress with mobile-friendly design -->
<template>
    <div class="touch-progress-container">
        <!-- Progress Bar -->
        <div class="progress-wrapper">
            <div class="progress-track">
                <div class="progress-fill" :style="{ width: `${progressPercentage}%` }"></div>
            </div>

            <!-- Step Indicators -->
            <div class="step-indicators">
                <div
                    v-for="step in totalSteps"
                    :key="step"
                    :class="[
                        'step-indicator',
                        {
                            'step-completed': step < currentStep,
                            'step-current': step === currentStep,
                            'step-pending': step > currentStep,
                        },
                    ]"
                    @click="handleStepClick(step)"
                >
                    <!-- Step Number or Check Icon -->
                    <div class="step-content">
                        <svg v-if="step < currentStep" class="step-check" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                        <span v-else class="step-number">{{ step }}</span>
                    </div>

                    <!-- Step Label -->
                    <div v-if="showLabels && stepLabels[step - 1]" class="step-label">
                        {{ stepLabels[step - 1] }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Text -->
        <div class="progress-text">
            <span class="progress-current">Step {{ currentStep }} of {{ totalSteps }}</span>
            <span class="progress-percentage">{{ Math.round(progressPercentage) }}% Complete</span>
        </div>

        <!-- Navigation Buttons (Optional) -->
        <div v-if="showNavigation" class="progress-navigation">
            <button @click="handlePrevious" :disabled="currentStep <= 1" class="nav-button nav-previous" type="button">
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Previous
            </button>

            <button @click="handleNext" :disabled="currentStep >= totalSteps" class="nav-button nav-next" type="button">
                Next
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    currentStep: number;
    totalSteps: number;
    progress?: number; // Optional override for progress percentage
    showLabels?: boolean;
    stepLabels?: string[];
    showNavigation?: boolean;
    allowStepClick?: boolean;
    variant?: 'default' | 'minimal' | 'detailed';
}

interface Emits {
    'step-change': [step: number];
    previous: [];
    next: [];
}

const props = withDefaults(defineProps<Props>(), {
    currentStep: 1,
    totalSteps: 1,
    showLabels: false,
    stepLabels: () => [],
    showNavigation: false,
    allowStepClick: false,
    variant: 'default',
});

const emit = defineEmits<Emits>();

// Computed
const progressPercentage = computed(() => {
    if (props.progress !== undefined) {
        return Math.min(Math.max(props.progress, 0), 100);
    }

    if (props.totalSteps <= 1) return 100;

    return ((props.currentStep - 1) / (props.totalSteps - 1)) * 100;
});

// Methods
const handleStepClick = (step: number) => {
    if (props.allowStepClick && step !== props.currentStep) {
        emit('step-change', step);
    }
};

const handlePrevious = () => {
    if (props.currentStep > 1) {
        emit('previous');
        emit('step-change', props.currentStep - 1);
    }
};

const handleNext = () => {
    if (props.currentStep < props.totalSteps) {
        emit('next');
        emit('step-change', props.currentStep + 1);
    }
};
</script>

<style scoped>
.touch-progress-container {
    @apply w-full py-4;
}

.progress-wrapper {
    @apply relative mb-4;
}

.progress-track {
    @apply h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700;
}

.progress-fill {
    @apply h-full rounded-full bg-blue-600 dark:bg-blue-500;
    @apply transition-all duration-300 ease-in-out;
}

.step-indicators {
    @apply mt-4 flex items-center justify-between;
}

.step-indicator {
    @apply flex flex-col items-center gap-2;
    @apply transition-all duration-200 ease-in-out;
}

.step-indicator.step-completed .step-content {
    @apply border-green-600 bg-green-600 text-white dark:border-green-500 dark:bg-green-500;
}

.step-indicator.step-current .step-content {
    @apply border-blue-600 bg-blue-600 text-white dark:border-blue-500 dark:bg-blue-500;
    @apply ring-2 ring-blue-200 dark:ring-blue-800;
}

.step-indicator.step-pending .step-content {
    @apply bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400;
    @apply border-gray-300 dark:border-gray-600;
}

.step-content {
    @apply h-10 w-10 rounded-full border-2;
    @apply flex items-center justify-center;
    @apply text-sm font-medium;
    @apply transition-all duration-200 ease-in-out;

    /* Touch-friendly minimum size */
    min-width: 44px;
    min-height: 44px;
}

.step-number {
    @apply font-semibold;
}

.step-check {
    @apply h-5 w-5;
}

.step-label {
    @apply text-center text-xs text-gray-600 dark:text-gray-400;
    @apply max-w-20 truncate;
    @apply mt-1;
}

.step-indicator.step-current .step-label {
    @apply font-medium text-blue-600 dark:text-blue-400;
}

.step-indicator.step-completed .step-label {
    @apply text-green-600 dark:text-green-400;
}

.progress-text {
    @apply flex items-center justify-between text-sm;
    @apply text-gray-600 dark:text-gray-400;
    @apply mt-4 px-2;
}

.progress-current {
    @apply font-medium;
}

.progress-percentage {
    @apply font-medium text-blue-600 dark:text-blue-400;
}

.progress-navigation {
    @apply mt-6 flex items-center justify-between;
}

.nav-button {
    @apply flex items-center gap-2 px-4 py-2;
    @apply rounded-lg text-sm font-medium;
    @apply border border-gray-300 dark:border-gray-600;
    @apply bg-white dark:bg-gray-800;
    @apply text-gray-700 dark:text-gray-300;
    @apply transition-all duration-200 ease-in-out;
    @apply focus:border-blue-500 focus:ring-2 focus:ring-blue-500;

    /* Touch-friendly minimum size */
    min-height: 44px;
}

.nav-button:hover:not(:disabled) {
    @apply border-gray-400 bg-gray-50 dark:border-gray-500 dark:bg-gray-700;
}

.nav-button:disabled {
    @apply cursor-not-allowed opacity-50;
}

.nav-icon {
    @apply h-4 w-4;
}

.nav-previous {
    @apply flex-row;
}

.nav-next {
    @apply flex-row-reverse;
}

/* Clickable steps */
.step-indicator {
    cursor: default;
}

.step-indicator.clickable {
    @apply cursor-pointer;
}

.step-indicator.clickable:hover .step-content {
    @apply scale-110;
}

/* Variant: Minimal */
.variant-minimal .step-indicators {
    @apply hidden;
}

.variant-minimal .progress-track {
    @apply h-1;
}

/* Variant: Detailed */
.variant-detailed .step-content {
    @apply h-12 w-12;
    min-width: 48px;
    min-height: 48px;
}

.variant-detailed .step-label {
    @apply max-w-24 text-sm;
}

/* Responsive Design */
@media (max-width: 768px) {
    .step-content {
        @apply h-8 w-8;
        min-width: 40px;
        min-height: 40px;
        @apply text-xs;
    }

    .step-check {
        @apply h-4 w-4;
    }

    .step-label {
        @apply max-w-16 text-xs;
    }

    .progress-navigation {
        @apply flex-col gap-3;
    }

    .nav-button {
        @apply w-full justify-center;
    }
}

@media (max-width: 640px) {
    .progress-text {
        @apply flex-col gap-1 text-center;
    }

    .step-indicators {
        @apply gap-2;
    }

    .step-label {
        @apply hidden;
    }
}

/* Touch Device Optimizations */
@media (hover: none) and (pointer: coarse) {
    .step-content {
        min-width: 48px;
        min-height: 48px;
    }

    .nav-button {
        min-height: 48px;
        @apply px-6 py-3;
    }
}

/* Focus Visible for Keyboard Navigation */
.step-content:focus-visible,
.nav-button:focus-visible {
    @apply outline-2 outline-offset-2 outline-blue-500;
}

/* Animation for progress fill */
@keyframes progress-fill {
    from {
        width: 0%;
    }
    to {
        width: var(--progress-width);
    }
}

.progress-fill {
    animation: progress-fill 0.5s ease-in-out;
}

/* Reduce Motion for Accessibility */
@media (prefers-reduced-motion: reduce) {
    .progress-fill,
    .step-content,
    .nav-button {
        transition: none;
    }

    .progress-fill {
        animation: none;
    }
}
</style>
