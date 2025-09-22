<template>
    <DefaultLayout title="What's New">
        <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="mb-4 flex items-center space-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-purple-600">
                        <SparklesIcon class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">What's New</h1>
                        <p class="text-gray-600 dark:text-gray-400">Stay up to date with the latest features and improvements</p>
                    </div>
                </div>
            </div>

            <!-- Feature Highlights -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="feature in featuredUpdates"
                    :key="feature.id"
                    class="overflow-hidden rounded-lg bg-white shadow-lg transition-shadow hover:shadow-xl dark:bg-gray-800"
                >
                    <!-- Feature Image/Icon -->
                    <div
                        class="flex h-48 items-center justify-center bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900 dark:to-purple-900"
                    >
                        <component :is="getFeatureIcon(feature.icon)" class="h-16 w-16 text-blue-500" />
                    </div>

                    <!-- Feature Content -->
                    <div class="p-6">
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ feature.title }}
                            </h3>
                            <span
                                class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200"
                            >
                                ✨ New
                            </span>
                        </div>
                        <p class="mb-4 text-gray-600 dark:text-gray-400">
                            {{ feature.description }}
                        </p>
                        <div class="flex space-x-2">
                            <button
                                @click="tryFeature(feature)"
                                class="flex-1 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                            >
                                Try It Now
                            </button>
                            <button @click="learnMore(feature)" class="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-500">
                                Learn More
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Updates Timeline -->
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Recent Updates</h2>
                </div>

                <div class="p-6">
                    <div class="space-y-8">
                        <div v-for="update in updates" :key="update.id" class="relative">
                            <!-- Timeline Line -->
                            <div class="absolute bottom-0 left-4 top-8 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                            <!-- Update Content -->
                            <div class="flex items-start space-x-4">
                                <!-- Update Icon -->
                                <div
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full"
                                    :class="getUpdateTypeStyle(update.type)"
                                >
                                    <component :is="getUpdateIcon(update.type)" class="h-4 w-4" />
                                </div>

                                <!-- Update Details -->
                                <div class="min-w-0 flex-1">
                                    <div class="mb-2 flex items-center justify-between">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ update.title }}
                                        </h3>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatDate(update.created_at) }}
                                        </span>
                                    </div>

                                    <p class="mb-4 text-gray-700 dark:text-gray-300">
                                        {{ update.description }}
                                    </p>

                                    <!-- Update Features -->
                                    <div v-if="update.features && update.features.length > 0" class="mb-4">
                                        <h4 class="mb-2 text-sm font-semibold text-gray-900 dark:text-white">What's Included:</h4>
                                        <ul class="space-y-1">
                                            <li
                                                v-for="feature in update.features"
                                                :key="feature"
                                                class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400"
                                            >
                                                <CheckCircleIcon class="h-4 w-4 flex-shrink-0 text-green-500" />
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
                                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                                        >
                                            {{ action.label }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Newsletter Signup -->
            <div class="mt-8 rounded-lg bg-gradient-to-r from-blue-500 to-purple-600 p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="mb-2 text-lg font-semibold">Stay in the Loop</h3>
                        <p class="text-blue-100">Get notified about new features and updates</p>
                    </div>
                    <div class="flex space-x-2">
                        <button
                            @click="toggleNotifications"
                            class="rounded-md bg-white bg-opacity-20 px-4 py-2 font-medium text-white hover:bg-opacity-30"
                        >
                            {{ notificationsEnabled ? 'Notifications On' : 'Enable Notifications' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </DefaultLayout>
</template>

<script setup>
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import {
    AcademicCapIcon,
    ArrowPathIcon,
    BriefcaseIcon,
    BugAntIcon,
    CalendarIcon,
    ChartBarIcon,
    ChatBubbleLeftRightIcon,
    CheckCircleIcon,
    CurrencyDollarIcon,
    HeartIcon,
    MapIcon,
    PlusIcon,
    RocketLaunchIcon,
    ShieldCheckIcon,
    SparklesIcon,
    TrophyIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { format } from 'date-fns';
import { onMounted, ref } from 'vue';

const featuredUpdates = ref([]);
const updates = ref([]);
const notificationsEnabled = ref(false);

onMounted(async () => {
    await loadUpdates();
    checkNotificationStatus();
});

const loadUpdates = async () => {
    try {
        // Load featured updates
        featuredUpdates.value = [
            {
                id: 'alumni-map',
                title: 'Alumni Map Visualization',
                description: 'Discover alumni around the world with our interactive map',
                icon: 'map',
                route: '/alumni/directory?view=map',
            },
            {
                id: 'achievement-celebrations',
                title: 'Achievement Celebrations',
                description: 'Celebrate milestones and achievements with your network',
                icon: 'trophy',
                route: '/career/timeline',
            },
            {
                id: 'smart-job-matching',
                title: 'Smart Job Matching',
                description: 'AI-powered job recommendations based on your network',
                icon: 'briefcase',
                route: '/jobs/dashboard',
            },
        ];

        // Load all updates
        const response = await fetch('/api/onboarding/whats-new', {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const data = await response.json();
        if (data.success) {
            updates.value = data.updates;
        }
    } catch (error) {
        console.error('Failed to load updates:', error);
    }
};

const checkNotificationStatus = () => {
    notificationsEnabled.value = localStorage.getItem('autoShowUpdates') === 'true';
};

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

const getUpdateTypeStyle = (type) => {
    const styles = {
        feature: 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400',
        improvement: 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400',
        bugfix: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400',
        security: 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400',
        announcement: 'bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-400',
    };
    return styles[type] || styles.feature;
};

const getUpdateIcon = (type) => {
    const icons = {
        feature: PlusIcon,
        improvement: ArrowPathIcon,
        bugfix: BugAntIcon,
        security: ShieldCheckIcon,
        announcement: RocketLaunchIcon,
    };
    return icons[type] || PlusIcon;
};

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMMM d, yyyy');
};

const tryFeature = (feature) => {
    if (feature.route) {
        window.location.href = feature.route;
    }
};

const learnMore = (feature) => {
    // Show feature spotlight
    window.dispatchEvent(
        new CustomEvent('show-feature-spotlight', {
            detail: { feature },
        }),
    );
};

const performUpdateAction = (update, action) => {
    if (action.type === 'navigate') {
        window.location.href = action.url;
    }
};

const toggleNotifications = () => {
    notificationsEnabled.value = !notificationsEnabled.value;
    localStorage.setItem('autoShowUpdates', notificationsEnabled.value.toString());
};
</script>













