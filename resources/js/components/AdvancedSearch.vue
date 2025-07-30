<template>
  <div class="advanced-search">
    <!-- Search Header -->
    <div class="search-header">
      <div class="search-input-container">
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search alumni by name, company, skills, or interests..."
            class="search-input"
            @input="onSearchInput"
            @keydown.enter="performSearch"
            @focus="showSuggestions = true"
          />
          <button
            @click="performSearch"
            class="search-button"
            :disabled="isSearching"
          >
            <svg v-if="!isSearching" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </button>
        </div>

        <!-- Search Suggestions -->
        <SearchSuggestions
          v-if="showSuggestions && suggestions.length > 0"
          :suggestions="suggestions"
          @select="selectSuggestion"
          @close="showSuggestions = false"
        />
      </div>

      <!-- Search Actions -->
      <div class="search-actions">
        <button
          @click="toggleFilters"
          class="filter-toggle"
          :class="{ active: showFilters }"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
          </svg>
          Filters
          <span v-if="activeFiltersCount > 0" class="filter-count">{{ activeFiltersCount }}</span>
        </button>

        <button
          v-if="hasSearched"
          @click="showSaveModal = true"
          class="save-search-button"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
          </svg>
          Save Search
        </button>

        <button
          @click="showSavedSearches = true"
          class="saved-searches-button"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          Saved Searches
        </button>
      </div>
    </div>

    <!-- Search Filters -->
    <SearchFilters
      v-if="showFilters"
      v-model="filters"
      :aggregations="aggregations"
      @update="onFiltersUpdate"
      @clear="clearFilters"
    />

    <!-- Search Results -->
    <SearchResults
      v-if="hasSearched"
      :results="searchResults"
      :total="totalResults"
      :loading="isSearching"
      :query="searchQuery"
      :filters="filters"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="onPageChange"
      @export="exportResults"
    />

    <!-- Save Search Modal -->
    <SaveSearchModal
      v-if="showSaveModal"
      :query="searchQuery"
      :filters="filters"
      @save="saveSearch"
      @close="showSaveModal = false"
    />

    <!-- Saved Searches Modal -->
    <SavedSearches
      v-if="showSavedSearches"
      @load="loadSavedSearch"
      @close="showSavedSearches = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { debounce } from 'lodash'
import SearchSuggestions from './SearchSuggestions.vue'
import SearchFilters from './SearchFilters.vue'
import SearchResults from './SearchResults.vue'
import SavedSearches from './SavedSearches.vue'
import SaveSearchModal from './SaveSearchModal.vue'

// Props
const props = defineProps({
  initialQuery: {
    type: String,
    default: ''
  },
  initialFilters: {
    type: Object,
    default: () => ({})
  }
})

// Reactive data
const searchQuery = ref(props.initialQuery)
const filters = ref({ ...props.initialFilters })
const searchResults = ref([])
const totalResults = ref(0)
const aggregations = ref({})
const suggestions = ref([])
const currentPage = ref(1)
const totalPages = ref(0)

// UI state
const isSearching = ref(false)
const showFilters = ref(false)
const showSuggestions = ref(false)
const showSaveModal = ref(false)
const showSavedSearches = ref(false)
const hasSearched = ref(false)

// Computed
const activeFiltersCount = computed(() => {
  let count = 0
  Object.values(filters.value).forEach(value => {
    if (Array.isArray(value) && value.length > 0) count++
    else if (value && typeof value === 'object' && Object.keys(value).length > 0) count++
    else if (value && typeof value === 'string') count++
  })
  return count
})

// Methods
const performSearch = async (page = 1) => {
  if (!searchQuery.value.trim() && activeFiltersCount.value === 0) {
    return
  }

  isSearching.value = true
  currentPage.value = page

  try {
    const response = await fetch('/api/search', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}`
      },
      body: JSON.stringify({
        query: searchQuery.value,
        filters: filters.value,
        page: page,
        size: 20
      })
    })

    const data = await response.json()

    if (data.success) {
      searchResults.value = data.data.users
      totalResults.value = data.data.total
      aggregations.value = data.data.aggregations
      totalPages.value = data.data.total_pages
      hasSearched.value = true
    } else {
      console.error('Search failed:', data.message)
    }
  } catch (error) {
    console.error('Search error:', error)
  } finally {
    isSearching.value = false
  }
}

const getSuggestions = debounce(async (query) => {
  if (query.length < 2) {
    suggestions.value = []
    return
  }

  try {
    const response = await fetch(`/api/search/suggestions?query=${encodeURIComponent(query)}`, {
      headers: {
        'Authorization': `Bearer ${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}`
      }
    })

    const data = await response.json()

    if (data.success) {
      suggestions.value = data.data
    }
  } catch (error) {
    console.error('Suggestions error:', error)
  }
}, 300)

const onSearchInput = () => {
  getSuggestions(searchQuery.value)
}

const selectSuggestion = (suggestion) => {
  searchQuery.value = suggestion.text
  showSuggestions.value = false
  performSearch()
}

const toggleFilters = () => {
  showFilters.value = !showFilters.value
}

const onFiltersUpdate = () => {
  if (hasSearched.value) {
    performSearch(1)
  }
}

const clearFilters = () => {
  filters.value = {}
  if (hasSearched.value) {
    performSearch(1)
  }
}

const onPageChange = (page) => {
  performSearch(page)
}

const saveSearch = async (searchData) => {
  try {
    const response = await fetch('/api/search/save', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${document.querySelector('meta[name="csrf-token"]').getAttribute('content')}`
      },
      body: JSON.stringify({
        ...searchData,
        query: searchQuery.value,
        filters: filters.value
      })
    })

    const data = await response.json()

    if (data.success) {
      showSaveModal.value = false
      // Show success message
    } else {
      console.error('Save search failed:', data.message)
    }
  } catch (error) {
    console.error('Save search error:', error)
  }
}

const loadSavedSearch = (savedSearch) => {
  searchQuery.value = savedSearch.query
  filters.value = { ...savedSearch.filters }
  showSavedSearches.value = false
  performSearch(1)
}

const exportResults = () => {
  // Export functionality would be implemented here
  console.log('Exporting results...')
}

// Initialize search if there are initial parameters
onMounted(() => {
  if (props.initialQuery || Object.keys(props.initialFilters).length > 0) {
    performSearch()
  }
})

// Watch for external changes
watch(() => props.initialQuery, (newQuery) => {
  searchQuery.value = newQuery
})

watch(() => props.initialFilters, (newFilters) => {
  filters.value = { ...newFilters }
})
</script>

<style scoped>
.advanced-search {
  @apply space-y-6;
}

.search-header {
  @apply flex flex-col lg:flex-row gap-4;
}

.search-input-container {
  @apply flex-1 relative;
}

.search-input {
  @apply w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}

.search-button {
  @apply absolute right-2 top-1/2 transform -translate-y-1/2 p-2 text-gray-400 hover:text-gray-600 disabled:opacity-50;
}

.search-actions {
  @apply flex gap-2;
}

.filter-toggle {
  @apply flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors;
}

.filter-toggle.active {
  @apply bg-blue-50 border-blue-300 text-blue-700;
}

.filter-count {
  @apply bg-blue-500 text-white text-xs rounded-full px-2 py-1 min-w-[20px] text-center;
}

.save-search-button,
.saved-searches-button {
  @apply flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors;
}
</style>