<template>
    <AppLayout title="Alumni Recommendations">
        <Head title="Alumni Recommendations" />

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Alumni Recommendations</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Discover alumni you should connect with based on your network and interests</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-3">
                    <!-- People You May Know -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-8">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">People You May Know</h2>
                        </div>
                        <div class="p-6">
                            <div v-if="recommendations.length === 0" class="text-center py-12">
                                <UsersIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No recommendations available</h3>
                                <p class="text-gray-500 dark:text-gray-400">Complete your profile to get personalized recommendations</p>
                            </div>
                            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <RecommendationCard
                                    v-for="recommendation in recommendations"
                                    :key="recommendation.user.id"
                                    :recommendation="recommendation"
                                    @connect-requested="handleConnectRequest"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Connection Insights -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Connection Insights</h2>
                        </div>
                        <div class="p-6">
                            <ConnectionInsights :insights="connectionInsights" />
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Network Stats -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Your Network</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Total Connections</span>
                                <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ networkStats.total_connections }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Mutual Connections</span>
                                <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ networkStats.mutual_connections }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Same Institution</span>
                                <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ networkStats.same_institution }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendation Filters -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Filter Recommendations</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Institution
                                </label>
                                <select
                                    v-model="filters.institution_id"
                                    @change="applyFilters"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Institutions</option>
                                    <option v-for="institution in institutions" :key="institution.id" :value="institution.id">
                                        {{ institution.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Industry
                                </label>
                                <select
                                    v-model="filters.industry"
                                    @change="applyFilters"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Industries</option>
                                    <option v-for="industry in industries" :key="industry" :value="industry">
                                        {{ industry }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Location
                                </label>
                                <input
                                    v-model="filters.location"
                                    @input="applyFilters"
                                    type="text"
                                    placeholder="City, Country"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Actions</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <Link 
                                :href="route('alumni.directory')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <UsersIcon class="w-5 h-5" />
                                <span>Browse Directory</span>
                            </Link>
                            <Link 
                                :href="route('alumni.connections')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <UserGroupIcon class="w-5 h-5" />
                                <span>My Connections</span>
                            </Link>
                            <Link 
                                :href="route('social.timeline')"
                                class="flex items-center space-x-3 text-sm text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <ChatBubbleLeftIcon class="w-5 h-5" />
                                <span>Social Timeline</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import RecommendationCard from '@/components/RecommendationCard.vue'
import ConnectionInsights from '@/components/ConnectionInsights.vue'
import {
    UsersIcon,
    UserGroupIcon,
    ChatBubbleLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    recommendations: Array,
    connectionInsights: Array,
    networkStats: Object,
    institutions: Array,
    industries: Array,
    currentFilters: Object,
})

const filters = reactive({
    institution_id: props.currentFilters?.institution_id || '',
    industry: props.currentFilters?.industry || '',
    location: props.currentFilters?.location || '',
})

const applyFilters = () => {
    router.get(route('alumni.recommendations'), filters, {
        preserveState: true,
        preserveScroll: true,
    })
}

const handleConnectRequest = (userId) => {
    router.post(route('api.connections.request'), {
        user_id: userId
    }, {
        preserveState: true,
        onSuccess: () => {
            // Remove from recommendations or show success message
        }
    })
}
</script>