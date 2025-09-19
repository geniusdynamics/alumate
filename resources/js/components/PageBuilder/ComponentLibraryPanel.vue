<template>
  <div class="component-library-panel">
    <!-- Panel Header -->
    <div class="panel-header">
      <div class="header-content">
        <h3 class="panel-title">Component Library</h3>
        <p class="panel-subtitle">Drag components to add them to your page</p>
      </div>
      <button @click="$emit('close')" class="close-btn" aria-label="Close component library">
        <Icon name="x" class="w-5 h-5" />
      </button>
    </div>

    <!-- Search and Filters -->
    <div class="search-section">
      <div class="search-input-container">
        <Icon name="search" class="search-icon" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search components..."
          class="search-input"
          @input="handleSearch"
        />
        <button
          v-if="searchQuery"
          @click="clearSearch"
          class="clear-search-btn"
          aria-label="Clear search"
        >
          <Icon name="x" class="w-4 h-4" />
        </button>
      </div>

      <!-- Category Filters -->
      <div class="category-filters">
        <button
          v-for="category in categories"
          :key="category.id"
          @click="selectCategory(category.id)"
          :class="['category-btn', { 'active': selectedCategory === category.id }]"
          :title="category.description"
        >
          <Icon :name="category.icon" class="w-4 h-4" />
          <span>{{ category.name }}</span>
          <span class="component-count">{{ category.count }}</span>
        </button>
      </div>
    </div>

    <!-- Component Grid -->
    <div class="components-section">
      <div v-if="isLoading" class="loading-state">
        <Icon name="spinner" class="w-6 h-6 animate-spin" />
        <p>Loading components...</p>
      </div>

      <div v-else-if="filteredComponents.length === 0" class="empty-state">
        <Icon name="component" class="w-12 h-12 text-gray-400" />
        <p class="empty-title">No components found</p>
        <p class="empty-subtitle">
          {{ searchQuery ? 'Try adjusting your search terms' : 'No components available in this category' }}
        </p>
      </div>

      <div v-else class="components-grid">
        <div
          v-for="component in filteredComponents"
          :key="component.id"
          class="component-card"
          :draggable="true"
          @dragstart="handleDragStart($event, component)"
          @click="selectComponent(component)"
          :class="{ 'selected': selectedComponentId === component.id }"
        >
          <!-- Component Preview -->
          <div class="component-preview">
            <img
              v-if="component.preview_image"
              :src="component.preview_image"
              :alt="component.name"
              class="preview-image"
              @error="handleImageError"
            />
            <div v-else class="preview-placeholder">
              <Icon :name="component.icon || 'component'" class="w-8 h-8" />
            </div>
            
            <!-- Component Actions -->
            <div class="component-actions">
              <button
                @click.stop="previewComponent(component)"
                class="action-btn preview-btn"
                :title="`Preview ${component.name}`"
              >
                <Icon name="eye" class="w-4 h-4" />
              </button>
              <button
                @click.stop="addComponent(component)"
                class="action-btn add-btn"
                :title="`Add ${component.name}`"
              >
                <Icon name="plus" class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Component Info -->
          <div class="component-info">
            <h4 class="component-name">{{ component.name }}</h4>
            <p class="component-description">{{ component.description }}</p>
            
            <!-- Component Meta -->
            <div class="component-meta">
              <span class="category-tag">{{ component.category }}</span>
              <span v-if="component.is_premium" class="premium-tag">Premium</span>
              <span class="usage-count">{{ component.usage_count || 0 }} uses</span>
            </div>

            <!-- Component Features -->
            <div v-if="component.features" class="component-features">
              <span
                v-for="feature in component.features.slice(0, 3)"
                :key="feature"
                class="feature-tag"
              >
                {{ feature }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Component Preview Modal -->
    <ComponentPreviewModal
      v-if="showPreviewModal"
      :component="previewingComponent"
      @close="closePreview"
      @add="addComponent"
    />

    <!-- Component Details Panel -->
    <div v-if="selectedComponent" class="component-details">
      <div class="details-header">
        <h4>{{ selectedComponent.name }}</h4>
        <button @click="selectedComponent = null" class="close-details-btn">
          <Icon name="x" class="w-4 h-4" />
        </button>
      </div>
      
      <div class="details-content">
        <p class="details-description">{{ selectedComponent.description }}</p>
        
        <!-- Configuration Options -->
        <div v-if="selectedComponent.config_schema" class="config-options">
          <h5>Configuration Options</h5>
          <div class="config-list">
            <div
              v-for="option in selectedComponent.config_schema"
              :key="option.key"
              class="config-item"
            >
              <span class="config-name">{{ option.label }}</span>
              <span class="config-type">{{ option.type }}</span>
            </div>
          </div>
        </div>

        <!-- Usage Examples -->
        <div v-if="selectedComponent.examples" class="usage-examples">
          <h5>Usage Examples</h5>
          <div class="examples-list">
            <div
              v-for="example in selectedComponent.examples"
              :key="example.id"
              class="example-item"
            >
              <span class="example-name">{{ example.name }}</span>
              <button
                @click="applyExample(example)"
                class="apply-example-btn"
              >
                Apply
              </button>
            </div>
          </div>
        </div>

        <!-- Add Component Button -->
        <div class="details-actions">
          <button
            @click="addComponent(selectedComponent)"
            class="add-component-btn"
          >
            <Icon name="plus" class="w-4 h-4" />
            Add to Page
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Icon from '../ui/Icon.vue'
import ComponentPreviewModal from './ComponentPreviewModal.vue'
import { useComponentLibrary } from '../../composables/useComponentLibrary'
import { useToast } from '../../composables/useToast'

// Emits
const emit = defineEmits<{
  close: []
  componentSelected: [component: any]
  componentDragStart: [component: any]
}>()

// Composables
const { showToast } = useToast()
const {
  components,
  categories,
  isLoading,
  searchQuery,
  selectedCategory,
  loadComponents,
  searchComponents,
  getComponentsByCategory
} = useComponentLibrary()

// State
const selectedComponentId = ref<string | null>(null)
const selectedComponent = ref<any>(null)
const showPreviewModal = ref(false)
const previewingComponent = ref<any>(null)

// Computed
const filteredComponents = computed(() => {
  let filtered = components.value

  // Filter by category
  if (selectedCategory.value && selectedCategory.value !== 'all') {
    filtered = filtered.filter(c => c.category === selectedCategory.value)
  }

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(c => 
      c.name.toLowerCase().includes(query) ||
      c.description.toLowerCase().includes(query) ||
      c.category.toLowerCase().includes(query)
    )
  }

  return filtered
})

// Methods
const handleSearch = async () => {
  if (searchQuery.value.length > 2) {
    await searchComponents(searchQuery.value)
  } else if (searchQuery.value.length === 0) {
    await loadComponents()
  }
}

const clearSearch = () => {
  searchQuery.value = ''
  loadComponents()
}

const selectCategory = async (categoryId: string) => {
  selectedCategory.value = categoryId
  if (categoryId === 'all') {
    await loadComponents()
  } else {
    await getComponentsByCategory(categoryId)
  }
}

const selectComponent = (component: any) => {
  selectedComponentId.value = component.id
  selectedComponent.value = component
  emit('componentSelected', component)
}

const handleDragStart = (event: DragEvent, component: any) => {
  if (!event.dataTransfer) return
  
  event.dataTransfer.setData('application/json', JSON.stringify(component))
  event.dataTransfer.effectAllowed = 'copy'
  
  emit('componentDragStart', component)
}

const addComponent = (component: any) => {
  try {
    // Emit component selection for the page builder to handle
    emit('componentSelected', component)
    showToast(`${component.name} added to page`, 'success')
    
    // Track usage
    trackComponentUsage(component.id)
  } catch (error) {
    console.error('Failed to add component:', error)
    showToast('Failed to add component', 'error')
  }
}

const previewComponent = (component: any) => {
  previewingComponent.value = component
  showPreviewModal.value = true
}

const closePreview = () => {
  showPreviewModal.value = false
  previewingComponent.value = null
}

const applyExample = (example: any) => {
  if (selectedComponent.value) {
    const componentWithExample = {
      ...selectedComponent.value,
      config: example.config
    }
    addComponent(componentWithExample)
  }
}

const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  img.style.display = 'none'
}

const trackComponentUsage = async (componentId: string) => {
  try {
    await fetch(`/api/components/${componentId}/track-usage`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
  } catch (error) {
    console.error('Failed to track component usage:', error)
  }
}

// Lifecycle
onMounted(async () => {
  await loadComponents()
})

// Watch for category changes to update counts
watch(components, () => {
  // Update category counts
  categories.value.forEach(category => {
    if (category.id === 'all') {
      category.count = components.value.length
    } else {
      category.count = components.value.filter(c => c.category === category.id).length
    }
  })
}, { deep: true })
</script>

<style scoped>
.component-library-panel {
  @apply h-full flex flex-col bg-white dark:bg-gray-800;
}

.panel-header {
  @apply flex items-start justify-between p-4 border-b border-gray-200 dark:border-gray-700;
}

.header-content {
  @apply flex-1;
}

.panel-title {
  @apply text-lg font-semibold text-gray-900 dark:text-white;
}

.panel-subtitle {
  @apply text-sm text-gray-500 dark:text-gray-400 mt-1;
}

.close-btn {
  @apply p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700;
}

.search-section {
  @apply p-4 border-b border-gray-200 dark:border-gray-700;
}

.search-input-container {
  @apply relative mb-4;
}

.search-icon {
  @apply absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400;
}

.search-input {
  @apply w-full pl-10 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}

.clear-search-btn {
  @apply absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300;
}

.category-filters {
  @apply flex flex-wrap gap-2;
}

.category-btn {
  @apply flex items-center space-x-2 px-3 py-2 text-sm font-medium rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors;
}

.category-btn.active {
  @apply bg-blue-50 dark:bg-blue-900 border-blue-300 dark:border-blue-600 text-blue-700 dark:text-blue-300;
}

.component-count {
  @apply text-xs bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-400 px-1.5 py-0.5 rounded-full;
}

.components-section {
  @apply flex-1 overflow-y-auto p-4;
}

.loading-state {
  @apply flex flex-col items-center justify-center py-12 text-gray-500 dark:text-gray-400;
}

.empty-state {
  @apply flex flex-col items-center justify-center py-12 text-center;
}

.empty-title {
  @apply text-lg font-medium text-gray-900 dark:text-white mt-4;
}

.empty-subtitle {
  @apply text-sm text-gray-500 dark:text-gray-400 mt-2;
}

.components-grid {
  @apply grid grid-cols-1 gap-4;
}

.component-card {
  @apply border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800 hover:shadow-md transition-shadow cursor-pointer;
}

.component-card.selected {
  @apply ring-2 ring-blue-500 border-blue-500;
}

.component-preview {
  @apply relative aspect-video bg-gray-100 dark:bg-gray-700;
}

.preview-image {
  @apply w-full h-full object-cover;
}

.preview-placeholder {
  @apply w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500;
}

.component-actions {
  @apply absolute top-2 right-2 flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity;
}

.component-card:hover .component-actions {
  @apply opacity-100;
}

.action-btn {
  @apply p-1.5 bg-white dark:bg-gray-800 rounded-md shadow-sm border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white;
}

.component-info {
  @apply p-3;
}

.component-name {
  @apply font-medium text-gray-900 dark:text-white text-sm;
}

.component-description {
  @apply text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2;
}

.component-meta {
  @apply flex items-center space-x-2 mt-2;
}

.category-tag {
  @apply text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-1 rounded;
}

.premium-tag {
  @apply text-xs bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-300 px-2 py-1 rounded;
}

.usage-count {
  @apply text-xs text-gray-500 dark:text-gray-400;
}

.component-features {
  @apply flex flex-wrap gap-1 mt-2;
}

.feature-tag {
  @apply text-xs bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 px-1.5 py-0.5 rounded;
}

.component-details {
  @apply border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900;
}

.details-header {
  @apply flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700;
}

.details-content {
  @apply p-4;
}

.details-description {
  @apply text-sm text-gray-600 dark:text-gray-400 mb-4;
}

.config-options h5,
.usage-examples h5 {
  @apply text-sm font-medium text-gray-900 dark:text-white mb-2;
}

.config-list,
.examples-list {
  @apply space-y-2 mb-4;
}

.config-item,
.example-item {
  @apply flex items-center justify-between text-sm;
}

.config-name,
.example-name {
  @apply text-gray-700 dark:text-gray-300;
}

.config-type {
  @apply text-gray-500 dark:text-gray-400 text-xs;
}

.apply-example-btn {
  @apply text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-xs;
}

.details-actions {
  @apply pt-4 border-t border-gray-200 dark:border-gray-700;
}

.add-component-btn {
  @apply w-full flex items-center justify-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium transition-colors;
}
</style>