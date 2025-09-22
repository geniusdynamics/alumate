<template>
    <div class="forum-search">
        <div class="flex items-center space-x-4">
            <!-- Search Input -->
            <div class="relative flex-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search topics, posts, and discussions..."
                    class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-10 pr-3 leading-5 placeholder-gray-500 focus:border-blue-500 focus:placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    @keyup.enter="performSearch"
                />
            </div>

            <!-- Filters -->
            <div class="flex items-center space-x-2">
                <!-- Forum Filter -->
                <select
                    v-model="selectedForum"
                    class="block w-40 rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                    <option value="">All Forums</option>
                    <option v-for="forum in availableForums" :key="forum.id" :value="forum.id">
                        {{ forum.name }}
                    </option>
                </select>

                <!-- Sort Filter -->
                <select
                    v-model="sortBy"
                    class="block w-32 rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                    <option value="relevance">Relevance</option>
                    <option value="newest">Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="popular">Popular</option>
                    <option value="activity">Activity</option>
                </select>

                <!-- Search Button -->
                <button
                    @click="performSearch"
                    :disabled="!searchQuery.trim()"
                    class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Search
                </button>
            </div>
        </div>

        <!-- Advanced Filters (Collapsible) -->
        <div v-if="showAdvanced" class="mt-4 rounded-lg bg-gray-50 p-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <!-- Tag Filter -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Tag</label>
                    <input
                        v-model="selectedTag"
                        type="text"
                        placeholder="Enter tag name"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                </div>

                <!-- Author Filter -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Author</label>
                    <input
                        v-model="selectedAuthor"
                        type="text"
                        placeholder="Enter author name"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                </div>

                <!-- Date Range -->
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Date Range</label>
                    <select
                        v-model="dateRange"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">Any time</option>
                        <option value="today">Today</option>
                        <option value="week">This week</option>
                        <option value="month">This month</option>
                        <option value="year">This year</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex justify-between">
                <button @click="clearFilters" class="text-sm text-gray-600 hover:text-gray-800">Clear all filters</button>
                <button @click="showAdvanced = false" class="text-sm text-blue-600 hover:text-blue-800">Hide advanced filters</button>
            </div>
        </div>

        <!-- Toggle Advanced Filters -->
        <div v-if="!showAdvanced" class="mt-2">
            <button @click="showAdvanced = true" class="text-sm text-blue-600 hover:text-blue-800">Show advanced filters</button>
        </div>

        <!-- Search Suggestions -->
        <div v-if="suggestions.length > 0" class="mt-4">
            <h4 class="mb-2 text-sm font-medium text-gray-700">Popular searches:</h4>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="suggestion in suggestions"
                    :key="suggestion"
                    @click="
                        searchQuery = suggestion;
                        performSearch();
                    "
                    class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-800 hover:bg-gray-200"
                >
                    {{ suggestion }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { onMounted, ref } from 'vue';

// Emits
const emit = defineEmits(['search']);

// Reactive data
const searchQuery = ref('');
const selectedForum = ref('');
const selectedTag = ref('');
const selectedAuthor = ref('');
const sortBy = ref('relevance');
const dateRange = ref('');
const showAdvanced = ref(false);
const availableForums = ref([]);
const suggestions = ref(['career advice', 'networking', 'job opportunities', 'mentorship', 'industry trends', 'alumni events']);

// Methods
const performSearch = () => {
    if (!searchQuery.value.trim()) return;

    const searchData = {
        query: searchQuery.value.trim(),
        forum_id: selectedForum.value || undefined,
        tag: selectedTag.value || undefined,
        author: selectedAuthor.value || undefined,
        sort: sortBy.value,
        date_range: dateRange.value || undefined,
    };

    emit('search', searchData);
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedForum.value = '';
    selectedTag.value = '';
    selectedAuthor.value = '';
    sortBy.value = 'relevance';
    dateRange.value = '';
};

const loadAvailableForums = async () => {
    try {
        const response = await fetch('/api/forums');
        const data = await response.json();

        if (data.success) {
            availableForums.value = data.data;
        }
    } catch (error) {
        console.error('Error loading forums:', error);
    }
};

// Lifecycle
onMounted(() => {
    loadAvailableForums();
});
</script>
