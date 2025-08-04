<template>
    <div class="career-tool-card bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow duration-200">
        <!-- Tool Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div 
                        :class="getToolIconClass(tool.category)"
                        class="w-12 h-12 rounded-lg flex items-center justify-center"
                    >
                        <component 
                            :is="getToolIcon(tool.category)" 
                            class="w-6 h-6 text-white"
                        />
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ tool.title }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ tool.category }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <span 
                    :class="getToolStatusClass(tool.status)"
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                >
                    {{ formatToolStatus(tool.status) }}
                </span>
            </div>
        </div>

        <!-- Tool Description -->
        <div class="mb-4">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ tool.description }}</p>
            
            <!-- Tool Features -->
            <div v-if="tool.features && tool.features.length > 0" class="mb-3">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Features:</h4>
                <ul class="space-y-1">
                    <li 
                        v-for="feature in tool.features.slice(0, 3)"
                        :key="feature"
                        class="flex items-center text-sm text-gray-600 dark:text-gray-400"
                    >
                        <CheckIcon class="w-4 h-4 text-green-500 mr-2 flex-shrink-0" />
                        {{ feature }}
                    </li>
                    <li v-if="tool.features.length > 3" class="text-sm text-gray-500 dark:text-gray-400">
                        +{{ tool.features.length - 3 }} more features
                    </li>
                </ul>
            </div>
        </div>

        <!-- Tool Metrics -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ tool.completion_time || 'N/A' }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Est. Time</div>
            </div>
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ tool.difficulty_level || 'Beginner' }}</div>
                <div class="text-xs text-gray-600 dark:text-gray-400">Difficulty</div>
            </div>
        </div>

        <!-- Progress Bar (if user has started) -->
        <div v-if="tool.user_progress" class="mb-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Progress</span>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ tool.user_progress.percentage }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                <div 
                    class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: tool.user_progress.percentage + '%' }"
                ></div>
            </div>
            <div v-if="tool.user_progress.last_accessed" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Last accessed: {{ formatDate(tool.user_progress.last_accessed) }}
            </div>
        </div>

        <!-- Tool Tags -->
        <div v-if="tool.tags && tool.tags.length > 0" class="mb-4">
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="tag in tool.tags.slice(0, 4)"
                    :key="tag"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300"
                >
                    {{ tag }}
                </span>
                <span
                    v-if="tool.tags.length > 4"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400"
                >
                    +{{ tool.tags.length - 4 }}
                </span>
            </div>
        </div>

        <!-- Prerequisites -->
        <div v-if="tool.prerequisites && tool.prerequisites.length > 0" class="mb-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Prerequisites:</h4>
            <div class="flex flex-wrap gap-2">
                <span
                    v-for="prereq in tool.prerequisites"
                    :key="prereq"
                    class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300"
                >
                    {{ prereq }}
                </span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-3">
            <button
                @click="useTool"
                :disabled="!tool.is_available"
                :class="getActionButtonClass()"
                class="flex-1 px-4 py-2 rounded-md text-sm font-medium transition-colors"
            >
                {{ getActionButtonText() }}
            </button>
            
            <button
                v-if="tool.has_preview"
                @click="previewTool"
                class="px-4 py-2 text-blue-600 hover:text-blue-800 border border-blue-300 hover:border-blue-400 rounded-md text-sm font-medium transition-colors dark:text-blue-400 dark:hover:text-blue-200 dark:border-blue-600"
            >
                Preview
            </button>
            
            <button
                v-if="tool.external_link"
                @click="openExternal"
                class="px-4 py-2 text-gray-600 hover:text-gray-800 border border-gray-300 hover:border-gray-400 rounded-md text-sm font-medium transition-colors dark:text-gray-400 dark:hover:text-gray-200 dark:border-gray-600"
            >
                <ArrowTopRightOnSquareIcon class="w-4 h-4" />
            </button>
        </div>

        <!-- Tool Rating -->
        <div v-if="tool.rating" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <div class="flex items-center">
                        <StarIcon 
                            v-for="star in 5"
                            :key="star"
                            :class="star <= tool.rating.average ? 'text-yellow-400' : 'text-gray-300'"
                            class="w-4 h-4"
                        />
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ tool.rating.average.toFixed(1) }} ({{ tool.rating.count }} reviews)
                    </span>
                </div>
                <button
                    v-if="tool.user_progress && tool.user_progress.completed"
                    @click="rateTool"
                    class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400"
                >
                    Rate Tool
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { format } from 'date-fns'
import {
    CheckIcon,
    StarIcon,
    ArrowTopRightOnSquareIcon,
    BriefcaseIcon,
    AcademicCapIcon,
    ChartBarIcon,
    UserGroupIcon,
    DocumentTextIcon,
    CogIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    tool: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['use-tool', 'preview-tool', 'rate-tool'])

const getToolIconClass = (category) => {
    const classes = {
        'assessment': 'bg-blue-500',
        'planning': 'bg-green-500',
        'networking': 'bg-purple-500',
        'skills': 'bg-orange-500',
        'interview': 'bg-red-500',
        'resume': 'bg-indigo-500'
    }
    return classes[category] || 'bg-gray-500'
}

const getToolIcon = (category) => {
    const icons = {
        'assessment': ChartBarIcon,
        'planning': DocumentTextIcon,
        'networking': UserGroupIcon,
        'skills': AcademicCapIcon,
        'interview': BriefcaseIcon,
        'resume': DocumentTextIcon
    }
    return icons[category] || CogIcon
}

const getToolStatusClass = (status) => {
    const classes = {
        'available': 'bg-green-100 text-green-800',
        'premium': 'bg-yellow-100 text-yellow-800',
        'coming_soon': 'bg-gray-100 text-gray-800',
        'maintenance': 'bg-red-100 text-red-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatToolStatus = (status) => {
    return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const getActionButtonClass = () => {
    if (!props.tool.is_available) {
        return 'bg-gray-300 text-gray-500 cursor-not-allowed'
    }
    
    if (props.tool.user_progress && props.tool.user_progress.percentage > 0) {
        return 'bg-blue-600 hover:bg-blue-700 text-white'
    }
    
    return 'bg-green-600 hover:bg-green-700 text-white'
}

const getActionButtonText = () => {
    if (!props.tool.is_available) {
        return 'Not Available'
    }
    
    if (props.tool.user_progress) {
        if (props.tool.user_progress.completed) {
            return 'Review Results'
        } else if (props.tool.user_progress.percentage > 0) {
            return 'Continue'
        }
    }
    
    return 'Start Tool'
}

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy')
}

const useTool = () => {
    if (props.tool.is_available) {
        emit('use-tool', props.tool.id)
    }
}

const previewTool = () => {
    emit('preview-tool', props.tool.id)
}

const rateTool = () => {
    emit('rate-tool', props.tool.id)
}

const openExternal = () => {
    if (props.tool.external_link) {
        window.open(props.tool.external_link, '_blank')
    }
}
</script>

<style scoped>
.career-tool-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.career-tool-card:hover {
    transform: translateY(-2px);
}
</style>
