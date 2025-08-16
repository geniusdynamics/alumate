<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                            🎉 Discover New Features
                        </h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Check out the latest additions to your alumni platform
                        </p>
                    </div>
                    <button
                        @click="$emit('close')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>

                <!-- Features Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <div
                        v-for="feature in features"
                        :key="feature.id"
                        class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors cursor-pointer"
                        @click="exploreFeature(feature)"
                    >
                        <!-- Feature Icon -->
                        <div class="flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg mb-4">
                            <component :is="getFeatureIcon(feature.icon)" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>

                        <!-- Feature Content -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                {{ feature.title }}
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                {{ feature.description }}
                            </p>
                            
                            <!-- Feature Tags -->
                            <div class="flex flex-wrap gap-2 mb-4">
                                <span
                                    v-for="tag in feature.tags"
                                    :key="tag"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                >
                                    {{ tag }}
                                </span>
                            </div>

                            <!-- Feature Status -->
                            <div class="flex items-center justify-between">
                                <span
                                    :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        feature.status === 'new' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                        feature.status === 'updated' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                        'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                                    ]"
                                >
                                    {{ feature.status === 'new' ? '✨ New' : feature.status === 'updated' ? '🔄 Updated' : '📈 Enhanced' }}
                                </span>
                                
                                <button class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                                    Explore →
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-2">
                        <input
                            id="dont-show-again"
                            v-model="dontShowAgain"
                            type="checkbox"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="dont-show-again" class="text-sm text-gray-600 dark:text-gray-400">
                            Don't show this again
                        </label>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button
                            @click="$emit('close')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            Maybe Later
                        </button>
                        <button
                            @click="startFeatureTour"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700"
                        >
                            Take a Tour
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import {
    ChatBubbleLeftRightIcon,
    UsersIcon,
    BriefcaseIcon,
    CalendarIcon,
    ChartBarIcon,
    MapIcon,
    AcademicCapIcon,
    HeartIcon,
    CurrencyDollarIcon,
    TrophyIcon,
    SparklesIcon,
    RocketLaunchIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    features: {
        type: Array,
        required: true
    }
})

const emit = defineEmits(['close', 'feature-explored'])

const dontShowAgain = ref(false)

const getFeatureIcon = (iconName) => {
    const icons = {
        'chat': ChatBubbleLeftRightIcon,
        'users': UsersIcon,
        'briefcase': BriefcaseIcon,
        'calendar': CalendarIcon,
        'chart': ChartBarIcon,
        'map': MapIcon,
        'academic': AcademicCapIcon,
        'heart': HeartIcon,
        'currency': CurrencyDollarIcon,
        'trophy': TrophyIcon,
        'sparkles': SparklesIcon,
        'rocket': RocketLaunchIcon
    }
    return icons[iconName] || SparklesIcon
}

const exploreFeature = (feature) => {
    emit('feature-explored', feature)
}

const startFeatureTour = () => {
    // Start a guided tour of new features
    window.dispatchEvent(new CustomEvent('start-feature-tour', {
        detail: { features: props.features }
    }))
    emit('close')
}
</script>