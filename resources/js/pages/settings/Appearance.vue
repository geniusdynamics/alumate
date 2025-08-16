<template>
    <AppLayout title="Appearance Settings">
        <Head title="Appearance Settings" />
        
        <!-- Mobile Hamburger Menu -->
        <MobileHamburgerMenu class="lg:hidden" />
        
        <div class="appearance-settings theme-bg-secondary min-h-screen">
            <!-- Mobile Header -->
            <div class="lg:hidden bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 safe-area-top">
                <div class="flex items-center justify-between p-4">
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Appearance</h1>
                    <Link
                        :href="route('settings.profile')"
                        class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300"
                    >
                        Done
                    </Link>
                </div>
            </div>
            
            <!-- Content -->
            <div class="mobile-container lg:py-12">
                <div class="max-w-2xl mx-auto">
                    <!-- Desktop Header -->
                    <div class="hidden lg:block mb-8">
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Appearance Settings</h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Customize how the platform looks and feels for you.
                        </p>
                    </div>
                    
                    <!-- Theme Selection -->
                    <div class="card-mobile">
                        <div class="card-mobile-header">
                            <h2 class="card-mobile-title">Theme</h2>
                            <SunIcon class="h-6 w-6 text-gray-400" />
                        </div>
                        
                        <div class="space-y-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Choose how the platform appears to you. Select a single theme, or sync with your system and automatically switch between day and night themes.
                            </p>
                            
                            <!-- Theme Options -->
                            <div class="space-y-3">
                                <div
                                    v-for="option in themeOptions"
                                    :key="option.value"
                                    class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                                    :class="{ 'ring-2 ring-blue-500 border-blue-500': currentTheme === option.value }"
                                    @click="setTheme(option.value)"
                                >
                                    <div class="flex items-center space-x-3">
                                        <component :is="option.icon" class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ option.label }}
                                            </h3>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ option.description }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div
                                            class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 flex items-center justify-center"
                                            :class="currentTheme === option.value ? 'border-blue-500 bg-blue-500' : ''"
                                        >
                                            <div
                                                v-if="currentTheme === option.value"
                                                class="w-2 h-2 rounded-full bg-white"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Theme Toggle Component Demo -->
                            <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Theme Toggle Styles</h3>
                                <div class="flex flex-wrap gap-4">
                                    <div class="flex flex-col items-center space-y-2">
                                        <ThemeToggle variant="simple" />
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Simple</span>
                                    </div>
                                    <div class="flex flex-col items-center space-y-2">
                                        <ThemeToggle variant="switch" />
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Switch</span>
                                    </div>
                                    <div class="flex flex-col items-center space-y-2">
                                        <ThemeToggle variant="dropdown" :show-label="true" />
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Dropdown</span>
                                    </div>
                                    <div class="flex flex-col items-center space-y-2">
                                        <ThemeToggle variant="segmented" :show-label="false" />
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Segmented</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Display Preferences -->
                    <div class="card-mobile">
                        <div class="card-mobile-header">
                            <h2 class="card-mobile-title">Display</h2>
                            <ComputerDesktopIcon class="h-6 w-6 text-gray-400" />
                        </div>
                        
                        <div class="space-y-4">
                            <!-- Reduced Motion -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Reduce Motion</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Minimize animations and transitions
                                    </p>
                                </div>
                                <button
                                    @click="toggleReducedMotion"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    :class="reducedMotion ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
                                    role="switch"
                                    :aria-checked="reducedMotion"
                                >
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                        :class="reducedMotion ? 'translate-x-6' : 'translate-x-1'"
                                    />
                                </button>
                            </div>
                            
                            <!-- High Contrast -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">High Contrast</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Increase contrast for better visibility
                                    </p>
                                </div>
                                <button
                                    @click="toggleHighContrast"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    :class="highContrast ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
                                    role="switch"
                                    :aria-checked="highContrast"
                                >
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                        :class="highContrast ? 'translate-x-6' : 'translate-x-1'"
                                    />
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Preferences -->
                    <div class="card-mobile lg:hidden">
                        <div class="card-mobile-header">
                            <h2 class="card-mobile-title">Mobile</h2>
                            <DevicePhoneMobileIcon class="h-6 w-6 text-gray-400" />
                        </div>
                        
                        <div class="space-y-4">
                            <!-- Pull to Refresh -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Pull to Refresh</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Enable pull-to-refresh on mobile
                                    </p>
                                </div>
                                <button
                                    @click="togglePullToRefresh"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    :class="pullToRefresh ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
                                    role="switch"
                                    :aria-checked="pullToRefresh"
                                >
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                        :class="pullToRefresh ? 'translate-x-6' : 'translate-x-1'"
                                    />
                                </button>
                            </div>
                            
                            <!-- Swipe Gestures -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Swipe Gestures</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Enable swipe navigation
                                    </p>
                                </div>
                                <button
                                    @click="toggleSwipeGestures"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    :class="swipeGestures ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700'"
                                    role="switch"
                                    :aria-checked="swipeGestures"
                                >
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                        :class="swipeGestures ? 'translate-x-6' : 'translate-x-1'"
                                    />
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview Section -->
                    <div class="card-mobile">
                        <div class="card-mobile-header">
                            <h2 class="card-mobile-title">Preview</h2>
                            <EyeIcon class="h-6 w-6 text-gray-400" />
                        </div>
                        
                        <div class="space-y-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                See how your theme choices look across different components.
                            </p>
                            
                            <!-- Sample Components -->
                            <div class="space-y-3">
                                <!-- Sample Button -->
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-900 dark:text-white">Buttons</span>
                                    <div class="flex space-x-2">
                                        <button class="btn-mobile-primary text-sm">Primary</button>
                                        <button class="btn-mobile-secondary text-sm">Secondary</button>
                                    </div>
                                </div>
                                
                                <!-- Sample Input -->
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-900 dark:text-white">Input</span>
                                    <input
                                        type="text"
                                        placeholder="Sample input"
                                        class="input-mobile w-32 text-sm"
                                        readonly
                                    />
                                </div>
                                
                                <!-- Sample Card -->
                                <div class="p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">Sample Card</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">This is how cards will appear with your current theme.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import MobileHamburgerMenu from '@/components/MobileHamburgerMenu.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
import { useTheme } from '@/composables/useTheme'
import {
    SunIcon,
    MoonIcon,
    ComputerDesktopIcon,
    DevicePhoneMobileIcon,
    EyeIcon
} from '@heroicons/vue/24/outline'

const { currentTheme, setTheme, themes } = useTheme()

const themeOptions = [
    {
        value: themes.LIGHT,
        label: 'Light',
        description: 'Clean and bright interface',
        icon: SunIcon
    },
    {
        value: themes.DARK,
        label: 'Dark',
        description: 'Easy on the eyes in low light',
        icon: MoonIcon
    },
    {
        value: themes.SYSTEM,
        label: 'System',
        description: 'Matches your device settings',
        icon: ComputerDesktopIcon
    }
]

// Accessibility preferences
const reducedMotion = ref(false)
const highContrast = ref(false)

// Mobile preferences
const pullToRefresh = ref(true)
const swipeGestures = ref(true)

const toggleReducedMotion = () => {
    reducedMotion.value = !reducedMotion.value
    
    // Apply reduced motion preference
    if (reducedMotion.value) {
        document.documentElement.style.setProperty('--transition-fast', 'none')
        document.documentElement.style.setProperty('--transition-normal', 'none')
        document.documentElement.style.setProperty('--transition-slow', 'none')
    } else {
        document.documentElement.style.removeProperty('--transition-fast')
        document.documentElement.style.removeProperty('--transition-normal')
        document.documentElement.style.removeProperty('--transition-slow')
    }
    
    // Save preference
    localStorage.setItem('reduced-motion', reducedMotion.value.toString())
}

const toggleHighContrast = () => {
    highContrast.value = !highContrast.value
    
    // Apply high contrast preference
    if (highContrast.value) {
        document.documentElement.classList.add('high-contrast')
    } else {
        document.documentElement.classList.remove('high-contrast')
    }
    
    // Save preference
    localStorage.setItem('high-contrast', highContrast.value.toString())
}

const togglePullToRefresh = () => {
    pullToRefresh.value = !pullToRefresh.value
    localStorage.setItem('pull-to-refresh', pullToRefresh.value.toString())
}

const toggleSwipeGestures = () => {
    swipeGestures.value = !swipeGestures.value
    localStorage.setItem('swipe-gestures', swipeGestures.value.toString())
}

// Load saved preferences
onMounted(() => {
    // Load accessibility preferences
    const savedReducedMotion = localStorage.getItem('reduced-motion')
    if (savedReducedMotion !== null) {
        reducedMotion.value = savedReducedMotion === 'true'
        if (reducedMotion.value) {
            toggleReducedMotion()
        }
    }
    
    const savedHighContrast = localStorage.getItem('high-contrast')
    if (savedHighContrast !== null) {
        highContrast.value = savedHighContrast === 'true'
        if (highContrast.value) {
            toggleHighContrast()
        }
    }
    
    // Load mobile preferences
    const savedPullToRefresh = localStorage.getItem('pull-to-refresh')
    if (savedPullToRefresh !== null) {
        pullToRefresh.value = savedPullToRefresh === 'true'
    }
    
    const savedSwipeGestures = localStorage.getItem('swipe-gestures')
    if (savedSwipeGestures !== null) {
        swipeGestures.value = savedSwipeGestures === 'true'
    }
})
</script>

<style scoped>
/* High contrast mode styles */
:global(.high-contrast) {
    --border-primary: 0 0 0;
    --border-secondary: 0 0 0;
}

:global(.dark.high-contrast) {
    --border-primary: 255 255 255;
    --border-secondary: 255 255 255;
}
</style>