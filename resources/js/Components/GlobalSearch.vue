<template>
    <div class="global-search relative">
        <!-- Search Input -->
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
            </div>
            <input
                ref="searchInput"
                v-model="searchQuery"
                type="text"
                class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-10 pr-3 leading-5 text-gray-900 placeholder-gray-500 focus:border-blue-500 focus:placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                :placeholder="placeholder"
                @input="handleInput"
                @focus="showResults = true"
                @keydown.escape="closeResults"
                @keydown.arrow-down="navigateResults(1)"
                @keydown.arrow-up="navigateResults(-1)"
                @keydown.enter="selectResult"
            />
            <div v-if="isSearching" class="absolute inset-y-0 right-0 flex items-center pr-3">
                <div class="h-4 w-4 animate-spin rounded-full border-b-2 border-blue-600"></div>
            </div>
        </div>

        <!-- Search Results Dropdown -->
        <div
            v-if="showResults && (searchResults.length > 0 || recentSearches.length > 0 || searchQuery.length > 0)"
            class="absolute z-50 mt-1 max-h-96 w-full overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800"
        >
            <!-- Recent Searches (when no query) -->
            <div v-if="!searchQuery && recentSearches.length > 0" class="p-2">
                <div class="px-2 py-1 text-xs font-medium text-gray-500 dark:text-gray-400">Recent Searches</div>
                <button
                    v-for="(search, index) in recentSearches"
                    :key="`recent-${index}`"
                    @click="executeSearch(search.query, search.type)"
                    class="flex w-full items-center space-x-2 rounded px-2 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <ClockIcon class="h-4 w-4 text-gray-400" />
                    <span>{{ search.query }}</span>
                    <span class="ml-auto text-xs text-gray-400">{{ search.type }}</span>
                </button>
            </div>

            <!-- Search Suggestions -->
            <div v-if="searchQuery && searchResults.length === 0 && !isSearching" class="p-2">
                <div class="px-2 py-1 text-xs font-medium text-gray-500 dark:text-gray-400">Search in</div>
                <button
                    v-for="(category, index) in searchCategories"
                    :key="`category-${index}`"
                    :class="[
                        'flex w-full items-center space-x-2 rounded px-2 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                        selectedIndex === index ? 'bg-blue-50 dark:bg-blue-900/20' : '',
                    ]"
                    @click="executeSearch(searchQuery, category.type)"
                >
                    <component :is="category.icon" class="h-4 w-4 text-gray-400" />
                    <span>{{ searchQuery }} in {{ category.name }}</span>
                </button>
            </div>

            <!-- Search Results -->
            <div v-if="searchResults.length > 0" class="p-2">
                <div class="px-2 py-1 text-xs font-medium text-gray-500 dark:text-gray-400">Results</div>
                <div
                    v-for="(result, index) in searchResults"
                    :key="`result-${result.type}-${result.id}`"
                    :class="[
                        'cursor-pointer rounded px-2 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700',
                        selectedIndex === index + searchCategories.length ? 'bg-blue-50 dark:bg-blue-900/20' : '',
                    ]"
                    @click="navigateToResult(result)"
                >
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <component :is="getResultIcon(result.type)" class="h-5 w-5 text-gray-400" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-medium text-gray-900 dark:text-white">
                                {{ result.title }}
                            </div>
                            <div class="truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ result.subtitle }}
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <span
                                class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                            >
                                {{ result.type }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Results -->
            <div v-if="searchQuery && searchResults.length === 0 && !isSearching" class="p-4 text-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">No results found for "{{ searchQuery }}"</div>
                <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">Try searching in specific categories above</div>
            </div>

            <!-- Loading State -->
            <div v-if="isSearching" class="p-4 text-center">
                <div class="mx-auto h-6 w-6 animate-spin rounded-full border-b-2 border-blue-600"></div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Searching...</div>
            </div>
        </div>

        <!-- Backdrop -->
        <div v-if="showResults" class="fixed inset-0 z-40" @click="closeResults"></div>
    </div>
</template>

<script setup>
import {
    AcademicCapIcon,
    BriefcaseIcon,
    CalendarIcon,
    ClockIcon,
    AcademicCapIcon as GraduationCapIcon,
    HeartIcon,
    MagnifyingGlassIcon,
    StarIcon,
    TrophyIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    placeholder: {
        type: String,
        default: 'Search alumni, jobs, events, and more...',
    },
});

const searchInput = ref(null);
const searchQuery = ref('');
const searchResults = ref([]);
const recentSearches = ref([]);
const showResults = ref(false);
const isSearching = ref(false);
const selectedIndex = ref(-1);
let searchTimeout = null;

const searchCategories = [
    { name: 'Alumni', type: 'alumni', icon: UsersIcon },
    { name: 'Jobs', type: 'jobs', icon: BriefcaseIcon },
    { name: 'Events', type: 'events', icon: CalendarIcon },
    { name: 'Success Stories', type: 'stories', icon: StarIcon },
    { name: 'Fundraising', type: 'campaigns', icon: HeartIcon },
    { name: 'Scholarships', type: 'scholarships', icon: GraduationCapIcon },
    { name: 'Achievements', type: 'achievements', icon: TrophyIcon },
];

const totalItems = computed(() => {
    if (searchQuery.value && searchResults.value.length === 0) {
        return searchCategories.length;
    }
    return searchResults.value.length;
});

onMounted(() => {
    loadRecentSearches();
    document.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleGlobalKeydown);
});

const handleGlobalKeydown = (event) => {
    // Global search shortcut (Ctrl/Cmd + K)
    if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        event.preventDefault();
        focusSearch();
    }
};

const focusSearch = () => {
    nextTick(() => {
        searchInput.value?.focus();
    });
};

const handleInput = () => {
    selectedIndex.value = -1;

    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    if (searchQuery.value.length >= 2) {
        searchTimeout = setTimeout(() => {
            performSearch();
        }, 300);
    } else {
        searchResults.value = [];
        isSearching.value = false;
    }
};

const performSearch = async () => {
    if (searchQuery.value.length < 2) return;

    isSearching.value = true;

    try {
        const response = await fetch(`/api/search/global?q=${encodeURIComponent(searchQuery.value)}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const data = await response.json();

        if (data.success) {
            searchResults.value = data.results;
        }
    } catch (error) {
        console.error('Search error:', error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

const executeSearch = (query, type) => {
    addToRecentSearches(query, type);
    closeResults();

    const routes = {
        alumni: '/alumni/directory',
        jobs: '/jobs',
        events: '/events',
        stories: '/stories',
        campaigns: '/campaigns',
        scholarships: '/scholarships',
        achievements: '/achievements',
    };

    const route = routes[type] || '/search';
    router.visit(route, {
        data: { search: query },
        preserveState: true,
    });
};

const navigateToResult = (result) => {
    addToRecentSearches(searchQuery.value, result.type);
    closeResults();

    if (result.url) {
        router.visit(result.url);
    }
};

const navigateResults = (direction) => {
    const maxIndex = totalItems.value - 1;

    if (direction === 1) {
        selectedIndex.value = selectedIndex.value < maxIndex ? selectedIndex.value + 1 : 0;
    } else {
        selectedIndex.value = selectedIndex.value > 0 ? selectedIndex.value - 1 : maxIndex;
    }
};

const selectResult = () => {
    if (selectedIndex.value >= 0) {
        if (searchResults.value.length === 0) {
            // Select from categories
            const category = searchCategories[selectedIndex.value];
            if (category) {
                executeSearch(searchQuery.value, category.type);
            }
        } else {
            // Select from results
            const result = searchResults.value[selectedIndex.value - searchCategories.length];
            if (result) {
                navigateToResult(result);
            }
        }
    } else if (searchQuery.value) {
        // Default to alumni search
        executeSearch(searchQuery.value, 'alumni');
    }
};

const closeResults = () => {
    showResults.value = false;
    selectedIndex.value = -1;
};

const getResultIcon = (type) => {
    const icons = {
        alumni: UsersIcon,
        job: BriefcaseIcon,
        event: CalendarIcon,
        story: StarIcon,
        campaign: HeartIcon,
        scholarship: GraduationCapIcon,
        achievement: TrophyIcon,
    };
    return icons[type] || AcademicCapIcon;
};

const loadRecentSearches = () => {
    const stored = localStorage.getItem('recent_searches');
    if (stored) {
        recentSearches.value = JSON.parse(stored).slice(0, 5);
    }
};

const addToRecentSearches = (query, type) => {
    const search = { query, type, timestamp: Date.now() };

    // Remove existing entry if it exists
    recentSearches.value = recentSearches.value.filter((s) => !(s.query === query && s.type === type));

    // Add to beginning
    recentSearches.value.unshift(search);

    // Keep only last 5
    recentSearches.value = recentSearches.value.slice(0, 5);

    // Save to localStorage
    localStorage.setItem('recent_searches', JSON.stringify(recentSearches.value));
};

// Expose methods for parent Components
defineExpose({
    focus: focusSearch,
    clear: () => {
        searchQuery.value = '';
        searchResults.value = [];
        closeResults();
    },
});
</script>

<style scoped>
.global-search {
    @apply relative;
}
</style>
