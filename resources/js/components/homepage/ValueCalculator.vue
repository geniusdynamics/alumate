<template>
    <section class="value-calculator">
        <div class="container mx-auto px-4">
            <div class="mb-12 text-center">
                <h2 class="mb-4 text-4xl font-bold text-gray-900">Calculate Your Career Value</h2>
                <p class="mx-auto max-w-3xl text-xl text-gray-600">
                    Discover your potential career advancement and salary growth through alumni networking. Get personalized insights based on real
                    platform data.
                </p>
            </div>

            <!-- Calculator Modal Trigger -->
            <div class="mb-8 text-center">
                <button
                    @click="openCalculator"
                    class="rounded-lg bg-blue-600 px-8 py-4 text-lg font-semibold text-white shadow-lg transition-colors duration-200 hover:bg-blue-700 hover:shadow-xl"
                    :disabled="isLoading"
                >
                    <span v-if="!isLoading">Start Your Career Assessment</span>
                    <span v-else class="flex items-center">
                        <svg class="-ml-1 mr-3 h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        Loading...
                    </span>
                </button>
            </div>

            <!-- Preview Statistics -->
            <div class="mx-auto grid max-w-4xl grid-cols-1 gap-8 md:grid-cols-3">
                <div class="text-center">
                    <div class="mb-2 text-3xl font-bold text-blue-600">40%</div>
                    <div class="text-gray-600">Average Salary Increase</div>
                </div>
                <div class="text-center">
                    <div class="mb-2 text-3xl font-bold text-green-600">6 months</div>
                    <div class="text-gray-600">Average Time to Promotion</div>
                </div>
                <div class="text-center">
                    <div class="mb-2 text-3xl font-bold text-purple-600">85%</div>
                    <div class="text-gray-600">Job Placement Success Rate</div>
                </div>
            </div>

            <!-- Calculator Modal -->
            <div
                v-if="showCalculator"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
                @click="closeCalculator"
            >
                <div class="max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-lg bg-white shadow-2xl" @click.stop>
                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-2xl font-bold">Career Value Calculator</h3>
                                <p class="mt-1 text-blue-100">Step {{ currentStep }} of {{ totalSteps }}</p>
                            </div>
                            <button @click="closeCalculator" class="text-white transition-colors hover:text-gray-200">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-4">
                            <div class="h-2 rounded-full bg-blue-500 bg-opacity-30">
                                <div
                                    class="h-2 rounded-full bg-white transition-all duration-300"
                                    :style="{ width: `${(currentStep / totalSteps) * 100}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Content -->
                    <div class="max-h-[calc(90vh-200px)] overflow-y-auto p-6">
                        <!-- Step Content -->
                        <div v-if="currentStep <= totalSteps">
                            <component
                                :is="currentStepComponent"
                                :form-data="formData"
                                :errors="errors"
                                :is-loading="isSubmitting"
                                @update="updateFormData"
                                @next="nextStep"
                                @previous="previousStep"
                                @submit="submitCalculator"
                            />
                        </div>

                        <!-- Results -->
                        <div v-else-if="calculationResult">
                            <CalculatorResults
                                :result="calculationResult"
                                :form-data="formData"
                                @restart="restartCalculator"
                                @email-report="requestEmailReport"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import type { CalculationResult, CalculatorInput, CalculatorStep } from '@/Types/homepage';
import { computed, onMounted, ref } from 'vue';

// Step Components (will be created)
import CalculatorResults from './calculator/CalculatorResults.vue';
import CalculatorStep1 from './calculator/CalculatorStep1.vue';
import CalculatorStep2 from './calculator/CalculatorStep2.vue';
import CalculatorStep3 from './calculator/CalculatorStep3.vue';
import CalculatorStep4 from './calculator/CalculatorStep4.vue';

// Props
interface Props {
    calculatorSteps?: CalculatorStep[];
}

const props = withDefaults(defineProps<Props>(), {
    calculatorSteps: () => [],
});

// Reactive state
const showCalculator = ref(false);
const currentStep = ref(1);
const totalSteps = ref(4);
const isLoading = ref(false);
const isSubmitting = ref(false);
const calculationResult = ref<CalculationResult | null>(null);
const errors = ref<Record<string, string>>({});

// Form data
const formData = ref<CalculatorInput>({
    currentRole: '',
    industry: '',
    experienceYears: 0,
    careerGoals: [],
    location: '',
    educationLevel: '',
    currentSalary: undefined,
    targetRole: '',
});

// Computed
const currentStepComponent = computed(() => {
    const stepComponents = {
        1: CalculatorStep1,
        2: CalculatorStep2,
        3: CalculatorStep3,
        4: CalculatorStep4,
    };
    return stepComponents[currentStep.value as keyof typeof stepComponents];
});

// Methods
const openCalculator = () => {
    showCalculator.value = true;
    // Track analytics
    trackEvent('calculator_opened', {
        source: 'homepage_cta',
    });
};

const closeCalculator = () => {
    showCalculator.value = false;
    // Track analytics
    trackEvent('calculator_closed', {
        step: currentStep.value,
        completed: calculationResult.value !== null,
    });
};

const updateFormData = (updates: Partial<CalculatorInput>) => {
    formData.value = { ...formData.value, ...updates };
    // Clear related errors
    Object.keys(updates).forEach((key) => {
        if (errors.value[key]) {
            delete errors.value[key];
        }
    });
};

const validateCurrentStep = (): boolean => {
    errors.value = {};

    switch (currentStep.value) {
        case 1:
            if (!formData.value.currentRole) {
                errors.value.currentRole = 'Current role is required';
            }
            if (!formData.value.industry) {
                errors.value.industry = 'Industry is required';
            }
            if (formData.value.experienceYears < 0) {
                errors.value.experienceYears = 'Experience years must be positive';
            }
            break;
        case 2:
            if (formData.value.careerGoals.length === 0) {
                errors.value.careerGoals = 'Please select at least one career goal';
            }
            break;
        case 3:
            if (!formData.value.location) {
                errors.value.location = 'Location is required';
            }
            if (!formData.value.educationLevel) {
                errors.value.educationLevel = 'Education level is required';
            }
            break;
        case 4:
            // Optional step, no validation required
            break;
    }

    return Object.keys(errors.value).length === 0;
};

const nextStep = () => {
    if (validateCurrentStep()) {
        if (currentStep.value < totalSteps.value) {
            currentStep.value++;
            trackEvent('calculator_step_completed', {
                step: currentStep.value - 1,
                data: formData.value,
            });
        }
    }
};

const previousStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const submitCalculator = async () => {
    if (!validateCurrentStep()) {
        return;
    }

    isSubmitting.value = true;

    try {
        // Call API to calculate results
        const response = await fetch('/api/calculator/calculate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(formData.value),
        });

        if (!response.ok) {
            throw new Error('Calculation failed');
        }

        const result = await response.json();
        calculationResult.value = result.data;
        currentStep.value = totalSteps.value + 1; // Show results

        trackEvent('calculator_completed', {
            formData: formData.value,
            result: result.data,
        });
    } catch (error) {
        console.error('Calculator submission error:', error);
        errors.value.submit = 'Failed to calculate results. Please try again.';
    } finally {
        isSubmitting.value = false;
    }
};

const restartCalculator = () => {
    currentStep.value = 1;
    calculationResult.value = null;
    formData.value = {
        currentRole: '',
        industry: '',
        experienceYears: 0,
        careerGoals: [],
        location: '',
        educationLevel: '',
        currentSalary: undefined,
        targetRole: '',
    };
    errors.value = {};
};

const requestEmailReport = async (email: string) => {
    try {
        const response = await fetch('/api/calculator/email-report', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                email,
                formData: formData.value,
                result: calculationResult.value,
            }),
        });

        if (response.ok) {
            trackEvent('email_report_requested', { email });
            // Show success message
        }
    } catch (error) {
        console.error('Email report request failed:', error);
    }
};

const trackEvent = (eventName: string, data: any) => {
    // Analytics tracking implementation
    if (typeof window !== 'undefined' && (window as any).gtag) {
        (window as any).gtag('event', eventName, {
            custom_parameter: data,
        });
    }
};

// Lifecycle
onMounted(() => {
    // Initialize any required data
});
</script>

<style scoped>
.value-calculator {
    @apply bg-gradient-to-br from-gray-50 to-blue-50 py-16;
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .value-calculator .container {
        @apply px-2;
    }

    .fixed .bg-white {
        @apply mx-2 max-w-none;
    }
}

/* Animation for modal */
.fixed {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.bg-white {
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>


