<template>
    <div class="rounded-lg border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-6">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="flex items-center text-lg font-semibold text-gray-900">
                <FlagIcon class="mr-2 h-5 w-5 text-blue-600" />
                Career Goals & Suggestions
            </h3>
            <button @click="collapsed = !collapsed" class="text-gray-400 transition-colors hover:text-gray-600">
                <ChevronDownIcon :class="['h-5 w-5 transition-transform', { 'rotate-180': !collapsed }]" />
            </button>
        </div>

        <div v-if="!collapsed" class="space-y-4">
            <p class="mb-4 text-sm text-gray-600">Based on your career progression, here are some personalized suggestions for your next steps:</p>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="suggestion in suggestions"
                    :key="suggestion.type"
                    class="rounded-lg border border-gray-200 bg-white p-4 transition-shadow hover:shadow-md"
                >
                    <!-- Priority indicator -->
                    <div class="mb-3 flex items-center justify-between">
                        <span
                            :class="[
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                getPriorityColor(suggestion.priority),
                            ]"
                        >
                            {{ formatPriority(suggestion.priority) }}
                        </span>
                        <component :is="getSuggestionIcon(suggestion.type)" class="h-5 w-5 text-gray-400" />
                    </div>

                    <!-- Content -->
                    <h4 class="mb-2 font-medium text-gray-900">{{ suggestion.title }}</h4>
                    <p class="mb-4 text-sm text-gray-600">{{ suggestion.description }}</p>

                    <!-- Actions -->
                    <div class="flex items-center space-x-2">
                        <button
                            @click="markAsCompleted(suggestion)"
                            class="flex-1 rounded-lg bg-blue-100 px-3 py-2 text-xs font-medium text-blue-700 transition-colors hover:bg-blue-200"
                        >
                            Mark as Goal
                        </button>
                        <button
                            @click="dismissSuggestion(suggestion)"
                            class="rounded-lg bg-gray-100 px-3 py-2 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-200"
                        >
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>

            <!-- Custom goal input -->
            <div class="mt-6 border-t border-gray-200 pt-4">
                <div class="flex items-center space-x-3">
                    <input
                        v-model="customGoal"
                        type="text"
                        placeholder="Add your own career goal..."
                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        @keyup.enter="addCustomGoal"
                    />
                    <button
                        @click="addCustomGoal"
                        :disabled="!customGoal.trim()"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Add Goal
                    </button>
                </div>
            </div>

            <!-- Active goals -->
            <div v-if="activeGoals.length > 0" class="mt-6 border-t border-gray-200 pt-4">
                <h4 class="mb-3 font-medium text-gray-900">Active Goals</h4>
                <div class="space-y-2">
                    <div
                        v-for="goal in activeGoals"
                        :key="goal.id"
                        class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-3"
                    >
                        <div class="flex items-center space-x-3">
                            <input
                                type="checkbox"
                                :checked="goal.completed"
                                @change="toggleGoalCompletion(goal)"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span :class="['text-sm', goal.completed ? 'text-gray-500 line-through' : 'text-gray-900']">
                                {{ goal.title }}
                            </span>
                        </div>
                        <button @click="removeGoal(goal)" class="text-gray-400 transition-colors hover:text-red-600">
                            <XMarkIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import {
    AcademicCapIcon,
    BriefcaseIcon,
    ChevronDownIcon,
    FlagIcon,
    ArrowTrendingUpIcon as TrendingUpIcon,
    TrophyIcon,
    UserGroupIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    suggestions: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['goalAdded', 'goalCompleted', 'suggestionDismissed']);

// Reactive data
const collapsed = ref(false);
const customGoal = ref('');
const activeGoals = ref([
    // Mock data - in real app this would come from API
    { id: 1, title: 'Complete leadership training', completed: false, type: 'custom' },
    { id: 2, title: 'Get AWS certification', completed: true, type: 'certification' },
]);

// Methods
const getPriorityColor = (priority) => {
    const colors = {
        high: 'bg-red-100 text-red-800',
        medium: 'bg-yellow-100 text-yellow-800',
        low: 'bg-green-100 text-green-800',
    };
    return colors[priority] || 'bg-gray-100 text-gray-800';
};

const formatPriority = (priority) => {
    return priority.charAt(0).toUpperCase() + priority.slice(1) + ' Priority';
};

const getSuggestionIcon = (type) => {
    const icons = {
        skill_development: AcademicCapIcon,
        specialization: TrophyIcon,
        leadership: UserGroupIcon,
        career_move: BriefcaseIcon,
        certification: AcademicCapIcon,
        networking: UserGroupIcon,
    };
    return icons[type] || TrendingUpIcon;
};

const markAsCompleted = (suggestion) => {
    const newGoal = {
        id: Date.now(),
        title: suggestion.title,
        completed: false,
        type: suggestion.type,
        description: suggestion.description,
    };

    activeGoals.value.push(newGoal);
    emit('goalAdded', newGoal);
};

const dismissSuggestion = (suggestion) => {
    emit('suggestionDismissed', suggestion);
};

const addCustomGoal = () => {
    if (!customGoal.value.trim()) return;

    const newGoal = {
        id: Date.now(),
        title: customGoal.value.trim(),
        completed: false,
        type: 'custom',
    };

    activeGoals.value.push(newGoal);
    customGoal.value = '';
    emit('goalAdded', newGoal);
};

const toggleGoalCompletion = (goal) => {
    goal.completed = !goal.completed;
    emit('goalCompleted', goal);
};

const removeGoal = (goal) => {
    const index = activeGoals.value.findIndex((g) => g.id === goal.id);
    if (index > -1) {
        activeGoals.value.splice(index, 1);
    }
};
</script>

