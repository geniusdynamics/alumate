<template>
    <div class="relative">
        <!-- Help Button -->
        <button
            @click="toggleHelpMenu"
            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            title="Help & Support"
        >
            <QuestionMarkCircleIcon class="w-5 h-5" />
        </button>

        <!-- Help Menu -->
        <div
            v-if="showHelpMenu"
            class="absolute right-0 top-full mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50"
        >
            <!-- Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Help & Support</h3>
            </div>

            <!-- Menu Items -->
            <div class="py-2">
                <button
                    @click="startTour"
                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                    <RocketLaunchIcon class="w-4 h-4 mr-3" />
                    Take a Tour
                </button>
                
                <button
                    @click="showFeatureDiscovery"
                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                    <SparklesIcon class="w-4 h-4 mr-3" />
                    Discover Features
                </button>
                
                <a
                    :href="route('training.index')"
                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                    <AcademicCapIcon class="w-4 h-4 mr-3" />
                    Training Center
                </a>
                
                <a
                    :href="route('whats-new')"
                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                    <NewspaperIcon class="w-4 h-4 mr-3" />
                    What's New
                </a>
                
                <button
                    @click="showKeyboardShortcuts"
                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                    <CommandLineIcon class="w-4 h-4 mr-3" />
                    Keyboard Shortcuts
                </button>
                
                <div class="border-t border-gray-200 dark:border-gray-700 my-2"></div>
                
                <a
                    href="mailto:support@alumni.com"
                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                    <EnvelopeIcon class="w-4 h-4 mr-3" />
                    Contact Support
                </a>
                
                <button
                    @click="reportIssue"
                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                    <BugAntIcon class="w-4 h-4 mr-3" />
                    Report an Issue
                </button>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 rounded-b-lg">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Need help?</span>
                    <span>Press ? for shortcuts</span>
                </div>
            </div>
        </div>

        <!-- Keyboard Shortcuts Modal -->
        <div
            v-if="showShortcuts"
            class="fixed inset-0 z-50 overflow-y-auto"
            @click="closeShortcuts"
        >
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Keyboard Shortcuts</h3>
                        <button
                            @click="closeShortcuts"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div
                            v-for="shortcut in keyboardShortcuts"
                            :key="shortcut.key"
                            class="flex items-center justify-between"
                        >
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ shortcut.description }}</span>
                            <kbd class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500">
                                {{ shortcut.key }}
                            </kbd>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import {
    QuestionMarkCircleIcon,
    RocketLaunchIcon,
    SparklesIcon,
    NewspaperIcon,
    CommandLineIcon,
    EnvelopeIcon,
    BugAntIcon,
    XMarkIcon,
    AcademicCapIcon
} from '@heroicons/vue/24/outline'

const showHelpMenu = ref(false)
const showShortcuts = ref(false)

const keyboardShortcuts = [
    { key: '?', description: 'Show keyboard shortcuts' },
    { key: 'Ctrl + K', description: 'Quick search' },
    { key: 'Ctrl + /', description: 'Toggle help menu' },
    { key: 'Ctrl + N', description: 'Create new post' },
    { key: 'Ctrl + Shift + T', description: 'Start tour' },
    { key: 'Esc', description: 'Close modals/menus' }
]

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    document.removeEventListener('keydown', handleKeydown)
})

const toggleHelpMenu = () => {
    showHelpMenu.value = !showHelpMenu.value
}

const handleClickOutside = (event) => {
    if (!event.target.closest('.relative')) {
        showHelpMenu.value = false
    }
}

const handleKeydown = (event) => {
    // Show shortcuts on '?' key
    if (event.key === '?' && !event.ctrlKey && !event.metaKey) {
        event.preventDefault()
        showShortcuts.value = true
    }
    
    // Toggle help menu on Ctrl+/
    if (event.key === '/' && (event.ctrlKey || event.metaKey)) {
        event.preventDefault()
        toggleHelpMenu()
    }
    
    // Start tour on Ctrl+Shift+T
    if (event.key === 'T' && (event.ctrlKey || event.metaKey) && event.shiftKey) {
        event.preventDefault()
        startTour()
    }
    
    // Close on Escape
    if (event.key === 'Escape') {
        showHelpMenu.value = false
        showShortcuts.value = false
    }
}

const startTour = () => {
    showHelpMenu.value = false
    window.dispatchEvent(new CustomEvent('restart-onboarding-tour'))
}

const showFeatureDiscovery = () => {
    showHelpMenu.value = false
    // Trigger feature discovery modal
    window.dispatchEvent(new CustomEvent('show-feature-discovery'))
}

const showKeyboardShortcuts = () => {
    showHelpMenu.value = false
    showShortcuts.value = true
}

const closeShortcuts = () => {
    showShortcuts.value = false
}

const reportIssue = () => {
    showHelpMenu.value = false
    // Open issue reporting form or external link
    window.open('https://github.com/your-repo/issues/new', '_blank')
}
</script>