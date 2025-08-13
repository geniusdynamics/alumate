<template>
  <DefaultLayout>
    <div class="advanced-search-page">
      <!-- Page Header -->
      <div class="page-header">
        <div class="header-content">
          <h1 class="page-title">Advanced Search</h1>
          <p class="page-description">
            Search across alumni profiles, posts, jobs, and events with powerful filters and AI-powered suggestions.
          </p>
        </div>
        
        <div class="header-actions">
          <button
            @click="showSavedSearches = !showSavedSearches"
            class="saved-searches-toggle"
            :class="{ active: showSavedSearches }"
          >
            <BookmarkIcon class="w-5 h-5" />
            Saved Searches
          </button>
        </div>
      </div>

      <!-- Main Search Interface -->
      <div class="search-container">
        <div class="search-main">
          <AdvancedSearch
            :initial-query="initialQuery"
            :initial-filters="initialFilters"
            @search-performed="handleSearchPerformed"
            @result-selected="handleResultSelected"
          />
        </div>

        <!-- Saved Searches Sidebar -->
        <div
          v-if="showSavedSearches"
          class="saved-searches-sidebar"
        >
          <SavedSearches
            :current-query="currentQuery"
            :current-filters="currentFilters"
            @search-selected="handleSavedSearchSelected"
            @search-run="handleSavedSearchRun"
          />
        </div>
      </div>

      <!-- Search Analytics (for admins) -->
      <div v-if="canViewAnalytics" class="search-analytics">
        <SearchAnalytics />
      </div>
    </div>
  </DefaultLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import AdvancedSearch from '@/Components/AdvancedSearch.vue'
import SavedSearches from '@/Components/SavedSearches.vue'
import SearchAnalytics from '@/Components/SearchAnalytics.vue'
import { BookmarkIcon } from '@heroicons/vue/24/outline'

interface SearchResponse {
  hits: any[]
  total: number
  aggregations: Record<string, any>
  took: number
}

interface SavedSearch {
  id: number
  name: string
  query: string
  filters: Record<string, any>
}

const props = defineProps<{
  initialQuery?: string
  initialFilters?: Record<string, any>
}>()

// Page data
const page = usePage()

// Reactive state
const showSavedSearches = ref(false)
const currentQuery = ref(props.initialQuery || '')
const currentFilters = ref(props.initialFilters || {})
const searchResults = ref<SearchResponse | null>(null)

// Computed properties
const canViewAnalytics = computed(() => {
  // Check if user has admin role or analytics permission
  return page.props.auth?.user?.roles?.includes('admin') || false
})

// Methods
const handleSearchPerformed = (query: string, results: SearchResponse) => {
  currentQuery.value = query
  searchResults.value = results
  
  // Update URL with search parameters
  const url = new URL(window.location.href)
  url.searchParams.set('q', query)
  if (Object.keys(currentFilters.value).length > 0) {
    url.searchParams.set('filters', JSON.stringify(currentFilters.value))
  }
  window.history.replaceState({}, '', url.toString())
}

const handleResultSelected = (result: any) => {
  // Track result selection for analytics
  console.log('Result selected:', result)
}

const handleSavedSearchSelected = (savedSearch: SavedSearch) => {
  currentQuery.value = savedSearch.query
  currentFilters.value = savedSearch.filters
}

const handleSavedSearchRun = (query: string, filters: Record<string, any>) => {
  currentQuery.value = query
  currentFilters.value = filters
}

// Lifecycle
onMounted(() => {
  // Parse URL parameters
  const urlParams = new URLSearchParams(window.location.search)
  const queryParam = urlParams.get('q')
  const filtersParam = urlParams.get('filters')
  
  if (queryParam) {
    currentQuery.value = queryParam
  }
  
  if (filtersParam) {
    try {
      currentFilters.value = JSON.parse(filtersParam)
    } catch (e) {
      console.warn('Failed to parse filters from URL:', e)
    }
  }
})
</script>

<style scoped>
.advanced-search-page {
  @apply min-h-screen bg-gray-50;
}

.page-header {
  @apply bg-white border-b border-gray-200 px-6 py-8;
  @apply flex items-center justify-between;
}

.header-content {
  @apply flex-1;
}

.page-title {
  @apply text-3xl font-bold text-gray-900 mb-2;
}

.page-description {
  @apply text-lg text-gray-600 max-w-2xl;
}

.header-actions {
  @apply flex items-center space-x-4;
}

.saved-searches-toggle {
  @apply flex items-center space-x-2 px-4 py-2;
  @apply border border-gray-300 rounded-md text-gray-700;
  @apply hover:bg-gray-50 transition-colors;
}

.saved-searches-toggle.active {
  @apply bg-blue-50 border-blue-300 text-blue-700;
}

.search-container {
  @apply flex gap-6 p-6;
}

.search-main {
  @apply flex-1;
}

.saved-searches-sidebar {
  @apply w-80 bg-white rounded-lg shadow-sm border border-gray-200 p-6;
  @apply max-h-screen overflow-y-auto;
}

.search-analytics {
  @apply mt-8 px-6;
}

/* Responsive design */
@media (max-width: 1024px) {
  .search-container {
    @apply flex-col;
  }
  
  .saved-searches-sidebar {
    @apply w-full max-h-96;
  }
}

@media (max-width: 768px) {
  .page-header {
    @apply flex-col items-start space-y-4;
  }
  
  .header-actions {
    @apply w-full justify-end;
  }
  
  .search-container {
    @apply p-4;
  }
}
</style>