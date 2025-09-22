<template>
    <div class="guided-tour" v-if="isActive">
        <!-- Tour Overlay -->
        <div
            class="fixed inset-0 z-[100] bg-black/50 backdrop-blur-sm transition-opacity"
            @click="handleOverlayClick"
            role="dialog"
            aria-modal="true"
        >
            <!-- Tour Tooltip -->
            <div
                v-if="currentStep"
                class="pointer-events-auto absolute z-[110] max-w-sm rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800"
                :style="tooltipStyle"
                role="tooltip"
            >
                <!-- Step Counter -->
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-sm font-medium text-white">
                            {{ currentStepIndex + 1 }}
                        </div>
                        <span class="text-sm text-gray-500"> {{ currentStepIndex + 1 }} of {{ steps.length }} </span>
                    </div>

                    <button @click="closeTour" class="rounded p-1 text-gray-400 transition-colors hover:text-gray-600" aria-label="Close tour">
                        ✕
                    </button>
                </div>

                <!-- Step Content -->
                <div class="mb-6">
                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                        {{ currentStep.title }}
                    </h3>
                    <p class="leading-relaxed text-gray-600 dark:text-gray-300">
                        {{ currentStep.description }}
                    </p>
                </div>

                <!-- Navigation -->
                <div class="flex items-center justify-between">
                    <button
                        v-if="currentStepIndex > 0"
                        @click="previousStep"
                        class="flex items-center space-x-2 px-4 py-2 text-gray-600 transition-colors hover:text-gray-800"
                    >
                        <span>← Previous</span>
                    </button>

                    <div v-else class="w-20"></div>

                    <button
                        v-if="currentStepIndex < steps.length - 1"
                        @click="nextStep"
                        class="flex items-center space-x-2 rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700"
                    >
                        <span>Next →</span>
                    </button>

                    <button v-else @click="completeTour" class="rounded-lg bg-green-600 px-4 py-2 text-white transition-colors hover:bg-green-700">
                        Complete Tour
                    </button>
                </div>
            </div>
        </div>

        <!-- Tour Controls -->
        <div v-if="!currentStep && showControls" class="fixed bottom-6 right-6 z-[100]">
            <div class="max-w-sm rounded-lg border border-gray-200 bg-white p-4 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-3 flex items-center justify-between">
                    <h4 class="font-semibold text-gray-900 dark:text-white">Platform Tour</h4>
                    <button @click="closeTour" class="rounded p-1 text-gray-400 transition-colors hover:text-gray-600" aria-label="Close tour">
                        ✕
                    </button>
                </div>

                <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">Discover key features and learn how to make the most of the platform.</p>

                <div class="flex space-x-2">
                    <button @click="startTour" class="flex-1 rounded-lg bg-blue-600 px-3 py-2 text-sm text-white transition-colors hover:bg-blue-700">
                        Start Tour
                    </button>
                    <button
                        @click="closeTour"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Skip
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { AudienceType } from '@/Types/homepage';
import { computed, onMounted, ref } from 'vue';

interface TourStep {
    id: string;
    title: string;
    description: string;
    target: string;
    position: 'top' | 'bottom' | 'left' | 'right' | 'center';
}

interface Props {
    audience: AudienceType;
    autoStart?: boolean;
    showControls?: boolean;
    steps?: TourStep[];
}

const props = withDefaults(defineProps<Props>(), {
    autoStart: false,
    showControls: true,
    steps: () => [],
});

const emit = defineEmits<{
    start: [];
    complete: [];
    skip: [];
    stepChange: [stepIndex: number, step: TourStep];
}>();

// Reactive state
const isActive = ref(false);
const currentStepIndex = ref(0);

// Computed properties
const steps = computed((): TourStep[] => {
    if (props.steps.length > 0) {
        return props.steps;
    }

    // Default steps
    return [
        {
            id: 'welcome',
            title: 'Welcome to Your Alumni Platform',
            description: "Let's take a quick tour of the key features.",
            target: 'body',
            position: 'center',
        },
    ];
});

const currentStep = computed((): TourStep | null => {
    return steps.value[currentStepIndex.value] || null;
});

const tooltipStyle = computed(() => {
    return {
        left: '50%',
        top: '50%',
        transform: 'translate(-50%, -50%)',
    };
});

// Methods
const startTour = async (): Promise<void> => {
    isActive.value = true;
    currentStepIndex.value = 0;
    emit('start');
};

const closeTour = (): void => {
    isActive.value = false;
    currentStepIndex.value = 0;
    emit('skip');
};

const completeTour = (): void => {
    isActive.value = false;
    currentStepIndex.value = 0;
    emit('complete');
};

const nextStep = async (): Promise<void> => {
    if (currentStepIndex.value < steps.value.length - 1) {
        currentStepIndex.value++;
    }
};

const previousStep = async (): Promise<void> => {
    if (currentStepIndex.value > 0) {
        currentStepIndex.value--;
    }
};

const handleOverlayClick = (event: MouseEvent): void => {
    if (event.target === event.currentTarget) {
        closeTour();
    }
};

// Lifecycle hooks
onMounted(() => {
    if (props.autoStart) {
        setTimeout(() => {
            startTour();
        }, 1000);
    }
});
</script>

<style scoped>
.guided-tour {
    z-index: 9999;
}
</style>














