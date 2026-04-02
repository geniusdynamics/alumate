<template>
    <div class="calculator-step">
        <div class="mb-8">
            <h4 class="mb-2 text-2xl font-bold text-gray-900">Additional details</h4>
            <p class="text-gray-600">Help us provide more accurate and personalized recommendations.</p>
        </div>

        <div class="space-y-6">
            <!-- Location -->
            <div>
                <label for="location" class="mb-2 block text-sm font-medium text-gray-700"> Location (City, State/Country) * </label>
                <input
                    type="text"
                    id="location"
                    :value="formData.location"
                    @input="updateField('location', ($event.target as HTMLInputElement).value)"
                    placeholder="e.g., San Francisco, CA or London, UK"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    :class="{ 'border-red-500': errors.location }"
                />
                <p v-if="errors.location" class="mt-1 text-sm text-red-600">
                    {{ errors.location }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Location affects salary ranges and job market opportunities</p>
            </div>

            <!-- Education Level -->
            <div>
                <label for="educationLevel" class="mb-2 block text-sm font-medium text-gray-700"> Highest education level * </label>
                <select
                    id="educationLevel"
                    :value="formData.educationLevel"
                    @change="updateField('educationLevel', ($event.target as HTMLSelectElement).value)"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                    :class="{ 'border-red-500': errors.educationLevel }"
                >
                    <option value="">Select education level</option>
                    <option value="high_school">High School</option>
                    <option value="associate">Associate Degree</option>
                    <option value="bachelor">Bachelor's Degree</option>
                    <option value="master">Master's Degree</option>
                    <option value="mba">MBA</option>
                    <option value="doctorate">Doctorate/PhD</option>
                    <option value="professional">Professional Degree (JD, MD, etc.)</option>
                    <option value="certification">Professional Certifications</option>
                    <option value="bootcamp">Bootcamp/Trade School</option>
                    <option value="self_taught">Self-Taught</option>
                </select>
                <p v-if="errors.educationLevel" class="mt-1 text-sm text-red-600">
                    {{ errors.educationLevel }}
                </p>
            </div>

            <!-- Company Size Preference -->
            <div>
                <label class="mb-3 block text-sm font-medium text-gray-700"> Preferred company size </label>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div
                        v-for="size in companySizeOptions"
                        :key="size.value"
                        class="company-size-option"
                        :class="{ selected: formData.preferredCompanySize === size.value }"
                        @click="updateField('preferredCompanySize', size.value)"
                    >
                        <div class="flex items-center space-x-3">
                            <div class="radio" :class="{ checked: formData.preferredCompanySize === size.value }">
                                <div v-if="formData.preferredCompanySize === size.value" class="radio-dot"></div>
                            </div>
                            <div>
                                <h6 class="font-medium text-gray-900">{{ size.label }}</h6>
                                <p class="text-sm text-gray-600">{{ size.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Work Style Preference -->
            <div>
                <label class="mb-3 block text-sm font-medium text-gray-700"> Preferred work arrangement </label>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <div
                        v-for="style in workStyleOptions"
                        :key="style.value"
                        class="work-style-option"
                        :class="{ selected: formData.workStyle === style.value }"
                        @click="updateField('workStyle', style.value)"
                    >
                        <div class="text-center">
                            <div class="radio mx-auto mb-2" :class="{ checked: formData.workStyle === style.value }">
                                <div v-if="formData.workStyle === style.value" class="radio-dot"></div>
                            </div>
                            <h6 class="mb-1 font-medium text-gray-900">{{ style.label }}</h6>
                            <p class="text-xs text-gray-600">{{ style.description }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Skills to Develop -->
            <div>
                <label for="skillsToLearn" class="mb-2 block text-sm font-medium text-gray-700"> Skills you want to develop (optional) </label>
                <textarea
                    id="skillsToLearn"
                    :value="formData.skillsToLearn"
                    @input="updateField('skillsToLearn', ($event.target as HTMLTextAreaElement).value)"
                    placeholder="e.g., Python programming, project management, public speaking, data analysis..."
                    rows="3"
                    class="w-full resize-none rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                ></textarea>
                <p class="mt-1 text-xs text-gray-500">This helps us recommend relevant mentors and learning opportunities</p>
            </div>
        </div>

        <!-- Navigation -->
        <div class="mt-8 flex justify-between border-t border-gray-200 pt-6">
            <button
                @click="emit('previous')"
                class="rounded-lg px-6 py-3 font-semibold text-gray-600 transition-colors duration-200 hover:text-gray-800"
            >
                <svg class="mr-2 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Previous
            </button>
            <button
                @click="handleNext"
                :disabled="!canProceed"
                class="rounded-lg bg-blue-600 px-6 py-3 font-semibold text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-300"
            >
                Next Step
                <svg class="ml-2 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { CalculatorInput } from '@/types/homepage';
import { computed } from 'vue';

// Props
interface Props {
    formData: CalculatorInput;
    errors: Record<string, string>;
    isLoading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isLoading: false,
});

// Emits
const emit = defineEmits<{
    update: [updates: Partial<CalculatorInput>];
    next: [];
    previous: [];
}>();

// Company size options
const companySizeOptions = [
    {
        value: 'startup',
        label: 'Startup',
        description: '1-50 employees',
    },
    {
        value: 'small',
        label: 'Small Company',
        description: '51-200 employees',
    },
    {
        value: 'medium',
        label: 'Medium Company',
        description: '201-1000 employees',
    },
    {
        value: 'large',
        label: 'Large Company',
        description: '1000+ employees',
    },
    {
        value: 'enterprise',
        label: 'Enterprise',
        description: '10,000+ employees',
    },
    {
        value: 'no_preference',
        label: 'No Preference',
        description: 'Open to any size',
    },
];

// Work style options
const workStyleOptions = [
    {
        value: 'remote',
        label: 'Remote',
        description: 'Work from anywhere',
    },
    {
        value: 'hybrid',
        label: 'Hybrid',
        description: 'Mix of office and remote',
    },
    {
        value: 'office',
        label: 'In-Office',
        description: 'Traditional office setting',
    },
];

// Computed
const canProceed = computed(() => {
    return props.formData.location && props.formData.educationLevel;
});

// Methods
const updateField = (field: keyof CalculatorInput, value: any) => {
    emit('update', { [field]: value });
};

const handleNext = () => {
    if (canProceed.value) {
        emit('next');
    }
};
</script>

<style scoped>
.company-size-option,
.work-style-option {
    @apply cursor-pointer rounded-lg border-2 border-gray-200 p-4 transition-all duration-200 hover:border-blue-300 hover:bg-blue-50;
}

.company-size-option.selected,
.work-style-option.selected {
    @apply border-blue-500 bg-blue-50;
}

.radio {
    @apply flex h-5 w-5 items-center justify-center rounded-full border-2 border-gray-300 transition-all duration-200;
}

.radio.checked {
    @apply border-blue-600;
}

.radio-dot {
    @apply h-2.5 w-2.5 rounded-full bg-blue-600;
}

.company-size-option:hover .radio,
.work-style-option:hover .radio {
    @apply border-blue-400;
}
</style>
