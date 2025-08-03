<template>
    <AppLayout title="Reunions">
        <Head title="Reunions" />

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Alumni Reunions</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Reconnect with classmates and celebrate your shared memories</p>
            </div>

            <!-- Featured Reunion -->
            <div v-if="featuredReunion" class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Featured Reunion</h2>
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg p-8 text-white">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                        <div>
                            <h3 class="text-2xl font-bold mb-2">{{ featuredReunion.title }}</h3>
                            <p class="text-blue-100 mb-4">{{ featuredReunion.description }}</p>
                            <div class="space-y-2 mb-6">
                                <div class="flex items-center space-x-2">
                                    <CalendarIcon class="w-5 h-5" />
                                    <span>{{ formatDate(featuredReunion.start_date) }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <MapPinIcon class="w-5 h-5" />
                                    <span>{{ featuredReunion.location }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <UsersIcon class="w-5 h-5" />
                                    <span>{{ featuredReunion.attendee_count }} attending</span>
                                </div>
                            </div>
                            <Link
                                :href="route('reunions.show', featuredReunion.id)"
                                class="bg-white text-blue-600 px-6 py-3 rounded-md font-medium hover:bg-gray-100 transition-colors"
                            >
                                Learn More
                            </Link>
                        </div>
                        <div class="text-center">
                            <div class="text-4xl font-bold mb-2">{{ getDaysUntil(featuredReunion.start_date) }}</div>
                            <div class="text-blue-100">Days Until Reunion</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                <div class="p-6">
                    <form @submit.prevent="applyFilters" class="space-y-4">
                        <!-- Search Bar -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Search Reunions
                            </label>
                            <input
                                id="search"
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Search by class year, department, or event name..."
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Filter Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Reunion Type -->
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Type
                                </label>
                                <select
                                    id="type"
                                    v-model="searchForm.type"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Types</option>
                                    <option value="class_reunion">Class Reunion</option>
                                    <option value="department_reunion">Department Reunion</option>
                                    <option value="milestone_celebration">Milestone Celebration</option>
                                    <option value="homecoming">Homecoming</option>
                                    <option value="special_event">Special Event</option>
                                </select>
                            </div>

                            <!-- Graduation Year -->
                            <div>
                                <label for="graduation_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Class Year
                                </label>
                                <select
                                    id="graduation_year"
                                    v-model="searchForm.graduation_year"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Years</option>
                                    <option v-for="year in graduationYears" :key="year" :value="year">
                                        Class of {{ year }}
                                    </option>
                                </select>
                            </div>

                            <!-- Location -->
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Location
                                </label>
                                <input
                                    id="location"
                                    v-model="searchForm.location"
                                    type="text"
                                    placeholder="City, State"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                />
                            </div>

                            <!-- Date Range -->
                            <div>
                                <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Date From
                                </label>
                                <input
                                    id="date_from"
                                    v-model="searchForm.date_from"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-between items-center">
                            <div class="flex space-x-3">
                                <button
                                    type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors"
                                >
                                    Apply Filters
                                </button>
                                <button
                                    type="button"
                                    @click="clearFilters"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-md font-medium transition-colors"
                                >
                                    Clear All
                                </button>
                            </div>
                            
                            <Link
                                :href="route('reunions.create')"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium transition-colors"
                            >
                                Organize Reunion
                            </Link>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Reunions Grid -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ reunions.total }} Reunions Found
                    </h2>
                </div>

                <div class="p-6">
                    <div v-if="reunions.data.length === 0" class="text-center py-12">
                        <UsersIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No reunions found</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">Try adjusting your search criteria or be the first to organize a reunion!</p>
                        <Link
                            :href="route('reunions.create')"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition-colors"
                        >
                            Organize a Reunion
                        </Link>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <ReunionCard
                            v-for="reunion in reunions.data"
                            :key="reunion.id"
                            :reunion="reunion"
                            @rsvp-updated="handleRsvpUpdate"
                            @favorite-toggled="handleFavoriteToggle"
                        />
                    </div>

                    <!-- Pagination -->
                    <div v-if="reunions.last_page > 1" class="mt-8">
                        <Pagination :links="reunions.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import { format, differenceInDays } from 'date-fns'
import AppLayout from '@/layouts/AppLayout.vue'
import ReunionCard from '@/Components/ReunionCard.vue'
import Pagination from '@/Components/Pagination.vue'
import {
    CalendarIcon,
    MapPinIcon,
    UsersIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    reunions: Object,
    featuredReunion: Object,
    graduationYears: Array,
    filters: Object,
})

const searchForm = reactive({
    search: props.filters.search || '',
    type: props.filters.type || '',
    graduation_year: props.filters.graduation_year || '',
    location: props.filters.location || '',
    date_from: props.filters.date_from || '',
})

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMMM dd, yyyy')
}

const getDaysUntil = (dateString) => {
    const targetDate = new Date(dateString)
    const today = new Date()
    const days = differenceInDays(targetDate, today)
    return days > 0 ? days : 0
}

const applyFilters = () => {
    router.get(route('reunions.index'), searchForm, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    Object.keys(searchForm).forEach(key => {
        searchForm[key] = ''
    })
    applyFilters()
}

const handleRsvpUpdate = (reunionId, status) => {
    // Update local state or reload data
    router.reload({ only: ['reunions'] })
}

const handleFavoriteToggle = (reunionId, isFavorited) => {
    // Update local state or reload data
    router.reload({ only: ['reunions'] })
}
</script>
