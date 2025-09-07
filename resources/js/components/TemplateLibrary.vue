<template>
  <div class="template-library">
    <!-- Header Section -->
    <div class="library-header">
      <div class="header-content">
        <h1 class="library-title">Template Library</h1>
        <p class="library-subtitle">Choose from our collection of professionally designed templates</p>
      </div>

      <!-- View Toggle -->
      <div class="view-controls">
        <div class="view-toggle">
          <button
            @click="viewMode = 'grid'"
            :class="{ 'active': viewMode === 'grid' }"
            class="view-btn"
            :aria-pressed="viewMode === 'grid'"
            aria-label="Grid view"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
            </svg>
          </button>
          <button
            @click="viewMode = 'list'"
            :class="{ 'active': viewMode === 'list' }"
            class="view-btn"
            :aria-pressed="viewMode === 'list'"
            aria-label="List view"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="search-filters">
      <!-- Search Bar -->
      <div class="search-bar">
        <label for="template-search" class="sr-only">Search templates</label>
        <div class="relative">
          <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            id="template-search"
            v-model="searchQuery"
            type="text"
            placeholder="Search templates..."
            class="search-input"
            @input="debouncedSearch"
          />
        </div>
      </div>

      <!-- Filter Controls -->
      <div class="filter-controls">
        <!-- Category Filter -->
        <div class="filter-group">
          <label class="filter-label">Category</label>
          <select
            v-model="selectedCategory"
            class="filter-select"
            @change="applyFilters"
          >
            <option value="">All Categories</option>
            <option v-for="category in categories" :key="category.value" :value="category.value">
              {{ category.label }} ({{ category.count }})
            </option>
          </select>
        </div>

        <!-- Audience Type Filter -->
        <div class="filter-group">
          <label class="filter-label">Audience</label>
          <select
            v-model="selectedAudienceType"
            class="filter-select"
            @change="applyFilters"
          >
            <option value="">All Audiences</option>
            <option value="individual">Individual</option>
            <option value="institution">Institution</option>
            <option value="employer">Employer</option>
            <option value="general">General</option>
          </select>
        </div>

        <!-- Campaign Type Filter -->
        <div class="filter-group" v-if="campaignTypes.length > 0">
          <label class="filter-label">Campaign</label>
          <select
            v-model="selectedCampaignType"
            class="filter-select"
            @change="applyFilters"
            :aria-label="'Filter by campaign type'"
          >
            <option value="">All Campaigns</option>
            <option v-for="campaign in campaignTypes" :key="campaign.value" :value="campaign.value">
              {{ campaign.label }} ({{ campaign.count }})
            </option>
          </select>
        </div>

        <!-- Premium Filter -->
        <div class="filter-group">
          <label class="filter-label">Premium</label>
          <select
            v-model="showPremiumOnly"
            class="filter-select"
            @change="applyFilters"
            :aria-label="'Filter by premium status'"
          >
            <option value="">All Templates</option>
            <option value="false">Free Templates</option>
            <option value="true">Premium Only</option>
          </select>
        </div>

        <!-- Recent Templates -->
        <div class="filter-group" v-if="showFavorites">
          <label class="filter-label">Time Filter</label>
          <select
            v-model="showRecentsOnly"
            class="filter-select"
            @change="applyFilters"
            :aria-label="'Filter by recency'"
          >
            <option value="">All Time</option>
            <option value="true">Recent Templates</option>
          </select>
        </div>

        <!-- Favorites Filter -->
        <div class="filter-group" v-if="showFavorites">
          <label class="filter-label">Favorites</label>
          <select
            v-model="showFavoritesOnly"
            class="filter-select"
            @change="applyFilters"
            :aria-label="'Show favorite templates'"
          >
            <option value="">All Templates</option>
            <option value="true">Favorites Only</option>
          </select>
        </div>

        <!-- Clear Filters -->
        <button
          @click="clearFilters"
          class="clear-filters-btn"
        >
          Clear Filters
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="loading-spinner"></div>
      <p>Loading templates...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <div class="error-content">
        <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        <h3 class="error-title">Error Loading Templates</h3>
        <p class="error-message">{{ error }}</p>
        <button @click="fetchTemplates" class="retry-btn">
          Try Again
        </button>
      </div>
    </div>

    <!-- Templates Grid/List -->
    <div v-else class="templates-container">
      <!-- Active Filters Display -->
      <div v-if="hasActiveFilters" class="active-filters">
        <span class="filters-label">Active filters:</span>
        <div class="filter-tags">
          <span v-if="searchQuery" class="filter-tag">
            Search: "{{ searchQuery }}"
            <button @click="searchQuery = ''" class="filter-remove">&times;</button>
          </span>
          <span v-if="selectedCategory" class="filter-tag">
            Category: {{ categories.find(c => c.value === selectedCategory)?.label }}
            <button @click="selectedCategory = ''" class="filter-remove">&times;</button>
          </span>
          <span v-if="selectedAudienceType" class="filter-tag">
            Audience: {{ selectedAudienceType }}
            <button @click="selectedAudienceType = ''" class="filter-remove">&times;</button>
          </span>
          <span v-if="showPremiumOnly" class="filter-tag">
            {{ showPremiumOnly === 'true' ? 'Premium Only' : 'Free Only' }}
            <button @click="showPremiumOnly = ''" class="filter-remove">&times;</button>
          </span>
        </div>
        <button @click="clearFilters" class="clear-all-btn">
          Clear All
        </button>
      </div>

      <!-- Templates Display -->
      <div v-if="templates.data.length > 0" :class="templatesContainerClasses">
        <template-card
          v-for="template in templates.data"
          :key="template.id"
          :template="template"
          :view-mode="viewMode"
          @preview="handlePreview"
          @select="handleSelect"
        />
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state">
        <div class="empty-content">
          <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h3 class="empty-title">No Templates Found</h3>
          <p class="empty-message">
            {{ searchQuery || hasActiveFilters
              ? 'Try adjusting your search criteria or filters'
              : 'No templates are available at this time'
            }}
          </p>
          <button v-if="searchQuery || hasActiveFilters" @click="clearFilters" class="reset-btn">
            Clear Search & Filters
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="templates.total > templates.perPage" class="pagination-container">
        <div class="pagination">
          <button
            @click="goToPage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="pagination-btn"
          >
            Previous
          </button>

          <div class="pagination-numbers">
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="goToPage(page)"
              :class="{ 'active': page === currentPage }"
              class="pagination-number"
            >
              {{ page }}
            </button>
          </div>

          <button
            @click="goToPage(currentPage + 1)"
            :disabled="currentPage === templates.lastPage"
            class="pagination-btn"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { templateService } from '@/services/TemplateService'
import type {
  Template,
  TemplateCollection,
  TemplateFilterOptions,
  TemplateSearchParams,
  ViewMode,
  TemplateAudienceType,
  TemplateCategory,
  TemplateCampaignType,
  ViewportType
} from '@/types/components'

// Props
interface Props {
  initialCategory?: string
  initialAudienceType?: string
  enablePreview?: boolean
  enableBrandCustomization?: boolean
  defaultViewport?: ViewportType
  showFavorites?: boolean
  enableKeyboardNavigation?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  initialCategory: '',
  initialAudienceType: '',
  enablePreview: true,
  enableBrandCustomization: false,
  defaultViewport: 'desktop' as ViewportType,
  showFavorites: true,
  enableKeyboardNavigation: true
})

// Emits
const emit = defineEmits<{
  templateSelected: [template: Template]
  templatePreviewed: [template: Template, viewport: ViewportType]
  templateCustomized: [template: Template, customizations: any]
  libraryFiltersChanged: [filters: TemplateFilterOptions]
}>()

// No template store needed for now

// Reactive state
const viewMode = ref<ViewMode>('grid')
const loading = ref(false)
const error = ref('')
const templates = ref<TemplateCollection>({
  data: [],
  total: 0,
  page: 1,
  perPage: 12,
  lastPage: 1
})

// Search and filters
const searchQuery = ref('')
const selectedCategory = ref('')
const selectedAudienceType = ref('')
const selectedCampaignType = ref('')
const showPremiumOnly = ref('')
const showRecentsOnly = ref('')
const showFavoritesOnly = ref('')

// Filter options
const categories = ref<Array<{ value: string; label: string; count: number }>>([])
const campaignTypes = ref<Array<{ value: string; label: string; count: number }>>([])

// Keyboard navigation
const focusedTemplateIndex = ref(-1)
const templatesContainer = ref<HTMLElement>()

// Computed properties
const currentPage = computed(() => templates.value.page)

const hasActiveFilters = computed(() => {
  return searchQuery.value ||
         selectedCategory.value ||
         selectedAudienceType.value ||
         selectedCampaignType.value ||
         showPremiumOnly.value ||
         showRecentsOnly.value ||
         showFavoritesOnly.value
})

const filteredTemplates = computed(() => {
  if (!hasActiveFilters.value) return templates.value.data

  return templates.value.data.filter(template => {
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase()
      const matchesSearch = template.name.toLowerCase().includes(query) ||
                          template.description?.toLowerCase().includes(query) ||
                          template.tags.some(tag => tag.toLowerCase().includes(query))
      if (!matchesSearch) return false
    }

    if (selectedCategory.value && template.category !== selectedCategory.value) {
      return false
    }

    if (selectedAudienceType.value && template.audienceType !== selectedAudienceType.value) {
      return false
    }

    if (selectedCampaignType.value && template.campaignType !== selectedCampaignType.value) {
      return false
    }

    if (showPremiumOnly.value === 'true' && !template.isPremium) {
      return false
    }

    if (showPremiumOnly.value === 'false' && template.isPremium) {
      return false
    }

    if (showRecentsOnly.value === 'true' && !template.lastUsedAt) {
      return false
    }

    return true
  })
})

const templatesContainerClasses = computed(() => {
  const base = ['templates-grid']
  if (viewMode.value === 'list') {
    base.push('templates-list')
  }
  return base.join(' ')
})

const visiblePages = computed(() => {
  const totalPages = templates.value.lastPage
  const current = templates.value.page
  const pages: number[] = []

  // Always show first page
  if (totalPages > 0) {
    pages.push(1)
  }

  // Calculate range around current page
  const startPage = Math.max(2, current - 2)
  const endPage = Math.min(totalPages - 1, current + 2)

  // Add ellipsis after first page if needed
  if (startPage > 2) {
    pages.push(-1) // -1 represents ellipsis
  }

  // Add pages in range
  for (let i = startPage; i <= endPage; i++) {
    pages.push(i)
  }

  // Add ellipsis before last page if needed
  if (endPage < totalPages - 1) {
    pages.push(-2) // -2 represents ellipsis
  }

  // Always show last page
  if (totalPages > 1) {
    pages.push(totalPages)
  }

  return pages
})

// activeFiltersCount is used for display purposes - uncomment when needed
// const activeFiltersCount = computed(() => {
//   return [searchQuery.value, selectedCategory.value, selectedAudienceType.value,
//           selectedCampaignType.value, showPremiumOnly.value].filter(Boolean).length
// })

// Methods
const debouncedSearch = useDebounceFn(() => {
  applyFilters()
}, 300)

const applyFilters = () => {
  templates.value.page = 1
  emit('libraryFiltersChanged', getCurrentFilters())
  fetchTemplates()
}

const clearFilters = () => {
  searchQuery.value = ''
  selectedCategory.value = ''
  selectedAudienceType.value = ''
  selectedCampaignType.value = ''
  showPremiumOnly.value = ''
  showRecentsOnly.value = ''
  showFavoritesOnly.value = ''
  templates.value.page = 1
  applyFilters()
}

const getCurrentFilters = (): TemplateFilterOptions => ({
  category: selectedCategory.value ? [selectedCategory.value as TemplateCategory] : undefined,
  audienceType: selectedAudienceType.value ? [selectedAudienceType.value as TemplateAudienceType] : undefined,
  campaignType: selectedCampaignType.value ? [selectedCampaignType.value as TemplateCampaignType] : undefined,
  isPremium: showPremiumOnly.value ? showPremiumOnly.value === 'true' : undefined,
  searchQuery: searchQuery.value || undefined,
})

const goToPage = (page: number) => {
  if (page >= 1 && page <= templates.value.lastPage) {
    templates.value.page = page
    fetchTemplates()
    scrollToTemplates()
  }
}

const fetchTemplates = async () => {
  try {
    loading.value = true
    error.value = ''

    const filters = getCurrentFilters()
    const params: TemplateSearchParams = {
      filters,
      page: templates.value.page,
      perPage: templates.value.perPage,
      sortBy: showRecentsOnly.value ? 'last_used_at' : 'name',
      sortOrder: showRecentsOnly.value ? 'desc' : 'asc'
    }

    const result = await templateService.fetchTemplates(params)
    templates.value = result

    // Reset focus index when templates change
    focusedTemplateIndex.value = -1
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load templates'
    console.error('Error fetching templates:', err)
  } finally {
    loading.value = false
  }
}

const fetchCategories = async () => {
  try {
    // Get categories from template service and/or dynamically from current templates
    const dynamicCategories = await templateService.fetchCategories()
    categories.value = dynamicCategories

    // Generate campaign types from current data
    const campaigns = [
      { value: 'onboarding', label: 'Onboarding', count: templates.value.data.filter(t => t.campaignType === 'onboarding').length },
      { value: 'event_promotion', label: 'Event Promotion', count: templates.value.data.filter(t => t.campaignType === 'event_promotion').length },
      { value: 'donation', label: 'Donation', count: templates.value.data.filter(t => t.campaignType === 'donation').length },
      { value: 'networking', label: 'Networking', count: templates.value.data.filter(t => t.campaignType === 'networking').length },
      { value: 'career_services', label: 'Career Services', count: templates.value.data.filter(t => t.campaignType === 'career_services').length },
      { value: 'marketing', label: 'Marketing', count: templates.value.data.filter(t => t.campaignType === 'marketing').length },
    ].filter(c => c.count > 0)
    campaignTypes.value = campaigns
  } catch (err) {
    console.error('Error fetching filter options:', err)
  }
}

const handlePreview = async (template: Template) => {
  try {
    // Generate responsive preview using the enhanced service
    await templateService.generateResponsivePreview(template.id, {})
    emit('templatePreviewed', template, props.defaultViewport)
  } catch (error) {
    console.error('Error generating preview:', error)
    // Still emit the event fallback
    emit('templatePreviewed', template, props.defaultViewport)
  }
}

const handleSelect = async (template: Template) => {
  // Update usage count
  try {
    await templateService.updateTemplateUsage(template.id)
  } catch (err) {
    console.warn('Failed to update template usage:', err)
  }
  emit('templateSelected', template)
}

const handleKeyboardNavigation = (event: KeyboardEvent) => {
  if (!props.enableKeyboardNavigation || !filteredTemplates.value.length) return

  const templatesCount = filteredTemplates.value.length
  let newIndex = focusedTemplateIndex.value

  switch (event.key) {
    case 'ArrowRight':
      event.preventDefault()
      newIndex = Math.min(newIndex + 1, templatesCount - 1)
      break
    case 'ArrowLeft':
      event.preventDefault()
      newIndex = Math.max(newIndex - 1, 0)
      break
    case 'ArrowDown':
      event.preventDefault()
      newIndex = Math.min(newIndex + (viewMode.value === 'grid' ? 4 : 1), templatesCount - 1)
      break
    case 'ArrowUp':
      event.preventDefault()
      newIndex = Math.max(newIndex - (viewMode.value === 'grid' ? 4 : 1), 0)
      break
    case 'Home':
      event.preventDefault()
      newIndex = 0
      break
    case 'End':
      event.preventDefault()
      newIndex = templatesCount - 1
      break
    default:
      return
  }

  if (newIndex !== focusedTemplateIndex.value) {
    focusedTemplateIndex.value = newIndex
    scrollToFocusedTemplate()
  }
}


const scrollToTemplates = () => {
  nextTick(() => {
    const container = templatesContainer.value
    if (container) {
      container.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  })
}

const scrollToFocusedTemplate = () => {
  nextTick(() => {
    const container = templatesContainer.value
    if (container && focusedTemplateIndex.value >= 0) {
      const templateElement = container.querySelector(
        `[data-template-index="${focusedTemplateIndex.value}"]`
      ) as HTMLElement
      if (templateElement) {
        templateElement.focus({ preventScroll: false })
        templateElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
      }
    }
  })
}

// Watchers
watch(() => props.initialCategory, (newValue) => {
  selectedCategory.value = newValue
})

watch(() => props.initialAudienceType, (newValue) => {
  selectedAudienceType.value = newValue
})

// Lifecycle
onMounted(() => {
  fetchCategories()
  fetchTemplates()

  // Set up keyboard event listeners
  if (props.enableKeyboardNavigation) {
    document.addEventListener('keydown', handleKeyboardNavigation)
  }
})

// Cleanup event listeners on unmount
onBeforeUnmount(() => {
  if (props.enableKeyboardNavigation) {
    document.removeEventListener('keydown', handleKeyboardNavigation)
  }
})
</script>

<style scoped>
.template-library {
  @apply max-w-7xl mx-auto px-4 py-8;
}

/* Header */
.library-header {
  @apply flex items-center justify-between mb-8;
}

.library-title {
  @apply text-3xl font-bold text-gray-900 dark:text-white;
}

.library-subtitle {
  @apply text-lg text-gray-600 dark:text-gray-400 mt-2;
}

.view-controls {
  @apply flex items-center;
}

.view-toggle {
  @apply flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden;
}

.view-btn {
  @apply px-3 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors;
}

.view-btn.active {
  @apply bg-blue-600 text-white;
}

/* Search and Filters */
.search-filters {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8;
}

.search-bar {
  @apply mb-6;
}

.search-input {
  @apply w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400;
}

.search-icon {
  @apply absolute left-3 top-3.5 h-5 w-5 text-gray-400;
}

.filter-controls {
  @apply grid grid-cols-1 md:grid-cols-4 gap-4;
}

.filter-group {
  @apply flex flex-col;
}

.filter-label {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300 mb-2;
}

.filter-select {
  @apply w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white;
}

.clear-filters-btn {
  @apply w-full px-4 py-2 text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors;
}

/* States */
.loading-state {
  @apply flex flex-col items-center justify-center py-16;
}

.loading-spinner {
  @apply w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-4;
}

.error-state {
  @apply flex items-center justify-center py-16;
}

.error-content {
  @apply text-center;
}

.error-icon {
  @apply w-12 h-12 text-red-500 mx-auto mb-4;
}

.error-title {
  @apply text-xl font-semibold text-gray-900 dark:text-white mb-2;
}

.error-message {
  @apply text-gray-600 dark:text-gray-400 mb-4;
}

.retry-btn {
  @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors;
}

/* Templates */
.templates-container {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700;
}

.active-filters {
  @apply px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center flex-wrap gap-2;
}

.filters-label {
  @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.filter-tags {
  @apply flex items-center gap-2 flex-1;
}

.filter-tag {
  @apply inline-flex items-center px-3 py-1 text-sm bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full;
}

.filter-remove {
  @apply ml-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200;
}

.clear-all-btn {
  @apply px-3 py-1 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 underline;
}

.templates-grid {
  @apply grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 p-6;
}

.templates-list {
  @apply p-6;
}

/* Empty State */
.empty-state {
  @apply flex items-center justify-center py-16 px-6;
}

.empty-content {
  @apply text-center;
}

.empty-icon {
  @apply w-12 h-12 text-gray-400 mx-auto mb-4;
}

.empty-title {
  @apply text-xl font-semibold text-gray-900 dark:text-white mb-2;
}

.empty-message {
  @apply text-gray-600 dark:text-gray-400 mb-6;
}

.reset-btn {
  @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors;
}

/* Pagination */
.pagination-container {
  @apply px-6 py-4 border-t border-gray-200 dark:border-gray-700;
}

.pagination {
  @apply flex items-center justify-between;
}

.pagination-btn {
  @apply px-4 py-2 text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
}

.pagination-numbers {
  @apply flex items-center gap-2;
}

.pagination-number {
  @apply px-3 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-700 rounded transition-colors;
}

.pagination-number.active {
  @apply bg-blue-600 text-white;
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
  .template-library {
    @apply text-gray-300;
  }

  .library-title {
    @apply text-gray-100;
  }

  .search-input::placeholder {
    @apply text-gray-400;
  }
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .template-library {
    @apply px-2;
  }

  .library-header {
    @apply flex-col gap-4;
  }

  .filter-controls {
    @apply grid-cols-1;
  }

  .templates-grid {
    @apply grid-cols-1 sm:grid-cols-2 lg:grid-cols-3;
  }

  .pagination {
    @apply flex-col gap-4;
  }

  .pagination-numbers {
    @apply order-first;
  }
}
</style>