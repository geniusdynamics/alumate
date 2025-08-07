<template>
    <div class="mobile-navigation lg:hidden">
        <!-- Mobile Bottom Navigation -->
        <div class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 safe-area-bottom">
            <div class="grid grid-cols-5 h-16">
                <Link
                    v-for="item in bottomNavItems"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'flex flex-col items-center justify-center space-y-1 text-xs font-medium transition-colors',
                        item.active 
                            ? 'text-blue-600 dark:text-blue-400' 
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                    ]"
                >
                    <component :is="item.icon" class="h-5 w-5" />
                    <span class="truncate">{{ item.name }}</span>
                    <div v-if="item.badge" class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 rounded-full flex items-center justify-center">
                        <span class="text-xs text-white">{{ item.badge }}</span>
                    </div>
                </Link>
            </div>
        </div>

        <!-- Mobile Pull-to-Refresh -->
        <div
            v-if="showPullToRefresh"
            class="fixed top-0 left-0 right-0 z-40 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800 transition-all duration-300"
            :style="{ transform: `translateY(${pullDistance}px)` }"
        >
            <div class="flex items-center justify-center py-4">
                <div v-if="isPulling" class="flex items-center space-x-2 text-blue-600 dark:text-blue-400">
                    <ArrowPathIcon class="h-5 w-5 animate-spin" />
                    <span class="text-sm font-medium">Refreshing...</span>
                </div>
                <div v-else class="flex items-center space-x-2 text-blue-600 dark:text-blue-400">
                    <ArrowDownIcon class="h-5 w-5" />
                    <span class="text-sm font-medium">Pull to refresh</span>
                </div>
            </div>
        </div>

        <!-- Mobile Search Overlay -->
        <div
            v-if="showMobileSearch"
            class="fixed inset-0 z-50 bg-white dark:bg-gray-900"
        >
            <div class="flex flex-col h-full">
                <!-- Search Header -->
                <div class="flex items-center p-4 border-b border-gray-200 dark:border-gray-700">
                    <button
                        @click="closeMobileSearch"
                        class="mr-3 p-2 -ml-2 text-gray-500 dark:text-gray-400"
                    >
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                    <div class="flex-1">
                        <GlobalSearch 
                            ref="mobileSearchRef"
                            placeholder="Search everything..."
                        />
                    </div>
                </div>

                <!-- Search Content -->
                <div class="flex-1 overflow-y-auto p-4">
                    <!-- Recent Searches -->
                    <div v-if="recentMobileSearches.length > 0" class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Recent</h3>
                        <div class="space-y-2">
                            <button
                                v-for="search in recentMobileSearches"
                                :key="search.id"
                                @click="executeSearch(search.query, search.type)"
                                class="w-full flex items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-left"
                            >
                                <ClockIcon class="h-5 w-5 text-gray-400 mr-3" />
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ search.query }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ search.type }}</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Quick Actions</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                v-for="action in quickActions"
                                :key="action.name"
                                @click="executeQuickAction(action)"
                                class="flex flex-col items-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg"
                            >
                                <component :is="action.icon" class="h-8 w-8 text-blue-600 dark:text-blue-400 mb-2" />
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ action.name }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Notification Panel -->
        <div
            v-if="showMobileNotifications"
            class="fixed inset-0 z-50 bg-white dark:bg-gray-900"
        >
            <div class="flex flex-col h-full">
                <!-- Notifications Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Notifications</h2>
                    <button
                        @click="closeMobileNotifications"
                        class="p-2 text-gray-500 dark:text-gray-400"
                    >
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>

                <!-- Notifications Content -->
                <div class="flex-1 overflow-y-auto">
                    <NotificationDropdown :mobile="true" />
                </div>
            </div>
        </div>

        <!-- Floating Action Button -->
        <div class="fixed bottom-20 right-4 z-40">
            <button
                @click="showQuickActions = !showQuickActions"
                class="w-14 h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg flex items-center justify-center transition-all duration-200"
                :class="{ 'rotate-45': showQuickActions }"
            >
                <PlusIcon class="h-6 w-6" />
            </button>

            <!-- Quick Actions Menu -->
            <div
                v-if="showQuickActions"
                class="absolute bottom-16 right-0 mb-2 space-y-2"
            >
                <button
                    v-for="action in fabActions"
                    :key="action.name"
                    @click="executeFabAction(action)"
                    class="flex items-center space-x-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-4 py-3 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 whitespace-nowrap"
                >
                    <component :is="action.icon" class="h-5 w-5" />
                    <span class="text-sm font-medium">{{ action.name }}</span>
                </button>
            </div>
        </div>

        <!-- Backdrop for overlays -->
        <div
            v-if="showQuickActions"
            class="fixed inset-0 z-30"
            @click="showQuickActions = false"
        ></div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import GlobalSearch from '@/components/GlobalSearch.vue'
import NotificationDropdown from '@/components/NotificationDropdown.vue'
import {
    HomeIcon,
    UsersIcon,
    BriefcaseIcon,
    CalendarIcon,
    BellIcon,
    MagnifyingGlassIcon,
    PlusIcon,
    XMarkIcon,
    ClockIcon,
    ArrowPathIcon,
    ArrowDownIcon,
    PencilIcon,
    CameraIcon,
    UserPlusIcon
} from '@heroicons/vue/24/outline'

const page = usePage()
const showMobileSearch = ref(false)
const showMobileNotifications = ref(false)
const showQuickActions = ref(false)
const showPullToRefresh = ref(false)
const isPulling = ref(false)
const pullDistance = ref(0)
const mobileSearchRef = ref(null)

const bottomNavItems = computed(() => [
    {
        name: 'Home',
        href: '/dashboard',
        icon: HomeIcon,
        active: page.url.startsWith('/dashboard')
    },
    {
        name: 'Alumni',
        href: '/alumni/directory',
        icon: UsersIcon,
        active: page.url.startsWith('/alumni')
    },
    {
        name: 'Jobs',
        href: '/jobs/dashboard',
        icon: BriefcaseIcon,
        active: page.url.startsWith('/jobs')
    },
    {
        name: 'Events',
        href: '/events',
        icon: CalendarIcon,
        active: page.url.startsWith('/events')
    },
    {
        name: 'More',
        href: '#',
        icon: BellIcon,
        active: false,
        badge: page.props.auth?.unreadNotifications || null
    }
])

const quickActions = [
    { name: 'Find Alumni', icon: UsersIcon, action: 'search', type: 'alumni' },
    { name: 'Browse Jobs', icon: BriefcaseIcon, action: 'navigate', url: '/jobs' },
    { name: 'View Events', icon: CalendarIcon, action: 'navigate', url: '/events' },
    { name: 'Notifications', icon: BellIcon, action: 'notifications' }
]

const fabActions = [
    { name: 'Create Post', icon: PencilIcon, action: 'create-post' },
    { name: 'Add Photo', icon: CameraIcon, action: 'add-photo' },
    { name: 'Invite Alumni', icon: UserPlusIcon, action: 'invite-alumni' },
    { name: 'Search', icon: MagnifyingGlassIcon, action: 'search' }
]

const recentMobileSearches = ref([])

onMounted(() => {
    loadRecentSearches()
    setupPullToRefresh()
})

onUnmounted(() => {
    removePullToRefresh()
})

const loadRecentSearches = () => {
    const stored = localStorage.getItem('mobile_recent_searches')
    if (stored) {
        recentMobileSearches.value = JSON.parse(stored).slice(0, 5)
    }
}

const executeSearch = (query, type) => {
    closeMobileSearch()
    // Add to recent searches
    addToRecentSearches(query, type)
    
    const routes = {
        alumni: '/alumni/directory',
        jobs: '/jobs',
        events: '/events'
    }
    
    router.visit(routes[type] || '/search', {
        data: { search: query }
    })
}

const executeQuickAction = (action) => {
    if (action.action === 'search') {
        showMobileSearch.value = true
    } else if (action.action === 'navigate') {
        router.visit(action.url)
    } else if (action.action === 'notifications') {
        showMobileNotifications.value = true
    }
}

const executeFabAction = (action) => {
    showQuickActions.value = false
    
    if (action.action === 'search') {
        showMobileSearch.value = true
    } else if (action.action === 'create-post') {
        // Trigger post creation modal
        window.dispatchEvent(new CustomEvent('open-post-creator'))
    } else if (action.action === 'add-photo') {
        // Trigger photo upload
        window.dispatchEvent(new CustomEvent('open-photo-upload'))
    } else if (action.action === 'invite-alumni') {
        router.visit('/alumni/invite')
    }
}

const closeMobileSearch = () => {
    showMobileSearch.value = false
}

const closeMobileNotifications = () => {
    showMobileNotifications.value = false
}

const addToRecentSearches = (query, type) => {
    const search = { id: Date.now(), query, type, timestamp: Date.now() }
    
    recentMobileSearches.value = recentMobileSearches.value.filter(s => 
        !(s.query === query && s.type === type)
    )
    
    recentMobileSearches.value.unshift(search)
    recentMobileSearches.value = recentMobileSearches.value.slice(0, 5)
    
    localStorage.setItem('mobile_recent_searches', JSON.stringify(recentMobileSearches.value))
}

// Pull to refresh functionality
let startY = 0
let currentY = 0
let isRefreshing = false

const setupPullToRefresh = () => {
    document.addEventListener('touchstart', handleTouchStart, { passive: true })
    document.addEventListener('touchmove', handleTouchMove, { passive: false })
    document.addEventListener('touchend', handleTouchEnd, { passive: true })
}

const removePullToRefresh = () => {
    document.removeEventListener('touchstart', handleTouchStart)
    document.removeEventListener('touchmove', handleTouchMove)
    document.removeEventListener('touchend', handleTouchEnd)
}

const handleTouchStart = (e) => {
    startY = e.touches[0].clientY
}

const handleTouchMove = (e) => {
    if (isRefreshing) return
    
    currentY = e.touches[0].clientY
    const diff = currentY - startY
    
    // Only trigger if at top of page and pulling down
    if (window.scrollY === 0 && diff > 0) {
        e.preventDefault()
        
        const maxPull = 100
        const distance = Math.min(diff * 0.5, maxPull)
        
        if (distance > 10) {
            showPullToRefresh.value = true
            pullDistance.value = distance
        }
    }
}

const handleTouchEnd = () => {
    if (pullDistance.value > 50 && !isRefreshing) {
        triggerRefresh()
    } else {
        resetPullToRefresh()
    }
}

const triggerRefresh = () => {
    isRefreshing = true
    isPulling.value = true
    
    // Simulate refresh
    setTimeout(() => {
        window.location.reload()
    }, 1000)
}

const resetPullToRefresh = () => {
    showPullToRefresh.value = false
    pullDistance.value = 0
    isPulling.value = false
    isRefreshing = false
}

// Expose methods for parent components
defineExpose({
    openSearch: () => { showMobileSearch.value = true },
    openNotifications: () => { showMobileNotifications.value = true }
})
</script>

<style scoped>
.safe-area-bottom {
    padding-bottom: env(safe-area-inset-bottom);
}

.mobile-navigation {
    /* Ensure proper z-index stacking */
}
</style>
