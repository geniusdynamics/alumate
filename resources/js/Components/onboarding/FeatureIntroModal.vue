<template>
    <BaseModal :show="show" max-width="lg" @close="$emit('close')">
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg" :class="getFeatureColorClass(feature.category)">
                        <component :is="getFeatureIcon(feature.icon)" class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ feature.title }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ feature.category }} Feature</p>
                    </div>
                </div>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>

            <!-- Feature Preview -->
            <div v-if="feature.preview" class="mb-6">
                <div class="relative overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-700">
                    <img v-if="feature.preview.type === 'image'" :src="feature.preview.url" :alt="feature.title" class="h-48 w-full object-cover" />
                    <video
                        v-else-if="feature.preview.type === 'video'"
                        :src="feature.preview.url"
                        class="h-48 w-full object-cover"
                        autoplay
                        muted
                        loop
                    ></video>
                    <div v-else class="flex h-48 w-full items-center justify-center">
                        <component :is="getFeatureIcon(feature.icon)" class="h-16 w-16 text-gray-400" />
                    </div>

                    <!-- Play button for interactive demos -->
                    <div
                        v-if="feature.hasDemo"
                        class="absolute inset-0 flex cursor-pointer items-center justify-center bg-black bg-opacity-30 transition-colors hover:bg-opacity-40"
                        @click="playDemo"
                    >
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white bg-opacity-90">
                            <PlayIcon class="ml-1 h-8 w-8 text-gray-800" />
                        </div>
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
                    <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">✨ Key Benefits:</h4>
                    <ul class="space-y-2">
                        <li
                            v-for="benefit in feature.benefits"
                            :key="benefit"
                            class="flex items-start space-x-2 text-sm text-gray-600 dark:text-gray-400"
                        >
                            <CheckCircleIcon class="mt-0.5 h-4 w-4 flex-shrink-0 text-green-500" />
                            <span>{{ benefit }}</span>
                        </li>
                    </ul>
                </div>

                <!-- How It Works -->
                <div v-if="feature.howItWorks && feature.howItWorks.length > 0" class="mb-4">
                    <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">🔧 How It Works:</h4>
                    <ol class="space-y-2">
                        <li
                            v-for="(step, index) in feature.howItWorks"
                            :key="step"
                            class="flex items-start space-x-3 text-sm text-gray-600 dark:text-gray-400"
                        >
                            <span
                                class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-blue-500 text-xs font-medium text-white"
                            >
                                {{ index + 1 }}
                            </span>
                            <span>{{ step }}</span>
                        </li>
                    </ol>
                </div>

                <!-- Pro Tips -->
                <div v-if="feature.tips && feature.tips.length > 0" class="mb-4">
                    <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">💡 Pro Tips:</h4>
                    <ul class="space-y-2">
                        <li v-for="tip in feature.tips" :key="tip" class="flex items-start space-x-2 text-sm text-gray-600 dark:text-gray-400">
                            <span class="mt-0.5 text-yellow-500">💡</span>
                            <span>{{ tip }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Feature Stats -->
            <div v-if="feature.stats" class="mb-6">
                <div class="grid grid-cols-3 gap-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                    <div v-for="stat in feature.stats" :key="stat.label" class="text-center">
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
                <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">🔗 You might also like:</h4>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="relatedFeature in feature.relatedFeatures"
                        :key="relatedFeature.id"
                        @click="showRelatedFeature(relatedFeature)"
                        class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1.5 text-xs font-medium text-blue-800 transition-colors hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:hover:bg-blue-800"
                    >
                        <component :is="getFeatureIcon(relatedFeature.icon)" class="mr-1 h-3 w-3" />
                        {{ relatedFeature.title }}
                    </button>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                <div class="flex items-center space-x-2">
                    <input
                        id="dont-show-feature-intros"
                        v-model="dontShowAgain"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                    <label for="dont-show-feature-intros" class="text-sm text-gray-600 dark:text-gray-400"> Don't show feature intros </label>
                </div>

                <div class="flex space-x-3">
                    <button
                        @click="$emit('close')"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    >
                        Maybe Later
                    </button>
                    <button
                        v-if="feature.demoUrl"
                        @click="watchDemo"
                        class="rounded-md border border-blue-300 bg-blue-100 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-200"
                    >
                        Watch Demo
                    </button>
                    <button
                        @click="tryFeature"
                        class="rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        {{ feature.actionText || 'Try It Now' }}
                    </button>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import BaseModal from '@/Components/ui/BaseModal.vue';
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
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    feature: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close', 'try-feature', 'show-related-feature']);

const dontShowAgain = ref(false);

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

const getFeatureColorClass = (category) => {
    const colors = {
        social: 'bg-gradient-to-br from-blue-500 to-blue-600',
        career: 'bg-gradient-to-br from-green-500 to-green-600',
        networking: 'bg-gradient-to-br from-purple-500 to-purple-600',
        events: 'bg-gradient-to-br from-orange-500 to-orange-600',
        analytics: 'bg-gradient-to-br from-indigo-500 to-indigo-600',
        fundraising: 'bg-gradient-to-br from-pink-500 to-pink-600',
    };
    return colors[category] || colors.social;
};

const tryFeature = () => {
    if (dontShowAgain.value) {
        localStorage.setItem('hideFeatureIntros', 'true');
    }

    emit('try-feature', props.feature);
};

const watchDemo = () => {
    if (props.feature.demoUrl) {
        window.open(props.feature.demoUrl, '_blank');
    }
};

const playDemo = () => {
    // Trigger interactive demo
    window.dispatchEvent(
        new CustomEvent('play-feature-demo', {
            detail: { feature: props.feature },
        }),
    );
};

const showRelatedFeature = (relatedFeature) => {
    emit('show-related-feature', relatedFeature);
};
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











