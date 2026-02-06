<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

            <!-- Modal panel -->
            <div
                class="inline-block transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl sm:p-6 sm:align-middle dark:bg-gray-800"
            >
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">🎉 Discover New Features</h3>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">Check out the latest additions to your alumni platform</p>
                    </div>
                    <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>

                <!-- Features Grid -->
                <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="feature in features"
                        :key="feature.id"
                        class="cursor-pointer rounded-lg bg-gray-50 p-6 transition-colors hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600"
                        @click="exploreFeature(feature)"
                    >
                        <!-- Feature Icon -->
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                            <component :is="getFeatureIcon(feature.icon)" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>

                        <!-- Feature Content -->
                        <div>
                            <h4 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                                {{ feature.title }}
                            </h4>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ feature.description }}
                            </p>

                            <!-- Feature Tags -->
                            <div class="mb-4 flex flex-wrap gap-2">
                                <span
                                    v-for="tag in feature.tags"
                                    :key="tag"
                                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                >
                                    {{ tag }}
                                </span>
                            </div>

                            <!-- Feature Status -->
                            <div class="flex items-center justify-between">
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                        feature.status === 'new'
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                            : feature.status === 'updated'
                                              ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                                              : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                    ]"
                                >
                                    {{ feature.status === 'new' ? '✨ New' : feature.status === 'updated' ? '🔄 Updated' : '📈 Enhanced' }}
                                </span>

                                <button class="text-sm font-medium text-blue-600 hover:text-blue-500">Explore →</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between border-t border-gray-200 pt-6 dark:border-gray-700">
                    <div class="flex items-center space-x-2">
                        <input
                            id="dont-show-again"
                            v-model="dontShowAgain"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label for="dont-show-again" class="text-sm text-gray-600 dark:text-gray-400"> Don't show this again </label>
                    </div>

                    <div class="flex space-x-3">
                        <button
                            @click="$emit('close')"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            Maybe Later
                        </button>
                        <button
                            @click="startFeatureTour"
                            class="rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
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
import {
    AcademicCapIcon,
    BriefcaseIcon,
    CalendarIcon,
    ChartBarIcon,
    ChatBubbleLeftRightIcon,
    CurrencyDollarIcon,
    HeartIcon,
    MapIcon,
    RocketLaunchIcon,
    SparklesIcon,
    TrophyIcon,
    UsersIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    features: {
        type: Array,
        required: true,
    },
});

const emit = defineEmits(['close', 'feature-explored']);

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

const exploreFeature = (feature) => {
    emit('feature-explored', feature);
};

const startFeatureTour = () => {
    // Start a guided tour of new features
    window.dispatchEvent(
        new CustomEvent('start-feature-tour', {
            detail: { features: props.features },
        }),
    );
    emit('close');
};
</script>
