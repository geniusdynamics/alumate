<template>
  <div 
    class="component-browser"
    :class="containerClasses"
    role="application"
    :aria-label="ariaLabel"
  >
    <!-- Header Section -->
    <header class="component-browser__header">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-8">
        <div class="flex-1">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            Component Browser
          </h1>
          <p class="text-lg text-gray-600 dark:text-gray-300">
            Discover, compare, and select the perfect components for your pages
          </p>
        </div>
        
        <!-- Quick Stats -->
        <div class="flex items-center space-x-6 text-sm text-gray-500 dark:text-gray-400">
          <div class="flex items-center space-x-2">
            <Icon name="collection" class="h-4 w-4" />
            <span>{{ totalComponents }} components</span>
          </div>
          <div class="flex items-center space-x-2">
            <Icon name="heart" class="h-4 w-4" />
            <span>{{ favoriteCount }} favorites</span>
          </div>
          <div class="flex items-center space-x-2">
            <Icon name="clock" class="h-4 w-4" />
            <span>{{ recentlyUsedCount }} recent</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Advanced Search and Filters -->
    <div class="component-browser__search-filters bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
      <!-- Search Bar -->
      <div class="relative mb-6">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
          <Icon name="search" class="h-5 w-5 text-gray-400" aria-hidden="true" />
        </div>
        <input
          v-model="searchQuery"
          type="search"
          placeholder="Search components by name, description, or tags..."
          class="block w-full pl-12 pr-12 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-lg bg-white dark:bg-gray-700 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white"
          :aria-label="searchAriaLabel"
          @input="handleSearchInput"
          @keydown.escape="clearSearch"
        />
        <button
          v-if="searchQuery"
          @click="clearSearch"
          class="absolute inset-y-0 right-0 pr-4 flex items-center"
          aria-label="Clear search"
        >
          <Icon name="x" class="h-5 w-5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" />
        </button>
      </div>

      <!-- Filter Controls -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Category Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Category
          </label>
          <select
            v-model="selectedCategory"
            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            aria-label="Filter by category"
          >
            <option value="">All Categories</option>
            <option
              v-for="category in categories"
              :key="category.id"
              :value="category.id"
            >
              {{ category.name }} ({{ getCategoryCount(category.id) }})
            </option>
          </select>
        </div>

        <!-- Audience Type Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Audience
          </label>
          <select
            v-model="selectedAudienceType"
            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            aria-label="Filter by audience type"
          >
            <option value="">All Audiences</option>
            <option value="individual">Individual Alumni</option>
            <option value="institution">Institutions</option>
            <option value="employer">Employers</option>
          </select>
        </div>

        <!-- Rating Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Rating
          </label>
          <select
            v-model="selectedRating"
            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            aria-label="Filter by rating"
          >
            <option value="">Any Rating</option>
            <option value="5">5 Stars</option>
            <option value="4">4+ Stars</option>
            <option value="3">3+ Stars</option>
          </select>
        </div>

        <!-- Sort Options -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Sort By
          </label>
          <select
            v-model="sortBy"
            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            aria-label="Sort components by"
          >
            <option value="name">Name A-Z</option>
            <option value="name-desc">Name Z-A</option>
            <option value="rating">Highest Rated</option>
            <option value="usage">Most Popular</option>
            <option value="recent">Recently Updated</option>
            <option value="created">Newest First</option>
          </select>
        </div>
      </div>

      <!-- Quick Filter Buttons -->
      <div class="flex flex-wrap items-center gap-3">
        <button
          @click="toggleFavoritesOnly"
          :class="getQuickFilterClasses('favorites')"
          :aria-pressed="showFavoritesOnly"
          aria-label="Show favorites only"
        >
          <Icon name="heart" class="h-4 w-4 mr-2" />
          Favorites Only
          <span v-if="favoriteCount > 0" class="ml-2 bg-white bg-opacity-20 px-2 py-0.5 rounded-full text-xs">
            {{ favoriteCount }}
          </span>
        </button>

        <button
          @click="toggleRecentOnly"
          :class="getQuickFilterClasses('recent')"
          :aria-pressed="showRecentOnly"
          aria-label="Show recently used only"
        >
          <Icon name="clock" class="h-4 w-4 mr-2" />
          Recent Only
          <span v-if="recentlyUsedCount > 0" class="ml-2 bg-white bg-opacity-20 px-2 py-0.5 rounded-full text-xs">
            {{ recentlyUsedCount }}
          </span>
        </button>

        <button
          @click="toggleHighRatedOnly"
          :class="getQuickFilterClasses('highRated')"
          :aria-pressed="showHighRatedOnly"
          aria-label="Show highly rated only"
        >
          <Icon name="star" class="h-4 w-4 mr-2" />
          Top Rated
        </button>

        <button
          v-if="hasActiveFilters"
          @click="clearAllFilters"
          class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          aria-label="Clear all filters"
        >
          <Icon name="x" class="h-4 w-4 mr-2" />
          Clear All
        </button>
      </div>
    </div>

    <!-- View Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div class="flex items-center space-x-4">
        <!-- View Mode Toggle -->
        <div class="flex items-center space-x-2">
          <span class="text-sm text-gray-700 dark:text-gray-300">View:</span>
          <div class="flex rounded-md shadow-sm" role="group" aria-label="View options">
            <button
              @click="setViewMode('grid')"
              :class="getViewButtonClasses('grid')"
              :aria-pressed="viewMode === 'grid'"
              aria-label="Grid view"
            >
              <Icon name="view-grid" class="h-4 w-4" />
            </button>
            <button
              @click="setViewMode('list')"
              :class="getViewButtonClasses('list')"
              :aria-pressed="viewMode === 'list'"
              aria-label="List view"
            </button>
            <button
              @click="setViewMode('comparison')"
              :class="getViewButtonClasses('comparison')"
              :aria-pressed="viewMode === 'comparison'"
              aria-label="Comparison view"
            >
              <Icon name="scale" class="h-4 w-4" />
            </button>
          </div>
        </div>

        <!-- Results Count -->
        <div class="text-sm text-gray-600 dark:text-gray-400">
          {{ filteredComponents.length }} of {{ totalComponents }} components
        </div>
      </div>

      <!-- Comparison Controls -->
      <div v-if="viewMode === 'comparison'" class="flex items-center space-x-3">
        <span class="text-sm text-gray-700 dark:text-gray-300">
          Compare: {{ selectedForComparison.length }}/{{ maxComparison }}
        </span>
        <button
          v-if="selectedForComparison.length > 0"
          @click="clearComparison"
          class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white px-3 py-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        >
          Clear Selection
        </button>
      </div>
    </div>

    <!-- Component Display Area -->
    <main class="component-browser__content">
      <!-- Loading State -->
      <div 
        v-if="isLoading" 
        class="grid gap-6"
        :class="getGridClasses()"
        role="status"
        aria-label="Loading components"
      >
        <ComponentSkeleton
          v-for="n in 12"
          :key="`skeleton-${n}`"
          :view-mode="viewMode"
        />
      </div>

      <!-- Empty State -->
      <div 
        v-else-if="filteredComponents.length === 0"
        class="text-center py-16"
        role="status"
      >
        <div class="max-w-md mx-auto">
          <Icon 
            name="search" 
            class="mx-auto h-16 w-16 text-gray-400 mb-6" 
            aria-hidden="true" 
          />
          <h3 class="text-xl font-medium text-gray-900 dark:text-white mb-3">
            {{ getEmptyStateTitle() }}
          </h3>
          <p class="text-gray-500 dark:text-gray-400 mb-6">
            {{ getEmptyStateMessage() }}
          </p>
          <button
            v-if="hasActiveFilters"
            @click="clearAllFilters"
            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Clear filters and show all components
          </button>
        </div>
      </div>

      <!-- Components Display -->
      <div v-else>
        <!-- Grid/List View -->
        <div 
          v-if="viewMode !== 'comparison'"
          :class="getContentClasses()"
          role="grid"
          :aria-label="`${filteredComponents.length} components in ${viewMode} view`"
        >
          <ComponentBrowserCard
            v-for="component in paginatedComponents"
            :key="component.id"
            :component="component"
            :view-mode="viewMode"
            :is-favorite="isFavorite(component.id)"
            :recently-used="isRecentlyUsed(component.id)"
            :usage-stats="getUsageStats(component.id)"
            :rating="getComponentRating(component.id)"
            :selected-for-comparison="isSelectedForComparison(component.id)"
            :comparison-mode="viewMode === 'comparison'"
            :max-comparison-reached="selectedForComparison.length >= maxComparison"
            @preview="handlePreview"
            @favorite="handleFavorite"
            @select="handleSelect"
            @drag-start="handleDragStart"
            @toggle-comparison="handleToggleComparison"
            @view-details="handleViewDetails"
          />
        </div>

        <!-- Comparison View -->
        <div v-else class="comparison-view">
          <div v-if="selectedForComparison.length === 0" class="text-center py-12">
            <Icon name="scale" class="mx-auto h-12 w-12 text-gray-400 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
              Select components to compare
            </h3>
            <p class="text-gray-500 dark:text-gray-400">
              Click the compare button on components to add them to comparison (max {{ maxComparison }})
            </p>
          </div>

          <div v-else class="comparison-table-container">
            <ComponentComparisonTable
              :components="getSelectedComponents()"
              @remove="handleRemoveFromComparison"
              @select="handleSelect"
              @preview="handlePreview"
            />
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div 
        v-if="totalPages > 1 && viewMode !== 'comparison'"
        class="mt-12 flex items-center justify-between"
      >
        <div class="text-sm text-gray-700 dark:text-gray-300">
          Showing {{ startIndex + 1 }} to {{ endIndex }} of {{ filteredComponents.length }} components
        </div>
        
        <nav class="flex items-center space-x-2" aria-label="Pagination">
          <button
            @click="goToPage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
            aria-label="Previous page"
          >
            <Icon name="chevron-left" class="h-4 w-4" />
          </button>
          
          <div class="flex space-x-1">
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="goToPage(page)"
              :class="getPageButtonClasses(page)"
              :aria-current="page === currentPage ? 'page' : undefined"
            >
              {{ page }}
            </button>
          </div>
          
          <button
            @click="goToPage(currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
            aria-label="Next page"
          >
            <Icon name="chevron-right" class="h-4 w-4" />
          </button>
        </nav>
      </div>
    </main>

    <!-- Component Detail Modal -->
    <ComponentDetailModal
      v-if="detailComponent"
      :component="detailComponent"
      :is-open="showDetailModal"
      :usage-stats="getUsageStats(detailComponent.id)"
      :rating="getComponentRating(detailComponent.id)"
      :documentation="getComponentDocumentation(detailComponent.id)"
      :examples="getComponentExamples(detailComponent.id)"
      @close="closeDetailModal"
      @preview="handlePreview"
      @select="handleSelect"
      @favorite="handleFavorite"
    />

    <!-- Component Preview Modal -->
    <ComponentPreviewModal
      v-if="previewComponent"
      :component="previewComponent"
      :is-open="showPreviewModal"
      @close="closePreviewModal"
      @select="handleSelect"
    />

    <!-- Screen Reader Announcements -->
    <div
      :aria-live="announcements.length > 0 ? 'polite' : 'off'"
      :aria-atomic="true"
      class="sr-only"
    >
      {{ currentAnnouncement }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, watch, nextTick } from 'vue'
import type { ComponentCategory, Component, AudienceType } from '@/types/components'
import { useDebounce } from '@/composables/useDebounce'
import { useAnalytics } from '@/composables/useAnalytics'
import { useUserPreferences } from '@/composables/useUserPreferences'

// Import child components
import Icon from '@/components/Common/Icon.vue'
import ComponentSkeleton from './ComponentSkeleton.vue'
import ComponentBrowserCard from './ComponentBrowserCard.vue'
import ComponentComparisonTable from './ComponentComparisonTable.vue'
import ComponentDetailModal from './ComponentDetailModal.vue'
import ComponentPreviewModal from './ComponentPreviewModal.vue'

interface ComponentUsageStats {
  totalUsage: number
  recentUsage: number
  conversionRate?: number
  averageRating?: number
  totalRatings?: number
}

interface ComponentRating {
  average: number
  count: number
  distribution: Record<number, number>
}

interface ComponentDocumentation {
  description: string
  properties: Record<string, string>
  examples: string[]
  bestPractices: string[]
  accessibility: string[]
}

interface Props {
  components?: Component[]
  initialCategory?: ComponentCategory
  sampleData?: boolean
  enableComparison?: boolean
  maxComparison?: number
}

interface Emits {
  (e: 'component-selected', component: Component): void
  (e: 'component-preview', component: Component): void
  (e: 'category-changed', category: ComponentCategory): void
  (e: 'drag-initiated', component: Component): void
}

const props = withDefaults(defineProps<Props>(), {
  components: () => [],
  initialCategory: 'hero',
  sampleData: false,
  enableComparison: true,
  maxComparison: 3
})

const emit = defineEmits<Emits>()

// Composables
const { trackEvent, trackUserAction } = useAnalytics()
const { getPreference, setPreference } = useUserPreferences()

// Reactive state
const isLoading = ref(true)
const searchQuery = ref('')
const selectedCategory = ref<ComponentCategory | ''>('')
const selectedAudienceType = ref<AudienceType | ''>('')
const selectedRating = ref<string>('')
const viewMode = ref<'grid' | 'list' | 'comparison'>('grid')
const sortBy = ref<'name' | 'name-desc' | 'rating' | 'usage' | 'recent' | 'created'>('name')
const showFavoritesOnly = ref(false)
const showRecentOnly = ref(false)
const showHighRatedOnly = ref(false)
const currentPage = ref(1)
const itemsPerPage = ref(12)

// Component state
const favorites = ref<string[]>([])
const recentlyUsed = ref<string[]>([])
const selectedForComparison = ref<string[]>([])
const previewComponent = ref<Component | null>(null)
const showPreviewModal = ref(false)
const detailComponent = ref<Component | null>(null)
const showDetailModal = ref(false)
const announcements = ref<string[]>([])

// Mock data for ratings and usage stats
const componentRatings = ref<Record<string, ComponentRating>>({})
const componentUsageStats = ref<Record<string, ComponentUsageStats>>({})
const componentDocumentation = ref<Record<string, ComponentDocumentation>>({})

// Categories configuration
const categories = ref([
  { id: 'hero' as ComponentCategory, name: 'Hero Sections', icon: 'star' },
  { id: 'forms' as ComponentCategory, name: 'Forms', icon: 'document-text' },
  { id: 'testimonials' as ComponentCategory, name: 'Testimonials', icon: 'chat-bubble-left-right' },
  { id: 'statistics' as ComponentCategory, name: 'Statistics', icon: 'chart-bar' },
  { id: 'ctas' as ComponentCategory, name: 'Call to Actions', icon: 'cursor-arrow-rays' },
  { id: 'media' as ComponentCategory, name: 'Media', icon: 'photo' }
])

// Debounced search
const debouncedSearch = useDebounce((query: string) => {
  currentPage.value = 1
  
  if (query.trim()) {
    trackEvent('component_browser_search', {
      query: query.trim(),
      category: selectedCategory.value,
      results_count: filteredComponents.value.length
    })
  }
}, 300)

// Computed properties
const containerClasses = computed(() => [
  'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8',
  {
    'component-browser--loading': isLoading.value,
    'component-browser--comparison': viewMode.value === 'comparison'
  }
])

const ariaLabel = computed(() => 
  `Component browser with ${props.components.length} components across ${categories.value.length} categories`
)

const searchAriaLabel = computed(() => 
  `Search ${filteredComponents.value.length} components`
)

const totalComponents = computed(() => props.components.length)

const favoriteCount = computed(() => favorites.value.length)

const recentlyUsedCount = computed(() => recentlyUsed.value.length)

const filteredComponents = computed(() => {
  let components = [...props.components]

  // Filter by category
  if (selectedCategory.value) {
    components = components.filter(c => c.category === selectedCategory.value)
  }

  // Filter by audience type
  if (selectedAudienceType.value) {
    components = components.filter(c => {
      const config = c.config as any
      return config.audienceType === selectedAudienceType.value
    })
  }

  // Filter by search query
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim()
    components = components.filter(c => 
      c.name.toLowerCase().includes(query) ||
      c.description?.toLowerCase().includes(query) ||
      c.type.toLowerCase().includes(query) ||
      c.category.toLowerCase().includes(query)
    )
  }

  // Filter by rating
  if (selectedRating.value) {
    const minRating = parseInt(selectedRating.value)
    components = components.filter(c => {
      const rating = getComponentRating(c.id)
      return rating.average >= minRating
    })
  }

  // Filter by favorites
  if (showFavoritesOnly.value) {
    components = components.filter(c => favorites.value.includes(c.id))
  }

  // Filter by recent
  if (showRecentOnly.value) {
    components = components.filter(c => recentlyUsed.value.includes(c.id))
  }

  // Filter by high rated
  if (showHighRatedOnly.value) {
    components = components.filter(c => {
      const rating = getComponentRating(c.id)
      return rating.average >= 4.0
    })
  }

  // Sort components
  components.sort((a, b) => {
    switch (sortBy.value) {
      case 'name':
        return a.name.localeCompare(b.name)
      case 'name-desc':
        return b.name.localeCompare(a.name)
      case 'rating':
        return getComponentRating(b.id).average - getComponentRating(a.id).average
      case 'usage':
        return getUsageStats(b.id).totalUsage - getUsageStats(a.id).totalUsage
      case 'recent':
        return new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime()
      case 'created':
        return new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
      default:
        return 0
    }
  })

  return components
})

const paginatedComponents = computed(() => {
  if (viewMode.value === 'comparison') {
    return filteredComponents.value
  }
  
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredComponents.value.slice(start, end)
})

const totalPages = computed(() => 
  Math.ceil(filteredComponents.value.length / itemsPerPage.value)
)

const startIndex = computed(() => 
  (currentPage.value - 1) * itemsPerPage.value
)

const endIndex = computed(() => 
  Math.min(startIndex.value + itemsPerPage.value, filteredComponents.value.length)
)

const visiblePages = computed(() => {
  const pages = []
  const maxVisible = 5
  const half = Math.floor(maxVisible / 2)
  
  let start = Math.max(1, currentPage.value - half)
  let end = Math.min(totalPages.value, start + maxVisible - 1)
  
  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

const hasActiveFilters = computed(() => 
  searchQuery.value.trim() !== '' || 
  selectedCategory.value !== '' ||
  selectedAudienceType.value !== '' ||
  selectedRating.value !== '' ||
  showFavoritesOnly.value || 
  showRecentOnly.value ||
  showHighRatedOnly.value
)

const currentAnnouncement = computed(() => 
  announcements.value[announcements.value.length - 1] || ''
)

// Methods
const getCategoryCount = (categoryId: ComponentCategory) => {
  return props.components.filter(c => c.category === categoryId).length
}

const getQuickFilterClasses = (filterType: string) => {
  const isActive = 
    (filterType === 'favorites' && showFavoritesOnly.value) ||
    (filterType === 'recent' && showRecentOnly.value) ||
    (filterType === 'highRated' && showHighRatedOnly.value)

  return [
    'inline-flex items-center px-4 py-2 rounded-md text-sm font-medium transition-colors',
    isActive
      ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300'
      : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
  ]
}

const getViewButtonClasses = (mode: 'grid' | 'list' | 'comparison') => [
  'px-4 py-2 text-sm font-medium border focus:outline-none focus:ring-1 focus:ring-indigo-500',
  mode === 'grid' ? 'rounded-l-md' : mode === 'list' ? '-ml-px' : 'rounded-r-md -ml-px',
  viewMode.value === mode
    ? 'bg-indigo-50 border-indigo-500 text-indigo-700 dark:bg-indigo-900 dark:border-indigo-500 dark:text-indigo-300'
    : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600'
]

const getPageButtonClasses = (page: number) => [
  'px-4 py-2 text-sm font-medium border rounded-md transition-colors',
  page === currentPage.value
    ? 'bg-indigo-50 border-indigo-500 text-indigo-600 dark:bg-indigo-900 dark:border-indigo-500 dark:text-indigo-300'
    : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600'
]

const getGridClasses = () => {
  if (viewMode.value === 'list') {
    return 'space-y-4'
  }
  
  return [
    'grid gap-6',
    'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'
  ]
}

const getContentClasses = () => {
  if (viewMode.value === 'list') {
    return 'space-y-4'
  }
  
  return [
    'grid gap-6',
    'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'
  ]
}

const getEmptyStateTitle = () => {
  if (searchQuery.value.trim()) {
    return 'No components found'
  }
  if (hasActiveFilters.value) {
    return 'No components match your filters'
  }
  return 'No components available'
}

const getEmptyStateMessage = () => {
  if (searchQuery.value.trim()) {
    return `No components match "${searchQuery.value}". Try adjusting your search terms or filters.`
  }
  if (hasActiveFilters.value) {
    return 'Try adjusting your filters to see more components.'
  }
  return 'There are no components available in this category.'
}

const setViewMode = (mode: 'grid' | 'list' | 'comparison') => {
  viewMode.value = mode
  setPreference('componentBrowserViewMode', mode)
  
  if (mode === 'comparison') {
    currentPage.value = 1
  }
  
  trackEvent('component_browser_view_mode_changed', {
    view_mode: mode
  })
}

const handleSearchInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  searchQuery.value = target.value
  debouncedSearch(target.value)
}

const clearSearch = () => {
  searchQuery.value = ''
  currentPage.value = 1
}

const toggleFavoritesOnly = () => {
  showFavoritesOnly.value = !showFavoritesOnly.value
  currentPage.value = 1
  
  trackEvent('component_browser_favorites_toggled', {
    show_favorites_only: showFavoritesOnly.value,
    favorites_count: favorites.value.length
  })
}

const toggleRecentOnly = () => {
  showRecentOnly.value = !showRecentOnly.value
  currentPage.value = 1
  
  trackEvent('component_browser_recent_toggled', {
    show_recent_only: showRecentOnly.value,
    recent_count: recentlyUsed.value.length
  })
}

const toggleHighRatedOnly = () => {
  showHighRatedOnly.value = !showHighRatedOnly.value
  currentPage.value = 1
  
  trackEvent('component_browser_high_rated_toggled', {
    show_high_rated_only: showHighRatedOnly.value
  })
}

const clearAllFilters = () => {
  searchQuery.value = ''
  selectedCategory.value = ''
  selectedAudienceType.value = ''
  selectedRating.value = ''
  showFavoritesOnly.value = false
  showRecentOnly.value = false
  showHighRatedOnly.value = false
  currentPage.value = 1
  
  announceToScreenReader('All filters cleared.')
  
  trackEvent('component_browser_filters_cleared')
}

const goToPage = (page: number) => {
  if (page < 1 || page > totalPages.value) return
  
  currentPage.value = page
  
  // Scroll to top of component grid
  const content = document.querySelector('.component-browser__content')
  if (content) {
    content.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

const isFavorite = (componentId: string) => {
  return favorites.value.includes(componentId)
}

const isRecentlyUsed = (componentId: string) => {
  return recentlyUsed.value.includes(componentId)
}

const isSelectedForComparison = (componentId: string) => {
  return selectedForComparison.value.includes(componentId)
}

const getUsageStats = (componentId: string): ComponentUsageStats => {
  return componentUsageStats.value[componentId] || {
    totalUsage: Math.floor(Math.random() * 1000),
    recentUsage: Math.floor(Math.random() * 100),
    conversionRate: Math.random() * 0.1 + 0.05,
    averageRating: Math.random() * 2 + 3,
    totalRatings: Math.floor(Math.random() * 500)
  }
}

const getComponentRating = (componentId: string): ComponentRating => {
  if (!componentRatings.value[componentId]) {
    const average = Math.random() * 2 + 3 // 3-5 stars
    const count = Math.floor(Math.random() * 500) + 10
    componentRatings.value[componentId] = {
      average,
      count,
      distribution: {
        5: Math.floor(count * 0.4),
        4: Math.floor(count * 0.3),
        3: Math.floor(count * 0.2),
        2: Math.floor(count * 0.08),
        1: Math.floor(count * 0.02)
      }
    }
  }
  return componentRatings.value[componentId]
}

const getComponentDocumentation = (componentId: string): ComponentDocumentation => {
  return componentDocumentation.value[componentId] || {
    description: 'Comprehensive documentation for this component.',
    properties: {
      'headline': 'Main heading text',
      'subheading': 'Secondary heading text',
      'description': 'Descriptive text content'
    },
    examples: [
      'Basic usage example',
      'Advanced configuration example',
      'Custom styling example'
    ],
    bestPractices: [
      'Keep headlines concise and impactful',
      'Use appropriate heading hierarchy',
      'Ensure mobile responsiveness'
    ],
    accessibility: [
      'Proper ARIA labels included',
      'Keyboard navigation supported',
      'Screen reader compatible'
    ]
  }
}

const getComponentExamples = (componentId: string): string[] => {
  return [
    'Example 1: Basic implementation',
    'Example 2: Advanced configuration',
    'Example 3: Custom styling'
  ]
}

const getSelectedComponents = () => {
  return selectedForComparison.value
    .map(id => props.components.find(c => c.id === id))
    .filter(Boolean) as Component[]
}

const handlePreview = (component: Component) => {
  previewComponent.value = component
  showPreviewModal.value = true
  
  trackEvent('component_browser_preview_opened', {
    component_id: component.id,
    component_name: component.name,
    component_category: component.category
  })
  
  emit('component-preview', component)
}

const closePreviewModal = () => {
  showPreviewModal.value = false
  previewComponent.value = null
}

const handleViewDetails = (component: Component) => {
  detailComponent.value = component
  showDetailModal.value = true
  
  trackEvent('component_browser_details_opened', {
    component_id: component.id,
    component_name: component.name,
    component_category: component.category
  })
}

const closeDetailModal = () => {
  showDetailModal.value = false
  detailComponent.value = null
}

const handleFavorite = (component: Component) => {
  const index = favorites.value.indexOf(component.id)
  
  if (index > -1) {
    favorites.value.splice(index, 1)
    announceToScreenReader(`${component.name} removed from favorites.`)
  } else {
    favorites.value.push(component.id)
    announceToScreenReader(`${component.name} added to favorites.`)
  }
  
  setPreference('componentBrowserFavorites', favorites.value)
  
  trackEvent('component_browser_favorite_toggled', {
    component_id: component.id,
    component_name: component.name,
    is_favorite: index === -1
  })
}

const handleSelect = (component: Component) => {
  // Add to recently used
  const index = recentlyUsed.value.indexOf(component.id)
  if (index > -1) {
    recentlyUsed.value.splice(index, 1)
  }
  recentlyUsed.value.unshift(component.id)
  
  // Keep only last 20 recently used
  if (recentlyUsed.value.length > 20) {
    recentlyUsed.value = recentlyUsed.value.slice(0, 20)
  }
  
  setPreference('componentBrowserRecentlyUsed', recentlyUsed.value)
  
  trackEvent('component_browser_selected', {
    component_id: component.id,
    component_name: component.name,
    component_category: component.category,
    selection_method: 'click'
  })
  
  emit('component-selected', component)
}

const handleDragStart = (component: Component) => {
  trackEvent('component_browser_drag_started', {
    component_id: component.id,
    component_name: component.name,
    component_category: component.category
  })
  
  emit('drag-initiated', component)
}

const handleToggleComparison = (component: Component) => {
  const index = selectedForComparison.value.indexOf(component.id)
  
  if (index > -1) {
    selectedForComparison.value.splice(index, 1)
    announceToScreenReader(`${component.name} removed from comparison.`)
  } else if (selectedForComparison.value.length < props.maxComparison) {
    selectedForComparison.value.push(component.id)
    announceToScreenReader(`${component.name} added to comparison.`)
  }
  
  trackEvent('component_browser_comparison_toggled', {
    component_id: component.id,
    component_name: component.name,
    is_selected: index === -1,
    total_selected: selectedForComparison.value.length
  })
}

const handleRemoveFromComparison = (component: Component) => {
  const index = selectedForComparison.value.indexOf(component.id)
  if (index > -1) {
    selectedForComparison.value.splice(index, 1)
    announceToScreenReader(`${component.name} removed from comparison.`)
  }
}

const clearComparison = () => {
  selectedForComparison.value = []
  announceToScreenReader('Comparison selection cleared.')
}

const announceToScreenReader = (message: string) => {
  announcements.value.push(message)
  
  // Remove announcement after 3 seconds
  setTimeout(() => {
    const index = announcements.value.indexOf(message)
    if (index > -1) {
      announcements.value.splice(index, 1)
    }
  }, 3000)
}

// Lifecycle
onMounted(async () => {
  // Load user preferences
  viewMode.value = getPreference('componentBrowserViewMode', 'grid')
  favorites.value = getPreference('componentBrowserFavorites', [])
  recentlyUsed.value = getPreference('componentBrowserRecentlyUsed', [])
  
  // Simulate loading
  setTimeout(() => {
    isLoading.value = false
  }, 500)
  
  // Track component browser view
  trackEvent('component_browser_viewed', {
    total_components: props.components.length,
    initial_category: selectedCategory.value
  })
})

// Watch for component changes
watch(() => props.components, () => {
  currentPage.value = 1
}, { deep: true })

// Watch for search query changes
watch(searchQuery, () => {
  currentPage.value = 1
})

// Watch for filter changes
watch([selectedCategory, selectedAudienceType, selectedRating, showFavoritesOnly, showRecentOnly, showHighRatedOnly], () => {
  currentPage.value = 1
})
</script>

<style scoped>
.component-browser {
  container-type: inline-size;
}

.component-browser__header {
  @apply mb-8;
}

.component-browser__search-filters {
  @apply mb-8;
}

.component-browser__content {
  @apply min-h-[600px];
}

.comparison-view {
  @apply min-h-[400px];
}

.comparison-table-container {
  @apply overflow-x-auto;
}

/* Loading state */
.component-browser--loading {
  @apply pointer-events-none;
}

/* Comparison mode */
.component-browser--comparison .component-browser__content {
  @apply min-h-[500px];
}

/* Focus styles for better accessibility */
.component-browser button:focus,
.component-browser input:focus,
.component-browser select:focus {
  @apply outline-none ring-2 ring-indigo-500 ring-offset-2;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .component-browser {
    @apply contrast-125;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .component-browser *,
  .component-browser *::before,
  .component-browser *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Container queries for responsive design */
@container (max-width: 640px) {
  .component-browser__header {
    @apply space-y-4;
  }
  
  .component-browser__search-filters {
    @apply p-4;
  }
}

@container (min-width: 1024px) {
  .component-browser__content {
    @apply min-h-[700px];
  }
}
</style>