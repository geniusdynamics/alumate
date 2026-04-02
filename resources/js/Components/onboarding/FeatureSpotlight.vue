<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

            <!-- Modal panel -->
            <div
                class="inline-block transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6 sm:align-middle dark:bg-gray-800"
            >
                <!-- Feature Header -->
                <div class="mb-6 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-purple-600">
                        <component :is="getFeatureIcon(feature.icon)" class="h-8 w-8 text-white" />
                    </div>
                    <h3 class="mb-2 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ feature.title }}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ feature.subtitle }}
                    </p>
                </div>

                <!-- Feature Preview -->
                <div v-if="feature.preview" class="mb-6">
                    <div class="relative overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-700">
                        <img
                            v-if="feature.preview.type === 'image'"
                            :src="feature.preview.src"
                            :alt="feature.preview.alt"
                            class="h-48 w-full object-cover"
                        />
                        <div
                            v-else-if="feature.preview.type === 'video'"
                            class="flex h-48 w-full items-center justify-center bg-gray-200 dark:bg-gray-600"
                        >
                            <button
                                @click="playPreviewVideo"
                                class="flex items-center space-x-2 rounded-lg bg-white bg-opacity-90 px-4 py-2 text-gray-900 transition-all hover:bg-opacity-100"
                            >
                                <PlayIcon class="h-5 w-5" />
                                <span>Watch Preview</span>
                            </button>
                        </div>
                        <div
                            v-else
                            class="flex h-48 w-full items-center justify-center bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900 dark:to-purple-900"
                        >
                            <component :is="getFeatureIcon(feature.icon)" class="h-16 w-16 text-blue-500 opacity-50" />
                        </div>
                    </div>
                </div>

                <!-- Feature Description -->
                <div class="mb-6">
                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                        {{ feature.description }}
                    </p>

                    <!-- Key Benefits -->
                    <div v-if="feature.benefits && feature.benefits.length > 0" class="mb-4">
                        <h4 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">âœ¨ Key Benefits</h4>
                        <ul class="space-y-2">
                            <li v-for="benefit in feature.benefits" :key="benefit" class="flex items-start space-x-2">
                                <CheckCircleIcon class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-500" />
                                <span class="text-gray-700 dark:text-gray-300">{{ benefit }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- How to Use -->
                    <div v-if="feature.howToUse && feature.howToUse.length > 0" class="mb-4">
                        <h4 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">ðŸš€ How to Use</h4>
                        <ol class="space-y-2">
                            <li v-for="(step, index) in feature.howToUse" :key="step" class="flex items-start space-x-3">
                                <span
                                    class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-medium text-blue-600 dark:bg-blue-900 dark:text-blue-400"
                                >
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
                            class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                        >
                            {{ tag }}
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between border-t border-gray-200 pt-6 dark:border-gray-700">
                    <div class="flex items-center space-x-2">
                        <input
                            id="dont-show-spotlight"
                            v-model="dontShowSpotlight"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label for="dont-show-spotlight" class="text-sm text-gray-600 dark:text-gray-400">
                            Don't show spotlights for new features
                        </label>
                    </div>

                    <div class="flex space-x-3">
                        <button
                            @click="$emit('close')"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            Maybe Later
                        </button>
                        <button
                            @click="tryFeature"
                            class="rounded-md border border-transparent bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-2 text-sm font-medium text-white transition-all hover:from-blue-700 hover:to-purple-700"
                        >
                            {{ feature.actionText || 'Try It Now' }}
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
    CalendarIcon,
    ChartBarIcon,
    ChatBubbleLeftRightIcon,
    CheckCircleIcon,
    CurrencyDollarIcon,
    HeartIcon,
    MapIcon,
    PlayIcon,
    RocketLaunchIcon,
    SparklesIcon,
    TrophyIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    feature: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close', 'try-feature']);

const dontShowSpotlight = ref(false);

const getFeatureIcon = (iconName) => {
    const icons = {
        chat: ChatBubbleLeftRightIcon,
        users: UsersIcon,
        briefcase: BriefcaseIcon,
        calendar: CalendarIcon,
        chart: ChartBarIcon,
        map: MapIcon,
        academic: AcademicCapIcon,
        heart: HeartIcon,
        currency: CurrencyDollarIcon,
        trophy: TrophyIcon,
        sparkles: SparklesIcon,
        rocket: RocketLaunchIcon,
    };
    return icons[iconName] || SparklesIcon;
};

const playPreviewVideo = () => {
    // Open video in modal or navigate to demo
    if (props.feature.preview.videoUrl) {
        window.open(props.feature.preview.videoUrl, '_blank');
    }
};

const tryFeature = () => {
    if (dontShowSpotlight.value) {
        // Save user preference
        localStorage.setItem('hideFeatureSpotlights', 'true');
    }

    emit('try-feature', props.feature);
};
</script>

