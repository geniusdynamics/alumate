<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center">
                <!-- Offline Icon -->
                <div class="mx-auto h-24 w-24 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center mb-6">
                    <WifiOffIcon class="h-12 w-12 text-gray-400 dark:text-gray-500" />
                </div>
                
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    You're Offline
                </h1>
                
                <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
                    It looks like you've lost your internet connection. Don't worry, you can still access some features.
                </p>
            </div>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <!-- Connection Status -->
                <div class="mb-6">
                    <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <div class="flex items-center">
                            <div class="h-3 w-3 bg-red-500 rounded-full mr-3"></div>
                            <span class="text-sm font-medium text-red-800 dark:text-red-200">
                                {{ connectionStatus }}
                            </span>
                        </div>
                        <button
                            @click="checkConnection"
                            :disabled="isChecking"
                            class="text-sm text-red-600 dark:text-red-400 hover:text-red-500 disabled:opacity-50"
                        >
                            {{ isChecking ? 'Checking...' : 'Retry' }}
                        </button>
                    </div>
                </div>

                <!-- Available Offline Features -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Available Offline
                    </h3>
                    
                    <div class="space-y-3">
                        <div
                            v-for="feature in offlineFeatures"
                            :key="feature.name"
                            class="flex items-center p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                            @click="navigateToFeature(feature.path)"
                        >
                            <component :is="feature.icon" class="h-5 w-5 text-gray-600 dark:text-gray-400 mr-3" />
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
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Recently Viewed
                    </h3>
                    
                    <div class="space-y-2">
                        <div
                            v-for="content in cachedContent"
                            :key="content.url"
                            class="flex items-center p-2 bg-gray-50 dark:bg-gray-700 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                            @click="navigateToContent(content.url)"
                        >
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
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
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                        💡 Offline Tips
                    </h4>
                    <ul class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
                        <li>• Check your WiFi or mobile data connection</li>
                        <li>• Some content may be available from cache</li>
                        <li>• Your actions will sync when you're back online</li>
                        <li>• Try refreshing the page once connected</li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="mt-6 space-y-3">
                    <button
                        @click="refreshPage"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                        :disabled="isChecking"
                    >
                        {{ isChecking ? 'Checking Connection...' : 'Try Again' }}
                    </button>
                    
                    <button
                        @click="goHome"
                        class="w-full flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Go to Dashboard
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Alumni Platform • Offline Mode
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    WifiIcon as WifiOffIcon,
    ChevronRightIcon,
    UserGroupIcon,
    BriefcaseIcon,
    BookmarkIcon,
    ClockIcon
} from '@heroicons/vue/24/outline'

const connectionStatus = ref('No internet connection')
const isChecking = ref(false)
const cachedContent = ref([])

const offlineFeatures = [
    {
        name: 'Saved Profiles',
        description: 'View previously saved alumni profiles',
        icon: UserGroupIcon,
        path: '/alumni/saved'
    },
    {
        name: 'Bookmarked Jobs',
        description: 'Access your bookmarked job opportunities',
        icon: BriefcaseIcon,
        path: '/jobs/bookmarks'
    },
    {
        name: 'Saved Content',
        description: 'View your saved posts and articles',
        icon: BookmarkIcon,
        path: '/saved'
    },
    {
        name: 'Recent Activity',
        description: 'Check your recent platform activity',
        icon: ClockIcon,
        path: '/activity'
    }
]

onMounted(() => {
    checkConnection()
    loadCachedContent()
    
    // Listen for online/offline events
    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)
})

onUnmounted(() => {
    window.removeEventListener('online', handleOnline)
    window.removeEventListener('offline', handleOffline)
})

const checkConnection = async () => {
    isChecking.value = true
    
    try {
        // Try to fetch a small resource to test connectivity
        const response = await fetch('/api/ping', {
            method: 'HEAD',
            cache: 'no-cache'
        })
        
        if (response.ok) {
            handleOnline()
        } else {
            handleOffline()
        }
    } catch (error) {
        handleOffline()
    } finally {
        isChecking.value = false
    }
}

const handleOnline = () => {
    connectionStatus.value = 'Connected'
    // Redirect to the intended page or dashboard
    setTimeout(() => {
        router.visit('/dashboard')
    }, 1000)
}

const handleOffline = () => {
    connectionStatus.value = 'No internet connection'
}

const loadCachedContent = async () => {
    try {
        // Load cached content from service worker cache
        if ('caches' in window) {
            const cache = await caches.open('alumni-dynamic-v1.0.0')
            const requests = await cache.keys()
            
            const content = []
            for (const request of requests.slice(0, 5)) { // Show last 5
                const url = new URL(request.url)
                if (url.pathname.startsWith('/') && !url.pathname.startsWith('/api/')) {
                    content.push({
                        title: getPageTitle(url.pathname),
                        url: url.pathname,
                        cachedAt: Date.now() - Math.random() * 86400000 // Mock timestamp
                    })
                }
            }
            
            cachedContent.value = content
        }
    } catch (error) {
        console.error('Failed to load cached content:', error)
    }
}

const getPageTitle = (pathname) => {
    const titles = {
        '/dashboard': 'Dashboard',
        '/alumni/directory': 'Alumni Directory',
        '/jobs/dashboard': 'Job Dashboard',
        '/events': 'Events',
        '/social/timeline': 'Social Timeline',
        '/stories': 'Success Stories'
    }
    
    return titles[pathname] || pathname.split('/').pop() || 'Page'
}

const formatDate = (timestamp) => {
    const date = new Date(timestamp)
    const now = new Date()
    const diffMs = now - date
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
    
    if (diffHours < 1) {
        return 'Just now'
    } else if (diffHours < 24) {
        return `${diffHours}h ago`
    } else {
        return `${Math.floor(diffHours / 24)}d ago`
    }
}

const navigateToFeature = (path) => {
    router.visit(path)
}

const navigateToContent = (url) => {
    router.visit(url)
}

const refreshPage = () => {
    checkConnection()
}

const goHome = () => {
    router.visit('/dashboard')
}
</script>
