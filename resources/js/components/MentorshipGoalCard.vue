<template>
    <div class="mentorship-goal-card bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow duration-200">
        <!-- Goal Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div 
                        :class="getGoalStatusIconClass(goal.status)"
                        class="w-12 h-12 rounded-lg flex items-center justify-center"
                    >
                        <component 
                            :is="getGoalStatusIcon(goal.status)" 
                            class="w-6 h-6 text-white"
                        />
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ goal.title }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ goal.category || 'Personal Development' }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span 
                    :class="getGoalStatusClass(goal.status)"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                >
                    {{ formatGoalStatus(goal.status) }}
                </span>
            </div>
        </div>

        <!-- Goal Description -->
        <div class="mb-4">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ goal.description }}</p>
        </div>

        <!-- Progress Section -->
        <div class="mb-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progress</span>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ goal.progress_percentage || 0 }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                <div 
                    :class="getProgressBarClass(goal.status)"
                    class="h-3 rounded-full transition-all duration-300"
                    :style="{ width: (goal.progress_percentage || 0) + '%' }"
                ></div>
            </div>
            <div v-if="goal.target_date" class="flex justify-between items-center mt-2 text-xs text-gray-500 dark:text-gray-400">
                <span>Target: {{ formatDate(goal.target_date) }}</span>
                <span v-if="getDaysRemaining(goal.target_date)" :class="getDaysRemainingClass(goal.target_date)">
                    {{ getDaysRemaining(goal.target_date) }}
                </span>
            </div>
        </div>

        <!-- Milestones -->
        <div v-if="goal.milestones && goal.milestones.length > 0" class="mb-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Milestones</h4>
            <div class="space-y-2">
                <div 
                    v-for="milestone in goal.milestones.slice(0, 3)"
                    :key="milestone.id"
                    class="flex items-center space-x-3 text-sm"
                >
                    <CheckCircleIcon 
                        v-if="milestone.completed" 
                        class="w-4 h-4 text-green-500 flex-shrink-0" 
                    />
                    <ClockIcon 
                        v-else-if="milestone.in_progress" 
                        class="w-4 h-4 text-yellow-500 flex-shrink-0" 
                    />
                    <StopIcon 
                        v-else 
                        class="w-4 h-4 text-gray-400 flex-shrink-0" 
                    />
                    <span 
                        :class="milestone.completed ? 'line-through text-gray-500' : 'text-gray-700 dark:text-gray-300'"
                        class="flex-1"
                    >
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
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Skills Focus</h4>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="skill in goal.skills_focus.slice(0, 5)"
                    :key="skill"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300"
                >
                    {{ skill }}
                </span>
                <span
                    v-if="goal.skills_focus.length > 5"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                >
                    +{{ goal.skills_focus.length - 5 }}
                </span>
            </div>
        </div>

        <!-- Mentor Feedback -->
        <div v-if="goal.mentor_feedback" class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 flex items-center">
                <ChatBubbleLeftIcon class="w-4 h-4 text-blue-500 mr-2" />
                Mentor Feedback
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ goal.mentor_feedback.comment }}</p>
            <div class="flex items-center justify-between mt-2">
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
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Resources</h4>
            <div class="space-y-2">
                <div 
                    v-for="resource in goal.resources.slice(0, 2)"
                    :key="resource.id"
                    class="flex items-center justify-between text-sm"
                >
                    <div class="flex items-center space-x-2">
                        <LinkIcon class="w-4 h-4 text-blue-500" />
                        <span class="text-gray-700 dark:text-gray-300">{{ resource.title }}</span>
                    </div>
                    <button
                        @click="openResource(resource.url)"
                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-xs"
                    >
                        Open
                    </button>
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
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
            >
                Update Progress
            </button>
            
            <button
                @click="viewDetails"
                class="px-4 py-2 text-blue-600 hover:text-blue-800 border border-blue-300 hover:border-blue-400 rounded-md text-sm font-medium transition-colors dark:text-blue-400 dark:hover:text-blue-200 dark:border-blue-600"
            >
                Details
            </button>
            
            <button
                @click="editGoal"
                class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 hover:border-gray-400 rounded-md text-sm font-medium transition-colors dark:text-gray-400 dark:hover:text-gray-200 dark:border-gray-600"
            >
                <PencilIcon class="w-4 h-4" />
            </button>
        </div>

        <!-- Goal Statistics -->
        <div v-if="goal.statistics" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
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
import { format, differenceInDays, isPast } from 'date-fns'
import {
    CheckCircleIcon,
    ClockIcon,
    StopIcon,
    ChatBubbleLeftIcon,
    LinkIcon,
    PencilIcon,
    TrophyIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    goal: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['update-progress', 'view-details', 'edit-goal', 'open-resource'])

const getGoalStatusIconClass = (status) => {
    const classes = {
        'not_started': 'bg-gray-500',
        'in_progress': 'bg-blue-500',
        'completed': 'bg-green-500',
        'paused': 'bg-yellow-500',
        'cancelled': 'bg-red-500'
    }
    return classes[status] || 'bg-gray-500'
}

const getGoalStatusIcon = (status) => {
    const icons = {
        'not_started': StopIcon,
        'in_progress': ClockIcon,
        'completed': TrophyIcon,
        'paused': ExclamationTriangleIcon,
        'cancelled': ExclamationTriangleIcon
    }
    return icons[status] || StopIcon
}

const getGoalStatusClass = (status) => {
    const classes = {
        'not_started': 'bg-gray-100 text-gray-800',
        'in_progress': 'bg-blue-100 text-blue-800',
        'completed': 'bg-green-100 text-green-800',
        'paused': 'bg-yellow-100 text-yellow-800',
        'cancelled': 'bg-red-100 text-red-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getProgressBarClass = (status) => {
    const classes = {
        'not_started': 'bg-gray-400',
        'in_progress': 'bg-blue-600',
        'completed': 'bg-green-600',
        'paused': 'bg-yellow-500',
        'cancelled': 'bg-red-500'
    }
    return classes[status] || 'bg-blue-600'
}

const formatGoalStatus = (status) => {
    return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy')
}

const getDaysRemaining = (targetDate) => {
    const days = differenceInDays(new Date(targetDate), new Date())
    if (days < 0) return 'Overdue'
    if (days === 0) return 'Due today'
    if (days === 1) return '1 day left'
    return `${days} days left`
}

const getDaysRemainingClass = (targetDate) => {
    const days = differenceInDays(new Date(targetDate), new Date())
    if (days < 0) return 'text-red-600 font-medium'
    if (days <= 3) return 'text-orange-600 font-medium'
    if (days <= 7) return 'text-yellow-600 font-medium'
    return 'text-green-600'
}

const updateProgress = () => {
    emit('update-progress', props.goal.id)
}

const viewDetails = () => {
    emit('view-details', props.goal.id)
}

const editGoal = () => {
    emit('edit-goal', props.goal.id)
}

const openResource = (url) => {
    if (url) {
        window.open(url, '_blank')
    }
    emit('open-resource', url)
}
</script>

<style scoped>
.mentorship-goal-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.mentorship-goal-card:hover {
    transform: translateY(-2px);
}
</style>
