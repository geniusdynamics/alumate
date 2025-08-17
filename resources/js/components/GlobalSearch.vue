<template>
    <div class="global-search relative">
        <!-- Search Input -->
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
            </div>
            <input
                ref="searchInput"
                v-model="searchQuery"
                type="text"
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                :placeholder="placeholder"
                @input="handleInput"
                @focus="showResults = true"
                @keydown.escape="closeResults"
                @keydown.arrow-down="navigateResults(1)"
                @keydown.arrow-up="navigateResults(-1)"
                @keydown.enter="selectResult"
            />
            <div v-if="isSearching" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
            </div>
        </div>

        <!-- Search Results Dropdown -->
        <div
            v-if="showResults && (searchResults.length > 0 || recentSearches.length > 0 || searchQuery.length > 0)"
            class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-600 max-h-96 overflow-y-auto"
        >
            <!-- Recent Searches (when no query) -->
            <div v-if="!searchQuery && recentSearches.length > 0" class="p-2">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 px-2 py-1">Recent Searches</div>
                <button
                    v-for="(search, index) in recentSearches"
                    :key="`recent-${index}`"
                    @click="executeSearch(search.query, search.type)"
                    class="w-full text-left px-2 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded flex items-center space-x-2"
                >
                    <ClockIcon class="h-4 w-4 text-gray-400" />
                    <span>{{ search.query }}</span>
                    <span class="text-xs text-gray-400 ml-auto">{{ search.type }}</span>
                </button>
            </div>

            <!-- Search Suggestions -->
            <div v-if="searchQuery && searchResults.length === 0 && !isSearching" class="p-2">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 px-2 py-1">Search in</div>
                <button
                    v-for="(category, index) in searchCategories"
                    :key="`category-${index}`"
                    :class="[
                        'w-full text-left px-2 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded flex items-center space-x-2',
                        selectedIndex === index ? 'bg-blue-50 dark:bg-blue-900/20' : ''
                    ]"
                    @click="executeSearch(searchQuery, category.type)"
                >
                    <component :is="category.icon" class="h-4 w-4 text-gray-400" />
                    <span>{{ searchQuery }} in {{ category.name }}</span>
                </button>
            </div>

            <!-- Search Results -->
            <div v-if="searchResults.length > 0" class="p-2">
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 px-2 py-1">Results</div>
                <div
                    v-for="(result, index) in searchResults"
                    :key="`result-${result.type}-${result.id}`"
                    :class="[
                        'px-2 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer',
                        selectedIndex === index + searchCategories.length ? 'bg-blue-50 dark:bg-blue-900/20' : ''
                    ]"
                    @click="navigateToResult(result)"
                >
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <component :is="getResultIcon(result.type)" class="h-5 w-5 text-gray-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-gray-900 dark:text-white font-medium truncate">
                                {{ result.title }}
                            </div>
                            <div class="text-gray-500 dark:text-gray-400 text-xs truncate">
                                {{ result.subtitle }}
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                {{ result.type }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Results -->
            <div v-if="searchQuery && searchResults.length === 0 && !isSearching" class="p-4 text-center">
                <div class="text-gray-500 dark:text-gray-400 text-sm">
                    No results found for "{{ searchQuery }}"
                </div>
                <div class="text-gray-400 dark:text-gray-500 text-xs mt-1">
                    Try searching in specific categories above
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isSearching" class="p-4 text-center">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                <div class="text-gray-500 dark:text-gray-400 text-sm mt-2">Searching...</div>
            </div>
        </div>

        <!-- Backdrop -->
        <div
            v-if="showResults"
            class="fixed inset-0 z-40"
            @click="closeResults"
        ></div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    MagnifyingGlassIcon,
    ClockIcon,
    UsersIcon,
    BriefcaseIcon,
    CalendarIcon,
    StarIcon,
    HeartIcon,
    AcademicCapIcon as GraduationCapIcon,
    TrophyIcon,
    AcademicCapIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    placeholder: {
        type: String,
        default: 'Search alumni, jobs, events, and more...'
    }
})

const searchInput = ref(null)
const searchQuery = ref('')
const searchResults = ref([])
const recentSearches = ref([])
const showResults = ref(false)
const isSearching = ref(false)
const selectedIndex = ref(-1)
let searchTimeout = null

const searchCategories = [
    { name: 'Alumni', type: 'alumni', icon: UsersIcon },
    { name: 'Jobs', type: 'jobs', icon: BriefcaseIcon },
    { name: 'Events', type: 'events', icon: CalendarIcon },
    { name: 'Success Stories', type: 'stories', icon: StarIcon },
    { name: 'Fundraising', type: 'campaigns', icon: HeartIcon },
    { name: 'Scholarships', type: 'scholarships', icon: GraduationCapIcon },
    { name: 'Achievements', type: 'achievements', icon: TrophyIcon }
]

const totalItems = computed(() => {
    if (searchQuery.value && searchResults.value.length === 0) {
        return searchCategories.length
    }
    return searchResults.value.length
})

onMounted(() => {
    loadRecentSearches()
    document.addEventListener('keydown', handleGlobalKeydown)
})

onUnmounted(() => {
    document.removeEventListener('keydown', handleGlobalKeydown)
})

const handleGlobalKeydown = (event) => {
    // Global search shortcut (Ctrl/Cmd + K)
    if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        event.preventDefault()
        focusSearch()
    }
}

const focusSearch = () => {
    nextTick(() => {
        searchInput.value?.focus()
    })
}

const handleInput = () => {
    selectedIndex.value = -1
    
    if (searchTimeout) {
        clearTimeout(searchTimeout)
    }
    
    if (searchQuery.value.length >= 2) {
        searchTimeout = setTimeout(() => {
            performSearch()
        }, 300)
    } else {
        searchResults.value = []
        isSearching.value = false
    }
}

const performSearch = async () => {
    if (searchQuery.value.length < 2) return
    
    isSearching.value = true
    
    try {
        const response = await fetch(`/api/search/global?q=${encodeURIComponent(searchQuery.value)}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        
        const data = await response.json()
        
        if (data.success) {
            searchResults.value = data.results
        }
    } catch (error) {
        console.error('Search error:', error)
        searchResults.value = []
    } finally {
        isSearching.value = false
    }
}

const executeSearch = (query, type) => {
    addToRecentSearches(query, type)
    closeResults()
    
    const routes = {
        alumni: '/alumni/directory',
        jobs: '/jobs',
        events: '/events',
        stories: '/stories',
        campaigns: '/campaigns',
        scholarships: '/scholarships',
        achievements: '/achievements'
    }
    
    const route = routes[type] || '/search'
    router.visit(route, {
        data: { search: query },
        preserveState: true
    })
}

const navigateToResult = (result) => {
    addToRecentSearches(searchQuery.value, result.type)
    closeResults()
    
    if (result.url) {
        router.visit(result.url)
    }
}

const navigateResults = (direction) => {
    const maxIndex = totalItems.value - 1
    
    if (direction === 1) {
        selectedIndex.value = selectedIndex.value < maxIndex ? selectedIndex.value + 1 : 0
    } else {
        selectedIndex.value = selectedIndex.value > 0 ? selectedIndex.value - 1 : maxIndex
    }
}

const selectResult = () => {
    if (selectedIndex.value >= 0) {
        if (searchResults.value.length === 0) {
            // Select from categories
            const category = searchCategories[selectedIndex.value]
            if (category) {
                executeSearch(searchQuery.value, category.type)
            }
        } else {
            // Select from results
            const result = searchResults.value[selectedIndex.value - searchCategories.length]
            if (result) {
                navigateToResult(result)
            }
        }
    } else if (searchQuery.value) {
        // Default to alumni search
        executeSearch(searchQuery.value, 'alumni')
    }
}

const closeResults = () => {
    showResults.value = false
    selectedIndex.value = -1
}

const getResultIcon = (type) => {
    const icons = {
        alumni: UsersIcon,
        job: BriefcaseIcon,
        event: CalendarIcon,
        story: StarIcon,
        campaign: HeartIcon,
        scholarship: GraduationCapIcon,
        achievement: TrophyIcon
    }
    return icons[type] || AcademicCapIcon
}

const loadRecentSearches = () => {
    const stored = localStorage.getItem('recent_searches')
    if (stored) {
        recentSearches.value = JSON.parse(stored).slice(0, 5)
    }
}

const addToRecentSearches = (query, type) => {
    const search = { query, type, timestamp: Date.now() }
    
    // Remove existing entry if it exists
    recentSearches.value = recentSearches.value.filter(s => 
        !(s.query === query && s.type === type)
    )
    
    // Add to beginning
    recentSearches.value.unshift(search)
    
    // Keep only last 5
    recentSearches.value = recentSearches.value.slice(0, 5)
    
    // Save to localStorage
    localStorage.setItem('recent_searches', JSON.stringify(recentSearches.value))
}

// Expose methods for parent components
defineExpose({
    focus: focusSearch,
    clear: () => {
        searchQuery.value = ''
        searchResults.value = []
        closeResults()
    }
})
</script>

<style scoped>
.global-search {
    @apply relative;
}
</style>
