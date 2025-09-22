<template>
    <div class="mobile-hamburger-menu lg:hidden">
        <!-- Hamburger Button -->
        <button
            @click="toggleMenu"
            class="touch-target fixed left-4 top-4 z-[60] rounded-lg border border-gray-200 bg-white p-2 shadow-lg dark:border-gray-700 dark:bg-gray-800"
            :class="{ 'bg-gray-100 dark:bg-gray-700': isOpen }"
            :aria-label="isOpen ? 'Close navigation menu' : 'Open navigation menu'"
            :aria-expanded="isOpen"
            aria-controls="mobile-menu-panel"
            type="button"
        >
            <div class="flex h-6 w-6 flex-col items-center justify-center">
                <span
                    class="block h-0.5 w-5 bg-gray-600 transition-all duration-300 dark:bg-gray-300"
                    :class="isOpen ? 'translate-y-1.5 rotate-45' : ''"
                ></span>
                <span class="mt-1 block h-0.5 w-5 bg-gray-600 transition-all duration-300 dark:bg-gray-300" :class="isOpen ? 'opacity-0' : ''"></span>
                <span
                    class="mt-1 block h-0.5 w-5 bg-gray-600 transition-all duration-300 dark:bg-gray-300"
                    :class="isOpen ? '-translate-y-1.5 -rotate-45' : ''"
                ></span>
            </div>
        </button>

        <!-- Mobile Menu Overlay -->
        <div v-if="isOpen" class="fixed inset-0 z-[50] bg-black bg-opacity-50 backdrop-blur-sm" @click="closeMenu" aria-hidden="true"></div>

        <!-- Mobile Menu Panel -->
        <nav
            id="mobile-menu-panel"
            class="fixed left-0 top-0 z-[55] h-full w-80 max-w-[85vw] transform bg-white shadow-xl transition-transform duration-300 ease-in-out dark:bg-gray-800"
            :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
            role="navigation"
            :aria-hidden="!isOpen"
            aria-label="Mobile navigation menu"
        >
            <!-- Menu Header -->
            <header class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
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
                    class="touch-target p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                    aria-label="Close navigation menu"
                    type="button"
                >
                    <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                </button>
            </header>

            <!-- User Profile Section -->
            <section class="border-b border-gray-200 p-4 dark:border-gray-700" aria-labelledby="user-profile-heading">
                <h3 id="user-profile-heading" class="sr-only">User Profile</h3>
                <div class="flex items-center space-x-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600"
                        role="img"
                        :aria-label="`Profile picture for ${$page.props.auth?.user?.name}`"
                    >
                        <span class="text-sm font-medium text-white" aria-hidden="true">
                            {{ getUserInitials($page.props.auth?.user?.name) }}
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                            {{ $page.props.auth?.user?.name }}
                        </p>
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ $page.props.auth?.user?.email }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Navigation Menu -->
            <div class="flex-1 overflow-y-auto py-4" role="none">
                <div class="space-y-1 px-2">
                    <!-- Main Navigation Items -->
                    <section class="mb-6" aria-labelledby="main-nav-heading">
                        <h3 id="main-nav-heading" class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Main
                        </h3>
                        <ul role="list" class="space-y-1">
                            <li v-for="item in mainNavItems" :key="item.name">
                                <Link
                                    :href="item.href"
                                    @click="closeMenu"
                                    class="touch-target group flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                                    :class="
                                        item.active
                                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300'
                                            : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                                    "
                                    :aria-current="item.active ? 'page' : undefined"
                                >
                                    <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0" aria-hidden="true" />
                                    {{ item.name }}
                                    <span
                                        v-if="item.badge"
                                        class="ml-auto rounded-full bg-red-500 px-2 py-0.5 text-xs text-white"
                                        :aria-label="`${item.badge} notifications`"
                                    >
                                        {{ item.badge }}
                                    </span>
                                </Link>
                            </li>
                        </ul>
                    </section>

                    <!-- Social Features -->
                    <section class="mb-6" aria-labelledby="social-nav-heading">
                        <h3 id="social-nav-heading" class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Social
                        </h3>
                        <ul role="list" class="space-y-1">
                            <li v-for="item in socialNavItems" :key="item.name">
                                <Link
                                    :href="item.href"
                                    @click="closeMenu"
                                    class="touch-target group flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                                    :class="
                                        item.active
                                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300'
                                            : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                                    "
                                    :aria-current="item.active ? 'page' : undefined"
                                >
                                    <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0" aria-hidden="true" />
                                    {{ item.name }}
                                </Link>
                            </li>
                        </ul>
                    </section>

                    <!-- Career Features -->
                    <section class="mb-6" aria-labelledby="career-nav-heading">
                        <h3 id="career-nav-heading" class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Career
                        </h3>
                        <ul role="list" class="space-y-1">
                            <li v-for="item in careerNavItems" :key="item.name">
                                <Link
                                    :href="item.href"
                                    @click="closeMenu"
                                    class="touch-target group flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                                    :class="
                                        item.active
                                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300'
                                            : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                                    "
                                    :aria-current="item.active ? 'page' : undefined"
                                >
                                    <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0" aria-hidden="true" />
                                    {{ item.name }}
                                </Link>
                            </li>
                        </ul>
                    </section>

                    <!-- Settings & Account -->
                    <section class="mb-6" aria-labelledby="account-nav-heading">
                        <h3
                            id="account-nav-heading"
                            class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                        >
                            Account
                        </h3>
                        <ul role="list" class="space-y-1">
                            <li v-for="item in accountNavItems" :key="item.name">
                                <Link
                                    :href="item.href"
                                    @click="closeMenu"
                                    class="touch-target group flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                                    :class="
                                        item.active
                                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300'
                                            : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                                    "
                                    :aria-current="item.active ? 'page' : undefined"
                                >
                                    <component :is="item.icon" class="mr-3 h-5 w-5 flex-shrink-0" aria-hidden="true" />
                                    {{ item.name }}
                                    <span
                                        v-if="item.badge"
                                        class="ml-auto rounded-full bg-red-500 px-2 py-0.5 text-xs text-white"
                                        :aria-label="`${item.badge} notifications`"
                                    >
                                        {{ item.badge }}
                                    </span>
                                </Link>
                            </li>
                        </ul>
                    </section>
                </div>
            </div>

            <!-- Menu Footer -->
            <footer class="border-t border-gray-200 p-4 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <!-- Theme Toggle -->
                    <button
                        @click="toggleTheme"
                        class="theme-toggle touch-target flex items-center space-x-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        :aria-label="`Switch to ${isDarkMode ? 'light' : 'dark'} mode`"
                        type="button"
                    >
                        <div class="relative h-5 w-5" aria-hidden="true">
                            <SunIcon
                                class="theme-toggle-icon theme-toggle-sun absolute inset-0"
                                :class="{ 'rotate-90 scale-0': isDarkMode, 'rotate-0 scale-100': !isDarkMode }"
                            />
                            <MoonIcon
                                class="theme-toggle-icon theme-toggle-moon absolute inset-0"
                                :class="{ 'rotate-0 scale-100': isDarkMode, 'rotate-90 scale-0': !isDarkMode }"
                            />
                        </div>
                        <span>{{ isDarkMode ? 'Light' : 'Dark' }} Mode</span>
                    </button>

                    <!-- Logout Button -->
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        @click="closeMenu"
                        class="touch-target flex items-center space-x-2 rounded-lg px-3 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                        aria-label="Sign out of your account"
                    >
                        <ArrowRightOnRectangleIcon class="h-5 w-5" aria-hidden="true" />
                        <span>Logout</span>
                    </Link>
                </div>
            </footer>
        </nav>
    </div>
</template>

<script setup>
import { useTheme } from '@/composables/useTheme';
import {
    AcademicCapIcon,
    ArrowRightOnRectangleIcon,
    BellIcon,
    BriefcaseIcon,
    CalendarIcon,
    ChatBubbleLeftRightIcon,
    CogIcon,
    HeartIcon,
    HomeIcon,
    MoonIcon,
    SunIcon,
    TrophyIcon,
    UserIcon,
    UsersIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page = usePage();
const { isDarkMode, toggleTheme } = useTheme();
const isOpen = ref(false);

const mainNavItems = computed(() => [
    {
        name: 'Dashboard',
        href: '/dashboard',
        icon: HomeIcon,
        active: page.url === '/dashboard',
    },
]);

const socialNavItems = computed(() => [
    {
        name: 'Social Timeline',
        href: '/social/timeline',
        icon: ChatBubbleLeftRightIcon,
        active: page.url.startsWith('/social'),
    },
    {
        name: 'Alumni Directory',
        href: '/alumni/directory',
        icon: UsersIcon,
        active: page.url.startsWith('/alumni'),
    },
    {
        name: 'Events',
        href: '/events',
        icon: CalendarIcon,
        active: page.url.startsWith('/events'),
    },
    {
        name: 'Success Stories',
        href: '/stories',
        icon: TrophyIcon,
        active: page.url.startsWith('/stories'),
    },
]);

const careerNavItems = computed(() => [
    {
        name: 'Job Dashboard',
        href: '/jobs/dashboard',
        icon: BriefcaseIcon,
        active: page.url.startsWith('/jobs'),
    },
    {
        name: 'Career Center',
        href: '/career/timeline',
        icon: AcademicCapIcon,
        active: page.url.startsWith('/career'),
    },
    {
        name: 'Mentorship Hub',
        href: '/career/mentorship-hub',
        icon: HeartIcon,
        active: page.url.includes('/mentorship'),
    },
]);

const accountNavItems = computed(() => [
    {
        name: 'Profile',
        href: '/profile',
        icon: UserIcon,
        active: page.url.startsWith('/profile'),
    },
    {
        name: 'Settings',
        href: '/settings',
        icon: CogIcon,
        active: page.url.startsWith('/settings'),
    },
    {
        name: 'Notifications',
        href: '/notifications',
        icon: BellIcon,
        active: page.url.startsWith('/notifications'),
        badge: page.props.auth?.unreadNotifications || null,
    },
]);

const toggleMenu = () => {
    isOpen.value = !isOpen.value;

    // Prevent body scroll when menu is open
    if (isOpen.value) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
};

const closeMenu = () => {
    isOpen.value = false;
    document.body.style.overflow = '';
};

const getUserInitials = (name) => {
    if (!name) return 'U';
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

// Handle escape key
const handleEscape = (e) => {
    if (e.key === 'Escape' && isOpen.value) {
        closeMenu();
    }
};

// Handle window resize
const handleResize = () => {
    if (window.innerWidth >= 1024 && isOpen.value) {
        closeMenu();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleEscape);
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
    window.removeEventListener('resize', handleResize);
    document.body.style.overflow = '';
});

// Expose methods for parent components
defineExpose({
    open: () => {
        isOpen.value = true;
    },
    close: closeMenu,
    toggle: toggleMenu,
});
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














