<template>
    <div class="fixed top-4 right-4 z-50 max-w-sm">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Header -->
            <div class="p-4 bg-gradient-to-r from-green-500 to-blue-600">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                            <SparklesIcon class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h3 class="text-white font-semibold">What's New</h3>
                            <p class="text-green-100 text-sm">{{ updates.length }} new update{{ updates.length !== 1 ? 's' : '' }}</p>
                        </div>
                    </div>
                    <button
                        @click="$emit('close')"
                        class="text-white hover:text-green-100"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Updates List -->
            <div class="max-h-96 overflow-y-auto">
                <div
                    v-for="update in updates"
                    :key="update.id"
                    class="p-4 border-b border-gray-200 dark:border-gray-700 last:border-b-0"
                    :class="{ 'bg-blue-50 dark:bg-blue-900 bg-opacity-30': !update.read }"
                >
                    <!-- Update Header -->
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center space-x-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center"
                                 :class="getUpdateTypeStyle(update.type)">
                                <component :is="getUpdateIcon(update.type)" class="w-3 h-3" />
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ update.title }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatDate(update.created_at) }}
                                </p>
                            </div>
                        </div>
                        <span
                            v-if="!update.read"
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                        >
                            New
                        </span>
                    </div>

                    <!-- Update Content -->
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                        {{ update.description }}
                    </p>

                    <!-- Update Features -->
                    <div v-if="update.features && update.features.length > 0" class="mb-3">
                        <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                            <li
                                v-for="feature in update.features"
                                :key="feature"
                                class="flex items-center space-x-2"
                            >
                                <CheckCircleIcon class="w-3 h-3 text-green-500 flex-shrink-0" />
                                <span>{{ feature }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Update Actions -->
                    <div v-if="update.actions && update.actions.length > 0" class="flex space-x-2">
                        <button
                            v-for="action in update.actions"
                            :key="action.label"
                            @click="performUpdateAction(update, action)"
                            class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded font-medium"
                        >
                            {{ action.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-4 bg-gray-50 dark:bg-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <input
                            id="auto-show-updates"
                            v-model="autoShowUpdates"
                            type="checkbox"
                            class="h-3 w-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="auto-show-updates" class="text-xs text-gray-600 dark:text-gray-400">
                            Show new updates automatically
                        </label>
                    </div>
                    
                    <button
                        @click="viewAllUpdates"
                        class="text-xs text-blue-600 hover:text-blue-500 font-medium"
                    >
                        View All
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { format } from 'date-fns'
import {
    XMarkIcon,
    SparklesIcon,
    CheckCircleIcon,
    PlusIcon,
    ArrowPathIcon,
    BugAntIcon,
    ShieldCheckIcon,
    RocketLaunchIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    updates: {
        type: Array,
        required: true
    }
})

const emit = defineEmits(['close', 'view-details'])

const autoShowUpdates = ref(true)

const getUpdateTypeStyle = (type) => {
    const styles = {
        'feature': 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400',
        'improvement': 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400',
        'bugfix': 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400',
        'security': 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400',
        'announcement': 'bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-400'
    }
    return styles[type] || styles.feature
}

const getUpdateIcon = (type) => {
    const icons = {
        'feature': PlusIcon,
        'improvement': ArrowPathIcon,
        'bugfix': BugAntIcon,
        'security': ShieldCheckIcon,
        'announcement': RocketLaunchIcon
    }
    return icons[type] || PlusIcon
}

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM d, yyyy')
}

const performUpdateAction = (update, action) => {
    if (action.type === 'navigate') {
        emit('view-details', { ...update, route: action.url })
    } else if (action.type === 'modal') {
        window.dispatchEvent(new CustomEvent('show-update-modal', {
            detail: { update, action }
        }))
    } else if (action.type === 'feature-spotlight') {
        window.dispatchEvent(new CustomEvent('show-feature-spotlight', {
            detail: { feature: action.feature }
        }))
    }
}

const viewAllUpdates = () => {
    // Save auto-show preference
    localStorage.setItem('autoShowUpdates', autoShowUpdates.value.toString())
    
    // Navigate to updates page
    window.location.href = '/updates'
}
</script>