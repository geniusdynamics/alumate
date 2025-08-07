<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                <!-- Feature Header -->
                <div class="text-center mb-6">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 mb-4">
                        <component :is="getFeatureIcon(feature.icon)" class="w-8 h-8 text-white" />
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ feature.title }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ feature.subtitle }}
                    </p>
                </div>

                <!-- Feature Preview -->
                <div v-if="feature.preview" class="mb-6">
                    <div class="relative rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                        <img
                            v-if="feature.preview.type === 'image'"
                            :src="feature.preview.src"
                            :alt="feature.preview.alt"
                            class="w-full h-48 object-cover"
                        >
                        <div
                            v-else-if="feature.preview.type === 'video'"
                            class="w-full h-48 bg-gray-200 dark:bg-gray-600 flex items-center justify-center"
                        >
                            <button
                                @click="playPreviewVideo"
                                class="flex items-center space-x-2 bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-900 px-4 py-2 rounded-lg transition-all"
                            >
                                <PlayIcon class="w-5 h-5" />
                                <span>Watch Preview</span>
                            </button>
                        </div>
                        <div
                            v-else
                            class="w-full h-48 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900 dark:to-purple-900 flex items-center justify-center"
                        >
                            <component :is="getFeatureIcon(feature.icon)" class="w-16 h-16 text-blue-500 opacity-50" />
                        </div>
                    </div>
                </div>

                <!-- Feature Description -->
                <div class="mb-6">
                    <p class="text-gray-700 dark:text-gray-300 mb-4">
                        {{ feature.description }}
                    </p>
                    
                    <!-- Key Benefits -->
                    <div v-if="feature.benefits && feature.benefits.length > 0" class="mb-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                            ✨ Key Benefits
                        </h4>
                        <ul class="space-y-2">
                            <li
                                v-for="benefit in feature.benefits"
                                :key="benefit"
                                class="flex items-start space-x-2"
                            >
                                <CheckCircleIcon class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" />
                                <span class="text-gray-700 dark:text-gray-300">{{ benefit }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- How to Use -->
                    <div v-if="feature.howToUse && feature.howToUse.length > 0" class="mb-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">
                            🚀 How to Use
                        </h4>
                        <ol class="space-y-2">
                            <li
                                v-for="(step, index) in feature.howToUse"
                                :key="step"
                                class="flex items-start space-x-3"
                            >
                                <span class="flex items-center justify-center w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full text-sm font-medium flex-shrink-0">
                                    {{ index + 1 }}
                                </span>
                                <span class="text-gray-700 dark:text-gray-300">{{ step }}</span>
                            </li>
                        </ol>
                    </div>
                </div>

                <!-- Feature Tags -->
                <div v-if="feature.tags && feature.tags.length > 0" class="mb-6">
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="tag in feature.tags"
                            :key="tag"
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                        >
                            {{ tag }}
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-2">
                        <input
                            id="dont-show-spotlight"
                            v-model="dontShowSpotlight"
                            type="checkbox"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="dont-show-spotlight" class="text-sm text-gray-600 dark:text-gray-400">
                            Don't show spotlights for new features
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
                            @click="tryFeature"
                            class="px-6 py-2 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 border border-transparent rounded-md hover:from-blue-700 hover:to-purple-700 transition-all"
                        >
                            {{ feature.actionText || 'Try It Now' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import {
    CheckCircleIcon,
    PlayIcon,
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
    feature: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close', 'try-feature'])

const dontShowSpotlight = ref(false)

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

const playPreviewVideo = () => {
    // Open video in modal or navigate to demo
    if (props.feature.preview.videoUrl) {
        window.open(props.feature.preview.videoUrl, '_blank')
    }
}

const tryFeature = () => {
    if (dontShowSpotlight.value) {
        // Save user preference
        localStorage.setItem('hideFeatureSpotlights', 'true')
    }
    
    emit('try-feature', props.feature)
}
</script>