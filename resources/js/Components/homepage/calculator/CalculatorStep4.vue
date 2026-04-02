<template>
    <div class="calculator-step">
        <div class="mb-8">
            <h4 class="mb-2 text-2xl font-bold text-gray-900">Final details</h4>
            <p class="text-gray-600">Just a few more questions to personalize your career insights.</p>
        </div>

        <div class="space-y-6">
            <!-- Timeline for Goals -->
            <div>
                <label class="mb-3 block text-sm font-medium text-gray-700"> When do you want to achieve your primary career goal? </label>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div
                        v-for="timeline in timelineOptions"
                        :key="timeline.value"
                        class="timeline-option"
                        :class="{ selected: formData.goalTimeline === timeline.value }"
                        @click="updateField('goalTimeline', timeline.value)"
                    >
                        <div class="flex items-center space-x-3">
                            <div class="radio" :class="{ checked: formData.goalTimeline === timeline.value }">
                                <div v-if="formData.goalTimeline === timeline.value" class="radio-dot"></div>
                            </div>
                            <div>
                                <h6 class="font-medium text-gray-900">{{ timeline.label }}</h6>
                                <p class="text-sm text-gray-600">{{ timeline.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Challenges -->
            <div>
                <label class="mb-3 block text-sm font-medium text-gray-700"> What's your biggest career challenge right now? </label>
                <div class="space-y-2">
                    <div
                        v-for="challenge in challengeOptions"
                        :key="challenge.value"
                        class="challenge-option"
                        :class="{ selected: formData.primaryChallenge === challenge.value }"
                        @click="updateField('primaryChallenge', challenge.value)"
                    >
                        <div class="flex items-start space-x-3">
                            <div class="radio mt-0.5" :class="{ checked: formData.primaryChallenge === challenge.value }">
                                <div v-if="formData.primaryChallenge === challenge.value" class="radio-dot"></div>
                            </div>
                            <div class="flex-1">
                                <h6 class="font-medium text-gray-900">{{ challenge.label }}</h6>
                                <p class="text-sm text-gray-600">{{ challenge.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Networking Experience -->
            <div>
                <label class="mb-3 block text-sm font-medium text-gray-700"> How would you rate your current professional networking? </label>
                <div class="networking-scale">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="text-sm text-gray-500">Beginner</span>
                        <span class="text-sm text-gray-500">Expert</span>
                    </div>
                    <input
                        type="range"
                        :value="formData.networkingLevel || 3"
                        @input="updateField('networkingLevel', parseInt(($event.target as HTMLInputElement).value))"
                        min="1"
                        max="5"
                        step="1"
                        class="slider h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200"
                    />
                    <div class="mt-1 flex justify-between text-xs text-gray-500">
                        <span>1</span>
                        <span>2</span>
                        <span>3</span>
                        <span>4</span>
                        <span>5</span>
                    </div>
                    <div class="mt-2 text-center">
                        <span class="text-sm font-medium text-blue-600">
                            {{ getNetworkingLevelLabel(formData.networkingLevel || 3) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Willingness to Invest -->
            <div>
                <label class="mb-3 block text-sm font-medium text-gray-700"> How much time can you dedicate to networking per week? </label>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div
                        v-for="time in timeInvestmentOptions"
                        :key="time.value"
                        class="time-option"
                        :class="{ selected: formData.timeInvestment === time.value }"
                        @click="updateField('timeInvestment', time.value)"
                    >
                        <div class="text-center">
                            <div class="radio mx-auto mb-2" :class="{ checked: formData.timeInvestment === time.value }">
                                <div v-if="formData.timeInvestment === time.value" class="radio-dot"></div>
                            </div>
                            <h6 class="mb-1 font-medium text-gray-900">{{ time.label }}</h6>
                            <p class="text-xs text-gray-600">{{ time.description }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Comments -->
            <div>
                <label for="additionalInfo" class="mb-2 block text-sm font-medium text-gray-700">
                    Anything else you'd like us to know? (optional)
                </label>
                <textarea
                    id="additionalInfo"
                    :value="formData.additionalInfo"
                    @input="updateField('additionalInfo', ($event.target as HTMLTextAreaElement).value)"
                    placeholder="Share any specific goals, concerns, or context that might help us provide better recommendations..."
                    rows="4"
                    class="w-full resize-none rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-blue-500"
                ></textarea>
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
                @click="handleSubmit"
                :disabled="isLoading"
                class="rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-3 font-semibold text-white shadow-lg transition-all duration-200 hover:from-blue-700 hover:to-indigo-700 hover:shadow-xl disabled:cursor-not-allowed disabled:from-gray-300 disabled:to-gray-300"
            >
                <span v-if="!isLoading" class="flex items-center">
                    Calculate My Career Value
                    <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </span>
                <span v-else class="flex items-center">
                    <svg class="-ml-1 mr-3 h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    Calculating...
                </span>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { CalculatorInput } from '@/types/homepage';

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
    submit: [];
    previous: [];
}>();

// Timeline options
const timelineOptions = [
    {
        value: '3_months',
        label: '3 months',
        description: 'Urgent career change needed',
    },
    {
        value: '6_months',
        label: '6 months',
        description: 'Actively looking for opportunities',
    },
    {
        value: '1_year',
        label: '1 year',
        description: 'Planning ahead strategically',
    },
    {
        value: '2_years',
        label: '2+ years',
        description: 'Long-term career planning',
    },
];

// Challenge options
const challengeOptions = [
    {
        value: 'finding_opportunities',
        label: 'Finding the right opportunities',
        description: 'Struggling to discover relevant job openings or career paths',
    },
    {
        value: 'networking',
        label: 'Building professional connections',
        description: 'Difficulty expanding network or making meaningful connections',
    },
    {
        value: 'skill_gaps',
        label: 'Skill development',
        description: 'Need to learn new skills or technologies for career growth',
    },
    {
        value: 'interview_performance',
        label: 'Interview and application process',
        description: 'Getting interviews but not converting to offers',
    },
    {
        value: 'career_direction',
        label: 'Unclear career direction',
        description: 'Unsure about next steps or long-term career path',
    },
    {
        value: 'work_life_balance',
        label: 'Work-life balance',
        description: 'Finding roles that offer better personal time and flexibility',
    },
    {
        value: 'salary_negotiation',
        label: 'Salary negotiation',
        description: 'Getting fair compensation for skills and experience',
    },
    {
        value: 'industry_transition',
        label: 'Industry transition',
        description: 'Moving to a different industry or field',
    },
];

// Time investment options
const timeInvestmentOptions = [
    {
        value: '1_hour',
        label: '1-2 hours',
        description: 'Light networking',
    },
    {
        value: '3_hours',
        label: '3-5 hours',
        description: 'Moderate engagement',
    },
    {
        value: '6_hours',
        label: '6-10 hours',
        description: 'Active networking',
    },
    {
        value: '10_hours',
        label: '10+ hours',
        description: 'Intensive focus',
    },
];

// Methods
const updateField = (field: keyof CalculatorInput, value: any) => {
    emit('update', { [field]: value });
};

const getNetworkingLevelLabel = (level: number): string => {
    const labels = {
        1: 'Just starting out',
        2: 'Some experience',
        3: 'Comfortable networking',
        4: 'Strong networker',
        5: 'Networking expert',
    };
    return labels[level as keyof typeof labels] || 'Comfortable networking';
};

const handleSubmit = () => {
    emit('submit');
};
</script>

<style scoped>
.timeline-option,
.challenge-option,
.time-option {
    @apply cursor-pointer rounded-lg border-2 border-gray-200 p-4 transition-all duration-200 hover:border-blue-300 hover:bg-blue-50;
}

.timeline-option.selected,
.challenge-option.selected,
.time-option.selected {
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

.timeline-option:hover .radio,
.challenge-option:hover .radio,
.time-option:hover .radio {
    @apply border-blue-400;
}

/* Custom slider styling */
.slider::-webkit-slider-thumb {
    appearance: none;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.slider::-moz-range-thumb {
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background: #3b82f6;
    cursor: pointer;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.slider::-webkit-slider-track {
    height: 8px;
    border-radius: 4px;
    background: #e5e7eb;
}

.slider::-moz-range-track {
    height: 8px;
    border-radius: 4px;
    background: #e5e7eb;
}
</style>














