<template>
    <div class="mobile-hamburger-menu lg:hidden">
        <!-- Hamburger Button -->
        <button
            @click="toggleMenu"
            class="fixed top-4 left-4 z-[60] p-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 touch-target"
            :class="{ 'bg-gray-100 dark:bg-gray-700': isOpen }"
            aria-label="Toggle navigation menu"
        >
            <div class="w-6 h-6 flex flex-col justify-center items-center">
                <span
                    class="block w-5 h-0.5 bg-gray-600 dark:bg-gray-300 transition-all duration-300"
                    :class="isOpen ? 'rotate-45 translate-y-1.5' : ''"
                ></span>
                <span
                    class="block w-5 h-0.5 bg-gray-600 dark:bg-gray-300 mt-1 transition-all duration-300"
                    :class="isOpen ? 'opacity-0' : ''"
                ></span>
                <span
                    class="block w-5 h-0.5 bg-gray-600 dark:bg-gray-300 mt-1 transition-all duration-300"
                    :class="isOpen ? '-rotate-45 -translate-y-1.5' : ''"
                ></span>
            </div>
        </button>

        <!-- Mobile Menu Overlay -->
        <div
            v-if="isOpen"
            class="fixed inset-0 z-[50] bg-black bg-opacity-50 backdrop-blur-sm"
            @click="closeMenu"
        ></div>

        <!-- Mobile Menu Panel -->
        <div
            class="fixed top-0 left-0 h-full w-80 max-w-[85vw] bg-white dark:bg-gray-800 shadow-xl z-[55] transform transition-transform duration-300 ease-in-out"
            :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Menu Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <img
                        :src="$page.props.app?.logo || '/images/logo.png'"
                        :alt="$page.props.app?.name || 'Alumni Platform'"
                        class="h-8 w-8 rounded"
                    />
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $page.props.app?.name || 'Alumni Platform' }}
                    </h2>
                </div>
                <button
                    @click="closeMenu"
                    class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                >
                    <XMarkIcon class="h-6 w-6" />
                </button>
            </div>

            <!-- User Profile Section -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 bg-blue-600 rounded-full flex items-center justify-center">
                        <span class="text-white font-medium text-sm">
                            {{ getUserInitials($page.props.auth?.user?.name) }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ $page.props.auth?.user?.name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ $page.props.auth?.user?.email }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu -->
            <nav class="flex-1 overflow-y-auto py-4">
                <div class="space-y-1 px-2">
                    <!-- Main Navigation Items -->
                    <div class="mb-6">
                        <h3 class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Main
                        </h3>
                        <Link
                            v-for="item in mainNavItems"
                            :key="item.name"
                            :href="item.href"
                            @click="closeMenu"
                            class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors touch-target"
                            :class="item.active 
                                ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' 
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        >
                            <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0" />
                            {{ item.name }}
                            <span v-if="item.badge" class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-0.5">
                                {{ item.badge }}
                            </span>
                        </Link>
                    </div>

                    <!-- Social Features -->
                    <div class="mb-6">
                        <h3 class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Social
                        </h3>
                        <Link
                            v-for="item in socialNavItems"
                            :key="item.name"
                            :href="item.href"
                            @click="closeMenu"
                            class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors touch-target"
                            :class="item.active 
                                ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' 
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        >
                            <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0" />
                            {{ item.name }}
                        </Link>
                    </div>

                    <!-- Career Features -->
                    <div class="mb-6">
                        <h3 class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Career
                        </h3>
                        <Link
                            v-for="item in careerNavItems"
                            :key="item.name"
                            :href="item.href"
                            @click="closeMenu"
                            class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors touch-target"
                            :class="item.active 
                                ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' 
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        >
                            <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0" />
                            {{ item.name }}
                        </Link>
                    </div>

                    <!-- Settings & Account -->
                    <div class="mb-6">
                        <h3 class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                            Account
                        </h3>
                        <Link
                            v-for="item in accountNavItems"
                            :key="item.name"
                            :href="item.href"
                            @click="closeMenu"
                            class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors touch-target"
                            :class="item.active 
                                ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' 
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                        >
                            <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0" />
                            {{ item.name }}
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- Menu Footer -->
            <div class="border-t border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <!-- Theme Toggle -->
                    <button
                        @click="toggleTheme"
                        class="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors touch-target"
                    >
                        <SunIcon v-if="isDarkMode" class="h-5 w-5" />
                        <MoonIcon v-else class="h-5 w-5" />
                        <span>{{ isDarkMode ? 'Light' : 'Dark' }} Mode</span>
                    </button>

                    <!-- Logout Button -->
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        @click="closeMenu"
                        class="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors touch-target"
                    >
                        <ArrowRightOnRectangleIcon class="h-5 w-5" />
                        <span>Logout</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useTheme } from '@/composables/useTheme'
import {
    XMarkIcon,
    HomeIcon,
    UsersIcon,
    BriefcaseIcon,
    CalendarIcon,
    ChatBubbleLeftRightIcon,
    AcademicCapIcon,
    TrophyIcon,
    HeartIcon,
    BookOpenIcon,
    UserIcon,
    CogIcon,
    BellIcon,
    SunIcon,
    MoonIcon,
    ArrowRightOnRectangleIcon,
    ChartBarIcon,
    MagnifyingGlassIcon
} from '@heroicons/vue/24/outline'

const page = usePage()
const { isDarkMode, toggleTheme } = useTheme()
const isOpen = ref(false)

const mainNavItems = computed(() => [
    {
        name: 'Dashboard',
        href: '/dashboard',
        icon: HomeIcon,
        active: page.url === '/dashboard'
    }
])

const socialNavItems = computed(() => [
    {
        name: 'Social Timeline',
        href: '/social/timeline',
        icon: ChatBubbleLeftRightIcon,
        active: page.url.startsWith('/social')
    },
    {
        name: 'Alumni Directory',
        href: '/alumni/directory',
        icon: UsersIcon,
        active: page.url.startsWith('/alumni')
    },
    {
        name: 'Events',
        href: '/events',
        icon: CalendarIcon,
        active: page.url.startsWith('/events')
    },
    {
        name: 'Success Stories',
        href: '/stories',
        icon: TrophyIcon,
        active: page.url.startsWith('/stories')
    }
])

const careerNavItems = computed(() => [
    {
        name: 'Job Dashboard',
        href: '/jobs/dashboard',
        icon: BriefcaseIcon,
        active: page.url.startsWith('/jobs')
    },
    {
        name: 'Career Center',
        href: '/career/timeline',
        icon: AcademicCapIcon,
        active: page.url.startsWith('/career')
    },
    {
        name: 'Mentorship Hub',
        href: '/career/mentorship-hub',
        icon: HeartIcon,
        active: page.url.includes('/mentorship')
    }
])

const accountNavItems = computed(() => [
    {
        name: 'Profile',
        href: '/profile',
        icon: UserIcon,
        active: page.url.startsWith('/profile')
    },
    {
        name: 'Settings',
        href: '/settings',
        icon: CogIcon,
        active: page.url.startsWith('/settings')
    },
    {
        name: 'Notifications',
        href: '/notifications',
        icon: BellIcon,
        active: page.url.startsWith('/notifications'),
        badge: page.props.auth?.unreadNotifications || null
    }
])

const toggleMenu = () => {
    isOpen.value = !isOpen.value
    
    // Prevent body scroll when menu is open
    if (isOpen.value) {
        document.body.style.overflow = 'hidden'
    } else {
        document.body.style.overflow = ''
    }
}

const closeMenu = () => {
    isOpen.value = false
    document.body.style.overflow = ''
}

const getUserInitials = (name) => {
    if (!name) return 'U'
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

// Handle escape key
const handleEscape = (e) => {
    if (e.key === 'Escape' && isOpen.value) {
        closeMenu()
    }
}

// Handle window resize
const handleResize = () => {
    if (window.innerWidth >= 1024 && isOpen.value) {
        closeMenu()
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleEscape)
    window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape)
    window.removeEventListener('resize', handleResize)
    document.body.style.overflow = ''
})

// Expose methods for parent components
defineExpose({
    open: () => { isOpen.value = true },
    close: closeMenu,
    toggle: toggleMenu
})
</script>

<style scoped>
/* Ensure proper z-index stacking */
.mobile-hamburger-menu {
    /* Component styles handled by Tailwind classes */
}

/* Smooth hamburger animation */
.hamburger-line {
    transform-origin: center;
}

/* Safe area support for devices with notches */
@supports (padding: max(0px)) {
    .mobile-hamburger-menu .fixed.top-4.left-4 {
        top: max(1rem, env(safe-area-inset-top));
        left: max(1rem, env(safe-area-inset-left));
    }
}
</style>