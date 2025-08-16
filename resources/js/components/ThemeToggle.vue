<template>
    <div class="theme-toggle">
        <!-- Simple Toggle Button -->
        <button
            v-if="variant === 'simple'"
            @click="toggleTheme"
            class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors touch-target"
            :title="`Switch to ${isDarkMode ? 'light' : 'dark'} mode`"
        >
            <SunIcon v-if="isDarkMode" class="h-5 w-5 text-yellow-500" />
            <MoonIcon v-else class="h-5 w-5 text-gray-600 dark:text-gray-400" />
        </button>

        <!-- Dropdown Toggle -->
        <div v-else-if="variant === 'dropdown'" class="relative">
            <button
                @click="showDropdown = !showDropdown"
                class="flex items-center space-x-2 p-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors touch-target"
                :title="getThemeLabel(currentTheme)"
            >
                <component :is="getThemeIcon(resolvedTheme)" class="h-5 w-5" />
                <span v-if="showLabel" class="text-sm font-medium">{{ getThemeLabel(currentTheme) }}</span>
                <ChevronDownIcon class="h-4 w-4" />
            </button>

            <!-- Dropdown Menu -->
            <div
                v-if="showDropdown"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
                @click.stop
            >
                <div class="py-1">
                    <button
                        v-for="theme in themeOptions"
                        :key="theme.value"
                        @click="selectTheme(theme.value)"
                        class="w-full flex items-center space-x-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        :class="{ 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300': currentTheme === theme.value }"
                    >
                        <component :is="theme.icon" class="h-4 w-4" />
                        <span>{{ theme.label }}</span>
                        <CheckIcon v-if="currentTheme === theme.value" class="h-4 w-4 ml-auto" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Switch Toggle -->
        <div v-else-if="variant === 'switch'" class="flex items-center space-x-3">
            <SunIcon class="h-5 w-5 text-yellow-500" />
            <button
                @click="toggleTheme"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                :class="isDarkMode ? 'bg-blue-600' : 'bg-gray-200'"
                role="switch"
                :aria-checked="isDarkMode"
            >
                <span
                    class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                    :class="isDarkMode ? 'translate-x-6' : 'translate-x-1'"
                />
            </button>
            <MoonIcon class="h-5 w-5 text-gray-600 dark:text-gray-400" />
        </div>

        <!-- Segmented Control -->
        <div v-else-if="variant === 'segmented'" class="flex bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
            <button
                v-for="theme in themeOptions"
                :key="theme.value"
                @click="selectTheme(theme.value)"
                class="flex items-center space-x-2 px-3 py-2 rounded-md text-sm font-medium transition-colors touch-target"
                :class="currentTheme === theme.value 
                    ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' 
                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
            >
                <component :is="theme.icon" class="h-4 w-4" />
                <span v-if="showLabel">{{ theme.label }}</span>
            </button>
        </div>

        <!-- Backdrop for dropdown -->
        <div
            v-if="showDropdown"
            class="fixed inset-0 z-40"
            @click="showDropdown = false"
        ></div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useTheme } from '@/composables/useTheme'
import {
    SunIcon,
    MoonIcon,
    ComputerDesktopIcon,
    ChevronDownIcon,
    CheckIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    variant: {
        type: String,
        default: 'simple', // simple, dropdown, switch, segmented
        validator: (value) => ['simple', 'dropdown', 'switch', 'segmented'].includes(value)
    },
    showLabel: {
        type: Boolean,
        default: false
    },
    size: {
        type: String,
        default: 'md', // sm, md, lg
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    }
})

const { currentTheme, resolvedTheme, isDarkMode, setTheme, toggleTheme, themes } = useTheme()
const showDropdown = ref(false)

const themeOptions = [
    {
        value: themes.LIGHT,
        label: 'Light',
        icon: SunIcon
    },
    {
        value: themes.DARK,
        label: 'Dark',
        icon: MoonIcon
    },
    {
        value: themes.SYSTEM,
        label: 'System',
        icon: ComputerDesktopIcon
    }
]

const getThemeIcon = (theme) => {
    switch (theme) {
        case themes.DARK:
            return MoonIcon
        case themes.LIGHT:
            return SunIcon
        default:
            return ComputerDesktopIcon
    }
}

const getThemeLabel = (theme) => {
    const option = themeOptions.find(opt => opt.value === theme)
    return option ? option.label : 'System'
}

const selectTheme = (theme) => {
    setTheme(theme)
    showDropdown.value = false
}

// Close dropdown on escape key
const handleEscape = (e) => {
    if (e.key === 'Escape' && showDropdown.value) {
        showDropdown.value = false
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape)
})
</script>

<style scoped>
/* Theme toggle animations */
.theme-toggle button {
    transition: all 0.2s ease-in-out;
}

.theme-toggle button:active {
    transform: scale(0.95);
}

/* Switch animation */
.theme-toggle [role="switch"] span {
    transition: transform 0.2s ease-in-out;
}

/* Dropdown animation */
.theme-toggle .absolute {
    animation: fadeInScale 0.15s ease-out;
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-5px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}
</style>