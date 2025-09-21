<template>
    <div class="calculator-step">
        <div class="mb-8">
            <h4 class="mb-2 text-2xl font-bold text-gray-900">What are your career goals?</h4>
            <p class="text-gray-600">Select all that apply. This helps us understand what success looks like for you.</p>
        </div>

        <div class="space-y-4">
            <!-- Career Goals Grid -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div
                    v-for="goal in careerGoalOptions"
                    :key="goal.value"
                    class="goal-option"
                    :class="{ selected: isGoalSelected(goal.value) }"
                    @click="toggleGoal(goal.value)"
                >
                    <div class="flex items-start space-x-3">
                        <div class="mt-1 flex-shrink-0">
                            <div class="checkbox" :class="{ checked: isGoalSelected(goal.value) }">
                                <svg v-if="isGoalSelected(goal.value)" class="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h5 class="mb-1 font-semibold text-gray-900">{{ goal.label }}</h5>
                            <p class="text-sm text-gray-600">{{ goal.description }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <p v-if="errors.careerGoals" class="text-sm text-red-600">
                {{ errors.careerGoals }}
            </p>
        </div>

        <!-- Target Role (Optional) -->
        <div class="mt-8">
            <label for="targetRole" class="mb-2 block text-sm font-medium text-gray-700"> Specific role you're targeting (optional) </label>
            <input
                type="text"
                id="targetRole"
                :value="formData.targetRole"
                @input="updateField('targetRole', ($event.target as HTMLInputElement).value)"
                placeholder="e.g., Senior Software Engineer, Marketing Director, VP of Sales"
                class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-blue-500"
            />
            <p class="mt-1 text-xs text-gray-500">This helps us provide more specific salary and timeline projections</p>
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

// Career goal options
const careerGoalOptions = [
    {
        value: 'salary_increase',
        label: 'Increase Salary',
        description: 'Get a significant raise or find a higher-paying position',
    },
    {
        value: 'promotion',
        label: 'Get Promoted',
        description: 'Advance to a higher position within your current company',
    },
    {
        value: 'job_change',
        label: 'Change Jobs',
        description: 'Find a new position at a different company',
    },
    {
        value: 'career_pivot',
        label: 'Career Pivot',
        description: 'Transition to a different industry or role type',
    },
    {
        value: 'leadership_role',
        label: 'Leadership Role',
        description: 'Move into management or executive positions',
    },
    {
        value: 'entrepreneurship',
        label: 'Start a Business',
        description: 'Launch your own company or become self-employed',
    },
    {
        value: 'skill_development',
        label: 'Develop New Skills',
        description: 'Learn new technologies or professional competencies',
    },
    {
        value: 'networking',
        label: 'Expand Network',
        description: 'Build professional relationships and industry connections',
    },
    {
        value: 'mentorship',
        label: 'Find Mentors',
        description: 'Connect with experienced professionals for guidance',
    },
    {
        value: 'work_life_balance',
        label: 'Better Work-Life Balance',
        description: 'Find roles with more flexibility and personal time',
    },
    {
        value: 'remote_work',
        label: 'Remote Work',
        description: 'Secure positions that allow working from anywhere',
    },
    {
        value: 'consulting',
        label: 'Consulting/Freelancing',
        description: 'Transition to independent consulting or freelance work',
    },
];

// Computed
const canProceed = computed(() => {
    return props.formData.careerGoals.length > 0;
});

// Methods
const updateField = (field: keyof CalculatorInput, value: any) => {
    emit('update', { [field]: value });
};

const isGoalSelected = (goalValue: string): boolean => {
    return props.formData.careerGoals.includes(goalValue);
};

const toggleGoal = (goalValue: string) => {
    const currentGoals = [...props.formData.careerGoals];
    const index = currentGoals.indexOf(goalValue);

    if (index > -1) {
        currentGoals.splice(index, 1);
    } else {
        currentGoals.push(goalValue);
    }

    updateField('careerGoals', currentGoals);
};

const handleNext = () => {
    if (canProceed.value) {
        emit('next');
    }
};
</script>

<style scoped>
.goal-option {
    @apply cursor-pointer rounded-lg border-2 border-gray-200 p-4 transition-all duration-200 hover:border-blue-300 hover:bg-blue-50;
}

.goal-option.selected {
    @apply border-blue-500 bg-blue-50;
}

.checkbox {
    @apply flex h-5 w-5 items-center justify-center rounded border-2 border-gray-300 transition-all duration-200;
}

.checkbox.checked {
    @apply border-blue-600 bg-blue-600;
}

.goal-option:hover .checkbox {
    @apply border-blue-400;
}

.goal-option.selected .checkbox {
    @apply border-blue-600 bg-blue-600;
}
</style>
