<template>
    <DefaultLayout title="Help & Support">
        <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="mb-4 flex items-center space-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-r from-green-500 to-blue-600">
                        <QuestionMarkCircleIcon class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Help & Support</h1>
                        <p class="text-gray-600 dark:text-gray-400">Get help with using your alumni platform</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-4">
                <button @click="startTour" class="rounded-lg bg-white p-6 text-left shadow transition-shadow hover:shadow-lg dark:bg-gray-800">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                        <RocketLaunchIcon class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Take a Tour</h3>
                    <p class="text-gray-600 dark:text-gray-400">Get a guided walkthrough of key features</p>
                </button>

                <a
                    :href="route('training.index')"
                    class="block rounded-lg bg-white p-6 text-left shadow transition-shadow hover:shadow-lg dark:bg-gray-800"
                >
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                        <AcademicCapIcon class="h-6 w-6 text-green-600 dark:text-green-400" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Training Center</h3>
                    <p class="text-gray-600 dark:text-gray-400">Access guides, tutorials, and documentation</p>
                </a>

                <a
                    :href="route('whats-new')"
                    class="block rounded-lg bg-white p-6 text-left shadow transition-shadow hover:shadow-lg dark:bg-gray-800"
                >
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900">
                        <SparklesIcon class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">What's New</h3>
                    <p class="text-gray-600 dark:text-gray-400">Discover the latest features and updates</p>
                </a>

                <button
                    @click="showKeyboardShortcuts"
                    class="rounded-lg bg-white p-6 text-left shadow transition-shadow hover:shadow-lg dark:bg-gray-800"
                >
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900">
                        <CommandLineIcon class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Keyboard Shortcuts</h3>
                    <p class="text-gray-600 dark:text-gray-400">Learn shortcuts to work more efficiently</p>
                </button>
            </div>

            <!-- FAQ Section -->
            <div class="mb-8 rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Frequently Asked Questions</h2>
                </div>

                <div class="p-6">
                    <div class="space-y-6">
                        <div v-for="faq in faqs" :key="faq.id" class="border-b border-gray-200 pb-6 last:border-b-0 last:pb-0 dark:border-gray-700">
                            <button @click="toggleFaq(faq.id)" class="flex w-full items-center justify-between text-left">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ faq.question }}</h3>
                                <ChevronDownIcon
                                    class="h-5 w-5 text-gray-500 transition-transform"
                                    :class="{ 'rotate-180': openFaqs.includes(faq.id) }"
                                />
                            </button>

                            <div v-if="openFaqs.includes(faq.id)" class="mt-4 text-gray-700 dark:text-gray-300" v-html="faq.answer"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Support -->
            <div class="rounded-lg bg-gradient-to-r from-blue-500 to-purple-600 p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="mb-2 text-lg font-semibold">Still Need Help?</h3>
                        <p class="text-blue-100">Our support team is here to assist you</p>
                    </div>
                    <div class="flex space-x-3">
                        <a
                            href="mailto:support@alumni.com"
                            class="rounded-md bg-white bg-opacity-20 px-4 py-2 font-medium text-white transition-colors hover:bg-opacity-30"
                        >
                            Email Support
                        </a>
                        <button
                            @click="reportIssue"
                            class="rounded-md bg-white bg-opacity-20 px-4 py-2 font-medium text-white transition-colors hover:bg-opacity-30"
                        >
                            Report Issue
                        </button>
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
import DefaultLayout from '@/Layouts/DefaultLayout.vue';
import {
    AcademicCapIcon,
    ChevronDownIcon,
    CommandLineIcon,
    QuestionMarkCircleIcon,
    RocketLaunchIcon,
    SparklesIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const showShortcuts = ref(false);
const openFaqs = ref([]);

const keyboardShortcuts = [
    { key: '?', description: 'Show keyboard shortcuts' },
    { key: 'Ctrl + K', description: 'Quick search' },
    { key: 'Ctrl + /', description: 'Toggle help menu' },
    { key: 'Ctrl + N', description: 'Create new post' },
    { key: 'Ctrl + Shift + T', description: 'Start tour' },
    { key: 'Esc', description: 'Close modals/menus' },
];

const faqs = [
    {
        id: 1,
        question: 'How do I connect with other alumni?',
        answer: 'You can connect with alumni through the <strong>Alumni Directory</strong>. Use the search and filter options to find alumni by graduation year, location, industry, or company. Send personalized connection requests to build your network.',
    },
    {
        id: 2,
        question: 'How do I update my career timeline?',
        answer: 'Go to <strong>Career Timeline</strong> in the main navigation. Click "Add Position" to add new roles, or edit existing entries. Keep your timeline updated to get better job recommendations and connect with relevant alumni.',
    },
    {
        id: 3,
        question: 'How does the job matching system work?',
        answer: 'Our AI-powered job matching system analyzes your profile, skills, career goals, and network connections to recommend relevant opportunities. Jobs from companies where you have alumni connections are prioritized.',
    },
    {
        id: 4,
        question: 'Can I request referrals through the platform?',
        answer: 'Yes! When viewing job opportunities, you can see if you have connections at the company and request introductions or referrals through your alumni network.',
    },
    {
        id: 5,
        question: 'How do I find mentors?',
        answer: 'Visit the <strong>Mentorship Hub</strong> to browse available mentors. You can filter by industry, experience level, and expertise. Send mentorship requests with your goals and what you hope to learn.',
    },
    {
        id: 6,
        question: 'How do I share updates with my network?',
        answer: 'Use the <strong>Social Timeline</strong> to share career updates, achievements, and insights. You can create posts with text, images, and tag relevant topics to increase visibility.',
    },
    {
        id: 7,
        question: 'How do I find alumni events?',
        answer: 'Check the <strong>Events</strong> section to discover upcoming alumni gatherings, networking events, webinars, and reunions. You can filter by location, type, and your interests.',
    },
    {
        id: 8,
        question: 'How do I control my privacy settings?',
        answer: 'Go to your profile settings to control who can see your information, contact you, and find you in searches. You can adjust visibility for different aspects of your profile.',
    },
];

const startTour = () => {
    window.dispatchEvent(new CustomEvent('restart-onboarding-tour'));
};

const showKeyboardShortcuts = () => {
    showShortcuts.value = true;
};

const closeShortcuts = () => {
    showShortcuts.value = false;
};

const toggleFaq = (faqId) => {
    const index = openFaqs.value.indexOf(faqId);
    if (index > -1) {
        openFaqs.value.splice(index, 1);
    } else {
        openFaqs.value.push(faqId);
    }
};

const reportIssue = () => {
    window.open('https://github.com/your-repo/issues/new', '_blank');
};
</script>

