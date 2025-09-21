<template>
    <AppLayout title="Alumni Speaker Bureau">
        <Head title="Alumni Speaker Bureau" />

        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Alumni Speaker Bureau</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Connect with accomplished alumni speakers for your events, classes, and presentations
                </p>
            </div>

            <!-- Quick Stats -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-4">
                <div class="rounded-lg bg-white p-6 text-center shadow dark:bg-gray-800">
                    <div class="mb-2 text-3xl font-bold text-blue-600 dark:text-blue-400">
                        {{ speakers.total }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Available Speakers</div>
                </div>
                <div class="rounded-lg bg-white p-6 text-center shadow dark:bg-gray-800">
                    <div class="mb-2 text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ speakingTopics.length }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Speaking Topics</div>
                </div>
                <div class="rounded-lg bg-white p-6 text-center shadow dark:bg-gray-800">
                    <div class="mb-2 text-3xl font-bold text-purple-600 dark:text-purple-400">
                        {{ upcomingEvents.length }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Upcoming Events</div>
                </div>
                <div class="rounded-lg bg-white p-6 text-center shadow dark:bg-gray-800">
                    <div class="mb-2 text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ completedEvents }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Events Completed</div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mb-6 flex flex-wrap gap-4">
                <Link
                    :href="route('speaker-bureau.request')"
                    class="rounded-md bg-blue-600 px-6 py-3 font-medium text-white transition-colors hover:bg-blue-700"
                >
                    Request a Speaker
                </Link>
                <Link
                    :href="route('speaker-bureau.join')"
                    class="rounded-md bg-green-600 px-6 py-3 font-medium text-white transition-colors hover:bg-green-700"
                >
                    Become a Speaker
                </Link>
                <Link
                    :href="route('speaker-bureau.events')"
                    class="rounded-md bg-purple-600 px-6 py-3 font-medium text-white transition-colors hover:bg-purple-700"
                >
                    Browse Events
                </Link>
            </div>

            <!-- Search and Filters -->
            <div class="mb-6 rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="p-6">
                    <form @submit.prevent="searchSpeakers" class="space-y-4">
                        <!-- Search Bar -->
                        <div>
                            <label for="search" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Search Speakers </label>
                            <input
                                id="search"
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Search by name, expertise, company, or speaking topics..."
                                class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Filter Grid -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <!-- Speaking Topic -->
                            <div>
                                <label for="topic" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Speaking Topic </label>
                                <select
                                    id="topic"
                                    v-model="searchForm.topic"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Topics</option>
                                    <option v-for="topic in speakingTopics" :key="topic" :value="topic">
                                        {{ topic }}
                                    </option>
                                </select>
                            </div>

                            <!-- Industry -->
                            <div>
                                <label for="industry" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Industry </label>
                                <select
                                    id="industry"
                                    v-model="searchForm.industry"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Industries</option>
                                    <option v-for="industry in industries" :key="industry" :value="industry">
                                        {{ industry }}
                                    </option>
                                </select>
                            </div>

                            <!-- Event Type -->
                            <div>
                                <label for="event_type" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300"> Event Type </label>
                                <select
                                    id="event_type"
                                    v-model="searchForm.event_type"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">All Event Types</option>
                                    <option value="keynote">Keynote</option>
                                    <option value="panel">Panel Discussion</option>
                                    <option value="workshop">Workshop</option>
                                    <option value="classroom">Classroom Visit</option>
                                    <option value="career_fair">Career Fair</option>
                                    <option value="networking">Networking Event</option>
                                </select>
                            </div>

                            <!-- Availability -->
                            <div>
                                <label for="availability" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Availability
                                </label>
                                <select
                                    id="availability"
                                    v-model="searchForm.availability"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="">Any Time</option>
                                    <option value="immediate">Available Now</option>
                                    <option value="this_month">This Month</option>
                                    <option value="next_month">Next Month</option>
                                    <option value="flexible">Flexible</option>
                                </select>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-3">
                            <button type="submit" class="rounded-md bg-blue-600 px-6 py-2 font-medium text-white transition-colors hover:bg-blue-700">
                                Search Speakers
                            </button>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="rounded-md bg-gray-300 px-6 py-2 font-medium text-gray-700 transition-colors hover:bg-gray-400"
                            >
                                Clear Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Featured Speakers -->
            <div v-if="featuredSpeakers.length > 0" class="mb-8">
                <h2 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">Featured Speakers</h2>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <SpeakerCard
                        v-for="speaker in featuredSpeakers"
                        :key="speaker.id"
                        :speaker="speaker"
                        :featured="true"
                        @request-speaker="handleSpeakerRequest"
                        @view-profile="handleViewProfile"
                    />
                </div>
            </div>

            <!-- Speakers Grid -->
            <div class="rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ speakers.total }} Speakers Available</h2>
                        <div class="flex items-center space-x-2">
                            <label for="sort" class="text-sm text-gray-600 dark:text-gray-400">Sort by:</label>
                            <select
                                id="sort"
                                v-model="searchForm.sort"
                                @change="searchSpeakers"
                                class="rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="relevance">Relevance</option>
                                <option value="rating">Highest Rated</option>
                                <option value="experience">Most Experience</option>
                                <option value="recent">Recently Active</option>
                                <option value="alphabetical">Alphabetical</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div v-if="speakers.data.length === 0" class="py-12 text-center">
                        <MicrophoneIcon class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                        <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-white">No speakers found</h3>
                        <p class="mb-4 text-gray-500 dark:text-gray-400">Try adjusting your search criteria or browse all speakers</p>
                        <button
                            @click="clearFilters"
                            class="rounded-md bg-blue-600 px-4 py-2 font-medium text-white transition-colors hover:bg-blue-700"
                        >
                            View All Speakers
                        </button>
                    </div>

                    <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <SpeakerCard
                            v-for="speaker in speakers.data"
                            :key="speaker.id"
                            :speaker="speaker"
                            @request-speaker="handleSpeakerRequest"
                            @view-profile="handleViewProfile"
                        />
                    </div>

                    <!-- Pagination -->
                    <div v-if="speakers.last_page > 1" class="mt-8">
                        <Pagination :links="speakers.links" />
                    </div>
                </div>
            </div>

            <!-- Upcoming Speaking Events -->
            <div v-if="upcomingEvents.length > 0" class="mt-8 rounded-lg bg-white shadow dark:bg-gray-800">
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Upcoming Speaking Events</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <SpeakingEventCard v-for="event in upcomingEvents" :key="event.id" :event="event" @view-details="handleViewEventDetails" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import Pagination from '@/Components/Pagination.vue';
import SpeakerCard from '@/Components/SpeakerCard.vue';
import SpeakingEventCard from '@/Components/SpeakingEventCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { MicrophoneIcon } from '@heroicons/vue/24/outline';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    speakers: Object,
    featuredSpeakers: Array,
    upcomingEvents: Array,
    speakingTopics: Array,
    industries: Array,
    completedEvents: Number,
    filters: Object,
});

const searchForm = reactive({
    search: props.filters.search || '',
    topic: props.filters.topic || '',
    industry: props.filters.industry || '',
    event_type: props.filters.event_type || '',
    availability: props.filters.availability || '',
    sort: props.filters.sort || 'relevance',
});

const searchSpeakers = () => {
    router.get(route('speaker-bureau.index'), searchForm, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    Object.keys(searchForm).forEach((key) => {
        if (key !== 'sort') {
            searchForm[key] = '';
        }
    });
    searchForm.sort = 'relevance';
    searchSpeakers();
};

const handleSpeakerRequest = (speakerId) => {
    router.visit(route('speaker-bureau.request', { speaker: speakerId }));
};

const handleViewProfile = (speakerId) => {
    router.visit(route('speaker-bureau.speaker', speakerId));
};

const handleViewEventDetails = (eventId) => {
    router.visit(route('speaker-bureau.event', eventId));
};
</script>
