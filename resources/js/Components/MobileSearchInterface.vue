<template>
    <div class="mobile-search-interface">
        <!-- Mobile Search Bar -->
        <div class="search-mobile">
            <input
                ref="searchInput"
                v-model="searchQuery"
                type="text"
                :placeholder="placeholder"
                class="input-mobile pl-12 pr-10"
                @input="handleSearchInput"
                @focus="handleSearchFocus"
                @blur="handleSearchBlur"
                @keydown.enter="executeSearch"
                @keydown.escape="clearSearch"
            />
            <MagnifyingGlassIcon class="search-icon" />
            <button
                v-if="searchQuery"
                @click="clearSearch"
                class="absolute right-3 top-1/2 -translate-y-1/2 transform p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
                <XMarkIcon class="h-5 w-5" />
            </button>
        </div>

        <!-- Search Suggestions -->
        <div
            v-if="showSuggestions && (suggestions.length > 0 || recentSearches.length > 0)"
            class="absolute left-0 right-0 top-full z-50 max-h-80 overflow-y-auto rounded-b-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
            <!-- Recent Searches -->
            <div v-if="recentSearches.length > 0 && !searchQuery" class="p-4">
                <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Recent Searches</h3>
                <div class="space-y-2">
                    <button
                        v-for="search in recentSearches"
                        :key="search.id"
                        @click="selectRecentSearch(search)"
                        class="flex w-full items-center rounded-lg p-2 text-left transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <ClockIcon class="mr-3 h-4 w-4 flex-shrink-0 text-gray-400" />
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm text-gray-900 dark:text-white">{{ search.query }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ search.category }}</div>
                        </div>
                        <button @click.stop="removeRecentSearch(search.id)" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <XMarkIcon class="h-3 w-3" />
                        </button>
                    </button>
                </div>
            </div>

            <!-- Search Suggestions -->
            <div v-if="suggestions.length > 0" class="p-4">
                <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Suggestions</h3>
                <div class="space-y-1">
                    <button
                        v-for="(suggestion, index) in suggestions"
                        :key="suggestion.id"
                        @click="selectSuggestion(suggestion)"
                        class="flex w-full items-center rounded-lg p-3 text-left transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
                        :class="{ 'bg-blue-50 dark:bg-blue-900/20': index === selectedSuggestionIndex }"
                    >
                        <component :is="getSuggestionIcon(suggestion.type)" class="mr-3 h-5 w-5 flex-shrink-0 text-gray-400" />
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                {{ suggestion.title }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ suggestion.subtitle }}
                            </div>
                        </div>
                        <div class="text-xs font-medium text-blue-600 dark:text-blue-400">
                            {{ suggestion.type }}
                        </div>
                    </button>
                </div>
            </div>

            <!-- No Results -->
            <div v-if="searchQuery && suggestions.length === 0 && !isLoading" class="p-4 text-center">
                <MagnifyingGlassIcon class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                <p class="text-sm text-gray-500 dark:text-gray-400">No results found for "{{ searchQuery }}"</p>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="p-4 text-center">
                <div class="mx-auto h-6 w-6 animate-spin rounded-full border-2 border-blue-600 border-t-transparent"></div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Searching...</p>
            </div>
        </div>

        <!-- Search Filters (Mobile Optimized) -->
        <div v-if="showFilters" class="filter-mobile">
            <button @click="toggleFilters" class="filter-mobile-toggle">
                <span>Filters</span>
                <ChevronDownIcon class="h-4 w-4" :class="{ 'rotate-180': filtersExpanded }" />
            </button>

            <div v-if="filtersExpanded" class="filter-mobile-panel">
                <div class="space-y-4 p-4">
                    <!-- Category Filter -->
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Category </label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="category in categories"
                                :key="category.value"
                                @click="toggleCategory(category.value)"
                                class="flex items-center justify-center rounded-lg border p-2 text-sm transition-colors"
                                :class="
                                    selectedCategories.includes(category.value)
                                        ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300'
                                        : 'border-gray-200 bg-white text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'
                                "
                            >
                                <component :is="category.icon" class="mr-2 h-4 w-4" />
                                {{ category.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Location Filter -->
                    <div v-if="showLocationFilter">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Location </label>
                        <input v-model="locationFilter" type="text" placeholder="Enter city or region" class="input-mobile" />
                    </div>

                    <!-- Date Range Filter -->
                    <div v-if="showDateFilter">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"> Date Range </label>
                        <div class="grid grid-cols-2 gap-2">
                            <input v-model="dateFrom" type="date" class="input-mobile text-sm" />
                            <input v-model="dateTo" type="date" class="input-mobile text-sm" />
                        </div>
                    </div>

                    <!-- Filter Actions -->
                    <div class="flex space-x-2 pt-2">
                        <button @click="applyFilters" class="btn-mobile-primary flex-1">Apply Filters</button>
                        <button @click="clearFilters" class="btn-mobile-secondary px-4">Clear</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useDebouncedRef } from '@/Composables/useDebounce';
import {
    AcademicCapIcon,
    BriefcaseIcon,
    BuildingOfficeIcon,
    CalendarIcon,
    ChevronDownIcon,
    ClockIcon,
    MagnifyingGlassIcon,
    UsersIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    placeholder: {
        type: String,
        default: 'Search...',
    },
    showFilters: {
        type: Boolean,
        default: false,
    },
    showLocationFilter: {
        type: Boolean,
        default: false,
    },
    showDateFilter: {
        type: Boolean,
        default: false,
    },
    categories: {
        type: Array,
        default: () => [
            { value: 'alumni', label: 'Alumni', icon: UsersIcon },
            { value: 'jobs', label: 'Jobs', icon: BriefcaseIcon },
            { value: 'events', label: 'Events', icon: CalendarIcon },
            { value: 'companies', label: 'Companies', icon: BuildingOfficeIcon },
        ],
    },
    autoFocus: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['search', 'suggestion-selected', 'filters-changed', 'focus', 'blur']);

const searchInput = ref(null);
const searchQuery = ref('');
const debouncedSearchQuery = useDebouncedRef(searchQuery, 300);
const showSuggestions = ref(false);
const suggestions = ref([]);
const recentSearches = ref([]);
const isLoading = ref(false);
const selectedSuggestionIndex = ref(-1);

// Filter states
const filtersExpanded = ref(false);
const selectedCategories = ref([]);
const locationFilter = ref('');
const dateFrom = ref('');
const dateTo = ref('');

const categories = computed(() => props.categories);

// Watch for search query changes
watch(debouncedSearchQuery, async (newQuery) => {
    if (newQuery.trim()) {
        await fetchSuggestions(newQuery);
    } else {
        suggestions.value = [];
    }
});

onMounted(() => {
    loadRecentSearches();
    if (props.autoFocus) {
        nextTick(() => {
            searchInput.value?.focus();
        });
    }

    // Add keyboard navigation
    document.addEventListener('keydown', handleKeyNavigation);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyNavigation);
});

const handleSearchInput = (event) => {
    searchQuery.value = event.target.value;
};

const handleSearchFocus = () => {
    showSuggestions.value = true;
    emit('focus');
};

const handleSearchBlur = () => {
    // Delay hiding suggestions to allow for clicks
    setTimeout(() => {
        showSuggestions.value = false;
    }, 200);
    emit('blur');
};

const executeSearch = () => {
    if (!searchQuery.value.trim()) return;

    const searchData = {
        query: searchQuery.value,
        categories: selectedCategories.value,
        location: locationFilter.value,
        dateFrom: dateFrom.value,
        dateTo: dateTo.value,
    };

    addToRecentSearches(searchQuery.value, selectedCategories.value[0] || 'all');
    showSuggestions.value = false;
    emit('search', searchData);
};

const clearSearch = () => {
    searchQuery.value = '';
    suggestions.value = [];
    showSuggestions.value = false;
    searchInput.value?.focus();
};

const fetchSuggestions = async (query) => {
    if (!query.trim()) return;

    isLoading.value = true;

    try {
        const response = await fetch('/api/search/suggestions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                query,
                categories: selectedCategories.value,
                limit: 8,
            }),
        });

        const data = await response.json();
        suggestions.value = data.suggestions || [];
    } catch (error) {
        console.error('Failed to fetch suggestions:', error);
        suggestions.value = [];
    } finally {
        isLoading.value = false;
    }
};

const selectSuggestion = (suggestion) => {
    searchQuery.value = suggestion.title;
    showSuggestions.value = false;
    addToRecentSearches(suggestion.title, suggestion.type);
    emit('suggestion-selected', suggestion);
};

const selectRecentSearch = (search) => {
    searchQuery.value = search.query;
    selectedCategories.value = search.category !== 'all' ? [search.category] : [];
    showSuggestions.value = false;
    executeSearch();
};

const removeRecentSearch = (searchId) => {
    recentSearches.value = recentSearches.value.filter((s) => s.id !== searchId);
    saveRecentSearches();
};

const loadRecentSearches = () => {
    try {
        const stored = localStorage.getItem('mobile_search_recent');
        if (stored) {
            recentSearches.value = JSON.parse(stored).slice(0, 5);
        }
    } catch (error) {
        console.error('Failed to load recent searches:', error);
    }
};

const addToRecentSearches = (query, category) => {
    const search = {
        id: Date.now(),
        query,
        category,
        timestamp: Date.now(),
    };

    // Remove duplicates
    recentSearches.value = recentSearches.value.filter((s) => !(s.query === query && s.category === category));

    recentSearches.value.unshift(search);
    recentSearches.value = recentSearches.value.slice(0, 5);

    saveRecentSearches();
};

const saveRecentSearches = () => {
    try {
        localStorage.setItem('mobile_search_recent', JSON.stringify(recentSearches.value));
    } catch (error) {
        console.error('Failed to save recent searches:', error);
    }
};

const getSuggestionIcon = (type) => {
    const icons = {
        alumni: UsersIcon,
        jobs: BriefcaseIcon,
        events: CalendarIcon,
        companies: BuildingOfficeIcon,
        courses: AcademicCapIcon,
    };
    return icons[type] || MagnifyingGlassIcon;
};

const handleKeyNavigation = (event) => {
    if (!showSuggestions.value || suggestions.value.length === 0) return;

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            selectedSuggestionIndex.value = Math.min(selectedSuggestionIndex.value + 1, suggestions.value.length - 1);
            break;
        case 'ArrowUp':
            event.preventDefault();
            selectedSuggestionIndex.value = Math.max(selectedSuggestionIndex.value - 1, -1);
            break;
        case 'Enter':
            event.preventDefault();
            if (selectedSuggestionIndex.value >= 0) {
                selectSuggestion(suggestions.value[selectedSuggestionIndex.value]);
            } else {
                executeSearch();
            }
            break;
    }
};

// Filter methods
const toggleFilters = () => {
    filtersExpanded.value = !filtersExpanded.value;
};

const toggleCategory = (category) => {
    const index = selectedCategories.value.indexOf(category);
    if (index > -1) {
        selectedCategories.value.splice(index, 1);
    } else {
        selectedCategories.value.push(category);
    }
};

const applyFilters = () => {
    filtersExpanded.value = false;
    executeSearch();

    emit('filters-changed', {
        categories: selectedCategories.value,
        location: locationFilter.value,
        dateFrom: dateFrom.value,
        dateTo: dateTo.value,
    });
};

const clearFilters = () => {
    selectedCategories.value = [];
    locationFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    filtersExpanded.value = false;

    emit('filters-changed', {
        categories: [],
        location: '',
        dateFrom: '',
        dateTo: '',
    });
};

// Expose methods for parent Components
defineExpose({
    focus: () => searchInput.value?.focus(),
    blur: () => searchInput.value?.blur(),
    clear: clearSearch,
    setQuery: (query) => {
        searchQuery.value = query;
    },
});
</script>

<style scoped>
.mobile-search-interface {
    position: relative;
}

/* Smooth animations */
.filter-mobile-panel {
    animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Touch-friendly interactions */
@media (hover: hover) {
    .mobile-search-interface button:hover {
        transform: translateY(-1px);
    }
}

.mobile-search-interface button:active {
    transform: translateY(0);
}
</style>















