<template>
  <div 
    class="component-library"
    :class="containerClasses"
    role="application"
    :aria-label="ariaLabel"
  >
    <!-- Header Section -->
    <header class="component-library__header">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Component Library
          </h1>
          <p class="text-gray-600 dark:text-gray-300 mt-1">
            Browse and customize components for your pages
          </p>
        </div>
        
        <!-- Search Bar -->
        <div class="relative flex-1 max-w-md">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <Icon 
              name="search" 
              class="h-5 w-5 text-gray-400" 
              aria-hidden="true" 
            />
          </div>
          <input
            v-model="searchQuery"
            type="search"
            placeholder="Search components..."
            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white dark:bg-gray-700 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white sm:text-sm"
            :aria-label="searchAriaLabel"
            @input="handleSearchInput"
            @keydown.escape="clearSearch"
          />
          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="absolute inset-y-0 right-0 pr-3 flex items-center"
            aria-label="Clear search"
          >
            <Icon 
              name="x" 
              class="h-4 w-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" 
            />
          </button>
        </div>
      </div>
    </header>

    <!-- Category Navigation -->
    <nav 
      class="component-library__nav"
      role="tablist"
      :aria-label="navAriaLabel"
    >
      <div class="border-b border-gray-200 dark:border-gray-700">
        <div class="flex space-x-8 overflow-x-auto scrollbar-hide">
          <button
            v-for="category in categories"
            :key="category.id"
            @click="setActiveCategory(category.id)"
            :class="getCategoryButtonClasses(category.id)"
            :aria-selected="activeCategory === category.id"
            :aria-controls="`panel-${category.id}`"
            role="tab"
            :tabindex="activeCategory === category.id ? 0 : -1"
            @keydown="handleCategoryKeydown"
          >
            <Icon 
              :name="category.icon" 
              class="h-5 w-5 mr-2" 
              :aria-hidden="true" 
            />
            <span class="whitespace-nowrap">{{ category.name }}</span>
            <span 
              v-if="getCategoryCount(category.id) > 0"
              class="ml-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 py-0.5 px-2 rounded-full text-xs font-medium"
              :aria-label="`${getCategoryCount(category.id)} components`"
            >
              {{ getCategoryCount(category.id) }}
            </span>
          </button>
        </div>
      </div>
    </nav>

    <!-- Filters and Actions -->
    <div class="component-library__filters flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 py-4">
      <div class="flex items-center space-x-4">
        <!-- View Toggle -->
        <div class="flex items-center space-x-2">
          <span class="text-sm text-gray-700 dark:text-gray-300">View:</span>
          <div class="flex rounded-md shadow-sm" role="group" aria-label="View options">
            <button
              @click="setViewMode('grid')"
              :class="getViewButtonClasses('grid')"
              :aria-pressed="viewMode === 'grid'"
              aria-label="Grid view"
            >
              <Icon name="grid" class="h-4 w-4" />
            </button>
            <button
              @click="setViewMode('list')"
              :class="getViewButtonClasses('list')"
              :aria-pressed="viewMode === 'list'"
              aria-label="List view"
            >
              <Icon name="list" class="h-4 w-4" />
            </button>
          </div>
        </div>

        <!-- Sort Options -->
        <select
          v-model="sortBy"
          class="text-sm border border-gray-300 dark:border-gray-600 rounded-md px-3 py-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
          aria-label="Sort components by"
        >
          <option value="name">Sort by Name</option>
          <option value="category">Sort by Category</option>
          <option value="recent">Recently Used</option>
          <option value="popular">Most Popular</option>
        </select>
      </div>

      <!-- Quick Actions -->
      <div class="flex items-center space-x-2">
        <button
          @click="toggleFavoritesOnly"
          :class="getFavoritesButtonClasses"
          :aria-pressed="showFavoritesOnly"
          aria-label="Show favorites only"
        >
          <Icon name="heart" class="h-4 w-4 mr-1" />
          Favorites
        </button>
        
        <button
          @click="clearRecentlyUsed"
          class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white px-3 py-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          :disabled="recentlyUsed.length === 0"
          aria-label="Clear recently used components"
        >
          Clear Recent
        </button>
      </div>
    </div>

    <!-- Component Grid/List -->
    <main class="component-library__content">
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
        class="text-center py-12"
        role="status"
      >
        <div class="max-w-md mx-auto">
          <Icon 
            name="search" 
            class="mx-auto h-12 w-12 text-gray-400 mb-4" 
            aria-hidden="true" 
          />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
            {{ getEmptyStateTitle() }}
          </h3>
          <p class="text-gray-500 dark:text-gray-400 mb-4">
            {{ getEmptyStateMessage() }}
          </p>
          <button
            v-if="hasActiveFilters"
            @click="clearAllFilters"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-600 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:text-indigo-400 dark:bg-indigo-900 dark:hover:bg-indigo-800"
          >
            Clear filters
          </button>
        </div>
      </div>

      <!-- Components Display -->
      <div 
        v-else
        :class="getContentClasses()"
        role="tabpanel"
        :id="`panel-${activeCategory}`"
        :aria-labelledby="`tab-${activeCategory}`"
      >
        <ComponentCard
          v-for="component in paginatedComponents"
          :key="component.id"
          :component="component"
          :view-mode="viewMode"
          :is-favorite="isFavorite(component.id)"
          :recently-used="isRecentlyUsed(component.id)"
          @preview="handlePreview"
          @favorite="handleFavorite"
          @select="handleSelect"
          @drag-start="handleDragStart"
        />
      </div>

      <!-- Pagination -->
      <div 
        v-if="totalPages > 1"
        class="mt-8 flex items-center justify-between"
      >
        <div class="text-sm text-gray-700 dark:text-gray-300">
          Showing {{ startIndex + 1 }} to {{ endIndex }} of {{ filteredComponents.length }} components
        </div>
        
        <nav class="flex items-center space-x-2" aria-label="Pagination">
          <button
            @click="goToPage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
            aria-label="Previous page"
          >
            Previous
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
            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
            aria-label="Next page"
          >
            Next
          </button>
        </nav>
      </div>
    </main>

    <!-- Component Preview Modal -->
    <ComponentPreviewModal
      v-if="previewComponent"
      :component="previewComponent"
      :is-open="showPreview"
      @close="closePreview"
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
import type { ComponentCategory, Component } from '@/types/components'
import { useDebounce } from '@/composables/useDebounce'
import { useAnalytics } from '@/composables/useAnalytics'
import { useUserPreferences } from '@/composables/useUserPreferences'

// Import child components
import Icon from '@/components/Common/Icon.vue'
import ComponentCard from './ComponentCard.vue'
import ComponentSkeleton from './ComponentSkeleton.vue'
import ComponentPreviewModal from './ComponentPreviewModal.vue'

interface Props {
  components?: Component[]
  initialCategory?: ComponentCategory
  sampleData?: boolean
}

interface Emits {
  (e: 'component-selected', component: Component): void
  (e: 'component-preview', component: Component): void
  (e: 'category-changed', category: ComponentCategory): void
}

const props = withDefaults(defineProps<Props>(), {
  components: () => [],
  initialCategory: 'hero',
  sampleData: false
})

const emit = defineEmits<Emits>()

// Composables
const { trackEvent, trackUserAction } = useAnalytics()
const { getPreference, setPreference } = useUserPreferences()

// Reactive state
const isLoading = ref(true)
const searchQuery = ref('')
const activeCategory = ref<ComponentCategory>(props.initialCategory)
const viewMode = ref<'grid' | 'list'>('grid')
const sortBy = ref<'name' | 'category' | 'recent' | 'popular'>('name')
const showFavoritesOnly = ref(false)
const currentPage = ref(1)
const itemsPerPage = ref(12)

// Component state
const favorites = ref<string[]>([])
const recentlyUsed = ref<string[]>([])
const previewComponent = ref<Component | null>(null)
const showPreview = ref(false)
const announcements = ref<string[]>([])

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
  // Reset to first page when searching
  currentPage.value = 1
  
  // Track search
  if (query.trim()) {
    trackEvent('component_search', {
      query: query.trim(),
      category: activeCategory.value,
      results_count: filteredComponents.value.length
    })
  }
}, 300)

// Computed properties
const containerClasses = computed(() => [
  'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8',
  {
    'component-library--loading': isLoading.value
  }
])

const ariaLabel = computed(() => 
  `Component library with ${props.components.length} components across ${categories.value.length} categories`
)

const searchAriaLabel = computed(() => 
  `Search ${filteredComponents.value.length} components`
)

const navAriaLabel = computed(() => 
  'Component categories'
)

const filteredComponents = computed(() => {
  let components = [...props.components]

  // Filter by category
  if (activeCategory.value !== 'all') {
    components = components.filter(c => c.category === activeCategory.value)
  }

  // Filter by search query
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim()
    components = components.filter(c => 
      c.name.toLowerCase().includes(query) ||
      c.description?.toLowerCase().includes(query) ||
      c.type.toLowerCase().includes(query)
    )
  }

  // Filter by favorites
  if (showFavoritesOnly.value) {
    components = components.filter(c => favorites.value.includes(c.id))
  }

  // Sort components
  components.sort((a, b) => {
    switch (sortBy.value) {
      case 'name':
        return a.name.localeCompare(b.name)
      case 'category':
        return a.category.localeCompare(b.category)
      case 'recent':
        const aIndex = recentlyUsed.value.indexOf(a.id)
        const bIndex = recentlyUsed.value.indexOf(b.id)
        if (aIndex === -1 && bIndex === -1) return a.name.localeCompare(b.name)
        if (aIndex === -1) return 1
        if (bIndex === -1) return -1
        return aIndex - bIndex
      case 'popular':
        // For now, sort by name as we don't have usage statistics
        return a.name.localeCompare(b.name)
      default:
        return 0
    }
  })

  return components
})

const paginatedComponents = computed(() => {
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
  searchQuery.value.trim() !== '' || showFavoritesOnly.value
)

const currentAnnouncement = computed(() => 
  announcements.value[announcements.value.length - 1] || ''
)

const getFavoritesButtonClasses = computed(() => [
  'text-sm px-3 py-1 rounded-md transition-colors flex items-center',
  showFavoritesOnly.value
    ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'
    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700'
])

// Methods
const getCategoryButtonClasses = (categoryId: ComponentCategory) => [
  'flex items-center py-2 px-1 border-b-2 font-medium text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
  activeCategory.value === categoryId
    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
]

const getViewButtonClasses = (mode: 'grid' | 'list') => [
  'px-3 py-2 text-sm font-medium border focus:outline-none focus:ring-1 focus:ring-indigo-500',
  mode === 'grid' ? 'rounded-l-md' : 'rounded-r-md -ml-px',
  viewMode.value === mode
    ? 'bg-indigo-50 border-indigo-500 text-indigo-700 dark:bg-indigo-900 dark:border-indigo-500 dark:text-indigo-300'
    : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600'
]

const getPageButtonClasses = (page: number) => [
  'px-3 py-2 text-sm font-medium border rounded-md transition-colors',
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

const getCategoryCount = (categoryId: ComponentCategory) => {
  return props.components.filter(c => c.category === categoryId).length
}

const getEmptyStateTitle = () => {
  if (searchQuery.value.trim()) {
    return 'No components found'
  }
  if (showFavoritesOnly.value) {
    return 'No favorite components'
  }
  return 'No components available'
}

const getEmptyStateMessage = () => {
  if (searchQuery.value.trim()) {
    return `No components match "${searchQuery.value}". Try adjusting your search terms.`
  }
  if (showFavoritesOnly.value) {
    return 'You haven\'t favorited any components yet. Click the heart icon on components to add them to your favorites.'
  }
  return 'There are no components available in this category.'
}

const setActiveCategory = (categoryId: ComponentCategory) => {
  if (activeCategory.value === categoryId) return
  
  activeCategory.value = categoryId
  currentPage.value = 1
  
  // Track category change
  trackEvent('component_category_changed', {
    category: categoryId,
    component_count: getCategoryCount(categoryId)
  })
  
  // Announce category change
  announceToScreenReader(`Switched to ${categories.value.find(c => c.id === categoryId)?.name} category. ${getCategoryCount(categoryId)} components available.`)
  
  emit('category-changed', categoryId)
}

const setViewMode = (mode: 'grid' | 'list') => {
  viewMode.value = mode
  setPreference('componentLibraryViewMode', mode)
  
  trackEvent('component_view_mode_changed', {
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
  
  trackEvent('component_favorites_toggled', {
    show_favorites_only: showFavoritesOnly.value,
    favorites_count: favorites.value.length
  })
}

const clearRecentlyUsed = () => {
  recentlyUsed.value = []
  setPreference('componentLibraryRecentlyUsed', [])
  
  announceToScreenReader('Recently used components cleared.')
}

const clearAllFilters = () => {
  searchQuery.value = ''
  showFavoritesOnly.value = false
  currentPage.value = 1
  
  announceToScreenReader('All filters cleared.')
}

const goToPage = (page: number) => {
  if (page < 1 || page > totalPages.value) return
  
  currentPage.value = page
  
  // Scroll to top of component grid
  const content = document.querySelector('.component-library__content')
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

const handlePreview = (component: Component) => {
  previewComponent.value = component
  showPreview.value = true
  
  trackEvent('component_preview_opened', {
    component_id: component.id,
    component_name: component.name,
    component_category: component.category
  })
  
  emit('component-preview', component)
}

const closePreview = () => {
  showPreview.value = false
  previewComponent.value = null
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
  
  setPreference('componentLibraryFavorites', favorites.value)
  
  trackEvent('component_favorite_toggled', {
    component_id: component.id,
    component_name: component.name,
    is_favorite: !index > -1
  })
}

const handleSelect = (component: Component) => {
  // Add to recently used
  const index = recentlyUsed.value.indexOf(component.id)
  if (index > -1) {
    recentlyUsed.value.splice(index, 1)
  }
  recentlyUsed.value.unshift(component.id)
  
  // Keep only last 10 recently used
  if (recentlyUsed.value.length > 10) {
    recentlyUsed.value = recentlyUsed.value.slice(0, 10)
  }
  
  setPreference('componentLibraryRecentlyUsed', recentlyUsed.value)
  
  trackEvent('component_selected', {
    component_id: component.id,
    component_name: component.name,
    component_category: component.category,
    selection_method: 'click'
  })
  
  emit('component-selected', component)
}

const handleDragStart = (component: Component) => {
  trackEvent('component_drag_started', {
    component_id: component.id,
    component_name: component.name,
    component_category: component.category
  })
}

const handleCategoryKeydown = (event: KeyboardEvent) => {
  const currentIndex = categories.value.findIndex(c => c.id === activeCategory.value)
  let newIndex = currentIndex
  
  switch (event.key) {
    case 'ArrowLeft':
      event.preventDefault()
      newIndex = currentIndex > 0 ? currentIndex - 1 : categories.value.length - 1
      break
    case 'ArrowRight':
      event.preventDefault()
      newIndex = currentIndex < categories.value.length - 1 ? currentIndex + 1 : 0
      break
    case 'Home':
      event.preventDefault()
      newIndex = 0
      break
    case 'End':
      event.preventDefault()
      newIndex = categories.value.length - 1
      break
    default:
      return
  }
  
  setActiveCategory(categories.value[newIndex].id)
  
  // Focus the new tab
  nextTick(() => {
    const newTab = document.querySelector(`[aria-controls="panel-${categories.value[newIndex].id}"]`) as HTMLElement
    if (newTab) {
      newTab.focus()
    }
  })
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
  viewMode.value = getPreference('componentLibraryViewMode', 'grid')
  favorites.value = getPreference('componentLibraryFavorites', [])
  recentlyUsed.value = getPreference('componentLibraryRecentlyUsed', [])
  
  // Simulate loading
  setTimeout(() => {
    isLoading.value = false
  }, 500)
  
  // Track component library view
  trackEvent('component_library_viewed', {
    total_components: props.components.length,
    initial_category: activeCategory.value
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
</script>

<style scoped>
.component-library {
  container-type: inline-size;
}

.component-library__header {
  @apply mb-6;
}

.component-library__nav {
  @apply mb-6;
}

.component-library__filters {
  @apply border-b border-gray-200 dark:border-gray-700 pb-4 mb-6;
}

.component-library__content {
  @apply min-h-[400px];
}

/* Scrollbar hiding for category navigation */
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

/* Loading state */
.component-library--loading {
  @apply pointer-events-none;
}

/* Focus styles for better accessibility */
.component-library button:focus,
.component-library input:focus,
.component-library select:focus {
  @apply outline-none ring-2 ring-indigo-500 ring-offset-2;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .component-library {
    @apply contrast-125;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .component-library *,
  .component-library *::before,
  .component-library *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Container queries for responsive design */
@container (max-width: 640px) {
  .component-library__header {
    @apply space-y-4;
  }
  
  .component-library__filters {
    @apply space-y-4;
  }
}

@container (min-width: 1024px) {
  .component-library__content {
    @apply min-h-[600px];
  }
}
</style>