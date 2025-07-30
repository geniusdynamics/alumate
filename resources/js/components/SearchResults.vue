<template>
  <div class="search-results">
    <!-- Results Header -->
    <div class="results-header">
      <div class="results-info">
        <h2 class="results-title">
          Search Results
          <span v-if="!loading" class="results-count">({{ total.toLocaleString() }} found)</span>
        </h2>
        <div v-if="query || hasActiveFilters" class="search-summary">
          <span v-if="query" class="search-query">
            Searching for: <strong>"{{ query }}"</strong>
          </span>
          <span v-if="hasActiveFilters" class="active-filters-summary">
            with {{ activeFiltersCount }} filter{{ activeFiltersCount !== 1 ? 's' : '' }} applied
          </span>
        </div>
      </div>
      
      <div class="results-actions">
        <button
          @click="$emit('export')"
          class="export-button"
          :disabled="loading || total === 0"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Export Results
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="loading-spinner">
        <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
      <p class="loading-text">Searching alumni...</p>
    </div>

    <!-- No Results -->
    <div v-else-if="total === 0" class="no-results">
      <div class="no-results-icon">
        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
      <h3 class="no-results-title">No alumni found</h3>
      <p class="no-results-message">
        Try adjusting your search terms or filters to find more results.
      </p>
    </div>

    <!-- Results List -->
    <div v-else class="results-list">
      <div
        v-for="user in results"
        :key="user.id"
        class="result-item"
      >
        <div class="result-avatar">
          <img
            v-if="user.avatar"
            :src="user.avatar"
            :alt="user.name"
            class="avatar-image"
          />
          <div v-else class="avatar-placeholder">
            {{ getInitials(user.name) }}
          </div>
        </div>

        <div class="result-content">
          <div class="result-header">
            <h3 class="result-name" v-html="highlightText(user.name, user.highlight?.name)"></h3>
            <div class="result-score">
              <span class="score-label">Match:</span>
              <div class="score-bar">
                <div 
                  class="score-fill" 
                  :style="{ width: `${Math.min(user.score * 10, 100)}%` }"
                ></div>
              </div>
            </div>
          </div>

          <div class="result-details">
            <div v-if="user.title || user.company" class="result-job">
              <span v-if="user.title" v-html="highlightText(user.title, user.highlight?.title)"></span>
              <span v-if="user.title && user.company"> at </span>
              <span v-if="user.company" v-html="highlightText(user.company, user.highlight?.company)"></span>
            </div>

            <div v-if="user.location" class="result-location">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              {{ user.location }}
            </div>

            <div v-if="user.graduation_year" class="result-graduation">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
              </svg>
              Class of {{ user.graduation_year }}
            </div>

            <div v-if="user.bio" class="result-bio">
              <p v-html="highlightText(user.bio, user.highlight?.bio)"></p>
            </div>

            <div v-if="user.skills && user.skills.length > 0" class="result-skills">
              <span
                v-for="skill in user.skills.slice(0, 5)"
                :key="skill"
                class="skill-tag"
                v-html="highlightText(skill, user.highlight?.skills)"
              ></span>
              <span v-if="user.skills.length > 5" class="more-skills">
                +{{ user.skills.length - 5 }} more
              </span>
            </div>
          </div>
        </div>

        <div class="result-actions">
          <button class="action-button primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
            </svg>
            Connect
          </button>
          <button class="action-button secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            View Profile
          </button>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="pagination">
      <button
        @click="$emit('page-change', currentPage - 1)"
        :disabled="currentPage === 1"
        class="pagination-button"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Previous
      </button>

      <div class="pagination-info">
        Page {{ currentPage }} of {{ totalPages }}
      </div>

      <button
        @click="$emit('page-change', currentPage + 1)"
        :disabled="currentPage === totalPages"
        class="pagination-button"
      >
        Next
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// Props
const props = defineProps({
  results: {
    type: Array,
    default: () => []
  },
  total: {
    type: Number,
    default: 0
  },
  loading: {
    type: Boolean,
    default: false
  },
  query: {
    type: String,
    default: ''
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  currentPage: {
    type: Number,
    default: 1
  },
  totalPages: {
    type: Number,
    default: 0
  }
})

// Emits
const emit = defineEmits(['page-change', 'export'])

// Computed
const hasActiveFilters = computed(() => {
  return Object.values(props.filters).some(value => {
    if (Array.isArray(value)) return value.length > 0
    if (typeof value === 'object' && value !== null) {
      return Object.values(value).some(v => v !== null && v !== '')
    }
    return value !== '' && value !== null
  })
})

const activeFiltersCount = computed(() => {
  let count = 0
  Object.values(props.filters).forEach(value => {
    if (Array.isArray(value) && value.length > 0) count++
    else if (value && typeof value === 'object' && Object.keys(value).length > 0) count++
    else if (value && typeof value === 'string') count++
  })
  return count
})

// Methods
const getInitials = (name) => {
  return name
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const highlightText = (text, highlight) => {
  if (!highlight || !highlight.length) {
    return text
  }
  
  // Elasticsearch returns highlighted text in an array
  return highlight[0] || text
}
</script>

<style scoped>
.search-results {
  @apply space-y-6;
}

.results-header {
  @apply flex justify-between items-start;
}

.results-title {
  @apply text-2xl font-bold text-gray-900;
}

.results-count {
  @apply text-lg font-normal text-gray-600;
}

.search-summary {
  @apply mt-2 text-sm text-gray-600 space-x-2;
}

.search-query {
  @apply inline;
}

.active-filters-summary {
  @apply inline;
}

.results-actions {
  @apply flex gap-2;
}

.export-button {
  @apply flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed;
}

.loading-state {
  @apply flex flex-col items-center justify-center py-12;
}

.loading-spinner {
  @apply mb-4;
}

.loading-text {
  @apply text-gray-600;
}

.no-results {
  @apply text-center py-12;
}

.no-results-icon {
  @apply mb-4;
}

.no-results-title {
  @apply text-xl font-semibold text-gray-900 mb-2;
}

.no-results-message {
  @apply text-gray-600;
}

.results-list {
  @apply space-y-4;
}

.result-item {
  @apply flex gap-4 p-6 bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow;
}

.result-avatar {
  @apply flex-shrink-0;
}

.avatar-image {
  @apply w-16 h-16 rounded-full object-cover;
}

.avatar-placeholder {
  @apply w-16 h-16 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 font-semibold;
}

.result-content {
  @apply flex-1 min-w-0;
}

.result-header {
  @apply flex justify-between items-start mb-2;
}

.result-name {
  @apply text-lg font-semibold text-gray-900;
}

.result-score {
  @apply flex items-center gap-2 text-sm text-gray-500;
}

.score-bar {
  @apply w-16 h-2 bg-gray-200 rounded-full overflow-hidden;
}

.score-fill {
  @apply h-full bg-blue-500 transition-all duration-300;
}

.result-details {
  @apply space-y-2;
}

.result-job {
  @apply text-gray-700 font-medium;
}

.result-location,
.result-graduation {
  @apply flex items-center gap-1 text-sm text-gray-600;
}

.result-bio {
  @apply text-sm text-gray-600 line-clamp-2;
}

.result-skills {
  @apply flex flex-wrap gap-2;
}

.skill-tag {
  @apply px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full;
}

.more-skills {
  @apply px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full;
}

.result-actions {
  @apply flex flex-col gap-2;
}

.action-button {
  @apply flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors;
}

.action-button.primary {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.action-button.secondary {
  @apply border border-gray-300 text-gray-700 hover:bg-gray-50;
}

.pagination {
  @apply flex justify-between items-center;
}

.pagination-button {
  @apply flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed;
}

.pagination-info {
  @apply text-sm text-gray-600;
}
</style>