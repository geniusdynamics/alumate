<template>
    <AppLayout title="Loading States Examples">

        <Head title="Loading States Examples" />

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                    Loading States & Skeleton Screens
                </h1>
                <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
                    Comprehensive loading states with contextual feedback and accessibility support
                </p>
            </div>

            <!-- Control Panel -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Interactive Controls
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Loading Duration
                        </label>
                        <select v-model="loadingDuration"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="1000">1 second</option>
                            <option value="3000">3 seconds</option>
                            <option value="5000">5 seconds</option>
                            <option value="10000">10 seconds</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Loading Type
                        </label>
                        <select v-model="loadingType"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <option value="success">Success</option>
                            <option value="error">Error</option>
                            <option value="timeout">Timeout</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <BaseButton @click="triggerAllLoading" :disabled="isAnyLoading" class="w-full">
                            {{ isAnyLoading ? 'Loading...' : 'Trigger All Examples' }}
                        </BaseButton>
                    </div>
                </div>
            </div>
            <!-- Loading Examples Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Basic Loading States -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Basic Loading States
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Spinner Loader
                            </h4>
                            <LoadingSpinner :loading="loadingStates.spinner" size="md" color="primary" />
                            <BaseButton @click="triggerLoading('spinner')" :disabled="loadingStates.spinner"
                                class="mt-2" size="sm">
                                Test Spinner
                            </BaseButton>
                        </div>

                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Smart Loader with Context
                            </h4>
                            <SmartLoader :loading="loadingStates.smart" context="Fetching user data..."
                                :show-progress="true" :progress="smartLoaderProgress" />
                            <BaseButton @click="triggerSmartLoader" :disabled="loadingStates.smart" class="mt-2"
                                size="sm">
                                Test Smart Loader
                            </BaseButton>
                        </div>
                    </div>
                </div>

                <!-- Skeleton Loaders -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Skeleton Loaders
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Card Skeleton
                            </h4>
                            <SkeletonCard :loading="loadingStates.card" :show-avatar="true" :show-actions="true">
                                <div v-if="!loadingStates.card" class="p-4 border rounded-lg">
                                    <div class="flex items-center space-x-3 mb-3">
                                        <img src="https://via.placeholder.com/40" alt="User"
                                            class="w-10 h-10 rounded-full">
                                        <div>
                                            <h5 class="font-medium">John Doe</h5>
                                            <p class="text-sm text-gray-500">Software Engineer</p>
                                        </div>
                                    </div>
                                    <p class="text-gray-700 dark:text-gray-300">
                                        This is sample content that appears after loading.
                                    </p>
                                    <div class="flex space-x-2 mt-3">
                                        <BaseButton size="sm">Like</BaseButton>
                                        <BaseButton size="sm" variant="outline">Share</BaseButton>
                                    </div>
                                </div>
                            </SkeletonCard>
                            <BaseButton @click="triggerLoading('card')" :disabled="loadingStates.card" class="mt-2"
                                size="sm">
                                Test Card Skeleton
                            </BaseButton>
                        </div>
                    </div>
                </div>

                <!-- List Loading -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        List Loading States
                    </h3>
                    <SkeletonList :loading="loadingStates.list" :item-count="5" :show-avatar="true">
                        <div v-if="!loadingStates.list" class="space-y-3">
                            <div v-for="item in sampleListData" :key="item.id"
                                class="flex items-center space-x-3 p-3 border rounded-lg">
                                <img :src="item.avatar" :alt="item.name" class="w-10 h-10 rounded-full">
                                <div class="flex-1">
                                    <h5 class="font-medium">{{ item.name }}</h5>
                                    <p class="text-sm text-gray-500">{{ item.role }}</p>
                                </div>
                                <span class="text-xs text-gray-400">{{ item.time }}</span>
                            </div>
                        </div>
                    </SkeletonList>
                    <BaseButton @click="triggerLoading('list')" :disabled="loadingStates.list" class="mt-4" size="sm">
                        Test List Loading
                    </BaseButton>
                </div>

                <!-- Contextual Loading -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Contextual Loading
                    </h3>
                    <div class="space-y-4">
                        <ContextualLoader :loading="loadingStates.contextual" context="search"
                            :metadata="{ query: 'alumni', count: 150 }">
                            <div v-if="!loadingStates.contextual" class="p-4 border rounded-lg">
                                <h5 class="font-medium mb-2">Search Results</h5>
                                <p class="text-gray-600 dark:text-gray-400">
                                    Found 150 alumni matching your search criteria.
                                </p>
                            </div>
                        </ContextualLoader>
                        <BaseButton @click="triggerLoading('contextual')" :disabled="loadingStates.contextual"
                            size="sm">
                            Test Contextual Loading
                        </BaseButton>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Performance Metrics
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ performanceMetrics.totalLoads }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Total Loads
                        </div>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ performanceMetrics.avgLoadTime }}ms
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Avg Load Time
                        </div>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            {{ performanceMetrics.successRate }}%
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Success Rate
                        </div>
                    </div>
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                            {{ performanceMetrics.activeLoaders }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Active Loaders
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template><script 
setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BaseButton from '@/Components/ui/BaseButton.vue'
import LoadingSpinner from '@/Components/ui/LoadingSpinner.vue'
import SmartLoader from '@/Components/ui/SmartLoader.vue'
import SkeletonCard from '@/Components/ui/SkeletonCard.vue'
import SkeletonList from '@/Components/ui/SkeletonList.vue'
import ContextualLoader from '@/Components/ui/ContextualLoader.vue'
import { useLoadingStates } from '@/composables/useLoadingStates'
import { usePerformanceMonitoring } from '@/composables/usePerformanceMonitoring'

// Loading state management
const {
    loadingStates,
    setLoading,
    clearLoading,
    isAnyLoading
} = useLoadingStates([
    'spinner',
    'smart',
    'card',
    'list',
    'contextual'
])

// Performance monitoring
const {
    startMeasurement,
    endMeasurement,
    getMetrics
} = usePerformanceMonitoring()

// Control panel state
const loadingDuration = ref(3000)
const loadingType = ref<'success' | 'error' | 'timeout'>('success')

// Smart loader progress
const smartLoaderProgress = ref(0)
let progressInterval: NodeJS.Timeout | null = null

// Sample data
const sampleListData = ref([
    {
        id: 1,
        name: 'Alice Johnson',
        role: 'Senior Developer',
        avatar: 'https://via.placeholder.com/40/4F46E5/FFFFFF?text=AJ',
        time: '2 min ago'
    },
    {
        id: 2,
        name: 'Bob Smith',
        role: 'Product Manager',
        avatar: 'https://via.placeholder.com/40/059669/FFFFFF?text=BS',
        time: '5 min ago'
    },
    {
        id: 3,
        name: 'Carol Davis',
        role: 'UX Designer',
        avatar: 'https://via.placeholder.com/40/DC2626/FFFFFF?text=CD',
        time: '10 min ago'
    },
    {
        id: 4,
        name: 'David Wilson',
        role: 'DevOps Engineer',
        avatar: 'https://via.placeholder.com/40/7C3AED/FFFFFF?text=DW',
        time: '15 min ago'
    },
    {
        id: 5,
        name: 'Eva Brown',
        role: 'Data Scientist',
        avatar: 'https://via.placeholder.com/40/EA580C/FFFFFF?text=EB',
        time: '20 min ago'
    }
])

// Performance metrics
const performanceMetrics = ref({
    totalLoads: 0,
    avgLoadTime: 0,
    successRate: 100,
    activeLoaders: 0
})

// Computed properties
const isAnyLoadingComputed = computed(() => {
    return Object.values(loadingStates.value).some(state => state)
})

// Methods
const triggerLoading = async (type: string) => {
    const measurementId = `loading-${type}-${Date.now()}`

    try {
        startMeasurement(measurementId, `Loading ${type}`)
        setLoading(type, true)
        performanceMetrics.value.totalLoads++
        updateActiveLoaders()

        // Simulate different loading scenarios
        await simulateLoading(type)

        endMeasurement(measurementId)
        updatePerformanceMetrics()

    } catch (error) {
        console.error(`Loading error for ${type}:`, error)
        performanceMetrics.value.successRate = Math.max(0, performanceMetrics.value.successRate - 5)
    } finally {
        setLoading(type, false)
        updateActiveLoaders()
    }
}

const triggerSmartLoader = async () => {
    const measurementId = `smart-loader-${Date.now()}`

    try {
        startMeasurement(measurementId, 'Smart loader with progress')
        setLoading('smart', true)
        smartLoaderProgress.value = 0

        // Simulate progress updates
        progressInterval = setInterval(() => {
            smartLoaderProgress.value += Math.random() * 15
            if (smartLoaderProgress.value >= 100) {
                smartLoaderProgress.value = 100
                if (progressInterval) {
                    clearInterval(progressInterval)
                    progressInterval = null
                }
            }
        }, 200)

        await simulateLoading('smart')
        endMeasurement(measurementId)

    } finally {
        if (progressInterval) {
            clearInterval(progressInterval)
            progressInterval = null
        }
        setLoading('smart', false)
        smartLoaderProgress.value = 0
    }
}

const triggerAllLoading = async () => {
    const loaderTypes = ['spinner', 'card', 'list', 'contextual']
    const promises = loaderTypes.map(type => triggerLoading(type))

    // Also trigger smart loader
    promises.push(triggerSmartLoader())

    await Promise.all(promises)
}

const simulateLoading = (type: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        const duration = loadingDuration.value

        setTimeout(() => {
            switch (loadingType.value) {
                case 'success':
                    resolve()
                    break
                case 'error':
                    reject(new Error(`Simulated error for ${type}`))
                    break
                case 'timeout':
                    // Simulate timeout by taking longer
                    setTimeout(resolve, duration * 2)
                    break
                default:
                    resolve()
            }
        }, duration)
    })
}

const updateActiveLoaders = () => {
    performanceMetrics.value.activeLoaders = Object.values(loadingStates.value)
        .filter(state => state).length
}

const updatePerformanceMetrics = () => {
    const metrics = getMetrics()
    if (metrics.length > 0) {
        const avgTime = metrics.reduce((sum, metric) => sum + metric.duration, 0) / metrics.length
        performanceMetrics.value.avgLoadTime = Math.round(avgTime)
    }
}

// Lifecycle
onMounted(() => {
    // Initialize performance tracking
    updatePerformanceMetrics()
})

onUnmounted(() => {
    if (progressInterval) {
        clearInterval(progressInterval)
    }
})
</script>

<style scoped>
/* Custom animations for loading states */
@keyframes pulse-slow {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.loading-demo {
    animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Enhanced focus styles for accessibility */
.focus-enhanced:focus {
    outline: 2px solid theme('colors.blue.500');
    outline-offset: 2px;
}

/* Performance metrics styling */
.metric-card {
    transition: all 0.3s ease;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Dark mode enhancements */
@media (prefers-color-scheme: dark) {
    .metric-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }
}

/* Responsive grid adjustments */
@media (max-width: 768px) {
    .grid-responsive {
        grid-template-columns: 1fr;
    }
}

/* Loading state transitions */
.loading-transition {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.loading-enter-active,
.loading-leave-active {
    transition: opacity 0.3s ease;
}

.loading-enter-from,
.loading-leave-to {
    opacity: 0;
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
    .loading-demo,
    .metric-card,
    .loading-transition {
        animation: none;
        transition: none;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .bg-gray-50 {
        background-color: white;
        border: 1px solid black;
    }
    
    .dark .bg-gray-700 {
        background-color: black;
        border: 1px solid white;
    }
}
</style>