<template>
    <AppLayout title="Reunions">
        <Head title="Reunions" />

        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Alumni Reunions</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Reconnect with classmates and celebrate your shared memories</p>
            </div>

            <!-- Featured Reunion -->
            <div v-if="featuredReunion" class="mb-8">
                <h2 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">Featured Reunion</h2>
                <div class="rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 p-8 text-white">
                    <div class="grid grid-cols-1 items-center gap-8 lg:grid-cols-2">
                        <div>
                            <h3 class="mb-2 text-2xl font-bold">{{ featuredReunion.title }}</h3>
                            <p class="mb-4 text-blue-100">{{ featuredReunion.description }}</p>
                            <div class="mb-6 space-y-2">
                                <div class="flex items-center space-x-2">
                                    <CalendarIcon class="h-5 w-5" />
                                    <span>{{ formatDate(featuredReunion.start_date) }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <MapPinIcon class="h-5 w-5" />
                                    <span>{{ featuredReunion.location }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <UsersIcon class="h-5 w-5" />
                                    <span>{{ featuredReunion.attendee_count }} attending</span>
                                </div>
                            </div>
                            <Link
                                :href="route('reunions.show', featuredReunion.id)"
                                class="rounded-md bg-white px-6 py-3 font-medium text-blue-600 transition-colors hover:bg-gray-100"
                            >
                                Learn More
                            </Link>
                        </div>
                        <div class="text-center">
                            <div class="mb-2 text-4xl font-bold">{{ getDaysUntil(featuredReunion.start_date) }}</div>
                            <div class="text-blue-100">Days Until Reunion</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="mb-6 rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-6">
                    <form @submit.prevent="applyFilters" class="space-y-4">
                        <!-- Search Bar -->
                        <div>
                            <label for="search" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Search Reunions </label>
                            <input
                                id="search"
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Search by class year, department, or event name..."
                                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Filter Row -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <!-- Reunion Type -->
                            <div>
                                <label for="type" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Type </label>
                                <select
                                    id="type"
                                    v-model="searchForm.type"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
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
                                <label for="graduation_year" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Class Year
                                </label>
                                <select
                                    id="graduation_year"
                                    v-model="searchForm.graduation_year"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Years</option>
                                    <option v-for="year in graduationYears" :key="year" :value="year">Class of {{ year }}</option>
                                </select>
                            </div>

                            <!-- Location -->
                            <div>
                                <label for="location" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Location </label>
                                <input
                                    id="location"
                                    v-model="searchForm.location"
                                    type="text"
                                    placeholder="City, State"
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
                        <div class="flex items-center justify-between">
                            <div class="flex space-x-3">
                                <button
                                    type="submit"
                                    class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700"
                                >
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

                            <Link
                                :href="route('reunions.create')"
                                class="rounded-md bg-green-600 px-4 py-2 font-medium text-white transition-colors hover:bg-green-700"
                            >
                                Organize Reunion
                            </Link>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Reunions Grid -->
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ reunions.total }} Reunions Found</h2>
                </div>

                <div class="p-6">
                    <div v-if="reunions.data.length === 0" class="py-12 text-center">
                        <UsersIcon class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                        <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-white">No reunions found</h3>
                        <p class="mb-4 text-gray-500 dark:text-gray-400">Try adjusting your search criteria or be the first to organize a reunion!</p>
                        <Link
                            :href="route('reunions.create')"
                            class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700"
                        >
                            Organize a Reunion
                        </Link>
                    </div>

                    <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
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
import Pagination from '@/components/Pagination.vue';
import ReunionCard from '@/components/ReunionCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { CalendarIcon, MapPinIcon, UsersIcon } from '@heroicons/vue/24/outline';
import { Head, Link, router } from '@inertiajs/vue3';
import { differenceInDays, format } from 'date-fns';
import { reactive } from 'vue';

const props = defineProps({
    reunions: Object,
    featuredReunion: Object,
    graduationYears: Array,
    filters: Object,
});

const searchForm = reactive({
    search: props.filters.search || '',
    type: props.filters.type || '',
    graduation_year: props.filters.graduation_year || '',
    location: props.filters.location || '',
    date_from: props.filters.date_from || '',
});

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMMM dd, yyyy');
};

const getDaysUntil = (dateString) => {
    const targetDate = new Date(dateString);
    const today = new Date();
    const days = differenceInDays(targetDate, today);
    return days > 0 ? days : 0;
};

const applyFilters = () => {
    router.get(route('reunions.index'), searchForm, {
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

const handleRsvpUpdate = (reunionId, status) => {
    // Update local state or reload data
    router.reload({ only: ['reunions'] });
};

const handleFavoriteToggle = (reunionId, isFavorited) => {
    // Update local state or reload data
    router.reload({ only: ['reunions'] });
};
</script>
