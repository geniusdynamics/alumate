<template>
    <AppLayout title="Events">
        <Head title="Events" />

        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Events</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Discover and join alumni events, workshops, and networking opportunities</p>
            </div>

            <!-- Search and Filters -->
            <div class="mb-6 rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-6">
                    <form @submit.prevent="applyFilters" class="space-y-4">
                        <!-- Search Bar -->
                        <div>
                            <label for="search" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Search Events </label>
                            <input
                                id="search"
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Search by title, description, or keywords..."
                                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Filter Row -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <!-- Event Type Filter -->
                            <div>
                                <label for="type" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Event Type </label>
                                <select
                                    id="type"
                                    v-model="searchForm.type"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Types</option>
                                    <option v-for="type in eventTypes" :key="type" :value="type">
                                        {{ formatEventType(type) }}
                                    </option>
                                </select>
                            </div>

                            <!-- Institution Filter -->
                            <div>
                                <label for="institution" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Institution </label>
                                <select
                                    id="institution"
                                    v-model="searchForm.institution_id"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Institutions</option>
                                    <option v-for="institution in institutions" :key="institution.id" :value="institution.id">
                                        {{ institution.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Location Filter -->
                            <div>
                                <label for="location" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Location </label>
                                <input
                                    id="location"
                                    v-model="searchForm.location"
                                    type="text"
                                    placeholder="City, Country"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>

                            <!-- Date Range -->
                            <div>
                                <label for="date_from" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Date From </label>
                                <input
                                    id="date_from"
                                    v-model="searchForm.date_from"
                                    type="date"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-3">
                            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700">
                                Apply Filters
                            </button>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="rounded-md bg-gray-300 px-4 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-400"
                            >
                                Clear All
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Events Grid -->
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ events.total }} Events Found</h2>
                </div>

                <div class="p-6">
                    <div v-if="events.data.length === 0" class="py-12 text-center">
                        <CalendarIcon class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                        <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-white">No events found</h3>
                        <p class="text-gray-500 dark:text-gray-400">Try adjusting your search criteria or check back later for new events</p>
                    </div>

                    <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
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
import EventCard from '@/components/EventCard.vue';
import Pagination from '@/components/Pagination.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { CalendarIcon } from '@heroicons/vue/24/outline';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    events: Object,
    institutions: Array,
    eventTypes: Array,
    userRegistrations: Array,
    filters: Object,
});

const searchForm = reactive({
    search: props.filters.search || '',
    type: props.filters.type || '',
    institution_id: props.filters.institution_id || '',
    location: props.filters.location || '',
    date_from: props.filters.date_from || '',
});

const formatEventType = (type) => {
    return type.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};

const applyFilters = () => {
    router.get(route('events.index'), searchForm, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    Object.keys(searchForm).forEach((key) => {
        searchForm[key] = '';
    });
    applyFilters();
};

const handleEventRegistration = (eventId) => {
    router.post(
        route('api.events.register', eventId),
        {},
        {
            preserveState: true,
            onSuccess: () => {
                // Add to user registrations
                if (!props.userRegistrations.includes(eventId)) {
                    props.userRegistrations.push(eventId);
                }
            },
        },
    );
};

const handleEventUnregistration = (eventId) => {
    router.delete(route('api.events.unregister', eventId), {
        preserveState: true,
        onSuccess: () => {
            // Remove from user registrations
            const index = props.userRegistrations.indexOf(eventId);
            if (index > -1) {
                props.userRegistrations.splice(index, 1);
            }
        },
    });
};
</script>
