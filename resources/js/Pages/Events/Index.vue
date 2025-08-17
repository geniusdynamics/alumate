<template>
    <AppLayout title="Events">
        <Head title="Events" />

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Events</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Discover and join alumni events, workshops, and networking opportunities</p>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                <div class="p-6">
                    <form @submit.prevent="applyFilters" class="space-y-4">
                        <!-- Search Bar -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Search Events
                            </label>
                            <input
                                id="search"
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Search by title, description, or keywords..."
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Filter Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Event Type Filter -->
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Event Type
                                </label>
                                <select
                                    id="type"
                                    v-model="searchForm.type"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Types</option>
                                    <option v-for="type in eventTypes" :key="type" :value="type">
                                        {{ formatEventType(type) }}
                                    </option>
                                </select>
                            </div>

                            <!-- Institution Filter -->
                            <div>
                                <label for="institution" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Institution
                                </label>
                                <select
                                    id="institution"
                                    v-model="searchForm.institution_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Institutions</option>
                                    <option v-for="institution in institutions" :key="institution.id" :value="institution.id">
                                        {{ institution.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Location Filter -->
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Location
                                </label>
                                <input
                                    id="location"
                                    v-model="searchForm.location"
                                    type="text"
                                    placeholder="City, Country"
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
                    </form>
                </div>
            </div>

            <!-- Events Grid -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ events.total }} Events Found
                    </h2>
                </div>

                <div class="p-6">
                    <div v-if="events.data.length === 0" class="text-center py-12">
                        <CalendarIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No events found</h3>
                        <p class="text-gray-500 dark:text-gray-400">Try adjusting your search criteria or check back later for new events</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <EventCard
                            v-for="event in events.data"
                            :key="event.id"
                            :event="event"
                            :is-registered="userRegistrations.includes(event.id)"
                            @register="handleEventRegistration"
                            @unregister="handleEventUnregistration"
                        />
                    </div>

                    <!-- Pagination -->
                    <div v-if="events.last_page > 1" class="mt-8">
                        <Pagination :links="events.links" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import EventCard from '@/components/EventCard.vue'
import Pagination from '@/components/Pagination.vue'
import { CalendarIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    events: Object,
    institutions: Array,
    eventTypes: Array,
    userRegistrations: Array,
    filters: Object,
})

const searchForm = reactive({
    search: props.filters.search || '',
    type: props.filters.type || '',
    institution_id: props.filters.institution_id || '',
    location: props.filters.location || '',
    date_from: props.filters.date_from || '',
})

const formatEventType = (type) => {
    return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const applyFilters = () => {
    router.get(route('events.index'), searchForm, {
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

const handleEventRegistration = (eventId) => {
    router.post(route('api.events.register', eventId), {}, {
        preserveState: true,
        onSuccess: () => {
            // Add to user registrations
            if (!props.userRegistrations.includes(eventId)) {
                props.userRegistrations.push(eventId)
            }
        }
    })
}

const handleEventUnregistration = (eventId) => {
    router.delete(route('api.events.unregister', eventId), {
        preserveState: true,
        onSuccess: () => {
            // Remove from user registrations
            const index = props.userRegistrations.indexOf(eventId)
            if (index > -1) {
                props.userRegistrations.splice(index, 1)
            }
        }
    })
}
</script>
