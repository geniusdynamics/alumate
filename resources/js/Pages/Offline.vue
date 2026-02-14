<template>
    <div class="flex min-h-screen flex-col justify-center bg-gray-50 py-12 sm:px-6 lg:px-8 dark:bg-gray-900">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center">
                <!-- Offline Icon -->
                <div class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
                    <WifiOffIcon class="h-12 w-12 text-gray-400 dark:text-gray-500" />
                </div>

                <h1 class="mb-4 text-3xl font-bold text-gray-900 dark:text-white">You're Offline</h1>

                <p class="mb-8 text-lg text-gray-600 dark:text-gray-400">
                    It looks like you've lost your internet connection. Don't worry, you can still access some features.
                </p>
            </div>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white px-4 py-8 shadow sm:rounded-lg sm:px-10 dark:bg-gray-800">
                <!-- Connection Status -->
                <div class="mb-6">
                    <div class="flex items-center justify-between rounded-lg bg-red-50 p-3 dark:bg-red-900/20">
                        <div class="flex items-center">
                            <div class="mr-3 h-3 w-3 rounded-full bg-red-500"></div>
                            <span class="text-sm font-medium text-red-800 dark:text-red-200">
                                {{ connectionStatus }}
                            </span>
                        </div>
                        <button
                            @click="checkConnection"
                            :disabled="isChecking"
                            class="text-sm text-red-600 hover:text-red-500 disabled:opacity-50 dark:text-red-400"
                        >
                            {{ isChecking ? 'Checking...' : 'Retry' }}
                        </button>
                    </div>
                </div>

                <!-- Available Offline Features -->
                <div class="mb-6">
                    <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">Available Offline</h3>

                    <div class="space-y-3">
                        <div
                            v-for="feature in offlineFeatures"
                            :key="feature.name"
                            class="flex cursor-pointer items-center rounded-lg bg-gray-50 p-3 transition-colors hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600"
                            @click="navigateToFeature(feature.path)"
                        >
                            <component :is="feature.icon" class="mr-3 h-5 w-5 text-gray-600 dark:text-gray-400" />
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ feature.name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ feature.description }}
                                </div>
                            </div>
                            <ChevronRightIcon class="h-4 w-4 text-gray-400" />
                        </div>
                    </div>
                </div>

                <!-- Cached Content -->
                <div class="mb-6" v-if="cachedContent.length > 0">
                    <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">Recently Viewed</h3>

                    <div class="space-y-2">
                        <div
                            v-for="content in cachedContent"
                            :key="content.url"
                            class="flex cursor-pointer items-center rounded bg-gray-50 p-2 transition-colors hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600"
                            @click="navigateToContent(content.url)"
                        >
                            <div class="flex-1">
                                <div class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                    {{ content.title }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatDate(content.cachedAt) }}
                                </div>
                            </div>
                            <ChevronRightIcon class="h-4 w-4 text-gray-400" />
                        </div>
                    </div>
                </div>

                <!-- Tips -->
                <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                    <h4 class="mb-2 text-sm font-medium text-blue-800 dark:text-blue-200">ðŸ’¡ Offline Tips</h4>
                    <ul class="space-y-1 text-xs text-blue-700 dark:text-blue-300">
                        <li>â€¢ Check your WiFi or mobile data connection</li>
                        <li>â€¢ Some content may be available from cache</li>
                        <li>â€¢ Your actions will sync when you're back online</li>
                        <li>â€¢ Try refreshing the page once connected</li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="mt-6 space-y-3">
                    <button
                        @click="refreshPage"
                        class="flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                        :disabled="isChecking"
                    >
                        {{ isChecking ? 'Checking Connection...' : 'Try Again' }}
                    </button>

                    <button
                        @click="goHome"
                        class="flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    >
                        Go to Dashboard
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">Alumni Platform â€¢ Offline Mode</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { BookmarkIcon, BriefcaseIcon, ChevronRightIcon, ClockIcon, UserGroupIcon, WifiIcon as WifiOffIcon } from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

const connectionStatus = ref('No internet connection');
const isChecking = ref(false);
const cachedContent = ref([]);

const offlineFeatures = [
    {
        name: 'Saved Profiles',
        description: 'View previously saved alumni profiles',
        icon: UserGroupIcon,
        path: '/alumni/saved',
    },
    {
        name: 'Bookmarked Jobs',
        description: 'Access your bookmarked job opportunities',
        icon: BriefcaseIcon,
        path: '/jobs/bookmarks',
    },
    {
        name: 'Saved Content',
        description: 'View your saved posts and articles',
        icon: BookmarkIcon,
        path: '/saved',
    },
    {
        name: 'Recent Activity',
        description: 'Check your recent platform activity',
        icon: ClockIcon,
        path: '/activity',
    },
];

onMounted(() => {
    checkConnection();
    loadCachedContent();

    // Listen for online/offline events
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);
});

onUnmounted(() => {
    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);
});

const checkConnection = async () => {
    isChecking.value = true;

    try {
        // Try to fetch a small resource to test connectivity
        const response = await fetch('/api/ping', {
            method: 'HEAD',
            cache: 'no-cache',
        });

        if (response.ok) {
            handleOnline();
        } else {
            handleOffline();
        }
    } catch (error) {
        handleOffline();
    } finally {
        isChecking.value = false;
    }
};

const handleOnline = () => {
    connectionStatus.value = 'Connected';
    // Redirect to the intended page or dashboard
    setTimeout(() => {
        router.visit('/dashboard');
    }, 1000);
};

const handleOffline = () => {
    connectionStatus.value = 'No internet connection';
};

const loadCachedContent = async () => {
    try {
        // Load cached content from service worker cache
        if ('caches' in window) {
            const cache = await caches.open('alumni-dynamic-v1.0.0');
            const requests = await cache.keys();

            const content = [];
            for (const request of requests.slice(0, 5)) {
                // Show last 5
                const url = new URL(request.url);
                if (url.pathname.startsWith('/') && !url.pathname.startsWith('/api/')) {
                    content.push({
                        title: getPageTitle(url.pathname),
                        url: url.pathname,
                        cachedAt: Date.now() - Math.random() * 86400000, // Mock timestamp
                    });
                }
            }

            cachedContent.value = content;
        }
    } catch (error) {
        console.error('Failed to load cached content:', error);
    }
};

const getPageTitle = (pathname) => {
    const titles = {
        '/dashboard': 'Dashboard',
        '/alumni/directory': 'Alumni Directory',
        '/jobs/dashboard': 'Job Dashboard',
        '/events': 'Events',
        '/social/timeline': 'Social Timeline',
        '/stories': 'Success Stories',
    };

    return titles[pathname] || pathname.split('/').pop() || 'Page';
};

const formatDate = (timestamp) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));

    if (diffHours < 1) {
        return 'Just now';
    } else if (diffHours < 24) {
        return `${diffHours}h ago`;
    } else {
        return `${Math.floor(diffHours / 24)}d ago`;
    }
};

const navigateToFeature = (path) => {
    router.visit(path);
};

const navigateToContent = (url) => {
    router.visit(url);
};

const refreshPage = () => {
    checkConnection();
};

const goHome = () => {
    router.visit('/dashboard');
};
</script>

