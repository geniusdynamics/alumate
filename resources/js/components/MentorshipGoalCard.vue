<template>
    <div
        class="mentorship-goal-card rounded-lg border border-gray-200 bg-white p-6 shadow-md transition-shadow duration-200 hover:shadow-lg dark:border-gray-700 dark:bg-gray-800"
    >
        <!-- Goal Header -->
        <div class="mb-4 flex items-start justify-between">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div :class="getGoalStatusIconClass(goal.status)" class="flex h-12 w-12 items-center justify-center rounded-lg">
                        <component :is="getGoalStatusIcon(goal.status)" class="h-6 w-6 text-white" />
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ goal.title }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ goal.category || 'Personal Development' }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span :class="getGoalStatusClass(goal.status)" class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium">
                    {{ formatGoalStatus(goal.status) }}
                </span>
            </div>
        </div>

        <!-- Goal Description -->
        <div class="mb-4">
            <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">{{ goal.description }}</p>
        </div>

        <!-- Progress Section -->
        <div class="mb-4">
            <div class="mb-2 flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progress</span>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ goal.progress_percentage || 0 }}%</span>
            </div>
            <div class="h-3 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                <div
                    :class="getProgressBarClass(goal.status)"
                    class="h-3 rounded-full transition-all duration-300"
                    :style="{ width: (goal.progress_percentage || 0) + '%' }"
                ></div>
            </div>
            <div v-if="goal.target_date" class="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>Target: {{ formatDate(goal.target_date) }}</span>
                <span v-if="getDaysRemaining(goal.target_date)" :class="getDaysRemainingClass(goal.target_date)">
                    {{ getDaysRemaining(goal.target_date) }}
                </span>
            </div>
        </div>

        <!-- Milestones -->
        <div v-if="goal.milestones && goal.milestones.length > 0" class="mb-4">
            <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Milestones</h4>
            <div class="space-y-2">
                <div v-for="milestone in goal.milestones.slice(0, 3)" :key="milestone.id" class="flex items-center space-x-3 text-sm">
                    <CheckCircleIcon v-if="milestone.completed" class="h-4 w-4 flex-shrink-0 text-green-500" />
                    <ClockIcon v-else-if="milestone.in_progress" class="h-4 w-4 flex-shrink-0 text-yellow-500" />
                    <StopIcon v-else class="h-4 w-4 flex-shrink-0 text-gray-400" />
                    <span :class="milestone.completed ? 'text-gray-500 line-through' : 'text-gray-700 dark:text-gray-300'" class="flex-1">
                        {{ milestone.title }}
                    </span>
                    <span v-if="milestone.due_date" class="text-xs text-gray-500 dark:text-gray-400">
                        {{ formatDate(milestone.due_date) }}
                    </span>
                </div>
                <div v-if="goal.milestones.length > 3" class="text-sm text-gray-500 dark:text-gray-400">
                    +{{ goal.milestones.length - 3 }} more milestones
                </div>
            </div>
        </div>

        <!-- Skills & Competencies -->
        <div v-if="goal.skills_focus && goal.skills_focus.length > 0" class="mb-4">
            <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Skills Focus</h4>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="skill in goal.skills_focus.slice(0, 5)"
                    :key="skill"
                    class="inline-flex items-center rounded-md bg-blue-100 px-2 py-1 text-xs text-blue-700 dark:bg-blue-900/20 dark:text-blue-300"
                >
                    {{ skill }}
                </span>
                <span
                    v-if="goal.skills_focus.length > 5"
                    class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                >
                    +{{ goal.skills_focus.length - 5 }}
                </span>
            </div>
        </div>

        <!-- Mentor Feedback -->
        <div v-if="goal.mentor_feedback" class="mb-4 rounded-md bg-blue-50 p-3 dark:bg-blue-900/20">
            <h4 class="mb-2 flex items-center text-sm font-medium text-gray-900 dark:text-white">
                <ChatBubbleLeftIcon class="mr-2 h-4 w-4 text-blue-500" />
                Mentor Feedback
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ goal.mentor_feedback.comment }}</p>
            <div class="mt-2 flex items-center justify-between">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ goal.mentor_feedback.mentor_name }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatDate(goal.mentor_feedback.created_at) }}
                </span>
            </div>
        </div>

        <!-- Resources & Actions -->
        <div v-if="goal.resources && goal.resources.length > 0" class="mb-4">
            <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Resources</h4>
            <div class="space-y-2">
                <div v-for="resource in goal.resources.slice(0, 2)" :key="resource.id" class="flex items-center justify-between text-sm">
                    <div class="flex items-center space-x-2">
                        <LinkIcon class="h-4 w-4 text-blue-500" />
                        <span class="text-gray-700 dark:text-gray-300">{{ resource.title }}</span>
                    </div>
                    <button @click="openResource(resource.url)" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">Open</button>
                </div>
                <div v-if="goal.resources.length > 2" class="text-sm text-gray-500 dark:text-gray-400">
                    +{{ goal.resources.length - 2 }} more resources
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-3">
            <button
                @click="updateProgress"
                class="flex-1 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
            >
                Update Progress
            </button>

            <button
                @click="viewDetails"
                class="rounded-md border border-blue-300 px-4 py-2 text-sm font-medium text-blue-600 transition-colors hover:border-blue-400 hover:text-blue-800 dark:border-blue-600 dark:text-blue-400 dark:hover:text-blue-200"
            >
                Details
            </button>

            <button
                @click="editGoal"
                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:border-gray-400 hover:text-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <PencilIcon class="h-4 w-4" />
            </button>
        </div>

        <!-- Goal Statistics -->
        <div v-if="goal.statistics" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ goal.statistics.sessions_count || 0 }}</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Sessions</div>
                </div>
                <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ goal.statistics.hours_spent || 0 }}h</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Time Spent</div>
                </div>
                <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ goal.statistics.completion_rate || 0 }}%</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Completion</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {
    ChatBubbleLeftIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    LinkIcon,
    PencilIcon,
    StopIcon,
    TrophyIcon,
} from '@heroicons/vue/24/outline';
import { differenceInDays, format } from 'date-fns';

const props = defineProps({
    goal: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['update-progress', 'view-details', 'edit-goal', 'open-resource']);

const getGoalStatusIconClass = (status) => {
    const classes = {
        not_started: 'bg-gray-500',
        in_progress: 'bg-blue-500',
        completed: 'bg-green-500',
        paused: 'bg-yellow-500',
        cancelled: 'bg-red-500',
    };
    return classes[status] || 'bg-gray-500';
};

const getGoalStatusIcon = (status) => {
    const icons = {
        not_started: StopIcon,
        in_progress: ClockIcon,
        completed: TrophyIcon,
        paused: ExclamationTriangleIcon,
        cancelled: ExclamationTriangleIcon,
    };
    return icons[status] || StopIcon;
};

const getGoalStatusClass = (status) => {
    const classes = {
        not_started: 'bg-gray-100 text-gray-800',
        in_progress: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        paused: 'bg-yellow-100 text-yellow-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getProgressBarClass = (status) => {
    const classes = {
        not_started: 'bg-gray-400',
        in_progress: 'bg-blue-600',
        completed: 'bg-green-600',
        paused: 'bg-yellow-500',
        cancelled: 'bg-red-500',
    };
    return classes[status] || 'bg-blue-600';
};

const formatGoalStatus = (status) => {
    return status.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy');
};

const getDaysRemaining = (targetDate) => {
    const days = differenceInDays(new Date(targetDate), new Date());
    if (days < 0) return 'Overdue';
    if (days === 0) return 'Due today';
    if (days === 1) return '1 day left';
    return `${days} days left`;
};

const getDaysRemainingClass = (targetDate) => {
    const days = differenceInDays(new Date(targetDate), new Date());
    if (days < 0) return 'text-red-600 font-medium';
    if (days <= 3) return 'text-orange-600 font-medium';
    if (days <= 7) return 'text-yellow-600 font-medium';
    return 'text-green-600';
};

const updateProgress = () => {
    emit('update-progress', props.goal.id);
};

const viewDetails = () => {
    emit('view-details', props.goal.id);
};

const editGoal = () => {
    emit('edit-goal', props.goal.id);
};

const openResource = (url) => {
    if (url) {
        window.open(url, '_blank');
    }
    emit('open-resource', url);
};
</script>

<style scoped>
.mentorship-goal-card {
    transition:
        transform 0.2s ease-in-out,
        box-shadow 0.2s ease-in-out;
}

.mentorship-goal-card:hover {
    transform: translateY(-2px);
}
</style>
