<template>
    <div class="relative">
        <!-- Help Button -->
        <button
            @click="toggleHelpMenu"
            class="rounded-full p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
            title="Help & Support"
        >
            <QuestionMarkCircleIcon class="h-5 w-5" />
        </button>

        <!-- Help Menu -->
        <div
            v-if="showHelpMenu"
            class="absolute right-0 top-full z-50 mt-2 w-64 rounded-lg border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800"
        >
            <!-- Header -->
            <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Help & Support</h3>
            </div>

            <!-- Menu Items -->
            <div class="py-2">
                <button
                    @click="startTour"
                    class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <RocketLaunchIcon class="mr-3 h-4 w-4" />
                    Take a Tour
                </button>

                <button
                    @click="showFeatureDiscovery"
                    class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <SparklesIcon class="mr-3 h-4 w-4" />
                    Discover Features
                </button>

                <a
                    :href="route('training.index')"
                    class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <AcademicCapIcon class="mr-3 h-4 w-4" />
                    Training Center
                </a>

                <a
                    :href="route('whats-new')"
                    class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <NewspaperIcon class="mr-3 h-4 w-4" />
                    What's New
                </a>

                <button
                    @click="showKeyboardShortcuts"
                    class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <CommandLineIcon class="mr-3 h-4 w-4" />
                    Keyboard Shortcuts
                </button>

                <div class="my-2 border-t border-gray-200 dark:border-gray-700"></div>

                <a
                    href="mailto:support@alumni.com"
                    class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <EnvelopeIcon class="mr-3 h-4 w-4" />
                    Contact Support
                </a>

                <button
                    @click="reportIssue"
                    class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <BugAntIcon class="mr-3 h-4 w-4" />
                    Report an Issue
                </button>
            </div>

            <!-- Footer -->
            <div class="rounded-b-lg border-t border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Need help?</span>
                    <span>Press ? for shortcuts</span>
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
</template>

<script setup lang="ts">
import {
    AcademicCapIcon,
    BugAntIcon,
    CommandLineIcon,
    EnvelopeIcon,
    NewspaperIcon,
    QuestionMarkCircleIcon,
    RocketLaunchIcon,
    SparklesIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { onMounted, onUnmounted, ref } from 'vue';

const showHelpMenu = ref(false);
const showShortcuts = ref(false);

const keyboardShortcuts = [
    { key: '?', description: 'Show keyboard shortcuts' },
    { key: 'Ctrl + K', description: 'Quick search' },
    { key: 'Ctrl + /', description: 'Toggle help menu' },
    { key: 'Ctrl + N', description: 'Create new post' },
    { key: 'Ctrl + Shift + T', description: 'Start tour' },
    { key: 'Esc', description: 'Close modals/menus' },
];

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
});

const toggleHelpMenu = () => {
    showHelpMenu.value = !showHelpMenu.value;
};

const handleClickOutside = (event) => {
    if (!event.target.closest('.relative')) {
        showHelpMenu.value = false;
    }
};

const handleKeydown = (event) => {
    // Show shortcuts on '?' key
    if (event.key === '?' && !event.ctrlKey && !event.metaKey) {
        event.preventDefault();
        showShortcuts.value = true;
    }

    // Toggle help menu on Ctrl+/
    if (event.key === '/' && (event.ctrlKey || event.metaKey)) {
        event.preventDefault();
        toggleHelpMenu();
    }

    // Start tour on Ctrl+Shift+T
    if (event.key === 'T' && (event.ctrlKey || event.metaKey) && event.shiftKey) {
        event.preventDefault();
        startTour();
    }

    // Close on Escape
    if (event.key === 'Escape') {
        showHelpMenu.value = false;
        showShortcuts.value = false;
    }
};

const startTour = () => {
    showHelpMenu.value = false;
    window.dispatchEvent(new CustomEvent('restart-onboarding-tour'));
};

const showFeatureDiscovery = () => {
    showHelpMenu.value = false;
    // Trigger feature discovery modal
    window.dispatchEvent(new CustomEvent('show-feature-discovery'));
};

const showKeyboardShortcuts = () => {
    showHelpMenu.value = false;
    showShortcuts.value = true;
};

const closeShortcuts = () => {
    showShortcuts.value = false;
};

const reportIssue = () => {
    showHelpMenu.value = false;
    // Open issue reporting form or external link
    window.open('https://github.com/your-repo/issues/new', '_blank');
};
</script>

