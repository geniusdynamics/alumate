<template>
    <BaseModal
        :show="show"
        max-width="lg"
        @close="$emit('close')"
    >
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center"
                         :class="getFeatureColorClass(feature.category)">
                        <component :is="getFeatureIcon(feature.icon)" class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ feature.title }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ feature.category }} Feature
                        </p>
                    </div>
                </div>
                <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                    <XMarkIcon class="w-6 h-6" />
                </button>
            </div>

            <!-- Feature Preview -->
            <div v-if="feature.preview" class="mb-6">
                <div class="relative rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                    <img
                        v-if="feature.preview.type === 'image'"
                        :src="feature.preview.url"
                        :alt="feature.title"
                        class="w-full h-48 object-cover"
                    >
                    <video
                        v-else-if="feature.preview.type === 'video'"
                        :src="feature.preview.url"
                        class="w-full h-48 object-cover"
                        autoplay
                        muted
                        loop
                    ></video>
                    <div
                        v-else
                        class="w-full h-48 flex items-center justify-center"
                    >
                        <component :is="getFeatureIcon(feature.icon)" class="w-16 h-16 text-gray-400" />
                    </div>
                    
                    <!-- Play button for interactive demos -->
                    <div
                        v-if="feature.hasDemo"
                        class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30 hover:bg-opacity-40 transition-colors cursor-pointer"
                        @click="playDemo"
                    >
                        <div class="w-16 h-16 bg-white bg-opacity-90 rounded-full flex items-center justify-center">
                            <PlayIcon class="w-8 h-8 text-gray-800 ml-1" />
                        </div>
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
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                        ✨ Key Benefits:
                    </h4>
                    <ul class="space-y-2">
                        <li
                            v-for="benefit in feature.benefits"
                            :key="benefit"
                            class="flex items-start space-x-2 text-sm text-gray-600 dark:text-gray-400"
                        >
                            <CheckCircleIcon class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" />
                            <span>{{ benefit }}</span>
                        </li>
                    </ul>
                </div>

                <!-- How It Works -->
                <div v-if="feature.howItWorks && feature.howItWorks.length > 0" class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                        🔧 How It Works:
                    </h4>
                    <ol class="space-y-2">
                        <li
                            v-for="(step, index) in feature.howItWorks"
                            :key="step"
                            class="flex items-start space-x-3 text-sm text-gray-600 dark:text-gray-400"
                        >
                            <span class="flex items-center justify-center w-5 h-5 bg-blue-500 text-white rounded-full text-xs font-medium flex-shrink-0 mt-0.5">
                                {{ index + 1 }}
                            </span>
                            <span>{{ step }}</span>
                        </li>
                    </ol>
                </div>

                <!-- Pro Tips -->
                <div v-if="feature.tips && feature.tips.length > 0" class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                        💡 Pro Tips:
                    </h4>
                    <ul class="space-y-2">
                        <li
                            v-for="tip in feature.tips"
                            :key="tip"
                            class="flex items-start space-x-2 text-sm text-gray-600 dark:text-gray-400"
                        >
                            <span class="text-yellow-500 mt-0.5">💡</span>
                            <span>{{ tip }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Feature Stats -->
            <div v-if="feature.stats" class="mb-6">
                <div class="grid grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div
                        v-for="stat in feature.stats"
                        :key="stat.label"
                        class="text-center"
                    >
                        <div class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ stat.value }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ stat.label }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Features -->
            <div v-if="feature.relatedFeatures && feature.relatedFeatures.length > 0" class="mb-6">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                    🔗 You might also like:
                </h4>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="relatedFeature in feature.relatedFeatures"
                        :key="relatedFeature.id"
                        @click="showRelatedFeature(relatedFeature)"
                        class="inline-flex items-center px-3 py-1.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-xs font-medium hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors"
                    >
                        <component :is="getFeatureIcon(relatedFeature.icon)" class="w-3 h-3 mr-1" />
                        {{ relatedFeature.title }}
                    </button>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-2">
                    <input
                        id="dont-show-feature-intros"
                        v-model="dontShowAgain"
                        type="checkbox"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    >
                    <label for="dont-show-feature-intros" class="text-sm text-gray-600 dark:text-gray-400">
                        Don't show feature intros
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
                        v-if="feature.demoUrl"
                        @click="watchDemo"
                        class="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200"
                    >
                        Watch Demo
                    </button>
                    <button
                        @click="tryFeature"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700"
                    >
                        {{ feature.actionText || 'Try It Now' }}
                    </button>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import { ref } from 'vue'
import BaseModal from '@/Components/ui/BaseModal.vue'
import {
    XMarkIcon,
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
    show: {
        type: Boolean,
        default: false
    },
    feature: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close', 'try-feature', 'show-related-feature'])

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

const getFeatureColorClass = (category) => {
    const colors = {
        'social': 'bg-gradient-to-br from-blue-500 to-blue-600',
        'career': 'bg-gradient-to-br from-green-500 to-green-600',
        'networking': 'bg-gradient-to-br from-purple-500 to-purple-600',
        'events': 'bg-gradient-to-br from-orange-500 to-orange-600',
        'analytics': 'bg-gradient-to-br from-indigo-500 to-indigo-600',
        'fundraising': 'bg-gradient-to-br from-pink-500 to-pink-600'
    }
    return colors[category] || colors.social
}

const tryFeature = () => {
    if (dontShowAgain.value) {
        localStorage.setItem('hideFeatureIntros', 'true')
    }
    
    emit('try-feature', props.feature)
}

const watchDemo = () => {
    if (props.feature.demoUrl) {
        window.open(props.feature.demoUrl, '_blank')
    }
}

const playDemo = () => {
    // Trigger interactive demo
    window.dispatchEvent(new CustomEvent('play-feature-demo', {
        detail: { feature: props.feature }
    }))
}

const showRelatedFeature = (relatedFeature) => {
    emit('show-related-feature', relatedFeature)
}
</script>

<style scoped>
/* Custom animations for feature intro */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.feature-intro-content {
    animation: slideInUp 0.3s ease-out;
}
</style>