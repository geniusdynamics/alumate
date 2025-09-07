<template>
  <div class="advanced-search">
    <!-- Search Input -->
    <div class="search-input-container">
      <div class="relative">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search alumni, posts, jobs, events..."
          class="search-input"
          @input="handleSearchInput"
          @keydown.enter="performSearch"
          @focus="showSuggestions = true"
          aria-label="Search"
          role="searchbox"
          aria-expanded="false"
          aria-haspopup="listbox"
        />
        <button
          @click="performSearch"
          class="search-button"
          :disabled="isSearching"
          aria-label="Search"
        >
          <SearchIcon v-if="!isSearching" class="w-5 h-5" />
          <LoadingSpinner v-else class="w-5 h-5" />
        </button>
      </div>

      <!-- Search Suggestions -->
      <div
        v-if="showSuggestions && suggestions.length > 0"
        class="suggestions-dropdown"
        role="listbox"
        aria-label="Search suggestions"
      >
        <div
          v-for="(suggestion, index) in suggestions"
          :key="index"
          @click="selectSuggestion(suggestion)"
          class="suggestion-item"
          role="option"
          :aria-selected="false"
        >
          <span class="suggestion-text">{{ suggestion.text }}</span>
          <span class="suggestion-type">{{ suggestion.type }}</span>
        </div>
      </div>
    </div>

    <!-- Search Filters -->
    <SearchFilters
      v-if="showFilters"
      :filters="filters"
      :aggregations="aggregations"
      @update:filters="updateFilters"
      @clear-filters="clearFilters"
    />

    <!-- Search Results -->
    <div v-if="hasSearched" class="search-results">
      <!-- Results Header -->
      <div class="results-header">
        <div class="results-info">
          <span class="results-count">
            {{ searchResults.total.toLocaleString() }} results
          </span>
          <span v-if="searchResults.took" class="search-time">
            ({{ searchResults.took }}ms)
          </span>
        </div>
        
        <div class="results-actions">
          <button
            @click="toggleFilters"
            class="filter-toggle"
            :class="{ active: showFilters }"
          >
            <FilterIcon class="w-4 h-4" />
            Filters
          </button>
          
          <select
            v-model="sortBy"
            @change="performSearch"
            class="sort-select"
            aria-label="Sort results"
          >
            <option value="relevance">Relevance</option>
            <option value="date">Date</option>
            <option value="name">Name</option>
            <option value="engagement">Engagement</option>
          </select>
          
          <button
            v-if="canSaveSearch"
            @click="saveCurrentSearch"
            class="save-search-btn"
            :disabled="isSavingSearch"
          >
            <BookmarkIcon class="w-4 h-4" />
            Save Search
          </button>
        </div>
      </div>

      <!-- Results List -->
      <div class="results-list">
        <div
          v-for="result in searchResults.hits"
          :key="`${result.type}-${result.id}`"
          class="result-item"
          :class="`result-${result.type}`"
        >
          <!-- User Result -->
          <UserResult
            v-if="result.type === 'user'"
            :user="result.source"
            :highlight="result.highlight"
            :score="result.score"
          />
          
          <!-- Post Result -->
          <PostResult
            v-else-if="result.type === 'post'"
            :post="result.source"
            :highlight="result.highlight"
            :score="result.score"
          />
          
          <!-- Job Result -->
          <JobResult
            v-else-if="result.type === 'job'"
            :job="result.source"
            :highlight="result.highlight"
            :score="result.score"
          />
          
          <!-- Event Result -->
          <EventResult
            v-else-if="result.type === 'event'"
            :event="result.source"
            :highlight="result.highlight"
            :score="result.score"
          />
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="searchResults.total > pageSize" class="pagination">
        <button
          @click="previousPage"
          :disabled="currentPage === 1"
          class="pagination-btn"
        >
          Previous
        </button>
        
        <span class="pagination-info">
          Page {{ currentPage }} of {{ totalPages }}
        </span>
        
        <button
          @click="nextPage"
          :disabled="currentPage >= totalPages"
          class="pagination-btn"
        >
          Next
        </button>
      </div>
    </div>

    <!-- No Results -->
    <div v-if="hasSearched && searchResults.total === 0" class="no-results">
      <div class="no-results-icon">
        <SearchIcon class="w-12 h-12 text-gray-400" />
      </div>
      <h3 class="no-results-title">No results found</h3>
      <p class="no-results-message">
        Try adjusting your search terms or filters to find what you're looking for.
      </p>
      <button @click="clearSearch" class="clear-search-btn">
        Clear Search
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="isSearching" class="search-loading">
      <LoadingSpinner class="w-8 h-8" />
      <p>Searching...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { debounce } from 'lodash-es'
import { useToast } from '@/composables/useToast'
import SearchFilters from './SearchFilters.vue'
import UserResult from './SearchResults/UserResult.vue'
import PostResult from './SearchResults/PostResult.vue'
import JobResult from './SearchResults/JobResult.vue'
import EventResult from './SearchResults/EventResult.vue'
import {
  MagnifyingGlassIcon as SearchIcon,
  FunnelIcon as FilterIcon,
  BookmarkIcon
} from '@heroicons/vue/24/outline'
import LoadingSpinner from './LoadingSpinner.vue'

interface SearchResult {
  id: string
  type: 'user' | 'post' | 'job' | 'event'
  score: number
  source: any
  highlight: Record<string, string[]>
}

interface SearchResponse {
  hits: SearchResult[]
  total: number
  aggregations: Record<string, any>
  took: number
}

interface SearchFilters {
  types: string[]
  location: string
  graduation_year: string
  industry: string[]
  skills: string[]
  date_range: {
    from: string
    to: string
  }
}

const props = defineProps<{
  initialQuery?: string
  initialFilters?: Partial<SearchFilters>
}>()

const emit = defineEmits<{
  'search-performed': [query: string, results: SearchResponse]
  'result-selected': [result: SearchResult]
}>()

// Reactive state
const searchQuery = ref(props.initialQuery || '')
const isSearching = ref(false)
const hasSearched = ref(false)
const showSuggestions = ref(false)
const showFilters = ref(false)
const suggestions = ref<Array<{ text: string; type: string; score: number }>>([])
const currentPage = ref(1)
const pageSize = ref(20)
const sortBy = ref('relevance')
const isSavingSearch = ref(false)

const filters = reactive<SearchFilters>({
  types: ['user', 'post', 'job', 'event'],
  location: '',
  graduation_year: '',
  industry: [],
  skills: [],
  date_range: {
    from: '',
    to: ''
  },
  ...props.initialFilters
})

const searchResults = reactive<SearchResponse>({
  hits: [],
  total: 0,
  aggregations: {},
  took: 0
})

const aggregations = ref<Record<string, any>>({})

// Computed properties
const totalPages = computed(() => Math.ceil(searchResults.total / pageSize.value))
const canSaveSearch = computed(() => searchQuery.value.length > 0 && hasSearched.value)

// Toast composable
const { showToast } = useToast()

// Debounced search input handler
const handleSearchInput = debounce(async () => {
  if (searchQuery.value.length > 2) {
    await getSuggestions()
  } else {
    suggestions.value = []
    showSuggestions.value = false
  }
}, 300)

// Methods
const performSearch = async () => {
  if (!searchQuery.value.trim()) return

  isSearching.value = true
  showSuggestions.value = false

  try {
    const response = await fetch('/api/search', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        query: searchQuery.value,
        filters: {
          ...filters,
          sort: sortBy.value
        },
        size: pageSize.value,
        from: (currentPage.value - 1) * pageSize.value
      })
    })

    if (!response.ok) {
      throw new Error('Search request failed')
    }

    const data = await response.json()
    
    Object.assign(searchResults, data)
    aggregations.value = data.aggregations || {}
    hasSearched.value = true

    emit('search-performed', searchQuery.value, data)
  } catch (error) {
    console.error('Search failed:', error)
    showToast('Search failed. Please try again.', 'error')
  } finally {
    isSearching.value = false
  }
}

const getSuggestions = async () => {
  try {
    const response = await fetch(`/api/search/suggestions?q=${encodeURIComponent(searchQuery.value)}`)
    if (response.ok) {
      const data = await response.json()
      suggestions.value = data.suggestions || []
      showSuggestions.value = suggestions.value.length > 0
    }
  } catch (error) {
    console.error('Failed to get suggestions:', error)
  }
}

const selectSuggestion = (suggestion: { text: string; type: string }) => {
  searchQuery.value = suggestion.text
  showSuggestions.value = false
  performSearch()
}

const updateFilters = (newFilters: Partial<SearchFilters>) => {
  Object.assign(filters, newFilters)
  currentPage.value = 1
  performSearch()
}

const clearFilters = () => {
  Object.assign(filters, {
    types: ['user', 'post', 'job', 'event'],
    location: '',
    graduation_year: '',
    industry: [],
    skills: [],
    date_range: { from: '', to: '' }
  })
  currentPage.value = 1
  performSearch()
}

const toggleFilters = () => {
  showFilters.value = !showFilters.value
}

const clearSearch = () => {
  searchQuery.value = ''
  hasSearched.value = false
  Object.assign(searchResults, { hits: [], total: 0, aggregations: {}, took: 0 })
  clearFilters()
}

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
    performSearch()
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
    performSearch()
  }
}

const saveCurrentSearch = async () => {
  isSavingSearch.value = true
  
  try {
    const response = await fetch('/api/saved-searches', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        name: `Search: ${searchQuery.value}`,
        query: searchQuery.value,
        filters: filters
      })
    })

    if (response.ok) {
      showToast('Search saved successfully!', 'success')
    } else {
      throw new Error('Failed to save search')
    }
  } catch (error) {
    console.error('Failed to save search:', error)
    showToast('Failed to save search. Please try again.', 'error')
  } finally {
    isSavingSearch.value = false
  }
}

// Watch for sort changes
watch(sortBy, () => {
  if (hasSearched.value) {
    currentPage.value = 1
    performSearch()
  }
})

// Hide suggestions when clicking outside
const handleClickOutside = (event: Event) => {
  const target = event.target as Element
  if (!target.closest('.search-input-container')) {
    showSuggestions.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  
  // Perform initial search if query provided
  if (props.initialQuery) {
    performSearch()
  }
})
</script>

<style scoped>
.advanced-search {
  @apply max-w-4xl mx-auto;
}

.search-input-container {
  @apply relative mb-6;
}

.search-input {
  @apply w-full px-4 py-3 pr-12 text-lg border border-gray-300 rounded-lg;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-transparent;
  @apply placeholder-gray-500;
}

.search-button {
  @apply absolute right-3 top-1/2 transform -translate-y-1/2;
  @apply p-2 text-gray-500 hover:text-blue-600;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.suggestions-dropdown {
  @apply absolute top-full left-0 right-0 z-10 mt-1;
  @apply bg-white border border-gray-200 rounded-lg shadow-lg;
  @apply max-h-60 overflow-y-auto;
}

.suggestion-item {
  @apply px-4 py-2 hover:bg-gray-50 cursor-pointer;
  @apply flex items-center justify-between;
}

.suggestion-text {
  @apply text-gray-900;
}

.suggestion-type {
  @apply text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded;
}

.results-header {
  @apply flex items-center justify-between mb-6 pb-4 border-b border-gray-200;
}

.results-info {
  @apply flex items-center space-x-2;
}

.results-count {
  @apply text-lg font-semibold text-gray-900;
}

.search-time {
  @apply text-sm text-gray-500;
}

.results-actions {
  @apply flex items-center space-x-4;
}

.filter-toggle {
  @apply flex items-center space-x-2 px-3 py-2 border border-gray-300 rounded-md;
  @apply hover:bg-gray-50 transition-colors;
}

.filter-toggle.active {
  @apply bg-blue-50 border-blue-300 text-blue-700;
}

.sort-select {
  @apply px-3 py-2 border border-gray-300 rounded-md;
  @apply focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}

.save-search-btn {
  @apply flex items-center space-x-2 px-3 py-2;
  @apply bg-blue-600 text-white rounded-md hover:bg-blue-700;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.results-list {
  @apply space-y-4;
}

.result-item {
  @apply bg-white border border-gray-200 rounded-lg p-4;
  @apply hover:shadow-md transition-shadow;
}

.pagination {
  @apply flex items-center justify-center space-x-4 mt-8;
}

.pagination-btn {
  @apply px-4 py-2 border border-gray-300 rounded-md;
  @apply hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed;
}

.pagination-info {
  @apply text-sm text-gray-600;
}

.no-results {
  @apply text-center py-12;
}

.no-results-icon {
  @apply flex justify-center mb-4;
}

.no-results-title {
  @apply text-xl font-semibold text-gray-900 mb-2;
}

.no-results-message {
  @apply text-gray-600 mb-6;
}

.clear-search-btn {
  @apply px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700;
}

.search-loading {
  @apply flex flex-col items-center justify-center py-12;
  @apply text-gray-600;
}
</style>