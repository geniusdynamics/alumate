<template>
    <DefaultLayout title="Training & Documentation">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Training & Documentation</h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">Master the platform with our comprehensive guides and tutorials</p>
                    </div>

                    <!-- Search -->
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            @input="performSearch"
                            type="text"
                            placeholder="Search training content..."
                            class="w-80 rounded-lg border border-gray-300 py-2 pl-10 pr-4 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <MagnifyingGlassIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                    </div>
                </div>
            </div>

            <!-- Training Progress -->
            <div class="mb-8 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Your Training Progress</h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ trainingProgress.completed_steps }} of {{ trainingProgress.total_steps }} completed
                    </span>
                </div>

                <div class="mb-4">
                    <div class="mb-1 flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Progress</span>
                        <span>{{ trainingProgress.completion_percentage }}%</span>
                    </div>
                    <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-2 rounded-full bg-blue-600 transition-all duration-300"
                            :style="{ width: trainingProgress.completion_percentage + '%' }"
                        ></div>
                    </div>
                </div>

                <div v-if="trainingProgress.next_recommended_action" class="flex items-center space-x-2">
                    <LightBulbIcon class="h-5 w-5 text-yellow-500" />
                    <span class="text-sm text-gray-700 dark:text-gray-300"> Next: {{ trainingProgress.next_recommended_action }} </span>
                </div>
            </div>

            <!-- Search Results -->
            <div v-if="searchResults && searchQuery" class="mb-8">
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                    <h2 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">Search Results ({{ searchResults.total_results }})</h2>

                    <!-- Search Results Content -->
                    <div class="space-y-6">
                        <!-- Guides Results -->
                        <div v-if="searchResults.guides.length > 0">
                            <h3 class="mb-3 text-lg font-medium text-gray-900 dark:text-white">User Guides</h3>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div
                                    v-for="guide in searchResults.guides"
                                    :key="guide.id"
                                    class="cursor-pointer rounded-lg border border-gray-200 p-4 transition-shadow hover:shadow-md dark:border-gray-700"
                                    @click="viewGuide(guide.id)"
                                >
                                    <div class="flex items-start space-x-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                            <component :is="getIcon(guide.icon)" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900 dark:text-white">{{ guide.title }}</h4>
                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ guide.description }}</p>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ guide.estimated_time }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tutorials Results -->
                        <div v-if="searchResults.tutorials.length > 0">
                            <h3 class="mb-3 text-lg font-medium text-gray-900 dark:text-white">Video Tutorials</h3>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div
                                    v-for="tutorial in searchResults.tutorials"
                                    :key="tutorial.id"
                                    class="cursor-pointer overflow-hidden rounded-lg border border-gray-200 transition-shadow hover:shadow-md dark:border-gray-700"
                                    @click="viewTutorial(tutorial.id)"
                                >
                                    <div class="flex aspect-video items-center justify-center bg-gray-100 dark:bg-gray-700">
                                        <PlayIcon class="h-12 w-12 text-gray-400" />
                                    </div>
                                    <div class="p-4">
                                        <h4 class="font-medium text-gray-900 dark:text-white">{{ tutorial.title }}</h4>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ tutorial.description }}</p>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ tutorial.duration }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FAQs Results -->
                        <div v-if="searchResults.faqs.length > 0">
                            <h3 class="mb-3 text-lg font-medium text-gray-900 dark:text-white">FAQs</h3>
                            <div class="space-y-3">
                                <div
                                    v-for="faq in searchResults.faqs"
                                    :key="faq.id"
                                    class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
                                >
                                    <h4 class="mb-2 font-medium text-gray-900 dark:text-white">{{ faq.question }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400" v-html="faq.answer"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content (when not searching) -->
            <div v-else class="space-y-8">
                <!-- Quick Actions -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
                    <button
                        @click="startOnboarding"
                        class="rounded-lg bg-gradient-to-r from-blue-500 to-purple-600 p-6 text-left text-white shadow transition-shadow hover:shadow-lg"
                    >
                        <RocketLaunchIcon class="mb-3 h-8 w-8" />
                        <h3 class="mb-2 text-lg font-semibold">Take a Tour</h3>
                        <p class="text-sm text-blue-100">Get a guided walkthrough</p>
                    </button>

                    <Link
                        :href="route('training.faqs')"
                        class="block rounded-lg bg-white p-6 text-left shadow transition-shadow hover:shadow-lg dark:bg-gray-800"
                    >
                        <QuestionMarkCircleIcon class="mb-3 h-8 w-8 text-green-600 dark:text-green-400" />
                        <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">FAQs</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Find quick answers</p>
                    </Link>

                    <button
                        @click="showKeyboardShortcuts"
                        class="rounded-lg bg-white p-6 text-left shadow transition-shadow hover:shadow-lg dark:bg-gray-800"
                    >
                        <CommandLineIcon class="mb-3 h-8 w-8 text-purple-600 dark:text-purple-400" />
                        <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Shortcuts</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Work more efficiently</p>
                    </button>

                    <a
                        href="mailto:support@alumni.com"
                        class="block rounded-lg bg-white p-6 text-left shadow transition-shadow hover:shadow-lg dark:bg-gray-800"
                    >
                        <EnvelopeIcon class="mb-3 h-8 w-8 text-red-600 dark:text-red-400" />
                        <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Get Help</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Contact support</p>
                    </a>
                </div>

                <!-- User Guides -->
                <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">User Guides</h2>
                        <p class="mt-1 text-gray-600 dark:text-gray-400">Step-by-step guides tailored for your role</p>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div
                                v-for="guide in userGuides"
                                :key="guide.id"
                                class="cursor-pointer rounded-lg border border-gray-200 p-6 transition-shadow hover:shadow-md dark:border-gray-700"
                                @click="viewGuide(guide.id)"
                            >
                                <div class="flex items-start space-x-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                        <component :is="getIcon(guide.icon)" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ guide.title }}
                                        </h3>
                                        <p class="mb-3 text-gray-600 dark:text-gray-400">
                                            {{ guide.description }}
                                        </p>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ guide.estimated_time }}
                                            </span>
                                            <span
                                                class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                            >
                                                {{ guide.sections.length }} sections
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Video Tutorials -->
                <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Video Tutorials</h2>
                        <p class="mt-1 text-gray-600 dark:text-gray-400">Learn through interactive video content</p>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="tutorial in videoTutorials"
                                :key="tutorial.id"
                                class="cursor-pointer overflow-hidden rounded-lg border border-gray-200 transition-shadow hover:shadow-md dark:border-gray-700"
                                @click="viewTutorial(tutorial.id)"
                            >
                                <!-- Video Thumbnail -->
                                <div
                                    class="relative flex aspect-video items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800"
                                >
                                    <PlayIcon class="h-16 w-16 rounded-full bg-black bg-opacity-50 p-4 text-white" />
                                    <div class="absolute bottom-2 right-2 rounded bg-black bg-opacity-75 px-2 py-1 text-xs text-white">
                                        {{ tutorial.duration }}
                                    </div>
                                </div>

                                <!-- Tutorial Info -->
                                <div class="p-4">
                                    <h3 class="mb-2 font-semibold text-gray-900 dark:text-white">
                                        {{ tutorial.title }}
                                    </h3>
                                    <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ tutorial.description }}
                                    </p>
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="topic in tutorial.topics.slice(0, 3)"
                                            :key="topic"
                                            class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                                        >
                                            {{ topic }}
                                        </span>
                                        <span
                                            v-if="tutorial.topics.length > 3"
                                            class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                                        >
                                            +{{ tutorial.topics.length - 3 }} more
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Popular FAQs -->
                <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Popular FAQs</h2>
                                <p class="mt-1 text-gray-600 dark:text-gray-400">Quick answers to common questions</p>
                            </div>
                            <Link :href="route('training.faqs')" class="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                                View All FAQs →
                            </Link>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4">
                            <div
                                v-for="faq in faqs.slice(0, 5)"
                                :key="faq.id"
                                class="border-b border-gray-200 pb-4 last:border-b-0 last:pb-0 dark:border-gray-700"
                            >
                                <button @click="toggleFAQ(faq.id)" class="flex w-full items-center justify-between text-left">
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ faq.question }}</h3>
                                    <ChevronDownIcon
                                        class="h-5 w-5 text-gray-500 transition-transform"
                                        :class="{ 'rotate-180': openFAQs.includes(faq.id) }"
                                    />
                                </button>

                                <div v-if="openFAQs.includes(faq.id)" class="mt-3 text-sm text-gray-700 dark:text-gray-300" v-html="faq.answer"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Keyboard Shortcuts Modal -->
            <div v-if="showShortcuts" class="fixed inset-0 z-50 overflow-y-auto" @click="closeShortcuts">
                <div class="flex min-h-screen items-center justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

                    <div
                        class="inline-block transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6 sm:align-middle dark:bg-gray-800"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Keyboard Shortcuts</h3>
                            <button @click="closeShortcuts" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div v-for="shortcut in keyboardShortcuts" :key="shortcut.key" class="flex items-center justify-between">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ shortcut.description }}</span>
                                <kbd
                                    class="rounded-lg border border-gray-200 bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-800 dark:border-gray-500 dark:bg-gray-600 dark:text-gray-100"
                                >
                                    {{ shortcut.key }}
                                </kbd>
                            </div>
                        </div>
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
    BriefcaseIcon,
    BuildingOfficeIcon,
    CalendarIcon,
    ChartBarIcon,
    ChatBubbleLeftRightIcon,
    ChevronDownIcon,
    CommandLineIcon,
    ComputerDesktopIcon,
    CurrencyDollarIcon,
    EnvelopeIcon,
    HeartIcon,
    InformationCircleIcon,
    LightBulbIcon,
    MagnifyingGlassIcon,
    MapIcon,
    PlayIcon,
    QuestionMarkCircleIcon,
    RocketLaunchIcon,
    ShieldCheckIcon,
    SparklesIcon,
    TrophyIcon,
    UsersIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Link, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps({
    userGuides: Array,
    videoTutorials: Array,
    trainingProgress: Object,
    faqs: Array,
    role: String,
});

const searchQuery = ref('');
const searchResults = ref(null);
const openFAQs = ref([]);
const showShortcuts = ref(false);
const searchTimeout = ref(null);

const keyboardShortcuts = [
    { key: '?', description: 'Show keyboard shortcuts' },
    { key: 'Ctrl + K', description: 'Quick search' },
    { key: 'Ctrl + /', description: 'Toggle help menu' },
    { key: 'Ctrl + N', description: 'Create new post' },
    { key: 'Ctrl + Shift + T', description: 'Start tour' },
    { key: 'Esc', description: 'Close modals/menus' },
];

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

const handleKeydown = (event) => {
    if (event.key === '?' && !event.ctrlKey && !event.metaKey) {
        event.preventDefault();
        showShortcuts.value = true;
    }

    if (event.key === 'Escape') {
        showShortcuts.value = false;
    }
};

const getIcon = (iconName) => {
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
        shield: ShieldCheckIcon,
        building: BuildingOfficeIcon,
        info: InformationCircleIcon,
        monitor: ComputerDesktopIcon,
    };
    return icons[iconName] || SparklesIcon;
};

const performSearch = () => {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value);
    }

    if (!searchQuery.value.trim()) {
        searchResults.value = null;
        return;
    }

    searchTimeout.value = setTimeout(async () => {
        try {
            const response = await fetch(`/api/training/search?query=${encodeURIComponent(searchQuery.value)}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
            });

            const data = await response.json();
            if (data.success) {
                searchResults.value = data.data;
            }
        } catch (error) {
            console.error('Search failed:', error);
        }
    }, 300);
};

const startOnboarding = () => {
    window.dispatchEvent(new CustomEvent('restart-onboarding-tour'));
};

const showKeyboardShortcuts = () => {
    showShortcuts.value = true;
};

const closeShortcuts = () => {
    showShortcuts.value = false;
};

const viewGuide = (guideId) => {
    router.visit(route('training.guide', guideId));
};

const viewTutorial = (tutorialId) => {
    router.visit(route('training.tutorial', tutorialId));
};

const toggleFAQ = (faqId) => {
    const index = openFAQs.value.indexOf(faqId);
    if (index > -1) {
        openFAQs.value.splice(index, 1);
    } else {
        openFAQs.value.push(faqId);
    }
};
</script>
